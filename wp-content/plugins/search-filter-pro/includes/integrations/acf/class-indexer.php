<?php
/**
 * ACF Indexer Integration
 *
 * Handles index value extraction for ACF fields.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations\Acf;

use Search_Filter\Features;
use Search_Filter\Integrations;
use Search_Filter_Pro\Indexer\Query;
use Search_Filter_Pro\Integrations\Woocommerce\Indexer as WC_Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ACF field indexing.
 *
 * @since 3.2.3
 */
class Indexer {

	/**
	 * Initialize ACF indexer integration.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public static function init() {
		// Add indexer support.
		add_filter( 'search-filter-pro/indexer/sync_field_index/override_values', array( __CLASS__, 'index_values' ), 10, 3 );
		// Add suppor for legacy-type searching via the bitmap indexer.
		add_filter( 'search-filter-pro/indexer/sync_field_index/override_values', array( __CLASS__, 'index_legacy_search_values' ), 10, 3 );
		// When the beta enhanced search is not enabled, fall back to indexing in the bitmap table to match legacy behaviour.
		add_filter( 'search-filter-pro/indexer/strategy/supports', array( __CLASS__, 'add_search_support_to_bitmap_indexer' ), 10, 3 );
		// Add custom bitmap query filtering for legacy search field support.
		add_filter( 'search-filter-pro/indexer/query/field_result_bitmaps', array( __CLASS__, 'add_search_bitmaps' ), 10, 2 );
		add_filter( 'search-filter-pro/indexer/query/search/result', array( __CLASS__, 'update_query_search_result' ), 10, 3 );
	}

	/**
	 * Add search fields to bitmap indexer when enhanced search is not enabled.
	 *
	 * Matches legacy indexer behaviour.
	 *
	 * @since 3.0.0
	 *
	 * @param bool                                               $supports Whether the strategy supports the field.
	 * @param \Search_Filter\Fields\Field                        $field    The field being checked.
	 * @param \Search_Filter_Pro\Indexer\Strategy\Index_Strategy $strategy The indexer strategy.
	 * @return bool Whether the strategy supports the field.
	 */
	public static function add_search_support_to_bitmap_indexer( $supports, $field, $strategy ) {

		// Only filter for bitmap strategy.
		if ( $strategy->get_type() !== 'bitmap' ) {
			return $supports;
		}

		// Ensure the field is a search field.
		if ( $field->get_attribute( 'type' ) !== 'search' ) {
			return $supports;
		}

		// Ensure we're looking at an ACF field.
		if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
			return $supports;
		}

		// Disable all search strategy support if beta features and enhanced search is not enabled.
		if ( self::is_enhanced_search_available() ) {
			// Bail early if enhanced search is enabled.
			return $supports;
		}

