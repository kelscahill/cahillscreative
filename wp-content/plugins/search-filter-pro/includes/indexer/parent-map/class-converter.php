<?php
/**
 * Parent Map Converter - Optimized for 400k-1M child→parent mappings
 *
 * Converts child IDs to parent IDs with three-tier caching.
 * Works with any hierarchical data: product variations, hierarchical posts, etc.
 *
 * Performance targets:
 * - <1ms for 20,000 ID conversions (cached)
 * - <2ms for 50,000 ID conversions (cached)
 * - Memory: 6-25MB for 400k-1M mappings
 *
 * @package Search_Filter_Pro\Indexer
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Parent_Map;

use Search_Filter_Pro\Indexer\Parent_Map\Database\Query as Parent_Map_Query;
use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Utils\Compression;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parent Map Converter Class
 *
 * @since 3.2.0
 */
class Converter {

	/**
	 * Cache TTL (24 hours)
	 */
	const CACHE_TTL = 86400;

	/**
	 * Cache group prefix for parent map.
	 */
	const CACHE_GROUP_PREFIX = 'parent_map_';

	/**
	 * Request-level cache keyed by source (loaded once per source per request)
	 *
	 * @var array<string, array>
	 */
	private static $memory_cache = array();

	/**
	 * Tiered_Cache instances per source group.
	 *
	 * @var array<string, Tiered_Cache>
	 */
	private static $cache_instances = array();

	/**
	 * Track known source groups for full invalidation.
	 *
	 * @var array<string>
	 */
	private static $known_sources = array();

	/**
	 * Get the Tiered_Cache instance for a specific source group.
	 *
	 * Each source gets its own cache group for independent invalidation.
	 * Parent map uses Memory → APCu → wp_cache only (no database layer).
	 * Uses raw format since we handle our own compression via pack/unpack.
	 *
	 * @since 3.2.0
	 *
	 * @param string $source Source identifier or '_all' for all sources.
	 * @return Tiered_Cache
	 */
	private static function get_cache_instance( $source ) {
		if ( ! isset( self::$cache_instances[ $source ] ) ) {
			self::$cache_instances[ $source ] = new Tiered_Cache(
				self::CACHE_GROUP_PREFIX . $source,
				array(
					'layers' => array( 'memory', 'apcu', 'wp_cache' ),
					'format' => 'raw',
					'ttl'    => self::CACHE_TTL,
				)
			);

			// Track this source for full invalidation.
			if ( $source !== '_all' && ! in_array( $source, self::$known_sources, true ) ) {
				self::$known_sources[] = $source;
			}
		}
		return self::$cache_instances[ $source ];
	}

	/**
	 * Convert post type slugs to source identifiers.
	 *
	 * Used to thread post type information from queries to parent map lookups.
	 *
	 * @since 3.2.0
	 *
	 * @param array $post_types Array of post type slugs (e.g., ['product', 'product_variation']).
	 * @return array Array of source identifiers (e.g., ['post-product', 'post-product_variation']).
	 */
	public static function post_types_to_sources( $post_types ) {
		if ( empty( $post_types ) ) {
			return array();
		}

		return array_map(
			function ( $post_type ) {
				return 'post-' . $post_type;
			},
			$post_types
		);
	}

	/**
	 * Convert child IDs to parent IDs with automatic optimization
	 *
	 * Automatically selects best method based on array size:
	 * - Small (<1k): Prioritize readability
	 * - Medium (1k-50k): Prioritize speed
	 * - Large (>50k): Prioritize memory
	 *
	 * @param array $ids     Mixed child/parent IDs.
	 * @param array $sources Data source identifiers (e.g., ['woocommerce', 'post']). Empty = all sources.
	 * @return array Deduplicated parent IDs
	 */
	public static function convert_to_parents( $ids, $sources = array() ) {
		$count = count( $ids );

		if ( $count === 0 ) {
			return array();
		}

		// Load mapping for specified sources once per request.
		$map = self::get_mapping_for_sources( $sources );

		// Extract child→parent map for lookup.
		$children_map = $map['children'];

		// Choose optimal conversion method based on size.
		if ( $count < 1000 ) {
			return self::convert_small( $ids, $children_map );
		} elseif ( $count < 50000 ) {
			return self::convert_medium( $ids, $children_map );
		} else {
			return self::convert_large( $ids, $children_map );
		}
	}

