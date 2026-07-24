<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\FormsInventory;

use WPForms\Analytics\Analytics as AnalyticsFeature;
use WPForms\Db\Analytics\DB as AnalyticsDB;
use WPForms\Integrations\AI\Admin\Chat\Filter\Operators;
use WPForms\Integrations\AI\Admin\Chat\Scope\FormsInventory\FormSearcher as LiteFormSearcher;
use WPForms\Pro\Db\Analytics\DB as ProAnalyticsDB;

/**
 * Pro extension of FormSearcher — translates Pro-only filter fields.
 *
 * @since 2.0.0
 */
class FormSearcher extends LiteFormSearcher {

	/**
	 * Map the analytics field slug to its `wp_wpforms_analytics_forms` column.
	 *
	 * `analytics_interactions` is intentionally absent — interactions are a
	 * field-level metric (focus + click + input), so they are resolved against
	 * `wp_wpforms_analytics_fields` in a dedicated path rather than this
	 * forms-aggregate column map.
	 *
	 * @since 2.0.0
	 */
	private const ANALYTICS_COLUMN_MAP = [
		'analytics_views'       => 'views',
		'analytics_submissions' => 'submissions',
	];

	/**
	 * Lazy-built filter-operator helper.
	 *
	 * @since 2.0.0
	 *
	 * @var Operators|null
	 */
	private $operators;

	/**
	 * Lazy-built entries-table query helper.
	 *
	 * @since 2.0.0
	 *
	 * @var EntriesQuery|null
	 */
	private $entries_query;

	/**
	 * Whether this searcher can rank forms server-side by the given metric.
	 *
	 * Pro owns the entries table, so it can rank by `entries_count`. Other
	 * metrics are not yet supported by ranking and fall through to filtering.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Metric field slug.
	 *
	 * @return bool
	 */
	public function supports_metric_ranking( string $field ): bool {

		return $field === 'entries_count';
	}

	/**
	 * Rank form IDs by their entry count, descending or ascending.
	 *
	 * Delegates to {@see EntriesQuery::rank_form_ids_by_entries_count()}. Forms
	 * with zero entries are excluded — see that method for the rationale.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $limit        Maximum number of ranked IDs to return.
	 * @param string $order        Sort direction — `asc` for ascending, anything else descending.
	 * @param array  $restrict_ids When non-empty, rank only within these form IDs.
	 * @param string $since        ISO date floor (optional).
	 * @param string $until        ISO date ceiling (optional).
	 *
	 * @return array Form IDs in ranked order.
	 */
	public function rank_form_ids_by_entries_count( int $limit, string $order, array $restrict_ids, string $since, string $until ): array {

		return $this->get_entries_query()->rank_form_ids_by_entries_count( $limit, $order, $restrict_ids, $since, $until );
	}

	/**
	 * Count the forms that have at least one entry (within window / restrict set).
	 *
	 * Delegates to {@see EntriesQuery::count_forms_with_entries()}.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $restrict_ids When non-empty, count only within these form IDs.
	 * @param string $since        ISO date floor (optional).
	 * @param string $until        ISO date ceiling (optional).
	 *
	 * @return int Number of forms having at least one entry.
	 */
	public function count_forms_with_entries( array $restrict_ids, string $since, string $until ): int {

		return $this->get_entries_query()->count_forms_with_entries( $restrict_ids, $since, $until );
	}

	/**
	 * Translate Pro-only filters into a `post__in` pre-query result.
	 *
	 * `entries_count` is resolved against `wp_wpforms_entries` (COUNT + HAVING).
	 * `analytics_*` fields are resolved against `wp_wpforms_analytics_forms`
	 * using the lifetime sentinel row when no date scope is set, or summed over
	 * daily rows in the `since`/`until` window. `analytics_conversion` is
	 * derived (submissions / views * 100) so it filters in PHP after the
	 * underlying sums are fetched.
	 *
	 * The resolver merges its result into `args['post__in']` — intersection
	 * with any existing `post__in` happens via array_intersect so multiple
	 * Pro filters compose correctly with each other and with Lite filters.
	 *
	 * @since 2.0.0
	 *
	 * @param array $filter       Normalized filter (`{ field, op, value, since, until }`).
	 * @param array $args         WP_Query args accumulator.
	 * @param array $wheres       Posts_where callable accumulator.
	 * @param array $date_clauses Date_query accumulator.
	 * @param array $tax_clauses  Tax_query accumulator.
	 */
	protected function translate_extra_field( array $filter, array &$args, array &$wheres, array &$date_clauses, array &$tax_clauses ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$field = (string) $filter['field'];

		$matched_ids = $this->resolve_extra_field_ids( $field, $filter );

		if ( $matched_ids === null ) {
			// Unrecognised Pro field — no query contribution.
			return;
		}

		$this->merge_matched_ids_into_post_in( $args, $matched_ids );
	}

