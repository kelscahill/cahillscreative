<?php
/**
 * Legacy Batch Query - Batch database operations for legacy index.
 *
 * Provides multi-row INSERT/DELETE operations for efficient batch
 * processing of legacy index data during migration dual-write.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Legacy/Database
 */

namespace Search_Filter_Pro\Indexer\Legacy\Database;

use Search_Filter_Pro\Indexer\Legacy\Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Batch Query class.
 *
 * Provides batch operations for the legacy index table.
 *
 * @since 3.2.0
 */
class Batch_Query {

	/**
	 * Batch delete legacy index entries for multiple posts.
	 *
	 * Efficiently removes entries for multiple posts in a single query.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs to delete entries for.
	 * @return bool True on success.
	 */
	public static function batch_delete_by_posts( $field_id, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return true;
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		if ( empty( $table_name ) ) {
			return false;
		}

		// Build placeholders for post IDs.
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$query_params = array_merge( array( $table_name, $field_id ), $post_ids );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i
				WHERE field_id = %d AND object_id IN ({$placeholders})",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	/**
	 * Batch insert legacy index entries.
	 *
	 * Uses multi-row INSERT for efficient bulk insertion.
	 *
	 * @since 3.2.0
	 *
	 * @param array $items Array of items to insert, each with keys:
	 *                     - field_id: Field ID
	 *                     - object_id: Post ID
	 *                     - object_parent_id: Parent post ID
	 *                     - value: Index value.
	 * @return bool True on success.
	 */
	public static function batch_insert_items( $items ) {
		if ( empty( $items ) ) {
			return true;
		}

		global $wpdb;
		$table_name   = Manager::get_table_name();
		$current_time = current_time( 'mysql' );

		if ( empty( $table_name ) ) {
			return false;
		}

		// Build multi-row INSERT query.
		$values_sql = array();
		$values     = array();

		foreach ( $items as $item ) {
			$values_sql[] = '(%d, %d, %d, %s, %s)';
			$values[]     = $item['field_id'];
			$values[]     = $item['object_id'];
			$values[]     = $item['object_parent_id'];
			$values[]     = $item['value'];
			$values[]     = $current_time;
		}

		$values_clause = implode( ', ', $values_sql );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i
				(field_id, object_id, object_parent_id, value, date_modified)
				VALUES {$values_clause}",
				array_merge( array( $table_name ), $values )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	/**
	 * Batch delete and insert legacy index entries for a field.
	 *
	 * Convenience method that combines delete and insert in one call.
	 * Useful for updating multiple posts' index data atomically.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs to clear.
	 * @param array $items    Array of items to insert (see batch_insert_items).
	 * @return bool True on success.
	 */
	public static function batch_replace( $field_id, $post_ids, $items ) {
		// First delete existing entries.
		$delete_result = self::batch_delete_by_posts( $field_id, $post_ids );

		if ( $delete_result === false ) {
			return false;
		}

		// Then insert new entries.
		return self::batch_insert_items( $items );
	}

	/**
	 * Get count of legacy index entries for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return int Number of entries.
	 */
	public static function get_entry_count( $field_id ) {
		global $wpdb;
		$table_name = Manager::get_table_name();

		if ( empty( $table_name ) ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE field_id = %d',
				$table_name,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $count;
	}
}
