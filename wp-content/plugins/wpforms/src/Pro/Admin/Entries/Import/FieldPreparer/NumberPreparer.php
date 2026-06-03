<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Number field value preparer.
 *
 * Value preparation is a no-op today; the preparer exists to provide
 * a specific error message for the Number field.
 *
 * @since 1.10.1
 */
class NumberPreparer implements FieldPreparerInterface {

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
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — number fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'Invalid number format', 'wpforms' );
	}
}
