<?php
/**
 * Index Query Direct - High-performance direct database queries.
 *
 * This class provides optimized direct SQL queries for the index table,
 * bypassing the ORM layer to ensure optimal index usage and performance.
 *
 * Use this class when:
 * - Performance is critical (large datasets, frequent queries)
 * - You need to ensure specific indexes are used
 * - The ORM layer adds unnecessary overhead
 *
 * @package Search_Filter_Pro\Indexer\Bitmap\Database
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Bitmap\Database;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bitmap\Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Index Query Direct Class.
 *
 * Provides high-performance direct database queries for the index table.
 *
 * @since 3.2.0
 */
class Index_Query_Direct {

	/**
	 * Unified cache for field bitmaps and metadata.
	 *
	 * Structure: [
	 *     field_id => [
	 *         'all_loaded' => bool,      // True if all values loaded via get_field_bitmaps()
	 *         'has_bitmaps' => bool,     // Cached result of has_bitmaps_for_field()
	 *         'values' => array|null,    // Cached list of values (from get_unique_field_values)
	 *         'bitmaps' => [             // Individual bitmap data
	 *             'value1' => Bitmap,
	 *             'value2' => Bitmap,
	 *         ]
	 *     ]
	 * ]
	 *
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Whether caching is enabled
	 *
	 * @var bool
	 */
	private static $enable_cache = true;

	/**
	 * Get a bitmap for a specific field and value.
	 *
	 * @param int    $field_id Field ID.
	 * @param string $value    Field value.
	 * @return Bitmap|null Bitmap object or null if not found.
	 */
	public static function get_bitmap( $field_id, $value ) {

		// Check cache first.
		if ( self::$enable_cache && isset( self::$cache[ $field_id ]['bitmaps'][ $value ] ) ) {
			return self::$cache[ $field_id ]['bitmaps'][ $value ];
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT bitmap_data, max_object_id
			FROM %i
			WHERE field_id = %d AND value = %s',
				$table_name,
				$field_id,
				$value
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $row ) {
			return null; // Row not found.
		}

		if ( empty( $row->bitmap_data ) ) {
			return new Bitmap(); // Row found but bitmap data is NULL/empty.
		}

		// Decompress bitmap.
		$bitmap = Bitmap::decompress( $row->bitmap_data );

		// Cache if enabled.
		if ( self::$enable_cache && $bitmap ) {
			self::init_field_cache( $field_id );
			self::$cache[ $field_id ]['bitmaps'][ $value ] = $bitmap;
		}

		return $bitmap;
	}

