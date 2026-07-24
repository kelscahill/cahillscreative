<?php

namespace WPForms\Pro\Analytics;

use WPForms\Pro\Db\Analytics\DB;

/**
 * Canonical analytics-stats service.
 *
 * Single source of truth for the enriched form-level and field-level shapes
 * consumed by the Analytics admin page AND the AI chat Analytics scope. The
 * `DB` layer stops at raw aggregates; this class owns derivation (`*_pct`,
 * `interactions`, `avg_time_s`), today's-snapshot merging for field rows,
 * non-interactive field filtering, suppression of fields not rendered on the
 * frontend, and metadata enrichment.
 *
 * @since 2.0.0
 */
class Stats {

	/**
	 * Field type slugs with no meaningful interaction signal — excluded from
	 * field-level stats so the LLM / table doesn't waste space on rows that
	 * cannot have views/interactions/errors.
	 *
	 * @since 2.0.0
	 */
	public const NON_INTERACTIVE_FIELD_TYPES = [
		'html',
		'content',
		'divider',
		'pagebreak',
		'entry-preview',
		'payment-total',
		'internal-information',
		'layout',
		'hidden',
	];

	/**
	 * Zero-stat row shape for interactive fields that have no analytics data yet.
	 *
	 * @since 2.0.0
	 */
	private const ZERO_FIELD_STATS = [
		'field_id'        => 0,
		'subfield_key'    => '',
		'label'           => '',
		'type'            => '',
		'views'           => 0,
		'click_count'     => 0,
		'focus_count'     => 0,
		'input_count'     => 0,
		'interactions'    => 0,
		'completion_rate' => 0.0,
		'abandonments'    => 0,
		'abandonment_pct' => 0.0,
		'errors'          => 0,
		'error_pct'       => 0.0,
		'avg_time_s'      => 0,
	];

	/**
	 * Object-cache group for persistently cached immutable (no-today) ranges.
	 *
	 * @since 2.0.0
	 */
	private const CACHE_GROUP = 'wpforms_pro_analytics_stats';

	/**
	 * Request-level memoization store, keyed by a signature of the method args.
	 *
	 * Kills duplicate heavy aggregate queries within a single admin render /
	 * AJAX request. Transparent: identical inputs always map to the same output.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $cache = [];

	/**
	 * Form-level stats for the inclusive [$from, $to] window.
	 *
	 * `views` / `submissions` come from the forms-aggregate table (plus today's
	 * form snapshot when the range includes today). The field-derived totals
	 * (`interactions`, `abandonments`, `errors`, `field_views`) are reconciled to
	 * the exact rows the field-activity table renders — summed from
	 * `get_field_stats()`, so deleted and non-interactive fields never inflate the
	 * cards. Derived rates (`conversion_rate`, `abandonment_pct`, `error_pct`) are
	 * computed last from the reconciled totals (0–100 scale, 1 decimal, div-by-zero safe).
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id   Form ID.
	 * @param string $from      Range start (Y-m-d).
	 * @param string $to        Range end (Y-m-d).
	 * @param array  $form_data Decoded form array used to filter the field-derived
	 *                          totals to the rows the analytics table displays.
	 *
	 * @return array Keys: views, submissions, conversion_rate,
	 *               interactions, abandonments, abandonment_pct,
	 *               errors, error_pct, field_views.
	 */
	public function get_form_stats( int $form_id, string $from, string $to, array $form_data = [] ): array {

		$fields_def = is_array( $form_data['fields'] ?? null ) ? $form_data['fields'] : [];

		// Field-def is part of the signature: the field-derived totals now depend
		// on which fields exist, so a rename/delete must not return a stale total.
		// Mirrors the get_field_stats() signature.
		$signature = md5( implode( '|', [ 'form', $form_id, $from, $to, wp_json_encode( $fields_def ) ] ) ); // NOSONAR.

		return $this->remember(
			$signature,
			$from,
			$to,
			function () use ( $form_id, $from, $to, $form_data ): array {
				return $this->build_form_stats( $form_id, $from, $to, $form_data );
			}
		);
	}

