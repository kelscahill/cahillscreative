<?php
/**
 * The main components class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\Component_Loader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles components initialisation.
 */
class Components {

	/**
	 * Initialize components.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		$components = array(
			'range' => array(
				'name'   => 'range',
				'script' => array(
					'src'        => SEARCH_FILTER_PRO_URL . 'assets/frontend/components/range.js',
					'asset_path' => SEARCH_FILTER_PRO_PATH . 'assets/frontend/components/range.asset.php',
				),
				'style'  => array(
					'src' => SEARCH_FILTER_PRO_URL . 'assets/frontend/components/range.css',
				),
			),

			/*
			'date-picker' => array(
				'name'   => 'date-picker',
				'script' => array(
					'src'        => SEARCH_FILTER_PRO_URL . 'assets/frontend/components/date-picker.js',
					'asset_path' => SEARCH_FILTER_PRO_PATH . 'assets/frontend/components/date-picker.asset.php',
				),
				'style'  => array(
					'src'        => SEARCH_FILTER_PRO_URL . 'assets/frontend/components/date-picker.css',
				),
			),
			*/
		);
		foreach ( $components as $component_name => $component_config ) {
			Component_Loader::register( $component_name, $component_config );
		}
	}
}
