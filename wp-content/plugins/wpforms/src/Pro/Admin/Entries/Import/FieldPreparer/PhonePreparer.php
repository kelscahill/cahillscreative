<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Phone field value preparer.
 *
 * Normalizes a phone number format.
 *
 * @since 1.10.1
 */
class PhonePreparer implements FieldPreparerInterface {

	/**
	 * Prepare the phone value.
	 *
	 * Strips invalid characters, preserving digits and common phone characters.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string Prepared phone value.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		$value = trim( $value );

		if ( $value === '' ) {
			return $value;
		}

		// Keep only digits, plus, parentheses, hyphens, dots, and spaces.
		$value = preg_replace( '/[^\d+().\-\s]/', '', $value );
		$value = preg_replace( '/-+/', '-', $value );

		return trim( $value, '-' );
	}

	/**
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — phone fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'Invalid phone number', 'wpforms' );
	}
}
