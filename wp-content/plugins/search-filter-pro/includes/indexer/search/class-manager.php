<?php
/**
 * Search Index Orchestrator.
 *
 * Manages the lifecycle of the search index tables:
 * - Registers tables with Table_Manager (terms, postings, doc_stats)
 * - Installs tables when first search field is created
 * - Drops tables when last search field is deleted
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer/Search
 */

namespace Search_Filter_Pro\Indexer\Search;

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Indexer\Table_Validator;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search Index Orchestrator class.
 *
 * Handles registration and lifecycle management for search index tables.
 *
 * @since 3.2.0
 */
class Manager {

	/**
	 * Table keys for search index tables.
	 *
	 * @var array
	 */
	const TABLE_KEYS = array(
		'search_terms',
		'search_postings',
		'search_doc_stats',
	);

	/**
	 * Map of short names to full table keys.
	 *
	 * @var array<string, string>
	 */
	const TABLE_KEY_MAP = array(
		'terms'     => 'search_terms',
		'postings'  => 'search_postings',
		'doc_stats' => 'search_doc_stats',
	);

	/**
	 * Get a search table instance.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'terms', 'postings', or 'doc_stats'. Default 'terms'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return \Search_Filter_Pro\Database\Engine\Table|null The table instance, or null if not registered.
	 */
	public static function get_table( $type = 'terms', $should_use = true ) {
		$key = self::TABLE_KEY_MAP[ $type ] ?? 'search_terms';
		return Table_Manager::get( $key, $should_use );
	}

	/**
	 * Get a search table name.
	 *
	 * @since 3.2.0
	 *
	 * @param string $type Table type: 'terms', 'postings', or 'doc_stats'. Default 'terms'.
	 * @param bool   $should_use Whether the table should be used based on settings.
	 * @return string The prefixed table name, or empty string if table not registered.
	 */
	public static function get_table_name( $type = 'terms', $should_use = true ) {
		$table = self::get_table( $type, $should_use );
		return $table ? $table->get_table_name() : '';
	}

	/**
	 * Register the search orchestrator.
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
	 * Initialize the search orchestrator.
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
	 * Register search tables with Table_Manager.
	 *
	 * Uses Table_Manager::has() check to guard against duplicate registration.
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		// Guard: Skip if tables are already registered.
		if ( Table_Manager::has( 'search_terms' ) ) {
			return;
		}

		Table_Manager::register(
			'search_terms',
			\Search_Filter_Pro\Indexer\Search\Database\Terms_Table::class
		);
		Table_Manager::register(
			'search_postings',
			\Search_Filter_Pro\Indexer\Search\Database\Postings_Table::class
		);
		Table_Manager::register(
			'search_doc_stats',
			\Search_Filter_Pro\Indexer\Search\Database\Doc_Stats_Table::class
		);
	}



	/**
	 * Check if the search tables should be used.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if search tables should exist, false otherwise.
	 */
	public static function should_use() {
		$data    = Table_Validator::get_data();
		$default = $data['has_indexer_queries'] && $data['search_count'] > 0;
		return apply_filters( 'search-filter-pro/indexer/search/should_use', $default );
	}

	/**
	 * Validate search tables existence based on current field strategies.
	 *
	 * Creates the tables if fields use search strategy, drops them if none do.
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
	 * Uninstall all search tables.
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

	/**
	 * Ensure the search tables are installed.
	 */
	public static function ensure_tables() {
		// Ensure all search tables are installed.
		foreach ( self::TABLE_KEYS as $key ) {
			Table_Manager::use( $key );
		}
	}
}
