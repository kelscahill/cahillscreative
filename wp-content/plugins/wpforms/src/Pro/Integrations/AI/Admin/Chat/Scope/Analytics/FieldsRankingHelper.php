<?php
/**
 * Suppress unrelated inspections.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics;

use WPForms\Db\Analytics\DB as LiteDB;
use WPForms\Forms\Fields\Registry;
use WPForms\Integrations\AI\Admin\Chat\Filter\FieldType;
use WPForms\Integrations\AI\Admin\Chat\Filter\FilterCompiler;
use WPForms\Integrations\AI\Admin\Chat\Filter\Translators\WpdbTranslationContext;
use WPForms\Integrations\AI\Admin\Chat\Filter\Translators\WpdbTranslators;
use WPForms\Pro\Analytics\SubfieldLabels;
use WPForms\Pro\Db\Analytics\DB as AnalyticsDB;

/**
 * Fields-ranking query, enrichment, type rollup, and field-type filtering.
 *
 * Extracted from Analytics to comply with the 20-method-per-class threshold.
 *
 * @since 2.0.0
 */
class FieldsRankingHelper {

	/**
	 * Per-call cap for the `fields_ranking` data source, mode `field`.
	 *
	 * @since 2.0.0
	 */
	public const FIELDS_RANKING_MAX_LIMIT_PER_FIELD = 100;

	/**
	 * Per-call cap for the `fields_ranking` data source, mode `type`.
	 *
	 * @since 2.0.0
	 */
	private const FIELDS_RANKING_MAX_LIMIT_PER_TYPE = 50;

	/**
	 * Default row count when the model omits `limit`.
	 *
	 * @since 2.0.0
	 */
	private const FIELDS_RANKING_DEFAULT_LIMIT = 10;

	/**
	 * Max filters accepted per `fields_ranking` call.
	 *
	 * @since 2.0.0
	 */
	public const FIELDS_RANKING_MAX_FILTERS = 10;

	/**
	 * `group_by` value selecting per-(form_id, field_id) mode.
	 *
	 * @since 2.0.0
	 */
	public const FIELDS_RANKING_GROUP_BY_FIELD = 'field';

	/**
	 * `group_by` value selecting per-field-type rollup mode.
	 *
	 * @since 2.0.0
	 */
	public const FIELDS_RANKING_GROUP_BY_TYPE = 'type';

