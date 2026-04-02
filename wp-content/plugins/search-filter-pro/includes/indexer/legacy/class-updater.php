<?php
/**
 * Legacy Updater - Legacy index write operations.
 *
 * Handles writing to and clearing from the legacy forward index table.
 * Used during migration to maintain dual-write capability.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro\Indexer\Legacy
 */

namespace Search_Filter_Pro\Indexer\Legacy;

use Search_Filter_Pro\Indexer\Legacy\Database\Index_Query;
use Search_Filter_Pro\Indexer\Legacy\Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Updater class.
 *
 * Provides write and clear operations for the legacy index table.
 * Used during migration to maintain dual-write with legacy system.
 *
 * @since 3.2.0
 */
class Updater {

	/**
	 * Write field values to legacy index.
	 *
	 * Converts field values to legacy index rows and inserts them.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $values       Array of values (not used directly for legacy).
	 * @param array $object_data  Array of object data with field_id, object_id, parent_id, value.
	 * @return bool True on success, false on failure.
	 */
	public static function write_field_values( $field_id, $values, $object_data ) {
		if ( empty( $object_data ) ) {
			return false;
		}

		Manager::ensure_tables();

		$items = array();
		foreach ( $object_data as $data ) {
			$items[] = array(
				'field_id'         => $field_id,
				'object_id'        => $data['object_id'],
				'object_parent_id' => $data['object_parent_id'],
				'value'            => $data['value'],
			);
		}

		$query = new Index_Query();
		return $query->add_items( $items ) !== false;
	}

	/**
	 * Clear field index from legacy table.
	 *
	 * Removes rows for specified field ID and optionally object ID.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id  Field ID.
	 * @param int $object_id Object ID (-1 for all objects).
	 * @return bool True on success, false on failure.
	 */
	public static function clear_field_index( $field_id, $object_id = -1 ) {

		Manager::ensure_tables();

		$query = new Index_Query();

		$delete_where = array( 'field_id' => $field_id );

		if ( $object_id !== -1 ) {
			$delete_where['object_id'] = $object_id;
		}

		return $query->delete_items( $delete_where ) !== false;
	}
	/**
	 * Clear object index from legacy table.
	 *
	 * Removes rows for specified field ID and optionally object ID.
	 *
	 * @since 3.2.0
	 *
	 * @param int $object_id Object ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function clear_object_index( $object_id ) {

		Manager::ensure_tables();

		$query                     = new Index_Query();
		$delete_where['object_id'] = $object_id;

		return $query->delete_items( $delete_where ) !== false;
	}

	/**
	 * Clear entire legacy index (all fields).
	 *
	 * Typically called during migration finalization or full rebuild.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True on success.
	 */
	public static function reset() {

		$index_table = new Database\Index_Table();
		if ( $index_table->exists() ) {
			return $index_table->truncate();
		}
		return true;
	}
}
