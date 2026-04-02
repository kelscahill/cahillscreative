<?php
/**
 * Bucket Query Class.
 *
 * Executes range queries using the bucket index system.
 *
 * Query Algorithm:
 * 1. Find overlapping buckets using range index
 * 2. Union bucket bitmaps to get candidates
 * 3. Include overflow bucket items
 * 4. Intersect with result bitmap if provided
 * 5. Post-filter candidates for exact range (guarantees 100% accuracy)
 *
 * @package Search_Filter_Pro\Indexer
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Bucket;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bucket\Database\Index_Query_Direct;
use Search_Filter_Pro\Indexer\Bucket\Manager;
use Search_Filter_Pro\Indexer\Utils\Compression;
use Search_Filter_Pro\Util;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bucket Query Class.
 *
 * @since 3.2.0
 */
class Query {

	/**
	 * Get bitmap for range query.
	 *
	 * Single-pass algorithm that combines bucket bitmaps with overflow items,
	 * using values_data for exact filtering. Zero redundant queries.
	 *
	 * Optimizations:
	 * - Loads bucket values_data in initial query (no N+1)
	 * - Early result_bitmap intersection (processes fewer values_data)
	 * - Overflow pre-filtered by result_bitmap (when provided)
	 * - All processing in-memory after 2 queries
	 *
	 * Boundary Handling: Double-inclusive bounds cause intentional overlap.
	 * Alternative (half-open intervals) requires tracking "last bucket" (too complex).
	 * Deduplication via array_unique() is efficient.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $field_id      Field ID.
	 * @param float       $min           Minimum value (inclusive).
	 * @param float       $max           Maximum value (inclusive).
	 * @param Bitmap|null $result_bitmap Optional result bitmap to intersect with.
	 * @return Bitmap Bitmap of matching post IDs.
	 */
	public static function get_range_bitmap( $field_id, $min, $max, $result_bitmap = null ) {
		// Query 1: Get buckets in range WITH values_data (single query).
		$buckets = Index_Query_Direct::get_buckets_in_range( $field_id, $min, $max );

		// Query 2: Get overflow (pre-filtered to exact range).
		// Optionally filter by result_bitmap for early filtering.
		$result_ids   = ( $result_bitmap !== null ) ? $result_bitmap->to_post_ids() : null;
		$overflow_ids = Index_Query_Direct::get_overflow_in_range( $field_id, $min, $max, $result_ids );

		// Initialize result bitmap from overflow IDs (already exact-filtered by DB).
		// Filtered candidates will be added directly via set_bit() - faster than
		// array accumulation + from_post_ids, and handles duplicates implicitly.
		$exact_bitmap = Bitmap::from_post_ids( $overflow_ids );

		// Process buckets (in-memory, zero additional SQL).
		foreach ( $buckets as $bucket ) {
			// Decompress bucket bitmap.
			$bucket_bitmap = Bitmap::decompress( $bucket->bitmap_data );
			if ( ! $bucket_bitmap || $bucket_bitmap->is_empty() ) {
				continue;
			}

			// OPTIMIZATION: Early intersection with result_bitmap.
			// This reduces candidate set before values_data extraction.
			if ( $result_bitmap !== null ) {
				$bucket_bitmap = $bucket_bitmap->intersect( $result_bitmap );
				if ( $bucket_bitmap->is_empty() ) {
					continue; // No intersection, skip this bucket.
				}
			}

			$candidate_ids = $bucket_bitmap->to_post_ids();

			// Extract exact values from values_data BLOB.
			if ( isset( $bucket->values_data ) ) {
				// Have values_data - use for exact filtering.
				$bucket_data = array(
					'values_data'       => $bucket->values_data,
					'values_compressed' => $bucket->values_compressed,
					'values_format'     => $bucket->values_format,
				);

				$values = self::extract_values_from_blob( $bucket_data, $candidate_ids );

				// Filter to exact range - set bits directly in result bitmap.
				foreach ( $candidate_ids as $i => $post_id ) {
					if ( isset( $values[ $i ] ) ) {
						$value = $values[ $i ];
						if ( $value >= $min && $value <= $max ) {
							$exact_bitmap->set_bit( $post_id );
						}
					}
				}
			} else {
				// No values_data available (shouldn't happen after bucket build).
				// Graceful degradation: Include all candidates (approximate).
				// This maintains functionality even if values_data missing.
				foreach ( $candidate_ids as $post_id ) {
					$exact_bitmap->set_bit( $post_id );
				}
			}
		}

		return $exact_bitmap;
	}

