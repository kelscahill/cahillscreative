<?php
/**
 * WooCommerce Search Indexer Integration
 *
 * Handles search content extraction for WooCommerce products and variations.
 * Uses a unified extraction method with config-driven inheritance behavior.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations\Woocommerce;

use Search_Filter\Integrations\Woocommerce as WooCommerce_Integration;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce search content indexing.
 *
 * Provides unified content extraction for all data types with config-driven
 * inheritance behavior for variations.
 *
 * @since 3.0.0
 */
class Search_Indexer {

	/**
	 * In-memory cache for extracted parent search content during batch indexing.
	 *
	 * For fields with 'inherit' behavior, all variations share the parent's
	 * extracted search content. Caching prevents repeated content extraction.
	 * Key format: "{parent_id}:{field_id}:{source_hash}"
	 *
	 * @since 3.2.3
	 * @var array<string, array>
	 */
	private static $parent_content_cache = array();

	/**
	 * Inheritance behavior configuration per data type.
	 *
	 * Defines how variations should handle each data type:
	 * - 'inherit'       - Use parent's value only (variation doesn't have its own)
	 * - 'combine'       - Merge parent + variation values (deduplicated)
	 * - 'inherit_or_own' - Use variation's value if set, else parent's
	 * - 'smart'         - Check WC attribute metadata (get_variation() flag)
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private static $inheritance_config = array(
		// Post attributes - inherit from parent (variations usually don't have their own).
		'post_attribute' => array(
			'post_title'   => 'inherit',
			'post_content' => 'inherit',
			'post_excerpt' => 'inherit',
			'post_type'    => 'inherit',
			'post_status'  => 'inherit',
			'post_author'  => 'inherit',
		),

		// WooCommerce data - mostly inherit, SKU combines.
		'woocommerce'    => array(
			'stock_status'     => 'inherit_or_own',
			'on_sale'          => 'inherit_or_own',
			'price'            => 'inherit_or_own',
			'sku'              => 'combine',
			'guid'             => 'combine',
			'product_cat'      => 'inherit',
			'product_brand'    => 'inherit',
			'product_tag'      => 'inherit',
			'custom_attribute' => 'smart',
			// Taxonomy attributes (pa_*) default to 'smart'.
		),

		// Custom fields - combine parent + variation values.
		'custom_field'   => 'combine',

		// Taxonomy - inherit from parent for variations.
		'taxonomy'       => 'inherit',
	);

	/**
	 * Initialize WooCommerce search indexer integration.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public static function init() {
		// Add unified search indexer hooks.
		add_filter( 'search-filter-pro/indexer/sync_field_search_index/override_values', array( __CLASS__, 'index_variation_search_content' ), 10, 4 );
		add_filter( 'search-filter-pro/indexer/sync_field_search_index/override_values', array( __CLASS__, 'index_simple_product_search_content' ), 10, 4 );
	}

	/**
	 * Index search content for product variations.
	 *
	 * Handles ALL data types for variations with parent inheritance/combining.
	 *
	 * @since 3.2.0
	 *
	 * @param string|null                 $content   Content string (null = not handled yet).
	 * @param array                       $source    Single data source configuration.
	 * @param \Search_Filter\Fields\Field $field     Field object.
	 * @param int                         $object_id Post ID.
	 * @return string|null Content string or null if not applicable.
	 */
	public static function index_variation_search_content( $content, $source, $field, $object_id ) {
		// Only handle product_variation post type.
		if ( get_post_type( $object_id ) !== 'product_variation' ) {
			return $content;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $content;
		}

		// Use cached product loading to prevent repeated wc_get_product() calls.
		$variation = Indexer::get_cached_product( $object_id );
		if ( ! $variation || $variation->get_type() !== 'variation' ) {
			return $content;
		}

		$parent_id = $variation->get_parent_id();
		if ( ! $parent_id ) {
			return $content;
		}

		$parent = Indexer::get_cached_product( $parent_id );
		if ( ! $parent ) {
			return $content;
		}

		// Extract content based on inheritance config.
		$values = self::extract_with_inheritance( $variation, $parent, $source, $field );

		if ( empty( $values ) ) {
			return null;
		}

		return implode( ' ', array_unique( array_filter( $values ) ) );
	}

