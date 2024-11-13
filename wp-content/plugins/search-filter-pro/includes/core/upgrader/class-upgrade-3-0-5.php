<?php

namespace Search_Filter_Pro\Core\Upgrader;

class Upgrade_3_0_5 {

	public static function upgrade() {
		// When we upgrade to 3.0.5, try to invalidate all the update caches manually.
		// Because the Upgrade Manager doesn't exist yet in the extension plugins, we
		// should try to do this manually for this upgrade only.
		// This should show any related updates asap.
		$plugins_slugs = array( 'search-filter-pro', 'search-filter', 'search-filter-bb', 'search-filter-elementor' );
		foreach ( $plugins_slugs as $plugin_slug ) {
			$cache_key             = 'edd_sl_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			$api_request_cache_key = 'edd_api_request_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			delete_option( $cache_key );
			delete_option( $api_request_cache_key );
		}
	}
}
