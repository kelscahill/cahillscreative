<?php
/**
 * Field queries for indexer counts.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Core\Data_Store;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Cache;
use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Parent_Map\Converter as Parent_Map_Converter;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;
use Search_Filter_Pro\Cache\Tiered_Cache;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the field counts for the indexer.
 *
 * @since 3.0.0
 */
class Field_Queries {

	/**
	 * The local copied store of the result cache.
	 *
	 * @var array
	 */
	private static $fields = array();

	/**
	 * Tiered_Cache instances keyed by query ID.
	 *
	 * @since 3.2.0
	 *
	 * @var array<int, Tiered_Cache>
	 */
	private static $cache_instances = array();

	/**
	 * Get the Tiered_Cache instance for a query.
	 *
	 * @since 3.2.0
	 *
	 * @param int $query_id The query ID.
	 * @return Tiered_Cache
	 */
	private static function get_cache_instance( $query_id ) {
		if ( ! isset( self::$cache_instances[ $query_id ] ) ) {
			self::$cache_instances[ $query_id ] = new Tiered_Cache( 'query_cache_' . $query_id );
		}
		return self::$cache_instances[ $query_id ];
	}

	/**
	 * Get the TTL for cache based on whether query has search.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $has_search Whether the query has a search term.
	 * @return int TTL in seconds.
	 */
	private static function get_cache_ttl( $has_search ) {
		return $has_search ? 2 * HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Init the field queries.
	 *
	 * Registers hooks that will delegate to Legacy\Field_Queries if migration
	 * has not completed (checked at runtime when hooks fire).
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Calculate the counts for field options.
		add_action( 'search-filter/fields/choice/create_options/start', array( __CLASS__, 'start_create_options' ), 10, 1 );
		// Filter the field options and add the counts.
		add_filter( 'search-filter/fields/choice/option', array( __CLASS__, 'filter_field_option' ), 0, 2 );

		// Modify the get_terms args to disable hide_empty.
		add_filter( 'search-filter/fields/choice/create_options/get_terms_args', array( __CLASS__, 'filter_get_terms_args' ), 10, 2 );
	}

	/**
	 * Reset static field data (for testing).
	 *
	 * Clears the static $fields array to prevent data persistence between tests.
	 *
	 * @since 3.0.0
	 */
	public static function reset() {
		self::$fields = array();
	}

	/**
	 * Fetch all data for the field options when the field options
	 * are starting to be created.
	 *
	 * @since 3.0.0
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to start creating options for.
	 */
	public static function start_create_options( $field ) {

		$field_id = $field->get_id();

		// If field ID is 0 we're in admin preview mode, skip.
		if ( $field_id === 0 ) {
			return;
		}

		// Delegate to Legacy Field_Queries if migration not completed.
		if ( ! \Search_Filter_Pro\Indexer::migration_completed() ) {
			\Search_Filter_Pro\Indexer\Legacy\Field_Queries::start_create_options( $field );
			return;
		}

		$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
		$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

		if ( isset( self::$fields[ $field_id ] ) ) {
			return;
		}

		$hide_empty = $field->get_attribute( 'hideEmpty' ) === 'yes';
		$show_count = $field->get_attribute( 'showCount' ) === 'yes';

		// Don't proceed if we're not showing the count or hiding empty options.
		if ( ! $hide_empty && ! $show_count ) {
			return;
		}

		$query_id = absint( $field->get_attribute( 'queryId' ) );

		// Try to get the query in advance.
		$source_query = Data_Store::get( 'query', $query_id );
		// Now we know we're using an indexer query, init the field.
		self::$fields[ $field_id ] = array(
			'field'             => $field,
			'matchMethod'       => $multiple_match_method,
			'showCount'         => $field->get_attribute( 'showCount' ) === 'yes',
			'showCountBrackets' => $field->get_attribute( 'showCountBrackets' ) === 'yes',
			'hideEmpty'         => $field->get_attribute( 'hideEmpty' ) === 'yes',
			'queryId'           => $query_id,
			'ids'               => array(), // Contains the resolved IDs for the field with the current query.
			'options'           => array(),
			'useIndexer'        => 'no',
			'counts'            => array(),
		);

		if ( $source_query ) {
			self::$fields[ $field_id ]['useIndexer'] = $source_query->get_attribute( 'useIndexer' );
		}

		// Build the query to get the current IDs if it's not already built.
		$indexer_query = Query_Store::get_query( $query_id );

		if ( $indexer_query === null && $query_id !== 0 ) {

			$query = Query::get_instance( $query_id );

			if ( is_wp_error( $query ) ) {
				return;
			}
			if ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				return;
			}

			$indexer_query = new Indexer_Query( $query );
			Query_Store::add_query( $indexer_query );
		}
		if ( $indexer_query === null ) {
			return;
		}
		$source_query = $indexer_query->get_query();

