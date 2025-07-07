<?php

namespace WPForms\Pro\Forms\Fields\Phone;

use WPForms\Forms\Fields\Phone\Field as FieldLite;

/**
 * Phone number field.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	/**
	 * Hooks.
	 *
	 * @since 1.9.4
	 */
	protected function hooks() {

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_phone', [ $this, 'field_properties' ], 5, 3 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_frontend_css' ] );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		// Add frontend strings.
		add_filter( 'wpforms_frontend_strings', [ $this, 'add_frontend_strings' ] );

		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_builder_enqueues' ] );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_properties( $properties, $field, $form_data ): array {

		$properties = (array) $properties;

		$properties['inputs']['primary']['attr']['aria-label'] = $field['label'] ?? esc_html__( 'Phone number', 'wpforms' );

		$field['format'] = ! empty( $field['format'] ) ? $field['format'] : 'smart';

		// Smart: add validation rule and class.
		if ( $field['format'] === 'smart' ) {
			$properties['inputs']['primary']['class'][]                        = 'wpforms-smart-phone-field';
			$properties['inputs']['primary']['data']['rule-smart-phone-field'] = 'true';
		}

		// US: add input mask and class.
		if ( $field['format'] === 'us' ) {
			$properties['inputs']['primary']['class'][]                     = 'wpforms-masked-input';
			$properties['inputs']['primary']['data']['inputmask']           = "'mask': '(999) 999-9999'";
			$properties['inputs']['primary']['data']['rule-us-phone-field'] = 'true';
			$properties['inputs']['primary']['data']['inputmask-inputmode'] = 'tel';
		}

		// International: add validation rule and class.
		if ( $field['format'] === 'international' ) {
			$properties['inputs']['primary']['data']['rule-int-phone-field'] = 'true';
		}

		return $properties;
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_frontend_css( $forms ): void {

		$forms = (array) $forms;

		$phone_formats = $this->get_formats( $forms );

		if ( empty( $phone_formats['smart'] ) && ! wpforms()->obj( 'frontend' )->assets_global() ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		// International Telephone Input library CSS.
		wp_enqueue_style(
			'wpforms-smart-phone-field',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/phone/intl-tel-input{$min}.css",
			[],
			self::INTL_VERSION
		);
	}

	/**
	 * Enqueue script for the admin form builder.
	 *
	 * @since 1.9.4
	 */
	public function admin_builder_enqueues(): void {

		$min = wpforms_get_min_suffix();

		// JavaScript.
		wp_enqueue_script(
			'wpforms-builder-phone-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/builder/fields/phone{$min}.js",
			[ 'jquery', 'wpforms-builder' ],
			WPFORMS_VERSION,
			false
		);
	}

	/**
	 * Form frontend JS enqueues.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_frontend_js( $forms ): void {

		$forms = (array) $forms;

		$phone_formats = $this->get_formats( $forms );

		if ( ! $phone_formats && ! wpforms()->obj( 'frontend' )->assets_global() ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		// Load International Telephone Input library only if phones with "smart" format exist.
		// https://github.com/jackocnr/intl-tel-input.
		if ( ! empty( $phone_formats['smart'] ) ) {
			wp_enqueue_script(
				'wpforms-smart-phone-field',
				WPFORMS_PLUGIN_URL . 'assets/pro/lib/intl-tel-input/intlTelInputWithUtils.min.js',
				[],
				self::INTL_VERSION,
				$this->load_script_in_footer()
			);
		}

		wp_enqueue_script(
			'wpforms-smart-phone-field-core',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/phone{$min}.js",
			[ 'jquery' ],
			WPFORMS_VERSION,
			$this->load_script_in_footer()
		);
	}

	/**
	 * Retrieve phone formats from the provided forms.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Form data of forms on the current page.
	 *
	 * @return array An array of phone formats.
	 */
	private function get_formats( array $forms ): array {

		$formats = [];

		foreach ( $forms as $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				continue;
			}

			foreach ( $form_data['fields'] as $field ) {
				if (
					! isset( $field['type'], $field['format'] ) ||
					isset( $formats[ $field['format'] ] ) ||
					$field['type'] !== $this->type
				) {
					continue;
				}

				$formats[ $field['format'] ] = true;

				// Return immediately, because for smart format we will enqueue all phone-related scripts,
				// which covers "US" and "International" formats.
				if ( isset( $formats['smart'] ) ) {
					return $formats;
				}
			}
		}

		return $formats;
	}

	/**
	 * Add phone validation error to frontend strings.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $strings Frontend strings.
	 *
	 * @return array Frontend strings.
	 */
	public function add_frontend_strings( $strings ): array {

		$strings = (array) $strings;

		$strings['val_phone'] = wpforms_setting( 'validation-phone', esc_html__( 'Please enter a valid phone number.', 'wpforms' ) );

		return $strings;
	}

	/**
	 * Get a preview option.
	 *
	 * @since 1.9.3
	 *
	 * @param string $option  Option name.
	 * @param array  $field   Field data.
	 * @param array  $args    Additional arguments.
	 * @param bool   $do_echo Echo or return.
	 */
	public function field_preview_option( $option, $field, $args = [], $do_echo = true ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound

		// Skip preview option for the editor.
		if ( wpforms_is_editor_page() ) {
			return;
		}

		parent::field_preview_option( $option, $field, $args, $do_echo );
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
	 * @noinspection HtmlWrongAttributeValue
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		if ( wpforms_is_editor_page() ) {
			$this->field_preview( $field );

			return;
		}

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		/**
		 * Allow an input type to be changed for this particular field.
		 *
		 * @since 1.4.0
		 *
		 * @param string $type Input type.
		 */
		$type = apply_filters( 'wpforms_phone_field_input_type', 'tel' ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		// Primary field.
		printf(
			'<input type="%s" %s %s>',
			esc_attr( $type ),
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Validate field on form submitted.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$form_id = $form_data['id'];
		$value   = $this->sanitize_value( $field_submit );

		// If the field is marked as required, check for entry data.
		if (
			! empty( $form_data['fields'][ $field_id ]['required'] ) &&
			empty( $value )
		) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_get_required_label();
		}

		if (
			empty( $value ) ||
			empty( $form_data['fields'][ $field_id ]['format'] )
		) {
			return;
		}

		$value  = preg_replace( '/[^\d]/', '', $value );
		$length = strlen( $value );

		if ( $form_data['fields'][ $field_id ]['format'] === 'us' ) {
			$error = $length !== 10;
		} else {
			$error = $length === 0;
		}

		if ( $error ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_setting( 'validation-phone', esc_html__( 'Please enter a valid phone number.', 'wpforms' ) );
		}
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $field_id     Field id.
	 * @param string $field_submit Submitted value.
	 * @param array  $form_data    Form data.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';

		// Set final field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => sanitize_text_field( $name ),
			'value' => $this->sanitize_value( $field_submit ),
			'id'    => wpforms_validate_field_id( $field_id ),
			'type'  => $this->type,
		];
	}

	/**
	 * Sanitize the value.
	 *
	 * @since 1.9.4
	 *
	 * @param string $value The Phone field submitted value.
	 *
	 * @return string
	 */
	private function sanitize_value( $value ): string {

		return preg_replace( '/[^-+0-9() ]/', '', $value );
	}
}