	/**
	 * Index search content for simple products.
	 *
	 * Handles products WITHOUT children - extracts just the product's own data.
	 * Variable products (with children) are SKIPPED - only variations are indexed.
	 *
	 * @since 3.2.0
	 *
	 * @param string|null                 $content   Content string (null = not handled yet).
	 * @param array                       $source    Single data source configuration.
	 * @param \Search_Filter\Fields\Field $field     Field object.
	 * @param int                         $object_id Post ID.
	 * @return string|null Content string or null if not applicable.
	 */
	public static function index_simple_product_search_content( $content, $source, $field, $object_id ) {
		// Only handle product post type.
		if ( get_post_type( $object_id ) !== 'product' ) {
			return $content;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $content;
		}

		// Use cached product loading to prevent repeated wc_get_product() calls.
		$product = Indexer::get_cached_product( $object_id );
		if ( ! $product ) {
			return $content;
		}

		// Skip variable products (variations are indexed separately).
		if ( $product->is_type( 'variable' ) && ! empty( $product->get_children() ) ) {
			return $content;
		}

		// Extract content from product only.
		$values = self::get_search_content( $product, $source );

		if ( empty( $values ) ) {
			return null;
		}

		return implode( ' ', array_filter( $values ) );
	}

	/**
	 * Extract content with inheritance behavior for variations.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product                 $variation Variation product object.
	 * @param \WC_Product                 $parent_product Parent product object.
	 * @param array                       $source         Data source configuration.
	 * @param \Search_Filter\Fields\Field $field Field object (for cache key).
	 * @return array Array of searchable values.
	 */
	private static function extract_with_inheritance( $variation, $parent_product, $source, $field = null ) {
		$behavior = self::get_inheritance_behavior( $source );

		switch ( $behavior ) {
			case 'inherit':
				// Use parent only - cache parent content across variations.
				return self::get_cached_parent_content( $parent_product, $source, $field );

			case 'combine':
				// Merge variation + parent values.
				$variation_values = self::get_search_content( $variation, $source );
				$parent_values    = self::get_cached_parent_content( $parent_product, $source, $field );
				return array_merge( $variation_values, $parent_values );

			case 'inherit_or_own':
				// Use variation if not empty, else parent.
				$values = self::get_search_content( $variation, $source );
				if ( empty( $values ) ) {
					$values = self::get_cached_parent_content( $parent_product, $source, $field );
				}
				return $values;

			case 'smart':
				// Check WC attribute metadata.
				return self::extract_smart_attribute( $variation, $parent_product, $source );

			default:
				return self::get_search_content( $variation, $source );
		}
	}

	/**
	 * Get parent search content with caching.
	 *
	 * For inherited search fields, all variations of the same parent share identical
	 * content. Caching prevents repeated content extraction.
	 *
	 * @since 3.2.3
	 *
	 * @param \WC_Product                 $parent_product Parent product object.
	 * @param array                       $source         Data source config.
	 * @param \Search_Filter\Fields\Field $field          Field object (optional, for cache key).
	 * @return array Array of searchable values.
	 */
	private static function get_cached_parent_content( $parent_product, $source, $field = null ) {
		// Hash the source config so different data sources (e.g. post_title vs post_content)
		// get separate cache entries without hard-coding property names.
		$source_hash = md5( wp_json_encode( $source ) );
		$cache_key   = $parent_product->get_id();
		if ( $field ) {
			$cache_key .= ':' . $field->get_id();
		}
		$cache_key .= ':' . $source_hash;

		if ( ! isset( self::$parent_content_cache[ $cache_key ] ) ) {
			self::$parent_content_cache[ $cache_key ] = self::get_search_content( $parent_product, $source );
		}

		return self::$parent_content_cache[ $cache_key ];
	}

