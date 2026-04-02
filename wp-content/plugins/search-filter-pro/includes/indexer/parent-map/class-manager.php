<?php
/**
 * Parent Map Converter - Optimized for 400k-1M child→parent mappings
 *
 * Converts child IDs to parent IDs with three-tier caching.
 * Works with any hierarchical data: product variations, hierarchical posts, etc.
 *
 * Performance targets:
 * - <1ms for 20,000 ID conversions (cached)
 * - <2ms for 50,000 ID conversions (cached)
 * - Memory: 6-25MB for 400k-1M mappings
 *
 * @package Search_Filter_Pro\Indexer\Parent_Map
 * @since 3.2.0
 */

namespace Search_Filter_Pro\Indexer\Parent_Map;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Indexer\Table_Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parent Map Index Class.
 *
 * @since 3.2.0
 */
class Manager {

	/**
	 * Table key for parent map index.
	 *
	 * @var string
	 */
	const TABLE_KEY = 'parent_map_index';

	/**
	 * Get the parent map table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter_Pro\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $should_use = true ) {
		return Table_Manager::get( self::TABLE_KEY, $should_use );
	}

	/**
	 * Get the parent map table name.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $should_use Whether the table should be used based on settings.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $should_use = true ) {
		$table = self::get_table( $should_use );
		return $table ? $table->get_table_name() : '';
	}

	/**
	 * Initialize the Parent Map Index.
	 *
	 * @since 3.2.0
	 */
	public static function register() {
		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );

		// Hook for direct validation trigger.
		add_action( 'search-filter-pro/indexer/validate_tables', array( __CLASS__, 'validate_tables' ) );
	}

	/**
	 * Register indexer tables.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 * This is more robust than a static flag because it correctly handles
	 * Table_Manager::reset() scenarios (e.g., in tests).
	 *
	 * @since    3.2.0
	 */
	public static function register_tables() {
		if ( Table_Manager::has( 'parent_map_index' ) ) {
			return;
		}

		Table_Manager::register( 'parent_map_index', \Search_Filter_Pro\Indexer\Parent_Map\Database\Table::class );
	}

	/**
	 * Ensure the parent map index table is installed.
	 */
	public static function ensure_tables() {
		Table_Manager::use( 'parent_map_index' );
	}

	/**
	 * Check if the parent map table can be used.
	 *
	 * Checks there are queries that require the indexer.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if parent can be used, false otherwise.
	 */
	public static function can_use() {
		$data = Table_Validator::get_data();
		return $data['has_indexer_queries'];
	}
	/**
	 * Check if the parent map table should be used.
	 *
	 * Uses the 'search-filter-pro/indexer/parent_map/should_use' filter to determine
	 * if any integration (e.g., WooCommerce) requires the parent map table.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if parent map should be used, false otherwise.
	 */
	public static function should_use() {
		// We should pass `self::can_use()` rather than false, but for now this feature
		// in only opt-in via the filter.
		return apply_filters( 'search-filter-pro/indexer/parent_map/should_use', false );
	}

	/**
	 * Get post types that support parent mapping.
	 *
	 * Allows integrations (e.g., WooCommerce) to add their post types.
	 * Default is empty - only enabled via filter.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of post type slugs that support parent mapping.
	 */
	public static function get_parent_map_post_types() {
		return apply_filters( 'search-filter-pro/indexer/parent_map/post_types', array() );
	}

	/**
	 * Conditionally ensure the parent map table is installed.
	 *
	 * Only creates the table if an integration has indicated it needs parent mapping
	 * via the 'search-filter-pro/indexer/parent_map/should_use' filter.
	 *
	 * @since 3.2.0
	 */
	public static function validate_tables() {
		if ( self::should_use() ) {
			self::ensure_tables();
		} else {
			self::uninstall_tables();
		}
	}

	/**
	 * Uninstall the parent map table.
	 *
	 * Drops the table if no integration requires parent mapping (filter returns false).
	 * Called when integrations are disabled to clean up unused tables.
	 *
	 * @since 3.2.0
	 */
	public static function uninstall_tables() {
		$table = Table_Manager::get( self::TABLE_KEY );  // Don't install during uninstall!
		if ( $table && $table->exists() ) {
			$table->uninstall();
		}
	}
}
