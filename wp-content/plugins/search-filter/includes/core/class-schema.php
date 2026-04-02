<?php
/**
 * Defines the structure of the plugin data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

use Search_Filter\Database\Table_Manager;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the general Schema of the data used
 * Think post types and taxonomies
 */
class Schema {
	/**
	 * Creates the main structure for the plugin.
	 *
	 * Currently, this is only the tables, but in the future, we might want to
	 * create the options and other data structures.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/schema/register', array( __CLASS__, 'register_tables' ), 1 );
	}

	/**
	 * Fire the schema registration hook.
	 *
	 * Called during WordPress 'init' action at priority 1.
	 * Extensions should use this hook to register additional tables.
	 *
	 * @since 3.2.0
	 *
	 * @example
	 * add_action( 'search-filter/schema/register', function() {
	 *     \Search_Filter\Database\Table_Manager::register(
	 *         'my_custom_table',
	 *         '\My_Extension\Database\Custom_Table'
	 *     );
	 * });
	 */
	public static function register() {
		do_action( 'search-filter/schema/register' );
	}

	/**
	 * Register core tables with Table_Manager.
	 *
	 * We should use the same schema/register hook so we can ensure all
	 * our tables are loaded at the same time - useful for testing and setup.
	 *
	 * @since 3.2.0
	 */
	public static function register_tables() {
		// Logs table needs to be registered asap.
		// The Logs table doesn't have its own orchestrator class so we
		// we need to load it manually here for now.
		if ( ! Table_Manager::has( 'logs' ) ) {
			// Don't run allow multiple registrations - needed for testing.
			Table_Manager::register( 'logs', \Search_Filter\Database\Tables\Logs::class, true );
		}
	}
}
