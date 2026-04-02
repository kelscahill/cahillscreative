<?php
/**
 * WooCommerce Indexer Integration
 *
 * Handles index value extraction for WooCommerce products and variations.
 * Uses a unified extraction method with config-driven inheritance behavior.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations\Woocommerce;

use Search_Filter\Integrations;
use Search_Filter\Integrations\Woocommerce as WooCommerce_Integration;
use Search_Filter_Pro\Indexer\Parent_Map\Manager as Parent_Map_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce product and variation indexing.
 *
 * Provides unified value extraction for all data types with config-driven
 * inheritance behavior for variations.
 *
 * @since 3.0.0
 */
class Indexer {

	/**
	 * In-memory cache for WC_Product objects during batch indexing.
	 *
	 * Prevents repeated wc_get_product() calls for the same product
	 * when processing multiple fields. Each product is loaded once
	 * and reused for all field extractions.
	 *
	 * @since 3.2.3
	 * @var array<int, \WC_Product|false>
	 */
	private static $product_cache = array();

	/**
	 * In-memory cache for extracted parent values during batch indexing.
	 *
	 * For fields with 'inherit' behavior, all variations share the parent's
	 * extracted values. Caching these prevents repeated term lookups.
	 * Key format: "{parent_id}:{field_id}"
	 *
	 * @since 3.2.3
	 * @var array<string, array>
	 */
	private static $parent_values_cache = array();

	/**
	 * Inheritance behavior configuration per data type.
	 *
	 * Defines how variations should handle each data type:
	 * - 'inherit'        - Use parent's value only (variation doesn't have its own)
	 * - 'combine'        - Merge parent + variation values (deduplicated)
	 * - 'inherit_or_own' - Use variation's value if set, else parent's
	 * - 'smart'          - Check WC attribute metadata (get_variation() flag)
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private static $inheritance_config = array(
		// Post attributes - inherit from parent (variations should use parent's values).
		'post_attribute' => array(
			'post_type'   => 'inherit',
			'post_status' => 'inherit',
			'post_author' => 'inherit',
		),

		// WooCommerce data - mixed inheritance behavior.
		'woocommerce'    => array(
			'stock_status'     => 'inherit_or_own',
			'on_sale'          => 'inherit_or_own',
			'price'            => 'inherit_or_own',
			'sku'              => 'combine',
			'guid'             => 'combine',
			'weight'           => 'inherit_or_own',
			'length'           => 'inherit_or_own',
			'width'            => 'inherit_or_own',
			'height'           => 'inherit_or_own',
			'dimensions'       => 'inherit_or_own',
			'product_cat'      => 'inherit',
			'product_brand'    => 'inherit',
			'product_tag'      => 'inherit',
			'custom_attribute' => 'smart',
			// Taxonomy attributes (pa_*) default to 'smart'.
		),

		// Custom fields - fallback to parent if variation empty.
		'custom_field'   => 'inherit_or_own',

		// Taxonomy - inherit from parent for variations.
		'taxonomy'       => 'inherit',
	);

	/**
	 * Initialize WooCommerce indexer integration.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public static function init() {
		// Enable parent map table for WooCommerce variation→parent conversion.
		add_filter( 'search-filter-pro/indexer/parent_map/should_use', array( __CLASS__, 'should_use_parent_map_table' ), 10, 1 );
		add_filter( 'search-filter-pro/indexer/parent_map/post_types', array( __CLASS__, 'add_parent_map_post_types' ), 10, 1 );
		add_filter( 'search-filter-pro/indexer/query/parent_map_sources', array( __CLASS__, 'filter_query_parent_map_sources' ), 10, 2 );

		// Unified indexer hooks (2 hooks instead of 6).
		add_filter( 'search-filter-pro/indexer/sync_field_index/override_values', array( __CLASS__, 'index_variation_values' ), 10, 3 );
		add_filter( 'search-filter-pro/indexer/sync_field_index/override_values', array( __CLASS__, 'index_product_values' ), 10, 3 );

		add_filter( 'search-filter-pro/indexer/resync_queue/items', array( __CLASS__, 'add_resync_queue_items' ), 10, 1 );

		// Make sure we update the post types to include variations whever we run rebuild tasks.
		add_action( 'search-filter-pro/indexer/run_task/start', array( __CLASS__, 'init_sync_data_start' ), 10, 1 );
		add_action( 'search-filter-pro/indexer/run_task/finish', array( __CLASS__, 'init_sync_data_finish' ), 10, 1 );
		// Also update the post type whenver the sync data is init, cover cases like a post being updated and being synced immediately.
		add_action( 'search-filter-pro/indexer/init_sync_data/start', array( __CLASS__, 'init_sync_data_start' ), 10, 1 );
		add_action( 'search-filter-pro/indexer/init_sync_data/finish', array( __CLASS__, 'init_sync_data_finish' ), 10, 1 );

		add_filter( 'search-filter-pro/indexer/query/result_lookup/query_args', array( __CLASS__, 'result_lookup_query_args' ), 10, 2 );
		add_filter( 'search-filter-pro/indexer/query/collapse_children', array( __CLASS__, 'collapse_children' ), 10, 2 );
		add_filter( 'search-filter/fields/range/auto_detect_custom_field', array( __CLASS__, 'auto_detect_custom_field' ), 10, 2 );

		// Clear per-post product cache after each post is processed.
		// This allows caching across fields within a single post.
		add_action( 'search-filter-pro/indexer/task_batch_post_sync/process_post', array( __CLASS__, 'flush_product_cache' ) );
	}

	/**
	 * Index values for product variations.
	 *
	 * Handles ALL data types for variations with parent inheritance/combining.
	 *
	 * @since 3.2.0
	 *
	 * @param array|null                  $values    Values array (null = not handled yet).
	 * @param \Search_Filter\Fields\Field $field     Field object.
	 * @param int                         $object_id Post ID.
	 * @return array|null Values array or null if not applicable.
	 */
	public static function index_variation_values( $values, $field, $object_id ) {
		// Only handle product_variation post type.
		if ( get_post_type( $object_id ) !== 'product_variation' ) {
			return $values;
		}

		// Check if this is a data type we handle.
		$data_type = $field->get_attribute( 'dataType' );
		if ( ! self::is_supported_data_type( $data_type, $field ) ) {
			return $values;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $values;
		}

		// Use cached product loading to prevent repeated wc_get_product() calls
		// across multiple fields for the same product.
		$variation = self::get_cached_product( $object_id );
		if ( ! $variation || $variation->get_type() !== 'variation' ) {
			return $values;
		}

		$parent_id = $variation->get_parent_id();
		if ( ! $parent_id ) {
			return $values;
		}

		// Use cached product loading for parent.
		$parent = self::get_cached_product( $parent_id );
		if ( ! $parent ) {
			return $values;
		}

		// Extract values based on inheritance config.
		return self::extract_with_inheritance( $variation, $parent, $field );
	}

