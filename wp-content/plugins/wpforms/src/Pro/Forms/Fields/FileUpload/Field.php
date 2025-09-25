<?php

namespace WPForms\Pro\Forms\Fields\FileUpload;

use WPForms\Forms\Fields\FileUpload\Field as FieldLite;
use WPForms\Pro\Helpers\Upload;
use WPForms\Forms\Fields\Traits\FileDisplayTrait;
use WPForms\Forms\Fields\Traits\FileMethodsTrait;

/**
 * File upload field.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	use FileDisplayTrait;
	use FileMethodsTrait;

	/**
	 * Dropzone plugin version.
	 *
	 * @since 1.9.4
	 *
	 * @var string
	 */
	public const DROPZONE_VERSION = '5.9.3';

	/**
	 * Handle name for wp_register_styles handle.
	 *
	 * @since 1.9.4
	 *
	 * @var string
	 */
	private const HANDLE = 'wpforms-dropzone';

	/**
	 * Upload files helper.
	 *
	 * @since 1.9.4
	 *
	 * @var Upload
	 */
	private $upload;

	/**
	 * Builder object.
	 *
	 * @since 1.9.4
	 *
	 * @var mixed
	 */
	protected $builder_obj;

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
	 * @since 1.9.4
	 */
	public function init(): void {

		parent::init();

		$this->remove_webfiles_from_denylist();

		/**
		 * Filter defined in WPForms\Pro\Forms\Fields\Camera\Field::init().
		 */
		// phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName, WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.Comments.SinceTagHooks.MissingSinceTag
		$this->wait_time = absint( apply_filters( 'wpforms_pro_forms_fields_camera_field_wait_time_seconds', 3 ) );

		// Init our upload helper and add the actions.
		$this->upload = new Upload();

		$this->init_objects();

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.9.4
	 */
	protected function hooks(): void {

		// Form frontend javascript.
		add_action( 'wpforms_frontend_js', [ $this, 'frontend_js' ] );

		// Form frontend CSS.
		add_action( 'wpforms_frontend_css', [ $this, 'frontend_css' ] );

		// Field styles for Gutenberg. Register after wpforms-pro-integrations.
		add_action( 'init', [ $this, 'register_gutenberg_styles' ], 20 );

		// Set editor style handle for block type editor.
		add_filter( 'register_block_type_args', [ $this, 'register_block_type_args' ], 10, 2 );

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_file-upload', [ $this, 'field_properties' ], 5, 3 );

		// Customize value format.
		add_filter( 'wpforms_html_field_value', [ $this, 'html_field_value' ], 10, 4 );

		// Add builder strings.
		add_filter( 'wpforms_builder_strings', [ $this, 'add_builder_strings' ], 10, 2 );

		// Upload file ajax route.
		add_action( 'wp_ajax_wpforms_file_upload_speed_test', 'wp_send_json_success' );
		add_action( 'wp_ajax_nopriv_wpforms_file_upload_speed_test', 'wp_send_json_success' );

		// Ajax handlers for newest uploads (With chunks and parallel support).
		add_action( 'wp_ajax_wpforms_upload_chunk_init', [ $this, 'ajax_chunk_upload_init' ] );
		add_action( 'wp_ajax_nopriv_wpforms_upload_chunk_init', [ $this, 'ajax_chunk_upload_init' ] );

		add_action( 'wp_ajax_wpforms_upload_chunk', [ $this, 'ajax_chunk_upload' ] );
		add_action( 'wp_ajax_nopriv_wpforms_upload_chunk', [ $this, 'ajax_chunk_upload' ] );

		add_action( 'wp_ajax_wpforms_file_chunks_uploaded', [ $this, 'ajax_chunk_upload_finalize' ] );
		add_action( 'wp_ajax_nopriv_wpforms_file_chunks_uploaded', [ $this, 'ajax_chunk_upload_finalize' ] );

		// Remove file ajax route.
		add_action( 'wp_ajax_wpforms_remove_file', [ $this, 'ajax_modern_remove' ] );
		add_action( 'wp_ajax_nopriv_wpforms_remove_file', [ $this, 'ajax_modern_remove' ] );

		add_action( 'wp_ajax_wpforms_ajax_search_user_names', [ $this, 'ajax_search_user_names' ] );

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['slow'] ) && $_POST['slow'] === 'true' && ! empty( $this->ajax_validate_form_field_modern() ) ) {
			add_action( 'wpforms_file_upload_chunk_parallel', '__return_false' );
			add_action( 'wpforms_file_upload_chunk_size', [ $this, 'get_slow_connection_chunk_size' ] );
		}

		add_filter( 'wpforms_pro_admin_entries_edit_field_output_editable', [ $this, 'is_editable' ], 10, 4 );

		add_filter( 'wpforms_process_after_filter', [ $this, 'upload_complete' ], PHP_INT_MAX, 3 );
		add_action( 'wpforms_process_entry_saved', [ $this, 'create_protection' ], 10, 5 );

		add_filter( 'wpforms_pro_fields_entry_preview_is_field_support_preview_file-upload_field', '__return_false' );

		// Update smart tag value for protected files.
		add_filter( 'wpforms_smart_tags_formatted_field_value', [ $this, 'smart_tags_formatted_field_value' ], 10, 4 );

		// Delete file protection after a file is deleted.
		add_action( 'wpforms_pro_forms_fields_file_upload_field_delete_uploaded_file', [ $this, 'delete_file_protection' ], 10, 2 );

		add_filter( 'wpforms_pro_admin_entries_export_ajax_get_entry_fields_data_field', [ $this, 'export_entry_field_data' ] );

		add_action( 'wpforms_form_handler_duplicate_form', [ $this, 'duplicate_fields_restrictions' ], 10, 3 );
	}

	/**
	 * Initialize objects.
	 *
	 * @since 1.9.4
	 */
	private function init_objects(): void {

		$is_ajax = wp_doing_ajax();

		if ( $is_ajax || wpforms_is_admin_page( 'builder' ) ) {
			$this->builder_obj = $this->get_object( 'Builder' );

			$this->builder_obj->init();
		}
	}

	/**
	 * Remove web files from denylist.
	 *
	 * @since 1.9.4
	 */
	private function remove_webfiles_from_denylist(): void {

		if (
			! function_exists( 'current_user_can' ) ||
			/**
			 * Filter to enable removing web files from denylist.
			 *
			 * @since 1.9.0
			 *
			 * @param bool $enabled Default value is false.
			 *
			 * @return bool
			 */
			! apply_filters( 'wpforms_field_file_upload_remove_webfiles_from_denylist_enabled', false ) // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		) {
			return;
		}

		if ( current_user_can( 'unfiltered_html' ) ) {
			$this->denylist = array_diff( $this->denylist, [ 'htm', 'html', 'js' ] );
		}
	}

	/**
	 * Enqueue frontend field js.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function frontend_js( $forms ): void {

		$min = wpforms_get_min_suffix();

		// Check if among the forms is the field with camera_enabled set to true.
		$camera_enabled = false;

		foreach ( $forms as $form ) {
			if ( $this->is_camera_enabled( $form ) ) {
				$camera_enabled = true;

				break;
			}
		}

		if ( $camera_enabled ) {
			wp_enqueue_script(
				'wpforms-camera-field',
				WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/camera{$min}.js",
				[ 'wpforms' ],
				WPFORMS_VERSION,
				true
			);
		}

		$is_file_modern_style = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$is_file_modern_style = true;

				break;
			}
		}

		if (
			$is_file_modern_style ||
			wpforms()->obj( 'frontend' )->assets_global()
		) {
			wp_enqueue_script(
				self::HANDLE,
				WPFORMS_PLUGIN_URL . 'assets/pro/lib/dropzone.min.js',
				[ 'jquery' ],
				self::DROPZONE_VERSION,
				$this->load_script_in_footer()
			);

			wp_enqueue_script(
				'wpforms-file-upload',
				WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/file-upload.es5{$min}.js",
				[ 'wpforms', 'wp-util', self::HANDLE ],
				WPFORMS_VERSION,
				$this->load_script_in_footer()
			);

			wp_localize_script(
				self::HANDLE,
				'wpforms_file_upload',
				[
					'url'             => admin_url( 'admin-ajax.php' ),
					'errors'          => [
						'default_error'     => esc_html__( 'Something went wrong, please try again.', 'wpforms' ),
						'file_not_uploaded' => esc_html__( 'This file was not uploaded.', 'wpforms' ),
						'file_limit'        => wpforms_setting(
							'validation-maxfilenumber',
							sprintf( /* translators: %s - max number of files allowed. */
								esc_html__( 'File uploads exceed the maximum number allowed (%s).', 'wpforms' ),
								'{fileLimit}'
							)
						),
						'file_extension'    => wpforms_setting( 'validation-fileextension', esc_html__( 'File type is not allowed.', 'wpforms' ) ),
						'file_size'         => wpforms_setting( 'validation-filesize', esc_html__( 'File exceeds the max size allowed.', 'wpforms' ) ),
						'post_max_size'     => sprintf( /* translators: %s - max allowed file size by a server. */
							esc_html__( 'File exceeds the upload limit allowed (%s).', 'wpforms' ),
							wpforms_max_upload()
						),
					],
					'loading_message' => esc_html__( 'File upload is in progress. Please submit the form once uploading is completed.', 'wpforms' ),
				]
			);
		}
	}

	/**
	 * Enqueue frontend field CSS.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function frontend_css( $forms ): void {

		$is_file_modern_style = false;

		foreach ( $forms as $form ) {
			if ( $this->is_field_style( $form, self::STYLE_MODERN ) ) {
				$is_file_modern_style = true;

				break;
			}
		}

		if (
			$is_file_modern_style ||
			wpforms()->obj( 'frontend' )->assets_global()
		) {

			$min = wpforms_get_min_suffix();

			wp_enqueue_style(
				self::HANDLE,
				WPFORMS_PLUGIN_URL . "assets/pro/css/dropzone{$min}.css",
				[],
				self::DROPZONE_VERSION
			);
		}
	}

	/**
	 * Whether the provided form has a file field with a specified style.
	 *
	 * @since 1.9.4
	 *
	 * @param array  $form  Form data.
	 * @param string $style Desired field style.
	 *
	 * @return bool
	 */
	protected function is_field_style( $form, $style ): bool {

		if ( empty( $form['fields'] ) ) {
			return false;
		}

		$is_field_style = false;

		foreach ( (array) $form['fields'] as $field ) {

			if (
				! empty( $field['type'] ) &&
				$field['type'] === $this->type &&
				! empty( $field['style'] ) &&
				$field['style'] === sanitize_key( $style )
			) {
				$is_field_style = true;

				break;
			}
		}

		return $is_field_style;
	}

	/**
	 * Register Gutenberg block styles.
	 *
	 * @since 1.9.4
	 */
	public function register_gutenberg_styles(): void {

		$min  = wpforms_get_min_suffix();
		$deps = is_admin() ? [ 'wpforms-pro-integrations' ] : [];

		wp_register_style(
			self::HANDLE,
			WPFORMS_PLUGIN_URL . "assets/pro/css/dropzone{$min}.css",
			$deps,
			self::DROPZONE_VERSION
		);
	}

	/**
	 * Set editor style handle for block type editor.
	 *
	 * @since 1.9.4
	 *
	 * @param array  $args       Array of arguments for registering a block type.
	 * @param string $block_type Block type name including namespace.
	 */
	public function register_block_type_args( $args, $block_type ) {

		if ( $block_type !== 'wpforms/form-selector' ) {
			return $args;
		}

		// The Full Site Editor (FSE) uses an iframe with the site editor.
		// It inserts into the iframe only those scripts defined during the block registration.
		// Here we set the 'editor_style' field of the 'wpforms/form-selector' block to the current handle.
		// All other styles required for the 'wpforms / form-selector' block will be loaded as dependencies.
		// So, our styles will be loaded in the following order:
		// wpforms-integrations
		// wpforms-gutenberg-form-selector
		// wpforms-pro-integrations
		// wpforms-dropzone.
		$args['editor_style'] = self::HANDLE;

		return $args;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field data and settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {

		$this->form_data  = (array) $form_data;
		$this->form_id    = absint( $this->form_data['id'] );
		$this->field_id   = absint( $field['id'] );
		$this->field_data = $this->form_data['fields'][ $this->field_id ] ?? [];

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "wpforms_{$this->form_id}_{$this->field_id}";

		// Input Primary: filter files in classic uploader style in a file selection window.
		if ( empty( $field['style'] ) || $field['style'] === self::STYLE_CLASSIC ) {
			$properties['inputs']['primary']['attr']['accept'] = rtrim( '.' . implode( ',.', $this->get_extensions() ), ',.' );
		}

		// Input Primary: allowed file extensions.
		$properties['inputs']['primary']['data']['rule-extension'] = implode( ',', $this->get_extensions() );

		// Input Primary: max file size.
		$properties['inputs']['primary']['data']['rule-maxsize'] = $this->max_file_size();

		return $properties;
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		// We need to disable the ability to steal files from user computer.
		return false;
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Current field specific data.
	 *
	 * @return bool
	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		// We need to disable the ability to steal files from user computer.
		return false;
	}

	/**
	 * Add Builder strings that are passed to JS.
	 *
	 * @since 1.9.4
	 *
	 * @param array $strings Form Builder strings.
	 * @param array $form    Form Data.
	 *
	 * @return array Form Builder strings.
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_builder_strings( $strings, $form ) {

		$strings['file_upload'] = $this->get_strings();

		return $strings;
	}

	/**
	 * Only a non-empty field is editable.
	 *
	 * @since 1.9.4
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
	public function is_editable( $is_editable, $field, $entry_fields, $form_data ) {

		if ( $field['type'] !== $this->type ) {
			return $is_editable;
		}

		return ! empty( $entry_fields[ $field['id'] ]['value'] );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Modern style.
		if ( self::is_modern_upload( $field ) ) {

			$strings         = $this->get_strings();
			$max_file_number = $this->get_max_file_number( $field );
			$input_name      = $this->get_input_name();
			$files           = $this->sanitize_modern_files_input();
			$value           = ! empty( $files ) ? wp_json_encode( $files ) : '';
			$count           = count( $files );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wpforms_render(
				'fields/file-upload-frontend',
				[
					'field_id'          => $field['id'],
					'form_id'           => $form_data['id'],
					'value'             => $value,
					'input_name'        => $input_name,
					'required'          => $primary['required'],
					'extensions'        => $primary['data']['rule-extension'],
					'max_size'          => abs( $primary['data']['rule-maxsize'] ),
					'chunk_size'        => $this->get_chunk_size(),
					'max_file_number'   => $max_file_number,
					'preview_hint'      => str_replace( self::TEMPLATE_MAXFILENUM, $max_file_number, $strings['preview_hint'] ),
					'post_max_size'     => wp_max_upload_size(),
					'is_full'           => ! empty( $value ) && $count >= $max_file_number,
					'classes'           => $primary['class'],
					'camera_enabled'    => ! empty( $field['camera_enabled'] ),
					'camera_format'     => $field['camera_format'] ?? 'photo',
					'camera_time_limit' => $this->get_camera_time_limit( $field ),
					'wait_time'         => $this->wait_time,
				],
				true
			);

			return;
		}

		// Classic style.
		printf(
			'<input type="file" %s %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			! empty( $primary['required'] ) ? 'required' : ''
		);

		if ( ! empty( $field['camera_enabled'] ) ) {
			/**
			 * Filter defined in WPForms\Forms\Fields\FileUpload\Field::get_strings().
			 */
			// phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName, WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.Comments.SinceTagHooks.MissingSinceTag
			$classic_camera_text = (string) apply_filters(
				'wpforms_forms_fields_file_upload_field_classic_camera_text',
				__( 'Capture With Your Camera', 'wpforms' )
			);

			printf(
				'<p class="wpforms-file-upload-capture-camera wpforms-file-upload-capture-camera-classic">
					<a class="wpforms-camera-link" href="#">
						%s
					</a>
				</p>',
				esc_html( $classic_camera_text )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wpforms_render(
				'fields/camera-modal',
				[
					'field_id'          => $field['id'],
					'form_id'           => $form_data['id'],
					'camera_format'     => $field['camera_format'] ?? 'photo',
					'camera_time_limit' => $this->get_camera_time_limit( $field ),
					'wait_time'         => $this->wait_time,
				],
				true
			);
		}
	}

	/**
	 * Input name.
	 *
	 * The input name is the name in which the data is expected to be sent in from the client.
	 *
	 * @since 1.9.4
	 *
	 * @return string
	 */
	public function get_input_name(): string {

		return sprintf( 'wpforms_%d_%d', $this->form_id, $this->field_id );
	}

	/**
	 * Maximum size for a chunk in file uploads.
	 *
	 * @since 1.9.4
	 *
	 * @return int
	 */
	public function get_chunk_size(): int {

		/**
		 * Filter the maximum size for a chunk in file uploads.
		 *
		 * @since 1.6.2
		 *
		 * @param int $chunk_size Maximum size for a chunk in file uploads.
		 */
		$chunk_size = apply_filters( 'wpforms_file_upload_chunk_size', 2 * 1024 * 1024 ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		return min( $chunk_size, wp_max_upload_size(), $this->max_file_size() );
	}

	/**
	 * Maximum chunk for slow connections.
	 *
	 * @since 1.9.4
	 *
	 * @return int Chunk size expected for slow connections.
	 */
	public function get_slow_connection_chunk_size(): int {

		return min(
			512 * 1024,
			wp_max_upload_size(),
			$this->max_file_size()
		);
	}

	/**
	 * Validate field for various errors on the form submitted.
	 *
	 * @since 1.9.4
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
		$style            = ! empty( $this->field_data['style'] ) ? $this->field_data['style'] : self::STYLE_CLASSIC;

		// Add modern validate.
		if ( $style === self::STYLE_CLASSIC ) {
			$this->validate_classic( $input_name );
		} else {
			$this->validate_modern( $input_name );
		}
	}

	/**
	 * Validate classic file uploader field data.
	 *
	 * @since 1.9.4
	 *
	 * @param string $deprecated_input_name Input name inside the form on the front-end.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function validate_classic( $deprecated_input_name ): void { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( ! isset( get_defined_vars()['deprecated_input_name'] ) ) {
			_deprecated_argument( __METHOD__, '1.7.2 of the WPForms plugin', 'The `$input_name` argument was deprecated.' );
		}

		$input_name = $this->get_input_name();

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
		$validated_filetype = $this->validate_wp_filetype_and_ext( $_FILES[ $input_name ]['tmp_name'], sanitize_file_name( wp_unslash( $name ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! empty( $validated_filetype ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = $validated_filetype;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Validate modern file uploader field data.
	 *
	 * @since 1.9.4
	 *
	 * @param string $deprecated_input_name Input name inside the form on the front-end.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function validate_modern( $deprecated_input_name ): void {

		if ( ! isset( get_defined_vars()['deprecated_input_name'] ) ) {
			_deprecated_argument( __METHOD__, '1.7.2 of the WPForms plugin', 'The `$input_name` argument was deprecated.' );
		}

		$value = $this->sanitize_modern_files_input();

		if (
			empty( $value ) &&
			$this->is_required() &&
			/**
			 * Filter to skip validation for a required file upload field.
			 *
			 * @since 1.9.8
			 *
			 * @param bool  $skip       Whether to skip validation.
			 * @param array $form_data  Form data.
			 * @param array $field_data Field data.
			 * @param array $value      Field value.
			 *
			 * @return bool
			 */
			! apply_filters( 'wpforms_pro_forms_fields_file_upload_field_skip_validation', false, $this->form_data, $this->field_data, $value )
		) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = wpforms_get_required_label();

			return;
		}

		if ( ! empty( $value ) ) {
			$this->validate_modern_files( $value );
		}
	}

	/**
	 * Sanitize modern files input.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	private function sanitize_modern_files_input() {

		$input_name = $this->get_input_name();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$json_value = isset( $_POST[ $input_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) ) : '';
		$files      = json_decode( $json_value, true );

		if ( empty( $files ) || ! is_array( $files ) ) {
			return [];
		}

		return array_filter( array_map( [ $this, 'sanitize_modern_file' ], $files ) );
	}

	/**
	 * Sanitize modern file.
	 *
	 * @since 1.9.4
	 *
	 * @param array $file File information.
	 *
	 * @return array
	 */
	private function sanitize_modern_file( $file ) {

		if ( empty( $file['file'] ) || empty( $file['name'] ) ) {
			return [];
		}

		$sanitized_file = [];
		$rules          = [
			'name'           => 'sanitize_file_name',
			'file'           => 'sanitize_file_name',
			'url'            => 'esc_url_raw',
			'size'           => 'absint',
			'type'           => 'sanitize_text_field',
			'file_user_name' => 'sanitize_text_field',
		];

		foreach ( $rules as $rule => $callback ) {
			$file_attribute          = $file[ $rule ] ?? '';
			$sanitized_file[ $rule ] = $callback( $file_attribute );
		}

		return $sanitized_file;
	}

	/**
	 * Validate files for a modern file upload field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $files List of uploaded files.
	 */
	private function validate_modern_files( $files ): void {

		if ( ! $this->has_missing_tmp_file( $files ) ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = esc_html__( 'File(s) not uploaded. Remove and re-attach file(s).', 'wpforms' );

			return;
		}

		$max_file_number = $this->get_max_file_number( $this->field_data );

		if ( count( $files ) > $max_file_number ) {
			wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = str_replace(
				'{fileLimit}',
				$max_file_number,
				wpforms_setting(
					'validation-maxfilenumber',
					sprintf( /* translators: %s - max number of files allowed. */
						esc_html__( 'File uploads exceed the maximum number allowed (%s).', 'wpforms' ),
						'{fileLimit}'
					)
				)
			);

			return;
		}

		foreach ( $files as $file ) {
			$path      = trailingslashit( $this->get_tmp_dir() ) . $file['file'];
			$file_size = filesize( $path );
			$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			$errors    = wpforms_chain( [] )
				->array_merge( (array) $this->validate_size( [ $file_size ] ) )
				->array_merge( (array) $this->validate_extension( $extension ) )
				->array_merge( (array) $this->validate_wp_filetype_and_ext( $path, $file['name'] ) )
				->array_filter()
				->value();

			if ( ! empty( $errors ) ) {
				wpforms()->obj( 'process' )->errors[ $this->form_id ][ $this->field_id ] = implode( ' ', $errors );

				return;
			}
		}
	}

	/**
	 * Check if files exist in the temp directory.
	 *
	 * @since 1.9.4
	 *
	 * @param array $files List of files.
	 *
	 * @return bool
	 */
	private function has_missing_tmp_file( $files ) {

		foreach ( $files as $file ) {
			if ( empty( $file['file'] ) || ! is_file( trailingslashit( $this->get_tmp_dir() ) . $file['file'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field_id    = absint( $field_id );
		$field_label = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '';
		$style       = ! empty( $form_data['fields'][ $field_id ]['style'] ) && $form_data['fields'][ $field_id ]['style'] === self::STYLE_MODERN
			? self::STYLE_MODERN
			: self::STYLE_CLASSIC;

		if ( $style === self::STYLE_CLASSIC ) {
			wpforms()->obj( 'process' )->fields[ $field_id ] = [
				'name'          => $field_label,
				'value'         => '',
				'file'          => '',
				'file_original' => '',
				'ext'           => '',
				'id'            => $field_id,
				'type'          => $this->type,
			];

			return;
		}

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'      => $field_label,
			'value'     => '',
			'value_raw' => '',
			'id'        => $field_id,
			'type'      => $this->type,
			'style'     => self::STYLE_MODERN,
		];
	}

	/**
	 * Create protection for uploaded files.
	 *
	 * @since 1.9.4
	 *
	 * @param array $fields     Form fields data.
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
	 * @since 1.9.4
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

		$restriction = wpforms()->obj( 'file_restrictions' )->get_restriction( $form_id, $field['id'] );

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
	 * @since 1.9.4
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

		wpforms()->obj( 'protected_files' )->create_protection( $args );
	}

	/**
	 * Format the field value for smart tags.
	 *
	 * @since 1.9.4
	 *
	 * @param string $value     The field value.
	 * @param int    $field_id  The field ID.
	 * @param array  $fields    The form fields.
	 * @param string $field_key The field key.
	 *
	 * @return string
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function smart_tags_formatted_field_value( $value, $field_id, $fields, $field_key ) {

		$field = $fields[ $field_id ] ?? [];

		return $this->get_formatted_value( $value, $field );
	}

	/**
	 * Get formatted value.
	 *
	 * @since 1.9.4
	 *
	 * @param string $value Field value.
	 * @param array  $field Field settings.
	 *
	 * @return string
	 */
	private function get_formatted_value( $value, array $field ) {

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
	 * Export entry field data.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	public function export_entry_field_data( $field ): array {

		$field = (array) $field;
		$value = $field['value'] ?? '';

		$field['value'] = $this->get_formatted_value( $value, $field );

		return $field;
	}

	/**
	 * Duplicate field restrictions when duplicating a form.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $id            Original form ID.
	 * @param int   $new_form_id   New form ID.
	 * @param array $new_form_data New form data.
	 */
	public function duplicate_fields_restrictions( $id, $new_form_id, $new_form_data ): void {

		$fields = $new_form_data['fields'] ?? [];

		$file_restrictions = wpforms()->obj( 'file_restrictions' );

		foreach ( $fields as $field ) {
			if ( empty( $field['type'] ) || $field['type'] !== $this->type ) {
				continue;
			}

			$restriction = $file_restrictions->get_restriction( $id, $field['id'] );

			if ( empty( $restriction ) ) {
				continue;
			}

			unset( $restriction['id'] );

			$restriction['form_id'] = $new_form_id;

			// Check if the duplicated form already has a restriction for this field.
			$existing_restriction = $file_restrictions->get_restriction( $new_form_id, $field['id'] );

			if ( ! empty( $existing_restriction ) ) {
				wpforms()->obj( 'file_restrictions' )->update( $existing_restriction['id'], $restriction );
				continue;
			}

			wpforms()->obj( 'file_restrictions' )->add( $restriction );
		}
	}

	/**
	 * Get file URLs.
	 *
	 * @since 1.9.4
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
	 * Complete the upload process for all upload fields.
	 *
	 * @since 1.9.4
	 *
	 * @param array $fields    Fields data.
	 * @param array $entry     Submitted form entry.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function upload_complete( $fields, $entry, $form_data ) {

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
			$is_visible       = ! isset( wpforms()->obj( 'process' )->fields[ $field_id ]['visible'] ) || ! empty( wpforms()->obj( 'process' )->fields[ $field_id ]['visible'] );

			$fields[ $field_id ]['visible'] = $is_visible;

			if ( ! $is_visible ) {
				continue;
			}

			$fields[ $field_id ] = self::is_modern_upload( $field )
				? $this->complete_upload_modern( $field )
				: $this->complete_upload_classic( $field );
		}

		return $fields;
	}

	/**
	 * Complete the upload process for the classic upload field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $processed_field Processed field data.
	 *
	 * @return array
	 */
	private function complete_upload_classic( $processed_field ): array {

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
	 * Complete the upload process for the modern upload field.
	 *
	 * @since 1.9.4
	 *
	 * @param array $processed_field Processed field data.
	 *
	 * @return array
	 */
	private function complete_upload_modern( $processed_field ) {

		$files = $this->sanitize_modern_files_input();

		if ( empty( $files ) ) {
			return $processed_field;
		}

		wpforms_create_upload_dir_htaccess_file();

		$upload_dir = wpforms_upload_dir();

		if ( empty( $upload_dir['error'] ) ) {
			wpforms_create_index_html_file( $upload_dir['path'] );
		}

		$data = [];

		foreach ( $files as $file ) {
			$data[] = $this->process_file( $file );
		}

		$data                         = array_filter( $data );
		$processed_field['value_raw'] = $data;
		$processed_field['value']     = wpforms_chain( $data )
			->map(
				static function ( $file ) {

					return $file['value'];
				}
			)
			->implode( "\n" )
			->value();

		return $processed_field;
	}

	/**
	 * Generate ready for DB data for each file.
	 *
	 * @since 1.9.4
	 *
	 * @param array $file File to generate data for.
	 *
	 * @return array
	 */
	protected function generate_file_data( $file ) {

		$data = [
			'name'           => sanitize_text_field( $file['file_name'] ),
			'value'          => esc_url_raw( $file['file_url'] ),
			'file'           => $file['file_name_new'],
			'file_original'  => $file['file_name'],
			'file_user_name' => sanitize_text_field( $file['file_user_name'] ),
			'ext'            => wpforms_chain( $file['file'] )->explode( '.' )->pop()->value(),
			'attachment_id'  => isset( $file['attachment_id'] ) ? absint( $file['attachment_id'] ) : 0,
			'id'             => $this->field_id,
			'type'           => $file['type'],
		];

		if ( ! empty( $file['protection_hash'] ) ) {
			$data['protection_hash'] = $file['protection_hash'];
		}

		return $data;
	}


	/**
	 * Clean up the tmp folder - remove all old files every day (filterable interval).
	 *
	 * @since 1.9.4
	 */
	protected function clean_tmp_files(): void {

		$files = glob( trailingslashit( $this->get_tmp_dir() ) . '*' );

		if ( ! is_array( $files ) || empty( $files ) ) {
			return;
		}

		/**
		 * Filter the lifespan of temporary files.
		 *
		 * @since 1.5.6
		 *
		 * @param int $lifespan Lifespan of temporary files in seconds.
		 *                      Default is 1 day.
		 */
		$lifespan = (int) apply_filters( 'wpforms_field_' . $this->type . '_clean_tmp_files_lifespan', DAY_IN_SECONDS ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		foreach ( $files as $file ) {
			if ( $file === 'index.html' || ! is_file( $file ) ) {
				continue;
			}

			// In some cases filemtime() can return false, in that case - pretend this is a new file and do nothing.
			$modified = (int) filemtime( $file );

			if ( empty( $modified ) ) {
				$modified = time();
			}

			if ( ( time() - $modified ) >= $lifespan ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
				@unlink( $file );
			}
		}
	}

	/**
	 * Remove the file from the temporary directory.
	 *
	 * @since 1.9.4
	 */
	public function ajax_modern_remove(): void {

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$default_error = esc_html__( 'Something went wrong while removing the file.', 'wpforms' );

		$validated_form_field = $this->ajax_validate_form_field_modern();

		if ( empty( $validated_form_field ) ) {
			wp_send_json_error( $default_error, 400 );
		}

		if ( empty( $_POST['file'] ) ) {
			wp_send_json_error( $default_error, 403 );
		}

		// Don't delete the file - it will get removed through the clean_tmp_files() method later.
		wp_send_json_success( sanitize_file_name( wp_unslash( $_POST['file'] ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Initializes the chunk upload process.
	 *
	 * No data is being sent by the client,
	 * they're expecting an authorization from this method before sending any chunk.
	 *
	 * The server may return different configs to the uploader client (smaller chunks, disable
	 * parallel uploads, etc.).
	 *
	 * This method would validate the file extension, maximum size, and other things.
	 *
	 * @since 1.9.4
	 */
	public function ajax_chunk_upload_init(): void {

		$default_error = esc_html__( 'Something went wrong, please try again.', 'wpforms' );

		$validated_form_field = $this->ajax_validate_form_field_modern();

		if ( empty( $validated_form_field ) ) {
			wp_send_json_error( $default_error );
		}

		$handler = Chunk::from_current_request( $this );

		if ( ! $handler || ! $handler->create_metadata() ) {
			wp_send_json_error( $default_error, 403 );
		}

		$error     = 0;
		$name      = sanitize_file_name( wp_unslash( $handler->get_file_name() ) );
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$errors    = wpforms_chain( [] )
			->array_merge( (array) $this->validate_basic( $error ) )
			->array_merge( (array) $this->validate_size( [ $handler->get_file_size() ] ) )
			->array_merge( (array) $this->validate_extension( $extension ) )
			->array_filter()
			->value();

		if ( count( $errors ) > 0 ) {
			wp_send_json_error( implode( ',', $errors ) );
		}

		/**
		 * Filter to enable/disable parallel chunk uploads.
		 *
		 * @since 1.6.2
		 *
		 * @param bool $is_parallel True to enable parallel uploads, false to disable.
		 */
		$is_parallel = apply_filters( 'wpforms_file_upload_chunk_parallel', true ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		wp_send_json(
			[
				'success' => true,
				'data'    => [
					'dzchunksize'          => $handler->get_chunk_size(),
					'parallelChunkUploads' => $is_parallel,
				],
			]
		);
	}

	/**
	 * Upload the files using chunks.
	 *
	 * @since 1.9.4
	 */
	public function ajax_chunk_upload(): void {

		$default_error = esc_html__( 'Something went wrong, please try again.', 'wpforms' );

		$validated_form_field = $this->ajax_validate_form_field_modern();

		if ( empty( $validated_form_field ) ) {
			wp_send_json_error( $default_error );
		}

		$handler = Chunk::from_current_request( $this );

		if ( ! $handler || ! $handler->load_metadata() ) {
			wp_send_json_error( $default_error, 403 );
		}

		if ( ! $handler->write() ) {
			wp_send_json_error( $default_error, 403 );
		}

		wp_send_json( [ 'success' => true ] );
	}

	/**
	 * Ajax handler for finalizing a chunked upload.
	 *
	 * @since 1.9.4
	 */
	public function ajax_chunk_upload_finalize(): void {

		$default_error = esc_html__( 'Something went wrong, please try again.', 'wpforms' );
		$handler       = Chunk::from_current_request( $this );

		if ( ! $handler || ! $handler->load_metadata() ) {
			wp_send_json_error( $default_error, 403 );
		}

		$file_name      = $handler->get_file_name();
		$file_user_name = $handler->get_file_user_name();
		$extension      = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		$tmp_dir        = $this->get_tmp_dir();
		$tmp_name       = $this->get_tmp_file_name( $extension );
		$tmp_path       = wp_normalize_path( $tmp_dir . '/' . $tmp_name );
		$file_new       = pathinfo( $tmp_path, PATHINFO_FILENAME ) . '.' . pathinfo( $tmp_path, PATHINFO_EXTENSION );

		if ( ! $handler->finalize( $tmp_path, $file_name ) ) {
			wp_send_json_error( $default_error, 403 );
		}

		$is_valid_type = $this->validate_wp_filetype_and_ext( $tmp_path, $file_name );

		if ( $is_valid_type !== false ) {
			wp_send_json_error( $is_valid_type, 403 );
		}

		$this->clean_tmp_files();

		wp_send_json_success(
			[
				'name'           => $file_name,
				'file'           => $file_new,
				'url'            => $this->get_tmp_url() . '/' . $file_new,
				'size'           => filesize( $tmp_path ),
				'type'           => wp_check_filetype( $tmp_path )['type'],
				'file_user_name' => $file_user_name,
			]
		);
	}

	/**
	 * Validate form ID, field ID, and field style for existence and that they are actually valid.
	 *
	 * @since 1.9.4
	 *
	 * @return array Empty array on any kind of failure.
	 */
	protected function ajax_validate_form_field_modern(): array {

		if (
			empty( $_POST['form_id'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing
			empty( $_POST['field_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		) {
			return [];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_data = wpforms()->obj( 'form' )->get( (int) $_POST['form_id'], [ 'content_only' => true ] );

		if ( empty( $form_data ) || ! is_array( $form_data ) ) {
			return [];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$field_id = absint( $_POST['field_id'] );

		if (
			! isset( $form_data['fields'][ $field_id ]['style'] ) ||
			$form_data['fields'][ $field_id ]['style'] !== self::STYLE_MODERN
		) {
			return [];
		}

		// Make data available everywhere in the class, so we don't need to pass it manually.
		$this->form_data  = $form_data;
		$this->form_id    = $this->form_data['id'];
		$this->field_id   = $field_id;
		$this->field_data = $this->form_data['fields'][ $this->field_id ];

		return [
			'form_data' => $form_data,
			'field_id'  => $field_id,
		];
	}

	/**
	 * Ajax handler for searching usernames.
	 *
	 * @since 1.9.4
	 */
	public function ajax_search_user_names(): void {

		// Run a security check.
		if ( ! check_ajax_referer( 'wpforms-builder', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the builder.', 'wpforms' ) );
		}

		// Check for permissions.
		if ( ! wpforms_current_user_can( 'edit_forms' ) ) {
			wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'wpforms' ) );
		}

		if ( ! isset( $_GET['search'] ) ) {
			wp_send_json_error( esc_html__( 'Incorrect usage of this operation.', 'wpforms' ) );
		}

		$search_name = sanitize_text_field( wp_unslash( $_GET['search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		/**
		 * Filter the columns to search for usernames.
		 *
		 * @since 1.9.4
		 *
		 * @param array $search_columns Columns to search for usernames.
		 */
		$search_columns = (array) apply_filters( 'wpforms_pro_forms_fields_file_upload_field_ajax_search_user_names_columns', [ 'user_login', 'display_name' ] );

		$args = [
			'search'         => '*' . $search_name . '*',
			'search_columns' => $search_columns,
			'number'         => 10,
			'fields'         => [ 'ID', 'display_name' ],
		];

		if ( array_key_exists( 'exclude', $_GET ) ) {
			$exclude = array_map( 'absint', $_GET['exclude'] );

			$args['exclude'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		// Get the list of users.
		$users = get_users( $args );

		$users = array_map(
			static function ( $user ) {

				return [
					'value' => $user->ID,
					'label' => $user->display_name,
				];
			},
			$users
		);

		if ( empty( $users ) ) {
			wp_send_json_success( [] );
		}

		wp_send_json_success( $users );
	}

	/**
	 * Basic file upload validation.
	 *
	 * @since 1.9.4
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
	 * Generate both the file info and the file data to send to the database.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $file File to generate data from.
	 *
	 * @return array File data.
	 */
	public function process_file( $file ): array {

		$file = (array) $file;

		$file['tmp_name'] = trailingslashit( $this->get_tmp_dir() ) . $file['file'];
		$file['type']     = 'application/octet-stream';

		if ( is_file( $file['tmp_name'] ) ) {
			$filetype     = wp_check_filetype( $file['tmp_name'] );
			$file['type'] = $filetype['type'];
			$file['size'] = filesize( $file['tmp_name'] );
		}

		$uploaded_file = $this->upload->process_file(
			$file,
			$this->field_id,
			$this->form_data,
			$this->is_media_integrated()
		);

		if ( empty( $uploaded_file ) ) {
			return [];
		}

		$uploaded_file['file']           = $file['file'];
		$uploaded_file['file_user_name'] = $file['file_user_name'];
		$uploaded_file['type']           = $file['type'];

		return $this->generate_file_data( $uploaded_file );
	}

	/**
	 * Validate file size.
	 *
	 * @since 1.9.4
	 *
	 * @param array $sizes Array with all file sizes in bytes.
	 *
	 * @return false|string False if no errors found, error text otherwise.
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
				return sprintf( /* translators: $s - allowed file size in MB. */
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
	 * @since 1.9.4
	 *
	 * @param string $ext Extension.
	 *
	 * @return false|string False if no errors found, error text otherwise.
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
	 * @since 1.9.4
	 *
	 * @param string $path Path to a newly uploaded file.
	 * @param string $name Name of a newly uploaded file.
	 *
	 * @return false|string False if no errors found, error text otherwise.
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
	 * Get form-specific uploads directory path and URL.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	protected function get_form_files_dir(): array {

		$upload_dir = wpforms_upload_dir();
		$folder     = absint( $this->form_data['id'] ) . '-' . wp_hash( $this->form_data['created'] . $this->form_data['id'] );

		return [
			'path' => trailingslashit( $upload_dir['path'] ) . $folder,
			'url'  => trailingslashit( $upload_dir['url'] ) . $folder,
		];
	}

	/**
	 * Get tmp dir for files.
	 *
	 * @since 1.9.4
	 *
	 * @return string
	 */
	public function get_tmp_dir(): string {

		$upload_dir = wpforms_upload_dir();
		$tmp_root   = $upload_dir['path'] . '/tmp';

		if ( ! file_exists( $tmp_root ) || ! wp_is_writable( $tmp_root ) ) {
			wp_mkdir_p( $tmp_root );
		}

		// Check if the index.html exists in the directory, if not - create it.
		wpforms_create_index_html_file( $tmp_root );

		return $tmp_root;
	}

	/**
	 * Get tmp url for files.
	 *
	 * @since 1.9.4
	 *
	 * @return string
	 */
	private function get_tmp_url(): string {

		$upload_dir = wpforms_upload_dir();

		return $upload_dir['url'] . '/tmp';
	}

	/**
	 * Create both the directory and index.html file in it if any of them doesn't exist.
	 *
	 * @since 1.9.4
	 *
	 * @param string $path Path to the directory.
	 *
	 * @return string Path to the newly created directory.
	 */
	protected function create_dir( $path ): string {

		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		// Check if the index.html exists in the path, if not - create it.
		wpforms_create_index_html_file( $path );

		return $path;
	}

	/**
	 * Get tmp file name.
	 *
	 * @since 1.9.4
	 *
	 * @param string $extension File extension.
	 *
	 * @return string
	 */
	protected function get_tmp_file_name( $extension ): string {

		return wp_hash( wp_rand() . microtime() . $this->form_id . $this->field_id ) . '.' . $extension;
	}

	/**
	 * Move a file to a permanent location.
	 *
	 * @since 1.9.4
	 *
	 * @param string $path_from From.
	 * @param string $path_to   To.
	 *
	 * @return false|string False on error.
	 */
	protected function move_file( $path_from, $path_to ) {

		$this->create_dir( dirname( $path_to ) );

		// phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
		if ( false === move_uploaded_file( $path_from, $path_to ) ) {
			wpforms_log(
				'Upload Error, could not upload file',
				$path_from,
				[
					'type' => [ 'entry', 'error' ],
				]
			);

			return false;
		}

		$this->upload->set_file_fs_permissions( $path_to );

		return $path_to;
	}


	/**
	 * Whether a field is required or not.
	 *
	 * @uses $this->field_data
	 *
	 * @since 1.9.4
	 *
	 * @return bool
	 */
	protected function is_required(): bool {

		return ! empty( $this->field_data['required'] );
	}

	/**
	 * Whether the field is integrated with WordPress Media Library.
	 *
	 * @uses $this->field_data
	 *
	 * @since 1.9.4
	 *
	 * @return bool
	 */
	protected function is_media_integrated(): bool {

		return ! empty( $this->field_data['media_library'] ) && $this->field_data['media_library'] === '1';
	}



	/**
	 * Get a Form files path.
	 *
	 * @since 1.9.4
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return string
	 */
	public static function get_form_files_path( $form_id ): string {

		$form_data = wpforms()->obj( 'form' )->get( $form_id );

		if ( empty( $form_data ) ) {
			return '';
		}

		$upload_dir = wpforms_upload_dir();

		return trailingslashit( $upload_dir['path'] ) . ( new Upload() )->get_form_directory( $form_data->ID, $form_data->post_date );
	}

	/**
	 * Fallback method to get a Form files path for already existing uploads with incorrectly generated hashes
	 * (files uploaded before version 1.7.6).
	 *
	 * @since 1.9.4
	 *
	 * @param string $form_id Form ID.
	 *
	 * @return string
	 */
	private static function get_form_files_path_backward_fallback( $form_id ): string {

		$form_data = wpforms()->obj( 'form' )->get( $form_id );

		if ( empty( $form_data ) ) {
			return '';
		}

		$upload_dir = wpforms_upload_dir();

		return trailingslashit( $upload_dir['path'] ) . absint( $form_data->ID ) . '-' . md5( $form_data->post_date . $form_data->ID );
	}

	/**
	 * Maybe delete uploaded files from entry.
	 *
	 * @since 1.9.4
	 *
	 * @param string $entry_id       Entry ID.
	 * @param array  $delete_fields  Fields to delete.
	 * @param array  $exclude_fields Exclude fields.
	 *
	 * @return array Removed files names.
	 */
	public static function delete_uploaded_files_from_entry( $entry_id, $delete_fields = [], $exclude_fields = [] ): array {

		$removed_files = [];
		$entry         = wpforms()->obj( 'entry' )->get( $entry_id );

		if ( empty( $entry ) ) {
			return $removed_files;
		}

		$files_path = self::get_form_files_path( $entry->form_id );

		if ( ! is_dir( $files_path ) ) {
			$files_path = self::get_form_files_path_backward_fallback( $entry->form_id );
		}

		$fields_to_delete = $delete_fields ? $delete_fields : (array) wpforms_decode( $entry->fields );

		foreach ( $fields_to_delete as $field ) {

			if ( ! isset( $field['type'] ) || $field['type'] !== 'file-upload' || ( $exclude_fields && ! isset( $exclude_fields[ $field['id'] ] ) ) ) {
				continue;
			}

			$removed_files = self::delete_uploaded_file_from_entry( $removed_files, $field, $exclude_fields, $files_path, $entry );
		}

		return $removed_files;
	}

	/**
	 * Maybe delete an uploaded file from entry.
	 *
	 * @since 1.9.4
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

		if ( ! self::is_modern_upload( $field ) ) {

			$removed_files[] = self::delete_uploaded_file( $files_path, $field, $entry );

			return $removed_files;
		}

		$values = $field['value_raw'];

		if ( $exclude_fields ) {
			$values = ! empty( $field['value_raw'] ) ? array_diff_key( $exclude_fields[ $field['id'] ]['value_raw'], $field['value_raw'] ) : $exclude_fields[ $field['id'] ]['value_raw'];
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
	 * Delete uploaded file.
	 *
	 * @since 1.9.4
	 *
	 * @param string $files_path Path to files.
	 * @param array  $file_data  File data.
	 * @param object $entry      Entry.
	 *
	 * @return string
	 */
	private static function delete_uploaded_file( $files_path, $file_data, $entry ) {

		if ( empty( $file_data['file'] ) ) {
			return '';
		}

		// We delete attachments from Media Library only for spam entries.
		if ( $entry->status === 'spam' && ! empty( $file_data['attachment_id'] ) ) {
			wp_delete_attachment( $file_data['attachment_id'], true );

			return $file_data['file_user_name'];
		}

		$file = trailingslashit( $files_path ) . $file_data['file'];

		if ( ! is_file( $file ) ) {
			return '';
		}

		/**
		 * Fires before the uploaded file is deleted.
		 *
		 * @since 1.9.4
		 *
		 * @param array  $file_data File data.
		 * @param object $entry     Entry object.
		 */
		do_action( 'wpforms_pro_forms_fields_file_upload_field_delete_uploaded_file', $file_data, $entry );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		unlink( $file );

		return $file_data['file_user_name'];
	}

	/**
	 * Check if modern upload was used.
	 *
	 * @param array $field_data Field data.
	 *
	 * @since 1.9.4
	 *
	 * @return bool
	 */
	public static function is_modern_upload( $field_data ): bool {

		return isset( $field_data['style'] ) && $field_data['style'] === self::STYLE_MODERN;
	}

	/**
	 * Returns an array containing the file paths of the files uploading in a file upload entry.
	 *
	 * @since 1.9.4
	 *
	 * @param string $form_id           Form ID.
	 * @param array  $entry_field       Entry field data.
	 * @param bool   $exclude_protected Whether to exclude protected files.
	 *
	 * @return array The file path of the uploaded file. Returns an empty string if the file path isn't fetched.
	 */
	public static function get_entry_field_file_paths( $form_id, $entry_field, bool $exclude_protected = true ): array {

		$form_file_path = self::get_form_files_path( $form_id );
		$files          = [];

		if ( self::is_modern_upload( $entry_field ) ) {

			foreach ( $entry_field['value_raw'] as $value ) {
				$file_path = self::get_file_path( $value['attachment_id'], $value['file'], $form_file_path );

				if ( empty( $file_path ) || ( $exclude_protected && ! empty( $value['protection_hash'] ) ) ) {
					continue;
				}

				$files[] = $file_path;
			}
		} else {
			if ( $exclude_protected && ! empty( $entry_field['protection_hash'] ) ) {
				return $files;
			}

			$files[] = self::get_file_path( $entry_field['attachment_id'], $entry_field['file'], $form_file_path );
		}

		return $files;
	}

	/**
	 * Returns the file path of a given attachment ID or file name.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $attachment_id  Attachment ID.
	 * @param string $file_name      File name.
	 * @param string $file_base_path The base path of uploaded files.
	 *
	 * @return string
	 */
	public static function get_file_path( $attachment_id, $file_name, $file_base_path ): string {

		$file_path = empty( $attachment_id ) ? trailingslashit( $file_base_path ) . $file_name : get_attached_file( $attachment_id );

		return ( empty( $file_path ) || ! is_file( $file_path ) ) ? '' : $file_path;
	}

	/**
	 * Delete file protection.
	 *
	 * @since 1.9.4
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

		wpforms()->obj( 'protected_files' )->delete_protection( $hash );
	}

	/**
	 * Get access restrictions options attributes.
	 *
	 * @since 1.9.4
	 *
	 * @return array
	 */
	protected function get_access_restrictions_options_attrs(): array {

		$addons_obj = wpforms()->obj( 'addons' );

		if ( ! $addons_obj ) {
			return [];
		}

		$post_submissions = $addons_obj->get_addon( 'post-submissions' );

		$status = $post_submissions['status'] ?? '';

		// If Post Submissions is not installed return an empty array.
		if ( empty( $post_submissions ) || $status === 'missing' ) {
			return [];
		}

		$version = $post_submissions['version'] ?? '';
		$version = defined( 'WPFORMS_POST_SUBMISSIONS_VERSION' ) ? WPFORMS_POST_SUBMISSIONS_VERSION : $version;

		// Add the attribute to disable the field if the Post Submissions version is less than 1.8.0.
		if ( wpforms_version_compare( $version, '1.8.0', '<' ) ) {
			return [ 'post-submissions-disabled' => true ];
		}

		return [];
	}
}
