<?php
/**
 * Bitmap Updater - Incremental bitmap updates.
 *
 * Handles adding/removing individual posts from bitmap index without
 * requiring full rebuild.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro\Indexer\Bitmap
 */

namespace Search_Filter_Pro\Indexer\Bitmap;

use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitmap Updater class.
 *
 * Provides incremental update operations for bitmap indexes,
 * enabling efficient single-post updates without full rebuilds.
 *
 * @since 3.2.0
 */
class Updater {

	/**
	 * Add post to bitmaps for given field-value pairs.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id  Field ID.
	 * @param int   $post_id   Post ID.
	 * @param array $values    Array of values to add.
	 * @return bool True on success.
	 */
	public static function add_post_to_bitmaps( $field_id, $post_id, $values ) {
		foreach ( $values as $value ) {
			// Get existing bitmap (or create new).
			$bitmap = Index_Query_Direct::get_bitmap( $field_id, $value );

			if ( ! $bitmap ) {
				$bitmap = new Bitmap();
			}

			// Add to bitmap using direct bit operation.
			if ( ! $bitmap->get_bit( $post_id ) ) {
				$bitmap->set_bit( $post_id );
			}

			// Store updated bitmap.
			Index_Query_Direct::store_bitmap( $field_id, $value, $bitmap );
		}

		return true;
	}

	/**
	 * Remove post from all bitmaps for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @param int $post_id  Post ID.
	 * @return bool True on success.
	 */
	public static function remove_post_from_field( $field_id, $post_id ) {
		// Check all bitmaps for this field to find which values contain this post.
		// This is more expensive than a targeted removal, but necessary without
		// a secondary index mapping post_id -> values.
		return self::remove_post_from_all_bitmaps( $field_id, $post_id );
	}

	/**
	 * Remove post from a specific field-value bitmap.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $field_id Field ID.
	 * @param string $value    Field value.
	 * @param int    $post_id  Post ID.
	 * @return bool True on success.
	 */
	private static function remove_post_from_bitmap( $field_id, $value, $post_id ) {
		// Get existing bitmap.
		$bitmap = Index_Query_Direct::get_bitmap( $field_id, $value );

		if ( ! $bitmap ) {
			return false; // Bitmap doesn't exist.
		}

		// Quick check: is post actually in this bitmap? Skip update if not.
		if ( ! $bitmap->get_bit( $post_id ) ) {
			return true; // Post not in bitmap - no update needed.
		}

		// Remove post using direct bit operation.
		$bitmap->unset_bit( $post_id );

		// Check if bitmap is now empty after removal.
		if ( $bitmap->is_empty() ) {
			// No more posts for this value - delete the bitmap.
			return Index_Query_Direct::delete_bitmap( $field_id, $value );
		} else {
			// Store updated bitmap.
			return Index_Query_Direct::store_bitmap( $field_id, $value, $bitmap );
		}
	}

	/**
	 * Remove post from all bitmaps (when we don't know which values).
	 *
	 * This is expensive - queries all bitmaps for the field.
	 * Only used as fallback when legacy index unavailable.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @param int $post_id  Post ID.
	 * @return bool True on success.
	 */
	private static function remove_post_from_all_bitmaps( $field_id, $post_id ) {
		// Get all values for this field.
		$values = Index_Query_Direct::get_unique_field_values( $field_id );

		foreach ( $values as $value ) {
			self::remove_post_from_bitmap( $field_id, $value, $post_id );
		}

		return true;
	}

	/**
	 * Clear all bitmaps for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return bool True on success.
	 */
	public static function clear_field_index( $field_id ) {
		return Index_Query_Direct::delete_field_bitmaps( $field_id );
	}

	/**
	 * Reset all bitmap data (all fields).
	 *
	 * Reset the bitmap index.
	 *
	 * Truncates the bitmap index table. Used during full index rebuild.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Success status
	 */
	public static function reset() {

		// Truncate the table & cache.
		Index_Query_Direct::reset();

		return true;
	}
}
