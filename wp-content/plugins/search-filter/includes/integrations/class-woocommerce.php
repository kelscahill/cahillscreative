<?php
/**
 * WooCommerce Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */

namespace Search_Filter\Integrations;

use Search_Filter\Fields\Field;
use Search_Filter\Integrations;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\WooCommerce\Rest_API;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All WooCommerce integration functionality
 * Add options to admin, integrate with frontend queries
 */
class Woocommerce {

	/**
	 * Keeps track of the active query ID (which is currently being modified).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private static $active_query_id = 0;
	
	/**
	 * Previous active query ID.
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private static $previous_active_query_id = 0;

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {

		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 1 );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Don't do anything until all the settings are init.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 1 );

		// Need to update field support before field settings are setup.
		add_action( 'search-filter/settings/integrations/init', array( __CLASS__, 'hook_into_field_support' ), 10 );
	}
	/**
	 * Update the WooCommerce integration in the integrations section.
	 *
	 * @since 3.0.0
	 */
	public static function update_integration() {
		// We want to disable coming soon notice and enable the integration toggle.
		$woocommerce_integration = Integrations_Settings::get_setting( 'woocommerce' );
		if ( ! $woocommerce_integration ) {
			return;
		}

		$woocommerce_enabled         = class_exists( 'WooCommerce' );
		$update_integration_settings = array(
			'isPluginEnabled'      => $woocommerce_enabled,
			'isExtensionInstalled' => true,
		);
		// If we detect WC is enabled, then lets also set the pluign installed
		// property to true - because a plugin could be installed using a different
		// folder name and it we would initially detect is as not installed by using
		// by using `plugin_exists()` which is unreliable.
		if ( $woocommerce_enabled ) {
			$update_integration_settings['isPluginInstalled'] = true;
		}

		$woocommerce_integration->update( $update_integration_settings );
	}

	/**
	 * Update field support.
	 *
	 * @since 3.0.0
	 */
	public static function hook_into_field_support() {
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return;
		}
		// Add woocommerce data type support to input types.
		add_filter( 'search-filter/field/get_data_support', array( __CLASS__, 'get_field_data_support' ), 10, 3 );

		// Add support for taxonomy related settings.
		add_filter( 'search-filter/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );

		// Filter the setting early to add our depends conditions for tax archive support.
		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_use_taxonomy_archive_support' ), 10, 1 );
	}

