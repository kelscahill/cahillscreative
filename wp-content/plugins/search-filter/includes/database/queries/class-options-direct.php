<?php
/**
 * Options Query Direct - High-performance direct database queries.
 *
 * This class provides optimized direct SQL queries for the options table,
 * bypassing the ORM layer to ensure fresh reads without caching issues.
 *
 * Use this class for:
 * - Force-fresh option reads (bypassing Data_Store and ORM caches)
 * - Critical status checks that need real-time database values
 *
 * @package Search_Filter\Database\Queries
 * @since 3.2.0
 */

namespace Search_Filter\Database\Queries;

use Search_Filter\Database\Rows\Option as Option_Row;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Options Query Direct Class.
 *
 * Provides high-performance direct database queries for the options table.
 *
 * @since 3.2.0
 */
class Options_Direct {

	/**
	 * Get the options table name.
	 *
	 * @return string Table name with prefix
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'search_filter_options';
	}

	/**
	 * Get an option by name using direct database query.
	 *
	 * Bypasses all caching layers (ORM cache, Data_Store cache, WP object cache)
	 * to ensure a fresh read directly from the database.
	 *
	 * The returned Option_Row automatically populates Data_Store via its constructor.
	 *
	 * @since 3.2.0
	 *
	 * @param string $name The option name to retrieve.
	 * @return Option_Row|false The option row object, or false if not found.
	 */
	public static function get( $name ) {
		global $wpdb;

		// Disable WordPress object cache to ensure fresh read.
		wp_using_ext_object_cache( false );

		$table_name = self::get_table_name();

		// Direct database query with prepared statement for SQL injection protection.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for performance and to avoid stale data.
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE name = %s LIMIT 1',
				$table_name,
				$name
			)
		);

		// If no result found, return false.
		if ( ! $result ) {
			return false;
		}

		// Create Option_Row instance which auto-populates Data_Store in constructor.
		$option_row = new Option_Row( $result );

		return $option_row;
	}
}
