<?php
/**
 * Database Cache Class.
 *
 * Provides persistent database caching layer (L4) for Tiered_Cache.
 * Handles serialization and compression of values transparently.
 *
 * @since 3.2.0
 * @package Search_Filter_Pro\Cache
 */

namespace Search_Filter_Pro\Cache;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Cache\Database\Query_Direct;
use Search_Filter_Pro\Cache\Database\Table;
use Search_Filter_Pro\Indexer\Utils\Compression;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database Cache Class.
 *
 * Handles database-level caching with automatic format detection,
 * serialization, and compression. Delegates to Compression class for
 * compression decisions.
 *
 * FORMAT VALUES:
 * - 'raw': Explicit bypass, data stored as base64 (for pre-compressed data)
 * - 'processed': String processed through Compression, stored as base64
 * - 'serialized': Non-string serialized via Compression, stored as base64
 *
 * @since 3.2.0
 */
class Database_Cache {

	/**
	 * Table key for cache.
	 *
	 * @var string
	 */
	const TABLE_KEY = 'cache';

	/**
	 * Default TTL (24 hours).
	 *
	 * @var int
	 */
	const DEFAULT_TTL = 86400;

	/**
	 * Get the cache table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $should_use Whether to use (initialize) the table.
	 * @return \Search_Filter_Pro\Database\Engine\Table|null
	 */
	public static function get_table( $should_use = true ) {
		return Table_Manager::get( self::TABLE_KEY, $should_use );
	}

	/**
	 * Get the cache table name.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $should_use Whether to use (initialize) the table.
	 * @return string
	 */
	public static function get_table_name( $should_use = true ) {
		$table = self::get_table( $should_use );
		return $table ? $table->get_table_name() : '';
	}

	/**
	 * Get a value from the database cache.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key    Cache key.
	 * @param string $group  Cache group.
	 * @param string $format Expected format ('auto' or 'raw').
	 * @param bool   $found  Whether the key was found (passed by reference).
	 * @return mixed Cached value or null if not found.
	 */
	public static function get( $key, $group, $format = 'auto', &$found = null ) {
		$found = false;

		$row = Query_Direct::get_row_non_expired( self::hash_key( $key ), $group );

		if ( ! $row ) {
			return null;
		}

		$found = true;

		return self::decode_value( $row->cache_value, $row->format );
	}

	/**
	 * Set a value in the database cache.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $value  Value to cache.
	 * @param string $group  Cache group.
	 * @param string $format Format mode ('auto' or 'raw').
	 * @param int    $ttl    Time to live in seconds.
	 * @return bool Success.
	 */
	public static function set( $key, $value, $group, $format = 'auto', $ttl = self::DEFAULT_TTL ) {
		// Encode the value and determine storage format.
		$encoded = self::encode_value( $value, $format );

		$data = array(
			'cache_group' => $group,
			'cache_key'   => self::hash_key( $key ),
			'cache_value' => $encoded[0],
			'format'      => $encoded[1],
			'expires'     => time() + $ttl,
		);

		// Use upsert for efficient insert-or-update in single query.
		return Query_Direct::upsert( $data );
	}

	/**
	 * Delete a value from the database cache.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @return bool Success.
	 */
	public static function delete( $key, $group ) {
		return Query_Direct::delete( self::hash_key( $key ), $group );
	}

	/**
	 * Delete all values in a cache group.
	 *
	 * @since 3.2.0
	 *
	 * @param string $group Cache group.
	 * @return bool Success.
	 */
	public static function delete_group( $group ) {
		return Query_Direct::delete_group( $group );
	}

	/**
	 * Delete all expired cache entries.
	 *
	 * @since 3.2.0
	 *
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public static function delete_expired() {
		return Query_Direct::delete_expired();
	}

	/**
	 * Reset (truncate) all cache entries.
	 *
	 * Clears all persistent cache data from the database.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Success.
	 */
	public static function reset() {
		$table = self::get_table( false );
		if ( $table && $table->exists() ) {
			return $table->truncate();
		}
		return false;
	}

	/**
	 * Hash a cache key to fit in char(32) column.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key Original cache key.
	 * @return string MD5 hash of the key.
	 */
	private static function hash_key( $key ) {
		return md5( $key );
	}

	/**
	 * Encode a value for database storage.
	 *
	 * All values are base64 encoded for TEXT column safety.
	 * Compression class handles compression decisions internally.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed  $value  Value to encode.
	 * @param string $format Format mode ('auto' or 'raw').
	 * @return array [ stored_value, format_indicator ]
	 */
	private static function encode_value( $value, $format ) {
		// Raw format: bypass Compression, just base64 encode.
		// Use for pre-compressed data that shouldn't be processed again.
		if ( 'raw' === $format ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return array( base64_encode( $value ), 'raw' );
		}

		// Auto format: everything goes through Compression.
		if ( is_string( $value ) ) {
			// String: process without serialization.
			$processed = Compression::compress( $value, array( 'preprocess' => 'none' ) );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return array( base64_encode( $processed ), 'processed' );
		}

		// Non-string: process with serialization.
		$processed = Compression::compress( $value, array( 'preprocess' => 'serialize' ) );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return array( base64_encode( $processed ), 'serialized' );
	}

	/**
	 * Decode a value from database storage.
	 *
	 * @since 3.2.0
	 *
	 * @param string $stored_value  Stored value.
	 * @param string $stored_format Format indicator from database.
	 * @return mixed Decoded value.
	 */
	private static function decode_value( $stored_value, $stored_format ) {
		switch ( $stored_format ) {
			case 'raw':
				// Raw format: just base64 decode.
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				return base64_decode( $stored_value );

			case 'processed':
				// String that went through Compression.
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$decoded = base64_decode( $stored_value );
				return Compression::decompress( $decoded, array( 'preprocess' => 'none' ) );

			case 'serialized':
				// Non-string that went through Compression with serialization.
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$decoded = base64_decode( $stored_value );
				return Compression::decompress( $decoded, array( 'preprocess' => 'serialize' ) );

			default:
				// Unknown format, return as-is.
				return $stored_value;
		}
	}
}