	/**
	 * Get mapping for specified sources, loading from cache if needed.
	 *
	 * @param array $sources Data source identifiers. Empty = all sources.
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	private static function get_mapping_for_sources( $sources ) {
		$cache_key = empty( $sources ) ? '_all' : implode( '|', $sources );

		if ( ! isset( self::$memory_cache[ $cache_key ] ) ) {
			self::$memory_cache[ $cache_key ] = self::load_mapping_by_sources( $sources );
		}

		return self::$memory_cache[ $cache_key ];
	}

	/**
	 * Small batch conversion - prioritize readability
	 *
	 * @param array $ids Child IDs.
	 * @param array $map Child ID => Parent ID map.
	 * @return array Parent IDs.
	 */
	private static function convert_small( $ids, $map ) {
		// For small batches, readability > micro-optimization.
		$result = array();
		foreach ( $ids as $id ) {
			$result[] = $map[ $id ] ?? $id;
		}

		return array_unique( $result );
	}

	/**
	 * Medium batch conversion - prioritize speed
	 * Pre-allocated array for 3-5x performance boost
	 *
	 * @param array $ids Child IDs.
	 * @param array $map Child ID => Parent ID map.
	 * @return array Parent IDs.
	 */
	private static function convert_medium( $ids, $map ) {
		$count = count( $ids );

		// Pre-allocate result array (avoids reallocation overhead).
		$result = array_fill( 0, $count, 0 );

		$i = 0;
		foreach ( $ids as $id ) {
			// ?? is 10-15% faster than isset() ternary.
			$result[ $i++ ] = $map[ $id ] ?? $id;
		}

		return array_unique( $result );
	}

	/**
	 * Large batch conversion - prioritize memory
	 * Uses SplFixedArray for 60% memory savings
	 *
	 * @param array $ids Child IDs.
	 * @param array $map Child ID => Parent ID map.
	 * @return array Parent IDs.
	 */
	private static function convert_large( $ids, $map ) {
		$count = count( $ids );

		// SplFixedArray uses 60% less memory than regular arrays.
		$result = new \SplFixedArray( $count );

		$i = 0;
		foreach ( $ids as $id ) {
			$result[ $i++ ] = $map[ $id ] ?? $id;
		}

		// Convert back to regular array for array_unique.
		return array_unique( $result->toArray() );
	}

	/**
	 * Convert without deduplication
	 * Use when deduplication happens later in pipeline
	 * Saves ~1ms by skipping array_unique
	 *
	 * @param array $ids     Child IDs.
	 * @param array $sources Data source identifiers. Empty = all sources.
	 * @return array Parent IDs (may contain duplicates).
	 */
	public static function convert_no_dedup( $ids, $sources = array() ) {
		if ( empty( $ids ) ) {
			return array();
		}

		$map          = self::get_mapping_for_sources( $sources );
		$children_map = $map['children'];
		$count        = count( $ids );
		$result       = array_fill( 0, $count, 0 );

		$i = 0;
		foreach ( $ids as $id ) {
			$result[ $i++ ] = $children_map[ $id ] ?? $id;
		}

		return $result;
	}

