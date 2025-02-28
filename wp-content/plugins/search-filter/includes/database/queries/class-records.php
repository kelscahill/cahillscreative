<?php

namespace Search_Filter\Database\Queries;

use Exception;
use Search_Filter\Util;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class Records
 *
 * @package Search_Filter\Database\Queries
 */
class Records extends \Search_Filter\Database\Engine\Query {

	/**
	 * Delete items from the database.
	 *
	 * @param array $where The where clauses. Array key = value pairs of comparisons.
	 *
	 * @return bool|int The number of deleted items or false on failure.
	 */
	public function delete_items( $where = array() ) {
		// First lookup the items (so we can clear the meta and caches on success).
		$where['number'] = 0; // Delete all records.
		$where['fields'] = 'ids';
		$delete_items    = $this->query( $where );

		// Try to delete.
		foreach ( $delete_items as $delete_item ) {
			$this->delete_item( $delete_item );
		}

		return count( $delete_items );
	}


	/**
	 * Build the where clause parts SQL.
	 *
	 * @param array  $where The where clauses.
	 * @param string $relation The relation to use.
	 * @return string The where clause parts SQL.
	 */
	private function build_where_clause_parts_sql( $where = array(), $relation = 'AND' ) {

		$valid_conditions = array( '=', '!=', 'IN', 'NOT IN' );

		$where_clause       = '';
		$where_clause_parts = array();

		foreach ( $where as $key => $where_part ) {
			$condition = ! isset( $where_part['condition'] ) ? '=' : $where_part['condition'];
			$value     = $where_part['value'];

			if ( ! in_array( $condition, $valid_conditions, true ) ) {
				Util::error_log( 'Invalid condition in query: ' . $condition, 'error' );
				return false;
			}

			$where_clause_parts[] = "{$key} {$condition} {$value}";
		}

		// Ensure we only use AND or OR exactly.
		if ( $relation === 'AND' || $relation === 'OR' ) {
			$where_clause .= implode( " {$relation} ", $where_clause_parts );
		}

		return $where_clause;
	}
	/**
	 * Delete items, directly looking up required IDs using a regular
	 * query.
	 *
	 * Improves over delete_items when collecting item IDs for deletion,
	 * and doesn't cause "long queries" to be killed, like on WP Engine.
	 *
	 * @param array $where The where clauses.
	 * @return int The number of deleted items.
	 */
	public function delete_items_raw( $where = array() ) {

		$where_clause     = '';
		$add_where_clause = '';
		$join_clause      = '';
		$table_name       = $this->get_table_name();

		$where_columns = array();

		// Handle meta query.
		$meta_query = array();
		if ( isset( $where['meta_query'] ) ) {

			// Remove the meta_query from the where array.
			$meta_query = $where['meta_query'];
			unset( $where['meta_query'] );

			// Join the meta table.
			$item_id_column  = $this->apply_prefix( "{$this->item_name}_id" );
			$meta_table_name = $this->get_meta_table_name();
			$join_clause     = $this->get_db()->prepare(
				'LEFT JOIN %i ON %i.`id` = %i.%i',
				$meta_table_name,
				$table_name,
				$meta_table_name,
				$item_id_column
			);

			$where_sub_clauses = array();
			// Loop through the meta query parts and add them to the where clause using the meta table name.
			foreach ( $meta_query as $meta_query_part ) {
				// These need to be grouped together rather than flat in the where clause so we can
				// support multiple meta queries.
				$add_where = array(
					$this->get_db()->prepare( '%i.%i', $meta_table_name, 'meta_key' )   => array(
						'value'     => $this->get_db()->prepare( '%s', $meta_query_part['key'] ),
						'condition' => '=',
					),
					$this->get_db()->prepare( '%i.%i', $meta_table_name, 'meta_value' ) => array(
						'value'     => $this->get_db()->prepare( '%s', $meta_query_part['value'] ),
						'condition' => '=',
					),
				);

				$where_sub_clauses[] = '( ' . $this->build_where_clause_parts_sql( $add_where ) . ' )';
			}

			$add_where_clause .= '(' . implode( ' OR ', $where_sub_clauses ) . ')';
		}

		foreach ( $where as $where_key => $where_value ) {
			// Check for any $where_key that end in `__not_in`.
			if ( substr( $where_key, -8 ) === '__not_in' ) {
				$key = rtrim( $where_key, '__not_in' );
				if ( empty( $where_value ) ) {
					continue;
				}

				if ( ! is_array( $where_value ) ) {
					$key = array( $where_value );
				}

				$values = array_map(
					function ( $val ) {
						return $this->get_db()->prepare( '%d', $val );
					},
					$where_value
				);

				$where_columns[ $this->get_db()->prepare( '%i.%i', $table_name, $key ) ] = array(
					'value'     => '(' . implode( ',', $values ) . ')',
					'condition' => 'NOT IN',
				);
			} else {
				$where_columns[ $this->get_db()->prepare( '%i.%i', $table_name, $where_key ) ] = array(
					'value'     => $this->get_db()->prepare( '%s', $where_value ),
					'condition' => '=',
				);
			}
		}

		$where_clause_parts_sql = $this->build_where_clause_parts_sql( $where_columns );
		if ( ! empty( $where_clause_parts_sql ) ) {
			$where_clause .= ' WHERE ' . $where_clause_parts_sql;
		}

		if ( ! empty( $add_where_clause ) ) {
			if ( empty( $where_clause_parts_sql ) ) {
				$where_clause .= ' WHERE ' . $add_where_clause;
			} else {
				$where_clause .= ' AND ' . $add_where_clause;
			}
		}

		$results_select_sql = $this->get_db()->prepare(
			"SELECT id FROM %i $join_clause $where_clause",
			$table_name
		);

		// First lookup the items (so we can clear the meta and caches on success).
		$results = $this->get_db()->get_results(
			$results_select_sql
		);

		// Delete the items.
		$delete_result = $this->get_db()->query(
			$this->get_db()->prepare(
				"DELETE FROM %i WHERE %i.`id` IN (SELECT * FROM( $results_select_sql )tempDeleteTable)",
				$table_name,
				$table_name
			)
		);

		// Now delete associated meta from the meta table.
		if ( $results ) {
			foreach ( $results as $result ) {
				$this->delete_all_item_meta( $result->id );
			}
		}

		return count( $results );
	}

