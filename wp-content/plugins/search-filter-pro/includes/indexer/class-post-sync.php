<?php
/**
 * Post sync operations for the indexer.
 *
 * Handles single-post sync orchestration, field value extraction,
 * and index writing coordination.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Fields\Field;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;
use Search_Filter_Pro\Indexer\Parent_Map\Manager as Parent_Map_Manager;
use Search_Filter_Pro\Indexer\Parent_Map\Database\Query as Parent_Map_Query;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles post sync operations for the indexer.
 *
 * This class is responsible for:
 * - Single-post sync orchestration
 * - Field value extraction from posts
 * - Index writing coordination via strategies
 *
 * @since 3.0.0
 */
class Post_Sync {

	/**
	 * Resync of the index for a particular post.
	 *
	 * @since    3.0.0
	 *
	 * @param    int   $post_id           The post ID to resync.
	 * @param    array $args              Additional arguments, such as specific fields to sync.
	 * @param    bool  $clear_caches      Whether to clear the object caches after resyncing.
	 */
	public static function resync_post( $post_id, $args = array(), $clear_caches = true ) {
		if ( ! Indexer::should_resync( $post_id ) ) {
			return;
		}
		self::process_post_sync( $post_id, $args );

		// Because this function is an external API it could be called at any time,
		// so we need to default to clearing the object caches.  Unfortunately clearing
		// caches by group is not supported everywhere so we have to clear everything
		// by default.
		if ( $clear_caches ) {
			Util::clear_object_caches();
		}
	}

	/**
	 * Process the sync for a post.
	 *
	 * @since    3.0.0
	 *
	 * @param    int   $post_id    The post ID to resync.
	 * @param    array $args       Additional arguments to pass to the sync.
	 *                             Currently 'fields' and 'action' is supported.
	 */
	public static function process_post_sync( $post_id, $args = array() ) {

		wp_using_ext_object_cache( false );

		$post = get_post( $post_id );
		if ( ! $post ) {
			// Then remove any data for this post.
			return;
		}

		// Sync parent mapping if this post type supports it.
		self::sync_parent_mapping( $post );

		/**
		 * Get the fields to sync, if '$args['fields']' is not set or empty, then
		 * sync all the fields that are connected to the post type.
		 */
		$indexed_fields_by_post_type = Indexer::get_indexed_fields_by_post_type();
		$fields_to_sync              = isset( $indexed_fields_by_post_type[ $post->post_type ] )
			? $indexed_fields_by_post_type[ $post->post_type ]
			: array();

		// Only override default fields if explicitly provided AND non-empty.
		// This prevents empty arrays from bypassing the fallback logic.
		if ( isset( $args['fields'] ) && ! empty( $args['fields'] ) ) {
			$fields_to_sync = $args['fields'];
		}

		// absint will convert the false value to 0 in the event of an issue.
		$post_parent_id = absint( wp_get_post_parent_id( $post_id ) );

		$query_ids = array();
		// Loop through the fields synced to the post.
		foreach ( $fields_to_sync as $field ) {
			// Then get the new sync data for this field.
			// Pass args through to sync_field_index.
			self::sync_field_index( $field, $post_id, $post_parent_id, $args );
			// Track which connected queries have been updated.
			$query_ids[] = $field->get_query_id();
		}

		// Clear the caches for the associated queries.
		foreach ( $query_ids as $query_id ) {
			Tiered_Cache::invalidate_query_cache( $query_id );
		}
	}

	/**
	 * Clear the index for a field.
	 *
	 * @since    3.0.0
	 *
	 * @param    Field $field    The field to clear the index for.
	 * @param    int   $object_id  The post ID to clear the index for.
	 */
	public static function clear_field_index( $field, $object_id = -1 ) {
		// Clear from legacy index if migration not complete.
		if ( ! Indexer::migration_completed() ) {
			Legacy\Updater::clear_field_index( $field->get_id(), $object_id );
		}

		// Use strategy to clear the appropriate new index.
		$strategy = Index_Strategy_Factory::for_field( $field );
		if ( $strategy ) {
			$strategy->clear( $field->get_id(), $object_id );
		}
	}

