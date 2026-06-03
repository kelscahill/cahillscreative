<?php

namespace WPForms\Pro\Admin\Entries\Import;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use WPForms\Pro\Admin\Entries\Import\Source\AbstractSource;
use WPForms\Pro\Admin\Entries\Import\Source\Database\Plugins;
use WPForms\Pro\Admin\Entries\Import\Source\Database\WPFormsDbSource;
use WPForms\Pro\Admin\Entries\Import\Source\File\Files;

/**
 * Handles entry import logic and field mapping.
 *
 * @since 1.10.1
 */
class EntryImporter {

	/**
	 * Minimum acceptable entry timestamp (WordPress launch: 2003-05-27 00:00:00 UTC).
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	private const MIN_ENTRY_TIMESTAMP = 1053993600;

	/**
	 * MySQL DATETIME format used for entry date columns.
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	private const ENTRY_DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Form ID.
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	private $form_id;

	/**
	 * Source identifier (e.g., 'wpforms', 'csv', 'gravity-forms').
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	private $source_identifier;

	/**
	 * Failed entries CSV writer.
	 *
	 * @since 1.10.1
	 *
	 * @var FailedEntriesCsv
	 */
	private $failed_entries_csv;

	/**
	 * Field preparers registry (class names).
	 *
	 * @since 1.10.1
	 *
	 * @var array<string, string>
	 */
	private $default_preparers = [
		'email'         => FieldPreparer\EmailPreparer::class,
		'url'           => FieldPreparer\UrlPreparer::class,
		'phone'         => FieldPreparer\PhonePreparer::class,
		'select'        => FieldPreparer\ChoicesPreparer::class,
		'radio'         => FieldPreparer\ChoicesPreparer::class,
		'checkbox'      => FieldPreparer\CheckboxPreparer::class,
		'date-time'     => FieldPreparer\DateTimePreparer::class,
		'address'       => FieldPreparer\AddressPreparer::class,
		'name'          => FieldPreparer\NamePreparer::class,
		'number'        => FieldPreparer\NumberPreparer::class,
		'number-slider' => FieldPreparer\NumberPreparer::class,
	];

	/**
	 * Instantiated preparer instances.
	 *
	 * @since 1.10.1
	 *
	 * @var array<string, FieldPreparer\FieldPreparerInterface>
	 */
	private $preparer_instances = [];

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 *
	 * @param int              $form_id            Form ID.
	 * @param string           $source_identifier  Source identifier.
	 * @param FailedEntriesCsv $failed_entries_csv Failed entries CSV writer.
	 */
	public function __construct( int $form_id, string $source_identifier, FailedEntriesCsv $failed_entries_csv ) {

		$this->form_id            = $form_id;
		$this->source_identifier  = $source_identifier;
		$this->failed_entries_csv = $failed_entries_csv;
	}

	/**
	 * Return an instantiated source object for the given identifier.
	 *
	 * @since 1.10.1
	 *
	 * @param string $source_type Source type identifier: a file extension (e.g. 'csv') or plugin slug (e.g. 'cf7').
	 * @param array  $source_args Constructor arguments for the source class.
	 *
	 * @return AbstractSource
	 *
	 * @throws InvalidArgumentException When no source class is registered for the given identifier.
	 */
	public static function get_source( string $source_type, array $source_args = [] ): AbstractSource {

		$source = Files::get_by_extension( $source_type ) ?? Plugins::get_by_slug( $source_type );

		if ( $source === null ) {
			throw new InvalidArgumentException(
				esc_html( sprintf( /* translators: %s - source identifier string. */ __( 'No import source registered for identifier "%s".', 'wpforms' ), $source_type ) )
			);
		}

		if ( empty( $source_args ) ) {
			return $source;
		}

		$class = get_class( $source );

		return new $class( ...$source_args );
	}