	/**
	 * Delete items with a specific status.
	 *
	 * @param string $status The status to delete.
	 *
	 * @return bool|int The number of deleted items or false on failure.
	 */
	public function delete_items_with_status( $status ) {
		$where = array( 'status' => $status );
		return $this->delete_items( $where );
	}

	/**
	 * Add an item to the database, throw exceptions on error instead of
	 * leaving it to wpdb to display & handle.
	 *
	 * @param mixed $item
	 * @return bool
	 * @throws \Exception
	 */
	public function add_item_with_exceptions( $item ) {

		// Get the current setting for suppress errors & show errors.
		$suppress_errors = $this->get_db()->suppress_errors();
		$show_errors     = $this->get_db()->show_errors();

		// Enable suppress errors & disable show errors.
		$this->get_db()->suppress_errors( true );
		$this->get_db()->show_errors( false );

		$result = $this->add_item( $item );

		if ( ! empty( $this->get_db()->last_error ) ) {
			throw new \Exception( $this->get_db()->last_error );
		}

		// Restore suppress errors & show errors.
		$this->get_db()->suppress_errors( $suppress_errors );
		$this->get_db()->show_errors( $show_errors );

		return $result;
	}

	/**
	 * Add multiple items to the database.
	 *
	 * @param array $items The items to add.
	 * @return bool|int The number of inserted items or false on failure.
	 */
	public function add_items( $items ) {
		$table      = $this->get_table_name();
		$save_items = array();

		// Most of what's in this loop is copied from the `add_item` method.
		foreach ( $items as $data ) {
			// Get the primary column name.
			$primary = $this->get_primary_column_name();

			// If data includes primary column, check if item already exists.
			if ( ! empty( $data[ $primary ] ) ) {

				// Shape the primary item ID.
				$item_id = $this->shape_item_id( $data[ $primary ] );

				// Get item by ID (from database, not cache).
				$item = $this->get_item_raw( $primary, $item_id );

				// Bail if item already exists.
				if ( ! empty( $item ) ) {
					return false;
				}

				// Set data primary ID to newly shaped ID.
				$data[ $primary ] = $item_id;
			}

			// Get default values for item (from columns).
			$item = $this->default_item();

			// Unset the primary key if not part of data array (auto-incremented).
			if ( empty( $data[ $primary ] ) ) {
				unset( $item[ $primary ] );
			}

			// Cut out non-keys for meta.
			$columns = $this->get_column_names();
			$data    = array_merge( $item, $data );
			$meta    = array_diff_key( $data, $columns );
			$save    = array_intersect_key( $data, $columns );

			// Bail if nothing to save.
			if ( empty( $save ) && empty( $meta ) ) {
				return false;
			}

			// Get the current time (maybe used by created/modified).
			$time = $this->get_current_time();

			// If date-created exists, but is empty or default, use the current time.
			$created = $this->get_column_by( array( 'created' => true ) );
			if ( ! empty( $created ) && ( empty( $save[ $created->name ] ) || ( $save[ $created->name ] === $created->default ) ) ) {
				$save[ $created->name ] = $time;
			}

			// If date-modified exists, but is empty or default, use the current time.
			$modified = $this->get_column_by( array( 'modified' => true ) );
			if ( ! empty( $modified ) && ( empty( $save[ $modified->name ] ) || ( $save[ $modified->name ] === $modified->default ) ) ) {
				$save[ $modified->name ] = $time;
			}

			// Try to add.
			$table  = $this->get_table_name();
			$reduce = $this->reduce_item( 'insert', $save );
			$save   = $this->validate_item( $reduce );

			$save_items[] = $save;
		}

		$result = false;
		if ( ! empty( $save_items ) ) {
			// $this->get_db()->insert() calls `$wpdb->__insert_replace_helper
			// So lets replace it with our own method for handling multiple inserts.
			$result = ! empty( $save_items )
				? $this->multiple_insert_replace_helper( $table, $save_items )
				: false;

			// Bail on failure.
			if ( ! $this->is_success( $result ) ) {
				return false;
			}
		}

		return $result;
	}

