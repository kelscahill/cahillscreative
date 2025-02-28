<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles compatibility checks and notices.
 */
class Compatibility {

	public static function init() {
		self::litespeed();
	}
	/**
	 * Add a notice for WP Engine users if the long query governor is enabled.
	 *
	 * @return void
	 */
	public static function litespeed() {

		if ( ! defined( 'LSCWP_V' ) ) {
			return;
		}

		// Disable caching for Search & Filter REST API requests.
		add_action( 'search-filter/rest-api/request', array( __CLASS__, 'disable_litespeed_cache' ) );
	}

	/**
	 * Disable LiteSpeed cache for Search & Filter Pro REST API requests.
	 */
	public static function disable_litespeed_cache() {
		do_action( 'litespeed_control_set_nocache', 'Search & Filter Indexer Update' );
	}
}
