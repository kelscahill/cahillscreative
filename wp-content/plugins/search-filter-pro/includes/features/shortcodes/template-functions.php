<?php

function search_filter_pagination_get_current_page() {
	$paged = 1;
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );
	} elseif ( get_query_var( 'page' ) ) {
		$paged = get_query_var( 'page' );
	}
	return $paged;
}
/**
 * This part is a hacky, but we need to redeclare an old function that was used for pagination
 * in our old template files to prevent them from causing critical errors.
 */
function search_filter_get_next_posts_link( $label = null, $max_page = 0 ) {
	global $wp_query;

	if ( ! $max_page ) {
		$max_page = $wp_query->max_num_pages;
	}

	$paged = 1;
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );
	} elseif ( get_query_var( 'page' ) ) {
		$paged = get_query_var( 'page' );
	}

	$next_page = (int) $paged + 1;
	if ( null === $label ) {
		$label = __( 'Next Page &raquo;' );
	}
	if ( $next_page <= $max_page ) {
		/**
		 * Filters the anchor tag attributes for the next posts page link.
		 *
		 * @since 2.7.0
		 *
		 * @param string $attributes Attributes for the anchor tag.
		 */
		$attr = apply_filters( 'next_posts_link_attributes', '' );
		
		$link   = search_filter_get_next_posts_page_link( $max_page );
		$link = $link ? esc_url( $link ) : '';

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			$link,
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}

function search_filter_get_previous_posts_link( $label = null ) {
	
	$paged = 1;
	if ( get_query_var( 'paged' ) ) {
		$paged = get_query_var( 'paged' );
	} elseif ( get_query_var( 'page' ) ) {
		$paged = get_query_var( 'page' );
	}

	if ( null === $label ) {
		$label = __( '&laquo; Previous Page' );
	}

	if ( $paged > 1 ) {
		/**
		 * Filters the anchor tag attributes for the previous posts page link.
		 *
		 * @since 2.7.0
		 *
		 * @param string $attributes Attributes for the anchor tag.
		 */
		$attr = apply_filters( 'previous_posts_link_attributes', '' );

		$link = esc_url( search_filter_get_previous_posts_page_link() );

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			$link,
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}




/**
 * Modified from core `get_next_posts_page_link` to work with `page` as well
 * as `paged` - otherwise pagination won't work on a static homepage.
 *
 * @global int $paged
 *
 * @param int $max_page Optional. Max pages. Default 0.
 * @return string|void The link URL for next posts page.
 */
function search_filter_get_next_posts_page_link( $max_page = 0 ) {
	
	$paged = search_filter_pagination_get_current_page();

	if ( ! is_single() ) {
		if ( ! $paged ) {
			$paged = 1;
		}

		$next_page = (int) $paged + 1;

		if ( ! $max_page || $max_page >= $next_page ) {
			return get_pagenum_link( $next_page );
		}
	}
}
/**
 * Retrieves the previous posts page link.
 *
 * Will only return string, if not on a single page or post.
 *
 * Backported to 2.0.10 from 2.1.3.
 *
 * @since 2.0.10
 *
 * @global int $paged
 *
 * @return string|void The link for the previous posts page.
 */
function search_filter_get_previous_posts_page_link() {
	
	$paged = search_filter_pagination_get_current_page();

	if ( ! is_single() ) {
		$previous_page = (int) $paged - 1;

		if ( $previous_page < 1 ) {
			$previous_page = 1;
		}

		return get_pagenum_link( $previous_page );
	}
}