<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Address field value preparer.
 *
 * Normalizes state and country codes.
 *
 * @since 1.10.1
 */
class AddressPreparer implements FieldPreparerInterface {

	/**
	 * Prepare the address value.
	 *
	 * Normalizes state codes for a US scheme, country codes for an international scheme.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string|array Prepared address value.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		if ( ! is_array( $value ) || empty( $value ) ) {
			return $value;
		}

		$scheme = $field['scheme'] ?? 'us';

		if ( $scheme === 'us' && ! empty( $value['state'] ) ) {
			$value['state'] = $this->normalize_code( $value['state'], $this->get_us_states() );
		}

		if ( $scheme === 'international' && ! empty( $value['country'] ) ) {
			$value['country'] = $this->normalize_code( $value['country'], $this->get_countries() );
		}

		return $value;
	}

	/**
	 * Normalize a value to its 2-letter code.
	 *
	 * @since 1.10.1
	 *
	 * @param string $input  Name or code to normalize.
	 * @param array  $lookup Code => name pairs.
	 *
	 * @return string Matched code, or original input if not found.
	 */
	private function normalize_code( string $input, array $lookup ): string {

		$input = trim( $input );

		if ( $input === '' ) {
			return $input;
		}

		$upper = strtoupper( $input );

		if ( isset( $lookup[ $upper ] ) ) {
			return $upper;
		}

		foreach ( $lookup as $code => $name ) {
			if ( strcasecmp( $name, $input ) === 0 ) {
				return $code;
			}
		}

		$needle      = strtolower( $input ) . ' ';
		$best_code   = '';
		$best_length = PHP_INT_MAX;

		foreach ( $lookup as $code => $name ) {
			if ( strpos( strtolower( $name ), $needle ) !== 0 ) {
				continue;
			}

			$length = strlen( $name );

			if ( $length < $best_length ) {
				$best_code   = $code;
				$best_length = $length;
			}
		}

		return $best_code !== '' ? $best_code : $input;
	}

	/**
	 * Get US states array.
	 *
	 * @since 1.10.1
	 *
	 * @return array State code => name pairs.
	 */
	private function get_us_states(): array {

		static $us_states;

		if ( $us_states ) {
			return $us_states;
		}

		$us_states = wpforms_us_states();

		return $us_states;
	}

	/**
	 * Get country array.
	 *
	 * @since 1.10.1
	 *
	 * @return array Country code => name pairs.
	 */
	private function get_countries(): array {

		static $countries;

		if ( $countries ) {
			return $countries;
		}

		$countries = wpforms_countries();

		return $countries;
	}

	/**
	 * Get the error message for a given status and address subfield.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Subfield key: 'address1', 'address2', 'city', 'state', 'postal', 'country', or empty.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		$messages = [
			'address1' => esc_html__( 'Invalid address line 1', 'wpforms' ),
			'address2' => esc_html__( 'Invalid address line 2', 'wpforms' ),
			'city'     => esc_html__( 'Invalid city', 'wpforms' ),
			'state'    => esc_html__( 'Invalid state', 'wpforms' ),
			'postal'   => esc_html__( 'Invalid postal code', 'wpforms' ),
			'country'  => esc_html__( 'Invalid country', 'wpforms' ),
		];

		return $messages[ $subfield ] ?? esc_html__( 'Invalid address', 'wpforms' );
	}
}
