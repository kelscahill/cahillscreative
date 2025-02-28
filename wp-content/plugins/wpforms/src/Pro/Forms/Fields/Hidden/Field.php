<?php

namespace WPForms\Pro\Forms\Fields\Hidden;

use WPForms\Forms\Fields\Hidden\Field as FieldLite;

/**
 * Hidden text field.
 *
 * @since 1.9.4
 */
class Field extends FieldLite {

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.9.4
	 *
	 * @param array $field      Field data and settings.
	 * @param array $deprecated Not used any more field attributes.
	 * @param array $form_data  Form data and settings.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		// Primary field.
		printf(
			'<input type="hidden" %s>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wpforms_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		);
	}
}
