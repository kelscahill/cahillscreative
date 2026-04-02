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
 * Important: While this may look like a copy of the base plugins
 * Options_Direct class, it is intentionally seperated to avoid
 * circular dependencies so it can be used to interaction with options
 * even when the base plugin is disabled.
 *
 * Contains additional methods for atomic upsert and increment operations
 * to avoid race conditions and prevent multiple process spawns in the
 * task runner.
 *
 * @package Search_Filter_Pro\Database\Queries
 * @since 3.2.3
 */

namespace Search_Filter_Pro\Database\Queries;

use Search_Filter_Pro\Database\Fresh_Connection;

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
	 * Upsert an option value using a single atomic SQL statement.
	 *
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE to ensure the row is never
	 * absent between a DELETE and INSERT. This prevents race conditions
	 * where concurrent reads could see a missing row.
	 *
	 * @since 3.2.3
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The option value (must be scalar).
	 * @return int|false Number of affected rows, or false on error.
	 */
	public static function upsert( $name, $value ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic upsert required to prevent race conditions.
		return $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (name, value) VALUES (%s, %s) ON DUPLICATE KEY UPDATE value = VALUES(value)',
				$table_name,
				$name,
				$value
			)
		);
	}

	/**
	 * Atomically claim an option row.
	 *
	 * Uses INSERT IGNORE to attempt creation. If the row already exists
	 * (another process claimed it first), silently no-ops.
	 *
	 * @since 3.2.3
	 *
	 * @param string $name  The option name to claim.
	 * @param mixed  $value The value to set if claimed.
	 * @return bool True if claimed (inserted), false if already taken.
	 */
	public static function claim( $name, $value ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic claim required to prevent duplicate process spawns.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO %i (name, value) VALUES (%s, %s)',
				$table_name,
				$name,
				$value
			)
		);

		// 1 = we inserted (claimed). 0 = row already existed.
		return $wpdb->rows_affected === 1;
	}

	/**
	 * Atomically increment a numeric option value.
	 *
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE to ensure the increment
	 * is atomic. If the row doesn't exist, it's created with the given amount.
	 *
	 * @since 3.2.3
	 *
	 * @param string $name   The option name.
	 * @param int    $amount The amount to increment by (default 1).
	 * @return int The new value after incrementing.
	 */
	public static function increment( $name, $amount = 1 ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic increment required to prevent race conditions.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (name, value) VALUES (%s, %d) ON DUPLICATE KEY UPDATE value = CAST(value AS UNSIGNED) + %d',
				$table_name,
				$name,
				$amount,
				$amount
			)
		);

		// Read back the current value.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Must read fresh value after atomic increment.
		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT value FROM %i WHERE name = %s',
				$table_name,
				$name
			)
		);

		return absint( $result );
	}

	/**
	 * Get an option by name using direct database query.
	 *
	 * Bypasses all caching layers (ORM cache, Data_Store cache, WP object cache)
	 * to ensure a fresh read directly from the database.
	 *
	 * @since 3.2.0
	 *
	 * @param string $name The option name to retrieve.
	 * @return object|false The option row object, or false if not found.
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

		return $result;
	}

	/**
	 * Delete multiple options, falling back to a fresh connection if $wpdb is broken.
	 *
	 * Designed for shutdown handlers where $wpdb may be broken
	 * ("Commands out of sync" after mid-query kill).
	 *
	 * @since 3.2.3
	 *
	 * @param array $names Array of option names to delete.
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public static function resilient_bulk_delete( $names ) {
		global $wpdb;

		if ( empty( $names ) ) {
			return 0;
		}

		$table_name = self::get_table_name();

		// 1. Try $wpdb first.
		if ( $wpdb && ! empty( $wpdb->dbh ) && $wpdb->ready ) {
			$placeholders = implode( ', ', array_fill( 0, count( $names ), '%s' ) );
			$args         = array_merge( array( $table_name ), $names );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Shutdown handler, must bypass cache.
			$result = $wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic placeholder count.
				$wpdb->prepare(
					"DELETE FROM %i WHERE name IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is safe (contains only %s tokens).
					$args
				)
			);
			if ( false !== $result ) {
				return (int) $result;
			}
		}

		// 2. Fall back to fresh connection.
		$conn = Fresh_Connection::create();
		if ( ! $conn ) {
			return false;
		}

		$escaped_names = array();
		foreach ( $names as $name ) {
			$escaped_names[] = "'" . $conn->real_escape_string( $name ) . "'";
		}
		$in_clause = implode( ', ', $escaped_names );

		$result  = $conn->query( "DELETE FROM `{$table_name}` WHERE `name` IN ({$in_clause})" );
		$deleted = $result ? $conn->affected_rows : false;
		$conn->close();

		return $deleted;
	}
}