	/**
	 * Index values for simple products.
	 *
	 * Handles products WITHOUT children - extracts just the product's own data.
	 * Variable products (with children) are SKIPPED - only variations are indexed.
	 *
	 * @since 3.2.0
	 *
	 * @param array|null                  $values    Values array (null = not handled yet).
	 * @param \Search_Filter\Fields\Field $field     Field object.
	 * @param int                         $object_id Post ID.
	 * @return array|null Values array or null if not applicable.
	 */
	public static function index_product_values( $values, $field, $object_id ) {
		// Only handle product post type.
		if ( get_post_type( $object_id ) !== 'product' ) {
			return $values;
		}

		// Check if this is a data type we handle.
		$data_type = $field->get_attribute( 'dataType' );
		if ( ! self::is_supported_data_type( $data_type, $field ) ) {
			return $values;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $values;
		}

		// Use cached product loading to prevent repeated wc_get_product() calls
		// across multiple fields for the same product.
		$product = self::get_cached_product( $object_id );
		if ( ! $product ) {
			return $values;
		}

		// Skip variable products that have variations (variations are indexed separately).
		// For variable products that don't have variations yet, we need to store on the parent.
		if ( $product->is_type( 'variable' ) && ! empty( $product->get_children() ) ) {
			return array();
		}

		// Extract values from product only.
		return self::get_index_values( $product, $field );
	}

	/**
	 * Check if this is a data type we handle.
	 *
	 * @since 3.2.0
	 *
	 * @param string                      $data_type The data type.
	 * @param \Search_Filter\Fields\Field $field     The field.
	 * @return bool True if we handle this data type.
	 */
	private static function is_supported_data_type( $data_type, $field ) {
		// We handle woocommerce, post_attribute, custom_field, and taxonomy for WC queries.
		if ( $data_type === 'woocommerce' ) {
			return true;
		}

		if ( $data_type === 'post_attribute' ) {
			return true;
		}

		// For taxonomy and custom_field, only handle if it's a WooCommerce query.
		if ( in_array( $data_type, array( 'taxonomy', 'custom_field' ), true ) ) {
			$field_query = $field->get_query();
			if ( ! $field_query ) {
				return false;
			}
			return WooCommerce_Integration::is_woocommerce_query( $field_query );
		}

		return false;
	}