	/**
	 * Load mapping for specified sources with tiered caching.
	 *
	 * Uses per-source cache groups for reliable invalidation:
	 * - Single source: cached in source-specific group
	 * - Multiple sources: NOT cached (merged from individual source caches)
	 * - Empty (all): cached in '_all' group
	 *
	 * @param array $sources Data source identifiers. Empty = all sources.
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	private static function load_mapping_by_sources( $sources ) {
		// Empty sources = load all.
		if ( empty( $sources ) ) {
			return self::load_mapping_for_group( '_all', array() );
		}

		// Single source = use source-specific cache.
		if ( count( $sources ) === 1 ) {
			return self::load_mapping_for_group( $sources[0], $sources );
		}

		// Multiple sources = merge from individual caches (not cached as composite).
		return self::merge_mappings_from_sources( $sources );
	}

	/**
	 * Load mapping for a specific cache group.
	 *
	 * @param string $group   Cache group identifier.
	 * @param array  $sources Sources to load from DB on cache miss.
	 * @return array Bidirectional mapping.
	 */
	private static function load_mapping_for_group( $group, $sources ) {
		$cache = self::get_cache_instance( $group );
		$found = false;

		$compressed = $cache->get( 'data', $found );

		if ( $found && $compressed !== null ) {
			return self::unpack_binary( $compressed );
		}

		// Cache miss - load from database.
		$map = self::load_from_database( $sources );

		// Store compressed in cache.
		$compressed = self::pack_binary( $map );
		$cache->set( 'data', $compressed );

		return $map;
	}

	/**
	 * Merge mappings from multiple individual source caches.
	 *
	 * For composite queries, we load each source's cached data
	 * and merge them. This avoids caching composite keys.
	 *
	 * @param array $sources Source identifiers to merge.
	 * @return array Merged bidirectional mapping.
	 */
	private static function merge_mappings_from_sources( $sources ) {
		$parents  = array();
		$children = array();

		foreach ( $sources as $source ) {
			// Load from individual source cache (will populate cache if needed).
			$source_map = self::load_mapping_for_group( $source, array( $source ) );

			// Merge children (first source wins on conflict).
			$children = $children + $source_map['children'];

			// Merge parents (combine child arrays for same parent).
			foreach ( $source_map['parents'] as $parent_id => $child_ids ) {
				if ( ! isset( $parents[ $parent_id ] ) ) {
					$parents[ $parent_id ] = $child_ids;
				} else {
					$parents[ $parent_id ] = array_merge( $parents[ $parent_id ], $child_ids );
				}
			}
		}

		return array(
			'parents'  => $parents,
			'children' => $children,
		);
	}


	/**
	 * Pack and compress parent map for cache storage
	 *
	 * Stores child=>parent pairs (from 'children' key) in compressed format.
	 * The 'parents' map can be rebuilt from these pairs on unpack.
	 *
	 * Format: [child_id1, parent_id1, child_id2, parent_id2, ...] → pack('L*') → gzcompress
	 * Result: 75-80% smaller than uncompressed.
	 *
	 * @param array $map Bidirectional map with 'parents' and 'children' keys.
	 * @return string Compressed binary data.
	 */
	private static function pack_binary( $map ) {
		// Store child=>parent pairs (can rebuild parents map from these).
		$children = isset( $map['children'] ) ? $map['children'] : array();

		// Convert to interleaved pairs array.
		$pairs = array();
		foreach ( $children as $child_id => $parent_id ) {
			$pairs[] = $child_id;
			$pairs[] = $parent_id;
		}

		// Compress using utility (pack + gzcompress, adaptive level 1-2).
		return Compression::compress( $pairs, array( 'preprocess' => 'pack' ) );
	}

