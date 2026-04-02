<?php
/**
 * Tiered Cache Class
 *
 * Instance-based multi-tier caching with configurable layers.
 * Uses versioned-prefix invalidation pattern (WooCommerce style).
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache
 */

namespace Search_Filter_Pro\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiered Cache
 *
 * Multi-tier caching with configurable layers per instance.
 *
 * TIERS:
 * - L1 (Memory): Within-request (~0.01ms) - always enabled
 * - L2 (APCu): Cross-request, if enabled (~0.01ms, in-process)
 * - L3 (wp_cache): Uses Redis/Memcached if external object cache available
 * - L4 (Database): Persistent storage via Database_Cache
 *
 * USAGE:
 *
 * // Query cache - all layers
 * $cache = new Tiered_Cache('query_cache_42');
 * $cache->set('my_key', $data);
 * $data = $cache->get('my_key', $found);
 *
 * // Parent map - skip database (raw binary format)
 * $cache = new Tiered_Cache('parent_map', [
 *     'layers' => ['memory', 'apcu', 'wp_cache'],
 *     'format' => 'raw',
 * ]);
 *
 * // Invalidate entire group
 * $cache->invalidate();
 *
 * // Static invalidation from anywhere
 * Tiered_Cache::invalidate_group('query_cache_42');
 * Tiered_Cache::invalidate_query_cache(42);
 *
 * @since 3.2.0
 */
class Tiered_Cache {

	/**
	 * Group prefix for cache keys.
	 */
	const GROUP_PREFIX = 'sfpro_';

	/**
	 * Default TTL (24 hours).
	 */
	const DEFAULT_TTL = 86400;

	/**
	 * Default layers (all enabled).
	 */
	const DEFAULT_LAYERS = array( 'memory', 'apcu', 'wp_cache', 'database' );

	/**
	 * L1 cache: Request-level memory (shared across instances).
	 * Structure: [versioned_key => value, ...]
	 *
	 * @var array
	 */
	private static $memory_cache = array();

	/**
	 * Cached group versions (avoid repeated lookups).
	 *
	 * @var array
	 */
	private static $group_versions = array();

	/**
	 * Cached capability flags.
	 *
	 * @var array|null
	 */
	private static $capabilities = null;

	/**
	 * Instance cache group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Active layers for this instance.
	 *
	 * @var array
	 */
	private $layers;

	/**
	 * Format mode for this instance ('auto' or 'raw').
	 *
	 * @var string
	 */
	private $format;

	/**
	 * Default TTL for this instance.
	 *
	 * @var int
	 */
	private $ttl;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 *
	 * @param string $group Cache group name.
	 * @param array  $args {
	 *     Optional configuration.
	 *
	 *     @type array  $layers Which cache layers to use. Default all.
	 *     @type string $format 'auto' (serialize/compress as needed) or 'raw' (store as-is).
	 *     @type int    $ttl    Default TTL in seconds.
	 * }
	 */
	public function __construct( $group, $args = array() ) {
		$this->group  = $group;
		$this->layers = $args['layers'] ?? self::DEFAULT_LAYERS;
		$this->format = $args['format'] ?? 'auto';
		$this->ttl    = $args['ttl'] ?? self::DEFAULT_TTL;

		// Filter out unavailable layers.
		$this->layers = $this->filter_available_layers( $this->layers );
	}