	/**
	 * Extract values with inheritance behavior for variations.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $variation Variation product object.
	 * @param \WC_Product                 $parent_product Parent product object.
	 * @param \Search_Filter\Fields\Field $field          Field object.
	 * @return array Array of index values.
	 */
	private static function extract_with_inheritance( $variation, $parent_product, $field ) {
		$behavior = self::get_inheritance_behavior( $field );

		switch ( $behavior ) {
			case 'inherit':
				// Use parent only - cache parent values across variations.
				return self::get_cached_parent_values( $parent_product, $field );

			case 'combine':
				// Merge variation + parent values.
				$variation_values = self::get_index_values( $variation, $field );
				$parent_values    = self::get_cached_parent_values( $parent_product, $field );
				return array_unique( array_merge( $variation_values, $parent_values ) );

			case 'inherit_or_own':
				// Use variation if not empty, else parent.
				$result = self::get_index_values( $variation, $field );
				if ( empty( $result ) ) {
					$result = self::get_cached_parent_values( $parent_product, $field );
				}
				return $result;

			case 'smart':
				// Check WC attribute metadata.
				return self::extract_smart_attribute( $variation, $parent_product, $field );

			default:
				return self::get_index_values( $variation, $field );
		}
	}

	/**
	 * Get parent values with caching.
	 *
	 * For inherited fields, all variations of the same parent share identical
	 * values. Caching prevents repeated term lookups (e.g., wc_get_product_terms).
	 *
	 * @since 3.2.3
	 *
	 * @param \WC_Product                 $parent_product Parent product object.
	 * @param \Search_Filter\Fields\Field $field          Field object.
	 * @return array Array of index values.
	 */
	private static function get_cached_parent_values( $parent_product, $field ) {
		$cache_key = $parent_product->get_id() . ':' . $field->get_id();

		if ( ! isset( self::$parent_values_cache[ $cache_key ] ) ) {
			self::$parent_values_cache[ $cache_key ] = self::get_index_values( $parent_product, $field );
		}

		return self::$parent_values_cache[ $cache_key ];
	}

	/**
	 * Get inheritance behavior for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param \Search_Filter\Fields\Field $field Field object.
	 * @return string Inheritance behavior ('inherit', 'combine', 'inherit_or_own', 'smart').
	 */
	private static function get_inheritance_behavior( $field ) {
		$data_type = $field->get_attribute( 'dataType' );

		if ( ! isset( self::$inheritance_config[ $data_type ] ) ) {
			return 'inherit_or_own'; // Default.
		}

		$config = self::$inheritance_config[ $data_type ];

		// If config is a string, use it directly.
		if ( is_string( $config ) ) {
			return $config;
		}

		// If config is an array, look up the specific sub-type.
		$sub_type = self::get_field_sub_type( $field );

		// For WooCommerce taxonomy attributes (pa_*), default to 'smart'.
		if ( $data_type === 'woocommerce' && ! isset( $config[ $sub_type ] ) ) {
			if ( WooCommerce_Integration::data_source_is_taxonomy_attribute( $sub_type ) ) {
				return 'smart';
			}
		}

		return $config[ $sub_type ] ?? 'inherit_or_own';
	}

	/**
	 * Get the sub-type identifier from a field.
	 *
	 * @since 3.2.0
	 *
	 * @param \Search_Filter\Fields\Field $field Field object.
	 * @return string Sub-type identifier.
	 */
	private static function get_field_sub_type( $field ) {
		$data_type = $field->get_attribute( 'dataType' );

		switch ( $data_type ) {
			case 'post_attribute':
				return $field->get_attribute( 'dataPostAttribute' ) ?? '';

			case 'woocommerce':
				return $field->get_attribute( 'dataWoocommerce' ) ?? '';

			case 'custom_field':
				return $field->get_attribute( 'dataCustomField' ) ?? '';

			case 'taxonomy':
				return $field->get_attribute( 'dataTaxonomy' ) ?? '';

			default:
				return '';
		}
	}

