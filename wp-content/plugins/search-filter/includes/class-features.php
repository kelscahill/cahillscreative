<?php
/**
 * Looks for `search_filter_queries` in a WP_Query (pre_get_posts), and takes over the query
 * parses url args + query settings into queries to made on our own tables
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter;

use Search_Filter\Core\Errors;
use Search_Filter\Features\Dynamic_Assets;
use Search_Filter\Features\Settings as Features_Settings;
use Search_Filter\Features\Settings_Data;
use Search_Filter\Features\Shortcodes;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main features class.
 *
 * Handles interactions with the features settings as well as the features
 * screen.
 *
 * @since 3.0.0
 */
class Features {

	/**
	 * Local features array.
	 *
	 * @since 3.0.0
	 *
	 * @var array|null
	 */
	private static $features = null;

	/**
	 * Local enabled features array.
	 *
	 * @since 3.0.0
	 *
	 * @var array|null
	 */
	private static $enabled_features = null;

	/**
	 * Reset static caches.
	 *
	 * Useful for tests to clear cached values after modifying options.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$features         = null;
		self::$enabled_features = null;
	}

	/**
	 * Init the features.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Preload the features option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );

		// Now init the features.
		self::init_features();
	}

	/**
	 * Preload the features option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		// We can't set any defaults at this stage because Settings haven't been registered yet.
		// After the first call to Options::get() below, we'll store value so preloading
		// will work after the first setup.
		$options_to_preload[] = 'features';
		return $options_to_preload;
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Features_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}
	/**
	 * Get the features.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_all() {
		// Return cached value if available.
		if ( self::$features !== null ) {
			return self::$features;
		}

		if ( ! did_action( 'search-filter/settings/features/init' ) ) {
			// Show error if we're trying to access features before the settings are initialized.
			Errors::add(
				__( 'Trying to access features before initialisation.', 'search-filter' )
			);
			return array();
		}

		self::init_option();

		return self::$features ?? array();
	}

	/**
	 * Initialize the features option.
	 *
	 * @since 3.2.0
	 */
	private static function init_option() {

		$defaults        = Features_Settings::get_defaults();
		$features_option = Options::get( 'features', $defaults, true );
		$features        = $defaults;
		if ( $features_option ) {
			$features = wp_parse_args( $features_option, $defaults );
		}

		// Cache the result.
		self::$features = $features;

		// Setup the enabled features array.
		$enabled_features = array();
		foreach ( $features as $feature => $enabled ) {
			if ( $enabled ) {
				$enabled_features[] = $feature;
			}
		}

		// Cache the result.
		self::$enabled_features = $enabled_features;
	}

	/**
	 * Get the enabled features.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	private static function get_enabled() {
		// Return cached value if available.
		if ( self::$enabled_features === null ) {
			self::init_option();
		}

		return self::$enabled_features;
	}
	/**
	 * Check if a feature is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param string $feature The feature to check.
	 *
	 * @return bool True if the feature is enabled, false if not.
	 */
	public static function is_enabled( $feature ) {
		$features = self::get_enabled();
		return in_array( $feature, $features, true );
	}

	/**
	 * Init the features.
	 *
	 * @since 3.0.0
	 */
	private static function init_features() {
		Shortcodes::init();
		Dynamic_Assets::init();
	}

	/**
	 * Get the value of a feature sub setting.
	 *
	 * @param string $feature_group The feature group.
	 * @param string $feature_name  The feature name.
	 *
	 * @return string|null The value of the feature sub setting.
	 */
	public static function get_setting_value( $feature_group, $feature_name ) {

		$settings_class = Settings::get_register_class( $feature_group );

		if ( ! $settings_class ) {
			return null;
		}

		// Get the defaults for the group.
		$feature_group_defaults = call_user_func( array( $settings_class, 'get_defaults' ) );

		// Get the values for the group, and store/init the defaults if they don't exist yet.
		$feature_group_values = Options::get( $feature_group, $feature_group_defaults, true );

		$setting_value = null;
		if ( isset( $feature_group_values[ $feature_name ] ) ) {
			$setting_value = $feature_group_values[ $feature_name ];
		}

		return $setting_value;
	}


	/**
	 * Update the value of a feature sub setting.
	 *
	 * @param array $updated_features The updated feature array.
	 */
	public static function update_all( $updated_features ) {

		$features = self::get_all();

		// Merge the updated features with the existing ones.
		$updated_features = wp_parse_args( $updated_features, $features );

		// Save the data as in the options table.
		Options::update( 'features', $updated_features );

		// Update local cache.
		self::$features = $updated_features;

		$enabled_features = array();
		// Update the enabled features cache.
		foreach ( $updated_features as $feature => $enabled ) {
			if ( $enabled ) {
				$enabled_features[] = $feature;
			}
		}

		self::$enabled_features = $enabled_features;
	}
}
