<?php

namespace WPForms\Pro\Forms\Fields\Html;

use WPForms\Forms\Fields\Html\Field as FieldLite;

/**
 * HTML block text field.
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
		add_filter( 'wpforms_field_properties_html', [ $this, 'field_properties' ], 5, 3 );
		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );
		add_filter( 'wpforms_pro_admin_entries_print_preview_field_value_use_nl2br', [ $this, 'print_preview_use_nl2br' ], 10, 2 );
	}

	/**
	 * Do not use nl2br on HTML field's value.
	 *
	 * @since 1.9.4
	 *
	 * @param bool|mixed $use_nl2br Boolean value flagging if field should use the 'nl2br' function.
	 * @param array      $field     Field data.
	 *
	 * @return bool
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public function print_preview_use_nl2br( $use_nl2br, $field ): bool {

		$use_nl2br = (bool) $use_nl2br;

		return $field['type'] === $this->type ? false : $use_nl2br;
	}

	/**
	 * Define additional field properties.
	 *
	 * @since        1.3.7
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

		// Remove input attributes references.
		$properties['inputs']['primary']['attr'] = [];

		// Add code value.
		$properties['inputs']['primary']['code'] = ! empty( $field['code'] ) ? $field['code'] : '';

		return $properties;
	}

	/**
	 * Whether the current field can be populated dynamically.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 *
	 * @return bool
 	 */
	public function is_dynamic_population_allowed( $properties, $field ): bool {

		return false;
	}

	/**
	 * Whether the current field can be populated using a fallback.
	 *
	 * @since 1.9.4
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 *
	 * @return bool
 	 */
	public function is_fallback_population_allowed( $properties, $field ): bool {

		return false;
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
			'<div %s>%s</div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			do_shortcode( force_balance_tags( $primary['code'] ) )
		);
	}

	/**
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
	}
}
