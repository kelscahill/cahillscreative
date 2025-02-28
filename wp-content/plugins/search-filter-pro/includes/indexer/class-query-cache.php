<?php
/**
 * Query Cache class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter_Pro\Core\Dependencies;
use Search_Filter_Pro\Indexer\Cache\Query;
use Search_Filter_Pro\Indexer\Cache\Row;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles caching result IDs.
 *
 * Uses the S&F options table.
 *
 * @since 3.0.0
 */
class Query_Cache {

	const DEFAULT_EXPIRY = 7 * DAY_IN_SECONDS;

	/**
	 * The local copied store of the result cache.
	 *
	 * @var array
	 */
	private static $local = array();

	/**
	 * Init the Query Cache.
	 */
	public static function init() {
		self::init_cron();
	}
	/**
	 * Init the cron job.
	 */
	public static function init_cron() {
		// Setup CRON job for checking for expired items.
		add_action( 'init', array( __CLASS__, 'validate_cron_schedule' ) );
		// Create the schedule.
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		// Add the cron job action.
		add_action( 'search-filter-pro/indexer/query_cache/cron', array( __CLASS__, 'cron_run_task' ) );
		// Attach the cron job to the init action.
		add_action( 'search-filter-pro/core/activator/activate', array( __CLASS__, 'cron_activate' ) );
		// Remove the scheduled cron job on plugin deactivation.
		add_action( 'search-filter-pro/core/deactivator/deactivate', array( __CLASS__, 'cron_deactivate' ) );
	}
	/**
	 * Update the item result cache IDs.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $data    The data to update the item with.
	 */
	public static function update_item( $data ) {
		// First check if the cache already exists.
		$lookup_args = $data;
		if ( isset( $lookup_args['cache_value'] ) ) {
			unset( $lookup_args['cache_value'] );
		}
		$existing_item = self::get_item( $lookup_args );

		$query = new Query();
		$id    = 0;

		if ( ! isset( $data['expires'] ) ) {
			$data['expires'] = time() + self::DEFAULT_EXPIRY;
		}

		if ( $existing_item ) {
			$id = $existing_item->get_id();
			// Then update the cache value.
			$query->update_item( $id, $data );

		} else {
			// Then create the cache item.
			$id = $query->add_item( $data );
		}

		// Create a row item from the data (rather than looking it up again in the DB).
		$data['id'] = $id;
		$item       = new Row( $data );

		self::update_local_item( $item );
	}

	/**
	 * Get the item result.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $args    The query args.
	 * @return   Indexer\Cache\Row    The item from the cache.
	 */
	public static function get_item( $args ) {

		// Try to get the local value first.
		$item = self::get_local_item( $args );
		if ( $item !== null ) {
			return $item;
		}

		if ( ! isset( $args['expires'] ) ) {
			$args['expires_query'] = array(
				'compare' => '>=',
				'value'   => time(),
			);
		}

		$args['number'] = 1;

		$query = new Query( $args );

		if ( is_wp_error( $query ) ) {
			return false;
		}

		if ( empty( $query->items ) ) {
			return null;
		}

		$item = $query->items[0];

		self::update_local_item( $item );
		return $item;
	}

	/**
	 * Get the item result value.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $args    The query args.
	 * @return   string    The cache value.
	 */
	public static function get_value( $args ) {

		// Try to get the local value first.
		$item = self::get_item( $args );
		if ( $item === null ) {
			// There is no item stored.
			return null;
		} elseif ( $item === false ) {
			// There was an error with getting the item.
			return false;
		}

		return $item->get_cache_value();
	}


	/**
	 * Get the item result.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $args    The query args.
	 * @return   array    The items from the cache.
	 */
	public static function get_items( $args ) {

		// TODO - see if we can store the query args locally and get the same items locally?

		$query = new Query( $args );

		if ( is_wp_error( $query ) ) {
			return false;
		}

		if ( empty( $query->items ) ) {
			return false;
		}

		foreach ( $query->items as $item ) {
			self::update_local_item( $item );
		}
		return $query->items;
	}

	/**
	 * Get the item result value.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $args    The query args.
	 * @return   string    The cache value.
	 */
	public static function get_values( $args ) {

		// Try to get the local value first.
		$items = self::get_items( $args );
		if ( ! $items ) {
			return false;
		}

		$values = array();
		foreach ( $items as $item ) {
			$values[] = $item->get_cache_value();
		}
		return $values;
	}


	/**
	 * Delete the cached items.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $delete_where     The where clause.
	 */
	public static function delete_items( $delete_where ) {
		$query = new Query();

		$query->delete_items( $delete_where );

		// TODO - this won't work unless all the args are supplied the same.
		// Might need to rethink this when when only the field_id is supplied.
		self::delete_local_item( $delete_where );
	}

	/**
	 * Updates the local copy of the result cache.
	 *
	 * Data_Store is doing something similar, but its by ID, we'll usually need to
	 * lookup by cache_key so it won't work.
	 *
	 * @since 3.0.0
	 *
	 * @param Indexer\Cache\Row $item The cache value to update.
	 */
	private static function update_local_item( $item ) {

		$cache_key = $item->get_cache_key();
		$query_id  = $item->get_query_id();
		$field_id  = $item->get_field_id();
		$type      = $item->get_type();

		if ( ! isset( self::$local[ $query_id ] ) ) {
			self::$local[ $query_id ] = array();
		}
		if ( ! isset( self::$local[ $query_id ][ $field_id ] ) ) {
			self::$local[ $query_id ][ $field_id ] = array();
		}
		if ( ! isset( self::$local[ $query_id ][ $field_id ][ $type ] ) ) {
			self::$local[ $query_id ][ $field_id ][ $type ] = array();
		}
		self::$local[ $query_id ][ $field_id ][ $type ][ $cache_key ] = $item;
	}

