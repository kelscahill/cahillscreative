<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Choices field value preparer.
 *
 * Resolves labels to values and handles multi-value delimiters.
 *
 * @since 1.10.1
 */
class ChoicesPreparer implements FieldPreparerInterface {

	use ChoicesTrait;

	/**
	 * Prepare the choice value.
	 *
	 * Resolves labels to values and handles multi-value delimiters.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return string|array Prepared choice value(s).
	 */
	public function prepare( $value, array $field, array $form_data ) {

		if ( $value === '' ) {
			return $value;
		}

		$choices = $field['choices'] ?? [];

		if ( is_array( $value ) ) {
			return $this->resolve_multiple_values( $value, $choices );
		}

		if ( $this->is_multiple_field( $field ) ) {
			$values = $this->split_and_filter( $value );

			if ( count( $values ) > 1 ) {
				return $this->resolve_multiple_values( $values, $choices );
			}
		}

		$matched = $this->find_choice_match( (string) $value, $choices );

		if ( $matched !== null ) {
			return $matched;
		}

		// Radio with the "Add Other Choice" option enabled — route the unmatched
		// value through the Other path so WPForms tags it as is_other. Strip the
		// "{Other label}: " prefix produced by the entry export round-trip so it
		// isn't stored twice on re-import.
		if ( ( $field['type'] ?? '' ) === 'radio' && ! empty( $field['choices_other'] ) ) {
			$stripped = $this->strip_other_prefix( (string) $value, $choices );

			return [ 'other' => trim( $stripped ) ];
		}

		return trim( (string) $value );
	}

	/**
	 * Strip the "{Other label}: " prefix added by Radio::export_entry_field_data.
	 *
	 * The export prefixes Other answers with the Other choice's label and a
	 * colon-space separator. On re-import, the prefix must be removed so the
	 * stored value matches the original user input and the next export does
	 * not double the prefix.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value   Raw source value.
	 * @param array  $choices Field choices.
	 *
	 * @return string Value with the prefix removed, or the original value if no prefix matches.
	 */
	private function strip_other_prefix( string $value, array $choices ): string {

		foreach ( $choices as $choice ) {
			if ( ! is_array( $choice ) || empty( $choice['other'] ) ) {
				continue;
			}

			$label = (string) ( $choice['label'] ?? '' );

			if ( $label === '' ) {
				return $value;
			}

			$prefix = $label . ': ';

			if ( stripos( $value, $prefix ) === 0 ) {
				return substr( $value, strlen( $prefix ) );
			}

			return $value;
		}

		return $value;
	}

	/**
	 * Split a string by delimiters and filter empty values.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value Value to split.
	 *
	 * @return array Filtered values.
	 */
	private function split_and_filter( string $value ): array {

		$values = preg_split( $this->get_delimiters_pattern(), $value );

		if ( $values === false ) {
			return [];
		}

		return array_filter( array_map( 'trim', $values ), 'strlen' );
	}

	/**
	 * Resolve multiple values against choices.
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
			$result[] = $this->resolve_value_against_choices( (string) $val, $choices );
		}

		return array_values( $result );
	}

	/**
	 * Check if the field supports multiple values.
	 *
	 * @since 1.10.1
	 *
	 * @param array $field Field settings.
	 *
	 * @return bool
	 */
	private function is_multiple_field( array $field ): bool {

		$type = $field['type'] ?? '';

		return $type === 'select' && ! empty( $field['multiple'] );
	}

	/**
	 * Get the error message for a given status.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Unused — single-choice fields do not use subfields.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		return esc_html__( 'No matching choice', 'wpforms' );
	}
}