	/**
	 * Get a value from cache.
	 *
	 * Checks L1 → L2 → L3 → L4, warms upper tiers on hit.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key   Cache key.
	 * @param bool   $found Optional. Whether the key was found (passed by reference).
	 * @return mixed Cached value or null if not found.
	 */
	public function get( $key, &$found = null ) {
		$found         = false;
		$versioned_key = $this->get_versioned_key( $key );

		// L1: Memory (fastest, always checked).
		if ( $this->has_layer( 'memory' ) && isset( self::$memory_cache[ $versioned_key ] ) ) {
			$found = true;
			return self::$memory_cache[ $versioned_key ];
		}

		// L2: APCu (cross-request, if available).
		if ( $this->has_layer( 'apcu' ) ) {
			$apcu_key = self::GROUP_PREFIX . $versioned_key;
			$success  = false;
			$value    = apcu_fetch( $apcu_key, $success );

			if ( $success ) {
				// Warm L1.
				if ( $this->has_layer( 'memory' ) ) {
					self::$memory_cache[ $versioned_key ] = $value;
				}
				$found = true;
				return $value;
			}
		}

		// L3: wp_cache (Redis/Memcached if external object cache).
		if ( $this->has_layer( 'wp_cache' ) ) {
			$wp_group = self::GROUP_PREFIX . $this->group;
			$wp_found = false;
			$value    = wp_cache_get( $versioned_key, $wp_group, false, $wp_found );

			if ( $wp_found ) {
				// Warm L1 and L2.
				if ( $this->has_layer( 'memory' ) ) {
					self::$memory_cache[ $versioned_key ] = $value;
				}
				if ( $this->has_layer( 'apcu' ) ) {
					$apcu_key = self::GROUP_PREFIX . $versioned_key;
					apcu_store( $apcu_key, $value, $this->ttl );
				}
				$found = true;
				return $value;
			}
		}

		// L4: Database (persistent).
		if ( $this->has_layer( 'database' ) ) {
			$db_found = false;
			$value    = Database_Cache::get( $key, $this->group, $this->format, $db_found );

			if ( $db_found ) {
				// Warm L1, L2, L3.
				if ( $this->has_layer( 'memory' ) ) {
					self::$memory_cache[ $versioned_key ] = $value;
				}
				if ( $this->has_layer( 'apcu' ) ) {
					$apcu_key = self::GROUP_PREFIX . $versioned_key;
					apcu_store( $apcu_key, $value, $this->ttl );
				}
				if ( $this->has_layer( 'wp_cache' ) ) {
					$wp_group = self::GROUP_PREFIX . $this->group;
					wp_cache_set( $versioned_key, $value, $wp_group, $this->ttl );
				}
				$found = true;
				return $value;
			}
		}

		return null;
	}

	/**
	 * Set a value in cache.
	 *
	 * Stores in all available tiers.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Optional. Time to live in seconds.
	 * @return bool Success.
	 */
	public function set( $key, $value, $ttl = null ) {
		if ( null === $ttl ) {
			$ttl = $this->ttl;
		}

		$versioned_key = $this->get_versioned_key( $key );

		// L1: Memory.
		if ( $this->has_layer( 'memory' ) ) {
			self::$memory_cache[ $versioned_key ] = $value;
		}

		// L2: APCu.
		if ( $this->has_layer( 'apcu' ) ) {
			$apcu_key = self::GROUP_PREFIX . $versioned_key;
			apcu_store( $apcu_key, $value, $ttl );
		}

		// L3: wp_cache.
		if ( $this->has_layer( 'wp_cache' ) ) {
			$wp_group = self::GROUP_PREFIX . $this->group;
			wp_cache_set( $versioned_key, $value, $wp_group, $ttl );
		}

		// L4: Database.
		if ( $this->has_layer( 'database' ) ) {
			Database_Cache::set( $key, $value, $this->group, $this->format, $ttl );
		}

		return true;
	}

	/**
	 * Delete a specific key from cache.
	 *
	 * Removes from all tiers.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key Cache key.
	 * @return bool Success.
	 */
	public function delete( $key ) {
		$versioned_key = $this->get_versioned_key( $key );

		// L1: Memory.
		unset( self::$memory_cache[ $versioned_key ] );

		// L2: APCu.
		if ( $this->has_layer( 'apcu' ) ) {
			$apcu_key = self::GROUP_PREFIX . $versioned_key;
			apcu_delete( $apcu_key );
		}

		// L3: wp_cache.
		if ( $this->has_layer( 'wp_cache' ) ) {
			$wp_group = self::GROUP_PREFIX . $this->group;
			wp_cache_delete( $versioned_key, $wp_group );
		}

		// L4: Database.
		if ( $this->has_layer( 'database' ) ) {
			Database_Cache::delete( $key, $this->group );
		}

		return true;
	}

