<?php
/**
 * Indexer query builder.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Fields;
use Search_Filter\Query\Template_Data;
use Search_Filter_Pro\Util;
use Search_Filter_Pro\Indexer\Bucket\Updater as Bucket_Updater;
use Search_Filter_Pro\Indexer\Bucket\Query as Bucket_Query;
use Search_Filter_Pro\Indexer\Parent_Map\Converter as Parent_Map_Converter;
use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Cache;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the relevant queries needed when using the indexer.
 *
 * @since 3.0.0
 */
class Query {

	/**
	 * The S&F query object.
	 *
	 * @since 3.0.0
	 *
	 * @var \Search_Filter\Queries\Query
	 */
	private $query;

	/**
	 * The fields for the query.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Fields by their ID for easy lookup.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $fields_by_id = array();

	/**
	 * The result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $query_args = array();

	/**
	 * The cache key based on the query args.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private $cache_key = '';

	/**
	 * The query args that were used to generate the cache key.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $cache_query_args = array();

	/**
	 * Field result IDs stored as bitmaps.
	 *
	 * @since 3.0.7
	 * @var array [field_id => Bitmap]
	 */
	private $field_result_bitmaps = array();

	/**
	 * The result bitmap (filtered).
	 *
	 * @since 3.0.7
	 * @var Bitmap|null
	 */
	private $result_bitmap = null;

	/**
	 * The unfiltered result bitmap.
	 *
	 * When collapse_children is enabled, this contains child IDs (converted from parent IDs)
	 * for intersection with value bitmaps in get_field_value_matched_bitmap().
	 *
	 * @since 3.0.7
	 * @var Bitmap|null
	 */
	private $unfiltered_result_bitmap = null;

	/**
	 * Unfiltered result bitmap in collapsed (parent) form.
	 *
	 * When collapse_children is enabled, this is the parent-space representation of
	 * unfiltered_result_bitmap. Both contain the same logical result set:
	 * - unfiltered_result_bitmap: expanded to child IDs (for index intersection)
	 * - unfiltered_result_bitmap_collapsed: parent IDs (for validity filtering in counts)
	 *
	 * Only set when collapse_children is enabled.
	 *
	 * @since 3.2.0
	 * @var Bitmap|null
	 */
	private $unfiltered_result_bitmap_collapsed = null;

	/**
	 * Value bitmaps loaded for each field (for data reuse).
	 *
	 * Structure: [field_id => ['value' => ['bitmap' => Bitmap, 'count' => int]]]
	 * These are loaded during field queries and can be reused for counting,
	 * eliminating redundant database queries.
	 *
	 * @since 3.0.7
	 * @var array
	 */
	private $field_value_bitmaps = array();

	/**
	 * The unfiltered cache key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private $unfiltered_cache_key = '';

	/**
	 * The field arguments to set the cache key.
	 *
	 * Usually just the field IDs and their values.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $field_cache_args = array();

	/**
	 * Whether the unfiltered query is needed for accurate counts.
	 *
	 * True when there are fields with showCount/hideEmpty AND
	 * (query has match_any OR field has match_any OR has bucket strategy fields).
	 *
	 * @since 3.0.7
	 *
	 * @var bool
	 */
	private $needs_unfiltered_query = false;

	/**
	 * Whether the query has fields that need counts.
	 *
	 * This is used to determine if we need to run an unfiltered query
	 * for accurate counts.
	 *
	 * @since 3.0.7
	 *
	 * @var bool
	 */
	private $has_fields_needing_counts = false;

	/**
	 * Whether the query has a search term.
	 *
	 * @since 3.0.0
	 *
	 * @var bool|null
	 */
	private $has_search = null;

	/**
	 * BM25-ordered post IDs from search (for relevance ordering).
	 *
	 * When search fields are used, this stores the post IDs in
	 * BM25 relevance order so we can preserve ordering in WP_Query.
	 *
	 * @since 3.1.0
	 *
	 * @var array|null
	 */
	private $search_ordered_ids = null;

