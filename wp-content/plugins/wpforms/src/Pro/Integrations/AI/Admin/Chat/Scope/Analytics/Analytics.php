<?php
/**
 * Suppress unrelated inspections.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics;

use WPForms\Integrations\AI\Admin\Chat\Scope\FormsInventory\FormsInventory;
use WPForms\Integrations\AI\Admin\Chat\ScopeBase;

/**
 * Scope: Analytics (form_stats, field_stats).
 *
 * @since 2.0.0
 */
class Analytics extends ScopeBase {

	/**
	 * Scope slug.
	 *
	 * @since 2.0.0
	 */
	public const SLUG = 'analytics';

	/**
	 * Lazy-built state builder.
	 *
	 * @since 2.0.0
	 *
	 * @var StateBuilder|null
	 */
	private $state_builder;

	/**
	 * Lazy-built ranking helper.
	 *
	 * @since 2.0.0
	 *
	 * @var RankingHelper|null
	 */
	private $ranking_helper;

	/**
	 * Lazy-built fields ranking helper.
	 *
	 * @since 2.0.0
	 *
	 * @var FieldsRankingHelper|null
	 */
	private $fields_ranking_helper;

	/**
	 * Lazy-built trend helper.
	 *
	 * @since 2.0.0
	 *
	 * @var TrendHelper|null
	 */
	private $trend_helper;

	/**
	 * Capability required to use this scope.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_capability(): string {

		return 'view_entries';
	}

	/**
	 * License tier required.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_requires(): string {

		return 'pro';
	}

	/**
	 * Per-scope data sources mapping include-token to callable.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_data_sources(): array {

		return [
			'form_stats'       => [ $this, 'fetch_form_stats' ],
			'form_stats_trend' => [ $this, 'fetch_form_stats_trend' ],
			'field_stats'      => [ $this, 'fetch_field_stats' ],
			'forms_ranking'    => [ $this, 'fetch_forms_ranking' ],
			'fields_ranking'   => [ $this, 'fetch_fields_ranking' ],
		];
	}

	/**
	 * Build the scope's state contribution.
	 *
	 * @since 2.0.0
	 *
	 * @param array $surface     Active surface config.
	 * @param array $request     Sanitized request payload.
	 * @param array $accumulated Running state dict so far.
	 *
	 * @return array
	 */
	public function build_state( array $surface, array $request, array $accumulated ): array {

		$slug    = (string) ( $surface['slug'] ?? '' );
		$builder = $this->get_state_builder();

		if ( $slug === StateBuilder::ANALYTICS_SURFACE_SLUG ) {
			return $builder->build_single_form_state( $request );
		}

		$state      = $builder->build_portfolio_state();
		$lite_forms = $accumulated['surface_state'][ FormsInventory::SLUG ]['forms_summary'] ?? null;

		if ( is_array( $lite_forms ) && ! empty( $state['forms_summary'] ) ) {
			$state['lite_forms_enrichment'] = $builder->index_by_form_id( $state['forms_summary'] );
		}

		return $state;
	}

	/**
	 * Follow-up data source: aggregated form-level stats for a date range.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array
	 */
	public function fetch_form_stats( array $payload ): array {

		$form_id = $this->get_state_builder()->resolve_form_id( $payload );

		if ( $form_id === 0 ) {
			return [];
		}

		[ $from, $to ] = $this->get_trend_helper()->derive_date_range( $payload );

		$service = wpforms()->obj( 'analytics_stats' );

		if ( ! $service ) {
			return [];
		}

		// Pass form_data so the form-level totals reconcile to the displayed field
		// rows (deleted / non-interactive fields excluded), matching the table and
		// the Analytics admin page rather than an inflated unfiltered sum.
		$form_data = $this->get_state_builder()->load_form_data( $form_id );

		$stats = $service->get_form_stats( $form_id, $from, $to, $form_data );

		// Echo the resolved form_id and date window so the middleware/LLM can
		// confirm the data identity and name the dates in the answer regardless
		// of whether they came from args, pageState, or the default lookback.
		// Without form_id echo the LLM hedges with "I can't confirm this is for
		// form #X" when the form_id was resolved from pageState rather than args.
		$stats['form_id']    = $form_id;
		$stats['date_range'] = [
			'from' => $from,
			'to'   => $to,
		];

		return $stats;
	}

