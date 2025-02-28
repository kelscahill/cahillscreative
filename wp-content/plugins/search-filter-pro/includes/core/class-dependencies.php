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

namespace Search_Filter_Pro\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependency checks for the foundation free version of the plugin.
 *
 * @author
 * @version 3.0.0
 */
class Dependencies {

	/**
	 * Check if the parent plugin is required version.
	 *
	 * @since    3.0.0
	 *
	 * @return   boolean
	 */
	public static function is_search_filter_required_version() {
		if ( ! self::is_search_filter_enabled() ) {
			return false;
		}
		// We use this hook on plugins_loaded, so we can't use get_plugins to find the version
		// of an inactive plugin.
		if ( version_compare( SEARCH_FILTER_VERSION, SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION, '<' ) ) {
			return false;
		}
		return true;
	}
	/**
	 * Check if the parent plugin is required version.
	 *
	 * @since    3.0.0
	 *
	 * @return   boolean
	 */
	public static function is_search_filter_recommended_version() {
		if ( ! self::is_search_filter_enabled() ) {
			return false;
		}
		// We use this hook on plugins_loaded, so we can't use get_plugins to find the version
		// of an inactive plugin.
		if ( version_compare( SEARCH_FILTER_VERSION, SEARCH_FILTER_PRO_RECOMMENDED_BASE_VERSION, '<' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the plugin is installed.
	 *
	 * @since    3.0.0
	 *
	 * @return   boolean
	 */
	public static function is_search_filter_installed() {
		$plugin_file = 'search-filter/search-filter.php';
		return self::is_plugin_installed( $plugin_file );
	}

	/**
	 * Check if the plugin is enabled.
	 *
	 * @since    3.0.0
	 *
	 * @return   boolean
	 */
	public static function is_search_filter_enabled() {
		if ( ! defined( 'SEARCH_FILTER_VERSION' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if a plugin is installed.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $plugin_path    The path to the plugin.
	 * @return   boolean
	 */
	public static function is_plugin_installed( $plugin_path ) {
		if ( ! function_exists( '\get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = \get_plugins();
		return isset( $plugins[ $plugin_path ] );
	}

	/**
	 * Get the version of the plugin.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public static function get_base_plugin_version() {
		if ( ! function_exists( '\get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		// Use get_plugins to get the plugin version (so we can get the version even if it's not activated).
		$plugins     = \get_plugins();
		$plugin_path = 'search-filter/search-filter.php';
		if ( ! isset( $plugins[ $plugin_path ] ) ) {
			return false;
		}
		$search_filter_version = $plugins[ $plugin_path ]['Version'];
		return $search_filter_version;
	}

	/**
	 * Check if the base plugin is a legacy version.
	 *
	 * @since    3.0.0
	 *
	 * @return   boolean
	 */
	public static function has_legacy_base_plugin() {
		$base_plugin_version = self::get_base_plugin_version();
		if ( $base_plugin_version === false ) {
			return false;
		}
		return version_compare( $base_plugin_version, '2.0.0', '<' );
	}
	/**
	 * Ensure we can't disable the free plugin, while the pro plugin is active.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 9, 2 );
		add_filter( 'network_admin_plugin_action_links', array( __CLASS__, 'network_plugin_action_links' ), 10, 2 );
	}

	/**
	 * Remove the disable link from the plugin row and add a notice instead.
	 *
	 * @param array  $actions The plugin actions.
	 * @param string $plugin_file The path to the plugin file.
	 * @return array
	 */
	public static function plugin_action_links( $actions, $plugin_file ) {

		if ( ! self::is_search_filter_installed() || ! self::is_search_filter_enabled() ) {
			return $actions;
		}

		if ( $plugin_file === 'search-filter/search-filter.php' ) {
			return self::modify_base_plugin_links( $actions );
		}

		if ( $plugin_file === 'search-filter-pro/search-filter-pro.php' ) {
			$actions['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=search-filter' ) ) . '">' . esc_html__( 'Settings', 'search-filter-pro' ) . '</a>';
		}

		return $actions;
	}
	/**
	 * Remove the disable link from the plugin row and add a notice instead.
	 *
	 * @param array  $actions The plugin actions.
	 * @param string $plugin_file The path to the plugin file.
	 * @return array
	 */
	public static function network_plugin_action_links( $actions, $plugin_file ) {

		if ( ! self::is_search_filter_installed() || ! self::is_search_filter_enabled() ) {
			return $actions;
		}

		if ( $plugin_file === 'search-filter/search-filter.php' ) {
			return self::modify_base_plugin_links( $actions );
		}

		return $actions;
	}

	private static function modify_base_plugin_links( $actions ) {
		if ( isset( $actions['deactivate'] ) ) {
			unset( $actions['deactivate'] );
			$actions['disable_notice'] = __( 'Required by Search & Filter Pro', 'search-filter-pro' );
		}

		return $actions;
	}

}
