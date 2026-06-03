<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\Database;

use WPForms\Pro\Admin\Entries\Import\Source\AbstractDatabaseSource;

/**
 * Gravity Forms database import source.
 *
 * @since 1.10.1
 */
class GravityFormsDbSource extends AbstractDatabaseSource {

	/**
	 * Required database tables (without prefix).
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	protected static $required_tables = [ 'gf_form', 'gf_form_meta', 'gf_entry', 'gf_entry_meta' ];

	/**
	 * Gravity Forms field types that are allowed to be imported.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const ALLOWED_FIELD_TYPES = [
		'address',
		'checkbox',
		'date',
		'email',
		'hidden',
		'image_choice',
		'multi_choice',
		'multiselect',
		'name',
		'number',
		'phone',
		'post_category',
		'post_content',
		'post_custom_field',
		'post_excerpt',
		'post_tags',
		'post_title',
		'radio',
		'select',
		'text',
		'textarea',
		'time',
		'website',
	];

	/**
	 * Gravity Forms field types that support multiple selections.
	 *
	 * These fields have an input array but should not be broken into subfields.
	 * Instead, their values are combined into newline-separated strings.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const MULTIPLE_FIELD_TYPES = [
		'checkbox',
		'image_choice',
		'multi_choice',
	];

	/**
	 * Multipart fields tracking.
	 *
	 * Maps field IDs to their type and subfield keys for merging during import.
	 * Multipart fields are stored as multiple meta-entries in Gravity Forms
	 * (checkbox choices, time parts, date parts) that we merge into a single value.
	 *
	 * Structure: [ field_id => [ 'type' => 'checkbox|time|date', 'subfields' => [...] ] ]
	 *
	 * @since 1.10.1
	 *
	 * @var array<string, array{type: string, subfields: array}>
	 */
	private $multi_part_fields = [];

	/**
	 * Choice value → text translations per choice field.
	 *
	 * Gravity Forms stores the choice value (not the text) in entry meta whenever
	 * a custom value is set or the field has Show Values enabled. The rest of the
	 * import pipeline matches against WPForms choice labels, so we translate the
	 * stored value back to the choice text before handing it off.
	 *
	 * Structure: [ field_id => [ stored_value => choice_text ] ].
	 *
	 * @since 1.10.1
	 *
	 * @var array<string, array<string, string>>
	 */
	private $choice_value_to_text_map = [];

	/**
	 * Return the plugin slug for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {

		return 'gravity-forms';
	}

	/**
	 * Return the human-readable plugin name.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'Gravity Forms';
	}

	/**
	 * Determine whether this source is available on the current site.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		return class_exists( 'GFForms' ) && $this->required_table_exist();
	}

	/**
	 * Return the total entry count across all Gravity Forms.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total_entry_count(): int {

		global $wpdb;

		$form_table  = $wpdb->prefix . 'gf_form';
		$entry_table = $wpdb->prefix . 'gf_entry';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			'SELECT COUNT( e.id )
			FROM ' . esc_sql( $entry_table ) . ' AS e
			INNER JOIN ' . esc_sql( $form_table ) . ' AS f ON f.id = e.form_id AND f.is_trash = 0
			WHERE e.status = "active"'
		);

		return (int) $count;
	}

	/**
	 * Return Gravity Forms form fields for the source form.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_fields(): array {

		if ( ! $this->source_form_id ) {
			return [];
		}

		static $fields = [];

		if ( ! empty( $fields ) ) {
			return $fields;
		}

		$this->multi_part_fields        = [];
		$this->choice_value_to_text_map = [];

		$form_meta = $this->get_form_meta();

		if ( empty( $form_meta['fields'] ) ) {
			return [];
		}

		foreach ( $form_meta['fields'] as $field ) {
			$this->process_field( $field, $fields );
			$this->register_choice_value_to_text_map( $field );
		}

		return $fields;
	}

	/**
	 * Process a single field and add it to the field array.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field  Field data from Gravity Forms.
	 * @param array $fields Fields array to append to (passed by reference).
	 */
	private function process_field( array $field, array &$fields ): void {

		$field_type = $field['type'] ?? '';

		if ( ! $this->is_allowed_field_type( $field_type ) ) {
			return;
		}

		if ( $this->is_time_field( $field ) ) {
			$this->add_multi_part_field( $field, $fields, 'time', $this->get_time_subfield_keys( $field ) );

			return;
		}

		if ( $this->is_date_field_with_subfields( $field ) ) {
			$this->add_multi_part_field( $field, $fields, 'date', $this->get_date_subfield_keys( $field ) );

			return;
		}

		if ( $this->is_multiple_field_type( $field_type ) ) {
			$this->add_multi_part_field( $field, $fields, 'checkbox', $this->get_checkbox_subfield_keys( $field ) );

			return;
		}

		if ( $this->has_subfields( $field ) ) {
			$this->append_subfields( $field, $fields );

			return;
		}

		$fields[] = [
			'key'   => (string) $field['id'],
			'label' => $this->get_field_label( $field ),
		];
	}

