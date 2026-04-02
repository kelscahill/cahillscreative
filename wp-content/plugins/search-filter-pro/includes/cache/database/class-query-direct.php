<?php
/**
 * Cache Query Direct - High-performance direct database queries.
 *
 * This class provides optimized direct SQL queries for the cache table,
 * bypassing the ORM layer for better performance when used with Tiered_Cache.
 *
 * Use this class when:
 * - Performance is critical
 * - The cache is wrapped in Tiered_Cache (ORM caching adds overhead)
 * - You need direct database operations without ORM overhead
 *
 * @package Search_Filter_Pro\Cache\Database
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Cache\Database;

use Search_Filter_Pro\Cache\Database_Cache;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cache Query Direct Class.
 *
 * Provides high-performance direct database queries for the cache table.
 *
 * @since 3.2.0
 */
class Query_Direct {

	/**
	 * Get a single cache row by key and group.
	 *
	 * Returns the raw row without checking expiration.
	 *
	 * @since 3.2.0
	 *
	 * @param string $hashed_key Hashed cache key (MD5).
	 * @param string $group      Cache group.
	 * @return object|null Row object or null if not found.
	 */
	public static function get_row( $hashed_key, $group ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, cache_group, cache_key, cache_value, format, expires
				FROM %i
				WHERE cache_group = %s AND cache_key = %s
				LIMIT 1',
				$table_name,
				$group,
				$hashed_key
			)
		);

		return $row;
	}

	/**
	 * Get a single non-expired cache row by key and group.
	 *
	 * @since 3.2.0
	 *
	 * @param string $hashed_key Hashed cache key (MD5).
	 * @param string $group      Cache group.
	 * @return object|null Row object or null if not found/expired.
	 */
	public static function get_row_non_expired( $hashed_key, $group ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, cache_group, cache_key, cache_value, format, expires
				FROM %i
				WHERE cache_group = %s AND cache_key = %s AND expires >= %d
				LIMIT 1',
				$table_name,
				$group,
				$hashed_key,
				time()
			)
		);

		return $row;
	}

	/**
	 * Insert a new cache row.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data Row data with cache_group, cache_key, cache_value, format, expires.
	 * @return int|false Insert ID on success, false on failure.
	 */
	public static function insert( $data ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table_name,
			array(
				'cache_group' => $data['cache_group'],
				'cache_key'   => $data['cache_key'],
				'cache_value' => $data['cache_value'],
				'format'      => $data['format'],
				'expires'     => $data['expires'],
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing cache row.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $id   Row ID.
	 * @param array $data Row data with cache_value, format, expires.
	 * @return bool True on success, false on failure.
	 */
	public static function update( $id, $data ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table_name,
			array(
				'cache_value' => $data['cache_value'],
				'format'      => $data['format'],
				'expires'     => $data['expires'],
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Insert or update a cache row using INSERT ON DUPLICATE KEY UPDATE.
	 *
	 * More efficient than separate get_row + insert/update for single operations.
	 *
	 * @since 3.2.0
	 *
	 * @param array $data Row data with cache_group, cache_key, cache_value, format, expires.
	 * @return bool True on success, false on failure.
	 */
	public static function upsert( $data ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (cache_group, cache_key, cache_value, format, expires)
				VALUES (%s, %s, %s, %s, %d)
				ON DUPLICATE KEY UPDATE
					cache_value = VALUES(cache_value),
					format = VALUES(format),
					expires = VALUES(expires)',
				$table_name,
				$data['cache_group'],
				$data['cache_key'],
				$data['cache_value'],
				$data['format'],
				$data['expires']
			)
		);

		return false !== $result;
	}

	/**
	 * Delete a cache row by key and group.
	 *
	 * @since 3.2.0
	 *
	 * @param string $hashed_key Hashed cache key (MD5).
	 * @param string $group      Cache group.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $hashed_key, $group ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array(
				'cache_group' => $group,
				'cache_key'   => $hashed_key,
			),
			array( '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Delete all cache rows for a group.
	 *
	 * @since 3.2.0
	 *
	 * @param string $group Cache group.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_group( $group ) {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'cache_group' => $group ),
			array( '%s' )
		);

		return false !== $result;
	}

	/**
	 * Delete all expired cache entries.
	 *
	 * @since 3.2.0
	 *
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public static function delete_expired() {
		global $wpdb;

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE expires <= %d AND expires > 0',
				$table_name,
				time()
			)
		);
	}

	/**
	 * Get multiple cache rows by keys for a group.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $hashed_keys Array of hashed cache keys (MD5).
	 * @param string $group       Cache group.
	 * @return array Array of row objects indexed by cache_key.
	 */
	public static function get_many( $hashed_keys, $group ) {
		global $wpdb;

		if ( empty( $hashed_keys ) ) {
			return array();
		}

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $hashed_keys ), '%s' ) );
		$params       = array_merge( array( $table_name, $group ), $hashed_keys, array( time() ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, cache_group, cache_key, cache_value, format, expires
				FROM %i
				WHERE cache_group = %s AND cache_key IN ({$placeholders}) AND expires >= %d",
				$params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		// Index by cache_key for easy lookup.
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row->cache_key ] = $row;
		}

		return $result;
	}

	/**
	 * Delete multiple cache rows by keys for a group.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $hashed_keys Array of hashed cache keys (MD5).
	 * @param string $group       Cache group.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_many( $hashed_keys, $group ) {
		global $wpdb;

		if ( empty( $hashed_keys ) ) {
			return true;
		}

		$table_name = Database_Cache::get_table_name();
		if ( empty( $table_name ) ) {
			return false;
		}

		$placeholders = implode( ',', array_fill( 0, count( $hashed_keys ), '%s' ) );
		$params       = array_merge( array( $table_name, $group ), $hashed_keys );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE cache_group = %s AND cache_key IN ({$placeholders})",
				$params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return false !== $result;
	}

	/**
	 * Get cache statistics.
	 *
	 * @since 3.2.0
	 *
	 * @return array Statistics array.
	 */
	public static function get_statistics() {
		global $wpdb;

		$table = Database_Cache::get_table( false );
		if ( ! $table || ! $table->exists() ) {
			return array(
				'total_rows'   => 0,
				'total_size'   => 0,
				'by_group'     => array(),
				'expired_rows' => 0,
			);
		}

		$table_name = Database_Cache::get_table_name( false );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = array();

		// Total count.
		$stats['total_rows'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name )
		);

		// Total size of cache values.
		$stats['total_size'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT SUM(LENGTH(cache_value)) FROM %i', $table_name )
		);

		// Count by group.
		$stats['by_group'] = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT cache_group, COUNT(*) as count, SUM(LENGTH(cache_value)) as size
				FROM %i
				GROUP BY cache_group
				ORDER BY count DESC',
				$table_name
			),
			ARRAY_A
		);

		// Expired rows count.
		$stats['expired_rows'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE expires > 0 AND expires < %d',
				$table_name,
				time()
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $stats;
	}
}
