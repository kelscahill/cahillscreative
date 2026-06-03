<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Email field value preparer.
 *
 * Fixes common email typos before validation.
 *
 * @since 1.10.1
 */
class EmailPreparer implements FieldPreparerInterface {

	/**
	 * Prepare the email value.
	 *
	 * Fixes common typos: double @, double dots, spaces, domain case normalization.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string Prepared email value.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		$value = str_replace( [ ' ', '@@' ], [ '', '@' ], $value );

		$value = preg_replace( '/\.{2,}/', '.', $value );

		// Lowercase domain only (local part can be case-sensitive per RFC 5321).
		return trim( $this->lowercase_domain( $value ), '.' );
	}

	/**
	 * Lowercase only the domain part of email.
	 *
	 * @since 1.10.1
	 *
	 * @param string $email Email address.
	 *
	 * @return string Email with lowercased domain.
	 */
	private function lowercase_domain( string $email ): string {

		$at_pos = strrpos( $email, '@' );

		if ( $at_pos === false ) {
			return $email;
		}

		$local  = substr( $email, 0, $at_pos );
		$domain = substr( $email, $at_pos );

		return $local . strtolower( $domain );
	}

	/**
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — email fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'Invalid email', 'wpforms' );
	}
}
