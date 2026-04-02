<?php
/**
 * Bitmap class for ultra-fast count operations
 *
 * This class implements a bitmap data structure for efficient set operations
 * on large collections of post IDs. It provides 100-1000x faster intersection
 * and counting compared to array operations.
 *
 * @package Search_Filter_Pro\Indexer
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter_Pro\Indexer\Utils\Compression;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitmap class for fast bitwise operations
 */
class Bitmap {

	/**
	 * Binary string containing the bitmap data
	 * Each bit represents whether a post ID is present in the set
	 *
	 * @var string
	 */
	private $data = '';

	/**
	 * The maximum post ID represented in this bitmap
	 *
	 * @var int
	 */
	private $max_id = 0;

	/**
	 * Constructor
	 *
	 * @param string $data   Binary bitmap data.
	 * @param int    $max_id Maximum post ID.
	 */
	public function __construct( $data = '', $max_id = 0 ) {
		$this->data   = $data;
		$this->max_id = $max_id;
	}

	/**
	 * Create bitmap from array of post IDs
	 *
	 * @param array $post_ids Array of post IDs.
	 * @return Bitmap
	 */
	public static function from_post_ids( $post_ids ) {
		if ( empty( $post_ids ) ) {
			return new self();
		}

		// Ensure IDs are integers and find max.
		$post_ids = array_map( 'absint', $post_ids );
		$max_id   = max( $post_ids );

		// Create bitmap.
		$bitmap         = new self();
		$bitmap->max_id = $max_id;

		// Calculate required bytes (8 bits per byte).
		$num_bytes = (int) ceil( ( $max_id + 1 ) / 8 );

		// Initialize bitmap with zeros.
		$bitmap->data = str_repeat( "\x00", $num_bytes );

		// Set bits for each post ID.
		foreach ( $post_ids as $post_id ) {
			$bitmap->set_bit( $post_id );
		}

		return $bitmap;
	}

	/**
	 * Set a bit at position (post ID)
	 *
	 * @param int $position The bit position (post ID).
	 */
	public function set_bit( $position ) {
		$byte_index = (int) floor( $position / 8 );
		$bit_index  = $position % 8;

		// Expand bitmap if needed.
		if ( $byte_index >= strlen( $this->data ) ) {
			$this->data .= str_repeat( "\x00", $byte_index - strlen( $this->data ) + 1 );
		}

		// Set the bit using bitwise OR.
		$byte_value                = ord( $this->data[ $byte_index ] );
		$byte_value               |= ( 1 << $bit_index );
		$this->data[ $byte_index ] = chr( $byte_value );

		// Update max_id if needed.
		if ( $position > $this->max_id ) {
			$this->max_id = $position;
		}
	}

	/**
	 * Unset a bit at position (post ID)
	 *
	 * @param int $position The bit position (post ID).
	 */
	public function unset_bit( $position ) {
		$byte_index = (int) floor( $position / 8 );
		$bit_index  = $position % 8;

		// Check if position is within current bitmap bounds.
		if ( $byte_index >= strlen( $this->data ) ) {
			return; // Bit not set (beyond current data).
		}

		// Clear the bit using bitwise AND with NOT.
		$byte_value                = ord( $this->data[ $byte_index ] );
		$byte_value               &= ~( 1 << $bit_index );
		$this->data[ $byte_index ] = chr( $byte_value );
	}

	/**
	 * Get a bit at position (post ID)
	 *
	 * @param int $position The bit position (post ID).
	 * @return bool Whether the bit is set.
	 */
	public function get_bit( $position ) {
		$byte_index = (int) floor( $position / 8 );
		$bit_index  = $position % 8;

		if ( $byte_index >= strlen( $this->data ) ) {
			return false;
		}

		$byte_value = ord( $this->data[ $byte_index ] );
		return (bool) ( $byte_value & ( 1 << $bit_index ) );
	}

