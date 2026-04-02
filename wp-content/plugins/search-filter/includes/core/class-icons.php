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

	/**
	 * Whether the icons have been initialized.
	 *
	 * @var bool
	 */
	private static $init = false;

	/**
	 * Array of registered icons.
	 *
	 * @var array
	 */
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

		self::$icons = array(
			// Frontend UI icons.
			'search'                     => SEARCH_FILTER_PATH . 'assets/images/svg/search.svg',
			'clear'                      => SEARCH_FILTER_PATH . 'assets/images/svg/clear.svg',
			'arrow-down'                 => SEARCH_FILTER_PATH . 'assets/images/svg/arrow-down.svg',
			'arrow-right'                => SEARCH_FILTER_PATH . 'assets/images/svg/arrow-right.svg',
			'arrow-right-double'         => SEARCH_FILTER_PATH . 'assets/images/svg/arrow-right-double.svg',
			'radio'                      => SEARCH_FILTER_PATH . 'assets/images/svg/radio.svg',
			'radio-checked'              => SEARCH_FILTER_PATH . 'assets/images/svg/radio-checked.svg',
			'checkbox'                   => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox.svg',
			'checkbox-checked'           => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox-checked.svg',
			'checkbox-mixed'             => SEARCH_FILTER_PATH . 'assets/images/svg/checkbox-mixed.svg',
			'event'                      => SEARCH_FILTER_PATH . 'assets/images/svg/event.svg',
			// Admin Integration icons.
			'integration-acf'            => SEARCH_FILTER_PATH . 'assets/images/integrations/acf.svg',
			'integration-beaverbuilder'  => SEARCH_FILTER_PATH . 'assets/images/integrations/beaverbuilder.svg',
			'integration-bricks'         => SEARCH_FILTER_PATH . 'assets/images/integrations/bricks.svg',
			'integration-divi'           => SEARCH_FILTER_PATH . 'assets/images/integrations/divi.svg',
			'integration-elementor'      => SEARCH_FILTER_PATH . 'assets/images/integrations/elementor.svg',
			'integration-generateblocks' => SEARCH_FILTER_PATH . 'assets/images/integrations/generateblocks.svg',
			'integration-polylang'       => SEARCH_FILTER_PATH . 'assets/images/integrations/polylang.svg',
			'integration-relevanssi'     => SEARCH_FILTER_PATH . 'assets/images/integrations/relevanssi.svg',
			'integration-woocommerce'    => SEARCH_FILTER_PATH . 'assets/images/integrations/woocommerce.svg',
			'integration-wordpress'      => SEARCH_FILTER_PATH . 'assets/images/integrations/wordpress.svg',
			'integration-wpbakery'       => SEARCH_FILTER_PATH . 'assets/images/integrations/wpbakery.svg',
			'integration-wpml'           => SEARCH_FILTER_PATH . 'assets/images/integrations/wpml.svg',
		);

		foreach ( self::$icons as $icon => $file ) {
			SVG_Loader::register( $icon, $file, false );
		}
	}

	/**
	 * Enqueues all registered icons.
	 *
	 * @since 3.0.0
	 */
	public static function enqueue() {
		foreach ( self::$icons as $icon_name => $path ) {
			SVG_Loader::enqueue( $icon_name );
		}
	}
}
