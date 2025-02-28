<?php
namespace Search_Filter_Pro\Indexer;

use Search_Filter\Core\Data_Store;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Database\Index_Query;

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
	 * Enable caching for the queries.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private static $enable_caching = true;


	/**
	 * Temporary timers for the queries.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $timers = array();


	/**
	 * Init the field queries.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Disable caching for admins.
		// TODO - we should use S&F roles to handle this.
		if ( current_user_can( 'administrator' ) ) {
			self::$enable_caching = false;
		}

		add_action( 'search-filter/fields/filter/choice/create_options/start', array( __CLASS__, 'start_create_options' ), 10, 2 );
		// Filter field options.
		add_filter( 'search-filter/field/choice/option', array( __CLASS__, 'filter_field_option' ), 0, 2 );
	}

	/**
	 * Fetch all data for the field options when the field options
	 * are starting to be created.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to start creating options for.
	 */
	public static function start_create_options( $field ) {

		self::$timers[ $field->get_id() ] = microtime( true );

		$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
		$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

		$field_id = $field->get_id();

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

			// TODO - this is inefficient, we need to store the queries
			// in an object and reuse them.
			$query = Query::find( array( 'id' => $query_id ) );
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

		if ( empty( self::$fields[ $field_id ]['options'] ) ) {

			if ( $indexer_query === null & $query_id === 0 ) {
				return;
			}

			// Get the relationship value from the source query.
			$source_query       = $indexer_query->get_query();
			$field_relationship = $source_query->get_attribute( 'fieldRelationship' );

			$cache_key = self::get_field_cache_key( $field, $indexer_query, $field_relationship, $multiple_match_method );

			$count_items = null;

			if ( self::$enable_caching ) {
				$count_items = Query_Cache::get_value(
					array(
						'query_id'  => $query_id,
						'field_id'  => $field_id,
						'cache_key' => $cache_key,
						'type'      => 'count',
					)
				);
			}

			// There was an error.
			if ( $count_items === false ) {
				return;
			}

			if ( $count_items === null ) {
				// There is no cached item in the DB so build it and store it.
				$filtered_result_ids   = $indexer_query->get_result_ids();
				$unfiltered_result_ids = $indexer_query->get_unfiltered_result_ids();

				// Figure out which IDs we need to compare against.
				$result_ids = self::get_field_value_matched_ids( $field, $filtered_result_ids, $unfiltered_result_ids, $field_relationship, $indexer_query );

				$result_ids_string = implode( ',', array_map( 'absint', $result_ids ) );
				$field_id          = absint( $field_id );

				// Whether to collapse children into parents when counting, this
				// means a count for a child object is the same as its parent.
				$collapse_children_into_parents = apply_filters( 'search-filter-pro/indexer/query/collapse_children', false, $indexer_query->get_query() );
				$count_select_sql               = 'COUNT(object_id)';
				if ( $collapse_children_into_parents ) {
					$count_select_sql = 'COUNT(DISTINCT CASE 
						WHEN object_parent_id = 0 THEN object_id 
						ELSE object_parent_id 
					END)';
				}

				if ( empty( $result_ids_string ) ) {
					// Then we have no results to combine with, use ID of 0 to force
					// no matches.
					$result_ids_string = 0;
				}

				global $wpdb;
				$table_name = $wpdb->prefix . 'search_filter_index';
				$sql        = $wpdb->prepare(
					"SELECT value, 
						$count_select_sql as count
					FROM %i 
					WHERE field_id = %d 
					AND object_id IN ($result_ids_string) 
					GROUP BY value",
					$table_name,
					$field_id
				);

				$count_items = $wpdb->get_results( $sql, ARRAY_A );

				if ( self::$enable_caching ) {

					$item_data = array(
						'query_id'    => $query_id,
						'field_id'    => $field_id,
						'type'        => 'count',
						'cache_key'   => $cache_key,
						'cache_value' => wp_json_encode( $count_items ),
					);

					// If there is a search term, don't hold on to the cache for
					// too long to prevent huge tables.
					if ( $indexer_query->has_search() ) {
						$item_data['expires'] = time() + HOUR_IN_SECONDS;
					}

					Query_Cache::update_item( $item_data );
				}
			} else {
				$count_items = json_decode( $count_items, true );
			}

			if ( $count_items ) {
				foreach ( $count_items as $item ) {
					self::$fields[ $field_id ]['counts'][ $item['value'] ] = absint( $item['count'] );
				}
			}
		}
	}

	/**
	 * Get cache key for the field.
	 *
	 * Based on the field relationship and match mode so we can
	 * reuse the cache key where possible.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field         $field    The field to get the cache key for.
	 * @param    Indexer_Query $indexer_query    The indexer query.
	 * @param    string        $field_relationship    The field relationship.
	 * @param    string        $multiple_match_method    The multiple match method.
	 * @return   string    The cache key.
	 */
	private static function get_field_cache_key( $field, $indexer_query, $field_relationship, $multiple_match_method ) {

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
	 * Get the field value matched IDs.
	 *
	 * Figures out which IDs to query against for generating counts based
	 * on the field relationship setting and match mode.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field         $field    The field to get the IDs for.
	 * @param    array         $filtered_result_ids    The filtered result IDs.
	 * @param    array         $unfiltered_result_ids    The unfiltered result IDs.
	 * @param    string        $field_relationship    The field relationship.
	 * @param    Indexer_Query $indexer_query    The indexer query.
	 * @return   array    The field value matched IDs.
	 */
	private static function get_field_value_matched_ids( $field, $filtered_result_ids, $unfiltered_result_ids, $field_relationship, $indexer_query ) {

		$has_multiple_match_method = $field->get_attribute( 'multipleMatchMethod' ) !== '' && $field->get_attribute( 'multipleMatchMethod' ) !== null;
		$multiple_match_method     = $has_multiple_match_method ? $field->get_attribute( 'multipleMatchMethod' ) : 'any';

		if ( $field_relationship === 'all' ) {

			// Field relationship is set to 'all', match mode 'all'.
			if ( $multiple_match_method === 'all' ) {
				return $filtered_result_ids;
			}

			// Field relationship is set to 'all', match mode 'any'.

			// If we require any match, then we need to get the IDs of all the other fields
			// combined and intersect that with the unfiltered result IDs.
			$combined_result_ids = $indexer_query->get_combined_result_field_ids_excluding( $field->get_id() );

			if ( $combined_result_ids !== null ) {
				$combined_result_ids = Indexer_Query::array_intersect( $combined_result_ids, $unfiltered_result_ids );
			} else {
				// If combined IDs is null then there were no fields applied to the query other than
				// possibly the current field.
				$combined_result_ids = $unfiltered_result_ids;
			}

			return $combined_result_ids;
		}

		// Field relationship is set to 'any', match mode 'any'.
		if ( $multiple_match_method === 'any' ) {
			// Then we only need the unfiltered result IDs to compare against.
			return $unfiltered_result_ids;
		}

		// Field relationship is set to 'any', match mode 'all'.

		// We can get this from the indexer query as the combine has already been done.
		$field_result_ids = $indexer_query->get_field_result_ids( $field->get_id() );
		// Combine with the unfiltered result IDs if its set, will be null if there are no active values.
		$result_ids = $unfiltered_result_ids;
		if ( $field_result_ids !== null ) {
			$result_ids = Indexer_Query::array_intersect( $field_result_ids, $unfiltered_result_ids );
		}
		return $result_ids;
	}

	/**
	 * Format the count.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $count The count number.
	 * @param bool $show_brackets Whether to show brackets.
	 * @return string The formatted count.
	 */
	private static function format_count( $count, $show_brackets = true ) {
		if ( $show_brackets ) {
			return '(' . absint( $count ) . ')';
		}
		return absint( $count );
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
		// Check if we need to show/hide options based on the field setting
		// and add counts from the indexer.
		if ( ! isset( $option['value'] ) || empty( $option['value'] ) ) {
			return $option;
		}
		if ( ! isset( self::$fields[ $field_id ] ) ) {
			return $option;
		}
		$hide_empty          = self::$fields[ $field_id ]['hideEmpty'];
		$show_count          = self::$fields[ $field_id ]['showCount'];
		$show_count_brackets = self::$fields[ $field_id ]['showCountBrackets'];
		$query_id            = self::$fields[ $field_id ]['queryId'];

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
	 * @param int   $field_id The field ID to check.
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