	/**
	 * Follow-up data source: trend stats grouped by day, week, or month.
	 *
	 * Defaults to monthly grouping over the trailing 6 calendar months when
	 * the caller supplies no `granularity` and no `date_range`. Granularity-
	 * specific row caps (see `TREND_MAX_ROWS`) keep the LLM context bounded;
	 * the most-recent N rows are kept on overflow.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array
	 */
	public function fetch_form_stats_trend( array $payload ): array {

		$form_id = $this->get_state_builder()->resolve_form_id( $payload );

		if ( $form_id === 0 ) {
			return [];
		}

		$helper        = $this->get_trend_helper();
		$granularity   = $helper->resolve_trend_granularity( $payload );
		[ $from, $to ] = $helper->derive_trend_date_range( $payload, $granularity );

		$db = wpforms()->obj( 'analytics_db' );

		if ( ! $db ) {
			return [];
		}

		$rows = (array) $db->get_form_stats_trend( $form_id, $from, $to, $granularity );

		$max_rows  = TrendHelper::TREND_MAX_ROWS[ $granularity ] ?? TrendHelper::TREND_MAX_ROWS['month'];
		$truncated = false;

		if ( count( $rows ) > $max_rows ) {
			$rows      = array_slice( $rows, -$max_rows );
			$truncated = true;
		}

		$metrics = $helper->resolve_trend_metrics( $payload );
		$rows    = $helper->project_trend_metrics( $rows, $metrics );

		$result = [
			'rows'        => $rows,
			'granularity' => $granularity,
			'metrics'     => $metrics,
			'date_range'  => [
				'from' => $from,
				'to'   => $to,
			],
			'truncated'   => $truncated,
		];

		if ( $form_id > 0 ) {
			$result['form_id'] = $form_id;
		}

		return $result;
	}

	/**
	 * Follow-up data source: aggregated field-level stats for a date range.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload.
	 *
	 * @return array
	 */
	public function fetch_field_stats( array $payload ): array {

		$form_id = $this->get_state_builder()->resolve_form_id( $payload );

		if ( $form_id === 0 ) {
			return [];
		}

		[ $from, $to ] = $this->get_trend_helper()->derive_date_range( $payload );

		$service = wpforms()->obj( 'analytics_stats' );

		if ( ! $service ) {
			return [];
		}

		$form_data = $this->get_state_builder()->load_form_data( $form_id );

		return [
			'form_id'    => $form_id,
			'date_range' => [
				'from' => $from,
				'to'   => $to,
			],
			'fields'     => $service->get_field_stats( $form_id, $from, $to, $form_data ),
		];
	}

	/**
	 * Follow-up data source: ranked per-form aggregates from `wp_wpforms_analytics_forms`.
	 *
	 * Returns rows of `[ form_id, views, submissions, conversion_rate ]`
	 * summed across the filter's date scope, sorted by the chosen metric. Filterable on
	 * any of those metrics (HAVING for aggregates, WHERE for raw columns / dates).
	 *
	 * Invalid filters are surfaced back to the LLM via `rejected_filters`. When
	 * every filter is rejected, returns an empty rows list plus the rejection list
	 * so the model knows to retry with corrections.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload (with model's `request_data` frame).
	 *
	 * @return array
	 */
	public function fetch_forms_ranking( array $payload ): array {

		$args        = (array) ( $payload['request_data']['args'] ?? [] );
		$raw_filters = isset( $args['filters'] ) && is_array( $args['filters'] ) ? $args['filters'] : [];
		$raw_filters = $this->maybe_apply_page_state_period( $raw_filters, $payload );

		$ranking = $this->get_ranking_helper();

		$result = $ranking->get_compiler()->normalize( $raw_filters, RankingHelper::RANKING_MAX_FILTERS );

		$sort_field = $ranking->resolve_sort_field( $args );
		$sort_dir   = $ranking->resolve_sort_dir( $args );
		$limit      = $ranking->resolve_limit( $args );

		$response = [
			'rows'      => [],
			'filters'   => $result->kept,
			'order_by'  => $sort_field,
			'order'     => $sort_dir,
			'total'     => 0,
			'truncated' => false,
		];

		// All-rejected fallback: no point running a query whose only signal would
		// be misleading. Return empty data + the rejection list; the LLM can
		// retry with corrections.
		if ( $result->kept === [] && $result->rejected !== [] ) {
			$response['rejected_filters'] = $result->rejected;

			return $response;
		}

		$ctx = $ranking->compile_filters_to_ctx( $result->kept );

		[ $rows, $truncated ] = $ranking->run_ranking_query( $ctx, $sort_field, $sort_dir, $limit );

		$response['rows']      = $rows;
		$response['total']     = count( $rows );
		$response['truncated'] = $truncated;

		if ( $result->rejected !== [] ) {
			$response['rejected_filters'] = $result->rejected;
		}

		return $response;
	}

