<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * URL field value preparer.
 *
 * Ensures URLs have a protocol.
 *
 * @since 1.10.1
 */
class UrlPreparer implements FieldPreparerInterface {

	/**
	 * Prepare the URL value.
	 *
	 * Prepends https:// if no protocol is present.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string Prepared URL value.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		$value = trim( $value );

		if ( $value === '' ) {
			return $value;
		}

		if ( preg_match( '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $value ) ) {
			return $value;
		}

		return 'https://' . $value;
	}

	/**
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — URL fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'Invalid URL', 'wpforms' );
	}
}