	/**
	 * Compute the form-level stats (uncached).
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id   Form ID.
	 * @param string $from      Range start (Y-m-d).
	 * @param string $to        Range end (Y-m-d).
	 * @param array  $form_data Decoded form array used to filter the field-derived totals.
	 *
	 * @return array Keys: views, submissions, conversion_rate,
	 *               interactions, abandonments, abandonment_pct,
	 *               errors, error_pct, field_views.
	 */
	private function build_form_stats( int $form_id, string $from, string $to, array $form_data = [] ): array {

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db ) {
			return [];
		}

		$stats = (array) $db->get_form_stats( $form_id, $from, $to );

		if ( $this->range_includes_today( $from, $to ) ) {
			$stats = $this->merge_today_form_stats( $db, $form_id, $stats );
		}

		// Reconcile the field-derived totals to the exact rows the field-activity
		// table renders. Deleted fields (absent from form_data) and non-interactive
		// types are dropped by enrich_field_rows(), so their orphaned counters no
		// longer inflate the cards above the visible per-field breakdown. The field
		// rows already include today's snapshot via get_field_stats().
		$stats = array_merge( $stats, $this->sum_field_rows( $this->get_field_stats( $form_id, $from, $to, $form_data ) ) );

		$views                    = DB::int_from_row( $stats, 'views' );
		$stats['conversion_rate'] = DB::safe_pct( DB::int_from_row( $stats, 'submissions' ), $views );
		// Abandonment rate is per field impression, not per form view: the count is
		// the sum of per-field abandonments, so dividing by form views over-counts
		// (a session that abandons N fields would read as N/views). Dividing by the
		// total field impressions (sum of per-field views) keeps it bounded to 100%,
		// since each field's abandonments never exceed its own views.
		$stats['abandonment_pct'] = DB::safe_pct( DB::int_from_row( $stats, 'abandonments' ), DB::int_from_row( $stats, 'field_views' ) );
		$stats['error_pct']       = DB::safe_pct( DB::int_from_row( $stats, 'errors' ), $views );

