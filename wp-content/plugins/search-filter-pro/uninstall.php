<?php
/**
 *  Uninstall functions, remove S&F Pro data if the option is set
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

use Search_Filter_Pro\Database\Table_Manager;
use Search_Filter_Pro\Database\Engine\Table;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

global $wpdb;
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
 *
 * @return array {
 *     Result of uninstall operation.
 *
 *     @type bool  $remove_all_data Whether removal was enabled.
 *     @type array $tables_dropped  List of table names that were dropped.
 *     @type array $options_deleted List of option names that were deleted.
 * }
 */
function search_filter_pro_uninstall() {
	global $wpdb;

	$result = array(
		'remove_all_data' => false,
		'tables_dropped'  => array(),
		'options_deleted' => array(),
	);

	// Check if we should remove all data.
	// We need to check the free plugin's options table since that's where features are stored.
	$remove_all_data = false;

	// If the options table exists, check the removeDataOnUninstall feature.
	if ( $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'search_filter_options' )
	) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$options_result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE name = %s', $wpdb->prefix . 'search_filter_options', 'features' ) );
		if ( count( $options_result ) > 0 ) {
			$features        = json_decode( $options_result[0]->value, true );
			$remove_all_data = isset( $features['removeDataOnUninstall'] ) ? $features['removeDataOnUninstall'] : false;
		}
	}

	// Don't proceed if remove all data is not enabled.
	if ( ! $remove_all_data ) {
		return $result;
	}

	$result['remove_all_data'] = true;

	// Register all Pro tables so we can uninstall them.
	// Task runner tables.
	Table_Manager::register( 'tasks', \Search_Filter_Pro\Task_Runner\Database\Tasks_Table::class );
	Table_Manager::register( 'taskmeta', \Search_Filter_Pro\Task_Runner\Database\Tasks_Meta_Table::class );

	// New unified cache table.
	Table_Manager::register( 'cache', \Search_Filter_Pro\Cache\Database\Table::class );

	// Legacy indexer table.
	Table_Manager::register( 'index', \Search_Filter_Pro\Indexer\Legacy\Database\Index_Table::class );

	// Bitmap indexer table.
	Table_Manager::register( 'bitmap_index', \Search_Filter_Pro\Indexer\Bitmap\Database\Index_Table::class );

	// Bucket indexer tables.
	Table_Manager::register( 'bucket_index', \Search_Filter_Pro\Indexer\Bucket\Database\Index_Table::class );
	Table_Manager::register( 'bucket_metadata', \Search_Filter_Pro\Indexer\Bucket\Database\Metadata_Table::class );
	Table_Manager::register( 'bucket_overflow', \Search_Filter_Pro\Indexer\Bucket\Database\Overflow_Table::class );

	// Parent map table.
	Table_Manager::register( 'parent_map_index', \Search_Filter_Pro\Indexer\Parent_Map\Database\Table::class );

	// Search indexer tables.
	Table_Manager::register( 'search_terms', \Search_Filter_Pro\Indexer\Search\Database\Terms_Table::class );
	Table_Manager::register( 'search_postings', \Search_Filter_Pro\Indexer\Search\Database\Postings_Table::class );
	Table_Manager::register( 'search_doc_stats', \Search_Filter_Pro\Indexer\Search\Database\Doc_Stats_Table::class );

	// Uninstall all registered tables.
	foreach ( Table_Manager::get_registered() as $key ) {
		$table = Table_Manager::get( $key );
		if ( $table && $table->exists() ) {
			$table->uninstall();
			$result['tables_dropped'][] = $table->get_table_name();
		}
	}

	// Delete the consolidated table version registry.
	delete_option( Table::OPTION_NAME );
	$result['options_deleted'][] = Table::OPTION_NAME;

	// Delete network registry for multisite.
	if ( is_multisite() ) {
		delete_network_option( get_main_network_id(), Table::OPTION_NAME );
	}

	global $wp_rewrite;
	$wp_rewrite->flush_rules();

	return $result;
}
