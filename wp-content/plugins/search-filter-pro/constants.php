<?php
/**
 *  Commonly used constants
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

if ( ! defined( 'SEARCH_FILTER_PRO_VERSION' ) ) {
	define( 'SEARCH_FILTER_PRO_VERSION', '3.2.3' );
}

if ( ! defined( 'SEARCH_FILTER_PRO_URL' ) ) {
	define( 'SEARCH_FILTER_PRO_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SEARCH_FILTER_PRO_PATH' ) ) {
	define( 'SEARCH_FILTER_PRO_PATH', plugin_dir_path( __FILE__ ) );
}

// Extensions declare their "tested upto" version - bump this every time the
// core plugins make breaking changes to apis extensions rely on - this will
// prevent them from loading.
if ( ! defined( 'SEARCH_FILTER_PRO_EXTENSION_REQUIRES_VERSION' ) ) {
	define( 'SEARCH_FILTER_PRO_EXTENSION_REQUIRES_VERSION', '3.2.0' );
}

// The required version number of the Search & Filter base plugin.
if ( ! defined( 'SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION' ) ) {
	define( 'SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION', '3.2.0' );
}

// The recommended version number of the Search & Filter base plugin.
if ( ! defined( 'SEARCH_FILTER_PRO_RECOMMENDED_BASE_VERSION' ) ) {
	define( 'SEARCH_FILTER_PRO_RECOMMENDED_BASE_VERSION', '3.2.2' );
}

// Define exception codes.
define( 'SEARCH_FILTER_PRO_TASK_RUNNER_LOCK_ERROR', '600' );


// Table Manager exception codes.
define( 'SEARCH_FILTER_PRO_EXCEPTION_TABLE_EXISTS', 4001 );
define( 'SEARCH_FILTER_PRO_EXCEPTION_TABLE_NOT_FOUND', 4002 );
define( 'SEARCH_FILTER_PRO_EXCEPTION_TABLE_CLASS_MISSING', 4003 );