	/**
	 * Follow-up data source: ranked per-field interaction aggregates from `wp_wpforms_analytics_fields`.
	 *
	 * Two modes via `request_data.args.group_by`:
	 *
	 *   - "field" (default): one row per (form_id, field_id), enriched with label + type.
	 *   - "type"           : one row per field_type, aggregated across the portfolio.
	 *
	 * Invalid filters surface via `rejected_filters`. All-rejected → empty rows + rejection list.
	 *
	 * @since 2.0.0
	 *
	 * @param array $payload Request payload (with model's `request_data` frame).
	 *
	 * @return array
	 */
	public function fetch_fields_ranking( array $payload ): array {

		$args        = (array) ( $payload['request_data']['args'] ?? [] );
		$raw_filters = isset( $args['filters'] ) && is_array( $args['filters'] ) ? $args['filters'] : [];

		$fields  = $this->get_fields_ranking_helper();
		$ranking = $this->get_ranking_helper();

		$result = $fields->get_fields_compiler()->normalize( $raw_filters, FieldsRankingHelper::FIELDS_RANKING_MAX_FILTERS );

		$group_by   = $fields->resolve_fields_group_by( $args );
		$sort_field = $fields->resolve_fields_sort_field( $args, $group_by );
		$sort_dir   = $ranking->resolve_sort_dir( $args );
		$limit      = $fields->resolve_fields_limit( $args, $group_by );

		$response = [
			'rows'      => [],
			'filters'   => $result->kept,
			'group_by'  => $group_by,
			'order_by'  => $sort_field,
			'order'     => $sort_dir,
			'total'     => 0,
			'truncated' => false,
		];

		if ( $result->kept === [] && $result->rejected !== [] ) {
			$response['rejected_filters'] = $result->rejected;

			return $response;
		}

		$ctx = $fields->compile_fields_filters_to_ctx( $result->kept );

		// A `field_type` filter is applied in PHP (after the SQL query), so the SQL
		// LIMIT must NOT run first in field mode — it would cap the result set before
		// the type filter, returning fewer than $limit matching fields (or none).
		// Fetch unlimited in that case (the query is already ORDER BY'd by the sort
		// metric) and slice in PHP below. The fetched set is bounded by the install's
		// total tracked field count (one aggregated row per field), not by traffic, so
		// it stays small for realistic portfolios.
		$has_field_type_filter = $fields->fields_filters_have_field_type( $result->kept );
		$apply_sql_limit       = $group_by === FieldsRankingHelper::FIELDS_RANKING_GROUP_BY_FIELD && ! $has_field_type_filter;

		[ $rows, $truncated ] = $fields->run_fields_ranking_query( $ctx, $sort_field, $sort_dir, $limit, $apply_sql_limit );

		$metadata = $this->get_state_builder()->get_portfolio_field_metadata();

		$rows = $fields->apply_field_type_filter( $rows, $result->kept, $metadata );

		if ( $group_by === FieldsRankingHelper::FIELDS_RANKING_GROUP_BY_TYPE ) {
			$rows      = $fields->rollup_to_type_rows( $rows, $metadata );
			$rows      = $fields->sort_type_rows( $rows, $sort_field, $sort_dir );
			$truncated = count( $rows ) > $limit;
			$rows      = array_slice( $rows, 0, $limit );
		} else {
			// When the SQL LIMIT was skipped (field mode + field_type filter), the
			// rows are still ordered by the sort metric but not yet capped, so apply
			// the limit and recompute truncation in PHP after the type filter.
			if ( ! $apply_sql_limit ) {
				$truncated = count( $rows ) > $limit;
				$rows      = array_slice( $rows, 0, $limit );
			}

			$rows = $fields->enrich_field_rows( $rows, $metadata );
		}

		$response['rows']      = $rows;
		$response['total']     = count( $rows );
		$response['truncated'] = $truncated;

		if ( $result->rejected !== [] ) {
			$response['rejected_filters'] = $result->rejected;
		}

		return $response;
	}

