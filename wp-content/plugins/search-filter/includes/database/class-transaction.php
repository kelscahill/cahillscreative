<?php
/**
 * Database Transaction Wrapper
 *
 * Provides transaction state tracking with support for deferred callbacks.
 * This allows operations like error logging to be safely deferred until
 * after the transaction completes, preventing DB access during exception handling.
 *
 * @link       https://searchandfilter.com
 * @since      3.1.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Database
 */

namespace Search_Filter\Database;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transaction wrapper with depth tracking and deferred callbacks.
 *
 * Tracks transaction state so operations like error logging can automatically
 * defer when inside an active transaction, preventing DB access issues.
 *
 * @since 3.1.0
 */
class Transaction {

	/**
	 * Transaction depth (supports nested calls).
	 *
	 * @since 3.1.0
	 * @var int
	 */
	private static $depth = 0;

	/**
	 * Deferred callbacks to execute after transaction.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	private static $deferred_callbacks = array();

	/**
	 * Start a transaction.
	 *
	 * Only issues START TRANSACTION on the first call. Nested calls
	 * increment the depth counter but don't start new transactions.
	 *
	 * @since 3.1.0
	 */
	public static function start() {
		global $wpdb;

		if ( self::$depth === 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'START TRANSACTION' );
		}
		++self::$depth;
	}

	/**
	 * Commit the transaction.
	 *
	 * Only issues COMMIT when depth returns to 0. Flushes deferred
	 * callbacks after the commit completes.
	 *
	 * @since 3.1.0
	 */
	public static function commit() {
		global $wpdb;

		--self::$depth;

		if ( self::$depth === 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
			self::flush_deferred_callbacks();
		}
	}

	/**
	 * Rollback the transaction.
	 *
	 * Always issues ROLLBACK and resets depth to 0. Flushes deferred
	 * callbacks after the rollback completes.
	 *
	 * @since 3.1.0
	 */
	public static function rollback() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'ROLLBACK' );
		self::$depth = 0;
		self::flush_deferred_callbacks();
	}

	/**
	 * Check if currently inside a transaction.
	 *
	 * @since 3.1.0
	 * @return bool True if inside a transaction, false otherwise.
	 */
	public static function is_active() {
		return self::$depth > 0;
	}

	/**
	 * Queue a callback for execution after transaction ends.
	 *
	 * This is generic and can be used for logging or other deferred operations.
	 * Callbacks are executed in order after commit() or rollback().
	 *
	 * @since 3.1.0
	 * @param callable $callback The callback to execute after transaction.
	 */
	public static function defer( callable $callback ) {
		self::$deferred_callbacks[] = $callback;
	}

	/**
	 * Flush all deferred callbacks.
	 *
	 * Called after transaction ends (commit or rollback).
	 * Callbacks are executed in the order they were added.
	 *
	 * @since 3.1.0
	 */
	private static function flush_deferred_callbacks() {
		if ( empty( self::$deferred_callbacks ) ) {
			return;
		}

		$callbacks                = self::$deferred_callbacks;
		self::$deferred_callbacks = array();

		foreach ( $callbacks as $callback ) {
			call_user_func( $callback );
		}
	}

	/**
	 * Get current transaction depth.
	 *
	 * Primarily for testing purposes.
	 *
	 * @since 3.1.0
	 * @return int Current depth (0 = not in transaction).
	 */
	public static function get_depth() {
		return self::$depth;
	}

	/**
	 * Reset all state.
	 *
	 * For testing purposes only. Does NOT issue any SQL commands.
	 *
	 * @since 3.1.0
	 */
	public static function reset() {
		self::$depth              = 0;
		self::$deferred_callbacks = array();
	}
}