	/**
	 * Allow-list of LLM-facing fields for the fields_ranking FieldSpec.
	 *
	 * Maps field slug to [ FieldType::*, ops, supports_window ].
	 *
	 * @since 2.0.0
	 */
	public const FIELDS_RANKING_FIELDS = [
		'form_id'          => [ FieldType::INT, [ 'eq', 'neq', 'in' ], false ],
		'field_id'         => [ FieldType::INT, [ 'eq', 'neq', 'in' ], false ],
		'field_type'       => [ FieldType::KEY, [ 'eq', 'in' ], false ],
		'period_date'      => [ FieldType::DATE, [ 'eq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'views'            => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'focus_count'      => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'input_count'      => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'abandonments'     => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'errors'           => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'completion_rate'  => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'abandonment_rate' => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'error_rate'       => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'avg_duration_ms'  => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
	];

	/**
	 * Allow-list of sort fields for fields_ranking, mode `field`.
	 *
	 * @since 2.0.0
	 */
	private const FIELDS_RANKING_SORT_FIELDS_PER_FIELD = [
		'views',
		'focus_count',
		'input_count',
		'abandonments',
		'errors',
		'completion_rate',
		'abandonment_rate',
		'error_rate',
		'avg_duration_ms',
	];

	/**
	 * Allow-list of sort fields for fields_ranking, mode `type`.
	 *
	 * Includes `field_count` and `form_count` which only exist in the rollup.
	 *
	 * @since 2.0.0
	 */
	private const FIELDS_RANKING_SORT_FIELDS_PER_TYPE = [
		'views',
		'focus_count',
		'input_count',
		'abandonments',
		'errors',
		'completion_rate',
		'abandonment_rate',
		'error_rate',
		'avg_duration_ms',
		'field_count',
		'form_count',
	];

	/**
	 * Ranking helper for shared filter dispatch and SQL expression infra.
	 *
	 * @since 2.0.0
	 *
	 * @var RankingHelper
	 */
	private $ranking;

	/**
	 * Lazy-built filter compiler for fields_ranking.
	 *
	 * @since 2.0.0
	 *
	 * @var FilterCompiler|null
	 */
	private $fields_compiler;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param RankingHelper $ranking Ranking helper for shared dispatch/expression infra.
	 */
	public function __construct( RankingHelper $ranking ) {

		$this->ranking = $ranking;
	}

	/**
	 * Assemble + execute the fields_ranking SQL (per-(form_id, field_id) grain).
	 *
	 * In `field` mode the SQL applies `LIMIT $limit + 1` so the runner can detect
	 * truncation by fence. In `type` mode the caller passes `$apply_limit = false`
	 * to bypass the SQL LIMIT — the rollup needs every per-field row to compute
	 * correct totals, and truncation is recomputed in PHP after the rollup.
	 *
	 * @since 2.0.0
	 *
	 * @param WpdbTranslationContext $ctx         Compiled filter context.
	 * @param string                 $sort_field  Allow-listed sort field.
	 * @param string                 $sort_dir    The `asc` or `desc` direction.
	 * @param int                    $limit       Requested limit (already clamped).
	 * @param bool                   $apply_limit Whether to apply the SQL LIMIT clause.
	 *
	 * @return array{0: array, 1: bool}
	 */
	public function run_fields_ranking_query( WpdbTranslationContext $ctx, string $sort_field, string $sort_dir, int $limit, bool $apply_limit = true ): array {

		global $wpdb;

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty ranking instead of fataling on a null service.
		if ( ! $db ) {
			return [ [], false ];
		}

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();

		$today_date = substr( $today_start, 0, 10 );

		$snap_table = LiteDB::snapshots_table();
		$sf_table   = AnalyticsDB::snapshot_fields_table();

		$sql  = $this->build_fields_ranking_sql( $ctx, $sort_field, $sort_dir, $apply_limit, $snap_table, $sf_table );
		$bind = array_merge(
			[ LiteDB::LIFETIME_SENTINEL_DATE ],          // Inner: exclude sentinel.
			[ $today_date, $today_start, $tomorrow_start ],   // UNION today snapshots.
			[ $today_start, $tomorrow_start ],                // Dedup subquery: latest snapshot per session.
			$ctx->where_params,                               // Outer WHERE user filters.
			$ctx->having_params,                              // HAVING.
			$apply_limit ? [ $limit + 1 ] : []                // LIMIT.
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$raw_rows = $wpdb->get_results( $wpdb->prepare( $sql, $bind ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$raw_rows = is_array( $raw_rows ) ? $raw_rows : [];

		if ( ! $apply_limit ) {
			return [ $raw_rows, false ];
		}

		$truncated = count( $raw_rows ) > $limit;
		$raw_rows  = array_slice( $raw_rows, 0, $limit );

		return [ $raw_rows, $truncated ];
	}

	/**
	 * Enrich raw analytics_fields rows with label/type and computed rate metrics.
	 *
	 * Derived rates are recomputed in PHP — the SQL projection carries the SUM
	 * inputs but not the ratios (HAVING uses the derived expressions, but SELECT doesn't).
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_rows Raw rows from the SQL query.
	 * @param array $metadata Portfolio field metadata map.
	 *
	 * @return array
	 */
	public function enrich_field_rows( array $raw_rows, array $metadata ): array {

		$rows     = [];
		$defaults = [
			'form_id'      => 0,
			'field_id'     => 0,
			'subfield_key' => '',
			'views'        => 0,
			'focus_count'  => 0,
			'input_count'  => 0,
			'abandonments' => 0,
			'errors'       => 0,
		];

		foreach ( $raw_rows as $row ) {
			$row         += $defaults;
			$form_id      = (int) $row['form_id'];
			$field_id     = (int) $row['field_id'];
			$subfield_key = (string) ( $row['subfield_key'] ?? '' );
			$meta         = $metadata[ $form_id ][ $field_id ] ?? null;
			$label        = SubfieldLabels::compose_from_map( (string) ( $meta['label'] ?? '' ), (array) ( $meta['subfields'] ?? [] ), $subfield_key );

			$rows[] = [
				'form_id'      => $form_id,
				'field_id'     => $field_id,
				'subfield_key' => $subfield_key,
				'label'        => $label,
				'type'         => $meta['type'] ?? '',
				'views'        => $this->ranking->int_col( $row, 'views' ),
				'focus_count'  => $this->ranking->int_col( $row, 'focus_count' ),
				'input_count'  => $this->ranking->int_col( $row, 'input_count' ),
				'abandonments' => $this->ranking->int_col( $row, 'abandonments' ),
				'errors'       => $this->ranking->int_col( $row, 'errors' ),
			] + $this->compute_field_rates( $row );
		}

		return $rows;
	}

	/**
	 * Group per-(form_id, field_id) rows by field_type, summing aggregates and
	 * recomputing derived metrics from the totals.
	 *
	 * Operates on raw rows (pre-enrichment). Uses the portfolio metadata map to
	 * map field_id → type. Rows whose type can't be resolved are dropped.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_rows Raw rows from `run_fields_ranking_query()`.
	 * @param array $metadata Portfolio field metadata map.
	 *
	 * @return array Type rows ready to be sorted/sliced and returned.
	 */
	public function rollup_to_type_rows( array $raw_rows, array $metadata ): array {

		$by_type = [];
		$forms   = [];

		// Tracks the distinct `(form_id, field_id)` pairs already counted per type.
		// With the subfield grain a composite field yields one raw row per subfield,
		// so `field_count` must count distinct fields rather than raw rows.
		$seen_fields = [];

		foreach ( $raw_rows as $row ) {
			$this->accumulate_type_row( (array) $row, $metadata, $by_type, $forms, $seen_fields );
		}

		$rows = [];

		foreach ( $by_type as $type => $agg ) {
			$rows[] = [
				'type'         => $type,
				'label'        => $agg['label'],
				'field_count'  => $agg['field_count'],
				'form_count'   => count( $forms[ $type ] ?? [] ),
				'views'        => $agg['views'],
				'focus_count'  => $agg['focus_count'],
				'input_count'  => $agg['input_count'],
				'abandonments' => $agg['abandonments'],
				'errors'       => $agg['errors'],
			] + $this->compute_field_rates( $agg );
		}

		return $rows;
	}

	/**
	 * Sort type-rollup rows by the requested metric.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $rows       Type rows produced by `rollup_to_type_rows`.
	 * @param string $sort_field Allow-listed sort field.
	 * @param string $sort_dir   The `asc` or `desc` direction.
	 *
	 * @return array
	 */
	public function sort_type_rows( array $rows, string $sort_field, string $sort_dir ): array {

		usort(
			$rows,
			static function ( array $a, array $b ) use ( $sort_field, $sort_dir ): int {

				$av  = $a[ $sort_field ] ?? 0;
				$bv  = $b[ $sort_field ] ?? 0;
				$cmp = $av <=> $bv;

				return $sort_dir === 'asc' ? $cmp : -$cmp;
			}
		);

		return $rows;
	}

	/**
	 * Filter raw rows by field_type using the portfolio metadata map.
	 *
	 * Operates on raw rows (pre-enrichment) so downstream rollup logic can run
	 * against a clean subset. Accepts `eq` (single value) and `in` (array).
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_rows Raw rows from `run_fields_ranking_query()`.
	 * @param array $kept     Normalized filter list.
	 * @param array $metadata Portfolio field metadata map.
	 *
	 * @return array
	 */
	public function apply_field_type_filter( array $raw_rows, array $kept, array $metadata ): array {

		$allowed = $this->collect_allowed_field_types( $kept );

		if ( $allowed === [] ) {
			return $raw_rows;
		}

		$filtered = [];

		foreach ( $raw_rows as $row ) {
			$fid     = (int) ( $row['field_id'] ?? 0 );
			$form_id = (int) ( $row['form_id'] ?? 0 );
			$type    = (string) ( $metadata[ $form_id ][ $fid ]['type'] ?? '' );

			if ( $type === '' || ! in_array( $type, $allowed, true ) ) {
				continue;
			}

			$filtered[] = $row;
		}

		return $filtered;
	}

	/**
	 * Whether the filter set contains a `field_type` filter.
	 *
	 * Mirrors the extraction in {@see apply_field_type_filter()} (via
	 * {@see collect_allowed_field_types()}) so callers can detect a type filter
	 * before deciding whether the SQL LIMIT is safe to apply.
	 *
	 * @since 2.0.0
	 *
	 * @param array $kept Normalized filter list.
	 *
	 * @return bool
	 */
	public function fields_filters_have_field_type( array $kept ): bool {

		return $this->collect_allowed_field_types( $kept ) !== [];
	}

	/**
	 * Resolve the `group_by` arg for fields_ranking.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Args from the model's `request_data.args`.
	 *
	 * @return string Either `field` or `type`.
	 */
	public function resolve_fields_group_by( array $args ): string {

		$candidate = (string) ( $args['group_by'] ?? '' );

		if ( $candidate === self::FIELDS_RANKING_GROUP_BY_TYPE ) {
			return self::FIELDS_RANKING_GROUP_BY_TYPE;
		}

		return self::FIELDS_RANKING_GROUP_BY_FIELD;
	}

	/**
	 * Resolve the `order_by` arg for fields_ranking, scoped to the chosen mode.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $args Args from the model's `request_data.args`.
	 * @param string $mode Resolved group_by mode.
	 *
	 * @return string
	 */
	public function resolve_fields_sort_field( array $args, string $mode ): string {

		$candidate = isset( $args['order_by'] ) ? (string) $args['order_by'] : '';
		$allow     = $mode === self::FIELDS_RANKING_GROUP_BY_TYPE
			? self::FIELDS_RANKING_SORT_FIELDS_PER_TYPE
			: self::FIELDS_RANKING_SORT_FIELDS_PER_FIELD;

		if ( in_array( $candidate, $allow, true ) ) {
			return $candidate;
		}

		return 'views';
	}

	/**
	 * Resolve and clamp the `limit` arg for fields_ranking, scoped to the chosen mode.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $args Args from the model's `request_data.args`.
	 * @param string $mode Resolved group_by mode.
	 *
	 * @return int
	 */
	public function resolve_fields_limit( array $args, string $mode ): int {

		$candidate = isset( $args['limit'] ) ? (int) $args['limit'] : self::FIELDS_RANKING_DEFAULT_LIMIT;
		$max       = $mode === self::FIELDS_RANKING_GROUP_BY_TYPE
			? self::FIELDS_RANKING_MAX_LIMIT_PER_TYPE
			: self::FIELDS_RANKING_MAX_LIMIT_PER_FIELD;

		return max( 1, min( $candidate, $max ) );
	}

	/**
	 * Dispatch each normalized fields_ranking filter to the right WpdbTranslators method.
	 *
	 * `field_type` is intentionally NOT dispatched here — it's resolved post-fetch
	 * via the portfolio field metadata map (see `apply_field_type_filter()`).
	 *
	 * @since 2.0.0
	 *
	 * @param array $kept Normalized filters that survived FilterCompiler validation.
	 *
	 * @return WpdbTranslationContext
	 */
	public function compile_fields_filters_to_ctx( array $kept ): WpdbTranslationContext {

		$ctx = new WpdbTranslationContext();

		foreach ( $kept as $filter ) {
			$this->dispatch_fields_filter( $filter, $ctx, $this->ranking->get_translators() );
		}

		return $ctx;
	}

	/**
	 * Lazy-build the filter compiler for the `fields_ranking` data source.
	 *
	 * @since 2.0.0
	 *
	 * @return FilterCompiler
	 */
	public function get_fields_compiler(): FilterCompiler {

		if ( $this->fields_compiler !== null ) {
			return $this->fields_compiler;
		}

		$this->fields_compiler = new FilterCompiler( $this->ranking->build_field_spec_from_config( self::FIELDS_RANKING_FIELDS ) );

		return $this->fields_compiler;
	}

	/**
	 * Assemble the fields_ranking SQL statement.
	 *
	 * Uses a UNION ALL subquery to combine aggregated `analytics_fields` rows
	 * (excluding the lifetime sentinel) with today's unprocessed snapshot data
	 * from `analytics_snapshot_fields` joined to `analytics_snapshots`.
	 *
	 * `$sort_field` / `$sort_dir` come from allow-lists, so interpolating them is
	 * safe; the sentinel, today boundaries, and limit stay placeholder-bound.
	 * Derived rate expressions are aliased in the SELECT projection so ORDER BY
	 * can resolve them.
	 *
	 * @since 2.0.0
	 *
	 * @param WpdbTranslationContext $ctx         Compiled filter context.
	 * @param string                 $sort_field  Allow-listed sort field.
	 * @param string                 $sort_dir    The `asc` or `desc` direction.
	 * @param bool                   $apply_limit Whether to append the SQL LIMIT clause.
	 * @param string                 $snap_table  Snapshots table name.
	 * @param string                 $sf_table    Snapshot fields table name.
	 *
	 * @return string Prepared-statement SQL with `%s`/`%d` placeholders intact.
	 */
	private function build_fields_ranking_sql( WpdbTranslationContext $ctx, string $sort_field, string $sort_dir, bool $apply_limit, string $snap_table, string $sf_table ): string {

		$table        = AnalyticsDB::fields_table();
		$where        = $ctx->where_parts !== [] ? ' AND ' . implode( ' AND ', $ctx->where_parts ) : '';
		$having       = $ctx->having_parts !== [] ? 'HAVING ' . implode( ' AND ', $ctx->having_parts ) : '';
		$limit_clause = $apply_limit ? 'LIMIT %d' : '';

		// $sort_field and $sort_dir come from allow-lists, so interpolating them is safe.
		// Sentinel, today boundaries, and limit are placeholder-bound.
		// Derived rate expressions are aliased in the SELECT projection so ORDER BY can resolve them.
		return sprintf(
			// language=text
			'SELECT form_id, field_id, subfield_key,
			        SUM(views) AS views,
			        SUM(focus_count) AS focus_count,
			        SUM(click_count) AS click_count,
			        SUM(input_count) AS input_count,
			        SUM(abandonments) AS abandonments,
			        SUM(errors) AS errors,
			        SUM(total_duration_ms) AS total_duration_ms,
			        SUM(duration_count) AS duration_count,
			        %s AS completion_rate,
			        %s AS abandonment_rate,
			        %s AS error_rate,
			        %s AS avg_duration_ms
			   FROM (
			       SELECT form_id, field_id, subfield_key, period_date, views, focus_count, click_count, input_count,
			              abandonments, errors, total_duration_ms, duration_count
			         FROM %s
			        WHERE period_date != %%s
			       UNION ALL
			       SELECT s.form_id, sf.field_id, sf.subfield_key, %%s AS period_date,
			              SUM(sf.was_displayed) AS views,
			              SUM(sf.focus_count) AS focus_count,
			              SUM(sf.click_count) AS click_count,
			              SUM(sf.input_count) AS input_count,
			              0 AS abandonments,
			              SUM(sf.errors) AS errors,
			              COALESCE(SUM(sf.duration_ms), 0) AS total_duration_ms,
			              SUM(CASE WHEN sf.duration_ms IS NOT NULL AND sf.duration_ms > 0 THEN 1 ELSE 0 END) AS duration_count
			         FROM %s sf
			        INNER JOIN %s s ON s.id = sf.snapshot_id
			        WHERE s.processed = 0 AND s.form_visible = 1
			          AND s.occurred_at >= %%s AND s.occurred_at < %%s
			          AND s.id IN (
			              SELECT MAX(id) FROM %s
			               WHERE processed = 0 AND form_visible = 1
			                 AND occurred_at >= %%s AND occurred_at < %%s
			               GROUP BY session_id, form_id
			          )
			        GROUP BY s.form_id, sf.field_id, sf.subfield_key
			   ) t
			  WHERE 1=1%s
			  GROUP BY form_id, field_id, subfield_key
			  %s
			  ORDER BY %s %s
			  %s',
			RankingHelper::COMPLETION_RATE_EXPR,
			RankingHelper::ABANDONMENT_RATE_EXPR,
			RankingHelper::ERROR_RATE_EXPR,
			RankingHelper::AVG_DURATION_EXPR,
			$table,
			$sf_table,
			$snap_table,
			$snap_table, // Reused for the MAX(id)-per-session dedup subquery.
			$where,
			$having,
			$sort_field,
			strtoupper( $sort_dir ),
			$limit_clause
		);
	}

	/**
	 * Compute the derived rate metrics for a field-level aggregation row.
	 *
	 * Same formula in PHP as the SQL HAVING expressions — recomputed here for
	 * consistent rounding across drivers and to avoid float oddities.
	 *
	 * @since 2.0.0
	 *
	 * @param array $totals Row containing SUM totals: views, focus_count, click_count, input_count, abandonments, errors, total_duration_ms, duration_count.
	 *
	 * @return array Map with completion_rate, abandonment_rate, error_rate, avg_duration_ms.
	 */
	private function compute_field_rates( array $totals ): array {

		$totals += [
			'views'             => 0,
			'focus_count'       => 0,
			'click_count'       => 0,
			'input_count'       => 0,
			'abandonments'      => 0,
			'errors'            => 0,
			'total_duration_ms' => 0,
			'duration_count'    => 0,
		];

		$views          = (int) $totals['views'];
		$focus_count    = (int) $totals['focus_count'];
		$click_count    = (int) $totals['click_count'];
		$input_count    = (int) $totals['input_count'];
		$abandonments   = (int) $totals['abandonments'];
		$errors         = (int) $totals['errors'];
		$duration_sum   = (int) $totals['total_duration_ms'];
		$duration_count = (int) $totals['duration_count'];

		return [
			'completion_rate'  => AnalyticsDB::safe_pct( $input_count, $input_count + $focus_count + $click_count ),
			'abandonment_rate' => AnalyticsDB::safe_pct( $abandonments, $views ),
			'error_rate'       => AnalyticsDB::safe_pct( $errors, $views ),
			'avg_duration_ms'  => $duration_count > 0 ? (int) round( $duration_sum / $duration_count ) : 0,
		];
	}

	/**
	 * Accumulate a single raw row into the per-type rollup buffers.
	 *
	 * Resolves the row's field type via the portfolio metadata map, seeds a fresh
	 * accumulator on first sight of a type, then merges this row's metrics into it.
	 * Rows whose type can't be resolved are skipped.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row         Single raw row from `run_fields_ranking_query()`.
	 * @param array $metadata    Portfolio field metadata map.
	 * @param array $by_type     Per-type accumulator buffer, passed by reference.
	 * @param array $forms       Per-type form_id set buffer, passed by reference.
	 * @param array $seen_fields Distinct `(form_id, field_id)` set buffer, passed by reference.
	 *
	 * @return void
	 */
	private function accumulate_type_row( array $row, array $metadata, array &$by_type, array &$forms, array &$seen_fields ): void {

		$form_id  = (int) ( $row['form_id'] ?? 0 );
		$field_id = (int) ( $row['field_id'] ?? 0 );
		$type     = $metadata[ $form_id ][ $field_id ]['type'] ?? '';

		if ( $type === '' ) {
			return;
		}

		if ( ! isset( $by_type[ $type ] ) ) {
			$by_type[ $type ] = $this->new_type_accumulator( $type );
			$forms[ $type ]   = [];
		}

		// Count each field once per type. A composite field produces one raw row per
		// subfield, so increment `field_count` only on the first row for this field.
		$field_key = $form_id . ':' . $field_id;

		if ( ! isset( $seen_fields[ $type ][ $field_key ] ) ) {
			$seen_fields[ $type ][ $field_key ] = true;

			++$by_type[ $type ]['field_count'];
		}

		// `abandonments` AND `views` are both summed across subfields here, which
		// over-counts composite fields: `abandonments` is a DISTINCT-session count
		// (the same session is counted once per touched subfield) and `views` carries
		// the field-level displayed flag replicated onto every subfield row. Both are
		// replicated-per-subfield, not additive (see the additivity note in the
		// per-subfield tracking knowledge base). The additive counters
		// (focus/click/input/errors/duration) sum correctly. This per-type rollup
		// requires one row per field type, so the over-count is an accepted
		// replace-parent tradeoff; the per-subfield breakdown (group_by=field) avoids
		// it entirely by never summing across subfields.
		$this->merge_type_metrics( $by_type[ $type ], $row );

		$forms[ $type ][ $form_id ] = true;
	}

	/**
	 * Merge a raw row's summable metric columns into a type accumulator.
	 *
	 * @since 2.0.0
	 *
	 * @param array $accumulator Type accumulator, passed by reference.
	 * @param array $row         Single raw row.
	 *
	 * @return void
	 */
	private function merge_type_metrics( array &$accumulator, array $row ): void {

		$metric_keys = [ 'views', 'focus_count', 'click_count', 'input_count', 'abandonments', 'errors', 'total_duration_ms', 'duration_count' ];

		foreach ( $metric_keys as $key ) {
			$accumulator[ $key ] += (int) ( $row[ $key ] ?? 0 );
		}
	}

	/**
	 * Build a zeroed per-type accumulator seed.
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Field type slug.
	 *
	 * @return array Accumulator with zeroed metric buckets.
	 */
	private function new_type_accumulator( string $type ): array {

		return [
			'type'              => $type,
			'label'             => $this->humanize_field_type( $type ),
			'field_count'       => 0,
			'form_count'        => 0,
			'views'             => 0,
			'focus_count'       => 0,
			'click_count'       => 0,
			'input_count'       => 0,
			'abandonments'      => 0,
			'errors'            => 0,
			'total_duration_ms' => 0,
			'duration_count'    => 0,
		];
	}

	/**
	 * Map a WPForms field-type slug to a human-readable label.
	 *
	 * Resolves the label from the registered field object's name. Falls back to
	 * a Title-Case version of the slug for types whose field class is not loaded
	 * (e.g. a deactivated addon with historical entry data).
	 *
	 * @since 2.0.0
	 *
	 * @param string $type Field type slug.
	 *
	 * @return string
	 */
	private function humanize_field_type( string $type ): string {

		$registry = wpforms()->obj( 'fields_registry' );
		$names    = $registry instanceof Registry ? $registry->get_names() : [];

		return $names[ $type ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $type ) );
	}

	/**
	 * Collect the de-duplicated list of allowed field types from the filter set.
	 *
	 * Accepts `eq` (single value) and `in` (array) for the `field_type` field;
	 * other fields are ignored.
	 *
	 * @since 2.0.0
	 *
	 * @param array $kept Normalized filter list.
	 *
	 * @return array Unique allowed field-type slugs (may be empty).
	 */
	private function collect_allowed_field_types( array $kept ): array {

		$allowed = [];

		foreach ( $kept as $filter ) {
			if ( ( $filter['field'] ?? '' ) !== 'field_type' ) {
				continue;
			}

			$value = $filter['value'] ?? null;

			if ( is_array( $value ) ) {
				$allowed = array_merge( $allowed, array_map( 'strval', $value ) );

				continue;
			}

			$allowed[] = (string) $value;
		}

		return array_unique( $allowed );
	}

	/**
	 * Dispatch a single normalized fields_ranking filter.
	 *
	 * Field-to-clause mapping (analytics_fields is the table involved):
	 *
	 *   - `form_id` / `field_id`       → WHERE comparison or WHERE IN.
	 *   - `period_date`                → WHERE date range.
	 *   - raw metric columns           → HAVING SUM(...).
	 *   - derived rates                → HAVING derived expression (NULLIF-guarded).
	 *   - `field_type`                 → no-op here, applied post-fetch.
	 *
	 * @since 2.0.0
	 *
	 * @param array                  $filter      Normalized filter entry.
	 * @param WpdbTranslationContext $ctx         Accumulator context.
	 * @param WpdbTranslators        $translators Translator helper.
	 *
	 * @return void
	 */
	private function dispatch_fields_filter( array $filter, WpdbTranslationContext $ctx, WpdbTranslators $translators ): void {

		$field = (string) ( $filter['field'] ?? '' );

		if ( $field === 'form_id' || $field === 'field_id' ) {
			$this->ranking->dispatch_form_id_filter( $field, $filter, $ctx, $translators );

			return;
		}

		if ( $field === 'period_date' ) {
			$translators->where_date_range( 'period_date', $filter, $ctx );

			return;
		}

		// `field_type` falls through unhandled — applied post-fetch.
		$this->ranking->dispatch_fields_metric_filter( $field, $filter, $ctx, $translators );
	}
}
