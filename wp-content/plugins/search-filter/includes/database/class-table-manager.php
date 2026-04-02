<?php
/**
 * Table Manager - Manages database table registration and lazy instantiation.
 *
 * Provides centralized control over table lifecycle with lazy initialization
 * and a hook-based registration system for extensibility.
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Database
 */

namespace Search_Filter\Database;

use Search_Filter\Core\Exception;
use Search_Filter\Database\Engine\Table;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Table Manager class.
 *
 * Handles registration and lifecycle of database tables.
 * Tables are registered by class name and instantiated lazily on first use.
 *
 * @since 3.2.0
 */
class Table_Manager {

	/**
	 * Registered table class names.
	 *
	 * @since 3.2.0
	 * @var array<string, class-string<Table>>
	 */
	private static $registry = array();

	/**
	 * Cached table instances.
	 *
	 * @since 3.2.0
	 * @var array<string, Table>
	 */
	private static $instances = array();

	/**
	 * Initialize the table manager.
	 *
	 * Safe to call multiple times - subsequent calls are no-ops.
	 * Note: Multisite switch_blog handling is delegated to individual Table instances.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		// No-op. Individual tables handle their own switch_blog hooks.
	}

	/**
	 * Register a table class.
	 *
	 * Stores the class name without instantiation. The table will be
	 * instantiated lazily when first accessed via get() or use().
	 *
	 * @since 3.2.0
	 *
	 * @param string $key        Unique identifier for the table.
	 * @param string $class_name Fully qualified class name extending Table.
	 * @param bool   $should_use   Whether to immediately instantiate and ensure the table exists.
	 *
	 * @throws Exception If the key is already registered.
	 * @throws Exception If the class does not exist.
	 */
	public static function register( $key, $class_name, $should_use = false ) {
		// Guard: Prevent duplicate registration.
		if ( isset( self::$registry[ $key ] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the table key */
					esc_html__( 'Table "%s" is already registered. Use update() to replace.', 'search-filter' ),
					esc_html( $key )
				),
				(int) SEARCH_FILTER_EXCEPTION_TABLE_EXISTS
			);
		}

		// Guard: Class must exist.
		if ( ! class_exists( $class_name ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the class name */
					esc_html__( 'Table class "%s" does not exist.', 'search-filter' ),
					esc_html( $class_name )
				),
				(int) SEARCH_FILTER_EXCEPTION_TABLE_CLASS_MISSING
			);
		}

		self::$registry[ $key ] = $class_name;

		// Instantiate and use the table if requested.
		if ( $should_use ) {
			self::use( $key );
		}
	}

	/**
	 * Update an existing table registration.
	 *
	 * Allows replacing an already registered table class.
	 * Clears any cached instance for the key.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key        The table key to update.
	 * @param string $class_name New fully qualified class name.
	 *
	 * @throws Exception If the key is not registered.
	 * @throws Exception If the class does not exist.
	 */
	public static function update( $key, $class_name ) {
		// Guard: Key must exist.
		if ( ! isset( self::$registry[ $key ] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the table key */
					esc_html__( 'Cannot update table "%s" - it is not registered. Use register() first.', 'search-filter' ),
					esc_html( $key )
				),
				(int) SEARCH_FILTER_EXCEPTION_TABLE_NOT_FOUND
			);
		}

		// Guard: Class must exist.
		if ( ! class_exists( $class_name ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the class name */
					esc_html__( 'Table class "%s" does not exist.', 'search-filter' ),
					esc_html( $class_name )
				),
				(int) SEARCH_FILTER_EXCEPTION_TABLE_CLASS_MISSING
			);
		}

		// Clear cached instance if exists.
		unset( self::$instances[ $key ] );

		self::$registry[ $key ] = $class_name;
	}

	/**
	 * Check if a table is registered.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key The table key to check.
	 * @return bool True if registered, false otherwise.
	 */
	public static function has( $key ) {
		return isset( self::$registry[ $key ] );
	}

	/**
	 * Ensure a table exists, is installed, and is up-to-date.
	 *
	 * Instantiates the table if not already cached, and delegates
	 * lifecycle management to the table's init() method.
	 *
	 * @since 3.2.0
	 *
	 * @param string $key The table key.
	 *
	 * @throws Exception If the table key is not registered.
	 */
	public static function use( $key ) {

		// Guard: Key must be registered.
		if ( ! isset( self::$registry[ $key ] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s is the table key */
					esc_html__( 'Table "%s" is not registered.', 'search-filter' ),
					esc_html( $key )
				),
				(int) SEARCH_FILTER_EXCEPTION_TABLE_NOT_FOUND
			);
		}

		// Instantiate if not cached.
		if ( ! isset( self::$instances[ $key ] ) ) {
			$class_name              = self::$registry[ $key ];
			self::$instances[ $key ] = new $class_name();
		}

		// Delegate lifecycle management to the table instance.
		self::$instances[ $key ]->init();
	}

	/**
	 * Get a table instance.
	 *
	 * Returns the cached table instance, instantiating it if necessary.
	 * Optionally ensures the table exists via use().
	 *
	 * @since 3.2.0
	 *
	 * @param string $key        The table key.
	 * @param bool   $should_use Whether to ensure table exists (install/upgrade). Default true.
	 * @return Table|null The table instance, or null if not registered.
	 */
	public static function get( $key, $should_use = false ) {

		// Return null if not registered (silent failure).
		if ( ! isset( self::$registry[ $key ] ) ) {
			return null;
		}

		// Instantiate if not cached.
		if ( ! isset( self::$instances[ $key ] ) ) {
			$class_name              = self::$registry[ $key ];
			self::$instances[ $key ] = new $class_name();
		}

		// Optionally ensure table exists (install/upgrade).
		if ( $should_use ) {
			try {
				self::use( $key );
			} catch ( Exception $e ) {
				return null;
			}
		}

		return self::$instances[ $key ];
	}

	/**
	 * Get all registered table keys.
	 *
	 * Returns only the keys without instantiating any tables.
	 * Use this to iterate and selectively use() or get() tables as needed.
	 *
	 * @since 3.2.0
	 *
	 * @return array Array of registered table keys.
	 */
	public static function get_registered() {

		return array_keys( self::$registry );
	}

	/**
	 * Flush all caches without clearing registrations.
	 *
	 * Clears cached table instances and version registry caches.
	 * Registrations are preserved so tables don't need to be re-registered.
	 *
	 * Use this in test tearDown() to ensure fresh state between tests while
	 * maintaining table registrations. Handles the case where MySQL DDL
	 * statements (e.g., DROP TABLE) persist while DML statements are rolled
	 * back by WordPress transactions.
	 *
	 * @since 3.2.0
	 */
	public static function flush() {
		// Clear cached instances (stale existence flags).
		self::$instances = array();

		// Clear Table's registry cache.
		Table::clear_registry_cache();
	}

	/**
	 * Reset the manager to uninitialized state.
	 *
	 * Clears everything: registrations, instances, and caches.
	 * Use this for complete teardown in tests.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$registry = array();

		// Flush all caches (instances + version registry).
		self::flush();
	}
}
