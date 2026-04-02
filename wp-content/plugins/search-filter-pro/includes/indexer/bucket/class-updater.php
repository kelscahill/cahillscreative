<?php
/**
 * Bucket Updater Class.
 *
 * Handles incremental updates to the bucket system via the overflow bucket.
 * In Phase 1, all updates go to the overflow bucket until manual rebuild.
 *
 * Future: Will support in-place bucket bitmap updates for values within range.
 *
 * @package Search_Filter_Pro\Indexer
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Bucket;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bucket\Manager;
use Search_Filter_Pro\Indexer\Utils\Compression;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bucket Updater Class.
 *
 * @since 3.2.0
 */
class Updater {

	/**
	 * Handle post update.
	 *
	 * In Phase 1: All updates go to overflow bucket (simple implementation).
	 * Future phases may update bucket bitmaps directly if value is within range.
	 *
	 * @since 3.2.0
	 *
	 * @param int        $post_id   Post ID.
	 * @param int        $field_id  Field ID.
	 * @param float      $new_value New value.
	 * @param float|null $old_value Old value (for updates, not used in Phase 1).
	 * @return bool Success status.
	 */
	public static function handle_post_update( $post_id, $field_id, $new_value, $old_value = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $old_value reserved for Phase 2 API.
		// Phase 1: All updates go to overflow, regardless of bucket existence.
		// This allows initial population before buckets are built.
		// The overflow_type will be determined based on metadata availability.
		return self::add_to_overflow( $field_id, $post_id, $new_value );
	}

	/**
	 * Add item to overflow bucket.
	 *
	 * Stores values that fall outside current bucket range or need
	 * incremental update (Phase 1 approach).
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param int   $post_id  Post ID.
	 * @param float $value    Value to store.
	 * @return bool Success status.
	 */
	private static function add_to_overflow( $field_id, $post_id, $value ) {
		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// Delete any existing overflow rows for this post to prevent duplicates.
		// This handles cases where post is reindexed (including multiple values).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$overflow_table,
			array(
				'field_id'  => $field_id,
				'object_id' => $post_id,
			),
			array( '%d', '%d' )
		);

		$metadata = self::get_bucket_metadata( $field_id );

