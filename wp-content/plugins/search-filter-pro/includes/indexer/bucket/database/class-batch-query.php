<?php
/**
 * Bucket Batch Query - Batch database operations for bucket index.
 *
 * Provides multi-row INSERT/DELETE operations for efficient batch
 * processing of bucket overflow data.
 *
 * @link       http://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Bucket/Database
 */

namespace Search_Filter_Pro\Indexer\Bucket\Database;

use Search_Filter_Pro\Indexer\Bucket\Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bucket Batch Query class.
 *
 * Provides batch operations for the bucket overflow table.
 *
 * @since 3.2.0
 */
class Batch_Query {

	/**
	 * Batch delete overflow entries for multiple posts.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs to delete overflow entries for.
	 * @return bool True on success.
	 */
	public static function batch_delete_overflow( $field_id, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return true;
		}

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// Build placeholders for post IDs.
		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$query_params = array_merge( array( $overflow_table, $field_id ), $post_ids );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i
				WHERE field_id = %d AND object_id IN ({$placeholders})",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result !== false;
	}

	/**
	 * Batch insert overflow entries.
	 *
	 * Uses multi-row INSERT for efficient bulk insertion.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $entries  Array of entries, each with keys:
	 *                        - object_id: Post ID
	 *                        - value: Numeric value.
	 * @return bool True on success.
	 */
	public static function batch_insert_overflow( $field_id, $entries ) {
		if ( empty( $entries ) ) {
			return true;
		}

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );
		$metadata_table = Manager::get_table_name( 'metadata' );
		$current_time   = current_time( 'mysql' );

		// Get bucket metadata for overflow type determination.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$metadata = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT min_value, max_value FROM %i WHERE field_id = %d',
				$metadata_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Build multi-row INSERT query.
		$values_sql = array();
		$values     = array();

		foreach ( $entries as $entry ) {
			$value         = (float) $entry['value'];
			$overflow_type = 'PENDING';

			// Determine overflow type based on metadata.
			if ( $metadata ) {
				if ( $value < $metadata->min_value ) {
					$overflow_type = 'BELOW_MIN';
				} elseif ( $value > $metadata->max_value ) {
					$overflow_type = 'ABOVE_MAX';
				}
			}

			$values_sql[] = '(%d, %d, %f, %s, %s)';
			$values[]     = $field_id;
			$values[]     = $entry['object_id'];
			$values[]     = $value;
			$values[]     = $overflow_type;
			$values[]     = $current_time;
		}

		$values_clause = implode( ', ', $values_sql );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i
				(field_id, object_id, value, overflow_type, created_at)
				VALUES {$values_clause}",
				array_merge( array( $overflow_table ), $values )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		do_action( 'search-filter-pro/indexer/bucket/rebuild', $field_id );

		return $result !== false;
	}

	/**
	 * Batch get overflow entries for multiple posts.
	 *
	 * @since 3.2.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $post_ids Array of post IDs.
	 * @return array Array of overflow entries grouped by post_id.
	 */
	public static function batch_get_overflow( $field_id, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return array();
		}

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
		$query_params = array_merge( array( $overflow_table, $field_id ), $post_ids );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT object_id, value, overflow_type
				FROM %i
				WHERE field_id = %d AND object_id IN ({$placeholders})",
				$query_params
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Group by object_id.
		$result = array();
		foreach ( $rows as $row ) {
			$object_id = (int) $row->object_id;
			if ( ! isset( $result[ $object_id ] ) ) {
				$result[ $object_id ] = array();
			}
			$result[ $object_id ][] = array(
				'value'         => (float) $row->value,
				'overflow_type' => $row->overflow_type,
			);
		}

		return $result;
	}

	/**
	 * Get count of overflow entries for a field.
	 *
	 * @since 3.2.0
	 *
	 * @param int $field_id Field ID.
	 * @return int Number of overflow entries.
	 */
	public static function get_overflow_count( $field_id ) {

		global $wpdb;
		$overflow_table = Manager::get_table_name( 'overflow' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE field_id = %d',
				$overflow_table,
				$field_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $count;
	}
}