	/**
	 * Get the list of supported destination field types for entry import.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 */
	public static function get_supported_destination_fields(): array {

		/**
		 * Filter the list of supported destination field types for entry import.
		 *
		 * @since 1.10.1
		 *
		 * @param array $field_types Array of supported field type slugs.
		 */
		return (array) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_get_supported_destination_fields',
			[
				'address',
				'checkbox',
				'date-time',
				'email',
				'hidden',
				'name',
				'number',
				'number-slider',
				'password',
				'phone',
				'radio',
				'rating',
				'richtext',
				'select',
				'text',
				'textarea',
				'url',
			]
		);
	}

	/**
	 * Retrieve the destination fields for the form.
	 *
	 * @since 1.10.1
	 *
	 * @return array
	 *
	 * @throws RuntimeException If the form has no supported fields.
	 */
	public function get_destination_fields(): array {

		$form_fields = wpforms_get_form_fields( $this->form_id, self::get_supported_destination_fields() );

		$no_fields_error = __( 'Form has no supported fields.', 'wpforms' );

		if ( empty( $form_fields ) ) {
			throw new RuntimeException( esc_html( $no_fields_error ) );
		}

		$fields             = $this->append_subfields( $form_fields );
		$destination_fields = $this->append_choices( $fields, $form_fields );

		if ( empty( $destination_fields ) ) {
			throw new RuntimeException( esc_html( $no_fields_error ) );
		}

		return $destination_fields;
	}

	/**
	 * Build destination fields with all subfields expanded for Name, Address, and Date/Time.
	 *
	 * Always shows all available subfields regardless of field format.
	 *
	 * @since 1.10.1
	 *
	 * @param array $form_fields Array of form field settings.
	 *
	 * @return array
	 */
	private function append_subfields( array $form_fields ): array {

		$fields = [];

		foreach ( $form_fields as $field_id => $field ) {
			if ( empty( $field['type'] ) || ! isset( $field['id'] ) ) {
				continue;
			}

			$type = $field['type'];

			// Name field: always show first, middle, last.
			if ( $type === 'name' ) {
				foreach ( [ 'first', 'middle', 'last' ] as $subfield ) {
					$fields[] = [
						'key'   => "$field_id.$subfield",
						'label' => WPFormsDbSource::get_field_label( $field, 'label', $subfield ),
					];
				}

				continue;
			}

			// Date/Time field: always show date and time.
			if ( $type === 'date-time' ) {
				foreach ( [ 'date', 'time' ] as $subfield ) {
					$fields[] = [
						'key'   => "$field_id.$subfield",
						'label' => WPFormsDbSource::get_field_label( $field, 'label', $subfield ),
					];
				}

				continue;
			}

			// Address field: show all subfields based on scheme.
			if ( $type === 'address' ) {
				WPFormsDbSource::append_address_subfields( $field, $fields );

				continue;
			}

			/**
			 * Allow addons to expand a custom field type into multiple destination subfields.
			 *
			 * Return an array of destination subfield rows (each with `key` and `label`).
			 * Returning an empty array keeps the field as a single destination column.
			 *
			 * @since 1.10.1
			 *
			 * @param array $expanded Destination subfields. Default empty array.
			 * @param array $field    Field settings.
			 */
			$expanded = (array) apply_filters(
				'wpforms_pro_admin_entries_import_entry_importer_append_subfields_for_type',
				[],
				$field
			);

			if ( ! empty( $expanded ) ) {
				$fields = array_merge( $fields, $expanded );

				continue;
			}

			// Other fields: add as single field.
			$fields[] = [
				'key'   => $field_id,
				'label' => WPFormsDbSource::get_field_label( $field ),
			];
		}

		return $fields;
	}

	/**
	 * Append choices from the provided fields to destination fields.
	 *
	 * For multiple-selection fields (checkbox, multi-select), expands each choice
	 * into a separate mappable field.
	 *
	 * @since 1.10.1
	 *
	 * @param array $fields      Array of fields to process.
	 * @param array $form_fields Array of form field settings.
	 *
	 * @return array
	 */
	private function append_choices( array $fields, array $form_fields ): array {

		$destination_fields = [];

		foreach ( $fields as $field ) {
			if ( ! isset( $field['key'], $field['label'] ) ) {
				continue;
			}

			$field_id       = $field['key'];
			$field_settings = $form_fields[ $field_id ] ?? [];

			if ( ! $this->is_multiple_field( $field_settings ) ) {
				$destination_fields[] = $field;

				continue;
			}

			$choices = $this->get_multiple_field_choices( $field_settings );

			if ( empty( $choices ) ) {
				$destination_fields[] = $field;

				continue;
			}

			if ( ! isset( $field_settings['id'] ) || ! is_numeric( $field_settings['id'] ) ) {
				$destination_fields[] = $field;

				continue;
			}

			foreach ( $choices as $choice_id => $choice ) {
				$destination_fields[] = [
					'key'   => "$field_id.$choice_id",
					'label' => isset( $choice['label'] ) && ! wpforms_is_empty_string( $choice['label'] )
						? $field['label'] . ': ' . $choice['label']
						/* translators: %s - choice number. */
						: sprintf( esc_html__( 'Choice %s', 'wpforms' ), $choice_id ),
				];
			}
		}

		return $destination_fields;
	}

	/**
	 * Import entries based on the provided mapping and update the result array.
	 *
	 * @since 1.10.1
	 *
	 * @param array $results   Reference to an array that will store the import results.
	 * @param array $form_data Form data and settings.
	 * @param array $map       The mapping configuration for the import process.
	 *
	 * @return int The number of successful entries imported.
	 */
	public function import_entries( array &$results, array $form_data, array $map ): int {

		$imported          = 0;
		$meta_map          = [];
		$results['errors'] = $results['errors'] ?? [];

		if ( isset( $map['entry_date'] ) ) {
			$meta_map['entry_date'] = $map['entry_date'];

			unset( $map['entry_date'] );
		}

		foreach ( $results['entries'] as $source_entry ) {
			$entry_id = $this->process_source_entry( $source_entry, $form_data, $map, $meta_map, $results['errors'] );

			if ( $entry_id ) {
				++$imported;
			}
		}

		return $imported;
	}

	/**
	 * Process a single source entry: validate, prepare fields, create the destination entry, and collect errors.
	 *
	 * @since 1.10.1
	 *
	 * @param array $source_entry Source entry data.
	 * @param array $form_data    Destination form data.
	 * @param array $map          Field mapping configuration.
	 * @param array $meta_map     Meta fields mapping configuration.
	 * @param array $errors       Errors array, passed by reference.
	 *
	 * @return int Created entry ID, or 0 when the entry was skipped.
	 */
	private function process_source_entry( array $source_entry, array $form_data, array $map, array $meta_map, array &$errors ): int {

		$source_entry_id = $source_entry['id'] ?? 0;

		if ( empty( $source_entry['fields'] ) || array_filter( $source_entry['fields'], 'strlen' ) === [] ) {
			$errors[] = $this->make_source_error( $source_entry_id, esc_html__( 'Empty Entry. The record was skipped.', 'wpforms' ) );

			return 0;
		}

		$entry_errors = [];
		$fields       = $this->prepare_fields( $source_entry['fields'], $map, $form_data, $entry_errors );

		if ( empty( $fields ) ) {
			$errors[] = $this->make_source_error( $source_entry_id, esc_html__( 'No fields were mapped. The record was skipped.', 'wpforms' ) );

			$this->failed_entries_csv->record( $source_entry['fields'] );

			return 0;
		}

		$entry_meta = $source_entry['meta'] ?? [];

		// When a source field is mapped to Entry Date, use its value as the entry date.
		if ( ! empty( $meta_map['entry_date'] ) && $meta_map['entry_date'] !== 'current' && isset( $source_entry['fields'][ $meta_map['entry_date'] ] ) ) {
			$entry_meta['date'] = (string) $source_entry['fields'][ $meta_map['entry_date'] ];
		}

		$this->merge_fields_extra( $fields, $source_entry, $map );

		$entry_id = $this->create_entry( $fields, $form_data, $entry_meta );

		if ( ! $entry_id ) {
			$errors[] = $this->make_source_error( $source_entry_id, esc_html__( 'Entry creation failed. The record was skipped.', 'wpforms' ) );

			$this->failed_entries_csv->record( $source_entry['fields'] );

			return 0;
		}

		/**
		 * Fires after a single entry is successfully imported.
		 *
		 * @since 1.10.1
		 *
		 * @param int    $entry_id          Newly created entry ID.
		 * @param int    $source_entry_id   Source entry ID (0 if not available).
		 * @param int    $form_id           Destination form ID.
		 * @param string $source_identifier Source identifier (e.g., 'wpforms', 'csv').
		 */
		do_action(
			'wpforms_pro_admin_entries_import_entry_importer_entry_imported',
			$entry_id,
			$source_entry_id,
			$this->form_id,
			$this->source_identifier
		);

		foreach ( $entry_errors as $error ) {
			$errors[] = wp_parse_args(
				$error,
				[
					'type'     => 'destination',
					'entry_id' => $entry_id,
					'value'    => '',
					'field'    => '',
					'message'  => '',
					'status'   => 'skipped',
				]
			);
		}

		return $entry_id;
	}

	/**
	 * Merge per-field extras from the source entry into the formatted destination fields.
	 *
	 * Extras are extra keys (e.g. quiz_result) that sources attach to the source
	 * entry payload via `fields_extra`, keyed by source field ID. The map is used
	 * to translate each source ID into the matching destination field ID(s) so the
	 * extras land in the right `$fields` row regardless of whether the source and
	 * destination forms share field IDs.
	 *
	 * @since 1.10.1
	 *
	 * @param array $fields       Formatted destination fields keyed by destination field ID, passed by reference.
	 * @param array $source_entry Source entry payload (id, fields, fields_extra, meta).
	 * @param array $map          Map of destination field ID (string) to source field ID (string).
	 *
	 * @return void
	 */
	private function merge_fields_extra( array &$fields, array $source_entry, array $map ): void {

		if ( empty( $source_entry['fields_extra'] ) ) {
			return;
		}

		foreach ( $source_entry['fields_extra'] as $source_field_id => $extras ) {
			$dest_field_keys = array_keys( $map, (string) $source_field_id, true );

			foreach ( $dest_field_keys as $dest_field_key ) {
				$dest_field_id = (int) explode( '.', (string) $dest_field_key )[0];

				if ( ! isset( $fields[ $dest_field_id ] ) || ! is_array( $fields[ $dest_field_id ] ) ) {
					continue;
				}

				$fields[ $dest_field_id ] = array_merge( $fields[ $dest_field_id ], (array) $extras );
			}
		}
	}

	/**
	 * Build a source-level skipped-entry error record.
	 *
	 * @since 1.10.1
	 *
	 * @param int    $source_entry_id Source entry ID.
	 * @param string $message         Human-readable reason the entry was skipped.
	 *
	 * @return array
	 */
	private function make_source_error( int $source_entry_id, string $message ): array {

		return [
			'type'     => 'source',
			'entry_id' => $source_entry_id,
			'status'   => 'skipped',
			'message'  => $message,
		];
	}

	/**
	 * Prepare (format) form fields from a source entry using the field map.
	 *
	 * @since 1.10.1
	 *
	 * @param array $source_entry Source entry data keyed by the source field name.
	 * @param array $map          Map of source field name to destination field ID string.
	 * @param array $form_data    Destination form data.
	 * @param array $errors       List of errors.
	 *
	 * @return array Formatted fields keyed by field ID, ready for entry creation.
	 */
	private function prepare_fields( array $source_entry, array $map, array $form_data, array &$errors ): array {

		if ( empty( $form_data['fields'] ) ) {
			return [];
		}

		$process         = wpforms()->obj( 'process' );
		$process->fields = [];
		$entries_edit    = wpforms()->obj( 'entries_edit' );
		$all_empty       = true;

		foreach ( $form_data['fields'] as $field_settings ) {
			$field_id   = $field_settings['id'] ?? '';
			$field_type = $field_settings['type'] ?? '';

			if ( empty( $field_type ) || wpforms_is_empty_string( $field_id ) ) {
				continue;
			}

			$field_id         = (int) $field_id;
			$raw_source_value = $this->prepare_field_value( $field_settings, $source_entry, $map );

			if ( ! $this->is_source_value_empty( $raw_source_value ) ) {
				$all_empty = false;
			}

			$this->format_source_field( $field_id, $raw_source_value, $field_settings, $form_data, $entries_edit, $errors );
		}

		if ( $all_empty ) {
			return [];
		}

		return $process->fields;
	}

	/**
	 * Determine if a raw source value is considered empty.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value Raw source value returned by prepare_field_value().
	 *
	 * @return bool
	 */
	private function is_source_value_empty( $value ): bool {

		return is_array( $value ) ? empty( $value ) : wpforms_is_empty_string( $value );
	}

	/**
	 * Format a single source field and collect validation errors.
	 *
	 * @since 1.10.1
	 *
	 * @param int          $field_id         Destination field ID.
	 * @param string|array $raw_source_value Raw value returned by prepare_field_value().
	 * @param array        $field_settings   Destination field settings.
	 * @param array        $form_data        Destination form data.
	 * @param object       $entries_edit     EntriesEdit object used for formatting.
	 * @param array        $errors           Validation errors, passed by reference.
	 */
	private function format_source_field( int $field_id, $raw_source_value, array $field_settings, array $form_data, object $entries_edit, array &$errors ): void { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		// Raw import mode: skip all field formatting and validation, write data as-is.
		if ( $this->is_raw_import_mode() ) {
			$value = is_array( $raw_source_value ) ? implode( "\n", $raw_source_value ) : $raw_source_value;
			$value = sanitize_textarea_field( $value );

			if ( wpforms_is_empty_string( $value ) ) {
				return;
			}

			$process = wpforms()->obj( 'process' );

			$process->fields[ $field_id ] = [
				'name'  => $field_settings['label'] ?? '',
				'value' => $value,
				'id'    => $field_id,
				'type'  => $field_settings['type'] ?? '',
			];

			return;
		}

		$sanitized_source_value = $this->prepare_source_value( $raw_source_value, $field_settings, $form_data );
		$field_object           = $entries_edit->get_entries_edit_field_object( $field_settings['type'] );

		// Allow off-list values (Select / Checkbox / Radio without Other) to be
		// written as-is during import rather than filtered to empty by the
		// choice allowlist sanitizer.
		add_filter( 'wpforms_field_choices_allow_unknown_value', '__return_true' ); // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$field_object->format(
			$field_id,
			$sanitized_source_value,
			$entries_edit->get_empty_entry_field_data( $field_settings ),
			$form_data
		);

		remove_filter( 'wpforms_field_choices_allow_unknown_value', '__return_true' ); // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$process         = wpforms()->obj( 'process' );
		$formatted_field = ! empty( $process->fields[ $field_id ] )
			? (array) $process->fields[ $field_id ]
			: [
				'id'   => $field_id,
				'name' => $field_settings['label'] ?? '',
			];

		$field_type = (string) ( $field_settings['type'] ?? '' );

		$this->validate_errors( $raw_source_value, $formatted_field, $field_type, $errors, $field_settings );
	}

	/**
	 * Sanitize source value to adjust for our current field format.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $raw_source_value Field value.
	 * @param array        $field_settings   Field settings.
	 * @param array        $form_data        Form data and settings.
	 *
	 * @return string|array
	 */
	private function prepare_source_value( $raw_source_value, array $field_settings, array $form_data ) {

		$field_type = $field_settings['type'] ?? '';

		if ( wpforms_is_empty_string( $field_type ) ) {
			return $raw_source_value;
		}

		return $this->get_preparer_instance( $field_type )->prepare( $raw_source_value, $field_settings, $form_data );
	}

	/**
	 * Get the field preparers registry.
	 *
	 * @since 1.10.1
	 *
	 * @return array<string, string>
	 */
	private function get_preparers(): array {

		/**
		 * Filter the field preparers registry for entry import.
		 *
		 * Allows registering custom field preparer classes for specific field types.
		 * Each preparer must implement FieldPreparer\FieldPreparerInterface.
		 *
		 * @since 1.10.1
		 *
		 * @param array  $preparers         Map of field type slug to preparer class name.
		 * @param int    $form_id           Destination form ID.
		 * @param string $source_identifier Source identifier (e.g., 'csv', 'gravity-forms').
		 */
		return (array) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_get_preparers',
			$this->default_preparers,
			$this->form_id,
			$this->source_identifier
		);
	}

	/**
	 * Get or create a preparer instance for the given field type.
	 *
	 * Always returns a preparer — falls back to DefaultPreparer when no
	 * specific preparer is registered for the field type.
	 *
	 * @since 1.10.1
	 *
	 * @param string $field_type Field type.
	 *
	 * @return FieldPreparer\FieldPreparerInterface
	 */
	private function get_preparer_instance( string $field_type ): FieldPreparer\FieldPreparerInterface {

		if ( isset( $this->preparer_instances[ $field_type ] ) ) {
			return $this->preparer_instances[ $field_type ];
		}

		$preparers      = $this->get_preparers();
		$preparer_class = $preparers[ $field_type ] ?? FieldPreparer\DefaultPreparer::class;

		if ( ! class_exists( $preparer_class ) ) {
			$preparer_class = FieldPreparer\DefaultPreparer::class;
		}

		$preparer = new $preparer_class();

		if ( ! $preparer instanceof FieldPreparer\FieldPreparerInterface ) {
			$preparer = new FieldPreparer\DefaultPreparer();
		}

		$this->preparer_instances[ $field_type ] = $preparer;

		return $preparer;
	}

	/**
	 * Validate source value errors.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $raw_source_value Field value.
	 * @param array        $field_format     Field format.
	 * @param string       $field_type       Destination field type (e.g. 'email', 'address').
	 * @param array        $errors           Error list.
	 * @param array        $field_settings   Destination field settings (used for re-parse normalization checks).
	 */
	private function validate_errors( $raw_source_value, array $field_format, string $field_type, array &$errors, array $field_settings = [] ): void { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$field_id = (int) ( $field_settings['id'] ?? 0 );

		/**
		 * Filter the validation errors appended for a specific field type.
		 *
		 * Allows addons to override the generic per-subfield comparison loop for
		 * field types where it can't produce meaningful results (e.g., Map's
		 * choice-index storage). Return an array of error rows (possibly empty)
		 * to claim ownership of the field's validation and bypass the generic
		 * loop. Return null (default) to let the generic loop run.
		 *
		 * @since 1.10.1
		 *
		 * @param array|null $appended         Error rows to append, or null to defer.
		 * @param int        $field_id         Destination field ID.
		 * @param array      $field_format     Formatted field payload after format() ran.
		 * @param array      $field_settings   Destination field settings.
		 * @param mixed      $raw_source_value Raw source value passed to the field.
		 */
		$appended = apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_validate_errors_appended',
			null,
			$field_id,
			$field_format,
			$field_settings,
			$raw_source_value
		);

		if ( is_array( $appended ) ) {
			$errors = array_merge( $errors, $appended );

			return;
		}

		if ( is_array( $raw_source_value ) && $this->is_multiple_field( $field_settings ) ) {
			$raw_source_value = implode( "\n", array_filter( $raw_source_value ) );
		}

		$values = is_string( $raw_source_value )
			? [ 'value' => $raw_source_value ]
			: $raw_source_value;

		foreach ( $values as $key => $raw_source_value_item ) {
			$this->compare_values( (string) $raw_source_value_item, $field_format, (string) $key, $field_type, $errors, $field_settings );
		}
	}

	/**
	 * Compare the original source value with the formatted and sanitized value stored in a database.
	 *
	 * @since 1.10.1
	 *
	 * @param string $raw_source_value Field value.
	 * @param array  $field_format     Field format.
	 * @param string $subfield         Field format subkey.
	 * @param string $field_type       Destination field type.
	 * @param array  $errors           Error list.
	 * @param array  $field_settings   Destination field settings (used for re-parse normalization checks).
	 */
	private function compare_values( string $raw_source_value, array $field_format, string $subfield, string $field_type, array &$errors, array $field_settings = [] ): void {

		if ( wpforms_is_empty_string( $raw_source_value ) ) {
			return;
		}

		$formatted_value = $field_format[ $subfield ] ?? '';

		if ( $raw_source_value === (string) $formatted_value ) {
			return;
		}

		$label    = WPFormsDbSource::get_field_label( $field_format, 'name', $subfield );
		$status   = $formatted_value === '' ? 'skipped' : 'fixed';
		$preparer = $this->get_preparer_instance( $field_type );

		// Suppress the "fixed" flag when the raw value, fed through the preparer,
		// naturally produces the same subfield value (e.g. extracting the date
		// portion from "03/25/2026 10:30 AM" or normalizing ISO to m/d/Y).
		// The data is preserved exactly as the preparer would normally render it,
		// so there is nothing to flag.
		if ( $status === 'fixed' && $field_settings !== [] && $this->is_normalized_subfield( $preparer, $raw_source_value, (string) $formatted_value, $subfield, $field_settings ) ) {
			return;
		}

		$message_subfield = $subfield === 'value' ? '' : $subfield;
		$message          = $preparer->get_error_message( $status, $message_subfield );

		/**
		 * Filter the per-field error message emitted during entry import.
		 *
		 * @since 1.10.1
		 *
		 * @param string $message      The resolved error message.
		 * @param string $status       Error status: 'skipped' or 'fixed'.
		 * @param string $field_type   Destination field type slug.
		 * @param string $subfield     Subfield key, or empty string if none.
		 * @param array  $field_format Field format data used to derive the message.
		 */
		$message = (string) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_get_error_message',
			$message,
			$status,
			$field_type,
			$message_subfield,
			$field_format
		);

		if ( $message === '' ) {
			return;
		}

		$errors[] = [
			'value'   => $raw_source_value,
			'field'   => $label,
			'status'  => $status,
			'message' => $message,
		];
	}

	/**
	 * Decide whether the formatted subfield value is just a normalization of the raw input.
	 *
	 * @since 1.10.1
	 *
	 * @param FieldPreparer\FieldPreparerInterface $preparer         Preparer instance.
	 * @param string                               $raw_source_value Raw value from the source.
	 * @param string                               $formatted_value  Formatted value stored on the field.
	 * @param string                               $subfield         Subfield key (e.g. 'date', 'time').
	 * @param array                                $field_settings   Destination field settings.
	 *
	 * @return bool
	 */
	private function is_normalized_subfield( FieldPreparer\FieldPreparerInterface $preparer, string $raw_source_value, string $formatted_value, string $subfield, array $field_settings ): bool {

		$reparsed = $preparer->prepare( $raw_source_value, $field_settings, [] );

		if ( ! is_array( $reparsed ) ) {
			return false;
		}

		// Radio "Other" path: the preparer returns [ 'other' => $x ], which
		// Radio::format() then stores as $field['value']. Treat as normalization
		// when the formatted 'value' equals the preparer's 'other'.
		if ( $subfield === 'value' && isset( $reparsed['other'] ) ) {
			return $formatted_value === (string) $reparsed['other'];
		}

		$reparsed_value = (string) ( $reparsed[ $subfield ] ?? '' );

		return $formatted_value === $reparsed_value;
	}

	/**
	 * Prepare the value for a given field based on its settings and mapping configuration.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field_settings Configuration settings for the field.
	 * @param array $source_entry   The source entry containing the data to map.
	 * @param array $map            Mapping configuration for processing the field.
	 *
	 * @return string|array The prepared value or subfield values for the field.
	 */
	private function prepare_field_value( array $field_settings, array $source_entry, array $map ) {

		if ( $this->is_multiple_field( $field_settings ) ) {
			return $this->prepare_multiple_field_value( $field_settings, $source_entry, $map );
		}

		// Name, Date/Time, and Address fields always have subfields in the destination column dropdown
		// (see append_subfields), so we must use subfield handling regardless of the source format.
		$type                 = $field_settings['type'] ?? '';
		$always_has_subfields = in_array( $type, [ 'name', 'date-time', 'address' ], true );

		if ( ! $always_has_subfields && ! WPFormsDbSource::field_has_subfields( $field_settings ) ) {
			return $this->get_field_submit_value( $source_entry, $map, $field_settings );
		}

		return $this->prepare_subfields_value( $field_settings, $source_entry, $map, $type, $always_has_subfields );
	}

	/**
	 * Prepare the value for a multiple-choice field (checkboxes/multi-select style).
	 *
	 * @since 1.10.1
	 *
	 * @param array $field_settings Configuration settings for the field.
	 * @param array $source_entry   The source entry containing the data to map.
	 * @param array $map            Mapping configuration for processing the field.
	 *
	 * @return string|array
	 */
	private function prepare_multiple_field_value( array $field_settings, array $source_entry, array $map ) {

		$field_id       = (int) ( $field_settings['id'] ?? '' );
		$field_submit   = [];
		$source_columns = [];
		$choices        = $this->get_multiple_field_choices( $field_settings );

		foreach ( $choices as $choice_id => $choice ) {
			$field_settings['id'] = "$field_id.$choice_id";
			$current_field_id     = (string) $field_settings['id'];
			$value                = $this->get_field_submit_value( $source_entry, $map, $field_settings );

			if ( wpforms_is_empty_string( $value ) ) {
				continue;
			}

			$field_submit[ $choice_id ] = $value;

			if ( isset( $map[ $current_field_id ] ) ) {
				$source_columns[ $choice_id ] = $map[ $current_field_id ];
			}
		}

		if ( empty( $field_submit ) ) {
			return '';
		}

		// Compare source columns rather than values so different columns carrying identical
		// data (e.g. a multi-row Likert where every row received the same answer) aren't
		// false-collapsed into a single string the preparer can't re-split. Collapse on a
		// single shared source column regardless of how many destination choices the user
		// mapped, so a single mapping still routes through the preparer's delimiter split.
		if ( count( array_unique( $source_columns ) ) === 1 ) {
			return reset( $field_submit );
		}

		return $field_submit;
	}

	/**
	 * Prepare the value for a field with subfields (Name, Date/Time, Address, etc.).
	 *
	 * @since 1.10.1
	 *
	 * @param array  $field_settings       Configuration settings for the field.
	 * @param array  $source_entry         The source entry containing the data to map.
	 * @param array  $map                  Mapping configuration for processing the field.
	 * @param string $type                 Field type.
	 * @param bool   $always_has_subfields Whether the field type always renders subfields in the destination dropdown.
	 *
	 * @return array
	 */
	private function prepare_subfields_value( array $field_settings, array $source_entry, array $map, string $type, bool $always_has_subfields ): array {

		$subfields = [];

		WPFormsDbSource::append_field_subfields( $field_settings, $subfields );

		// If no subfields were returned but this field type always shows subfields in destination dropdown,
		// add the expected subfields manually. This handles cases like Name fields with "simple" format
		// where the source doesn't have subfields, but the destination dropdown shows them.
		if ( empty( $subfields ) && $always_has_subfields ) {
			$subfields = $this->get_default_subfields( $field_settings, $type );
		}

		[ $field_submit, $source_columns ] = $this->collect_subfield_values( $subfields, $field_settings, $source_entry, $map );

		if ( empty( $field_submit ) ) {
			return $field_submit;
		}

		// If all subfields map to the same source column (single column mapped to the whole field),
		// keep only the first subfield to avoid duplication.
		// We check source columns, not values, to avoid false positives when different columns
		// happen to contain identical data.
		if ( count( $field_submit ) > 1 && count( array_unique( $source_columns ) ) === 1 ) {
			reset( $field_submit );
			$first_key = key( $field_submit );

			return [ $first_key => current( $field_submit ) ];
		}

		return $field_submit;
	}

	/**
	 * Build the default subfield list for field types whose destination dropdown always shows subfields.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $field_settings Configuration settings for the field.
	 * @param string $type           Field type.
	 *
	 * @return array
	 */
	private function get_default_subfields( array $field_settings, string $type ): array {

		$field_id = $field_settings['id'];
		$defaults = [
			'name'      => [ 'first', 'middle', 'last' ],
			'date-time' => [ 'date', 'time' ],
			'address'   => [ 'address1', 'address2', 'city', 'state', 'postal', 'country' ],
		];

		$subfields = [];

		foreach ( $defaults[ $type ] ?? [] as $subfield ) {
			$subfields[] = [
				'key'   => "$field_id.$subfield",
				'label' => WPFormsDbSource::get_field_label( $field_settings, 'label', $subfield ),
			];
		}

		return $subfields;
	}

	/**
	 * Collect submit values and source-column mappings for each subfield.
	 *
	 * @since 1.10.1
	 *
	 * @param array $subfields      Subfield descriptors with 'key' entries.
	 * @param array $field_settings Configuration settings for the field.
	 * @param array $source_entry   The source entry containing the data to map.
	 * @param array $map            Mapping configuration for processing the field.
	 *
	 * @return array{0: array, 1: array} Tuple of [ field_submit, source_columns ].
	 */
	private function collect_subfield_values( array $subfields, array $field_settings, array $source_entry, array $map ): array {

		$field_submit   = [];
		$source_columns = [];

		foreach ( $subfields as $subfield ) {
			$field_settings['id'] = $subfield['key'];
			$field_id_parts       = explode( '.', $field_settings['id'] );
			$field_subfield       = isset( $field_id_parts[1] ) ? (string) $field_id_parts[1] : 'value';
			$current_field_id     = (string) $field_settings['id'];
			$value                = $this->get_field_submit_value( $source_entry, $map, $field_settings );

			if ( wpforms_is_empty_string( $value ) ) {
				continue;
			}

			$field_submit[ $field_subfield ] = $value;

			// Track which source column this subfield maps to.
			if ( isset( $map[ $current_field_id ] ) ) {
				$source_columns[ $field_subfield ] = $map[ $current_field_id ];
			}
		}

		return [ $field_submit, $source_columns ];
	}

	/**
	 * Build the field submit value from the source entry using the field map.
	 *
	 * @since 1.10.1
	 *
	 * @param array $source_entry   Source entry data keyed by the source field name.
	 * @param array $map            Map of source field name to destination field ID string.
	 * @param array $field_settings Field settings.
	 *
	 * @return string
	 */
	private function get_field_submit_value( array $source_entry, array $map, array $field_settings ): string {

		$field_id = (string) $field_settings['id'];

		if ( ! isset( $map[ $field_id ] ) ) {
			return '';
		}

		$source_field_id = $map[ $field_id ];

		return isset( $source_entry[ $source_field_id ] ) ? (string) $source_entry[ $source_field_id ] : '';
	}

	/**
	 * Normalize a raw date string to UTC Y-m-d H:i:s.
	 *
	 * Returns the current UTC time when $raw_date is empty or unparseable.
	 *
	 * @since 1.10.1
	 *
	 * @param string $raw_date Raw date string from the source.
	 *
	 * @return string
	 */
	private function normalize_entry_date( string $raw_date ): string {

		$fallback = current_time( self::ENTRY_DATE_FORMAT, true );

		if ( $raw_date === '0' || wpforms_is_empty_string( $raw_date ) ) {
			return $fallback;
		}

		// Unix timestamps must be handled explicitly; DateTime constructor does not accept them.
		if ( ctype_digit( $raw_date ) ) {
			$timestamp = (int) $raw_date;

			if ( $timestamp < self::MIN_ENTRY_TIMESTAMP || $timestamp > time() ) {
				return $fallback;
			}

			return gmdate( self::ENTRY_DATE_FORMAT, $timestamp );
		}

		try {
			$date      = new DateTime( $raw_date, new DateTimeZone( 'UTC' ) );
			$timestamp = $date->getTimestamp();

			if ( $timestamp < self::MIN_ENTRY_TIMESTAMP || $timestamp > time() ) {
				return $fallback;
			}

			return $date->format( self::ENTRY_DATE_FORMAT );
		} catch ( Exception $e ) {
			return $fallback;
		}
	}

	/**
	 * Persist entry-meta rows to the wpforms_entry_meta table.
	 *
	 * @since 1.10.1
	 *
	 * @param int   $entry_id   Newly created entry ID.
	 * @param array $entry_meta Map of meta type => data to persist.
	 */
	private function save_entry_meta( int $entry_id, array $entry_meta ): void {

		$handler = wpforms()->obj( 'entry_meta' );

		if ( ! $handler ) {
			return;
		}

		// Notes and logs carry authorship; attribute imported rows to the importing user
		// so downstream UI (entry single, print preview) can resolve a valid WP user.
		$authored_types = [ 'note', 'log' ];
		$current_user   = get_current_user_id();

		foreach ( $entry_meta as $type => $data ) {
			if ( wpforms_is_empty_string( $type ) ) {
				continue;
			}

			$type  = sanitize_key( $type );
			$items = is_array( $data ) ? $data : [ $data ];

			foreach ( $items as $item ) {
				if ( wpforms_is_empty_string( $item ) ) {
					continue;
				}

				$row = [
					'entry_id' => $entry_id,
					'form_id'  => $this->form_id,
					'type'     => $type,
					'data'     => $item,
				];

				if ( in_array( $type, $authored_types, true ) ) {
					$row['user_id'] = $current_user;
				}

				$handler->add( $row, 'entry_meta' );
			}
		}
	}

	/**
	 * Persist a formatted entry to the database.
	 *
	 * @since 1.10.1
	 *
	 * @param array $fields     Formatted fields from prepare_fields().
	 * @param array $form_data  Destination form data.
	 * @param array $entry_meta Flat meta array. Known entry-table column keys
	 *                          (date, ip_address, user_agent, user_uuid, user_id, viewed, starred)
	 *                          are written to wpforms_entries; every other key is stored as a
	 *                          row in wpforms_entry_meta.
	 *
	 * @return int Created entry ID, or 0 on failure.
	 */
	private function create_entry( array $fields, array $form_data, array $entry_meta = [] ): int {

		$date = $this->normalize_entry_date( $entry_meta['date'] ?? '' );

		$entry_args = [
			'form_id'       => $this->form_id,
			'type'          => 'imported',
			'fields'        => wp_json_encode( $fields ),
			'date'          => $date,
			'date_modified' => $date,
			'user_id'       => isset( $entry_meta['user_id'] ) ? (int) $entry_meta['user_id'] : 0,
			'viewed'        => isset( $entry_meta['viewed'] ) ? (int) $entry_meta['viewed'] : 0,
			'starred'       => isset( $entry_meta['starred'] ) ? (int) $entry_meta['starred'] : 0,
		];

		$entry_args = array_merge( $entry_args, $this->get_gdpr_entry_args( $entry_meta, $form_data ) );

		$entry_id = (int) wpforms()->obj( 'entry' )->add( $entry_args, 'entry' );

		if ( ! $entry_id ) {
			return 0;
		}

		wpforms()->obj( 'entry_fields' )->save( $fields, $form_data, $entry_id );

		$column_keys = [ 'date', 'ip_address', 'user_agent', 'user_uuid', 'user_id', 'viewed', 'starred' ];
		$meta_rows   = array_diff_key( $entry_meta, array_flip( $column_keys ) );

		if ( ! empty( $meta_rows ) ) {
			$this->save_entry_meta( $entry_id, $meta_rows );
		}

		return $entry_id;
	}

	/**
	 * Build the GDPR-gated entry args for IP address, user agent, and user UUID.
	 *
	 * @since 1.10.1
	 *
	 * @param array $entry_meta Flat meta array from the import source.
	 * @param array $form_data  Destination form data.
	 *
	 * @return array Subset of entry args safe to merge into the entry row.
	 */
	private function get_gdpr_entry_args( array &$entry_meta, array $form_data ): array {

		$args = [];

		if ( ! empty( $entry_meta['user_uuid'] ) && wpforms_is_collecting_cookies_allowed() ) {
			$args['user_uuid'] = sanitize_text_field( $entry_meta['user_uuid'] );
		}

		if ( ! wpforms_is_collecting_ip_allowed( $form_data ) ) {
			unset( $entry_meta['location'] );

			return $args;
		}

		if ( ! empty( $entry_meta['ip_address'] ) ) {
			$args['ip_address'] = sanitize_text_field( $entry_meta['ip_address'] );
		}

		if ( ! empty( $entry_meta['user_agent'] ) ) {
			$args['user_agent'] = sanitize_text_field( $entry_meta['user_agent'] );
		}

		return $args;
	}

	/**
	 * Get the iterable choices for a multiple-selection field.
	 *
	 * Defaults to the field's `choices` array. The result drives both the
	 * per-choice destination dropdown labels and the per-choice value loop
	 * during field preparation.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field settings.
	 *
	 * @return array Map of choice key to choice data (must include a `label` key).
	 */
	private function get_multiple_field_choices( array $field ): array {

		$choices = $field['choices'] ?? [];

		/**
		 * Filter the iterable choices for a multiple-selection field.
		 *
		 * Allows addons to provide non-`choices`-based item lists (e.g., Likert
		 * Scale rows) that should drive destination expansion and per-item value
		 * mapping.
		 *
		 * @since 1.10.1
		 *
		 * @param array $choices Map of item key to item data. Each item must include a `label` key.
		 * @param array $field   Field settings.
		 */
		return (array) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_get_multiple_field_choices',
			$choices,
			$field
		);
	}

	/**
	 * Determine if a given field is a multiple selection field.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field The field to check, containing type and configuration details.
	 *
	 * @return bool
	 */
	private function is_multiple_field( array $field ): bool {

		if ( empty( $field['type'] ) ) {
			return false;
		}

		$is_multiple = false;

		if ( $field['type'] === 'checkbox' ) {
			$is_multiple = true;
		}

		if ( $field['type'] === 'select' && ! empty( $field['multiple'] ) ) {
			$is_multiple = true;
		}

		/**
		 * Filter whether a given field is treated as a multiple-selection field for entry import.
		 *
		 * Allows addons (e.g., Surveys & Polls) to register field types that should be
		 * expanded into per-choice destination columns in the import mapping UI.
		 *
		 * @since 1.10.1
		 *
		 * @param bool  $is_multiple Whether the field is multiple-selection.
		 * @param array $field       Field settings.
		 */
		return (bool) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_is_multiple_field',
			$is_multiple,
			$field
		);
	}

	/**
	 * Check if raw import mode is enabled.
	 *
	 * When enabled, field values are written as-is without formatting or validation.
	 *
	 * @since 1.10.1
	 *
	 * @return bool
	 */
	private function is_raw_import_mode(): bool {

		/**
		 * Filter to enable raw import mode.
		 *
		 * When enabled, field values are imported without any formatting or validation,
		 * preserving the exact source data.
		 *
		 * @since 1.10.1
		 *
		 * @param bool   $is_raw_mode       Whether raw import mode is enabled. Default false.
		 * @param int    $form_id           Destination form ID.
		 * @param string $source_identifier Source identifier (e.g., 'csv', 'gravity-forms').
		 */
		return (bool) apply_filters(
			'wpforms_pro_admin_entries_import_entry_importer_is_raw_import_mode',
			false,
			$this->form_id,
			$this->source_identifier
		);
	}
}