	/**
	 * Decompress and unpack parent map from cache
	 *
	 * Decompresses and unpacks, then rebuilds both maps from pairs.
	 *
	 * @param string $compressed Compressed binary data.
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	private static function unpack_binary( $compressed ) {
		// Decompress using utility (gzuncompress + unpack to array).
		$pairs = Compression::decompress( $compressed, array( 'preprocess' => 'pack' ) );

		// Rebuild both maps from interleaved pairs: [child1, parent1, child2, parent2, ...].
		$parents     = array(); // parent_id => [child_ids].
		$children    = array(); // child_id => parent_id.
		$pairs_count = count( $pairs );
		for ( $i = 0; $i < $pairs_count; $i += 2 ) {
			$child_id  = $pairs[ $i ];
			$parent_id = $pairs[ $i + 1 ];

			$parents[ $parent_id ][] = $child_id;
			$children[ $child_id ]   = $parent_id;
		}

		return array(
			'parents'  => $parents,
			'children' => $children,
		);
	}

	/**
	 * Load from MySQL database for specified sources
	 *
	 * @param array $sources Data source identifiers. Empty = all sources.
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	private static function load_from_database( $sources ) {
		if ( empty( $sources ) ) {
			// Load all mappings regardless of source.
			return Parent_Map_Query::get_all_mappings();
		}

		// Load mappings for specific sources and merge.
		$parents  = array();
		$children = array();
		foreach ( $sources as $source ) {
			$source_map = Parent_Map_Query::get_mappings_by_source( $source );

			// Merge children (first source wins on conflict).
			$children = $children + $source_map['children'];

			// Merge parents (combine child arrays for same parent).
			foreach ( $source_map['parents'] as $parent_id => $child_ids ) {
				if ( ! isset( $parents[ $parent_id ] ) ) {
					$parents[ $parent_id ] = $child_ids;
				} else {
					$parents[ $parent_id ] = array_merge( $parents[ $parent_id ], $child_ids );
				}
			}
		}

		return array(
			'parents'  => $parents,
			'children' => $children,
		);
	}

	/**
	 * Invalidate caches for specified sources.
	 *
	 * Call after updating mapping table.
	 *
	 * Uses per-source cache groups for reliable invalidation:
	 * - Each source has its own cache group that can be invalidated independently
	 * - Always invalidates '_all' group since it contains data from all sources
	 * - Empty $sources invalidates all known source groups
	 *
	 * @param array $sources Data source identifiers to invalidate. Empty = full invalidation.
	 */
	public static function reset( $sources = array() ) {
		// Determine which sources to invalidate.
		$sources_to_invalidate = empty( $sources ) ? self::$known_sources : $sources;

		// 1. Clear memory cache entries for affected sources.
		foreach ( $sources_to_invalidate as $source ) {
			unset( self::$memory_cache[ $source ] );

			// Also clear composite keys containing this source.
			foreach ( array_keys( self::$memory_cache ) as $cache_key ) {
				if ( strpos( $cache_key, $source ) !== false ) {
					unset( self::$memory_cache[ $cache_key ] );
				}
			}

			// 2. Invalidate the source's cache group.
			if ( isset( self::$cache_instances[ $source ] ) ) {
				self::$cache_instances[ $source ]->invalidate();
			} else {
				// Create instance just to invalidate (ensures group version is incremented).
				self::get_cache_instance( $source )->invalidate();
			}
		}

		// 3. Always invalidate '_all' group since it contains data from all sources.
		unset( self::$memory_cache['_all'] );
		if ( isset( self::$cache_instances['_all'] ) ) {
			self::$cache_instances['_all']->invalidate();
		} else {
			self::get_cache_instance( '_all' )->invalidate();
		}

		// 4. If full reset, also clear cache instances and known sources.
		if ( empty( $sources ) ) {
			self::$cache_instances = array();
			self::$memory_cache    = array();
			// Keep known_sources for next time.
		}
	}

