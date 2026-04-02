<?php
/**
 * Index Strategy Interface.
 *
 * Defines the contract for indexing strategies that handle different
 * field types (bitmap, bucket, search).
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Strategy
 */

namespace Search_Filter_Pro\Indexer\Strategy;

use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index Strategy Interface.
 *
 * Each indexing strategy handles:
 * - Determining if it supports a given field
 * - Extracting data from posts for indexing
 * - Writing data to the appropriate index
 * - Clearing index data
 *
 * Strategies support both single-post indexing (immediate write) and
 * batch indexing (extraction only, for Batch_Writer to flush later).
 *
 * @since 3.2.0
 */
interface Index_Strategy {

	/**
	 * Check if this strategy supports the given field.
	 *
	 * Called by the factory to determine which strategy handles a field.
	 * Strategies should check field type, data type, and any other
	 * relevant attributes.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to check.
	 * @return bool True if this strategy handles the field.
	 */
	public function supports( Field $field ): bool;

	/**
	 * Check if this strategy supports the given field interaction type.
	 *
	 * Doesn't actually check if a _particular_ field instance is supported,
	 * just if it _could_ be supported based on interaction type.
	 *
	 * @since 3.2.0
	 *
	 * @param string $interaction_type The interaction type to check.
	 *
	 * @return bool True if this strategy handles the field.
	 */
	public function supports_interaction_type( string $interaction_type ): bool;

	/**
	 * Get the interaction types this strategy supports.
	 *
	 * @since 3.2.0
	 *
	 * @return string[] Array of supported interaction types.
	 */
	public function get_interaction_types(): array;

	/**
	 * Get the strategy type identifier.
	 *
	 * Returns a string identifying the index type: 'bitmap', 'bucket', or 'search'.
	 * Used by Batch_Writer to route data to correct flush method.
	 *
	 * @since 3.2.0
	 *
	 * @return string The strategy type.
	 */
	public function get_type(): string;

	/**
	 * Extract indexable data from a post for a field.
	 *
	 * This method extracts data without writing to the index.
	 * Used by batch indexing to collect data for later batch flush.
	 *
	 * For bitmap/bucket: Returns array of values (strings or numbers).
	 * For search: Returns array of field_name => content pairs.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id The post/object ID.
	 * @param Field $field     The field instance.
	 * @return array Extracted data (format depends on strategy type).
	 */
	public function extract( int $object_id, Field $field ): array;

	/**
	 * Index a single post for a field.
	 *
	 * Performs both extraction and immediate write to the index.
	 * Used by single-post sync operations.
	 *
	 * Implementations should:
	 * 1. Clear existing index data for this object/field
	 * 2. Extract data using extract()
	 * 3. Write to the appropriate index
	 *
	 * @since 3.2.0
	 *
	 * @param int   $object_id        The post/object ID.
	 * @param Field $field            The field instance.
	 * @return bool True on success.
	 */
	public function index( int $object_id, Field $field ): bool;

	/**
	 * Clear index data for a field.
	 *
	 * Removes index entries for a specific object, or all objects
	 * if object_id is -1.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id  The field ID.
	 * @param int $object_id The object ID to clear, or -1 for all.
	 * @return bool True on success.
	 */
	public function clear( int $field_id, int $object_id = -1 ): bool;

	/**
	 * Apply field-specific formatting to extracted values/content.
	 *
	 * Allows fields to modify extracted values before indexing.
	 * For example, date fields may format dates to a standard format.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $data The extracted data.
	 * @param Field $field  The field instance.
	 * @return mixed The formatted data.
	 */
	public function apply_field_formatting( $data, Field $field );
}