	/**
	 * Init the integration.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {

		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return;
		}

		// Add WC options to the admin UI.
		self::register_query_settings();
		self::register_field_settings();

		add_filter( 'search-filter/queries/query/get_results_data', array( __CLASS__, 'get_results_data' ), 10, 2 );
		add_filter( 'search-filter/queries/query/get_results_url/override_url', array( __CLASS__, 'get_results_url' ), 10, 2 );
		add_filter( 'search-filter/queries/query/apply_wp_query_args', array( __CLASS__, 'make_post_types_singular_on_shop' ), 10, 2 );
		add_filter( 'search-filter/rest-api/get_query_post_types', array( __CLASS__, 'get_query_post_types' ), 10, 2 );
		add_filter( 'search-filter/query/selector/should_attach', array( __CLASS__, 'attach_query' ), 10, 3 );
		add_filter( 'search-filter/field/choice/options_data', array( __CLASS__, 'choice_options_data' ), 10, 2 );
		add_filter( 'search-filter/integrations/gutenberg/add_attributes', array( __CLASS__, 'add_block_attributes' ), 10 );
		add_filter( 'search-filter/field/url_name', array( __CLASS__, 'field_url_name' ), 10, 2 );
		add_filter( 'search-filter/field/choice/wp_query_args', array( __CLASS__, 'choice_wp_query_args' ), 10, 2 );

		// Filter the WC Products block.
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_products_block' ), 10, 2 );
		// Remove hook from the WC Products block.
		add_filter( 'render_block', array( __CLASS__, 'cleanup_query_block' ), 10, 3 );

		// Add support WC products shortcode.
		add_filter( 'shortcode_atts_products', array( __CLASS__, 'shortcode_attributes' ), 11, 3 );
		add_action( 'woocommerce_shortcode_after_products_loop', array( __CLASS__, 'finish_shortcode_loop' ), 21 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', array( __CLASS__, 'finish_shortcode_loop' ), 21 );
		add_action( 'woocommerce_shortcode_products_query_results', array( __CLASS__, 'track_wc_query_data' ), 10, 2 );

		// Add support for taxonomy filter archive.
		add_filter( 'search-filter/field/url_template', array( __CLASS__, 'field_url_template' ), 10, 2 );
		add_action( 'search-filter/queries/query/init_render_config_values', array( __CLASS__, 'init_render_config_values' ), 10, 1 );
		add_filter( 'search-filter/field/parse_url_value', array( __CLASS__, 'parse_url_value' ), 10, 2 );
		add_filter( 'search-filter/fields/field/connected_data', array( __CLASS__, 'add_taxonomy_archive_connected_data' ), 10, 2 );
		add_filter( 'search-filter/queries/query/can_apply_at_current_location', array( __CLASS__, 'can_apply_query_at_current_location' ), 10, 2 );
		Rest_API::init();
	}

	public static function field_url_template( $url_template, $field ) {
		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return $url_template;
		}

		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' ) {
			return $url_template;
		}

		// Bail early if the attribute on the field is not enabled.
		if ( $field->get_attribute( 'taxonomyFilterArchive' ) !== 'yes' ) {
			return $url_template;
		}
		$taxonomy_name = self::get_taxonomy_name_from_data_source( $field->get_attribute( 'dataWoocommerce' ) );
		if ( empty( $taxonomy_name ) ) {
			return $url_template;
		}

		$query = Query::find( array( 'id' => $field->get_query_id() ) );
		if ( is_wp_error( $query ) ) {
			return $url_template;
		}

		// Ensure query has filtering taxonomy archives enabled.
		if ( $query->get_attribute( 'archiveFilterTaxonomies' ) !== 'yes' ) {
			return $url_template;
		}

		// Make sure the connected query is using the shop integration.
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return $url_template;
		}

		// Usually we'd check to ensure the taxonomy is only associated with one
		// post type, but its possible that the taxonomy is assigned to the `product`
		// and `product_variation` post types.

		// Now we can try to get the taxonomy url template.
		return Template_Data::get_term_template_link( $taxonomy_name );
	}
	public static function init_render_config_values( $query ) {
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return;
		}

		$filter_tax_archives = $query->get_attribute( 'archiveFilterTaxonomies' );
		if ( $filter_tax_archives !== 'yes' ) {
			return;
		}

		global $wp_query;
		if ( ! $wp_query->is_archive() ) {
			return;
		}
		if ( ! $wp_query->is_tax() ) {
			return;
		}

		$query_post_types = $query->get_attribute( 'postTypes' );

		// For some reason there are multiple post types, so bail.
		if ( count( $query_post_types ) > 1 ) {
			return;
		}
		$archive_post_type = $query_post_types[0];
		// Build the term archive URL.
		$queried_object = get_queried_object();
		// Get the postType taxonomies.
		$taxonomies = get_object_taxonomies( $archive_post_type );

		$tax_archive_url = '';
		$tax_slug        = '';
		foreach ( $taxonomies as $taxonomy ) {
			if ( $queried_object->taxonomy === $taxonomy ) {
				$tax_archive_url = get_term_link( $queried_object->term_id );
				$tax_slug        = $taxonomy;
				break;
			}
		}
		$query->set_render_config_value( 'currentTaxonomyArchive', $tax_slug );
		$query->set_render_config_value( 'taxonomyArchiveUrl', $tax_archive_url );
	}

	public static function parse_url_value( $value, $field ) {

		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return $value;
		}

		if ( $field->get_attribute( 'taxonomyFilterArchive' ) !== 'yes' ) {
			return $value;
		}
		$taxonomy_name = self::get_taxonomy_name_from_data_source( $field->get_attribute( 'dataWoocommerce' ) );
		if ( empty( $taxonomy_name ) ) {
			return $value;
		}

		$query = Query::find( array( 'id' => $field->get_query_id() ) );
		if ( is_wp_error( $query ) ) {
			return $value;
		}
		// Ensure query has filtering taxonomy archives enabled.
		if ( $query->get_attribute( 'archiveFilterTaxonomies' ) !== 'yes' ) {
			return $value;
		}

		// Make sure the connected query is using the shop integration.
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return $value;
		}

		// Check if we are on this tax archive.
		if ( ! is_tax( $taxonomy_name ) ) {
			return $value;
		}

		$term = get_queried_object();

		// Check if $term is a term object.
		if ( ! is_a( $term, 'WP_Term' ) ) {
			return $value;
		}

		global $wp_query;
		/*
		 * We want to make sure that we don't detect anything here
		 * if the archive has multiple terms, eg: yoursite.com/category/term1+term2
		 */
		if ( ! isset( $wp_query->tax_query->queried_terms[ $taxonomy_name ] ) ) {
			return $value;
		}

		if ( count( $wp_query->tax_query->queried_terms[ $taxonomy_name ]['terms'] ) !== 1 ) {
			return $value;
		}