	/**
	 * Invalidate this instance's cache group.
	 *
	 * Uses versioned-prefix pattern for L1-L3, DELETE for L4.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Success.
	 */
	public function invalidate() {
		return self::invalidate_group( $this->group );
	}

	/**
	 * Get or compute a cached value.
	 *
	 * If key exists, return cached value. Otherwise, call callback,
	 * cache the result, and return it.
	 *
	 * @since 3.2.0
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Function to compute value if not cached.
	 * @param int      $ttl      Optional. Time to live in seconds.
	 * @return mixed Cached or computed value.
	 */
	public function remember( $key, $callback, $ttl = null ) {
		$found = false;
		$value = $this->get( $key, $found );

		if ( $found ) {
			return $value;
		}

		// Compute value.
		$value = $callback();

		// Cache result.
		$this->set( $key, $value, $ttl );

		return $value;
	}

	/**
	 * Get the cache group for this instance.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_group() {
		return $this->group;
	}

	/**
	 * Check if a layer is enabled for this instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $layer Layer name.
	 * @return bool
	 */
	private function has_layer( $layer ) {
		return in_array( $layer, $this->layers, true );
	}

	/**
	 * Get versioned cache key.
	 *
	 * Prepends group version to key for invalidation pattern.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key Original cache key.
	 * @return string Versioned key.
	 */
	private function get_versioned_key( $key ) {
		$version = self::get_group_version( $this->group );
		return $version . '_' . $this->group . '_' . $key;
	}

	/**
	 * Filter layers based on availability.
	 *
	 * @since 3.2.0
	 *
	 * @param array $layers Requested layers.
	 * @return array Available layers.
	 */
	private function filter_available_layers( $layers ) {
		return array_filter(
			$layers,
			function ( $layer ) {
				switch ( $layer ) {
					case 'memory':
						return true; // Always available.
					case 'apcu':
						return self::has_apcu();
					case 'wp_cache':
						return wp_using_ext_object_cache();
					case 'database':
						return true; // Always available (our table).
					default:
						return false;
				}
			}
		);
	}

	/** Static Methods ********************************************************/

	/**
	 * Invalidate an entire cache group.
	 *
	 * Uses versioned-prefix pattern: increment version number so all
	 * existing keys become orphaned. Old keys are cleaned by LRU eviction.
	 * Database entries are deleted directly.
	 *
	 * @since 3.2.0
	 *
	 * @param string $group Cache group to invalidate.
	 * @return bool Success.
	 */
	public static function invalidate_group( $group ) {
		// Clear cached version for this group.
		unset( self::$group_versions[ $group ] );

		// Clear memory cache entries (we can't easily filter by group).
		self::$memory_cache = array();

		$version_key      = '_version_' . self::GROUP_PREFIX . $group;
		$wp_version_group = self::GROUP_PREFIX . 'versions';

		// wp_cache is source of truth (shared across servers).
		// Initialize atomically if needed, then increment.
		wp_cache_add( $version_key, 0, $wp_version_group, 0 );
		$new_version = wp_cache_incr( $version_key, 1, $wp_version_group );

		if ( false !== $new_version ) {
			// Sync new version to APCu for faster local lookups.
			if ( self::has_apcu() ) {
				apcu_store( $version_key, $new_version, 0 );
			}

			// Cache in memory for this request.
			self::$group_versions[ $group ] = $new_version;
		}

		// Delete from database (L4 doesn't use versioning).
		Database_Cache::delete_group( $group );

		return true;
	}