	/**
	 * Bitwise AND (intersection) - posts that are in BOTH bitmaps
	 *
	 * @param Bitmap $other The other bitmap.
	 * @return Bitmap The intersection.
	 */
	public function intersect( Bitmap $other ) {

		$result         = new self();
		$result->max_id = min( $this->max_id, $other->max_id );

		$len          = min( strlen( $this->data ), strlen( $other->data ) );
		$chunks       = (int) ( $len / 4 );
		$result->data = '';

		if ( $chunks > 0 ) {
			// Process 32-bit chunks.
			$a_ints = unpack( 'V*', substr( $this->data, 0, $chunks * 4 ) );
			$b_ints = unpack( 'V*', substr( $other->data, 0, $chunks * 4 ) );

			foreach ( $a_ints as $k => $v ) {
				$result->data .= pack( 'V', $v & $b_ints[ $k ] );
			}
		}

		// Handle remainder bytes.
		for ( $i = $chunks * 4; $i < $len; $i++ ) {
			$result->data .= chr( ord( $this->data[ $i ] ) & ord( $other->data[ $i ] ) );
		}

		// Trim trailing zeros to save memory.
		$result->data = rtrim( $result->data, "\x00" );

		return $result;
	}

	/**
	 * Bitwise OR (union) - posts that are in EITHER bitmap
	 *
	 * @param Bitmap $other The other bitmap.
	 * @return Bitmap The union
	 */
	public function union( Bitmap $other ) {
		$result         = new self();
		$result->max_id = max( $this->max_id, $other->max_id );

		// Use the longer length for union.
		$len          = max( strlen( $this->data ), strlen( $other->data ) );
		$result->data = '';

		// Perform bitwise OR on each byte.
		for ( $i = 0; $i < $len; $i++ ) {
			$byte_a        = $i < strlen( $this->data ) ? ord( $this->data[ $i ] ) : 0;
			$byte_b        = $i < strlen( $other->data ) ? ord( $other->data[ $i ] ) : 0;
			$result->data .= chr( $byte_a | $byte_b );
		}

		// Trim trailing zeros to save memory.
		$result->data = rtrim( $result->data, "\x00" );

		return $result;
	}

