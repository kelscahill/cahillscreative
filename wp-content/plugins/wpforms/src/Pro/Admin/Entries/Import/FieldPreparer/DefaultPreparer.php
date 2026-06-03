<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Default field value preparer.
 *
 * Acts as a pass-through for field types without a dedicated preparer.
 * Value preparation is a no-op; error messages fall back to generic strings.
 *
 * @since 1.10.1
 */
class DefaultPreparer implements FieldPreparerInterface {

	/**
	 * Prepare the value (pass-through).
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string|array Unmodified value.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		return $value;
	}

	/**
	 * Get the generic error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — generic messages ignore subfield.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'Invalid value', 'wpforms' );
	}
}