	/**
	 * Sync the index for a field + post.
	 *
	 * Uses the Index Strategy pattern to determine extraction and writing logic
	 * based on field type (bitmap for choice, bucket for range, search for text).
	 *
	 * @since    3.0.0
	 * @since    3.2.0 Refactored to use Index Strategy pattern.
	 *
	 * @param    Field $field             The field to sync.
	 * @param    int   $object_id         The object ID to sync.
	 * @param    int   $object_parent_id  The object parent ID to sync.
	 * @param    array $args              Optional arguments (e.g., is_migration flag).
	 */
	public static function sync_field_index( $field, $object_id, $object_parent_id = 0, $args = array() ) {

		// Get the appropriate strategy for this field type.
		$strategy = Strategy\Index_Strategy_Factory::for_field( $field );

		if ( $strategy ) {
			// Use strategy pattern for indexing.
			// Strategy handles clearing, extraction, and writing to appropriate index.
			$strategy->index( $object_id, $field );

			// Handle legacy dual-write during migration (non-search fields only).
			// Search fields don't use legacy index.
			$is_migration_task = isset( $args['is_migration'] ) && $args['is_migration'];
			if ( ! Indexer::migration_completed() && ! $is_migration_task && $strategy->get_type() !== 'search' ) {
				// Extract values for legacy write.
				$values = $strategy->extract( $object_id, $field );
				if ( ! empty( $values ) ) {
					$item_attributes = array(
						'object_id'        => $object_id,
						'object_parent_id' => $object_parent_id,
						'field_id'         => $field->get_id(),
					);
					$object_data     = self::build_object_data_from_values( $item_attributes, $values );
					Legacy\Updater::write_field_values( $field->get_id(), $values, $object_data );
				}
			}

			// Clear caches for this field's query.
			Tiered_Cache::invalidate_query_cache( $field->get_query_id() );
		}
	}


	/**
	 * Build object data array from values.
	 *
	 * Converts field values into structured object data for indexing.
	 *
	 * @since 3.0.9
	 *
	 * @param array $item_attributes Base attributes (field_id, object_id, parent_id).
	 * @param array $values          Array of values.
	 * @return array Object data array.
	 */
	public static function build_object_data_from_values( $item_attributes, $values ) {
		$object_data = array();

		foreach ( $values as $value ) {
			$object_data[] = array(
				'field_id'         => $item_attributes['field_id'],
				'object_id'        => $item_attributes['object_id'],
				'object_parent_id' => $item_attributes['object_parent_id'],
				'value'            => $value,
			);
		}

		return $object_data;
	}

	/**
	 * Get field values for batch indexing.
	 *
	 * Uses the Index Strategy pattern to extract values from a post for a field.
	 * The strategy handles both filter fields (returning values) and search fields
	 * (returning content arrays).
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field   The field instance.
	 * @param int   $post_id The post ID.
	 * @return array Array of values/content for the field.
	 */
	public static function get_field_values_for_batch( $field, $post_id ) {
		// Get the appropriate strategy for this field type.
		$strategy = Strategy\Index_Strategy_Factory::for_field( $field );

		if ( $strategy ) {
			// Use strategy's extract method for batch collection.
			return $strategy->extract( $post_id, $field );
		}

		// No strategy found - return empty array.
		// If this happens, it indicates a missing strategy implementation.
		return array();
	}

	/**
	 * Get the index values for a post taxonomy.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $post_id    The post ID to get the data for.
	 * @param    string $taxonomy_name    The taxonomy name to get the data for.
	 * @return   array    The index values for the post taxonomy.
	 */
	public static function get_post_taxonomy_values( $post_id, $taxonomy_name ) {
		$terms = get_the_terms( $post_id, $taxonomy_name );
		if ( is_wp_error( $terms ) || $terms === false ) {
			return array();
		}

		$values = array();
		foreach ( $terms as $term ) {
			// Add each term to the item.
			$values[] = $term->slug;

			// Loop through and attach all parents that exist.
			$parent_id = $term->parent;
			while ( $parent_id !== 0 ) {
				$parent_term = get_term( $parent_id, $taxonomy_name );
				// If for some reason the parent term is not found break.
				if ( is_wp_error( $parent_term ) || ! $parent_term ) {
					break;
				}
				$parent_id = $parent_term->parent;
				if ( ! in_array( $parent_term->slug, $values, true ) ) {
					$values[] = $parent_term->slug;
				}
			}
		}
		return $values;
	}

