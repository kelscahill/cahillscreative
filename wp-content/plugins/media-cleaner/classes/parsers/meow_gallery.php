<?php

add_action('wpmc_scan_post', 'wpmc_scan_html_meow_gallery', 10, 2);
add_filter( 'mgl_is_archive_context', 'wpmc_mgl_truncate');

function wpmc_mgl_truncate( $should_truncate )
{
    $should_truncate = false;
    return $should_truncate ;
}

function wpmc_scan_html_meow_gallery($html, $id)
{
    global $wpmc;
    $html = apply_filters( 'wpmc_affect_html_from_builder', $html, $id );

    $posts_images_urls = array();
    $posts_images_ids = array();

    $matches = array();
    

    global $wpmgl;
    if ( !isset( $wpmgl ) ) {
       return; // Meow Gallery is not active
    }


    // Galleries
    $pattern = get_shortcode_regex( ['gallery', 'meow-gallery'] );
    preg_match_all( '/'. $pattern .'/s', $html, $matches );

    foreach( $matches[3] as $index => $attrs_string ) {
        $attrs_string = stripslashes( $attrs_string );
        $attributes = shortcode_parse_atts( $attrs_string );
        $attributes = apply_filters( 'shortcode_atts_gallery', $attributes, null, $attributes, null );
        $inner_html = $wpmgl->gallery( $attributes );

        $urls = $wpmc->get_urls_from_html( $inner_html );

        $posts_images_urls = array_merge( $posts_images_urls, $urls );
    }

    $wpmc->add_reference_url( $posts_images_urls, 'Meow Gallery', $id );
    $posts_images_urls = array();

    // Collections

    global $wpmgl_pro;
    if ( !isset( $wpmgl_pro ) ) {
       return; // Meow Gallery Pro is not active
    }

    $pattern = get_shortcode_regex( ['meow-collection'] );
    preg_match_all( '/'. $pattern .'/s', $html, $matches );

    $thumbnail_keys = [
        'gallery_id' => 'id',
        'wplr_collection_id' => 'wplr-collection',
    ];

    foreach( $matches[3] as $index => $attrs_string ) {
        $attrs_string = stripslashes( $attrs_string );
        $attributes  = shortcode_parse_atts( $attrs_string );

        // This filters triggers Photo Engine to transform wplr-folder to attributes
        $attributes = apply_filters( 'shortcode_atts_collection', $attributes, null, $attributes );
        list( $gallery_ids, $layout ) = $wpmgl_pro->get_galleries_from_collection( $attributes );

        //We either get the galleries ids or the thumbnails information from wplr
        $collection_thumbnails = $wpmgl_pro->get_thumbnails( $gallery_ids, $attributes, $layout );

        foreach ( $collection_thumbnails as $thumbnail ) {
            $key   = null;
            $value = null;

            if ( array_key_exists( 'wplr_collection_id', $thumbnail ) ) {
                $key   = $thumbnail_keys['wplr_collection_id'];
                $value = $thumbnail['wplr_collection_id'];
            }

            if( array_key_exists( 'gallery_id', $thumbnail ) ) {
                $key   = $thumbnail_keys['gallery_id'];
                $value = $thumbnail[ 'gallery_id' ];
            }

            $inner_html = $wpmgl->gallery( [ $key => $value ] );
            $urls = $wpmc->get_urls_from_html( $inner_html );

            $posts_images_urls = array_merge( $posts_images_urls, $urls );
        }
    }

    $wpmc->add_reference_url( $posts_images_urls, 'Meow Collection', $id );
}