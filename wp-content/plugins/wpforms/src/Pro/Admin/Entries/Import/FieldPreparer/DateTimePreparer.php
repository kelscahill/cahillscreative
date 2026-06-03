<?php

namespace WPForms\Pro\Admin\Entries\Import\FieldPreparer;

/**
 * DateTime field value preparer.
 *
 * Parses various date formats into WPForms expected structure.
 *
 * @since 1.10.1
 */
class DateTimePreparer implements FieldPreparerInterface {

	/**
	 * Default date format (US).
	 *
	 * @since 1.10.1
	 */
	private const DEFAULT_DATE_FORMAT = 'm/d/Y';

	/**
	 * Map of PHP date format to parsing regex and part order.
	 *
	 * @since 1.10.1
	 *
	 * @var array
	 */
	private const FORMAT_PATTERNS = [
		'm/d/Y' => [
			'pattern' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})/',
			'order'   => [ 'month', 'day', 'year' ],
		],
		'd/m/Y' => [
			'pattern' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})/',
			'order'   => [ 'day', 'month', 'year' ],
		],
		'Y/m/d' => [
			'pattern' => '/^(\d{4})\/(\d{1,2})\/(\d{1,2})/',
			'order'   => [ 'year', 'month', 'day' ],
		],
		'm.d.Y' => [
			'pattern' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})/',
			'order'   => [ 'month', 'day', 'year' ],
		],
		'd.m.Y' => [
			'pattern' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})/',
			'order'   => [ 'day', 'month', 'year' ],
		],
		'Y.m.d' => [
			'pattern' => '/^(\d{4})\.(\d{1,2})\.(\d{1,2})/',
			'order'   => [ 'year', 'month', 'day' ],
		],
	];

	/**
	 * Prepare the date/time value.
	 *
	 * Parses various date formats into ['date' => 'm/d/Y', 'time' => 'g:i A'] structure.
	 *
	 * @since 1.10.1
	 *
	 * @param string|array $value     Raw value from CSV.
	 * @param array        $field     Field settings.
	 * @param array        $form_data Form data and settings.
	 *
	 * @return array Prepared date/time value.
	 */
	public function prepare( $value, array $field, array $form_data ): array {

		if ( is_array( $value ) ) {
			return $this->prepare_array_form( $value, $field );
		}

		$value = trim( (string) $value );

		if ( $value === '' ) {
			return [
				'date' => '',
				'time' => '',
			];
		}

		$format = $field['format'] ?? 'date-time';

		// Time-only field.
		if ( $format === 'time' ) {
			return [
				'date' => '',
				'time' => $this->parse_time( $value ),
			];
		}

		$date_format = $field['date_format'] ?? self::DEFAULT_DATE_FORMAT;

		return $this->parse_date_time( $value, $date_format );
	}

	/**
	 * Prepare a value supplied as an array of subfields.
	 *
	 * @since 1.10.1
	 *
	 * @param array $value Pre-split value with 'date' and/or 'time' subfields.
	 * @param array $field Field settings.
	 *
	 * @return array
	 */
	private function prepare_array_form( array $value, array $field ): array {

		$date_value = $value['date'] ?? '';
		$time_value = $value['time'] ?? '';

		// WPForms dropdown format stores 'date' as an m/d/y array — keep as-is.
		if ( is_array( $date_value ) ) {
			return $value;
		}

		$date = '';
		$time = '';

		if ( (string) $date_value !== '' ) {
			$date_format = $field['date_format'] ?? self::DEFAULT_DATE_FORMAT;
			$parsed      = $this->parse_date_time( (string) $date_value, $date_format );
			$date        = $parsed['date'];

			// Adopt time from the date parse only when the user didn't supply one separately.
			if ( (string) $time_value === '' ) {
				$time = $parsed['time'];
			}
		}

		if ( (string) $time_value !== '' ) {
			$time = $this->parse_time( (string) $time_value );
		}

		return [
			'date' => $date,
			'time' => $time,
		];
	}

	/**
	 * Parse date and optional time from string.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value       Value to parse.
	 * @param string $date_format Expected date format from field settings.
	 *
	 * @return array Parsed date and time.
	 */
	private function parse_date_time( string $value, string $date_format ): array {

		$parsed = $this->parse_iso_format( $value, $date_format ) ?? $this->parse_by_format( $value, $date_format );

		if ( $parsed !== null ) {
			return $parsed;
		}

		$timestamp = strtotime( $value );

		// Reject totally invalid input AND timestamps outside a sane year range
		// (e.g. "0000-00-00" parses to year -0001 and would otherwise leak through).
		if ( $timestamp === false || (int) date( 'Y', $timestamp ) < 1000 ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			return [
				'date' => '',
				'time' => '',
			];
		}

		// Render the strtotime() result in the field's configured format so the
		// downstream Field::format() doesn't have to re-interpret a raw string.
		// Only emit a time when the original input actually contained time content
		// (a digit:digit pair or an explicit am/pm/noon/midnight token), otherwise
		// midnight from strtotime() would fabricate a "12:00 AM" the user never typed.
		$has_time = (bool) preg_match( '/\d:\d|\b(?:am|pm|noon|midnight)\b/i', $value );

		return [
			'date' => date( $this->normalize_date_format( $date_format ), $timestamp ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			'time' => $has_time ? date( 'g:i A', $timestamp ) : '',                     // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		];
	}

	/**
	 * Parse ISO format date (Y-m-d).
	 *
	 * @since 1.10.1
	 *
	 * @param string $value       Value to parse.
	 * @param string $date_format Field's configured date format used to render the output.
	 *
	 * @return array|null Parsed result or null if not ISO format.
	 */
	private function parse_iso_format( string $value, string $date_format ): ?array {

		// Match ISO format: 2026-03-25, 2026-03-25 10:30:00, or 2026-03-25T10:30:00.
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](.+))?$/', $value, $matches ) ) {
			return null;
		}

		[ , $year, $month, $day ] = $matches;

		if ( ! checkdate( (int) $month, (int) $day, (int) $year ) ) {
			return null;
		}

		$date = $this->format_date( (int) $year, (int) $month, (int) $day, $date_format );
		$time = '';

		// Extract time if present (captured after T or space separator).
		if ( ! empty( $matches[4] ) ) {
			$time = $this->parse_time( trim( $matches[4] ) );
		}

		return [
			'date' => $date,
			'time' => $time,
		];
	}

	/**
	 * Parse date according to the field's configured format.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value       Value to parse.
	 * @param string $date_format Expected date format.
	 *
	 * @return array|null Parsed result or null if format doesn't match.
	 */
	private function parse_by_format( string $value, string $date_format ): ?array {

		$format_config = self::FORMAT_PATTERNS[ $date_format ] ?? null;

		if ( $format_config === null ) {
			return null;
		}

		$pattern = $format_config['pattern'];
		$order   = $format_config['order'];

		if ( ! preg_match( $pattern, $value, $matches ) ) {
			return null;
		}

		$parts = [];

		foreach ( $order as $index => $part ) {
			$parts[ $part ] = $matches[ $index + 1 ];
		}

		$month = (int) $parts['month'];
		$day   = (int) $parts['day'];
		$year  = (int) $parts['year'];

		if ( ! checkdate( $month, $day, $year ) ) {
			return null;
		}

		$date = $this->format_date( $year, $month, $day, $date_format );
		$time = '';

		// Extract time portion if present; strip ISO T separator if present.
		$time_part = ltrim( trim( preg_replace( $pattern, '', $value ) ), 'T' );

		if ( $time_part !== '' ) {
			$time = $this->parse_time( $time_part );
		}

		return [
			'date' => $date,
			'time' => $time,
		];
	}

	/**
	 * Parse time string.
	 *
	 * Normalizes various time formats to WPForms format (g:i A).
	 * Returns the original value if a format is not recognized.
	 *
	 * @since 1.10.1
	 *
	 * @param string $value Time value.
	 *
	 * @return string Formatted time (g:i A) or empty string if not parseable.
	 */
	private function parse_time( string $value ): string {

		$value = trim( $value );

		if ( $value === '' ) {
			return '';
		}

		$timestamp = strtotime( $value );

		// Reject totally invalid input AND pre-year-1000 timestamps so date-shaped
		// junk like "0000-00-00" doesn't slip through as midnight.
		if ( $timestamp === false || (int) date( 'Y', $timestamp ) < 1000 ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			return '';
		}

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Using date() to match strtotime() timezone context and avoid incorrect time shifts.
		return date( 'g:i A', $timestamp );
	}

	/**
	 * Render a (year, month, day) triple in the field's configured date_format.
	 *
	 * Translates datepicker tokens (mm/dd/yyyy etc.) to PHP date() tokens so the
	 * preparer's output matches what `WPForms\Pro\Forms\Fields\DateTime\Field::format()`
	 * expects to receive — otherwise the entry's display `value` ends up in the
	 * preparer's canonical m/d/Y instead of the field's actual format.
	 *
	 * @since 1.10.1
	 *
	 * @param int    $year        Year.
	 * @param int    $month       Month.
	 * @param int    $day         Day.
	 * @param string $date_format Field's configured date format.
	 *
	 * @return string
	 */
	private function format_date( int $year, int $month, int $day, string $date_format ): string {

		$timestamp = mktime( 12, 0, 0, $month, $day, $year );

		if ( $timestamp === false ) {
			return '';
		}

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Output stays in the local interpretation supplied by the field's date_format.
		return date( $this->normalize_date_format( $date_format ), $timestamp );
	}

	/**
	 * Translate datepicker date_format tokens into PHP date() tokens.
	 *
	 * @since 1.10.1
	 *
	 * @param string $date_format Field's configured date format.
	 *
	 * @return string
	 */
	private function normalize_date_format( string $date_format ): string {

		static $map = [
			'mm/dd/yyyy'   => 'm/d/Y',
			'dd/mm/yyyy'   => 'd/m/Y',
			'mmmm d, yyyy' => 'F j, Y',
		];

		return $map[ $date_format ] ?? $date_format;
	}

	/**
	 * Get the error message for a given status and subfield.
	 *
	 * @since 1.10.1
	 *
	 * @param string $status   Error status: 'skipped' or 'fixed'.
	 * @param string $subfield Subfield key: 'date', 'time', or empty.
	 *
	 * @return string
	 */
	public function get_error_message( string $status, string $subfield = '' ): string {

		if ( $subfield === 'date' ) {
			return esc_html__( 'Invalid date', 'wpforms' );
		}

		if ( $subfield === 'time' ) {
			return esc_html__( 'Invalid time', 'wpforms' );
		}

		return esc_html__( 'Invalid date/time', 'wpforms' );
	}
}
