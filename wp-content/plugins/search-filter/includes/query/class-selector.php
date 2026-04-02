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

namespace Search_Filter\Query;

use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Figures out which query to select or attach to.
 */
class Selector {

	/**
	 * Stores a local copy of our queries.
	 *
	 * @var array
	 */
	private static $queries = array();

	/**
	 * Register the stylesheets for the public-facing side of the plugin.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'init_queries' ), 21 );

		// Initially attach the hooks.
		self::attach_pre_get_posts_hooks();

		// Allow them to be attached/detached via an action.
		add_action( 'search-filter/query/pre_get_posts/attach', array( __CLASS__, 'attach_pre_get_posts_hooks' ), 10 );
		add_action( 'search-filter/query/pre_get_posts/detach', array( __CLASS__, 'detach_pre_get_posts_hooks' ), 10 );
	}

	/**
	 * Attach the pre_get_posts hooks.
	 */
	public static function attach_pre_get_posts_hooks() {
		// Priority is important.  We ideally want this to be after the default priority of 10
		// as that's where most user functions will be called.
		add_action( 'pre_get_posts', array( __CLASS__, 'attach_ids' ), 20 );
		add_action( 'pre_get_posts', array( __CLASS__, 'attach_queries' ), 20 );
	}

	/**
	 * Detach the pre_get_posts hooks.
	 */
	public static function detach_pre_get_posts_hooks() {
		remove_action( 'pre_get_posts', array( __CLASS__, 'attach_ids' ), 20 );
		remove_action( 'pre_get_posts', array( __CLASS__, 'attach_queries' ), 20 );
	}

	/**
	 * Init the queries.
	 */
	public static function init_queries() {
		self::$queries = Queries::find(
			array(
				'status' => 'enabled',
				'number' => 0,
			)
		);
		foreach ( self::$queries as $query ) {
			$existing_instance = Query::has_instance( $query->get_id() );
			if ( ! $existing_instance ) {
				Query::set_instance( $query );
			}
		}
	}

	/**
	 * Attach S&F queries by ID.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $wp_query The WP_Query instance.
	 */
	public static function attach_ids( \WP_Query $wp_query ) {
		$search_filter_id = $wp_query->get( 'search_filter_query_id' );
		if ( empty( $search_filter_id ) ) {
			return;
		}

		$query = Query::get_instance( $search_filter_id );

		if ( is_wp_error( $query ) ) {
			return;
		}

		if ( $query->get_status() !== 'enabled' ) {
			return;
		}

		$fields = $query->get_fields();

		$wp_query->set( 'search_filter_queries', array( $query ) );
	}
	/**
	 * Based on saved admin queries, check the current query / page to see if we need to
	 * attach an ID.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $wp_query The WP_Query instance.
	 */
	public static function attach_queries( \WP_Query $wp_query ) {

		// TODO - store the integration settings in seperate columns so we can look them up,
		// rather than looping through all of them on every page load.
		foreach ( self::$queries as $saved_query ) {

			$should_attach = $saved_query->should_attach_to_query( $wp_query );

			// Allow for custom integration types.
			$should_attach = apply_filters( 'search-filter/query/selector/should_attach', $should_attach, $saved_query, $wp_query );

			if ( $should_attach ) {
				$wp_query->set( 'search_filter_queries', array( $saved_query ) );
			}
		}
	}
}
