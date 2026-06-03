<?php

namespace WPForms\Pro\Admin\Entries\Import\Source\Database;

use WPForms\Pro\Admin\Entries\Import\Source\AbstractDatabaseSource;

/**
 * Ninja Forms database import source.
 *
 * Ninja Forms stores submissions as WordPress custom post type `nf_sub`
 * with field values in postmeta using keys like `_field_{field_id}`.
 *
 * @since 1.10.1
 */
class NinjaFormsDbSource extends AbstractDatabaseSource {

	/**
	 * Required database tables (without prefix).
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	protected static $required_tables = [ 'nf3_forms', 'nf3_fields', 'nf3_field_meta', 'posts', 'postmeta' ];

	/**
	 * Ninja Forms field types that are allowed to be imported.
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const ALLOWED_FIELD_TYPES = [
		'address',
		'checkbox',
		'city',
		'confirm',
		'date',
		'email',
		'file_upload',
		'firstname',
		'hidden',
		'lastname',
		'listcheckbox',
		'listcountry',
		'listimage',
		'listmultiselect',
		'listradio',
		'listselect',
		'liststate',
		'number',
		'phone',
		'starrating',
		'textarea',
		'textbox',
		'zip',
	];

	/**
	 * Ninja Forms field types that have options (choices).
	 *
	 * @since 1.10.1
	 *
	 * @var string[]
	 */
	private const OPTION_FIELD_TYPES = [
		'listcheckbox',
		'listmultiselect',
		'listselect',
		'listradio',
		'liststate',
		'listcountry',
		'listimage',
	];

	/**
	 * Return the plugin slug for this source.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {

		return 'ninja-forms';
	}

	/**
	 * Return the human-readable plugin name.
	 *
	 * @since 1.10.1
	 *
	 * @return string
	 */
	public function get_name(): string {

		return 'Ninja Forms';
	}

	/**
	 * Return the total entry count across all Ninja Forms.
	 *
	 * @since 1.10.1
	 *
	 * @return int
	 */
	public function get_total_entry_count(): int {

		global $wpdb;

		$forms_table = $wpdb->prefix . 'nf3_forms';

		// Only count entries that belong to existing forms.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			'SELECT COUNT( p.ID )
			FROM ' . esc_sql( $forms_table ) . ' AS f
			INNER JOIN ' . esc_sql( $wpdb->postmeta ) . ' AS pm
				ON CAST( pm.meta_value AS UNSIGNED ) = f.id
				AND pm.meta_key = "_form_id"
			INNER JOIN ' . esc_sql( $wpdb->posts ) . ' AS p
				ON p.ID = pm.post_id
				AND p.post_type = "nf_sub"
				AND p.post_status = "publish"'
		);

