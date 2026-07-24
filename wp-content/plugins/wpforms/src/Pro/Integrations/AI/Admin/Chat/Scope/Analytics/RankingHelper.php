<?php
/**
 * Suppress unrelated inspections.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics;

use WPForms\Db\Analytics\DB as LiteDB;
use WPForms\Integrations\AI\Admin\Chat\Filter\FieldSpec;
use WPForms\Integrations\AI\Admin\Chat\Filter\FieldType;
use WPForms\Integrations\AI\Admin\Chat\Filter\FilterCompiler;
use WPForms\Integrations\AI\Admin\Chat\Filter\Translators\WpdbTranslationContext;
use WPForms\Integrations\AI\Admin\Chat\Filter\Translators\WpdbTranslators;
use WPForms\Pro\Db\Analytics\DB as AnalyticsDB;

/**
 * Forms-ranking queries, SQL generation, and shared filter dispatch infrastructure.
 *
 * Extracted from Analytics to comply with the 20-method-per-class threshold.
 *
 * @since 2.0.0
 */
class RankingHelper {

	/**
	 * Per-call cap for the `forms_ranking` data source.
	 *
	 * @since 2.0.0
	 */
	public const RANKING_MAX_LIMIT = 50;

	/**
	 * Default row count for `forms_ranking` when the model doesn't supply `limit`.
	 *
	 * @since 2.0.0
	 */
	public const RANKING_DEFAULT_LIMIT = 10;

	/**
	 * Max filters accepted per `forms_ranking` call.
	 *
	 * @since 2.0.0
	 */
	public const RANKING_MAX_FILTERS = 10;

	/**
	 * Derived conversion-rate SQL expression (percentage, 0–100).
	 *
	 * Scope-owned constant string — never accept this from filter input. The
	 * NULLIF guards against divide-by-zero on forms with no views.
	 *
	 * @since 2.0.0
	 */
	public const CONVERSION_RATE_EXPR = 'SUM(f.submissions) / NULLIF(SUM(f.views), 0) * 100';

	/**
	 * Derived abandonment-rate SQL expression (percentage, 0–100).
	 *
	 * Numerator comes from the analytics_fields side of the JOIN (aliased `fa`),
	 * denominator from analytics_forms (aliased `f`). Mirrors the Analytics
	 * admin page's `abandonment_pct` formula so chat answers and the page UI
	 * report the same number.
	 *
	 * @since 2.0.0
	 */
	public const RANKING_ABANDONMENT_RATE_EXPR = 'MAX(fa.abandonments) / NULLIF(SUM(f.views), 0) * 100';

	/**
	 * Derived error-rate SQL expression (percentage, 0–100).
	 *
	 * Same shape as `RANKING_ABANDONMENT_RATE_EXPR` — see its comment.
	 *
	 * @since 2.0.0
	 */
	public const RANKING_ERROR_RATE_EXPR = 'MAX(fa.errors) / NULLIF(SUM(f.views), 0) * 100';

