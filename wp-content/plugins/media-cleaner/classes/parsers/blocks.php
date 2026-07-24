<?php

// WordPress Block Editor (core blocks).
//
// Since WordPress 6.5, blocks can carry a background image through the block
// supports API. The image is kept in the block attributes only, and WordPress
// renders the background-image rule at display time, so the URL never appears
// in the saved HTML. Scanning the content alone therefore misses it, and the
// media looks unused. The attributes are read here instead.

add_action( 'wpmc_scan_post', 'wpmc_scan_post_blocks', 10, 2 );

function wpmc_scan_post_blocks( $html, $id ) {
	global $wpmc;

	if ( empty( $html ) || !is_string( $html ) ) {
		return;
	}

	$ids = array();
	$urls = array();

	if ( strpos( $html, '<!-- wp:' ) !== false ) {
		$blocks = parse_blocks( $html );
		if ( is_array( $blocks ) ) {
			wpmc_scan_blocks_recursively( $blocks, $ids, $urls );
		}
	}
	else if ( get_post_type( $id ) === 'wp_global_styles' ) {
		// The site-wide background set in the Site Editor is kept here as plain JSON,
		// with no block markup at all, so nothing above ever sees it. The attributes
		// are shaped the same, so the same walk finds it.
		$styles = json_decode( $html, true );
		if ( is_array( $styles ) ) {
			wpmc_scan_block_attributes( $styles, $ids, $urls );
		}
	}

	if ( !empty( $ids ) ) {
		$wpmc->add_reference_id( array_values( array_unique( $ids ) ), 'Block Background (ID)', $id );
	}
	if ( !empty( $urls ) ) {
		$wpmc->add_reference_url( array_values( array_unique( $urls ) ), 'Block Background (URL)', $id );
	}
}

function wpmc_scan_blocks_recursively( $blocks, &$ids, &$urls, $depth = 0 ) {
	if ( $depth > 32 || !is_array( $blocks ) ) {
		return;
	}
	foreach ( $blocks as $block ) {
		if ( !is_array( $block ) ) {
			continue;
		}
		if ( !empty( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
			wpmc_scan_block_attributes( $block['attrs'], $ids, $urls );
		}
		// Background images are commonly set on nested blocks too.
		if ( !empty( $block['innerBlocks'] ) ) {
			wpmc_scan_blocks_recursively( $block['innerBlocks'], $ids, $urls, $depth + 1 );
		}
	}
}

function wpmc_scan_block_attributes( $attributes, &$ids, &$urls, $depth = 0 ) {
	global $wpmc;

	if ( $depth > 32 || !is_array( $attributes ) ) {
		return;
	}
	foreach ( $attributes as $key => $value ) {
		if ( $key === 'backgroundImage' && is_array( $value ) ) {
			if ( !empty( $value['id'] ) && is_numeric( $value['id'] ) ) {
				$ids[] = (int) $value['id'];
			}
			if ( !empty( $value['url'] ) && is_string( $value['url'] ) ) {
				$url = $wpmc->clean_url( $value['url'] );
				if ( !empty( $url ) ) {
					$urls[] = $url;
				}
			}
			continue;
		}
		if ( is_array( $value ) ) {
			wpmc_scan_block_attributes( $value, $ids, $urls, $depth + 1 );
		}
	}
}

?>
