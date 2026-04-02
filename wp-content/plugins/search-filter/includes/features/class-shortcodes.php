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
use Search_Filter\Features\Shortcodes\Shortcode_Parser;
use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for managing shortcode features.
 *
 * @since 3.0.0
 */
class Shortcodes {
	/**
	 * Initialize the shortcodes feature.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Hook after features are ready.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'init_preload_assets' ), 10 );
	}

	/**
	 * Initialize the preload assets functionality.
	 *
	 * Hooks into the dynamic assets feature to preload shortcode assets.
	 *
	 * @since 3.0.0
	 */
	public static function init_preload_assets() {

		// Check to make sure the shortcodes feature is enabled.
		if ( ! Features::is_enabled( 'shortcodes' ) ) {
			return;
		}

		add_action( 'search-filter/features/dynamic-assets/preload_assets', array( __CLASS__, 'preload_assets' ), 10, 1 );
	}

	/**
	 * Preload assets for shortcodes found in the post content.
	 *
	 * Parses the content for shortcodes and enqueues the necessary assets.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_content The post content to parse for shortcodes.
	 */
	public static function preload_assets( $post_content ) {
		// TODO - parse the post content for our shortcodes and queue up any
		// assets that might need to be loaded.
		$field_shortcodes = Shortcode_Parser::extract_fields( $post_content );
		foreach ( $field_shortcodes as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			$field->enqueue_assets();
		}
	}
}
