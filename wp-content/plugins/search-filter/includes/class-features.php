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

use Search_Filter\Features\Settings as Features_Settings;
use Search_Filter\Features\Settings_Data;

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
	 * Init the features.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );
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
	public static function get_features() {
		$defaults        = Features_Settings::get_defaults();
		$features_option = Options::get_option_value( 'features' );
		$features        = $defaults;
		if ( $features_option ) {
			$features = wp_parse_args( $features_option, $defaults );
		}
		return $features;
	}

	/**
	 * Get the enabled features.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_enabled_features() {
		$features         = self::get_features();
		$enabled_features = array();
		foreach ( $features as $feature => $enabled ) {
			if ( $enabled ) {
				$enabled_features[] = $feature;
			}
		}
		return $enabled_features;
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
		$features = self::get_enabled_features();
		return in_array( $feature, $features, true );
	}
}
