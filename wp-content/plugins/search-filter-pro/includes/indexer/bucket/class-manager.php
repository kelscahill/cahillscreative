<?php
/**
 * Bucket Index Orchestrator.
 *
 * Manages the lifecycle of the bucket index tables:
 * - Registers tables with Table_Manager (index, metadata, overflow)
 * - Installs tables when first bucket field is created
 * - Drops tables when last bucket field is deleted
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Bucket
 */

namespace Search_Filter_Pro\Indexer\Bucket;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Indexer\Table_Validator;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bucket Index Orchestrator class.
 *
 * Handles registration and lifecycle management for bucket index tables.
 *
 * @since 3.2.0
 */
class Manager {

	/**
	 * Table keys for bucket index tables.
	 *
	 * @var array
	 */
	const TABLE_KEYS = array(
		'bucket_index',
		'bucket_metadata',
		'bucket_overflow',
	);

	/**
	 * Map of short names to full table keys.
	 *
	 * @var array<string, string>
	 */
	const TABLE_KEY_MAP = array(
		'index'    => 'bucket_index',
		'metadata' => 'bucket_metadata',
		'overflow' => 'bucket_overflow',
	);

	/**
	 * Get a bucket table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'index', 'metadata', or 'overflow'. Default 'index'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter_Pro\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $type = 'index', $should_use = true ) {
		$key = self::TABLE_KEY_MAP[ $type ] ?? 'bucket_index';
		return Table_Manager::get( $key, $should_use );
	}

	/**
	 * Get a bucket table name.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type       Table type: 'index', 'metadata', or 'overflow'. Default 'index'.
	 * @param bool   $should_use Whether to ensure table exists. Default true for transparent lazy loading.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $type = 'index', $should_use = true ) {
		$table = self::get_table( $type, $should_use );
		return $table ? $table->get_table_name() : '';
	}

	/**
	 * Register the bucket orchestrator.
	 *
	 * @since 3.2.0
	 */
	public static function register() {
		// Register tables with Table_Manager.
		add_action( 'search-filter-pro/schema/register', array( __CLASS__, 'register_tables' ) );

		// Hook for validating all tables at once (e.g., after field delete).
		add_action( 'search-filter-pro/indexer/validate_tables', array( __CLASS__, 'validate_tables' ) );
	}
	/**
	 * Initialize the bucket orchestrator.
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
	 * Register bucket tables with Table_Manager.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 * This is more robust than a static flag because it correctly handles
	 * Table_Manager::reset() scenarios (e.g., in tests).
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		// Guard: Skip if tables are already registered.
		if ( Table_Manager::has( 'bucket_index' ) ) {
			return;
		}

		Table_Manager::register(
			'bucket_index',
			\Search_Filter_Pro\Indexer\Bucket\Database\Index_Table::class
		);
		Table_Manager::register(
			'bucket_metadata',
			\Search_Filter_Pro\Indexer\Bucket\Database\Metadata_Table::class
		);
		Table_Manager::register(
			'bucket_overflow',
			\Search_Filter_Pro\Indexer\Bucket\Database\Overflow_Table::class
		);
	}

	/**
	 * Ensure the parent map index table is installed.
	 */
	public static function ensure_tables() {
		// Ensure all bucket tables are installed.
		foreach ( self::TABLE_KEYS as $key ) {
			Table_Manager::use( $key );
		}
	}

	/**
	 * Check if the bucket tables should be used.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if bucket tables should exist, false otherwise.
	 */
	public static function should_use() {
		$data    = Table_Validator::get_data();
		$default = $data['has_indexer_queries'] && $data['bucket_count'] > 0;
		return apply_filters( 'search-filter-pro/indexer/bucket/should_use', $default );
	}

	/**
	 * Validate bucket tables existence based on current field strategies.
	 *
	 * Creates the tables if fields use bucket strategy, drops them if none do.
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
	 * Uninstall all bucket tables.
	 *
	 * @since 3.2.0
	 */
	public static function uninstall_tables() {
		foreach ( self::TABLE_KEYS as $key ) {
			$table = Table_Manager::get( $key );  // Don't install during uninstall!
			if ( $table && $table->exists() ) {
				$table->uninstall();
			}
		}
	}
}