	/**
	 * Add a multipart field to the field array and track its subfields.
	 *
	 * Multipart fields have multiple meta-entries that we merge into a single value.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $field     Field data from Gravity Forms.
	 * @param array  $fields    Fields array to append to (passed by reference).
	 * @param string $type      Field type ('checkbox', 'time', or 'date').
	 * @param array  $subfields Subfield keys for merging values during import.
	 */
	private function add_multi_part_field( array $field, array &$fields, string $type, array $subfields ): void {

		$field_id = (string) $field['id'];

		$fields[] = [
			'key'   => $field_id,
			'label' => $this->get_field_label( $field ),
		];

		$this->multi_part_fields[ $field_id ] = [
			'type'      => $type,
			'subfields' => $subfields,
		];
	}

	/**
	 * Get subfield keys for a checkbox field.
	 *
	 * Gravity Forms checkbox fields store each choice as a separate subfield
	 * with keys like "5.1", "5.2", "5.3".
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return array Array of subfield keys.
	 */
	private function get_checkbox_subfield_keys( array $field ): array {

		$keys = [];

		if ( empty( $field['inputs'] ) || ! is_array( $field['inputs'] ) ) {
			return $keys;
		}

		foreach ( $field['inputs'] as $input ) {
			if ( ! empty( $input['isHidden'] ) ) {
				continue;
			}

			$keys[] = (string) ( $input['id'] ?? '' );
		}

		return array_filter( $keys );
	}

	/**
	 * Return total importable entry count for the source form.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total(): int {

		if ( ! $this->source_form_id ) {
			return 0;
		}

		global $wpdb;

		$entry_table = $wpdb->prefix . 'gf_entry';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( id )
				FROM ' . esc_sql( $entry_table ) . '
				WHERE form_id = %d
				AND status = "active"',
				$this->source_form_id
			)
		);

		return (int) $count;
	}

	/**
	 * Process a chunk of up to N entries starting at the given offset cursor.
	 *
	 * @since 1.10.1
	 *
	 * @param int $cursor         Number of already-processed entries (offset).
	 * @param int $number_entries Number of entries to process in this chunk.
	 *
	 * @return array
	 */
	public function process_chunk( int $cursor, int $number_entries ): array {

		$result = [
			'entries'     => [],
			'next_cursor' => $cursor,
			'errors'      => [],
		];

		if ( ! $this->source_form_id ) {
			return $result;
		}

		$entries = $this->get_entries( $cursor, $number_entries );

		if ( empty( $entries ) ) {
			return $result;
		}

		$field_keys = $this->get_field_keys();

		foreach ( $entries as $entry ) {
			$entry_id   = (int) $entry->id;
			$entry_data = $this->get_entry_field_values( $entry_id, $field_keys );

			$result['entries'][] = [
				'id'     => $entry_id,
				'fields' => $entry_data,
				'meta'   => $this->build_entry_meta( $entry ),
			];
		}

		$next_cursor           = $cursor + count( $entries );
		$this->cursor          = $next_cursor;
		$result['next_cursor'] = $next_cursor;

		return $result;
	}

