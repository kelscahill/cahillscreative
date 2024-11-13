<?php
/**
 * Update the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions related to checking if other plugins are installed and enabled.
 *
 * @author
 * @version 3.0.0
 */
class Dependants {

	public static function is_search_filter_pro_installed() {
		$plugin_file = 'search-filter-pro/search-filter-pro.php';
		return self::is_plugin_installed( $plugin_file );
	}

	public static function is_search_filter_pro_enabled() {
		if ( ! defined( 'SEARCH_FILTER_PRO_VERSION' ) ) {
			return false;
		}

		return true;
	}
	public static function is_search_filter_pro_requirements_met() {
		if ( ! defined( 'SEARCH_FILTER_PRO_VERSION' ) && ! defined( 'SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION' ) ) {
			return false;
		}

		// Check if the min version was met.
		if ( version_compare( SEARCH_FILTER_VERSION, SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION, '<' ) ) {
			return false;
		}
		return true;
	}
	public static function is_plugin_installed( $plugin_path ) {
		if ( ! function_exists( '\get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = \get_plugins();
		return isset( $plugins[ $plugin_path ] );
	}

	/**
	 * Check if a plugin is enabled.
	 *
	 * @param string $plugin_path The path to the plugin.
	 * @return bool
	 */
	public static function is_plugin_enabled( $plugin_path ) {
		return is_plugin_active( $plugin_path );
	}

	/**
	 * Enable a plugin.
	 *
	 * @param string $plugin_path The path to the plugin.
	 * @return void
	 */
	public static function enable_plugin( $plugin_path ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		\activate_plugin( $plugin_path );
	}

	/**
	 * Disable a plugin.
	 *
	 * @param string $plugin_path The path to the plugin.
	 * @return void
	 */
	public static function disable_plugin( $plugin_path ) {
		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			return;
		}
		\deactivate_plugins( $plugin_path );
	}

	/**
	 * Enable the Search Filter Pro plugin.
	 */
	public static function enable_search_filter_pro() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( self::is_search_filter_pro_installed() && ! self::is_search_filter_pro_enabled() ) {
			\activate_plugin( 'search-filter-pro/search-filter-pro.php' );
		}
	}
}
