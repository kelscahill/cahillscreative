<?php

namespace WPForms\Pro\Forms\Fields\Password;

use WPForms\Forms\Fields\Password\Field as FieldLite;
use ZxcvbnPhp\Zxcvbn;

/**
 * Password field.
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
		add_filter( 'wpforms_field_properties_password', [ $this, 'field_properties' ], 5, 3 );

		// Set confirmation status to option wrapper class.
		add_filter( 'wpforms_builder_field_option_class', [ $this, 'field_option_class' ], 10, 2 );

		// Form frontend CSS enqueues.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_frontend_css' ] );

		// Form frontend JS enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		// Add frontend strings.
		add_filter( 'wpforms_frontend_strings', [ $this, 'add_frontend_strings' ] );

		add_action( 'wpforms_pro_fields_entry_preview_get_field_value_password_field', [ $this, 'modify_entry_preview_value' ], 10, 3 );

		// This field requires fieldset+legend instead of the field label.
		add_filter( "wpforms_frontend_modern_is_field_requires_fieldset_{$this->type}", [ $this, 'is_field_requires_fieldset' ], PHP_INT_MAX, 2 );
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
	 */
	public function field_properties( $properties, $field, $form_data ): array {

		$properties = (array) $properties;

		// Prevent "spell-jacking" of passwords.
		$properties['inputs']['primary']['attr']['spellcheck'] = 'false';

		if ( ! empty( $field['password-strength'] ) ) {
			$properties['inputs']['primary']['data']['rule-password-strength']  = true;
			$properties['inputs']['primary']['data']['password-strength-level'] = $field['password-strength-level'];
		}

		if ( ! empty( $field['password-visibility'] ) ) {
			$properties['container']['class'][] = 'wpforms-field-password-visibility-enabled';
		}

		if ( empty( $field['confirmation'] ) ) {
			return $properties;
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = wpforms_validate_field_id( $field['id'] );

		// Password confirmation setting enabled.
		$props = [
			'inputs' => [
				'primary'   => [
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
						'wpforms-first',
					],
					'class'    => [
						'wpforms-field-password-primary',
					],
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Password', 'wpforms' ),
					],
				],
				'secondary' => [
					'attr'     => [
						'name'        => "wpforms[fields][{$field_id}][secondary]",
						'value'       => '',
						'placeholder' => ! empty( $field['confirmation_placeholder'] ) ? $field['confirmation_placeholder'] : '',
						'spellcheck'  => 'false',
					],
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
					],
					'class'    => [
						'wpforms-field-password-secondary',
					],
					'data'     => [
						'rule-confirm' => '#' . $properties['inputs']['primary']['id'],
					],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-secondary",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Confirm Password', 'wpforms' ),
					],
					'value'    => '',
				],
			],
		];

		$properties = array_merge_recursive( $properties, $props );

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "wpforms[fields][{$field_id}][primary]";

		// Input Primary: remove size and error classes.
		$properties['inputs']['primary']['class'] = array_diff(
			$properties['inputs']['primary']['class'],
			[
				'wpforms-field-' . sanitize_html_class( $field['size'] ),
				'wpforms-error',
			]
		);

		// Input Primary: add an error class if needed.
		if ( ! empty( $properties['error']['value']['primary'] ) ) {
			$properties['inputs']['primary']['class'][] = 'wpforms-error';
		}

		// Input Secondary: add an error class if needed.
		if ( ! empty( $properties['error']['value']['secondary'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'wpforms-error';
		}

		// Input Secondary: add the required class if needed.
		if ( ! empty( $field['required'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'wpforms-field-required';
		}

		// Remove reference to an input element to prevent duplication.
		if ( empty( $field['sublabel_hide'] ) ) {
			unset( $properties['label']['attr']['for'] );
		}

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

		return false;
	}

	/**
	 * Add class to field options wrapper to indicate if field confirmation is enabled.
	 *
	 * @since 1.9.4
	 *
	 * @param string|mixed $css_class Class name.
	 * @param array        $field     Field data.
	 *
	 * @return string
	 */
	public function field_option_class( $css_class, $field ): string {

		$css_class = (string) $css_class;

		if ( $field['type'] !== 'password' ) {
			return $css_class;
		}

		return isset( $field['confirmation'] ) ? 'wpforms-confirm-enabled' : 'wpforms-confirm-disabled';
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
		$confirmation = ! empty( $field['confirmation'] );
		$primary      = $field['properties']['inputs']['primary'];
		$secondary    = ! empty( $field['properties']['inputs']['secondary'] ) ? $field['properties']['inputs']['secondary'] : [];

		$field_markup = '<input type="password" %1$s %2$s>';

		if ( ! empty( $field['password-visibility'] ) ) {
			$field_markup  = '<div class="wpforms-field-password-input">';
			$field_markup .= '<input type="password" %1$s %2$s>';

			$field_markup .= sprintf(
				'<a href="#" class="wpforms-field-password-input-icon" aria-label="%1$s" title="%1$s" data-switch-title="%2$s">
					<svg class="wpforms-field-password-input-icon-invisible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" role="img"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
					<svg class="wpforms-field-password-input-icon-visible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" role="img"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L525.6 386.7c39.6-40.6 66.4-86.1 79.9-118.4c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C465.5 68.8 400.8 32 320 32c-68.2 0-125 26.3-169.3 60.8L38.8 5.1zM223.1 149.5C248.6 126.2 282.7 112 320 112c79.5 0 144 64.5 144 144c0 24.9-6.3 48.3-17.4 68.7L408 294.5c8.4-19.3 10.6-41.4 4.8-63.3c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3c0 10.2-2.4 19.8-6.6 28.3l-90.3-70.8zM373 389.9c-16.4 6.5-34.3 10.1-53 10.1c-79.5 0-144-64.5-144-144c0-6.9 .5-13.6 1.4-20.2L83.1 161.5C60.3 191.2 44 220.8 34.5 243.7c-3.3 7.9-3.3 16.7 0 24.6c14.9 35.7 46.2 87.7 93 131.1C174.5 443.2 239.2 480 320 480c47.8 0 89.9-12.9 126.2-32.5L373 389.9z"/></svg>
				</a>',
				__( 'Show Password', 'wpforms' ),
				__( 'Hide Password', 'wpforms' )
			);

			$field_markup .= '</div>';
		}

		// Standard password field.
		if ( ! $confirmation ) {
			// Primary field.
			printf( // The `$field_markup` variable is escaped above, we should escape only passed variables to placeholders.
				$field_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			return;
		}

		// Confirmation password field configuration.
		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';
			// Primary field.
			echo '<div ' . wpforms_html_attributes( false, $primary['block'] ) . '>';
				$this->field_display_sublabel( 'primary', 'before', $field );
				printf( // The `$field_markup` variable is escaped above, we should escape only passed variables to placeholders.
					$field_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
					esc_attr( $primary['required'] )
				);
				$this->field_display_sublabel( 'primary', 'after', $field );
				$this->field_display_error( 'primary', $field );
			echo '</div>';

			// Secondary field.
			echo '<div ' . wpforms_html_attributes( false, $secondary['block'] ) . '>';
				$this->field_display_sublabel( 'secondary', 'before', $field );
				printf( // The `$field_markup` variable is escaped above, we should escape only passed variables to placeholders.
					$field_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wpforms_html_attributes( $secondary['id'], $secondary['class'], $secondary['data'], $secondary['attr'] ),
					esc_attr( $secondary['required'] )
				);
				$this->field_display_sublabel( 'secondary', 'after', $field );
				$this->field_display_error( 'secondary', $field );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Validate field on form submission.
	 *
	 * @since 1.9.4
	 *
	 * @param int          $field_id     Field ID.
	 * @param array|string $field_submit Submitted field value (raw data).
	 * @param array        $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$form_id  = $form_data['id'];
		$fields   = $form_data['fields'];
		$required = wpforms_get_required_label();

		// Standard configuration, confirmation disabled.
		if ( empty( $fields[ $field_id ]['confirmation'] ) ) {

			// Required check.
			if ( ! empty( $fields[ $field_id ]['required'] ) && wpforms_is_empty_string( $field_submit ) ) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = $required;
			}
		} else {

			if ( ! empty( $fields[ $field_id ]['required'] ) && isset( $field_submit['primary'] ) && wpforms_is_empty_string( $field_submit['primary'] ) ) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['primary'] = $required;
			}

			// Required check, secondary confirmation field.
			if ( ! empty( $fields[ $field_id ]['required'] ) && isset( $field_submit['secondary'] ) && wpforms_is_empty_string( $field_submit['secondary'] ) ) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['secondary'] = $required;
			}

			// Fields need to match.
			if ( $field_submit['primary'] !== $field_submit['secondary'] ) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['secondary'] = esc_html__( 'Field values do not match.', 'wpforms' );
			}
		}

		if (
			! empty( $fields[ $field_id ]['password-strength'] ) &&
			! empty( $fields[ $field_id ]['password-strength-level'] ) &&
			PHP_VERSION_ID >= 70200 &&
			! $this->is_empty_not_required_field( $field_id, $field_submit, $fields ) // Don't check the password strength for empty fields which is set as not required.
		) {

			require_once WPFORMS_PLUGIN_DIR . 'pro/libs/bjeavons/zxcvbn-php/autoload.php';

			$password_value = empty( $fields[ $field_id ]['confirmation'] ) ? $field_submit : $field_submit['primary'];
			$strength       = ( new Zxcvbn() )->passwordStrength( $password_value );

			if ( isset( $strength['score'] ) && $strength['score'] < (int) $fields[ $field_id ]['password-strength-level'] ) {
				wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = $this->strength_error_message();
			}
		}
	}

	/**
	 * Format and sanitize field.
	 *
	 * @since 1.9.4
	 *
	 * @param int          $field_id     Field ID.
	 * @param array|string $field_submit Submitted field value.
	 * @param array        $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		// Define data.
		if ( is_array( $field_submit ) ) {
			$value = isset( $field_submit['primary'] ) && ! wpforms_is_empty_string( $field_submit['primary'] ) ? $field_submit['primary'] : '';
		} else {
			$value = ! wpforms_is_empty_string( $field_submit ) ? $field_submit : '';
		}

		$name = ! wpforms_is_empty_string( $form_data['fields'][ $field_id ] ['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';

		// Set final field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'      => sanitize_text_field( $name ),
			'value'     => sanitize_text_field( $value ),
			'value_raw' => $value, // This is necessary for the login form to work correctly, it will be deleted before saving the entry.
			'id'        => wpforms_validate_field_id( $field_id ),
			'type'      => $this->type,
		];
	}

	/**
	 * Form frontend CSS enqueues.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Form data of forms on the current page.
	 */
	public function enqueue_frontend_css( $forms ): void {

		$frontend_obj = wpforms()->obj( 'frontend' );

		if ( ! $frontend_obj ) {
			return;
		}

		if (
			! $this->option_is_enabled( $forms, 'password-strength' )
			&& ! $this->option_is_enabled( $forms, 'password-visibility' )
			&& ! $frontend_obj->assets_global()
		) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-password-field',
			WPFORMS_PLUGIN_URL . "assets/pro/css/fields/password{$min}.css",
			[],
			WPFORMS_VERSION
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

		$frontend_obj = wpforms()->obj( 'frontend' );

		if ( ! $frontend_obj ) {
			return;
		}

		if (
			! $this->option_is_enabled( $forms, 'password-strength' )
			&& ! $this->option_is_enabled( $forms, 'password-visibility' )
			&& ! $frontend_obj->assets_global()
		) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-password-field',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/fields/password{$min}.js",
			[ 'jquery', 'password-strength-meter' ],
			WPFORMS_VERSION,
			$this->load_script_in_footer()
		);
	}

	/**
	 * Check if a password field option is enabled in any of the forms.
	 *
	 * @since 1.9.8
	 *
	 * @param array  $forms       Form data of forms on the current page.
	 * @param string $option_name Name of the password field option to check.
	 *
	 * @return bool
	 */
	private function option_is_enabled( array $forms, string $option_name ): bool {

		foreach ( $forms as $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				continue;
			}

			foreach ( $form_data['fields'] as $field ) {
				if ( $field['type'] === 'password' && ! empty( $field[ $option_name ] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add a password strength validation error to frontend strings.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $strings Frontend strings.
	 *
	 * @return array Frontend strings.
	 */
	public function add_frontend_strings( $strings ): array {

		$strings = (array) $strings;

		$strings['val_password_strength'] = $this->strength_error_message();

		return $strings;
	}

	/**
	 * Get a strength error message.
	 *
	 * @since 1.9.4
	 *
	 * @return string
	 */
	private function strength_error_message(): string {

		return wpforms_setting( 'validation-passwordstrength', esc_html__( 'A stronger password is required. Consider using upper and lower case letters, numbers, and symbols.', 'wpforms' ) );
	}

	/**
	 * Modify the value for the entry preview field.
	 *
	 * @since 1.9.4
	 *
	 * @param string $value     Value.
	 * @param array  $field     Field data.
	 * @param array  $form_data Form data.
	 *
	 * @return string
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function modify_entry_preview_value( $value, $field, $form_data ): string {

		return str_repeat( '*', strlen( $value ) );
	}

	/**
	 * Checks if the password field has been submitted empty and set as not required at the same time.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $fields       Fields settings.
	 *
	 * @return bool
	 */
	private function is_empty_not_required_field( $field_id, $field_submit, $fields ): bool {

		return (
				// If the submitted value is empty or is an array of empty values (that happens when password confirmation is enabled).
				empty( $field_submit ) || empty( implode( '', array_values( (array) $field_submit ) ) )
			)
			&& empty( $fields[ $field_id ]['required'] ); // If the field is not set as required.
	}

	/**
	 * Determine if the field requires fieldset instead of the regular field label.
	 *
	 * @since 1.9.4
	 *
	 * @param bool  $requires_fieldset True if it requires a fieldset.
	 * @param array $field             Field data.
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function is_field_requires_fieldset( $requires_fieldset, $field ): bool {

		return ! empty( $field['confirmation'] );
	}
}