	/**
	 * Get all bitmaps for a field.
	 *
	 * @param int        $field_id Field ID.
	 * @param array|null $values   Optional: specific values to fetch. null = all values (default).
	 * @return array Array of value => ['bitmap' => Bitmap, 'count' => int].
	 */
	public static function get_field_bitmaps( $field_id, $values = null ) {

		// If requesting ALL values and we've already loaded all, return from cache.
		if ( self::$enable_cache && $values === null ) {
			if ( ! empty( self::$cache[ $field_id ]['all_loaded'] ) ) {
				return self::get_field_bitmaps_from_cache( $field_id );
			}
		}

		// If requesting SPECIFIC values, check if all are cached.
		if ( self::$enable_cache && $values !== null ) {
			$all_cached     = true;
			$cached_bitmaps = array();
			foreach ( $values as $value ) {
				if ( isset( self::$cache[ $field_id ]['bitmaps'][ $value ] ) ) {
					$bitmap                   = self::$cache[ $field_id ]['bitmaps'][ $value ];
					$cached_bitmaps[ $value ] = array(
						'bitmap' => $bitmap,
						'count'  => $bitmap->count(),
					);
				} else {
					$all_cached = false;
					break;
				}
			}
			if ( $all_cached ) {
				return $cached_bitmaps;
			}
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// Build WHERE clause.
		$where = $wpdb->prepare( 'field_id = %d', $field_id );

		// Add value filter if specified.
		if ( null !== $values && ! empty( $values ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$where .= $wpdb->prepare( " AND value IN ($placeholders)", $values );
			// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Where is already prepared above.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT value, bitmap_data, object_count, max_object_id
				FROM %i
				WHERE {$where}
				ORDER BY object_count DESC",
				$table_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$bitmaps = array();
		foreach ( $rows as $row ) {
			if ( empty( $row->bitmap_data ) ) {
				continue;
			}
			$bitmap = Bitmap::decompress( $row->bitmap_data );
			if ( $bitmap ) {
				$bitmaps[ $row->value ] = array(
					'bitmap' => $bitmap,
					'count'  => (int) $row->object_count,
				);
			}
		}

		// Cache results and mark field as fully loaded if we fetched all values.
		if ( self::$enable_cache ) {
			self::init_field_cache( $field_id );
			foreach ( $bitmaps as $value => $data ) {
				self::$cache[ $field_id ]['bitmaps'][ $value ] = $data['bitmap'];
			}
			if ( $values === null ) {
				self::$cache[ $field_id ]['all_loaded'] = true;
				// Only set has_bitmaps definitively when we queried ALL values.
				self::$cache[ $field_id ]['has_bitmaps'] = ! empty( $bitmaps );
			} elseif ( ! empty( $bitmaps ) ) {
				// If specific values found, we know bitmaps exist.
				self::$cache[ $field_id ]['has_bitmaps'] = true;
			}
			// If specific values queried and empty result, don't set has_bitmaps
			// (we can't definitively say there are no bitmaps for other values).
		}

		return $bitmaps;
	}

	/**
	 * Get bitmaps for a field where value matches a wildcard pattern.
	 *
	 * Performs a LIKE '%value%' search against bitmap values.
	 *
	 * @param int    $field_id Field ID.
	 * @param string $value    Value to search for (will be wrapped with wildcards).
	 * @return array Array of value => ['bitmap' => Bitmap, 'count' => int].
	 */
	public static function get_field_bitmaps_like( $field_id, $value ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		$like = '%' . $wpdb->esc_like( $value ) . '%';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT value, bitmap_data, object_count, max_object_id
				FROM %i
				WHERE field_id = %d AND value LIKE %s
				ORDER BY object_count DESC',
				$table_name,
				$field_id,
				$like
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$bitmaps = array();
		foreach ( $rows as $row ) {
			if ( empty( $row->bitmap_data ) ) {
				continue;
			}
			$bitmap = Bitmap::decompress( $row->bitmap_data );
			if ( $bitmap ) {
				$bitmaps[ $row->value ] = array(
					'bitmap' => $bitmap,
					'count'  => (int) $row->object_count,
				);
			}
		}

		return $bitmaps;
	}

	/**
	 * Get bitmaps for multiple fields of different types in a SINGLE query.
	 *
	 * This is a unified batching method that handles choice, range, search,
	 * and advanced field types, executing them all in one database query.
	 *
	 * @since 3.0.8
	 *
	 * @param array $fields_config Map of field_id => config with 'type' key.
	 *                             Example:
	 *                             [
	 *                               1 => ['values' =>  ['value1', 'value2']],
	 *                               2 => ['values' => [ '10', '20']],
	 *                             ].
	 * @return array [ field_id => [ value => ['bitmap' => Bitmap, 'count' => int], ... ], ... ].
	 */
	public static function get_batched_field_bitmaps( $fields_config ) {

		if ( empty( $fields_config ) ) {
			return array();
		}

		$results         = array();
		$fields_to_query = array();

		// Check cache for each field first.
		if ( self::$enable_cache ) {
			foreach ( $fields_config as $field_id => $config ) {
				$field_id = (int) $field_id;

				// If requesting all values and field is fully loaded.
				if ( empty( $config['values'] ) ) {
					if ( ! empty( self::$cache[ $field_id ]['all_loaded'] ) ) {
						$results[ $field_id ] = self::get_field_bitmaps_from_cache( $field_id );
						continue;
					}
				} else {
					// Requesting specific values - check if all are cached.
					$all_cached     = true;
					$cached_bitmaps = array();
					foreach ( $config['values'] as $value ) {
						if ( isset( self::$cache[ $field_id ]['bitmaps'][ $value ] ) ) {
							$bitmap                   = self::$cache[ $field_id ]['bitmaps'][ $value ];
							$cached_bitmaps[ $value ] = array(
								'bitmap' => $bitmap,
								'count'  => $bitmap->count(),
							);
						} else {
							$all_cached = false;
							break;
						}
					}
					if ( $all_cached ) {
						$results[ $field_id ] = $cached_bitmaps;
						continue;
					}
				}

				// Field not fully cached, need to query.
				$fields_to_query[ $field_id ] = $config;
			}
		} else {
			$fields_to_query = $fields_config;
		}

		// If all fields were cached, return early.
		if ( empty( $fields_to_query ) ) {
			foreach ( array_keys( $fields_config ) as $field_id ) {
				if ( ! isset( $results[ $field_id ] ) ) {
					$results[ $field_id ] = array();
				}
			}
			return $results;
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// Build WHERE clauses for each field type.
		$where_parts = array();

		foreach ( $fields_to_query as $field_id => $config ) {
			$where_parts[] = self::build_where( $field_id, $config );
		}

		// Combine all WHERE clauses with OR.
		$where_clause = implode( ' OR ', $where_parts );

		// Execute SINGLE batch query for ALL field types.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT field_id, value, bitmap_data, object_count, max_object_id
				 FROM %i
				 WHERE {$where_clause}
				 ORDER BY field_id, object_count DESC",
				$table_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Group results by field_id (same format for all types).
		$db_results = array();
		foreach ( $rows as $row ) {
			$field_id = (int) $row->field_id;

			// Handle empty bitmaps - they represent values with 0 count.
			if ( empty( $row->bitmap_data ) ) {
				$bitmap = new Bitmap();
			} else {
				$bitmap = Bitmap::decompress( $row->bitmap_data );
			}

			if ( $bitmap ) {
				if ( ! isset( $db_results[ $field_id ] ) ) {
					$db_results[ $field_id ] = array();
				}

				$db_results[ $field_id ][ $row->value ] = array(
					'bitmap' => $bitmap,
					'count'  => (int) $row->object_count,
				);
			}
		}

		// Cache DB results.
		if ( self::$enable_cache ) {
			// Cache fields that had results.
			foreach ( $db_results as $field_id => $field_bitmaps ) {
				self::init_field_cache( $field_id );
				foreach ( $field_bitmaps as $value => $data ) {
					self::$cache[ $field_id ]['bitmaps'][ $value ] = $data['bitmap'];
				}
				// Mark fully loaded if we fetched all values for this field.
				if ( empty( $fields_to_query[ $field_id ]['values'] ) ) {
					self::$cache[ $field_id ]['all_loaded']  = true;
					self::$cache[ $field_id ]['has_bitmaps'] = true;
				} else {
					// Specific values found, we know bitmaps exist.
					self::$cache[ $field_id ]['has_bitmaps'] = true;
				}
			}

			// Handle fields that were queried but had NO results.
			foreach ( $fields_to_query as $field_id => $config ) {
				if ( isset( $db_results[ $field_id ] ) ) {
					continue; // Already handled above.
				}
				// Only set has_bitmaps=false if we queried ALL values and found none.
				if ( empty( $config['values'] ) ) {
					self::init_field_cache( $field_id );
					self::$cache[ $field_id ]['all_loaded']  = true;
					self::$cache[ $field_id ]['has_bitmaps'] = false;
				}
				// If specific values queried and empty, don't set has_bitmaps.
			}
		}

		// Merge cached and DB results.
		$results = array_replace( $results, $db_results );

		// Ensure all requested fields are in result.
		foreach ( array_keys( $fields_config ) as $field_id ) {
			if ( ! isset( $results[ $field_id ] ) ) {
				$results[ $field_id ] = array();
			}
		}

		return $results;
	}

	/**
	 * Build WHERE clause for choice field type.
	 *
	 * @since 3.0.8
	 *
	 * @param int   $field_id Field ID.
	 * @param array $config   Config array, may contain 'values' key.
	 * @return string SQL WHERE clause part.
	 */
	private static function build_where( $field_id, $config ) {

		global $wpdb;

		if ( empty( $config['values'] ) ) {
			// Fetch all values for this field.
			return $wpdb->prepare( 'field_id = %d', $field_id );
		}

		// Specific values requested.
		$placeholders = implode( ',', array_fill( 0, count( $config['values'] ), '%s' ) );
		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->prepare(
			"(field_id = %d AND value IN ($placeholders))",
			array_merge( array( $field_id ), $config['values'] )
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $result;
	}

	/**
	 * Store a bitmap.
	 *
	 * @param int    $field_id Field ID.
	 * @param string $value    Field value.
	 * @param Bitmap $bitmap   Bitmap object.
	 * @return bool True on success.
	 */
	public static function store_bitmap( $field_id, $value, Bitmap $bitmap ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		// Compress bitmap for storage.
		$compressed_data = $bitmap->compress();
		$object_count    = $bitmap->count();
		$max_object_id   = $bitmap->get_max_id();

		$data = array(
			'field_id'      => $field_id,
			'value'         => $value,
			'bitmap_data'   => $compressed_data,
			'object_count'  => $object_count,
			'max_object_id' => $max_object_id,
			'last_updated'  => \current_time( 'mysql' ),
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (field_id, value, bitmap_data, object_count, max_object_id, last_updated)
				 VALUES (%d, %s, %s, %d, %d, %s)
				 ON DUPLICATE KEY UPDATE
					bitmap_data = VALUES(bitmap_data),
					object_count = VALUES(object_count),
					max_object_id = VALUES(max_object_id),
					last_updated = VALUES(last_updated)',
				$table_name,
				$data['field_id'],
				$data['value'],
				$data['bitmap_data'],
				$data['object_count'],
				$data['max_object_id'],
				$data['last_updated']
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Update cache on success (not delete).
		if ( $result !== false && self::$enable_cache ) {
			self::init_field_cache( $field_id );
			self::$cache[ $field_id ]['bitmaps'][ $value ] = $bitmap;
			self::$cache[ $field_id ]['has_bitmaps']       = true;

			// Invalidate values list since we added/updated a value.
			unset( self::$cache[ $field_id ]['values'] );
		}

		return false !== $result;
	}

	/**
	 * Delete a bitmap.
	 *
	 * @param int    $field_id Field ID.
	 * @param string $value    Field value.
	 * @return bool True on success.
	 */
	public static function delete_bitmap( $field_id, $value ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array(
				'field_id' => $field_id,
				'value'    => $value,
			),
			array( '%d', '%s' )
		);

		// Clear cache for this entry and invalidate field metadata.
		if ( self::$enable_cache && isset( self::$cache[ $field_id ] ) ) {
			unset( self::$cache[ $field_id ]['bitmaps'][ $value ] );
			unset( self::$cache[ $field_id ]['values'] );
			unset( self::$cache[ $field_id ]['all_loaded'] );
			// Note: has_bitmaps might still be true (other values exist).
		}

		return false !== $result;
	}

	/**
	 * Delete all bitmaps for a field.
	 *
	 * @param int $field_id Field ID.
	 * @return bool True on success.
	 */
	public static function delete_field_bitmaps( $field_id ) {
		global $wpdb;

		// Get table without installing - if it doesn't exist, nothing to delete.
		$table = Manager::get_table( false );

		$result = false;
		if ( $table && $table->exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->delete(
				$table->get_table_name(),
				array( 'field_id' => $field_id ),
				array( '%d' )
			);
		}

		// Clear entire field from cache.
		if ( self::$enable_cache ) {
			unset( self::$cache[ $field_id ] );
		}

		return false !== $result;
	}

	/**
	 * Update bitmap for a specific post.
	 *
	 * @param int   $post_id     Post ID.
	 * @param array $field_values Array of field_id => values for this post.
	 * @return bool True on success.
	 */
	public static function update_post_in_bitmaps( $post_id, $field_values ) {
		$success = true;

		foreach ( $field_values as $field_id => $values ) {
			// Ensure values is an array.
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			foreach ( $values as $value ) {
				// Get existing bitmap.
				$bitmap = self::get_bitmap( $field_id, $value );

				if ( ! $bitmap ) {
					// Create new bitmap.
					$bitmap = new Bitmap();
				}

				// Add post using direct bit operation (only if not already set).
				if ( ! $bitmap->get_bit( $post_id ) ) {
					$bitmap->set_bit( $post_id );

					// Store updated bitmap.
					if ( ! self::store_bitmap( $field_id, $value, $bitmap ) ) {
						$success = false;
					}
				}
			}
		}

		return $success;
	}

	/**
	 * Remove post from bitmaps.
	 *
	 * @param int   $post_id     Post ID.
	 * @param array $field_values Array of field_id => values to remove from.
	 * @return bool True on success.
	 */
	public static function remove_post_from_bitmaps( $post_id, $field_values ) {
		$success = true;

		foreach ( $field_values as $field_id => $values ) {
			// Ensure values is an array.
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			foreach ( $values as $value ) {
				// Get existing bitmap.
				$bitmap = self::get_bitmap( $field_id, $value );

				if ( ! $bitmap ) {
					continue; // Nothing to remove.
				}

				// Quick check: is post in this bitmap? Skip if not.
				if ( ! $bitmap->get_bit( $post_id ) ) {
					continue; // Post not in bitmap - skip update.
				}

				// Remove post using direct bit operation.
				$bitmap->unset_bit( $post_id );

				if ( $bitmap->is_empty() ) {
					// Delete bitmap if no posts remain.
					if ( ! self::delete_bitmap( $field_id, $value ) ) {
						$success = false;
					}
				} elseif ( ! self::store_bitmap( $field_id, $value, $bitmap ) ) {
					// Store updated bitmap.
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Get statistics about stored bitmaps.
	 *
	 * @return array Statistics.
	 */
	public static function get_statistics() {
		// Get table without auto-creation (pass false).
		$table = Manager::get_table( false );

		// Return empty stats if table doesn't exist.
		if ( ! $table || ! $table->exists() ) {
			return array(
				'total_bitmaps' => 0,
				'by_field'      => array(),
				'storage'       => null,
			);
		}

		global $wpdb;
		$table_name = $table->get_table_name();

		$stats = array();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Total count.
		$stats['total_bitmaps'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name )
		);

		// By field.
		$stats['by_field'] = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT field_id, COUNT(*) as count, SUM(object_count) as total_posts
				FROM %i
				GROUP BY field_id
				ORDER BY count DESC',
				$table_name
			),
			ARRAY_A
		);

		// Storage size.
		$stats['storage'] = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					SUM(LENGTH(bitmap_data)) as total_compressed_size,
					AVG(LENGTH(bitmap_data)) as avg_compressed_size,
					MAX(LENGTH(bitmap_data)) as max_compressed_size,
					AVG(object_count) as avg_posts_per_bitmap,
					MAX(object_count) as max_posts_per_bitmap
				FROM %i',
				$table_name
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $stats;
	}

	/**
	 * Initialize field cache structure if not exists.
	 *
	 * @param int $field_id Field ID.
	 */
	private static function init_field_cache( $field_id ) {
		if ( ! isset( self::$cache[ $field_id ] ) ) {
			self::$cache[ $field_id ] = array(
				'bitmaps' => array(),
			);
		}
	}

	/**
	 * Get all bitmaps for a field from cache in standard format.
	 *
	 * @param int $field_id Field ID.
	 * @return array Array of value => ['bitmap' => Bitmap, 'count' => int].
	 */
	private static function get_field_bitmaps_from_cache( $field_id ) {
		$bitmaps = array();

		if ( ! isset( self::$cache[ $field_id ]['bitmaps'] ) ) {
			return $bitmaps;
		}

		foreach ( self::$cache[ $field_id ]['bitmaps'] as $value => $bitmap ) {
			$bitmaps[ $value ] = array(
				'bitmap' => $bitmap,
				'count'  => $bitmap->count(),
			);
		}

		return $bitmaps;
	}



	/**
	 * Enable or disable caching.
	 *
	 * @param bool $enable Whether to enable caching.
	 */
	public static function set_caching( $enable ) {
		self::$enable_cache = (bool) $enable;
		if ( ! $enable ) {
			self::flush();
		}
	}

	/**
	 * Check if bitmaps are available for a field.
	 *
	 * @param int $field_id Field ID.
	 * @return bool True if bitmaps exist.
	 */
	public static function has_bitmaps_for_field( $field_id ) {

		// Check cache first.
		if ( self::$enable_cache && isset( self::$cache[ $field_id ]['has_bitmaps'] ) ) {
			return self::$cache[ $field_id ]['has_bitmaps'];
		}

		// Also check if we have any cached bitmaps for this field.
		if ( self::$enable_cache && ! empty( self::$cache[ $field_id ]['bitmaps'] ) ) {
			self::$cache[ $field_id ]['has_bitmaps'] = true;
			return true;
		}

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

		// Cache the result.
		if ( self::$enable_cache ) {
			self::init_field_cache( $field_id );
			self::$cache[ $field_id ]['has_bitmaps'] = $count > 0;
		}

		return $count > 0;
	}

	/**
	 * Get all unique values for a field from the bitmap table.
	 *
	 * @since 3.0.8
	 *
	 * @param int $field_id The field ID to query.
	 * @return array Array of unique values for this field, sorted alphabetically.
	 */
	public static function get_unique_field_values( $field_id ) {

		// Check cache first.
		if ( self::$enable_cache && isset( self::$cache[ $field_id ]['values'] ) ) {
			return self::$cache[ $field_id ]['values'];
		}

		// If all bitmaps are loaded, extract values from cache.
		if ( self::$enable_cache && ! empty( self::$cache[ $field_id ]['all_loaded'] ) ) {
			$values = array_keys( self::$cache[ $field_id ]['bitmaps'] ?? array() );
			sort( $values );
			self::$cache[ $field_id ]['values'] = $values;
			return $values;
		}

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$values = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT value
			FROM %i
			WHERE field_id = %d
			ORDER BY value ASC',
				$table_name,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$values = $values ? $values : array();

		// Cache the result.
		if ( self::$enable_cache ) {
			self::init_field_cache( $field_id );
			self::$cache[ $field_id ]['values']      = $values;
			self::$cache[ $field_id ]['has_bitmaps'] = ! empty( $values );
		}

		return $values;
	}


	/**
	 * Reset bitmaps.
	 *
	 * Clears all cached bitmap data & truncates tables for this class.
	 */
	public static function reset() {

		$bitmap_table = Manager::get_table( false );
		// Truncate bitmap index table (faster than DELETE for full clear).
		if ( $bitmap_table && $bitmap_table->exists() ) {
			$bitmap_table->truncate();
		}

		// Clear cache.
		self::flush();
	}


	/**
	 * Reset the bitmap cache.
	 *
	 * Clears all cached bitmap data for this class.
	 */
	public static function flush() {
		self::$cache = array();
	}
}