	/**
	 * Warm cache for specified sources on demand.
	 *
	 * Useful for admin actions or cron jobs.
	 *
	 * @param array $sources Data source identifiers. Empty = warm all sources.
	 * @return bool Success
	 */
	public static function warm_cache( $sources = array() ) {
		try {
			if ( empty( $sources ) ) {
				// Warm '_all' cache group.
				$map        = self::load_from_database( array() );
				$compressed = self::pack_binary( $map );
				self::get_cache_instance( '_all' )->set( 'data', $compressed );
			} elseif ( count( $sources ) === 1 ) {
				// Warm single source cache group.
				$source     = $sources[0];
				$map        = self::load_from_database( $sources );
				$compressed = self::pack_binary( $map );
				self::get_cache_instance( $source )->set( 'data', $compressed );
			} else {
				// Warm each individual source (composites are not cached).
				foreach ( $sources as $source ) {
					$map        = self::load_from_database( array( $source ) );
					$compressed = self::pack_binary( $map );
					self::get_cache_instance( $source )->set( 'data', $compressed );
				}
			}

			return true;
		} catch ( \Exception $e ) {
			Util::error_log( 'Parent Map: Cache warming failed - ' . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array Cache stats.
	 */
	public static function get_cache_stats() {
		$sources_loaded = array_keys( self::$memory_cache );
		$total_mappings = 0;
		foreach ( self::$memory_cache as $map ) {
			// Each $map has 'parents' and 'children' keys.
			// Count the children mappings (child_id => parent_id pairs).
			$total_mappings += isset( $map['children'] ) ? count( $map['children'] ) : 0;
		}

		$tiered_stats = Tiered_Cache::get_stats();

		return array(
			'apcu_available'      => $tiered_stats['apcu_available'],
			'wp_cache_available'  => $tiered_stats['wp_cache_available'] ?? false,
			'sources_loaded'      => $sources_loaded,
			'known_sources'       => self::$known_sources,
			'cache_groups_active' => array_keys( self::$cache_instances ),
			'total_mapping_count' => $total_mappings,
		);
	}

	/**
	 * Convert child bitmap to parent bitmap
	 *
	 * Uses foreach_set_bit() for direct transformation.
	 *
	 * @since 3.2.0
	 * @param Bitmap      $child_bitmap     Bitmap containing child IDs.
	 * @param array       $sources          Data source identifiers. Empty = all sources.
	 * @param Bitmap|null $collapsed_bitmap Optional collapsed (parent-space) bitmap for filtering.
	 *                                      When provided, only parents with bits set in this bitmap are included.
	 * @return Bitmap Bitmap containing unique parent IDs.
	 */
	public static function convert_bitmap_to_parents( Bitmap $child_bitmap, $sources = array(), $collapsed_bitmap = null ) {
		if ( $child_bitmap->is_empty() ) {
			return new Bitmap();
		}

		// Load mapping for specified sources once per request.
		$map           = self::get_mapping_for_sources( $sources );
		$children_map  = $map['children']; // child_id => parent_id.
		$parent_bitmap = new Bitmap();

		/**
		 * The reason we need to check if the ID is in the collapsed bitmap is for an
		 * edge case where a child exists in the WP_Query but the parent does not. When
		 * we convert the child to parent we could inadvertently re-introduce a parent that
		 * would otherwise be excluded by the WP_Query (e.g., private parent products).
		 */

		// Rather than check if $collapsed_bitmap is null on each iteration,
		// we define the appropriate callback once.
		if ( $collapsed_bitmap === null ) {
			// No validity check needed - simpler callback.
			$callback = function ( $child_id ) use ( $parent_bitmap, $children_map ) {
				$parent_id = $children_map[ $child_id ] ?? $child_id;
				$parent_bitmap->set_bit( $parent_id );
			};
		} else {
			// With validity check - filter parents not in collapsed bitmap.
			$callback = function ( $child_id ) use ( $parent_bitmap, $children_map, $collapsed_bitmap ) {
				$parent_id = $children_map[ $child_id ] ?? $child_id;
				if ( $collapsed_bitmap->get_bit( $parent_id ) ) {
					$parent_bitmap->set_bit( $parent_id );
				}
			};
		}

		$child_bitmap->foreach_set_bit( $callback );

		return $parent_bitmap;
	}

	/**
	 * Convert multiple child bitmaps to parent bitmaps (batch)
	 *
	 * Optimized for field counting with 20-50 value bitmaps
	 * Loads mapping once, converts all bitmaps
	 *
	 * @since 3.2.0
	 * @param array       $child_bitmaps    ['value' => Bitmap, ...].
	 * @param array       $sources          Data source identifiers. Empty = all sources.
	 * @param Bitmap|null $collapsed_bitmap Optional collapsed (parent-space) bitmap for filtering.
	 *                                      When provided, only parents with bits set in this bitmap are included.
	 * @return array ['value' => Bitmap, ...] parent bitmaps.
	 */
	public static function convert_bitmaps_batch( array $child_bitmaps, $sources = array(), $collapsed_bitmap = null ) {
		if ( empty( $child_bitmaps ) ) {
			return array();
		}

		// Load mapping for specified sources once for all conversions.
		$map            = self::get_mapping_for_sources( $sources );
		$children_map   = $map['children']; // child_id => parent_id.
		$parent_bitmaps = array();

		// Convert each bitmap using bitmap-native approach.
		foreach ( $child_bitmaps as $key => $child_bitmap ) {
			if ( $child_bitmap->is_empty() ) {
				$parent_bitmaps[ $key ] = new Bitmap();
				continue;
			}

			$parent_bitmap = new Bitmap();

			// Direct transformation with inline validity check.
			$child_bitmap->foreach_set_bit(
				function ( $child_id ) use ( $parent_bitmap, $children_map, $collapsed_bitmap ) {
					$parent_id = $children_map[ $child_id ] ?? $child_id;
					// O(1) check - only include parent if in collapsed bitmap (or if no filter provided).
					if ( $collapsed_bitmap === null || $collapsed_bitmap->get_bit( $parent_id ) ) {
						$parent_bitmap->set_bit( $parent_id );
					}
				}
			);

			$parent_bitmaps[ $key ] = $parent_bitmap;
		}

		return $parent_bitmaps;
	}

	/**
	 * Convert parent IDs to child IDs
	 *
	 * Inverse of convert_to_parents(). Used when WP_Query returns parent IDs
	 * but we need child IDs for bitmap intersection with variation-level indexes.
	 *
	 * @since 3.2.0
	 *
	 * @param array $parent_ids Parent IDs to convert.
	 * @param array $sources    Data source identifiers. Empty = all sources.
	 * @return array Child IDs (includes parents that have no children - simple products).
	 */
	public static function convert_to_children( $parent_ids, $sources = array() ) {
		if ( empty( $parent_ids ) ) {
			return array();
		}

		$map         = self::get_mapping_for_sources( $sources );
		$parents_map = $map['parents']; // parent_id => [child_ids].

		$child_ids = array();
		foreach ( $parent_ids as $parent_id ) {
			if ( isset( $parents_map[ $parent_id ] ) ) {
				foreach ( $parents_map[ $parent_id ] as $child_id ) {
					$child_ids[] = $child_id;
				}
			} else {
				// Simple product (no children) - include self.
				$child_ids[] = $parent_id;
			}
		}

		return array_unique( $child_ids );
	}

	/**
	 * Convert parent IDs to children and build both bitmaps in a single pass.
	 *
	 * Optimized method that avoids redundant iterations by building both
	 * parent and child bitmaps simultaneously. Use instead of separate
	 * Bitmap::from_post_ids() + convert_to_children() + Bitmap::from_post_ids() calls.
	 *
	 * @since 3.2.0
	 *
	 * @param array $parent_ids Array of parent IDs from WP_Query.
	 * @param array $sources    Source identifiers for parent map.
	 * @return array {
	 *     @type Bitmap $parent_bitmap Parent IDs bitmap (collapsed form, for validity filtering).
	 *     @type Bitmap $child_bitmap  Child IDs bitmap (for field value intersection).
	 * }
	 */
	public static function convert_to_children_with_bitmaps( $parent_ids, $sources = array() ) {
		if ( empty( $parent_ids ) ) {
			return array(
				'parent_bitmap' => new Bitmap(),
				'child_bitmap'  => new Bitmap(),
			);
		}

		$map         = self::get_mapping_for_sources( $sources );
		$parents_map = $map['parents']; // parent_id => [child_ids].

		$parent_bitmap = new Bitmap();
		$child_bitmap  = new Bitmap();

		foreach ( $parent_ids as $parent_id ) {
			$parent_id = (int) $parent_id;

			// Set parent bit.
			$parent_bitmap->set_bit( $parent_id );

			// Set child bits (or self if no children).
			if ( isset( $parents_map[ $parent_id ] ) ) {
				foreach ( $parents_map[ $parent_id ] as $child_id ) {
					$child_bitmap->set_bit( $child_id );
				}
			} else {
				// Simple product - include self.
				$child_bitmap->set_bit( $parent_id );
			}
		}

		return array(
			'parent_bitmap' => $parent_bitmap,
			'child_bitmap'  => $child_bitmap,
		);
	}
}
