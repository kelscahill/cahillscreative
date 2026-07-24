<?php

namespace WPForms\Pro\Analytics;

use WPForms\Forms\Fields\PaymentSingle\Field as PaymentSingleField;

/**
 * Resolves whether a form field is actually rendered on the frontend.
 *
 * The analytics field-activity table must not show rows for fields a visitor
 * never sees. "Rendered" is decided the same way `Frontend::render_field()`
 * decides it, so the table mirrors the live form:
 *
 *   1. The field type has a registered `wpforms_display_field_{$type}` action.
 *   2. The field survives the `wpforms_field_data` filter — `ProField`
 *      empties a disabled field ( a Pro field in Lite, or an addon whose plugin
 *      is deactivated ), which is exactly what removes it from the rendered form.
 *
 * Gate 1 alone is insufficient: core registers stub field classes for addon
 * field types ( e.g. Signature ) so the builder can upsell them, so the display
 * action stays registered even when the addon is inactive. Only gate 2 catches that.
 *
 * A registered, non-disabled field can still be configured hidden — a Single
 * Item payment field set to "Hidden" renders as a hidden input — so that is
 * checked too.
 *
 * @since 2.0.0
 */
class FieldVisibility {

	/**
	 * Whether a field is rendered on the frontend.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field     Field definition from `form_data['fields']`.
	 * @param array $form_data Decoded form array.
	 *
	 * @return bool
	 */
	public static function is_displayed( array $field, array $form_data ): bool {

		$type = strtolower( (string) ( $field['type'] ?? '' ) );

		// The type must render at all — the same first gate as Frontend::render_field().
		if ( $type === '' || ! has_action( "wpforms_display_field_{$type}" ) ) {
			return false;
		}

		// A disabled field ( Pro-in-Lite, or an inactive addon ) is emptied by the
		// wpforms_field_data filter ( ProField::filter_frontend_field_data ) — exactly
		// how the frontend drops it. has_action() can't see this because core keeps a
		// stub display action registered for inactive-addon field types.
		$field_data = apply_filters( 'wpforms_field_data', $field, $form_data ); // phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation,WPForms.PHP.ValidateHooks.InvalidHookName -- Core filter consumed as the frontend display gate; documented at its canonical site, Frontend::render_field().

		if ( empty( $field_data ) ) {
			return false;
		}

		// A registered Single Item payment field can still be configured "Hidden"
		// ( or left with no item type ), rendering as a hidden input.
		if ( $type === 'payment-single' ) {
			$format = (string) ( $field['format'] ?? '' );

			return $format !== '' && $format !== PaymentSingleField::FORMAT_HIDDEN;
		}

		return true;
	}
}