	/**
	 * Unified value extraction for indexing.
	 *
	 * Routes to appropriate extraction method based on data type.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $product Product object.
	 * @param \Search_Filter\Fields\Field $field   Field object.
	 * @return array Array of index values.
	 */
	private static function get_index_values( $product, $field ) {
		$data_type = $field->get_attribute( 'dataType' );

		switch ( $data_type ) {
			case 'post_attribute':
				return self::extract_post_attribute_values( $product, $field );

			case 'woocommerce':
				return self::extract_woocommerce_values( $product, $field );

			case 'custom_field':
				return self::extract_custom_field_values( $product, $field );

			case 'taxonomy':
				return self::extract_taxonomy_values( $product, $field );

			default:
				return array();
		}
	}

	/**
	 * Extract post attribute values for indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $product Product object.
	 * @param \Search_Filter\Fields\Field $field   Field object.
	 * @return array Array of index values.
	 */
	private static function extract_post_attribute_values( $product, $field ) {
		$attribute = $field->get_attribute( 'dataPostAttribute' );
		$post_id   = $product->get_id();

		switch ( $attribute ) {
			case 'post_type':
				$post_type = get_post_type( $post_id );
				return $post_type !== false ? array( $post_type ) : array();

			case 'post_status':
				$post_status = get_post_status( $post_id );
				return $post_status !== false ? array( $post_status ) : array();

			case 'post_author':
				$post_author = get_post_field( 'post_author', $post_id );
				// Note: $post_author could be '0' which is considered empty in PHP.
				// We return it as long as it's not an empty string.
				return $post_author !== '' ? array( $post_author ) : array();

			default:
				return array();
		}
	}

