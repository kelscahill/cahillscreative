<?php
/**
 * Async Handler.
 *
 * Handles shutdown callbacks for deferred operations.
 * Allows registering callbacks to run on shutdown after response is sent to user.
 *
 * @package Search_Filter\Core
 * @since 3.0.0
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shutdown callbacks for deferred operations.
 *
 * @since 3.0.0
 */
class Async {

	/**
	 * Queue of callbacks to execute on shutdown.
	 *
	 * @var array
	 */
	private static $callbacks = array();

	/**
	 * Register a callback to run on shutdown.
	 *
	 * Duplicate callbacks (same callback + args) are ignored.
	 *
	 * @since 3.0.0
	 *
	 * @param callable $callback The callback to execute.
	 * @param array    $args     Optional arguments to pass to callback.
	 * @return bool True if registered, false if not callable or already exists.
	 */
	public static function register_callback( $callback, $args = array() ) {
		if ( ! is_callable( $callback ) ) {
			return false;
		}

		// Skip if this exact callback + args is already registered.
		if ( self::has_callback( $callback, $args ) ) {
			return true;
		}

		self::$callbacks[] = array(
			'callback' => $callback,
			'args'     => $args,
		);

		// Register shutdown hook if not already registered.
		if ( ! has_action( 'shutdown', array( __CLASS__, 'run_callbacks' ) ) ) {
			add_action( 'shutdown', array( __CLASS__, 'run_callbacks' ), 100 );
		}

		return true;
	}

	/**
	 * Check if a callback + args combination is already registered.
	 *
	 * @since 3.0.0
	 *
	 * @param callable $callback The callback to check.
	 * @param array    $args     The arguments to check.
	 * @return bool True if already registered, false otherwise.
	 */
	private static function has_callback( $callback, $args = array() ) {
		foreach ( self::$callbacks as $existing ) {
			if ( $existing['callback'] === $callback && $existing['args'] === $args ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Execute all registered callbacks on shutdown.
	 *
	 * @since 3.0.0
	 */
	public static function run_callbacks() {
		foreach ( self::$callbacks as $item ) {
			call_user_func_array( $item['callback'], $item['args'] );
		}
		self::$callbacks = array();
	}

	/**
	 * Reset all state.
	 *
	 * For testing purposes only.
	 *
	 * @since 3.0.0
	 */
	public static function reset() {
		self::$callbacks = array();
	}
}
