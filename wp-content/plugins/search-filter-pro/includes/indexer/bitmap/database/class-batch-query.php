<?php
/**
 * Bitmap Batch Query - Batch database operations for bitmap index.
 *
 * Provides multi-row INSERT/UPDATE/DELETE operations for efficient
 * batch processing of bitmap index data.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Bitmap/Database
 */

namespace Search_Filter_Pro\Indexer\Bitmap\Database;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bitmap\Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitmap Batch Query class.
 *
 * Provides batch operations for the bitmap index table.
 *
 * @since 3.2.0
 */
class Batch_Query {

	/**
	 * Batch load bitmaps for a field and multiple values.
	 *
	 * Loads all bitmaps for the given field-value pairs in a single query.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $values   Array of values to load bitmaps for.
	 * @return array Map of value => Bitmap object.
	 */
	public static function batch_load_bitmaps( $field_id, $values ) {
		if ( empty( $values ) ) {
			return array();
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// Build placeholders for values.
		$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
		$query_params = array_merge( array( $table_name, $field_id ), $values );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT value, bitmap_data, object_count, max_object_id
				FROM %i
				WHERE field_id = %d AND value IN ({$placeholders})",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$bitmaps = array();
		foreach ( $rows as $row ) {
			if ( empty( $row->bitmap_data ) ) {
				$bitmaps[ $row->value ] = new Bitmap();
			} else {
				$bitmap = Bitmap::decompress( $row->bitmap_data );
				if ( $bitmap ) {
					$bitmaps[ $row->value ] = $bitmap;
				}
			}
		}

		return $bitmaps;
	}

	/**
	 * Batch store multiple bitmaps for a field.
	 *
	 * Uses multi-row INSERT with ON DUPLICATE KEY UPDATE for efficient bulk storage.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $bitmaps  Map of value => Bitmap object.
	 * @return bool True on success.
	 */
	public static function batch_store_bitmaps( $field_id, $bitmaps ) {
		if ( empty( $bitmaps ) ) {
			return true;
		}

		global $wpdb;
		$table_name   = Manager::get_table_name();
		$current_time = \current_time( 'mysql' );

		// Build multi-row INSERT with ON DUPLICATE KEY UPDATE query.
		$values_sql = array();
		$values     = array();

		foreach ( $bitmaps as $value => $bitmap ) {
			$compressed_data = $bitmap->compress();
			$object_count    = $bitmap->count();
			$max_object_id   = $bitmap->get_max_id();

			$values_sql[] = '(%d, %s, %s, %d, %d, %s)';
			$values[]     = $field_id;
			$values[]     = $value;
			$values[]     = $compressed_data;
			$values[]     = $object_count;
			$values[]     = $max_object_id;
			$values[]     = $current_time;
		}

		$values_clause = implode( ', ', $values_sql );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i
				(field_id, value, bitmap_data, object_count, max_object_id, last_updated)
				VALUES {$values_clause}
				ON DUPLICATE KEY UPDATE
					bitmap_data = VALUES(bitmap_data),
					object_count = VALUES(object_count),
					max_object_id = VALUES(max_object_id),
					last_updated = VALUES(last_updated)",
				array_merge( array( $table_name ), $values )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Reset cache for stored bitmaps.
		Index_Query_Direct::flush();

		return $result !== false;
	}

	/**
	 * Batch remove posts from all bitmaps for a field.
	 *
	 * Efficiently removes multiple posts from all bitmaps of a field.
	 * This is more efficient than removing one post at a time.
	 *
	 * Strategy:
	 * 1. Load all bitmaps for the field that might contain these posts
	 * 2. Remove posts from each bitmap in memory
	 * 3. Batch store updated bitmaps (or delete empty ones)
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs to remove.
	 * @return bool True on success.
	 */
	public static function batch_remove_posts( $field_id, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return true;
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// Get all bitmaps for this field.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT value, bitmap_data, object_count, max_object_id
				FROM %i
				WHERE field_id = %d',
				$table_name,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $rows ) ) {
			return true; // No bitmaps to update.
		}

		$updated_bitmaps = array();
		$empty_values    = array();
		$post_ids_set    = array_flip( $post_ids ); // For O(1) lookup.

		foreach ( $rows as $row ) {
			if ( empty( $row->bitmap_data ) ) {
				continue;
			}

			$bitmap = Bitmap::decompress( $row->bitmap_data );
			if ( ! $bitmap ) {
				continue;
			}

			$modified = false;

			// Remove each post from this bitmap.
			foreach ( $post_ids as $post_id ) {
				if ( $bitmap->get_bit( $post_id ) ) {
					$bitmap->unset_bit( $post_id );
					$modified = true;
				}
			}

			if ( $modified ) {
				if ( $bitmap->is_empty() ) {
					$empty_values[] = $row->value;
				} else {
					$updated_bitmaps[ $row->value ] = $bitmap;
				}
			}
		}

		// Delete empty bitmaps.
		if ( ! empty( $empty_values ) ) {
			self::batch_delete_bitmaps( $field_id, $empty_values );
		}

		// Store updated bitmaps.
		if ( ! empty( $updated_bitmaps ) ) {
			self::batch_store_bitmaps( $field_id, $updated_bitmaps );
		}

		return true;
	}

	/**
	 * Batch delete bitmaps for a field and specific values.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $values   Array of values to delete.
	 * @return bool True on success.
	 */
	public static function batch_delete_bitmaps( $field_id, $values ) {
		if ( empty( $values ) ) {
			return true;
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
		$query_params = array_merge( array( $table_name, $field_id ), $values );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i
				WHERE field_id = %d AND value IN ({$placeholders})",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Reset cache.
		Index_Query_Direct::flush();

		return $result !== false;
	}

	/**
	 * Get count of bitmaps that would be affected by post removal.
	 *
	 * Useful for estimating operation cost before batch removal.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return int Number of bitmaps for this field.
	 */
	public static function get_bitmap_count( $field_id ) {
		global $wpdb;
		$table_name = Manager::get_table_name();

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