	/**
	 * Extract WooCommerce-specific values for indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $product Product object.
	 * @param \Search_Filter\Fields\Field $field   Field object.
	 * @return array Array of index values.
	 */
	private static function extract_woocommerce_values( $product, $field ) {
		$wc_data = $field->get_attribute( 'dataWoocommerce' );
		$values  = array();

		if ( $wc_data === 'stock_status' ) {
			$values[] = $product->get_stock_status();
			return $values;

		} elseif ( $wc_data === 'on_sale' ) {
			if ( $product->is_on_sale() ) {
				$values[] = 'on-sale';
			}
			return $values;

		} elseif ( $wc_data === 'price' ) {
			$price = $product->get_price();
			return $price !== '' ? array( $price ) : array();

		} elseif ( $wc_data === 'sku' ) {
			$sku = $product->get_sku();
			return ! empty( $sku ) ? array( $sku ) : array();

		} elseif ( $wc_data === 'guid' ) {
			$guid = $product->get_global_unique_id();
			return ! empty( $guid ) ? array( $guid ) : array();

		} elseif ( in_array( $wc_data, array( 'weight', 'length', 'width', 'height' ), true ) ) {
			$getter = 'get_' . $wc_data;
			$value  = $product->$getter();
			return $value !== '' ? array( $value ) : array();

		} elseif ( $wc_data === 'dimensions' ) {
			$length = $product->get_length();
			$width  = $product->get_width();
			$height = $product->get_height();
			if ( $length !== '' && $width !== '' && $height !== '' ) {
				return array( $length . 'x' . $width . 'x' . $height );
			}
			return array();

		} elseif ( $wc_data === 'product_cat' ) {
			$product_category_ids = $product->get_category_ids();

			foreach ( $product_category_ids as $product_category_id ) {
				$product_category = get_term( $product_category_id, 'product_cat' );
				if ( ! $product_category || is_wp_error( $product_category ) ) {
					continue;
				}
				$values[] = $product_category->slug;

				// Include parent hierarchy.
				$parent_id = $product_category->parent;
				while ( $parent_id !== 0 ) {
					$parent_term = get_term( $parent_id, 'product_cat' );
					if ( ! $parent_term || is_wp_error( $parent_term ) ) {
						break;
					}
					$parent_id = $parent_term->parent;
					if ( ! in_array( $parent_term->slug, $values, true ) ) {
						$values[] = $parent_term->slug;
					}
				}
			}
			return $values;

		} elseif ( $wc_data === 'product_brand' ) {
			$product_brand_terms = wc_get_product_terms( $product->get_id(), 'product_brand' );
			if ( ! $product_brand_terms ) {
				return $values;
			}

			foreach ( $product_brand_terms as $product_brand ) {
				if ( ! $product_brand || is_wp_error( $product_brand ) ) {
					continue;
				}
				$values[] = $product_brand->slug;

				// Include parent hierarchy.
				$parent_id = $product_brand->parent;
				while ( $parent_id !== 0 ) {
					$parent_term = get_term( $parent_id, 'product_brand' );
					if ( ! $parent_term || is_wp_error( $parent_term ) ) {
						break;
					}
					$parent_id = $parent_term->parent;
					if ( ! in_array( $parent_term->slug, $values, true ) ) {
						$values[] = $parent_term->slug;
					}
				}
			}
			return $values;

		} elseif ( $wc_data === 'product_tag' ) {
			$product_tag_ids = $product->get_tag_ids();
			foreach ( $product_tag_ids as $product_tag_id ) {
				$product_tag = get_term( $product_tag_id, 'product_tag' );
				if ( ! $product_tag || is_wp_error( $product_tag ) ) {
					continue;
				}
				$values[] = $product_tag->slug;
			}
			return $values;

		} elseif ( $wc_data === 'custom_attribute' ) {
			$custom_attribute   = $field->get_attribute( 'dataWoocommerceCustomAttribute' );
			$product_attributes = $product->get_attributes();
			if ( ! isset( $product_attributes[ $custom_attribute ] ) ) {
				return array();
			}
			return $product_attributes[ $custom_attribute ]->get_options();

		} else {
			// Taxonomy product attribute (pa_*).
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
					return $attribute->get_slugs();
				}
			}
		}

		return $values;
	}

	/**
	 * Extract taxonomy term values for indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $product Product object.
	 * @param \Search_Filter\Fields\Field $field   Field object.
	 * @return array Array of index values (term slugs).
	 */
	private static function extract_taxonomy_values( $product, $field ) {
		$taxonomy = $field->get_attribute( 'dataTaxonomy' );

		if ( empty( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $product->get_id(), $taxonomy );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}

		$values = array();
		foreach ( $terms as $term ) {
			$values[] = $term->slug;

			// Include parent term slugs for hierarchical taxonomies.
			$parent_id = $term->parent;
			while ( $parent_id !== 0 ) {
				$parent_term = get_term( $parent_id, $taxonomy );
				if ( is_wp_error( $parent_term ) || ! $parent_term ) {
					break;
				}
				if ( ! in_array( $parent_term->slug, $values, true ) ) {
					$values[] = $parent_term->slug;
				}
				$parent_id = $parent_term->parent;
			}
		}

		return $values;
	}

	/**
	 * Extract custom field values for indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $product Product object.
	 * @param \Search_Filter\Fields\Field $field   Field object.
	 * @return array Array of index values.
	 */
	private static function extract_custom_field_values( $product, $field ) {
		$custom_field_key = $field->get_attribute( 'dataCustomField' );

		if ( empty( $custom_field_key ) ) {
			return array();
		}

		$field_values = get_post_meta( $product->get_id(), $custom_field_key, false );

		if ( empty( $field_values ) ) {
			return array();
		}

		// Flatten nested arrays.
		return self::flatten_custom_field_values( $field_values );
	}

	/**
	 * Extract attribute with smart inheritance based on WC metadata.
	 *
	 * For variation attributes, uses the variation's own value.
	 * For non-variation attributes, inherits from parent.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $variation Variation product object.
	 * @param \WC_Product                 $parent_product Parent product object.
	 * @param \Search_Filter\Fields\Field $field          Field object.
	 * @return array Array of index values.
	 */
	private static function extract_smart_attribute( $variation, $parent_product, $field ) {
		$wc_data = $field->get_attribute( 'dataWoocommerce' );

		if ( $wc_data === 'custom_attribute' ) {
			// Custom attributes - check if it's a variation attribute.
			$custom_attribute_key = $field->get_attribute( 'dataWoocommerceCustomAttribute' );
			if ( empty( $custom_attribute_key ) ) {
				return array();
			}

			$parent_attributes = $parent_product->get_attributes();
			if ( ! isset( $parent_attributes[ $custom_attribute_key ] ) ) {
				return array();
			}

			$product_attribute = $parent_attributes[ $custom_attribute_key ];

			if ( $product_attribute->get_variation() ) {
				// Variation attribute - use variation's value.
				$variation_value = $variation->get_attribute( $custom_attribute_key );
				return ! empty( $variation_value ) ? array( $variation_value ) : array();
			} else {
				// Non-variation attribute - inherit parent's values.
				return $product_attribute->get_options();
			}
		}

		// Taxonomy attribute (pa_*).
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $wc_data );
		if ( empty( $taxonomy_name ) ) {
			return array();
		}

		$parent_attributes = $parent_product->get_attributes();
		if ( ! isset( $parent_attributes[ $taxonomy_name ] ) ) {
			return array();
		}

		$product_attribute = $parent_attributes[ $taxonomy_name ];

		if ( $product_attribute->get_variation() ) {
			// Variation attribute - use variation's value.
			$attributes = $variation->get_attributes();
			if ( isset( $attributes[ $taxonomy_name ] ) && ! empty( $attributes[ $taxonomy_name ] ) ) {
				return array( $attributes[ $taxonomy_name ] );
			}
			return array();
		} else {
			// Non-variation attribute - inherit parent's slugs.
			return $product_attribute->get_slugs();
		}
	}

	/**
	 * Determine if we should use the parent map table.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $use_parent_map Existing value.
	 * @return bool True if we should use the parent map table.
	 */
	public static function should_use_parent_map_table( $use_parent_map ) {
		/*
		 * We need a safety net check as there is a case where use_parent_map is
		 * still fired after the integration has been disabled, preventing the
		 * table from being dropped immediately.
		 *
		 * It would be removed on the next request automatically anyway but better
		 * to cleanup immediately (for testing too).
		 */
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			// Return the existing value.
			return $use_parent_map;
		}

		// Now check to see if the basic requirements are met.
		return Parent_Map_Manager::can_use();
	}

	/**
	 * Add WooCommerce post types to parent mapping.
	 *
	 * Registers product and product_variation post types so their parent
	 * relationships are tracked in the parent_map table.
	 *
	 * @since 3.2.0
	 *
	 * @param array $post_types Existing post types.
	 * @return array Updated post types including WC types.
	 */
	public static function add_parent_map_post_types( $post_types ) {
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return $post_types;
		}

		$post_types[] = 'product';
		$post_types[] = 'product_variation';
		return $post_types;
	}

	/**
	 * Filter the parent map sources for WooCommerce queries.
	 *
	 * @since 3.2.0
	 *
	 * @param array                        $sources   The sources.
	 * @param \Search_Filter\Queries\Query $query     The query.
	 * @return array   The sources.
	 */
	public static function filter_query_parent_map_sources( $sources, $query ) {

		if ( ! WooCommerce_Integration::is_woocommerce_query( $query ) ) {
			return $sources;
		}

		// The parent Map table stores the parent/child relationship using the child post
		// type whereas our various queries use the post type defined in the query (which
		// would be 'post-product' for WooCommerce products).
		return array(
			'post-product_variation',
		);
	}

	/**
	 * If there are any parent products, make sure we add the variation IDs too.
	 *
	 * This is necessary because when we update products attributes, if they are
	 * not used on variations, then the variations will never get them.
	 *
	 * @param array $items The items to add.
	 * @return array The items to add.
	 */
	public static function add_resync_queue_items( $items ) {

		$items_to_add = array();
		foreach ( $items as $item ) {
			$post_id = $item;
			if ( get_post_type( $post_id ) !== 'product' ) {
				continue;
			}

			$product = \wc_get_product( $post_id );
			if ( ! $product ) {
				continue;
			}

			if ( ! $product->is_type( 'variable' ) ) {
				continue;
			}

			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$items_to_add[] = $variation_id;
			}
		}

		return array_merge( $items, $items_to_add );
	}

	/**
	 * Add the variations post type to the query attributes when the product post type is used.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes    The attributes.
	 * @return array    The attributes.
	 */
	public static function add_variations_to_post_types( $attributes ) {
		if ( ! isset( $attributes['postTypes'] ) ) {
			return $attributes;
		}

		if ( ! in_array( 'product', $attributes['postTypes'], true ) ) {
			return $attributes;
		}

		if ( ! in_array( 'product_variation', $attributes['postTypes'], true ) ) {
			$attributes['postTypes'][] = 'product_variation';
		}

		return $attributes;
	}
	/**
	 * Add the variations post type when using posts.
	 *
	 * Ensures post variations are included in when syncing.
	 *
	 * @since 3.0.0
	 */
	public static function init_sync_data_start() {
		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'add_variations_to_post_types' ), 10 );
	}

	/**
	 * Remove the filter when finished.
	 *
	 * @since 3.0.0
	 */
	public static function init_sync_data_finish() {
		remove_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'add_variations_to_post_types' ), 10 );
	}

	/**
	 * Insert the product_variation post type into the indexer query args
	 * when setting up the queries to obtain results IDs (not the actual
	 * final WP_Query for listing the posts).
	 *
	 * @since 3.0.0
	 *
	 * @param array  $query_args    The query args.
	 * @param object $query         The query object.
	 * @return array    The query args.
	 */
	public static function result_lookup_query_args( $query_args, $query ) {

		if ( ! WooCommerce_Integration::is_woocommerce_query( $query ) ) {
			return $query_args;
		}

		if ( ! is_array( $query_args['post_type'] ) ) {
			$query_args['post_type'] = array( $query_args['post_type'] );
		}
		$query_args['post_type'][] = 'product_variation';

		return $query_args;
	}

	/**
	 * Collapse children into parents when counting.
	 *
	 * Ensures that product variation hits are counted against the parent product.
	 *
	 * @since 3.0.0
	 *
	 * @param bool   $should_collapse    Whether to collapse children.
	 * @param object $query              The query object.
	 * @return bool    Whether to collapse children.
	 */
	public static function collapse_children( $should_collapse, $query ) {

		if ( ! WooCommerce_Integration::is_woocommerce_query( $query ) ) {
			return $should_collapse;
		}

		return true;
	}

	/**
	 * Get the custom field key for the range field when using price, weight or dimensions.
	 *
	 * @since 3.0.0
	 *
	 * @param string $custom_field_key    The custom field key.
	 * @param array  $attributes          The attributes.
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

		$wc_data = $attributes['dataWoocommerce'];

		if ( $wc_data === 'price' ) {
			return '_price';
		}

		if ( in_array( $wc_data, array( 'weight', 'length', 'width', 'height' ), true ) ) {
			return '_' . $wc_data;
		}

		return $custom_field_key;
	}

	/**
	 * Flatten custom field values from post meta.
	 *
	 * Handles both scalar values and arrays of values.
	 *
	 * @since 3.2.0
	 *
	 * @param array $meta_values The meta values from get_post_meta().
	 * @param array $result      The existing result array to append to.
	 * @return array The flattened values.
	 */
	public static function flatten_custom_field_values( $meta_values, $result = array() ) {
		if ( empty( $meta_values ) ) {
			return $result;
		}

		foreach ( $meta_values as $value ) {
			if ( is_scalar( $value ) && $value !== '' ) {
				$result[] = $value;
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $array_value ) {
					if ( is_scalar( $array_value ) && $array_value !== '' ) {
						$result[] = $array_value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get a WC_Product from cache or load it.
	 *
	 * Caches products in memory to prevent repeated wc_get_product() calls
	 * when processing multiple fields for the same product. Public so other
	 * integrations (ACF, etc.) can reuse the cache.
	 *
	 * @since 3.2.3
	 *
	 * @param int $product_id The product ID.
	 * @return \WC_Product|false The product object or false if not found.
	 */
	public static function get_cached_product( $product_id ) {
		if ( ! isset( self::$product_cache[ $product_id ] ) ) {
			self::$product_cache[ $product_id ] = wc_get_product( $product_id );
		}
		return self::$product_cache[ $product_id ];
	}

	/**
	 * Clear the in-memory product cache.
	 *
	 * Should be called periodically during batch processing to prevent
	 * memory buildup from cached product objects.
	 *
	 * @since 3.2.3
	 *
	 * @param int $product_id The product ID to remove from cache.
	 */
	public static function flush_product_cache( $product_id ) {
		if ( isset( self::$product_cache[ $product_id ] ) ) {
			unset( self::$product_cache[ $product_id ] );
		}
	}

	/**
	 * Flush the entire product cache.
	 *
	 * Required for test runner to ensure clean state between tests.
	 *
	 * @since 3.2.3
	 */
	public static function flush_product_caches() {
		self::$product_cache = array();
	}

	/**
	 * Flush the in-memory parent values cache.
	 *
	 * Called during full cache clears (every N posts) to prevent memory buildup.
	 * Unlike product cache, this persists across variations of the same parent.
	 *
	 * Required for test runner.
	 *
	 * @since 3.2.3
	 */
	public static function flush_parent_values_cache() {
		self::$parent_values_cache = array();
	}
}
