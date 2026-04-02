<?php
/**
 * Upgrade routines for version 3.0.5
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.0.5
 */
class Upgrade_3_0_5 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.0.5
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// When we upgrade to 3.0.5, try to invalidate all the update caches manually.
		// Because the Upgrade Manager doesn't exist yet in the extension plugins, we
		// should try to do this manually for this upgrade only.
		// This should show any related updates asap.
		$plugins_slugs = array( 'search-filter-pro', 'search-filter', 'search-filter-bb', 'search-filter-elementor' );
		foreach ( $plugins_slugs as $plugin_slug ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Matches WordPress core update cache key generation.
			$cache_key = 'edd_sl_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Matches WordPress core update cache key generation.
			$api_request_cache_key = 'edd_api_request_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			delete_option( $cache_key );
			delete_option( $api_request_cache_key );
		}

		return Upgrade_Result::success();
	}
}
