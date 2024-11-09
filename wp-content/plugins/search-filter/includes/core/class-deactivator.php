<?php
/**
 * Fired during plugin deactivation
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
 * Runs actions on plugin deactivation (via the `deactivate` button in wp-admin)
 */
class Deactivator {

	/**
	 * Runs actions on plugin deactivation
	 *
	 * @since    3.0.0
	 */
	public static function deactivate() {
		\Search_Filter\Integrations\Gutenberg\Cron::init();
		do_action( 'search-filter/core/deactivator/deactivate' );
	}
}
