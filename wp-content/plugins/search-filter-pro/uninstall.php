<?php
/**
 *  Uninstall functions, remove S&F data if the option is set
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */


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
		search_filter_pro_uninstall();
		restore_current_blog();
	}
} else {
	search_filter_pro_uninstall();
}


/**
 * Uninstall the plugin.
 *
 * @since    3.0.0
 */
function search_filter_pro_uninstall() {
	global $wpdb;

	// We can't load most of our plugin here because we inherit a lot of
	// dependencies from the free version, and its possible that is not
	// enabled or installed at point of uninstall...

	// Look for the option name `features` in the `search_filter_options` table.

	// Check if the table search_filter_options exists first.
	$remove_all_data = false;
	// If the table doesn't exist, we can assume that free version has been removed before the pro.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}search_filter_options'" ) ) {
		$options_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}search_filter_options WHERE name = %s", 'features' ) );
		if ( count( $options_result ) === 0 ) {
			// The setting was probably not init, so lets use the default of not removing all data.
			$remove_all_data = false;
		} else {
			$features        = json_decode( $options_result[0]->value, true );
			$remove_all_data = isset( $features['removeDataOnUninstall'] ) ? $features['removeDataOnUninstall'] : false;
		}
	}

	// Don't proceed if remove all data is not enabled.
	if ( ! $remove_all_data ) {
		return;
	}

	$tables_to_remove = array(
		'index',
		'index_cache',
		'tasks',
		'taskmeta',
	);

	foreach ( $tables_to_remove as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}search_filter_{$table}" );
		// Delete version info in the options table.
		delete_option( "search_filter_pro_{$table}_table_version", );
	}

	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
