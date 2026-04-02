<?php
/**
 * Centralized maintenance cron for Search & Filter.
 *
 * Fires a hook that consumers can attach their maintenance tasks to.
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
 * Centralized maintenance cron.
 *
 * This class ONLY manages the schedule and fires the action hook.
 * Consumers are responsible for attaching themselves via:
 * add_action( 'search-filter/cron/maintenance', ... )
 *
 * @since 3.0.0
 */
class Cron {

	/**
	 * The cron hook name.
	 */
	const CRON_HOOK = 'search-filter/cron/maintenance';

	/**
	 * The cron interval name.
	 */
	const CRON_INTERVAL_NAME = 'search_filter_3days';

	/**
	 * The cron interval (3 days).
	 */
	const CRON_INTERVAL = DAY_IN_SECONDS * 3;

	/**
	 * Initialize the cron.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Create the schedule.
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		// Schedule the cron job on plugin activation.
		add_action( 'search-filter/core/activator/activate', array( __CLASS__, 'activate' ) );
		// Remove the scheduled cron job on plugin deactivation.
		add_action( 'search-filter/core/deactivator/deactivate', array( __CLASS__, 'deactivate' ) );
		// Note: Consumers hook directly to CRON_HOOK. WP cron fires do_action(CRON_HOOK)
		// automatically when the scheduled event runs.
	}

	/**
	 * Setup the interval for the cron job.
	 *
	 * @since 3.0.0
	 *
	 * @param array $schedules The existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public static function schedules( $schedules ) {
		if ( ! isset( $schedules[ self::CRON_INTERVAL_NAME ] ) ) {
			$schedules[ self::CRON_INTERVAL_NAME ] = array(
				'interval' => self::CRON_INTERVAL,
				'display'  => __( 'Once every 3 days', 'search-filter' ),
			);
		}
		return $schedules;
	}

	/**
	 * Activate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function activate() {
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
	 * Fires the maintenance action.
	 *
	 * Consumers hook into this via:
	 * add_action( 'search-filter/cron/maintenance', ... )
	 *
	 * @since 3.0.0
	 */
	public static function run() {
		do_action( 'search-filter/cron/maintenance' );
	}
}