	/**
	 * Deletes the local result cache reference.
	 *
	 * @since 3.0.0
	 *
	 * @param string $args The args to delete.
	 */
	private static function delete_local_item( $args ) {
		$defaults = array(
			'cache_key' => '',
			'query_id'  => 0,
			'field_id'  => 0,
			'type'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_key = $args['cache_key'];
		$query_id  = $args['query_id'];
		$field_id  = $args['field_id'];
		$type      = $args['type'];

		if ( ! isset( self::$local[ $query_id ] ) ) {
			return;
		}

		if ( ! isset( self::$local[ $query_id ][ $field_id ] ) ) {
			return;
		}
		if ( ! isset( self::$local[ $query_id ][ $field_id ][ $type ] ) ) {
			return;
		}
		if ( ! isset( self::$local[ $query_id ][ $field_id ][ $type ][ $cache_key ] ) ) {
			return;
		}

		unset( self::$local[ $query_id ][ $field_id ][ $type ][ $cache_key ] );
	}

	/**
	 * Get the local result cache.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The args to get.
	 * @return string|null The cache value.
	 */
	private static function get_local_item( $args ) {

		$defaults = array(
			'cache_key' => '',
			'query_id'  => 0,
			'field_id'  => 0,
			'type'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$cache_key = $args['cache_key'];
		$query_id  = $args['query_id'];
		$field_id  = $args['field_id'];
		$type      = $args['type'];

		if ( ! isset( self::$local[ $query_id ] ) ) {
			return null;
		}

		if ( ! isset( self::$local[ $query_id ][ $field_id ] ) ) {
			return null;
		}

		if ( ! isset( self::$local[ $query_id ][ $field_id ][ $type ] ) ) {
			return null;
		}

		if ( ! isset( self::$local[ $query_id ][ $field_id ][ $type ][ $cache_key ] ) ) {
			return null;
		}

		return self::$local[ $query_id ][ $field_id ][ $type ][ $cache_key ];
	}

	/**
	 * Clear the caches for a query ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $query_id    The query ID to clear the caches for.
	 */
	public static function clear_caches_by_query_id( $query_id ) {
		// Clear any cached results for the query.
		self::delete_items(
			array(
				'query_id' => $query_id,
			)
		);
	}

	/**
	 * Clear the caches for a field ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $field_id    The field ID to clear the caches for.
	 */
	public static function clear_caches_by_field_id( $field_id ) {
		self::delete_items(
			array(
				'field_id' => $field_id,
			)
		);
	}

	/**
	 * Add the cron schedule.
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
	 * Make sure the cron job is scheduled.
	 */
	public static function cron_activate() {
		// If the cron job is not scheduled, schedule it.
		if ( ! wp_next_scheduled( 'search-filter-pro/indexer/query_cache/cron' ) ) {
			$result = wp_schedule_event( time(), 'search_filter_pro_30minutes', 'search-filter-pro/indexer/query_cache/cron' );
		}
	}

	/**
	 * Deactivate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function cron_deactivate() {
		wp_clear_scheduled_hook( 'search-filter-pro/indexer/query_cache/cron' );
	}

	/**
	 * Checks if the cron job expired and clears & reruns it.
	 *
	 * @since 3.0.0
	 */
	public static function validate_cron_schedule() {
		if ( ! wp_next_scheduled( 'search-filter-pro/indexer/query_cache/cron' ) ) {
			wp_schedule_event( time(), 'search_filter_pro_30minutes', 'search-filter-pro/indexer/query_cache/cron' );
		}

		$next_event = wp_get_scheduled_event( 'search-filter-pro/indexer/query_cache/cron' );
		if ( ! $next_event ) {
			return;
		}

		$time_diff  = $next_event->timestamp - time();
		$time_1_day = DAY_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_1_day ) {
			// This means our scheduled event has been missed by more then 1 day.
			// So lets run manually and reschedule.
			self::cron_run_task();
			Util::error_log( 'Expired query cache cron job found, running and rescheduling.', 'error' );
			wp_clear_scheduled_hook( 'search-filter-pro/indexer/query_cache/cron' );
			wp_schedule_event( time(), 'search_filter_pro_30minutes', 'search-filter-pro/indexer/query_cache/cron' );
		}

	}

	/**
	 * Clear expired cache items.
	 *
	 * Need to use WPDB directly so we can use a comparison against the expires time.
	 *
	 * @since 3.0.0
	 */
	public static function cron_run_task() {
		// Cron jobs are added on activate, even if the base plugin is
		// disabled, so make sure it's enabled before running anything
		// that might depend on it.
		if ( ! Dependencies::is_search_filter_enabled() ) {
			return;
		}

		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'clear_expired_cache_items' ) );
	}

	/**
	 * Clear the expired cache items.
	 */
	public static function clear_expired_cache_items() {
		// Clear the expired cache items.
		global $wpdb;
		$table_name = $wpdb->prefix . 'search_filter_index_cache';
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE expires <= %d', $table_name, time() ) );
		remove_action( 'shutdown', array( __CLASS__, 'clear_expired_cache_items' ) );
	}
}
