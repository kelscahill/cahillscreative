<?php

namespace WPForms\Pro\Frontend;

use WPForms\Analytics\Analytics as AnalyticsGate;
use WPForms\Frontend\Analytics as BaseAnalytics;
use WPForms\Pro\Analytics\Analytics as ProAnalytics;

/**
 * Form Analytics frontend script enqueue (Pro).
 *
 * Extends the Lite frontend by enqueueing the Pro analytics extension
 * script with wpforms-analytics as a dependency.
 *
 * @since 2.0.0
 */
class Analytics extends BaseAnalytics {

	/**
	 * Hooks.
	 *
	 * @since 2.0.0
	 */
	protected function hooks(): void {

		parent::hooks();

		add_filter( 'wpforms_field_properties', [ $this, 'field_properties' ], 10, 3 );
	}

	/**
	 * Field properties.
	 *
	 * @since 2.0.0
	 *
	 * @param array|mixed $properties Field properties.
	 * @param array       $field      Current field specific data.
	 * @param array       $form_data  Prepared form data/settings.
	 *
	 * @return array Modified field properties.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function field_properties( $properties, array $field, array $form_data ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$properties = (array) $properties;

		$properties['container']['data']['field-type'] = $field['type'] ?? '';

		return $properties;
	}

	/**
	 * Enqueue frontend analytics assets.
	 *
	 * @since 2.0.0
	 *
	 * @param array $forms List of forms on the page.
	 */
	public function enqueue_footer_assets( array $forms ): void {

		if ( empty( $forms ) ) {
			return;
		}

		// Skip site staff before calling parent — otherwise the parent guard
		// would skip the base script while this method still enqueued the Pro
		// script against a now-missing wpforms-analytics dependency.
		if ( ! AnalyticsGate::should_track_user() ) {
			return;
		}

		parent::enqueue_footer_assets( $forms );

		if ( ! ProAnalytics::is_allowed() ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-analytics-pro',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/analytics-pro{$min}.js",
			[ 'wpforms-analytics' ],
			WPFORMS_VERSION,
			true
		);
	}
}
