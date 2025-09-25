<?php

namespace WPForms\Pro\Forms\Fields\Divider;

use WPForms\Forms\Fields\Divider\Field as FieldLite;

/**
 * Section Divider field.
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

		add_filter( "wpforms_pro_admin_entries_edit_is_field_displayable_{$this->type}", '__return_false' );
		add_filter( "wpforms_field_properties_{$this->type}", [ $this, 'field_properties' ], 5, 3 );
		add_filter( 'wpforms_field_atts', [ $this, 'add_custom_class' ], 10, 3 );
		add_filter( 'wpforms_field_preview_class', [ $this, 'add_css_class_for_field_wrapper' ], 10, 2 );
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

		// Disable field label.
		$properties['label']['disabled'] = true;

		return $properties;
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
		$label   = $field['properties']['label'];

		// H3 element should not have the name attribute.
		unset( $primary['attr']['name'] );

		// Primary field.
		if ( ! empty( $label['value'] ) ) {
			printf(
				'<h3 %s>%s</h3>',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_html( $field['label'] )
			);
		}
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
	 * Whether the current field can be populated using a fallback.
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
	 * Format field.
	 *
	 * @since 1.9.4
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
	}

	/**
	 * Add .wpforms-field-divider-hide_line class when Hide Divider Line is checked.
	 *
	 * @since 1.9.7
	 *
	 * @param mixed $attributes Field attributes.
	 * @param array $field      Field data.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function add_custom_class( $attributes, array $field, array $form_data ): array {

		$attributes = (array) $attributes;

		if ( ! isset( $field['type'] ) || $field['type'] !== $this->type ) {
			return $attributes;
		}

		if ( ! empty( $field['hide_divider_line'] ) && $field['hide_divider_line'] === '1' ) {
			$attributes['field_class'][] = ' wpforms-field-divider-hide_line';
		}

		return $attributes;
	}

	/**
	 * Add .hide_line class to the field wrapper when Hide Divider Line is checked on the field preview.
	 *
	 * @since 1.9.7
	 *
	 * @param mixed $css   CSS classes for the field wrapper.
	 * @param array $field Field data.
	 *
	 * @return string
	 */
	public function add_css_class_for_field_wrapper( $css, array $field ): string {

		$css = (string) $css;

		if ( ! isset( $field['type'] ) || $field['type'] !== $this->type ) {
			return $css;
		}

		if ( ! empty( $field['hide_divider_line'] ) && $field['hide_divider_line'] === '1' ) {
			$css .= ' hide_line ';
		}

		return $css;
	}
}
