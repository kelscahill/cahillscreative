<?php
/**
 * Fresh mysqli connection for shutdown handlers.
 *
 * When PHP is killed mid-query (LiteSpeed, OOM), $wpdb is broken.
 * This class creates a fresh mysqli connection using WP DB constants,
 * replicating wpdb::db_connect() without the bail() call.
 *
 * @package Search_Filter_Pro\Database
 * @since 3.3.3
 */

namespace Search_Filter_Pro\Database;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fresh Connection Class.
 *
 * @since 3.3.3
 */
class Fresh_Connection {

	/**
	 * Open a fresh mysqli connection using WordPress DB constants.
	 *
	 * Uses $wpdb->parse_db_host() for IPv6/socket/port parsing,
	 * respects MYSQL_CLIENT_FLAGS, handles mysqlnd IPv6 brackets.
	 *
	 * @since 3.3.3
	 *
	 * @return \mysqli|false Connection or false on failure.
	 */
	public static function create() {
		global $wpdb;

		if ( ! defined( 'DB_HOST' ) || ! defined( 'DB_NAME' ) ) {
			return false;
		}

		$db_host = DB_HOST;
		$db_user = defined( 'DB_USER' ) ? DB_USER : '';
		$db_pass = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
		$db_name = DB_NAME;

		// Parse host using WordPress's own method (handles IPv6, sockets, ports).
		$host    = $db_host;
		$port    = null;
		$socket  = null;
		$is_ipv6 = false;

		if ( $wpdb && method_exists( $wpdb, 'parse_db_host' ) ) {
			$host_data = $wpdb->parse_db_host( $db_host );
			if ( $host_data ) {
				list( $host, $port, $socket, $is_ipv6 ) = $host_data;
			}
		}

		// mysqlnd requires IPv6 addresses in brackets.
		if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
			$host = "[$host]";
		}

		// Respect MYSQL_CLIENT_FLAGS (SSL, compression, etc.).
		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		// Suppress PHP 8.1+ strict mysqli error reporting.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.DB.RestrictedFunctions -- Fresh connection needed, $wpdb is broken in shutdown.
		@mysqli_report( MYSQLI_REPORT_OFF );

		// phpcs:ignore WordPress.DB.RestrictedFunctions -- Fresh connection needed, $wpdb is broken in shutdown.
		$conn = mysqli_init();
		if ( ! $conn ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.RestrictedFunctions, WordPress.PHP.NoSilencedErrors.Discouraged -- Fresh connection needed, $wpdb is broken in shutdown.
		$connected = @mysqli_real_connect( $conn, $host, $db_user, $db_pass, $db_name, $port, $socket, $client_flags );
		if ( ! $connected || $conn->connect_errno ) {
			return false;
		}

		return $conn;
	}
}
