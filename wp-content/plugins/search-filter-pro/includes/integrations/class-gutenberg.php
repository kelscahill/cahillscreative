<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Integrations;
use Search_Filter\Queries\Settings as Queries_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Gutenberg integration functionality
 */
class Gutenberg {

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}
		add_action( 'search-filter/integrations/gutenberg/asset_handle', array( __CLASS__, 'block_editor_asset_handle' ), 20 );
		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 1 );
	}

	/**
	 * Setup the Gutenberg integration.
	 */
	public static function setup() {

		if ( ! Integrations::is_enabled( 'blockeditor' ) ) {
			return;
		}

		self::register_settings();
		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 2, 2 );
		// Priority needs to be lower than `11` - before the render_block filter in S&F Free Gutenberg
		// extension class, which will unset the connected query IDs.
		add_filter( 'render_block', array( __CLASS__, 'render_query_block' ), 10, 2 );
		add_action( 'search-filter/integrations/gutenberg/register_assets', array( __CLASS__, 'register_assets' ), 19 );
	}

	/**
	 * Register the assets for the Gutenberg editor.
	 *
	 * Ensure we load our assets at the same time as the S&F plugin so the block
	 * scripts & styles are setup correctly in the editor.
	 *
	 * @since 3.2.0
	 */
	public static function register_assets() {

		$asset_configs = array(
			array(
				'name'   => 'search-filter-pro-gutenberg',
				'script' => array(
					'src'          => SEARCH_FILTER_PRO_URL . 'assets/admin/block-editor.js',
					'asset_path'   => SEARCH_FILTER_PRO_PATH . 'assets/admin/block-editor.asset.php',
					'dependencies' => array( 'search-filter-gutenberg' ), // Additional dependencies.
				),
				'style'  => array(
					'src'          => SEARCH_FILTER_PRO_URL . 'assets/admin/block-editor.css',
					'dependencies' => array( 'search-filter-gutenberg' ),
				),
			),
		);

		$assets = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );
	}

	/**
	 * Override the default asset handle.
	 */
	public static function block_editor_asset_handle() {
		return 'search-filter-pro-gutenberg';
	}

	/**
	 * Modify the query block and add a classname if our query is attached.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @return string    The modified block content.
	 */
	public static function render_query_block( $block_content, $block ) {

		if ( $block['blockName'] !== 'core/query' ) {
			return $block_content;
		}

		if ( isset( $block['attrs']['namespace'] ) ) {
			return $block_content;
		}

		$connected_query_ids = array();

		if ( self::query_block_uses_global_query( $block ) ) {
			// Then we need to check if S&F is affecting the global query or not.
			global $wp_query;
			if ( ! isset( $wp_query->query_vars['search_filter_queries'] ) ) {
				return $block_content;
			}

			foreach ( $wp_query->query_vars['search_filter_queries'] as $query ) {
				$connected_query_ids[] = $query->get_id();
			}
		} else {
			// Check if we have any IDs connected to this query.
			$connected_query_ids = \Search_Filter\Integrations\Gutenberg::get_active_query_ids();
			if ( count( $connected_query_ids ) === 0 ) {
				return $block_content;
			}
		}

		// Instantiate the tag processor.
		$content = new \WP_HTML_Tag_Processor( $block_content );
		// Find the first <div> tag in the block markup.
		$content->next_tag( array( 'div' ) );

		foreach ( $connected_query_ids as $connected_query_id ) {
			$content->add_class( 'search-filter-query' );
			$content->add_class( 'search-filter-query--id-' . $connected_query_id );
		}
		// Save the updated block content.
		$block_content = (string) $content;
		return $block_content;
	}

	/**
	 * Check if the query block uses the global query.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block    The block.
	 * @return bool    Whether the query block uses the global query.
	 */
	private static function query_block_uses_global_query( $block ) {
		return isset( $block['attrs']['query'] ) && isset( $block['attrs']['query']['inherit'] ) && $block['attrs']['query']['inherit'] === true;
	}

	/**
	 * Update the query attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $attributes    The attributes to update.
	 * @param object $query         The query object.
	 * @return array    The updated attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {

		$id = $query->get_id();

		// We want `queryPostsContainer` and `queryPaginationSelector` to be set automatically.
		// `queryContainer` handled in base plugin.
		if ( ! isset( $attributes['integrationType'] ) ) {
			return $attributes;
		}

		if ( ! isset( $attributes['queryIntegration'] ) ) {
			return $attributes;
		}
		$query_integration = $attributes['queryIntegration'];

		if ( $query_integration === 'query_block' ) {
			$attributes['queryPostsContainer']     = '.search-filter-query--id-' . $id . ' .wp-block-post-template';
			$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . $id . ' .wp-block-query-pagination a';
		}

		return $attributes;
	}

	/**
	 * Update the query settings automatically.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {

		$depends_conditions = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'option'  => 'queryIntegration',
					'compare' => '!=',
					'value'   => 'query_block',
				),
			),
		);

		$query_posts_container = Queries_Settings::get_setting( 'queryPostsContainer' );
		if ( $query_posts_container ) {
			$query_posts_container->add_depends_condition( $depends_conditions );
		}

		$pagination_selector = Queries_Settings::get_setting( 'queryPaginationSelector' );
		if ( $pagination_selector ) {
			$pagination_selector->add_depends_condition( $depends_conditions );
		}
	}
}
