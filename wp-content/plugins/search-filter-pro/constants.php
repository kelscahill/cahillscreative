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
	define( 'SEARCH_FILTER_PRO_VERSION', '3.0.6' );
}

if ( ! defined( 'SEARCH_FILTER_PRO_URL' ) ) {
	define( 'SEARCH_FILTER_PRO_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SEARCH_FILTER_PRO_PATH' ) ) {
	define( 'SEARCH_FILTER_PRO_PATH', plugin_dir_path( __FILE__ ) );
}

// The required version number of the Search & Filter base plugin.
if ( ! defined( 'SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION' ) ) {
	define( 'SEARCH_FILTER_PRO_REQUIRED_BASE_VERSION', '3.0.6' );
}

// Define exception codes.
define( 'SEARCH_FILTER_PRO_TASK_RUNNER_LOCK_ERROR', '600' );


// Include the environment constants.
$env_path = plugin_dir_path( __FILE__ ) . 'env.php';
if ( file_exists( $env_path ) ) {
	require_once $env_path;
}