	/**
	 * Get entries for the current form starting at the given offset.
	 *
	 * @since 1.10.1
	 *
	 * @param int $offset         Number of entries to skip (offset).
	 * @param int $number_entries Number of entries to retrieve.
	 *
	 * @return array
	 */
	private function get_entries( int $offset, int $number_entries ): array {

		global $wpdb;

		$entry_table = $wpdb->prefix . 'gf_entry';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, date_created, ip, user_agent, created_by, source_url, is_read, is_starred
				FROM ' . esc_sql( $entry_table ) . '
				WHERE form_id = %d
				AND status = "active"
				ORDER BY id
				LIMIT %d OFFSET %d',
				$this->source_form_id,
				$number_entries,
				$offset
			)
		);
	}

	/**
	 * Get all field keys that we're importing.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	private function get_field_keys(): array {

		$fields   = $this->get_fields();
		$all_keys = [ array_column( $fields, 'key' ) ];

		foreach ( $this->multi_part_fields as $multi_part ) {
			$subfields = $multi_part['subfields'] ?? [];

			$all_keys[] = array_filter( array_values( $subfields ) );
		}

		return array_unique( array_merge( ...$all_keys ) );
	}

	/**
	 * Get field values for an entry.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $entry_id   Entry ID.
	 * @param array $field_keys Array of field keys to retrieve.
	 *
	 * @return array Associative array of field values keyed by field key.
	 */
	private function get_entry_field_values( int $entry_id, array $field_keys ): array {

		if ( empty( $field_keys ) ) {
			return [];
		}

		$raw_values = $this->fetch_entry_meta( $entry_id, $field_keys );
		$entry_data = $this->process_multi_part_fields( $raw_values );

		foreach ( $raw_values as $meta_key => $value ) {
			if ( $this->is_multi_part_subfield_key( $meta_key ) ) {
				continue;
			}

			$entry_data[ $meta_key ] = $this->maybe_convert_multiselect_value( $value );
		}

		foreach ( $entry_data as $field_id => $value ) {
			$entry_data[ $field_id ] = $this->translate_choice_value( (string) $field_id, (string) $value );
		}

		return $entry_data;
	}

	/**
	 * Fetch entry meta-values from the database.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $entry_id   Entry ID.
	 * @param array $field_keys Array of field keys to retrieve.
	 *
	 * @return array Raw values keyed by meta key.
	 */
	private function fetch_entry_meta( int $entry_id, array $field_keys ): array {

		global $wpdb;

		$entry_meta_table = $wpdb->prefix . 'gf_entry_meta';
		$raw_values       = [];
		$field_keys_sql   = wpforms_wpdb_prepare_in( $field_keys );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT meta_key, meta_value
				FROM ' . esc_sql( $entry_meta_table ) . '
				WHERE entry_id = %d
				AND meta_key IN ( ' . $field_keys_sql . ' )', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$entry_id
			)
		);

		foreach ( $results as $row ) {
			$value = $row->meta_value;

			if ( $value === '' || $value === null ) {
				continue;
			}

			$raw_values[ $row->meta_key ] = $value;
		}

		return $raw_values;
	}

	/**
	 * Process multipart fields (checkbox, time, and date) from raw values.
	 *
	 * @since 1.10.1
	 *
	 * @param array $raw_values Raw values from entry meta.
	 *
	 * @return array Entry data with merged field values.
	 */
	private function process_multi_part_fields( array $raw_values ): array {

		$entry_data = [];

		foreach ( $this->multi_part_fields as $field_id => $multi_part ) {
			$type      = $multi_part['type'] ?? '';
			$subfields = $multi_part['subfields'] ?? [];

			switch ( $type ) {
				case 'checkbox':
					$value = $this->combine_checkbox_value( $raw_values, $subfields );
					break;

				case 'time':
					$value = $this->combine_time_value( $raw_values, $subfields );
					break;

				case 'date':
					$value = $this->combine_date_value( $raw_values, $subfields );
					break;

				default:
					$value = '';
			}

			if ( $value !== '' ) {
				$entry_data[ $field_id ] = $value;
			}
		}

		return $entry_data;
	}

	/**
	 * Combine checkbox subfield values into a newline-separated string.
	 *
	 * Gravity Forms stores each checked choice as a separate meta-entry.
	 * We combine all checked values into a single newline-separated string.
	 *
	 * @since 1.10.1
	 *
	 * @param array $raw_values    All raw values from the entry.
	 * @param array $subfield_keys Array of subfield keys for this checkbox field.
	 *
	 * @return string Combined checkbox value with newline-separated choices.
	 */
	private function combine_checkbox_value( array $raw_values, array $subfield_keys ): string {

		$values = [];

		foreach ( $subfield_keys as $key ) {
			if ( isset( $raw_values[ $key ] ) && $raw_values[ $key ] !== '' ) {
				$values[] = $raw_values[ $key ];
			}
		}

		return implode( "\n", $values );
	}

	/**
	 * Check if a meta-key is a multipart subfield key (checkbox, time, or date).
	 *
	 * @since 1.10.1
	 *
	 * @param string $meta_key Meta key to check.
	 *
	 * @return bool
	 */
	private function is_multi_part_subfield_key( string $meta_key ): bool {

		foreach ( $this->multi_part_fields as $multi_part ) {
			$subfields = ! empty( $multi_part['subfields'] ) ? array_values( $multi_part['subfields'] ) : [];

			if ( in_array( $meta_key, $subfields, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Combine time subfield values into a single time string.
	 *
	 * @since 1.10.1
	 *
	 * @param array $raw_values    All raw values from the entry.
	 * @param array $subfield_keys Array with 'hour', 'minute', 'ampm' keys.
	 *
	 * @return string Combined time value (e.g., "10:30 am").
	 */
	private function combine_time_value( array $raw_values, array $subfield_keys ): string {

		$hour   = $raw_values[ $subfield_keys['hour'] ] ?? '';
		$minute = $raw_values[ $subfield_keys['minute'] ] ?? '';
		$ampm   = $raw_values[ $subfield_keys['ampm'] ] ?? '';

		if ( $hour === '' && $minute === '' ) {
			return '';
		}

		// Format as "H:MM am/pm" for the DateTimePreparer to parse.
		$time = sprintf( '%s:%02d', $hour, (int) $minute );

		if ( $ampm !== '' ) {
			$time .= ' ' . $ampm;
		}

		return $time;
	}

	/**
	 * Combine date subfield values into a single date string.
	 *
	 * @since 1.10.1
	 *
	 * @param array $raw_values    All raw values from the entry.
	 * @param array $subfield_keys Array with 'month', 'day', 'year' keys.
	 *
	 * @return string Combined date value in ISO format (e.g., "2026-04-30").
	 */
	private function combine_date_value( array $raw_values, array $subfield_keys ): string {

		$month = $raw_values[ $subfield_keys['month'] ] ?? '';
		$day   = $raw_values[ $subfield_keys['day'] ] ?? '';
		$year  = $raw_values[ $subfield_keys['year'] ] ?? '';

		if ( $month === '' && $day === '' && $year === '' ) {
			return '';
		}

		// Format as ISO date "YYYY-MM-DD" for the DateTimePreparer to parse.
		return sprintf( '%04d-%02d-%02d', (int) $year, (int) $month, (int) $day );
	}

	/**
	 * Build entry meta from a Gravity Forms entry row.
	 *
	 * @since 1.10.1
	 *
	 * @param object $source_entry Gravity Forms gf_entry row.
	 *
	 * @return array
	 */
	protected function build_entry_meta( $source_entry ): array {

		$meta = [];

		if ( ! empty( $source_entry->date_created ) ) {
			$meta['date'] = $source_entry->date_created;
		}

		if ( ! empty( $source_entry->ip ) ) {
			$meta['ip_address'] = $source_entry->ip;
		}

		if ( ! empty( $source_entry->user_agent ) ) {
			$meta['user_agent'] = $source_entry->user_agent;
		}

		if ( ! empty( $source_entry->created_by ) ) {
			$meta['user_id'] = (int) $source_entry->created_by;
		}

		$meta['viewed']  = isset( $source_entry->is_read ) ? (int) $source_entry->is_read : 0;
		$meta['starred'] = isset( $source_entry->is_starred ) ? (int) $source_entry->is_starred : 0;

		if ( ! empty( $source_entry->source_url ) ) {
			$meta['page_url'] = $source_entry->source_url;
		}

		return $meta;
	}

	/**
	 * Get a list of Gravity Forms available for import.
	 *
	 * Returns forms that have at least one active entry.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_forms(): array {

		global $wpdb;

		$form_table  = $wpdb->prefix . 'gf_form';
		$entry_table = $wpdb->prefix . 'gf_entry';

		// Only return forms that have at least one entry.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			'SELECT f.id, f.title, COUNT( e.id ) AS entry_count
			FROM ' . esc_sql( $form_table ) . ' AS f
			INNER JOIN ' . esc_sql( $entry_table ) . ' AS e ON e.form_id = f.id AND e.status = "active"
			WHERE f.is_trash = 0
			GROUP BY f.id, f.title
			HAVING entry_count > 0
			ORDER BY entry_count DESC, f.title'
		);

		if ( empty( $results ) ) {
			return [];
		}

		$forms = [];

		foreach ( $results as $row ) {
			$entry_count = (int) $row->entry_count;
			$form_title  = wpforms_is_empty_string( trim( $row->title ) )
				? sprintf( /* translators: %d - form ID. */
					__( 'Form %d', 'wpforms' ),
					$row->id
				)
				: $row->title;

			$forms[] = [
				'id'               => (int) $row->id,
				'title'            => $form_title,
				'entry_count'      => $entry_count,
				'disabled'         => $entry_count === 0,
				'form_entries_url' => add_query_arg(
					[
						'page' => 'gf_entries',
						'id'   => (int) $row->id,
					],
					admin_url( 'admin.php' )
				),
			];
		}

		return $forms;
	}

	/**
	 * Get form metadata from the gf_form_meta table.
	 *
	 * @since 1.10.1
	 *
	 * @return array|null
	 */
	private function get_form_meta(): ?array {

		global $wpdb;

		$meta_table = $wpdb->prefix . 'gf_form_meta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$display_meta = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT display_meta FROM ' . esc_sql( $meta_table ) . ' WHERE form_id = %d',
				$this->source_form_id
			)
		);

		if ( empty( $display_meta ) ) {
			return null;
		}

		$form = json_decode( $display_meta, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// Try unserializing for legacy data.
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$form = maybe_unserialize( $display_meta );
		}

		return is_array( $form ) ? $form : null;
	}

	/**
	 * Determine if a field type is allowed to be imported.
	 *
	 * @since 1.10.1
	 *
	 * @param string $type Field type from Gravity Forms.
	 *
	 * @return bool
	 */
	private function is_allowed_field_type( string $type ): bool {

		$is_allowed = in_array( $type, self::ALLOWED_FIELD_TYPES, true );

		/**
		 * Filter whether a Gravity Forms field type is allowed to be imported.
		 *
		 * @since 1.10.1
		 *
		 * @param bool   $is_allowed Whether the field type is allowed.
		 * @param string $type       Gravity Forms field type.
		 */
		return (bool) apply_filters( 'wpforms_pro_admin_entries_import_source_database_gravity_forms_db_source_is_allowed_field_type', $is_allowed, $type );
	}

	/**
	 * Determine if a field has subfields (input array).
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return bool
	 */
	private function has_subfields( array $field ): bool {

		// Email fields with confirmation enabled expose two UI inputs in the form
		// definition, but only a single value is stored per entry. Treat as single-row.
		if ( ( $field['type'] ?? '' ) === 'email' ) {
			return false;
		}

		return ! empty( $field['inputs'] ) && is_array( $field['inputs'] );
	}

	/**
	 * Determine if a field type supports multiple selections.
	 *
	 * @since 1.10.1
	 *
	 * @param string $type Field type from Gravity Forms.
	 *
	 * @return bool
	 */
	private function is_multiple_field_type( string $type ): bool {

		return in_array( $type, self::MULTIPLE_FIELD_TYPES, true );
	}

	/**
	 * Determine if a field is a time field.
	 *
	 * Time fields in Gravity Forms have inputs for hour, minute, and am/pm,
	 * but we want to combine them into a single value during import.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return bool
	 */
	private function is_time_field( array $field ): bool {

		return ! empty( $field['type'] ) && $field['type'] === 'time';
	}

	/**
	 * Get subfield keys for a time field.
	 *
	 * Gravity Forms time fields store hour, minute, and am/pm as separate subfields
	 * with keys like "5.1", "5.2", "5.3".
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return array Array with 'hour', 'minute', 'ampm' keys mapped to subfield keys.
	 */
	private function get_time_subfield_keys( array $field ): array {

		$keys = [
			'hour'   => '',
			'minute' => '',
			'ampm'   => '',
		];

		if ( empty( $field['inputs'] ) || ! is_array( $field['inputs'] ) ) {
			return $keys;
		}

		foreach ( $field['inputs'] as $input ) {
			$label = strtolower( $input['label'] ?? '' );
			$key   = (string) ( $input['id'] ?? '' );

			if ( strpos( $label, 'hour' ) !== false ) {
				$keys['hour'] = $key;
			} elseif ( strpos( $label, 'minute' ) !== false ) {
				$keys['minute'] = $key;
			} elseif ( strpos( $label, 'am' ) !== false || strpos( $label, 'pm' ) !== false ) {
				$keys['ampm'] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Determine if a field is a date field with subfields (datefield or datedropdown).
	 *
	 * Datepicker type stores as a single value and doesn't need combining.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return bool
	 */
	private function is_date_field_with_subfields( array $field ): bool {

		if ( ( $field['type'] ?? '' ) !== 'date' ) {
			return false;
		}

		// Only datefield and datedropdown have subfields.
		$date_type = $field['dateType'] ?? 'datepicker';

		return in_array( $date_type, [ 'datefield', 'datedropdown' ], true );
	}

	/**
	 * Get subfield keys for a date field.
	 *
	 * Gravity Forms date fields (datefield/datedropdown) store month, day, and year
	 * as separate subfields with keys like "5.1", "5.2", "5.3".
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return array Array with 'month', 'day', 'year' keys mapped to subfield keys.
	 */
	private function get_date_subfield_keys( array $field ): array {

		$keys = [
			'month' => '',
			'day'   => '',
			'year'  => '',
		];

		if ( empty( $field['inputs'] ) || ! is_array( $field['inputs'] ) ) {
			return $keys;
		}

		foreach ( $field['inputs'] as $input ) {
			$label = strtolower( $input['label'] ?? '' );
			$key   = (string) ( $input['id'] ?? '' );

			if ( strpos( $label, 'month' ) !== false ) {
				$keys['month'] = $key;
			} elseif ( strpos( $label, 'day' ) !== false ) {
				$keys['day'] = $key;
			} elseif ( strpos( $label, 'year' ) !== false ) {
				$keys['year'] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Get the label for a field, with fallback to a default.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 *
	 * @return string
	 */
	private function get_field_label( array $field ): string {

		if ( ! empty( $field['label'] ) && trim( $field['label'] ) !== '' ) {
			return $field['label'];
		}

		if ( ! empty( $field['adminLabel'] ) && trim( $field['adminLabel'] ) !== '' ) {
			return $field['adminLabel'];
		}

		return sprintf( /* translators: %d - field ID. */
			__( 'Field %d', 'wpforms' ),
			$field['id'] ?? 0
		);
	}

	/**
	 * Append subfields for fields like name, address, time.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field  Field data from Gravity Forms.
	 * @param array $fields Fields array to append to (passed by reference).
	 */
	private function append_subfields( array $field, array &$fields ): void {

		$field_label = $this->get_field_label( $field );

		foreach ( $field['inputs'] as $input ) {
			if ( ! empty( $input['isHidden'] ) ) {
				continue;
			}

			$input_label = $input['label'] ?? '';

			$fields[] = [
				'key'   => (string) $input['id'],
				'label' => $input_label ? $field_label . ': ' . $input_label : $field_label,
			];
		}
	}

	/**
	 * Convert multiselect dropdown values from a JSON array to a newline-separated format.
	 *
	 * Gravity Forms stores multiselect dropdown values as JSON arrays like:
	 * ["First Choice", "Second Choice", "Third Choice"]
	 *
	 * WPForms expects them as newline-separated values:
	 * First Choice
	 * Second Choice
	 * Third Choice
	 *
	 * @since 1.10.1
	 *
	 * @param string $value The field value from the database.
	 *
	 * @return string The converted value or original if not a JSON array.
	 */
	private function maybe_convert_multiselect_value( string $value ): string {

		if ( ! wpforms_is_json( $value ) ) {
			return $value;
		}

		$decoded = json_decode( $value, true );

		if ( ! is_array( $decoded ) ) {
			return $value;
		}

		return implode( "\n", array_filter( array_map( 'strval', $decoded ) ) );
	}

	/**
	 * Register the choice value → text translation map for a choice-based field.
	 *
	 * Gravity Forms saves the choice value (not the text) to entry meta whenever
	 * a custom value is set on a choice or the field has Show Values enabled.
	 * Building a value → text map lets us translate the stored value back to the
	 * choice label so the importer can resolve it against WPForms choices.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field data from Gravity Forms.
	 */
	private function register_choice_value_to_text_map( array $field ): void {

		$field_id = (string) ( $field['id'] ?? '' );
		$choices  = $field['choices'] ?? [];

		if ( $field_id === '' || empty( $choices ) || ! is_array( $choices ) ) {
			return;
		}

		if ( ! $this->is_choice_based_field( $field['type'] ?? '' ) ) {
			return;
		}

		$map = $this->build_choice_value_to_text_map( $choices );

		if ( ! empty( $map ) ) {
			$this->choice_value_to_text_map[ $field_id ] = $map;
		}
	}

	/**
	 * Build a value → text map for an array of Gravity Forms choices.
	 *
	 * Skips choices that have no value stored or whose value already equals the
	 * text — neither case needs translation.
	 *
	 * @since 1.10.1
	 *
	 * @param array $choices Gravity Forms choices for a single field.
	 *
	 * @return array<string, string>
	 */
	private function build_choice_value_to_text_map( array $choices ): array {

		$map = [];

		foreach ( $choices as $choice ) {
			if ( ! is_array( $choice ) ) {
				continue;
			}

			$value = (string) ( $choice['value'] ?? '' );
			$text  = (string) ( $choice['text'] ?? '' );

			if ( $value === '' || $text === '' || $value === $text ) {
				continue;
			}

			$map[ $value ] = $text;
		}

		return $map;
	}

	/**
	 * Determine if a field type stores its submission as a choice value.
	 *
	 * @since 1.10.1
	 *
	 * @param string $type Field type from Gravity Forms.
	 *
	 * @return bool
	 */
	private function is_choice_based_field( string $type ): bool {

		return in_array( $type, [ 'radio', 'select', 'multiselect', 'checkbox', 'multi_choice', 'image_choice' ], true );
	}

	/**
	 * Translate stored choice values back to their choice text.
	 *
	 * Handles single values as well as newline-separated lists produced by
	 * checkbox combination and multiselect conversion. Values without a
	 * registered translation pass through unchanged.
	 *
	 * @since 1.10.1
	 *
	 * @param string $field_id Field ID the value belongs to.
	 * @param string $value    Stored value (single or newline-separated).
	 *
	 * @return string Translated value (or the original value when no map applies).
	 */
	private function translate_choice_value( string $field_id, string $value ): string {

		$map = $this->choice_value_to_text_map[ $field_id ] ?? [];

		if ( empty( $map ) || $value === '' ) {
			return $value;
		}

		if ( strpos( $value, "\n" ) === false ) {
			return $map[ $value ] ?? $value;
		}

		$parts = explode( "\n", $value );

		$translated = array_map(
			static function ( string $part ) use ( $map ): string {

				return $map[ $part ] ?? $part;
			},
			$parts
		);

		return implode( "\n", $translated );
	}
}
