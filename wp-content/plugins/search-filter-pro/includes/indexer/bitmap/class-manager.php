<?php
/**
 * Bitmap Index Orchestrator.
 *
 * Manages the lifecycle of the bitmap index table:
 * - Registers table with Table_Manager
 * - Installs table when first bitmap field is created
 * - Drops table when last bitmap field is deleted
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Bitmap
 */

namespace Search_Filter_Pro\Indexer\Bitmap;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Indexer\Table_Validator;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bitmap Index Orchestrator class.
 *
 * Handles registration and lifecycle management for the bitmap index table.
 *
 * @since 3.2.0
 */
class Manager {

	/**
	 * Table key for bitmap index.
	 *
	 * @var string
	 */
	const TABLE_KEY = 'bitmap_index';

	/**
	 * Get the bitmap table instance.
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
	 * Get the bitmap table name.
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
	 * Register the bitmap orchestrator.
	 *
	 * @since 3.2.0
	 */
	public static function register() {
		// Register table with Table_Manager.
		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );

		// Hook for validating all tables at once (e.g., after field delete).
		add_action( 'search-filter-pro/indexer/validate_tables', array( __CLASS__, 'validate_tables' ), 11 );
	}

	/**
	 * Initialize the bitmap orchestrator.
	 *
	 * Note: Field lifecycle hooks for table validation are now handled centrally
	 * via Fields\Indexer which fires the appropriate validation actions.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		// Field lifecycle validation is now triggered centrally via hooks.
		// See: Fields\Indexer::field_check_for_indexer_changes() and field_post_destroy().
	}

	/**
	 * Register bitmap tables with Table_Manager.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 * This is more robust than a static flag because it correctly handles
	 * Table_Manager::reset() scenarios (e.g., in tests).
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		if ( Table_Manager::has( self::TABLE_KEY ) ) {
			return;
		}

		Table_Manager::register(
			self::TABLE_KEY,
			\Search_Filter_Pro\Indexer\Bitmap\Database\Index_Table::class
		);
	}

	/**
	 * Ensure the parent map index table is installed.
	 */
	public static function ensure_tables() {
		Table_Manager::use( self::TABLE_KEY );
	}


	/**
	 * Check if the bitmap table should be used.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if bitmap table should exist, false otherwise.
	 */
	public static function should_use() {
		$data    = Table_Validator::get_data();
		$default = $data['has_indexer_queries'] && $data['bitmap_count'] > 0;
		return apply_filters( 'search-filter-pro/indexer/bitmap/should_use', $default );
	}

	/**
	 * Validate bitmap table existence based on current field strategies.
	 *
	 * Creates the table if fields use bitmap strategy, drops it if none do.
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
	 * Uninstall the bitmap table.
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
