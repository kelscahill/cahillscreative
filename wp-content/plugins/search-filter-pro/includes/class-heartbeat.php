<?php
/**
 * The main class for initialising all things for the frontend.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook into the WP Heartbeat.
 */
class Heartbeat {

	/**
	 * Init.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Use the heartbeat to check the status of the indexer, and resume etc if needed.
		add_action( 'heartbeat_tick', array( __CLASS__, 'heartbeat' ) );
	}

	/**
	 * Heartbeat.
	 *
	 * @since 3.0.0
	 */
	public static function heartbeat() {
		if ( ! Features::is_enabled( 'indexer' ) ) {
			return;
		}
		// Now check the status of the indexer.
		Indexer::check_for_errors();
	}

}
