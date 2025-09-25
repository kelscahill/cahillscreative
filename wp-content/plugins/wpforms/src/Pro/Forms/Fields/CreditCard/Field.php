<?php

namespace WPForms\Pro\Forms\Fields\CreditCard;

use WPForms\Forms\Fields\CreditCard\Field as FieldLite;

/**
 * Credit card field (legacy).
 *
 * @since 1.0.0
 */
class Field extends FieldLite {

	/**
	 * Hooks.
	 *
	 * @since 1.8.1
	 */
	protected function hooks(): void {

		// Define additional field properties.
		add_filter( 'wpforms_field_properties_credit-card', [ $this, 'field_properties' ], 5, 3 );

		// Set field to the required by default.
		add_filter( 'wpforms_field_new_required', [ $this, 'default_required' ], 10, 2 );

		// Hide field if supporting payment gateway is not activated.
		add_action( 'wpforms_builder_print_footer_scripts', [ $this, 'builder_footer_scripts' ] );

		// Load required scripts.
		add_action( 'wpforms_frontend_js', [ $this, 'load_js' ] );

		// This field requires fieldset+legend instead of the field label.
		add_filter( "wpforms_frontend_modern_is_field_requires_fieldset_{$this->type}", '__return_true', PHP_INT_MAX, 2 );
	}

	/**
	 * Load required scripts.
	 *
	 * @since 1.7.5.3
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function load_js( $forms ): void {

		if (
			wpforms_has_field_type( 'credit-card', $forms, true ) ||
			wpforms()->obj( 'frontend' )->assets_global()
		) {
			wp_enqueue_script(
				'wpforms-payment',
				WPFORMS_PLUGIN_URL . 'assets/pro/lib/jquery.payment.min.js',
				[ 'jquery' ],
				WPFORMS_VERSION,
				$this->load_script_in_footer()
			);
		}
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.3.8
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Field settings.
	 * @param array       $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$properties = (array) $properties;

		// Remove primary for expanded formats since we have first, middle, last.
		unset( $properties['inputs']['primary'] );

		// Remove reference to an input element to prevent duplication.
		if ( empty( $field['sublabel_hide'] ) ) {
			unset( $properties['label']['attr']['for'] );
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = absint( $field['id'] );
		$position = wpforms_get_render_engine() === 'classic' ? 'before' : 'after';

		$props      = [
			'inputs' => [
				'number' => [
					'attr'     => [
						'name'         => '',
						'value'        => '',
						'placeholder'  => ! empty( $field['cardnumber_placeholder'] ) ? $field['cardnumber_placeholder'] : '',
						'autocomplete' => 'off',
					],
					'block'    => [
						'wpforms-field-credit-card-number',
					],
					'class'    => [
						'wpforms-field-credit-card-cardnumber',
					],
					'data'     => [
						'rule-creditcard' => 'yes',
					],
					'id'       => "wpforms-{$form_id}-field_{$field_id}",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Card Number', 'wpforms' ),
						'position' => $position,
					],
				],
				'cvc'    => [
					'attr'     => [
						'name'         => '',
						'value'        => '',
						'placeholder'  => ! empty( $field['cardcvc_placeholder'] ) ? $field['cardcvc_placeholder'] : '',
						'maxlength'    => '4',
						'autocomplete' => 'off',
					],
					'block'    => [
						'wpforms-field-credit-card-code',
					],
					'class'    => [
						'wpforms-field-credit-card-cardcvc',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardcvc",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Security Code', 'wpforms' ),
						'position' => $position,
					],
				],
				'name'   => [
					'attr'     => [
						'name'        => '',
						'value'       => '',
						'placeholder' => ! empty( $field['cardname_placeholder'] ) ? $field['cardname_placeholder'] : '',
					],
					'block'    => [
						'wpforms-field-credit-card-name',
					],
					'class'    => [
						'wpforms-field-credit-card-cardname',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardname",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Name on Card', 'wpforms' ),
						'position' => $position,
					],
				],
				'month'  => [
					'attr'     => [],
					'class'    => [
						'wpforms-field-credit-card-cardmonth',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardmonth",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => [
						'hidden'   => ! empty( $field['sublabel_hide'] ),
						'value'    => esc_html__( 'Expiration', 'wpforms' ),
						'position' => $position,
					],
				],
				'year'   => [
					'attr'     => [],
					'class'    => [
						'wpforms-field-credit-card-cardyear',
					],
					'data'     => [],
					'id'       => "wpforms-{$form_id}-field_{$field_id}-cardyear",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
				],
			],
		];
		$properties = array_merge_recursive( $properties, $props );

		// If this field is required, we need to make some adjustments.
		if ( ! empty( $field['required'] ) ) {

			// Add required class if needed (for multipage validation).
			$properties['inputs']['number']['class'][] = 'wpforms-field-required';
			$properties['inputs']['cvc']['class'][]    = 'wpforms-field-required';
			$properties['inputs']['name']['class'][]   = 'wpforms-field-required';
			$properties['inputs']['month']['class'][]  = 'wpforms-field-required';
			$properties['inputs']['year']['class'][]   = 'wpforms-field-required';

			// Below, we add our input special classes if certain fields are required.
			// The jQuery Validation library will not correctly validate fields that do not have a name attribute.
			// So, we use the `wpforms-input-temp-name` class to let jQuery know
			// we should add a temporary name attribute before validation is initialized.
			// Then, remove it before the form submits.
			$properties['inputs']['number']['class'][] = 'wpforms-input-temp-name';
			$properties['inputs']['cvc']['class'][]    = 'wpforms-input-temp-name';
			$properties['inputs']['name']['class'][]   = 'wpforms-input-temp-name';
			$properties['inputs']['month']['class'][]  = 'wpforms-input-temp-name';
			$properties['inputs']['year']['class'][]   = 'wpforms-input-temp-name';
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
	 * Default to the required.
	 *
	 * @since 1.0.9
	 *
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function default_required( $required, $field ): bool {

		if ( $field['type'] === 'credit-card' ) {
			return true;
		}

		return (bool) $required;
	}

	/**
	 * If a supporting payment gateway is not active, don't allow users to add
	 * the field inside the form builder.
	 *
	 * @since 1.4.6
	 */
	public function builder_footer_scripts(): void {

		/**
		 * Filter to enable/disable the credit card field.
		 *
		 * @since 1.4.6
		 *
		 * @param bool $enable True to enable, false to disable.
		 */
		if ( apply_filters( 'wpforms_field_credit_card_enable', false ) ) { // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(function($){
				$( '#wpforms-add-fields-credit-card' ).remove();
			});
		</script>
		<?php
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Deprecated field attributes. Use field properties.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Define data.
		$number = ! empty( $field['properties']['inputs']['number'] ) ? $field['properties']['inputs']['number'] : [];
		$cvc    = ! empty( $field['properties']['inputs']['cvc'] ) ? $field['properties']['inputs']['cvc'] : [];
		$name   = ! empty( $field['properties']['inputs']['name'] ) ? $field['properties']['inputs']['name'] : [];
		$month  = ! empty( $field['properties']['inputs']['month'] ) ? $field['properties']['inputs']['month'] : [];
		$year   = ! empty( $field['properties']['inputs']['year'] ) ? $field['properties']['inputs']['year'] : [];

