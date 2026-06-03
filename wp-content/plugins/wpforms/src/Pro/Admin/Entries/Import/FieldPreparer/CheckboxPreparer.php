<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Checkbox field value preparer.
 *
 * Resolves checkbox values by matching against field choices.
 * Supports multiple value formats: comma-delimited, semicolon-delimited,
 * pipe-delimited, newline-delimited, and array input.
 *
 * @since 1.10.1
 */
class CheckboxPreparer implements FieldPreparerInterface {

	use ChoicesTrait;

	/**
	 * Prepare the checkbox value.
	 *
	 * Resolves choice labels to values and handles multiple value formats.
	 *
	 * @since 1.10.1
	 *
	 * @param string|int|array $value     Raw value from import source.
	 * @param array            $field     Field settings.
	 * @param array            $form_data Form data and settings.
	 *
	 * @return array|string Prepared checkbox values or empty string if no values.
	 */
	public function prepare( $value, array $field, array $form_data ) {

		$choices = $field['choices'] ?? [];

		if ( is_array( $value ) ) {
			$result = $this->resolve_multiple_values( $value, $choices );

			return empty( $result ) ? '' : $result;
		}

		$value = trim( (string) $value );

		if ( $value === '' ) {
			return '';
		}

		$result = $this->resolve_choice_values( $value, $choices );

		return empty( $result ) ? '' : $result;
	}

	/**
	 * Resolve choice values from a string that may contain multiple values.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value   Value string (may be delimited).
	 * @param array  $choices Field choices.
	 *
	 * @return array Resolved values.
	 */
	private function resolve_choice_values( string $value, array $choices ): array {

		$values = preg_split( $this->get_delimiters_pattern(), $value );

		if ( $values === false ) {
			return [];
		}

		return $this->resolve_multiple_values( $values, $choices );
	}

	/**
	 * Resolve multiple values to their choice values.
	 *
	 * @since 1.10.1
	 *
	 * @param array $values  Values to resolve.
	 * @param array $choices Field choices.
	 *
	 * @return array Resolved values.
	 */
	private function resolve_multiple_values( array $values, array $choices ): array {

		$result = [];

		foreach ( $values as $val ) {
			$resolved = $this->resolve_value_against_choices( (string) $val, $choices );

			if ( $resolved !== '' ) {
				$result[] = $resolved;
			}
		}

		return $result;
	}

	/**
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — checkbox fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'No matching choice', 'wpforms' );
	}
}
