<?php
/**
 * Class for handling authentication
 *
 * @link       http://searchandfilter.com
 * @since      3.0.1
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling authentication
 */
class Authentication {

	/**
	 * Try to get the current users http auth credentials so that we
	 * can pass them to wp_remote_get and wp_remote_post.
	 *
	 * @since    3.0.1
	 */
	public static function get_http_auth_credentials() {
		// Try to get any http auth from the PHP_AUTH... contants.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			return array(
				'username' => sanitize_user( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) ),
				'password' => sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_PW'] ) ),
			);
		}

		// If that fails, we can try to decode it from the Authorization header.
		if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {

			$auth_header = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
			$auth_string = str_replace( 'Basic ', '', $auth_header );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$auth_array = explode( ':', base64_decode( $auth_string ) );

			if ( count( $auth_array ) === 2 ) {
				return array(
					'username' => $auth_array[0],
					'password' => $auth_array[1],
				);
			}
		}

		// Else lets assume there is no http auth.
		return null;
	}
}
