<?php

namespace WPForms\Pro\Integrations\AI\Admin\Chat\Surface;

use WPForms\Integrations\AI\Admin\Chat\Scope\WPFormsGeneral;
use WPForms\Integrations\AI\Admin\Chat\Scope\FormsInventory\FormsInventory;
use WPForms\Integrations\AI\Admin\Chat\SurfaceBase;
use WPForms\Pro\Integrations\AI\Admin\Chat\Scope\Analytics\Analytics;

/**
 * Surface: Analytics admin page (`?page=wpforms-analytics`).
 *
 * @since 2.0.0
 */
class AnalyticsPage extends SurfaceBase {

	/**
	 * Surface slug — matches the `page` query arg for the Analytics admin screen.
	 *
	 * @since 2.0.0
	 */
	public const SLUG = 'wpforms-analytics';

	/**
	 * Default lookback window (days) when no `date_range` is provided.
	 *
	 * Mirrors `Scope\Analytics::DEFAULT_LOOKBACK_DAYS` so the page-state and
	 * the scope agree on the fallback window.
	 *
	 * @since 2.0.0
	 */
	private const DEFAULT_LOOKBACK_DAYS = 30;

	/**
	 * Capability required to use this surface.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_capability(): string {

		return 'view_forms';
	}

	/**
	 * Default scopes active on this surface.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_default_scopes(): array {

		return [
			WPFormsGeneral::SLUG,
			FormsInventory::SLUG,
			Analytics::SLUG,
		];
	}

	/**
	 * Whether the Analytics-page chat surface is active.
	 *
	 * Enabled by default; sites can opt out via the filter below.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {

		/**
		 * Filters whether the Analytics-page chat surface is active.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enabled Whether the surface is enabled.
		 */
		return (bool) apply_filters( 'wpforms_pro_integrations_ai_chat_analytics_surface_enabled', true ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Path to the Analytics surface JS module.
	 *
	 * Surface modules live under `assets/pro/js/integrations/ai/chat/modules/`;
	 * the chat element resolves the path relative to its own JS directory.
	 *
	 * @since 2.0.0
	 *
	 * @return string The surface module path.
	 */
	public function get_js_module(): ?string {

		$min = wpforms_get_min_suffix();

		return "../../../../pro/js/integrations/ai/chat/modules/surface-wpforms-analytics$min.js";
	}

	/**
	 * Surface-owned page state — focused form and selected date range.
	 *
	 * Translates the JS-side `pageState.analytics.{form_id,date_range}` into
	 * the `context.page_state` block the middleware sees. The scope's
	 * `resolve_form_id()` / `derive_date_range()` already read the same JS
	 * payload, but the middleware needs the values surfaced explicitly so
	 * its surface fragment can frame the page context.
	 *
	 * @since 2.0.0
	 *
	 * @param array $surface Active surface config.
	 * @param array $request Sanitized request payload.
	 *
	 * @return array
	 */
	protected function build_page_state( array $surface, array $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$page_state = (array) ( $request['pageState'] ?? [] );
		$analytics  = (array) ( $page_state['analytics'] ?? [] );

		$form_id       = (int) ( $analytics['form_id'] ?? 0 );
		[ $from, $to ] = $this->resolve_page_state_dates( $analytics );

		return [
			'analytics' => [
				'form_id'    => $form_id,
				'date_range' => [
					'from' => $from,
					'to'   => $to,
				],
			],
		];
	}

	/**
	 * Resolve the page-state `[from, to]` window from the JS payload.
	 *
	 * Sanitizes the supplied range and falls back to a trailing
	 * DEFAULT_LOOKBACK_DAYS window when either end is missing, so the middleware
	 * always sees an explicit `date_range` and the scope's per-tool
	 * `derive_date_range()` agrees with the page context.
	 *
	 * @since 2.0.0
	 *
	 * @param array $analytics The `pageState.analytics` payload.
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function resolve_page_state_dates( array $analytics ): array {

		$date_range = (array) ( $analytics['date_range'] ?? [] );

		$from = isset( $date_range['from'] ) ? sanitize_text_field( (string) $date_range['from'] ) : '';
		$to   = isset( $date_range['to'] ) ? sanitize_text_field( (string) $date_range['to'] ) : '';

		// Fall back to a trailing DEFAULT_LOOKBACK_DAYS window when the JS
		// payload did not include a usable date range.
		if ( $from === '' || $to === '' ) {
			return $this->default_date_range();
		}

		return [ $from, $to ];
	}

	/**
	 * Build the trailing-DEFAULT_LOOKBACK_DAYS window ending today.
	 *
	 * @since 2.0.0
	 *
	 * @return array Tuple [ from, to ] as 'Y-m-d' strings.
	 */
	private function default_date_range(): array {

		$to_date   = (string) current_time( 'Y-m-d' );
		$to_ts     = strtotime( $to_date );
		$from_ts   = $to_ts ? strtotime( '-' . self::DEFAULT_LOOKBACK_DAYS . ' days', $to_ts ) : false;
		$from_date = $from_ts ? gmdate( 'Y-m-d', $from_ts ) : $to_date;

		return [ $from_date, $to_date ];
	}
}
