<?php
/**
 * Debugger class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Database\Queries\Logs as Logs_Query;
use Search_Filter\Debugger\Cron as Debugger_Cron;
use Search_Filter\Debugger\Settings as Debugger_Settings;
use Search_Filter\Debugger\Settings_Data;
use Search_Filter\Queries\Query;

/**
 * Debugger class.
 */
class Debugger {

	/**
	 * The current page template name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private static $template_name = '';

	/**
	 * Tracked query data.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $tracked_queries = array();

	/**
	 * Initialises the debugger.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Add menu item to frontend.
		add_action( 'init', array( __CLASS__, 'add_debug_menu_item' ) );

		// Preload the debugger option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );

		// Initialize log cleanup cron.
		Debugger_Cron::init();
	}

	/**
	 * Creates a log in the database.
	 *
	 * @param array $data The log data.
	 */
	public static function create_log( array $data ) {
		$query = new Logs_Query();
		$query->add_item( $data );
	}

	/**
	 * Preload the debugger option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		$options_to_preload[] = 'debugger';
		return $options_to_preload;
	}

	/**
	 * Initialises and registers the settings.
	 *
	 * @since    3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Debugger_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}

	/**
	 * Adds the debug menu item to the admin bar.
	 */
	public static function add_debug_menu_item() {
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		// Don't load debug data if the user doesn't have the capability,
		// which could otherwise reveal sensitive information.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add the admin bar menu item.
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_debug_menu_item_to_admin_bar' ), 100 );

		// Add the debug data to the frontend.
		add_filter( 'search-filter/frontend/data', array( __CLASS__, 'add_frontend_debug_data' ), 100 );

		// Set the template name earlier when WP loads. For some reason calling `get_page_template` in the
		// footer with formidable forms, it causes the "Edit Site" link not to work in FSE themes (points to
		// the wrong template).
		add_action( 'init', array( __CLASS__, 'set_template_name' ), 100 );

		// Load scripts late - we want to load after S&F scripts but not have
		// to set S&F frontend as a dependency.
		if ( is_admin_bar_showing() ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 20 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
		}

		// Track which WP_Query is attached to which S&F query.
		add_action( 'search-filter/query/attach_wp_query', array( __CLASS__, 'track_query_data' ), 10, 2 );

		// Add the query data to the render settings for display.
		add_filter( 'search-filter/queries/query/get_render_settings', array( __CLASS__, 'add_debug_data_to_query_render_settings' ), 10, 2 );
	}


	/**
	 * Sets the template name.
	 */
	public static function set_template_name() {
		self::$template_name = basename( get_page_template() );
	}