	/**
	 * Parent map sources derived from query post types.
	 *
	 * Used to filter parent map lookups to only relevant sources.
	 *
	 * @since 3.2.0
	 *
	 * @var array
	 */
	private $parent_map_sources = array();

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Queries\Query $query The S&F query object.
	 */
	public function __construct( $query ) {

		/**
		 * Fires when indexer query initialization starts.
		 *
		 * @param \Search_Filter\Queries\Query $query The S&F query object.
		 */
		do_action( 'search-filter-pro/indexer/query/init/start', $query );

		$this->query  = $query;
		$this->fields = $query->get_fields();

		// Derive parent map sources from query post types for source-isolated lookups.
		$post_types               = $query->get_attribute( 'postTypes' );
		$this->parent_map_sources = Parent_Map_Converter::post_types_to_sources( $post_types );

		$this->parent_map_sources = apply_filters( 'search-filter-pro/indexer/query/parent_map_sources', $this->parent_map_sources, $query );
		// Start off as `null` so we know if any fields were applied to the query.
		// A null initial state also helps to determine how to initially combine
		// result IDs.
		$post__in                 = null;
		$query_field_relationship = $query->get_attribute( 'fieldRelationship' );

		// Setup the combine type for the fields.
		$combine_type = '';
		if ( $query_field_relationship === 'any' ) {
			$combine_type = 'merge';
		} elseif ( $query_field_relationship === 'all' ) {
			$combine_type = 'intersect';
		}

		$field_cache_args = array();

		$taxonomy_archive_filter = null;

		// Need to figure out if we're filtering a tax archive.
		$is_tax_archive = false;
		$wp_query_args  = array();
		$wp_query       = $query->get_wp_query();

		if ( $wp_query ) {
			if ( ( $wp_query->is_tax() || $wp_query->is_category() || $wp_query->is_tag() ) && $wp_query->is_archive() ) {
				$is_tax_archive = true;
			}
			if ( property_exists( $wp_query, 'query' ) ) {
				$wp_query_args = $wp_query->query;
			}
		}

		// Remove our query arg in case they made it through (eg when we filter the loop block args
		// our query info gets added to `->query` instead of `->query_vars`).
		if ( isset( $wp_query_args['search_filter_query_id'] ) ) {
			unset( $wp_query_args['search_filter_query_id'] );
		}
		if ( isset( $wp_query_args['search_filter_queries'] ) ) {
			unset( $wp_query_args['search_filter_queries'] );
		}

		if ( isset( $wp_query_args['posts_per_page'] ) ) {
			unset( $wp_query_args['posts_per_page'] );
		}
		if ( isset( $wp_query_args['paged'] ) ) {
			unset( $wp_query_args['paged'] );
		}

		// When using bitmap operations, collect bitmaps directly to avoid conversions.
		$all_filter_field_bitmaps = array();

		// Collect all fields.
		$fields_by_strategy = array(
			'bitmap' => array(),
			'bucket' => array(),
			'search' => array(),
			'none'   => array(),
		);

		// First pass: collect fields that can be batched.
		foreach ( $this->fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			if ( $is_tax_archive && method_exists( $field, 'navigates_taxonomy_archive' ) ) {
				$navigates_taxonomy_archive = $field->navigates_taxonomy_archive();
				// Make sure the current archive matches the the field before setting it
				// as we can only ever have 1 activate at a time.
				if ( $navigates_taxonomy_archive && Template_Data::get_tax_archive() === $navigates_taxonomy_archive ) {
					$taxonomy_archive_filter = $navigates_taxonomy_archive;
				}
			}

			$this->fields_by_id[ $field->get_id() ] = $field;
			if ( count( $field->get_values() ) === 0 ) {
				continue;
			}

			Fields::register_active_field( $field );

			$field_cache_args[ $field->get_id() ] = $field->get_values();

			$field_interaction_type = $field->get_interaction_type();
			$field_strategy         = Index_Strategy_Factory::get_by_interaction_type( $field_interaction_type );

			// Recheck field support via the strategy `supports()` method as it can be overriden on a per field basis.
			if ( $field_strategy && $field_strategy->supports( $field ) ) {
				$fields_by_strategy[ $field_strategy->get_type() ][] = $field;
			} else {
				$fields_by_strategy['none'][] = $field;
			}
		}

		// Determine if we should use collapsed bitmaps (for hierarchical posts, product variations, etc.).
		$collapse_children = apply_filters( 'search-filter-pro/indexer/query/collapse_children', false, $this->get_query() );

		// ============================================================
		// Bitmap strategy fields
		// ============================================================

		// Run batch query.
		if ( ! empty( $fields_by_strategy['bitmap'] ) ) {
			// Run batch query.
			$field_bitmap_configs = array();
			foreach ( $fields_by_strategy['bitmap'] as $choice_field ) {
				// We can specify which values to get or all.
				// TODO - depending on if we need counts etc, we might not need to get all values.
				$field_bitmap_configs[ $choice_field->get_id() ] = array(
					'values' => array(), // Query  all values.
				);
			}

			// Always load regular bitmaps (child-level), never collapsed.
			// Filtering must happen at child level to avoid false positives.
			// We'll convert to parent IDs after intersection.
			$batched_field_bitmaps = \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct::get_batched_field_bitmaps( $field_bitmap_configs );

			// Loop and build result ID bitmaps.
			foreach ( $fields_by_strategy['bitmap'] as $field ) {

				$field_id = $field->get_id();

				// Process bitmap field results.

				// Flag that we have a field that is using counts or hide empty logic.

				// Track match type for each field.
				$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
				$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

				$hide_empty               = $field->get_attribute( 'hideEmpty' ) === 'yes';
				$show_count               = $field->get_attribute( 'showCount' ) === 'yes';
				$needs_count_calculations = $hide_empty || $show_count;
				if ( $needs_count_calculations ) {
					$this->has_fields_needing_counts = true;
				}

				// Bitmap fields with 'any' match need unfiltered query to calculate option counts.
				if ( $multiple_match_method === 'any' && $needs_count_calculations ) {
					$this->needs_unfiltered_query = true;
				}

				if ( ! isset( $batched_field_bitmaps[ $field_id ] ) ) {
					continue;
				}

				$value_bitmaps = $batched_field_bitmaps[ $field_id ]; // ALL values from DB.

				if ( empty( $value_bitmaps ) ) {
					continue;
				}

				// Extract selected values.
				$selected_bitmaps = array();
				$field_values     = $this->get_bitmap_field_values( $field );

				foreach ( $field_values as $value ) {
					if ( isset( $value_bitmaps[ $value ] ) ) {
						$bitmap             = $value_bitmaps[ $value ]['bitmap'];
						$selected_bitmaps[] = $bitmap;
					} else {
						// Value has no results, so use empty bitmap.
						$selected_bitmaps[] = new Bitmap();
					}
				}

				// Combine based on match method.
				$result_bitmap = null;
				if ( $multiple_match_method === 'any' ) {
					// Union: red OR blue.
					$result_bitmap = array_shift( $selected_bitmaps );
					foreach ( $selected_bitmaps as $bitmap ) {
						$result_bitmap = $result_bitmap->union( $bitmap );
					}
				} else {

					// Intersection: red AND blue.
					$result_bitmap = array_shift( $selected_bitmaps );
					foreach ( $selected_bitmaps as $bitmap ) {
						$result_bitmap = $result_bitmap->intersect( $bitmap );
					}
				}

				// Store results.
				$this->field_result_bitmaps[ $field_id ] = $result_bitmap;
				$this->field_value_bitmaps[ $field_id ]  = $value_bitmaps;

				// Store in the filters array.
				$all_filter_field_bitmaps[] = $result_bitmap;
			}
		}

		// ============================================================
		// Bucket strategy fields
		// ============================================================
		if ( ! empty( $fields_by_strategy['bucket'] ) ) {
			foreach ( $fields_by_strategy['bucket'] as $field ) {

				// Process bucket field results.
				$field_values = $field->get_values();
				$field_id     = $field->get_id();

				// Extract min/max from field values.
				$min = $field_values[0] ?? null;
				$max = $field_values[1] ?? null;

				// Bucket strategy fields ALWAYS need unfiltered query.
				$this->needs_unfiltered_query = true;

				// Bucket fields use buckets.
				if ( Bucket_Updater::has_field_data( $field_id ) ) {
					$result_bitmap = Bucket_Query::get_range_bitmap( $field_id, $min, $max );
					if ( $result_bitmap ) {
						// Store results.
						$this->field_result_bitmaps[ $field_id ] = $result_bitmap;

						// Store in the filters array.
						$all_filter_field_bitmaps[] = $result_bitmap;
					}
				}
			}
		}

		// Allow custom query implementation for fields that don't have strategy.
		$this->field_result_bitmaps = apply_filters( 'search-filter-pro/indexer/query/field_result_bitmaps', $this->field_result_bitmaps, $fields_by_strategy );

		// Batch combine all filter field results.
		$combined_bitmap = null;
		if ( ! empty( $all_filter_field_bitmaps ) ) {
			$combined_bitmap = self::combine_bitmaps( $all_filter_field_bitmaps, $combine_type );

			// Apply parent conversion for final WP_Query post__in when collapse_children is enabled.
			// IMPORTANT: We convert a COPY to parents for post__in, keeping $combined_bitmap as child IDs.
			// This is because:
			// - Field_Queries needs child IDs in result_bitmap for proper counting intersection
			// - WP_Query needs parent IDs in post__in to return parent products
			// - They serve different purposes and need different ID types!
			if ( $collapse_children && $combined_bitmap && ! $combined_bitmap->is_empty() ) {
				$parent_bitmap = Parent_Map_Converter::convert_bitmap_to_parents( $combined_bitmap, $this->parent_map_sources );
				$post__in      = $parent_bitmap ? $parent_bitmap->to_post_ids() : null;
			} else {
				$post__in = $combined_bitmap ? $combined_bitmap->to_post_ids() : null;
			}
		}

		// ============================================================
		// Search strategy fields
		// ============================================================
		// TODO - we need to know if there are fields that need counts,  if so,
		// we still need to execute the search queries, if not, we can bypass.

		$search_failed_query      = false;
		$all_search_field_bitmaps = array();
		$all_search_ordered_ids   = array(); // Store BM25-ordered IDs for relevance ordering.
		if ( ! empty( $fields_by_strategy['search'] ) ) {

			// Skip search logic if there are no posts from filtering.
			foreach ( $fields_by_strategy['search'] as $field ) {

				// Process search field using inverted index.
				$field_id    = $field->get_id();
				$field_value = $field->get_value();

				// Use inverted index search for 50-100x speedup with BM25 scoring.
				$search_query = new \Search_Filter_Pro\Indexer\Search\Query_Builder();

				// Determine search constraint based on whether we need unfiltered query.
				// If unfiltered query needed for counts, run search unconstrained.
				// Otherwise, apply filter constraint for performance (FastBit optimization).
				$search_constraint = $this->needs_unfiltered_query ? null : $post__in;

				// Get query language for filtering (null = search all languages).
				$query_language = apply_filters( 'search-filter-pro/indexer/search/query_language', null, $field );

				// Execute search and get array result (preserves BM25 order).
				$search_ordered_ids = $search_query->search(
					$field_value,
					array(
						'field_id'           => $field_id,  // Constrain to this field's postings.
						'allowed_object_ids' => $search_constraint,
						'return_format'      => 'array',  // Return array to preserve BM25 order.
						'limit'              => -1, // No limit, we want all matching posts.
						'language'           => $query_language,
					)
				);

				// Store ordered IDs for relevance ordering (first search field takes precedence).
				if ( empty( $all_search_ordered_ids ) ) {
					$all_search_ordered_ids = $search_ordered_ids;
				}

				// Convert to bitmap for integration with existing combiner.
				$search_bitmap = Bitmap::from_post_ids( $search_ordered_ids );

				// Store bitmap result (integrates with existing bitmap combiner).
				$this->field_result_bitmaps[ $field_id ] = $search_bitmap;
				if ( $combine_type === 'intersect' && $search_bitmap->is_empty() ) {
					$search_failed_query = true;
					// TODO - should this be a `continue` so that we can figure out
					// counts for other fields?
					break;
				}

				$all_search_field_bitmaps[] = $search_bitmap;
			}

			// Store the BM25-ordered IDs for relevance ordering.
			$this->search_ordered_ids = $all_search_ordered_ids;
		}

		// Allow custom search query implementations.
		$search_result = apply_filters(
			'search-filter-pro/indexer/query/search/result',
			array(
				'bitmaps' => $all_search_field_bitmaps,
				'failed'  => $search_failed_query,
			),
			$fields_by_strategy,
			$this->field_result_bitmaps,
			$combine_type
		);

		// Handle search results logic.
		if ( $search_result['failed'] ) {
			$post__in = array(); // Signify no results found.
		} elseif ( ! empty( $search_result['bitmaps'] ) ) {
			// Combine search field results.
			$combined_search_bitmap = self::combine_bitmaps( $search_result['bitmaps'], $combine_type );

			// If search was run unconstrained (for counts), combine with filter results.
			// Otherwise, search was already constrained so use search results directly.
			if ( $this->needs_unfiltered_query && $combined_bitmap !== null ) {
				// Search ran unconstrained - must combine with filters for final result.
				$combined_bitmap = $combined_bitmap->intersect( $combined_search_bitmap );

				// Parent conversion for search + filters combination (convert copy for post__in).
				if ( $collapse_children && $combined_bitmap && ! $combined_bitmap->is_empty() ) {
					$parent_bitmap = Parent_Map_Converter::convert_bitmap_to_parents( $combined_bitmap, $this->parent_map_sources );
					$post__in      = $parent_bitmap ? $parent_bitmap->to_post_ids() : null;
				} else {
					$post__in = $combined_bitmap ? $combined_bitmap->to_post_ids() : null;
				}
			} elseif ( $combined_search_bitmap !== null ) {
				// Search was already constrained by filters - use search results directly.
				// Parent conversion for search-only results (convert copy for post__in).
				if ( $collapse_children && $combined_search_bitmap && ! $combined_search_bitmap->is_empty() ) {
					$parent_bitmap = Parent_Map_Converter::convert_bitmap_to_parents( $combined_search_bitmap, $this->parent_map_sources );
					$post__in      = $parent_bitmap ? $parent_bitmap->to_post_ids() : null;
				} else {
					$post__in = $combined_search_bitmap ? $combined_search_bitmap->to_post_ids() : null;
				}
			}
		}

		if ( is_array( $post__in ) && empty( $post__in ) ) {
			// Add a post ID of 0 to force the query to return no results.
			$post__in = array( 0 );
		}

		// Usually if a queries field relationship is set to `any` we'd need an unfiltered query
		// to get accurate counts for the other options in the field, but lets be sure we have
		// choice fields that really do need those counts as a micro optimization, we could for
		// find ourselves in a situation with only a search field, in which case requesting an
		// unfiltered query would be unnecessary.
		if ( $query_field_relationship === 'any' && $this->has_fields_needing_counts ) {
			$this->needs_unfiltered_query = true;
		}

		// We need to handle S&F queries that filter taxonomy archives.
		// If a query has a field that is filtering / associated with the archive,
		// then we'll need to unset wp_query tax archive and let the field handle it.
		if ( $taxonomy_archive_filter ) {
			// Then we need to unset the WP Query taxonomy filter and let the field handle it.
			// If its a category, then we need to unset `category_name` from the query args.
			// If its a tag, then we need to unset `tag` from the query args.
			// If its a taxonomy then we need unset the taxonomy name.
			// NOTE: WordPress docs say this way of filtering a query by tag/category/taxonomy
			// is deperecated, yet its used for all archives.
			$unset_key = $taxonomy_archive_filter;
			if ( $taxonomy_archive_filter === 'category' ) {
				$unset_key = 'category_name';
			} elseif ( $taxonomy_archive_filter === 'post_tag' ) {
				$unset_key = 'tag';
			}
			if ( isset( $wp_query_args[ $unset_key ] ) ) {
				unset( $wp_query_args[ $unset_key ] );
			}
		}

		// Get the query args from the query settings.
		$query_args = $query->apply_wp_query_args( $wp_query_args );

		// Apply the query args for fields that are not handled by the indexer.
		foreach ( $fields_by_strategy['none'] as $field ) {
			// Apply any field-specific query args.
			$query_args = $field->apply_wp_query_args( $query_args );
		}

		/**
		 * Filters the WP_Query args for the search query.
		 *
		 * Applies any user defined query args.
		 *
		 * @param array                        $query_args The query args.
		 * @param \Search_Filter\Queries\Query $query      The S&F query object.
		 */
		$query_args = apply_filters( 'search-filter/query/query_args', $query_args, $query );

		// Use the query args for the cache key (we don't need all the extra args to identify
		// the query as it will remain the same each time.
		$this->cache_query_args = $query_args;
		$cache_key              = $this->create_cache_key( $query_args, $field_cache_args );
		$this->cache_key        = $cache_key;
		$this->field_cache_args = $field_cache_args;

		if ( $this->needs_unfiltered_query && ! empty( $field_cache_args ) ) {
			// ============================================================
			// STRATEGY 1: Run unfiltered query, derive filtered results
			// ============================================================

			// Build cache key for unfiltered query (no field filters).
			$unfiltered_cache_key       = $this->create_cache_key( $query_args, array() );
			$this->unfiltered_cache_key = $unfiltered_cache_key;

			// Run the unfiltered query (base query without post__in from field results).
			$unfiltered_full_query_args = $this->create_full_query_args( $query_args );
			$unfiltered_result_ids      = $this->result_lookup( $unfiltered_cache_key, $unfiltered_full_query_args );

			// Create the bitmaps (even if empty) unless the result lookup failed.
			if ( $unfiltered_result_ids !== false ) {

				// Create unfiltered bitmap, converting to child IDs when collapse_children is enabled.
				// This is needed because Field_Queries intersects unfiltered_bitmap with value bitmaps
				// (which contain child IDs), so we need matching ID spaces.
				if ( $collapse_children && ! empty( $unfiltered_result_ids ) ) {
					// Build both bitmaps in single pass (optimized).
					$bitmaps                                  = Parent_Map_Converter::convert_to_children_with_bitmaps( $unfiltered_result_ids, $this->parent_map_sources );
					$this->unfiltered_result_bitmap_collapsed = $bitmaps['parent_bitmap'];
					$this->unfiltered_result_bitmap           = $bitmaps['child_bitmap'];
				} else {
					$this->unfiltered_result_bitmap = Bitmap::from_post_ids( $unfiltered_result_ids );
				}

				// Derive filtered results by intersecting unfiltered with field results.
				// When collapse_children is enabled:
				// - post__in contains parent IDs
				// - unfiltered_result_ids contains parent IDs (from WP_Query)
				// - Intersection works (parent ∩ parent)
				// - But Field_Queries needs child IDs in result_bitmap.
				$result_ids = $this->derive_filtered_ids_from_unfiltered( $unfiltered_result_ids, $post__in );

				// Set result_bitmap: use child IDs when collapse_children is enabled.
				if ( $collapse_children && ! empty( $result_ids ) ) {
					// Build child bitmap in single pass (optimized). Parent bitmap already set above.
					$bitmaps             = Parent_Map_Converter::convert_to_children_with_bitmaps( $result_ids, $this->parent_map_sources );
					$search_child_bitmap = $bitmaps['child_bitmap'];

					if ( $combined_bitmap && ! $combined_bitmap->is_empty() ) {
						// Intersect with filter bitmap to get only matching children.
						$this->result_bitmap = $combined_bitmap->intersect( $search_child_bitmap );
					} else {
						// No filter bitmap - use all children from search results.
						$this->result_bitmap = $search_child_bitmap;
					}
				} else {
					// Normal case (no collapse): use WP_Query results directly.
					$this->result_bitmap = Bitmap::from_post_ids( $result_ids );
				}
			}
		} else {
			// ============================================================
			// STRATEGY 2: Run filtered query only (no unfiltered needed)
			// ============================================================

			// Run the filtered query (with post__in from field results).
			$full_query_args = $this->create_full_query_args( $query_args, $post__in );
			$result_ids      = $this->result_lookup( $cache_key, $full_query_args );

			// Create the bitmaps (even if empty) unless the result lookup failed.
			if ( $result_ids !== false ) {
				// When collapse_children is enabled, post__in contains parent IDs, so WP_Query
				// returns parent IDs. But Field_Queries needs child IDs in result_bitmap.
				if ( $collapse_children && ! empty( $result_ids ) ) {
					// Build both bitmaps in single pass (optimized).
					$bitmaps                                  = Parent_Map_Converter::convert_to_children_with_bitmaps( $result_ids, $this->parent_map_sources );
					$this->unfiltered_result_bitmap_collapsed = $bitmaps['parent_bitmap'];
					$search_child_bitmap                      = $bitmaps['child_bitmap'];

					if ( $combined_bitmap && ! $combined_bitmap->is_empty() ) {
						// Intersect with filter bitmap to get only matching children.
						$this->result_bitmap = $combined_bitmap->intersect( $search_child_bitmap );
					} else {
						// No filter bitmap - use all children from search results.
						$this->result_bitmap = $search_child_bitmap;
					}

					// Use child bitmap for unfiltered - Field_Queries intersects
					// unfiltered_bitmap with value bitmaps (child IDs), so we need matching ID spaces.
					$this->unfiltered_result_bitmap = $search_child_bitmap;
				} else {
					// Normal case (no collapse): WP_Query results are already the right IDs.
					$this->result_bitmap            = Bitmap::from_post_ids( $result_ids );
					$this->unfiltered_result_bitmap = $this->result_bitmap; // Same for both.
				}
			}
		}

		$updated_post__in = $this->get_updated_post__in( $query_args, $post__in );

		$query_args['post__in'] = $updated_post__in;

		// Remove any IDs from `post__not_in` that are in `post__in`.
		if ( isset( $query_args['post__not_in'] ) ) {

			if ( ! is_array( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array();
			}
			// Lets ensure they're using integers.
			$post__not_in = array_map( 'absint', $query_args['post__not_in'] );
			// If there are posts in `post__in`, then remove the `post__not_in` IDs from `post__in`.
			if ( count( $query_args['post__in'] ) > 0 ) {
				$query_args['post__in'] = array_diff( $query_args['post__in'], $post__not_in );
				unset( $query_args['post__not_in'] );
			}
		}

		// Apply BM25 relevance ordering if search was used.
		if ( ! empty( $this->search_ordered_ids ) && ! empty( $query_args['post__in'] ) ) {
			$query_args = $this->apply_relevance_ordering( $query_args );
		}

		$this->query_args = $query_args;

		/**
		 * Fires when indexer query initialization finishes.
		 *
		 * @param \Search_Filter\Queries\Query $query The S&F query object.
		 */
		do_action( 'search-filter-pro/indexer/query/init/finish', $query );
	}

	/**
	 * Run the cached query, if its not enabled, then run the full query and cache it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key to use.
	 * @param array  $query_args The full query args.
	 * @return array The result IDs.
	 */
	private function result_lookup( $cache_key, $query_args ) {

		/**
		 * Fires when result lookup starts.
		 */
		do_action( 'search-filter-pro/indexer/query/result_lookup/start' );

		$result_ids = false;

		if ( Cache::enabled() ) {
			// Try to get the cached IDs.
			$result_ids = $this->get_query_cache( $cache_key );
		}

		// If not cached, then run the query.
		if ( ! $result_ids ) {

			/**
			 * Filters the query args before result lookup.
			 *
			 * @param array                        $query_args The query args.
			 * @param \Search_Filter\Queries\Query $query      The S&F query object.
			 */
			$query_args = apply_filters( 'search-filter-pro/indexer/query/result_lookup/query_args', $query_args, $this->get_query() );

			// Before we run the query, we need to remove the `pre_get_posts` hooks that
			// are already attached to prevent infinite loops.
			\Search_Filter\Query\Selector::detach_pre_get_posts_hooks();
			\Search_Filter\Query::detach_pre_get_posts_hooks();

			// Remove existing hooks from our plugin to prevent infinite loops.
			do_action( 'search-filter/query/pre_get_posts/detach' );

			$full_query = new \WP_Query( $query_args );

			// Re-attach the hooks.
			do_action( 'search-filter/query/pre_get_posts/attach' );

			\Search_Filter\Query\Selector::attach_pre_get_posts_hooks();
			\Search_Filter\Query::attach_pre_get_posts_hooks();

			if ( Cache::enabled() ) {
				$this->add_query_cache( $cache_key, $full_query->posts );
			}

			$result_ids = $full_query->posts;
		}

		/**
		 * Fires when result lookup finishes.
		 */
		do_action( 'search-filter-pro/indexer/query/result_lookup/finish' );

		return $result_ids;
	}

	/**
	 * Create the cache key.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args.
	 * @param array $field_cache_args The field cache args.
	 * @return string The cache key.
	 */
	public function create_cache_key( $query_args, $field_cache_args = array() ) {
		$cache_query_args = $query_args;

		if ( isset( $cache_query_args['posts_per_page'] ) ) {
			unset( $cache_query_args['posts_per_page'] );
		}
		if ( isset( $cache_query_args['paged'] ) ) {
			unset( $cache_query_args['paged'] );
		}

		// But we do need to know which filters are applied, so get their values.
		$cache_query_args['applied_fields'] = $field_cache_args;
		$cache_key                          = build_query( $cache_query_args );

		$cache_key = apply_filters( 'search-filter-pro/indexer/query/cache_key', $cache_key, $this->get_query() );
		return $cache_key;
	}

	/**
	 * Create the full query args.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args.
	 * @param array $result__post_in The result post in.
	 * @return array The full query args.
	 */
	private function create_full_query_args( $query_args, $result__post_in = array() ) {

		if ( ! empty( $result__post_in ) && is_array( $result__post_in ) ) {

			// Then update the post__in, and combine it with an existing post__in if its there.
			$post__in = $this->get_updated_post__in( $query_args, $result__post_in );

			// Then update the post__in in the query args.
			$query_args['post__in'] = $post__in;
		}

		$extend_query_args = array(
			'posts_per_page'         => -1,
			'paged'                  => 1,
			'fields'                 => 'ids',
			'suppress_filters'       => false,
			'no_found_rows'          => true,
			'nopaging'               => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$full_query_args = wp_parse_args( $extend_query_args, $query_args );

		return $full_query_args;
	}

	/**
	 * Get the updated post__in.
	 *
	 * @since 3.0.0
	 *
	 * @param array      $query_args     The query args.
	 * @param array|null $query_post__in The query post in (can be null if no filters applied).
	 * @return array The updated post in.
	 */
	private function get_updated_post__in( $query_args, $query_post__in ) {
		$query_args_post__in = isset( $query_args['post__in'] ) ? $query_args['post__in'] : array();

		// If $query_post__in is null, then there are no filters applied to intersect with,
		// use the query args post__in if it exists otherwise return an empty array.
		if ( $query_post__in === null ) {
			return $query_args_post__in;
		}

		// If there is no post__in in the query args and query_post__in is set, then use the query_post__in.
		// Don't use `empty()` as we want to know if there is a `0` in the array.
		if ( count( $query_args_post__in ) === 0 ) {
			return $query_post__in;
		}

		$new_post__in = self::array_intersect( $query_args_post__in, $query_post__in );
		// After we intersect the 2 arrays, if there are no results, then there are no possible
		// results to satisfy the query, so return a post ID of `0` to force a no results message.
		if ( count( $new_post__in ) === 0 ) {
			return array( 0 );
		}
		return $new_post__in;
	}

	/**
	 * Derive filtered results from unfiltered results and field post__in.
	 *
	 * This is used when we run only the unfiltered query for performance,
	 * then derive the filtered results via array intersection.
	 *
	 * @since 3.0.7
	 *
	 * @param array      $unfiltered_ids The unfiltered query result IDs.
	 * @param array|null $post__in       The post IDs from field filtering (can be null if no fields applied).
	 * @return array The filtered result IDs.
	 */
	private function derive_filtered_ids_from_unfiltered( $unfiltered_ids, $post__in ) {
		// Edge case 1: No field filters applied ($post__in is null).
		// Filtered results = unfiltered results.
		if ( $post__in === null ) {
			return $unfiltered_ids;
		}

		// Edge case 2: Field filters resulted in no matches ($post__in is [0]).
		// Force no results.
		if ( is_array( $post__in ) && count( $post__in ) === 1 && $post__in[0] === 0 ) {
			return array( 0 );
		}

		// Standard case: Intersect unfiltered results with field post__in.
		$filtered_ids = self::array_intersect( $unfiltered_ids, $post__in );

		// If intersection is empty, force no results.
		if ( empty( $filtered_ids ) ) {
			return array( 0 );
		}

		return $filtered_ids;
	}

	/**
	 * Get the query ID.
	 */
	public function get_id() {
		return $this->query->get_id();
	}

	/**
	 * Get the query.
	 *
	 * @since 3.0.0
	 *
	 * @return \Search_Filter\Queries\Query The query.
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Get the query cache result.
	 *
	 * Uses Tiered_Cache which checks all layers: Memory → APCu → wp_cache → Database.
	 *
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key.
	 * @return array|bool The query cache or false if not found.
	 */
	private function get_query_cache( $cache_key ) {
		$cache = $this->get_cache_instance();

		$found      = false;
		$cached_ids = $cache->get( $cache_key, $found );

		if ( $found ) {
			return $cached_ids;
		}

		return false;
	}

	/**
	 * Get the Tiered_Cache instance for this query.
	 *
	 * @since 3.2.0
	 *
	 * @return Tiered_Cache
	 */
	private function get_cache_instance() {
		$query_id = $this->query->get_id();
		$ttl      = $this->has_search() ? 2 * HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS;

		return new Tiered_Cache(
			'query_cache_' . $query_id,
			array( 'ttl' => $ttl )
		);
	}

	/**
	 * Get the query cache key.
	 */
	public function get_cache_key() {
		return $this->cache_key;
	}

	/**
	 * The cache key query args.
	 */
	public function get_cache_query_args() {
		return $this->cache_query_args;
	}
	/**
	 * The cache key query args.
	 */
	public function get_field_cache_args() {
		return $this->field_cache_args;
	}

	/**
	 * Get the unfiltered cache key.
	 */
	public function get_unfiltered_cache_key() {
		return $this->unfiltered_cache_key;
	}

	/**
	 * Set the query cache result for given query args.
	 *
	 * Uses Tiered_Cache which stores in all layers: Memory → APCu → wp_cache → Database.
	 *
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key.
	 * @param array  $ids        The IDs to cache.
	 */
	private function add_query_cache( $cache_key, $ids ) {
		$cache = $this->get_cache_instance();
		$cache->set( $cache_key, $ids );
	}

	/**
	 * Get the result bitmap (filtered).
	 *
	 * @since 3.0.7
	 * @return Bitmap|null
	 */
	public function get_result_bitmap() {
		return $this->result_bitmap;
	}

	/**
	 * Get the unfiltered result bitmap.
	 *
	 * @since 3.0.7
	 * @return Bitmap|null
	 */
	public function get_unfiltered_result_bitmap() {
		return $this->unfiltered_result_bitmap;
	}

	/**
	 * Get unfiltered result bitmap in collapsed (parent) form.
	 *
	 * Returns the parent-space representation of unfiltered_result_bitmap.
	 * Only set when collapse_children is enabled.
	 *
	 * @since 3.2.0
	 * @return Bitmap|null
	 */
	public function get_unfiltered_result_bitmap_collapsed() {
		return $this->unfiltered_result_bitmap_collapsed;
	}

	/**
	 * Get the query args.
	 *
	 * @since 3.0.0
	 *
	 * @return array The query args.
	 */
	public function get_query_args() {
		return $this->query_args;
	}


	/**
	 * Batch combine multiple bitmaps efficiently.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $bitmaps     Array of Bitmap objects to combine.
	 * @param string $combine_type The combine type (merge or intersect).
	 * @return Bitmap|null The combined bitmap or null if no bitmaps provided.
	 */
	public static function combine_bitmaps( $bitmaps, $combine_type = 'intersect' ) {

		if ( empty( $bitmaps ) ) {
			return null;
		}

		// If only one bitmap, return it directly.
		if ( count( $bitmaps ) === 1 ) {
			return reset( $bitmaps );
		}

		$combined = null;
		foreach ( $bitmaps as $bitmap ) {

			// Skip anything thats not a bitmap.
			if ( ! $bitmap instanceof Bitmap ) {
				continue;
			}

			// Init the first bitmap.
			if ( $combined === null ) {
				$combined = $bitmap;
				continue;
			}

			if ( $combine_type === 'merge' ) {
				if ( $bitmap->is_empty() ) {
					// Skip empty bitmaps for merge.
					continue;
				}
				// Union the bitmaps.
				$combined = $combined->union( $bitmap );

			} elseif ( $combine_type === 'intersect' ) {

				// No need to check for empty bitmaps, intersection already handles it.
				$combined = $combined->intersect( $bitmap );

				// Early exit if intersection becomes empty.
				if ( $combined->is_empty() ) {
					break;
				}
			}
		}

		// If no valid bitmaps were found, return empty (not null).
		if ( $combined === null ) {
			return new Bitmap();
		}

		return $combined;
	}

	/**
	 * Intersect two arrays.
	 *
	 * Slightly faster than the native array_intersect.
	 *
	 * @since 3.0.0
	 *
	 * @param array $array1 The first array.
	 * @param array $array2 The second array.
	 * @return array    The intersection.
	 */
	public static function array_intersect( $array1, $array2 ) {
		$intersection = array();
		$array2       = array_flip( $array2 );
		foreach ( $array1 as $value ) {
			if ( isset( $array2[ $value ] ) ) {
				$intersection[] = $value;
			}
		}
		return $intersection;
	}
	/**
	 * Get the choice field values, apply any transformations necessary for DB queries.
	 *
	 * TODO - this should be handled inside the field class.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Fields\Field $field The field to get the values for.
	 * @return array  The transformed field values.
	 */
	private function get_bitmap_field_values( $field ) {

		$field_values = $field->get_values();

		// We might need to transform the url values to a DB stored format.

		// Author fields use slugs in the URL, but use IDs in the database.
		if ( $field->get_attribute( 'dataType' ) === 'post_attribute' ) {
			$attribute_data_type = $field->get_attribute( 'dataPostAttribute' );
			if ( $attribute_data_type === 'post_author' ) {
				$field_values = Util::get_author_ids_from_slugs( $field_values );
			}
		}
		return $field_values;
	}

	/**
	 * Whether the query has a search term.
	 *
	 * TODO - at the next major version, use the query `has_search` method instead.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether the query has a search term.
	 */
	public function has_search() {

		if ( $this->has_search !== null ) {
			return $this->has_search;
		}

		$fields = $this->query->get_fields();

		$this->has_search = false;
		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			$type = $field->get_attribute( 'type' );

			if ( $type !== 'search' ) {
				continue;
			}
			$values = $field->get_values();
			if ( ! empty( $values ) ) {
				$this->has_search = true;
				break;
			}
		}
		return $this->has_search;
	}

	/**
	 * Gets the field result bitmap.
	 *
	 * @since 3.0.7
	 *
	 * @param int $field_id The field ID.
	 * @return Bitmap|null The field result bitmap.
	 */
	public function get_field_result_bitmap( $field_id ) {
		if ( ! isset( $this->field_result_bitmaps[ $field_id ] ) ) {
			return null;
		}
		return $this->field_result_bitmaps[ $field_id ];
	}

	/**
	 * Get value bitmaps loaded for a field.
	 *
	 * Returns all value bitmaps that were loaded during field query,
	 * enabling data reuse for counting without redundant database queries.
	 *
	 * @since 3.0.7
	 *
	 * @param int $field_id Field ID.
	 * @return array|null Array of value bitmaps or null if not loaded.
	 */
	public function get_field_value_bitmaps( $field_id ) {
		if ( ! isset( $this->field_value_bitmaps[ $field_id ] ) ) {
			return null;
		}
		return $this->field_value_bitmaps[ $field_id ];
	}

	/**
	 * Gets the combined result bitmaps of all the fields excluding the
	 * specified field ID.
	 *
	 * @since 3.0.7
	 *
	 * @param mixed $exclude_field_id The field ID to exclude.
	 * @return Bitmap|null The combined bitmap or null.
	 */
	public function get_combined_result_field_bitmaps_excluding( $exclude_field_id ) {

		if ( empty( $this->field_result_bitmaps ) ) {
			return null;
		}

		if ( ! isset( $this->fields_by_id[ $exclude_field_id ] ) ) {
			return null;
		}

		$field_relationship = $this->query->get_attribute( 'fieldRelationship' );
		// Setup the combine type for the fields.
		$combine_type = '';
		if ( $field_relationship === 'any' ) {
			$combine_type = 'merge';
		} elseif ( $field_relationship === 'all' ) {
			$combine_type = 'intersect';
		}

		// Need to make sure any other fields that accidentally share the same URL
		// var are not included.
		$exclude_url_name = $this->fields_by_id[ $exclude_field_id ]->get_url_name();

		// Batch approach: collect all bitmaps first, then combine once.
		$all_field_result_bitmaps = array();

		foreach ( $this->field_result_bitmaps as $field_id => $field_result_bitmap ) {
			// Make sure we ignore fields with the same url name.
			if ( ! isset( $this->fields_by_id[ $field_id ] ) ) {
				continue;
			}
			$field    = $this->fields_by_id[ $field_id ];
			$url_name = $field->get_url_name();
			if ( ( $field_id !== $exclude_field_id ) && ( $exclude_url_name !== $url_name ) ) {
				if ( $field_result_bitmap !== null ) {
					$all_field_result_bitmaps[] = $field_result_bitmap;
				}
			}
		}

		// Batch combine bitmaps for better performance.
		return self::combine_bitmaps( $all_field_result_bitmaps, $combine_type );
	}

	/**
	 * Apply BM25 relevance ordering to post__in.
	 *
	 * Reorders the post__in array to match the BM25 relevance scores
	 * from search, and sets orderby to 'post__in' to preserve the order.
	 *
	 * @since 3.1.0
	 *
	 * @param array $query_args The query args.
	 * @return array Modified query args with relevance ordering.
	 */
	private function apply_relevance_ordering( $query_args ) {
		if ( empty( $this->search_ordered_ids ) || empty( $query_args['post__in'] ) ) {
			return $query_args;
		}

		// Create a lookup of BM25 positions (lower = higher relevance).
		$position_lookup = array_flip( $this->search_ordered_ids );

		// Get the final post IDs that need to be ordered.
		$final_post_ids = $query_args['post__in'];

		// Sort by BM25 position, keeping posts not in search results at the end.
		usort(
			$final_post_ids,
			function ( $a, $b ) use ( $position_lookup ) {
				$pos_a = isset( $position_lookup[ $a ] ) ? $position_lookup[ $a ] : PHP_INT_MAX;
				$pos_b = isset( $position_lookup[ $b ] ) ? $position_lookup[ $b ] : PHP_INT_MAX;
				return $pos_a - $pos_b;
			}
		);

		$query_args['post__in'] = $final_post_ids;

		// Set orderby to preserve the BM25 relevance order.
		// This tells WP_Query (and our Query_Optimizer) to use FIELD() ordering.
		$query_args['orderby'] = 'post__in';

		return $query_args;
	}
}
