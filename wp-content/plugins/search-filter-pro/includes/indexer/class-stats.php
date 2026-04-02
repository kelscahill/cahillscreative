<?php
/**
 * Indexer statistics and caching.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Options;
use Search_Filter_Pro\Indexer;
use Search_Filter\Core\Async;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculate and caches the indexer statistics across all index types.
 *
 * @since 3.0.0
 */
class Stats {

	/**
	 * Default index stats structure.
	 *
	 * @var array
	 */
	private static $computing_defaults = array(
		'objects' => array(
			'total'     => -1,
			'postTypes' => array(),
			'queries'   => -1,
			'fields'    => -1,
		),
		'bitmap'  => array(
			'inProgress'   => false,
			'totalBitmaps' => 0,
			'rows'         => -1,
		),
		'bucket'  => array(
			'inProgress'   => false,
			'totalBuckets' => 0,
			'totalItems'   => 0,
			'rows'         => -1,
		),
		'search'  => array(
			'inProgress'    => false,
			'totalTerms'    => 0,
			'totalPostings' => 0,
			'totalDocs'     => 0,
			'rows'          => -1,
		),
	);
	/**
	 * Empty state defaults for when indexing has never occurred.
	 * Uses 0 instead of -1 to avoid triggering "calculating" UI state.
	 *
	 * @since 3.2.0
	 *
	 * @var array
	 */
	private static $empty_defaults = array(
		'objects' => array(
			'total'     => 0,
			'postTypes' => array(),
			'queries'   => 0,
			'fields'    => 0,
		),
		'bitmap'  => array(
			'inProgress'   => false,
			'totalBitmaps' => 0,
			'rows'         => 0,
		),
		'bucket'  => array(
			'inProgress'   => false,
			'totalBuckets' => 0,
			'totalItems'   => 0,
			'rows'         => 0,
		),
		'search'  => array(
			'inProgress'    => false,
			'totalTerms'    => 0,
			'totalPostings' => 0,
			'totalDocs'     => 0,
			'rows'          => 0,
		),
	);
	/**
	 * Default index stats structure.
	 *
	 * @var array
	 */
	private static $in_progress_defaults = array(
		'objects' => array(
			'total'     => 0,
			'postTypes' => array(),
			'queries'   => 0,
			'fields'    => 0,
		),
		'bitmap'  => array(
			'inProgress'   => true,
			'totalBitmaps' => 0,
			'rows'         => 0,
		),
		'bucket'  => array(
			'inProgress'   => true,
			'totalBuckets' => 0,
			'totalItems'   => 0,
			'rows'         => 0,
		),
		'search'  => array(
			'inProgress'    => true,
			'totalTerms'    => 0,
			'totalPostings' => 0,
			'totalDocs'     => 0,
			'rows'          => 0,
		),
	);
	/**
	 * Get all index statistics with unified caching.
	 *
	 * Returns stats for all 4 index types (objects, bitmap, bucket, search).
	 * Uses flag-based invalidation with lock mechanism. Returns -1 for rows
	 * while calculating to signal "calculating..." state to UI.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $is_idle Whether the indexer is idle (no active tasks).
	 *
	 * @return array All index stats with objects, bitmap, bucket, and search keys.
	 */
	public static function get( $is_idle = true ) {

		// If we're not idle, return in-progress defaults without doing any calculations.
		if ( ! $is_idle ) {
			return self::$in_progress_defaults;
		}

		// Default to 'never' if option doesn't exist - indicates indexing has never occurred.
		$needs_refresh = Options::get( 'indexer-index-stats-needs-refresh', 'never' );

		if ( $needs_refresh === 'yes' ) {
			// Queue async refresh on shutdown, return calculating state immediately.
			Async::register_callback( array( __CLASS__, 'refresh_all' ) );
			return self::$computing_defaults;
		}

		$stats = Options::get( 'indexer-index-stats' );

		if ( ! $stats ) {
			// If 'never', indexing has never happened - return empty defaults (0s).
			// Otherwise, stats exist but need computing - return computing defaults (-1s).
			if ( $needs_refresh === 'never' ) {
				return self::$empty_defaults;
			}
			return self::$computing_defaults;
		}

		return $stats;
	}

	/**
	 * Refresh all index stats with lock.
	 *
	 * Acquires lock, calculates all 4 stat types, stores result.
	 * Returns stats with rows=-1 if another process is refreshing.
	 *
	 * @since 3.2.0
	 *
	 * @return array All index stats, or with rows=-1 if locked.
	 */
	public static function refresh_all() {
		// Check for existing lock.
		$lock_time = Options::get( 'indexer-index-stats-refreshing' );

		if ( $lock_time ) {
			// Check if lock is stale (>20 seconds).
			if ( time() - (int) $lock_time > 20 ) {
				// Stale lock - clear it and continue.
				Options::delete( 'indexer-index-stats-refreshing' );
				Util::error_log( 'Index stats refresh lock expired, clearing stale lock', 'notice' );
			} else {
				// Another process is refreshing - return calculating state.
				return self::$computing_defaults;
			}
		}

		// Acquire lock.
		Options::update( 'indexer-index-stats-refreshing', time() );

		// Calculate all stats.
		$stats = self::calculate();

		// Store result and clear flags.
		Options::update( 'indexer-index-stats', $stats );
		Options::update( 'indexer-index-stats-needs-refresh', 'no' );
		Options::delete( 'indexer-index-stats-refreshing' );

		Util::error_log( 'All index stats refreshed', 'notice' );

		return $stats;
	}