	/**
	 * Resolve a Pro filter field to the list of matching form IDs.
	 *
	 * Returns `null` for fields this scope does not own (so the caller can skip
	 * the `post__in` merge entirely); a recognised field always returns an array
	 * (empty when the operator is unsupported or analytics is disabled).
	 *
	 * @since 2.0.0
	 *
	 * @param string $field  Filter field slug.
	 * @param array  $filter Normalized filter (`{ field, op, value, since, until }`).
	 *
	 * @return array|null Matching form IDs, or null when the field is not recognised.
	 */
	private function resolve_extra_field_ids( string $field, array $filter ): ?array {

		$op    = (string) $filter['op'];
		$value = (int) ( $filter['value'] ?? 0 );
		$since = (string) ( $filter['since'] ?? '' );
		$until = (string) ( $filter['until'] ?? '' );

		if ( $field === 'entries_count' ) {
			return $this->get_entries_query()->query_form_ids_by_entries_count( $op, $value, $since, $until );
		}

		return $this->resolve_analytics_field_ids( $field, $op, $value, $since, $until );
	}

	/**
	 * Resolve an analytics-backed filter field to its matching form IDs.
	 *
	 * Returns `null` for fields this resolver does not own; a recognised analytics
	 * field always returns an array (empty when analytics is disabled).
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Filter field slug.
	 * @param string $op    Operator.
	 * @param int    $value Threshold value.
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array|null Matching form IDs, or null when the field is not an analytics field.
	 */
	private function resolve_analytics_field_ids( string $field, string $op, int $value, string $since, string $until ): ?array {

		if ( $field === 'analytics_conversion' ) {
			return AnalyticsFeature::is_enabled()
				? $this->query_form_ids_by_analytics_conversion( $op, $value, $since, $until )
				: [];
		}

		if ( $field === 'analytics_interactions' ) {
			return AnalyticsFeature::is_enabled()
				? $this->query_form_ids_by_analytics_interactions( $op, $value, $since, $until )
				: [];
		}

		if ( isset( self::ANALYTICS_COLUMN_MAP[ $field ] ) ) {
			return AnalyticsFeature::is_enabled()
				? $this->query_form_ids_by_analytics_metric( self::ANALYTICS_COLUMN_MAP[ $field ], $op, $value, $since, $until )
				: [];
		}

		return null;
	}

	/**
	 * Merge resolved form IDs into the query's `post__in` accumulator.
	 *
	 * The first Pro filter seeds `post__in`; later filters intersect with it so
	 * Pro filters compose with each other and with Lite filters. Empty results
	 * collapse to the `[0]` sentinel so WP_Query returns no rows.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args        WP_Query args accumulator.
	 * @param array $matched_ids Form IDs matched by the current filter.
	 *
	 * @return void
	 */
	private function merge_matched_ids_into_post_in( array &$args, array $matched_ids ): void {

		// Intersect with any existing `post__in` (set by prior filters or fields).
		if ( isset( $args['post__in'] ) ) {
			$args['post__in'] = array_values( array_intersect( (array) $args['post__in'], $matched_ids ) );

			if ( $args['post__in'] === [] ) {
				// Use sentinel that yields no rows but keeps WP_Query happy.
				$args['post__in'] = [ 0 ];
			}

			return;
		}

		// First Pro filter — seed `post__in`. Use `[0]` for empty so WP_Query
		// returns nothing (a missing post__in would return all rows).
		$args['post__in'] = $matched_ids === [] ? [ 0 ] : $matched_ids;
	}

	/**
	 * Find form IDs whose analytics metric satisfies the operator.
	 *
	 * Lifetime: filters on the sentinel row directly. Date-bounded: sums daily
	 * rows in the window then applies the operator via HAVING.
	 *
	 * @since 2.0.0
	 *
	 * @param string $column Analytics column name (`views`, `submissions`).
	 * @param string $op     Operator.
	 * @param int    $value  Threshold value.
	 * @param string $since  ISO date floor (optional).
	 * @param string $until  ISO date ceiling (optional).
	 *
	 * @return array Form IDs.
	 */
	private function query_form_ids_by_analytics_metric( string $column, string $op, int $value, string $since, string $until ): array {

		$column = sanitize_key( $column );

		if ( $since === '' && $until === '' ) {
			$ids = $this->query_analytics_metric_lifetime( $column, $op, $value );
		} else {
			$ids = $this->query_analytics_metric_window( $column, $op, $value, $since, $until );
		}

		return is_array( $ids ) ? array_map( 'intval', $ids ) : [];
	}

