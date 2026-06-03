<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * Trait for choice-based field preparers.
 *
 * Used by preparers that handle choice-based fields (checkbox, select, radio)
 * to match values against choices.
 *
 * @since 1.10.1
 */
trait ChoicesTrait {

	/**
	 * Get the pattern for splitting multi-value strings.
	 *
	 * Supports comma, semicolon, pipe, and newline delimiters.
	 *
	 * @since 1.10.1
	 *
	 * @return string Regex pattern.
	 */
	protected function get_delimiters_pattern(): string {

		return '/[,;|\n]+/';
	}

	/**
	 * Match a value against a single choice.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value  Value to match.
	 * @param mixed  $choice Choice to match against.
	 *
	 * @return string|null Matched value or null if no match.
	 */
	protected function match_choice( string $value, $choice ): ?string {

		if ( ! is_array( $choice ) ) {
			return null;
		}

		$choice_value = $choice['value'] ?? '';
		$choice_label = $choice['label'] ?? '';

		if ( $choice_value !== '' && $choice_value === $value ) {
			return $value;
		}

		if ( $choice_value !== '' && strcasecmp( $choice_value, $value ) === 0 ) {
			return $choice_value;
		}

		if ( strcasecmp( $choice_label, $value ) === 0 ) {
			return $choice_value !== '' ? $choice_value : $choice_label;
		}

		return null;
	}

	/**
	 * Resolve a single value against field choices.
	 *
	 * Trims the value, checks for empty, and attempts to match against choices.
	 * Returns the original value if no match is found.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value   Value to resolve.
	 * @param array  $choices Field choices.
	 *
	 * @return string Resolved value.
	 */
	protected function resolve_value_against_choices( string $value, array $choices ): string {

		$matched = $this->find_choice_match( $value, $choices );

		return $matched !== null ? $matched : trim( $value );
	}

	/**
	 * Find the choice that matches a value, or null when no choice matches.
	 *
	 * Unlike resolve_value_against_choices(), this returns null on no match
	 * rather than falling back to the original input, so callers can branch
	 * on whether the value was an actual choice.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value   Value to match.
	 * @param array  $choices Field choices.
	 *
	 * @return string|null Matched choice value, or null if no choice matched.
	 */
	protected function find_choice_match( string $value, array $choices ): ?string {

		$value = trim( $value );

		if ( $value === '' ) {
			return null;
		}

		foreach ( $choices as $choice ) {
			// Skip the "Add Other Choice" placeholder — its label/value are not
			// real choices, so a literal match must fall through to the Other
			// path in the caller instead of being treated as a regular choice.
			if ( is_array( $choice ) && ! empty( $choice['other'] ) ) {
				continue;
			}

			$matched = $this->match_choice( $value, $choice );

			if ( $matched !== null ) {
				return $matched;
			}
		}

		return null;
	}
}
