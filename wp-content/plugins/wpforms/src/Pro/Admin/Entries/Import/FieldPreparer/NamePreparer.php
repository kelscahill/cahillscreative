<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Name field value preparer.
 *
 * Value preparation is a no-op today; the preparer exists to provide
 * subfield-specific error messages for the Name field.
 *
 * @since 1.10.1
 */
class NamePreparer implements FieldPreparerInterface {

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
	 * Get the error message for a given status and name subfield.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Subfield key: 'first', 'middle', 'last', or empty.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		$messages = [
			'first'  => esc_html__( 'Invalid first name', 'wpforms' ),
			'middle' => esc_html__( 'Invalid middle name', 'wpforms' ),
			'last'   => esc_html__( 'Invalid last name', 'wpforms' ),
		];

		return $messages[ $subfield ] ?? esc_html__( 'Invalid name', 'wpforms' );
	}
}
