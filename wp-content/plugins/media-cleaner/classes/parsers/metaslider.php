<?php

add_action( 'wpmc_scan_widgets', 'wpmc_scan_widgets_metaslider' );

function wpmc_scan_widgets_metaslider() {
	global $wpdb;
	global $wpmc;
	$wpmc->run_paged_parser( 'metaslider-images', function( $cursor, $limit ) use ( $wpdb ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT object_id
		FROM {$wpdb->term_relationships}
		WHERE object_id > 0
		AND object_id > %d
		AND term_taxonomy_id
		IN (SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'ml-slider')
		ORDER BY object_id ASC LIMIT %d", $cursor, $limit ) );
	}, function( $rows ) use ( $wpmc ) {
		$imageIds = wp_list_pluck( $rows, 'object_id' );
		if ( !empty( $imageIds ) ) {
			$wpmc->add_reference_id( $imageIds, 'SLIDER (ID)' );
		}
	}, 250, function( $rows, $cursor ) {
		$last = end( $rows );
		return $last ? (int) $last->object_id : $cursor;
	} );
}
?>
