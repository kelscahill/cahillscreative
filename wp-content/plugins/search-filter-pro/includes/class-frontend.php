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
use Search_Filter\Queries\Query_Render_Store;
use Search_Filter_Pro\Core\Scripts;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $search_filter_pro_output_buffer_enabled;
$search_filter_pro_output_buffer_enabled = false;

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

		if ( isset( $_GET['search-filter-api'] ) ) {
			add_action( 'template_redirect', array( $this, 'output_buffer_start' ), 0 );

			// We need to remove `search-filter-api` from all pagination links.
			add_filter( 'get_pagenum_link', array( $this, 'remove_api_arg_pagination' ), 100 );
			add_filter( 'paginate_links', array( $this, 'remove_api_arg_pagination' ), 100 );

			// TODO: Currently the query block prev/next links don't use standard WP functions,
			// so the URLs are not filterable: https://github.com/WordPress/gutenberg/issues/54423
			// So we'll use the `render_block` filter to modify the output.
			add_filter( 'render_block', array( $this, 'modify_block_pagination_urls' ), 10, 3 );

			// If we're doing an API request, we should send no-cache headers and no-index for SEO.
			// Uses WP send_headers hook.
			add_action( 'send_headers', array( $this, 'send_no_cache_headers' ), 20 );
		}
	}
	/**
	 * Send no-cache and no-index headers for API requests.
	 */
	public function send_no_cache_headers() {
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Expires: 0' );
		header( 'X-Robots-Tag: noindex, nofollow' );
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
	 * Register the JavaScript for the frontend
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Start the output buffer.
	 *
	 * We want to wrap the regular html response into a json object.
	 *
	 * @since 3.0.0
	 */
	public function output_buffer_start() {

		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			ob_end_clean();
		}

		// TOOD: This is a bit hacky, we never want to run this twice so
		// we'll use a global in case this class gets instantiated twice.
		// It probably means this shouldn't be be here and should be in
		// some other (singleton) class.
		global $search_filter_pro_output_buffer_enabled;
		if ( $search_filter_pro_output_buffer_enabled ) {
			return;
		}
		$search_filter_pro_output_buffer_enabled = true;
		header( 'Content-Type: application/json; charset=utf-8' );
		ob_start( array( $this, 'update_output_buffer' ) );
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
	public function update_output_buffer( $content ) {
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
		$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );

		/**
		 * Only allow tags that are allowed in post content.
		 * TODO - does some strange things to the content dumped in the footer, so
		 * maybe don't do this. Also: technically all the output has already been
		 * run through the various esc_* functions, so this is probably not necessary.
		 * $content = wp_kses_post( $content );
		 */
		$query_settings = array();
		foreach ( Fields::get_active_query_ids() as $query_id ) {
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
		return wp_json_encode( $api_response );
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
}
