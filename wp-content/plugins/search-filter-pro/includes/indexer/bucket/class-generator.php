<?php
/**
 * Bucket Generator Class.
 *
 * Analyzes field data distribution and generates optimal bucket boundaries
 * using percentile-based strategies for range fields.
 *
 * @package Search_Filter_Pro\Indexer
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Bucket;

use Search_Filter_Pro\Indexer\Bucket\Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bucket Generator Class.
 *
 * @since 3.2.0
 */
class Generator {

	/**
	 * Analyze field data distribution from overflow table.
	 *
	 * Queries the overflow table to get statistics about value distribution
	 * for a range field. Used for production bucket builds.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID to analyze.
	 * @return array|null Statistics array or null if no data found.
	 */
	public static function analyze_field( $field_id ) {

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Get basic statistics from overflow.
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
				MIN(value) as min_val,
				MAX(value) as max_val,
				COUNT(DISTINCT value) as unique_count,
				COUNT(*) as total_count,
				STDDEV(value) as std_dev
			FROM %i
			WHERE field_id = %d',
				$overflow_table,
				$field_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $stats || (int) $stats['total_count'] === 0 ) {
			return null;
		}

		$stats['cardinality_ratio'] = (int) $stats['unique_count'] / (int) $stats['total_count'];

		return $stats;
	}

	/**
	 * Generate percentile-based bucket boundaries from overflow.
	 *
	 * Creates bucket definitions by dividing the overflow data into equal-sized
	 * percentile buckets. Used for production bucket builds.
	 *
	 * Boundary Design: Double-inclusive bounds cause intentional overlap at boundaries.
	 * Alternative (half-open intervals) requires tracking "last bucket" (too complex).
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id    Field ID to generate buckets for.
	 * @param int $bucket_count Target number of buckets (default: 50).
	 * @return array Array of bucket definitions.
	 */
	public static function generate_percentile_buckets( $field_id, $bucket_count = 50 ) {
		// Get statistics from overflow.
		$stats = self::analyze_field( $field_id );
		if ( ! $stats ) {
			return array();
		}

		// Calculate percentiles.
		$percentiles = array();
		for ( $i = 0; $i <= 100; $i += ( 100 / $bucket_count ) ) {
			$percentiles[] = $i;
		}

		// Get actual values at these percentiles from overflow.
		$boundaries = self::get_percentile_values( $field_id, $percentiles );

		if ( empty( $boundaries ) ) {
			return array();
		}

		// Create buckets with double-inclusive boundaries (intentional overlap at boundaries).
		// Adjacent buckets share boundary values (bucket N max = bucket N+1 min), causing
		// boundary posts to appear in BOTH buckets. Alternative (half-open [min,max)) would
		// require tracking "last bucket" which adds complexity. Deduplication happens at
		// query time (see Query::get_range_bitmap()).
		$buckets          = array();
		$boundaries_count = count( $boundaries );
		for ( $i = 0; $i < $boundaries_count - 1; $i++ ) {
			$buckets[] = array(
				'bucket_id'   => $i,
				'bucket_type' => 'percentile',
				'min_value'   => $boundaries[ $i ],
				'max_value'   => $boundaries[ $i + 1 ],
			);
		}

		return $buckets;
	}

	/**
	 * Get values at specific percentiles from overflow.
	 *
	 * Retrieves the actual data values that correspond to given percentile
	 * positions in the sorted value distribution from overflow table.
	 *
	 * @since 3.0.9
	 *
	 * @param int   $field_id    Field ID.
	 * @param array $percentiles Array of percentile values (0-100).
	 * @return array Values at each percentile.
	 */
	private static function get_percentile_values( $field_id, $percentiles ) {

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Get all distinct values sorted from overflow.
		$values = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT value
			FROM %i
			WHERE field_id = %d
			ORDER BY value ASC',
				$overflow_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $values ) ) {
			return array();
		}

		$total      = count( $values );
		$boundaries = array();

		foreach ( $percentiles as $p ) {
			$index        = (int) floor( ( $p / 100 ) * ( $total - 1 ) );
			$boundaries[] = (float) $values[ $index ];
		}

		// Remove duplicates (can happen with heavily clustered data).
		$boundaries = array_unique( $boundaries );

		return array_values( $boundaries );
	}

	/**
	 * Get recommended bucket count based on data characteristics.
	 *
	 * Returns optimal bucket count based on field cardinality:
	 * - < 200 unique values: Not suitable for bucketing (use exact values)
	 * - < 1000 unique values: 30 buckets
	 * - < 10,000 unique values: 50 buckets
	 * - >= 10,000 unique values: 75 buckets
	 *
	 * @since 3.2.0
	 *
	 * @param array $stats Statistics from analyze_field().
	 * @return int Recommended bucket count (0 means don't use bucketing).
	 */
	public static function get_recommended_bucket_count( $stats ) {
		$unique_count = $stats['unique_count'];

		if ( $unique_count < 1000 ) {
			return 30;
		} elseif ( $unique_count < 10000 ) {
			return 50;
		} else {
			return 75;
		}
	}
}