		return (int) $count;
	}

	/**
	 * Return Ninja Forms form fields for the source form.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_fields(): array {

		if ( ! $this->source_form_id ) {
			return [];
		}

		global $wpdb;

		$fields_table = $wpdb->prefix . 'nf3_fields';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, type, label AS field_label, `key` AS field_key
				FROM ' . esc_sql( $fields_table ) . ' WHERE parent_id = %d
				ORDER BY `order`',
				$this->source_form_id
			)
		);

		if ( empty( $results ) ) {
			return [];
		}

		$fields = [];

		foreach ( $results as $row ) {
			if ( ! $this->is_allowed_field_type( $row->type ) ) {
				continue;
			}

			$fields[] = [
				'key'   => (string) $row->id,
				'label' => $this->get_field_label( $row ),
			];
		}

		return $fields;
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( p.ID )
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->postmeta ) . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "_form_id"
				WHERE p.post_type = "nf_sub"
				AND p.post_status = "publish"
				AND pm.meta_value = %d',
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

		$submissions = $this->get_submissions( $cursor, $number_entries );

		if ( empty( $submissions ) ) {
			return $result;
		}

		$field_ids   = $this->get_form_field_ids();
		$options_map = $this->get_field_options_map();

		// Merge single checkbox labels into options_map as "1" => label.
		foreach ( $this->get_single_checkbox_labels() as $field_id => $label ) {
			$options_map[ $field_id ] = [ '1' => $label ];
		}

		foreach ( $submissions as $submission ) {
			$submission_id = (int) $submission->ID;

			$entry_data = $this->get_submission_field_values( $submission_id, $field_ids, $options_map );

			$result['entries'][] = [
				'id'     => $submission_id,
				'fields' => $entry_data,
				'meta'   => $this->build_entry_meta( $submission ),
			];
		}

		$next_cursor           = $cursor + count( $submissions );
		$this->cursor          = $next_cursor;
		$result['next_cursor'] = $next_cursor;

		return $result;
	}

	/**
	 * Get submissions for the current form starting at the given offset.
	 *
	 * @since 1.10.1
	 *
	 * @param int $offset         Number of entries to skip (offset).
	 * @param int $number_entries Number of entries to retrieve.
	 *
	 * @return array
	 */
	private function get_submissions( int $offset, int $number_entries ): array {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.ID, p.post_date_gmt, p.post_author
				FROM ' . esc_sql( $wpdb->posts ) . ' AS p
				INNER JOIN ' . esc_sql( $wpdb->postmeta ) . ' AS pm ON pm.post_id = p.ID AND pm.meta_key = "_form_id"
				WHERE p.post_type = "nf_sub"
				AND p.post_status = "publish"
				AND pm.meta_value = %d
				ORDER BY p.ID
				LIMIT %d OFFSET %d',
				$this->source_form_id,
				$number_entries,
				$offset
			)
		);
	}

	/**
	 * Get all field IDs for the current form.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	private function get_form_field_ids(): array {

		static $field_ids = null;

		if ( $field_ids !== null ) {
			return $field_ids;
		}

		global $wpdb;

		$fields_table = $wpdb->prefix . 'nf3_fields';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM ' . esc_sql( $fields_table ) . ' WHERE parent_id = %d',
				$this->source_form_id
			)
		);

		$field_ids = array_map( 'intval', $results );

		return $field_ids;
	}

	/**
	 * Get labels for single checkbox fields.
	 *
	 * Ninja Forms store single checkbox values as "1" (checked) or "0" (unchecked).
	 * This method returns the field labels so we can convert "1" to the actual label.
	 * Results are cached for the lifetime of the request.
	 *
	 * @since 1.10.1
	 *
	 * @return array<int, string> Field ID => label.
	 */
	private function get_single_checkbox_labels(): array {

		static $labels = null;

		if ( $labels !== null ) {
			return $labels;
		}

		global $wpdb;

		$labels       = [];
		$fields_table = $wpdb->prefix . 'nf3_fields';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, label, `key`
				FROM ' . esc_sql( $fields_table ) . '
				WHERE parent_id = %d AND type = %s',
				$this->source_form_id,
				'checkbox'
			)
		);

		if ( ! $results ) {
			return $labels;
		}

		foreach ( $results as $row ) {
			$label = trim( $row->label );

			// Fallback to key if label is empty.
			if ( $label === '' ) {
				$label = trim( $row->key );
			}

			$field_id = (int) $row->id;

			// Fallback to generic label if both are empty.
			if ( $label === '' ) {
				$label = sprintf( /* translators: %d - field ID. */
					__( 'Field %d', 'wpforms' ),
					$field_id
				);
			}

			$labels[ $field_id ] = $label;
		}

		return $labels;
	}

	/**
	 * Get field options mapping value to label for fields with choices.
	 *
	 * @since 1.10.1
	 *
	 * @return array<int, array<string, string>> Field ID => [value => label].
	 */
	private function get_field_options_map(): array {

		static $options_map = null;

		if ( $options_map !== null ) {
			return $options_map;
		}

		global $wpdb;

		$options_map  = [];
		$fields_table = $wpdb->prefix . 'nf3_fields';
		$meta_table   = $wpdb->prefix . 'nf3_field_meta';

		$option_types_sql = wpforms_wpdb_prepare_in( self::OPTION_FIELD_TYPES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT f.id AS field_id, m.value AS options
				FROM ' . esc_sql( $fields_table ) . ' AS f
				INNER JOIN ' . esc_sql( $meta_table ) . ' AS m
					ON m.parent_id = f.id AND m.`key` = "options"
					WHERE f.parent_id = %d
						AND f.type IN ( ' . $option_types_sql . ' )', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$this->source_form_id
			)
		);

		if ( ! $results ) {
			return $options_map;
		}

		foreach ( $results as $row ) {
			$options = maybe_unserialize( $row->options );

			if ( ! is_array( $options ) ) {
				continue;
			}

			$field_map = [];

			foreach ( $options as $option ) {
				if ( is_array( $option ) && isset( $option['value'], $option['label'] ) ) {
					$field_map[ (string) $option['value'] ] = (string) $option['label'];
				}
			}

			if ( ! empty( $field_map ) ) {
				$options_map[ (int) $row->field_id ] = $field_map;
			}
		}

		return $options_map;
	}

	/**
	 * Fetch all field postmeta for a submission in a single query.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $submission_id Post ID of the submission.
	 * @param array $field_ids     Array of field IDs to retrieve.
	 *
	 * @return array<string, mixed> Meta key => unserialized meta value.
	 */
	private function get_submission_raw_meta( int $submission_id, array $field_ids ): array {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT meta_key, meta_value FROM ' . esc_sql( $wpdb->postmeta ) . ' WHERE post_id = %d AND meta_key LIKE %s',
				$submission_id,
				'\_field\_%'
			)
		);

		$allowed_keys = array_map(
			static function ( $id ) {

				return '_field_' . $id;
			},
			$field_ids
		);

		$meta = [];

		foreach ( $results as $row ) {
			if ( in_array( $row->meta_key, $allowed_keys, true ) ) {
				$meta[ $row->meta_key ] = maybe_unserialize( $row->meta_value );
			}
		}

		return $meta;
	}

	/**
	 * Get field values for a submission.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $submission_id Post ID of the submission.
	 * @param array $field_ids     Array of field IDs to retrieve.
	 * @param array $options_map   Field options mapping value to label.
	 *
	 * @return array Associative array of field values keyed by field ID.
	 */
	private function get_submission_field_values( int $submission_id, array $field_ids, array $options_map ): array {

		$entry_data = [];
		$raw_meta   = $this->get_submission_raw_meta( $submission_id, $field_ids );

		foreach ( $field_ids as $field_id ) {
			$value = $raw_meta[ '_field_' . $field_id ] ?? '';

			if ( $value === '' || $value === false ) {
				continue;
			}

			if ( is_array( $value ) ) {
				if ( isset( $value['date'] ) || isset( $value['hour'] ) ) {
					$entry_data[ (string) $field_id ] = $this->stringify_date_time_value( $value );

					continue;
				}

				$entry_data[ (string) $field_id ] = $this->stringify_multi_value_field( $value, $options_map[ $field_id ] ?? [] );

				continue;
			}

			$value = (string) $value;

			if ( isset( $options_map[ $field_id ][ $value ] ) ) {
				$value = $options_map[ $field_id ][ $value ];
			}

			$entry_data[ (string) $field_id ] = $value;
		}

		return $entry_data;
	}

	/**
	 * Stringify multi-value field (checkboxes, multi-select) to newline-separated labels.
	 *
	 * @since 1.10.1
	 *
	 * @param array $values        Array of selected values.
	 * @param array $field_options Field options mapping value to label.
	 *
	 * @return string Newline-separated labels.
	 */
	private function stringify_multi_value_field( array $values, array $field_options ): string {

		$labels = [];

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}

			$value = (string) $value;

			$labels[] = $field_options[ $value ] ?? $value;
		}

		return implode( "\n", $labels );
	}

	/**
	 * Stringify a date/time array value.
	 *
	 * Ninja Forms stores date/time fields as arrays with keys: date, hour, minute, ampm.
	 *
	 * @since 1.10.1
	 *
	 * @param array $value Date/time array.
	 *
	 * @return string
	 */
	private function stringify_date_time_value( array $value ): string {

		$value = wp_parse_args(
			$value,
			[
				'date'   => '',
				'hour'   => '',
				'minute' => '',
				'ampm'   => '',
			]
		);

		$date   = $value['date'];
		$hour   = $value['hour'];
		$minute = $value['minute'];
		$ampm   = $value['ampm'];

		$result = $date;

		if ( $hour !== '' && $minute !== '' ) {
			$result .= ' ' . $hour . ':' . $minute;

			if ( $ampm !== '' ) {
				$result .= ' ' . $ampm;
			}
		}

		return $result;
	}

	/**
	 * Build entry meta from a Ninja Forms submission WP post-row.
	 *
	 * @since 1.10.1
	 *
	 * @param object $source_entry Ninja Forms WP post row (ID, post_date_gmt, post_author).
	 *
	 * @return array
	 */
	protected function build_entry_meta( $source_entry ): array {

		$meta = [];

		if ( ! empty( $source_entry->post_date_gmt ) && $source_entry->post_date_gmt !== '0000-00-00 00:00:00' ) {
			$meta['date'] = $source_entry->post_date_gmt;
		}

		if ( ! empty( $source_entry->post_author ) ) {
			$meta['user_id'] = (int) $source_entry->post_author;
		}

		return $meta;
	}

	/**
	 * Get a list of Ninja Forms available for import.
	 *
	 * Returns forms that have at least one submission.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public function get_forms(): array {

		global $wpdb;

		$forms_table = $wpdb->prefix . 'nf3_forms';

		// Only return forms that have at least one important submission.
		// Ninja Forms stores submissions as CPT 'nf_sub' with _form_id in postmeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			'SELECT f.id, f.title, s.entry_count AS entry_count
			FROM ' . esc_sql( $forms_table ) . ' AS f
			INNER JOIN (
				SELECT CAST( pm.meta_value AS UNSIGNED ) AS form_id, COUNT( p.ID ) AS entry_count
				FROM ' . esc_sql( $wpdb->postmeta ) . ' AS pm
				INNER JOIN ' . esc_sql( $wpdb->posts ) . ' AS p
					ON p.ID = pm.post_id
					AND p.post_type = "nf_sub"
					AND p.post_status = "publish"
				WHERE pm.meta_key = "_form_id"
				GROUP BY pm.meta_value
			) AS s ON s.form_id = f.id
			WHERE s.entry_count > 0
			ORDER BY entry_count DESC, f.title'
		);

		if ( empty( $results ) ) {
			return [];
		}

		$forms = [];

		foreach ( $results as $row ) {
			$entry_count = (int) $row->entry_count;
			$form_title  = wpforms_is_empty_string( trim( $row->title ) )
				? sprintf( /* translators: %d – form ID. */
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
						'page'    => 'nf-submissions',
						'form_id' => (int) $row->id,
					],
					admin_url( 'admin.php' )
				),
			];
		}

		return $forms;
	}

	/**
	 * Determine if a field type should be allowed during import.
	 *
	 * @since 1.10.1
	 *
	 * @param string $type Field type from Ninja Forms.
	 *
	 * @return bool
	 */
	private function is_allowed_field_type( string $type ): bool {

		$is_allowed = in_array( $type, self::ALLOWED_FIELD_TYPES, true );

		/**
		 * Filter whether a Ninja Forms field type is allowed to be imported.
		 *
		 * @since 1.10.1
		 *
		 * @param bool   $is_allowed Whether the field type is allowed.
		 * @param string $type       Ninja Forms field type.
		 */
		return (bool) apply_filters( 'wpforms_pro_admin_entries_import_source_database_ninja_forms_db_source_is_allowed_field_type', $is_allowed, $type );
	}

	/**
	 * Get the label for a field, with fallback to a default.
	 *
	 * @since 1.10.1
	 *
	 * @param object $row Field row from database with field_label property.
	 *
	 * @return string
	 */
	private function get_field_label( object $row ): string {

		$row->field_label = trim( $row->field_label );
		$row->field_key   = trim( $row->field_key );

		if ( ! wpforms_is_empty_string( $row->field_label ) ) {
			return $row->field_label;
		}

		if ( ! wpforms_is_empty_string( $row->field_key ) ) {
			return $row->field_key;
		}

		return sprintf( /* translators: %d - field ID. */
			__( 'Field %d', 'wpforms' ),
			$row->id
		);
	}

	/**
	 * Determine whether this source is available on the current site.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		return class_exists( 'Ninja_Forms' ) && $this->required_table_exist();
	}
}
