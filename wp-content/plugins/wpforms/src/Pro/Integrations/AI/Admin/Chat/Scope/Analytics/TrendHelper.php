<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics;

use DateTimeImmutable;

/**
 * Trend metric resolution, date window derivation, and shared date utilities.
 *
 * Extracted from Analytics to comply with the 20-method-per-class threshold.
 *
 * @since 2.0.0
 */
class TrendHelper {

	/**
	 * Default lookback window (days).
	 *
	 * @since 2.0.0
	 */
	private const DEFAULT_LOOKBACK_DAYS = 30;

	/**
	 * Allowed granularity values for the `form_stats_trend` data source.
	 *
	 * @since 2.0.0
	 */
	private const TREND_GRANULARITIES = [ 'day', 'week', 'month' ];

	/**
	 * Default granularity when the caller does not supply one.
	 *
	 * @since 2.0.0
	 */
	private const TREND_DEFAULT_GRANULARITY = 'month';

	/**
	 * Default lookback window per granularity (when no explicit `date_range` is
	 * supplied). Tuned for chart readability on the 440px chat canvas.
	 *
	 * - day:   30 days  → ~30 line points.
	 * - week:  12 weeks → ~12 line points.
	 * - month: 6 months → 6 column bars.
	 *
	 * @since 2.0.0
	 */
	private const TREND_DEFAULT_LOOKBACK = [
		'day'   => 30,
		'week'  => 12,
		'month' => 6,
	];

	/**
	 * Hard cap on rows returned by a single `form_stats_trend` call. Keeps the
	 * LLM context bounded; the most-recent N rows are kept when the resolved
	 * window would exceed the cap.
	 *
	 * @since 2.0.0
	 */
	public const TREND_MAX_ROWS = [
		'day'   => 90,
		'week'  => 52,
		'month' => 24,
	];

	/**
	 * Allow-list of metric names accepted by `form_stats_trend`'s `metrics` arg.
	 *
	 * Order matches the per-row projection emitted by
	 * `Pro\Db\Analytics\DB::get_form_stats_trend()` so callers iterating the list
	 * see metrics in a stable, chart-friendly order.
	 *
	 * @since 2.0.0
	 */
	private const TREND_METRICS = [
		'views',
		'submissions',
		'interactions',
		'abandonments',
		'errors',
		'conversion_rate',
		'abandonment_rate',
		'error_rate',
	];

	/**
	 * Resolve and validate the optional `metrics` arg for `form_stats_trend`.
	 *
	 * When the caller supplies no `metrics` (or only unknown values), the full
	 * allow-list is returned so the LLM sees every dimension by default. When
	 * the caller supplies a subset, unknown entries are silently dropped and
	 * the survivors keep the caller-supplied order — that ordering flows
	 * through to the projected row keys, which is convenient for chart UIs.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array List of metric names, guaranteed non-empty.
	 */
	public function resolve_trend_metrics( array $payload ): array {

		$args      = (array) ( $payload['request_data']['args'] ?? [] );
		$requested = (array) ( $args['metrics'] ?? [] );

		$resolved = $this->filter_known_trend_metrics( $requested );

		if ( $resolved === [] ) {
			return self::TREND_METRICS;
		}

		return $resolved;
	}

	/**
	 * Filter a raw metrics list down to known, de-duplicated trend metrics.
	 *
	 * Unknown and non-string entries are dropped; survivors keep the caller's
	 * order (first occurrence wins on duplicates).
	 *
	 * @since 2.0.0
	 *
	 * @param array $requested Raw metric candidates from the caller.
	 *
	 * @return array Allow-listed, de-duplicated metric names (may be empty).
	 */
	private function filter_known_trend_metrics( array $requested ): array {

		$resolved = [];

		foreach ( $requested as $candidate ) {
			$candidate = is_string( $candidate ) ? $candidate : '';

			if ( $candidate === '' || ! in_array( $candidate, self::TREND_METRICS, true ) ) {
				continue;
			}

			if ( in_array( $candidate, $resolved, true ) ) {
				continue;
			}

			$resolved[] = $candidate;
		}

		return $resolved;
	}