		return $term->slug;
	}

	/**
	 * Sets the necessary connected data for filters that filter taxonomy archives.
	 *
	 * @since 3.0.0
	 *
	 * @param array $connected_data The existing connected data.
	 * @param Field $field          The field instance.
	 *
	 * @return array The updated connected data.
	 */
	public static function add_taxonomy_archive_connected_data( $connected_data, $field ) {
		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return $connected_data;
		}

		if ( $field->get_attribute( 'taxonomyFilterArchive' ) !== 'yes' ) {
			return $connected_data;
		}

		$taxonomy_name = self::get_taxonomy_name_from_data_source( $field->get_attribute( 'dataWoocommerce' ) );
		if ( ! empty( $taxonomy_name ) ) {
			$connected_data['filtersTaxonomyArchive'] = $taxonomy_name;
		}
		return $connected_data;
	}


	/**
	 * Undocumented function
	 *
	 * @param [type] $data_support
	 * @param [type] $type
	 * @param [type] $input_type
	 * @return void
	 */
	public static function can_apply_query_at_current_location( $can_apply, $query ) {
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return $can_apply;
		}
		global $wp_query;
		if ( self::is_shop( $wp_query ) ) {
			return true;
		}

		if ( $query->get_attribute( 'taxonomyFilterArchive' ) !== 'yes' ) {
			return $can_apply;
		}

		// Then check to see if we're on a product tax/attribute archive.
		return self::is_taxonomy_archive();
	}
	/**
	 * Update fields data support.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $data_support The existing data support setting.
	 * @param string $type         The field type.
	 * @param string $input_type   The field input type.
	 *
	 * @return array The updated data support.
	 */
	public static function get_field_data_support( $data_support, $type, $input_type ) {
		$supported_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
		);
		$supported_matrix = apply_filters( 'search-filter/integrations/woocommerce/get_field_data_support', $supported_matrix );

		if ( ! isset( $supported_matrix[ $type ] ) ) {
			return $data_support;
		}

		if ( ! in_array( $input_type, $supported_matrix[ $type ], true ) ) {
			return $data_support;
		}

		// Create a map of input type options and conditions we want to add.
		$data_type_options = self::get_data_type_options();
		// TODO - this should not be all the options... but right now it is.
		$data_type_values = array_map(
			function ( $option ) {
				return $option['value'];
			},
			$data_type_options
		);

		$data_support[] = array(
			'dataType'        => 'woocommerce',
			'dataWoocommerce' => $data_type_values,
		);
		return $data_support;
	}

	/**
	 * Cleanup the query block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block attributes.
	 *
	 * @return string The updated block content.
	 */
	public static function cleanup_query_block( $block_content, $block ) {

		if ( $block['blockName'] !== 'core/query' ) {
			return $block_content;
		}

		if ( ! isset( $block['attrs']['namespace'] ) ) {
			return $block_content;
		}

		if ( $block['attrs']['namespace'] !== 'woocommerce/product-query' && $block['attrs']['namespace'] !== 'woocommerce/product-collection' ) {
			return $block_content;
		}

		\Search_Filter\Integrations\Gutenberg::cleanup_query_block( $block );
		return $block_content;
	}

	/**
	 * Try to attach to the query on the pre render hook.
	 *
	 * @since 3.0.0
	 *
	 * @param bool  $pre_render Whether to short-circuit the block rendering.
	 * @param array $block      The block attributes.
	 *
	 * @return bool True if the block should be rendered, false if not.
	 */
	public static function pre_render_products_block( $pre_render, $block ) {

		// WC is moving to the new product collection block.
		if ( $block['blockName'] === 'woocommerce/product-collection' ) {
			\Search_Filter\Integrations\Gutenberg::try_connect_to_query_loop( $block, 'woocommerce/products_query_block', 'woocommerce/product-collection' );
			return $pre_render;
		}

		// Support legacy versions based on the core/query block.

		if ( $block['blockName'] !== 'core/query' ) {
			return $pre_render;
		}

		if ( ! isset( $block['attrs']['namespace'] ) ) {
			return $pre_render;
		}

		if ( $block['attrs']['namespace'] !== 'woocommerce/product-query' && $block['attrs']['namespace'] !== 'woocommerce/product-collection' ) {
			return $pre_render;
		}
		// We can get in here 2 ways - either via regular query block attached to the page and linked,
		// or via the WC Shop page/archive, if we're using a block theme.
		// This function will not try to connect to the shop page so we'll never know the ID of the query.
		\Search_Filter\Integrations\Gutenberg::try_connect_to_query_loop( $block, 'woocommerce/products_query_block' );
		\Search_Filter\Integrations\Gutenberg::try_connect_to_query_loop( $block, 'woocommerce/products_query_block' );

		return $pre_render;
	}
	/**
	 * On S&F settings register, add a new setting + update others
	 *
	 * @since    3.0.0
	 */
	public static function register_query_settings() {
		self::add_query_integration();
		self::add_single_integrations();
		self::add_taxonomy_settings();
		self::add_results_setting();
		self::add_shop_integration_type();
		self::modify_query_integration();
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_field_settings() {
		self::add_wc_setting();
		self::modify_data_type();
	}

	/**
	 * Add attributes to our field blocks.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes The existing attributes.
	 */
	public static function add_block_attributes( $attributes ) {
		$attributes['choice']['dataWoocommerce'] = array(
			'type'    => 'string',
			'default' => '',
		);
		$attributes['range']['dataWoocommerce']  = array(
			'type'    => 'string',
			'default' => '',
		);
		$attributes['search']['dataWoocommerce'] = array(
			'type'    => 'string',
			'default' => '',
		);
		return $attributes;
	}

	/**
	 * Get the taxonomy name from the data source.
	 *
	 * @since 3.0.0
	 *
	 * @param string $data_source The data source.
	 *
	 * @return string The taxonomy name.
	 */
	public static function get_taxonomy_name_from_data_source( $data_source ) {
		$taxonomy_name = '';
		if ( $data_source === 'product_cat' ) {
			$taxonomy_name = 'product_cat';
		} elseif ( $data_source === 'product_tag' ) {
			$taxonomy_name = 'product_tag';
		} elseif ( $data_source === 'product_brand' ) {
			$taxonomy_name = 'product_brand';
		}

		// If data source starts with `attribute:`.
		if ( substr( $data_source, 0, 10 ) === 'attribute:' ) {
			$attribute_name = substr( $data_source, 10 );
			$taxonomy_name  = \wc_attribute_taxonomy_name( $attribute_name );
		}
		return $taxonomy_name;
	}

	/**
	 * Modify the query args for the WooCommerce data type.
	 *
	 * @since    3.0.0
	 *
	 * @param array $query_args The existing query args.
	 * @param Field $field      The field instance.
	 *
	 * @return array The updated query args.
	 */
	public static function choice_wp_query_args( $query_args, $field ) {
		$attributes = $field->get_attributes();
		$values     = $field->get_values();

		if ( ! isset( $attributes['dataType'] ) ) {
			return $query_args;
		}
		$data_type = $attributes['dataType'];
		if ( $data_type !== 'woocommerce' ) {
			return $query_args;
		}
		$taxonomy_name = self::get_taxonomy_name_from_data_source( $attributes['dataWoocommerce'] );
		$query_values  = array();

		foreach ( $values as $tax_term ) {
			if ( term_exists( $tax_term, $taxonomy_name ) ) {
				$query_values[] = $tax_term;
			}
		}
		if ( empty( $query_values ) ) {
			return $query_args;
		}

		if ( ! isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array();
		}

		// TODO - figure out how to handle this in relation to other taxonomies being set
		// in the query already (ie via the loop block).
		$query_args['tax_query']['relation'] = 'AND';

		$compare_type = 'IN';
		if ( isset( $attributes['multipleMatchMethod'] ) ) {
			$compare_type = $attributes['multipleMatchMethod'] === 'all' ? 'AND' : 'IN';
		}
		if ( $compare_type === 'AND' ) {
			$sub_tax_query = array(
				'relation' => 'AND',
			);
			foreach ( $query_values as $value ) {
				$sub_tax_query[] = array(
					'taxonomy' => $taxonomy_name,
					'field'    => 'slug',
					'terms'    => array( $value ),
				);
			}
			$query_args['tax_query'][] = $sub_tax_query;
		} else {
			$query_args['tax_query'][] = array(
				array(
					'taxonomy' => $taxonomy_name,
					'field'    => 'slug',
					'compare'  => 'IN',
					'terms'    => $query_values,
				),
			);
		}
		return $query_args;
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
	public static function field_url_name( $url_name, $field ) {
		$data_type = $field->get_attribute( 'dataType' );

		if ( $data_type !== 'woocommerce' ) {
			return $url_name;
		}

		$data_source = $field->get_attribute( 'dataWoocommerce' );

		if ( ! $data_source ) {
			return $url_name;
		}
		if ( $data_source === 'product_cat' ) {
			return 'product_cat';
		} elseif ( $data_source === 'product_tag' ) {
			return 'product_tag';
		} elseif ( $data_source === 'product_brand' ) {
			$taxonomy_name = 'product_brand';
		}

		// If data source starts with `attribute:`.
		if ( substr( $data_source, 0, 10 ) === 'attribute:' ) {
			$attribute_name = substr( $data_source, 10 );
			$taxonomy_name  = \wc_attribute_taxonomy_name( $attribute_name );
			return $taxonomy_name;
		}

		return $url_name;
	}

	/**
	 * Populate the options in the fields that need them.
	 *
	 * @since    3.0.0
	 *
	 * @param array $options_data The existing options data.
	 * @param Field $field   The field instance.
	 *
	 * @return array The updated options.
	 */
	public static function choice_options_data( $options_data, $field ) {

		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' ) {
			return $options_data;
		}

		if ( count( $options_data['options'] ) > 0 ) {
			return $options_data;
		}

		$data_source = $field->get_attribute( 'dataWoocommerce' );

		// Check if we can get a taxonomy name from the data source.
		$wc_taxonomy_name = self::get_taxonomy_name_from_data_source( $data_source );

		// Only support taxonomies.
		if ( empty( $wc_taxonomy_name ) ) {
			return $options_data;
		}

		$order_dir = '';
		if ( $field->get_attribute( 'woocommerceTaxOrderDir' ) === 'asc' ) {
			$order_dir = 'ASC';
		} elseif ( $field->get_attribute( 'woocommerceTaxOrderDir' ) === 'desc' ) {
			$order_dir = 'DESC';
		}
		$args         = array(
			'order_by'            => $field->get_attribute( 'woocommerceTaxOrderBy' ) !== 'default' ? $field->get_attribute( 'woocommerceTaxOrderBy' ) : '',
			'order_dir'           => $order_dir,
			'hide_empty'          => $field->get_attribute( 'hideEmpty' ) === 'yes',
			'terms_conditions'    => $field->get_attribute( 'woocommerceTaxTermsConditions' ),
			'terms'               => $field->get_attribute( 'woocommerceTaxTerms' ),
			'is_hierarchical'     => $field->get_attribute( 'taxonomyHierarchical' ) === 'yes',
			'limit_depth'         => $field->get_attribute( 'limitTaxonomyDepth' ) === 'yes',
			'show_count'          => $field->get_attribute( 'showCount' ) === 'yes',
			'show_count_brackets' => $field->get_attribute( 'showCountBrackets' ) === 'yes',
		);
		$options_data = $field->get_taxonomy_options_data( $wc_taxonomy_name, $args );
		return $options_data;
	}

	private static function create_taxonomy_depends_conditions() {
		// Build conditions for all taxonomy attributes.
		$wc_tax_attributes   = \wc_get_attribute_taxonomies();
		$taxonomy_conditions = array(
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_tag',
				'compare' => '=',
			),
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_cat',
				'compare' => '=',
			),
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_brand',
				'compare' => '=',
			),
		);
		foreach ( $wc_tax_attributes as $attribute ) {
			$taxonomy_conditions[] = array(
				'option'  => 'dataWoocommerce',
				'value'   => 'attribute:' . $attribute->attribute_name,
				'compare' => '=',
			);
		}

		return $taxonomy_conditions;
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

		// Add show count + hide empty to choice fields, for indexed queries.
		$taxonomy_supported_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
		);

		if ( isset( $taxonomy_supported_matrix[ $type ] ) && in_array( $input_type, $taxonomy_supported_matrix[ $type ], true ) ) {

			$taxonomy_field_conditions = array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'woocommerce',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						'action'   => 'hide',
						'rules'    => self::create_taxonomy_depends_conditions(),
					),
				),
			);

			$setting_support['showCount']            = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'showCount', $taxonomy_field_conditions, false ),
			);
			$setting_support['hideEmpty']            = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'hideEmpty', $taxonomy_field_conditions, false ),
			);
			$setting_support['taxonomyHierarchical'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'taxonomyHierarchical', $taxonomy_field_conditions, false ),
			);

		}

		// Add show count + hide empty to choice fields, for indexed queries.
		$taxonomy_ordering_supported_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
			'search' => array( 'autocomplete' ),
		);

		if ( isset( $taxonomy_ordering_supported_matrix[ $type ] ) && in_array( $input_type, $taxonomy_ordering_supported_matrix[ $type ], true ) ) {

			$taxonomy_field_conditions = array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'woocommerce',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						'action'   => 'hide',
						'rules'    => self::create_taxonomy_depends_conditions(),
					),
				),
			);

			$setting_support['woocommerceTaxOrderBy']         = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'woocommerceTaxOrderBy', $taxonomy_field_conditions, true ),
			);
			$setting_support['woocommerceTaxOrderDir']        = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'woocommerceTaxOrderDir', $taxonomy_field_conditions, true ),
			);
			$setting_support['woocommerceTaxTermsConditions'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'woocommerceTaxTermsConditions', $taxonomy_field_conditions, true ),
			);
			$setting_support['woocommerceTaxTerms']           = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'woocommerceTaxTerms', $taxonomy_field_conditions, true ),
			);

		}

		return $setting_support;
	}


	/**
	 * Add the WC shop URL as the results link.
	 *
	 * @since    3.0.0
	 *
	 * @param array $results_data The existing results data.
	 * @param array $params       The query params.
	 *
	 * @return array The updated results data.
	 */
	public static function get_results_data( $results_data, $params ) {
		$integration_type = $params['integrationType'];
		if ( $integration_type !== 'woocommerce/shop' ) {
			return $results_data;
		}
		$results_data['url'] = \wc_get_page_permalink( 'shop' );
		return $results_data;
	}

	/**
	 * Add the WC shop URL as the results link.
	 *
	 * @since    3.0.0
	 *
	 * @param string $url The existing results URL.
	 * @param array  $params The query params.
	 *
	 * @return string The updated results URL.
	 */
	public static function get_results_url( $url, $params ) {
		$integration_type = $params['integrationType'];
		if ( $integration_type !== 'woocommerce/shop' ) {
			return $url;
		}
		return \wc_get_page_permalink( 'shop' );
	}

	/**
	 * Attach the S&F query to the shop.
	 *
	 * @since    3.0.0
	 *
	 * @param bool      $should_attach Whether to attach the query.
	 * @param Query     $saved_query   The saved query instance.
	 * @param \WP_Query $query     The WP_Query instance.
	 *
	 * @return bool True if the query should be attached, false if not.
	 */
	public static function attach_query( $should_attach, $saved_query, $query ) {
		// If it's already set to attach, don't change it.
		if ( $should_attach ) {
			return $should_attach;
		}
		if ( is_admin() ) {
			return false;
		}
		if ( ! $query->is_main_query() ) {
			return false;
		}

		$attributes       = $saved_query->get_attributes();
		$integration_type = $attributes['integrationType'];
		if ( $integration_type !== 'woocommerce/shop' ) {
			return $should_attach;
		}
		if ( self::is_shop( $query ) ) {
			self::set_active_query_id( $saved_query->get_id() );
			return true;
		}
		// Check if query is filtering taxonomy archives.
		if ( $saved_query->get_attribute( 'archiveFilterTaxonomies' ) !== 'yes' ) {
			return $should_attach;
		}

		// Then we want to get all product taxonomies and check if we are on their
		// archive or not.
		if ( self::is_taxonomy_archive() ) {
			self::set_active_query_id( $saved_query->get_id() );
			return true;
		}

		return $should_attach;
	}

	public static function set_active_query_id( $query_id ) {
		self::$previous_active_query_id = self::$active_query_id;
		self::$active_query_id = $query_id;
	}
	public static function reset_active_query_id() {
		self::$active_query_id = self::$previous_active_query_id;
		self::$previous_active_query_id = 0;
	}
	public static function is_taxonomy_archive() {
		// Then we want to get all product taxonomies and check if we are on their
		// archive or not.
		$wc_taxonomy_slugs = array( 'product_tag', 'product_cat', 'product_brand' );
		foreach ( $wc_taxonomy_slugs as $slug ) {
			if ( is_tax( $slug ) ) {
				return true;
			}
		}

		// Check attribute taxonomies.
		$wc_tax_attributes = \wc_get_attribute_taxonomies();
		foreach ( $wc_tax_attributes as $attribute ) {
			if ( is_tax( $attribute->attribute_name ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Conditional to check if we are on the shop page.
	 *
	 * Using is_shop on the frontpage throws errors/warning, so lets circumvent that.
	 * More info: https://github.com/woothemes/woocommerce/issues/10625#issuecomment-204212754
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return bool True if we are on the shop page, false if not.
	 */
	public static function is_shop( $query ) {
		$front_page_id        = get_option( 'page_on_front' );
		$current_page_id      = $query->get( 'page_id' );
		$shop_page_id         = apply_filters( 'woocommerce_get_shop_page_id', get_option( 'woocommerce_shop_page_id' ) );
		$is_static_front_page = 'page' === get_option( 'show_on_front' );

		// Warnings are thrown when using pre_get_posts, Woocommerce & the homepage when using the function `is_shop`.
		if ( $is_static_front_page && $front_page_id === $current_page_id ) {
			// its the homepage so use this function to detect shop.
			$is_shop_page = ( $current_page_id === $shop_page_id ) ? true : false;
		} else {
			// is_shop should work fine.
			$is_shop_page = \is_shop();
		}
		return $is_shop_page;
	}

	/**
	 * When on the shop page, set the post type to product (but not as an array).
	 *
	 * This mirrors the fix in Queries\Query::get_post_type_args where some plugins
	 * expect archives to have 1 post type set, but specifically NOT as an array.
	 *
	 * Known plugins - the BB Themer plugin.
	 *
	 * @param array $query_args The query args.
	 *
	 * @return array The updated query args.
	 */
	public static function make_post_types_singular_on_shop( $query_args, $query ) {

		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return $query_args;
		}

		global $wp_query;
		if ( ! self::is_shop( $wp_query ) ) {
			return $query_args;
		}

		$post_types = $query->get_attribute( 'postTypes' );

		if ( count( $post_types ) !== 1 ) {
			return $query_args;
		}

		$query_args['post_type'] = $post_types[0];
		return $query_args;
	}
	/**
	 * Restrict the post types if WooCommerce shop is set.
	 *
	 * @since    3.0.0
	 *
	 * @param array $query_post_types The existing post types.
	 * @param array $params           The query params.
	 *
	 * @return array The updated post types.
	 */
	public static function get_query_post_types( $query_post_types, $params ) {

		$integration_type  = isset( $params['integrationType'] ) ? $params['integrationType'] : '';
		$query_integration = isset( $params['queryIntegration'] ) ? $params['queryIntegration'] : '';

		if ( $query_integration === 'woocommerce/products_query_block' ) {
			$query_post_types = array(
				'disabled' => true,
				'value'    => array( 'product' ),
			);
		} elseif ( $query_integration === 'woocommerce/products_shortcode' ) {
			$query_post_types = array(
				'disabled' => true,
				'value'    => array( 'product' ),
			);
		} elseif ( $integration_type === 'woocommerce/shop' ) {
			$query_post_types = array(
				'disabled' => true,
				// 'value'    => array( 'product', 'product_variation' ),
				'value'    => array( 'product' ),
			// 'message'  => __( 'The WooCommerce Shop only supports the "Product" and "Product Variation" post types.', 'search-filter' ),
			);
		}
		return $query_post_types;
	}
	/**
	 * Add the results setting.
	 *
	 * @since 3.0.0
	 */
	public static function add_results_setting() {
		$setting = array(
			'name'         => 'resultsUrlWoocommerce',
			'type'         => 'info',
			'group'        => 'location',
			'label'        => __( 'Shop Link', 'search-filter' ),
			'help'         => __( 'This is your WooCommerce shop URL', 'search-filter' ),
			'loadingText'  => __( 'Fetching...', 'search-filter' ),
			'inputType'    => 'Info',
			'dataProvider' => array(
				'route' => '/settings/results-url',
				'args'  => array(
					'integrationType',
					'archiveType',
					'taxonomy',
					'resultsUrlSingle',
				),
			),
			'dependsOn'    => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'woocommerce/shop',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );
	}
	/**
	 * Add the results setting.
	 *
	 * @since 3.0.0
	 */
	public static function add_taxonomy_settings() {
		$settings = array(
			array(
				'name'      => 'woocommerceTaxOrderBy',
				'label'     => __( 'Order By', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Default', 'search-filter' ),
						'value' => 'default',
					),
					array(
						'label' => __( 'ID', 'search-filter' ),
						'value' => 'id',
					),
					array(
						'label' => __( 'Name', 'search-filter' ),
						'value' => 'name',
					),
					array(
						'label' => __( 'Slug', 'search-filter' ),
						'value' => 'slug',
					),
					array(
						'label' => __( 'Count', 'search-filter' ),
						'value' => 'count',
					),
					array(
						'label' => __( 'Term Group', 'search-filter' ),
						'value' => 'term_group',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'      => 'woocommerceTaxOrderDir',
				'label'     => __( 'Order Direction', 'search-filter' ),
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'admin/field/search', 'block/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Inherit', 'search-filter' ),
						'value' => 'inherit',
					),
					array(
						'label' => __( 'Ascending', 'search-filter' ),
						'value' => 'asc',
					),
					array(
						'label' => __( 'Descending', 'search-filter' ),
						'value' => 'desc',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),

			array(
				'name'      => 'woocommerceTaxTermsConditions',
				'label'     => __( 'Options Conditions', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Select',
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'all',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'Show all options', 'search-filter' ),
						'value' => 'all',
					),
					array(
						'label' => __( 'Restrict options', 'search-filter' ),
						'value' => 'include_terms',
					),
					array(
						'label' => __( 'Exclude options', 'search-filter' ),
						'value' => 'exclude_terms',
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'         => 'woocommerceTaxTerms',
				'label'        => __( 'Options', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'number',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array(),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'woocommerceTaxTermsConditions',
									'compare' => '=',
									'value'   => 'include_terms',
								),
								array(
									'option'  => 'woocommerceTaxTermsConditions',
									'compare' => '=',
									'value'   => 'exclude_terms',
								),
							),
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/woocommerce/taxonomy-terms',
					'args'  => array(
						'dataWoocommerce',
					),
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
		);

		foreach ( $settings as $setting ) {
			Fields_Settings::add_setting( $setting );
		}
	}

	/**
	 * Add WC shop as a query integration type.
	 *
	 * @since    3.0.0
	 */
	public static function add_shop_integration_type() {
		// Get the object for the data_type setting so we can grab its options.
		$integration_type_setting = Queries_Settings::get_setting( 'integrationType' );
		if ( $integration_type_setting ) {
			// Create the option.
			$wc_integration_type_option = array(
				'label' => __( 'WooCommerce Shop', 'search-filter' ),
				'value' => 'woocommerce/shop',
			);
			$integration_type_setting->add_option( $wc_integration_type_option );
		}
		// Add tax archive filter capability.
		$archive_filter_taxonomies_setting = Queries_Settings::get_setting( 'archiveFilterTaxonomies' );
		if ( $archive_filter_taxonomies_setting ) {
			$depends_conditions = array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'woocommerce/shop',
					),
				),
			);
			$archive_filter_taxonomies_setting->add_depends_condition( $depends_conditions );
		}
	}
	/**
	 * Add WC shop as a query integration type.
	 *
	 * @since    3.0.0
	 */
	public static function add_query_integration() {
		// get the object for the data_type setting so we can grab its options.
		$integration_type_setting = Queries_Settings::get_setting( 'integrationType' );
		if ( $integration_type_setting ) {
			// create the option.
			$wc_integration_type_option = array(
				'label' => __( 'WooCommerce Shop', 'search-filter' ),
				'value' => 'woocommerce/shop',
			);
			$integration_type_setting->add_option( $wc_integration_type_option );
		}
	}

	/**
	 * Add the single integrations.
	 *
	 * @since 3.0.0
	 */
	public static function add_single_integrations() {
		// get the object for the data_type setting so we can grab its options.
		$integration_type_setting = Queries_Settings::get_setting( 'queryIntegration' );
		if ( $integration_type_setting ) {
			$wc_integration_type_option = array(
				'label'     => __( 'WooCommerce Collections block', 'search-filter' ),
				'value'     => 'woocommerce/products_query_block',
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'dynamic',
						),
					),
				),
			);
			$integration_type_setting->add_option( $wc_integration_type_option );

			$wc_integration_type_option = array(
				'label'     => __( 'WooCommerce Products Shortcode', 'search-filter' ),
				'value'     => 'woocommerce/products_shortcode',
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'dynamic',
						),
					),
				),
			);
			$integration_type_setting->add_option( $wc_integration_type_option );
		}
	}
	/**
	 * Add WC as a data type
	 *
	 * @since    3.0.0
	 */
	public static function modify_data_type() {
		// get the object for the data_type setting so we can grab its options.
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );
		if ( $data_type_setting ) {
			// Create the option.
			$wc_data_type_option = array(
				'label'     => __( 'WooCommerce', 'search-filter' ),
				'value'     => 'woocommerce',
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'type',
							'compare' => '!=',
							'value'   => 'advanced',
						),
					),
				),
			);
			$data_type_setting->add_option( $wc_data_type_option );
		}
	}
	/**
	 * Add WC as a data type
	 *
	 * @since    3.0.0
	 */
	public static function modify_query_integration() {
		// get the object for the data_type setting so we can grab its options.
		$query_integration_setting = Queries_Settings::get_setting( 'queryIntegration' );
		if ( ! $query_integration_setting ) {
			return;
		}

		$setting_data = $query_integration_setting->get_data();
		$depends_on   = array(
			'relation' => 'OR',
			'rules'    => array(),
		);
		if ( isset( $setting_data['dependsOn'] ) ) {
			$depends_on = $setting_data['dependsOn'];
		}

		$depends_on['rules'][]     = array(
			'option'  => 'integrationType',
			'compare' => '!=',
			'value'   => 'woocommerce/shop',
		);
		$setting_data['dependsOn'] = $depends_on;
		$query_integration_setting->update( $setting_data );
	}

	/**
	 * Add the taxonomy archive support.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 * @param array $args The args.
	 *
	 * @return array The setting.
	 */
	public static function add_use_taxonomy_archive_support( $setting ) {
		if ( $setting['name'] !== 'taxonomyFilterArchive' ) {
			return $setting;
		}
		if ( ! isset( $setting['dependsOn'] ) ) {
			return $setting;
		}
		if ( ! isset( $setting['dependsOn']['rules'] ) ) {
			return $setting;
		}
		// Also add the field support for taxonomy filter archive.

		// Build conditions for all taxonomy attributes.
		$wc_tax_attributes   = \wc_get_attribute_taxonomies();
		$taxonomy_conditions = array(
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_tag',
				'compare' => '=',
			),
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_cat',
				'compare' => '=',
			),
			array(
				'option'  => 'dataWoocommerce',
				'value'   => 'product_brand',
				'compare' => '=',
			),
		);
		foreach ( $wc_tax_attributes as $attribute ) {
			$taxonomy_conditions[] = array(
				'option'  => 'dataWoocommerce',
				'value'   => 'attribute:' . $attribute->attribute_name,
				'compare' => '=',
			);
		}

		$depends_conditions = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'relation' => 'OR',
					'rules'    => $taxonomy_conditions,
				),
				array(
					'store'   => 'query',
					'option'  => 'integrationType',
					'compare' => '=',
					'value'   => 'woocommerce/shop',
				),
				array(
					'store'   => 'query',
					'option'  => 'archiveFilterTaxonomies',
					'compare' => '=',
					'value'   => 'yes',
				),
			),
		);

		$setting['dependsOn']['rules'][] = $depends_conditions;
		return $setting;
	}
	/**
	 * Gets the options for our data type field.
	 *
	 * @return array
	 */
	public static function get_data_type_options() {
		$data_options      = array(
			array(
				'label' => __( 'Tags', 'search-filter' ),
				'value' => 'product_tag',
			),
			array(
				'label' => __( 'Categories', 'search-filter' ),
				'value' => 'product_cat',
			),
			array(
				'label' => __( 'Brands', 'search-filter' ),
				'value' => 'product_brand',
			),
		);
		$wc_tax_attributes = \wc_get_attribute_taxonomies();
		foreach ( $wc_tax_attributes as $attribute ) {
			$data_options[] = array(
				'value' => 'attribute:' . $attribute->attribute_name,
				// Translators: %s is the attribute name.
				'label' => sprintf( __( 'Attribute: %1$s', 'search-filter' ), $attribute->attribute_label ),
			);
		}

		$data_options = apply_filters( 'search-filter/integrations/woocommerce/get_data_type_options', $data_options );

		return $data_options;
	}
	/**
	 * Creates a WC setting which is only shown when the WC data_type
	 * is selected
	 *
	 * @since    3.0.0
	 */
	public static function add_wc_setting() {

		$data_options = self::get_data_type_options();

		$setting = array(
			'name'        => 'dataWoocommerce',
			'label'       => __( 'Data Source', 'search-filter' ),
			'description' => __( 'Select a WooCommerce data type', 'search-filter' ),
			'group'       => 'data',
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Select',
			'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'placeholder' => __( 'Choose WooCommerce source', 'search-filter' ),
			'options'     => $data_options,
			'isDataType'  => true,
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'woocommerce',
					),
				),
			),
			'supports'    => array(
				'previewAPI' => true,
			),
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'dataType',
			),
		);
		Fields_Settings::add_setting( $setting, $setting_args );
	}

	/**
	 * Add the shortcode attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $out The existing shortcode attributes.
	 * @param array $pairs The shortcode pairs.
	 * @param array $atts The shortcode attributes.
	 *
	 * @return array The updated shortcode attributes.
	 */
	public static function shortcode_attributes( $out, $pairs, $atts ) {
		if ( ! isset( $atts['search_filter_query_id'] ) ) {
			return $out;
		}
		if ( self::$active_query_id !== 0 ) {
			return $out;
		}
		// Remove products shortcode caching.
		$out['cache']          = false;
		self::set_active_query_id( absint( $atts['search_filter_query_id'] ) );
		add_filter( 'woocommerce_shortcode_products_query', 'Search_Filter\\Integrations\\WooCommerce::add_query_to_args', 10, 1 );
		// Add `search_filter_query_id` to the shortcode attributes, otherwise they'll be removed by WC.
		$out['search_filter_query_id'] = $atts['search_filter_query_id'];
		return $out;
	}

	/**
	 * Add the query to the args.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The existing query args.
	 *
	 * @return array The updated query args.
	 */
	public static function add_query_to_args( $query_args ) {
		// Remove products shortcode caching.
		if ( self::$active_query_id === 0 ) {
			return $query_args;
		}
		$query_args['search_filter_query_id'] = self::$active_query_id;

		// Remove products shortcode caching.
		remove_filter( 'woocommerce_shortcode_products_query', 'Search_Filter\\Integrations\\WooCommerce::add_query_to_args', 10, 1 );
		return $query_args;
	}

	/**
	 * Get the active query ID.
	 *
	 * @since 3.0.0
	 *
	 * @return int The active query ID.
	 */
	public static function get_active_query_id() {
		return self::$active_query_id;
	}

	/**
	 * Finish the shortcode loop and reset the active query ID.
	 *
	 * @since 3.0.0
	 */
	public static function finish_shortcode_loop() {
		self::reset_active_query_id();
	}

	/**
	 * Track the query data for shortcode - the query data is mostly used for
	 * paginating when using the "load more" field.
	 *
	 * It doesn't use `loop_start` so we need to use this hook to access and
	 * reference the query data.
	 *
	 * @param array  $results The results data.
	 * @param Object $shortcode_class The shortcode class.
	 *
	 * @return array The results.
	 */
	public static function track_wc_query_data( $results, $shortcode_class ) {
		$query_args = $shortcode_class->get_query_args();
		if ( ! isset( $query_args['search_filter_query_id'] ) ) {
			return $results;
		}
		$query_id = $query_args['search_filter_query_id'];
		$query    = \Search_Filter\Queries\Query::find( array( 'id' => $query_id ) );

		if ( ! $query ) {
			return $results;
		}

		$query->set_render_config_value( 'paginationKey', 'product-page' );
		$query->set_render_config_value( 'currentPage', $results->current_page );
		$query->set_render_config_value( 'maxPages', $results->total_pages );
		$query->set_render_config_value( 'postsPerPage', $results->per_page );
		$query->set_render_config_value( 'foundPosts', $results->total );

		return $results;
	}
}
