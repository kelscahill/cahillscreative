<?php

namespace WPForms\Pro\Forms\Fields\Address;

use WPForms\Forms\Fields\Address\Field as FieldLite;

/**
 * Address field.
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
		add_filter( 'wpforms_field_properties_address', [ $this, 'field_properties' ], 5, 3 );

		// Customize value format.
		add_filter( 'wpforms_html_field_value', [ $this, 'html_field_value' ], 10, 4 );

		// This field requires fieldset+legend instead of the field label.
		add_filter( "wpforms_frontend_modern_is_field_requires_fieldset_{$this->type}", '__return_true', PHP_INT_MAX, 2 );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.9.4
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field data and settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$properties = (array) $properties;

		// Determine the scheme we should use moving forward.
		$scheme = 'us';

		if ( ! empty( $field['scheme'] ) ) {
			$scheme = esc_attr( $field['scheme'] );
		} elseif ( ! empty( $field['format'] ) ) {
			// <1.2.7 backwards compatibility.
			$scheme = esc_attr( $field['format'] );
		}

		// Expanded formats.
		// Remove primary for expanded formats.
		unset( $properties['inputs']['primary'] );

		// Remove reference to an input element to prevent duplication.
		if ( empty( $field['sublabel_hide'] ) ) {
			unset( $properties['label']['attr']['for'] );
		}

		$form_id   = absint( $form_data['id'] );
		$field_id  = wpforms_validate_field_id( $field['id'] );
		$countries = $this->schemes[ $scheme ]['countries'] ?? [];

		asort( $countries );

		$states            = $this->schemes[ $scheme ]['states'] ?? '';
		$state_placeholder = ! empty( $field['state_placeholder'] ) ? $field['state_placeholder'] : '';

		// Set placeholder for state dropdown.
		if ( is_array( $states ) && ! $state_placeholder ) {
			$state_placeholder = $this->dropdown_empty_value( 'state' );
		}

		// Properties shared by both core schemes.
		$props      = [
			'inputs' => [
				'address1' => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][address1]",
						'value'       => ! empty( $field['address1_default'] ) ? wpforms_process_smart_tags( $field['address1_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => ! empty( $field['address1_placeholder'] ) ? $field['address1_placeholder'] : '',
					],
					'block'    => [],
					'class'    => [
						'wpforms-field-address-address1',
					],
					'data'     => [],
					'id'       => "wpforms-$form_id-field_$field_id",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['address1_label'] ?? '',
					],
				],
				'address2' => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][address2]",
						'value'       => ! empty( $field['address2_default'] ) ? wpforms_process_smart_tags( $field['address2_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => ! empty( $field['address2_placeholder'] ) ? $field['address2_placeholder'] : '',
					],
					'block'    => [],
					'class'    => [
						'wpforms-field-address-address2',
					],
					'data'     => [],
					'hidden'   => ! empty( $field['address2_hide'] ),
					'id'       => "wpforms-$form_id-field_$field_id-address2",
					'required' => '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['address2_label'] ?? '',
					],
				],
				'city'     => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][city]",
						'value'       => ! empty( $field['city_default'] ) ? wpforms_process_smart_tags( $field['city_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => ! empty( $field['city_placeholder'] ) ? $field['city_placeholder'] : '',
					],
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
						'wpforms-first',
					],
					'class'    => [
						'wpforms-field-address-city',
					],
					'data'     => [],
					'id'       => "wpforms-$form_id-field_$field_id-city",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['city_label'] ?? '',
					],
				],
				'state'    => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][state]",
						'value'       => ! empty( $field['state_default'] ) ? wpforms_process_smart_tags( $field['state_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => $state_placeholder,
					],
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
					],
					'class'    => [
						'wpforms-field-address-state',
					],
					'data'     => [],
					'id'       => "wpforms-$form_id-field_$field_id-state",
					'options'  => $states,
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['state_label'] ?? '',
					],
				],
				'postal'   => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][postal]",
						'value'       => ! empty( $field['postal_default'] ) ? wpforms_process_smart_tags( $field['postal_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => ! empty( $field['postal_placeholder'] ) ? $field['postal_placeholder'] : '',
					],
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
						'wpforms-first',
					],
					'class'    => [
						'wpforms-field-address-postal',
					],
					'data'     => [],
					'hidden'   => ! empty( $field['postal_hide'] ) || ! isset( $this->schemes[ $scheme ]['postal_label'] ),
					'id'       => "wpforms-$form_id-field_$field_id-postal",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['postal_label'] ?? '',
					],
				],
				'country'  => [
					'attr'     => [
						'name'        => "wpforms[fields][$field_id][country]",
						'value'       => ! empty( $field['country_default'] ) ? wpforms_process_smart_tags( $field['country_default'], $form_data, [], '', 'field-properties' ) : '',
						'placeholder' => ! empty( $field['country_placeholder'] ) ? $field['country_placeholder'] : $this->dropdown_empty_value( 'country' ),
					],
					'block'    => [
						'wpforms-field-row-block',
						'wpforms-one-half',
					],
					'class'    => [
						'wpforms-field-address-country',
					],
					'data'     => [],
					'hidden'   => ! empty( $field['country_hide'] ) || ! isset( $this->schemes[ $scheme ]['countries'] ),
					'id'       => "wpforms-$form_id-field_$field_id-country",
					'options'  => $countries,
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => $this->schemes[ $scheme ]['country_label'] ?? '',
					],
				],
			],
		];
		$properties = array_merge_recursive( $properties, $props );

		// Input keys.
		$keys = [ 'address1', 'address2', 'city', 'state', 'postal', 'country' ];

		// Add input error class if needed.
		foreach ( $keys as $key ) {
			if ( ! empty( $properties['error']['value'][ $key ] ) ) {
				$properties['inputs'][ $key ]['class'][] = 'wpforms-error';
			}
		}

		// Add input required class if needed.
		foreach ( $keys as $key ) {
			if ( ! empty( $properties['inputs'][ $key ]['required'] ) ) {
				$properties['inputs'][ $key ]['class'][] = 'wpforms-field-required';
			}
		}

		// Add Postal code input mask for US address.
		if ( $scheme === 'us' ) {
			$properties['inputs']['postal']['class'][]                           = 'wpforms-masked-input';
			$properties['inputs']['postal']['data']['inputmask-mask']            = '(99999)|(99999-9999)';
			$properties['inputs']['postal']['data']['inputmask-keepstatic']      = 'true';
			$properties['inputs']['postal']['data']['rule-inputmask-incomplete'] = true;
		}

		return $properties;
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties instead.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		// Define data.
		$format   = ! empty( $field['format'] ) ? esc_attr( $field['format'] ) : 'us';
		$scheme   = ! empty( $field['scheme'] ) ? esc_attr( $field['scheme'] ) : $format;
		$address1 = ! empty( $field['properties']['inputs']['address1'] ) ? $field['properties']['inputs']['address1'] : [];
		$address2 = ! empty( $field['properties']['inputs']['address2'] ) ? $field['properties']['inputs']['address2'] : [];
		$city     = ! empty( $field['properties']['inputs']['city'] ) ? $field['properties']['inputs']['city'] : [];
		$state    = ! empty( $field['properties']['inputs']['state'] ) ? $field['properties']['inputs']['state'] : [];
		$postal   = ! empty( $field['properties']['inputs']['postal'] ) ? $field['properties']['inputs']['postal'] : [];
		$country  = ! empty( $field['properties']['inputs']['country'] ) ? $field['properties']['inputs']['country'] : [];

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

			// Address Line 1.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div ' . wpforms_html_attributes( false, $address1['block'] ) . '>';
				$this->field_display_sublabel( 'address1', 'before', $field );
				printf(
					'<input type="text" %s %s>',
					wpforms_html_attributes( $address1['id'], $address1['class'], $address1['data'], $address1['attr'] ),
					! empty( $address1['required'] ) ? 'required' : ''
				);
				$this->field_display_sublabel( 'address1', 'after', $field );
				$this->field_display_error( 'address1', $field );
			echo '</div>';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';

		if ( empty( $address2['hidden'] ) ) {

			// Row wrapper.
			echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

				// Address Line 2.
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<div ' . wpforms_html_attributes( false, $address2['block'] ) . '>';
					$this->field_display_sublabel( 'address2', 'before', $field );
					printf(
						'<input type="text" %s %s>',
						wpforms_html_attributes( $address2['id'], $address2['class'], $address2['data'], $address2['attr'] ),
						! empty( $address2['required'] ) ? 'required' : ''
					);
					$this->field_display_sublabel( 'address2', 'after', $field );
					$this->field_display_error( 'address2', $field );
				echo '</div>';
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</div>';
		}

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

			// City.
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div ' . wpforms_html_attributes( false, $city['block'] ) . '>';
				$this->field_display_sublabel( 'city', 'before', $field );
				printf(
					'<input type="text" %s %s>',
					wpforms_html_attributes( $city['id'], $city['class'], $city['data'], $city['attr'] ),
					! empty( $city['required'] ) ? 'required' : ''
				);
				$this->field_display_sublabel( 'city', 'after', $field );
				$this->field_display_error( 'city', $field );
			echo '</div>';

			// State.
			if ( isset( $this->schemes[ $scheme ]['states'], $state['options'] ) ) {

				// Remove placeholder attribute from <select>.
				$placeholder = ! empty( $state['attr']['placeholder'] ) ? $state['attr']['placeholder'] : '';

				echo '<div ' . wpforms_html_attributes( false, $state['block'] ) . '>';
					$this->field_display_sublabel( 'state', 'before', $field );
					if ( empty( $state['options'] ) ) {
						printf(
							'<input type="text" %s %s>',
							wpforms_html_attributes( $state['id'], $state['class'], $state['data'], $state['attr'] ),
							! empty( $state['required'] ) ? 'required' : ''
						);
					} else {
						// We need to clear the value attribute since it's not allowed for <select>.
						$state_default = $state['attr']['value'] ?? '';

						unset( $state['attr']['placeholder'], $state['attr']['value'] );
						printf(
							'<select %s %s>',
							wpforms_html_attributes( $state['id'], $state['class'], $state['data'], $state['attr'] ),
							! empty( $state['required'] ) ? 'required' : ''
						);
							// Revert default value.
						$state['attr']['value'] = $state_default;

						echo $this->get_state_select_options( $placeholder, $state );
						echo '</select>';
					}
					$this->field_display_sublabel( 'state', 'after', $field );
					$this->field_display_error( 'state', $field );
				echo '</div>';
			}
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';

		// Only render this row if we have at least one of the items.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! empty( $country['hidden'] ) && ! empty( $postal['hidden'] ) ) {
			return;
		}

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

			// Postal.
			if ( empty( $postal['hidden'] ) ) {

				echo '<div ' . wpforms_html_attributes( false, $postal['block'] ) . '>';
					$this->field_display_sublabel( 'postal', 'before', $field );
					printf(
						'<input type="text" %s %s>',
						wpforms_html_attributes( $postal['id'], $postal['class'], $postal['data'], $postal['attr'] ),
						! empty( $postal['required'] ) ? 'required' : ''
					);
					$this->field_display_sublabel( 'postal', 'after', $field );
					$this->field_display_error( 'postal', $field );
				echo '</div>';
			}

			// Country.
			if ( isset( $country['options'] ) && empty( $country['hidden'] ) ) {

				// Remove placeholder attribute from <select>.
				$placeholder = ! empty( $country['attr']['placeholder'] ) ? $country['attr']['placeholder'] : '';

				unset( $country['attr']['placeholder'] );

				echo '<div ' . wpforms_html_attributes( false, $country['block'] ) . '>';
					$this->field_display_sublabel( 'country', 'before', $field );
					if ( empty( $country['options'] ) ) {
						printf(
							'<input type="text" %s %s>',
							wpforms_html_attributes( $country['id'], $country['class'], $country['data'], $country['attr'] ),
							! empty( $country['required'] ) ? 'required' : ''
						);
					} else {
						printf(
							'<select %s %s>',
							wpforms_html_attributes( $country['id'], $country['class'], $country['data'], $country['attr'] ),
							! empty( $country['required'] ) ? 'required' : ''
						);
						echo $this->get_state_select_options( $placeholder, $country );
						echo '</select>';
					}
					$this->field_display_sublabel( 'country', 'after', $field );
					$this->field_display_error( 'country', $field );
				echo '</div>';
			}

		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Validate field form submit.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		if ( empty( $form_data['fields'][ $field_id ] ) ) {
			return;
		}

		$field    = $form_data['fields'][ $field_id ];
		$form_id  = $form_data['id'];
		$required = wpforms_get_required_label();
		$format   = ! empty( $field['format'] ) ? $field['format'] : 'us';
		$scheme   = ! empty( $field['scheme'] ) ? $field['scheme'] : $format;

		// Extended required validation needed for the different address fields.
		if ( empty( $field['required'] ) ) {
			return;
		}

		// Require Address Line 1.
		if ( isset( $field_submit['address1'] ) && wpforms_is_empty_string( $field_submit['address1'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['address1'] = $required;
		}

		// Require City.
		if ( isset( $field_submit['city'] ) && wpforms_is_empty_string( $field_submit['city'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['city'] = $required;
		}

		// Require ZIP/Postal.
		if ( isset( $this->schemes[ $scheme ]['postal_label'], $field_submit['postal'] ) && empty( $field['postal_hide'] ) && wpforms_is_empty_string( $field_submit['postal'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['postal'] = $required;
		}

		// Required State.
		if ( isset( $this->schemes[ $scheme ]['states'], $field_submit['state'] ) && wpforms_is_empty_string( $field_submit['state'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['state'] = $required;
		}

		// Required Country.
		if ( isset( $this->schemes[ $scheme ]['countries'], $field_submit['country'] ) && empty( $field['country_hide'] ) && wpforms_is_empty_string( $field_submit['country'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ]['country'] = $required;
		}
	}

	/**
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field values.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$name     = isset( $form_data['fields'][ $field_id ]['label'] ) && ! wpforms_is_empty_string( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';
		$address1 = isset( $field_submit['address1'] ) && ! wpforms_is_empty_string( $field_submit['address1'] ) ? $field_submit['address1'] : '';
		$address2 = isset( $field_submit['address2'] ) && ! wpforms_is_empty_string( $field_submit['address2'] ) ? $field_submit['address2'] : '';
		$city     = isset( $field_submit['city'] ) && ! wpforms_is_empty_string( $field_submit['city'] ) ? $field_submit['city'] : '';
		$state    = isset( $field_submit['state'] ) && ! wpforms_is_empty_string( $field_submit['state'] ) ? $field_submit['state'] : '';
		$postal   = isset( $field_submit['postal'] ) && ! wpforms_is_empty_string( $field_submit['postal'] ) ? $field_submit['postal'] : '';

		// If еру scheme type is 'us', define US as a country field value.
		if ( ! empty( $form_data['fields'][ $field_id ]['scheme'] ) && $form_data['fields'][ $field_id ]['scheme'] === 'us' ) {
			$country = 'US';
		} else {
			$country = isset( $field_submit['country'] ) && ! wpforms_is_empty_string( $field_submit['country'] ) ? $field_submit['country'] : '';
		}

		$value  = ! wpforms_is_empty_string( $address1 ) ? "$address1\n" : '';
		$value .= ! wpforms_is_empty_string( $address2 ) ? "$address2\n" : '';

		if ( ! wpforms_is_empty_string( $city ) && ! wpforms_is_empty_string( $state ) ) {
			$value .= "$city, $state\n";
		} elseif ( ! wpforms_is_empty_string( $state ) ) {
			$value .= "$state\n";
		} elseif ( ! wpforms_is_empty_string( $city ) ) {
			$value .= "$city\n";
		}
		$value .= ! wpforms_is_empty_string( $postal ) ? "$postal\n" : '';
		$value .= ! wpforms_is_empty_string( $country ) ? "$country\n" : '';
		$value  = wpforms_sanitize_textarea_field( $value );

		if ( wpforms_is_empty_string( $city ) && wpforms_is_empty_string( $address1 ) ) {
			$value = '';
		}

		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'     => sanitize_text_field( $name ),
			'value'    => $value,
			'id'       => wpforms_validate_field_id( $field_id ),
			'type'     => $this->type,
			'address1' => sanitize_text_field( $address1 ),
			'address2' => sanitize_text_field( $address2 ),
			'city'     => sanitize_text_field( $city ),
			'state'    => sanitize_text_field( $state ),
			'postal'   => sanitize_text_field( $postal ),
			'country'  => sanitize_text_field( $country ),
		];
	}

	/**
	 * Get field name for an ajax error message.
	 *
	 * @since 1.9.4
	 *
	 * @param string|mixed    $name  Field name for error triggered.
	 * @param array           $field Field settings.
	 * @param array           $props List of properties.
	 * @param string|string[] $error Error message.
	 *
	 * @return string
	 */
	public function ajax_error_field_name( $name, $field, $props, $error ): string {

		$name = (string) $name;

		if ( ! isset( $field['type'] ) || $field['type'] !== 'address' ) {
			return $name;
		}

		if ( ! isset( $field['scheme'] ) ) {
			return $name;
		}

		if ( $field['scheme'] === 'us' ) {
			$input = $props['inputs']['postal'] ?? [];
		} else {
			$input = $props['inputs']['country'] ?? [];
		}

		return $input['attr']['name'] ?? $name;
	}

	/**
	 * Customize a format for HTML display.
	 *
	 * @since 1.9.4
	 *
	 * @param string|mixed $val       Field value.
	 * @param array        $field     Field data.
	 * @param array        $form_data Form data and settings.
	 * @param string       $context   Value display context.
	 *
	 * @return string
	 */
	public function html_field_value( $val, $field, $form_data = [], $context = '' ): string {

		$val = (string) $val;

		if ( empty( $field['value'] ) || $field['type'] !== $this->type ) {
			return $val;
		}

		$scheme = $form_data['fields'][ $field['id'] ]['scheme'] ?? 'us';

		// In the US it is common to use abbreviations for both the country and states, e.g., New York, NY.
		if ( $scheme === 'us' ) {
			return $val;
		}

		$allowed_contexts = [
			'entry-table',
			'entry-single',
			'entry-preview',
		];

		/**
		 * Allows filtering contexts in which the value should be transformed for display.
		 *
		 * Available contexts:
		 * - `entry-table`   - entries list table,
		 * - `entry-single`  - view entry, edit entry (non-editable field display), print preview,
		 * - `email-html`    - entry email notification,
		 * - `entry-preview` - entry preview on the frontend,
		 * - `smart-tag`     - smart tag in various places (Confirmations, Notifications, integrations etc.).
		 *
		 * By default, `email-html` and `smart-tag` contexts are ignored. The data in these contexts
		 * can be used for automation and external data processing, so we keep the original format
		 * intact for backwards compatibility.
		 *
		 * @since 1.7.6
		 *
		 * @param array $allowed_contexts Contexts whitelist.
		 * @param array $field            Field data.
		 * @param array $form_data        Form data and settings.
		 */
		$allowed_contexts = (array) apply_filters( 'wpforms_field_address_html_field_value_allowed_contexts', $allowed_contexts, $field, $form_data ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		return in_array( $context, $allowed_contexts, true ) ?
			$this->transform_value_for_display( $scheme, $field, $val ) :
			$val;
	}

	/**
	 * Transform the value for display context.
	 *
	 * @since 1.9.4
	 *
	 * @param string $scheme The scheme used in the field.
	 * @param array  $field  Field data.
	 * @param string $value  Value to transform.
	 *
	 * @return string
	 */
	private function transform_value_for_display( $scheme, $field, $value ): string {

		$transform = [
			'state'   => 'states',
			'country' => 'countries',
		];

		foreach ( $transform as $singular => $plural ) {

			$collection = $this->schemes[ $scheme ][ $plural ] ?? '';

			// The 'countries' or 'states' is array, and the value exists as an array key.
			if ( is_array( $collection ) && array_key_exists( $field[ $singular ], $collection ) ) {
				$value = str_replace( $field[ $singular ], $collection[ $field[ $singular ] ], $value );
			}
		}

		return $value;
	}

	/**
	 * Get state select options.
	 *
	 * @since 1.9.4
	 *
	 * @param string $placeholder Placeholder text.
	 * @param array  $state       State data.
	 *
	 * @return string
	 */
	private function get_state_select_options( $placeholder, $state ): string {

		if ( ! empty( $placeholder ) && empty( $state['attr']['value'] ) ) {
			printf( '<option class="placeholder" value="" selected disabled>%s</option>', esc_html( $placeholder ) );
		}

		$options = [];

		foreach ( $state['options'] as $state_key => $state_label ) {
			$options[] = sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $state_key ),
				selected( ! empty( $state['attr']['value'] ) && ( $state_key === $state['attr']['value'] || $state_label === $state['attr']['value'] ), true, false ),
				esc_html( $state_label )
			);
		}

		return implode( '', $options );
	}
}