	/**
	 * Project trend rows down to `period` + the requested metric subset.
	 *
	 * Keys are emitted in the caller-supplied metric order so the result is
	 * stable for downstream rendering.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows    Rows returned by `DB::get_form_stats_trend()`.
	 * @param array $metrics Resolved metric names (allow-listed, ordered).
	 *
	 * @return array Projected rows.
	 */
	public function project_trend_metrics( array $rows, array $metrics ): array {

		$projected = [];

		foreach ( $rows as $row ) {
			$entry = [ 'period' => (string) ( $row['period'] ?? '' ) ];

			foreach ( $metrics as $metric ) {
				if ( array_key_exists( $metric, $row ) ) {
					$entry[ $metric ] = $row[ $metric ];
				}
			}

			$projected[] = $entry;
		}

		return $projected;
	}

	/**
	 * Resolve and validate the `granularity` arg.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return string One of `day`, `week`, `month`.
	 */
	public function resolve_trend_granularity( array $payload ): string {

		$args      = (array) ( $payload['request_data']['args'] ?? [] );
		$candidate = isset( $args['granularity'] ) ? (string) $args['granularity'] : '';

		if ( in_array( $candidate, self::TREND_GRANULARITIES, true ) ) {
			return $candidate;
		}

		return self::TREND_DEFAULT_GRANULARITY;
	}

	/**
	 * Derive the [from, to] window for `form_stats_trend`.
	 *
	 * Precedence:
	 *   1. `request_data.args.date_range` (LLM-driven).
	 *   2. `pageState.analytics.date_range` (surface picker).
	 *   3. Granularity-specific default lookback ending today (day) / this
	 *      week's Sunday (week) / current month's last day (month).
	 *
	 * @since 2.0.0
	 *
	 * @param array  $payload     Request payload.
	 * @param string $granularity Resolved granularity.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	public function derive_trend_date_range( array $payload, string $granularity ): array {

		$args_range = $this->args_date_range( $payload );

		if ( $args_range !== null ) {
			return $args_range;
		}

		$page_state_range = $this->page_state_date_range( $payload );

		if ( $page_state_range !== null ) {
			return $page_state_range;
		}

		return $this->default_trend_window( $granularity );
	}

	/**
	 * Resolve the [from, to] window from `request_data.args.date_range`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array|null Tuple [ from, to ] as 'Y-m-d' strings, or null when absent.
	 */
	public function args_date_range( array $payload ): ?array {

		$args  = (array) ( $payload['request_data']['args'] ?? [] );
		$range = (array) ( $args['date_range'] ?? [] );

		return $this->range_tuple_or_null( $range );
	}

	/**
	 * Resolve the [from, to] window from `pageState.analytics.date_range`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array|null Tuple [ from, to ] as 'Y-m-d' strings, or null when absent.
	 */
	public function page_state_date_range( array $payload ): ?array {

		$page_state = (array) ( $payload['pageState'] ?? [] );
		$analytics  = (array) ( $page_state['analytics'] ?? [] );
		$range      = (array) ( $analytics['date_range'] ?? [] );

		return $this->range_tuple_or_null( $range );
	}

	/**
	 * Normalize a raw date-range array into a [from, to] tuple.
	 *
	 * @since 2.0.0
	 *
	 * @param array $range Raw range array with optional `from`/`to` keys.
	 *
	 * @return array|null Tuple [ from, to ] as strings, or null when either end is empty.
	 */
	private function range_tuple_or_null( array $range ): ?array {

		$from = isset( $range['from'] ) ? (string) $range['from'] : '';
		$to   = isset( $range['to'] ) ? (string) $range['to'] : '';

		// Reject anything that is not a real Y-m-d date. An unvalidated value
		// ("banana", "2026-13-45") would flow into the trend SQL window and
		// silently produce an empty result; returning null falls back to the
		// default window instead.
		if ( ! $this->is_iso_date( $from ) || ! $this->is_iso_date( $to ) ) {
			return null;
		}

		return [ $from, $to ];
	}

