<?php

add_action( 'wpmc_scan_once', 'wpmc_scan_once_woocommerce' );
add_action( 'wpmc_scan_postmeta', 'wpmc_scan_postmeta_woocommerce' );

/**
 * Kept for third-party compatibility. Media Cleaner must never alter WordPress
 * core-table indexes during a scan.
 */
function wpmc_ensure_woocommerce_indexes() {
	return true;
}

function wpmc_scan_once_woocommerce() {
	global $wpdb, $wpmc;

	// Ensure indexes exist for better performance
	wpmc_ensure_woocommerce_indexes();

	$wpmc->run_paged_parser( 'woocommerce-category-images', function( $cursor, $limit ) use ( $wpdb ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_value FROM $wpdb->termmeta WHERE meta_key LIKE '%%thumbnail_id%%' AND meta_value != '' AND meta_value IS NOT NULL AND meta_id > %d ORDER BY meta_id ASC LIMIT %d", $cursor, $limit ) );
	}, function( $rows ) use ( $wpmc ) {
		$ids = array();
		foreach ( $rows as $row ) {
			if ( is_numeric( $row->meta_value ) && (int) $row->meta_value > 0 ) $ids[] = (int) $row->meta_value;
		}
		if ( !empty( $ids ) ) $wpmc->add_reference_id( $ids, 'WOOCOMMERCE (ID)' );
	}, 250, function( $rows, $cursor ) {
		$last = end( $rows );
		return $last ? (int) $last->meta_id : $cursor;
	} );

	// PlaceHolder Image ID
	$placeholder_id = get_option( 'woocommerce_placeholder_image', null, true );
	if ( !empty( $placeholder_id ) ) {
		$wpmc->add_reference_id( (int)$placeholder_id, 'WOOCOMMERCE (ID)' );
	}

	$wpmc->run_paged_parser( 'woocommerce-category-descriptions', function( $cursor, $limit ) use ( $wpdb ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT term_taxonomy_id, description FROM $wpdb->term_taxonomy WHERE taxonomy = 'product_cat' AND description <> '' AND description IS NOT NULL AND term_taxonomy_id > %d ORDER BY term_taxonomy_id ASC LIMIT %d", $cursor, $limit ) );
	}, function( $rows ) use ( $wpmc ) {
		foreach ( $rows as $row ) {
			$wpmc->add_reference_url( $wpmc->get_urls_from_html( $row->description ), 'WOOCOMMERCE (URL)' );
		}
	}, 100, function( $rows, $cursor ) {
		$last = end( $rows );
		return $last ? (int) $last->term_taxonomy_id : $cursor;
	} );
}

function wpmc_scan_postmeta_woocommerce( $id ) {
	global $wpdb, $wpmc;

	// Downloadable files
	$downloable_files = get_post_meta( $id, '_downloadable_files', true );
	if ( !empty( $downloable_files ) ) {
		foreach ( $downloable_files as $file ) {
			$wpmc->add_reference_url( $wpmc->clean_url( $file['file'] ), 'WOOCOMMERCE DOWNLOAD (URL)', $id );
		}
	} 

	// Galleries - check if any exist first
	$galleries_images_wc = array();
	$id = (int)$id;
	
	$gallery_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = '_product_image_gallery' AND meta_value != '' AND meta_value IS NOT NULL", $id ) );
	
	if ( $gallery_count > 0 ) {
		$res = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = '_product_image_gallery' AND meta_value != '' AND meta_value IS NOT NULL", $id ) );

		foreach ( $res as $values ) {
			$ids = explode( ',', $values );
			$galleries_images_wc = array_merge( $galleries_images_wc, $ids );
		}

		foreach ( $galleries_images_wc as $thumbnail_id ) {
			//* WooCommerce Gallery Images use srcset so the sizes URL are actually used
			$urls = $wpmc->get_thumbnails_urls_from_srcset( $thumbnail_id );
			$wpmc->add_reference_url( $urls, 'WooCommerce Gallery {SAFE}', $id );
		}

		$wpmc->add_reference_id( $galleries_images_wc, 'WooCommerce Gallery', $id );
	}

	$first_variation = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation' ORDER BY ID ASC LIMIT 1", $id ) );
	if ( $wpdb->last_error ) throw new RuntimeException( sprintf( __( 'WooCommerce variation parser database error: %s', 'media-cleaner' ), $wpdb->last_error ) );
	if ( !$first_variation ) return;

	$wpmc->run_paged_parser( 'woocommerce-variations-' . $id, function( $cursor, $limit ) use ( $wpdb, $id ) {
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = 'product_variation' AND ID > %d ORDER BY ID ASC LIMIT %d", $id, $cursor, $limit ) );
	}, function( $variations ) use ( $wpdb, $wpmc, $id ) {
		foreach ( $variations as $variation_id ) {
			$gallery_variations = array();

			// Check if this variation has additional images before querying
			$variation_images_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = '_wc_additional_variation_images' AND meta_value != '' AND meta_value IS NOT NULL", $variation_id ) );
			
			if ( $variation_images_count > 0 ) {
				$res = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = '_wc_additional_variation_images' AND meta_value != '' AND meta_value IS NOT NULL", $variation_id ) );
				
				if( !empty( $res ) ) {
					foreach ( $res as $values ) {
						$ids = explode( ',', $values );
						$gallery_variations = array_merge( $gallery_variations, $ids );
					}
				}

				// WooCommerce Gallery Images use srcset so the sizes URL are actually used
				foreach ( $gallery_variations as $thumbnail_id ) {
					if( empty( $thumbnail_id ) || !is_numeric( $thumbnail_id ) ) continue;

					$urls = $wpmc->get_thumbnails_urls_from_srcset( intval( $thumbnail_id ) );
					$wpmc->add_reference_url( $urls, 'WooCommerce Variations Gallery {SAFE}', $id );
					$wpmc->add_reference_id(  $thumbnail_id, 'WooCommerce Variations Gallery', $id );
				}
			}
		}
	}, 25, function( $variations, $cursor ) {
		$last = end( $variations );
		return $last ? (int) $last : $cursor;
	} );
}

?>
