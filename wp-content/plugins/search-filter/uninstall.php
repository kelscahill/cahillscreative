<?php
/**
 *  Uninstall functions, remove S&F data if the option is set
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

use Search_Filter\Features;

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( is_multisite() ) {
	// Get all blogs in the network and deactivate plugin on each one.
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	foreach ( $blog_ids as $next_blog_id ) {
		switch_to_blog( $next_blog_id );
		search_filter_uninstall();
		restore_current_blog();
	}
} else {
	search_filter_uninstall();
}

/**
 * Uninstall the plugin.
 *
 * @since    3.0.0
 */
function search_filter_uninstall() {

	$schema = new Search_Filter\Core\Schema();
	$schema->init();

	Features::init();

	if ( Features::is_enabled( 'removeDataOnUninstall' ) ) {

		$tables = $schema->get_tables();

		foreach ( $tables as $table ) {
			if ( $table->exists() ) {
				$table->uninstall();
			}
		}

		delete_option( 'search_filter_default_styles' );
		delete_option( 'search-filter-version' );

		// TODO - purge transients & cache.
	}

	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
