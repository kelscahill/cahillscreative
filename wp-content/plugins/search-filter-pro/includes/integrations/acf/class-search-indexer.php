<?php
/**
 * ACF Search Indexer Integration
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations\Acf;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ACF search content indexing.
 *
 * @since 3.2.3
 */
class Search_Indexer {

	/**
	 * Initialize ACF search indexer integration.
	 *
	 * @since 3.0.0
	 * @return void
	 */
	public static function init() {
		// Add search indexer support.
		add_filter( 'search-filter-pro/indexer/sync_field_search_index/override_values', array( __CLASS__, 'add_acf_search_content' ), 10, 4 );
	}

	/**
	 * Add ACF field content to search index.
	 *
	 * Extracts labels (not values) for ACF fields with value/label combinations.
	 *
	 * @since 3.0.9
	 *
	 * @param    string|null                 $content   Content string (null = not handled yet).
	 * @param    array                       $source    Single data source configuration.
	 * @param    \Search_Filter\Fields\Field $field     Field object.
	 * @param    int                         $object_id Post ID.
	 * @return   string|null Content string or null if not applicable.
	 */
	public static function add_acf_search_content( $content, $source, $field, $object_id ) {
		// Only handle acf_field data type.
		if ( ! isset( $source['dataType'] ) || $source['dataType'] !== 'acf_field' ) {
			return $content;
		}

		if ( ! function_exists( 'acf_get_field' ) ) {
			return null;
		}

		if ( ! isset( $source['dataAcfField'] ) ) {
			return null;
		}

		$field_key = $source['dataAcfField'];

		// Extract ACF field labels (preferred for search).
		$field_values = self::get_post_field_labels( $field_key, $object_id );

		if ( empty( $field_values ) ) {
			return null;
		}

		// Normalize to flat string array.
		$normalized = self::normalize_search_field_values( $field_values );

		if ( empty( $normalized ) ) {
			return null;
		}

		// Return concatenated content string.
		return implode( ' ', $normalized );
	}

	/**
	 * Get ACF field labels for search indexing.
	 *
	 * For fields with value/label choice structures, returns labels instead of values
	 * for better search relevance. Works with any ACF field type that uses choices array.
	 *
	 * Shares core logic with get_post_field_values() but returns labels where applicable.
	 *
	 * @since 3.0.9
	 *
	 * @param    string $field_key ACF field key.
	 * @param    int    $object_id Post ID.
	 * @return   array Field labels.
	 */
	public static function get_post_field_labels( $field_key, $object_id ) {
		$acf_field = \acf_get_field( $field_key );

		if ( ! $acf_field ) {
			return array();
		}

		// Build hierarchy of parent fields (for nested fields).
		$field_keys_hierarchy = array();
		$parent_field         = $acf_field;
		$top_parent_field     = $acf_field;

		while ( isset( $parent_field['parent'] ) && ! empty( $parent_field['parent'] ) ) {
			$field_keys_hierarchy[] = $parent_field['key'];
			$parent_field           = \acf_get_field( $parent_field['parent'] );
			if ( $parent_field ) {
				$top_parent_field = $parent_field;
			}
		}

		$field_keys_hierarchy = array_reverse( $field_keys_hierarchy );

		// Get field values using shared logic.
		$field_values = self::get_post_field_values_internal(
			$field_key,
			$object_id,
			$field_keys_hierarchy,
			$top_parent_field
		);

		// Convert values to labels where applicable.
		return self::convert_values_to_labels( $field_values, $acf_field );
	}
	/**
	 * Convert ACF field values to labels for search indexing.
	 *
	 * Checks if field has value/label choices structure and converts accordingly.
	 * This approach is future-proof and works with any ACF field type that uses choices.
	 *
	 * @since 3.0.9
	 *
	 * @param    array $values    Field values.
	 * @param    array $acf_field ACF field configuration.
	 * @return   array Field labels.
	 */
	private static function convert_values_to_labels( $values, $acf_field ) {
		// Check if field has choices array (value => label pairs).
		if ( ! isset( $acf_field['choices'] ) || ! is_array( $acf_field['choices'] ) ) {
			// No choices - return values as-is.
			return $values;
		}

		$choices = $acf_field['choices'];

		if ( empty( $choices ) ) {
			return $values;
		}

		// Verify this is actually a value/label structure.
		// If any choice has key !== value, it's a value/label field.
		$has_value_label_pairs = false;
		foreach ( $choices as $choice_value => $choice_label ) {
			if ( $choice_value !== $choice_label ) {
				$has_value_label_pairs = true;
				break;
			}
		}

		if ( ! $has_value_label_pairs ) {
			// No value/label distinction - return values as-is.
			return $values;
		}

		// Convert values to labels.
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$labels = array();
		foreach ( $values as $value ) {
			if ( isset( $choices[ $value ] ) ) {
				$labels[] = $choices[ $value ];
			} else {
				// Fallback to value if label not found.
				$labels[] = $value;
			}
		}

		return $labels;
	}

	/**
	 * Internal method to get field values (shared logic).
	 *
	 * Extracted from get_post_field_values() to share between value and label extraction.
	 *
	 * @since 3.0.9
	 *
	 * @param    string $field_key           ACF field key.
	 * @param    int    $object_id           Post ID.
	 * @param    array  $field_keys_hierarchy Hierarchy of parent field keys.
	 * @param    array  $top_parent_field    Top-level parent field config.
	 * @return   array Field values.
	 */
	private static function get_post_field_values_internal( $field_key, $object_id, $field_keys_hierarchy, $top_parent_field ) {
		$values = array();

		// We need to check if the top level field is a repeater. If so, we can get the values from it directly.
		if ( $top_parent_field['key'] === $field_key ) {
			// Then there is no nesting, so we can just get the values from the field directly.
			$field_values = \get_field( $field_key, $object_id, false, false );
			$values       = Indexer::normalise_index_field_values( $field_values );
		} elseif ( $top_parent_field['type'] === 'repeater' ) {
			// Then we have a repeater, so get the rows/values, and iterate through them.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = Indexer::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = Indexer::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'group' ) {
			// Then we have a group.
			$field_values = \get_field( $top_parent_field['name'], $object_id, false, false );

			if ( $field_values ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = Indexer::get_nested_group_values( $field_keys_hierarchy, $field_values );
				$values       = Indexer::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'flexible_content' ) {
			// Then we have flexible content, so get the rows/values, and iterate through them.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = Indexer::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = Indexer::normalise_index_field_values( $field_values );
			}
		}

		return $values;
	}

	/**
	 * Normalize field values for search indexing.
	 *
	 * Converts arrays, objects, and mixed values to searchable strings.
	 *
	 * @since 3.0.9
	 *
	 * @param    mixed $values Values to normalize.
	 * @return   array Flat array of strings.
	 */
	public static function normalize_search_field_values( $values ) {
		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		$normalized = array();

		foreach ( $values as $value ) {
			if ( is_scalar( $value ) && '' !== $value ) {
				$normalized[] = (string) $value;
			} elseif ( is_array( $value ) ) {
				// Recursively flatten.
				$flat       = self::normalize_search_field_values( $value );
				$normalized = array_merge( $normalized, $flat );
			} elseif ( is_object( $value ) ) {
				// Convert objects to string representation.
				if ( method_exists( $value, '__toString' ) ) {
					$normalized[] = (string) $value;
				} elseif ( isset( $value->post_title ) ) {
					// WordPress post object.
					$normalized[] = $value->post_title;
				}
			}
		}

		return array_filter( $normalized );
	}
}
