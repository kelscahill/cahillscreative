<?php

namespace WPForms\Pro\SetupWizard;

use WPForms\Helpers\Transient;
use WPForms\SetupWizard\RestApi as BaseRestApi;

/**
 * Setup Wizard REST API (Pro).
 *
 * Drops the tier-keyed addon caches when a license activation changes the
 * license tier, exactly as the existing Pro `WPForms_License::clear_cache()`
 * handles on a normal key change. The clear runs only when the tier actually
 * changed, so a same-tier re-activation never forces a needless license-API
 * round-trip on the next install.
 *
 * @since 2.0.0
 */
class RestApi extends BaseRestApi {

	/**
	 * Validate the key, then drop the tier-keyed addon caches on a tier change.
	 *
	 * @since 2.0.0
	 *
	 * @param string $key License key.
	 *
	 * @return array|\WP_Error
	 */
	protected function activate_license( string $key ) {

		$previous = (array) get_option( 'wpforms_license', [] );
		$response = parent::activate_license( $key );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$current = (array) get_option( 'wpforms_license', [] );

		if ( (string) ( $previous['type'] ?? '' ) !== (string) ( $current['type'] ?? '' ) ) {
			$this->invalidate_tier_caches();
		}

		return $response;
	}

	/**
	 * Invalidate the license-tier-keyed addon caches.
	 *
	 * The addon download URLs the installer resolves are specific to the license
	 * tier, so the `addons` and `addons_urls` transients hold URLs for the
	 * previous tier after a change; the WordPress plugins cache is flushed for the
	 * same reason. The shared addons feed is identical across tiers and is left
	 * untouched.
	 *
	 * @since 2.0.0
	 */
	private function invalidate_tier_caches(): void {

		Transient::delete( 'addons' );
		Transient::delete( 'addons_urls' );

		wp_clean_plugins_cache();
	}
}
