<?php

namespace WPForms\Pro\Analytics;

/**
 * Pro Analytics license gate.
 *
 * Provides a static check for whether the current license tier
 * qualifies for the full Analytics experience (admin UI, Pro tracking).
 *
 * @since 2.0.0
 */
class Analytics {

	/**
	 * Whether the current license tier includes full Analytics.
	 *
	 * Returns true for Pro, Elite, Agency, and Ultimate tiers.
	 * Basic and Plus tiers get Lite-level tracking only.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public static function is_allowed(): bool {

		return in_array( wpforms_get_license_type(), [ 'pro', 'elite', 'agency', 'ultimate' ], true );
	}
}
