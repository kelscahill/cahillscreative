<?php

// Max Mega Menu (https://wordpress.org/plugins/megamenu/)
// Added by Mike Meinz
//

add_action('wpmc_scan_widgets', 'wpmc_scan_widgets_maxmegamenu');

function wpmc_scan_widgets_maxmegamenu() {
	global $wpmc;
	global $wpdb;
	$wpmc->run_paged_parser( 'max-mega-menu', function( $cursor, $limit ) use ( $wpdb ) {
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_menu_item_url' AND LENGTH(TRIM(meta_value)) > 0 AND meta_id > %d ORDER BY meta_id ASC LIMIT %d",
			$cursor,
			$limit
		) );
	}, function( $rows ) use ( $wpmc ) {
		$urls = array();
		foreach( $rows as $row ) {
			$metavalue = $row->meta_value;
			if ( ( !empty( $metavalue ) ) && $wpmc->is_url( $metavalue ) ) {
				$url = $wpmc->clean_url( $metavalue );
				if ( !empty( $url ) ) {
					array_push( $urls, $url );
				}
			}
		}
		if ( !empty( $urls ) ) $wpmc->add_reference_url( $urls, 'MENU (URL)' );
	}, 250, function( $rows, $cursor ) {
		$last = end( $rows );
		return $last ? (int) $last->meta_id : $cursor;
	} );
}

?>
