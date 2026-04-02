<?php
/**
 * Defines the structure of the plugin data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the general Schema of the data used
 */
class Schema {

	/**
	 * Initialize schema setup.
	 *
	 * Currently a placeholder for any pre-registration schema setup.
	 * Pro tables are registered via the 'search-filter-pro/schema/register' hook.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public static function init(): void {
		// Placeholder for any pre-registration schema setup.
		// Actual table registration happens via Schema::register() hook.
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
	 * add_action( 'search-filter-pro/schema/register', function() {
	 *     \Search_Filter_Pro\Database\Table_Manager::register(
	 *         'my_custom_table',
	 *         '\My_Extension\Database\Custom_Table'
	 *     );
	 * });
	 */
	public static function register() {
		do_action( 'search-filter-pro/schema/register' );
	}
}
