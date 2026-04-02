<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Features;
use Search_Filter_Pro\Core\Scripts;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main class for initialising all things for the frontend.
 */
class Frontend {

	/**
	 * Keep track of the site url in case its different
	 * from the home URL.
	 *
	 * @var string
	 */
	private static $front_url = '';

	/**
	 * Keep track of the home url for comparison later.
	 *
	 * @var string
	 */
	private static $home_url = '';


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 */
	public static function init() {

		if ( ! \Search_Filter\Frontend::should_init() ) {
			return;
		}

		\Search_Filter_Pro\Fields::init();
		\Search_Filter_Pro\Queries::init();

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_debug_assets' ) );

		// Capture the starting html as soon as possible, before scripts have executed
		// and modified the DOM.
		add_action( 'wp_head', array( __CLASS__, 'start_tag_head_assets' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'end_tag_head_assets' ), 1000 );
		add_action( 'wp_footer', array( __CLASS__, 'end_body_capture' ), 1000 );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public API parameter check, no data modification.
		if ( isset( $_GET['search-filter-api'] ) ) {
			// Hooking in too early causes issues with other plugins that set the home_url,
			// including local by wpengine.
			add_action( 'template_redirect', array( __CLASS__, 'set_site_url' ), 20 );
			add_action( 'wp_footer', array( __CLASS__, 'remove_site_url' ) );
			// We need to remove `search-filter-api` from all pagination links.
			add_filter( 'get_pagenum_link', array( __CLASS__, 'remove_api_arg_pagination' ), 100 );
			add_filter( 'paginate_links', array( __CLASS__, 'remove_api_arg_pagination' ), 100 );

			// TODO: Currently the query block prev/next links don't use standard WP functions,
			// so the URLs are not filterable: https://github.com/WordPress/gutenberg/issues/54423
			// So we'll use the `render_block` filter to modify the output.
			add_filter( 'render_block', array( __CLASS__, 'modify_block_pagination_urls' ), 10, 2 );
			add_filter( 'wp_robots', array( __CLASS__, 'modify_robots_for_api' ), 1000, 1 );
		}
	}


	/**
	 * Replace the frontend script & styles with the pro version.
	 *
	 * @since 3.0.0
	 *
	 * @param array $registered_assets    The registered assets to update.
	 * @return array    The updated scripts.
	 */
	public static function update_assets( $registered_assets ) {
		$asset_configs = array(
			array(
				'name'   => 'search-filter-frontend',
				'script' => array(
					'src'        => SEARCH_FILTER_PRO_URL . 'assets/frontend/app.js',
					'asset_path' => SEARCH_FILTER_PRO_PATH . 'assets/frontend/app.asset.php',
				),
				'style'  => array(
					'src' => SEARCH_FILTER_PRO_URL . 'assets/frontend/app.css',
				),
			),
		);
		$new_assets    = Asset_Loader::create( $asset_configs );

		return array_merge( $registered_assets, $new_assets );
	}

	/**
	 * Register interactivity scripts.
	 *
	 * @since 3.0.0
	 */
	public static function register_interactivity_scripts() {
		wp_enqueue_script_module( 'wp-interactivity' );
		wp_enqueue_script_module( 'wp-interactivity-router' );
		wp_enqueue_script_module( 'wp-polyfill' );
		wp_enqueue_script_module(
			'@search-filter-pro/interactivity',
			Scripts::get_frontend_assets_url() . 'js/frontend/interactivity.js',
			array(
				array( 'id' => 'wp-interactivity' ),
				array( 'id' => 'wp-interactivity-router' ),
				array( 'id' => 'wp-polyfill' ),
				array( 'id' => 'search-filter' ),
			),
			SEARCH_FILTER_PRO_VERSION
		);
	}

	/**
	 * Register the debug app JavaScript for the frontend
	 *
	 * @since    3.0.0
	 */
	public static function enqueue_debug_assets() {

		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$asset_configs = array(
			array(
				'name'   => 'search-filter-pro-debug',
				'script' => array(
					'src'          => SEARCH_FILTER_PRO_URL . 'assets/debug/app.js',
					'asset_path'   => SEARCH_FILTER_PRO_PATH . 'assets/debug/app.asset.php',
					'dependencies' => array( 'search-filter-debug' ),
				),
				'style'  => array(),
			),
		);
		$assets        = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );

		// Enqueue the admin assets.
		Asset_Loader::enqueue( array( 'search-filter-pro-admin' ) );