	/**
	 * Query the lifetime sentinel row for a single analytics metric.
	 *
	 * UNIONs today's unprocessed snapshots with the sentinel row and applies
	 * the operator via GROUP BY / HAVING so the threshold covers both.
	 *
	 * @since 2.0.0
	 *
	 * @param string $column Sanitized analytics column name.
	 * @param string $op     Operator.
	 * @param int    $value  Threshold value.
	 *
	 * @return array|null Raw `form_id` column values, or null when the operator is unsupported.
	 */
	private function query_analytics_metric_lifetime( string $column, string $op, int $value ): ?array {

		global $wpdb;

		$having_op = $this->get_operators()->to_sql( "SUM({$column})", $op, $value );

		if ( $having_op === null ) {
			return [];
		}

		$table      = $wpdb->prefix . 'wpforms_analytics_forms';
		$snap_table = AnalyticsDB::snapshots_table();

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id
				FROM (
					SELECT form_id, views, submissions
					FROM {$table}
					WHERE period_date = %s
					UNION ALL
					SELECT form_id,
					       COUNT(DISTINCT session_id) AS views,
					       COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
					FROM {$snap_table}
					WHERE processed = 0
						AND form_visible = 1
						AND occurred_at >= %s
						AND occurred_at < %s
					GROUP BY form_id
				) t
				GROUP BY form_id
				HAVING $having_op ";

		return $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_start,
				$tomorrow_start
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Sum the daily rows in the `since`/`until` window for a single analytics metric.
	 *
	 * UNIONs today's unprocessed snapshots as a synthetic daily row so the
	 * date-bounded SUM includes real-time data.
	 *
	 * @since 2.0.0
	 *
	 * @param string $column Sanitized analytics column name.
	 * @param string $op     Operator.
	 * @param int    $value  Threshold value.
	 * @param string $since  ISO date floor (optional).
	 * @param string $until  ISO date ceiling (optional).
	 *
	 * @return array|null Raw `form_id` column values, or null when the operator is unsupported.
	 */
	private function query_analytics_metric_window( string $column, string $op, int $value, string $since, string $until ): ?array {

		global $wpdb;

		$having = $this->get_operators()->to_sql( "SUM({$column})", $op, $value );

		if ( $having === null ) {
			return [];
		}

		$table      = $wpdb->prefix . 'wpforms_analytics_forms';
		$snap_table = AnalyticsDB::snapshots_table();

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();
		[ $from, $to ]                    = $this->analytics_window_bounds( $since, $until );

		$today_date = substr( $today_start, 0, 10 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id
				FROM (
					SELECT form_id, period_date, views, submissions
					  FROM $table
					 WHERE period_date != %s
					UNION ALL
					SELECT form_id,
					       %s AS period_date,
					       COUNT(DISTINCT session_id) AS views,
					       COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
					  FROM {$snap_table}
					 WHERE processed = 0
					   AND form_visible = 1
					   AND occurred_at >= %s
					   AND occurred_at < %s
					 GROUP BY form_id
				) t
				WHERE period_date >= %s AND period_date <= %s
				GROUP BY form_id
				HAVING {$having}";

		return $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_date,
				$today_start,
				$tomorrow_start,
				$from,
				$to
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Resolve the inclusive `[from, to]` date bounds for a windowed analytics query.
	 *
	 * Empty ends collapse to wide sentinel dates so the BETWEEN range stays open
	 * on that side.
	 *
	 * @since 2.0.0
	 *
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Tuple [ from, to ] as ISO date strings.
	 */
	private function analytics_window_bounds( string $since, string $until ): array {

		return [
			$since !== '' ? $since : '1970-01-01',
			$until !== '' ? $until : '9999-12-31',
		];
	}

	/**
	 * Find form IDs whose conversion rate (submissions / views × 100) satisfies the operator.
	 *
	 * Conversion is derived, so the SQL fetches submissions + views per form and
	 * PHP applies the threshold. Forms with zero views are treated as 0% (and
	 * skipped for `gt`/`gte` thresholds above 0 to avoid 0/0 surprises).
	 *
	 * @since 2.0.0
	 *
	 * @param string $op    Operator.
	 * @param int    $value Threshold value (percentage, 0-100).
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Form IDs.
	 */
	private function query_form_ids_by_analytics_conversion( string $op, int $value, string $since, string $until ): array {

		$rows = $since === '' && $until === ''
			? $this->query_analytics_conversion_lifetime()
			: $this->query_analytics_conversion_window( $since, $until );

		return $this->filter_rows_by_conversion( (array) $rows, $op, $value );
	}

	/**
	 * Fetch the lifetime sentinel views/submissions rows for conversion filtering.
	 *
	 * UNIONs today's unprocessed snapshots with the sentinel row and groups
	 * per form so the derived conversion rate includes real-time data.
	 *
	 * @since 2.0.0
	 *
	 * @return array Rows of `[ form_id, views, submissions ]`.
	 */
	private function query_analytics_conversion_lifetime(): array {

		global $wpdb;

		$table      = $wpdb->prefix . 'wpforms_analytics_forms';
		$snap_table = AnalyticsDB::snapshots_table();

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id, SUM(views) AS views, SUM(submissions) AS submissions
				FROM (
					SELECT form_id, views, submissions
					  FROM $table
					 WHERE period_date = %s
					UNION ALL
					SELECT form_id,
					       COUNT(DISTINCT session_id) AS views,
					       COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
					  FROM {$snap_table}
					 WHERE processed = 0
					   AND form_visible = 1
					   AND occurred_at >= %s
					   AND occurred_at < %s
					 GROUP BY form_id
				) t
				GROUP BY form_id";

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_start,
				$tomorrow_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Sum the daily views/submissions rows in the window for conversion filtering.
	 *
	 * UNIONs today's unprocessed snapshots as a synthetic daily row so the
	 * date-bounded SUM includes real-time data.
	 *
	 * @since 2.0.0
	 *
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Rows of `[ form_id, views, submissions ]`.
	 */
	private function query_analytics_conversion_window( string $since, string $until ): array {

		global $wpdb;

		$table      = $wpdb->prefix . 'wpforms_analytics_forms';
		$snap_table = AnalyticsDB::snapshots_table();

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();
		[ $from, $to ]                    = $this->analytics_window_bounds( $since, $until );

		$today_date = substr( $today_start, 0, 10 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id, SUM(views) AS views, SUM(submissions) AS submissions
				FROM (
					SELECT form_id, period_date, views, submissions
					FROM $table
					WHERE period_date != %s
					UNION ALL
					SELECT form_id,
						%s AS period_date,
						COUNT(DISTINCT session_id) AS views,
						COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
					FROM $snap_table
					WHERE processed = 0
						AND form_visible = 1
						AND occurred_at >= %s
						AND occurred_at < %s
					GROUP BY form_id
				) t
				WHERE period_date >= %s AND period_date <= %s
				GROUP BY form_id";

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_date,
				$today_start,
				$tomorrow_start,
				$from,
				$to
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Filter analytics rows by their derived conversion rate against the operator.
	 *
	 * Conversion is submissions / views × 100; forms with zero views are treated
	 * as 0%.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $rows  Rows of `[ form_id, views, submissions ]`.
	 * @param string $op    Operator.
	 * @param int    $value Threshold value (percentage, 0-100).
	 *
	 * @return array Matching form IDs.
	 */
	private function filter_rows_by_conversion( array $rows, string $op, int $value ): array {

		$matched = [];

		foreach ( $rows as $row ) {
			$views       = (int) ( $row['views'] ?? 0 );
			$submissions = (int) ( $row['submissions'] ?? 0 );
			$conversion  = $views > 0 ? round( $submissions / $views * 100, 0 ) : 0.0;

			if ( $this->get_operators()->evaluate( $conversion, $op, $value ) ) {
				$matched[] = (int) $row['form_id'];
			}
		}

		return $matched;
	}

	/**
	 * Find form IDs whose interaction count satisfies the operator.
	 *
	 * Interactions are a field-level metric (focus + click + input), so unlike
	 * views / submissions they cannot be served from the forms aggregate table.
	 * They are summed from `wp_wpforms_analytics_fields` instead, matching the
	 * `interactions` figure shown in the Forms Overview column and the analytics
	 * rankings.
	 *
	 * Summing across a composite field's per-subfield rows is correct here:
	 * focus/click/input are genuine additive per-subfield counters (unlike
	 * `views`/`abandonments`, which are replicated per subfield and are not summed
	 * by this query). See the additivity note in the per-subfield tracking
	 * knowledge base.
	 *
	 * @since 2.0.0
	 *
	 * @param string $op    Operator.
	 * @param int    $value Threshold value.
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Form IDs.
	 */
	private function query_form_ids_by_analytics_interactions( string $op, int $value, string $since, string $until ): array {

		$ids = $since === '' && $until === ''
			? $this->query_analytics_interactions_lifetime( $op, $value )
			: $this->query_analytics_interactions_window( $op, $value, $since, $until );

		return array_map( 'intval', $ids );
	}

	/**
	 * Query lifetime interactions per form and keep those matching the operator.
	 *
	 * Lifetime totals live in the field sentinel rows; today's unprocessed
	 * snapshot fields are folded in with the same MAX(id)-per-session dedup the
	 * nightly aggregation applies, so the cumulative per-session counters are
	 * summed once rather than once per snapshot.
	 *
	 * @since 2.0.0
	 *
	 * @param string $op    Operator.
	 * @param int    $value Threshold value.
	 *
	 * @return array Form IDs.
	 */
	private function query_analytics_interactions_lifetime( string $op, int $value ): array {

		global $wpdb;

		$having = $this->get_operators()->to_sql( 'SUM(interactions)', $op, $value );

		if ( $having === null ) {
			return [];
		}

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();

		$fields_table = ProAnalyticsDB::fields_table();
		$sf_table     = ProAnalyticsDB::snapshot_fields_table();
		$snap_table   = AnalyticsDB::snapshots_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id
				FROM (
					SELECT form_id, focus_count + click_count + input_count AS interactions
					  FROM $fields_table
					 WHERE period_date = %s
					UNION ALL
					SELECT s.form_id, sf.focus_count + sf.click_count + sf.input_count AS interactions
					  FROM $sf_table sf
					 INNER JOIN $snap_table s ON s.id = sf.snapshot_id
					 WHERE s.id IN (
						 SELECT MAX(id)
						   FROM $snap_table
						  WHERE processed     = 0
							AND form_visible  = 1
							AND occurred_at  >= %s
							AND occurred_at   < %s
						  GROUP BY session_id, form_id
					 )
				) t
				GROUP BY form_id
				HAVING $having";

		return (array) $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_start,
				$tomorrow_start
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Sum interactions per form within the window and keep those matching the operator.
	 *
	 * Daily field rows (sentinel excluded) plus today's unprocessed snapshot
	 * fields — deduped MAX(id)-per-session — are bounded by the `[from, to]`
	 * window before the per-form SUM is compared against the threshold.
	 *
	 * @since 2.0.0
	 *
	 * @param string $op    Operator.
	 * @param int    $value Threshold value.
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Form IDs.
	 */
	private function query_analytics_interactions_window( string $op, int $value, string $since, string $until ): array {

		global $wpdb;

		$having = $this->get_operators()->to_sql( 'SUM(interactions)', $op, $value );

		if ( $having === null ) {
			return [];
		}

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty result instead of fataling on a null service.
		if ( ! $db ) {
			return [];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();
		[ $from, $to ]                    = $this->analytics_window_bounds( $since, $until );

		$today_date = substr( $today_start, 0, 10 );

		$fields_table = ProAnalyticsDB::fields_table();
		$sf_table     = ProAnalyticsDB::snapshot_fields_table();
		$snap_table   = AnalyticsDB::snapshots_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id
				FROM (
					SELECT form_id, period_date, focus_count + click_count + input_count AS interactions
					  FROM $fields_table
					 WHERE period_date != %s
					UNION ALL
					SELECT s.form_id, %s AS period_date,
					       sf.focus_count + sf.click_count + sf.input_count AS interactions
					  FROM $sf_table sf
					 INNER JOIN $snap_table s ON s.id = sf.snapshot_id
					 WHERE s.id IN (
						 SELECT MAX(id)
						   FROM $snap_table
						  WHERE processed     = 0
							AND form_visible  = 1
							AND occurred_at  >= %s
							AND occurred_at   < %s
						  GROUP BY session_id, form_id
					 )
				) t
				WHERE period_date >= %s AND period_date <= %s
				GROUP BY form_id
				HAVING $having";

		return (array) $wpdb->get_col(
			$wpdb->prepare(
				$sql,
				AnalyticsDB::LIFETIME_SENTINEL_DATE,
				$today_date,
				$today_start,
				$tomorrow_start,
				$from,
				$to
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Lazy-build the shared filter-operator helper.
	 *
	 * @since 2.0.0
	 *
	 * @return Operators
	 */
	private function get_operators(): Operators {

		if ( $this->operators !== null ) {
			return $this->operators;
		}

		$this->operators = new Operators();

		return $this->operators;
	}

	/**
	 * Lazy-build the entries-table query helper.
	 *
	 * @since 2.0.0
	 *
	 * @return EntriesQuery
	 */
	private function get_entries_query(): EntriesQuery {

		if ( $this->entries_query !== null ) {
			return $this->entries_query;
		}

		$this->entries_query = new EntriesQuery();

		return $this->entries_query;
	}
}
