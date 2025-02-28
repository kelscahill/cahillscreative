<?php

namespace WPForms\Pro\Forms\Fields\Url;

use WPForms\Forms\Fields\Url\Field as FieldLite;

/**
 * URL text field.
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

		add_filter( 'wpforms_html_field_value', [ $this, 'output_field_value' ], 10, 4 );
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

		// Primary field.
		printf(
			'<input type="url" %s %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Validate field on form submit event.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted field value (raw data).
	 * @param array  $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		$form_id = $form_data['id'];

		// Basic required check - If field is marked as required, check for entry data.
		if ( empty( $field_submit ) && ! empty( $form_data['fields'][ $field_id ]['required'] ) ) {
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = wpforms_get_required_label();
		}

		// Check that URL is in the valid format.
		if ( ! empty( $field_submit ) && ! wpforms_is_url( $field_submit ) ) {
			/**
			 * Filters the URL field error message.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message Error message.
			 */
			wpforms()->obj( 'process' )->errors[ $form_id ][ $field_id ] = apply_filters( 'wpforms_valid_url_label', esc_html__( 'Please enter a valid URL.', 'wpforms' ) ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
		}
	}

	/**
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted value.
	 * @param array  $form_data    Form data.
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		// Set field details.
		wpforms()->obj( 'process' )->fields[ $field_id ] = [
			'name'  => ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '',
			'value' => esc_url_raw( $field_submit ),
			'id'    => wpforms_validate_field_id( $field_id ),
			'type'  => $this->type,
		];
	}

	/**
	 * Filter the field value on output.
	 *
	 * This is necessary to perform escaping of non-sanitized values before output.
	 *
	 * @since 1.9.4
	 *
	 * @param string|mixed $value     Field value.
	 * @param array        $field     Entry field data.
	 * @param array        $form_data Form data and settings.
	 * @param string       $context   Value display context.
	 *
	 * @return string|mixed
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function output_field_value( $value, array $field, array $form_data = [], string $context = '' ) {

		if ( $this->type !== ( $field['type'] ?? '' ) ) {
			return $value;
		}

		return esc_url( $field['value'] ?? $value );
	}
}