	/**
	 * Calculate index objects statistics.
	 *
	 * Runs expensive queries to count indexed objects by post type.
	 *
	 * @since 3.2.0
	 *
	 * @return array Calculated stats with total and post_types breakdown.
	 */
	private static function calculate_index_objects() {
		// Calculate objects stats from the sync data.
		$sync_data = Indexer::init_sync_data();

		$objects_stats = array(
			'total'      => 0,
			'post_types' => array(),
		);

		if ( ! empty( $sync_data['post_types'] ) ) {
			$post_types_data = array();
			$total           = 0;

			foreach ( $sync_data['post_types'] as $post_type ) {

				// If the post type is `attachment` we need to add the `inherit` post status.
				if ( 'attachment' === $post_type && ! in_array( 'inherit', $sync_data['post_stati'], true ) ) {
					$sync_data['post_stati'][] = 'inherit';
				}

				$query = new \WP_Query(
					array(
						'post_type'      => $post_type,
						'post_status'    => $sync_data['post_stati'],
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'no_found_rows'  => false,
					)
				);

				$count  = $query->found_posts;
				$total += $count;

				$post_type_obj                 = get_post_type_object( $post_type );
				$post_types_data[ $post_type ] = array(
					'count' => $count,
					'label' => $post_type_obj ? $post_type_obj->label : $post_type,
				);
			}

			$objects_stats['total']      = $total;
			$objects_stats['post_types'] = $post_types_data;
		}

		// Resave queries.
		$all_queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);

		$queries_count = 0;
		$fields_count  = 0;
		foreach ( $all_queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			if ( $query->get_attribute( 'useIndexer' ) === 'yes' ) {
				++$queries_count;

				$query_fields  = $query->get_fields();
				$fields_count += count( $query_fields );
			}
		}

		$objects_stats['queries'] = $queries_count;
		$objects_stats['fields']  = $fields_count;

		return $objects_stats;
	}
	/**
	 * Calculate all index statistics.
	 *
	 * Runs expensive queries for all 4 index types.
	 *
	 * @since 3.2.0
	 *
	 * @return array Calculated stats for objects, bitmap, bucket, and search.
	 */
	private static function calculate() {

		// Calculate bitmap stats.
		$bitmap_raw   = Indexer\Bitmap\Database\Index_Query_Direct::get_statistics();
		$bitmap_stats = array(
			'inProgress'   => false,
			'totalBitmaps' => $bitmap_raw['total_bitmaps'] ?? 0,
			'rows'         => $bitmap_raw['total_bitmaps'] ?? 0,
		);

		// Calculate bucket stats.
		$bucket_raw   = Indexer\Bucket\Database\Index_Query_Direct::get_statistics();
		$bucket_stats = array(
			'inProgress'   => false,
			'totalBuckets' => $bucket_raw['total_buckets'] ?? 0,
			'totalItems'   => $bucket_raw['total_items'] ?? 0,
			'rows'         => $bucket_raw['total_buckets'] ?? 0,
		);

		// Calculate search stats.
		$search_stats_raw = Indexer\Search\Database\Search_Query_Direct::get_statistics();
		$search_stats     = array(
			'inProgress'    => false,
			'totalTerms'    => $search_stats_raw['total_terms'] ?? 0,
			'totalPostings' => $search_stats_raw['total_postings'] ?? 0,
			'totalDocs'     => $search_stats_raw['total_documents'] ?? 0,
			'rows'          => ( $search_stats_raw['total_terms'] ?? 0 ) + ( $search_stats_raw['total_postings'] ?? 0 ),
		);

		// Calculate objects stats.
		$objects_stats_raw = self::calculate_index_objects();
		$objects_stats     = array(
			'total'     => $objects_stats_raw['total'],
			'postTypes' => $objects_stats_raw['post_types'],
			'queries'   => $objects_stats_raw['queries'],
			'fields'    => $objects_stats_raw['fields'],
		);

		return array(
			'objects' => $objects_stats,
			'bitmap'  => $bitmap_stats,
			'bucket'  => $bucket_stats,
			'search'  => $search_stats,
		);
	}

	/**
	 * Flag all index stats as needing refresh.
	 *
	 * Called when tasks complete or index is modified to invalidate cached stats.
	 *
	 * @since 3.2.0
	 */
	public static function flag_refresh() {
		Options::update( 'indexer-index-stats-needs-refresh', 'yes' );
	}
}
