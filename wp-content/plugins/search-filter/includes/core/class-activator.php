<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
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
 * Runs actions on plugin activation (via the `activate` button in wp-admin)
 */
class Activator {

	/**
	 * Runs actions on plugin activation
	 *
	 * @since    3.0.0
	 */
	public static function activate() {
		\Search_Filter\Integrations\Gutenberg\Cron::init();
		do_action( 'search-filter/core/activator/activate' );
	}
}
