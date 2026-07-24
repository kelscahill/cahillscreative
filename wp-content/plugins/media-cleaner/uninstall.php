<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Get option to check if clean uninstall is enabled
$options = get_option( 'wpmc_options', [] );
$clean_uninstall = isset( $options['clean_uninstall'] ) ? $options['clean_uninstall'] : false;

if ( $clean_uninstall ) {
    global $wpdb;
	$table_scan = $wpdb->prefix . 'mclean_scan';
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_scan ) ) ) === $table_scan;
	$quarantined = 0;
	if ( $table_exists ) {
		$has_run_id = $wpdb->get_var( "SHOW COLUMNS FROM $table_scan LIKE 'run_id'" ) === 'run_id';
		if ( $has_run_id ) {
			$tracked_run_id = max( 0, (int) get_option( 'wpmc_active_run_id', 0 ) );
			if ( $tracked_run_id < 1 ) {
				$tracked_run_id = max( 0, (int) $wpdb->get_var( "SELECT MAX(run_id) FROM $table_scan" ) );
			}
			$quarantined = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $table_scan WHERE run_id = %d AND deleted = 1",
				$tracked_run_id
			) );
		}
		else {
			$quarantined = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_scan WHERE deleted = 1" );
		}
	}
	if ( $quarantined > 0 ) {
		wp_die(
			sprintf(
				__( 'Media Cleaner still has %d item(s) in its trash. Restore them or empty the trash before uninstalling so no files are stranded.', 'media-cleaner' ),
				$quarantined
			),
			__( 'Media Cleaner uninstall blocked', 'media-cleaner' ),
			array( 'response' => 409, 'back_link' => true )
		);
	}

	delete_transient( 'wpmc_progress' );

    // Remove all wpmc options
	$option_pattern = $wpdb->esc_like( 'wpmc_' ) . '%';
	$db_options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $option_pattern ) );
    foreach ( $db_options as $option ) {
        delete_option( $option->option_name );
    }

    // Remove database tables
    $table_refs = $wpdb->prefix . "mclean_refs";
    $table_legacy = $wpdb->prefix . "wpmcleaner";
	$table_runs = $wpdb->prefix . 'mclean_runs';
	$table_work = $wpdb->prefix . 'mclean_work';
	$table_operations = $wpdb->prefix . 'mclean_operations';
	$table_duplicates = $wpdb->prefix . 'mclean_duplicates';
	$wpdb->query( "DROP TABLE IF EXISTS $table_scan, $table_refs, $table_legacy, $table_runs, $table_work, $table_operations, $table_duplicates" );
}
