<?php
/**
 * Base Custom Database Table Class.
 *
 * @package     Database
 * @subpackage  Table
 * @copyright   Copyright (c) 2020
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

namespace Search_Filter_Pro\Database\Engine;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * A base database table class, which facilitates the creation of (and schema
 * changes to) individual database tables.
 *
 * This class is intended to be extended for each unique database table,
 * including global tables for multisite, and users tables.
 *
 * It exists to make managing database tables as easy as possible.
 *
 * Extending this class comes with several automatic benefits:
 * - Activation hook makes it great for plugins
 * - Tables store their versions in the database independently
 * - Tables upgrade via independent upgrade abstract methods
 * - Multisite friendly - site tables switch on "switch_blog" action
 *
 * @since 1.0.0
 */
abstract class Table extends Base {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $name = '';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $description = '';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = '';

	/**
	 * Is this table for a site, or global.
	 *
	 * @since 1.0.0
	 * @var   bool
	 */
	protected $global = false;

	/**
	 * Current database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $db_version = 0;

	/**
	 * Table prefix, including the site prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $table_prefix = '';

	/**
	 * Table name.
	 *
	 * @since 1.0.0
	 * @var  string
	 */
	protected $table_name = '';

	/**
	 * Table name, prefixed from the base.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $prefixed_name = '';

	/**
	 * Table schema.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $schema = '';

	/**
	 * Database character-set & collation for table.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $charset_collation = '';

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array();

	/**
	 * Cached result of exists() check.
	 *
	 * Three states:
	 * - null: Unknown, need to query database
	 * - true: Table exists
	 * - false: Table does not exist
	 *
	 * @since 3.2.0
	 * @var bool|null
	 */
	protected $exists_cached = null;

	/** Static Registry Properties ********************************************/

	/**
	 * Option key for the consolidated table version registry.
	 *
	 * @since 3.2.0
	 */
	const OPTION_NAME = 'search_filter_pro_table_registry';

	/**
	 * In-memory cache for site-level registry (one get_option per request).
	 *
	 * @since 3.2.0
	 * @var array|null
	 */
	private static $registry_cache = null;

	/**
	 * In-memory cache for network-level registry.
	 *
	 * @since 3.2.0
	 * @var array|null
	 */
	private static $network_registry_cache = null;

	/** Methods ***************************************************************/

	/**
	 * Hook into queries, admin screens, and more!
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Setup the database table.
		$this->setup();

		// Bail if setup failed.
		if ( empty( $this->name ) ) {
			return;
		}

		// Add the table to the database interface.
		$this->set_db_interface();

		// Set the database schema.
		$this->set_schema();

		// Add hooks.
		$this->add_hooks();

		// NOTE: Table installation/upgrade is handled by Table_Manager::use().
		// Constructor no longer calls maybe_upgrade() - this ensures the following:
		// 1. No side effects during object construction.
		// 2. Clean separation of concerns (construction != installation).
		// 3. Proper lazy-loading of tables (only installed when first used).
		// 4. Correct behavior in tests (temporary tables work properly).
	}

	/** Abstract **************************************************************/

	/**
	 * Setup this database table.
	 *
	 * @since 1.0.0
	 */
	abstract protected function set_schema();

	/** Multisite *************************************************************/

	/**
	 * Update table version & references.
	 *
	 * Hooked to the "switch_blog" action.
	 *
	 * @since 1.0.0
	 *
	 * @param int $site_id The site being switched to.
	 */
	public function switch_blog( $site_id = 0 ) {

		// For site-level tables, clear cache and reload version for new site.
		if ( ! $this->is_global() ) {
			// Clear registry cache so it's re-read for the new site.
			self::clear_registry_cache();
			$this->db_version = self::get_registry_version( $this->name, false );
		}

		// Update interface for switched site.
		$this->set_db_interface();

		// Clear exists cache - new site may not have this table.
		$this->exists_cached = null;
	}

	/** Public Helpers ********************************************************/

