<?php
/**
 * Sets up the support for the shortcode features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter_Pro\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles pagination fixes for Search & Filter queries.
 *
 * @since 3.0.0
 */
class Pagination {

	/**
	 * Initialize pagination hooks.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_filter( 'get_pagenum_link', array( __CLASS__, 'pagination_fix_pagenum' ), 100 );
		add_filter( 'paginate_links', array( __CLASS__, 'pagination_fix_paginate' ), 100 );
	}

	/**
	 * Fix pagination URL for get_pagenum_link filter.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The pagination URL.
	 * @return string Modified URL.
	 */
	public static function pagination_fix_pagenum( $url ) {
		// These methods are currently not implemented and just return the URL as-is.
		// pagination_fix not yet implemented.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
		// $new_url = $this->pagination_fix( $url );
		return $url;
	}

	/**
	 * Fix pagination URL for paginate_links filter.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The pagination URL.
	 * @return string Modified URL.
	 */
	public static function pagination_fix_paginate( $url ) {
		// These methods are currently not implemented and just return the URL as-is.
		// pagination_fix not yet implemented.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
		// $new_url = $this->pagination_fix( $url );
		return $url;
	}

	/**
	 * Extract page number from URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The URL to parse.
	 * @return int The page number.
	 */
	public function get_page_no_from_url( $url ) {
		$url = str_replace( '&#038;', '&', $url );
		$url = str_replace( '#038;', '&', $url );

		$url_query = wp_parse_url( $url, PHP_URL_QUERY );
		$url_args  = array();
		if ( $url_query !== null ) {
			parse_str( $url_query, $url_args );
		}
		$sf_page_no = 0;

		if ( isset( $url_args['paged'] ) ) {
			$sf_page_no = (int) $url_args['paged'];
		} elseif ( $this->has_url_var( $url, 'page' ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif -- Intentionally empty, awaiting get_url_var implementation.
			// Extract page value from URL path segments, get_url_var not yet implemented.
			// TODO: Implement get_url_var method to extract page number from permalink.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
			// $sf_page_no = (int) $this->get_url_var( $url, 'page' );
		} elseif ( $this->last_url_param_is_page_no( $url ) ) { // Try to get page number from permalink url.
			$sf_page_no = (int) $this->last_url_param( $url );
		} elseif ( isset( $url_args['product-page'] ) ) { // Then its woocommerce product shortcode pagination.

			$sf_page_no = (int) ( $url_args['product-page'] );
		} elseif ( isset( $url_args['sf_paged'] ) ) { /* sf_paged check needs to be last, because we will always add it on anyway */

			$current_page = 1;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination parameter, no data modification.
			if ( isset( $_GET['sf_paged'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public pagination parameter, no data modification.
				$current_page = (int) $_GET['sf_paged'];
			}

			// little hack to stop appending `sf_paged` to urls pointing to page 1, where `?sf_paged` is appended to the current URL (and therefor automatically adding it to all pagination links).
			// so if the sf_paged value equals the current pages sf_paged value, don't add it to the URL - who wants pagination linking to the current page anyway.
			if ( $current_page !== (int) $url_args['sf_paged'] ) {
				$sf_page_no = (int) $url_args['sf_paged'];
			}
		}

		return $sf_page_no;
	}

	/**
	 * Check if URL contains a specific variable.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url  The URL to check.
	 * @param string $name The variable name to look for.
	 * @return bool True if found, false otherwise.
	 */
	public function has_url_var( $url, $name ) {
		$str_url  = $url;
		$arr_vals = explode( '/', $str_url );
		$found    = 0;
		foreach ( $arr_vals as $index => $value ) {
			if ( $value === $name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the last parameter from URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The URL to parse.
	 * @return string The last URL parameter.
	 */
	public function last_url_param( $url ) {
		// remove query string.
		$url = preg_replace( '/\?.*/', '', $url );

		// now get the last part.
		$url_parts = explode( '/', rtrim( $url, '/' ) );
		$last_part = end( $url_parts );

		return $last_part;
	}

	/**
	 * Check if the last URL parameter is a page number.
	 *
	 * @since 3.0.0
	 *
	 * @param string $url The URL to check.
	 * @return bool True if last param is a page number, false otherwise.
	 */
	public function last_url_param_is_page_no( $url ) {

		// Only do this on single posts/pages/custom post types.
		if ( is_singular() ) {

			$post = get_post( get_the_ID() );
			if ( ! $post ) {
				return false;
			}
			$slug = $post->post_name;

			// remove query string.
			$url = preg_replace( '/\?.*/', '', $url );

			// now get the last part.
			$url_parts = explode( '/', rtrim( $url, '/' ) );
			$last_part = end( $url_parts );

			// Make sure the last part is not the doc name, and it looks numeric.
			if ( ( $last_part !== $slug ) && ( is_numeric( $last_part ) ) ) {
				return true;
			}
		}

		return false;
	}
}
