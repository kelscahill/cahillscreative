<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\Database;

use RuntimeException;
use WPForms\Pro\Admin\Entries\Import\EntryImporter;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractDatabaseSource;

/**
 * WPForms database import source.
 *
 * @since 1.10.1
 */
class WPFormsDbSource extends AbstractDatabaseSource {

	/**
	 * Return the plugin slug for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {

		return 'wpforms';
	}

	/**
	 * Return the human-readable plugin name.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'WPForms';
	}

	/**
	 * Return WPForms form fields for the source form.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 *
	 * @throws RuntimeException If the form has no supported fields.
	 */
	public function get_fields(): array {

		if ( ! $this->source_form_id ) {
			return [];
		}

		$form_fields = wpforms_get_form_fields( $this->source_form_id, EntryImporter::get_supported_destination_fields() );

		if ( empty( $form_fields ) ) {
			throw new RuntimeException( esc_html__( 'Form has no supported fields.', 'wpforms' ) );
		}

		$fields = [];

		foreach ( $form_fields as $field_id => $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}

			$label = self::get_field_label( $field );

			$has_subfields = self::field_has_subfields( $field );

			if ( ! $has_subfields ) {
				$fields[] = [
					'key'   => $field_id,
					'label' => $label,
				];

				continue;
			}

			self::append_field_subfields( $field, $fields );
		}

		if ( empty( $fields ) ) {
			throw new RuntimeException( esc_html__( 'Form has no supported fields.', 'wpforms' ) );
		}