	/**
	 * Get the index values for a posts post type.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post type.
	 */
	public static function get_post_type_values( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( $post_type === false ) {
			return array();
		}
		return array( $post_type );
	}

	/**
	 * Get the index values for a posts post status.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post status.
	 */
	public static function get_post_status_values( $post_id ) {
		$post_status = get_post_status( $post_id );
		if ( $post_status === false ) {
			return array();
		}
		return array( $post_status );
	}

	/**
	 * Get the index values for a post author.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post author.
	 */
	public static function get_post_author_values( $post_id ) {
		$post_author = get_post_field( 'post_author', $post_id );
		if ( $post_author === false ) {
			return array();
		}
		return array( $post_author );
	}

	/**
	 * Get the index values for a post date.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post date.
	 */
	public static function get_post_date_values( $post_id ) {
		$post_date = get_post_field( 'post_date', $post_id );
		if ( $post_date === false ) {
			return array();
		}
		return array( $post_date );
	}

	/**
	 * Get the index values for a post custom field.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $post_id    The post ID to get the data for.
	 * @param    string $custom_field_key    The custom field key to get the data for.
	 * @return   array    The index values for the custom field.
	 */
	public static function get_post_custom_field_values( $post_id, $custom_field_key ) {
		// Get the custom field data.
		$custom_field_data = get_post_meta( $post_id, $custom_field_key, false );

		if ( $custom_field_data === false ) {
			return array();
		}

		$values = array();
		foreach ( $custom_field_data as $custom_field_value ) {
			if ( is_scalar( $custom_field_value ) ) {
				// Add the item to the list.
				$values[] = $custom_field_value;

			} elseif ( is_array( $custom_field_value ) ) {
				// Loop through the array and add each value to the list.
				foreach ( $custom_field_value as $array_value ) {
					if ( is_scalar( $array_value ) ) {
						// Build the item and add it to the list.
						$values[] = $array_value;
					}
				}
			}
		}
		return $values;
	}

	/**
	 * Sync parent mapping for a post.
	 *
	 * Compares current post_parent against stored mapping and updates as needed.
	 * Only runs for post types that support parent mapping (via filter).
	 *
	 * @since 3.2.0
	 *
	 * @param \WP_Post $post The post object.
	 */
	private static function sync_parent_mapping( $post ) {
		// Check if this post type supports parent mapping.
		$parent_map_post_types = Parent_Map_Manager::get_parent_map_post_types();
		if ( empty( $parent_map_post_types ) || ! in_array( $post->post_type, $parent_map_post_types, true ) ) {
			return;
		}

		// Check if parent_map feature is enabled.
		if ( ! Parent_Map_Manager::should_use() ) {
			return;
		}

		$source         = 'post-' . $post->post_type;
		$current_parent = (int) $post->post_parent;

		// Get existing mapping from DB (returns null if not found).
		$existing_parent = Parent_Map_Query::get_parent_id( $post->ID, $source );

		// Determine action based on current vs stored state.
		if ( $current_parent > 0 ) {
			// Post has a parent.
			if ( $existing_parent !== $current_parent ) {
				// New mapping or parent changed - store/update.
				Parent_Map_Query::store_mapping( $post->ID, $current_parent, $source );
			}
			// If same, no action needed.
		} elseif ( $existing_parent !== null ) {
			// Post has no parent (top-level or simple product).
			// Had a mapping but now doesn't - delete it.
			Parent_Map_Query::delete_mapping( $post->ID, $source );
			// If no existing mapping, no action needed.
		}
	}
}
