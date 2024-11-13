<?php
/**
 * Figures out, based on saved S&F Queries, which WP Queries to affect, by assigning `sf_query_id` to the
 * appropriate WP Queries
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Query\Handler;

use Search_Filter\Queries;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Figures out which query to "Select" or attach to
 */
class Wp {

	/**
	 * Stores a local copy of the WP_Query object.
	 *
	 * @var WP_Query
	 */
	private $wp_query;

	/**
	 * Constructor.
	 *
	 * @param WP_Query $query The instantiated WP_Query object.
	 */
	public function __construct( $query ) {
		$this->wp_query = $query;
	}

	/**
	 * Using the query object, build the WP_Query args.
	 */
	public function get_query_args() {
		$query_args = array();
		// Apply the queries.
		if ( ! empty( $this->wp_query->get( 'search_filter_queries' ) ) ) {
			// TODO - add an option to define if we want to inherit the query or not.
			$query_args = array();
			// The return the fields for the query.
			$queries = $this->wp_query->get( 'search_filter_queries' );

			// Track the IDs of the queries so we can log an error if there is more than one.
			$attached_query_ids = array();
			foreach ( $queries as $query ) {

				Queries::register_active_query( $query->get_id() );

				$is_wp_query = apply_filters( 'search-filter/query/is_wp_query', true, $query );

				if ( $is_wp_query === false ) {
					continue;
				}

				$query_args           = $query->apply_wp_query_args( $query_args );
				$query_args           = $this->apply_field_wp_query_args( $query_args, $query->get_fields() );
				$attached_query_ids[] = $query->get_id();

				// Add filter to customise the query args further.

				// The naming of this hook does not match convention - but, its used in multiple places
				// the indexer and here so it probably shouldn't belong to the single query class, ie
				// `search-filter/queries/query...` it should be an exception.
				$query_args = apply_filters( 'search-filter/query/query_args', $query_args, $query );
			}

			if ( count( $attached_query_ids ) > 1 ) {
				Util::error_log( 'Detected possible conflicting queries: ' . implode( ', ', $attached_query_ids ) );
			}
		}
		return $query_args;
	}

	/**
	 * Loop through the fields and add their query args.
	 *
	 * @param array $query_args The query args.
	 * @param array $fields     The fields to apply the query args to.
	 *
	 * @return array The updated query args.
	 */
	public function apply_field_wp_query_args( $query_args, $fields ) {
		foreach ( $fields as $field ) {
			$query_args = $field->apply_wp_query_args( $query_args );
		}
		return $query_args;
	}

	/**
	 * Apply field WHERE clauses to the query.
	 *
	 * TODO - we could build the WHERE clauses in the wp_query args loop above
	 * so we're not re-running the same logic - then store in a local variable
	 *
	 * @param string $where The WHERE clauses.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public function apply_query_posts_where( $where ) {
		// Apply the queries' posts_where clauses.
		if ( ! empty( $this->wp_query->get( 'search_filter_queries' ) ) ) {
			// TODO - add an option to define if we want to inherit the query or not.
			$query_args = array();
			// $query_args = $this->wp_query->query_vars;

			// The return the fields for the query.
			$queries = $this->wp_query->get( 'search_filter_queries' );
			// TODO - throw an error if there is more thank one query?
			foreach ( $queries as $query ) {
				$where = $this->apply_field_wp_query_posts_where( $where, $query->get_fields() );
				// Add filter to customise the query args further.
				// TODO - the naming of this does not match convention.
				$where = apply_filters( 'search-filter/query/posts_where', $where, $query );
			}
		}

		return $where;
	}

	/**
	 * Apply the query posts_join clauses.
	 *
	 * @since 3.0.0
	 *
	 * @param string $join The JOIN clauses.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public function apply_query_posts_join( $join ) {
		// Apply the queries' posts_where clauses.
		if ( ! empty( $this->wp_query->get( 'search_filter_queries' ) ) ) {
			// The return the fields for the query.
			$queries = $this->wp_query->get( 'search_filter_queries' );
			// TODO - throw an error if there is more thank one query?
			foreach ( $queries as $query ) {
				$join = $this->apply_field_wp_query_posts_join( $join, $query->get_fields() );
				// Add filter to customise the query args further.
				// TODO - the naming of this does not match convention.
				$join = apply_filters( 'search-filter/query/posts_where', $join, $query );
			}
		}

		return $join;
	}
	/**
	 * Loop through the fields and apply their WHERE clauses.
	 *
	 * @param string $where The WHERE clauses.
	 * @param array  $fields The fields to apply the WHERE clauses to.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public function apply_field_wp_query_posts_where( $where, $fields ) {
		foreach ( $fields as $field ) {
			$where = $field->apply_wp_query_posts_where( $where );
		}
		return $where;
	}

	/**
	 * Loop through the fields and apply their JOIN clauses.
	 *
	 * @since 3.0.0
	 *
	 * @param string $join The JOIN clauses.
	 * @param array  $fields The fields to apply the JOIN clauses to.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public function apply_field_wp_query_posts_join( $join, $fields ) {
		foreach ( $fields as $field ) {
			$join = $field->apply_wp_query_posts_join( $join );
		}
		return $join;
	}
}
