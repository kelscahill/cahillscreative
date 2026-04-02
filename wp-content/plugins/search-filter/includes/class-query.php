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

use Search_Filter\Queries\Query as Queries_Query;

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
	 * The tracked WP_Query data.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $tracked_queries = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		if ( self::$query_type === 'wp' ) {
			// Initially attach the hooks.
			self::attach_pre_get_posts_hooks();
			// Allow them to be attached/detached via an action.
			add_action( 'search-filter/query/pre_get_posts/attach', array( __CLASS__, 'attach_pre_get_posts_hooks' ), 10 );
			add_action( 'search-filter/query/pre_get_posts/detach', array( __CLASS__, 'detach_pre_get_posts_hooks' ), 10 );

			// Track which WP_Query is attached to which S&F query.
			add_action( 'search-filter/query/attach_wp_query', array( __CLASS__, 'track_query_data' ), 10, 2 );

			// Output query specific render settings for use in the frontend.
			add_filter( 'search-filter/queries/query/get_render_settings', array( __CLASS__, 'get_render_settings' ), 10, 2 );

			// Adjust found_posts to account for offset setting.
			add_filter( 'found_posts', array( __CLASS__, 'adjust_found_posts_for_offset' ), 10, 2 );
		}
	}

	/**
	 * Attach pre_get_posts hooks.
	 *
	 * @since 3.0.0
	 */
	public static function attach_pre_get_posts_hooks() {
		// Priority must be higher than 20, as that's where we attach things in the selector class.
		add_action( 'pre_get_posts', array( __CLASS__, 'setup_wp_query' ), 30, 1 );
	}

	/**
	 * Detach pre_get_posts hooks.
	 *
	 * @since 3.0.0
	 */
	public static function detach_pre_get_posts_hooks() {
		remove_action( 'pre_get_posts', array( __CLASS__, 'setup_wp_query' ), 30 );
	}

	/**
	 * Setup the queries with their args.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 */
	public static function setup_wp_query( $query ) {
		if ( ! self::is_query_context() ) {
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
	 * @param \WP_Query     $wp_query The WP_Query instance.
	 * @param Queries_Query $query The Search & Filter query object.
	 */
	public static function track_query_data( &$wp_query, $query ) {
		if ( ! self::is_query_context() ) {
			return;
		}

		// Update local references to the queries.
		if ( ! isset( self::$tracked_queries[ $query->get_id() ] ) ) {
			self::$tracked_queries[ $query->get_id() ] = array();
		}
		$wp_queries = self::$tracked_queries[ $query->get_id() ];

		if ( ! in_array( $wp_query, $wp_queries, true ) ) {
			$wp_queries[] = $wp_query;
		}
		self::$tracked_queries[ $query->get_id() ] = $wp_queries;
	}

	/**
	 * Get render settings for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param array         $render_settings The current render settings.
	 * @param Queries_Query $query The Search & Filter query object.
	 * @return array The modified render settings.
	 */
	public static function get_render_settings( $render_settings, $query ) {

		if ( ! isset( self::$tracked_queries[ $query->get_id() ] ) ) {
			return $render_settings;
		}
		$wp_queries = self::$tracked_queries[ $query->get_id() ];

		if ( empty( $wp_queries ) ) {
			return $render_settings;
		}
		// For now only support one WP_Query, lets take the last one.
		$wp_query = end( $wp_queries );

		// Setup query render data.
		$page_key                       = isset( $render_settings['paginationKey'] ) ? $render_settings['paginationKey'] : null;
		$render_settings['currentPage'] = $wp_query->get( 'paged' );

		$page = 1;
		// The query block currently uses offset to control which "page" to show, so we
		// can't use the `paged` variable inside the query.
		// Other queries might try the same, so lets try to get the paged variable by various.

		// First, lets check if the query is paged, if so lets use the variable.
		if ( $wp_query->is_paged() ) {
			$page = $wp_query->get( 'paged' );
		} elseif ( ! empty( $page_key ) && Util::get_request_var( $page_key ) !== null ) {
			$page = (int) Util::get_request_var( $page_key );
		} else {
			$offset         = $wp_query->get( 'offset' );
			$posts_per_page = $wp_query->get( 'posts_per_page' );
			if ( ! empty( $offset ) && ! empty( $posts_per_page ) ) {
				// We can try to estimate the page based on offset + posts_per_page,
				// but make sure we have more than 1 page worth of total results.
				$page = (int) ( $offset / $posts_per_page ) + 1;
			}
		}

		$render_settings['currentPage']  = $page;
		$render_settings['maxPages']     = $wp_query->max_num_pages;
		$render_settings['postsPerPage'] = $wp_query->get( 'posts_per_page' );
		$render_settings['foundPosts']   = $wp_query->found_posts;

		return $render_settings;
	}

	/**
	 * Adjust found_posts to account for offset setting.
	 *
	 * When using offset with pagination, WordPress doesn't subtract the offset
	 * from found_posts, which can cause pagination to show incorrect total pages.
	 *
	 * @since 3.0.0
	 *
	 * @param int       $found_posts The number of found posts.
	 * @param \WP_Query $wp_query    The WP_Query instance.
	 * @return int The adjusted found_posts value.
	 */
	public static function adjust_found_posts_for_offset( $found_posts, $wp_query ) {
		if ( ! self::is_query_context() ) {
			return $found_posts;
		}

		$queries = $wp_query->get( 'search_filter_queries' );
		if ( empty( $queries ) ) {
			return $found_posts;
		}

		// Get the base offset (from settings, not the pagination-calculated one).
		foreach ( $queries as $query ) {
			$base_offset = $query->get_attribute( 'offset' );
			if ( $base_offset !== null && (int) $base_offset > 0 ) {
				$found_posts = max( 0, $found_posts - (int) $base_offset );
				break; // Only one query should be attached.
			}
		}

		return $found_posts;
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
		if ( ! self::is_query_context() ) {
			return $where;
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
		if ( ! self::is_query_context() ) {
			return $join;
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
	public static function is_query_context() {
		$override = apply_filters( 'search-filter/query/is_query_context', false );
		if ( $override ) {
			return true;
		}
		if ( ! is_admin() || wp_doing_ajax() ) {
			return true;
		}
		return false;
	}
}
