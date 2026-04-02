<?php
/**
 * Abstract base class for upgrades
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class that all upgrade classes should extend.
 *
 * Provides automatic error handling, logging, and result tracking.
 * Subclasses implement do_upgrade() which returns an Upgrade_Result.
 */
abstract class Upgrade_Base {

	/**
	 * Subclasses implement this method with the actual upgrade logic.
	 *
	 * @return Upgrade_Result The result of the upgrade operation.
	 */
	abstract protected static function do_upgrade();

	/**
	 * Run the upgrade with error handling.
	 *
	 * This is a template method - it wraps do_upgrade() with try/catch
	 * and handles logging. Subclasses should NOT override this method.
	 *
	 * @return Upgrade_Result The result of the upgrade operation.
	 */
	final public static function upgrade() {
		try {
			$result = static::do_upgrade();

			// Ensure we always have an Upgrade_Result.
			if ( ! ( $result instanceof Upgrade_Result ) ) {
				$result = Upgrade_Result::failed( 'Upgrade did not return an Upgrade_Result' );
			}

			if ( ! $result->is_success() ) {
				self::log_failure( $result );
			}

			return $result;

		} catch ( \Throwable $e ) {
			$result = Upgrade_Result::failed( $e->getMessage() );
			self::log_failure( $result, $e );
			return $result;
		}
	}

	/**
	 * Log a failure to the error log.
	 *
	 * @param Upgrade_Result  $result The failed result.
	 * @param \Throwable|null $e      Optional exception that caused the failure.
	 */
	protected static function log_failure( Upgrade_Result $result, $e = null ) {
		$message = sprintf(
			'[S&F Pro Upgrader] %s failed: %s',
			static::class,
			$result->message ? $result->message : 'Unknown error'
		);

		if ( $e ) {
			$message .= sprintf(
				' | Exception: %s in %s:%d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}

		if ( ! empty( $result->errors ) ) {
			$message .= ' | Errors: ' . wp_json_encode( $result->errors );
		}

		Util::error_log( $message, 'error' );
	}
}
