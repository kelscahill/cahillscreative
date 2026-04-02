<?php
/**
 * Task Query Direct - High-performance direct database queries.
 *
 * This class provides optimized direct SQL queries for the tasks table,
 * bypassing the ORM layer for improved performance with batch operations.
 *
 * Use this class for:
 * - Batch task insertion
 * - High-performance task operations
 *
 * @package Search_Filter_Pro\Task_Runner\Database
 * @since 3.0.0
 */

namespace Search_Filter_Pro\Task_Runner\Database;

use Search_Filter_Pro\Task_Runner;
use Search_Filter_Pro\Database\Table_Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Task Query Direct Class.
 *
 * Provides high-performance direct database queries for the tasks table.
 *
 * @since 3.0.0
 */
class Task_Query_Direct {

	/**
	 * Batch insert tasks into the database.
	 *
	 * Performs a single multi-row INSERT for optimal performance.
	 * Does not check for duplicates - caller is responsible for validation.
	 * Uses batch_id to reliably retrieve inserted IDs.
	 *
	 * @since 3.0.0
	 *
	 * @param array $tasks_data Array of task data arrays, each with specific fields.
	 *                          Each task should contain:
	 *                          - type (string, required)
	 *                          - action (string, required)
	 *                          - status (string, default: 'pending')
	 *                          - object_id (int, default: 0)
	 *                          - parent_id (int, default: 0).
	 * @return array|false Array with 'count' (number of rows) and 'ids' (array of inserted IDs), or false on error.
	 */
	public static function batch_insert( $tasks_data ) {
		global $wpdb;

		if ( empty( $tasks_data ) || ! is_array( $tasks_data ) ) {
			return false;
		}

		$table_name = Task_Runner::get_table_name( 'tasks' );

		// Generate unique batch ID for this insert operation.
		$batch_id = uniqid( 'batch_', true );

		// Build the VALUES clause with placeholders.
		$values        = array();
		$place_holders = array();
		$current_time  = current_time( 'mysql' );

		foreach ( $tasks_data as $task ) {
			// Set defaults.
			$task = wp_parse_args(
				$task,
				array(
					'type'      => '',
					'action'    => '',
					'status'    => 'pending',
					'object_id' => 0,
					'parent_id' => 0,
				)
			);

			// Validate required fields.
			if ( empty( $task['type'] ) || empty( $task['action'] ) ) {
				continue;
			}

			// Add values in the correct order matching the schema.
			$values[] = $task['type'];
			$values[] = $task['action'];
			$values[] = $task['status'];
			$values[] = $task['object_id'];
			$values[] = $task['parent_id'];
			$values[] = $batch_id;
			$values[] = $current_time;

			$place_holders[] = '(%s, %s, %s, %d, %d, %s, %s)';
		}

		// If no valid tasks, return.
		if ( empty( $place_holders ) ) {
			return false;
		}

		// Build the query.
		$query  = $wpdb->prepare( 'INSERT INTO %i (type, action, status, object_id, parent_id, batch_id, date_modified) VALUES ', $table_name );
		$query .= implode( ', ', $place_holders );

		// Execute the batch insert.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( $query, $values ) );

		if ( ! $result ) {
			return false;
		}

		// Query back the actual inserted IDs using the batch_id.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE batch_id = %s ORDER BY id ASC',
				$table_name,
				$batch_id
			)
		);

		// Convert to integers.
		$ids = array_map( 'intval', $ids );

		return array(
			'count' => $result,
			'ids'   => $ids,
		);
	}

	/**
	 * Batch insert task metadata into the database.
	 *
	 * Performs a single multi-row INSERT for optimal performance when inserting
	 * metadata for multiple tasks.
	 *
	 * @since 3.0.0
	 *
	 * @param array $meta_data Array of metadata entries with task associations.
	 *                         Each entry should contain:
	 *                         - task_id (int, required) - The task ID
	 *                         - meta_key (string, required) - The metadata key
	 *                         - meta_value (mixed, required) - The metadata value.
	 * @return int|false Number of metadata rows inserted, or false on error.
	 */
	public static function batch_insert_meta( $meta_data ) {
		global $wpdb;

		if ( empty( $meta_data ) || ! is_array( $meta_data ) ) {
			return false;
		}

		$meta_table = Task_Runner::get_table_name( 'meta' );

		// Build the VALUES clause with placeholders.
		$values        = array();
		$place_holders = array();

		foreach ( $meta_data as $meta ) {
			// Validate required fields.
			if ( ! isset( $meta['task_id'] ) || ! isset( $meta['meta_key'] ) || ! isset( $meta['meta_value'] ) ) {
				continue;
			}

			// Serialize the meta value if it's an array or object (matching WordPress behavior).
			$meta_value = $meta['meta_value'];
			if ( is_array( $meta_value ) || is_object( $meta_value ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Matching WordPress metadata behavior.
				$meta_value = serialize( $meta_value );
			}

			// Add values in the correct order matching the schema.
			// Schema: search_filter_task_id, meta_key, meta_value.
			$values[] = $meta['task_id'];
			$values[] = $meta['meta_key'];
			$values[] = $meta_value;

			$place_holders[] = '(%d, %s, %s)';
		}

		// If no valid metadata, return.
		if ( empty( $place_holders ) ) {
			return false;
		}

		// Build the query.
		$query  = $wpdb->prepare( 'INSERT INTO %i (search_filter_task_id, meta_key, meta_value) VALUES ', $meta_table );
		$query .= implode( ', ', $place_holders );

		// Execute the batch insert.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( $query, $values ) );

		return $result;
	}

	/**
	 * Check if the tasks table exists.
	 *
	 * @return bool True if table exists
	 */
	public static function table_exists() {
		// Use Table API for robust existence check (works with tests and TEMPORARY tables).
		$table = Table_Manager::get( 'tasks', false );  // Don't install, just check.
		return $table && $table->exists();
	}

	/**
	 * Truncate both tasks and taskmeta tables.
	 *
	 * Use for full reset operations. This is the fastest way to clear
	 * all tasks and their metadata.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public static function truncate_all() {
		global $wpdb;

		$tasks_table = Task_Runner::get_table_name( 'tasks' );
		$meta_table  = Task_Runner::get_table_name( 'meta' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $tasks_table ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $meta_table ) );
	}

	/**
	 * Batch delete tasks by type and their associated metadata.
	 *
	 * Deletes metadata FIRST using a subquery, then deletes the tasks.
	 * This ensures no orphaned metadata is left behind.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type The task type to delete (e.g., 'indexer').
	 * @return int Number of tasks deleted.
	 */
	public static function batch_delete_by_type( $type ) {
		global $wpdb;

		$tasks_table = Task_Runner::get_table_name( 'tasks' );
		$meta_table  = Task_Runner::get_table_name( 'meta' );

		// First, delete all metadata for tasks of this type in ONE query.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE search_filter_task_id IN (
					SELECT id FROM %i WHERE type = %s
				)',
				$meta_table,
				$tasks_table,
				$type
			)
		);

		// Then delete the tasks themselves.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE type = %s',
				$tasks_table,
				$type
			)
		);

		return $result;
	}

	/**
	 * Batch delete tasks matching specific criteria and their metadata.
	 *
	 * Supports filtering by type and meta_query (for field_id, query_id, etc.).
	 * Deletes metadata FIRST, then tasks.
	 *
	 * @since 3.2.0
	 *
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type string $type       Task type (required).
	 *     @type array  $meta_query Meta query array with 'key', 'value', 'compare'.
	 * }
	 * @return int Number of tasks deleted.
	 */
	public static function batch_delete( $args = array() ) {
		global $wpdb;

		$tasks_table = Task_Runner::get_table_name( 'tasks' );
		$meta_table  = Task_Runner::get_table_name( 'meta' );

		// Build id__not_in exclusion condition if provided.
		$id_not_in_condition = '';
		if ( ! empty( $args['id__not_in'] ) && is_array( $args['id__not_in'] ) ) {
			$exclude_ids          = array_map( 'intval', $args['id__not_in'] );
			$exclude_placeholders = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$id_not_in_condition = $wpdb->prepare( " AND id NOT IN ({$exclude_placeholders})", $exclude_ids );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		}

		// If no meta_query, use simpler type-only delete.
		if ( empty( $args['meta_query'] ) ) {
			if ( ! empty( $args['type'] ) ) {
				// If we have id__not_in exclusions, we need a custom query.
				if ( $id_not_in_condition ) {
					// First delete metadata for tasks we're about to delete.
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM %i WHERE search_filter_task_id IN (
								SELECT id FROM %i WHERE type = %s{$id_not_in_condition}
							)",
							$meta_table,
							$tasks_table,
							$args['type']
						)
					);
					// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					// Then delete the tasks.
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->query(
						$wpdb->prepare(
							"DELETE FROM %i WHERE type = %s{$id_not_in_condition}",
							$tasks_table,
							$args['type']
						)
					);
					// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
				return self::batch_delete_by_type( $args['type'] );
			}
			return 0;
		}

		// Build the meta query conditions.
		$meta_conditions = array();
		$meta_values     = array();

		foreach ( $args['meta_query'] as $meta_query ) {
			if ( ! isset( $meta_query['key'] ) || ! isset( $meta_query['value'] ) ) {
				continue;
			}

			$meta_conditions[] = '(m.meta_key = %s AND m.meta_value = %s)';
			$meta_values[]     = $meta_query['key'];
			$meta_values[]     = $meta_query['value'];
		}

		if ( empty( $meta_conditions ) ) {
			return 0;
		}

		$meta_where = implode( ' OR ', $meta_conditions );

		// Build type condition.
		$type_condition = '';
		if ( ! empty( $args['type'] ) ) {
			$type_condition = $wpdb->prepare( ' AND t.type = %s', $args['type'] );
		}

		// Build id__not_in condition for meta_query path.
		$id_exclusion_condition = '';
		if ( $id_not_in_condition ) {
			// The $id_not_in_condition uses 'id', but in this query we need 't.id'.
			$id_exclusion_condition = str_replace( ' AND id NOT IN', ' AND t.id NOT IN', $id_not_in_condition );
		}

		// Get the task IDs that match our criteria.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$task_ids_query = $wpdb->prepare(
			"SELECT DISTINCT t.id FROM %i t
			INNER JOIN %i m ON t.id = m.search_filter_task_id
			WHERE ({$meta_where}){$type_condition}{$id_exclusion_condition}",
			array_merge( array( $tasks_table, $meta_table ), $meta_values )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$task_ids = $wpdb->get_col( $task_ids_query );

		if ( empty( $task_ids ) ) {
			return 0;
		}

		// Convert to integers and create placeholders.
		$task_ids     = array_map( 'intval', $task_ids );
		$placeholders = implode( ',', array_fill( 0, count( $task_ids ), '%d' ) );

		// Delete metadata for these tasks.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE search_filter_task_id IN ({$placeholders})",
				array_merge( array( $meta_table ), $task_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Delete the tasks.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE id IN ({$placeholders})",
				array_merge( array( $tasks_table ), $task_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result;
	}

	/**
	 * Delete orphaned taskmeta entries.
	 *
	 * Removes metadata entries that reference non-existent tasks.
	 * Useful for cleaning up after interrupted operations.
	 *
	 * @since 3.2.0
	 *
	 * @return int Number of orphaned meta entries deleted.
	 */
	public static function delete_orphaned_meta() {
		global $wpdb;

		$tasks_table = Task_Runner::get_table_name( 'tasks' );
		$meta_table  = Task_Runner::get_table_name( 'meta' );

		// Delete meta entries where the task no longer exists.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i
				WHERE search_filter_task_id NOT IN (
					SELECT id FROM %i
				)',
				$meta_table,
				$tasks_table
			)
		);

		return $result;
	}
}