	/**
	 * Get inheritance behavior for a data source.
	 *
	 * @since 3.2.0
	 *
	 * @param array $source Data source configuration.
	 * @return string Inheritance behavior ('inherit', 'combine', 'inherit_or_own', 'smart').
	 */
	private static function get_inheritance_behavior( $source ) {
		$data_type = $source['dataType'] ?? '';

		if ( ! isset( self::$inheritance_config[ $data_type ] ) ) {
			return 'inherit_or_own'; // Default.
		}

		$config = self::$inheritance_config[ $data_type ];

		// If config is a string, use it directly.
		if ( is_string( $config ) ) {
			return $config;
		}

		// If config is an array, look up the specific sub-type.
		$sub_type = self::get_source_sub_type( $source );

		// For WooCommerce taxonomy attributes (pa_*), default to 'smart'.
		if ( $data_type === 'woocommerce' && ! isset( $config[ $sub_type ] ) ) {
			// Check if it's a taxonomy attribute.
			if ( WooCommerce_Integration::data_source_is_taxonomy_attribute( $sub_type ) ) {
				return 'smart';
			}
		}

		return $config[ $sub_type ] ?? 'inherit_or_own';
	}

	/**
	 * Get the sub-type identifier from a data source.
	 *
	 * @since 3.2.0
	 *
	 * @param array $source Data source configuration.
	 * @return string Sub-type identifier.
	 */
	private static function get_source_sub_type( $source ) {
		$data_type = $source['dataType'] ?? '';

		switch ( $data_type ) {
			case 'post_attribute':
				return $source['dataPostAttribute'] ?? '';

			case 'woocommerce':
				return $source['dataWoocommerce'] ?? '';

			case 'custom_field':
				return $source['dataCustomField'] ?? '';

			case 'taxonomy':
				return $source['dataTaxonomy'] ?? '';

			default:
				return '';
		}
	}

	/**
	 * Unified content extraction for search indexing.
	 *
	 * Routes to appropriate extraction method based on data type.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $source  Data source config (dataType, dataWoocommerce, etc.).
	 * @return array Array of searchable values/labels.
	 */
	private static function get_search_content( $product, $source ) {
		$data_type = $source['dataType'] ?? '';

		switch ( $data_type ) {
			case 'post_attribute':
				return self::extract_post_attribute_content( $product, $source );

			case 'woocommerce':
				return self::extract_woocommerce_content( $product, $source );

			case 'custom_field':
				return self::extract_custom_field_content( $product, $source );

			case 'taxonomy':
				return self::extract_taxonomy_content( $product, $source );

			default:
				return array();
		}
	}

	/**
	 * Extract post attribute content for search indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $source  Data source configuration.
	 * @return array Array of searchable values.
	 */
	private static function extract_post_attribute_content( $product, $source ) {
		$attribute = $source['dataPostAttribute'] ?? '';
		$post      = get_post( $product->get_id() );

		if ( ! $post ) {
			return array();
		}

		switch ( $attribute ) {
			case 'post_title':
				return ! empty( $post->post_title ) ? array( $post->post_title ) : array();

			case 'post_content':
				if ( empty( $post->post_content ) ) {
					return array();
				}
				return array( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );

			case 'post_excerpt':
				return ! empty( $post->post_excerpt ) ? array( wp_strip_all_tags( $post->post_excerpt ) ) : array();

			case 'post_type':
				$post_type = get_post_type( $product->get_id() );
				if ( ! $post_type ) {
					return array();
				}
				$post_type_object = get_post_type_object( $post_type );
				if ( ! $post_type_object ) {
					return array();
				}
				return array(
					$post_type_object->labels->name,
					$post_type_object->labels->singular_name,
				);

			case 'post_status':
				$post_status = get_post_status( $product->get_id() );
				if ( ! $post_status ) {
					return array();
				}
				$post_status_object = get_post_status_object( $post_status );
				if ( ! $post_status_object ) {
					return array();
				}
				return array( $post_status_object->label );

			case 'post_author':
				$post_author = get_post_field( 'post_author', $product->get_id() );
				if ( empty( $post_author ) ) {
					return array();
				}
				$author_display_name = get_the_author_meta( 'display_name', (int) $post_author );
				return $author_display_name !== false ? array( $author_display_name ) : array();

			default:
				return array();
		}
	}

