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
		add_action( 'search-filter/frontend/enqueue_scripts/data', array( $this, 'add_script_data' ) );

		if ( isset( $_GET['search-filter-api'] ) ) {

			// We need to remove `search-filter-api` from all pagination links.
			add_filter( 'get_pagenum_link', array( $this, 'remove_api_arg_pagination' ), 100 );
			add_filter( 'paginate_links', array( $this, 'remove_api_arg_pagination' ), 100 );

			// TODO: Currently the query block prev/next links don't use standard WP functions,
			// so the URLs are not filterable: https://github.com/WordPress/gutenberg/issues/54423
			// So we'll use the `render_block` filter to modify the output.
			add_filter( 'render_block', array( $this, 'modify_block_pagination_urls' ), 10, 3 );
			add_filter( 'wp_robots', array( $this, 'modify_robots_for_api' ), 1000, 1 );

			// Remove the existing data action and override it with our own.
			remove_action( 'wp_footer', array( \Search_Filter\Frontend::class, 'data' ), 100 );
			add_action( 'wp_footer', array( $this, 'add_api_request_data' ), 100 );
		}
	}

	public function add_script_data( $data ) {
		$data['isPro'] = true;
		return $data;
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
				$scripts[ $handle ]['src'] = Scripts::get_frontend_assets_url() . 'js/frontend/frontend.js';
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
				$styles[ $handle ]['src'] = Scripts::get_frontend_assets_url() . 'css/frontend/frontend.css';
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

	public function modify_robots_for_api( $robots ) {
		$robots['noindex'] = true;
		return $robots;
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

	public function add_api_request_data() {
		$queries = array();
		if ( method_exists( '\Search_Filter\Queries', 'get_used_queries' ) ) {
			$queries = \Search_Filter\Queries::get_used_queries();

		} else if ( method_exists( '\Search_Filter\Queries', 'get_active_queries' ) ) {
			// Backward compat.
			$queries = \Search_Filter\Queries::get_active_queries();
		}
		$data         = array(
			'fields'       => Fields::get_active_fields(),
			'queries'      => $queries,
			'shouldMount'  => false,
			'isApiRequest' => true,
		);
		// Add filter to modify the data.
		$data    = apply_filters( 'search-filter/frontend/data', $data );
		?>
		<span id="search-filter-data-json" data-search-filter-data="<?php echo esc_attr( wp_json_encode( $data ) ); ?>"></span>
		<?php
	}

}