	/**
	 * Convenience: Invalidate query cache by query ID.
	 *
	 * @since 3.2.0
	 *
	 * @param int $query_id Query ID.
	 * @return bool Success.
	 */
	public static function invalidate_query_cache( $query_id ) {
		return self::invalidate_group( 'query_cache_' . $query_id );
	}

	/**
	 * Get current version number for a group.
	 *
	 * Checks memory → APCu → wp_cache, warming upper tiers on hit.
	 * wp_cache is the source of truth for multi-server consistency.
	 *
	 * @since 3.2.0
	 *
	 * @param string $group Cache group.
	 * @return int Version number.
	 */
	private static function get_group_version( $group ) {
		// Check cached version first (fastest).
		if ( isset( self::$group_versions[ $group ] ) ) {
			return self::$group_versions[ $group ];
		}

		$version_key      = '_version_' . self::GROUP_PREFIX . $group;
		$wp_version_group = self::GROUP_PREFIX . 'versions';

		// Try APCu (cross-request, local to server).
		if ( self::has_apcu() ) {
			$success = false;
			$version = apcu_fetch( $version_key, $success );
			if ( $success ) {
				self::$group_versions[ $group ] = $version;
				return $version;
			}
		}

		// Try wp_cache (source of truth, shared across servers).
		$wp_found = false;
		$version  = wp_cache_get( $version_key, $wp_version_group, false, $wp_found );

		if ( $wp_found ) {
			// Warm APCu for faster local lookups.
			if ( self::has_apcu() ) {
				apcu_store( $version_key, $version, 0 );
			}
			self::$group_versions[ $group ] = $version;
			return $version;
		}

		// No version exists - initialize with timestamp.
		// Use wp_cache_add to avoid race conditions.
		$version = time();
		wp_cache_add( $version_key, $version, $wp_version_group, 0 );

		// Re-fetch to get the actual value (in case another process won the race).
		$version = wp_cache_get( $version_key, $wp_version_group );

		// Sync to APCu.
		if ( self::has_apcu() ) {
			apcu_store( $version_key, $version, 0 );
		}

		self::$group_versions[ $group ] = $version;
		return $version;
	}

	/**
	 * Check if APCu is available and enabled.
	 *
	 * Respects the search-filter-pro/cache/use_apcu filter.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	private static function has_apcu() {
		if ( null === self::$capabilities ) {
			self::$capabilities = array(
				'apcu' => function_exists( 'apcu_fetch' ) && function_exists( 'apcu_enabled' ) && apcu_enabled(),
			);
		}

		if ( ! self::$capabilities['apcu'] ) {
			return false;
		}

		/**
		 * Filter whether to use APCu caching.
		 *
		 * @since 3.2.0
		 *
		 * @param bool $use_apcu Whether to use APCu. Default true.
		 */
		return apply_filters( 'search-filter-pro/cache/use_apcu', true );
	}

	/**
	 * Reset all caches.
	 *
	 * Clears everything: in-memory caches AND database storage.
	 * This is the "nuclear option" - use when you need a complete reset.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		// Clear in-memory caches.
		self::flush();

		// Clear persistent database storage.
		Database_Cache::reset();
	}

	/**
	 * Flush in-memory caches only.
	 *
	 * Clears static vars and cached versions but preserves database storage.
	 * Use this in test tearDown when you only need to clear request-level state.
	 *
	 * @since 3.2.0
	 */
	public static function flush() {
		self::$memory_cache   = array();
		self::$group_versions = array();
		self::$capabilities   = null;
	}

	/**
	 * Get cache statistics.
	 *
	 * For debugging purposes.
	 *
	 * @since 3.2.0
	 *
	 * @return array Cache statistics.
	 */
	public static function get_stats() {
		return array(
			'memory_cache_count' => count( self::$memory_cache ),
			'group_versions'     => self::$group_versions,
			'apcu_available'     => self::has_apcu(),
			'wp_cache_available' => wp_using_ext_object_cache(),
		);
	}
}