	/**
	 * Default a `forms_ranking` request to the page's selected date window.
	 *
	 * `forms_ranking` is filter-driven, so without an explicit `period_date`
	 * filter the query aggregates over all available data — which silently
	 * contradicts the date range the user picked on the Analytics page. When the
	 * model omits a `period_date` filter, inject the page-state window (the same
	 * source `form_stats` / `field_stats` honour via `derive_date_range()`) as a
	 * `period_date` range so the ranking matches what the page shows. An explicit
	 * `period_date` filter from the model always wins.
	 *
	 * @since 2.0.0
	 *
	 * @param array $filters Raw filters from the model's `request_data.args`.
	 * @param array $payload Request payload (carries `pageState`).
	 *
	 * @return array
	 */
	private function maybe_apply_page_state_period( array $filters, array $payload ): array {

		foreach ( $filters as $filter ) {
			if ( ( $filter['field'] ?? '' ) === 'period_date' ) {
				return $filters;
			}
		}

		$range = $this->get_trend_helper()->page_state_date_range( $payload );

		if ( $range === null ) {
			return $filters;
		}

		[ $from, $to ] = $range;

		$filters[] = [
			'field' => 'period_date',
			'op'    => 'gte',
			'value' => $from,
		];

		$filters[] = [
			'field' => 'period_date',
			'op'    => 'lte',
			'value' => $to,
		];

		return $filters;
	}

	/**
	 * Get the fields ranking helper instance.
	 *
	 * @since 2.0.0
	 *
	 * @return FieldsRankingHelper
	 */
	private function get_fields_ranking_helper(): FieldsRankingHelper {

		if ( $this->fields_ranking_helper === null ) {
			$this->fields_ranking_helper = new FieldsRankingHelper( $this->get_ranking_helper() );
		}

		return $this->fields_ranking_helper;
	}

	/**
	 * Get the ranking helper instance.
	 *
	 * @since 2.0.0
	 *
	 * @return RankingHelper
	 */
	private function get_ranking_helper(): RankingHelper {

		if ( $this->ranking_helper === null ) {
			$this->ranking_helper = new RankingHelper();
		}

		return $this->ranking_helper;
	}

	/**
	 * Get the state builder instance.
	 *
	 * @since 2.0.0
	 *
	 * @return StateBuilder
	 */
	private function get_state_builder(): StateBuilder {

		if ( $this->state_builder === null ) {
			$this->state_builder = new StateBuilder( $this );
		}

		return $this->state_builder;
	}

	/**
	 * Default in-flight label for the analytics scope.
	 *
	 * @since 2.0.0
	 *
	 * @param array $includes Include tokens being resolved.
	 * @param array $args     Optional args from the request_data frame.
	 *
	 * @return string
	 */
	public function get_tool_call_default_label( array $includes, array $args ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		return __( 'Reading your analytics…', 'wpforms' );
	}

	/**
	 * Get the trend helper instance.
	 *
	 * @since 2.0.0
	 *
	 * @return TrendHelper
	 */
	private function get_trend_helper(): TrendHelper {

		if ( $this->trend_helper === null ) {
			$this->trend_helper = new TrendHelper();
		}

		return $this->trend_helper;
	}
}
