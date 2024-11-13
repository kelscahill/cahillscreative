<?php
/**
 * The main class for initialising integrations.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter_Pro\Integrations\Acf;
use Search_Filter_Pro\Integrations\Beaver_Builder;
use Search_Filter_Pro\Integrations\Elementor;
use Search_Filter_Pro\Integrations\Gutenberg;
use Search_Filter_Pro\Integrations\Relevanssi;
use Search_Filter_Pro\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads all 3rd party integrations
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/includes
 */
class Integrations {

	/**
	 * Main entry point for the integrations.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// TODO check this is loading after our database is init.
		add_action( 'search-filter/integrations/init', array( __CLASS__, 'init_integrations' ) );
	}

	/**
	 * Init the integration classes.
	 *
	 * @since    3.0.0
	 */
	public static function init_integrations() {
		Gutenberg::init();
		WooCommerce::init();
		Acf::init();
		Relevanssi::init();
		Elementor::init();
		Beaver_Builder::init();
	}
}
