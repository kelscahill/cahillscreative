<?php
/**
 * This part is a hacky, but we need to redeclare an old function that was used for pagination
 * in our old template files to prevent them from causing critical errors.
 *
 * We don't want to always declare this function globally, so we'll use a (horrible) `call_user_func`
 * to trapdoor out of our namespace and get it into the global namespace.
 */
function search_filter_get_next_posts_link( $label = null, $max_page = 0 ) {
	global $paged, $wp_query;

	if ( ! $max_page ) {
		$max_page = $wp_query->max_num_pages;
	}
	if ( ! $paged ) {
		$paged = 1;
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
		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			next_posts( $max_page, false ),
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}
function search_filter_get_previous_posts_link( $label = null ) {
	global $paged;

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

		return sprintf(
			'<a href="%1$s" %2$s>%3$s</a>',
			previous_posts( false ),
			$attr,
			preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label )
		);
	}
}
