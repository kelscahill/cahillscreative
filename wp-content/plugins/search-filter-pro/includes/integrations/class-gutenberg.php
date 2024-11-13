<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter_Pro\Integrations;

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
	 * @since 3.0.0
	 */
	public static function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}
		add_action( 'search-filter/settings/register/queries', array( __CLASS__, 'register_settings' ), 2 );
		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 2, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_query_block' ), 10, 3 );
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

		// Check if we have any IDs connected to this query.
		$connected_query_ids = \Search_Filter\Integrations\Gutenberg::get_active_query_ids();
		if ( count( $connected_query_ids ) === 0 ) {
			return $block_content;
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
			// TODO - when we choose WC block, we still don't see the queryContainer option in the list.
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
