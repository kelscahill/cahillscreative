<?php

// My Calendar (https://wordpress.org/plugins/my-calendar/)
// Added by Mike Meinz
//

add_action( 'wpmc_scan_widgets', 'wpmc_scan_widgets_mycalendar' );

function wpmc_scan_widgets_mycalendar() {
	global $wpmc;
	global $wpdb;
	$table = $wpdb->prefix . 'my_calendar';
	$wpmc->run_paged_parser( 'my-calendar-events', function( $cursor, $limit ) use ( $wpdb, $table ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT event_id, event_desc, event_short, event_link, event_url, event_image FROM {$table} WHERE
		 (LOWER(event_desc) like '%%http%%' or
		 LOWER(event_short) like '%%http%%' or
		 LOWER(event_link) like 'http%%' or
		 LOWER(event_image) like 'http%%' or
		 LOWER(event_url) like 'http%%') AND event_id > %d ORDER BY event_id ASC LIMIT %d", $cursor, $limit ) );
	}, function( $rows ) use ( $wpmc ) {
		$eventurls = array();
		foreach ( $rows as $row ) {
			if ( !empty($row->event_desc) ) {
				$urls = $wpmc->get_urls_from_html( $row->event_desc );
				$eventurls = array_merge( $eventurls, $urls);
			}
			if ( !empty($row->event_short) ) {
				$urls = $wpmc->get_urls_from_html( $row->event_short );
				$eventurls = array_merge( $eventurls, $urls);
			}
			if ( !empty($row->event_link) ) {
				array_push( $eventurls, $wpmc->clean_url( $row->event_link ) );
			}
			if ( !empty($row->event_url) ) {
				array_push( $eventurls, $wpmc->clean_url( $row->event_url ) );
			}
			if ( !empty($row->event_image) ) {
				array_push( $eventurls, $wpmc->clean_url( $row->event_image ) );
			}
		}
		if ( !empty( $eventurls ) ) $wpmc->add_reference_url( $eventurls, 'CALENDAR (URL)' );
	}, 100, function( $rows, $cursor ) {
		$last = end( $rows );
		return $last ? (int) $last->event_id : $cursor;
	} );
}

?>