		// Display warning for non SSL pages.
		if ( ! is_ssl() ) {
			echo '<div class="wpforms-cc-warning wpforms-error-alert">';
			esc_html_e( 'This page is insecure. Credit Card field should be used for testing purposes only.', 'wpforms' );
			echo '</div>';
		}

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

		// Card number.
		echo '<div ' . wpforms_html_attributes( false, $number['block'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->field_display_sublabel( 'number', 'before', $field );
		printf(
			'<input type="text" %s %s>',
			wpforms_html_attributes( $number['id'], $number['class'], $number['data'], $number['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $number['required'] ) ? 'required' : ''
		);
		$this->field_display_sublabel( 'number', 'after', $field );
		$this->field_display_error( 'number', $field );
		echo '</div>';

		// CVC.
		echo '<div ' . wpforms_html_attributes( false, $cvc['block'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->field_display_sublabel( 'cvc', 'before', $field );
		printf(
			'<input type="text" %s %s>',
			wpforms_html_attributes( $cvc['id'], $cvc['class'], $cvc['data'], $cvc['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $cvc['required'] ) ? 'required' : ''
		);
		$this->field_display_sublabel( 'cvc', 'after', $field );
		$this->field_display_error( 'cvc', $field );
		echo '</div>';

		echo '</div>';

		// Row wrapper.
		echo '<div class="wpforms-field-row wpforms-field-' . sanitize_html_class( $field['size'] ) . '">';

		// Name.
		echo '<div ' . wpforms_html_attributes( false, $name['block'] ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->field_display_sublabel( 'name', 'before', $field );
		printf(
			'<input type="text" %s %s>',
			wpforms_html_attributes( $name['id'], $name['class'], $name['data'], $name['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $name['required'] ) ? 'required' : ''
		);
		$this->field_display_sublabel( 'name', 'after', $field );
		$this->field_display_error( 'name', $field );
		echo '</div>';

		// Expiration.
		echo '<div class="wpforms-field-credit-card-expiration">';

		// Month.
		$this->field_display_sublabel( 'month', 'before', $field );
		printf(
			'<select %s %s>',
			wpforms_html_attributes( $month['id'], $month['class'], $month['data'], $month['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $month['required'] ) ? 'required' : ''
		);
		echo '<option class="placeholder" selected disabled>MM</option>';
		foreach ( range( 1, 12 ) as $number ) {
			printf( '<option value="%1$d">%1$d</option>', absint( $number ) );
		}
		echo '</select>';
		$this->field_display_sublabel( 'month', 'after', $field );
		$this->field_display_error( 'month', $field );

		// Sep.
		echo '<span>/</span>';

		// Year.
		$this->field_display_sublabel( 'year', 'before', $field );
		printf(
			'<select %s %s>',
			wpforms_html_attributes( $year['id'], $year['class'], $year['data'], $year['attr'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $year['required'] ) ? 'required' : ''
		);
		echo '<option class="placeholder" selected disabled>YY</option>';
		$start_year = gmdate( 'y' );
		$end_year   = $start_year + 11;

		for ( $i = $start_year; $i < $end_year; $i++ ) {
			printf( '<option value="%1$d">%1$d</option>', absint( $i ) );
		}
		echo '</select>';
		$this->field_display_sublabel( 'year', 'after', $field );
		$this->field_display_error( 'year', $field );

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Currently validation happens on the front end. We do not do
	 * generic server-side validation because we do not allow the card
	 * details to POST to the server.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value (raw data).
	 * @param array $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
	}

	/**
	 * Format field.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		// Define data.
		$name = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? $form_data['fields'][ $field_id ]['label'] : '';

		// Set final field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => sanitize_text_field( $name ),
			'value' => '',
			'id'    => absint( $field_id ),
			'type'  => sanitize_key( $this->type ),
		];
	}
}