	/**
	 * Extract WooCommerce-specific content for search indexing.
	 *
	 * @since 3.0.9
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $source  Data source configuration.
	 * @return array Array of searchable values/labels.
	 */
	private static function extract_woocommerce_content( $product, $source ) {
		$wc_data = $source['dataWoocommerce'] ?? '';
		$values  = array();

		if ( $wc_data === 'stock_status' ) {
			$stock_status_options = wc_get_product_stock_status_options();
			$stock_status         = $product->get_stock_status();
			if ( isset( $stock_status_options[ $stock_status ] ) ) {
				$values[] = $stock_status_options[ $stock_status ];
			}
			return $values;

		} elseif ( $wc_data === 'on_sale' ) {
			if ( $product->is_on_sale() ) {
				$values[] = __( 'On Sale', 'search-filter-pro' );
			}
			return $values;

		} elseif ( $wc_data === 'price' ) {
			$price = $product->get_price();
			return ! empty( $price ) ? array( $price ) : array();

		} elseif ( $wc_data === 'sku' ) {
			$sku = $product->get_sku();
			return ! empty( $sku ) ? array( $sku ) : array();

		} elseif ( $wc_data === 'guid' ) {
			$guid = $product->get_global_unique_id();
			return ! empty( $guid ) ? array( $guid ) : array();

		} elseif ( $wc_data === 'product_cat' ) {
			$product_category_ids = $product->get_category_ids();
			foreach ( $product_category_ids as $product_category_id ) {
				$product_category = get_term( $product_category_id, 'product_cat' );
				if ( ! $product_category || is_wp_error( $product_category ) ) {
					continue;
				}
				$values[] = $product_category->name;

				// Include parent hierarchy.
				$parent_id = $product_category->parent;
				while ( $parent_id !== 0 ) {
					$parent_term = get_term( $parent_id, 'product_cat' );
					if ( ! $parent_term || is_wp_error( $parent_term ) ) {
						break;
					}
					$parent_id = $parent_term->parent;
					if ( ! in_array( $parent_term->name, $values, true ) ) {
						$values[] = $parent_term->name;
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
				$values[] = $product_brand->name;

				// Include parent hierarchy.
				$parent_id = $product_brand->parent;
				while ( $parent_id !== 0 ) {
					$parent_term = get_term( $parent_id, 'product_brand' );
					if ( ! $parent_term || is_wp_error( $parent_term ) ) {
						break;
					}
					$parent_id = $parent_term->parent;
					if ( ! in_array( $parent_term->name, $values, true ) ) {
						$values[] = $parent_term->name;
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
				$values[] = $product_tag->name;
			}
			return $values;

		} elseif ( $wc_data === 'custom_attribute' ) {
			$custom_attribute = $source['dataWoocommerceCustomAttribute'] ?? '';
			if ( empty( $custom_attribute ) ) {
				return array();
			}
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
					$terms = wp_get_post_terms( $product->get_id(), $taxonomy_name );
					foreach ( $terms as $term ) {
						$values[] = $term->name;
					}
					return $values;
				}
			}
		}

		return $values;
	}

	/**
	 * Extract custom field content for search indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $source  Data source configuration.
	 * @return array Array of searchable values.
	 */
	private static function extract_custom_field_content( $product, $source ) {
		$custom_field_key = $source['dataCustomField'] ?? '';

		if ( empty( $custom_field_key ) ) {
			return array();
		}

		$field_values = get_post_meta( $product->get_id(), $custom_field_key, false );

		if ( empty( $field_values ) ) {
			return array();
		}

		// Flatten nested arrays.
		$values = array();
		return Indexer::flatten_custom_field_values( $field_values, $values );
	}

	/**
	 * Extract taxonomy term content for search indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $source  Data source configuration.
	 * @return array Array of searchable values.
	 */
	private static function extract_taxonomy_content( $product, $source ) {
		$taxonomy = $source['dataTaxonomy'] ?? '';

		if ( empty( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $product->get_id(), $taxonomy );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}

		$values = array();
		foreach ( $terms as $term ) {
			$values[] = $term->name;

			// Include parent term names for hierarchical taxonomies.
			$parent_id = $term->parent;
			while ( $parent_id !== 0 ) {
				$parent_term = get_term( $parent_id, $taxonomy );
				if ( is_wp_error( $parent_term ) || ! $parent_term ) {
					break;
				}
				if ( ! in_array( $parent_term->name, $values, true ) ) {
					$values[] = $parent_term->name;
				}
				$parent_id = $parent_term->parent;
			}
		}

		return $values;
	}

	/**
	 * Extract attribute with smart inheritance based on WC metadata.
	 *
	 * For variation attributes, uses the variation's own value.
	 * For non-variation attributes, inherits from parent.
	 *
	 * @since 3.2.0
	 *
	 * @param \WC_Product $variation Variation product object.
	 * @param \WC_Product $parent_product Parent product object.
	 * @param array       $source         Data source configuration.
	 * @return array Array of searchable values.
	 */
	private static function extract_smart_attribute( $variation, $parent_product, $source ) {
		$wc_data = $source['dataWoocommerce'] ?? '';

		if ( $wc_data === 'custom_attribute' ) {
			// Custom attributes - check if it's a variation attribute.
			$custom_attribute_key = $source['dataWoocommerceCustomAttribute'] ?? '';
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
				$options = $product_attribute->get_options();
				return is_array( $options ) ? $options : array();
			}
		}

		// Taxonomy attribute (pa_*).
		$taxonomy_name = WooCommerce_Integration::get_taxonomy_name_from_data_source( $wc_data );
		if ( empty( $taxonomy_name ) ) {
			return array();
		}

		// Get attribute slug from taxonomy name (e.g., 'pa_size' -> 'size').
		$attribute_name = str_replace( 'pa_', '', $taxonomy_name );

		$parent_attributes = $parent_product->get_attributes();
		if ( ! isset( $parent_attributes[ $taxonomy_name ] ) ) {
			return array();
		}

		$product_attribute = $parent_attributes[ $taxonomy_name ];

		if ( $product_attribute->get_variation() ) {
			// Variation attribute - use variation's value.
			$variation_value = $variation->get_attribute( $taxonomy_name );
			if ( ! empty( $variation_value ) ) {
				// Get term name from slug.
				$term = get_term_by( 'slug', $variation_value, $taxonomy_name );
				if ( $term && ! is_wp_error( $term ) ) {
					return array( $term->name );
				}
				// Fallback to raw value if term not found.
				return array( $variation_value );
			}
			return array();
		} else {
			// Non-variation attribute - inherit parent's term names.
			$values = array();
			$terms  = wp_get_post_terms( $parent_product->get_id(), $taxonomy_name );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$values[] = $term->name;
				}
			}
			return $values;
		}
	}

	/**
	 * Flush the in-memory parent content cache.
	 *
	 * Called during full cache clears (every N posts) to prevent memory buildup.
	 * Persists across variations of the same parent for efficiency.
	 *
	 * Required for test runner.
	 *
	 * @since 3.2.3
	 */
	public static function flush_parent_content_cache() {
		self::$parent_content_cache = array();
	}
}
