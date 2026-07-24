<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Scope\FormsInventory;

use WPForms\Integrations\AI\Admin\Chat\Scope\FormsInventory\FormSearcher as LiteFormSearcher;
use WPForms\Integrations\AI\Admin\Chat\Scope\FormsInventory\FormsInventory as LiteFormsInventory;

/**
 * Pro extension of the forms-inventory scope.
 *
 * Calls the Lite parent for the base row shape, then enriches each row with:
 *   - `entries_count` — from the `wp_wpforms_entries` table (always, when the table exists).
 *   - `analytics`     — views / submissions / interactions / conversion percentage,
 *                       gated on AnalyticsFeature::is_enabled().
 *
 * Both reads are bulk queries keyed on the form IDs already in the inventory.
 *
 * @since 2.0.0
 */
class FormsInventory extends LiteFormsInventory {

	/**
	 * Pro field spec — adds `entries_count` and the four analytics metrics on top of Lite.
	 *
	 * @since 2.0.0
	 */
	private const PRO_FIELDS = [
		'entries_count'          => 'numeric',
		'analytics_views'        => 'numeric',
		'analytics_submissions'  => 'numeric',
		'analytics_interactions' => 'numeric',
		'analytics_conversion'   => 'numeric',
	];

	/**
	 * Lazy-built row enricher.
	 *
	 * @since 2.0.0
	 *
	 * @var RowEnricher|null
	 */
	private $enricher;

	/**
	 * Extend the Lite field spec with Pro-only fields.
	 *
	 * Operator-by-type defaults come from `FieldType::default_ops_by_type()` —
	 * NUMERIC's full comparison family (`eq, neq, lt, lte, gt, gte`) is already
	 * declared there. Pro only needs to register the new fields here.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_field_spec(): array {

		return array_merge( parent::get_field_spec(), self::PRO_FIELDS );
	}

	/**
	 * Default in-flight label for the Pro forms inventory scope.
	 *
	 * @since 2.0.0
	 *
	 * @param array $includes Include tokens being resolved.
	 * @param array $args     Optional args from the request_data frame.
	 *
	 * @return string
	 */
	public function get_tool_call_default_label( array $includes, array $args ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Override exists to switch from the Lite text domain to the Pro one.
		return __( 'Looking up your forms…', 'wpforms' );
	}

	/**
	 * Enrich form rows with Pro-only fields.
	 *
	 * Called by Lite `FormsInventory::fetch_forms_refresh()` and
	 * `fetch_forms_search()` so both the round-1 snapshot and round-N name-search
	 * results carry the Pro enrichment.
	 *
	 * @since 2.0.0
	 *
	 * @param array $rows Form rows produced by Lite `build_rows()`.
	 *
	 * @return array
	 */
	protected function enrich_rows( array $rows ): array {

		if ( $this->enricher === null ) {
			$this->enricher = new RowEnricher();
		}

		return $this->enricher->enrich( $rows );
	}

	/**
	 * Create the Pro form searcher.
	 *
	 * @since 2.0.0
	 *
	 * @return LiteFormSearcher
	 */
	protected function create_searcher(): LiteFormSearcher {

		return new FormSearcher( [ $this, 'get_field_spec' ] );
	}
}
