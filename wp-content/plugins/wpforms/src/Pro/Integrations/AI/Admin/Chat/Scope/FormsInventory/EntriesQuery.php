<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\FormsInventory;

use WPForms\Integrations\AI\Admin\Chat\Filter\Operators;

/**
 * Entries-table query helper for the Pro forms-inventory scope.
 *
 * Owns every `wp_wpforms_entries` query the scope needs:
 *   - filter resolution (`entries_count` operator → matching form IDs),
 *   - server-side ranking (`ORDER BY COUNT(*)` for "top N forms by entries"),
 *   - the forms-with-entries total used to flag a truncated ranking.
 *
 * Extracted from `FormSearcher` so that class stays within the SonarCloud
 * per-class method budget; `FormSearcher` delegates to a lazily-built instance.
 *
 * @since 2.0.0
 */
class EntriesQuery {

	/**
	 * Lazy-built filter-operator helper.
	 *
	 * @since 2.0.0
	 *
	 * @var Operators|null
	 */
	private $operators;

	/**
	 * Find form IDs whose lifetime (or date-bounded) entries count satisfies the operator.
	 *
	 * The HAVING-based query only sees forms that have at least one entry row, so
	 * operators that match a zero count (e.g. `eq 0`, `lt 5`, `neq 3`) would
	 * silently miss forms with no entries at all. When `0 $op $value` evaluates
	 * to true, the zero-entry form IDs are fetched separately and merged in.
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
	public function query_form_ids_by_entries_count( string $op, int $value, string $since, string $until ): array {

		$zero_matches = $this->get_operators()->evaluate( 0.0, $op, $value );

		// For `eq 0` the HAVING query can never return results — short-circuit.
		if ( $op === 'eq' && $value === 0 ) {
			return $this->query_zero_entry_form_ids( $since, $until );
		}

		$ids = $this->query_form_ids_having_entries( $op, $value, $since, $until );

		if ( $zero_matches ) {
			$zero_ids = $this->query_zero_entry_form_ids( $since, $until );

			$ids = array_values( array_unique( array_merge( $ids, $zero_ids ) ) );
		}

		return $ids;
	}

	/**
	 * Query form IDs from the entries table using GROUP BY / HAVING.
	 *
	 * Only returns forms that have at least one entry row — forms with zero
	 * entries are invisible to this query by design.
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
	private function query_form_ids_having_entries( string $op, int $value, string $since, string $until ): array {

		global $wpdb;

		$table  = $wpdb->prefix . 'wpforms_entries';
		$having = $this->get_operators()->to_sql( 'COUNT(*)', $op, $value );

		if ( $having === null ) {
			return [];
		}

		[ $range_where, $range_args ] = $this->build_range_clause( $since, $until );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id FROM {$table} WHERE 1=1 $range_where GROUP BY form_id HAVING $having";

		$ids = $wpdb->get_col(
			$range_args === []
				? $sql
				: $wpdb->prepare( $sql, $range_args )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $ids ) ? array_map( 'intval', $ids ) : [];
	}

	/**
	 * Find form IDs that have zero entries in the given date range.
	 *
	 * Queries all `wpforms` posts whose ID does not appear in the entries
	 * table (optionally scoped to the `since`/`until` window). The result is
	 * intersected downstream with other WP_Query constraints (status, etc.)
	 * so we deliberately do not filter by post_status here.
	 *
	 * @since 2.0.0
	 *
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Form IDs with zero entries.
	 */
	private function query_zero_entry_form_ids( string $since, string $until ): array {

		global $wpdb;

		$entries_table = $wpdb->prefix . 'wpforms_entries';

		[ $range_where, $range_args ] = $this->build_range_clause( $since, $until );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$subquery = "SELECT DISTINCT form_id FROM $entries_table WHERE 1=1 $range_where";

		$sql = "SELECT ID FROM $wpdb->posts WHERE post_type = 'wpforms' AND ID NOT IN ($subquery)";

		$ids = $wpdb->get_col(
			$range_args === []
				? $sql
				: $wpdb->prepare( $sql, $range_args )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $ids ) ? array_map( 'intval', $ids ) : [];
	}

	/**
	 * Rank form IDs by their entry count, descending or ascending.
	 *
	 * Answers "top N forms by entries" with a true server-side
	 * `ORDER BY COUNT(*)` — unlike the recency-capped snapshot, an old form with
	 * the most entries still ranks first. Forms with zero entries are
	 * intentionally excluded: a "top by entries" list of zero-entry forms is
	 * meaningless, and the GROUP BY only sees forms that have at least one row.
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

		global $wpdb;

		$table = $wpdb->prefix . 'wpforms_entries';

		// Whitelist the sort direction so it is never bound from raw input — the
		// rest of the query binds every value via prepare().
		$direction = $order === 'asc' ? 'ASC' : 'DESC';

		[ $range_where, $args ] = $this->build_range_clause( $since, $until );

		$restrict_clause = $this->build_restrict_clause( $restrict_ids, $args );

		$args[] = max( 1, $limit );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT form_id, COUNT(*) AS c
				FROM {$table}
				WHERE 1=1 $range_where $restrict_clause
				GROUP BY form_id
				ORDER BY c $direction, form_id ASC
				LIMIT %d";

		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $args ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $ids ) ? array_map( 'intval', $ids ) : [];
	}

	/**
	 * Count the forms that have at least one entry (within window / restrict set).
	 *
	 * Drives the ranked response's `total` and the `truncated` flag: when the
	 * total exceeds the returned slice, the ranking was capped.
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

		global $wpdb;

		$table = $wpdb->prefix . 'wpforms_entries';

		[ $range_where, $args ] = $this->build_range_clause( $since, $until );

		$restrict_clause = $this->build_restrict_clause( $restrict_ids, $args );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "SELECT COUNT(*) FROM (
					SELECT form_id
					FROM {$table}
					WHERE 1=1 $range_where $restrict_clause
					GROUP BY form_id
				) t";

		$count = $args === []
			? $wpdb->get_var( $sql )
			: $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $count;
	}

	/**
	 * Build the optional `date >= / <=` range clause and seed the prepare args.
	 *
	 * @since 2.0.0
	 *
	 * @param string $since ISO date floor (optional).
	 * @param string $until ISO date ceiling (optional).
	 *
	 * @return array Tuple of [ where-fragment, prepare-args ].
	 */
	private function build_range_clause( string $since, string $until ): array {

		$range_where = '';
		$args        = [];

		if ( $since !== '' ) {
			$range_where .= ' AND date >= %s';
			$args[]       = $since . ' 00:00:00';
		}

		if ( $until !== '' ) {
			$range_where .= ' AND date <= %s';
			$args[]       = $until . ' 23:59:59';
		}

		return [ $range_where, $args ];
	}

	/**
	 * Build the optional `form_id IN (…)` clause and append its ints to the args.
	 *
	 * The placeholder list is generated dynamically only when `$restrict_ids`
	 * is non-empty; every ID is appended to the prepare-args so the values are
	 * bound, never interpolated.
	 *
	 * @since 2.0.0
	 *
	 * @param array $restrict_ids Form IDs to constrain to (may be empty).
	 * @param array $args         Prepare-args accumulator (modified in place).
	 *
	 * @return string WHERE fragment (empty when no restriction applies).
	 */
	private function build_restrict_clause( array $restrict_ids, array &$args ): string {

		$ids = array_map( 'intval', $restrict_ids );

		if ( $ids === [] ) {
			return '';
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		foreach ( $ids as $id ) {
			$args[] = $id;
		}

		return " AND form_id IN ( $placeholders )";
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
}
