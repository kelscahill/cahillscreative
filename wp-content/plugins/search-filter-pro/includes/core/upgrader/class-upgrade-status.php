<?php
/**
 * Upgrade Status persistence
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persists upgrade status to the database for diagnostics and retry tracking.
 */
class Upgrade_Status {

	/**
	 * Option key for storing upgrade status.
	 */
	const OPTION_KEY = 'search-filter-pro-upgrade-status';

	/**
	 * Read all upgrade status data, handling both legacy serialized arrays and JSON strings.
	 *
	 * @return array All upgrade statuses keyed by version.
	 */
	private static function get_all_data() {
		$value = get_option( self::OPTION_KEY, '' );
		if ( is_array( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && $value !== '' ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}
		return array();
	}

	/**
	 * Persist all upgrade status data as JSON.
	 *
	 * @param array $all All upgrade statuses keyed by version.
	 */
	private static function save_all_data( $all ) {
		update_option( self::OPTION_KEY, wp_json_encode( $all ) );
	}

	/**
	 * Set the status for a specific version upgrade.
	 *
	 * @param string      $version Version that was upgraded.
	 * @param string      $status  Status: 'success', 'failed', or 'skipped'.
	 * @param string|null $error   Optional error message.
	 * @param array       $errors  Optional array of detailed errors.
	 */
	public static function set( $version, $status, $error = null, $errors = array() ) {
		$all = self::get_all_data();
		// Preserve existing attempts count if present.
		$attempts        = isset( $all[ $version ]['attempts'] ) ? $all[ $version ]['attempts'] : 0;
		$all[ $version ] = array(
			'status'    => $status,
			'error'     => $error,
			'errors'    => $errors,
			'timestamp' => time(),
			'attempts'  => $attempts,
		);
		self::save_all_data( $all );
	}

	/**
	 * Get the status for a specific version.
	 *
	 * @param string $version Version to check.
	 * @return array|null Status data or null if not found.
	 */
	public static function get( $version ) {
		$all = self::get_all_data();
		return isset( $all[ $version ] ) ? $all[ $version ] : null;
	}

	/**
	 * Get all upgrade statuses.
	 *
	 * @return array All upgrade statuses keyed by version.
	 */
	public static function get_all() {
		return self::get_all_data();
	}

	/**
	 * Get all failed upgrades.
	 *
	 * @return array Failed upgrades keyed by version.
	 */
	public static function get_failed() {
		$all = self::get_all_data();
		return array_filter(
			$all,
			function ( $upgrade ) {
				return $upgrade['status'] === 'failed';
			}
		);
	}

	/**
	 * Check if there are any failed upgrades.
	 *
	 * @return bool
	 */
	public static function has_failures() {
		return ! empty( self::get_failed() );
	}

	/**
	 * Get the attempts count for a specific version.
	 *
	 * @param string $version Version to check.
	 * @return int Number of attempts.
	 */
	public static function get_attempts( $version ) {
		$status = self::get( $version );
		return $status ? ( $status['attempts'] ?? 0 ) : 0;
	}

	/**
	 * Increment the attempts count for a specific version.
	 *
	 * @param string $version Version to increment.
	 * @return int New attempts count.
	 */
	public static function increment_attempts( $version ) {
		$all = self::get_all_data();
		if ( ! isset( $all[ $version ] ) ) {
			$all[ $version ] = array();
		}
		$all[ $version ]['attempts'] = ( $all[ $version ]['attempts'] ?? 0 ) + 1;
		self::save_all_data( $all );
		return $all[ $version ]['attempts'];
	}

	/**
	 * Get all suspended upgrades (reached max attempts).
	 *
	 * @param int $max_attempts Maximum attempts before suspension.
	 * @return array Suspended upgrades keyed by version.
	 */
	public static function get_suspended( $max_attempts = 5 ) {
		$all = self::get_all_data();
		return array_filter(
			$all,
			function ( $upgrade ) use ( $max_attempts ) {
				$attempts = $upgrade['attempts'] ?? 0;
				$status   = $upgrade['status'] ?? '';
				return $status === 'failed' && $attempts >= $max_attempts;
			}
		);
	}

	/**
	 * Check if there are any suspended upgrades.
	 *
	 * @param int $max_attempts Maximum attempts before suspension.
	 * @return bool
	 */
	public static function has_suspended( $max_attempts = 5 ) {
		return ! empty( self::get_suspended( $max_attempts ) );
	}

	/**
	 * Clear the status for a specific version.
	 *
	 * @param string $version Version to clear.
	 */
	public static function clear( $version ) {
		$all = self::get_all_data();
		unset( $all[ $version ] );
		self::save_all_data( $all );
	}

	/**
	 * Clear all upgrade statuses.
	 */
	public static function clear_all() {
		delete_option( self::OPTION_KEY );
	}
}
