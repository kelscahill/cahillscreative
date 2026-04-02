<?php
/**
 * Debugger Cron Class
 *
 * Handles automatic cleanup of old debug logs.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Debugger
 */

namespace Search_Filter\Debugger;

use Search_Filter\Database\Queries\Logs as Logs_Query;
use Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles automatic cleanup of old debug logs.
 *
 * @since 3.0.0
 */
class Cron {

	/**
	 * Number of days to retain logs.
	 *
	 * @var int
	 */
	const RETENTION_DAYS = 10;

	/**
	 * Initialize the cron tasks.
	 *
	 * Attaches to the centralized maintenance cron.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Attach to the centralized maintenance cron.
		add_action( 'search-filter/cron/maintenance', array( __CLASS__, 'run_task' ) );
	}

	/**
	 * The task to run.
	 *
	 * @since 3.0.0
	 */
	public static function run_task() {
		// Only cleanup if logging is enabled.
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return;
		}

		$log_to_database = Features::get_setting_value( 'debugger', 'logToDatabase' );
		if ( 'yes' !== $log_to_database ) {
			return;
		}

		self::cleanup_old_logs();
	}

	/**
	 * Delete logs older than the retention period.
	 *
	 * @since 3.0.0
	 */
	private static function cleanup_old_logs() {
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS ) );

		$logs_query = new Logs_Query();
		$logs_query->delete_items(
			array(
				'date_created' => array(
					'value'   => $cutoff_date,
					'compare' => '<',
				),
			)
		);
	}
}