		// Determine overflow type.
		$overflow_type = 'PENDING';
		if ( $metadata ) {
			if ( $value < $metadata['min_value'] ) {
				$overflow_type = 'BELOW_MIN';
			} elseif ( $value > $metadata['max_value'] ) {
				$overflow_type = 'ABOVE_MAX';
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$overflow_table,
			array(
				'field_id'      => $field_id,
				'object_id'     => $post_id,
				'value'         => $value,
				'overflow_type' => $overflow_type,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%f', '%s', '%s' )
		);

		// Check if rebuild needed (logs action but doesn't auto-rebuild in Phase 1).
		self::check_rebuild_needed( $field_id );

		return $result !== false;
	}

	/**
	 * Check if rebuild is needed.
	 *
	 * Two-tier threshold system:
	 * - No buckets: Absolute threshold (200 items triggers initial build)
	 * - Buckets exist: Percentage threshold (5% triggers rebuild)
	 *
	 * Triggers action hook that can be used for automated rebuild via task runner.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool Whether rebuild threshold is exceeded.
	 */
	private static function check_rebuild_needed( $field_id ) {
		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$overflow_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE field_id = %d',
				$overflow_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$metadata = self::get_bucket_metadata( $field_id );

		if ( ! $metadata ) {
			// No buckets exist yet - use absolute threshold for initial build.
			if ( $overflow_count >= 200 ) {
				do_action( 'search-filter-pro/indexer/bucket/rebuild', $field_id, $overflow_count );
				return true;
			}
			return false;
		}

		// Buckets exist - use percentage threshold for rebuild.
		$total_count = $metadata['total_count'];
		$threshold   = $total_count * 0.05; // 5% threshold.

		if ( $overflow_count > $threshold ) {
			do_action( 'search-filter-pro/indexer/bucket/rebuild', $field_id, $overflow_count, $total_count );
			return true;
		}

		return false;
	}

	/**
	 * Get bucket metadata.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return array|null Metadata or null if not found.
	 */
	private static function get_bucket_metadata( $field_id ) {
		global $wpdb;
		$metadata_table = Manager::get_table_name( 'metadata' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE field_id = %d',
				$metadata_table,
				$field_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}

	/**
	 * Clear overflow bucket after rebuild.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool Success status.
	 */
	public static function clear_overflow( $field_id ) {
		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			$overflow_table,
			array( 'field_id' => $field_id ),
			array( '%d' )
		) !== false;
	}

	/**
	 * Get overflow item count for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return int Number of items in overflow.
	 */
	public static function get_overflow_count( $field_id ) {
		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE field_id = %d',
				$overflow_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $count;
	}

	/**
	 * Get overflow percentage for a field.
	 *
	 * Useful for monitoring and determining when manual rebuild is needed.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return float Overflow percentage (0-100).
	 */
	public static function get_overflow_percentage( $field_id ) {
		$overflow_count = self::get_overflow_count( $field_id );
		$metadata       = self::get_bucket_metadata( $field_id );

		if ( ! $metadata || (int) $metadata['total_count'] === 0 ) {
			return 0.0;
		}

		return ( $overflow_count / (int) $metadata['total_count'] ) * 100;
	}

	/**
	 * Remove post from bucket bitmaps.
	 *
	 * In Phase 1: Adds removal marker to overflow bucket.
	 * Finding which bucket contains a post requires querying all buckets (expensive),
	 * so we mark for rebuild instead.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID.
	 * @param int $post_id  Post ID.
	 * @return bool Success status.
	 */
	public static function remove_post_from_field( $field_id, $post_id ) {
		// Check if buckets exist for this field.
		// Don't add deletion markers if there are no buckets to delete from.
		if ( ! self::has_field_data( $field_id ) ) {
			return true; // Nothing to remove from, consider it successful.
		}

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// Add removal marker to overflow bucket.
		// Value set to 0 as a marker for deletion.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$overflow_table,
			array(
				'field_id'      => $field_id,
				'object_id'     => $post_id,
				'value'         => 0, // Marker for deletion.
				'overflow_type' => 'PENDING',
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%f', '%s', '%s' )
		);

		// Check if rebuild needed.
		self::check_rebuild_needed( $field_id );

		return $result !== false;
	}

	/**
	 * Clear all buckets for a field.
	 *
	 * Deletes all bucket data and metadata for the field.
	 * Use when removing a field or doing full rebuild.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID.
	 * @return bool Success status.
	 */
	public static function clear_field_index( $field_id ) {
		// Delegate to reset_field (same behavior, consistent naming).
		return self::delete_field_buckets( $field_id );
	}

	/**
	 * Reset all bucket data for a specific field.
	 *
	 * Clears buckets, overflow, and metadata for a single field.
	 * Used during field rebuild or deletion.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID.
	 * @return bool Success status.
	 */
	public static function delete_field_buckets( $field_id ) {
		global $wpdb;

		// Get tables without installing - if they don't exist, nothing to delete.
		$bucket_table   = Manager::get_table( 'index', false );
		$overflow_table = Manager::get_table( 'overflow', false );
		$metadata_table = Manager::get_table( 'metadata', false );

		// Only delete if tables exist.
		if ( $bucket_table && $bucket_table->exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $bucket_table->get_table_name(), array( 'field_id' => $field_id ), array( '%d' ) );
		}
		if ( $overflow_table && $overflow_table->exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $overflow_table->get_table_name(), array( 'field_id' => $field_id ), array( '%d' ) );
		}
		if ( $metadata_table && $metadata_table->exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $metadata_table->get_table_name(), array( 'field_id' => $field_id ), array( '%d' ) );
		}

		return true;
	}

	/**
	 * Reset all bucket data (all fields).
	 *
	 * Truncates all 3 bucket tables. Used during full index rebuild.
	 *
	 * @since 3.0.9
	 *
	 * @return bool Success status
	 */
	public static function reset() {

		// Truncate all bucket tables (faster than DELETE for full clear).
		$bucket_table = new Database\Index_Table();
		if ( $bucket_table->exists() ) {
			$bucket_table->truncate();
		}

		$overflow_table = new Database\Overflow_Table();
		if ( $overflow_table->exists() ) {
			$overflow_table->truncate();
		}

		$metadata_table = new Database\Metadata_Table();
		if ( $metadata_table->exists() ) {
			$metadata_table->truncate();
		}

		return true;
	}

	/**
	 * Build buckets for a field.
	 *
	 * This is the main entry point for bucket building. It:
	 * 1. Generates bucket definitions using percentile strategy.
	 * 2. Builds a bitmap for each bucket from overflow data.
	 * 3. Stores bucket metadata for fast min/max lookups.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID to build buckets for.
	 * @param array $args {
	 *     Build arguments.
	 *
	 *     @type int  $bucket_count  Number of buckets to create (default: 50).
	 *     @type bool $force_rebuild Force rebuild even if buckets exist.
	 * }
	 * @return array Status array with 'field_id', 'buckets_created', 'bucket_count', or 'error'.
	 */
	public static function build_field_buckets( $field_id, $args = array() ) {
		$defaults = array(
			'bucket_count'  => 50,
			'force_rebuild' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		// Generate bucket definitions.
		$buckets = Generator::generate_percentile_buckets(
			$field_id,
			$args['bucket_count']
		);

		if ( empty( $buckets ) ) {
			return array( 'error' => 'No buckets generated - field may have no data or insufficient unique values' );
		}

		// Build bitmap for each bucket.
		$created = 0;
		foreach ( $buckets as $bucket_def ) {
			if ( self::build_bucket_bitmap( $field_id, $bucket_def ) ) {
				++$created;
			}
		}

		// Save metadata.
		self::save_bucket_metadata( $field_id, $buckets );

		// Clear overflow after successful rebuild (data now in buckets).
		self::clear_overflow( $field_id );

		return array(
			'field_id'        => $field_id,
			'buckets_created' => $created,
			'bucket_count'    => count( $buckets ),
		);
	}

	/**
	 * Build bitmap for a single bucket.
	 *
	 * Merges existing bucket data with overflow items for efficient incremental rebuilds.
	 * Handles both initial build (no existing data) and rebuild (merge with existing).
	 *
	 * Boundary Design: Double-inclusive bounds cause intentional overlap at boundaries.
	 * Alternative half-open intervals would require tracking "last bucket" (too complex).
	 * ~2-5% storage overhead; deduplication in Query::get_range_bitmap().
	 *
	 * @since 3.0.9
	 *
	 * @param int   $field_id   Field ID.
	 * @param array $bucket_def Bucket definition with min_value, max_value, bucket_id.
	 * @return bool Success status.
	 */
	private static function build_bucket_bitmap( $field_id, $bucket_def ) {
		global $wpdb;

		// Step 1: Load existing bucket data (if exists).
		$existing_value_map = array();
		$existing_bucket    = Database\Index_Query_Direct::get_bucket( $field_id, $bucket_def['bucket_id'] );

		if ( $existing_bucket && isset( $existing_bucket->values_data ) ) {
			// Decompress existing values_data.
			$bucket_data = array(
				'values_data'       => $existing_bucket->values_data,
				'values_compressed' => $existing_bucket->values_compressed,
				'values_format'     => $existing_bucket->values_format,
			);

			// Extract value map: [post_id => value].
			// Use Compression utility (handles both compressed and uncompressed).
			$existing_value_map = Compression::decompress( $bucket_data['values_data'], array( 'preprocess' => 'serialize' ) );

			if ( ! is_array( $existing_value_map ) ) {
				$existing_value_map = array();
			}
		}

		// Step 2: Get overflow items in this bucket's range.
		$overflow_table = Manager::get_table_name( 'overflow' );

		// IMPORTANT: Intentional boundary overlap.
		// Using INCLUSIVE bounds (>= min AND <= max) means boundary values are stored
		// in BOTH adjacent buckets (e.g., value 35.0 appears in both [25,35] and [35,45]).
		// Alternative (half-open [min,max)) would eliminate duplication BUT requires
		// tracking "last bucket" which adds too much complexity. Accepting ~2-5% storage
		// overhead; deduplication happens at query time (see Query::get_range_bitmap()).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$overflow_items = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT object_id, value
			FROM %i
			WHERE field_id = %d
			  AND value >= %f
			  AND value <= %f',
				$overflow_table,
				$field_id,
				$bucket_def['min_value'],
				$bucket_def['max_value']
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Step 3: Merge overflow with existing (overflow overwrites for updated posts).
		$merged_value_map = $existing_value_map;

		foreach ( $overflow_items as $item ) {
			$object_id                      = absint( $item->object_id );
			$merged_value_map[ $object_id ] = (float) $item->value;
		}

		// Step 4: Build bitmap from merged data (child IDs only).
		if ( empty( $merged_value_map ) ) {
			// Empty bucket - store anyway for completeness.
			$bitmap = Bitmap::from_post_ids( array() );
		} else {
			$regular_ids = array_keys( $merged_value_map );
			$bitmap      = Bitmap::from_post_ids( $regular_ids );
		}

		// Step 5: Serialize and compress merged value map.
		$values_data = self::serialize_and_compress_values( $merged_value_map );

		// Step 6: Store bucket (child bitmap + values only).
		return Database\Index_Query_Direct::store_bucket(
			$field_id,
			$bucket_def,
			$bitmap,
			$values_data
		);
	}

	/**
	 * Serialize and compress value map.
	 *
	 * Uses serialize() + gzcompress() for maximum compatibility.
	 * Compression level 6 provides good balance between size and speed.
	 *
	 * @since 3.2.0
	 *
	 * @param array $value_map Map of [object_id => value].
	 * @return array|null Compressed data array or null if empty.
	 */
	private static function serialize_and_compress_values( $value_map ) {
		if ( empty( $value_map ) ) {
			return null;
		}

		// Use Compression utility (serialize + adaptive levels 1-2, igbinary support).
		$compressed_data = Compression::compress( $value_map, array( 'preprocess' => 'serialize' ) );

		// Determine if actually compressed (Compression may skip for small data <1KB).
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional: gzuncompress emits warning on non-gzip data, using @ for detection.
		$is_compressed = ( @gzuncompress( $compressed_data ) !== false ) ? 1 : 0;

		return array(
			'data'       => $compressed_data,
			'format'     => Compression::has_igbinary() ? 'igbinary' : 'serialize',
			'compressed' => $is_compressed,
		);
	}


	/**
	 * Save bucket metadata.
	 *
	 * Stores configuration and statistics about the bucket structure
	 * for fast min/max lookups and rebuild tracking.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $buckets  Bucket definitions array.
	 * @return bool Success status.
	 */
	private static function save_bucket_metadata( $field_id, $buckets ) {
		global $wpdb;
		$metadata_table = Manager::get_table_name( 'metadata' );

		$stats = Generator::analyze_field( $field_id );

		if ( ! $stats ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (field_id, min_value, max_value, unique_count, total_count, bucket_count, bucket_strategy, last_rebuild)
				 VALUES (%d, %f, %f, %d, %d, %d, %s, %s)
				 ON DUPLICATE KEY UPDATE
					min_value = VALUES(min_value),
					max_value = VALUES(max_value),
					unique_count = VALUES(unique_count),
					total_count = VALUES(total_count),
					bucket_count = VALUES(bucket_count),
					bucket_strategy = VALUES(bucket_strategy),
					last_rebuild = VALUES(last_rebuild)',
				$metadata_table,
				$field_id,
				$stats['min_val'],
				$stats['max_val'],
				$stats['unique_count'],
				$stats['total_count'],
				count( $buckets ),
				'percentile',
				current_time( 'mysql' )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Check if buckets exist for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool True if buckets exist.
	 */
	public static function has_field_data( $field_id ) {
		return Database\Index_Query_Direct::has_data_for_field( $field_id );
	}

	/**
	 * Get bucket metadata for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return array|null Metadata array or null if not found.
	 */
	public static function get_metadata( $field_id ) {
		global $wpdb;
		$metadata_table = Manager::get_table_name( 'metadata' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE field_id = %d',
				$metadata_table,
				$field_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}
}
