<?php
/**
 * Bitmap Index Strategy.
 *
 * Handles indexing for choice and advanced fields using bitmap indexes.
 * Extracts discrete values (slugs, IDs) and stores them as compressed bitmaps.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Strategy
 */

namespace Search_Filter_Pro\Indexer\Strategy;

use Search_Filter\Fields\Field;
use Search_Filter_Pro\Indexer\Bitmap;
use Search_Filter_Pro\Indexer\Bitmap\Manager as Bitmap_Manager;
use Search_Filter_Pro\Indexer\Post_Sync;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitmap Index Strategy.
 *
 * Indexes discrete field values (categories, tags, custom field values)
 * into bitmap data structures for fast filtering operations.
 *
 * @since 3.2.0
 */
class Bitmap_Strategy implements Index_Strategy {

	/**
	 * Supported interaction types.
	 *
	 * @var string[]
	 */
	protected $interaction_types = array( 'choice' );

	/**
	 * Check if this strategy supports the given field.
	 *
	 * Uses the field's interaction_type to determine support.
	 * Bitmap strategy handles 'choice' interaction types.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to check.
	 * @return bool True if this strategy handles the field.
	 */
	public function supports( Field $field ): bool {

		// Get interaction type from the field instance.
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
	 * Bitmap strategy handles 'choice' interaction types.
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
		return 'bitmap';
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
	 * @return array Array of values to index.
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
			$values = $this->sanitize_values( $values );
			return $this->apply_field_formatting( $values, $field );
		}

		// Default extraction based on data type.
		$values = $this->sanitize_values( $this->extract_default_values( $object_id, $field ) );

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
	 * Sanitize values by removing the unit separator character.
	 *
	 * The unit separator (\x1F) is used internally to escape commas in URL values.
	 * If this character exists in source data, it must be stripped to prevent
	 * encoding/decoding issues.
	 *
	 * @since 3.2.0
	 *
	 * @param array $values The values to sanitize.
	 * @return array The sanitized values.
	 */
	private function sanitize_values( array $values ): array {
		return array_map(
			function ( $value ) {
				if ( is_string( $value ) ) {
					return str_replace( "\x1F", '', $value );
				}
				return $value;
			},
			$values
		);
	}

	/**
	 * Extract values using default logic based on dataType.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param Field $field     The field instance.
	 * @return array Array of values.
	 */
	private function extract_default_values( int $object_id, Field $field ): array {
		$data_type = $field->get_attribute( 'dataType' );

		switch ( $data_type ) {
			case 'taxonomy':
				return $this->get_taxonomy_values( $object_id, $field->get_attribute( 'dataTaxonomy' ) );

			case 'post_attribute':
				return $this->get_post_attribute_values( $object_id, $field->get_attribute( 'dataPostAttribute' ) );

			case 'custom_field':
				return $this->get_custom_field_values( $object_id, $field->get_attribute( 'dataCustomField' ) );

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
		// Guard: skip if bitmap tables shouldn't exist.
		if ( ! Bitmap_Manager::should_use() ) {
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

		// Write to bitmap index.
		return Bitmap\Updater::add_post_to_bitmaps( $field_id, $object_id, $values );
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
			return Bitmap\Updater::clear_field_index( $field_id );
		}

		return Bitmap\Updater::remove_post_from_field( $field_id, $object_id );
	}

	/**
	 * Get taxonomy term values for a post.
	 *
	 * Delegates to Post_Sync for consistent value extraction.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $object_id     The post ID.
	 * @param string|null $taxonomy_name The taxonomy name.
	 * @return array Array of term slugs.
	 */
	private function get_taxonomy_values( int $object_id, ?string $taxonomy_name ): array {
		if ( ! $taxonomy_name ) {
			return array();
		}
		return Post_Sync::get_post_taxonomy_values( $object_id, $taxonomy_name );
	}

	/**
	 * Get post attribute values.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $object_id The post ID.
	 * @param string|null $attribute The attribute type.
	 * @return array Array of values.
	 */
	private function get_post_attribute_values( int $object_id, ?string $attribute ): array {

		switch ( $attribute ) {
			case 'post_type':
				$post_type = get_post_type( $object_id );
				return $post_type !== false ? array( $post_type ) : array();

			case 'post_status':
				$post_status = get_post_status( $object_id );
				return $post_status !== false ? array( $post_status ) : array();

			case 'post_author':
				$post_author = get_post_field( 'post_author', $object_id );
				return $post_author !== false ? array( $post_author ) : array();

			case 'post_published_date':
				$post_date = get_post_field( 'post_date', $object_id );
				return $post_date !== false ? array( $post_date ) : array();

			default:
				return array();
		}
	}

	/**
	 * Get custom field values for a post.
	 *
	 * Delegates to Post_Sync for consistent value extraction.
	 *
	 * @since 3.2.0
	 *
	 * @param int         $object_id        The post ID.
	 * @param string|null $custom_field_key The meta key.
	 * @return array Array of values.
	 */
	private function get_custom_field_values( int $object_id, ?string $custom_field_key ): array {
		if ( ! $custom_field_key ) {
			return array();
		}
		return Post_Sync::get_post_custom_field_values( $object_id, $custom_field_key );
	}
}
