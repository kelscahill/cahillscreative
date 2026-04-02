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
use Search_Filter\Database\Table_Manager;
use Search_Filter\Database\Engine\Table;

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {
	// Get all sites in the network and run uninstall on each one.
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0, // No limit.
		)
	);
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
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

	Features::init();

	if ( Features::is_enabled( 'removeDataOnUninstall' ) ) {

		// Register all tables so we can uninstall them.
		// Fields tables.
		Table_Manager::register( 'fields', \Search_Filter\Database\Tables\Fields::class );
		Table_Manager::register( 'fieldmeta', \Search_Filter\Database\Tables\Fields_Meta::class );

		// Queries tables.
		Table_Manager::register( 'queries', \Search_Filter\Database\Tables\Queries::class );
		Table_Manager::register( 'querymeta', \Search_Filter\Database\Tables\Queries_Meta::class );

		// Styles tables.
		Table_Manager::register( 'styles', \Search_Filter\Database\Tables\Style_Presets::class );
		Table_Manager::register( 'stylemeta', \Search_Filter\Database\Tables\Styles_Meta::class );

		// Options table.
		Table_Manager::register( 'options', \Search_Filter\Database\Tables\Options::class );

		// Logs table.
		Table_Manager::register( 'logs', \Search_Filter\Database\Tables\Logs::class );

		// Uninstall all registered tables.
		foreach ( Table_Manager::get_registered() as $key ) {
			$table = Table_Manager::get( $key );
			if ( $table && $table->exists() ) {
				$table->uninstall();
			}
		}

		// Delete the consolidated table version registry.
		delete_option( Table::OPTION_NAME );

		// Delete network registry for multisite.
		if ( is_multisite() ) {
			delete_network_option( get_main_network_id(), Table::OPTION_NAME );
		}

		delete_option( 'search_filter_default_styles' );
		delete_option( 'search-filter-version' );

		// TODO - purge transients & cache.
	}

	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
