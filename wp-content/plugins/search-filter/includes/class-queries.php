<?php
/**
 * Handles queries
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Deprecations;
use Search_Filter\Database\Queries\Queries as Queries_Query;
use Search_Filter\Queries\Query;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter\Queries\Settings_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all things to do with saved queries
 */
class Queries {

	/**
	 * Keeps track of which query IDs have run on a page.
	 *
	 * @var array
	 */
	private static $active_queries_ids = array();

	/**
	 * Keeps track of which query IDs will be needed on the page,
	 * for example if a field is loaded it will require its connected
	 * query data to be loaded.
	 *
	 * @var array
	 */
	private static $connected_queries_ids = array();

	/**
	 * Initialise styles.
	 *
	 * Add default styles if none exist, attach to the CSS_Loader.
	 *
	 * @return void
	 */
	public static function init() {

		add_action( 'search-filter/record/save', 'Search_Filter\\Queries::save_css', 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );
	}
	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'queries', 'Search_Filter\\Queries::get_css' );
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Queries_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}
	/**
	 * Find multiple styles by conditions
	 *
	 * @param array $args Column name => value pairs.
	 * @param bool  $return_record Whether to return the object or the record.
	 *
	 * @return array
	 */
	public static function find( $args = array(), $return_record = false ) {

		// Grab db instance.
		$query_args = array(
			'number'  => 10,
			'orderby' => 'date_published',
			'order'   => 'asc',
		);

		$query_args = wp_parse_args( $args, $query_args );

		// TODO - store a reference to the query with these args for re-using.
		$query   = new Queries_Query( $query_args );
		$queries = array();
		if ( $query ) {
			if ( ! $return_record ) {
				foreach ( $query->items as $record ) {
					try {
						$queries[] = Query::create_from_record( $record );
					} catch ( \Exception $e ) {
						$queries[] = new \WP_Error( 'invalid_query', $e->getMessage(), array( 'status' => 400 ) );
					}
				}
			} else {
				$queries = $query->items;
			}
		}
		return $queries;
	}
	/**
	 * Finds the count of styles presets.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Column name => value pairs.
	 *
	 * @return int
	 */
	public static function find_count( $args ) {
		// Grab db instance.
		$query_args = array(
			'number' => 0,
			'count'  => true,
		);
		$query_args = wp_parse_args( $args, $query_args );
		$query      = new Queries_Query( $query_args );
		return $query->found_items;
	}

	/**
	 * When saving a query , rebuild the CSS file.
	 *
	 * @param Style $query The query record instance.
	 * @param int   $section The section of the styles preset to save.
	 */
	public static function save_css( $query, $section ) {
		if ( $section !== 'query' ) {
			return;
		}
		CSS_Loader::save_css( 'queries' );
	}
	/**
	 * Loop through queries and build their CSS.
	 */
	public static function get_css() {
		$css = '';
		// Loop through fields, and build their CSS.
		$all_records = self::find(
			array(
				'number' => 0,
				'status' => 'enabled',
			),
			true
		);
		if ( count( $all_records ) > 0 ) {
			foreach ( $all_records as $record ) {
				$css .= self::get_record_css( $record ) . "\r\n";
			}
		}
		return $css;
	}
	/**
	 * Generates or fetches the CSS for a single style preset.
	 *
	 * @since   3.0.0
	 *
	 * @param Query $record The styles record instance.
	 * @return string The generated CSS.
	 */
	public static function get_record_css( $record ) {
		$css  = '';
		$name = $record->get_name();
		// Use cached version.
		$cached_css = $record->get_css();
		if ( $cached_css !== '' ) {
			$css .= '/* Query: ' . esc_html( $name ) . " */\r\n";
			$css .= CSS_Loader::clean_css( $cached_css );
		}

		return $css;
	}



	/**
	 * Keep track of active queries to preload their data.
	 *
	 * @param array $query_id The query ID.
	 */
	public static function register_active_query( $query_id ) {
		self::$active_queries_ids[] = $query_id;
	}
	public static function register_connected_query( $query_id ) {
		self::$connected_queries_ids[] = $query_id;
	}

	/**
	 * Get the active query IDs.
	 */
	public static function get_active_query_ids() {
		return self::$active_queries_ids;
	}

	/**
	 * Get the connected query IDs.
	 */
	public static function get_connected_query_ids() {
		return self::$connected_queries_ids;
	}

	/**
	 * Get the required query IDs.
	 *
	 * @return array
	 */
	public static function get_used_query_ids() {
		$required_query_ids = array_unique( array_merge( self::$active_queries_ids, self::$connected_queries_ids ) );
		return $required_query_ids;
	}

	/**
	 * Is the query an active query.
	 *
	 * @return bool
	 */
	private static function is_active_query( $query ) {
		return in_array( $query->get_id(), self::$active_queries_ids, true );
	}

	/**
	 * Get the connected query IDs.
	 */
	/**
	 * Keep track of active fields to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_used_queries() {
		$used_queries_ids = self::get_used_query_ids();
		$used_queries     = array();
		foreach ( $used_queries_ids as $query_id ) {
			// We only want to deal with enabled queries.
			$query = Query::find(
				array(
					'id'     => $query_id,
					'status' => 'enabled',
				)
			);

			if ( is_wp_error( $query ) ) {
				continue;
			}

			$query_data                = array(
				'id'         => $query_id,
				'attributes' => $query->get_attributes(),
				'settings'   => $query->get_render_settings(),
				'url'        => $query->get_results_url(),
				'isActive'   => self::is_active_query( $query ),
				// TODO - only add this if debugging features are enabled?
				'name'       => $query->get_name(),
			);
			$used_queries[ $query_id ] = $query_data;
		}
		return $used_queries;
	}

	/**
	 * Backwards compatibility to support older versions.
	 *
	 * @return array
	 */
	public static function get_active_queries() {
		Deprecations::add( 'Using outdated method `get_active_queries` which will be deprecated soon.  Update Search & Filter and extensions to remove this notice.' );
		return self::get_used_queries();
	}


	/**
	 * Gets the list of saved queries - used for displaying in dropdowns in our
	 * admin UI.
	 *
	 * @return array The list of saved queries.
	 */
	public static function get_queries_list() {
		$defaults   = array(
			'no_found_rows' => true,
			'status'        => 'enabled',
		);
		$query_args = wp_parse_args( $defaults );
		$query      = new \Search_Filter\Database\Queries\Queries( $query_args );
		return $query->items;
	}

	/**
	 * Gets the first query ID from the list.
	 *
	 * Helps when preloading the query data in our admin UI.
	 *
	 * @return int The first query ID.
	 */
	public static function get_queries_list_first_id() {
		$queries_list = self::get_queries_list();
		if ( count( $queries_list ) > 0 ) {
			return $queries_list[0]->id;
		}
		return 0;
	}
}