	/**
	 * Helper function for multiple inserts and replaces.
	 *
	 * @param string $table  The table name.
	 * @param array  $data_items   The data items to insert.
	 * @param string $format The format to use.
	 * @param string $type   The type of insert.
	 *
	 * @return bool|int The number of inserted items or false on failure.
	 */
	private function multiple_insert_replace_helper( $table, $data_items, $format = null, $type = 'INSERT' ) {

		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ), true ) ) {
			return false;
		}

		$rows = array();
		foreach ( $data_items as $data ) {
			$data = $this->process_fields( $table, $data, $format );
			// Process fields is changing the data to show the format and value...
			// we need to replicate.
			if ( false === $data ) {
				return false;
			}
			$formats = array();
			$values  = array();
			foreach ( $data as $value ) {
				if ( is_null( $value['value'] ) ) {
					$formats[] = 'NULL';
					continue;
				}

				$formats[] = $value['format'];
				$values[]  = $value['value'];
			}

			$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
			$formats = implode( ', ', $formats );
			$sql     = "($formats)";
			$rows[]  = $this->get_db()->prepare( $sql, $values );
		}
		if ( empty( $rows ) ) {
			return false;
		}

		$rows_sql = "$type INTO `$table` ($fields) VALUES " . implode( ',', $rows ) . ';';
		return $this->get_db()->query( $rows_sql );
	}

	/**
	 * Processes arrays of field/value pairs and field formats.
	 *
	 * This is a helper method for wpdb's CRUD methods, which take field/value pairs
	 * for inserts, updates, and where clauses. This method first pairs each value
	 * with a format.
	 *
	 * * Note: This is all taken directly from $wpdb...
	 *
	 * @since 4.2.0
	 *
	 * @param string          $table  Table name.
	 * @param array           $data   Array of values keyed by their field names.
	 * @param string[]|string $format Formats or format to be mapped to the values in the data.
	 * @return array|false An array of fields that contain paired value and formats.
	 *                     False for invalid values.
	 */
	protected function process_fields( $table, $data, $format ) {
		// This has been greatly simplified.
		$data = $this->process_field_formats( $data, $format );
		if ( false === $data ) {
			return false;
		}
		$data = $this->process_field_lengths( $data, $table );
		if ( false === $data ) {
			return false;
		}
		return $data;
	}


	/**
	 * For string fields, records the maximum string length that field can safely save.
	 *
	 * @since 4.2.1
	 *
	 * @param array  $data {
	 *      Array of values, formats, and charsets keyed by their field names,
	 *      as it comes from the wpdb::process_field_charsets() method.
	 *
	 *     @type array ...$0 {
	 *         Value, format, and charset for this field.
	 *
	 *         @type mixed        $value   The value to be formatted.
	 *         @type string       $format  The format to be mapped to the value.
	 *         @type string|false $charset The charset to be used for the value.
	 *     }
	 * }
	 * @param string $table Table name.
	 * @return array|false {
	 *     The same array of data with additional 'length' keys, or false if
	 *     information for the table cannot be found.
	 *
	 *     @type array ...$0 {
	 *         Value, format, charset, and length for this field.
	 *
	 *         @type mixed        $value   The value to be formatted.
	 *         @type string       $format  The format to be mapped to the value.
	 *         @type string|false $charset The charset to be used for the value.
	 *         @type array|false  $length  {
	 *             Information about the maximum length of the value.
	 *             False if the column has no length.
	 *
	 *             @type string $type   One of 'byte' or 'char'.
	 *             @type int    $length The column length.
	 *         }
	 *     }
	 * }
	 */
	protected function process_field_lengths( $data, $table ) {
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * We can skip this field if we know it isn't a string.
				 * This checks %d/%f versus ! %s because its sprintf() could take more.
				 */
				$value['length'] = false;
			} else {
				$value['length'] = $this->get_db()->get_col_length( $table, $field );
				if ( is_wp_error( $value['length'] ) ) {
					return false;
				}
			}

			$data[ $field ] = $value;
		}

		return $data;
	}

	/**
	 * Prepares arrays of value/format pairs as passed to wpdb CRUD methods.
	 *
	 * @since 4.2.0
	 *
	 * @param array           $data   Array of values keyed by their field names.
	 * @param string[]|string $format Formats or format to be mapped to the values in the data.
	 * @return array {
	 *     Array of values and formats keyed by their field names.
	 *
	 *     @type mixed  $value  The value to be formatted.
	 *     @type string $format The format to be mapped to the value.
	 * }
	 */
	protected function process_field_formats( $data, $format ) {
		$formats          = (array) $format;
		$original_formats = $formats;

		foreach ( $data as $field => $value ) {
			$value = array(
				'value'  => $value,
				'format' => '%s',
			);

			if ( ! empty( $format ) ) {
				$value['format'] = array_shift( $formats );
				if ( ! $value['format'] ) {
					$value['format'] = reset( $original_formats );
				}
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$value['format'] = $this->field_types[ $field ];
			}

			$data[ $field ] = $value;
		}

		return $data;
	}
}
