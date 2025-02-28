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
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$properties = (array) $properties;

		// Prevent "spell-jacking" of passwords.
		$properties['inputs']['primary']['attr']['spellcheck'] = 'false';

		if ( ! empty( $field['password-strength'] ) ) {
			$properties['inputs']['primary']['data']['rule-password-strength']  = true;
			$properties['inputs']['primary']['data']['password-strength-level'] = $field['password-strength-level'];
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

		// Input Primary: add error class if needed.
		if ( ! empty( $properties['error']['value']['primary'] ) ) {
			$properties['inputs']['primary']['class'][] = 'wpforms-error';
		}

		// Input Secondary: add error class if needed.
		if ( ! empty( $properties['error']['value']['secondary'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'wpforms-error';
		}

		// Input Secondary: add required class if needed.
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

		// Standard password field.
		if ( ! $confirmation ) {
			// Primary field.
			printf(
				'<input type="password" %s %s>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
		} else { // Confirmation password field configuration.
			// Row wrapper.
			echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';
				// Primary field.
				echo '<div ' . wpforms_html_attributes( false, $primary['block'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->field_display_sublabel( 'primary', 'before', $field );
					printf(
						'<input type="password" %s %s>',
						wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_attr( $primary['required'] )
					);
					$this->field_display_sublabel( 'primary', 'after', $field );
					$this->field_display_error( 'primary', $field );
				echo '</div>';

				// Secondary field.
				echo '<div ' . wpforms_html_attributes( false, $secondary['block'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$this->field_display_sublabel( 'secondary', 'before', $field );
					printf(
						'<input type="password" %s %s>',
						wpforms_html_attributes( $secondary['id'], $secondary['class'], $secondary['data'], $secondary['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_attr( $secondary['required'] )
					);
					$this->field_display_sublabel( 'secondary', 'after', $field );
					$this->field_display_error( 'secondary', $field );
				echo '</div>';
			echo '</div>';
		}
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
	public function validate( $field_id, $field_submit, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

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

		if ( ! $this->strength_enabled( $forms ) && ! wpforms()->obj( 'frontend' )->assets_global() ) {
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

		if ( ! $this->strength_enabled( $forms ) && ! wpforms()->obj( 'frontend' )->assets_global() ) {
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
	 * Check if password strength enabled.
	 *
	 * @since 1.9.4
	 *
	 * @param array $forms Form data of forms on the current page.
	 *
	 * @return bool
	 */
	private function strength_enabled( $forms ): bool {

		foreach ( $forms as $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				continue;
			}

			foreach ( $form_data['fields'] as $field ) {
				if ( $field['type'] === 'password' && isset( $field['password-strength'] ) ) {
					return (bool) $field['password-strength'];
				}
			}
		}

		return false;
	}

	/**
	 * Add password strength validation error to frontend strings.
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
	 * Modify value for the entry preview field.
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
	 * Checks if password field has been submitted empty and set as not required at the same time.
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
				// If submitted value is empty or is an array of empty values (that happens when password confirmation is enabled).
				empty( $field_submit ) || empty( implode( '', array_values( (array) $field_submit ) ) )
			)
			&& empty( $fields[ $field_id ]['required'] ); // If field is not set as required.
	}

	/**
	 * Determine if the field requires fieldset instead of the regular field label.
	 *
	 * @since 1.9.4
	 *
	 * @param bool  $requires_fieldset True if it requires fieldset.
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
