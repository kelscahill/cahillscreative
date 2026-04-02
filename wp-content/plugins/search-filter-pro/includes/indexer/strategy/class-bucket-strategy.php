<?php
/**
 * Bucket Index Strategy.
 *
 * Handles indexing for range fields using bucketed bitmap indexes.
 * Extracts numeric values and stores them in range buckets for efficient
 * range query operations.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Strategy
 */

namespace Search_Filter_Pro\Indexer\Strategy;

use Search_Filter\Fields\Field;
use Search_Filter_Pro\Indexer\Bucket\Updater;
use Search_Filter_Pro\Indexer\Bucket\Manager as Bucket_Manager;
use Search_Filter_Pro\Indexer\Post_Sync;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bucket Index Strategy.
 *
 * Indexes numeric field values (prices, dates, ratings) into bucketed
 * bitmap structures for efficient range filtering.
 *
 * Uses an overflow-first strategy: new values go to overflow table,
 * then are periodically rebuilt into optimized percentile buckets.
 *
 * @since 3.2.0
 */
class Bucket_Strategy implements Index_Strategy {

	/**
	 * Supported interaction types.
	 *
	 * @var string[]
	 */
	protected $interaction_types = array( 'range' );

	/**
	 * Check if this strategy supports the given field.
	 *
	 * Uses the field's interaction_type to determine support.
	 * Bucket strategy handles 'range' interaction types.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to check.
	 * @return bool True if this strategy handles the field.
	 */
	public function supports( Field $field ): bool {

		$interaction_type = $field->get_interaction_type();

		$supports = $this->supports_interaction_type( $interaction_type );

		return apply_filters(
			'search-filter-pro/indexer/strategy/supports',
			$supports,
			$field,
			$this
		);
	}

	/**
	 * Check if this strategy supports the given field's interaction type.
	 *
	 * Uses the field's interaction_type to determine support.
	 * Bucket strategy handles 'range' interaction types.
	 *
	 * @since 3.2.0
	 *
	 * @param string $interaction_type The interaction type to check.
	 * @return bool True if this strategy handles the interaction type.
	 */
	public function supports_interaction_type( $interaction_type ): bool {
		return in_array( $interaction_type, $this->interaction_types, true );
	}

	/**
	 * Get the interaction types this strategy supports.
	 *
	 * @since 3.2.0
	 *
	 * @return string[] Array of supported interaction types.
	 */
	public function get_interaction_types(): array {
		return $this->interaction_types;
	}

	/**
	 * Get the strategy type identifier.
	 *
	 * @since 3.2.0
	 *
	 * @return string The strategy type.
	 */
	public function get_type(): string {
		return 'bucket';
	}

	/**
	 * Extract indexable values from a post for a field.
	 *
	 * Applies the override hook for integrations (ACF, WooCommerce),
	 * then falls back to default extraction based on dataType.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param Field $field     The field instance.
	 * @return array Array of numeric values to index.
	 */
	public function extract( int $object_id, Field $field ): array {
		// Apply override hook for integrations (ACF, WooCommerce, etc.).
		$values = apply_filters(
			'search-filter-pro/indexer/sync_field_index/override_values',
			null,
			$field,
			$object_id
		);

		// If override returned an array, use it.
		if ( is_array( $values ) ) {
			return $this->apply_field_formatting( $values, $field );
		}

		// Default extraction based on data type.
		$values = $this->extract_default_values( $object_id, $field );

		// Allow fields formatting override.
		return $this->apply_field_formatting( $values, $field );
	}

	/**
	 * Apply field-specific formatting to extracted data.
	 *
	 * Allows fields to modify extracted data before indexing.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $data The extracted data.
	 * @param Field $field  The field instance.
	 * @return mixed The formatted data.
	 */
	public function apply_field_formatting( $data, Field $field ) {
		// Allow fields to format index data.
		if ( method_exists( $field, 'prepare_index_data' ) ) {
			$data = $field->prepare_index_data( $data, $this->get_type() );
		}
		return $data;
	}

	/**
	 * Extract values using default logic based on dataType.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param Field $field     The field instance.
	 * @return array Array of numeric values.
	 */
	private function extract_default_values( int $object_id, Field $field ): array {
		$data_type = $field->get_attribute( 'dataType' );

		switch ( $data_type ) {
			case 'custom_field':
				return $this->get_custom_field_values( $object_id, $field->get_attribute( 'dataCustomField' ) );

			case 'post_attribute':
				return $this->get_post_attribute_values( $object_id, $field->get_attribute( 'dataPostAttribute' ) );

			default:
				return array();
		}
	}

	/**
	 * Index a single post for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id        The post/object ID.
	 * @param Field $field            The field instance.
	 * @return bool True on success.
	 */
	public function index( int $object_id, Field $field ): bool {
		// Guard: skip if bucket tables shouldn't exist.
		if ( ! Bucket_Manager::should_use() ) {
			return false;
		}

		$field_id = $field->get_id();

		// Clear existing index data for this object/field.
		$this->clear( $field_id, $object_id );

		// Extract values.
		$values = $this->extract( $object_id, $field );

		if ( empty( $values ) ) {
			return true; // Nothing to index is success.
		}

		// Write each value to bucket index (typically single value for range fields).
		foreach ( $values as $value ) {
			Updater::handle_post_update( $object_id, $field_id, (float) $value );
		}

		return true;
	}

	/**
	 * Clear index data for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id  The field ID.
	 * @param int $object_id The object ID to clear, or -1 for all.
	 * @return bool True on success.
	 */
	public function clear( int $field_id, int $object_id = -1 ): bool {
		if ( $object_id === -1 ) {
			return Updater::clear_field_index( $field_id );
		}

		return Updater::remove_post_from_field( $field_id, $object_id );
	}

	/**
	 * Get custom field values for a post.
	 *
	 * Delegates to Post_Sync for extraction, then filters for numeric values
	 * and casts to float (required for range bucket calculations).
	 *
	 * @since 3.2.0
	 *
	 * @param int         $object_id        The post ID.
	 * @param string|null $custom_field_key The meta key.
	 * @return array Array of numeric values.
	 */
	private function get_custom_field_values( int $object_id, ?string $custom_field_key ): array {
		if ( ! $custom_field_key ) {
			return array();
		}
		$values = Post_Sync::get_post_custom_field_values( $object_id, $custom_field_key );

		// Filter to numeric values only and cast to float for range calculations.
		return array_map( 'floatval', array_filter( $values, 'is_numeric' ) );
	}

	/**
	 * Get post attribute values for range fields.
	 *
	 * Handles date-based attributes by converting to timestamps.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $object_id The post ID.
	 * @param string|null $attribute The attribute type.
	 * @return array Array of numeric values.
	 */
	private function get_post_attribute_values( int $object_id, ?string $attribute ): array {

		switch ( $attribute ) {
			case 'post_published_date':
				$post_date = get_post_field( 'post_date', $object_id );
				if ( $post_date ) {
					$timestamp = strtotime( $post_date );
					return $timestamp !== false ? array( (float) $timestamp ) : array();
				}
				return array();

			case 'post_modified_date':
				$post_modified = get_post_field( 'post_modified', $object_id );
				if ( $post_modified ) {
					$timestamp = strtotime( $post_modified );
					return $timestamp !== false ? array( (float) $timestamp ) : array();
				}
				return array();

			default:
				return array();
		}
	}
}
