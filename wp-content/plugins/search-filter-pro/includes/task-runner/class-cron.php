<?php
namespace Search_Filter_Pro\Task_Runner;

use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Task_Runner;
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
class Cron {

	const CRON_INTERVAL_NAME = 'search_filter_pro_1day';
	const CRON_INTERVAL      = DAY_IN_SECONDS;
	const CRON_HOOK          = 'search-filter-pro/task-runner/cron';
	/**
	 * Init the cron class.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Check for missed tasks.
		add_action( 'admin_init', array( __CLASS__, 'validate' ) );
		// Create the schedule.
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		// Add the cron job action.
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_task' ) );
		// Attach the cron job to the init action.
		add_action( 'search-filter-pro/core/activator/activate', array( __CLASS__, 'activate' ) );
		// Remove the scheduled cron job on plugin deactivation.
		add_action( 'search-filter-pro/core/deactivator/deactivate', array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Setup the interval/frequency for the cron job.
	 *
	 * @since 3.0.0
	 *
	 * @param array $schedules
	 *
	 * @return array    The schedules.
	 */
	public static function schedules( $schedules ) {
		// Create a search_filter_pro_1day interval.
		if ( ! isset( $schedules[ self::CRON_INTERVAL_NAME ] ) ) {
			$schedules[ self::CRON_INTERVAL_NAME ] = array(
				'interval' => self::CRON_INTERVAL,
				'display'  => __( 'Once every day', 'search-filter-pro' ),
			);
		}
		return $schedules;
	}

	/**
	 * Make sure the cron job is scheduled.
	 */
	public static function activate() {
		// If the cron job is not scheduled, schedule it.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
		}

	}

	/**
	 * Deactivate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * The task to run.
	 *
	 * @since 3.0.0
	 */
	public static function run_task() {
		// Cron jobs are added on activate, even if the base plugin is
		// disabled, so make sure it's enabled before running anything
		// that might depend on it.
		if ( ! Dependencies::is_search_filter_enabled() ) {
			return;
		}
		// Maybe spawn a new process.
        Task_Runner::test_background_process();
	}

	/**
	 * Validate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function validate() {
		$next_event = wp_get_scheduled_event( self::CRON_HOOK );
		if ( ! $next_event ) {
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
			return;
		}

		$time_diff      = $next_event->timestamp - time();
		$time_5_minutes = 5 * MINUTE_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_5_minutes ) {
			// This means our scheduled event has been missed by more then 5 minutes.
			// So lets run manually and reschedule.
			self::run_task();
			Util::error_log( 'Expired indexer cron job found, re-running and rescheduling.', 'error' );
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
		}
	}
}