	/**
	 * Whether a value is a valid `Y-m-d` calendar date.
	 *
	 * The round-trip comparison rejects out-of-range parts that DateTime would
	 * otherwise roll over (e.g. `2026-13-45`).
	 *
	 * @since 2.0.0
	 *
	 * @param string $value Candidate date string.
	 *
	 * @return bool True when $value parses back to itself as Y-m-d.
	 */
	private function is_iso_date( string $value ): bool {

		$date = DateTimeImmutable::createFromFormat( 'Y-m-d', $value );

		return $date !== false && $date->format( 'Y-m-d' ) === $value;
	}

	/**
	 * Build the granularity-specific default [from, to] trend window.
	 *
	 * @since 2.0.0
	 *
	 * @param string $granularity One of `day`, `week`, `month`.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function default_trend_window( string $granularity ): array {

		$count = self::TREND_DEFAULT_LOOKBACK[ $granularity ] ?? self::TREND_DEFAULT_LOOKBACK['month'];
		$today = new DateTimeImmutable( 'today', wp_timezone() );

		if ( $granularity === 'day' ) {
			return $this->relative_trend_window( $count, 'days', $today );
		}

		if ( $granularity === 'week' ) {
			return $this->relative_trend_window( $count, 'weeks', $today );
		}

		return $this->month_trend_window( $count, $today );
	}

	/**
	 * Build a day/week trend window ending today, looking back `$count - 1` units.
	 *
	 * @since 2.0.0
	 *
	 * @param int               $count Number of points in the window.
	 * @param string            $unit  Relative unit word (`days` or `weeks`).
	 * @param DateTimeImmutable $today Today in the site's timezone.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function relative_trend_window( int $count, string $unit, DateTimeImmutable $today ): array {

		$steps = $count - 1;
		$from  = $today->modify( "-{$steps} {$unit}" );

		return [
			$from !== false ? $from->format( 'Y-m-d' ) : $today->format( 'Y-m-d' ),
			$today->format( 'Y-m-d' ),
		];
	}

	/**
	 * Build the month default trend window with both ends aligned to month boundaries.
	 *
	 * @since 2.0.0
	 *
	 * @param int               $count Number of months in the window.
	 * @param DateTimeImmutable $today Today in the site's timezone.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function month_trend_window( int $count, DateTimeImmutable $today ): array {

		// Month default — align both ends to month boundaries.
		$months  = $count - 1;
		$to_date = $today->format( 'Y-m-t' );
		$from    = $today->modify( "-{$months} months" );

		return [
			$from !== false ? $from->format( 'Y-m-01' ) : $today->format( 'Y-m-01' ),
			$to_date,
		];
	}

	/**
	 * Derive a [from, to] date pair (Y-m-d) from the payload.
	 *
	 * Precedence: `request_data.args.date_range` (LLM-driven) →
	 * `pageState.analytics.date_range` (surface picker) → last `DEFAULT_LOOKBACK_DAYS`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	public function derive_date_range( array $payload ): array {

		$args_range = $this->args_date_range( $payload );

		if ( $args_range !== null ) {
			return $args_range;
		}

		$page_state_range = $this->page_state_date_range( $payload );

		if ( $page_state_range !== null ) {
			return $page_state_range;
		}

		return $this->default_lookback_window();
	}

	/**
	 * Build the default [from, to] window ending today, looking back `DEFAULT_LOOKBACK_DAYS`.
	 *
	 * @since 2.0.0
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function default_lookback_window(): array {

		$today   = new DateTimeImmutable( 'today', wp_timezone() );
		$to_date = $today->format( 'Y-m-d' );
		$from    = $today->modify( '-' . self::DEFAULT_LOOKBACK_DAYS . ' days' );

		return [
			$from !== false ? $from->format( 'Y-m-d' ) : $to_date,
			$to_date,
		];
	}
}
