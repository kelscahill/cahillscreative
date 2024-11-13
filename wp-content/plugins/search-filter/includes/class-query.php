<?php
/**
 * Looks for `search_filter_queries` in a WP_Query (pre_get_posts), and takes over the query
 * parses url args + query settings into queries to made on our own tables
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main class for attaching S&F queries on WP queries.
 */
class Query {
	/**
	 * Query Type
	 *
	 * Which query engine to use. Defaults to WP.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private static $query_type = 'wp';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		if ( self::$query_type === 'wp' ) {
			// Try to be the last thing to attach to the hook.
			add_action( 'pre_get_posts', array( __CLASS__, 'setup_wp_query' ), 100, 1 );
			// add_action( 'posts_where', array( __CLASS__, 'setup_wp_query_posts_where' ), 100, 2 );
			// add_action( 'posts_join', array( __CLASS__, 'setup_wp_query_posts_join' ), 100, 2 );
			add_action( 'loop_start', array( __CLASS__, 'track_query_data' ), 10 );
		}
	}

	/**
	 * Setup the queries with their args.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 */
	public static function setup_wp_query( $query ) {
		if ( ! self::is_frontend_query() ) {
			return;
		}

		$query_handler = new \Search_Filter\Query\Handler\Wp( $query );
		$query_args    = $query_handler->get_query_args();
		// Now try to update the query from the provided args.
		foreach ( $query_args as $key => $value ) {
			$query->set( $key, $value );
		}
	}

	/**
	 * Track the query data for WP_Query instance.
	 *
	 * @param \WP_Query $wp_query The WP_Query instance.
	 */
	public static function track_query_data( $wp_query ) {
		if ( ! self::is_frontend_query() ) {
			return;
		}

		$queries = $wp_query->get( 'search_filter_queries' );
		if ( empty( $queries ) ) {
			return;
		}
		// Now we have found a S&F query, lets get its data.
		foreach ( $queries as $query ) {
			// Setup query render data.
			$page_key = $query->get_render_config_value( 'paginationKey' );
			$query->set_render_config_value( 'currentPage', $wp_query->get( 'paged' ) );

			$page = 1;
			// The query block currently uses offset to control which "page" to show, so we
			// can't use the `paged` variable inside the query.
			// Other queries might try the same, so lets try to get the paged variable by various.

			// First, lets check if the query is paged, if so lets use the variable.
			if ( $wp_query->is_paged() ) {
				$page = $wp_query->get( 'paged' );
			} elseif ( ! empty( $page_key ) && isset( $_GET[ $page_key ] ) ) {
				$page = (int) $_GET[ $page_key ];
			} else {
				$offset         = $wp_query->get( 'offset' );
				$posts_per_page = $wp_query->get( 'posts_per_page' );
				if ( ! empty( $offset ) && ! empty( $posts_per_page ) ) {
					// We can try to estimate the page based on offset + posts_per_page,
					// but make sure we have more than 1 page worth of total results.
					$page = (int) ( $offset / $posts_per_page ) + 1;
				}
			}

			$query->set_render_config_value( 'currentPage', $page );
			$query->set_render_config_value( 'maxPages', $wp_query->max_num_pages );
			$query->set_render_config_value( 'postsPerPage', $wp_query->get( 'posts_per_page' ) );
			$query->set_render_config_value( 'foundPosts', $wp_query->found_posts );
		}
	}
	/**
	 * Setup the custom WHERE clauses for WP_Query query.
	 *
	 * @param string    $where The WHERE clauses.
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public static function setup_wp_query_posts_where( $where, $query ) {
		if ( ! self::is_frontend_query() ) {
			return;
		}
		$query_handler = new \Search_Filter\Query\Handler\Wp( $query );
		$where         = $query_handler->apply_query_posts_where( $where );
		return $where;
	}
	/**
	 * Setup the custom JOIN clauses for WP_Query query.
	 *
	 * @param string    $join The JOIN clauses.
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public static function setup_wp_query_posts_join( $join, $query ) {
		if ( ! self::is_frontend_query() ) {
			return;
		}
		$query_handler = new \Search_Filter\Query\Handler\Wp( $query );
		$join          = $query_handler->apply_query_posts_join( $join );
		return $join;
	}

	/**
	 * Check if we're on a frontend query.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public static function is_frontend_query() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return true;
		}
		return false;
	}
}
