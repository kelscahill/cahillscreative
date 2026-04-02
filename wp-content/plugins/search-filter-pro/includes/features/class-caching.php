<?php
/**
 * Sets up the caching feature settings.
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro/Features
 */

namespace Search_Filter_Pro\Features;

use Search_Filter\Features;
use Search_Filter_Pro\Features\Caching\Settings;
use Search_Filter_Pro\Features\Caching\Settings_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages caching settings for Search & Filter Pro.
 *
 * @since 3.2.0
 */
class Caching {

	/**
	 * Cached APCu enabled state.
	 *
	 * @since 3.2.0
	 *
	 * @var bool|null
	 */
	private static $apcu_enabled = null;

	/**
	 * Initialize the caching feature.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		// Setup the caching feature once features are initialized.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'setup' ), 10 );

		// Preload the option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );
	}

	/**
	 * Preload the caching option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array The updated options array.
	 */
	public static function preload_option( $options_to_preload ) {
		$options_to_preload[] = 'caching';
		return $options_to_preload;
	}

	/**
	 * Setup the caching feature.
	 *
	 * @since 3.2.0
	 */
	public static function setup() {
		add_filter( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );

		// Filter enableApcu setting to disable when APCu unavailable.
		add_filter( 'search-filter/settings/caching/setting/enableApcu', array( __CLASS__, 'filter_apcu_setting' ) );

		Settings::init( Settings_Data::get(), Settings_Data::get_groups() );

		// Hook the database caching filter for backwards compatibility.
		add_filter( 'search-filter-pro/indexer/query/enable_caching', array( __CLASS__, 'filter_enable_caching' ), 5 );

		// Hook the APCu caching filter.
		add_filter( 'search-filter-pro/cache/use_apcu', array( __CLASS__, 'filter_use_apcu' ), 10 );
	}

	/**
	 * Add the preload API paths.
	 *
	 * @since 3.2.0
	 *
	 * @param array $paths The paths to add.
	 * @return array The paths to add.
	 */
	public static function add_preload_api_paths( $paths ) {
		$paths[] = '/search-filter/v1/admin/settings?section=caching';
		$paths[] = '/search-filter/v1/settings?section=caching';
		return $paths;
	}

	/**
	 * Check if APCu caching is enabled.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Whether APCu caching is enabled.
	 */
	public static function is_apcu_enabled() {
		if ( self::$apcu_enabled === null ) {
			$setting_value      = Features::get_setting_value( 'caching', 'enableApcu' );
			self::$apcu_enabled = $setting_value === 'yes';
		}
		return self::$apcu_enabled;
	}

	/**
	 * Filter for the enable_caching hook.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Whether caching should be enabled.
	 */
	public static function filter_enable_caching() {
		$setting_value = Features::get_setting_value( 'caching', 'enableCaching' );
		return $setting_value === 'yes';
	}

	/**
	 * Filter for the APCu caching hook.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $use_apcu Whether to use APCu.
	 * @return bool Whether to use APCu.
	 */
	public static function filter_use_apcu( $use_apcu ) {
		if ( ! self::is_apcu_enabled() ) {
			return false;
		}
		return $use_apcu;
	}

	/**
	 * Check if APCu PHP extension is available.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Whether APCu is available.
	 */
	public static function is_apcu_available() {
		return function_exists( 'apcu_fetch' )
			&& function_exists( 'apcu_enabled' )
			&& apcu_enabled();
	}

	/**
	 * Filter the enableApcu setting to disable when APCu unavailable.
	 *
	 * @since 3.2.0
	 *
	 * @param array $setting The setting data.
	 * @return array Modified setting data.
	 */
	public static function filter_apcu_setting( $setting ) {
		if ( ! self::is_apcu_available() ) {
			$setting['disabled'] = true;
			$setting['help']    .= ' ' . __( 'Not available on this server.', 'search-filter-pro' );
			$setting['default']  = 'no';
		}
		return $setting;
	}

	/**
	 * Reset cached states (for testing).
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$apcu_enabled = null;
	}
}
