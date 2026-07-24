<?php

namespace WPForms\Pro\Db\Analytics;

use DateTimeImmutable;
use RuntimeException;
use WPForms\Analytics\Aggregation;
use WPForms\Db\Analytics\DB as BaseDB;

/**
 * Pro Analytics DB operations.
 *
 * Extends the Lite DB with field-level writes and all Pro read queries
 * (form stats, field stats, today's unprocessed rows, navigation helpers).
 *
 * @since 2.0.0
 */
class DB extends BaseDB {

	/**
	 * Get the full wp_wpforms_analytics_snapshot_fields table name.
	 *
	 * @since 2.0.0
	 *
	 * @return string Prefixed table name.
	 */
	public static function snapshot_fields_table(): string {

		global $wpdb;

		return $wpdb->prefix . 'wpforms_analytics_snapshot_fields';
	}

	/**
	 * Get the full wp_wpforms_analytics_fields table name.
	 *
	 * @since 2.0.0
	 *
	 * @return string Prefixed table name.
	 */
	public static function fields_table(): string {

		global $wpdb;

		return $wpdb->prefix . 'wpforms_analytics_fields';
	}

	/**
	 * Upsert a daily field-level aggregate row.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id      Form ID.
	 * @param int    $field_id     Field ID.
	 * @param string $subfield_key Subfield discriminator (e.g. 'first', 'last', '').
	 * @param string $period_date  Aggregation date (Y-m-d).
	 * @param array  $deltas       Keys: views, focus_count, click_count, input_count,
	 *                             abandonments, errors, total_duration_ms, duration_count.
	 *                             Missing keys default to 0.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the upsert fails, so the surrounding aggregation transaction rolls back.
	 */
	public function upsert_field_aggregate( int $form_id, int $field_id, string $subfield_key, string $period_date, array $deltas ): void {

		global $wpdb;

		$table = self::fields_table();

		$values = [
			'views'             => self::int_from_row( $deltas, 'views' ),
			'focus_count'       => self::int_from_row( $deltas, 'focus_count' ),
			'click_count'       => self::int_from_row( $deltas, 'click_count' ),
			'input_count'       => self::int_from_row( $deltas, 'input_count' ),
			'abandonments'      => self::int_from_row( $deltas, 'abandonments' ),
			'errors'            => self::int_from_row( $deltas, 'errors' ),
			'total_duration_ms' => self::int_from_row( $deltas, 'total_duration_ms' ),
			'duration_count'    => self::int_from_row( $deltas, 'duration_count' ),
		];

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (
					form_id, field_id, subfield_key, period_date,
					views, focus_count, click_count, input_count,
					abandonments, errors, total_duration_ms, duration_count
				)
				VALUES ( %d, %d, %s, %s, %d, %d, %d, %d, %d, %d, %d, %d )
				ON DUPLICATE KEY UPDATE
				    views             = views             + VALUES(views),
				    focus_count       = focus_count       + VALUES(focus_count),
				    click_count       = click_count       + VALUES(click_count),
				    input_count       = input_count       + VALUES(input_count),
				    abandonments      = abandonments      + VALUES(abandonments),
				    errors            = errors            + VALUES(errors),
				    total_duration_ms = total_duration_ms + VALUES(total_duration_ms),
				    duration_count    = duration_count    + VALUES(duration_count)",
				$form_id,
				$field_id,
				$subfield_key,
				$period_date,
				$values['views'],
				$values['focus_count'],
				$values['click_count'],
				$values['input_count'],
				$values['abandonments'],
				$values['errors'],
				$values['total_duration_ms'],
				$values['duration_count']
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $result === false ) {
			wpforms_log(
				'Analytics DB: upsert_field_aggregate failed',
				[
					'table'        => $table,
					'form_id'      => $form_id,
					'field_id'     => $field_id,
					'subfield_key' => $subfield_key,
					'period_date'  => $period_date,
					'last_error'   => $wpdb->last_error,
				],
				[
					'type'  => [ 'error' ],
					'force' => true,
				]
			);

			// Abort the aggregation transaction: committing the marked-processed
			// snapshots while this additive delta was dropped would lose the delta
			// permanently. Throwing routes through aggregate_in_transaction()'s
			// catch, which rolls back so the snapshots are retried next run.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message routed to the error log, not HTML output.
			throw new RuntimeException( 'Analytics aggregation: upsert_field_aggregate failed. ' . $wpdb->last_error );
		}
	}

	/**
	 * Upsert the lifetime sentinel row for a field.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id      Form ID.
	 * @param int    $field_id     Field ID.
	 * @param string $subfield_key Subfield discriminator (e.g. 'first', 'last', '').
	 * @param array  $deltas       Delta values (see upsert_field_aggregate).
	 *
	 * @return void
	 */
	public function upsert_field_sentinel( int $form_id, int $field_id, string $subfield_key, array $deltas ): void {

		$this->upsert_field_aggregate( $form_id, $field_id, $subfield_key, self::LIFETIME_SENTINEL_DATE, $deltas );
	}

	/**
	 * Bulk-insert field-level rows for a snapshot in a single multi-row INSERT.
	 *
	 * No-op on empty $fields. One prepared INSERT replaces N per-field queries —
	 * a 30-field form previously fired 30 separate inserts on every beacon. A
	 * failed query drops the whole batch (errors are not surfaced); the columns
	 * are sanitized scalars so failure means a structural problem (e.g. missing
	 * table), not a single bad row.
	 *
	 * @since 2.0.0
	 *
	 * @param int   $snapshot_id Parent snapshot row ID.
	 * @param array $fields      Field rows. Each row keys: field_id, subfield_key,
	 *                           was_displayed, focus_count, click_count, input_count,
	 *                           errors, duration_ms.
	 *
	 * @return void
	 */
	public function save_fields( int $snapshot_id, array $fields ): void {

		if ( empty( $fields ) ) {
			return;
		}

		global $wpdb;

		$table     = self::snapshot_fields_table();
		$row_specs = [];
		$values    = [];

		foreach ( $fields as $field ) {

			// duration_ms is the only nullable column, so its placeholder is
			// either %d or a literal NULL depending on whether the row carries it.
			$has_duration = isset( $field['duration_ms'] );
			$row_specs[]  = $has_duration
				? '( %d, %d, %s, %d, %d, %d, %d, %d, %d )'
				: '( %d, %d, %s, %d, %d, %d, %d, %d, NULL )';

			$values[] = $snapshot_id;
			$values[] = self::int_from_row( $field, 'field_id' );
			$values[] = self::string_from_row( $field, 'subfield_key' );
			$values[] = self::int_from_row( $field, 'was_displayed' );
			$values[] = self::int_from_row( $field, 'focus_count' );
			$values[] = self::int_from_row( $field, 'click_count' );
			$values[] = self::int_from_row( $field, 'input_count' );
			$values[] = self::int_from_row( $field, 'errors' );

			if ( $has_duration ) {
				$values[] = (int) $field['duration_ms'];
			}
		}

		$sql = "INSERT INTO $table
			( snapshot_id, field_id, subfield_key, was_displayed, focus_count, click_count, input_count, errors, duration_ms )
			VALUES " . implode( ', ', $row_specs );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query( $wpdb->prepare( $sql, $values ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * Get aggregated form-level stats for a date range.
	 *
	 * Pulls only the persisted aggregates: `views` and `submissions` from
	 * `analytics_forms`, plus the field-derived counters
	 * (`interactions`, `abandonments`, `errors`) from `analytics_fields`. The
	 * lifetime sentinel is naturally excluded by the `BETWEEN` filter against
	 * realistic dates.
	 *
	 * Policy that used to live here — today's-snapshot merge and the derived
	 * `conversion_rate` — now lives in `Stats::get_form_stats()` so the DB
	 * layer stays "raw SUM only" and the same source of truth applies to
	 * every consumer (Analytics page UI, AI chat scope, future REST exports).
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $from    Date range start (Y-m-d).
	 * @param string $to      Date range end (Y-m-d).
	 *
	 * @return array Keys: views, submissions,
	 *               interactions, abandonments, errors.
	 */
	public function get_form_stats( int $form_id, string $from, string $to ): array {

		global $wpdb;

		$table = self::forms_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE( SUM(views), 0 )       AS views,
					COALESCE( SUM(submissions), 0 ) AS submissions
				 FROM {$table}
				 WHERE form_id     = %d
				   AND period_date BETWEEN %s AND %s",
				$form_id,
				$from,
				$to
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$field_aggregates = $this->get_form_field_aggregates( $form_id, $from, $to );

		return [
			'views'        => (int) ( $row['views'] ?? 0 ),
			'submissions'  => (int) ( $row['submissions'] ?? 0 ),
			'interactions' => $field_aggregates['interactions'],
			'abandonments' => $field_aggregates['abandonments'],
			'errors'       => $field_aggregates['errors'],
			'field_views'  => $field_aggregates['field_views'],
		];
	}

	/**
	 * Sum the field-level aggregates of a single form across a date range.
	 *
	 * `interactions` mirrors the Pro overview convention — focus_count plus
	 * click_count plus input_count, totaled across every field of the form
	 * (see `get_interactions_for_forms()`). `abandonments` and `errors` are
	 * direct sums of the corresponding `analytics_fields` columns; both are
	 * per-field event counts (a single session can contribute to multiple
	 * fields).
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $from    Date range start (Y-m-d).
	 * @param string $to      Date range end (Y-m-d).
	 *
	 * @return array Keys: interactions, abandonments, errors — all int.
	 */
	private function get_form_field_aggregates( int $form_id, string $from, string $to ): array {

		global $wpdb;

		$table = self::fields_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE( SUM(focus_count + click_count + input_count), 0 ) AS interactions,
					COALESCE( SUM(abandonments), 0 )                            AS abandonments,
					COALESCE( SUM(errors), 0 )                                  AS errors,
					COALESCE( SUM(views), 0 )                                   AS field_views
				 FROM {$table}
				 WHERE form_id     = %d
				   AND period_date BETWEEN %s AND %s",
				$form_id,
				$from,
				$to
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// `field_views` (sum of per-field views) is the abandonment-rate denominator
		// — see Stats::get_stats() for why it is used instead of form views.
		return [
			'interactions' => (int) ( $row['interactions'] ?? 0 ),
			'abandonments' => (int) ( $row['abandonments'] ?? 0 ),
			'errors'       => (int) ( $row['errors'] ?? 0 ),
			'field_views'  => (int) ( $row['field_views'] ?? 0 ),
		];
	}

	/**
	 * Get aggregated form stats grouped by day, week (ISO, Monday start), or month.
	 *
	 * Conversion rate is recomputed in PHP for consistent rounding across
	 * drivers. The `period` column is emitted as an ISO `Y-m-d` date so the
	 * consumer can format it for locale-aware display:
	 *
	 *   - day:   that day's date.
	 *   - week:  the Monday of that ISO week.
	 *   - month: the first day of that month.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id     Form ID.
	 * @param string $from        Date range start (Y-m-d), inclusive.
	 * @param string $to          Date range end (Y-m-d), inclusive.
	 * @param string $granularity One of `day`, `week`, `month`. Unknown values
	 *                            fall back to `month`.
	 *
	 * @return array List of rows ordered by period asc. Each row keys:
	 *               period, views, submissions, interactions,
	 *               abandonments, errors, conversion_rate, abandonment_rate,
	 *               error_rate.
	 */
	public function get_form_stats_trend( int $form_id, string $from, string $to, string $granularity = 'month' ): array {

		$rows = $this->build_trend_query( $form_id, $from, $to, $granularity );

		// Merge today's unprocessed snapshots if range includes today.
		$today = (string) current_time( 'Y-m-d' );

		if ( $today >= $from && $today <= $to ) {
			$rows = $this->merge_today_into_trend( $rows, $form_id, $today, $granularity );
		}

		$shaped = [];

		foreach ( $rows as $row ) {
			$shaped[] = $this->normalise_trend_row( $row );
		}

		return $shaped;
	}

	/**
	 * Run the trend aggregation query and return its raw rows.
	 *
	 * The form-level columns (views / submissions) live on
	 * `analytics_forms`; the field-derived counters (interactions =
	 * focus + click + input, plus abandonments + errors) come from
	 * `analytics_fields` aggregated per period_date.
	 *
	 * The period spine is a `UNION` of distinct `period_date` values from BOTH
	 * tables (each filtered by form_id + range), aliased `f` so the
	 * scope-owned `f.period_date` fragments still resolve. `analytics_forms`
	 * (`af`) and the daily fields-aggregate subquery (`fa`) are LEFT JOINed onto
	 * that spine, so a `period_date` that exists in only one table is still
	 * bucketed by the outer GROUP BY. This keeps the trend consistent with
	 * `get_form_stats()`, which sums the two tables independently. The lifetime
	 * sentinel is excluded naturally by the `BETWEEN` against realistic dates.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id     Form ID.
	 * @param string $from        Date range start (Y-m-d), inclusive.
	 * @param string $to          Date range end (Y-m-d), inclusive.
	 * @param string $granularity One of `day`, `week`, `month`.
	 *
	 * @return array List of raw associative rows (possibly empty).
	 */
	private function build_trend_query( int $form_id, string $from, string $to, string $granularity ): array {

		global $wpdb;

		$forms_table  = self::forms_table();
		$fields_table = self::fields_table();

		[ $period_expr, $group_expr ] = $this->get_trend_sql_fragments( $granularity );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
						$period_expr                        AS period,
						COALESCE( SUM(af.views), 0 )        AS views,
						COALESCE( SUM(af.submissions), 0 )  AS submissions,
						COALESCE( SUM(fa.interactions), 0 ) AS interactions,
						COALESCE( SUM(fa.abandonments), 0 ) AS abandonments,
						COALESCE( SUM(fa.errors), 0 )       AS errors,
						COALESCE( SUM(fa.field_views), 0 )  AS field_views
					FROM (
						SELECT period_date
							FROM $forms_table
							WHERE form_id = %d
						        AND period_date BETWEEN %s AND %s
						UNION
						SELECT period_date
							FROM $fields_table
							WHERE form_id = %d
								AND period_date BETWEEN %s AND %s
					) f
					LEFT JOIN $forms_table af
						ON af.form_id = %d
						AND af.period_date = f.period_date
					LEFT JOIN (
						SELECT
							period_date,
							SUM( focus_count + click_count + input_count ) AS interactions,
							SUM( abandonments )                            AS abandonments,
							SUM( errors )                                  AS errors,
							SUM( views )                                   AS field_views
						FROM $fields_table
						WHERE form_id = %d
							AND period_date BETWEEN %s AND %s
						GROUP BY period_date
					) fa
						ON fa.period_date = f.period_date
					GROUP BY $group_expr
					ORDER BY $group_expr ",
				$form_id,
				$from,
				$to,
				$form_id,
				$from,
				$to,
				$form_id,
				$form_id,
				$from,
				$to
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Normalize a raw trend row into the typed, rate-enriched output shape.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row Raw associative row from the trend query.
	 *
	 * @return array Typed row with conversion / abandonment / error rates.
	 */
	private function normalise_trend_row( array $row ): array {

		$views        = self::int_from_row( $row, 'views' );
		$submissions  = self::int_from_row( $row, 'submissions' );
		$abandonments = self::int_from_row( $row, 'abandonments' );
		$errors       = self::int_from_row( $row, 'errors' );
		// Field impressions — abandonment-rate denominator (see Stats::get_stats()).
		$field_views  = self::int_from_row( $row, 'field_views' );

		return [
			'period'           => (string) ( $row['period'] ?? '' ),
			'views'            => $views,
			'submissions'      => $submissions,
			'interactions'     => self::int_from_row( $row, 'interactions' ),
			'abandonments'     => $abandonments,
			'errors'           => $errors,
			'conversion_rate'  => self::safe_pct( $submissions, $views ),
			'abandonment_rate' => self::safe_pct( $abandonments, $field_views ),
			'error_rate'       => self::safe_pct( $errors, $views ),
		];
	}

	/**
	 * Merge today's unprocessed snapshots into raw trend rows.
	 *
	 * Queries form-level and field-level snapshot data for today, computes the
	 * period bucket based on granularity, and either adds the counts to the
	 * matching existing row or appends a new one.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $rows        Raw trend rows from build_trend_query().
	 * @param int    $form_id     Form ID.
	 * @param string $today       Today Y-m-d.
	 * @param string $granularity One of day, week, month.
	 *
	 * @return array Updated rows.
	 */
	private function merge_today_into_trend( array $rows, int $form_id, string $today, string $granularity ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh -- Two SQL queries + bucket dispatch + row merge; splitting further would exceed the 20-method class cap.

		global $wpdb;

		$snap_table = self::snapshots_table();
		$sf_table   = self::snapshot_fields_table();

		[ $today_start, $tomorrow_start ] = $this->today_boundaries();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$form_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT session_id ) AS views,
				        COUNT( DISTINCT CASE WHEN trigger_type = 2 THEN session_id END ) AS submissions
				   FROM {$snap_table}
				  WHERE form_id = %d
				    AND processed = 0 AND form_visible = 1
				    AND occurred_at >= %s AND occurred_at < %s",
				$form_id,
				$today_start,
				$tomorrow_start
			),
			ARRAY_A
		);

		$today_views = self::int_from_row( $form_row, 'views' );

		if ( $today_views === 0 ) {
			return $rows;
		}

		$field_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE( SUM( sf.focus_count + sf.click_count + sf.input_count ), 0 ) AS interactions,
				        COALESCE( SUM( sf.errors ), 0 ) AS errors
				   FROM $sf_table sf
				  INNER JOIN $snap_table s ON s.id = sf.snapshot_id
				  WHERE s.form_id = %d
				    AND s.processed = 0 AND s.form_visible = 1
				    AND s.occurred_at >= %s AND s.occurred_at < %s",
				$form_id,
				$today_start,
				$tomorrow_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$deltas = [
			'views'        => $today_views,
			'submissions'  => self::int_from_row( $form_row, 'submissions' ),
			'interactions' => self::int_from_row( $field_row, 'interactions' ),
			'errors'       => self::int_from_row( $field_row, 'errors' ),
		];

		// Resolve today into the correct period bucket.
		$period = substr( $today, 0, 7 ) . '-01';

		if ( $granularity === 'day' ) {
			$period = $today;
		} elseif ( $granularity === 'week' ) {
			$period = ( new DateTimeImmutable( $today ) )->modify( 'monday this week' )->format( 'Y-m-d' );
		}

		// Find the matching period row and add today's counts.
		foreach ( $rows as &$row ) {
			$row_period = (string) ( $row['period'] ?? '' );

			if ( $row_period !== $period ) {
				continue;
			}

			// Abandonments are intentionally not merged for today: detect_abandonments()
			// runs only at the nightly rollover, so today's abandonment count is 0 until
			// then (mirrors Stats::merge_today_form_stats). abandonment_rate for a period
			// including today is a live approximation that self-corrects after that pass.
			$row['views']        = self::int_from_row( $row, 'views' ) + $deltas['views'];
			$row['submissions']  = self::int_from_row( $row, 'submissions' ) + $deltas['submissions'];
			$row['interactions'] = self::int_from_row( $row, 'interactions' ) + $deltas['interactions'];
			$row['errors']       = self::int_from_row( $row, 'errors' ) + $deltas['errors'];

			unset( $row );

			return $rows;
		}

		unset( $row );

		// No existing row for this period — append one. Abandonments and the
		// field-impression denominator stay 0: today's abandonments only
		// materialize at the nightly rollover (see the merge note above).
		$deltas['period']       = $period;
		$deltas['abandonments'] = 0;
		$deltas['field_views']  = 0;
		$rows[]                 = $deltas;

		return $rows;
	}

	/**
	 * Resolve the SELECT + GROUP BY SQL fragments for a trend granularity.
	 *
	 * All fragments are scope-owned constants — never interpolate caller-supplied
	 * values into them. Unknown granularity falls back to `month`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $granularity One of `day`, `week`, `month`.
	 *
	 * @return array Tuple [ period_select_expr, group_by_expr ].
	 */
	private function get_trend_sql_fragments( string $granularity ): array {

		if ( $granularity === 'day' ) {
			$expr = "DATE_FORMAT( f.period_date, '%%Y-%%m-%%d' )";

			return [ $expr, $expr ];
		}

		if ( $granularity === 'week' ) {
			// WEEKDAY() returns 0 for Monday … 6 for Sunday — subtract to align to the ISO Monday.
			$monday = 'DATE_SUB( f.period_date, INTERVAL WEEKDAY( f.period_date ) DAY )';
			$expr   = "DATE_FORMAT( $monday, '%%Y-%%m-%%d' )";

			return [ $expr, $expr ];
		}

		// Month default. SELECT and GROUP BY share the same DATE_FORMAT
		// expression so MySQL's `ONLY_FULL_GROUP_BY` strict-mode check
		// recognizes the column as functionally dependent on the group.
		$expr = "DATE_FORMAT( f.period_date, '%%Y-%%m-01' )";

		return [ $expr, $expr ];
	}

	/**
	 * Get aggregated field-level stats for a date range.
	 *
	 * Returns one row per `(field_id, subfield_key)` — composite fields surface
	 * one row per subfield, simple fields keep their single empty-key row. This
	 * grain is deliberate: it is the per-subfield breakdown the feature exists to
	 * provide, and grouping by subfield avoids the cross-subfield over-count of
	 * `views`/`abandonments` (both replicated per subfield, not additive — see
	 * the additivity note in the per-subfield-tracking knowledge base). Excludes
	 * the lifetime sentinel naturally -- `period_date BETWEEN %s AND %s` with
	 * realistic dates skips year 1000.
	 *
	 * @since 2.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $from    Date range start (Y-m-d).
	 * @param string $to      Date range end (Y-m-d).
	 *
	 * @return array List of rows, each keyed by field_id/subfield_key/views/focus_count/...
	 */
	public function get_field_stats( int $form_id, string $from, string $to ): array {

		global $wpdb;

		$table = self::fields_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					field_id,
					subfield_key,
					SUM(views)             AS views,
					SUM(focus_count)       AS focus_count,
					SUM(click_count)       AS click_count,
					SUM(input_count)       AS input_count,
					SUM(abandonments)      AS abandonments,
					SUM(errors)            AS errors,
					SUM(total_duration_ms) AS total_duration_ms,
					SUM(duration_count)    AS duration_count
				 FROM {$table}
				 WHERE form_id     = %d
				   AND period_date BETWEEN %s AND %s
				 GROUP BY field_id, subfield_key
				 ORDER BY field_id, subfield_key",
				$form_id,
				$from,
				$to
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $rows ?? [];
	}

	/**
	 * Get the earliest calendar date Form Analytics recorded data, across all
	 * forms. Excludes the lifetime sentinel row, so the result is the real first
	 * day of collection. It never moves backward or vanishes: only raw snapshots
	 * are purged on retention, the daily aggregate buckets are kept.
	 *
	 * @since 2.0.0
	 *
	 * @return string Earliest 'Y-m-d' date, or an empty string when no data has been collected yet.
	 */
	public function get_first_collected_date(): string {

		global $wpdb;

		$table = self::forms_table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$date = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(period_date)
				 FROM {$table}
				 WHERE period_date <> %s",
				self::LIFETIME_SENTINEL_DATE
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $date ? (string) $date : '';
	}

	/**
	 * Get today's unprocessed form-level snapshot counts (site timezone).
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array Keys: views, submissions.
	 */
	public function get_today_form_stats( int $form_id ): array {

		global $wpdb;

		$table = self::snapshots_table();

		[ $today_start, $tomorrow_start ] = $this->today_boundaries();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(DISTINCT session_id) AS views,
					COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
				 FROM {$table}
				 WHERE form_id      = %d
				   AND processed    = 0
				   AND form_visible = 1
				   AND occurred_at >= %s
				   AND occurred_at <  %s",
				$form_id,
				$today_start,
				$tomorrow_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return [
			'views'       => (int) ( $row['views'] ?? 0 ),
			'submissions' => (int) ( $row['submissions'] ?? 0 ),
		];
	}

	/**
	 * Get today's unprocessed field-level snapshot counts (site timezone).
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array List of rows keyed by field_id/subfield_key.
	 */
	public function get_today_field_stats( int $form_id ): array {

		global $wpdb;

		$snaps_table           = self::snapshots_table();
		$snapshot_fields_table = self::snapshot_fields_table();

		[ $today_start, $tomorrow_start ] = $this->today_boundaries();

		// Grouped by `(field_id, subfield_key)` so composite fields surface one row
		// per subfield, mirroring the persisted-aggregate grain in get_field_stats().
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sf.field_id,
						sf.subfield_key,
						SUM(sf.was_displayed) AS views,
						SUM(sf.focus_count)   AS focus_count,
						SUM(sf.click_count)   AS click_count,
						SUM(sf.input_count)   AS input_count,
						SUM(sf.errors)        AS errors,
						SUM(sf.duration_ms)   AS total_duration_ms,
						SUM(CASE WHEN sf.duration_ms IS NOT NULL AND sf.duration_ms > 0 THEN 1 ELSE 0 END) AS duration_count
					FROM $snapshot_fields_table sf
					INNER JOIN $snaps_table s ON s.id = sf.snapshot_id
					WHERE s.id IN (
						SELECT MAX(id) FROM {$snaps_table}
					    WHERE form_id        = %d
					        AND processed    = 0
						    AND form_visible = 1
						    AND occurred_at >= %s
						    AND occurred_at <  %s
					    GROUP BY session_id
					)
					GROUP BY sf.field_id, sf.subfield_key
					HAVING views > 0
						OR focus_count > 0
						OR click_count > 0
						OR input_count > 0
						OR errors > 0
						OR total_duration_ms > 0
					ORDER BY sf.field_id, sf.subfield_key",
				$form_id,
				$today_start,
				$tomorrow_start
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $rows ?? [];
	}

	/**
	 * Count today's unprocessed field-level abandonments (site timezone).
	 *
	 * Mirrors the nightly `Aggregation::detect_abandonments()` logic but scoped
	 * to today's window and a single form: sessions with engagement but no
	 * submission are counted as abandoned per field.
	 *
	 * @since 2.0.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array List of rows keyed by field_id/subfield_key/abandonments.
	 */
	public function get_today_field_abandonments( int $form_id ): array {

		global $wpdb;

		$snaps_table           = self::snapshots_table();
		$snapshot_fields_table = self::snapshot_fields_table();

		[ $today_start ] = $this->today_boundaries();

		// Mirror the nightly task's grace window: only count sessions whose
		// latest snapshot is older than ABANDONMENT_GRACE_SECONDS. A user who
		// opened the form less than an hour ago may still be actively typing.
		$grace_cutoff = ( new DateTimeImmutable( '-' . Aggregation::ABANDONMENT_GRACE_SECONDS . ' seconds', wp_timezone() ) )
			->format( 'Y-m-d H:i:s' );

		// All of today's sessions are within the grace window (e.g. early morning).
		if ( $grace_cutoff <= $today_start ) {
			return [];
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sf.field_id,
						sf.subfield_key,
						COUNT(DISTINCT s.session_id) AS abandonments
					FROM $snapshot_fields_table sf
					INNER JOIN $snaps_table s ON s.id = sf.snapshot_id
					WHERE s.id IN (
						SELECT MAX(id) FROM $snaps_table
						WHERE form_id        = %d
							AND processed    = 0
							AND form_visible = 1
							AND occurred_at >= %s
						GROUP BY session_id
						HAVING MAX(occurred_at) < %s
					)
					AND sf.was_displayed = 1
					AND ( sf.focus_count > 0 OR sf.click_count > 0 OR sf.input_count > 0 )
					AND NOT EXISTS (
						SELECT 1 FROM $snaps_table sub
						WHERE sub.session_id = s.session_id
							AND sub.form_id  = s.form_id
							AND sub.trigger_type = 2
					)
					GROUP BY sf.field_id, sf.subfield_key",
				$form_id,
				$today_start,
				$grace_cutoff
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $rows ?? [];
	}

	/**
	 * Get the top N forms by lifetime submissions for portfolio analytics.
	 *
	 * Combines the lifetime sentinel layer from analytics_forms with today's
	 * unprocessed snapshots via UNION ALL + GROUP BY. This ensures forms that
	 * only have today's data (no nightly aggregation yet) are included in the
	 * ranking. Used by the AI Chat "Analytics" scope to assemble round-1
	 * context on surfaces other than the dedicated Analytics admin page.
	 *
	 * @since 2.0.0
	 *
	 * @param int $limit Maximum number of rows to return.
	 *
	 * @return array List of rows keyed by form_id, with
	 *                                        views, submissions.
	 */
	public function get_top_forms( int $limit ): array {

		if ( $limit <= 0 ) {
			return [];
		}

		global $wpdb;

		$table      = self::forms_table();
		$snap_table = self::snapshots_table();

		[ $today_start, $tomorrow_start ] = $this->today_boundaries();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id,
				        SUM(views)           AS views,
				        SUM(submissions)     AS submissions
				 FROM (
				     SELECT form_id, views, submissions
				       FROM {$table}
				      WHERE period_date = %s
				     UNION ALL
				     SELECT form_id,
				            COUNT(DISTINCT session_id)  AS views,
				            COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
				       FROM {$snap_table}
				      WHERE processed = 0 AND form_visible = 1
				        AND occurred_at >= %s AND occurred_at < %s
				      GROUP BY form_id
				 ) combined
				 GROUP BY form_id
				 ORDER BY submissions DESC, views DESC, form_id
				 LIMIT %d",
				self::LIFETIME_SENTINEL_DATE,
				$today_start,
				$tomorrow_start,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $rows ) ) {
			return [];
		}

		$result = [];

		foreach ( $rows as $row ) {
			$result[] = [
				'form_id'     => (int) $row['form_id'],
				'views'       => (int) $row['views'],
				'submissions' => (int) $row['submissions'],
			];
		}

		return $result;
	}

	/**
	 * Get overview stats including interactions (Pro override).
	 *
	 * Calls the Lite base hybrid query, then layers interactions on top using
	 * the same sentinel + today pattern against the field tables.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids Form IDs.
	 *
	 * @return array Map of form_id => Lite stats + 'interactions'.
	 */
	public function get_overview_stats( array $form_ids ): array {

		$base = parent::get_overview_stats( $form_ids );

		if ( empty( $base ) ) {
			return $base;
		}

		foreach ( $base as $form_id => $row ) {
			$base[ $form_id ]['interactions'] = 0;
		}

		foreach ( $this->get_interactions_for_forms( $form_ids ) as $form_id => $interactions ) {
			if ( isset( $base[ $form_id ] ) ) {
				$base[ $form_id ]['interactions'] = $interactions;
			}
		}

		return $base;
	}

	/**
	 * Compute interactions (focus + click + input) for the given forms.
	 *
	 * Sentinel layer from analytics_fields + today's unprocessed rows from
	 * analytics_snapshot_fields joined back to snapshots for form_visible/processed gating.
	 *
	 * @since 2.0.0
	 *
	 * @param array $form_ids Form IDs.
	 *
	 * @return array Map of form_id => total interactions count.
	 */
	private function get_interactions_for_forms( array $form_ids ): array {

		global $wpdb;

		$form_ids = array_values( array_filter( array_map( 'absint', $form_ids ) ) );

		if ( empty( $form_ids ) ) {
			return [];
		}

		$placeholders          = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
		$fields_table          = self::fields_table();
		$snaps_table           = self::snapshots_table();
		$snapshot_fields_table = self::snapshot_fields_table();

		[ $today_start, $tomorrow_start ] = $this->today_boundaries();

		// Layer 1: field sentinel rows.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$sentinel_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id,
				        SUM(focus_count + click_count + input_count) AS interactions
				 FROM {$fields_table}
				 WHERE form_id IN ($placeholders)
				   AND period_date = %s
				 GROUP BY form_id",
				array_merge( $form_ids, [ self::LIFETIME_SENTINEL_DATE ] )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Layer 2: today's field-level interactions via snapshot_fields join.
		// Snapshots are cumulative per session by design; the nightly
		// Pro\Analytics\Aggregation::aggregate_fields()
		// query keeps MAX(id) per (session_id, form_id) inline and discards earlier
		// snapshots in the session. Today's unprocessed rows haven't run through that
		// dedup yet, so apply the same MAX(id)-per-session filter inline here to avoid
		// summing cumulative counters across multiple snapshots from a single session.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$today_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.form_id,
				        SUM(sf.focus_count + sf.click_count + sf.input_count) AS interactions
				 FROM $snapshot_fields_table sf
				 INNER JOIN $snaps_table s ON s.id = sf.snapshot_id
				 WHERE s.id IN (
				     SELECT MAX(id)
				     FROM $snaps_table
				     WHERE form_id IN ($placeholders)
				       AND processed    = 0
				       AND form_visible = 1
				       AND occurred_at >= %s
				       AND occurred_at <  %s
				     GROUP BY session_id, form_id
				 )
				 GROUP BY s.form_id",
				array_merge( $form_ids, [ $today_start, $tomorrow_start ] )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$by_form = [];

		foreach ( $form_ids as $id ) {
			$by_form[ $id ] = 0;
		}

		foreach ( $sentinel_rows as $row ) {
			$by_form[ (int) $row['form_id'] ] += (int) $row['interactions'];
		}

		foreach ( $today_rows as $row ) {
			$by_form[ (int) $row['form_id'] ] += (int) $row['interactions'];
		}

		return $by_form;
	}

	/**
	 * Read an integer column from a row array, defaulting absent keys to 0.
	 *
	 * Centralizes the `(int) ( $row[ $key ] ?? 0 )` idiom used when shaping
	 * delta and snapshot rows, keeping those builders linear. Accepts null so a
	 * failed `$wpdb->get_row()` — which returns null on a query error — degrades
	 * to 0 here instead of fataling on a non-nullable array type hint.
	 *
	 * @since 2.0.0
	 *
	 * @param array|null $row Row keyed by column name, or null on a failed query.
	 * @param string     $key Column key to read.
	 *
	 * @return int
	 */
	public static function int_from_row( ?array $row, string $key ): int {

		if ( $row === null ) {
			return 0;
		}

		return (int) ( $row[ $key ] ?? 0 );
	}

	/**
	 * Read a string column from a row array, defaulting absent keys to ''.
	 *
	 * @since 2.0.0
	 *
	 * @param array|null $row Row keyed by column name, or null on a failed query.
	 * @param string     $key Column key to read.
	 *
	 * @return string
	 */
	public static function string_from_row( ?array $row, string $key ): string {

		if ( $row === null ) {
			return '';
		}

		return (string) ( $row[ $key ] ?? '' );
	}

	/**
	 * Safe percentage: numerator / denominator * 100, rounded to 1 decimal.
	 *
	 * Returns 0.0 when the denominator is non-positive (avoids div-by-zero).
	 *
	 * @since 2.0.0
	 *
	 * @param int $numerator   Part value.
	 * @param int $denominator Total value.
	 *
	 * @return float
	 */
	public static function safe_pct( int $numerator, int $denominator ): float {

		return $denominator > 0 ? round( $numerator / $denominator * 100, 1 ) : 0.0;
	}
}