		// Make sure the query is using the indexer.
		if ( $source_query->get_attribute( 'useIndexer' ) !== 'yes' ) {
			return;
		}

		// If this field has already been built (before) then skip it.
		if ( ! empty( self::$fields[ $field_id ]['options'] ) ) {
			return;
		}

		// Get the relationship value from the source query.
		$source_query       = $indexer_query->get_query();
		$field_relationship = $source_query->get_attribute( 'fieldRelationship' );

		$cache_key = self::get_choice_field_cache_key( $field, $indexer_query, $field_relationship, $multiple_match_method );

		$count_items = null;

		if ( Cache::enabled() ) {
			// Try Tiered_Cache which handles all layers: Memory → APCu → wp_cache → Database.
			$cache        = self::get_cache_instance( $query_id );
			$tiered_key   = 'count_' . $field_id . '_' . $cache_key;
			$tiered_found = false;
			$tiered_count = $cache->get( $tiered_key, $tiered_found );

			if ( $tiered_found && $tiered_count !== null ) {
				$count_items = $tiered_count;
			}
		}

		// There was an error.
		if ( $count_items === false ) {
			return;
		}

		/**
		 * Fires when field count queries start.
		 *
		 * @param \Search_Filter\Fields\Field $field The field being counted.
		 */
		do_action( 'search-filter-pro/indexer/field_queries/counts/start', $field );

		if ( $count_items === null ) {
			// There is no cached item in the DB so build it and store it.
			$field_id = absint( $field_id );

			// Whether to collapse children into parents when counting, this
			// means a count for a child object is the same as its parent.
			/**
			 * Filters whether to collapse children into parents for counting.
			 *
			 * @param bool                         $collapse Whether to collapse children.
			 * @param \Search_Filter\Queries\Query $query    The S&F query object.
			 */
			$collapse_children_into_parents = apply_filters( 'search-filter-pro/indexer/query/collapse_children', false, $indexer_query->get_query() );

			$count_items = null;

			// Check if bitmaps are available for this field.
			$bitmaps_available = \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct::has_bitmaps_for_field( $field_id );

			if ( ! $bitmaps_available ) {
				// No bitmaps available - this shouldn't happen in normal operation.
				return;
			}

			// We intersect at child level, then convert to parents afterward.
			// This prevents false positives by maintaining attribute relationships.

			// Get all bitmaps for this field from the cache/DB.
			// Index_Query_Direct handles caching internally - if data was pre-loaded
			// during query construction, it will be returned from cache.
			$value_bitmaps = \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct::get_field_bitmaps( $field_id, null );

			// Get result bitmap for intersection.
			$filtered_bitmap   = $indexer_query->get_result_bitmap();
			$unfiltered_bitmap = $indexer_query->get_unfiltered_result_bitmap();

			// Early return if bitmaps are not available (result lookup failed).
			if ( $filtered_bitmap === null || $unfiltered_bitmap === null ) {
				return;
			}

			// Use bitmaps for count calculations.
			$result_bitmap = self::get_field_value_matched_bitmap(
				$field,
				$filtered_bitmap,
				$unfiltered_bitmap,
				$source_query->get_attribute( 'fieldRelationship' ),
				$indexer_query
			);

			// Derive sources from query post types for parent map lookup.
			$post_types = $source_query->get_attribute( 'postTypes' );
			$sources    = Parent_Map_Converter::post_types_to_sources( $post_types );

			// Use same hook from the query class.
			$sources = apply_filters( 'search-filter-pro/indexer/query/parent_map_sources', $sources, $source_query );

			// Get collapsed (parent-space) bitmap for filtering.
			// This is the parent-space representation of unfiltered_result_bitmap.
			$collapsed_bitmap = $collapse_children_into_parents
				? $indexer_query->get_unfiltered_result_bitmap_collapsed()
				: null;

			// Compute counts using value bitmaps.
			$count_items = self::compute_counts_from_bitmaps(
				$result_bitmap,
				$value_bitmaps,
				$collapse_children_into_parents,
				$sources,
				$collapsed_bitmap
			);

			if ( Cache::enabled() ) {
				// Store in Tiered_Cache which handles all layers: Memory → APCu → wp_cache → Database.
				$cache      = self::get_cache_instance( $query_id );
				$tiered_key = 'count_' . $field_id . '_' . $cache_key;
				$ttl        = self::get_cache_ttl( $indexer_query->has_search() );
				$cache->set( $tiered_key, wp_json_encode( $count_items ), $ttl );
			}
		} else {
			$count_items = json_decode( $count_items, true );
		}

