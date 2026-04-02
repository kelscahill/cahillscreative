<?php
/**
 * Sets up the support for the beta features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter_Pro\Features;

use Search_Filter_Pro\Features\Advanced\Settings;
use Search_Filter_Pro\Features\Advanced\Settings_Data;
use Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages beta features for Search & Filter Pro.
 *
 * @since 3.0.0
 */
class Advanced {

	/**
	 * Initialize beta features.
	 *
	 * @since 3.0.0
	 */
	/**
	 * Initialize the advanced features.
	 */
	public static function init() {
		// Setup the advanced features once features are initialized.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'setup' ), 10 );

		// Preload the option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );
	}

	/**
	 * Preload the advanced features option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array The updated options array.
	 */
	public static function preload_option( $options_to_preload ) {
		// Preload the advanced features option.
		$options_to_preload[] = 'advanced-features';
		return $options_to_preload;
	}

	/**
	 * Setup the advanced features.
	 */
	public static function setup() {
		if ( ! Features::is_enabled( 'advancedFeatures' ) ) {
			return;
		}

		Settings::init( Settings_Data::get(), Settings_Data::get_groups() );

		add_filter( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );

		// Setup the default prefix.
		add_filter( 'search-filter/frontend/data', array( __CLASS__, 'add_frontend_url_prefix' ), 2 );
		add_filter( 'search-filter/fields/field/url_prefix', array( __CLASS__, 'field_url_prefix' ), 2 );

		// Enable the use of a nonce for autocomplete requests.
		add_filter( 'search-filter/fields/search/autocomplete/use_nonce', array( __CLASS__, 'use_autocomplete_nonce' ), 2 );

		// Enable beta version release support.
		add_filter( 'search-filter-pro/core/update_manager/config', array( __CLASS__, 'add_beta_version_support' ), 10, 2 );
	}

	/**
	 * Enable beta version flag.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $config The plugins updater config.
	 * @param string $updater_name The name of the plugin.
	 *
	 * @return array The updated plugin config.
	 */
	public static function add_beta_version_support( $config, $updater_name ) {

		// Check if feature is enabled.
		$use_nonce_setting = Features::get_setting_value( 'advanced-features', 'subscribeToBetaVersions' );
		if ( $use_nonce_setting !== 'yes' ) {
			return $config;
		}

		// Only add beta version flag to core plugins.
		$core_plugins = array(
			'search-filter-pro/search-filter-pro.php',
			'search-filter/search-filter.php',
		);

		if ( ! in_array( $updater_name, $core_plugins, true ) ) {
			return $config;
		}

		// Enable beta version checking.
		$config['beta'] = true;

		return $config;
	}
	/**
	 * Filters the use of the autocomplete nonce.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $use_nonce Whether to use the autocomplete nonce.
	 * @return bool The updated value for using the autocomplete nonce.
	 */
	public static function use_autocomplete_nonce( $use_nonce ) {
		$use_nonce_setting = Features::get_setting_value( 'advanced-features', 'useAutocompleteNonce' );

		if ( $use_nonce_setting === 'yes' ) {
			return true;
		}

		return $use_nonce;
	}
	/**
	 * Add the url prefix to the frontend JS data.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data The assoc array of frontend data.
	 * @return array The updated frontend data.
	 */
	public static function add_frontend_url_prefix( $data ) {
		$data['urlPrefix'] = Features::get_setting_value( 'advanced-features', 'urlPrefix' );
		return $data;
	}

	/**
	 * Add the preload API paths.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $paths    The paths to add.
	 * @return   array    The paths to add.
	 */
	public static function add_preload_api_paths( $paths ) {
		$paths[] = '/search-filter/v1/admin/settings?section=advanced-features';
		$paths[] = '/search-filter/v1/settings?section=advanced-features';
		return $paths;
	}

	/**
	 * Add the url prefix to the field class.
	 *
	 * Used when reading data from the url.
	 *
	 * @since 3.2.0
	 *
	 * @return string The user selected url prefix setting.
	 */
	public static function field_url_prefix() {
		return Features::get_setting_value( 'advanced-features', 'urlPrefix' );
	}
}