	/**
	 * Allow-list of fields the `forms_ranking` filter spec exposes to the LLM.
	 *
	 * Maps the LLM-facing field slug to `[ FieldType::*, ops, supports_window ]`.
	 *
	 * The `interactions` / `abandonments` / `errors` triplet and the matching
	 * `*_rate` derived fields are aggregated from `analytics_fields` via a
	 * LEFT JOIN — they're lifetime aggregates regardless of any `period_date`
	 * filter the caller supplies. See `run_ranking_query()` for the caveat.
	 *
	 * @since 2.0.0
	 */
	private const RANKING_FIELDS = [
		'form_id'          => [ FieldType::INT, [ 'eq', 'neq', 'in' ], false ],
		'period_date'      => [ FieldType::DATE, [ 'eq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'views'            => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'submissions'      => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], true ],
		'interactions'     => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'abandonments'     => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'errors'           => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'conversion_rate'  => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'abandonment_rate' => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
		'error_rate'       => [ FieldType::NUMERIC, [ 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ], false ],
	];

	/**
	 * Allow-list of fields valid for the `order_by` arg.
	 *
	 * @since 2.0.0
	 */
	private const RANKING_SORT_FIELDS = [
		'form_id',
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
	 * Derived completion-rate SQL expression (percentage).
	 *
	 * Value-change events over total engagement events (input + focus + click).
	 * Since the tracker now absorbs a field's focus / click into its value change,
	 * a completed field carries input but no focus / click — so the denominator must
	 * include input to read as 100%. Scope-owned constant string — never accept this
	 * from filter input. The NULLIF guards against divide-by-zero on untouched fields.
	 *
	 * @since 2.0.0
	 */
	public const COMPLETION_RATE_EXPR = 'SUM(input_count) / NULLIF(SUM(input_count) + SUM(focus_count) + SUM(click_count), 0) * 100';

	/**
	 * Derived abandonment-rate SQL expression (percentage, 0–100).
	 *
	 * @since 2.0.0
	 */
	public const ABANDONMENT_RATE_EXPR = 'SUM(abandonments) / NULLIF(SUM(views), 0) * 100';

	/**
	 * Derived error-rate SQL expression (percentage).
	 *
	 * Validation errors per field view (errors over views). Scope-owned constant
	 * string — never accept this from filter input. The NULLIF guards against
	 * divide-by-zero on fields with no views.
	 *
	 * @since 2.0.0
	 */
	public const ERROR_RATE_EXPR = 'SUM(errors) / NULLIF(SUM(views), 0) * 100';

	/**
	 * Derived average-duration SQL expression (milliseconds).
	 *
	 * @since 2.0.0
	 */
	public const AVG_DURATION_EXPR = 'SUM(total_duration_ms) / NULLIF(SUM(duration_count), 0)';

	/**
	 * Map of derived rate field slugs to their SQL expressions.
	 *
	 * Used by `dispatch_fields_metric_filter()` to dispatch a derived-rate filter to the
	 * HAVING clause via the right expression. Scope-owned — the EXPR is never
	 * accepted from filter input.
	 *
	 * @since 2.0.0
	 */
	public const DERIVED_RATE_EXPRESSIONS = [
		'completion_rate'  => self::COMPLETION_RATE_EXPR,
		'abandonment_rate' => self::ABANDONMENT_RATE_EXPR,
		'error_rate'       => self::ERROR_RATE_EXPR,
		'avg_duration_ms'  => self::AVG_DURATION_EXPR,
	];

	/**
	 * Lazy-built filter compiler for forms_ranking.
	 *
	 * @since 2.0.0
	 *
	 * @var FilterCompiler|null
	 */
	private $compiler;

	/**
	 * Lazy-built $wpdb translator helper.
	 *
	 * @since 2.0.0
	 *
	 * @var WpdbTranslators|null
	 */
	private $translators;

	/**
	 * Get the filter compiler instance.
	 *
	 * @since 2.0.0
	 *
	 * @return FilterCompiler
	 */
	public function get_compiler(): FilterCompiler {

		if ( $this->compiler !== null ) {
			return $this->compiler;
		}

		$this->compiler = new FilterCompiler( $this->build_field_spec_from_config( self::RANKING_FIELDS ) );

		return $this->compiler;
	}

	/**
	 * Get the wpdb translator helper instance.
	 *
	 * @since 2.0.0
	 *
	 * @return WpdbTranslators
	 */
	public function get_translators(): WpdbTranslators {

		if ( $this->translators !== null ) {
			return $this->translators;
		}

		$this->translators = new WpdbTranslators();

		return $this->translators;
	}

	/**
	 * Build a FieldSpec from a field config map.
	 *
	 * Accepts a map shaped as `[ field_slug => [ FieldType::*, ops, supports_window ] ]`
	 * — see `RANKING_FIELDS` and `FIELDS_RANKING_FIELDS`.
	 *
	 * @since 2.0.0
	 *
	 * @param array $config Field config map.
	 *
	 * @return FieldSpec
	 */
	public function build_field_spec_from_config( array $config ): FieldSpec {

		$spec = new FieldSpec();

		foreach ( $config as $field => $entry ) {
			[ $type, $ops, $supports_window ] = $entry;

			$spec->add( $field, $type, $ops, [], $supports_window );
		}

		return $spec;
	}

	/**
	 * Read an integer column from a raw row, defaulting to zero when absent.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $row Raw row.
	 * @param string $key Column key.
	 *
	 * @return int
	 */
	public function int_col( array $row, string $key ): int {

		return (int) ( $row[ $key ] ?? 0 );
	}

	/**
	 * Dispatch a `form_id` filter, splitting the `in` operator from comparisons.
	 *
	 * @since 2.0.0
	 *
	 * @param string                 $column      Aliased column reference.
	 * @param array                  $filter      Normalized filter entry.
	 * @param WpdbTranslationContext $ctx         Accumulator context.
	 * @param WpdbTranslators        $translators Translator helper.
	 *
	 * @return void
	 */
	public function dispatch_form_id_filter( string $column, array $filter, WpdbTranslationContext $ctx, WpdbTranslators $translators ): void {

		$op = (string) ( $filter['op'] ?? '' );

		if ( $op === 'in' ) {
			$translators->where_in( $column, (array) ( $filter['value'] ?? [] ), $ctx );

			return;
		}

		$translators->where_compare( $column, $filter, $ctx );
	}

	/**
	 * Resolve the HAVING expression for a `forms_ranking` aggregate/derived field.
	 *
	 * Raw field aggregates (`interactions`/`abandonments`/`errors`) come pre-summed
	 * from the JOIN subquery (one row per form_id); the MAX() wrap satisfies
	 * ONLY_FULL_GROUP_BY without changing the value — it's constant per group.
	 *
	 * @since 2.0.0
	 *
	 * @param string $field Normalized filter field name.
	 *
	 * @return string SQL expression, or empty string when the field is not a HAVING target.
	 */
	public function ranking_having_expression( string $field ): string {

		if ( in_array( $field, [ 'views', 'submissions' ], true ) ) {
			return "SUM(f.$field)";
		}

		if ( in_array( $field, [ 'interactions', 'abandonments', 'errors' ], true ) ) {
			return "MAX(fa.$field)";
		}

		$derived = [
			'conversion_rate'  => self::CONVERSION_RATE_EXPR,
			'abandonment_rate' => self::RANKING_ABANDONMENT_RATE_EXPR,
			'error_rate'       => self::RANKING_ERROR_RATE_EXPR,
		];

		return $derived[ $field ] ?? '';
	}

	/**
	 * Dispatch a fields_ranking metric filter to the HAVING clause.
	 *
	 * Raw summable columns use `SUM(...)`; derived rates use their NULLIF-guarded
	 * expression. `field_type` is intentionally not handled — applied post-fetch.
	 *
	 * @since 2.0.0
	 *
	 * @param string                 $field       Normalized filter field name.
	 * @param array                  $filter      Normalized filter entry.
	 * @param WpdbTranslationContext $ctx         Accumulator context.
	 * @param WpdbTranslators        $translators Translator helper.
	 *
	 * @return void
	 */
	public function dispatch_fields_metric_filter( string $field, array $filter, WpdbTranslationContext $ctx, WpdbTranslators $translators ): void {

		if ( in_array( $field, [ 'views', 'focus_count', 'input_count', 'abandonments', 'errors' ], true ) ) {
			$translators->having_compare( "SUM({$field})", $filter, $ctx );

			return;
		}

		if ( isset( self::DERIVED_RATE_EXPRESSIONS[ $field ] ) ) {
			$translators->having_compare( self::DERIVED_RATE_EXPRESSIONS[ $field ], $filter, $ctx );
		}
	}

	/**
	 * Resolve and validate the `order_by` arg.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Args from the model's `request_data.args`.
	 *
	 * @return string
	 */
	public function resolve_sort_field( array $args ): string {

		$candidate = isset( $args['order_by'] ) ? (string) $args['order_by'] : '';

		if ( in_array( $candidate, self::RANKING_SORT_FIELDS, true ) ) {
			return $candidate;
		}

		return 'views';
	}

	/**
	 * Resolve and validate the `order` arg (asc/desc).
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Args from the model's `request_data.args`.
	 *
	 * @return string
	 */
	public function resolve_sort_dir( array $args ): string {

		$candidate = strtolower( (string) ( $args['order'] ?? '' ) );

		return $candidate === 'asc' ? 'asc' : 'desc';
	}

	/**
	 * Resolve and clamp the `limit` arg.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Args from the model's `request_data.args`.
	 *
	 * @return int
	 */
	public function resolve_limit( array $args ): int {

		$candidate = isset( $args['limit'] ) ? (int) $args['limit'] : self::RANKING_DEFAULT_LIMIT;

		return max( 1, min( $candidate, self::RANKING_MAX_LIMIT ) );
	}

	/**
	 * Dispatch each normalized filter to the right WpdbTranslators method.
	 *
	 * Field-to-clause mapping (analytics_forms is the only table involved):
	 *
	 *   - `form_id`         → WHERE comparison or WHERE IN (eq/neq/in).
	 *   - `period_date`     → WHERE date range (eq/lt/lte/gt/gte).
	 *   - `views` / `submissions` → HAVING aggregate (SUM-based).
	 *   - `conversion_rate` → HAVING derived expression (NULLIF guards divide-by-zero).
	 *
	 * @since 2.0.0
	 *
	 * @param array $kept Normalized filters that survived FilterCompiler validation.
	 *
	 * @return WpdbTranslationContext
	 */
	public function compile_filters_to_ctx( array $kept ): WpdbTranslationContext {

		$ctx = new WpdbTranslationContext();

		foreach ( $kept as $filter ) {
			$this->dispatch_filter( $filter, $ctx, $this->get_translators() );
		}

		return $ctx;
	}

	/**
	 * Assemble + execute the ranking SQL.
	 *
	 * Returns `[ rows, truncated ]`. `truncated` is true when the table held more
	 * matching rows than the requested limit (caller asked for limit + 1 internally
	 * and we sliced).
	 *
	 * @since 2.0.0
	 *
	 * @param WpdbTranslationContext $ctx        Compiled filter context.
	 * @param string                 $sort_field Allow-listed sort field (see RANKING_SORT_FIELDS).
	 * @param string                 $sort_dir   The `asc` or `desc` direction.
	 * @param int                    $limit      Requested limit (already clamped).
	 *
	 * @return array{0: array, 1: bool}
	 */
	public function run_ranking_query( WpdbTranslationContext $ctx, string $sort_field, string $sort_dir, int $limit ): array {

		global $wpdb;

		$db = wpforms()->obj( 'analytics_db' );

		// Analytics DB is unregistered when the feature is disabled or its tables
		// have not been migrated yet, while this scope stays reachable. Degrade to
		// an empty ranking instead of fataling on a null service.
		if ( ! $db ) {
			return [ [], false ];
		}

		[ $where, $having ] = $this->build_ranking_sql_clauses( $ctx );

		[ $today_start, $tomorrow_start ] = $db->today_boundaries();

		$today_date = substr( $today_start, 0, 10 );

		$snap_table = LiteDB::snapshots_table();
		$sf_table   = AnalyticsDB::snapshot_fields_table();

		$sql  = $this->build_ranking_sql( $where, $having, $sort_field, $sort_dir, $snap_table, $sf_table );
		$bind = array_merge(
			[ AnalyticsDB::LIFETIME_SENTINEL_DATE ],        // f UNION: exclude sentinel from daily rows.
			[ $today_date, $today_start, $tomorrow_start ], // f UNION: today's snapshots.
			[ AnalyticsDB::LIFETIME_SENTINEL_DATE ],        // fa UNION: exclude sentinel from fields.
			[ $today_start, $tomorrow_start ],              // fa UNION: today's field snapshots.
			[ $today_start, $tomorrow_start ],              // fa UNION: dedup subquery (latest snapshot per session).
			$ctx->where_params,
			$ctx->having_params,
			[ $limit + 1 ]
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$raw_rows = $wpdb->get_results( $wpdb->prepare( $sql, $bind ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$raw_rows  = is_array( $raw_rows ) ? $raw_rows : [];
		$truncated = count( $raw_rows ) > $limit;
		$raw_rows  = array_slice( $raw_rows, 0, $limit );

		// Prime the post cache once so each per-row get_the_title() lookup in
		// build_ranking_column_list() is a cache hit instead of a separate query.
		$this->prime_form_title_cache( $raw_rows );

		$rows = [];

		foreach ( $raw_rows as $row ) {
			$rows[] = $this->build_ranking_column_list( (array) $row );
		}

		return [ $rows, $truncated ];
	}

	/**
	 * Map a raw ranking row to its normalized output column list.
	 *
	 * Recomputes the three rates in PHP via `DB::safe_pct` to round consistently
	 * with `form_stats`/`field_stats` and avoid driver-specific float oddities — SQL
	 * only needs the alias for `ORDER BY <rate>` to resolve.
	 *
	 * @since 2.0.0
	 *
	 * @param array $row Raw row from the ranking query.
	 *
	 * @return array Normalized column list.
	 */
	public function build_ranking_column_list( array $row ): array {

		$form_id      = $this->int_col( $row, 'form_id' );
		$views        = $this->int_col( $row, 'views' );
		$submissions  = $this->int_col( $row, 'submissions' );
		$interactions = $this->int_col( $row, 'interactions' );
		$abandonments = $this->int_col( $row, 'abandonments' );
		$errors       = $this->int_col( $row, 'errors' );

		return [
			'form_id'          => $form_id,
			'form_title'       => get_the_title( $form_id ),
			'views'            => $views,
			'submissions'      => $submissions,
			'interactions'     => $interactions,
			'abandonments'     => $abandonments,
			'errors'           => $errors,
			'conversion_rate'  => AnalyticsDB::safe_pct( $submissions, $views ),
			'abandonment_rate' => AnalyticsDB::safe_pct( $abandonments, $views ),
			'error_rate'       => AnalyticsDB::safe_pct( $errors, $views ),
		];
	}

	/**
	 * Prime the WordPress post cache for the form IDs in a ranking result set.
	 *
	 * The form title for each ranked form is read via `get_the_title()` in
	 * `build_ranking_column_list()`. Priming the cache once up front keeps those
	 * per-row lookups as cache hits instead of one query per form.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_rows Raw rows from the ranking query.
	 *
	 * @return void
	 */
	private function prime_form_title_cache( array $raw_rows ): void {

		$form_ids = [];

		foreach ( $raw_rows as $row ) {
			$form_id = (int) ( $row['form_id'] ?? 0 );

			if ( $form_id > 0 ) {
				$form_ids[] = $form_id;
			}
		}

		if ( $form_ids === [] ) {
			return;
		}

		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $form_ids, false, false );
		}
	}

	/**
	 * Dispatch a single normalized filter to the appropriate WpdbTranslators method.
	 *
	 * Extracted from the per-filter loop to keep `compile_filters_to_ctx()` inside
	 * the WPForms PHPCS nesting-depth cap.
	 *
	 * @since 2.0.0
	 *
	 * @param array                  $filter      Normalized filter entry.
	 * @param WpdbTranslationContext $ctx         Accumulator context.
	 * @param WpdbTranslators        $translators Translator helper.
	 *
	 * @return void
	 */
	private function dispatch_filter( array $filter, WpdbTranslationContext $ctx, WpdbTranslators $translators ): void {

		$field = (string) ( $filter['field'] ?? '' );

		// The forms_ranking SQL aliases analytics_forms as `f` and LEFT-JOINs a
		// per-form field-aggregate subquery as `fa`. `form_id` needs the eq/in
		// split, so it's dispatched ahead of the HAVING-expression map.
		if ( $field === 'form_id' ) {
			$this->dispatch_form_id_filter( 'f.form_id', $filter, $ctx, $translators );

			return;
		}

		if ( $field === 'period_date' ) {
			$translators->where_date_range( 'f.period_date', $filter, $ctx );

			return;
		}

		$expression = $this->ranking_having_expression( $field );

		if ( $expression === '' ) {
			return;
		}

		$translators->having_compare( $expression, $filter, $ctx );
	}

	/**
	 * Build the optional WHERE / HAVING fragments for the ranking query.
	 *
	 * @since 2.0.0
	 *
	 * @param WpdbTranslationContext $ctx Compiled filter context.
	 *
	 * @return array{0: string, 1: string} Tuple [ where_fragment, having_clause ].
	 */
	private function build_ranking_sql_clauses( WpdbTranslationContext $ctx ): array {

		$where  = $ctx->where_parts !== [] ? ' AND ' . implode( ' AND ', $ctx->where_parts ) : '';
		$having = $ctx->having_parts !== [] ? 'HAVING ' . implode( ' AND ', $ctx->having_parts ) : '';

		return [ $where, $having ];
	}

	/**
	 * Assemble the ranking SQL statement with the supplied clauses and ordering.
	 *
	 * The `f` source is a UNION ALL of daily aggregated rows (sentinel excluded)
	 * and today's unprocessed snapshots, so forms that only exist in today's
	 * traffic are visible before the nightly aggregation runs.
	 *
	 * The `fa` LEFT JOIN similarly merges daily field aggregates with today's
	 * unprocessed snapshot-field rows.
	 *
	 * Field-total caveat: the `fa` subquery sums `analytics_fields` to one row per
	 * form. `interactions` (focus + click + input) and `errors` are additive across
	 * a composite field's subfields, so their per-form totals are accurate.
	 * `abandonments` is NOT additive — it is a DISTINCT-session count replicated on
	 * every touched subfield row, so `SUM(abandonments)` over-counts forms that
	 * contain composite fields (and inflates the derived abandonment_rate). This is
	 * an accepted replace-parent tradeoff: a per-form ranking needs a single number
	 * per form, and the true field-level DISTINCT-session count cannot be recovered
	 * by summing the aggregated subfield rows (see the additivity note in the
	 * per-subfield tracking knowledge base). `views`/`submissions` are sourced from
	 * the form layer (`f`), not the field rows, so they are unaffected.
	 *
	 * `$sort_field` and `$sort_dir` come from allow-lists (RANKING_SORT_FIELDS +
	 * asc/desc), so interpolating them is safe. Table names are from static
	 * accessors. All dynamic values use `%%s`/`%%d` for `$wpdb->prepare()`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $where      Optional WHERE fragment (leading ` AND ` included).
	 * @param string $having     Optional HAVING clause.
	 * @param string $sort_field Allow-listed sort field.
	 * @param string $sort_dir   The `asc` or `desc` direction.
	 * @param string $snap_table Snapshots table name.
	 * @param string $sf_table   Snapshot fields table name.
	 *
	 * @return string Prepared-statement SQL with `%s`/`%d` placeholders intact.
	 */
	private function build_ranking_sql( string $where, string $having, string $sort_field, string $sort_dir, string $snap_table, string $sf_table ): string {

		return sprintf(
			// language=text
			'SELECT f.form_id,
			        SUM(f.views) AS views,
			        SUM(f.submissions) AS submissions,
			        COALESCE(MAX(fa.interactions), 0) AS interactions,
			        COALESCE(MAX(fa.abandonments), 0) AS abandonments,
			        COALESCE(MAX(fa.errors), 0) AS errors,
			        %1$s AS conversion_rate,
			        %2$s AS abandonment_rate,
			        %3$s AS error_rate
			   FROM (
			       SELECT form_id, period_date, views, submissions
			         FROM %4$s
			        WHERE period_date != %%s
			       UNION ALL
			       SELECT form_id, %%s AS period_date,
			              COUNT(DISTINCT session_id) AS views,
			              COUNT(DISTINCT CASE WHEN trigger_type = 2 THEN session_id END) AS submissions
			         FROM %5$s
			        WHERE processed = 0 AND form_visible = 1
			          AND occurred_at >= %%s AND occurred_at < %%s
			        GROUP BY form_id
			   ) f
			   LEFT JOIN (
			       SELECT form_id, SUM(interactions) AS interactions,
			              SUM(abandonments) AS abandonments, SUM(errors) AS errors
			       FROM (
			           SELECT form_id,
			                  SUM(focus_count + click_count + input_count) AS interactions,
			                  SUM(abandonments) AS abandonments,
			                  SUM(errors) AS errors
			             FROM %6$s
			            WHERE period_date != %%s
			            GROUP BY form_id
			           UNION ALL
			           SELECT s.form_id,
			                  SUM(sf.focus_count + sf.click_count + sf.input_count) AS interactions,
			                  0 AS abandonments,
			                  SUM(sf.errors) AS errors
			             FROM %7$s sf
			            INNER JOIN %5$s s ON s.id = sf.snapshot_id
			            WHERE s.processed = 0 AND s.form_visible = 1
			              AND s.occurred_at >= %%s AND s.occurred_at < %%s
			              AND s.id IN (
			                  SELECT MAX(id) FROM %5$s
			                   WHERE processed = 0 AND form_visible = 1
			                     AND occurred_at >= %%s AND occurred_at < %%s
			                   GROUP BY session_id, form_id
			              )
			            GROUP BY s.form_id
			       ) combined
			       GROUP BY form_id
			   ) fa ON fa.form_id = f.form_id
			  WHERE 1=1%8$s
			  GROUP BY f.form_id
			  %9$s
			  ORDER BY %10$s %11$s
			  LIMIT %%d',
			self::CONVERSION_RATE_EXPR,
			self::RANKING_ABANDONMENT_RATE_EXPR,
			self::RANKING_ERROR_RATE_EXPR,
			AnalyticsDB::forms_table(),
			$snap_table,
			AnalyticsDB::fields_table(),
			$sf_table,
			$where,
			$having,
			$sort_field,
			strtoupper( $sort_dir )
		);
	}
}
