<?php
/**
 * Sets up the support for the shortcode features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter\Features;

use Search_Filter\Features;
use Search_Filter\Features\Dynamic_Assets\Settings_Data;
use Search_Filter\Features\Dynamic_Assets\Settings as Dynamic_Assets_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing dynamic asset loading features.
 *
 * @since 3.0.0
 */
class Dynamic_Assets {
	/**
	 * Initialize the dynamic assets feature.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Load the sub settings.
		Dynamic_Assets_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );

		// Hook after features are ready.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'init_preload_assets' ), 10 );
	}

	/**
	 * Initialize the preload assets functionality.
	 *
	 * Hooks into WordPress to preload assets if the dynamic asset loading feature is enabled.
	 *
	 * @since 3.0.0
	 */
	public static function init_preload_assets() {

		// Check to make sure the Dynamic Assets feature is enabled.
		if ( ! Features::is_enabled( 'dynamicAssetLoading' ) ) {
			return;
		}

		if ( Features::get_setting_value( 'dynamic-assets', 'dynamicAssetLoadingPreload' ) === 'yes' ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'preload_assets' ), 100 );
		}
	}

	/**
	 * Ideally before a page loads, we'd try to figure out which fields are loaded
	 * and preload (enqueue) the scripts & styles for them, as its better to at least
	 * have the styles loaded in the head.
	 */
	public static function preload_assets() {

		if ( ! is_singular() ) {
			return;
		}

		// If `the_content` has already been run, we don't need to do anything.
		if ( did_action( 'the_content' ) ) {
			return;
		}

		$post_id      = get_the_ID();
		$post_content = get_post_field( 'post_content', $post_id );
		if ( empty( $post_content ) ) {
			return;
		}

		// Now try to parse the content for our shortcodes or blocks and queue up any
		// assets that might need to be loaded.
		do_action( 'search-filter/features/dynamic-assets/preload_assets', $post_content );
	}
}
