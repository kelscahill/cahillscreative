<?php
/**
 * Centralized table validation management.
 *
 * Manages the full lifecycle of table validation:
 * - Cached validation data (field/query counts)
 * - Flagging when tables need revalidation
 * - Running validation checks
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Indexer;

use Search_Filter\Options;
use Search_Filter\Database\Table_Manager;
use Search_Filter\Fields;
use Search_Filter\Queries;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table Validator.
 *
 * Centralizes table validation logic:
 * - get_data(): Cached validation data for managers
 * - needs_revalidating(): Flag tables for validation
 * - maybe_validate(): Run validation if flagged
 * - reset(): Clear all state (for testing)
 *
 * @since 3.2.0
 */
class Table_Validator {

	/**
	 * Cached validation data.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Get validation data (lazy-loaded).
	 *
	 * Returns cached data if available, otherwise computes it.
	 *
	 * @since 3.2.0
	 *
	 * @return array {
	 *     @type bool $has_indexer_queries Whether any query uses the indexer.
	 *     @type int  $bitmap_count        Count of fields using bitmap strategy.
	 *     @type int  $bucket_count        Count of fields using bucket strategy.
	 *     @type int  $search_count        Count of fields using search strategy.
	 * }
	 */
	public static function get_data() {
		if ( self::$cache === null ) {
			self::$cache = self::compute();
		}
		return self::$cache;
	}

	/**
	 * Invalidate the cache.
	 *
	 * Call this when fields/queries change to ensure
	 * fresh data on next get_data().
	 *
	 * @since 3.2.0
	 */
	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Flag that tables need revalidation.
	 *
	 * Flushes cache and sets the validation flag.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $needs Whether validation is needed (default true).
	 */
	public static function needs_revalidating( bool $needs = true ): void {
		self::flush();
		Options::update( 'indexer-tables-need-validation', $needs ? 'yes' : 'no' );
	}

	/**
	 * Run validation if flagged.
	 *
	 * Checks the validation flag, and if set, runs validation
	 * and clears the flag.
	 *
	 * @since 3.2.0
	 */
	public static function maybe_validate(): void {
		if ( Options::get( 'indexer-tables-need-validation', 'no' ) !== 'yes' ) {
			return;
		}

		self::validate();
		Options::update( 'indexer-tables-need-validation', 'no' );
	}

	/**
	 * Run table validation immediately.
	 *
	 * Flushes cache and fires validation actions for all indexer
	 * components. Use this in tests or when immediate validation
	 * is needed without checking the flag.
	 *
	 * @since 3.2.0
	 */
	public static function validate(): void {

		// Invalidate cache so managers get fresh data.
		self::flush();

		// Run validation for each indexer component.
		do_action( 'search-filter-pro/indexer/validate_tables' );
	}

	/**
	 * Reset all state (for testing).
	 *
	 * Clears cache and resets the validation flag.
	 *
	 * @since 3.2.0
	 */
	public static function reset(): void {
		self::flush();
		Options::update( 'indexer-tables-need-validation', 'no' );
	}

	/**
	 * Compute validation data using efficient meta queries.
	 *
	 * Uses COUNT queries on meta tables instead of loading all records.
	 * Early-returns with zeros if no queries use indexer.
	 *
	 * @since 3.2.0
	 *
	 * @return array Validation data array.
	 */
	private static function compute() {
		global $wpdb;

		$queries_table   = Queries::get_table( 'queries', true );
		$querymeta_table = Queries::get_table( 'meta', true );

		$fields_table    = Fields::get_table( 'fields', true );
		$fieldmeta_table = Fields::get_table( 'meta', true );

		if ( ! $queries_table || ! $querymeta_table || ! $fields_table || ! $fieldmeta_table ) {
			return self::get_empty_result();
		}

		// Check if any non-trashed query uses indexer (single COUNT query).
		// Include both 'enabled' and 'disabled' - only exclude 'trashed'.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_indexer_queries = (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i m
				 INNER JOIN %i q ON m.search_filter_query_id = q.id
				 WHERE m.meta_key = %s
				 AND m.meta_value = %s
				 AND q.status IN (%s, %s)',
				$querymeta_table->get_table_name(),
				$queries_table->get_table_name(),
				'use_indexer',
				'yes',
				'enabled',
				'disabled'
			)
		);

		// Early out - no indexer = all zeros.
		if ( ! $has_indexer_queries ) {
			return self::get_empty_result();
		}

		// Count fields per interaction type (single query with GROUP BY).
		// Include both 'enabled' and 'disabled' - only exclude 'trashed'.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT m.meta_value as interaction_type, COUNT(*) as count
				 FROM %i m
				 INNER JOIN %i f ON m.search_filter_field_id = f.id
				 WHERE m.meta_key = %s
				 AND f.status IN (%s, %s)
				 GROUP BY m.meta_value',
				$fieldmeta_table->get_table_name(),
				$fields_table->get_table_name(),
				'interaction_type',
				'enabled',
				'disabled'
			),
			OBJECT_K
		);

		// Build strategy counts dynamically from registered strategies.
		$strategy_counts = array();
		foreach ( array_keys( Index_Strategy_Factory::get_strategies() ) as $type ) {
			$strategy_counts[ $type ] = 0;
		}

		// Map interaction types to strategy counts.
		foreach ( $counts as $interaction_type => $row ) {
			$strategy = Index_Strategy_Factory::get_by_interaction_type( $interaction_type );
			if ( $strategy ) {
				$type = $strategy->get_type();
				if ( isset( $strategy_counts[ $type ] ) ) {
					$strategy_counts[ $type ] += (int) $row->count;
				}
			}
		}

		// Build return array with {type}_count keys.
		$result = array( 'has_indexer_queries' => true );
		foreach ( $strategy_counts as $type => $count ) {
			$result[ $type . '_count' ] = $count;
		}

		return $result;
	}

	/**
	 * Get an empty result array with all strategy counts set to zero.
	 *
	 * Builds the array dynamically from registered strategies.
	 *
	 * @since 3.2.0
	 *
	 * @return array Empty result with has_indexer_queries=false and all counts=0.
	 */
	private static function get_empty_result(): array {
		$result = array( 'has_indexer_queries' => false );

		foreach ( array_keys( Index_Strategy_Factory::get_strategies() ) as $type ) {
			$result[ $type . '_count' ] = 0;
		}

		return $result;
	}

	/**
	 * Check if any non-trashed query uses the indexer.
	 *
	 * Includes both enabled and disabled queries, excludes trashed.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if any non-trashed query uses the indexer, false otherwise.
	 */
	public static function has_indexer_queries(): bool {
		$data = self::get_data();
		return $data['has_indexer_queries'];
	}
}
