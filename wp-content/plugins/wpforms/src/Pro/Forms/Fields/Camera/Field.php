<?php

namespace WPForms\Pro\Forms\Fields\Camera;

use WPForms\Forms\Fields\Camera\Field as FieldLite;
use WPForms\Forms\Fields\Traits\FileDisplayTrait;
use WPForms\Pro\Helpers\Upload;
use WPForms\Forms\Fields\Traits\FileMethodsTrait;

/**
 * Camera field.
 *
 * @since 1.9.8
 */
class Field extends FieldLite {

	use FileDisplayTrait;
	use FileMethodsTrait;

	/**
	 * Upload files helper.
	 *
	 * @since 1.9.8
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * Instance of the Builder class.
	 *
	 * @since 1.9.8
	 *
	 * @var Builder
	 */
	protected $builder_obj;

	/**
	 * Handle name for wp_register_styles handle.
	 *
	 * @since 1.9.8
	 *
	 * @var string
	 */
	private const HANDLE = 'wpforms-camera-field';

	/**
	 * Wait time.
	 *
	 * @since 1.9.8
	 *
	 * @var int
	 */
	private $wait_time;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.9.8
	 */
	public function init() {

		parent::init();

		/**
		 * Filter to change the wait time for the camera field.
		 *
		 * @since 1.9.8
		 *
		 * @param int $wait_time Wait time in seconds.
		 *
		 * @return int
		 */
		$this->wait_time = absint( apply_filters( 'wpforms_pro_forms_fields_camera_field_wait_time_seconds', 3 ) );

		// Init our upload helper.
		$this->upload = new Upload();

		$this->init_objects();

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.8
	 */
	private function hooks(): void {

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		// Customize value format.
		add_filter( 'wpforms_html_field_value', [ $this, 'html_field_value' ], 10, 4 );

		// Complete the upload process for camera fields.
		add_filter( 'wpforms_process_after_filter', [ $this, 'upload_complete' ], PHP_INT_MAX, 3 );

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_camera', [ $this, 'field_properties' ], 5, 3 );

		// Create file protection for camera fields.
		add_action( 'wpforms_process_entry_saved', [ $this, 'create_protection' ], 10, 5 );

		// Disable entry preview for camera fields.
		add_filter( 'wpforms_pro_fields_entry_preview_is_field_support_preview_camera_field', '__return_false' );

		// Delete file protection after a file is deleted.
		add_action( 'wpforms_pro_forms_fields_file_upload_field_delete_uploaded_file', [ $this, 'delete_file_protection' ], 10, 2 );
		add_action( 'wpforms_pro_forms_fields_camera_field_delete_uploaded_file', [ $this, 'delete_file_protection' ], 10, 2 );

		add_filter( 'wpforms_pro_admin_entries_edit_field_output_editable', [ $this, 'is_editable' ], 10, 4 );

		add_filter( 'wpforms_pro_admin_entries_export_ajax_get_entry_fields_data_field', [ $this, 'export_entry_field_data' ] );
	}

	/**
	 * Initialize objects.
	 *
	 * @since 1.9.8
	 */
	private function init_objects(): void {

		$is_ajax = wp_doing_ajax();

		if ( $is_ajax || wpforms_is_admin_page( 'builder' ) ) {
			$this->builder_obj = $this->get_object( 'Builder' );

			$this->builder_obj->init();
		}
	}

	/**
	 * Only a non-empty field is editable.
	 *
	 * @since 1.9.8
	 *
	 * @param bool  $is_editable  Default value.
	 * @param array $field        Field data.
	 * @param array $entry_fields Entry fields data.
	 * @param array $form_data    Form data and settings.
	 *
	 * @return bool
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function is_editable( $is_editable, $field, $entry_fields, $form_data ): bool {

		if ( $field['type'] !== $this->type ) {
			return $is_editable;
		}

		return ! empty( $entry_fields[ $field['id'] ]['value'] );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.9.8
	 *
	 * @param array $field      Field data.
	 * @param array $deprecated Deprecated field data.
	 * @param array $form_data  Form data.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$style   = ! empty( $field['style'] ) ? $field['style'] : self::STYLE_BUTTON;
		$text    = ! empty( $field['button_link_text'] ) ? $field['button_link_text'] : esc_html__( 'Capture With Your Camera', 'wpforms' );
		$primary = $field['properties']['inputs']['primary'];

		// Camera button/link with icon.
		if ( $style === self::STYLE_BUTTON ) {
			printf(
				'<button type="button" class="wpforms-camera-button wpforms-btn-secondary" id="%d">%s %s</button>',
				absint( $field['id'] ),
				$this->get_camera_icon_svg(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $text )
			);
		} else {
			printf(
				'<a href="#" class="wpforms-camera-link" data-field-id="%d">%s</a>',
				absint( $field['id'] ),
				esc_html( $text )
			);
		}

		// Print selected file holder.
		printf(
			'<div class="wpforms-camera-selected-file"><span></span> <button class="wpforms-camera-remove-file" title="%s">%s</button></div>',
			esc_html__( 'Remove file', 'wpforms' ),
			$this->get_camera_remove_file_icon() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		// Classic style.
		printf(
			'<input type="file" %s %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			! empty( $primary['required'] ) ? 'required' : ''
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render(
			'fields/camera-modal',
			[
				'field_id'          => $field['id'],
				'form_id'           => $form_data['id'],
				'camera_format'     => $field['camera_format'],
				'camera_time_limit' => $this->get_camera_time_limit( $field ),
				'wait_time'         => $this->wait_time,
			],
			true
		);
	}

	/**
	 * Form frontend JS enqueues.
	 *
	 * @since 1.9.8
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function enqueue_frontend_js( array $forms ): void {

		// Check if among the forms is the field with camera_enabled set to true.
		$camera_enabled       = false;
		$photo_format_enabled = false;

		foreach ( $forms as $form ) {
			if ( ! $this->is_camera_enabled( $form ) ) {
				continue;
			}

			$camera_enabled = true;

			// Check if any camera field has a photo format.
			if ( $this->has_photo_format_camera( $form ) ) {
				$photo_format_enabled = true;
			}

			if ( $photo_format_enabled ) {
				break;
			}
		}

		if ( ! $camera_enabled ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		$camera_requirements = [ 'wpforms' ];

		wp_enqueue_script(
			self::HANDLE,
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/camera{$min}.js",
			$camera_requirements,
			WPFORMS_VERSION,
			true
		);

		wp_localize_script(
			self::HANDLE,
			'wpforms_camera_frontend',
			[
				'wait_time' => $this->wait_time,
				'strings'   => $this->get_strings(),
			]
		);
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.9.8
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Field value that was submitted.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field_id    = absint( $field_id );
		$field_label = ! empty( $form_data['fields'][ $field_id ]['label'] )
			? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] )
			: '';

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'          => $field_label,
			'value'         => '',
			'file'          => '',
			'file_original' => '',
			'ext'           => '',
			'id'            => $field_id,
			'type'          => $this->type,
		];
	}

	/**
	 * Check if the form has camera fields with photo format.
	 *
	 * @since 1.9.8
	 *
	 * @param array|mixed $form Form data.
	 *
	 * @return bool
	 */
	private function has_photo_format_camera( $form ): bool {

		if ( empty( $form['fields'] ) ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			// Check if the field has camera enabled and a format is photo.
			if (
				! empty( $field['camera_enabled'] ) &&
				( empty( $field['camera_format'] ) || $field['camera_format'] === 'photo' )
			) {
				return true;
			}

			// Check if it's a camera field type (a default format is a photo).
			if (
				! empty( $field['type'] ) &&
				$field['type'] === 'camera' &&
				( empty( $field['camera_format'] ) || $field['camera_format'] === 'photo' )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the filter name for file URL modification.
	 *
	 * @since 1.9.8
	 *
	 * @return string
	 */
	protected function get_file_url_filter_name(): string {

		return 'wpforms_pro_forms_fields_camera_field_get_file_url';
	}

	/**
	 * Complete the upload process for camera fields.
	 *
	 * @since 1.9.8
	 *
	 * @param array|mixed $fields    Fields data.
	 * @param array       $entry     Submitted form entry.
	 * @param array       $form_data Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function upload_complete( $fields, $entry, $form_data ): array {

		$fields = (array) $fields;

		if ( ! empty( wpforms()->obj( 'process' )->errors[ $form_data['id'] ] ) ) {
			return $fields;
		}

		$this->form_data = $form_data;

		foreach ( $fields as $field_id => $field ) {
			if ( empty( $field['type'] ) || $field['type'] !== $this->type ) {
				continue;
			}

			$this->form_id    = absint( $form_data['id'] );
			$this->field_id   = $field_id;
			$this->field_data = ! empty( $this->form_data['fields'][ $field_id ] )
				? $this->form_data['fields'][ $field_id ]
				: [];
			$is_visible       =
				! isset( wpforms()->obj( 'process' )->fields[ $field_id ]['visible'] ) ||
				! empty( wpforms()->obj( 'process' )->fields[ $field_id ]['visible'] );

			$fields[ $field_id ]['visible'] = $is_visible;

			if ( ! $is_visible ) {
				continue;
			}

			$fields[ $field_id ] = $this->complete_upload_classic( $field );
		}

		return $fields;
	}

	/**
	 * Complete the upload process for the classic camera field.
	 *
	 * @since 1.9.8
	 *
	 * @param array $processed_field Processed field data.
	 *
	 * @return array
	 */
	private function complete_upload_classic( array $processed_field ): array {

		$input_name = $this->get_input_name();
		$file       = ! empty( $_FILES[ $input_name ] ) ? $_FILES[ $input_name ] : false; // phpcs:ignore

		// If there was no file uploaded, stop here before we continue with the upload process.
		if ( ! $file || $file['error'] !== 0 ) {
			return $processed_field;
		}

		$processed_file = $this->upload->process_file(
			$file,
			$this->field_id,
			$this->form_data,
			$this->is_media_integrated()
		);

		$processed_file_data = [
			'value'          => esc_url_raw( $processed_file['file_url'] ),
			'file'           => $processed_file['file_name_new'],
			'file_original'  => $processed_file['file_name'],
			'file_user_name' => sanitize_text_field( $file['name'] ),
			'ext'            => $processed_file['file_ext'],
			'attachment_id'  => absint( $processed_file['attachment_id'] ),
		];

		if ( ! empty( $processed_file['protection_hash'] ) ) {
			$processed_file_data['protection_hash'] = $processed_file['protection_hash'];
		}

		return array_merge( $processed_field, $processed_file_data );
	}

	/**
	 * Get the input name for the field.
	 *
	 * @since 1.9.8
	 *
	 * @return string
	 */
	protected function get_input_name(): string {

		return sprintf( 'wpforms_%d_%d', $this->form_id, $this->field_id );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.8
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field data and settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function field_properties( $properties, $field, $form_data ): array {

		$properties = (array) $properties;

		$this->form_data  = $form_data;
		$this->form_id    = absint( $this->form_data['id'] );
		$this->field_id   = absint( $field['id'] );
		$this->field_data = $this->form_data['fields'][ $this->field_id ] ?? [];

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "wpforms_{$this->form_id}_{$this->field_id}";

		// Input Primary: filter files in classic uploader style in a file selection window.
		$properties['inputs']['primary']['attr']['accept'] =
			rtrim( '.' . implode( ',.', $this->get_extensions() ), ',.' );

		// Input Primary: allowed file extensions.
		$properties['inputs']['primary']['data']['rule-extension'] = implode( ',', $this->get_extensions() );

		// Input Primary: max file size.
		$properties['inputs']['primary']['data']['rule-maxsize'] = $this->max_file_size();

		return $properties;
	}

	/**
	 * Validate field for various errors on the form submitted.
	 *
	 * @since 1.9.8
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$this->form_data  = (array) $form_data;
		$this->form_id    = absint( $this->form_data['id'] );
		$this->field_id   = absint( $field_id );
		$this->field_data = $this->form_data['fields'][ $this->field_id ];
		$input_name       = $this->get_input_name();

		$this->validate_classic( $input_name );
	}

	/**
	 * Validate classic camera field data.
	 *
	 * @since 1.9.8
	 *
	 * @param string $input_name Input name inside the form on the front-end.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function validate_classic( $input_name ): void { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES[ $input_name ] ) ) {
			return;
		}

		/*
		 * If nothing is uploaded, and it is not required, don't process.
		 */
		$error = isset( $_FILES[ $input_name ]['error'] ) ? (int) $_FILES[ $input_name ]['error'] : 0;

		if ( $error === 4 && ! $this->is_required() ) {
			return;
		}

		/*
		 * Basic file upload validation.
		 */
		$validated_basic = $this->validate_basic( $error );

		if ( ! empty( $validated_basic ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = $validated_basic;

			return;
		}

		/*
		 * Validate if a file is required and provided.
		 */
		if (
			( empty( $_FILES[ $input_name ]['tmp_name'] ) || $error === 4 ) &&
			$this->is_required()
		) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = wpforms_get_required_label();

			return;
		}

		/*
		 * Validate file size.
		 */
		$file_size      = ! empty( $_FILES[ $input_name ]['size'] ) ? (int) $_FILES[ $input_name ]['size'] : 0;
		$validated_size = $this->validate_size( [ $file_size ] );

		if ( ! empty( $validated_size ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = $validated_size;

			return;
		}

		/*
		 * Validate file extension.
		 */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$name = $_FILES[ $input_name ]['name'] ?? '';
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		$validated_ext = $this->validate_extension( $ext );

		if ( ! empty( $validated_ext ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = $validated_ext;

			return;
		}

		/*
		 * Validate a file against what WordPress is set to allow.
		 * At the end of the day, if you try to upload a file that WordPress
		 * doesn't allow, we won't allow it either. Users can use a plugin to
		 * filter the allowed mime types in WordPress if this is an issue.
		 */
		$validated_filetype = $this->validate_wp_filetype_and_ext(
			$_FILES[ $input_name ]['tmp_name'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			sanitize_file_name( wp_unslash( $name ) )
		);

		if ( ! empty( $validated_filetype ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = $validated_filetype;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Validate file size.
	 *
	 * @since 1.9.8
	 *
	 * @param array $sizes Array with all file sizes in bytes.
	 *
	 * @return false|string False if no errors found, error text otherwise.
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function validate_size( $sizes = null ) {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if (
			$sizes === null &&
			! empty( $_FILES )
		) {
			$sizes = [];

			foreach ( $_FILES as $file ) {
				$sizes[] = $file['size'];
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! is_array( $sizes ) ) {
			return false;
		}

		$max_size = min( wp_max_upload_size(), $this->max_file_size() );

		foreach ( $sizes as $size ) {
			if ( $size > $max_size ) {
				return sprintf( /* translators: %s - allowed file size in MB. */
					esc_html__( 'File exceeds max size allowed (%s).', 'wpforms' ),
					size_format( $max_size )
				);
			}
		}

		return false;
	}

	/**
	 * Validate extension against denylist and admin-provided list.
	 * There are certain extensions we do not allow under any circumstances for security purposes.
	 *
	 * @since 1.9.8
	 *
	 * @param string $ext Extension.
	 *
	 * @return false|string False if no errors found, error text otherwise.
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function validate_extension( $ext ) {

		// Make sure the file has an extension first.
		if ( empty( $ext ) ) {
			return esc_html__( 'File must have an extension.', 'wpforms' );
		}

		// Validate extension against all allowed values.
		if ( ! in_array( $ext, $this->get_extensions(), true ) ) {
			return esc_html__( 'File type is not allowed.', 'wpforms' );
		}

		return false;
	}

	/**
	 * Validate a file against what WordPress is set to allow.
	 * At the end of the day, if you try to upload a file that WordPress
	 * doesn't allow, we won't allow it either. Users can use a plugin to
	 * filter the allowed mime types in WordPress if this is an issue.
	 *
	 * @since 1.9.8
	 *
	 * @param string $path Path to a newly uploaded file.
	 * @param string $name Name of a newly uploaded file.
	 *
	 * @return false|string False if no errors found, error text otherwise.
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected function validate_wp_filetype_and_ext( $path, $name ) {

		$wp_filetype = wp_check_filetype_and_ext( $path, $name );

		$ext             = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
		$type            = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
		$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

		if ( $proper_filename || ! $ext || ! $type ) {
			return esc_html__( 'File type is not allowed.', 'wpforms' );
		}

		return false;
	}

	/**
	 * Whether a field is required or not.
	 *
	 * @uses $this->field_data
	 *
	 * @since 1.9.8
	 *
	 * @return bool
	 */
	protected function is_required(): bool {

		return ! empty( $this->field_data['required'] );
	}

	/**
	 * Basic file upload validation.
	 *
	 * @since 1.9.8
	 *
	 * @param int $error Error ID provided by PHP.
	 *
	 * @return false|string False if no errors found, error text otherwise.
	 */
	protected function validate_basic( $error ) {

		if ( $error === 0 || $error === 4 ) {
			return false;
		}

		$errors = [
			false,
			esc_html__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'wpforms' ),
			esc_html__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'wpforms' ),
			esc_html__( 'The uploaded file was only partially uploaded.', 'wpforms' ),
			esc_html__( 'No file was uploaded.', 'wpforms' ),
			'',
			esc_html__( 'Missing a temporary folder.', 'wpforms' ),
			esc_html__( 'Failed to write file to disk.', 'wpforms' ),
			esc_html__( 'File upload stopped by extension.', 'wpforms' ),
		];

		if ( array_key_exists( $error, $errors ) ) {
			return sprintf( /* translators: %s - error text. */
				esc_html__( 'File upload error. %s', 'wpforms' ),
				$errors[ $error ]
			);
		}

		return false;
	}

	/**
	 * Whether the field is integrated with WordPress Media Library.
	 *
	 * @uses $this->field_data
	 *
	 * @since 1.9.8
	 *
	 * @return bool
	 */
	private function is_media_integrated(): bool {

		return ! empty( $this->field_data['media_library'] ) && $this->field_data['media_library'] === '1';
	}

	/**
	 * Process field protection.
	 *
	 * @since 1.9.8
	 *
	 * @param array $fields     Fields data.
	 * @param array $entry      Entry data.
	 * @param array $form_data  Form data and settings.
	 * @param int   $entry_id   Entry ID.
	 * @param int   $payment_id Payment ID.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function create_protection( $fields, $entry, $form_data, $entry_id, $payment_id ): void {

		$form_id = $form_data['id'];

		foreach ( $fields as $field ) {
			$this->process_field_protection( (array) $field, (int) $form_id, (int) $entry_id );
		}
	}

	/**
	 * Process field protection.
	 *
	 * @since 1.9.8
	 *
	 * @param array $field    Field data.
	 * @param int   $form_id  Form ID.
	 * @param int   $entry_id Entry ID.
	 */
	private function process_field_protection( array $field, int $form_id, int $entry_id ): void {

		if ( empty( $field['type'] ) || $field['type'] !== $this->type ) {
			return;
		}

		$values = $field['value_raw'] ?? [ $field ];

		if ( empty( $values ) ) {
			return;
		}

		$file_restriction_obj = wpforms()->obj( 'file_restrictions' );
		$restriction          = $file_restriction_obj
			? $file_restriction_obj->get_restriction( $form_id, $field['id'] )
			: null;

		if ( empty( $restriction ) ) {
			return;
		}

		$args = [
			'entry_id'       => $entry_id,
			'form_id'        => $form_id,
			'restriction_id' => $restriction['id'],
		];

		foreach ( $values as $file ) {
			$this->create_file_protection( $file, $args );
		}
	}

	/**
	 * Create protection for a single file.
	 *
	 * @since 1.9.8
	 *
	 * @param array $file File data.
	 * @param array $args Additional arguments.
	 */
	private function create_file_protection( array $file, array $args ): void {

		$protection_hash = $file['protection_hash'] ?? '';

		if ( empty( $protection_hash ) ) {
			return;
		}

		$protection_args = [
			'hash' => $protection_hash,
			'file' => $file['file'],
		];

		$args = array_merge( $args, $protection_args );

		$protected_files_obj = wpforms()->obj( 'protected_files' );

		if ( $protected_files_obj ) {
			$protected_files_obj->create_protection( $args );
		}
	}

	/**
	 * Delete file protection.
	 *
	 * @since 1.9.8
	 *
	 * @param array $file_data File data.
	 * @param array $entry     Entry data.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function delete_file_protection( $file_data, $entry ): void {

		$hash = $file_data['protection_hash'] ?? '';

		if ( empty( $hash ) ) {
			return;
		}

		$protected_files_obj = wpforms()->obj( 'protected_files' );

		if ( $protected_files_obj ) {
			$protected_files_obj->delete_protection( $hash );
		}
	}

	/**
	 * Delete uploaded file.
	 *
	 * @since 1.9.8
	 *
	 * @param string $files_path Files path.
	 * @param array  $file_data  File data.
	 * @param object $entry      Entry.
	 *
	 * @return string
	 */
	private static function delete_uploaded_file( string $files_path, $file_data, object $entry ): string {

		if ( empty( $file_data['file'] ) ) {
			return '';
		}

		// We delete attachments from Media Library only for spam entries.
		if ( $entry->status === 'spam' && ! empty( $file_data['attachment_id'] ) ) {
			wp_delete_attachment( $file_data['attachment_id'], true );

			return (string) $file_data['file_user_name'];
		}

		$file = trailingslashit( $files_path ) . $file_data['file'];

		if ( ! is_file( $file ) ) {
			return '';
		}

		/**
		 * Fires before the uploaded file is deleted.
		 *
		 * @since 1.9.8
		 *
		 * @param array  $file_data File data.
		 * @param object $entry     Entry object.
		 */
		do_action( 'wpforms_pro_forms_fields_camera_field_delete_uploaded_file', $file_data, $entry );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		unlink( $file );

		return (string) $file_data['file_user_name'];
	}

	/**
	 * Get form files path.
	 *
	 * @since 1.9.8
	 *
	 * @param int|string $form_id Form ID.
	 *
	 * @return string
	 */
	public static function get_form_files_path( $form_id ): string {

		$form_obj  = wpforms()->obj( 'form' );
		$form_data = $form_obj ? $form_obj->get( $form_id ) : null;

		if ( empty( $form_data ) ) {
			return '';
		}

		$upload_dir = wpforms_upload_dir();

		return trailingslashit( $upload_dir['path'] ) . ( new Upload() )->get_form_directory( $form_data->ID, $form_data->post_date );
	}

	/**
	 * Get form files path backward fallback.
	 *
	 * @since 1.9.8
	 *
	 * @param int|string $form_id Form ID.
	 *
	 * @return string
	 */
	private static function get_form_files_path_backward_fallback( $form_id ): string {

		$form_obj  = wpforms()->obj( 'form' );
		$form_data = $form_obj ? $form_obj->get( $form_id ) : null;

		if ( empty( $form_data ) ) {
			return '';
		}

		$upload_dir = wpforms_upload_dir();

		return trailingslashit( $upload_dir['path'] ) . absint( $form_data->ID ) . '-' . md5( $form_data->post_date . $form_data->ID );
	}

	/**
	 * Delete uploaded files from entry.
	 *
	 * @since 1.9.8
	 *
	 * @param int   $entry_id       Entry ID.
	 * @param array $delete_fields  Fields to delete.
	 * @param array $exclude_fields Exclude fields.
	 *
	 * @return array Removed files names.
	 */
	public static function delete_uploaded_files_from_entry( $entry_id, $delete_fields = [], $exclude_fields = [] ): array {

		$entry_obj = wpforms()->obj( 'entry' );
		$entry     = $entry_obj ? $entry_obj->get( $entry_id ) : null;

		if ( empty( $entry ) ) {
			return [];
		}

		$files_path = self::get_form_files_path( $entry->form_id );

		if ( ! is_dir( $files_path ) ) {
			$files_path = self::get_form_files_path_backward_fallback( $entry->form_id );
		}

		$fields_to_delete = $delete_fields ? $delete_fields : (array) wpforms_decode( $entry->fields );
		$removed_files    = [];

		foreach ( $fields_to_delete as $field ) {
			if ( ! isset( $field['type'] ) || $field['type'] !== 'camera' || ( $exclude_fields && ! isset( $exclude_fields[ $field['id'] ] ) ) ) {
				continue;
			}

			$removed_files = self::delete_uploaded_file_from_entry( $removed_files, $field, $exclude_fields, $files_path, $entry );
		}

		return $removed_files;
	}

	/**
	 * Maybe delete an uploaded file from entry.
	 *
	 * @since 1.9.8
	 *
	 * @param array  $removed_files  The removed files array.
	 * @param array  $field          The field to delete.
	 * @param array  $exclude_fields Exclude fields.
	 * @param string $files_path     Form files path.
	 * @param object $entry          Entry.
	 *
	 * @return array
	 */
	private static function delete_uploaded_file_from_entry( $removed_files, $field, $exclude_fields, $files_path, $entry ): array {

		$removed_files = (array) $removed_files;

		if ( ! self::is_modern_upload( $field ) ) {
			$removed_files[] = self::delete_uploaded_file( $files_path, $field, $entry );

			return $removed_files;
		}

		$values = $field['value_raw'];

		if ( $exclude_fields ) {
			$values = ! empty( $field['value_raw'] )
				? array_diff_key( $exclude_fields[ $field['id'] ]['value_raw'], $field['value_raw'] )
				: $exclude_fields[ $field['id'] ]['value_raw'];
		}

		if ( empty( $values ) ) {
			return $removed_files;
		}

		foreach ( $values as $value_raw ) {
			$removed_files[] = self::delete_uploaded_file( $files_path, $value_raw, $entry );
		}

		return $removed_files;
	}

	/**
	 * Get all allowed extensions for the camera field.
	 *
	 * @since 1.9.8
	 *
	 * @return array
	 */
	protected function get_extensions(): array {

		return [ 'jpg', 'jpeg', 'png', 'webm', 'mp4' ];
	}

	/**
	 * Export entry field data.
	 *
	 * @since 1.9.8
	 *
	 * @param array|mixed $field Field data.
	 *
	 * @return array
	 */
	public function export_entry_field_data( $field ): array {

		$field = (array) $field;
		$value = (string) ( $field['value'] ?? '' );

		$field['value'] = $this->get_formatted_value( $value, $field );

		return $field;
	}

	/**
	 * Get formatted value.
	 *
	 * @since 1.9.8
	 *
	 * @param string $value Field value.
	 * @param array  $field Field settings.
	 *
	 * @return string
	 */
	private function get_formatted_value( string $value, array $field ): string {

		$type = $field['type'] ?? '';

		if ( $type !== $this->type ) {
			return $value;
		}

		if ( empty( $field['style'] ) ) {
			return $this->get_file_url( $field );
		}

		$values = (array) $field['value_raw'];
		$values = array_filter( $values );

		$urls = $this->get_file_urls( $values );

		return empty( $urls ) ? $value : implode( "\n", $urls );
	}

	/**
	 * Get file URLs.
	 *
	 * @since 1.9.8
	 *
	 * @param array $values Field values.
	 *
	 * @return array
	 */
	private function get_file_urls( array $values ): array {

		$urls = [];

		foreach ( $values as $file ) {
			$urls[] = $this->get_file_url( $file );
		}

		return $urls;
	}

	/**
	 * Return strings for localization.
	 *
	 * @since 1.9.8
	 *
	 * @return array
	 */
	private function get_strings(): array {

		$strings = [];

		$strings['camera_access_error']       = esc_html__( 'Camera access denied or not available. Please check your browser permissions', 'wpforms' );
		$strings['camera_video_access_error'] = esc_html__( 'Camera or microphone access denied or not available. Please check your browser permissions', 'wpforms' );
		$strings['video_recording_error']     = esc_html__( 'Video recording is not supported in your browser.', 'wpforms' );

		return $strings;
	}
}