		return $fields;
	}

	/**
	 * Return total importable entry count for the source form.
	 *
	 * Only counts published entries (status = '').
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total(): int {

		$entry_handler = wpforms()->obj( 'entry' );

		if ( ! $entry_handler || empty( $this->source_form_id ) ) {
			return 0;
		}

		return (int) $entry_handler->get_entries(
			[
				'form_id' => $this->source_form_id,
				'status'  => [ '' ],
			],
			true
		);
	}

	/**
	 * Process a chunk of up to N entries starting at the given offset cursor.
	 *
	 * Only processes published entries (status = '').
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Last processed entry ID (0 to start from the beginning).
	 * @param int $number_entries Number of entries to process in this chunk.
	 *
	 * @return array
	 */
	public function process_chunk( int $cursor, int $number_entries ): array {

		$entry_handler = wpforms()->obj( 'entry' );

		if (
			! $entry_handler
			|| empty( $this->source_form_id )
			|| ! wpforms_current_user_can( 'edit_entries_form_single', $this->source_form_id )
		) {
			return [
				'entries'     => [],
				'next_cursor' => 0,
				'errors'      => [],
			];
		}

		$entries = $entry_handler->get_entries(
			[
				'form_id' => $this->source_form_id,
				'status'  => [ '' ],
				'offset'  => $cursor,
				'number'  => $number_entries,
			]
		);

		if ( empty( $entries ) ) {
			return [
				'entries'     => [],
				'next_cursor' => $cursor,
				'errors'      => [],
			];
		}

		$form_fields    = wpforms_get_form_fields( $this->source_form_id, EntryImporter::get_supported_destination_fields() );
		$import_entries = [];

		foreach ( $entries as $entry ) {
			$import_entries[] = $this->prepare_entry( $entry, $form_fields );
		}

		$next_cursor  = $cursor + count( $entries );
		$this->cursor = $next_cursor;

		return [
			'entries'     => $import_entries,
			'next_cursor' => $next_cursor,
			'errors'      => [],
		];
	}

	/**
	 * Prepares an entry for import by decoding fields and extracting relevant data.
	 *
	 * @since 1.10.1
	 *
	 * @param object $entry       The entry object containing the data to process.
	 * @param array  $form_fields The associated form fields configuration.
	 *
	 * @return array
	 */
	private function prepare_entry( object $entry, array $form_fields ): array {

		$entry_fields = wpforms_decode( $entry->fields );
		$source_entry = [
			'id'           => (int) $entry->entry_id,
			'fields'       => [],
			'fields_extra' => [],
			'meta'         => $this->build_entry_meta( $entry ),
		];

		if ( $entry_fields ) {
			foreach ( $entry_fields as $field_data ) {
				if ( ! isset( $field_data['type'], $field_data['id'] ) ) {
					continue;
				}

				$field_id       = $field_data['id'];
				$field_settings = $form_fields[ $field_id ] ?? [];

				if ( isset( $field_data['quiz_result'] ) ) {
					$source_entry['fields_extra'][ $field_id ]['quiz_result'] = $field_data['quiz_result'];
				}

				/**
				 * Filter the source field data before subfield extraction.
				 *
				 * Allows addons to enrich a source field's data, e.g. by copying keys
				 * from a nested structure (Map's `location` dict) to the top level so
				 * the importer's subfield extraction can read them. Return the modified
				 * field data array.
				 *
				 * @since 1.10.1
				 *
				 * @param array $field_data     Source entry field data.
				 * @param array $field_settings Source form field settings.
				 */
				$field_data = (array) apply_filters(
					'wpforms_pro_admin_entries_import_source_database_wpforms_db_source_prepare_entry_field',
					$field_data,
					$field_settings
				);

				if ( ! empty( $field_settings ) && self::field_has_subfields( $field_settings ) ) {
					$this->extract_subfield_values( $field_data, $field_settings, $source_entry['fields'] );

					continue;
				}

				$source_entry['fields'][ $field_id ] = $field_data['value'] ?? '';
			}
		}

		/**
		 * Filter a prepared source entry before it is handed off to the importer.
		 *
		 * Allows modifying field values, appending data to fields_extra, or adding
		 * custom meta-rows on a per-entry basis.
		 *
		 * @since 1.10.1
		 *
		 * @param array  $source_entry Prepared entry payload (id, fields, fields_extra, meta).
		 * @param object $entry        Source entry database row.
		 * @param array  $form_fields  Source form fields configuration.
		 */
		return (array) apply_filters(
			'wpforms_pro_admin_entries_import_source_database_wpforms_db_source_prepare_entry',
			$source_entry,
			$entry,
			$form_fields
		);
	}

	/**
	 * Extract subfield values for name, address, and date-time fields into the import fields array.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field_data     Decoded field data from the entry.
	 * @param array $field_settings Form field settings.
	 * @param array $import_fields  Reference to the import fields array being built.
	 *
	 * @return void
	 */
	private function extract_subfield_values( array $field_data, array $field_settings, array &$import_fields ): void {

		$subfields = [];

		self::append_field_subfields( $field_settings, $subfields );

		$combined_value        = $field_data['value'] ?? '';
		$has_distinct_subfield = $this->has_distinct_subfield_value( $subfields, $field_data, $combined_value );
		$used_combined_value   = false;

		foreach ( $subfields as $subfield_info ) {
			$subfield       = explode( '.', $subfield_info['key'], 2 )[1] ?? 'value';
			$subfield_value = $field_data[ $subfield ] ?? '';

			$import_fields[ $subfield_info['key'] ] = $this->resolve_subfield_value(
				$subfield_value,
				$combined_value,
				$has_distinct_subfield,
				$used_combined_value
			);
		}
	}

	/**
	 * Check if field data has any subfield value distinct from the combined value.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $subfields      List of subfield info arrays.
	 * @param array  $field_data     Decoded field data from the entry.
	 * @param string $combined_value The combined value field.
	 *
	 * @return bool
	 */
	private function has_distinct_subfield_value( array $subfields, array $field_data, string $combined_value ): bool {

		foreach ( $subfields as $subfield_info ) {
			$subfield       = explode( '.', $subfield_info['key'], 2 )[1] ?? 'value';
			$subfield_value = $field_data[ $subfield ] ?? '';

			if ( $subfield_value !== '' && $subfield_value !== $combined_value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve the value to use for a subfield during import.
	 *
	 * @since 1.10.1
	 *
	 * @param string $subfield_value        The actual subfield value from entry.
	 * @param string $combined_value        The combined value field.
	 * @param bool   $has_distinct_subfield Whether any subfield has a distinct value.
	 * @param bool   $used_combined_value   Reference flag tracking if combined value was used.
	 *
	 * @return string
	 */
	private function resolve_subfield_value(
		string $subfield_value,
		string $combined_value,
		bool $has_distinct_subfield,
		bool &$used_combined_value
	): string {

		// Use actual subfield value if it exists.
		if ( $subfield_value !== '' ) {
			// Mark combined as used if this value equals it (prevents duplication for simple format).
			if ( $subfield_value === $combined_value && ! $has_distinct_subfield ) {
				$used_combined_value = true;
			}

			return $subfield_value;
		}

		// Use combined value fallback only for simple format entries (no distinct subfield values).
		if ( ! $has_distinct_subfield && ! $used_combined_value && $combined_value !== '' ) {
			$used_combined_value = true;

			return $combined_value;
		}

		return '';
	}

	/**
	 * Fetch wpforms_entry_meta rows for the given source entry ID.
	 *
	 * @since 1.10.1
	 *
	 * @param int $entry_id Source entry ID.
	 *
	 * @return array<string, string> Meta type => data map.
	 */
	private function fetch_source_entry_meta( int $entry_id ): array {

		$entry_meta_handler = wpforms()->obj( 'entry_meta' );

		if ( ! $entry_meta_handler || ! $entry_id ) {
			return [];
		}

		$rows = $entry_meta_handler->get_meta( [ 'entry_id' => $entry_id ] );

		if ( empty( $rows ) ) {
			return [];
		}

		$meta = [];

		foreach ( $rows as $row ) {
			if ( empty( $row->type ) ) {
				continue;
			}

			$data = $row->data ?? '';

			if ( ! isset( $meta[ $row->type ] ) ) {
				$meta[ $row->type ] = $data;

				continue;
			}

			if ( ! is_array( $meta[ $row->type ] ) ) {
				$meta[ $row->type ] = [ $meta[ $row->type ] ];
			}

			$meta[ $row->type ][] = $data;
		}

		return $meta;
	}

	/**
	 * Build entry meta from a WPForms entry object.
	 *
	 * Copies all supported entry-table columns and all entry-meta table rows.
	 *
	 * @since 1.10.1
	 *
	 * @param object $source_entry WPForms entry row object.
	 *
	 * @return array
	 */
	protected function build_entry_meta( $source_entry ): array {

		$defaults = [
			'date'       => '',
			'ip_address' => '',
			'user_agent' => '',
			'user_uuid'  => '',
			'user_id'    => 0,
			'viewed'     => null,
			'starred'    => null,
		];

		$meta = array_intersect_key( wp_parse_args( (array) $source_entry, $defaults ), $defaults );

		$entry_id = (int) $source_entry->entry_id;

		return array_merge( $meta, $this->fetch_source_entry_meta( $entry_id ) );
	}

	/**
	 * Get a list of plugin forms available for import.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_forms(): array {

		global $wpdb;

		$entry = wpforms()->obj( 'entry' );

		if ( ! $entry ) {
			return [];
		}

		// Only return forms that have at least one entry.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			'SELECT p.ID AS form_id, p.post_title, COUNT( e.entry_id ) AS entry_count
			FROM ' . esc_sql( $wpdb->posts ) . ' AS p
			INNER JOIN ' . esc_sql( $entry->table_name ) . ' AS e ON p.ID = e.form_id AND e.status = ""
			WHERE p.post_type = "wpforms" AND p.post_status = "publish"
			GROUP BY p.ID, p.post_title
			HAVING entry_count > 0
			ORDER BY entry_count DESC, p.post_title'
		);

		if ( empty( $results ) ) {
			return [];
		}

		$forms = [];

		foreach ( $results as $row ) {
			$form_id     = (int) $row->form_id;
			$entry_count = (int) $row->entry_count;

			if ( ! wpforms_current_user_can( 'edit_entries_form_single', $form_id ) ) {
				continue;
			}

			$forms[] = [
				'id'               => $form_id,
				'title'            => $row->post_title,
				'entry_count'      => $entry_count,
				'disabled'         => $entry_count === 0,
				'form_entries_url' => add_query_arg(
					[
						'page'    => 'wpforms-entries',
						'view'    => 'list',
						'form_id' => $form_id,
					],
					admin_url( 'admin.php' )
				),
			];
		}

		return $forms;
	}

	/**
	 * Determine if a field has subfields based on its type and format.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Array containing field properties.
	 *
	 * @return bool
	 */
	public static function field_has_subfields( array $field ): bool {

		if ( empty( $field['type'] ) ) {
			return false;
		}

		if ( $field['type'] === 'address' ) {
			return true;
		}

		if ( $field['type'] === 'name' ) {
			return ! empty( $field['format'] ) && $field['format'] !== 'simple';
		}

		if ( $field['type'] === 'date-time' ) {
			return ! empty( $field['format'] ) && $field['format'] === 'date-time';
		}

		/**
		 * Filter whether a non-core field type carries subfields the importer should expand.
		 *
		 * Allows addons to register custom field types whose entry data is composed
		 * of multiple subfield values (e.g., Map field's name/address/latitude/longitude).
		 *
		 * @since 1.10.1
		 *
		 * @param bool  $has_subfields Default false for unknown types.
		 * @param array $field         Field settings.
		 */
		return (bool) apply_filters(
			'wpforms_pro_admin_entries_import_source_database_wpforms_db_source_field_has_subfields',
			false,
			$field
		);
	}

	/**
	 * Retrieves the label of a form field or generates a default label if none is set.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $field     Field settings or format.
	 * @param string $label_key Omit when you use field settings and pass `name` when using field format.
	 * @param string $subfield  Subfield slug.
	 *
	 * @return string
	 */
	public static function get_field_label( array $field, string $label_key = 'label', string $subfield = 'value' ): string {

		$field_id = ! empty( $field['id'] ) ? absint( $field['id'] ) : 0;

		$label = isset( $field[ $label_key ] ) && ! wpforms_is_empty_string( $field[ $label_key ] )
			? $field[ $label_key ]
			: sprintf( /* translators: %d - field ID. */
				__( 'Field %d', 'wpforms' ),
				$field_id
			);

		if ( $subfield === 'value' ) {
			return $label;
		}

		$subfield_labels = self::get_address_subfield_labels( $field );
		$subfield_label  = $subfield_labels[ $subfield ] ?? ucfirst( $subfield );

		return $label . ': ' . $subfield_label;
	}

	/**
	 * Retrieves subfields for a given field if applicable.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field              The field data to process.
	 * @param array $destination_fields Destination fields list.
	 *
	 * @return void
	 */
	public static function append_field_subfields( array $field, array &$destination_fields ): void {

		if ( ! self::field_has_subfields( $field ) ) {
			return;
		}

		if ( ! isset( $field['id'] ) || ! is_numeric( $field['id'] ) ) {
			return;
		}

		if ( in_array( $field['type'], [ 'name', 'date-time' ], true ) ) {
			if ( empty( $field['format'] ) ) {
				$destination_fields[] = [
					'key'   => $field['id'],
					'label' => self::get_field_label( $field ),
				];

				return;
			}

			$subfields = array_filter( array_map( 'trim', explode( '-', $field['format'] ) ) );

			foreach ( $subfields as $subfield ) {
				$destination_fields[] = [
					'key'   => "$field[id].$subfield",
					'label' => self::get_field_label( $field, 'label', $subfield ),
				];
			}

			return;
		}

		if ( $field['type'] === 'address' ) {
			self::append_address_subfields( $field, $destination_fields );

			return;
		}

		/**
		 * Filter the destination subfield rows for a non-core field type.
		 *
		 * Addons that register a non-core field type as subfield-bearing (via the
		 * `wpforms_pro_admin_entries_import_source_database_wpforms_db_source_field_has_subfields`
		 * filter) should return an array of subfield rows here. Each row must have
		 * `key` (in the `{field_id}.{subfield}` format) and `label`.
		 *
		 * @since 1.10.1
		 *
		 * @param array $subfields Subfield rows to append. Default empty array.
		 * @param array $field     Field settings.
		 */
		$subfields = (array) apply_filters(
			'wpforms_pro_admin_entries_import_source_database_wpforms_db_source_append_field_subfields',
			[],
			$field
		);

		foreach ( $subfields as $subfield ) {
			$destination_fields[] = $subfield;
		}
	}

	/**
	 * Appends address subfields to the given field with subfields.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field              The field data containing address configuration.
	 * @param array $destination_fields Destination fields list.
	 *
	 * @return void
	 */
	public static function append_address_subfields( array $field, array &$destination_fields ): void {

		$address_field_subfields = self::get_address_subfield_labels( $field );

		foreach ( $address_field_subfields as $subfield => $subfield_label ) {
			if ( empty( $field[ "{$subfield}_hide" ] ) ) {
				$destination_fields[] = [
					'key'   => "$field[id].$subfield",
					'label' => self::get_field_label( $field, 'label', $subfield ),
				];
			}
		}
	}

	/**
	 * Retrieves labels for the subfields of an address field.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field settings.
	 *
	 * @return array
	 */
	private static function get_address_subfield_labels( array $field ): array {

		if ( empty( $field['type'] ) || $field['type'] !== 'address' ) {
			return [];
		}

		$address_field_subfields = [
			'address1' => esc_html__( 'Address Line 1', 'wpforms' ),
			'address2' => esc_html__( 'Address Line 2', 'wpforms' ),
			'city'     => esc_html__( 'City', 'wpforms' ),
			'postal'   => esc_html__( 'Zip Code', 'wpforms' ),
			'state'    => esc_html__( 'State', 'wpforms' ),
		];

		if ( ! empty( $field['scheme'] ) && $field['scheme'] === 'international' ) {
			$address_field_subfields['postal']  = esc_html__( 'Postal Code', 'wpforms' );
			$address_field_subfields['state']   = esc_html__( 'State / Province / Region', 'wpforms' );
			$address_field_subfields['country'] = esc_html__( 'Country', 'wpforms' );
		}

		return $address_field_subfields;
	}
}
