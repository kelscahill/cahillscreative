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

use Search_Filter\Fields;
use Search_Filter\Queries;
use Search_Filter\Queries\Query_Render_Store;
use Search_Filter_Pro\Core\Scripts;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Don't wait for plugins_loaded hook, start the output buffer as soon as possible.
Frontend::output_buffer();

/**
 * The main class for initialising all things for the frontend.
 */
class Frontend {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param string $plugin_name  The name of the plugin.
	 * @param string $version      The version of this plugin.
	 */
	public function __construct() {

		\Search_Filter_Pro\Fields::init();
		\Search_Filter_Pro\Queries::init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_debug_script' ) );

		if ( isset( $_GET['search-filter-api'] ) ) {

			// We need to remove `search-filter-api` from all pagination links.
			add_filter( 'get_pagenum_link', array( $this, 'remove_api_arg_pagination' ), 100 );
			add_filter( 'paginate_links', array( $this, 'remove_api_arg_pagination' ), 100 );

			// TODO: Currently the query block prev/next links don't use standard WP functions,
			// so the URLs are not filterable: https://github.com/WordPress/gutenberg/issues/54423
			// So we'll use the `render_block` filter to modify the output.
			add_filter( 'render_block', array( $this, 'modify_block_pagination_urls' ), 10, 3 );

			// If we're doing an API request, we should send no-cache headers and no-index for SEO.
			// Uses WP send_headers hook.
			add_action( 'send_headers', array( __CLASS__, 'send_headers' ), 20 );
		}
	}

	/**
	 * Replace the frontend script with the pro version.
	 *
	 * @since 3.0.0
	 *
	 * @param array $scripts    The scripts to update.
	 * @return array    The updated scripts.
	 */
	public function update_scripts( $scripts ) {
		foreach ( $scripts as $handle => $args ) {
			if ( $handle === 'search-filter' ) {
				$scripts[ $handle ]['src'] = Scripts::get_frontend_assets_url() . 'js/frontend/frontend.' . Util::get_file_ext( 'js' );
			}
		}

		return $scripts;
	}
	/**
	 * Replace the frontend styles with the pro version.
	 *
	 * @since 3.0.0
	 *
	 * @param array $styles    The styles to update.
	 * @return array    The updated styles.
	 */
	public function update_styles( $styles ) {
		foreach ( $styles as $handle => $args ) {
			if ( $handle === 'search-filter' ) {
				$styles[ $handle ]['src'] = Scripts::get_frontend_assets_url() . 'css/frontend/frontend.' . Util::get_file_ext( 'css' );
			}
		}
		return $styles;
	}
	/**
	 * Register the debug app JavaScript for the frontend
	 *
	 * @since    3.0.0
	 */
	public function enqueue_debug_script() {
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
	 * @param array  $instance         The instance.
	 * @return string    The modified block content.
	 */
	public function modify_block_pagination_urls( $block_content, $block, $instance = null ) {
		$desired_blocks = array(
			'core/query-pagination-next',
			'core/query-pagination-previous',
		);
		if ( ! in_array( $block['blockName'], $desired_blocks ) ) {
			return $block_content;
		}

		// Parse the block with the HTML Tag Processor.
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		while ( $processor->next_tag( 'a' ) ) {
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
	 * Remove the API arg from pagination URLs.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url    The URL to remove the arg from.
	 * @return string    The URL.
	 */
	public function remove_api_arg_pagination( $url ) {
		$url = remove_query_arg( 'search-filter-api', $url );
		return $url;
	}

	/**
	 * Start the output buffer.
	 *
	 * We want to wrap the regular html response into a json object.
	 *
	 * @since 3.0.0
	 */
	public static function output_buffer() {

		if ( ! isset( $_GET['search-filter-api'] ) ) {
			return;
		}

		$level = ob_get_level();
		for ( $i = 0; $i < $level; $i++ ) {
			ob_end_clean();
		}

		ob_start( array( __CLASS__, 'update_output_buffer' ), 0, PHP_OUTPUT_HANDLER_STDFLAGS ^ PHP_OUTPUT_HANDLER_REMOVABLE ^ PHP_OUTPUT_HANDLER_FLUSHABLE ^ PHP_OUTPUT_HANDLER_CLEANABLE );
	}

	/**
	 * Update the output buffer.
	 *
	 * Tidy up the content before exporting via our JSON response.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content    The content to update.
	 */
	public static function update_output_buffer( $content ) {
		// Get the page title.
		$title = wp_strip_all_tags( html_entity_decode( wp_get_document_title(), ENT_QUOTES, 'UTF-8' ) );

		// Simplify the content / document before exporting via our JSON response.

		// Get only the body tag, we don't need anything else.
		// Note - need to include the body tag itself so CSS selectors that specify the body
		// will continue to work.

		// TODO: some CSS selectors start at the html tag - need to document this.

		$matches = array();
		// TODO: we might need to add a way to extract other parts of the document.  There is
		// a use case with Elementor, on a page with no results, lots of the CSS and JS for
		// the loop grid template is not loaded (as a template is never loaded), so we need to
		// load them in when navigating to a page/search with results (otherwise templates are
		// loaded without their necessary css/js - even more true for external plugins.
		// Probably also applicable with the query loop.
		preg_match( '/<body[^>]*>(.*?)<\/body>/si', $content, $matches );
		if ( count( $matches ) > 0 ) {
			$content = $matches[0];
		}

		// Remove all remaining script tags.
		// TODO - is this necessary? Does it add unecessary overhead?
		$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );

		$query_settings = array();
		foreach ( Queries::get_active_query_ids() as $query_id ) {
			$render_data = Query_Render_Store::get_render_data( $query_id );
			if ( $render_data ) {
				$query_settings[ $query_id ] = $render_data;
			}
		}

		$api_response = array(
			'title'   => $title,
			'fields'  => Fields::get_active_fields(),
			'queries' => $query_settings,
			'results' => $content,
		);

		// Need to re-send the headers in case something else sent them in the page load.
		// Caching plugins often do this, so we need to set content type back to JSON.
		self::send_headers();
		return wp_json_encode( $api_response );
	}
	/**
	 * Send no-cache and no-index headers for API requests.
	 */
	public static function send_headers() {
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Expires: 0' );
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
}
