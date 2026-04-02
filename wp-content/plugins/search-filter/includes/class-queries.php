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

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Deprecations;
use Search_Filter\Database\Queries\Queries as Queries_Query;
use Search_Filter\Database\Table_Manager;
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
	 * Map of short names to full table keys.
	 *
	 * @var array<string, string>
	 */
	const TABLE_KEY_MAP = array(
		'queries' => 'queries',
		'meta'    => 'querymeta',
	);

	/**
	 * Initialise styles.
	 *
	 * Add default styles if none exist, attach to the CSS_Loader.
	 *
	 * @return void
	 */
	public static function init() {

		add_action( 'search-filter/record/save', array( __CLASS__, 'save_css' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Register table with Table_Manager.
		add_action( 'search-filter/schema/register', array( __CLASS__, 'register_tables' ) );
	}
	/**
	 * Register the CSS handler.
	 *
	 * @since    3.0.0
	 */
	public static function register_css_handler() {
		CSS_Loader::register_handler( 'queries', array( __CLASS__, 'get_css' ) );
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
	 * @param array  $args Column name => value pairs.
	 * @param string $return_as Whether to return the object or the record.
	 *
	 * @return array|Queries_Query
	 */
	public static function find( $args = array(), $return_as = 'objects' ) {

		// Grab db instance.
		$query_args = array(
			'number'  => 10,
			'orderby' => 'date_published',
			'order'   => 'asc',
		);

		$query_args = wp_parse_args( $args, $query_args );

		// TODO - store a reference to the query with these args for re-using.
		$query = new Queries_Query( $query_args );

		if ( $return_as === 'query' ) {
			return $query;
		}

		$queries = array();
		if ( $return_as === 'objects' ) {
			foreach ( $query->items as $record ) {
				try {
					$queries[] = Query::create_from_record( $record );
				} catch ( \Exception $e ) {
					$queries[] = new \WP_Error( 'invalid_query', $e->getMessage(), array( 'status' => 400 ) );
				}
			}
		} elseif ( $return_as === 'records' ) {
			$queries = $query->items;
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
	 * @param Query $query The query record instance.
	 * @param int   $section The section of the styles preset to save.
	 */
	public static function save_css( $query, $section ) {
		if ( $section !== 'query' ) {
			return;
		}
		CSS_Loader::queue_regeneration();
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
			'records'
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

		// If we have an active query, make sure we load the frontend assets.
		Asset_Loader::enqueue( array( 'search-filter-frontend', 'search-filter-frontend-ugc' ) );
	}

	/**
	 * Register a connected query.
	 *
	 * @param int $query_id The query ID to register.
	 */
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
	 * @param Query $query The query object.
	 * @return bool
	 */
	private static function is_active_query( $query ) {
		return in_array( $query->get_id(), self::$active_queries_ids, true );
	}

	/**
	 * Keep track of active queries to preload their data.
	 *
	 * @return array $active_fields Array of active fields.
	 */
	public static function get_used_queries() {
		$used_queries_ids = self::get_used_query_ids();
		$used_queries     = array();
		foreach ( $used_queries_ids as $query_id ) {

			$query = Query::get_instance( absint( $query_id ) );

			if ( is_wp_error( $query ) ) {
				continue;
			}

			// We only want to deal with enabled queries.
			if ( $query->get_status() !== 'enabled' ) {
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
			'status'        => array( 'enabled', 'disabled' ),
			'number'        => 0, // All records.
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


	/**
	 * Register and init the fields tables.
	 *
	 * @since    3.2.0
	 */
	public static function register_tables(): void {
		// Register all tables so we can uninstall them.
		if ( ! Table_Manager::has( 'queries' ) ) {
			Table_Manager::register( 'queries', \Search_Filter\Database\Tables\Queries::class );
		}
		if ( ! Table_Manager::has( 'querymeta' ) ) {
			Table_Manager::register( 'querymeta', \Search_Filter\Database\Tables\Queries_Meta::class );
		}
	}

	/**
	 * Get a query table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'queries' or 'meta'. Default 'queries'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $type = 'queries', $should_use = true ) {
		$key = self::TABLE_KEY_MAP[ $type ] ?? 'queries';
		return Table_Manager::get( $key, $should_use );
	}

	/**
	 * Get a query table name.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'queries' or 'meta'. Default 'queries'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $type = 'queries', $should_use = true ) {
		$table = self::get_table( $type, $should_use );
		return $table ? $table->get_table_name() : '';
	}
}
