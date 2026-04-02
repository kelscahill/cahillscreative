<?php
/**
 * WooCommerce Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Core\Data_Store;
use Search_Filter\Fields\Choice;
use Search_Filter\Integrations;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations\Woocommerce as WooCommerce_Integration;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Util;
use Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct as Bitmap_Index_Query_Direct;
use Search_Filter_Pro\Indexer\Table_Validator;
use Search_Filter_Pro\Integrations\Woocommerce\Indexer;
use Search_Filter_Pro\Integrations\Woocommerce\Search_Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All WooCommerce integration functionality.
 * Add options to admin UI, integrate with frontend queries and fields.
 */
class Woocommerce {
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

		// Priority of 2 so we can run before our gutenberg integration which is set to 3.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 2 );

		// Listen for integration disable to init or clean up parent_map table.
		add_action( 'search-filter/integrations/disable', array( __CLASS__, 'on_integration_disable' ), 10, 1 );
		add_action( 'search-filter/integrations/enable', array( __CLASS__, 'on_integration_enable' ), 10, 1 );

		// We are already inside the `search-filter/settings/integrations/init` hook.
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return;
		}
		// This needs to be updated before the field settings are init.
		add_filter( 'search-filter/integrations/woocommerce/get_data_type_options', array( __CLASS__, 'update_data_type_options' ), 10, 1 );

		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 11, 3 );
	}

	/**
	 * Handle integration enable/disable event.
	 *
	 * Cleans up the parent_map table when WooCommerce integration is disabled.
	 *
	 * @since 3.2.0
	 *
	 * @param string $integration The integration being disabled.
	 */
	public static function on_integration_disable( $integration ) {
		if ( $integration !== 'woocommerce' ) {
			return;
		}

		// Trigger a validation of the parent map tables.
		Table_Validator::needs_revalidating( true );
	}
	/**
	 * Handle integration enable/disable event.
	 *
	 * Cleans up the parent_map table when WooCommerce integration is disabled.
	 *
	 * @since 3.2.0
	 *
	 * @param string $integration The integration being disabled.
	 */
	public static function on_integration_enable( $integration ) {
		if ( $integration !== 'woocommerce' ) {
			return;
		}

		/*
		 * When the integration is enabled, setup() was already skipped due to
		 * Features::is_enabled() check, which means our
		 * `search-filter-pro/indexer/parent_map/should_use` never gets added.
		 *
		 * Call setup here to add the filter.
		 */
		self::setup();

		// Trigger a validation of the parent map tables.
		Table_Validator::needs_revalidating( true );
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

		// Handle indexing.
		Indexer::init();
		Search_Indexer::init();

		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 2, 2 );
		// Add div container to the to the shop loop (block version).
		// Need to be before `10` which is when queries/blocks are cleaned up in the free version.
		add_filter( 'render_block', array( __CLASS__, 'render_product_loop_block' ), 9, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_product_collection_block' ), 9, 2 );
		add_filter( 'render_block', array( __CLASS__, 'render_product_results_count' ), 10, 2 );

		// Add the results container class on the shop page when using non block editor themes.
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'open_classic_shop_results_container' ), 0 );
		// Priority of 11 to fire after WC pagination, 20 for after other plugins etc.
		add_action( 'woocommerce_after_shop_loop', array( __CLASS__, 'close_classic_shop_results_container' ), 20 );

		// Add the results container to the no products found message.
		add_action( 'woocommerce_no_products_found', array( __CLASS__, 'open_classic_shop_results_container' ), 0, 1 );
		add_action( 'woocommerce_no_products_found', array( __CLASS__, 'close_classic_shop_results_container' ), 20, 1 );

		// Add support WC products shortcode.
		add_action( 'woocommerce_shortcode_before_products_loop', array( __CLASS__, 'open_shortcode_results_container' ), 0 );
		add_action( 'woocommerce_shortcode_after_products_loop', array( __CLASS__, 'close_shortcode_results_container' ), 20 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', array( __CLASS__, 'open_shortcode_results_container' ), 0 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', array( __CLASS__, 'close_shortcode_results_container' ), 20 );
		add_filter( 'shortcode_atts_products', array( __CLASS__, 'shortcode_container_attributes' ), 11, 3 );

		// Add support for Autocomplete field suggestions.
		add_filter( 'search-filter-pro/field/search/autocomplete/suggestions', array( __CLASS__, 'get_autocomplete_suggestions' ), 10, 3 );
		// Add support to modify the query args.
		add_filter( 'search-filter/fields/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/wp_query_args', array( __CLASS__, 'get_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/range/wp_query_args', array( __CLASS__, 'get_range_wp_query_args' ), 10, 2 );

		add_filter( 'search-filter/fields/choice/options', array( __CLASS__, 'add_field_choice_options' ), 10, 2 );

		// Add default URL names for price, on sale, and stock status.
		add_filter( 'search-filter/fields/field/url_name', array( __CLASS__, 'update_field_url_name' ), 10, 2 );

				add_filter( 'search-filter-pro/fields/get_default_value', array( __CLASS__, 'get_field_default_value' ), 10, 2 );

		self::add_settings();
	}
	/**
	 * Set the URL name for the field.
	 *
	 * @since    3.0.0
	 *
	 * @param string $url_name The existing URL name.
	 * @param Field  $field    The field instance.
	 *
	 * @return string The updated URL name.
	 */
	public static function update_field_url_name( $url_name, $field ) {
		$data_type = $field->get_attribute( 'dataType' );

		if ( $data_type !== 'woocommerce' ) {
			return $url_name;
		}

		$data_source = $field->get_attribute( 'dataWoocommerce' );

		if ( ! $data_source ) {
			return $url_name;
		}
		if ( $data_source === 'price' ) {
			return 'price';
		} elseif ( $data_source === 'stock_status' ) {
			return 'stock_status';
		} elseif ( $data_source === 'on_sale' ) {
			return 'on_sale';
		} elseif ( $data_source === 'sku' ) {
			return 'sku';
		} elseif ( $data_source === 'guid' ) {
			return 'guid';
		} elseif ( in_array( $data_source, array( 'weight', 'dimensions', 'length', 'width', 'height' ), true ) ) {
			return $data_source;
		}

		return $url_name;
	}
	/**
	 * Updates the auto detected default value for a field
	 *
	 * For now, only detects choice fields with a WC taxonomy.
	 *
	 * @since    3.0.0
	 *
	 * @param string|int|null $value The existing default value.
	 * @param Field           $field    The field instance.
	 *
	 * @return string|int|null The updated default value.
	 */
	public static function get_field_default_value( $value, $field ) {
		if ( $value !== null ) {
			return $value;
		}

		// Only set for choice fields.
		$field_type = $field->get_attribute( 'type' );
		if ( $field_type !== 'choice' ) {
			return $value;
		}

		// Check WC data type.
		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'woocommerce' ) {
			return $value;
		}

		$data_source = $field->get_attribute( 'dataWoocommerce' );
		if ( ! $data_source ) {
			return $value;
		}

		if ( ! is_archive() ) {
			return $value;
		}

		// Try to get the taxonomy name.
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $data_source );
		if ( $taxonomy_name === '' ) {
			return $value;
		}

		// Bail if this field not configured to use a default value.
		if ( $field->get_attribute( 'defaultValueType' ) !== 'inherit' ) {
			return $value;
		}
		if ( $field->get_attribute( 'defaultValueInheritArchive' ) !== 'yes' ) {
			return $value;
		}

		// Bail if not a WC archive.
		if ( ! WooCommerce_Integration::is_taxonomy_archive() ) {
			return $value;
		}

		// Get the queried object.
		$queried_object = get_queried_object();

		if ( ! is_a( $queried_object, 'WP_Term' ) ) {
			return $value;
		}

		if ( $taxonomy_name !== $queried_object->taxonomy ) {
			return null;
		}

		return $queried_object->slug;
	}

	/**
	 * Update the field data support for the WooCommerce integration.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data_type_options    The data type options to update.
	 * @return array    The updated data type options.
	 */
	public static function update_data_type_options( $data_type_options ) {

		// Add option for stock status.
		$data_type_options[] = array(
			'value'     => 'custom_attribute',
			'label'     => 'Custom Attribute',
			'dependsOn' => array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '=',
				'value'   => 'yes',
			),
		);
		// Add option for SKU.
		$data_type_options[] = array(
			'value' => 'sku',
			'label' => 'SKU',
		);
		// Add option for Global UID (GTIN/UPC/EAN/ISBN).
		$data_type_options[] = array(
			'value' => 'guid',
			'label' => 'Global UID - GTIN/UPC/EAN/ISBN',
		);
		// Add option for stock status.
		$data_type_options[] = array(
			'value' => 'stock_status',
			'label' => 'Stock Status',
		);

		// Add option for on sale.
		$data_type_options[] = array(
			'value' => 'on_sale',
			'label' => 'On Sale',
		);

		// Add option for price.
		$data_type_options[] = array(
			'value' => 'price',
			'label' => 'Price',
		);

		// Add options for weight and dimensions.
		$data_type_options[] = array(
			'value' => 'weight',
			'label' => 'Weight',
		);
		$data_type_options[] = array(
			'value'     => 'dimensions',
			'label'     => 'Dimensions',
			'dependsOn' => array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '=',
				'value'   => 'yes',
			),
		);
		$data_type_options[] = array(
			'value' => 'length',
			'label' => 'Dimension: Length',
		);
		$data_type_options[] = array(
			'value' => 'width',
			'label' => 'Dimension: Width',
		);
		$data_type_options[] = array(
			'value' => 'height',
			'label' => 'Dimension: Height',
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

		// Add data type support to Pro fields.

		if ( $type === 'search' ) {

			// Add support for the WC data type.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataType', array( 'woocommerce' => true ) );

			// Allow searching of WC taxonomies.
			$wc_taxonomies_values   = WooCommerce_Integration::get_taxonomies_options_values();
			$setting_support_values = array();
			foreach ( $wc_taxonomies_values as $taxonomy_value ) {
				$setting_support_values[ $taxonomy_value ] = true;
			}
			// Allow searching by SKU.
			$setting_support_values['sku'] = true;
			// Allow searching by Global UID.
			$setting_support_values['guid'] = true;
			$setting_support                = Field::add_setting_support_value( $setting_support, 'dataWoocommerce', $setting_support_values );
		}

		if ( $type === 'range' ) {
			// Add support for the WC data type.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataType', array( 'woocommerce' => true ) );

			$setting_support_values                            = array(
				'price'            => true,
				'weight'           => true,
				'length'           => true,
				'width'            => true,
				'height'           => true,
				'custom_attribute' => true,
			);
			$setting_support                                   = Field::add_setting_support_value( $setting_support, 'dataWoocommerce', $setting_support_values );
			$setting_support['dataWoocommerceCustomAttribute'] = true;

		}
		if ( $type === 'choice' ) {
			$setting_support_values = array(
				'price'            => true,
				'stock_status'     => true,
				'on_sale'          => true,
				'weight'           => true,
				'dimensions'       => true,
				'length'           => true,
				'width'            => true,
				'height'           => true,
				'custom_attribute' => true,
			);
			$setting_support        = Field::add_setting_support_value( $setting_support, 'dataWoocommerce', $setting_support_values );

			$setting_support['dataWoocommerceCustomAttribute'] = true;
			$setting_support['dataWoocommerceShowOutOfStock']  = true;
			$setting_support['dataWoocommerceShowOnBackorder'] = true;
			$setting_support['dataWoocommerceShowOutOfStock']  = true;
		}

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
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'woocommerce',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
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
	 * @return string    The modified block content.
	 */
	public static function render_product_loop_block( $block_content, $block ) {

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
		$is_shop             = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );
		$is_taxonomy_archive = \Search_Filter\Integrations\Woocommerce::is_taxonomy_archive();

		if ( $is_shop || $is_taxonomy_archive ) {
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
	 * @return string    The modified block content.
	 */
	public static function render_product_collection_block( $block_content, $block ) {

		if ( $block['blockName'] !== 'woocommerce/product-collection' ) {
			return $block_content;
		}
		global $wp_query;
		// Check to see if we are on the shop page.
		$is_shop             = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );
		$is_taxonomy_archive = \Search_Filter\Integrations\Woocommerce::is_taxonomy_archive();

		if ( $is_shop || $is_taxonomy_archive ) {
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
	 * @return string    The modified block content.
	 */
	public static function render_product_results_count( $block_content, $block ) {

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
		$is_shop             = \Search_Filter\Integrations\Woocommerce::is_shop( $wp_query );
		$is_taxonomy_archive = \Search_Filter\Integrations\Woocommerce::is_taxonomy_archive();

		if ( ( $is_shop || $is_taxonomy_archive ) && isset( $wp_query->query_vars['search_filter_queries'] ) ) {
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
	 * @param array  $attributes    The attributes to update.
	 * @param object $query         The query object.
	 * @return array    The updated attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {
		$id = $query->get_id();

		// We want to set `queryContainer` and `queryPaginationSelector` automatically.
		if ( ! isset( $attributes['integrationType'] ) ) {
			return $attributes;
		}

		$integration_type = $attributes['integrationType'];

		$query_integration = '';
		if ( isset( $attributes['queryIntegration'] ) ) {
			$query_integration = $attributes['queryIntegration'];
		}

		if ( $query_integration === 'woocommerce/products_query_block' ) {

			$attributes['queryContainer']          = '.search-filter-query--id-' . absint( $id );
			$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . absint( $id ) . ' .wp-block-query-pagination a';
			$attributes['queryPostsContainer']     = '.search-filter-query--id-' . absint( $id ) . ' .wc-block-product-template';

		} elseif ( $query_integration === 'woocommerce/products_shortcode' ) {

			$attributes['queryContainer']          = '.search-filter-query--id-' . absint( $id );
			$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . absint( $id ) . ' .woocommerce-pagination a';
			$attributes['queryPostsContainer']     = '.search-filter-query--id-' . absint( $id ) . ' .products';

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
				 * TODO - we should only do this (and other related logic) if dynamic update is actually enabled.
				 */
				$query_class                  = '.search-filter-query--id-' . absint( $id );
				$attributes['queryContainer'] = $query_class;
				// TODO - we need to change this for the product collection block.
				$attributes['queryPaginationSelector']   = $query_class . ' .wp-block-query-pagination a';
				$attributes['queryPostsContainer']       = '.search-filter-query--id-' . absint( $id ) . ' .wc-block-product-template';
				$attributes['additionalDynamicSections'] = '.search-filter-query-section--id-' . absint( $id );

			} else {

				$query_class                           = '.search-filter-query--id-' . absint( $id );
				$attributes['queryContainer']          = $query_class;
				$attributes['queryPaginationSelector'] = $query_class . ' .woocommerce-pagination a';
				$attributes['queryPostsContainer']     = '.search-filter-query--id-' . absint( $id ) . ' .products';
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
			'rules'    => array(
				// Don't show setting if integration type is set to woocommerce/products_query_block or shortcode.
				array(
					'relation' => 'AND',
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

		$autodetect_depends_conditions = array(
			'option'  => 'queryIntegration',
			'compare' => '=',
			'value'   => 'woocommerce/products_query_block',
		);
		// Support auto-detecting the query.
		$query_loop_autodetect = Queries_Settings::get_setting( 'queryLoopAutodetect' );
		if ( $query_loop_autodetect ) {
			$query_loop_autodetect->add_depends_condition( $autodetect_depends_conditions );
		}
	}

	/**
	 * Wrap a div container around the results in non block themes.
	 *
	 * @since 3.0.0w
	 */
	public static function open_classic_shop_results_container() {
		if ( \wp_is_block_theme() ) {
			return;
		}

		global $wp_query;

		$query_id = WooCommerce_Integration::get_active_query_id();
		if ( $query_id === 0 ) {
			return;
		}
		if ( ! self::is_shop_query_id( $query_id ) ) {
			return;
		}

		echo '<div class="search-filter-query search-filter-query--id-' . absint( WooCommerce_Integration::get_active_query_id() ) . '">';
	}

	/**
	 * Close the results container in non block themes.
	 *
	 * @since 3.0.0
	 */
	public static function close_classic_shop_results_container() {
		if ( \wp_is_block_theme() ) {
			return;
		}

		$query_id = WooCommerce_Integration::get_active_query_id();
		if ( $query_id === 0 ) {
			return;
		}
		if ( ! self::is_shop_query_id( $query_id ) ) {
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
		if ( isset( $atts['search_filter_bypass_container'] ) ) {
			return;
		}
		$query_id = WooCommerce_Integration::get_active_query_id();
		if ( $query_id === 0 ) {
			return;
		}
		// Prevent adding a container if the query is the shop query.
		// This allows us to support additional `[products]` shortcodes
		// on the shop page.
		if ( self::is_shop_query_id( $query_id ) ) {
			return;
		}
		echo '<div class="search-filter-query search-filter-query--id-' . absint( $query_id ) . '">';
	}

	/**
	 * Check if the query is a shop query.
	 *
	 * @param int $query_id The query ID.
	 * @return bool True if the query is a shop query.
	 */
	private static function is_shop_query_id( $query_id ) {
		$query = Data_Store::get( 'query', $query_id );
		if ( ! $query ) {
			return false;
		}
		return $query->get_attribute( 'integrationType' ) === 'woocommerce/shop';
	}

	/**
	 * Close the shortcode results container.
	 *
	 * @since 3.0.0
	 *
	 * @param array $atts    The attributes.
	 */
	public static function close_shortcode_results_container( $atts ) {
		if ( isset( $atts['search_filter_bypass_container'] ) ) {
			return;
		}
		$query_id = WooCommerce_Integration::get_active_query_id();
		if ( $query_id === 0 || self::is_shop_query_id( $query_id ) ) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Add `search_filter_bypass_container` to the shortcode attributes, otherwise
	 * WC will remove it when it calls `shortcode_atts()` with its list of known
	 * attributes.
	 *
	 * This will allow the `WC_Shortcode_Products` class to keep the attribute around
	 * so we can use it in the `open_shortcode_results_container()` and
	 * `close_shortcode_results_container()` methods.
	 *
	 * @since 3.0.0
	 *
	 * @param array $out The existing shortcode attributes.
	 * @param array $pairs The shortcode pairs.
	 * @param array $atts The shortcode attributes.
	 *
	 * @return array The updated shortcode attributes.
	 */
	public static function shortcode_container_attributes( $out, $pairs, $atts ) {
		if ( ! isset( $atts['search_filter_bypass_container'] ) ) {
			return $out;
		}
		// Add `search_filter_bypass_container` to the shortcode attributes, otherwise they'll be removed by WC.
		$out['search_filter_bypass_container'] = $atts['search_filter_bypass_container'];
		return $out;
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

		if ( ! $data_wc ) {
			return $suggestions;
		}

		// TODO add options to autocomplete.

		/*
		 * Disabled: stock_status and on_sale options for autocomplete.
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
		}
		*/

		// Then we're dealing with a taxonomy.
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $data_wc );

		if ( $taxonomy_name === '' ) {
			return $suggestions;
		}

		// Method only exists on Search field type.
		if ( ! method_exists( $field, 'search_taxonomy_term_labels' ) ) {
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

		if ( ! $data_wc ) {
			return $query_args;
		}

		$search_term = $field->get_value();
		if ( $search_term === '' ) {
			return $query_args;
		}

		// Handle SKU search (non-indexed).
		if ( $data_wc === 'sku' ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => $search_term,
				'compare' => 'LIKE',
			);
			return $query_args;
		}

		// Handle GUID search (non-indexed).
		if ( $data_wc === 'guid' ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
			$query_args['meta_query'][] = array(
				'key'     => '_global_unique_id',
				'value'   => $search_term,
				'compare' => 'LIKE',
			);
			return $query_args;
		}

		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $data_wc );

		if ( $taxonomy_name === '' ) {
			return $query_args;
		}

		// Method only exists on Search field type.
		if ( ! method_exists( $field, 'search_taxonomy_term_labels' ) ) {
			return $query_args;
		}

		$taxonomy_terms = $field->search_taxonomy_term_labels( $search_term, $taxonomy_name, 'slug' );

		if ( ! isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( ! empty( $taxonomy_terms ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy_name,
				'field'    => 'slug',
				'terms'    => $taxonomy_terms,
			);
		} elseif ( method_exists( $field, 'add_fail_query_args' ) ) {
			// Method only exists on Search field type.
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

		if ( ! $data_wc ) {
			return $query_args;
		}

		if ( empty( $field->get_values() ) ) {
			return $query_args;
		}

		if ( $data_wc === 'stock_status' ) {

			$values = $field->get_values();

			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$query_args['meta_query'][] = array(
				array(
					'key'   => '_stock_status',
					'value' => $values,
				),
			);

			return $query_args;

		} elseif ( $data_wc === 'on_sale' ) {

			$value = $field->get_value();
			if ( $value === 'on-sale' ) {
				$products_on_sale = \wc_get_product_ids_on_sale();
				$has_post__in     = isset( $query_args['post__in'] );
				if ( ! $has_post__in ) {
					$query_args['post__in'] = $products_on_sale;
				} else {
					$query_args['post__in'] = array_intersect( $query_args['post__in'], $products_on_sale );
				}
			}

			return $query_args;
		} elseif ( $data_wc === 'price' ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$price_range = $field->get_values();

		} elseif ( in_array( $data_wc, array( 'weight', 'length', 'width', 'height' ), true ) ) {
			// Handle weight and individual dimensions for choice fields.
			$values = $field->get_values();

			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$query_args['meta_query'][] = array(
				array(
					'key'   => '_' . $data_wc,
					'value' => $values,
				),
			);

			return $query_args;
		} elseif ( $data_wc === 'dimensions' ) {
			// Handle combined dimensions for choice fields.
			// Values are in "LxWxH" format - explode to query individual meta fields.
			$values = $field->get_values();

			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			// Build OR query for multiple dimension selections.
			$dimension_meta_queries = array( 'relation' => 'OR' );
			foreach ( $values as $dimension_string ) {
				$parts = explode( 'x', $dimension_string );
				if ( count( $parts ) === 3 ) {
					$dimension_meta_queries[] = array(
						'relation' => 'AND',
						array(
							'key'   => '_length',
							'value' => $parts[0],
						),
						array(
							'key'   => '_width',
							'value' => $parts[1],
						),
						array(
							'key'   => '_height',
							'value' => $parts[2],
						),
					);
				}
			}

			if ( count( $dimension_meta_queries ) > 1 ) {
				$query_args['meta_query'][] = $dimension_meta_queries;
			}

			return $query_args;
		}

		// It must be a tax product attribute, product_cat or product_tag, or product_brand which is already handled in
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
			++$number_of_values;
		}

		if ( $to !== '' ) {
			++$number_of_values;
		}

		if ( $number_of_values === 0 ) {
			return $query_args;
		}

		if ( $data_wc === 'price' || in_array( $data_wc, array( 'weight', 'length', 'width', 'height' ), true ) ) {

			$custom_field_key = $data_wc === 'price' ? '_price' : '_' . $data_wc;
			$decimal_places   = $field->get_attribute( 'rangeDecimalPlaces' );

			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
			} elseif ( $from !== '' ) {
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

		$values = $field->get_values();

		// Build options for On Sale, Price, and Stock status.
		if ( $wc_data_type === 'on_sale' ) {
			$option = array(
				'value' => 'on-sale',
				'label' => __( 'On sale', 'search-filter-pro' ),
			);
			Choice::add_option_to_array(
				$options,
				$option,
				$field->get_id()
			);

			if ( in_array( $option['value'], $values, true ) ) {
				$field->set_value_labels( array( $option['value'] => $option['label'] ) );
			}
		} elseif ( $wc_data_type === 'stock_status' ) {

			$show_on_backorder = $field->get_attribute( 'dataWoocommerceShowOnBackorder' );
			$show_out_of_stock = $field->get_attribute( 'dataWoocommerceShowOutOfStock' );

			$stock_status_options = \wc_get_product_stock_status_options();
			foreach ( $stock_status_options as $stock_status => $stock_status_label ) {

				if ( $stock_status === 'onbackorder' && $show_on_backorder !== 'yes' ) {
					continue;
				}

				if ( $stock_status === 'outofstock' && $show_out_of_stock !== 'yes' ) {
					continue;
				}

				Choice::add_option_to_array(
					$options,
					array(
						'value' => $stock_status,
						'label' => $stock_status_label,
					),
					$field->get_id()
				);

				if ( in_array( $stock_status, $values, true ) ) {
					$field->set_value_labels( array( $stock_status => $stock_status_label ) );
				}
			}
		} elseif ( $wc_data_type === 'custom_attribute' ) {

			$custom_attribute = $field->get_attribute( 'dataWoocommerceCustomAttribute' );
			if ( empty( $custom_attribute ) ) {
				return $options;
			}

			// Then lookup the unique values in our indexer.
			$options = array();

			// Use index query direct for better performance with optimized indexes.
			$unique_values = Bitmap_Index_Query_Direct::get_unique_field_values( $field->get_id() );

			if ( empty( $unique_values ) ) {
				return $options;
			}

			$order           = $field->get_attribute( 'inputOptionsOrder' );
			$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
			$values          = Util::sort_array( $unique_values, $order, $order_direction );

			foreach ( $values as $value ) {
				if ( $value === '' ) {
					continue;
				}

				$value = (string) $value;
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $value,
					),
					$field->get_id()
				);

				if ( in_array( $value, $values, true ) ) {
					$field->set_value_labels( array( $value => $value ) );
				}
			}

			return $options;

		} elseif ( in_array( $wc_data_type, array( 'weight', 'dimensions', 'length', 'width', 'height' ), true ) ) {
			// Get unique values for weight/dimensions.
			$options = array();

			// Combined dimensions requires the indexer (can't generate efficiently without it).
			// Also not available in admin preview (field ID = 0).
			if ( $wc_data_type === 'dimensions' ) {
				if ( $field->get_id() === 0 ) {
					// Admin preview - no options available - lets create a some test dimensions.
					$options = array(
						array(
							'value' => '10x10x10',
							'label' => html_entity_decode(
								\wc_format_dimensions(
									array(
										'length' => '10',
										'width'  => '10',
										'height' => '10',
									)
								)
							),
						),
						array(
							'value' => '30x30x30',
							'label' => html_entity_decode(
								\wc_format_dimensions(
									array(
										'length' => '30',
										'width'  => '30',
										'height' => '30',
									)
								)
							),
						),
						array(
							'value' => '60x60x60',
							'label' => html_entity_decode(
								\wc_format_dimensions(
									array(
										'length' => '60',
										'width'  => '60',
										'height' => '60',
									)
								)
							),
						),
					);

					return $options;
				}

				$parent_query = $field->get_query();
				if ( ! $parent_query || $parent_query->get_attribute( 'useIndexer' ) !== 'yes' ) {
					return $options; // Not using indexer - no options available.
				}
			}

			// Determine if we should use the indexer.
			$use_indexer_query = false;
			// ID of `0` means an admin preview, which means we should use a regular query.
			if ( $field->get_id() > 0 ) {
				// Then check the useIndexer setting from the parent query.
				$parent_query = $field->get_query();
				if ( $parent_query && $parent_query->get_attribute( 'useIndexer' ) === 'yes' ) {
					$use_indexer_query = true;
				}
			}

			if ( $use_indexer_query ) {
				// Get unique indexed values for this field.
				$unique_values = Bitmap_Index_Query_Direct::get_unique_field_values( $field->get_id() );
			} else {
				// Use meta query to get distinct values.
				global $wpdb;
				$meta_key = '_' . $wc_data_type;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query for distinct WooCommerce meta values, results vary by context.
				$unique_values = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value FROM %i
						WHERE meta_key = %s AND meta_value != ''",
						$wpdb->postmeta,
						$meta_key
					)
				);
			}

			if ( empty( $unique_values ) ) {
				return $options;
			}

			// Sort numerically by default for weight/dimensions (alphabetically for combined dimensions).
			$order           = $field->get_attribute( 'inputOptionsOrder' ) ? $field->get_attribute( 'inputOptionsOrder' ) : 'label';
			$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
			$sort_type       = $wc_data_type === 'dimensions' ? 'alphabetical' : 'numerical';
			$sorted_values   = Util::sort_array( $unique_values, $order === 'label' ? $sort_type : $order, $order_direction );

			$field_values = $field->get_values();
			foreach ( $sorted_values as $value ) {
				if ( $value === '' ) {
					continue;
				}

				$value = (string) $value;

				// For combined dimensions, convert "LxWxH" to "L × W × H" for display.
				if ( $wc_data_type === 'dimensions' ) {
					$parts = explode( 'x', $value );
					$label = implode( ' × ', $parts );
				} else {
					$label = $value;
				}

				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $label,
					),
					$field->get_id()
				);

				if ( in_array( $value, $field_values, true ) ) {
					$field->set_value_labels( array( $value => $label ) );
				}
			}

			return $options;
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

	/**
	 * Add WooCommerce custom settings.
	 */
	private static function add_settings() {

		$setting = array(
			'name'        => 'dataWoocommerceCustomAttribute',
			'label'       => __( 'Custom Attribute', 'search-filter' ),
			'placeholder' => __( 'Enter custom attribute name', 'search-filter' ),
			'group'       => 'data',
			'notice'      => __( 'It is recommended to use Global Attributes for filtering.  Unable to generate field previews for custom attributes.', 'search-filter' ),
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Text',
			'default'     => '',
			'context'     => array( 'admin/field', 'block/field' ),
			'dependsOn'   => array(
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
						'value'   => 'custom_attribute',
					),
				),
			),
			'supports'    => array(
				'previewAPI' => false,
			),
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'dataWoocommerce',
			),
		);
		Fields_Settings::add_setting( $setting, $setting_args );

		// Dimensions notice - preview unavailable without indexer.
		$setting      = array(
			'name'      => 'dataWoocommerceDimensionsNotice',
			'content'   => __( 'Preview unavailable - dimensions require the indexer to generate real options.', 'search-filter-pro' ),
			'group'     => 'data',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Notice',
			'status'    => 'info',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'woocommerce',
					),
					array(
						'option'  => 'dataWoocommerce',
						'compare' => '=',
						'value'   => 'dimensions',
					),
				),
			),
		);
		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'dataWoocommerce',
			),
		);
		Fields_Settings::add_setting( $setting, $setting_args );

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
			'context'   => array( 'admin/field', 'block/field' ),
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

		$setting = array(
			'name'      => 'dataWoocommerceShowOutOfStock',
			'label'     => __( 'Show out of stock', 'search-filter' ),
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
			'context'   => array( 'admin/field', 'block/field' ),
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
				'setting'   => 'dataWoocommerceShowOnBackorder',
			),
		);
		Fields_Settings::add_setting( $setting, $setting_args );
	}
}
