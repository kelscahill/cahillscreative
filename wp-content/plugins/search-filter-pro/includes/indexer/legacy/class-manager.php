<?php
/**
 * Legacy Index Orchestrator.
 *
 * Manages the lifecycle of the legacy index table:
 * - Registers table with Table_Manager
 * - Used for migration support from older versions
 * - Never creates new legacy tables (no ensure_tables)
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Legacy
 */

namespace Search_Filter_Pro\Indexer\Legacy;

use Search_Filter_Pro\Database\Table_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Index Orchestrator class.
 *
 * Handles registration for the legacy index table (migration support only).
 *
 * @since 3.2.0
 */
class Manager {

	/**
	 * Table key for legacy index.
	 *
	 * @var string
	 */
	const TABLE_KEY = 'index';

	/**
	 * Get the legacy table instance.
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
	 * Get the legacy table name.
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
	 * Register the legacy orchestrator.
	 *
	 * @since 3.2.0
	 */
	public static function register() {
		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );
	}

	/**
	 * Register legacy table with Table_Manager.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		if ( Table_Manager::has( self::TABLE_KEY ) ) {
			return;
		}

		Table_Manager::register(
			self::TABLE_KEY,
			\Search_Filter_Pro\Indexer\Legacy\Database\Index_Table::class
		);
	}

	/**
	 * Check if the legacy index table exists in the database.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Whether the legacy table exists.
	 */
	public static function table_exists() {
		$table = self::get_table();
		return $table ? $table->exists() : false;
	}

	/**
	 * Ensure the legacy table is ready for use.
	 *
	 * Triggers instantiation to set up the $wpdb->search_filter_index
	 * property. Also creates the table if it doesn't exist.
	 *
	 * @since 3.2.0
	 */
	public static function ensure_tables() {
		Table_Manager::use( self::TABLE_KEY );
	}
}