	/**
	 * Maybe upgrade the database table. Handles creation & schema changes.
	 *
	 * Hooked to the `admin_init` action.
	 *
	 * @since 1.0.0
	 */
	public function maybe_upgrade() {

		// Bail if not upgradeable.
		if ( ! $this->is_upgradeable() ) {
			return;
		}

		// Bail if upgrade not needed.
		if ( ! $this->needs_upgrade() ) {
			return;
		}

		// Upgrade.
		if ( $this->exists() ) {
			$this->upgrade();

			// Install.
		} else {
			$this->install();
		}
	}

	/**
	 * Initialize this table for use.
	 *
	 * Called by Table_Manager::use() to ensure the table is ready.
	 * Handles all lifecycle scenarios:
	 * - Fresh install when table doesn't exist
	 * - Upgrades when registry version < class version
	 * - Legacy table detection (exists but no registry entry)
	 * - No-op when versions match (fast path)
	 *
	 * @since 3.2.0
	 */
	public function init() {
		// Bail if not upgradeable (respects wp_should_upgrade_global_tables).
		if ( ! $this->is_upgradeable() ) {
			return;
		}

		$registry_version = self::get_registry_version( $this->name, $this->is_global() );
		$class_version    = $this->version;

		// SCENARIO 1: No registry entry - determine fresh install vs legacy.
		if ( false === $registry_version ) {
			$this->handle_no_registry_entry();
			return;
		}

		// SCENARIO 2: Registry version < class version - upgrade needed.
		if ( version_compare( $registry_version, $class_version, '<' ) ) {
			$this->handle_version_mismatch();
			return;
		}

		// SCENARIO 3: Versions match - nothing to do (fast path).
	}

	/**
	 * Handle the case where table has no registry entry.
	 *
	 * Determines if this is a fresh install or a legacy table that
	 * exists but wasn't tracked in the registry.
	 *
	 * @since 3.2.0
	 */
	private function handle_no_registry_entry() {
		if ( $this->exists() ) {
			// Table exists but no registry - check if upgrade needed.
			if ( $this->needs_upgrade() ) {
				$this->upgrade();
			} else {
				// Table is current - record in registry.
				$this->set_db_version();
			}
		} else {
			// Fresh install - table doesn't exist.
			$this->install();
		}
	}

	/**
	 * Handle the case where registry version is older than class version.
	 *
	 * @since 3.2.0
	 */
	private function handle_version_mismatch() {
		if ( $this->exists() ) {
			$this->upgrade();
		} else {
			// Registry has version but table missing - reinstall.
			$this->install();
		}
	}

	/**
	 * Return whether this table needs an upgrade.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $version Database version to check if upgrade is needed.
	 *
	 * @return bool True if table needs upgrading. False if not.
	 */
	public function needs_upgrade( $version = false ) {

		// Use the current table version if none was passed.
		if ( empty( $version ) ) {
			$version = $this->version;
		}

		// Get the current database version.
		$this->get_db_version();

		// Is the database table up to date?
		$is_current = version_compare( $this->db_version, $version, '>=' );

		// Return false if current, true if out of date.
		return ( true === $is_current )
			? false
			: true;
	}

	/**
	 * Return whether this table can be upgraded.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if table can be upgraded. False if not.
	 */
	public function is_upgradeable() {

		// Bail if global and upgrading global tables is not allowed.
		if ( $this->is_global() && ! wp_should_upgrade_global_tables() ) {
			return false;
		}

		// Kinda weird, but assume it is.
		return true;
	}

	/**
	 * Return the current table version from the database.
	 *
	 * This is public method for accessing a private variable so that it cannot
	 * be externally modified.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_version() {
		$this->get_db_version();

		return $this->db_version;
	}

	/**
	 * Get the full prefixed table name.
	 *
	 * This is a public method for accessing a protected variable so that it
	 * cannot be externally modified.
	 *
	 * @since 3.2.0
	 *
	 * @return string The full prefixed table name.
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Get the class-defined table version.
	 *
	 * Returns the version constant defined in the table class, not the
	 * database-stored version. Used for registry population.
	 *
	 * @since 3.2.0
	 *
	 * @return string The class-defined version string.
	 */
	public function get_class_version() {
		return $this->version;
	}