	/**
	 * Register the assets for the Gutenberg editor.
	 *
	 * @since 3.2.0
	 */
	public static function register_assets() {

		$asset_configs = array(
			array(
				'name'   => 'search-filter-debug',
				'script' => array(
					'src'        => SEARCH_FILTER_URL . 'assets/debug/app.js',
					'asset_path' => SEARCH_FILTER_PATH . 'assets/debug/app.asset.php',
					'data'       => array(
						'identifier' => 'window.searchFilterDebug',
						'value'      => (object) Util::get_js_data(),
						'position'   => 'before',
					),
				),
				'style'  => array(
					'src' => SEARCH_FILTER_URL . 'assets/debug/app.css',
				),
			),
		);

		$assets = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );
	}

	/**
	 * Enqueues the assets.
	 */
	public static function enqueue_assets() {
		// Enqueue the admin assets.
		Asset_Loader::enqueue( array( 'search-filter-debug' ) );
	}

	/**
	 * Adds the debug menu item to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The admin bar.
	 */
	public static function add_debug_menu_item_to_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
		$menu_id = 'search-filter-debug';
		$args    = array(
			'id'    => $menu_id,
			'title' => 'Search & Filter',
			'href'  => '#',
			'meta'  => array(
				'class' => 'search-filter-debug',
			),
		);
		$wp_admin_bar->add_node( $args );

		// We need to add a blank menu item so that
		// the container html is generated.  It will be
		// replaced with the JS app.
		$wp_admin_bar->add_menu(
			array(
				'parent' => $menu_id,
				'id'     => 'search-filter-placeholder',
			)
		);
	}

	/**
	 * Adds the frontend debug data.
	 *
	 * @param array $data The data.
	 *
	 * @return array The data.
	 */
	public static function add_frontend_debug_data( array $data ): array {

		$template_data = array(
			'template'                          => self::$template_name,
			'queried_object_id'                 => get_queried_object_id(),
			'is_archive'                        => is_archive() ? 'true' : 'false',
			'is_search'                         => is_search() ? 'true' : 'false',
			'is_home (blog)'                    => is_home() ? 'true' : 'false',
			'is_front_page'                     => is_front_page() ? 'true' : 'false',
			'is_singular'                       => is_singular() ? 'true' : 'false',
			'is_page'                           => is_page() ? 'true' : 'false',
			'is_single'                         => is_single() ? 'true' : 'false',
			'is_404'                            => is_404() ? 'true' : 'false',
			'is_attachment'                     => is_attachment() ? 'true' : 'false',
			'is_author'                         => is_author() ? 'true' : 'false',
			'is_category'                       => is_category() ? 'true' : 'false',
			'is_tag'                            => is_tag() ? 'true' : 'false',
			'is_tax'                            => is_tax() ? 'true' : 'false',
			'is_singular_taxonomy_term_archive' => \Search_Filter\Query\Template_Data::is_singular_taxonomy_term_archive() ? 'true' : 'false',
			'taxonomy_term_archive_has_multiple_post_types' => \Search_Filter\Query\Template_Data::taxonomy_term_archive_has_multiple_post_types() ? 'true' : 'false',
			'is_date'                           => is_date() ? 'true' : 'false',
			'is_post_type_archive'              => is_post_type_archive() ? 'true' : 'false',
			'is_paged'                          => is_paged() ? 'true' : 'false',
			'is_preview'                        => is_preview() ? 'true' : 'false',
			'is_admin'                          => is_admin() ? 'true' : 'false',
			'is_customize_preview'              => is_customize_preview() ? 'true' : 'false',
			'is_rtl'                            => is_rtl() ? 'true' : 'false',
			'is_ssl'                            => is_ssl() ? 'true' : 'false',
			'is_user_logged_in'                 => is_user_logged_in() ? 'true' : 'false',
			'is_main_query'                     => is_main_query() ? 'true' : 'false',
			'wp_doing_ajax'                     => wp_doing_ajax() ? 'true' : 'false',
		);
		$data['template'] = $template_data;
		return $data;
	}


	/**
	 * Tracks the WP_Query data for each S&F query.
	 *
	 * @param \WP_Query $wp_query The WP_Query object.
	 * @param Query     $query The S&F query.
	 */
	public static function track_query_data( \WP_Query &$wp_query, Query $query ) {
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
	 * Adds debug data to the query render settings.
	 *
	 * @param array $render_settings The render settings.
	 * @param Query $query The query.
	 *
	 * @return array The render settings.
	 */
	public static function add_debug_data_to_query_render_settings( array $render_settings, Query $query ) {

		// Don't show debug data if debug mode is disabled.
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return $render_settings;
		}

		if ( ! isset( self::$tracked_queries[ $query->get_id() ] ) ) {
			return $render_settings;
		}

		$wp_queries = self::$tracked_queries[ $query->get_id() ];

		if ( empty( $wp_queries ) ) {
			return $render_settings;
		}

		// For now, lets assume there is only one WP_Query (get the last one).
		$wp_query = array_pop( $wp_queries );

		// A WP_Query can have more than one S&F query, although it shouldn't.
		$queries   = $wp_query->get( 'search_filter_queries' );
		$query_ids = array();
		if ( ! empty( $queries ) ) {
			$query_ids = array_map(
				function ( $query ) {
					return $query->get_id();
				},
				$queries
			);
		}

		// Convert new lines & tabs to spaces.
		$requests_cleaned = str_replace( array( "\n", "\t" ), ' ', $wp_query->request );

		// Create an array of Post Titles with IDs from the matching posts.
		$posts = array();
		foreach ( $wp_query->posts as $post ) {
			// The query could have returned different fields, rather than the WP_Post object.
			if ( $post instanceof \WP_Post ) {
				$posts[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title,
				);
			} else {
				$posts[] = $post;
			}
		}
		// Swap the class instances for the query IDs for debugging.
		$render_settings['debug'] = array(
			'wp_query' => array(
				'query'                 => $wp_query->query,
				'query_vars'            => $wp_query->query_vars,
				'tax_query'             => $wp_query->tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Not an actual DB call.
				'meta_query'            => $wp_query->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Not an actual DB call.
				'posts'                 => $posts,
				'page'                  => $wp_query->get( 'paged' ),
				'posts_per_page'        => $wp_query->get( 'posts_per_page' ),
				'found_posts'           => $wp_query->found_posts,
				'offset'                => $wp_query->get( 'offset' ),
				'request'               => $requests_cleaned,
				'search_filter_queries' => $query_ids,
			),
		);

		return $render_settings;
	}
}