		return true;
	}


	/**
	 * Add search bitmaps for ACF fields when enhanced search is not enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param array $field_result_bitmaps Array of field result bitmaps.
	 * @param array $fields_by_strategy   Fields grouped by strategy type.
	 * @return array Modified field result bitmaps.
	 */
	public static function add_search_bitmaps( $field_result_bitmaps, $fields_by_strategy ) {
		// Bail early if enhanced search is enabled.
		if ( self::is_enhanced_search_available() ) {
			return $field_result_bitmaps;
		}
		// Loop through fields without strategies and add see if we need to  add search bitmaps.
		foreach ( $fields_by_strategy['none'] as $field ) {

			if ( $field->get_attribute( 'type' ) !== 'search' ) {
				continue;
			}
			// Ensure we're looking at an ACF field.
			if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
				continue;
			}

			// Search the bitmap table for matching values.
			$search_value = $field->get_value();

			if ( $search_value === '' ) {
				continue;
			}

			$result_bitmaps = \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct::get_field_bitmaps_like( $field->get_id(), $search_value );

			// Extract and combine bitmaps for all values returned.
			$bitmaps = array();
			foreach ( $result_bitmaps as $result_bitmap ) {
				$bitmaps[] = $result_bitmap['bitmap'];
			}

			// Merge results across all matching values.
			$combined_bitmap                          = Query::combine_bitmaps( $bitmaps, 'merge' );
			$field_result_bitmaps[ $field->get_id() ] = $combined_bitmap ?? new \Search_Filter_Pro\Indexer\Bitmap();
		}

		return $field_result_bitmaps;
	}

	/**
	 * Update the query search result for ACF fields when enhanced search is not enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter_Pro\Indexer\Bitmap|null $search_result        The current search result bitmap.
	 * @param array                                  $fields_by_strategy   Fields grouped by strategy type.
	 * @param array                                  $field_result_bitmaps Array of field result bitmaps.
	 * @return \Search_Filter_Pro\Indexer\Bitmap|null Modified search result bitmap.
	 */
	public static function update_query_search_result( $search_result, $fields_by_strategy, $field_result_bitmaps ) {

		// Bail early if enhanced search is enabled.
		if ( self::is_enhanced_search_available() ) {
			return $search_result;
		}
		// Loop through fields without strategies and add see if we need to  add search bitmaps.
		// Loop through fields without strategies and add see if we need to  add search bitmaps.
		foreach ( $fields_by_strategy['none'] as $field ) {

			if ( $field->get_attribute( 'type' ) !== 'search' ) {
				continue;
			}
			// Ensure we're looking at an ACF field.
			if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
				continue;
			}

			// Search the bitmap table for matching values.
			$search_value = $field->get_value();

			if ( $search_value === '' ) {
				continue;
			}

			$field_result_bitmap = $field_result_bitmaps[ $field->get_id() ];

			// One of our search fields returned no results, so mark the search as failed.
			if ( $field_result_bitmap->is_empty() ) {
				$search_result['failed'] = true;
			} else {
				$search_result['bitmaps'][] = $field_result_bitmap;
			}
		}

		return $search_result;
	}
	/**
	 * Get the field values for a post.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $field_key    The ACF field key.
	 * @param    int    $object_id    The object ID.
	 * @return   array    The field values.
	 */
	public static function get_post_field_values( $field_key, $object_id ) {

		$acf_field = \acf_get_field( $field_key );

		if ( empty( $acf_field ) ) {
			return array();
		}

		// Track the order of the field names in the hierarchy.
		$field_keys_hierarchy = array();
		// Traverse until we get to the top level parent field.
		$parent_field     = $acf_field;
		$top_parent_field = $acf_field;
		while ( isset( $parent_field['parent'] ) && ! empty( $parent_field['parent'] ) ) {
			$field_keys_hierarchy[] = $parent_field['key'];
			// Now move up the hierarchy to the parent.
			$parent_field = \acf_get_field( $parent_field['parent'] );
			// The top parent field should return false as it would be the to level field group.
			if ( $parent_field ) {
				$top_parent_field = $parent_field;
			}
		}

		$field_keys_hierarchy = array_reverse( $field_keys_hierarchy );

		$values = array();
		// We need to check if the to level field is a repeater.  If so, we can get the values from it directly.
		if ( $top_parent_field['key'] === $field_key ) {
			// Then there is no nesting, so we can just get the values from the field directly.
			$field_values = \get_field( $field_key, $object_id, false, false );
			$values       = self::normalise_index_field_values( $field_values );
		} elseif ( $top_parent_field['type'] === 'repeater' ) {
			// Then we have a repeater, so get the rows/values, and iterate through them.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = self::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'group' ) {
			// Then we have a group.
			$field_values = \get_field( $top_parent_field['name'], $object_id, false, false );

			if ( $field_values ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_group_values( $field_keys_hierarchy, $field_values );
				$values       = self::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'flexible_content' ) {
			// Then we have a repeater, so get the rows/values, and plant to iterate through them.
			// Need to make a recursive function to keep iterationg through sub repeaters until we find the field we want.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = self::normalise_index_field_values( $field_values );
			}
		}

		$values = self::parse_field_values( $values );

		return array_unique( $values );
	}



	/**
	 * Figure out if the value(s) are empty or not.
	 *
	 * 0 should not be considered empty.
	 *
	 * @param string|array|bool|null $values The values to check.
	 * @return bool True if the values are empty.
	 */
	private static function values_are_empty( $values ) {
		// ACF returns false or null for fields that have never been set.
		if ( $values === false || $values === null ) {
			return true;
		}
		if ( is_array( $values ) && empty( $values ) ) {
			return true;
		}
		if ( is_string( $values ) && $values === '' ) {
			return true;
		}
		return false;
	}
	/**
	 * Normalise the index field values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $field_values    The field values.
	 * @return   array    The normalised field values.
	 */
	public static function normalise_index_field_values( $field_values ) {
		$values = array();

		// ACF returns an empty string when there are no values for many field types.
		if ( self::values_are_empty( $field_values ) ) {
			return $values;
		}
		if ( ! is_array( $field_values ) ) {
			$values[] = $field_values;
		} else {
			$values = $field_values;
		}
		return $values;
	}
	/**
	 * Override the index values and add ACF values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array                       $values    The values to index.
	 * @param    \Search_Filter\Fields\Field $field    The field to get the values for.
	 * @param    int                         $object_id    The object ID to get the values for.
	 * @return   array    The values to index.
	 */
	public static function index_values( $values, $field, $object_id ) {
		if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
			return $values;
		}

		// In case we support legacy search indexing suppor (via the bitmap table)
		// bail early if we find a search field.
		if ( $field->get_attribute( 'type' ) === 'search' ) {
			return $values;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		// Add support for WooCommerce products specifically.
		// 1. If we have a product which does not have variations, continue as normal.
		// 2. If it has variations, then skip, and allow indexing on the variation level.
		// 3. If we have a variation, we want to copy over the parent product values.
		$woocommerce_product_post = self::get_woocommerce_product_type( $object_id );

		if ( ! $woocommerce_product_post ) {
			// Return ACF values for the object ID as normal.
			return self::get_post_field_values( $field_key, $object_id );

		} elseif ( $woocommerce_product_post === 'product' ) {

			// We have a product, check if it has variations.
			// #1 If its not variable / doesn't have variations, setup for it, continue as normal.
			// Use WC cached product loader to avoid repeated wc_get_product() calls.
			$product = WC_Indexer::get_cached_product( $object_id );
			if ( ! $product ) {
				return $values;
			}

			// Skip setting up the product if its variable and has variations.
			if ( $product->is_type( 'variable' ) && ! empty( $product->get_children() ) ) {
				return $values;
			}

			// #2 We have a simple product, or a variable product without any children yet.
			return self::get_post_field_values( $field_key, $object_id );

		} elseif ( $woocommerce_product_post === 'product_variation' ) {
			// #3 We have a variation.
			// Use WC cached product loader to avoid repeated wc_get_product() calls.
			$product_variation = WC_Indexer::get_cached_product( $object_id );
			if ( ! $product_variation ) {
				return $values;
			}
			if ( $product_variation->get_type() !== 'variation' ) {
				return $values;
			}
			// Now get the parent product ID.
			$parent_id = $product_variation->get_parent_id();
			return self::get_post_field_values( $field_key, $parent_id );

		}
		return $values;
	}
	/**
	 * Override the index values and add ACF values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array                       $values    The values to index.
	 * @param    \Search_Filter\Fields\Field $field    The field to get the values for.
	 * @param    int                         $object_id    The object ID to get the values for.
	 * @return   array|null    The values to index.
	 */
	public static function index_legacy_search_values( $values, $field, $object_id ) {

		if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
			return $values;
		}

		// Support legacy behaviour by adding search data to the bitmap indexer
		// when enhanced search is not enabled.
		if ( $field->get_attribute( 'type' ) !== 'search' ) {
			return $values;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		// Extract ACF field labels (preferred for search).
		$field_values = Search_Indexer::get_post_field_labels( $field_key, $object_id );

		if ( empty( $field_values ) ) {
			return null;
		}

		// Normalize to flat string array.
		return Search_Indexer::normalize_search_field_values( $field_values );
	}
	/**
	 * Get the WooCommerce product type for the object ID, false if not a WC product or variation.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $object_id  The object ID.
	 * @return false|string   The product type, or false if not a WC product/variation.
	 */
	private static function get_woocommerce_product_type( $object_id ) {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		// Bail if WooCommerce integration is not enabled.
		if ( ! Integrations::is_enabled( 'woocommerce' ) ) {
			return false;
		}

		// Return false if not a product or variation.
		if ( 'product_variation' === get_post_type( $object_id ) ) {
			return 'product_variation';
		} elseif ( 'product' === get_post_type( $object_id ) ) {
			return 'product';
		}

		return false;
	}


	/**
	 * Parse the index field values.
	 *
	 * Some data types need to be parsed into a format that can be indexed.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $field_values    The field values.
	 * @return   array    The parsed field values.
	 */
	public static function parse_field_values( $field_values ) {
		if ( empty( $field_values ) ) {
			return $field_values;
		}

		// TODO - this looks like it no longer in use - it used to be used
		// to format dates before indexing them but that is now handled at
		// the query level.
		return $field_values;
	}
	/**
	 * Check if the enhanced search features are enabled.
	 *
	 * @return true|false
	 */
	private static function is_enhanced_search_available() {
		if ( Features::is_enabled( 'betaFeatures' ) ) {
			$enhanced_search_enabled = Features::get_setting_value( 'beta-features', 'enhancedSearch' ) === 'yes';
			if ( $enhanced_search_enabled ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Get the nested content values.
	 *
	 * Works with repeaters and flexible content types.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $hierarchy    The hierarchy.
	 * @param    array $field_rows    The field rows.
	 * @return   array    The nested repeater values.
	 */
	public static function get_nested_content_values( $hierarchy, $field_rows ) {
		$values            = array();
		$current_hierarchy = array_shift( $hierarchy );
		foreach ( $field_rows as $field_row ) {
			// Remove first element from hierarchy.
			if ( isset( $field_row[ $current_hierarchy ] ) ) {
				// Then we have a match, so recurse.

				// If there is no more hierarchies left, then we are on the final row, so return the value.
				if ( count( $hierarchy ) === 0 ) {
					// Then we are on the final row, so return the value.
					if ( is_array( $field_row[ $current_hierarchy ] ) ) {
						$values = array_merge( $values, $field_row[ $current_hierarchy ] );
					} else {
						$values[] = $field_row[ $current_hierarchy ];
					}
				} else {
					$values = array_merge( $values, self::get_nested_content_values( $hierarchy, $field_row[ $current_hierarchy ] ) );
				}
			}
		}
		return $values;
	}

	/**
	 * Get the nested group values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $hierarchy    The hierarchy.
	 * @param    array $field_values    The field values.
	 * @return   array    The nested group values.
	 */
	public static function get_nested_group_values( $hierarchy, $field_values ) {
		$values            = array();
		$current_hierarchy = array_shift( $hierarchy );
		// Remove first element from hierarchy.
		if ( isset( $field_values[ $current_hierarchy ] ) ) {
			// Then we have a match, so recurse.
			// If there is no more hierarchies left, then we are on the final value, so return it.
			if ( count( $hierarchy ) === 0 ) {
				// Then we are on the final row, so return the value.
				if ( is_array( $field_values[ $current_hierarchy ] ) ) {
					$values = array_merge( $values, $field_values[ $current_hierarchy ] );
				} else {
					$values[] = $field_values[ $current_hierarchy ];
				}
			} else {
				$values = array_merge( $values, self::get_nested_group_values( $hierarchy, $field_values[ $current_hierarchy ] ) );
			}
		}
		return $values;
	}
}