	/**
	 * Install a database table
	 *
	 * Creates the table and sets the version information if successful.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		// Try to create the table.
		$created = $this->create();

		// Set the DB version if create was successful.
		if ( true === $created ) {
			$this->set_db_version();
			// Mark as existing in cache.
			$this->exists_cached = true;
		}
	}

	/**
	 * Uninstall a database table
	 *
	 * Drops the table and deletes the version information if successful and/or
	 * the table does not exist anymore.
	 *
	 * @since 1.0.0
	 */
	public function uninstall() {
		// Try to drop the table.
		$dropped = $this->drop();

		// Delete the DB version if drop was successful or table does not exist.
		if ( ( true === $dropped ) || ! $this->exists() ) {
			$this->delete_db_version();
		}
	}

	/** Public Management *****************************************************/

	/**
	 * Check if table already exists.
	 *
	 * Uses instance-level caching to avoid redundant SHOW TABLES queries.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function exists() {
		// Return cached result if we already know the state.
		if ( null !== $this->exists_cached ) {
			return $this->exists_cached;
		}

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		$query    = 'SHOW TABLES LIKE %s';
		$like     = $db->esc_like( $this->table_name );
		$prepared = $db->prepare( $query, $like );
		$result   = $db->get_var( $prepared );

		// Does the table exist?
		$exists = $this->is_success( $result );

		// Cache the result (both true and false).
		$this->exists_cached = $exists;

		return $exists;
	}

	/**
	 * Reset the exists cache.
	 *
	 * Used primarily by test suite to ensure clean state between tests.
	 *
	 * @since 3.2.0
	 */
	public function reset_exists_cache() {
		$this->exists_cached = null;
	}

	/**
	 * Check if this instance has verified whether the table exists.
	 *
	 * Returns true if exists() has been called at least once on this instance,
	 * meaning exists_cached is not null. Used by Table_Manager to determine
	 * if registry data should be trusted or verified.
	 *
	 * @since 3.2.0
	 *
	 * @return bool True if existence has been verified (cached as true or false).
	 */
	public function has_verified_existence() {
		return null !== $this->exists_cached;
	}

	/**
	 * Get columns from table.
	 *
	 * @since 1.2.0
	 *
	 * @return array|false
	 */
	public function columns() {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->get_results( $db->prepare( 'SHOW FULL COLUMNS FROM %i', $this->table_name ) );

		// Return the results.
		return $this->is_success( $result )
			? $result
			: false;
	}

	/**
	 * Create the table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function create() {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		// Note: Schema and charset_collation are internal class properties, not user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $db->query(
			$db->prepare(
				"CREATE TABLE %i ( {$this->schema} ) {$this->charset_collation}",
				$this->table_name
			)
		);

		// Was the table created?
		return $this->is_success( $result );
	}

	/**
	 * Drop the database table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function drop() {
		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		// Use DROP TABLE IF EXISTS to avoid errors when table doesn't exist.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $db->query( $db->prepare( 'DROP TABLE IF EXISTS %i', $this->table_name ) );

		// Did the table get dropped?
		$dropped = $this->is_success( $result );

		// Mark as not existing in cache and remove from registry if dropped successfully.
		if ( $dropped ) {
			$this->exists_cached = false;
			// Also remove from registry so init() knows to reinstall if needed.
			self::remove_registry_version( $this->name, $this->is_global() );
		}

		return $dropped;
	}

	/**
	 * Truncate the database table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function truncate() {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->query( $db->prepare( 'TRUNCATE TABLE %i', $this->table_name ) );

		// Did the table get truncated?
		return $this->is_success( $result );
	}

	/**
	 * Delete all items from the database table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete_all() {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->query( $db->prepare( 'DELETE FROM %i', $this->table_name ) );

		// Return the results.
		return $result;
	}

	/**
	 * Copy the contents of this table to a new table.
	 *
	 * Pair with clone().
	 *
	 * @since 1.1.0
	 *
	 * @param string $new_table_name The name of the new table, without prefix.
	 *
	 * @return bool
	 */
	public function copy( $new_table_name = '' ) {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Sanitize the new table name.
		$table_name = $this->sanitize_table_name( $new_table_name );

		// Bail if new table name is invalid.
		if ( empty( $table_name ) ) {
			return false;
		}

		// Query statement.
		$table = $this->apply_prefix( $table_name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->query( $db->prepare( 'INSERT INTO %i SELECT * FROM %i', $table, $this->table_name ) );

		// Did the table get copied?
		return $this->is_success( $result );
	}

	/**
	 * Count the number of items in the database table.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function count() {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return 0;
		}

		// Query statement.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->get_var( $db->prepare( 'SELECT COUNT(*) FROM %i', $this->table_name ) );

		// Query success/fail.
		return intval( $result );
	}

	/**
	 * Check if column already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Column name to check.
	 *
	 * @return bool
	 */
	public function column_exists( $name = '' ) {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Query statement.
		$like = $db->esc_like( $name );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->query( $db->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $this->table_name, $like ) );

		// Does the column exist?
		return $this->is_success( $result );
	}

