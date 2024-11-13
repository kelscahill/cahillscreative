<?php
/**
 * WooCommerce Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Fields\Choice;
use Search_Filter\Integrations;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations\WooCommerce as WooCommerce_Integration;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All WooCommerce integration functionality.
 * Add options to admin UI, integrate with frontend queries and fields.
 */
class WooCommerce {
	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Add WC options to the admin UI.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		/**
		 * Add compatibility with WooCommerce Custom Order Tables (HPOS).
		 *
		 * We don't do anything with the order tables, but without this users cannot use
		 * custom order tables.  The alternative is to remove the WC tested upto version
		 * from the plugin readme.
		 */
		add_action(
			'before_woocommerce_init',
			function() {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SEARCH_FILTER_PRO_BASE_FILE, true );
				}
			}
		);

		// Priority of 2 so we can run before our gutenberg integration which is set to 3.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 2 );

		// We are already inside the `search-filter/integrations/init` hook.
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return;
		}
		// This needs to be updated before the field settings are init.
		add_filter( 'search-filter/integrations/woocommerce/get_field_data_support', array( __CLASS__, 'update_field_data_support' ), 10, 1 );
		add_filter( 'search-filter/integrations/woocommerce/get_data_type_options', array( __CLASS__, 'update_data_type_options' ), 10, 1 );

		add_filter( 'search-filter/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );
	}

	/**
	 * Setup the main hooks for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return;
		}

		// Add WC options to the admin UI.
		self::register_settings();
		self::update_data_setting();

		add_filter( 'search-filter/queries/query/get_attributes', 'Search_Filter_Pro\\Integrations\\WooCommerce::update_query_attributes', 2, 2 );
		// Add div container to the to the shop loop (block version).
		// Need to be before `10` which is when queries/blocks are cleaned up in the free version.
		add_filter( 'render_block', array( __CLASS__, 'render_product_loop_block' ), 9, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_product_collection_block' ), 9, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_product_results_count' ), 10, 2 );

		add_action( 'woocommerce_before_shop_loop', 'Search_Filter_Pro\\Integrations\\WooCommerce::open_results_container', 0 );
		// Priority of 11 to fire after WC pagination, 20 for after other plugins etc.
		add_action( 'woocommerce_after_shop_loop', 'Search_Filter_Pro\\Integrations\\WooCommerce::close_results_container', 20 );

		// Add the results container to the no products found message.
		add_action( 'woocommerce_no_products_found', 'Search_Filter_Pro\\Integrations\\WooCommerce::open_results_container', 0, 1 );
		add_action( 'woocommerce_no_products_found', 'Search_Filter_Pro\\Integrations\\WooCommerce::close_results_container', 20, 1 );

		// Add support WC products shortcode.
		add_action( 'woocommerce_shortcode_before_products_loop', 'Search_Filter_Pro\\Integrations\\WooCommerce::open_shortcode_results_container', 0 );
		add_action( 'woocommerce_shortcode_after_products_loop', 'Search_Filter_Pro\\Integrations\\WooCommerce::close_shortcode_results_container', 20 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', 'Search_Filter_Pro\\Integrations\\WooCommerce::open_shortcode_results_container', 0 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', 'Search_Filter_Pro\\Integrations\\WooCommerce::close_shortcode_results_container', 20 );

		// Add support for Autocomplete field suggestions.
		add_action( 'search-filter-pro/field/search/autocomplete/suggestions', array( __CLASS__, 'get_autocomplete_suggestions' ), 10, 3 );
		// Add support to modify the query args.
		add_filter( 'search-filter/field/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/choice/wp_query_args', array( __CLASS__, 'get_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/range/wp_query_args', array( __CLASS__, 'get_range_wp_query_args' ), 10, 2 );

		add_filter( 'search-filter/field/choice/options', array( __CLASS__, 'add_field_choice_options' ), 10, 2 );
		add_filter( 'search-filter/indexer/sync_field_index/override_values', array( __CLASS__, 'index_values' ), 10, 3 );
		add_filter( 'search-filter/field/range/auto_detect_custom_field', array( __CLASS__, 'auto_detect_custom_field' ), 10, 2 );

		self::add_settings();
		// add_filter( 'search-filter/field/url_name', array( __CLASS__, 'add_custom_field_url_name' ), 10, 2 );
		// add_filter( 'search-filter-pro/field/search/autocomplete/suggestions', array( __CLASS__, 'get_autocomplete_suggestions' ), 10, 3 );
	}

	/**
	 * Update the field data support for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 *
	 * @param array $matrix    The matrix to update.
	 * @return array    The updated matrix.
	 */
	public static function update_field_data_support( $matrix ) {
		$matrix['search'] = array( 'text', 'autocomplete' );
		$matrix['range']  = array( 'slider', 'select', 'radio', 'number' );
		return $matrix;
	}
	/**
	 * Update the field data support for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 *
	 * @param array $matrix    The matrix to update.
	 * @return array    The updated matrix.
	 */
	public static function update_data_type_options( $data_type_options ) {

		// Add option for stock status.
		$data_type_options[] = array(
			'value'     => 'stock_status',
			'label'     => 'Stock Status',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => 'choice',
					),
				),
			),
		);

		// Add option for on sale.
		$data_type_options[] = array(
			'value'     => 'on_sale',
			'label'     => 'On Sale',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => 'choice',
					),
				),
			),
		);

		// Add option for price.
		$data_type_options[] = array(
			'value'     => 'price',
			'label'     => 'Price',
			'dependsOn' => array(
				'relation' => 'OR',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => 'range',
					),
				),
			),
		);

		return $data_type_options;
	}

	/**
	 * Get the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @param    string $input_type    The input type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function get_field_setting_support( $setting_support, $type, $input_type ) {

		// Ordering options to stock status and on sale.
		$meta_ordering_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
			'search' => array( 'autocomplete' ),
		);

		// Build conditions for non taxonomy options.
		if ( isset( $meta_ordering_matrix[ $type ] ) && in_array( $input_type, $meta_ordering_matrix[ $type ], true ) ) {
			// For now, also make it so `inputOptionsOrderDir` only appears when stock status + backorder is set
			// otherwise stock_status and on_sale only have one option anyway.

			// Add the exclude condition.
			$meta_ordering_conditions                = array(
				'option'  => 'dataType',
				'value'   => 'woocommerce',
				'compare' => '!=',
			);
			$setting_support['inputOptionsOrderDir'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'inputOptionsOrderDir', $meta_ordering_conditions, true ),
			);

			// Then add the alternative condition to enable it with WC.
			$meta_ordering_conditions = array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'woocommerce',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						'action'   => 'hide',
						'rules'    => array(
							array(
								'option'  => 'dataWoocommerce',
								'value'   => 'on_sale',
								'compare' => '=',
							),
							array(
								'option'  => 'dataWoocommerce',
								'value'   => 'stock_status',
								'compare' => '=',
							),
						),
					),
				),
			);

			$setting_support['inputOptionsOrderDir'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'inputOptionsOrderDir', $meta_ordering_conditions, false ),
			);

			// Hide from fields for any WC data type.
			$options_order_conditions = array(
				'option'  => 'dataType',
				'value'   => 'woocommerce',
				'compare' => '!=',
			);

			$setting_support['inputOptionsOrder'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'inputOptionsOrder', $options_order_conditions, true ),
			);
		}

		return $setting_support;
	}
	/**
	 * Check if we need to add classes to the product loop block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @param array  $instance         The instance.
	 * @return string    The modified block content.
	 */
	public static function render_product_loop_block( $block_content, $block, $instance = null ) {

		if ( $block['blockName'] !== 'core/query' ) {
			return $block_content;
		}

		if ( ! isset( $block['attrs']['namespace'] ) ) {
			return $block_content;
		}

		if ( $block['attrs']['namespace'] !== 'woocommerce/product-query' && $block['attrs']['namespace'] !== 'woocommerce/product-collection' ) {
			return $block_content;
		}

		global $wp_query;
		// Check to see if we are on the shop page.
		$is_shop = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );

		if ( $is_shop ) {
			// Bail if we're not connected to this query.
			if ( ! isset( $wp_query->query_vars['search_filter_queries'] ) ) {
				return $block_content;
			}
			if ( count( $wp_query->query_vars['search_filter_queries'] ) === 0 ) {
				return $block_content;
			}
			$connected_queries = $wp_query->query_vars['search_filter_queries'];
			$classes           = array();
			foreach ( $connected_queries as $connected_query ) {
				$classes[] = 'search-filter-query--id-' . $connected_query->get_id();
			}
			$block_content = self::add_classes_to_query_block( $block_content, $classes );
			return $block_content;
		}

		// We are not on the shop page, so this is a product loop block on a single page/post.
		// Check if we have any IDs connected to this query.
		$connected_query_ids = \Search_Filter\Integrations\Gutenberg::get_active_query_ids();
		if ( count( $connected_query_ids ) === 0 ) {
			return $block_content;
		}
		$classes = array();
		foreach ( $connected_query_ids as $connected_query_id ) {
			$classes[] = 'search-filter-query--id-' . $connected_query_id;
		}
		$block_content = self::add_classes_to_query_block( $block_content, $classes );

		return $block_content;
	}
	/**
	 * Check if we need to add classes to the product loop block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @param array  $instance         The instance.
	 * @return string    The modified block content.
	 */
	public static function render_product_collection_block( $block_content, $block, $instance = null ) {

		if ( $block['blockName'] !== 'woocommerce/product-collection' ) {
			return $block_content;
		}
		global $wp_query;
		// Check to see if we are on the shop page.
		$is_shop = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );

		if ( $is_shop ) {
			// Bail if we're not connected to this query.
			if ( ! isset( $wp_query->query_vars['search_filter_queries'] ) ) {
				return $block_content;
			}
			if ( count( $wp_query->query_vars['search_filter_queries'] ) === 0 ) {
				return $block_content;
			}
			$connected_queries = $wp_query->query_vars['search_filter_queries'];
			$classes           = array();
			foreach ( $connected_queries as $connected_query ) {
				$classes[] = 'search-filter-query--id-' . $connected_query->get_id();
			}
			$block_content = self::add_classes_to_query_block( $block_content, $classes );
			return $block_content;
		}

		// We are not on the shop page, so this is a product loop block on a single page/post.
		// Check if we have any IDs connected to this query.
		$connected_query_ids = \Search_Filter\Integrations\Gutenberg::get_active_query_ids();
		if ( count( $connected_query_ids ) === 0 ) {
			return $block_content;
		}
		$classes = array();
		foreach ( $connected_query_ids as $connected_query_id ) {
			$classes[] = 'search-filter-query--id-' . $connected_query_id;
		}
		$block_content = self::add_classes_to_query_block( $block_content, $classes );

		return $block_content;
	}

	/**
	 * Add classes to the query block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $class_names      The class names to add.
	 * @return string    The modified block content.
	 */
	private static function add_classes_to_query_block( $block_content, $class_names ) {
		$content = new \WP_HTML_Tag_Processor( $block_content );
		$content->next_tag( array( 'div' ) );
		// Add query classes.
		$content->add_class( 'search-filter-query' );
		foreach ( $class_names as $class_name ) {
			$content->add_class( $class_name );
		}
		// Save the updated block content.
		$block_content = (string) $content;
		return $block_content;
	}

	/**
	 * Check if we need to add classes to the product results count block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content    The block content.
	 * @param array  $block            The block.
	 * @param array  $instance         The instance.
	 * @return string    The modified block content.
	 */
	public static function render_product_results_count( $block_content, $block, $instance = null ) {

		if ( $block['blockName'] !== 'woocommerce/product-results-count' ) {
			return $block_content;
		}

		global $wp_query;

		// Bail if we're not connected to this query.
		if ( ! isset( $wp_query->query_vars['search_filter_queries'] ) ) {
			return $block_content;
		}
		if ( count( $wp_query->query_vars['search_filter_queries'] ) === 0 ) {
			return $block_content;
		}

		// Check to see if we are on the shop page.
		$is_shop = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );
		if ( $is_shop && isset( $wp_query->query_vars['search_filter_queries'] ) ) {
			$connected_queries = $wp_query->query_vars['search_filter_queries'];
			$content           = new \WP_HTML_Tag_Processor( $block_content );
			$content->next_tag( array( 'div' ) );
			// Add query section class.
			$content->add_class( 'search-filter-query-section' );
			foreach ( $connected_queries as $connected_query ) {
				$content->add_class( 'search-filter-query-section--id-' . $connected_query->get_id() );
			}
			// Save the updated block content.
			$block_content = (string) $content;
			return $block_content;
		}
		return $block_content;
	}

	/**
	 * Update the query attributes automatically so the user doesn't have to.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes    The attributes to update.
	 * @param int   $id            The ID of the query.
	 * @return array    The updated attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {
		$id = $query->get_id();
		// We want to set `queryContainer` and `queryPaginationSelector` automatically.
		if ( ! isset( $attributes['integrationType'] ) ) {
			return $attributes;
		}
		$integration_type = $attributes['integrationType'];

		if ( ! isset( $attributes['queryIntegration'] ) ) {
			return $attributes;
		}
		$query_integration = $attributes['queryIntegration'];

		if ( $query_integration === 'woocommerce/products_query_block' ) {

			$attributes['queryContainer']          = '.search-filter-query--id-' . absint( $id );
			$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . absint( $id ) . ' .wp-block-query-pagination a';

		} elseif ( $query_integration === 'woocommerce/products_shortcode' ) {

			$attributes['queryContainer']          = '.search-filter-query--id-' . absint( $id );
			$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . absint( $id ) . ' .woocommerce-pagination a';

		} elseif ( $integration_type === 'woocommerce/shop' ) {

			/**
			 * Searching with ajax works on shop, using both methods (block or classic theme)
			 * But not when using a block theme, editing the shop template, and choosing
			 * "Revert to Classic Product Template".
			 */
			if ( \wp_is_block_theme() ) {
				/**
				 * Partially working, some issues:
				 * - If we start on a page with no results and reset the form, the layout is broken because the CSS is not loaded,
				 *   there is strategy for this in the Elementor plugin.
				 *
				 * // TODO - we should only do this (and other related logic) if dynamic update is actually enabled.
				 */
				$query_class                  = '.search-filter-query--id-' . absint( $id );
				$attributes['queryContainer'] = '.woocommerce-shop ' . $query_class;
				// TODO - we need to change this for the product collection block.
				$attributes['queryPaginationSelector']   = '.woocommerce-shop ' . $query_class . ' .wp-block-query-pagination a';
				$attributes['additionalDynamicSections'] = '.search-filter-query-section--id-' . absint( $id );

			} else {
				$attributes['queryContainer']          = '.woocommerce-shop .search-filter-query';
				$attributes['queryPaginationSelector'] = '.woocommerce-shop search-filter-query .woocommerce-pagination a';
			}
		}
		return $attributes;
	}

	/**
	 * Register the settings for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		$depends_conditions = array(
			'relation' => 'AND',
			'action'   => 'hide',
			'rules'    => array(
				// Don't show setting if integration type is set to woocommerce/products_query_block or shortcode.
				array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'queryIntegration',
							'compare' => '!=',
							'value'   => 'woocommerce/products_query_block',
						),
						array(
							'option'  => 'queryIntegration',
							'compare' => '!=',
							'value'   => 'woocommerce/products_shortcode',
						),
					),
				),
				// Don't show setting if integration type is set to woocommerce/shop.
				array(
					'option'  => 'integrationType',
					'compare' => '!=',
					'value'   => 'woocommerce/shop',
				),
			),
		);

		$query_container = Queries_Settings::get_setting( 'queryContainer' );
		if ( $query_container ) {
			$query_container->add_depends_condition( $depends_conditions );
		}

		$pagination_selector = Queries_Settings::get_setting( 'queryPaginationSelector' );
		if ( $pagination_selector ) {
			$pagination_selector->add_depends_condition( $depends_conditions );
		}
	}

	/**
	 * Update the data setting for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 */
	public static function update_data_setting() {
		$data_wc_setting = Fields_Settings::get_setting( 'dataWoocommerce' );
		if ( ! $data_wc_setting ) {
			return;
		}

		$setting_data = $data_wc_setting->get_data();
		if ( ! isset( $setting_data['context'] ) ) {
			return;
		}

		$setting_data['context'][] = 'block/field/search';
		$setting_data['context'][] = 'admin/field/search';
		$setting_data['context'][] = 'block/field/choice';
		$setting_data['context'][] = 'admin/field/choice';
		$setting_data['context'][] = 'block/field/range';
		$setting_data['context'][] = 'admin/field/range';
		$setting_data['context'][] = 'block/field/advanced';
		$setting_data['context'][] = 'admin/field/advanced';

		// Enable dependant options for the "dataPostAttribute" setting.
		if ( ! isset( $setting_data['supports'] ) ) {
			$setting_data['supports'] = array();
		}
		$setting_data['supports']['dependantOptions'] = true;

		$data_wc_setting->update( $setting_data );
	}

	/**
	 * Wrap a div container around the results in non block themes.
	 *
	 * @since 3.0.0
	 */
	public static function open_results_container() {
		if ( \wp_is_block_theme() ) {
			return;
		}
		echo '<div class="search-filter-query">';
	}

	/**
	 * Close the results container in non block themes.
	 *
	 * @since 3.0.0
	 */
	public static function close_results_container() {
		if ( \wp_is_block_theme() ) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Open the shortcode results container.
	 *
	 * @since 3.0.0
	 *
	 * @param array $atts    The attributes.
	 */
	public static function open_shortcode_results_container( $atts ) {
		if ( WooCommerce_Integration::get_active_query_id() === 0 ) {
			return;
		}
		echo '<div class="search-filter-query search-filter-query--id-' . absint( WooCommerce_Integration::get_active_query_id() ) . '">';
	}

	/**
	 * Close the shortcode results container.
	 *
	 * @since 3.0.0
	 *
	 * @param array $atts    The attributes.
	 */
	public static function close_shortcode_results_container( $atts ) {
		if ( WooCommerce_Integration::get_active_query_id() === 0 ) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Get the autocomplete suggestions for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $suggestions    The suggestions to get the autocomplete suggestions for.
	 * @param string $search_term    The search term.
	 * @param Field  $field    The field.
	 * @return array    The autocomplete suggestions.
	 */
	public static function get_autocomplete_suggestions( $suggestions, $search_term, $field ) {
		if ( ! self::wc_enabled() ) {
			return $suggestions;
		}
		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $suggestions;
		}

		$data_wc = $field->get_attribute( 'dataWoocommerce' );

		if ( ! $data_wc || $data_wc === '' ) {
			return $suggestions;
		}

		// TODO...
		/*
		 if ( $data_wc === 'stock_status' ) {
			Choice::add_option_to_array(
				$suggestions,
				array(
					'value' => 'instock',
					'label' => __( 'In stock', 'search-filter-pro' ),
				),
				$field->get_id()
			);
			$show_on_backorder = $field->get_attribute( 'dataWoocommerceShowOnBackorder' );
			if ( $show_on_backorder === 'yes' ) {
				Choice::add_option_to_array(
					$suggestions,
					array(
						'value' => 'on_backorder',
						'label' => __( 'On backorder', 'search-filter-pro' ),
					),
					$field->get_id()
				);
			}

			return $suggestions;

		} else if ( $data_wc === 'on_sale' ) {
			Choice::add_option_to_array(
				$suggestions,
				array(
					'value' => 'on_sale',
					'label' => __( 'On sale', 'search-filter-pro' ),
				),
				$field->get_id()
			);

			return $suggestions;
		} */

		// Then we're dealing with a taxonomy.
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $data_wc );

		if ( $taxonomy_name === '' ) {
			return $suggestions;
		}

		$terms = $field->search_taxonomy_term_labels( $search_term, $taxonomy_name, 'name' );

		return $terms;
	}
	/**
	 * Check if WooCommerce is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if WooCommerce is enabled.
	 */
	public static function wc_enabled() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Get the WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the WP query args for.
	 * @param Field $field    The field.
	 * @return array    The WP query args.
	 */
	public static function get_search_wp_query_args( $query_args, $field ) {

		if ( ! self::wc_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $query_args;
		}

		$data_wc = $field->get_attribute( 'dataWoocommerce' );

		if ( ! $data_wc || $data_wc === '' ) {
			return $query_args;
		}

		$search_term = $field->get_value();
		if ( $search_term === '' ) {
			return $query_args;
		}
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $data_wc );

		if ( $taxonomy_name === '' ) {
			return $query_args;
		}

		$taxonomy_terms = $field->search_taxonomy_term_labels( $search_term, $taxonomy_name, 'slug' );

		if ( ! isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array();
		}
		if ( ! empty( $taxonomy_terms ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy_name,
				'field'    => 'slug',
				'terms'    => $taxonomy_terms,
			);
		} else {
			$query_args = $field->add_fail_query_args( $query_args );
		}

		return $query_args;
	}
	/**
	 * Get the WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the WP query args for.
	 * @param Field $field    The field.
	 * @return array    The WP query args.
	 */
	public static function get_choice_wp_query_args( $query_args, $field ) {

		if ( ! self::wc_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $query_args;
		}

		$data_wc = $field->get_attribute( 'dataWoocommerce' );

		if ( ! $data_wc || $data_wc === '' ) {
			return $query_args;
		}

		if ( empty( $field->get_values() ) ) {
			return $query_args;
		}

		if ( $data_wc === 'stock_status' ) {

			$values = $field->get_values();

			$show_on_backorder = $field->get_attribute( 'dataWoocommerceShowOnBackorder' ) === 'yes';

			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			foreach ( $values as $value ) {

				if ( $value === 'backorder' && $show_on_backorder ) {
					$query_args['meta_query'][] = array(
						array(
							'key'   => '_backorders',
							'value' => 'yes',
						),
					);
				} else {
					// TODO - support custom stock status...
					if ( $value === 'in-stock' ) {
						$query_args['meta_query'][] = array(
							array(
								'key'   => '_stock_status',
								'value' => 'instock',
							),
						);
					}
				}
			}

			return $query_args;

		} elseif ( $data_wc === 'on_sale' ) {

			$value = $field->get_value();
			if ( $value === 'on-sale' ) {
				$products_on_sale = \wc_get_product_ids_on_sale();
				$has_post__in     = isset( $query_args['post__in'] );
				if ( ! $has_post__in ) {
					$query_args['post__in'] = $products_on_sale;
				} else {
					$query_args['post__in'] = array_splice( $query_args['post__in'], $products_on_sale );
				}
			}

			return $query_args;
		} elseif ( $data_wc === 'price' ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			$price_range = $field->get_values();

		}

		// It must be a tax product attribute, product_cat or product_tag, which is already handled in
		// the free version.
		return $query_args;
	}
	/**
	 * Get the WP query range args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the WP query args for.
	 * @param Field $field    The field.
	 * @return array    The WP query args.
	 */
	public static function get_range_wp_query_args( $query_args, $field ) {

		if ( ! self::wc_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $query_args;
		}

		$data_wc = $field->get_attribute( 'dataWoocommerce' );

		if ( empty( $data_wc ) ) {
			return $query_args;
		}

		if ( empty( $field->get_values() ) ) {
			return $query_args;
		}

		$values = $field->get_values();

		$from = $values[0];
		$to   = $values[1];

		$number_of_values = 0;

		if ( $from !== '' ) {
			$number_of_values++;
		}

		if ( $to !== '' ) {
			$number_of_values++;
		}

		if ( $number_of_values === 0 ) {
			return $query_args;
		}

		if ( $data_wc === 'price' ) {

			$custom_field_key = '_price';
			$decimal_places   = $field->get_attribute( 'rangeDecimalPlaces' );

			if ( $custom_field_key ) {

				if ( ! isset( $query_args['meta_query'] ) ) {
					$query_args['meta_query'] = array();
				}

				// If there is no relation add it.
				if ( ! isset( $query_args['meta_query']['relation'] ) ) {
					$query_args['meta_query']['relation'] = 'AND';
				}
				if ( $number_of_values === 2 ) {
					// If we have both min and max values, we can use BETWEEN.
					$query_args['meta_query'][] = array(
						'key'     => sanitize_text_field( $custom_field_key ),
						'value'   => array( sanitize_text_field( $from ), sanitize_text_field( $to ) ),
						'compare' => 'BETWEEN',
						'type'    => 'DECIMAL(12,' . absint( $decimal_places ) . ')',
					);
				} else {
					if ( $from !== '' ) {
						// If we have only a min value, we can use greater than or equal to.
						$query_args['meta_query'][] = array(
							'key'     => sanitize_text_field( $custom_field_key ),
							'value'   => sanitize_text_field( $from ),
							'compare' => '>=',
							'type'    => 'DECIMAL(12,' . absint( $decimal_places ) . ')',
						);
					} elseif ( $to !== '' ) {
						// If we have only a max value, we can use less than or equal to.
						$query_args['meta_query'][] = array(
							'key'     => sanitize_text_field( $custom_field_key ),
							'value'   => sanitize_text_field( $to ),
							'compare' => '<=',
							'type'    => 'DECIMAL(12,' . absint( $decimal_places ) . ')',
						);
					}
				}
			}
		}
		return $query_args;
	}

	/**
	 * Add the field choice options for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options    The options to add the field choice options for.
	 * @param Field $field    The field.
	 * @return array    The updated options.
	 */
	public static function add_field_choice_options( $options, $field ) {
		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $options;
		}

		if ( count( $options ) > 0 ) {
			return $options;
		}

		$wc_data_type = $field->get_attribute( 'dataWoocommerce' );
		if ( ! $wc_data_type ) {
			return $options;
		}

		// Build options for On Sale, Price, and Stock status.
		if ( $wc_data_type === 'on_sale' ) {
			Choice::add_option_to_array(
				$options,
				array(
					'value' => 'on-sale',
					'label' => __( 'On sale', 'search-filter-pro' ),
				),
				$field->get_id()
			);

		} elseif ( $wc_data_type === 'stock_status' ) {

			Choice::add_option_to_array(
				$options,
				array(
					'value' => 'in-stock',
					'label' => __( 'In stock', 'search-filter-pro' ),
				),
				$field->get_id()
			);
			$show_on_backorder = $field->get_attribute( 'dataWoocommerceShowOnBackorder' );
			if ( $show_on_backorder === 'yes' ) {
				Choice::add_option_to_array(
					$options,
					array(
						'value' => 'backorder',
						'label' => __( 'On backorder', 'search-filter-pro' ),
					),
					$field->get_id()
				);
			}
		}

		$order           = $field->get_attribute( 'inputOptionsOrder' ) ? $field->get_attribute( 'inputOptionsOrder' ) : 'label';
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
		if ( $order === 'label' ) {
			$options = Util::sort_assoc_array_by_property( $options, $order, 'alphabetical', $order_direction );
		} elseif ( $order === 'count' ) {
			$options = Util::sort_assoc_array_by_property( $options, 'count', 'numerical', $order_direction );
		}

		return $options;
	}

	private static function add_settings() {

		$setting = array(
			'name'      => 'dataWoocommerceShowOnBackorder',
			'label'     => __( 'Show on backorder', 'search-filter' ),
			'group'     => 'data',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter-pro' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter-pro' ),
				),
			),
			'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'woocommerce',
					),
					array(
						'option'  => 'dataWoocommerce',
						'compare' => '=',
						'value'   => 'stock_status',
					),
				),
			),
			'supports'  => array(
				'previewAPI' => true,
			),
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'dataWoocommerce',
			),
		);
		Fields_Settings::add_setting( $setting, $setting_args );
	}

	/**
	 * Override the index values and add WC values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $values    The values to index.
	 * @param    Field $field    The field to get the values for.
	 * @param    int   $object_id    The object ID to get the values for.
	 * @return   array    The values to index.
	 */
	public static function index_values( $values, $field, $object_id ) {
		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' ) {
			return $values;
		}

		$wc_data = $field->get_attribute( 'dataWoocommerce' );

		$product = \wc_get_product( $object_id );
		if ( ! $product ) {
			return $values;
		}

		if ( $wc_data === 'stock_status' ) {
			$values = array();
			if ( $product->is_in_stock() ) {
				$values[] = 'in-stock';
			}

			// TODO - support custom stock status by using $product->get_stock_status().

			// Add backorder if set, regardless if the setting is set to show it (to avoid rebuilds).
			if ( $product->is_on_backorder() ) {
				$values[] = 'backorder';
			}

			return $values;
		} elseif ( $wc_data === 'on_sale' ) {
			if ( $product->is_on_sale() ) {
				$values[] = 'on-sale';
			}
		} elseif ( $wc_data === 'price' ) {
			return array( $product->get_price() );
		} elseif ( $wc_data === 'product_cat' ) {
			$product_category_ids = $product->get_category_ids();
			$values               = array();
			foreach ( $product_category_ids as $product_category_id ) {
				$product_category = get_term( $product_category_id, 'product_cat' );
				if ( ! $product_category || is_wp_error( $product_category ) ) {
					continue;
				}
				$values[] = $product_category->slug;
			}
			return $values;
		} elseif ( $wc_data === 'product_tag' ) {
			$product_tag_ids = $product->get_tag_ids();
			$values          = array();
			foreach ( $product_tag_ids as $product_tag_id ) {
				$product_tag = get_term( $product_tag_id, 'product_tag' );
				if ( ! $product_tag || is_wp_error( $product_tag ) ) {
					continue;
				}
				$values[] = $product_tag->slug;
			}
			return $values;
		} else {
			// Then we are dealing with a product attribute or category or tag.
			// This should include the pa_ prefix.
			$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $wc_data );
			if ( empty( $taxonomy_name ) ) {
				return $values;
			}

			$attributes = $product->get_attributes();

			foreach ( $attributes as $attribute ) {
				if ( ! $attribute->is_taxonomy() ) {
					continue;
				}
				$attribute_taxonomy_name = $attribute->get_taxonomy();
				if ( $attribute_taxonomy_name === $taxonomy_name ) {
					$values = $attribute->get_slugs();
					return $values;
				}
			}
		}

		return $values;
	}
	/**
	 * Get the custom field key for the range field when using price.
	 *
	 * @since 3.0.0
	 *
	 * @param string $custom_field_key    The custom field key.
	 * @param Field  $field    The field.
	 * @return string    The custom field key.
	 */
	public static function auto_detect_custom_field( $custom_field_key, $attributes ) {
		if ( ! isset( $attributes['dataType'] ) ) {
			return $custom_field_key;
		}
		if ( $attributes['dataType'] !== 'woocommerce' ) {
			return $custom_field_key;
		}
		if ( ! isset( $attributes['dataWoocommerce'] ) ) {
			return $custom_field_key;
		}
		if ( $attributes['dataWoocommerce'] !== 'price' ) {
			return $custom_field_key;
		}

		return '_price';
	}
}
