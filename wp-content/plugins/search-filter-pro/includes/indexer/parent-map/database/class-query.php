<?php
/**
 * Parent Map Query Class
 *
 * Direct database queries for parent mapping table.
 * Follows Index_Query_Direct pattern.
 *
 * @since 3.2.0
 *
 * @package Search_Filter_Pro\Indexer\Parent_Map\Database
 */

namespace Search_Filter_Pro\Indexer\Parent_Map\Database;

use Search_Filter_Pro\Indexer\Parent_Map\Manager;
use Search_Filter_Pro\Indexer\Parent_Map\Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parent Map Query Class
 *
 * @since 3.2.0
 */
class Query {

	/**
	 * Store or update mapping
	 *
	 * @param int    $child_id  Child/variation ID.
	 * @param int    $parent_id Parent ID.
	 * @param string $source    Data source identifier.
	 * @return bool Success
	 */
	public static function store_mapping( $child_id, $parent_id, $source = '' ) {
		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (child_id, parent_id, source)
				 VALUES (%d, %d, %s)
				 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), last_updated = NOW()',
				$table_name,
				$child_id,
				$parent_id,
				$source
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Reset cache for this source to ensure fresh data.
		if ( false !== $result ) {
			Converter::reset( array( $source ) );
		}

		return false !== $result;
	}

	/**
	 * Store multiple mappings in a single batch query
	 *
	 * Efficient batch insert for index rebuilds - 200 mappings per call.
	 * Supports per-mapping sources when 'source' key is present in mapping array.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $mappings       Array of mappings with 'child_id', 'parent_id', and optional 'source' keys.
	 * @param string $default_source Default source if not specified per-mapping.
	 * @return bool Success
	 */
	public static function store_mappings_batch( $mappings, $default_source = '' ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		if ( empty( $mappings ) ) {
			return true;
		}

		// Build multi-row INSERT statement.
		$values       = array( $table_name ); // First placeholder is table name.
		$placeholders = array();

		foreach ( $mappings as $mapping ) {
			// Use per-mapping source if provided, otherwise fall back to default.
			$source         = isset( $mapping['source'] ) ? $mapping['source'] : $default_source;
			$values[]       = (int) $mapping['child_id'];
			$values[]       = (int) $mapping['parent_id'];
			$values[]       = $source;
			$placeholders[] = '(%d, %d, %s)';
		}

		$placeholders_str = implode( ', ', $placeholders );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO %i (child_id, parent_id, source)
				 VALUES {$placeholders_str}
				 ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), last_updated = NOW()",
				$values
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $result;
	}

	/**
	 * Remove mapping for a child ID
	 *
	 * With composite primary key (child_id, source), this method can:
	 * - Delete specific mapping when source is provided
	 * - Delete ALL mappings for child_id when source is null (across all sources)
	 *
	 * @param int         $child_id Child ID to remove.
	 * @param string|null $source   Optional source to target specific mapping.
	 * @return bool Success
	 */
	public static function delete_mapping( $child_id, $source = null ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		$where  = array( 'child_id' => $child_id );
		$format = array( '%d' );

		if ( $source !== null ) {
			$where['source'] = $source;
			$format[]        = '%s';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $table_name, $where, $format );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Reset caches for the affected source(s).
		if ( $source !== null ) {
			Converter::reset( array( $source ) );
		} else {
			Converter::reset();
		}

		return false !== $result;
	}

	/**
	 * Get the parent ID for a child from the mapping table.
	 *
	 * Not an efficient way to look up data - used when resyncing posts.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $child_id Child ID to look up.
	 * @param string $source   Data source identifier.
	 * @return int|null Parent ID if mapping exists, null otherwise.
	 */
	public static function get_parent_id( $child_id, $source ) {
		global $wpdb;
		$table_name = Manager::get_table_name();

		if ( empty( $table_name ) ) {
			return null;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$parent_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT parent_id FROM %i WHERE child_id = %d AND source = %s',
				$table_name,
				$child_id,
				$source
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $parent_id !== null ? (int) $parent_id : null;
	}

	/**
	 * Remove all mappings for a specific source
	 *
	 * Useful for source-specific index rebuilds.
	 *
	 * @since 3.2.0
	 *
	 * @param string $source Source identifier to clear.
	 * @return bool Success
	 */
	public static function delete_mappings_by_source( $source ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'source' => $source ),
			array( '%s' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Reset caches for this source.
		Converter::reset( array( $source ) );

		return false !== $result;
	}

	/**
	 * Load all mappings
	 *
	 * Used for cache warming. Returns bidirectional mapping structure.
	 *
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	public static function get_all_mappings() {

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT child_id, parent_id FROM %i',
				$table_name
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $results ) {
			return array(
				'parents'  => array(),
				'children' => array(),
			);
		}

		$parents  = array(); // parent_id => [child_ids].
		$children = array(); // child_id => parent_id.
		foreach ( $results as $row ) {
			$child_id  = (int) $row['child_id'];
			$parent_id = (int) $row['parent_id'];

			$parents[ $parent_id ][] = $child_id;
			$children[ $child_id ]   = $parent_id;
		}

		return array(
			'parents'  => $parents,
			'children' => $children,
		);
	}

	/**
	 * Load all mappings for a specific source
	 *
	 * Used for source-specific cache loading (e.g., 'woocommerce', 'post').
	 * More efficient than loading all mappings when only one source is needed.
	 *
	 * @since 3.2.0
	 *
	 * @param string $source Data source identifier (e.g., 'woocommerce', 'post').
	 * @return array {
	 *     @type array $parents  parent_id => [child_ids] - lookup a parent to get its children.
	 *     @type array $children child_id => parent_id - lookup a child to get its parent.
	 * }
	 */
	public static function get_mappings_by_source( $source ) {

		global $wpdb;
		$table_name = Manager::get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT child_id, parent_id FROM %i WHERE source = %s',
				$table_name,
				$source
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $results ) {
			return array(
				'parents'  => array(),
				'children' => array(),
			);
		}

		$parents  = array(); // parent_id => [child_ids].
		$children = array(); // child_id => parent_id.
		foreach ( $results as $row ) {
			$child_id  = (int) $row['child_id'];
			$parent_id = (int) $row['parent_id'];

			$parents[ $parent_id ][] = $child_id;
			$children[ $child_id ]   = $parent_id;
		}

		return array(
			'parents'  => $parents,
			'children' => $children,
		);
	}

	/**
	 * Get mapping statistics
	 *
	 * @return array Statistics
	 */
	public static function get_statistics() {
		// Get table without auto-creation (pass false).
		$table = Manager::get_table( false );

		// Return empty stats if table doesn't exist.
		if ( ! $table || ! $table->exists() ) {
			return array(
				'total_mappings' => 0,
				'sources'        => array(),
				'table_size_mb'  => 0,
			);
		}

		global $wpdb;
		$table_name = $table->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_mappings = $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name )
		);
		$sources        = $wpdb->get_results(
			$wpdb->prepare( 'SELECT source, COUNT(*) as count FROM %i GROUP BY source', $table_name ),
			ARRAY_A
		);
		$table_size_mb  = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2)
				 FROM information_schema.TABLES
				 WHERE table_name = %s',
				$table_name
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'total_mappings' => $total_mappings,
			'sources'        => $sources,
			'table_size_mb'  => $table_size_mb,
		);
	}

	/**
	 * Clear all mappings
	 *
	 * Used during full index rebuild
	 *
	 * @return bool Success
	 */
	public static function clear_all() {

		return Manager::get_table()->truncate();
	}
}