	/**
	 * Check if index already exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name   Index name to check.
	 * @param string $column Column name to search in.
	 *
	 * @return bool
	 */
	public function index_exists( $name = '', $column = 'Key_name' ) {

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return false;
		}

		// Limit $column to Key or Column name, until we can do better.
		if ( ! in_array( $column, array( 'Key_name', 'Column_name' ), true ) ) {
			$column = 'Key_name';
		}

		// Query statement.
		$like = $db->esc_like( $name );
		// Note: $column is sanitized above to only allow 'Key_name' or 'Column_name'.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $db->query( $db->prepare( "SHOW INDEXES FROM %i WHERE {$column} LIKE %s", $this->table_name, $like ) );

		// Does the index exist?
		return $this->is_success( $result );
	}

	/** Upgrades **************************************************************/

	/**
	 * Upgrade this database table.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function upgrade() {

		// Get pending upgrades.
		$upgrades = $this->get_pending_upgrades();

		// Bail if no upgrades.
		if ( empty( $upgrades ) ) {
			$this->set_db_version();

			// Return, without failure.
			return true;
		}

		// Default result.
		$result = false;

		// Try to do the upgrades.
		foreach ( $upgrades as $version => $callback ) {

			// Do the upgrade.
			$result = $this->upgrade_to( $version, $callback );

			// Bail if an error occurs, to avoid skipping upgrades.
			if ( ! $this->is_success( $result ) ) {
				return false;
			}
		}

		// Success/fail.
		return $this->is_success( $result );
	}

	/**
	 * Return array of upgrades that still need to run.
	 *
	 * @since 1.1.0
	 *
	 * @return array Array of upgrade callbacks, keyed by their db version.
	 */
	public function get_pending_upgrades() {

		// Initialize the database version.
		$this->get_db_version();

		// Default return value.
		$upgrades = array();

		// Bail if no upgrades, or no database version to compare to.
		if ( empty( $this->upgrades ) || empty( $this->db_version ) ) {
			return $upgrades;
		}

		// Loop through all upgrades, and pick out the ones that need doing.
		foreach ( $this->upgrades as $version => $callback ) {
			if ( true === version_compare( $version, $this->db_version, '>' ) ) {
				$upgrades[ $version ] = $callback;
			}
		}

		// Return.
		return $upgrades;
	}

	/**
	 * Upgrade to a specific database version.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $version  Database version to check if upgrade is needed.
	 * @param string $callback Callback function or class method to call.
	 *
	 * @return bool
	 */
	public function upgrade_to( $version = '', $callback = '' ) {

		// Bail if no upgrade is needed.
		if ( ! $this->needs_upgrade( $version ) ) {
			return false;
		}

		// Allow self-named upgrade callbacks.
		if ( empty( $callback ) ) {
			$callback = $version;
		}

		// Is the callback... callable?
		$callable = $this->get_callable( $callback );

		// Bail if no callable upgrade was found.
		if ( empty( $callable ) ) {
			return false;
		}

		// Do the upgrade.
		$result  = call_user_func( $callable );
		$success = $this->is_success( $result );

		// Bail if upgrade failed.
		if ( true !== $success ) {
			return false;
		}

		// Set the database version to this successful version.
		$this->set_db_version( $version );

		// Return success.
		return true;
	}

	/** Private ***************************************************************/

	/**
	 * Setup the necessary table variables.
	 *
	 * @since 1.0.0
	 */
	private function setup() {

		// Bail if no database interface is available.
		if ( empty( $this->get_db() ) ) {
			return;
		}

		// Sanitize the database table name.
		$this->name = $this->sanitize_table_name( $this->name );

		// Bail if database table name was garbage.
		if ( empty( $this->name ) ) {
			return;
		}

		// Separator.
		$glue = '_';

		// Setup the prefixed name.
		$this->prefixed_name = $this->apply_prefix( $this->name, $glue );
	}

	/**
	 * Set this table up in the database interface.
	 *
	 * This must be done directly because the database interface does not
	 * have a common mechanism for manipulating them safely.
	 *
	 * @since 1.0.0
	 */
	private function set_db_interface() {

		// Get the database once, to avoid duplicate function calls.
		$db = $this->get_db();

		// Bail if no database.
		if ( empty( $db ) ) {
			return;
		}

		// Set variables for global tables.
		if ( $this->is_global() ) {
			$site_id = 0;
			$tables  = 'ms_global_tables';

			// Set variables for per-site tables.
		} else {
			$site_id = null;
			$tables  = 'tables';
		}

		// Set the table prefix and prefix the table name.
		$this->table_prefix = $db->get_blog_prefix( $site_id );

		// Get the prefixed table name.
		$prefixed_table_name = "{$this->table_prefix}{$this->prefixed_name}";

		// Set the database interface.
		$this->table_name           = $prefixed_table_name;
		$db->{$this->prefixed_name} = $this->table_name;

		// Create the array if it does not exist.
		if ( ! isset( $db->{$tables} ) ) {
			$db->{$tables} = array();
		}

		// Add the table to the global table array.
		$db->{$tables}[] = $this->prefixed_name;

		// Charset.
		if ( ! empty( $db->charset ) ) {
			$this->charset_collation = "DEFAULT CHARACTER SET {$db->charset}";
		}

		// Collation.
		if ( ! empty( $db->collate ) ) {
			$this->charset_collation .= " COLLATE {$db->collate}";
		}
	}

	/**
	 * Set the database version for the table.
	 *
	 * Uses the consolidated Table_Manager registry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $version Database version to set when upgrading/creating.
	 */
	private function set_db_version( $version = '' ) {

		// If no version is passed during an upgrade, use the current version.
		if ( empty( $version ) ) {
			$version = $this->version;
		}

		// Update the registry.
		self::set_registry_version( $this->name, $version, $this->is_global() );

		// Set the DB version property.
		$this->db_version = $version;
	}

	/**
	 * Get the table version from the database.
	 *
	 * @since 1.0.0
	 */
	private function get_db_version() {
		$this->db_version = self::get_registry_version( $this->name, $this->is_global() );
	}

	/**
	 * Delete the table version from the database.
	 *
	 * @since 1.0.0
	 */
	private function delete_db_version() {
		self::remove_registry_version( $this->name, $this->is_global() );
		$this->db_version = false;
	}

	/** Static Registry Methods ***********************************************/

	/**
	 * Get a table's version from the registry.
	 *
	 * @since 3.2.0
	 *
	 * @param string $table_name Table name (without prefix).
	 * @param bool   $is_global  Whether this is a global/network table.
	 * @return string|false Version string or false if not found.
	 */
	public static function get_registry_version( $table_name, $is_global = false ) {
		$registry = self::load_registry( $is_global );
		return $registry[ $table_name ] ?? false;
	}

	/**
	 * Set a table's version in the registry.
	 *
	 * @since 3.2.0
	 *
	 * @param string $table_name Table name (without prefix).
	 * @param string $version    Version string.
	 * @param bool   $is_global  Whether this is a global/network table.
	 */
	public static function set_registry_version( $table_name, $version, $is_global = false ) {
		$registry                = self::load_registry( $is_global );
		$registry[ $table_name ] = $version;
		self::save_registry( $registry, $is_global );
	}

	/**
	 * Remove a table from the registry.
	 *
	 * @since 3.2.0
	 *
	 * @param string $table_name Table name (without prefix).
	 * @param bool   $is_global  Whether this is a global/network table.
	 */
	public static function remove_registry_version( $table_name, $is_global = false ) {
		$registry = self::load_registry( $is_global );
		unset( $registry[ $table_name ] );
		self::save_registry( $registry, $is_global );
	}

	/**
	 * Load registry from database (cached).
	 *
	 * Uses in-memory caching to ensure only one get_option call per request.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $is_global Whether to load network-level registry.
	 * @return array The registry data.
	 */
	private static function load_registry( $is_global = false ) {
		if ( $is_global && is_multisite() ) {
			if ( null === self::$network_registry_cache ) {
				$value                        = get_network_option( get_main_network_id(), self::OPTION_NAME, '' );
				self::$network_registry_cache = is_string( $value ) && '' !== $value
					? json_decode( $value, true )
					: array();
			}
			return self::$network_registry_cache;
		}

		if ( null === self::$registry_cache ) {
			$value                = get_option( self::OPTION_NAME, '' );
			self::$registry_cache = is_string( $value ) && '' !== $value
				? json_decode( $value, true )
				: array();
		}
		return self::$registry_cache;
	}

	/**
	 * Save registry to database and update cache.
	 *
	 * @since 3.2.0
	 *
	 * @param array $registry  The registry data to save.
	 * @param bool  $is_global Whether to save to network-level registry.
	 */
	private static function save_registry( $registry, $is_global = false ) {
		$json = wp_json_encode( $registry );

		if ( $is_global && is_multisite() ) {
			self::$network_registry_cache = $registry;
			update_network_option( get_main_network_id(), self::OPTION_NAME, $json );
		} else {
			self::$registry_cache = $registry;
			update_option( self::OPTION_NAME, $json, true );
		}
	}

	/**
	 * Clear registry caches.
	 *
	 * Used for testing and when switching blogs in multisite.
	 *
	 * @since 3.2.0
	 */
	public static function clear_registry_cache() {
		self::$registry_cache         = null;
		self::$network_registry_cache = null;
	}

	/**
	 * Add class hooks to the parent application actions.
	 *
	 * @since 1.0.0
	 */
	private function add_hooks() {
		// Handle multisite blog switching (updates table prefix).
		add_action( 'switch_blog', array( $this, 'switch_blog' ) );

		// NOTE: admin_init hook removed - upgrades are now handled by
		// Table_Manager::use() which does lightweight version comparison.
		// This eliminates redundant global polling on every admin page load.
	}

	/**
	 * Check if table is global.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_global() {
		return ( true === $this->global );
	}

	/**
	 * Try to get a callable upgrade, with some magic to avoid needing to
	 * do this dance repeatedly inside subclasses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $callback Callback name to check.
	 *
	 * @return mixed Callable string, or false if not callable.
	 */
	private function get_callable( $callback = '' ) {

		// Default return value.
		$callable = $callback;

		// Look for global function.
		if ( ! is_callable( $callable ) ) {

			// Fallback to local class method.
			$callable = array( $this, $callback );
			if ( ! is_callable( $callable ) ) {

				// Fallback to class method prefixed with "__".
				$callable = array( $this, "__{$callback}" );
				if ( ! is_callable( $callable ) ) {
					$callable = false;
				}
			}
		}

		// Return callable string, or false if not callable.
		return $callable;
	}
}
