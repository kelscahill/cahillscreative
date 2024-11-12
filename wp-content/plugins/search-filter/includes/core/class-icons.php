<?php
/**
 * Class to handle the loading of SVG files.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles loading of icon data and processing them via SVG_Loader.
 */
class Icons {

	private static $init  = false;
	private static $icons = array();

	/**
	 * Loading registers and enquques the icons.
	 *
	 * @return void
	 */
	public static function load() {
		if ( ! self::$init ) {
			self::register();
			self::enqueue();
		}
		self::$init = true;
	}
	/**
	 * Register icon file paths
	 *
	 * @return void
	 */
	public static function register() {
		// TODO - this should be moved into its own class.
		self::$icons = array(
			'search'           => SEARCH_FILTER_PATH . 'assets/images/svg/search.svg',
			'clear'            => SEARCH_FILTER_PATH . 'assets/images/svg/clear.svg',
			'arrow-down'       => SEARCH_FILTER_PATH . 'assets/images/svg/arrow-down.svg',
			'radio'            => SEARCH_FILTER_PATH . 'assets/images/svg/radio.svg',
			'radio-checked'    => SEARCH_FILTER_PATH . 'assets/images/svg/radio-checked.svg',
			'checkbox'         => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox.svg',
			'checkbox-checked' => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox-checked.svg',
			'checkbox-mixed'   => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox-mixed-3.svg',
			'event'            => SEARCH_FILTER_PATH . 'assets/images/svg/event.svg',
		);

		foreach ( self::$icons as $icon => $file ) {
			SVG_Loader::register( $icon, $file );
		}
	}
	public static function enqueue() {
		foreach ( self::$icons as $icon_name => $path ) {
			SVG_Loader::enqueue( $icon_name );
		}
	}
}