		// Because we use `search-filter-debug` as a dependency, we don't need to do any additional
		// checks to see we should load this or not.
		wp_enqueue_script( 'search-filter-pro-debug', \Search_Filter_Pro\Core\Scripts::get_frontend_assets_url() . 'js/frontend/debug.js', array( 'search-filter-debug' ), SEARCH_FILTER_PRO_VERSION, true );
	}


	/**
	 * Modify block pagination URLs.
	 *
	 * Because query block urls are not the same are the rest of the WP urls,
	 * we need to filter them seperately and remove the `search-filter-api` query arg.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @return string    The modified block content.
	 */
	public static function modify_block_pagination_urls( $block_content, $block ) {
		$desired_blocks = array(
			'core/query-pagination-next',
			'core/query-pagination-previous',
		);
		if ( ! in_array( $block['blockName'], $desired_blocks, true ) ) {
			return $block_content;
		}

		// Parse the block with the HTML Tag Processor.
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		while ( $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
			// Get href attribute.
			$url = $processor->get_attribute( 'href' );
			// Remove the `search-filter-api` query arg.
			$url = remove_query_arg( 'search-filter-api', $url );
			$processor->set_attribute( 'href', $url );
		}

		$block_content = $processor->get_updated_html();
		return $block_content;
	}

	/**
	 * Modify robots meta for API requests.
	 *
	 * @since 3.0.0
	 *
	 * @param array $robots The robots array.
	 * @return array The modified robots array.
	 */
	public static function modify_robots_for_api( $robots ) {
		$robots['noindex'] = true;
		return $robots;
	}

	/**
	 * Modify the home URL in our API requests.
	 *
	 * Useful when the api url + site url are different, and we need to return
	 * results containing results to the frontend location, not the WP install.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url    The URL to modify.
	 * @return string    The modified URL.
	 */
	public static function api_site_url( $url ) {
		// If we got here, it means the home_url and the frontend_url are different.
		$url = str_replace( self::$home_url, self::$front_url, $url );
		return $url;
	}

	/**
	 * Update the url if the front url is set and different to the home url.
	 *
	 * @since 3.0.0
	 */
	public static function set_site_url() {
		self::$front_url = apply_filters( 'search-filter/frontend/front_url', '' );
		self::$home_url  = home_url();

		if ( empty( self::$front_url ) ) {
			return;
		}

		if ( self::$front_url === self::$home_url ) {
			return;
		}
		add_filter( 'home_url', array( __CLASS__, 'api_site_url' ), 20 );
		add_filter( 'site_url', array( __CLASS__, 'api_site_url' ), 20 );
	}

	/**
	 * Remove the site url filter.
	 *
	 * @since 3.0.0
	 */
	public static function remove_site_url() {
		remove_filter( 'home_url', array( __CLASS__, 'api_site_url' ), 20 );
		remove_filter( 'site_url', array( __CLASS__, 'api_site_url' ), 20 );
	}

	/**
	 * Remove the API arg from pagination URLs.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url    The URL to remove the arg from.
	 * @return string    The URL.
	 */
	public static function remove_api_arg_pagination( $url ) {
		$url = remove_query_arg( 'search-filter-api', $url );
		return $url;
	}

	/**
	 * Start the head assets tag.
	 *
	 * We only want to capture assets added via WP the normal way,
	 * comments get stripped out by minification and optimization plugins
	 * so add meta tags to mark the start of the first regularly loaded
	 * head asset.
	 *
	 * @since 3.0.0
	 */
	public static function start_tag_head_assets() {
		?>
		<!-- Search & Filter: Head Assets Start -->
		<meta name="search-filter-head-assets-start">
		<?php
	}

	/**
	 * End the head assets tag.
	 *
	 * Mark the end of the head assets.
	 *
	 * @since 3.0.0
	 */
	public static function end_tag_head_assets() {
		?>
		<meta name="search-filter-head-assets-end">
		<?php
		/*
		 * Capture the head html of the page as early as possible.
		 *
		 * This is used to determine which elements were added to the head
		 * and which were added via JS.
		 *
		 * While not perfect, its more or less the earliest point the head
		 * assets will be added and available to read as html.
		 */
		?>
		<!-- Search & Filter: Head Assets End -->
		<script id="search-filter-dom-ready-head" type="text/javascript">
			window.searchAndFilterPage = { head: document.head.outerHTML, body: null };
		</script>
		<?php
	}

	/**
	 * Capture the body html of the page in the footer.
	 *
	 * Again
	 *
	 * @since 3.0.0
	 */
	public static function end_body_capture() {
		/*
		 * Capture the body html of the page as early as possible.
		 *
		 * This is used to determine which elements were added to the body
		 * via JS on page load.
		 *
		 * While not perfect, its more or less the earliest point the body
		 * assets will be added and available to read as html.  Other scripts
		 * could have run before now and manipuled the DOM.
		 *
		 * This will at least be run before dom ready and interactive ready
		 * state events have occured though, which is when most scripts are
		 * run.
		 */
		?>
		<script id="search-filter-dom-ready-body" type="text/javascript">
			window.searchAndFilterPage.body = document.body.outerHTML;
		</script>
		<?php
	}
}