		return $stats;
	}

	/**
	 * Fold today's unprocessed form-level snapshot into the windowed stats.
	 *
	 * Only `views` and `submissions` are merged here (from
	 * `analytics_snapshot_form`). The field-derived totals (interactions,
	 * abandonments, errors, field_views) are reconciled separately in
	 * `build_form_stats()` by summing the displayed field rows, whose own
	 * today-merge already runs inside `get_field_stats()`.
	 *
	 * @since 2.0.0
	 *
	 * @param object $db      Analytics DB instance.
	 * @param int    $form_id Form ID.
	 * @param array  $stats   Raw windowed stats from `DB::get_form_stats()`.
	 *
	 * @return array Stats with today's form-level counters added in.
	 */
	private function merge_today_form_stats( object $db, int $form_id, array $stats ): array {

		$today_form = (array) $db->get_today_form_stats( $form_id );

		$stats['views']       = DB::int_from_row( $stats, 'views' ) + DB::int_from_row( $today_form, 'views' );
		$stats['submissions'] = DB::int_from_row( $stats, 'submissions' ) + DB::int_from_row( $today_form, 'submissions' );

		return $stats;
	}

	/**
	 * Sum the displayed field rows into the form-level field-derived totals.
	 *
	 * The cards must equal the sum of the rows the field-activity table renders,
	 * so the form-level interactions / abandonments / errors / field_views are
	 * derived from the already-filtered enriched rows rather than an unfiltered
	 * SUM over every `analytics_fields` row (which would include the deleted and
	 * non-interactive fields the table never shows).
	 *
	 * @since 2.0.0
	 *
	 * @param array $field_rows Enriched field rows from `get_field_stats()`.
	 *
	 * @return array Keys: interactions, abandonments, errors, field_views.
	 */
	private function sum_field_rows( array $field_rows ): array {

		$totals = [
			'interactions' => 0,
			'abandonments' => 0,
			'errors'       => 0,
			'field_views'  => 0,
		];

		foreach ( $field_rows as $row ) {
			$totals['interactions'] += DB::int_from_row( $row, 'interactions' );
			$totals['abandonments'] += DB::int_from_row( $row, 'abandonments' );
			$totals['errors']       += DB::int_from_row( $row, 'errors' );
			$totals['field_views']  += DB::int_from_row( $row, 'views' );
		}

		return $totals;
	}

	/**
	 * Field-level rows for the inclusive [$from, $to] window.
	 *
	 * Steps: aggregated rows from analytics_fields → optionally merge today's
	 * unprocessed snapshot rows → enrich with label/type from form_data →
	 * filter non-interactive types → compute derived metrics.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id   Form ID.
	 * @param string $from      Range start (Y-m-d).
	 * @param string $to        Range end (Y-m-d).
	 * @param array  $form_data Decoded form array (`wpforms()->obj('form')->get( $id, [ 'content_only' => true ] )`).
	 *
	 * @return array Each row:
	 *                           { field_id, subfield_key, label, type, views, click_count,
	 *                             focus_count, input_count, interactions,
	 *                             completion_rate, abandonments, abandonment_pct,
	 *                             errors, error_pct, avg_time_s }.
	 */
	public function get_field_stats( int $form_id, string $from, string $to, array $form_data ): array {

		$fields_def = is_array( $form_data['fields'] ?? null ) ? $form_data['fields'] : [];

		// The fields definition affects enrichment / filtering, so it is part of
		// the cache signature — a label/type change must not return a stale shape.
		$signature = md5( implode( '|', [ 'field', $form_id, $from, $to, wp_json_encode( $fields_def ) ] ) ); // NOSONAR.

		return $this->remember(
			$signature,
			$from,
			$to,
			function () use ( $form_id, $from, $to, $form_data ): array {
				return $this->build_field_stats( $form_id, $from, $to, $form_data );
			}
		);
	}

	/**
	 * Compute the field-level rows (uncached).
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id   Form ID.
	 * @param string $from      Range start (Y-m-d).
	 * @param string $to        Range end (Y-m-d).
	 * @param array  $form_data Decoded form array.
	 *
	 * @return array Enriched, filtered field rows.
	 */
	private function build_field_stats( int $form_id, string $from, string $to, array $form_data ): array {

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db ) {
			return [];
		}

		$rows = (array) $db->get_field_stats( $form_id, $from, $to );

		if ( $this->range_includes_today( $from, $to ) ) {
			$today = (array) $db->get_today_field_stats( $form_id );
			$rows  = $this->merge_today_field_stats( $rows, $today );

			$today_abandonments = (array) $db->get_today_field_abandonments( $form_id );
			$rows               = $this->merge_today_field_abandonments( $rows, $today_abandonments );
		}

		return $this->enrich_field_rows( $rows, $form_data );
	}

	/**
	 * Enrich every raw field row, dropping the ones `enrich_field_row()` rejects,
	 * and return them in the form's current field order rather than the DB's
	 * `field_id` order.
	 *
	 * Non-interactive types and rows whose field is missing from `form_data`
	 * return null from `enrich_field_row()` and are skipped here. The order
	 * follows `$fields_def` key order, which is the form's visual field order
	 * (the order Reset Order restores on the analytics table). Fields without
	 * stats are simply absent. A composite field contributes one row per subfield,
	 * all emitted consecutively at the field's position. Layout/repeater children
	 * are kept flat, in their `field_id` position within `$fields_def`, not
	 * resolved to their nested visual position.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows      Raw field rows (possibly already today-merged).
	 * @param array $form_data Decoded form array.
	 *
	 * @return array Enriched, filtered rows.
	 */
	private function enrich_field_rows( array $rows, array $form_data ): array {

		$fields_def = is_array( $form_data['fields'] ?? null ) ? $form_data['fields'] : [];

		// Group the enriched rows by field id, so we can then emit them in the
		// form's current field order instead of the DB's field_id order. A
		// composite field yields one enriched row per subfield, so each field id
		// maps to a LIST of rows — keying by field id alone would overwrite every
		// subfield but the last and collapse the composite field to a single row.
		$rows_by_field_id = [];

		foreach ( $rows as $row ) {
			$entry = $this->enrich_field_row( $row, $fields_def );

			if ( $entry !== null ) {
				$rows_by_field_id[ $entry['field_id'] ][] = $entry;
			}
		}

		// No enriched rows means the form has no analytics data at all — return
		// empty rather than a table full of zeros for every field.
		if ( empty( $rows_by_field_id ) ) {
			return [];
		}

		// Walk the fields in their current form order and emit every subfield row
		// for each one. Interactive fields with no stats get a zero-stat row so
		// they still appear in the table. Deleted fields never appear here.
		$ordered = [];

		foreach ( $fields_def as $field_id => $field ) {
			if ( ! empty( $rows_by_field_id[ $field_id ] ) ) {
				$ordered = array_merge(
					$ordered,
					$this->build_subfield_breakdown( (int) $field_id, $field, $rows_by_field_id[ $field_id ] )
				);

				continue;
			}

			// Skip placeholder rows for fields not rendered on the frontend ( e.g.
			// an addon field whose addon is deactivated ), so the table does not
			// show a misleading all-zero row for a field visitors never see.
			if ( $this->should_hide_undisplayed_field( $field, $form_data ) ) {
				continue;
			}

			$ordered = array_merge( $ordered, $this->build_zero_stat_rows( (int) $field_id, $field ) );
		}

		return $ordered;
	}

	/**
	 * Whether a zero-data field should be omitted from the field-activity table.
	 *
	 * A field can live in `form_data['fields']` yet never render on the frontend
	 * ( a deactivated-addon field, a disabled Pro field, or a Single Item payment
	 * field set to "Hidden" ), in which case it has no analytics data and would
	 * otherwise show as a misleading all-zero row. `FieldVisibility::is_displayed()`
	 * decides "rendered" exactly as `Frontend::render_field()` does.
	 *
	 * Only synthesized placeholder rows are suppressed. Fields that carry real
	 * tracked data flow through `enrich_field_row()` and are never dropped, even
	 * if they are no longer displayed — that history is still valid.
	 *
	 * @since 2.0.0
	 *
	 * @param array $field     Field definition from `form_data['fields']`.
	 * @param array $form_data Decoded form array.
	 *
	 * @return bool
	 */
	private function should_hide_undisplayed_field( array $field, array $form_data ): bool {

		if ( FieldVisibility::is_displayed( $field, $form_data ) ) {
			return false;
		}

		/**
		 * Filters whether fields that are not rendered on the frontend are hidden
		 * from the analytics field-activity table when they have no tracked data.
		 *
		 * Defaults to true. Return false to restore the previous behavior, where
		 * every field in the form definition gets a row even when it cannot render.
		 *
		 * @since 2.0.0
		 *
		 * @param bool  $hide  Whether to hide the undisplayed field. Default true.
		 * @param array $field Field definition from `form_data['fields']`.
		 */
		return (bool) apply_filters( 'wpforms_pro_analytics_stats_hide_undisplayed_fields', true, $field );
	}

	/**
	 * Build the rendered row list for one field's group.
	 *
	 * Simple fields ( and simple-format Name ) are returned unchanged. A composite
	 * field that has at least one real subfield row is rendered as its full
	 * subfield breakdown in canonical order — real data where a subfield has it, a
	 * zero-stat row otherwise — so the breakdown is always complete even when only
	 * some subfields scrolled into view. The field-level base row ( empty
	 * subfield_key ) is dropped, since the breakdown supersedes it. A composite
	 * with only a base row ( no subfield rows yet ) is returned unchanged.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field    Field definition from `form_data['fields']`.
	 * @param array $rows     Enriched rows for this field id.
	 *
	 * @return array
	 */
	private function build_subfield_breakdown( int $field_id, array $field, array $rows ): array {

		// Index the rows that carry a subfield key ( skip the field-level base row ).
		$present = array_column(
			array_filter(
				$rows,
				static function ( $row ) {

					return (string) ( $row['subfield_key'] ?? '' ) !== '';
				}
			),
			null,
			'subfield_key'
		);

		// Not a composite with subfield data ( simple field, or a composite that
		// only carries its field-level base row ) — render the group as it is.
		if ( empty( $present ) ) {
			return $rows;
		}

		$zero_rows = $this->build_zero_stat_rows( $field_id, $field );

		// Defensive: a non-interactive/empty type yields no canonical rows; keep
		// the original rows rather than dropping everything.
		if ( empty( $zero_rows ) ) {
			return $rows;
		}

		// Emit the visible subfields in canonical order — real row where present,
		// else a zero-stat placeholder. Consume matched keys from $present as we go.
		$breakdown = [];

		foreach ( $zero_rows as $zero_row ) {
			$key         = (string) ( $zero_row['subfield_key'] ?? '' );
			$breakdown[] = $present[ $key ] ?? $zero_row;

			unset( $present[ $key ] );
		}

		// Anything left carries real data for a subfield hidden by the field's
		// current settings — keep it ( hide only when hidden AND empty ).
		foreach ( $present as $orphan ) {
			$breakdown[] = $orphan;
		}

		return $breakdown;
	}

	/**
	 * Whether the inclusive [$from, $to] window covers today (site timezone).
	 *
	 * Compares ISO `Y-m-d` strings — lexicographically safe.
	 *
	 * @since 2.0.0
	 *
	 * @param string $from Range start (Y-m-d).
	 * @param string $to   Range end (Y-m-d).
	 *
	 * @return bool
	 */
	private function range_includes_today( string $from, string $to ): bool {

		$today = (string) current_time( 'Y-m-d' );

		return $today >= $from && $today <= $to;
	}

	/**
	 * Resolve a stats result through the request-level memo and, for immutable
	 * historical ranges, the persistent object cache.
	 *
	 * Layering:
	 *   1. Request memo (`$this->cache`) — kills duplicate heavy queries within a
	 *      single render. Always active.
	 *   2. Persistent cache (`wp_cache_*`) — only when the range does NOT include
	 *      today, because a today-inclusive range must stay live (today's counters
	 *      change minute to minute). The persistent layer is skipped entirely for
	 *      today-inclusive ranges, so no stale-today risk is introduced.
	 *
	 * @since 2.0.0
	 *
	 * @param string   $signature Cache key derived from the caller's args.
	 * @param string   $from      Range start (Y-m-d).
	 * @param string   $to        Range end (Y-m-d).
	 * @param callable $compute   Producer invoked on a miss; returns the array result.
	 *
	 * @return array The cached or freshly computed result.
	 */
	private function remember( string $signature, string $from, string $to, callable $compute ): array {

		if ( isset( $this->cache[ $signature ] ) ) {
			return $this->cache[ $signature ];
		}

		// Persist only immutable (no-today) ranges, and only when an object cache
		// API is actually available — guarding keeps the request-memo path free of
		// any hard dependency on the cache layer.
		$persist = ! $this->range_includes_today( $from, $to ) && function_exists( 'wp_cache_get' );

		if ( $persist ) {
			$found  = false;
			$cached = wp_cache_get( $signature, self::CACHE_GROUP, false, $found );

			if ( $found && is_array( $cached ) ) {
				$this->cache[ $signature ] = $cached;

				return $cached;
			}
		}

		$result = (array) $compute();

		$this->cache[ $signature ] = $result;

		if ( $persist ) {
			wp_cache_set( $signature, $result, self::CACHE_GROUP, $this->cache_ttl() );
		}

		return $result;
	}

	/**
	 * Persistent-cache TTL for immutable ranges, in seconds.
	 *
	 * Defaults to one hour. `HOUR_IN_SECONDS` is resolved at call time (guarded)
	 * so the value is not baked into a class constant.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	private function cache_ttl(): int {

		$default = defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600;

		/**
		 * Filters the persistent-cache TTL (seconds) for immutable analytics-stats ranges.
		 *
		 * Only applies to ranges that do not include today.
		 *
		 * @since 2.0.0
		 *
		 * @param int $ttl Cache lifetime in seconds.
		 */
		return (int) apply_filters( 'wpforms_pro_analytics_stats_cache_ttl', $default );
	}

	/**
	 * Merge today's unprocessed field-snapshot rows into the aggregated rows.
	 *
	 * Sums the integer counters per `(field_id, subfield_key)`; subfields that
	 * only appear in today's snapshot are added as new rows. Keying on the
	 * subfield key keeps composite-field subfields independent — a composite
	 * field yields one row per subfield in both layers, so merging by field_id
	 * alone would collapse them and double-count.
	 *
	 * @since 2.0.0
	 *
	 * @param array $aggregated Aggregated rows from `analytics_fields`.
	 * @param array $today      Today's rows from `DB::get_today_field_stats()`.
	 *
	 * @return array
	 */
	private function merge_today_field_stats( array $aggregated, array $today ): array {

		$by_field = [];

		foreach ( $aggregated as $row ) {
			$by_field[ $this->field_row_key( $row ) ] = $row;
		}

		foreach ( $today as $row ) {
			$key = $this->field_row_key( $row );

			$by_field[ $key ] = isset( $by_field[ $key ] )
				? $this->merge_field_counters( $by_field[ $key ], $row )
				: $row;
		}

		return array_values( $by_field );
	}

	/**
	 * Fold today's field-level abandonment counts into the already-merged rows.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows               Field rows (already merged with today's counters).
	 * @param array $today_abandonments Rows from `DB::get_today_field_abandonments()`.
	 *
	 * @return array Field rows with today's abandonments added.
	 */
	private function merge_today_field_abandonments( array $rows, array $today_abandonments ): array {

		if ( empty( $today_abandonments ) ) {
			return $rows;
		}

		$abandon_map = [];

		foreach ( $today_abandonments as $ab ) {
			$abandon_map[ $this->field_row_key( $ab ) ] = DB::int_from_row( $ab, 'abandonments' );
		}

		foreach ( $rows as &$row ) {
			$key = $this->field_row_key( $row );

			if ( isset( $abandon_map[ $key ] ) ) {
				$row['abandonments'] = DB::int_from_row( $row, 'abandonments' ) + $abandon_map[ $key ];
			}
		}

		return $rows;
	}

	/**
	 * Build the merge key for a field row: `"<field_id>:<subfield_key>"`.
	 *
	 * Composite fields produce one row per subfield, so the merge between the
	 * persisted-aggregate and today-snapshot layers must key on both parts.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row Field row carrying `field_id` and (optionally) `subfield_key`.
	 *
	 * @return string
	 */
	private function field_row_key( array $row ): string {

		return DB::int_from_row( $row, 'field_id' ) . ':' . (string) ( $row['subfield_key'] ?? '' );
	}

	/**
	 * Sum the integer counters of two field rows into a single merged row.
	 *
	 * Adds the today-snapshot row's counters onto the aggregated row's; keys
	 * absent from either side default to 0. Non-counter keys on `$base` are
	 * preserved unchanged.
	 *
	 * @since 2.0.0
	 *
	 * @param array $base Aggregated field row (kept as the base).
	 * @param array $add  Today's field row whose counters are added on.
	 *
	 * @return array Merged field row.
	 */
	private function merge_field_counters( array $base, array $add ): array {

		$counter_keys = [
			'views',
			'focus_count',
			'click_count',
			'input_count',
			'errors',
			'total_duration_ms',
			'duration_count',
			'abandonments',
		];

		foreach ( $counter_keys as $key ) {
			$base[ $key ] = DB::int_from_row( $base, $key ) + DB::int_from_row( $add, $key );
		}

		return $base;
	}

	/**
	 * Build zero-stat placeholder rows for a field with no analytics data.
	 *
	 * Composite fields (Name, Address, Date/Time) expand into one row per
	 * subfield so they match the shape they would have once real data arrives.
	 * Simple fields emit a single row.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field    Field definition from `form_data['fields']`.
	 *
	 * @return array List of zero-stat rows (may be 1 or many).
	 */
	private function build_zero_stat_rows( int $field_id, array $field ): array {

		$type = strtolower( (string) ( $field['type'] ?? '' ) );

		if ( $type === '' || in_array( $type, self::NON_INTERACTIVE_FIELD_TYPES, true ) ) {
			return [];
		}

		$subfields = SubfieldLabels::resolve( $field );

		if ( empty( $subfields ) ) {
			return [
				wp_parse_args(
					[
						'field_id' => $field_id,
						'label'    => SubfieldLabels::compose( $field, '' ),
						'type'     => $type,
					],
					self::ZERO_FIELD_STATS
				),
			];
		}

		$rows = [];

		foreach ( $subfields as $sub_key => $sub_label ) {
			$rows[] = wp_parse_args(
				[
					'field_id'     => $field_id,
					'subfield_key' => $sub_key,
					'label'        => SubfieldLabels::compose( $field, $sub_key ),
					'type'         => $type,
				],
				self::ZERO_FIELD_STATS
			);
		}

		return $rows;
	}

	/**
	 * Enrich a single raw field row with label/type and derived metrics.
	 *
	 * Returns null for rows whose field is missing from `form_data` or whose
	 * type is non-interactive — caller skips those.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row        Raw field row.
	 * @param array $fields_def Form data ['fields']` map keyed by index.
	 *
	 * @return array|null
	 */
	private function enrich_field_row( array $row, array $fields_def ): ?array {

		$field_id     = DB::int_from_row( $row, 'field_id' );
		$subfield_key = (string) ( $row['subfield_key'] ?? '' );
		$field        = (array) ( $fields_def[ $field_id ] ?? [] );
		$type         = strtolower( (string) ( $field['type'] ?? '' ) );

		if ( $type === '' || in_array( $type, self::NON_INTERACTIVE_FIELD_TYPES, true ) ) {
			return null;
		}

		$views        = DB::int_from_row( $row, 'views' );
		$focus        = DB::int_from_row( $row, 'focus_count' );
		$click        = DB::int_from_row( $row, 'click_count' );
		$input        = DB::int_from_row( $row, 'input_count' );
		$abandonments = DB::int_from_row( $row, 'abandonments' );
		$errors       = DB::int_from_row( $row, 'errors' );

		return [
			'field_id'        => $field_id,
			'subfield_key'    => $subfield_key,
			'label'           => SubfieldLabels::compose( $field, $subfield_key ),
			'type'            => $type,
			'views'           => $views,
			'click_count'     => $click,
			'focus_count'     => $focus,
			'input_count'     => $input,
			'interactions'    => $focus + $click + $input,
			'completion_rate' => DB::safe_pct( $input, $input + $focus + $click ),
			'abandonments'    => $abandonments,
			'abandonment_pct' => DB::safe_pct( $abandonments, $views ),
			'errors'          => $errors,
			'error_pct'       => DB::safe_pct( $errors, $views ),
			'avg_time_s'      => $this->avg_field_time_seconds( $row ),
		];
	}

	/**
	 * Compute the average per-field time in whole seconds.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row Raw field row with `total_duration_ms` / `duration_count`.
	 *
	 * @return int
	 */
	private function avg_field_time_seconds( array $row ): int {

		$duration_count = DB::int_from_row( $row, 'duration_count' );

		if ( $duration_count <= 0 ) {
			return 0;
		}

		return (int) round( ( DB::int_from_row( $row, 'total_duration_ms' ) / $duration_count ) / 1000 );
	}
}
