<?php

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deprecations class
 *
 * @since 3.0.0
 */
class Deprecations {
	/**
	 * The array of deprecations.
	 *
	 * @var array
	 */
	private static $deprecations = array();
	/**
	 * Add a deprecation.
	 *
	 * @param string $message The message to add.
	 */
	public static function add( $message ) {
		if ( is_user_logged_in() ) {
			trigger_error( esc_html( $message ), E_USER_NOTICE );
		}
		error_log( $message );
	}
}
