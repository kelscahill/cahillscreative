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
use Search_Filter\Integrations\Woocommerce\Rest_API;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;
use Search_Filter\Util;

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
		// Update enabled status.
		$woocommerce_integration = Integrations_Settings::get_setting( 'woocommerce' );
		if ( ! $woocommerce_integration ) {
			return;
		}

		$woocommerce_enabled         = class_exists( 'WooCommerce' );
		$update_integration_settings = array(
			'isIntegrationEnabled' => $woocommerce_enabled,
		);
		// If we detect WC is enabled, then lets also set the pluign installed
		// property to true - because a plugin could be installed using a different
		// folder name and it we would initially detect is as not installed by using
		// by using `plugin_exists()` which is unreliable.
		if ( $woocommerce_enabled ) {
			$update_integration_settings['isIntegrationInstalled'] = true;
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
		// Add support for taxonomy related settings.
		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );

		// Filter the setting early to add our depends conditions for tax archive support.
		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_woocommerce_data_type' ), 11, 1 );
		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_use_taxonomy_archive_support' ), 11, 1 );
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

		// We don't want to add this query filter in the admin.
		if ( Util::is_frontend_request() ) {
			self::add_query_sort_filter();
		}

		add_filter( 'search-filter/queries/query/get_results_data', array( __CLASS__, 'get_results_data' ), 10, 2 );
		add_filter( 'search-filter/queries/query/get_results_url/override_url', array( __CLASS__, 'get_results_url' ), 10, 2 );
		add_filter( 'search-filter/queries/query/apply_wp_query_args', array( __CLASS__, 'make_post_types_singular_on_shop' ), 10, 2 );
		add_filter( 'search-filter/queries/query/apply_wp_query_args', array( __CLASS__, 'remove_wc_ordering_args' ), 10, 2 );

		add_filter( 'search-filter/rest-api/get_query_post_types', array( __CLASS__, 'get_query_post_types' ), 10, 2 );
		add_filter( 'search-filter/rest-api/get_query_archive_taxonomies/post_type', array( __CLASS__, 'get_query_archive_taxonomies_post_type' ), 10, 2 );
		add_filter( 'search-filter/query/selector/should_attach', array( __CLASS__, 'attach_query' ), 10, 3 );
		add_filter( 'search-filter/fields/choice/options', array( __CLASS__, 'choice_options' ), 10, 2 );
		add_filter( 'search-filter/fields/field/url_name', array( __CLASS__, 'field_url_name' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/wp_query_args', array( __CLASS__, 'choice_wp_query_args' ), 10, 2 );

		// Filter the WC Products block.
		add_filter( 'pre_render_block', array( __CLASS__, 'pre_render_products_block' ), 10, 2 );
		// Remove hook from the WC Products block.
		add_filter( 'render_block', array( __CLASS__, 'cleanup_query_block' ), 10, 2 );

		// Add support WC products shortcode.
		add_filter( 'shortcode_atts_products', array( __CLASS__, 'shortcode_attributes' ), 11, 3 );
		add_action( 'woocommerce_shortcode_after_products_loop', array( __CLASS__, 'finish_shortcode_loop' ), 21 );
		add_action( 'woocommerce_shortcode_products_loop_no_results', array( __CLASS__, 'finish_shortcode_loop' ), 21 );
		add_filter( 'woocommerce_shortcode_products_query_results', array( __CLASS__, 'track_wc_query_data' ), 10, 2 );

		// Add support for taxonomy filter archive.
		add_filter( 'search-filter/fields/field/url_template', array( __CLASS__, 'field_url_template' ), 10, 2 );
		add_action( 'search-filter/queries/query/init_render_config_values', array( __CLASS__, 'init_render_config_values' ), 10, 1 );
		add_filter( 'search-filter/fields/field/parse_url_value', array( __CLASS__, 'parse_url_value' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/navigates_taxonomy_archive', array( __CLASS__, 'navigates_taxonomy_archive' ), 10, 2 );
		add_filter( 'search-filter/queries/query/can_apply_at_current_location', array( __CLASS__, 'can_apply_query_at_current_location' ), 10, 2 );
		Rest_API::init();
	}

	/**
	 * Get the URL template for a field based on WooCommerce taxonomy settings.
	 *
	 * @param array  $url_template The current URL template.
	 * @param object $field        The field object.
	 * @return array The updated URL template.
	 */
	public static function field_url_template( $url_template, $field ) {

		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return $url_template;
		}

		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' && $field->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			return $url_template;
		}

		// Bail early if the attribute on the field is not enabled.
		if ( $field->get_attribute( 'taxonomyNavigatesArchive' ) !== 'yes' ) {
			return $url_template;
		}

		$query = Query::get_instance( $field->get_query_id() );

		if ( is_wp_error( $query ) ) {
			return $url_template;
		}

		if ( ! self::is_woocommerce_query( $query ) ) {
			return $url_template;
		}

		$taxonomy_name = self::get_taxonomy_from_field( $field );

		if ( empty( $taxonomy_name ) ) {
			return $url_template;
		}

		$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
		// Ensure query has filtering taxonomy archives enabled.
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return $url_template;
		}

		// If filtering on specific archives only, ensure one of those matches.
		if ( $archive_filter_taxonomies === 'custom' ) {
			$archive_post_type_taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
			if ( ! in_array( $taxonomy_name, $archive_post_type_taxonomies, true ) ) {
				return $url_template;
			}
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

	/**
	 * Initialize render configuration values for WooCommerce queries.
	 *
	 * @param Query $query The query object.
	 */
	public static function init_render_config_values( $query ) {
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return;
		}

		$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return;
		}

		global $wp_query;
		if ( ! $wp_query ) {
			return;
		}

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

		$taxonomies = array();
		// Get all the taxonomies for the post type.
		if ( $archive_filter_taxonomies === 'all' ) {
			$taxonomies = get_object_taxonomies( $archive_post_type );
		} elseif ( $archive_filter_taxonomies === 'custom' ) {
			// Get the custom list of taxonomies.
			$taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
		}

		$tax_archive_url = '';
		$tax_slug        = '';
		$term_slug       = '';
		foreach ( $taxonomies as $taxonomy ) {
			if ( $queried_object->taxonomy === $taxonomy ) {
				$tax_archive_url = get_term_link( $queried_object->term_id );
				$tax_slug        = $taxonomy;
				$term_slug       = $queried_object->slug;
				break;
			}
		}

		$query->set_render_config_value( 'currentTaxonomyArchive', $tax_slug );
		$query->set_render_config_value( 'currentTaxonomyTermArchive', $term_slug );
		$query->set_render_config_value( 'taxonomyArchiveUrl', $tax_archive_url );
	}

	/**
	 * Parse the URL value for taxonomy archive navigation.
	 *
	 * @param mixed  $value The current value.
	 * @param object $field The field object.
	 * @return mixed The parsed value.
	 */
	public static function parse_url_value( $value, $field ) {

		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return $value;
		}

		if ( $field->get_attribute( 'taxonomyNavigatesArchive' ) !== 'yes' ) {
			return $value;
		}

		$query = Query::get_instance( $field->get_query_id() );

		if ( is_wp_error( $query ) ) {
			return $value;
		}

		if ( ! self::is_woocommerce_shop_query( $query ) ) {
			return $value;
		}

		$taxonomy_name = self::get_taxonomy_from_field( $field );

		if ( empty( $taxonomy_name ) ) {
			return $value;
		}

		// Ensure query has filtering taxonomy archives enabled.
		$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return $value;
		}

		// If filtering on specific archives only, ensure one of those matches.
		if ( $archive_filter_taxonomies === 'custom' ) {
			$archive_post_type_taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
			if ( ! in_array( $taxonomy_name, $archive_post_type_taxonomies, true ) ) {
				return $value;
			}
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
	 * Check if a field navigates to a taxonomy archive page.
	 *
	 * @param mixed $navigates_taxonomy_archive Current navigation status.
	 * @param Field $field                      The field object.
	 * @return mixed The updated navigation status.
	 */
	public static function navigates_taxonomy_archive( $navigates_taxonomy_archive, $field ) {

		// Support regular taxonomies as  well as woocommerce taxonomies.
		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' && $field->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			return $navigates_taxonomy_archive;
		}

		if ( $field->get_attribute( 'taxonomyNavigatesArchive' ) !== 'yes' ) {
			return $navigates_taxonomy_archive;
		}

		// Only single select fields can be tax archive filters (for now).
		if ( ! method_exists( $field, 'is_single_select' ) || ! $field->is_single_select() ) {
			return $navigates_taxonomy_archive;
		}

		$query = Query::get_instance( $field->get_query_id() );

		if ( is_wp_error( $query ) ) {
			return $navigates_taxonomy_archive;
		}

		if ( ! self::is_woocommerce_query( $query ) ) {
			return $navigates_taxonomy_archive;
		}

		$taxonomy_name = self::get_taxonomy_from_field( $field );

		if ( ! empty( $taxonomy_name ) ) {
			// We need to double check that a query supports the taxonomy archive specified
			// by the field - this can change if a user updatees the query after creating
			// the field.

			// We need to check that the the taxonomy is enabled in the query settings.
			$archive_filter_taxonomies   = $query->get_attribute( 'archiveFilterTaxonomies' );
			$can_filter_taxonomy_archive = false;
			if ( $archive_filter_taxonomies === 'all' ) {
				$can_filter_taxonomy_archive = true;
			} elseif ( $archive_filter_taxonomies === 'custom' ) {
				$archive_post_type_taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
				if ( in_array( $taxonomy_name, $archive_post_type_taxonomies, true ) ) {
					$can_filter_taxonomy_archive = true;
				}
			}

			// If the query is a WC shop archive, and all taxonomies has been selected, or thee current taxonomy
			// was specifically selected, then the field can be a taxonomy archive filter.
			if ( $can_filter_taxonomy_archive ) {
				return $taxonomy_name;
			}

			return $navigates_taxonomy_archive;
		}

		return $navigates_taxonomy_archive;
	}


	/**
	 * Can apply query at current location.
	 *
	 * @param bool                         $can_apply Whether the query can be applied.
	 * @param \Search_Filter\Queries\Query $query     The query object.
	 * @return bool
	 */
	public static function can_apply_query_at_current_location( bool $can_apply, \Search_Filter\Queries\Query $query ) {
		if ( $query->get_attribute( 'integrationType' ) !== 'woocommerce/shop' ) {
			return $can_apply;
		}
		global $wp_query;
		if ( self::is_shop( $wp_query ) ) {
			return true;
		}

		// Check if query is filtering taxonomy archives.
		$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return $can_apply;
		}

		// Check if we're on the user specified custom taxonomy archives.
		if ( $archive_filter_taxonomies === 'custom' ) {
			$taxonomies = $query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();

			foreach ( $taxonomies as $taxonomy ) {
				if ( is_tax( $taxonomy ) ) {
					return true;
				}
			}
			return $can_apply;
		}
		// Otherwise check to see if we're on _any_ WC tax archive.
		return self::is_taxonomy_archive();
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
	public static function cleanup_query_block( string $block_content, array $block ) {

		// WC is moving to the new product collection block.
		if ( $block['blockName'] === 'woocommerce/product-collection' ) {
			\Search_Filter\Integrations\Gutenberg::cleanup_query_block( $block );
			return $block_content;
		}

		// Now check for the old product query block.
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
	 * @param string|null $pre_render Whether to short-circuit the block rendering.
	 * @param array       $block      The block attributes.
	 *
	 * @return string|null
	 */
	public static function pre_render_products_block( $pre_render, array $block ) {

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

		return $pre_render;
	}
	/**
	 * On S&F settings register, add a new setting + update others
	 *
	 * @since    3.0.0
	 */
	public static function register_query_settings() {
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
	}

	/**
	 * Check if a data source is a taxonomy attribute.
	 *
	 * @param string $data_source The data source to check.
	 * @return bool True if it's a taxonomy attribute.
	 */
	public static function data_source_is_taxonomy_attribute( $data_source ) {
		if ( substr( $data_source, 0, 10 ) === 'attribute:' ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the connected taxonomy name from the field.
	 *
	 * Handles regular  taxonomies and WC specific data attribute taxonomies.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field object.
	 *
	 * @return string The taxonomy name.
	 */
	private static function get_taxonomy_from_field( $field ) {

		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' && $field->get_attribute( 'dataType' ) !== 'taxonomy' ) {
			return '';
		}

		if ( $field->get_attribute( 'dataType' ) === 'woocommerce' ) {
			return self::get_taxonomy_name_from_data_source( $field->get_attribute( 'dataWoocommerce' ) );
		}
		return $field->get_attribute( 'dataTaxonomy' );
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
		if ( empty( $data_source ) ) {
			return $taxonomy_name;
		}
		if ( $data_source === 'product_cat' ) {
			$taxonomy_name = 'product_cat';
		} elseif ( $data_source === 'product_tag' ) {
			$taxonomy_name = 'product_tag';
		} elseif ( $data_source === 'product_brand' ) {
			$taxonomy_name = 'product_brand';
		}

		// If data source starts with `attribute:`.
		if ( self::data_source_is_taxonomy_attribute( $data_source ) ) {
			$attribute_name = substr( $data_source, 10 );
			// @phpstan-ignore function.notFound
			$taxonomy_name = \wc_attribute_taxonomy_name( $attribute_name );
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
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for WooCommerce product attribute filtering.
			$query_args['tax_query'] = array();
		}

		// TODO - figure out how to handle this in relation to other taxonomies being set
		// in the query already (ie via the loop block).
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for WooCommerce product attribute filtering.
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
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for WooCommerce product attribute filtering.
			$query_args['tax_query'][] = $sub_tax_query;
		} else {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for WooCommerce product attribute filtering.
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
		if ( self::data_source_is_taxonomy_attribute( $data_source ) ) {
			$attribute_name = substr( $data_source, 10 );
			// @phpstan-ignore function.notFound
			$taxonomy_name = \wc_attribute_taxonomy_name( $attribute_name );
			return $taxonomy_name;
		}

		return $url_name;
	}

	/**
	 * Populate the options in the fields that need them.
	 *
	 * @since    3.0.0
	 *
	 * @param array                        $options The existing options data.
	 * @param \Search_Filter\Fields\Choice $field   The field instance.
	 *
	 * @return array The updated options.
	 */
	public static function choice_options( array $options, $field ) {

		if ( $field->get_attribute( 'dataType' ) !== 'woocommerce' ) {
			return $options;
		}

		if ( count( $options ) > 0 ) {
			return $options;
		}

		$data_source = $field->get_attribute( 'dataWoocommerce' );

		// Check if we can get a taxonomy name from the data source.
		$wc_taxonomy_name = self::get_taxonomy_name_from_data_source( $data_source );

		// Only support taxonomies.
		if ( empty( $wc_taxonomy_name ) ) {
			return $options;
		}

		$order_dir = '';
		if ( $field->get_attribute( 'woocommerceTaxOrderDir' ) === 'asc' ) {
			$order_dir = 'ASC';
		} elseif ( $field->get_attribute( 'woocommerceTaxOrderDir' ) === 'desc' ) {
			$order_dir = 'DESC';
		}
		$args = array(
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

		$args = apply_filters( 'search-filter/fields/choice/create_options/get_terms_args', $args, $field );

		$options = $field->get_taxonomy_options( $wc_taxonomy_name, $args );

		// Set value_labels for active values so the Selection field can display them.
		$values = $field->get_values();
		foreach ( $options as $option ) {
			if ( in_array( $option['value'], $values, true ) ) {
				$field->set_value_labels( array( $option['value'] => $option['label'] ) );
			}
		}

		return $options;
	}

	/**
	 * Get all WooCommerce taxonomy option values.
	 *
	 * @return array Array of taxonomy slugs.
	 */
	public static function get_taxonomies_options_values() {
		// Get all the taxonomies registered by WooCommerce.
		$wc_taxonomies = array(
			'product_cat',
			'product_tag',
			'product_brand',
		);

		// @phpstan-ignore function.notFound
		$wc_tax_attributes = \wc_get_attribute_taxonomies();
		foreach ( $wc_tax_attributes as $attribute ) {
			$wc_taxonomies[] = 'attribute:' . $attribute->attribute_name;
		}

		return $wc_taxonomies;
	}
	/**
	 * Create the conditions for the taxonomy attributes.
	 *
	 * @since 3.0.0
	 *
	 * @return array The conditions.
	 */
	private static function create_taxonomy_depends_conditions() {
		// Build conditions for all taxonomy attributes.
		$taxonomy_conditions       = array();
		$taxonomies_options_values = self::get_taxonomies_options_values();
		foreach ( $taxonomies_options_values as $taxonomy_value ) {
			$taxonomy_conditions[] = array(
				'option'  => 'dataWoocommerce',
				'value'   => $taxonomy_value,
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

			// Add support for the WC data type.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataType', array( 'woocommerce' => true ) );

			// Support WC taxonomies.
			$wc_taxonomies_values   = self::get_taxonomies_options_values();
			$setting_support_values = array();
			foreach ( $wc_taxonomies_values as $taxonomy_value ) {
				$setting_support_values[ $taxonomy_value ] = true;
			}
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataWoocommerce', $setting_support_values );

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
						'rules'    => self::create_taxonomy_depends_conditions(),
					),
				),
			);

			$setting_support['showCount']                       = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'showCount', $taxonomy_field_conditions, false ),
			);
			$setting_support['hideEmpty']                       = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'hideEmpty', $taxonomy_field_conditions, false ),
			);
			$setting_support['taxonomyHierarchical']            = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'taxonomyHierarchical', $taxonomy_field_conditions, false ),
			);
			$setting_support['dataWoocommerceDimensionsNotice'] = true;
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
		// @phpstan-ignore function.notFound
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
		// @phpstan-ignore function.notFound
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

		$integration_type = $saved_query->get_attribute( 'integrationType' );
		if ( $integration_type !== 'woocommerce/shop' ) {
			return $should_attach;
		}
		if ( self::is_shop( $query ) ) {
			self::set_active_query_id( $saved_query->get_id() );
			return true;
		}

		// Check if query is filtering taxonomy archives.
		$archive_filter_taxonomies = $saved_query->get_attribute( 'archiveFilterTaxonomies' );
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return $should_attach;
		}

		// Check if we're on the user specified custom taxonomy archives.
		if ( $archive_filter_taxonomies === 'custom' ) {
			$taxonomies = $saved_query->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();

			foreach ( $taxonomies as $taxonomy ) {
				if ( is_tax( $taxonomy ) ) {
					self::set_active_query_id( $saved_query->get_id() );
					return true;
				}
			}
			return $should_attach;
		}

		// Then we want to get all product taxonomies and check if we are on one
		// of their archives.
		if ( self::is_taxonomy_archive() ) {
			self::set_active_query_id( $saved_query->get_id() );
			return true;
		}

		return $should_attach;
	}

	/**
	 * Set the active query ID for WooCommerce integration.
	 *
	 * @param int $query_id The query ID to set as active.
	 */
	public static function set_active_query_id( $query_id ) {
		self::$previous_active_query_id = self::$active_query_id;
		self::$active_query_id          = $query_id;
	}

	/**
	 * Reset the active query ID to the previous value.
	 */
	public static function reset_active_query_id() {
		self::$active_query_id          = self::$previous_active_query_id;
		self::$previous_active_query_id = 0;
	}

	/**
	 * Check if the current page is a WooCommerce taxonomy archive.
	 *
	 * @return bool True if on a WooCommerce taxonomy archive.
	 */
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
		/**
		 * Get WooCommerce attribute taxonomies.
		 *
		 * @phpstan-ignore-next-line
		 */
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
			/**
			 * Check if this is the shop page.
			 *
			 * @phpstan-ignore-next-line
			 */
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
	 * @param Query $query      The query object.
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
	 * Removes the default ordering arguments set by WooCommerce if ordering
	 * options have been set in the Search & Filter Query.
	 *
	 * @param array $query_args The query args.
	 * @param Query $query      The query object.
	 *
	 * @return array The updated query args.
	 */
	public static function remove_wc_ordering_args( $query_args, $query ) {

		if ( ! self::is_woocommerce_query( $query ) ) {
			return $query_args;
		}

		// The WC ordering field uses the orderby url arg, if it's set
		// support it.
		if ( isset( $_GET['orderby'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $query_args;
		}

		// Figure out if our query (or any of its fields) is trying to apply ordering.
		$has_search_filter_ordering = false;

		$sort_order = $query->get_attribute( 'sortOrder' );
		if ( ! empty( $sort_order ) ) {
			$has_search_filter_ordering = true;
		} else {
			$fields = $query->get_fields();
			foreach ( $fields as $field ) {
				if ( is_wp_error( $field ) ) {
					continue;
				}
				// Check to see if we have a sort order field, that's been set.
				if ( $field->get_attribute( 'type' ) !== 'control' ) {
					continue;
				}
				if ( $field->get_attribute( 'controlType' ) !== 'sort' ) {
					continue;
				}
				if ( empty( $field->get_values() ) ) {
					continue;
				}
				$has_search_filter_ordering = true;
				break;
			}
		}

		// If we're not applying any kind of ordering then we don't need to remove WC ordering.
		if ( ! $has_search_filter_ordering ) {
			return $query_args;
		}

		// We want to use our own ordering, so remove the default WC ordering args which can be set
		// via the customizer.
		self::remove_wc_query_ordering_args();

		return $query_args;
	}

	/**
	 * Remove the WC query ordering args.
	 *
	 * Check everything exists before trying to use it as we're not
	 * quite sure how long `WC()` has been around for and if it's
	 * an API we really should be using or not.
	 */
	private static function remove_wc_query_ordering_args() {

		// The WC() function is not well documented, so lets make sure everything exists
		// before trying to use it.

		if ( ! function_exists( '\WC' ) ) {
			return;
		}

		$wc_instance = \WC();

		if ( ! property_exists( $wc_instance, 'query' ) ) {
			return;
		}

		if ( ! method_exists( $wc_instance->query, 'remove_ordering_args' ) ) {
			return;
		}

		// Finally, remove the ordering args.
		$wc_instance->query->remove_ordering_args();
	}

	/**
	 * Wrapper function to make it easier to remove the query sort filter.
	 *
	 * @return void
	 */
	private static function remove_query_sort_filter() {
		remove_filter( 'search-filter/queries/query/get_attribute', array( __CLASS__, 'remove_query_sort_attribute' ), 10 );
	}

	/**
	 * Wrapper function to make it easier to add the query sort filter.
	 *
	 * @return void
	 */
	private static function add_query_sort_filter() {
		add_filter( 'search-filter/queries/query/get_attribute', array( __CLASS__, 'remove_query_sort_attribute' ), 10, 3 );
	}
	/**
	 * Removes the default ordering arguments set by WooCommerce if ordering
	 * options have been set in the Search & Filter Query.
	 *
	 * @param mixed                        $attribute The attribute value.
	 * @param string                       $attribute_name The attribute name.
	 * @param \Search_Filter\Queries\Query $query The query.
	 *
	 * @return mixed The updated query args.
	 */
	public static function remove_query_sort_attribute( $attribute, string $attribute_name, \Search_Filter\Queries\Query $query ) {

		// Remove this filter to prevent infinite loop on subsequent `get_attribute()`
		// calls in `is_woocommerce_query()`.
		self::remove_query_sort_filter();
		$is_woocommerce_query = self::is_woocommerce_query( $query );
		self::add_query_sort_filter();

		if ( ! $is_woocommerce_query ) {
			return $attribute;
		}

		if ( $attribute_name !== 'sortOrder' ) {
			return $attribute;
		}

		// If `orderby` is in the URL, then we can assume the user is trying to use
		// the native WC ordering filter which we should not override. Unset our
		// ordering attributes to allow the native WC ordering to work.
		if ( isset( $_GET['orderby'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Return null to bypass our ordering.
			return null;
		}

		return $attribute;
	}


	/**
	 * Checks if a Search & Filter Query is a WooCommerce query.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Queries\Query $query The query instance.
	 *
	 * @return bool True if the query is a WooCommerce query, false if not.
	 */
	public static function is_woocommerce_query( $query ) {
		$post_types = $query->get_attribute( 'postTypes' );
		if ( ! $post_types ) {
			return false;
		}
		if ( in_array( 'product', $post_types, true ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Checks if a Search & Filter Query is a WooCommerce archive query.
	 *
	 * Post type archives (with product set) or the WooCommerce shop page count
	 * as a WooCommerce archive query.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Queries\Query $query The query instance.
	 *
	 * @return bool True if the query is a WooCommerce query, false if not.
	 */
	public static function is_woocommerce_shop_query( $query ) {

		$integration_type = $query->get_attribute( 'integrationType' );
		if ( $integration_type === 'woocommerce/shop' ) {
			return true;
		}

		$archive_type      = $query->get_attribute( 'archiveType' );
		$archive_post_type = $query->get_attribute( 'archivePostType' );
		if ( $integration_type === 'archive' && $archive_type === 'post_type' && $archive_post_type === 'product' ) {
			return true;
		}

		return false;
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
				'value'    => array( 'product' ),
			);
		}
		return $query_post_types;
	}
	/**
	 * Override the post type used for looking up a queries taxonomy archives.
	 *
	 * Changes the post type to product when using the WooCommerce shop integration
	 * so that taxonomies connected to the product post type are available.
	 *
	 * @since    3.0.0
	 *
	 * @param string $post_type The existing post type.
	 * @param array  $params    The query params.
	 *
	 * @return string The updated post type.
	 */
	public static function get_query_archive_taxonomies_post_type( $post_type, $params ) {

		$integration_type = isset( $params['integrationType'] ) ? $params['integrationType'] : '';

		if ( $integration_type !== 'woocommerce/shop' ) {
			return $post_type;
		}
		return 'product';
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
					'archiveTaxonomy',
					'archivePostType',
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'default'   => 'inherit',
				'type'      => 'string',
				'inputType' => 'Select',
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'      => array( 'admin/field', 'block/field' ),
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
	 *
	 * @return array The setting.
	 */
	public static function add_woocommerce_data_type( array $setting ) {
		if ( $setting['name'] !== 'dataType' ) {
			return $setting;
		}

		if ( ! is_array( $setting['options'] ) ) {
			return $setting;
		}

		$setting['options'][] = array(
			'label'     => __( 'WooCommerce', 'search-filter' ),
			'value'     => 'woocommerce',
			'dependsOn' => array(
				'relation' => 'OR',
				'action'   => 'disable',
			),
		);

		return $setting;
	}
	/**
	 * Add the taxonomy archive support.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function add_use_taxonomy_archive_support( array $setting ) {
		if ( $setting['name'] !== 'taxonomyNavigatesArchive' ) {
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
		$wc_taxonomy_conditions   = array();
		$wc_taxonomy_conditions[] = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'option'  => 'dataType',
					'value'   => 'woocommerce',
					'compare' => '=',
				),
				array(
					'relation' => 'OR',
					'rules'    => self::create_taxonomy_depends_conditions(),
				),
			),
		);

		// Add support for all regular taxonomy (only relevant ones will be displayed).
		$wc_taxonomy_conditions[] = array(
			'option'  => 'dataType',
			'value'   => 'taxonomy',
			'compare' => '=',
		);
		$depends_conditions       = array(
			'relation' => 'OR',
			'rules'    => array(
				// Show the option when using WC shop integration.
				array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'relation' => 'OR',
							'rules'    => $wc_taxonomy_conditions,
						),
						array(
							'store'   => 'query',
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'woocommerce/shop',
						),
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'store'   => 'query',
									'option'  => 'archiveFilterTaxonomies',
									'compare' => '=',
									'value'   => 'all',
								),
								array(
									'store'   => 'query',
									'option'  => 'archiveFilterTaxonomies',
									'compare' => '=',
									'value'   => 'custom',
								),
							),
						),

					),
				),
				// Show the option when archive integration with product post type.
				array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'relation' => 'OR',
							'rules'    => $wc_taxonomy_conditions,
						),
						array(
							'store'   => 'query',
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'store'   => 'query',
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'post_type',
						),
						array(
							'store'   => 'query',
							'option'  => 'archivePostType',
							'compare' => '=',
							'value'   => 'product',
						),
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'store'   => 'query',
									'option'  => 'archiveFilterTaxonomies',
									'compare' => '=',
									'value'   => 'all',
								),
								array(
									'store'   => 'query',
									'option'  => 'archiveFilterTaxonomies',
									'compare' => '=',
									'value'   => 'custom',
								),
							),
						),
					),
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
		$data_options = array(
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

		// @phpstan-ignore function.notFound
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
			'isDataType'  => true, // Flag data types for the indexer to detect changes.
			'context'     => array( 'admin/field', 'block/field' ),
			'placeholder' => __( 'Choose WooCommerce source', 'search-filter' ),
			'options'     => $data_options,
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
				'previewAPI'       => true,
				'dependantOptions' => true,
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
	public static function shortcode_attributes( array $out, array $pairs, array $atts ) {
		if ( ! isset( $atts['search_filter_query_id'] ) ) {
			return $out;
		}
		if ( self::$active_query_id !== 0 ) {
			return $out;
		}
		// Remove products shortcode caching.
		$out['cache'] = false;
		self::set_active_query_id( absint( $atts['search_filter_query_id'] ) );
		add_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'add_query_to_args' ), 10, 1 );
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
	public static function add_query_to_args( array $query_args ) {
		// Remove products shortcode caching.
		if ( self::$active_query_id === 0 ) {
			return $query_args;
		}
		$query_args['search_filter_query_id'] = self::$active_query_id;

		// Remove products shortcode caching.
		remove_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'add_query_to_args' ), 10 );
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
	 * @param \stdClass $results The results data.
	 * @param object    $shortcode_class The shortcode class.
	 *
	 * @return \stdClass The results data.
	 */
	public static function track_wc_query_data( \stdClass $results, $shortcode_class ) {
		$query_args = $shortcode_class->get_query_args();
		if ( ! isset( $query_args['search_filter_query_id'] ) ) {
			return $results;
		}
		$query_id = $query_args['search_filter_query_id'];
		$query    = \Search_Filter\Queries\Query::get_instance( absint( $query_id ) );

		if ( is_wp_error( $query ) ) {
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