	/**
	 * Get min/max values for field.
	 *
	 * Fast lookup using bucket metadata when no filtering is applied.
	 * When filtering is needed, finds boundary buckets and refines with
	 * exact values from legacy index.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $field_id      Field ID.
	 * @param Bitmap|null $result_bitmap Optional result bitmap to filter by.
	 * @return array Array with 'min' and 'max' keys.
	 */
	public static function get_min_max( $field_id, $result_bitmap = null ) {

		global $wpdb;
		$metadata_table = Manager::get_table_name( 'metadata' );
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $result_bitmap === null ) {
			// No filtering: get min/max from metadata AND overflow.
			$metadata_result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT min_value, max_value
				FROM %i
				WHERE field_id = %d',
					$metadata_table,
					$field_id
				),
				ARRAY_A
			);

			// Also check overflow table for min/max (may extend beyond metadata).
			$overflow_result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT MIN(value) as min_value, MAX(value) as max_value
				FROM %i
				WHERE field_id = %d',
					$overflow_table,
					$field_id
				),
				ARRAY_A
			);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Merge metadata and overflow to get overall min/max.
			$min_value = null;
			$max_value = null;

			if ( $metadata_result ) {
				$min_value = (float) $metadata_result['min_value'];
				$max_value = (float) $metadata_result['max_value'];
			}

			if ( $overflow_result && $overflow_result['min_value'] !== null ) {
				$overflow_min = (float) $overflow_result['min_value'];
				$overflow_max = (float) $overflow_result['max_value'];

				$min_value = ( $min_value === null ) ? $overflow_min : min( $min_value, $overflow_min );
				$max_value = ( $max_value === null ) ? $overflow_max : max( $max_value, $overflow_max );
			}

			if ( $min_value !== null && $max_value !== null ) {
				return array(
					'min' => $min_value,
					'max' => $max_value,
				);
			}
		}

		// With filtering: find boundary buckets.
		$result_ids = $result_bitmap->to_post_ids();

		if ( empty( $result_ids ) ) {
			return array(
				'min' => null,
				'max' => null,
			);
		}

		// Get all buckets.
		$buckets = Index_Query_Direct::get_field_buckets( $field_id );

		// Extract bucket bitmaps for batch processing.
		$bucket_bitmaps = array();
		foreach ( $buckets as $bucket_id => $bucket ) {
			if ( isset( $bucket['bitmap'] ) && $bucket['bitmap'] ) {
				$bucket_bitmaps[ $bucket_id ] = $bucket['bitmap'];
			}
		}

		// Batch intersect counts (unpacks result_bitmap once).
		$counts = $result_bitmap->batch_intersect_counts( $bucket_bitmaps );

		// Find min/max buckets that intersect with result.
		$min_value  = null;
		$max_value  = null;
		$min_bucket = null;
		$max_bucket = null;

		foreach ( $counts as $bucket_id => $count ) {
			if ( $count > 0 ) {
				$bucket = $buckets[ $bucket_id ];

				if ( $min_value === null || $bucket['min'] < $min_value ) {
					$min_value  = $bucket['min'];
					$min_bucket = $bucket; // Save bucket reference.
				}
				if ( $max_value === null || $bucket['max'] > $max_value ) {
					$max_value  = $bucket['max'];
					$max_bucket = $bucket; // Save bucket reference.
				}
			}
		}

		// Also check overflow for items in result bitmap.
		$overflow_values = self::get_overflow_values_for_ids( $field_id, $result_ids );

		if ( ! empty( $overflow_values ) ) {
			$overflow_min = min( $overflow_values );
			$overflow_max = max( $overflow_values );

			$min_value = ( $min_value === null ) ? $overflow_min : min( $min_value, $overflow_min );
			$max_value = ( $max_value === null ) ? $overflow_max : max( $max_value, $overflow_max );
		}

		// Get exact values using value maps.
		if ( $min_bucket !== null && $max_bucket !== null ) {

			$precise = self::get_precise_min_max_from_value_maps( $field_id, $result_bitmap, $min_bucket, $max_bucket );

			// Merge with overflow values if any.
			if ( ! empty( $overflow_values ) ) {
				$precise['min'] = min( $precise['min'], min( $overflow_values ) );
				$precise['max'] = max( $precise['max'], max( $overflow_values ) );
			}

			return $precise;
		}

		return array(
			'min' => $min_value,
			'max' => $max_value,
		);
	}

	/**
	 * Get overflow values for specific post IDs.
	 *
	 * Used when filtering to find min/max within a result set.
	 * Transparently includes overflow data in min/max calculations.
	 *
	 * @since 3.0.9
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs to get values for.
	 * @return array Array of values.
	 */
	private static function get_overflow_values_for_ids( $field_id, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return array();
		}

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT value
			FROM %i
			WHERE field_id = %d AND object_id IN ({$placeholders})",
				array_merge( array( $overflow_table, $field_id ), $post_ids )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'floatval', $values );
	}

	/**
	 * Get precise min/max using value maps from bucket values_data.
	 *
	 * Load compressed value maps from bucket BLOBs, decompress in PHP,
	 * filter to result IDs, calculate min/max.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $field_id      Field ID.
	 * @param Bitmap $result_bitmap Result bitmap to filter by.
	 * @param array  $min_bucket    Min boundary bucket with 'bitmap' key.
	 * @param array  $max_bucket    Max boundary bucket with 'bitmap' key.
	 * @return array Array with 'min' and 'max' keys.
	 */
	private static function get_precise_min_max_from_value_maps( $field_id, $result_bitmap, $min_bucket, $max_bucket ) {

		global $wpdb;
		$table_name = Manager::get_table_name( 'index' );

		// Get boundary IDs.
		$min_boundary_ids = $result_bitmap->intersect( $min_bucket['bitmap'] )->to_post_ids();
		$max_boundary_ids = $result_bitmap->intersect( $max_bucket['bitmap'] )->to_post_ids();

		// Determine which bucket IDs to fetch.
		$bucket_ids    = array();
		$min_bucket_id = null;
		$max_bucket_id = null;

		// Extract bucket_id from bucket arrays (they come from get_field_buckets).
		foreach ( Index_Query_Direct::get_field_buckets( $field_id ) as $id => $bucket_data ) {
			if ( isset( $min_bucket['min'] ) && $bucket_data['min'] === $min_bucket['min'] &&
				isset( $min_bucket['max'] ) && $bucket_data['max'] === $min_bucket['max'] ) {
				$min_bucket_id = $id;
			}
			if ( isset( $max_bucket['min'] ) && $bucket_data['min'] === $max_bucket['min'] &&
				isset( $max_bucket['max'] ) && $bucket_data['max'] === $max_bucket['max'] ) {
				$max_bucket_id = $id;
			}
		}

		if ( $min_bucket_id === null || $max_bucket_id === null ) {
			// Bucket IDs not found - return null (graceful degradation).
			// This shouldn't happen in normal operation.
			return array(
				'min' => null,
				'max' => null,
			);
		}

		// Check if same bucket.
		if ( $min_bucket_id === $max_bucket_id ) {
			$bucket_ids[] = $min_bucket_id;
		} else {
			$bucket_ids[] = $min_bucket_id;
			$bucket_ids[] = $max_bucket_id;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// Fetch values_data for boundary buckets.
		$placeholders = implode( ',', array_fill( 0, count( $bucket_ids ), '%d' ) );
		$buckets      = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT bucket_id, values_data, values_format, values_compressed
			FROM %i
			WHERE field_id = %d AND bucket_id IN ({$placeholders})",
				array_merge( array( $table_name, $field_id ), $bucket_ids )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Extract values from value maps (zero SQL!).
		$all_values = array();

		foreach ( $buckets as $bucket_data ) {
			$bucket_id = (int) $bucket_data['bucket_id'];

			// Skip if values_data not available (shouldn't happen).
			if ( ! isset( $bucket_data['values_data'] ) ) {
				continue;
			}

			// Determine which IDs to filter for this bucket.
			if ( $bucket_id === $min_bucket_id ) {
				$target_ids = $min_boundary_ids;
			} elseif ( $bucket_id === $max_bucket_id ) {
				$target_ids = $max_boundary_ids;
			} else {
				continue;
			}

			// Extract values for these IDs.
			$values     = self::extract_values_from_blob( $bucket_data, $target_ids );
			$all_values = array_merge( $all_values, $values );
		}

		if ( empty( $all_values ) ) {
			return array(
				'min' => null,
				'max' => null,
			);
		}

		return array(
			'min' => (float) min( $all_values ),
			'max' => (float) max( $all_values ),
		);
	}

	/**
	 * Extract values from compressed BLOB for specific IDs.
	 *
	 * Decompresses and unserializes the value map, then filters to target IDs.
	 * Pure PHP operation - no SQL queries!
	 *
	 * @since 3.2.0
	 *
	 * @param array $bucket_data Bucket data with values_data, values_format, values_compressed.
	 * @param array $target_ids  Array of object IDs to extract values for.
	 * @return array Array of values.
	 */
	private static function extract_values_from_blob( $bucket_data, $target_ids ) {
		// Check for format mismatch (data serialized with igbinary but extension not available).
		if ( isset( $bucket_data['values_format'] ) && $bucket_data['values_format'] === 'igbinary' && ! Compression::has_igbinary() ) {
			Util::error_log(
				'Bucket data was serialized with igbinary but extension is not available. Indexer should be rebuilt to re-serialize data.',
				'error',
				true
			);
		}

		// Step 1 & 2: Decompress and unserialize using Compression utility.
		// Handles both compressed and uncompressed data automatically.
		$value_map = Compression::decompress( $bucket_data['values_data'], array( 'preprocess' => 'serialize' ) );

		if ( false === $value_map || ! is_array( $value_map ) ) {
			return array(); // Unserialization failed.
		}

		// Step 3: Extract values for target IDs.
		$values = array();
		foreach ( $target_ids as $id ) {
			if ( isset( $value_map[ $id ] ) ) {
				$values[] = (float) $value_map[ $id ];
			}
		}

		return $values;
	}
}
