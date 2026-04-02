<?php
/**
 * Compression Utilities
 *
 * Generic compression with adaptive levels based on data size.
 * Provides optional preprocessing (serialize, pack) or works with raw data.
 *
 * Based on Batch 3 benchmarks showing level 1 is optimal
 * (33% faster than level 9 with only 9% size difference).
 *
 * @package Search_Filter_Pro\Indexer\Utils
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Utils;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compression utility class
 *
 * USAGE:
 *
 * Default (auto level, no preprocessing):
 *   $compressed = Compression::compress($data);
 *   $data = Compression::decompress($compressed);
 *
 * With serialization:
 *   $compressed = Compression::compress($data, ['preprocess' => 'serialize']);
 *   $data = Compression::decompress($compressed, ['preprocess' => 'serialize']);
 *
 * With packing:
 *   $compressed = Compression::compress($data, ['preprocess' => 'pack']);
 *
 * With specific level (override auto):
 *   $compressed = Compression::compress($data, ['level' => 9, 'force' => true]);
 *
 * Full control:
 *   $compressed = Compression::compress($data, [
 *       'preprocess' => 'serialize',
 *       'level' => 9,
 *       'force' => true
 *   ]);
 *
 * @since 3.2.0
 */
class Compression {

	/**
	 * Size thresholds for compression levels (from Batch 3 benchmarks)
	 */
	const THRESHOLD_NO_COMPRESSION = 1024;      // <1KB: Don't compress.
	const THRESHOLD_LIGHT          = 51200;     // <50KB: Level 1.

	/**
	 * Compress data with optional preprocessing and level control
	 *
	 * Adaptive compression levels (from Batch 3 benchmarks):
	 * - <1KB: No compression (unless forced)
	 * - 1-50KB: Level 1 (optimal speed/size)
	 * - >50KB: Level 2
	 *
	 * @param mixed $data Data to compress.
	 * @param array $args {
	 *     Optional arguments.
	 *
	 *     @type string $preprocess  Preprocessing: 'serialize', 'pack', 'none' (default: 'none').
	 *     @type int    $level       Compression level 1-9 or 'auto' (default: 'auto').
	 *     @type bool   $force       Force compression even if small (default: false).
	 * }
	 * @return string Compressed data.
	 */
	public static function compress( $data, $args = array() ) {
		// Parse arguments with defaults.
		$preprocess = $args['preprocess'] ?? 'none';
		$level      = $args['level'] ?? 'auto';
		$force      = $args['force'] ?? false;

		// Preprocess data if needed.
		$prepared = self::preprocess( $data, $preprocess );

		// Check if compression worth it.
		$size = strlen( $prepared );

		if ( ! $force && $size < self::THRESHOLD_NO_COMPRESSION ) {
			return $prepared;  // Too small to compress.
		}

		// Choose compression level (auto or explicit).
		if ( $level === 'auto' ) {
			$level = self::get_compression_level( $size );
		}

		return gzcompress( $prepared, $level );
	}

	/**
	 * Decompress data with optional postprocessing
	 *
	 * @param string $compressed Compressed data.
	 * @param array  $args {
	 *     Optional arguments.
	 *
	 *     @type string $preprocess  Preprocessing used: 'serialize', 'pack', 'none' (default: 'none').
	 * }
	 * @return mixed Original data.
	 */
	public static function decompress( $compressed, $args = array() ) {
		// Parse arguments with defaults.
		$preprocess = $args['preprocess'] ?? 'none';

		// Try decompression.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional: gzuncompress emits warning on invalid data, we handle false return.
		$decompressed = @gzuncompress( $compressed );

		if ( $decompressed === false ) {
			// Not compressed, or decompression failed.
			$decompressed = $compressed;
		}

		// Postprocess data if needed.
		return self::postprocess( $decompressed, $preprocess );
	}

	/**
	 * Preprocess data before compression
	 *
	 * @param mixed  $data        Data to preprocess.
	 * @param string $method      Method: 'serialize', 'pack', 'none'.
	 * @return string Preprocessed data.
	 */
	private static function preprocess( $data, $method ) {
		switch ( $method ) {
			case 'serialize':
				return self::serialize( $data );

			case 'pack':
				// Assume data is numeric array, pack as unsigned longs.
				if ( ! is_array( $data ) ) {
					return $data;
				}
				return pack( 'L*', ...$data );

			case 'none':
			default:
				// Data is already prepared (string, binary, etc.).
				return $data;
		}
	}

	/**
	 * Postprocess data after decompression
	 *
	 * @param string $data        Decompressed data.
	 * @param string $method      Method: 'serialize', 'pack', 'none'.
	 * @return mixed Postprocessed data.
	 */
	private static function postprocess( $data, $method ) {
		switch ( $method ) {
			case 'serialize':
				return self::unserialize( $data );

			case 'pack':
				// Unpack to numeric array.
				$unpacked = unpack( 'L*', $data );
				// unpack returns 1-indexed array, convert to 0-indexed.
				return array_values( $unpacked );

			case 'none':
			default:
				// Return as-is.
				return $data;
		}
	}

	/**
	 * Serialize data with igbinary fallback
	 *
	 * @param mixed $data Data to serialize.
	 * @return string Serialized data.
	 */
	private static function serialize( $data ) {
		if ( function_exists( 'igbinary_serialize' ) ) {
			return igbinary_serialize( $data );
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Internal data only, no user input.
		return serialize( $data );
	}

	/**
	 * Unserialize data with igbinary fallback
	 *
	 * @param string $serialized Serialized data.
	 * @return mixed Unserialized data.
	 */
	private static function unserialize( $serialized ) {
		// Try igbinary first.
		if ( function_exists( 'igbinary_unserialize' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional: igbinary_unserialize emits warning on invalid data.
			$result = @igbinary_unserialize( $serialized );
			if ( $result !== false ) {
				return $result;
			}
		}

		// Fallback to standard unserialize.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Internal data only, no user input.
		return unserialize( $serialized );
	}

	/**
	 * Get compression level based on data size
	 *
	 * Based on Batch 3 benchmarks:
	 * - Level 1 is 33% faster than level 9 with only 9% size difference
	 * - Level 2 provides slight better ratio for large data
	 *
	 * @param int $size Data size in bytes.
	 * @return int Compression level (1-2).
	 */
	private static function get_compression_level( $size ) {
		if ( $size < self::THRESHOLD_LIGHT ) {
			return 1;  // Fast, good enough.
		} else {
			return 2;  // Slightly better ratio, still fast.
		}
	}

	/**
	 * Check if igbinary is available
	 *
	 * @return bool
	 */
	public static function has_igbinary() {
		return function_exists( 'igbinary_serialize' );
	}
}
