<?php

namespace Search_Filter\Core;

use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notices class
 *
 * @since 3.0.0
 */
class Notices {
	/**
	 * The array of notices.
	 *
	 * @var array
	 */
	private static $notices = array();
	/**
	 * Add a notice.
	 *
	 * @param string $message The message to add.
	 * @param string $type    The type of notice.
	 * @param string $id      The ID of the notice.
	 */
	public static function add_notice( $message, $status, $id = '', $actions = array() ) {

		if ( empty( $id ) ) {
			$id = self::get_new_id();
		}

		$actions_keys = array_keys( $actions );

		if ( self::is_notice_dismissed( $id ) ) {
			return;
		}

		self::$notices[] = array(
			'message' => $message,
			'status'  => $status,
			'id'      => $id,
			'actions' => $actions,
		);
	}
	/**
	 * Get the notices.
	 *
	 * @return array The notices.
	 */
	public static function get_notices() {
		do_action( 'search-filter/core/notices/get_notices' );
		return self::$notices;
	}
	/**
	 * Get a new ID.
	 *
	 * @return string The new ID.
	 */
	private static function get_new_id() {
		return md5( time() );
	}

	/**
	 * Check if a notice has been dismissed.
	 *
	 * @param string $id The ID of the notice.
	 * @return bool True if dismissed, false if not.
	 */
	public static function is_notice_dismissed( $id ) {
		$dismissed_notices = Options::get_option_value( 'dismissed-notices' );
		return isset( $dismissed_notices[ $id ] ) && $dismissed_notices[ $id ];
	}
	/**
	 * Dismiss a notice.
	 *
	 * @param string $id The ID of the notice.
	 */
	public static function dismiss_notice( $id ) {
		$dismissed_notices        = Options::get_option_value( 'dismissed-notices' );
		$dismissed_notices[ $id ] = true;
		Options::update_option_value( 'dismissed-notices', $dismissed_notices );
	}
}
