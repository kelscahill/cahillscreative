<?php
/**
 * Fired during plugin deactivation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs actions on plugin deactivation (via the `deactivate` button in wp-admin)
 */
class Deactivator {

	/**
	 * Run actions on plugin deactivation.
	 *
	 * @since    3.0.0
	 */
	public static function deactivate() {
		\Search_Filter_Pro\Indexer\Cron::init();
		\Search_Filter_Pro\Indexer\Query_Cache::init_cron();
		\Search_Filter_Pro\Core\License_Server::init();
		\Search_Filter_Pro\Task_Runner\Cron::init();
		\Search_Filter_Pro\Core\Remote_Notices::init();
		do_action( 'search-filter-pro/core/deactivator/deactivate' );
	}
}