		if ( $count_items ) {
			foreach ( $count_items as $item ) {
				self::$fields[ $field_id ]['counts'][ $item['value'] ] = absint( $item['count'] );
			}
		}

		/**
		 * Fires when field count queries finish.
		 *
		 * @param \Search_Filter\Fields\Field $field The field being counted.
		 */
		do_action( 'search-filter-pro/indexer/field_queries/counts/finish', $field );
	}


	/**
	 * Get cache key for a choice field.
	 *
	 * Based on the field relationship and match mode so we can
	 * reuse the cache key where possible.
	 *
	 * @since 3.0.0
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to get the cache key for.
	 * @param    Indexer_Query               $indexer_query    The indexer query.
	 * @param    string                      $field_relationship    The field relationship.
	 * @param    string                      $multiple_match_method    The multiple match method.
	 * @return   string    The cache key.
	 */
	private static function get_choice_field_cache_key( $field, $indexer_query, $field_relationship, $multiple_match_method ) {

		$field_id = $field->get_id();
		// If field relationship is 'all' and match mode is 'all', use the cache key
		// as it is, it contains all the fields values that are being used.
		$cache_key = $indexer_query->get_cache_key();

			// Get the filtered args except the current field into the cache key.
		if ( $field_relationship === 'all' && $multiple_match_method === 'any' ) {

			$cache_args       = $indexer_query->get_cache_query_args();
			$field_cache_args = $indexer_query->get_field_cache_args();
			// Unset the current field ID from the cache args.
			if ( isset( $field_cache_args[ $field_id ] ) ) {
				unset( $field_cache_args[ $field_id ] );
			}
			$cache_key = $indexer_query->create_cache_key(
				$cache_args,
				$field_cache_args
			);
		} elseif ( $field_relationship === 'any' && $multiple_match_method === 'any' ) {
			$cache_key = $indexer_query->get_unfiltered_cache_key();
		} elseif ( $field_relationship === 'any' && $multiple_match_method === 'all' ) {
			$cache_args = $indexer_query->get_cache_query_args();
			$cache_key  = $indexer_query->create_cache_key(
				$cache_args,
				array(
					$field_id => $field->get_values(),
				)
			);
		}

		return $cache_key;
	}


	/**
	 * Get the field value matched bitmap.
	 *
	 * @since 3.0.7
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to get the bitmap for.
	 * @param    Bitmap                      $filtered_bitmap    The filtered result bitmap.
	 * @param    Bitmap                      $unfiltered_bitmap  The unfiltered result bitmap.
	 * @param    string                      $field_relationship    The field relationship.
	 * @param    Indexer_Query               $indexer_query    The indexer query.
	 * @return   Bitmap    The field value matched bitmap.
	 */
	private static function get_field_value_matched_bitmap( $field, $filtered_bitmap, $unfiltered_bitmap, $field_relationship, $indexer_query ) {
		$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
		$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

		if ( $field_relationship === 'all' ) {
			if ( $multiple_match_method === 'all' ) {
				return $filtered_bitmap;
			}

			// Get combined bitmap excluding this field.
			$combined_bitmap = $indexer_query->get_combined_result_field_bitmaps_excluding( $field->get_id() );

			if ( $combined_bitmap !== null ) {
				return $combined_bitmap->intersect( $unfiltered_bitmap );
			} else {
				return $unfiltered_bitmap;
			}
		}

		if ( $multiple_match_method === 'any' ) {
			return $unfiltered_bitmap;
		}

		// Field relationship 'any', match mode 'all'.
		$field_result_bitmap = $indexer_query->get_field_result_bitmap( $field->get_id() );
		if ( $field_result_bitmap !== null ) {
			return $field_result_bitmap->intersect( $unfiltered_bitmap );
		}

		return $unfiltered_bitmap;
	}


	/**
	 * Compute counts from bitmaps with correct parent-level counting
	 *
	 * Implements nested query pattern:
	 * 1. Intersect at child level (maintains attribute relationships)
	 * 2. Map to parents (deduplication)
	 * 3. Count unique parents
	 *
	 * @since 3.0.7
	 *
	 * @param Bitmap      $result_bitmap   Result bitmap (child IDs).
	 * @param array       $value_bitmaps   Value bitmaps ['value' => ['bitmap' => Bitmap]].
	 * @param bool        $collapse_children Whether to count parents (not children).
	 * @param array       $sources         Source identifiers for parent map filtering.
	 * @param Bitmap|null $collapsed_bitmap Optional parent-space bitmap for validity filtering.
	 *                                       When provided, filters parent conversion to only include valid parents.
	 * @return array Count items [['value' => 'red', 'count' => 12], ...].
	 */
	private static function compute_counts_from_bitmaps( $result_bitmap, $value_bitmaps, $collapse_children = false, $sources = array(), $collapsed_bitmap = null ) {

		if ( ! $collapse_children ) {
			// Non-collapsed: Simple child-level counting.
			$bitmaps_to_count = array();
			foreach ( $value_bitmaps as $value => $bitmap_data ) {
				$bitmaps_to_count[ $value ] = $bitmap_data['bitmap'];
			}

			$batch_counts = $result_bitmap->batch_intersect_counts( $bitmaps_to_count );

			$counts = array();
			foreach ( $batch_counts as $value => $count ) {
				if ( $count > 0 ) {
					$counts[] = array(
						'value' => $value,
						'count' => $count,
					);
				}
			}

			return $counts;
		}

		// COLLAPSED MODE: Nested pattern with batch optimization.

		// STEP 1: Collect all non-empty intersections FIRST.
		// This allows us to batch convert all child bitmaps at once,
		// loading the parent mapping only ONCE instead of 100-500 times.
		$child_bitmaps_to_convert = array();

		foreach ( $value_bitmaps as $value => $bitmap_data ) {
			$value_child_bitmap = $bitmap_data['bitmap'];

			// Intersect at CHILD level (prevents false positives!).
			$matching_children = $result_bitmap->intersect( $value_child_bitmap );

			if ( ! $matching_children->is_empty() ) {
				$child_bitmaps_to_convert[ $value ] = $matching_children;
			}
		}

		// Early return if no matches.
		if ( empty( $child_bitmaps_to_convert ) ) {
			return array();
		}

		// STEP 2: Batch convert ALL bitmaps at once (loads mapping once).
		// This is 22-26% faster at scale (100-500 options) because it eliminates
		// the overhead of 100-500 function calls + cache lookups.
		// Pass collapsed_bitmap to filter out parents with invalid post_status (e.g., private products).
		$parent_bitmaps = Parent_Map_Converter::convert_bitmaps_batch( $child_bitmaps_to_convert, $sources, $collapsed_bitmap );

		// STEP 3: Count unique parents for each value.
		$counts = array();

		foreach ( $parent_bitmaps as $value => $parent_bitmap ) {
			$parent_count = $parent_bitmap->count();

			if ( $parent_count > 0 ) {
				$counts[] = array(
					'value' => $value,
					'count' => $parent_count,
				);
			}
		}

		return $counts;
	}

	/**
	 * Format the count.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $count The count number.
	 * @param bool $show_brackets Whether to show brackets.
	 * @return string|int The formatted count (string with brackets or int without).
	 */
	private static function format_count( $count, $show_brackets = true ) {
		if ( $show_brackets ) {
			return '(' . absint( $count ) . ')';
		}
		return absint( $count );
	}

	/**
	 * Remove hide_empty from get_terms args.
	 *
	 * We need to disable hide_empty for non WP_Query types to work around an issue searching
	 * the media library.  WP Doesn't recognise taxonomies properly assigned to them, and hiding
	 * empty always hides all taxonomy options, so this setting forces them all to be shown at
	 * the taxonomy level - when using the indexer, we hide them using indexer counts later.
	 *
	 * @since 3.0.0
	 *
	 * @param    array                       $args    The tax terms args to filter.
	 * @param    \Search_Filter\Fields\Field $field   The field to filter.
	 * @return   array    The filtered args.
	 */
	public static function filter_get_terms_args( $args, $field ) {
		// Early check to ensure a fields query is using the indexer.
		$field_query = $field->get_query();

		if ( ! $field_query || is_wp_error( $field_query ) ) {
			return $args;
		}

		if ( $field_query->get_attribute( 'useIndexer' ) !== 'yes' ) {
			return $args;
		}

		// If a strategy matches a field, then we know it's using the indexer
		// so we can disable hide_empty.
		if ( Index_Strategy_Factory::for_field( $field ) ) {
			$args['hide_empty'] = false;
		}
		return $args;
	}
	/**
	 * Filters a field option to add counts or hide it.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $option    The option to filter.
	 * @param    int   $field_id    The field ID to filter.
	 * @return   array|null    The filtered option.
	 */
	public static function filter_field_option( $option, $field_id ) {
		// Delegate to Legacy Field_Queries if migration not completed.
		if ( ! \Search_Filter_Pro\Indexer::migration_completed() ) {
			return \Search_Filter_Pro\Indexer\Legacy\Field_Queries::filter_field_option( $option, $field_id );
		}

		// Check if we need to show/hide options based on the field setting
		// and add counts from the indexer.
		if ( ! isset( $option['value'] ) || $option['value'] === '' ) {
			return $option;
		}
		if ( ! isset( self::$fields[ $field_id ] ) ) {
			return $option;
		}
		$hide_empty          = self::$fields[ $field_id ]['hideEmpty'];
		$show_count          = self::$fields[ $field_id ]['showCount'];
		$show_count_brackets = self::$fields[ $field_id ]['showCountBrackets'];

		$field        = self::$fields[ $field_id ]['field'];
		$option_value = $option['value'];

		if ( self::$fields[ $field_id ]['useIndexer'] !== 'yes' ) {
			return $option;
		}
		// If an option is in the selected field values, then we don't want to hide it,
		// otherwise the option dissapears and the user can no longer deselect it.
		$in_values           = in_array( $option_value, $field->get_values(), true );
		$has_active_children = self::option_has_active_children( $option, $field->get_values() );
		$can_hide            = ! $in_values && $hide_empty && ! $has_active_children;
		// If we already have the IDs for the option, then return it.
		// Occurs when the same field is added to the page multiple times.
		if ( $field_id === 0 ) {
			// Then we're likely in a preview, so we'll have to generate
			// random count numbers for now.
			$count = wp_rand( 1, 100 );
			if ( $can_hide && $count === 0 ) {
				return null;
			}
			$option['count'] = $count;
			if ( $show_count ) {
				$option['countLabel'] = self::format_count( $count, $show_count_brackets );
			}
			return $option;
		}
		// Use index_value in case the database stored value is different from the field value.
		$index_value = isset( $option['indexValue'] ) ? $option['indexValue'] : $option['value'];
		// Use the already stored value from the query.
		if ( isset( self::$fields[ $field_id ]['counts'][ $index_value ] ) ) {
			$count = self::$fields[ $field_id ]['counts'][ $index_value ];
			if ( $can_hide && $count === 0 ) {
				return null;
			}
			$option['count'] = $count;
			if ( $show_count ) {
				$option['countLabel'] = self::format_count( $count, $show_count_brackets );
			}
			return $option;
		}
		// We shouldn't get here, but if we do assume the count is 0.
		if ( $can_hide ) {
			return null;
		}

		$option['count'] = 0;
		if ( $show_count ) {
			$option['countLabel'] = self::format_count( 0, $show_count_brackets );
		}
		return $option;
	}

	/**
	 * Check if any children of the option are active.
	 *
	 * @since 3.0.4
	 *
	 * @param array $option The option to check.
	 * @param array $field_values The field values to check against.
	 * @return bool Whether the option has any active children.
	 */
	private static function option_has_active_children( $option, $field_values ) {

		if ( ! isset( $option['options'] ) ) {
			return false;
		}

		foreach ( $option['options'] as $child_option ) {

			if ( in_array( $child_option['value'], $field_values, true ) ) {
				return true;
			}

			if ( isset( $child_option['options'] ) ) {
				$has_active_children = self::option_has_active_children( $child_option, $field_values );
				if ( $has_active_children ) {
					return true;
				}
			}
		}

		return false;
	}
}
