<?php
namespace Search_Filter_Pro\Indexer;

use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the cron tasks.
 *
 * Mostly clears up expired / orphaned data.
 *
 * @since 3.0.0
 */
class Async {
	/**
	 * Hook check for dispatching an async request.
	 */
	public static function hook_dispatch_request() {

		// Don't add the hook if it's already added.
		if ( has_action( 'shutdown', array( __CLASS__, 'maybe_dispatch_request' ), 200 ) ) {
			return;
		}

		add_action( 'shutdown', array( __CLASS__, 'maybe_dispatch_request' ), 200 );
	}

	/**
	 * Unhook check for dispatching an async request.
	 */
	public static function unhook_dispatch_request() {
		remove_action( 'shutdown', array( __CLASS__, 'maybe_dispatch_request' ) );
	}

	/**
	 * Maybe dispatch an async request.
	 */
	public static function maybe_dispatch_request() {
		// Now see if we should try to start a process.
		Indexer::maybe_start_process();
		remove_action( 'shutdown', array( __CLASS__, 'maybe_dispatch_request' ) );
	}
}
