<?php
namespace Search_Filter_Pro\Indexer;

use Search_Filter_Pro\Indexer\Database\Index_Query;
use Search_Filter_Pro\Util;

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
	 * @var Search_Filter\Queries\Query
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
	 * Field result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @var null|array
	 */
	private $field_result_ids = null;

	/**
	 * The result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $result_ids = array();

	/**
	 * The unfilteredresult IDs.
	 *
	 * It will be null if the main query is already unfiltered.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $unfiltered_result_ids = null;

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
	 * Has search term.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private $has_search = false;

	private $field_has_match_any = false;

	private $query_has_match_any = false;

	private $timer = null;

	private $enable_caching = true;

	/**
	 * Construct.
	 *
	 * @since 3.0.0
	 *
	 * @param Search_Filter\Queries\Query $query The S&F query object.
	 */
	public function __construct( $query ) {

		// Disable caching for admins.
		// TODO - we should use S&F roles to handle this.
		if ( current_user_can( 'administrator' ) ) {
			$this->enable_caching = false;
		}

		$this->timer = microtime( true );

		$this->query  = $query;
		$this->fields = $query->get_fields();

		// Start off as `null` so we know if any fields were applied to the query.
		// A null initial state also helps to determine how to initially combine
		// result IDs.
		$post__in           = null;
		$field_relationship = $query->get_attribute( 'fieldRelationship' );

		// Setup the combine type for the fields.
		$combine_type = '';
		if ( $field_relationship === 'any' ) {
			$combine_type              = 'merge';
			$this->query_has_match_any = true;
		} elseif ( $field_relationship === 'all' ) {
			$combine_type = 'intersect';
		}

		$field_cache_args = array();
		foreach ( $this->fields as $field ) {

			$this->fields_by_id[ $field->get_id() ] = $field;

			if ( count( $field->get_values() ) === 0 ) {
				continue;
			}

			$field_cache_args[ $field->get_id() ] = $field->get_values();

			$field_post_ids = $this->field_query( $field );

			if ( $field_post_ids !== null ) {
				$post__in = self::combine_arrays( $post__in, $field_post_ids, $combine_type );
			}
		}
		if ( is_array( $post__in ) && empty( $post__in ) ) {
			// Add a post ID of 0 to force the query to return no results.
			$post__in = array( 0 );
		}

		// Get the query args from the query settings.
		$query_args = $query->apply_wp_query_args( array() );

		// Apply the query args for fields that are not handled by the indexer.
		$query_args = $this->apply_fields_wp_query_args( $query_args, $query );

		// Apply any user defined query args.
		$query_args = apply_filters( 'search-filter/query/query_args', $query_args, $query );

		// Use the query args for the cache key (we don't need all the extra args to identify
		// the query as it will remain the same each time.
		$this->cache_query_args = $query_args;
		$cache_key              = $this->create_cache_key( $query_args, $field_cache_args );
		$this->cache_key        = $cache_key;
		$this->field_cache_args = $field_cache_args;

		// Create the query args for the full extended query.
		$full_query_args = $this->create_full_query_args( $query_args, $post__in );

		$result_ids = $this->run_cached_query( $cache_key, $full_query_args );
		$this->set_result_ids( $result_ids );

		// Initially set the unfiltered result IDs to the same as the filtered result IDs,
		// we'll check if anything was filtered and run an additional query later if needed.
		$unfiltered_result_ids = $result_ids;

		/*
		 * To get accurate counts, we need the query IDs without other filters applied when
		 * the field relationship is set to `any`.
		 *
		 * Fortunately this should already be cached, as it would represent the query when first
		 * visiting a page and no filters are applied yet.
		 *
		 * TODO: we need to make sure that the query args would match the query args when visiting
		 * the page for the first time, so we can get a successful cache hit.
		 *
		 * ** If any field has a match of `any` or the query does, then we need the unfiltered query
		 * to build counts for the other options in the field.
		 */
		if ( ( $this->query_has_match_any || $this->field_has_match_any ) && ! empty( $field_cache_args ) ) {
			// Unfiltered IDs are required for accurate counts when using `any` relationship.
			$unfiltered_cache_key       = $this->create_cache_key( $query_args, array() );
			$this->unfiltered_cache_key = $unfiltered_cache_key;
			// Generate the query args for the unfiltered query.
			$unfiltered_full_query_args = $this->create_full_query_args( $query_args, $post__in, false );
			// Try to get the results from the cache, if not generate them.
			$unfiltered_result_ids = $this->run_cached_query( $unfiltered_cache_key, $unfiltered_full_query_args );

		}

		// Update the unfiltered result IDs.
		$this->set_unfiltered_result_ids( $unfiltered_result_ids );

		$this->query_args             = $query_args;
		$this->query_args['post__in'] = $this->get_updated_post__in( $query_args, $post__in );

		$end_time = microtime( true );
		$is_rest  = defined( 'REST_REQUEST' ) && REST_REQUEST;
		if ( ! $is_rest && ! wp_doing_ajax() && ! is_admin() ) {
			// echo 'Time for unfiltered query: ' . $query->get_id() . ': ' . ( $end_time - $this->timer ) . "<br />\r\n";
		}
	}

	/**
	 * Run the cached query, if its not enabled, then run the full query and cache it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key to use.
	 * @param array  $full_query_args The full query args.
	 * @return array The result IDs.
	 */
	private function run_cached_query( $cache_key, $full_query_args ) {

		$result_ids = false;

		if ( $this->enable_caching ) {
			// Try to get the cached IDs.
			$result_ids = $this->get_query_cache( $cache_key );
		}

		// If not cached, then run the query.
		if ( ! $result_ids ) {
			$full_query = new \WP_Query( $full_query_args );

			if ( $this->enable_caching ) {
				$this->add_query_cache( $cache_key, $full_query->posts );
			}

			$result_ids = $full_query->posts;
		}

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

		return $cache_key;
	}

	/**
	 * Create the full query args.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args.
	 * @param array $result__post_in The result post in.
	 * @param bool  $filtered Whether the query is filtered.
	 * @return array The full query args.
	 */
	private function create_full_query_args( $query_args, $result__post_in, $filtered = true ) {

		if ( $filtered ) {
			// Then update the post__in, and combine it with an existing post__in if its there.
			$post__in = $result__post_in;

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
			// 'lang'             => '',
			// 'cache_results'    => false,
		);

		$full_query_args = wp_parse_args( $extend_query_args, $query_args );

		return $full_query_args;
	}

	/**
	 * Get the updated post__in.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args.
	 * @param array $query_post__in The query post in.
	 * @return array The updated post in.
	 */
	private static function get_updated_post__in( $query_args, $query_post__in ) {
		$new_post__in = array();
		if ( isset( $query_args['post__in'] ) && count( $query_args['post__in'] ) > 0 ) {
			if ( empty( $query_post__in ) ) {
				$new_post__in = $query_args['post__in'];
			} else {
				$new_post__in = self::array_intersect( $query_args['post__in'], $query_post__in );
			}
		} else {
			$new_post__in = $query_post__in;
		}
		return $new_post__in;
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
	 * @return Search_Filter\Queries\Query The query.
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Get the query cache result.
	 *
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key.
	 * @return array|bool The query cache or false if not found.
	 */
	private function get_query_cache( $cache_key ) {
		$query_id = $this->query->get_id();

		$value = Query_Cache::get_value(
			array(
				'query_id'  => $query_id,
				'field_id'  => 0,
				'type'      => 'query',
				'cache_key' => $cache_key,
			)
		);

		if ( $value === false || $value === null ) {
			return false;
		}

		return explode( ',', $value );
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
	 * @since 3.0.0
	 *
	 * @param string $cache_key The cache key.
	 * @param array  $ids        The IDs to cache.
	 */
	private function add_query_cache( $cache_key, $ids ) {
		$query_id = $this->query->get_id();
		$data     = array(
			'query_id'    => $query_id,
			'field_id'    => 0,
			'type'        => 'query',
			'cache_key'   => $cache_key,
			'cache_value' => implode( ',', $ids ),
		);

		if ( $this->has_search() ) {
			$data['expires'] = time() + HOUR_IN_SECONDS;
		}
		Query_Cache::update_item( $data );
	}

	/**
	 * Get the result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @return array The result IDs.
	 */
	public function get_result_ids() {
		return $this->result_ids;
	}
	/**
	 * Get the count result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @return array The result IDs.
	 */
	public function get_unfiltered_result_ids() {
		return $this->unfiltered_result_ids;
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
	 * Set the result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param array $result_ids The result IDs.
	 */
	public function set_result_ids( $result_ids ) {
		$this->result_ids = $result_ids;
	}
	/**
	 * Set the result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param array $result_ids The result IDs.
	 */
	public function set_unfiltered_result_ids( $result_ids ) {
		$this->unfiltered_result_ids = $result_ids;
	}

	/**
	 * Run the individual field query.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to run the query for.
	 * @return array|null  The result IDs or null if there was no field query.
	 */
	public function field_query( $field ) {
		$field_type = $field->get_attribute( 'type' );
		if ( $field_type === 'search' ) {
			return $this->search( $field );
		} elseif ( $field_type === 'choice' ) {
			return $this->choice( $field );
		} elseif ( $field_type === 'range' ) {
			return $this->range( $field );
		} elseif ( $field_type === 'advanced' ) {
			return $this->advanced( $field );
		}
		// Return null so we know there was no field query.
		return null;
	}

	/**
	 * Run the choice field query.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to run the query for.
	 * @return array  The result IDs.
	 */
	public function search( $field ) {

		// For now, lets not support using the indexer for searching by default, we can enable it
		// on a case by case basis if needed.
		$should_enable = apply_filters( 'search-filter-pro/indexer/query/search/should_enable', false, $field );
		if ( ! $should_enable ) {
			return null;
		}

		$field_value = $field->get_value();
		$query_id    = $field->get_query_id();
		$field_id    = $field->get_id();

		// $this->init_field_values_ids( $query_id, $field_id, array( $field_value ) );

		if ( empty( $field_value ) ) {
			return array();
		}

		$field_post_ids = array();

		global $wpdb;
		$table_name = $wpdb->prefix . 'search_filter_index';
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT object_id FROM %i WHERE field_id = %d AND value LIKE %s', $table_name, $field_id, '%' . $field_value . '%' ) );

		if ( $results === null ) {
			return array();
		}

		foreach ( $results as $result_item ) {
			$field_post_ids[] = $result_item->object_id;
		}

		$this->field_result_ids[ $field_id ] = $field_post_ids;

		return $field_post_ids;
	}
	/**
	 * Run the choice field query.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to run the query for.
	 * @return array  The result IDs.
	 */
	public function choice( $field ) {

		$field_values = $field->get_values();
		$query_id     = $field->get_query_id();
		$field_id     = $field->get_id();

		$this->init_field_values_ids( $query_id, $field_id, $field_values );

		/*
		 * It's important to set a default match mode of "any" if the field doesn't have one set.
		 * Fields that don't have a match mode, will only be able to have 1 value assigned to a post
		 * such as post type, or author.
		 *
		 * This means they will default to "any" match mode.
		 */
		$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
		$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

		if ( $multiple_match_method === 'any' ) {
			$this->field_has_match_any = true;
		}

		if ( empty( $field_values ) ) {
			return array();
		}

		$field_values = $this->get_choice_field_values( $field );

		$field_post_ids = null; // Start off as null so we know if its the first combination.

		if ( empty( $field_values ) ) {
			return $field_post_ids;
		}

		foreach ( $field_values as $field_value ) {
			$ids = $this->get_field_value_ids( $query_id, $field_id, $field_value );

			$combine_type = '';
			if ( $multiple_match_method === 'any' ) {
				$combine_type = 'merge';
			} elseif ( $multiple_match_method === 'all' ) {
				$combine_type = 'intersect';
			}
			$field_post_ids = self::combine_arrays( $field_post_ids, $ids, $combine_type );
		}

		$this->field_result_ids[ $field_id ] = $field_post_ids;

		return $field_post_ids;
	}

	/**
	 * Combine two arrays based on the operator.
	 *
	 * @param null|array $source_array      The source array.
	 * @param array      $add_array         The array to add to the source array.
	 * @param string     $combine_type              The combine type (merge or intersect).
	 * @return mixed
	 */
	private static function combine_arrays( $source_array, $add_array, $combine_type = 'intersect' ) {
		$combined_array = array();
		// If its the source array is null then return the add array.
		if ( $source_array === null ) {
			return $add_array;
		}
		if ( $combine_type === 'intersect' ) {
			$combined_array = self::array_intersect( $source_array, $add_array );
		} elseif ( $combine_type === 'merge' ) {
			$combined_array = array_merge( $source_array, $add_array );
		}
		return $combined_array;
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
	 * @param Field $field The field to get the values for.
	 * @return array  The transformed field values.
	 */
	private function get_choice_field_values( $field ) {
		$field_values = $field->get_values();
		// We might need to transform the url values to a DB stored format.

		// Author fields use slugs in the URL, but IDs in the database.
		if ( $field->get_attribute( 'dataType' ) === 'post_attribute' ) {
			$attribute_data_type = $field->get_attribute( 'dataPostAttribute' );
			if ( $attribute_data_type === 'post_author' ) {
				$field_values = Util::get_author_ids_from_slugs( $field_values );
			}
		}
		return $field_values;
	}

	/**
	 * Run the range field query.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to run the query for.
	 * @return array  The result IDs.
	 */
	public function range( $field ) {
		$field_values = $field->get_values();

		if ( empty( $field_values ) ) {
			// Return null so we know there was no range query.
			return null;
		}

		$field_id = $field->get_id();

		$value_conditions = array(
			'relation' => 'AND',
		);
		if ( isset( $field_values[0] ) && $field_values[0] !== '' ) {
			$value_conditions[] = array(
				'compare'  => '>=',
				'value'    => $field_values[0],
				'decimals' => absint( $field->get_attribute( 'rangeDecimalPlaces' ) ),
			);
		}
		if ( isset( $field_values[1] ) && $field_values[1] !== '' ) {
			$value_conditions[] = array(
				'compare'  => '<=',
				'value'    => $field_values[1],
				'decimals' => absint( $field->get_attribute( 'rangeDecimalPlaces' ) ),
			);
		}

		$args  = array(
			'fields'      => 'object_id',
			'groupby'     => 'object_id',
			'number'      => 0,
			'field_id'    => $field_id,
			'value_query' => $value_conditions,
		);
		$query = new Index_Query( $args );

		$this->field_result_ids[ $field_id ] = $query->items;

		return $this->field_result_ids[ $field_id ];
	}
	/**
	 * Run the advanced field query.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to run the query for.
	 * @return array  The result IDs.
	 */
	public function advanced( $field ) {
		$field_values = $field->get_values();

		if ( empty( $field_values ) ) {
			// Return null so we know there was no advanced query.
			return null;
		}

		$field_id = $field->get_id();

		$field_input_type = $field->get_attribute( 'inputType' );

		$value_conditions = array(
			'relation' => 'AND',
		);

		if ( $field_input_type === 'date_picker' ) {

			$field_values = array_map(
				function( $value ) {
					return str_replace( '-', '', $value );
				},
				$field_values
			);

			if ( count( $field_values ) === 2 ) {
				if ( isset( $field_values[0] ) && $field_values[0] !== '' ) {
					$value_conditions[] = array(
						'compare' => '>=',
						'value'   => $field_values[0],
					);
				}
				if ( isset( $field_values[1] ) && $field_values[1] !== '' ) {
					$value_conditions[] = array(
						'compare' => '<=',
						'value'   => $field_values[1],
					);
				}
			}

			// TODO need to properly check if we're dealing with a range or single value.
			if ( count( $field_values ) === 1 ) {
				$value_conditions[] = array(
					'compare' => '=',
					'value'   => $field_values[0],
				);
			}
		}

		$args  = array(
			'fields'      => 'object_id',
			'groupby'     => 'object_id',
			'number'      => 0,
			'field_id'    => $field_id,
			'value_query' => $value_conditions,
		);
		$query = new Index_Query( $args );

		$this->field_result_ids[ $field_id ] = $query->items;

		return $this->field_result_ids[ $field_id ];
	}

	/**
	 * Get the result IDs for a field value.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $field_id    The field ID.
	 * @param mixed $field_value The field value.
	 * @return array  The IDs.
	 */
	private function get_field_value_ids( $query_id, $field_id, $field_value ) {

		$value = Query_Cache::get_value(
			array(
				'query_id'  => $query_id,
				'field_id'  => $field_id,
				'type'      => 'query',
				'cache_key' => $field_value,
			)
		);

		if ( $value === false ) {
			return array();
		}

		if ( $value === null ) {
			// There is no item in the DB, so we need to try to build it.
			$query = new Index_Query(
				array(
					'field_id' => $field_id,
					'value'    => $field_value,
					'number'   => 0,
					'fields'   => 'object_id',
				)
			);

			if ( is_wp_error( $query ) ) {
				return array();
			}

			$value = implode( ',', $query->items );

			Query_Cache::update_item(
				array(
					'query_id'    => $query_id,
					'field_id'    => $field_id,
					'type'        => 'query',
					'cache_key'   => $field_value,
					'cache_value' => $value,
				)
			);
		}

		return explode( ',', $value );
	}

	/**
	 * Perform a DB query to get the field values IDs for a field.
	 *
	 * Doesn't need to return anything, just update the Query_Cache local
	 * cache so we have the results ready for the individual calls.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $query_id    The query ID.
	 * @param int   $field_id    The field ID.
	 * @param array $field_values The field values.
	 */
	private function init_field_values_ids( $query_id, $field_id, $field_values ) {
		Query_Cache::get_items(
			array(
				'query_id'      => $query_id,
				'field_id'      => $field_id,
				'type'          => 'query',
				'cache_key__in' => $field_values,
			)
		);
	}
	/**
	 * Apply the field WP_Query args to the WP_Query args.
	 *
	 * @since 3.0.0
	 *
	 * @param array                       $query_args  The query args to get the field WP_Query args for.
	 * @param Search_Filter\Queries\Query $query       The query to get the field WP_Query args for.
	 * @return array    The field WP_Query args.
	 */
	private function apply_fields_wp_query_args( $query_args, $query ) {
		$fields = $query->get_fields();

		foreach ( $fields as $field ) {
			$type = $field->get_attribute( 'type' );

			if ( $type === 'search' ) {
				$values = $field->get_values();
				if ( ! empty( $values ) ) {
					$this->has_search = true;
				}
				$query_args = $field->apply_wp_query_args( $query_args );
			} elseif ( $type === 'control' ) {
				// Most controls do not apply query args, but some do.
				$query_args = $field->apply_wp_query_args( $query_args );
			}
		}
		return $query_args;
	}

	/**
	 * Gets the field result IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param int $field_id The field ID.
	 * @return array The field result IDs.
	 */
	public function get_field_result_ids( $field_id ) {
		if ( ! isset( $this->field_result_ids[ $field_id ] ) ) {
			return null;
		}
		return $this->field_result_ids[ $field_id ];
	}

	/**
	 * Gets the combined result IDs of all the fields excluding the
	 * specified ID.
	 *
	 * Requried to calculate the counts of fields that are using match
	 * mode `any`.
	 *
	 * @param mixed $exclude_field_id
	 * @return mixed
	 */
	public function get_combined_result_field_ids_excluding( $exclude_field_id ) {

		if ( ! is_array( $this->field_result_ids ) ) {
			return null;
		}

		if ( ! isset( $this->fields_by_id[ $exclude_field_id ] ) ) {
			return null;
		}

		$combined_result_ids = null;

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

		foreach ( $this->field_result_ids as $field_id => $field_result_ids ) {
			// Make sure we ignore fields with the same url name.
			if ( ! isset( $this->fields_by_id[ $field_id ] ) ) {
				continue;
			}
			$field    = $this->fields_by_id[ $field_id ];
			$url_name = $field->get_url_name();
			if ( ( $field_id !== $exclude_field_id ) && ( $exclude_url_name !== $url_name ) ) {
				if ( $field_result_ids !== null ) {
					$combined_result_ids = self::combine_arrays( $combined_result_ids, $field_result_ids, $combine_type );
				}
			}
		}
		return $combined_result_ids;
	}

	/**
	 * Has search term.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if the query has a search term.
	 */
	public function has_search() {
		return $this->has_search;
	}
}