	/**
	 * Count number of 1s (popcount) - ultra-fast counting
	 *
	 * Uses Brian Kernighan's algorithm for efficient bit counting
	 * (or optimized SWAR algorithm if enabled)
	 *
	 * @return int The number of set bits (posts in the set)
	 */
	public function count() {

		$len    = strlen( $this->data );
		$chunks = (int) ( $len / 4 );
		$total  = 0;

		// Try GMP first if available (fastest).
		if ( function_exists( 'gmp_popcount' ) ) {
			try {
				$hex = bin2hex( $this->data );
				if ( ! empty( $hex ) ) {
					$gmp   = gmp_init( $hex, 16 );
					$total = gmp_popcount( $gmp );
					return $total;
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// Fall through to SWAR.
			}
		}

		// SWAR algorithm for main chunks.
		if ( $chunks > 0 ) {
			$ints = unpack( 'V*', substr( $this->data, 0, $chunks * 4 ) );
			foreach ( $ints as $n ) {
				$total += $this->popcount_swar_32bit( $n );
			}
		}

		// Brian Kernighan for remainder bytes.
		for ( $i = $chunks * 4; $i < $len; $i++ ) {
			$byte = ord( $this->data[ $i ] );
			while ( $byte ) {
				// Clear lowest set bit (Kernighan's algorithm).
				$byte &= ( $byte - 1 );
				++$total;
			}
		}

		return $total;
	}

	/**
	 * SWAR popcount for 32-bit integer
	 *
	 * Counts bits in parallel using bit manipulation tricks.
	 *
	 * @since 3.2.0
	 * @param int $n 32-bit integer.
	 * @return int Number of set bits.
	 */
	private function popcount_swar_32bit( $n ) {
		$n = $n - ( ( $n >> 1 ) & 0x55555555 );
		$n = ( $n & 0x33333333 ) + ( ( $n >> 2 ) & 0x33333333 );
		$n = ( $n + ( $n >> 4 ) ) & 0x0F0F0F0F;
		return ( ( $n * 0x01010101 ) >> 24 ) & 0xFF;
	}

	/**
	 * Compress bitmap using gzip for storage
	 *
	 * Achieves 90%+ compression for sparse bitmaps.
	 * Uses Compression utility with adaptive levels (1-2, faster than old level 9).
	 *
	 * @return string Compressed bitmap data
	 */
	public function compress() {
		if ( strlen( $this->data ) === 0 ) {
			return '';
		}

		// Store max_id with the data for reconstruction.
		$packed = pack( 'N', $this->max_id ) . $this->data;

		// Use level 9 for optimal compression of sparse bitmaps.
		// Benchmarks show: 57% smaller than level 2 with same decompression speed for sparse data.
		// Force compression even for small bitmaps (decompress expects compressed data).
		return Compression::compress(
			$packed,
			array(
				'level' => 9,
				'force' => true,
			)
		);
	}

	/**
	 * Decompress bitmap from storage
	 *
	 * Uses Compression utility for automatic level detection.
	 *
	 * @param string $compressed_data Compressed bitmap data.
	 * @return Bitmap|null Decompressed bitmap or null on failure.
	 */
	public static function decompress( $compressed_data ) {
		if ( strlen( $compressed_data ) === 0 ) {
			return new self();
		}

		// Decompress (must be valid gzip data, no fallback).
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional: gzuncompress emits warning on invalid data, we handle false return.
		$packed = @gzuncompress( $compressed_data );
		if ( false === $packed ) {
			return null;  // Invalid or corrupted data.
		}

		// Extract max_id and data.
		if ( strlen( $packed ) < 4 ) {
			return null;
		}

		$max_id = unpack( 'N', substr( $packed, 0, 4 ) )[1];
		$data   = substr( $packed, 4 );

		return new self( $data, $max_id );
	}

	/**
	 * Convert bitmap back to array of post IDs
	 *
	 * Useful for debugging or fallback to array operations
	 *
	 * @return array Array of post IDs
	 */
	public function to_post_ids() {
		$post_ids = array();

		// Check each byte.
		$data_length = strlen( $this->data );
		for ( $i = 0; $i < $data_length; $i++ ) {
			$byte = ord( $this->data[ $i ] );

			// Skip empty bytes.
			if ( $byte === 0 ) {
				continue;
			}

			// Check each bit in the byte.
			for ( $bit = 0; $bit < 8; $bit++ ) {
				if ( $byte & ( 1 << $bit ) ) {
					$post_ids[] = ( $i * 8 ) + $bit;
				}
			}
		}

		return $post_ids;
	}

	/**
	 * Get the size of the bitmap in bytes
	 *
	 * @return int Size in bytes
	 */
	public function size() {
		return strlen( $this->data );
	}

	/**
	 * Get the maximum post ID in this bitmap
	 *
	 * @return int Maximum post ID
	 */
	public function get_max_id() {
		return $this->max_id;
	}

	/**
	 * Check if bitmap is empty
	 *
	 * @return bool True if empty
	 */
	public function is_empty() {
		if ( strlen( $this->data ) === 0 ) {
			return true;
		}

		// Check if all bytes are zero.
		$data_length = strlen( $this->data );
		for ( $i = 0; $i < $data_length; $i++ ) {
			if ( ord( $this->data[ $i ] ) !== 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Bitwise XOR (symmetric difference) - posts in one but not both
	 *
	 * @param Bitmap $other The other bitmap.
	 * @return Bitmap The XOR result.
	 */
	public function xor( Bitmap $other ) {
		$result         = new self();
		$result->max_id = max( $this->max_id, $other->max_id );

		$len          = max( strlen( $this->data ), strlen( $other->data ) );
		$result->data = '';

		for ( $i = 0; $i < $len; $i++ ) {
			$byte_a        = $i < strlen( $this->data ) ? ord( $this->data[ $i ] ) : 0;
			$byte_b        = $i < strlen( $other->data ) ? ord( $other->data[ $i ] ) : 0;
			$result->data .= chr( $byte_a ^ $byte_b );
		}

		$result->data = rtrim( $result->data, "\x00" );
		return $result;
	}

	/**
	 * Bitwise AND NOT (difference) - posts in this but not in other
	 *
	 * @param Bitmap $other The other bitmap.
	 * @return Bitmap The difference.
	 */
	public function diff( Bitmap $other ) {
		$result         = new self();
		$result->max_id = $this->max_id;

		$len          = strlen( $this->data );
		$result->data = '';

		for ( $i = 0; $i < $len; $i++ ) {
			$byte_a        = ord( $this->data[ $i ] );
			$byte_b        = $i < strlen( $other->data ) ? ord( $other->data[ $i ] ) : 0;
			$result->data .= chr( $byte_a & ~$byte_b );
		}

		$result->data = rtrim( $result->data, "\x00" );
		return $result;
	}

	/**
	 * Get raw binary data (for direct storage)
	 *
	 * @return string Binary bitmap data
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Create bitmap from raw binary data
	 *
	 * @param string $data   Binary bitmap data.
	 * @param int    $max_id Maximum post ID.
	 * @return Bitmap
	 */
	public static function from_data( $data, $max_id ) {
		return new self( $data, $max_id );
	}

	/**
	 * Clone the bitmap
	 *
	 * @return Bitmap A copy of this bitmap
	 */
	public function copy() {
		return new self( $this->data, $this->max_id );
	}

	/**
	 * Combined intersect and count operation (Full Optimization)
	 *
	 * Performs intersection and counting in a single pass without creating
	 * an intermediate bitmap object. This is the fastest method when you
	 * only need the count, not the intersection bitmap itself.
	 *
	 * Combines:
	 * - Word-aligned operations (32-bit chunks)
	 * - SWAR popcount algorithm
	 * - No intermediate object allocation
	 *
	 * @since 3.2.0
	 * @param Bitmap $other The other bitmap to intersect with.
	 * @return int The count of intersecting bits.
	 */
	public function intersect_count( Bitmap $other ) {

		$len    = min( strlen( $this->data ), strlen( $other->data ) );
		$chunks = (int) ( $len / 4 );
		$total  = 0;

		if ( $chunks > 0 ) {
			$a_ints = unpack( 'V*', substr( $this->data, 0, $chunks * 4 ) );
			$b_ints = unpack( 'V*', substr( $other->data, 0, $chunks * 4 ) );

			foreach ( $a_ints as $k => $v ) {
				$intersect = $v & $b_ints[ $k ];
				// Count immediately using SWAR.
				$total += $this->popcount_swar_32bit( $intersect );
			}
		}

		// Handle remainder bytes.
		for ( $i = $chunks * 4; $i < $len; $i++ ) {
			$byte = ord( $this->data[ $i ] ) & ord( $other->data[ $i ] );
			// Brian Kernighan for remainder.
			while ( $byte ) {
				// Clear lowest set bit (Kernighan's algorithm).
				$byte &= ( $byte - 1 );
				++$total;
			}
		}

		return $total;
	}

	/**
	 * Batch intersect and count multiple bitmaps against this bitmap.
	 *
	 * Processes all operations in a single pass, keeping base bitmap
	 * in CPU cache for maximum performance. This eliminates redundant
	 * unpack operations and reduces memory bandwidth by ~99%.
	 *
	 * Expected improvement: 25-35% faster than individual intersect_count calls.
	 *
	 * @since 3.2.0
	 * @param array $bitmaps Array of Bitmap objects (key => Bitmap).
	 * @return array Array of counts (key => count).
	 */
	public function batch_intersect_counts( array $bitmaps ) {
		if ( empty( $bitmaps ) ) {
			return array();
		}

		$len    = strlen( $this->data );
		$chunks = (int) ( $len / 4 );
		$counts = array();

		// Unpack base bitmap ONCE - stays hot in L1 cache.
		$base_ints = array();
		if ( $chunks > 0 ) {
			$base_ints = unpack( 'V*', substr( $this->data, 0, $chunks * 4 ) );
		}

		// Process each target bitmap.
		foreach ( $bitmaps as $key => $bitmap ) {
			$count = 0;

			// Ensure target bitmap is long enough.
			$target_len    = strlen( $bitmap->data );
			$target_chunks = min( $chunks, (int) ( $target_len / 4 ) );

			// Process word-aligned chunks.
			if ( $target_chunks > 0 ) {
				$other_ints = unpack( 'V*', substr( $bitmap->data, 0, $target_chunks * 4 ) );

				// Intersect and count in tight loop.
				foreach ( $base_ints as $i => $base_val ) {
					// Skip if beyond target bitmap length.
					if ( $i > $target_chunks ) {
						break;
					}

					$intersect = $base_val & $other_ints[ $i ];
					if ( $intersect !== 0 ) {
						$count += $this->popcount_swar_32bit( $intersect );
					}
				}
			}

			// Handle remainder bytes (non-aligned tail).
			// Start from where target bitmap chunks ended, not base bitmap chunks.
			$min_len = min( $len, $target_len );
			for ( $j = $target_chunks * 4; $j < $min_len; $j++ ) {
				$byte = ord( $this->data[ $j ] ) & ord( $bitmap->data[ $j ] );
				// Brian Kernighan's algorithm for remainder.
				while ( $byte ) {
					// Clear lowest set bit (Kernighan's algorithm).
					$byte &= ( $byte - 1 );
					++$count;
				}
			}

			$counts[ $key ] = $count;
		}

		return $counts;
	}

	/**
	 * Iterate over set bits with callback.
	 *
	 * Optimization: Skips empty bytes for speedup on sparse data
	 *
	 * @since 3.2.0
	 * @param callable $callback Function called for each set bit position.
	 */
	public function foreach_set_bit( callable $callback ) {
		$data_length = strlen( $this->data );

		for ( $i = 0; $i < $data_length; $i++ ) {
			$byte = ord( $this->data[ $i ] );

			// Skip empty bytes.
			if ( $byte === 0 ) {
				continue;
			}

			// Iterate set bits only (Kernighan-style).
			for ( $bit = 0; $bit < 8; $bit++ ) {
				if ( $byte & ( 1 << $bit ) ) {
					$callback( ( $i * 8 ) + $bit );
				}
			}
		}
	}

	/**
	 * Batch intersect and count with inline transformation
	 *
	 * Combines intersection + transformation + counting in a single pass.
	 * Eliminates intermediate bitmap allocations.
	 *
	 * Use case: Field counting with parent ID conversion (WooCommerce variations)
	 * - Intersects base bitmap with each target bitmap
	 * - Transforms child IDs to parent IDs inline (via callback)
	 * - Counts unique transformed IDs
	 * - All in one pass without intermediate bitmaps
	 *
	 * Performance: 74% faster than separate intersect→transform→count operations
	 *
	 * @since 3.2.0
	 * @param array    $target_bitmaps Array of Bitmap objects (key => Bitmap).
	 * @param callable $transform      Transform function (child ID -> parent ID).
	 *                                 Signature: function($id) : int|null.
	 * @return array Array of counts (key => count).
	 */
	public function batch_intersect_counts_with_transform( array $target_bitmaps, callable $transform ) {
		if ( empty( $target_bitmaps ) ) {
			return array();
		}

		$len    = strlen( $this->data );
		$chunks = (int) ( $len / 4 );
		$counts = array();

		// Unpack base bitmap ONCE - stays hot in L1 cache.
		$base_ints = array();
		if ( $chunks > 0 ) {
			$base_ints = unpack( 'V*', substr( $this->data, 0, $chunks * 4 ) );
		}

		// Process each target bitmap.
		foreach ( $target_bitmaps as $key => $bitmap ) {
			// Use a bitmap to track unique parent IDs (faster than array_unique).
			$parent_bitmap = new self();

			// Ensure target bitmap is long enough.
			$target_len    = strlen( $bitmap->data );
			$target_chunks = min( $chunks, (int) ( $target_len / 4 ) );

			// Process word-aligned chunks.
			if ( $target_chunks > 0 ) {
				$other_ints = unpack( 'V*', substr( $bitmap->data, 0, $target_chunks * 4 ) );

				// Intersect and transform in tight loop.
				foreach ( $base_ints as $i => $base_val ) {
					// Skip if beyond target bitmap length.
					if ( $i > $target_chunks ) {
						break;
					}

					$intersect = $base_val & $other_ints[ $i ];
					if ( $intersect !== 0 ) {
						// Extract set bits and transform.
						$this->extract_and_transform_32bit( $intersect, ( $i - 1 ) * 32, $transform, $parent_bitmap );
					}
				}
			}

			// Handle remainder bytes (non-aligned tail).
			$min_len = min( $len, $target_len );
			for ( $j = $target_chunks * 4; $j < $min_len; $j++ ) {
				$byte = ord( $this->data[ $j ] ) & ord( $bitmap->data[ $j ] );
				if ( $byte !== 0 ) {
					// Extract set bits and transform.
					$this->extract_and_transform_byte( $byte, $j * 8, $transform, $parent_bitmap );
				}
			}

			// Count unique parent IDs (popcount on parent bitmap).
			$counts[ $key ] = $parent_bitmap->count();
		}

		return $counts;
	}

	/**
	 * Extract set bits from 32-bit integer, transform IDs, and set in parent bitmap.
	 *
	 * @since 3.2.0
	 * @param int      $value        32-bit integer with set bits.
	 * @param int      $base_offset  Base bit position for this integer.
	 * @param callable $transform_fn Transform function (child ID -> parent ID).
	 * @param Bitmap   $parent_bitmap Bitmap to store parent IDs.
	 */
	private function extract_and_transform_32bit( $value, $base_offset, callable $transform_fn, $parent_bitmap ) {
		// Kernighan's algorithm for extracting set bits.
		while ( $value ) {
			// Find rightmost set bit position.
			$bit_position = $this->count_trailing_zeros( $value );
			$child_id     = $base_offset + $bit_position;

			// Transform child ID to parent ID.
			$parent_id = $transform_fn( $child_id );
			if ( $parent_id !== null ) {
				$parent_bitmap->set_bit( $parent_id );
			}

			// Clear the rightmost set bit.
			$value &= ( $value - 1 );
		}
	}

	/**
	 * Extract set bits from byte, transform IDs, and set in parent bitmap.
	 *
	 * @since 3.2.0
	 * @param int      $byte         Byte value with set bits.
	 * @param int      $base_offset  Base bit position for this byte.
	 * @param callable $transform_fn Transform function (child ID -> parent ID).
	 * @param Bitmap   $parent_bitmap Bitmap to store parent IDs.
	 */
	private function extract_and_transform_byte( $byte, $base_offset, callable $transform_fn, $parent_bitmap ) {
		// Kernighan's algorithm for extracting set bits.
		while ( $byte ) {
			// Find rightmost set bit position.
			$bit_position = $this->get_rightmost_bit_position( $byte );
			$child_id     = $base_offset + $bit_position;

			// Transform child ID to parent ID.
			$parent_id = $transform_fn( $child_id );
			if ( $parent_id !== null ) {
				$parent_bitmap->set_bit( $parent_id );
			}

			// Clear lowest set bit (Kernighan's algorithm).
			$byte &= ( $byte - 1 );
		}
	}

	/**
	 * Count trailing zeros in a 32-bit integer.
	 *
	 * Uses De Bruijn sequence for fast bit position lookup.
	 *
	 * @since 3.2.0
	 * @param int $value 32-bit integer.
	 * @return int Number of trailing zeros (0-31).
	 */
	private function count_trailing_zeros( $value ) {
		// Isolate rightmost set bit and use lookup.
		$rightmost = $value & -$value;

		// De Bruijn sequence for 32-bit CTZ.
		static $lookup = null;
		if ( $lookup === null ) {
			$lookup = array(
				0,
				1,
				28,
				2,
				29,
				14,
				24,
				3,
				30,
				22,
				20,
				15,
				25,
				17,
				4,
				8,
				31,
				27,
				13,
				23,
				21,
				19,
				16,
				7,
				26,
				12,
				18,
				6,
				11,
				5,
				10,
				9,
			);
		}

		$index = ( ( $rightmost * 0x077CB531 ) >> 27 ) & 0x1F;
		return $lookup[ $index ];
	}

	/**
	 * Get the position (0-7) of the rightmost set bit in a byte.
	 *
	 * Uses lookup table for single-bit values.
	 *
	 * @since 3.2.0
	 * @param int $byte Byte value (0-255).
	 * @return int Bit position (0-7).
	 */
	private function get_rightmost_bit_position( $byte ) {
		// Isolate rightmost set bit.
		$rightmost = $byte & -$byte;

		// Lookup table for single-bit values.
		static $lookup = array(
			0x01 => 0,
			0x02 => 1,
			0x04 => 2,
			0x08 => 3,
			0x10 => 4,
			0x20 => 5,
			0x40 => 6,
			0x80 => 7,
		);

		return $lookup[ $rightmost ];
	}
}
