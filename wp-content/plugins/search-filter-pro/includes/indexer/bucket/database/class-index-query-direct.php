<?php
/**
 * Bucket Index Query Direct - High-performance direct database queries.
 *
 * This class provides optimized direct SQL queries for the bucket index table,
 * bypassing the ORM layer to ensure optimal index usage and performance.
 *
 * Use this class for:
 * - Range query bucket retrieval
 * - Bucket bitmap storage and deletion
 * - High-performance bucket operations
 *
 * @package Search_Filter_Pro\Indexer\Database
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Bucket\Database;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bucket\Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bucket Index Query Direct Class.
 *
 * Provides high-performance direct database queries for the bucket index table.
 *
 * @since 3.2.0
 */
class Index_Query_Direct {

	/**
	 * Get buckets that overlap with a given range.
	 *
	 * Finds all buckets where the bucket range intersects with the query range.
	 * Uses the overlap formula: NOT (bucket.max < min OR bucket.min > max)
	 *
	 * Includes values_data BLOB for post-filtering without additional queries.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param float $min      Minimum value of query range.
	 * @param float $max      Maximum value of query range.
	 * @return array Array of bucket rows (stdClass objects) with values_data.
	 */
	public static function get_buckets_in_range( $field_id, $min, $max ) {
		// DEFENSIVE: Prevent SQL NULL comparison issues.
		// If min or max is NULL, return empty result set rather than returning all buckets.
		if ( $min === null || $max === null ) {
			return array();
		}

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// Find buckets where range overlaps: NOT (bucket.max < min OR bucket.min > max)
		// Include values_data for post-filtering (zero additional queries).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$buckets = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT bucket_id, min_value, max_value, bitmap_data,
			        item_count, values_data, values_compressed, values_format
			FROM %i
			WHERE field_id = %d
			  AND bucket_type = 'percentile'
			  AND NOT (max_value < %f OR min_value > %f)
			ORDER BY min_value ASC",
				$table_name,
				$field_id,
				$min,
				$max
			)
		);

		return $buckets;
	}

	/**
	 * Get overflow items in range (pre-filtered to exact range).
	 *
	 * Returns post IDs from overflow table that fall within the exact range.
	 * Optionally filters by result_bitmap for early filtering optimization.
	 *
	 * @since 3.0.9
	 *
	 * @param int        $field_id   Field ID.
	 * @param float      $min        Minimum value (inclusive).
	 * @param float      $max        Maximum value (inclusive).
	 * @param array|null $result_ids Optional result IDs to filter by (for early filtering).
	 * @return array Array of post IDs.
	 */
	public static function get_overflow_in_range( $field_id, $min, $max, $result_ids = null ) {

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// Build query with optional result_ids filtering.
		if ( $result_ids !== null && ! empty( $result_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $result_ids ), '%d' ) );
			$query_params = array_merge( array( $overflow_table, $field_id ), $result_ids, array( $min, $max ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT object_id
				FROM %i
				WHERE field_id = %d
				  AND object_id IN ({$placeholders})
				  AND value >= %f
				  AND value <= %f",
					$query_params
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT object_id
				FROM %i
				WHERE field_id = %d
				  AND value >= %f
				  AND value <= %f',
					$overflow_table,
					$field_id,
					$min,
					$max
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Get all buckets for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return array Array of bucket_id => bucket data.
	 */
	public static function get_field_buckets( $field_id ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$buckets = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT bucket_id, min_value, max_value, bitmap_data, item_count
			FROM %i
			WHERE field_id = %d AND bucket_type = 'percentile'
			ORDER BY bucket_id ASC",
				$table_name,
				$field_id
			)
		);

		$result = array();
		foreach ( $buckets as $bucket ) {
			$result[ $bucket->bucket_id ] = array(
				'min'    => (float) $bucket->min_value,
				'max'    => (float) $bucket->max_value,
				'bitmap' => Bitmap::decompress( $bucket->bitmap_data ),
				'count'  => (int) $bucket->item_count,
			);
		}

		return $result;
	}

	/**
	 * Store a bucket bitmap.
	 *
	 * Uses INSERT ON DUPLICATE KEY UPDATE to insert or update the bucket data.
	 *
	 * @since 3.2.0
	 *
	 * @param int        $field_id    Field ID.
	 * @param array      $bucket_def  Bucket definition with bucket_id, min_value, max_value, bucket_type.
	 * @param Bitmap     $bitmap      Bitmap of post IDs.
	 * @param array|null $values_data Optional compressed value map data.
	 * @return bool True on success.
	 */
	public static function store_bucket( $field_id, $bucket_def, Bitmap $bitmap, $values_data = null ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		$bucket_type  = $bucket_def['bucket_type'] ?? 'percentile';
		$min_value    = $bucket_def['min_value'];
		$max_value    = $bucket_def['max_value'];
		$item_count   = $bitmap->count();
		$bitmap_data  = $bitmap->compress();
		$last_updated = current_time( 'mysql' );

		// Build query based on whether values_data is provided.
		if ( $values_data !== null && isset( $values_data['data'] ) ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (field_id, bucket_id, bucket_type, min_value, max_value, item_count, bitmap_data, last_updated, values_data, values_format, values_compressed)
					 VALUES (%d, %d, %s, %f, %f, %d, %s, %s, %s, %s, %d)
					 ON DUPLICATE KEY UPDATE
						bucket_type = VALUES(bucket_type),
						min_value = VALUES(min_value),
						max_value = VALUES(max_value),
						item_count = VALUES(item_count),
						bitmap_data = VALUES(bitmap_data),
						last_updated = VALUES(last_updated),
						values_data = VALUES(values_data),
						values_format = VALUES(values_format),
						values_compressed = VALUES(values_compressed)',
					$table_name,
					$field_id,
					$bucket_def['bucket_id'],
					$bucket_type,
					$min_value,
					$max_value,
					$item_count,
					$bitmap_data,
					$last_updated,
					$values_data['data'],
					$values_data['format'],
					$values_data['compressed']
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		} else {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare(
					'INSERT INTO %i (field_id, bucket_id, bucket_type, min_value, max_value, item_count, bitmap_data, last_updated)
					 VALUES (%d, %d, %s, %f, %f, %d, %s, %s)
					 ON DUPLICATE KEY UPDATE
						bucket_type = VALUES(bucket_type),
						min_value = VALUES(min_value),
						max_value = VALUES(max_value),
						item_count = VALUES(item_count),
						bitmap_data = VALUES(bitmap_data),
						last_updated = VALUES(last_updated),
						values_data = NULL,
						values_format = NULL,
						values_compressed = NULL',
					$table_name,
					$field_id,
					$bucket_def['bucket_id'],
					$bucket_type,
					$min_value,
					$max_value,
					$item_count,
					$bitmap_data,
					$last_updated
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return false !== $result;
	}

	/**
	 * Delete all buckets for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool True on success.
	 */
	public static function delete_field_buckets( $field_id ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'field_id' => $field_id ),
			array( '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Get bucket metadata for a specific bucket.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id  Field ID.
	 * @param int $bucket_id Bucket ID.
	 * @return object|null Bucket row or null if not found.
	 */
	public static function get_bucket( $field_id, $bucket_id ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bucket = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT *
			FROM %i
			WHERE field_id = %d AND bucket_id = %d',
				$table_name,
				$field_id,
				$bucket_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $bucket;
	}

	/**
	 * Get statistics about stored buckets across all fields.
	 *
	 * Returns aggregated statistics for the entire bucket index table.
	 * This is more efficient than looping through fields when you just need totals.
	 *
	 * @since 3.2.0
	 *
	 * @return array Statistics with total_buckets and total_items.
	 */
	public static function get_statistics() {
		// Get table without auto-creation (pass false).
		$table = Manager::get_table( 'index', false );

		// Return empty stats if table doesn't exist.
		if ( ! $table || ! $table->exists() ) {
			return array(
				'total_buckets' => 0,
				'total_items'   => 0,
			);
		}

		global $wpdb;
		$table_name = $table->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				COUNT(*) as total_buckets,
				SUM(item_count) as total_items
			FROM %i
			WHERE bucket_type = 'percentile'",
				$table_name
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $stats ? $stats : array(
			'total_buckets' => 0,
			'total_items'   => 0,
		);
	}

	/**
	 * Get statistics about stored buckets for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return array Statistics.
	 */
	public static function get_field_statistics( $field_id ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				COUNT(*) as bucket_count,
				SUM(item_count) as total_items,
				AVG(item_count) as avg_items_per_bucket,
				MIN(min_value) as min_value,
				MAX(max_value) as max_value,
				SUM(LENGTH(bitmap_data)) as total_compressed_size,
				AVG(LENGTH(bitmap_data)) as avg_compressed_size
			FROM %i
			WHERE field_id = %d AND bucket_type = 'percentile'",
				$table_name,
				$field_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Check if any buckets exist.
		if ( ! $stats || ! isset( $stats['bucket_count'] ) || (int) $stats['bucket_count'] === 0 ) {
			return array();
		}

		return $stats;
	}

	/**
	 * Check if bucket data exists for a field.
	 *
	 * Checks both bucket_index (built buckets) and bucket_overflow (pending data).
	 * Returns true if either table has data for this field.
	 *
	 * Uses single EXISTS query with UNION ALL for optimal performance (1 round trip,
	 * short-circuits on first match).
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool True if bucket data exists (buckets or overflow).
	 */
	public static function has_data_for_field( $field_id ) {

		global $wpdb;
		$bucket_table   = Manager::get_table_name( 'index' );
		$overflow_table = Manager::get_table_name( 'overflow' );

		// Single query with EXISTS - short-circuits on first match.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT EXISTS(
				SELECT 1 FROM %i WHERE field_id = %d
				UNION ALL
				SELECT 1 FROM %i WHERE field_id = %d
				LIMIT 1
			)',
				$bucket_table,
				$field_id,
				$overflow_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return (bool) $exists;
	}
}
