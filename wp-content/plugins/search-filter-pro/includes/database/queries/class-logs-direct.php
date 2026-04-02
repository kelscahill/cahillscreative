<?php
/**
 * Logs Direct - Resilient log insertion for shutdown handlers.
 *
 * Inserts log entries with fallback to a fresh mysqli connection
 * when $wpdb is broken (e.g., after LiteSpeed kill).
 *
 * @package Search_Filter_Pro\Database\Queries
 * @since 3.3.3
 */

namespace Search_Filter_Pro\Database\Queries;

use Search_Filter_Pro\Database\Fresh_Connection;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Logs Direct Class.
 *
 * @since 3.3.3
 */
class Logs_Direct {

	/**
	 * Get the logs table name.
	 *
	 * @since 3.3.3
	 *
	 * @return string Table name with prefix.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'search_filter_logs';
	}

	/**
	 * Insert a log entry, falling back to fresh connection if $wpdb is broken.
	 *
	 * @since 3.3.3
	 *
	 * @param string $message Log message.
	 * @param string $level   Log level (error, warning, notice).
	 * @return bool True if inserted, false on failure.
	 */
	public static function resilient_create_log( $message, $level = 'error' ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// 1. Try $wpdb first.
		if ( $wpdb && ! empty( $wpdb->dbh ) && $wpdb->ready ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Shutdown handler, must bypass cache.
			$result = $wpdb->insert(
				$table_name,
				array(
					'message' => $message,
					'level'   => $level,
				)
			);
			if ( false !== $result ) {
				return true;
			}
		}

		// 2. Fall back to fresh connection.
		$conn = Fresh_Connection::create();
		if ( ! $conn ) {
			return false;
		}

		$escaped_message = $conn->real_escape_string( $message );
		$escaped_level   = $conn->real_escape_string( $level );
		$result          = $conn->query(
			"INSERT INTO `{$table_name}` (`message`, `level`) VALUES ('{$escaped_message}', '{$escaped_level}')"
		);
		$conn->close();

		return (bool) $result;
	}
}
