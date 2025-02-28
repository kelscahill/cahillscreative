<?php
/**
 * Scripts Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles scripts.
 *
 * @since 3.0.0
 */
class Scripts extends \Search_Filter\Core\Scripts {
	/**
	 * Gets the plugins assets URL path.
	 *
	 * @return string The URL path to the assets.
	 */
	public static function get_admin_assets_url() {
		$assets_url = SEARCH_FILTER_PRO_URL . 'assets/';
		if ( defined( 'SEARCH_FILTER_PRO_ADMIN_ASSETS_URL' ) ) {
			$assets_url = SEARCH_FILTER_PRO_ADMIN_ASSETS_URL;
		}
		return $assets_url;
	}
	/**
	 * Gets the plugins frontend assets URL path.
	 *
	 * @return string The URL path to the assets.
	 */
	public static function get_frontend_assets_url() {
		$assets_url = SEARCH_FILTER_PRO_URL . 'assets/';
		if ( defined( 'SEARCH_FILTER_PRO_FRONTEND_ASSETS_URL' ) ) {
			$assets_url = SEARCH_FILTER_PRO_FRONTEND_ASSETS_URL;
		}
		return $assets_url;
	}
}
