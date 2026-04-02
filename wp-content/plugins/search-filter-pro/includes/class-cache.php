<?php
/**
 * Cache Manager Class.
 *
 * Entry point for the caching system. Handles initialization,
 * table registration, and cron job scheduling.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Cache\Database_Cache;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Cache\Database\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Manager Class.
 *
 * @since 3.2.0
 */
class Cache {

	/**
	 * Table key for cache.
	 *
	 * @var string
	 */
	const TABLE_KEY = 'cache';

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'search-filter-pro/cache/cleanup';

	/**
	 * Enabled
	 *
	 * @var bool
	 */
	private static $enabled = null;

	/**
	 * Initialize the cache system.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		self::init_cron();

		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );
	}

	/**
	 * Register cache tables.
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		// Guard: Skip if table is already registered.
		if ( Table_Manager::has( self::TABLE_KEY ) ) {
			return;
		}

		Table_Manager::register( self::TABLE_KEY, Table::class );
	}

	/**
	 * Initialize cron job for cache cleanup.
	 *
	 * @since 3.2.0
	 */
	private static function init_cron() {
		// Setup CRON job for checking for expired items.
		add_action( 'init', array( __CLASS__, 'validate_cron_schedule' ) );

		// Create the schedule.
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );

		// Add the cron job action.
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_run_task' ) );

		// Attach the cron job to the activate action.
		add_action( 'search-filter-pro/core/activator/activate', array( __CLASS__, 'cron_activate' ) );

		// Remove the scheduled cron job on plugin deactivation.
		add_action( 'search-filter-pro/core/deactivator/deactivate', array( __CLASS__, 'cron_deactivate' ) );
	}

	/**
	 * Add the cron schedule.
	 *
	 * @since 3.2.0
	 *
	 * @param array $schedules The array of cron schedules.
	 * @return array The updated array of cron schedules.
	 */
	public static function cron_schedules( $schedules ) {
		// Create a search_filter_pro_30minutes interval.
		if ( ! isset( $schedules['search_filter_pro_30minutes'] ) ) {
			$schedules['search_filter_pro_30minutes'] = array(
				'interval' => MINUTE_IN_SECONDS * 30,
				'display'  => __( 'Once every 30 minutes', 'search-filter-pro' ),
			);
		}
		return $schedules;
	}

	/**
	 * Activate the cron job.
	 *
	 * @since 3.2.0
	 */
	public static function cron_activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'search_filter_pro_30minutes', self::CRON_HOOK );
		}
	}

	/**
	 * Deactivate the cron job.
	 *
	 * @since 3.2.0
	 */
	public static function cron_deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Validate cron schedule and fix if needed.
	 *
	 * @since 3.2.0
	 */
	public static function validate_cron_schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'search_filter_pro_30minutes', self::CRON_HOOK );
		}

		$next_event = wp_get_scheduled_event( self::CRON_HOOK );
		if ( ! $next_event ) {
			return;
		}

		$time_diff  = $next_event->timestamp - time();
		$time_1_day = DAY_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_1_day ) {
			// Scheduled event missed by more than 1 day - run and reschedule.
			self::cron_run_task();
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time(), 'search_filter_pro_30minutes', self::CRON_HOOK );
		}
	}

	/**
	 * Run the cron cleanup task.
	 *
	 * @since 3.2.0
	 */
	public static function cron_run_task() {
		// Ensure base plugin is enabled.
		if ( ! Dependencies::is_search_filter_enabled() ) {
			return;
		}

		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'cleanup_expired' ) );
	}

	/**
	 * Clean up expired cache entries.
	 *
	 * @since 3.2.0
	 */
	public static function cleanup_expired() {
		Database_Cache::delete_expired();
		remove_action( 'shutdown', array( __CLASS__, 'cleanup_expired' ) );
	}

	/**
	 * Reset all caches.
	 *
	 * Clears all tiers: memory, APCu (where possible), and database.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		// Tiered_Cache::reset() clears both memory and database.
		Tiered_Cache::reset();
	}

	/**
	 * Is caching enabled.
	 *
	 * Checks whether caching is enabled, using the setting from the user, and disabling for admins.
	 *
	 * @return bool  true if enabled, false if not.
	 */
	public static function enabled() {

		if ( self::$enabled !== null ) {
			return self::$enabled;
		}

		self::$enabled = apply_filters( 'search-filter-pro/indexer/query/enable_caching', true );
		// Disable caching for admins.
		// TODO - use S&F roles to handle this.
		if ( current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.WP.Capabilities.RoleFound
			self::$enabled = false;
		}

		return self::$enabled;
	}
}
