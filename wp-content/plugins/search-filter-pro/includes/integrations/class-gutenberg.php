<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Admin\Screens;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter_Pro\Core\Scripts;
use Search_Filter_Pro\Util;

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
	 * @since 3.0.0
	 */
	public static function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'register_settings' ), 2 );
		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 2, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_query_block' ), 10, 3 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ), 20 );

	}

	public static function editor_assets() {

		if ( Screens::is_search_filter_screen() ) {
			// For some reason, using some FSE / block editor themes, the `enqueue_block_editor_assets`
			// hook is called on our screens.
			// If we're on one of our admin screens, then we don't need to load the assets.
			return;
		}

		$asset_file = SEARCH_FILTER_PRO_PATH . 'assets/js/admin/gutenberg.asset.php';
		if ( file_exists( $asset_file ) ) {
			$asset               = require $asset_file;
			$script_dependencies = array_merge( array( 'search-filter-gutenberg' ), $asset['dependencies'] );
			wp_enqueue_script( 'search-filter-pro-gutenberg', Scripts::get_admin_assets_url() . 'js/admin/gutenberg.js', $script_dependencies, $asset['version'], false );
			wp_enqueue_style( 'search-filter-pro-gutenberg', Scripts::get_admin_assets_url() . 'css/admin/gutenberg.css', array( 'search-filter-gutenberg' ), $asset['version'] );
		} else {
			Util::error_log( 'Block Editor script asset file not found: ' . $asset_file, 'error' );
		}
	}

	

	/**
	 * Modify the query block and add a classname if our query is attached.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @param array  $connected_queries    The connected queries.
	 * @return string    The modified block content.
	 */
	public static function render_query_block( $block_content, $block, $connected_queries ) {
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

		// Add a custom class.
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
	 * @param array $attributes    The attributes to update.
	 * @param int   $id            The ID of the query.
	 * @return array    The updated attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {

		$id = $query->get_id();

		// We want `queryContainer` and `queryPaginationSelector` to be set automatically.
		if ( ! isset( $attributes['integrationType'] ) ) {
			return $attributes;
		}

		if ( ! isset( $attributes['queryIntegration'] ) ) {
			return $attributes;
		}
		$query_integration = $attributes['queryIntegration'];

		if ( $query_integration === 'query_block' ) {
			$attributes['queryContainer']          = '.search-filter-query--id-' . $id;
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
			'action'   => 'hide',
			'rules'    => array(
				array(
					'option'  => 'queryIntegration',
					'compare' => '!=',
					'value'   => 'query_block',
				),
			),
		);

		// Get the object for the data_type setting so we can grab its options.
		$query_container = Queries_Settings::get_setting( 'queryContainer' );
		if ( $query_container ) {
			$query_container->add_depends_condition( $depends_conditions );
		}

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
