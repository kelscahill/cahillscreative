<?php
/**
 * Debug logs REST API endpoints.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Rest_API
 */

namespace Search_Filter\Rest_API;

use Search_Filter\Database\Queries\Logs as Logs_Query;
use Search_Filter\Database\Tables\Logs as Logs_Table;
use Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug logs REST API endpoints.
 */
class Logs {

	/**
	 * Register REST routes.
	 */
	public function add_routes() {
		// Get logs with pagination and filtering.
		register_rest_route(
			'search-filter/v1',
			'/debug/logs',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_logs' ),
					'args'                => array(
						'paged'     => array(
							'type'              => 'number',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page'  => array(
							'type'              => 'number',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'level'     => array(
							'type'              => 'string',
							'enum'              => array( 'error', 'warning', 'notice', 'info' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'search'    => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'date_from' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'date_to'   => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'order'     => array(
							'type'    => 'string',
							'enum'    => array( 'ASC', 'DESC' ),
							'default' => 'DESC',
						),
						'orderby'   => array(
							'type'    => 'string',
							'enum'    => array( 'id', 'date_created', 'level' ),
							'default' => 'date_created',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'clear_logs' ),
					'args'                => array(
						'before_date' => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => array( $this, 'permissions' ),
				),
			)
		);

		// Export logs.
		register_rest_route(
			'search-filter/v1',
			'/debug/logs/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_logs' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
	}

	/**
	 * Check permissions.
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if logging features are enabled.
	 *
	 * @return array|true True if enabled, array with message if disabled.
	 */
	private function check_logging_enabled() {
		// Check if debug mode is enabled.
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return array(
				'logs'       => array(),
				'totalItems' => 0,
				'totalPages' => 0,
				'message'    => __( 'Debug mode is disabled. Enable it in Settings > Features to view logs.', 'search-filter' ),
			);
		}

		// Check if logging to database is enabled.
		$log_to_database = Features::get_setting_value( 'debugger', 'logToDatabase' );
		if ( 'yes' !== $log_to_database ) {
			return array(
				'logs'       => array(),
				'totalItems' => 0,
				'totalPages' => 0,
				'message'    => __( 'Database logging is disabled. Enable it in Settings > Debug Mode to view logs.', 'search-filter' ),
			);
		}

		return true;
	}

	/**
	 * Get logs with filtering.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return array Array containing logs data.
	 */
	public function get_logs( $request ) {
		// Check if logging features are enabled.
		$enabled = $this->check_logging_enabled();
		if ( true !== $enabled ) {
			return $enabled;
		}

		$logs_query = new Logs_Query();

		$query_args = array(
			'number'  => $request['per_page'],
			'offset'  => ( $request['paged'] - 1 ) * $request['per_page'],
			'order'   => $request['order'],
			'orderby' => $request['orderby'],
		);

		// Add filters.
		if ( ! empty( $request['level'] ) ) {
			$query_args['level'] = $request['level'];
		}

		if ( ! empty( $request['search'] ) ) {
			$query_args['search']         = $request['search'];
			$query_args['search_columns'] = array( 'message' );
		}

		if ( ! empty( $request['date_from'] ) ) {
			$query_args['date_query'] = array(
				array(
					'column' => 'date_created',
					'after'  => $request['date_from'],
				),
			);
		}

		if ( ! empty( $request['date_to'] ) ) {
			if ( ! isset( $query_args['date_query'] ) ) {
				$query_args['date_query'] = array();
			}
			$query_args['date_query'][] = array(
				'column' => 'date_created',
				'before' => $request['date_to'],
			);
		}

		$logs  = $logs_query->query( $query_args );
		$total = $logs_query->found_items;

		return array(
			'logs'       => $logs,
			'totalItems' => $total,
			'totalPages' => ceil( $total / $request['per_page'] ),
		);
	}

	/**
	 * Clear logs.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return array Array with success status.
	 */
	public function clear_logs( $request ) {
		// Check if logging features are enabled.
		$enabled = $this->check_logging_enabled();
		if ( true !== $enabled ) {
			return array(
				'success' => false,
				'message' => $enabled['message'],
			);
		}

		$logs_query = new Logs_Query();

		if ( ! empty( $request['before_date'] ) ) {
			// Delete logs before specified date.
			$logs_query->delete_items(
				array(
					'date_created' => array(
						'value'   => $request['before_date'],
						'compare' => '<',
					),
				)
			);
		} else {
			// Delete all logs.
			$logs_table = new Logs_Table();
			// If the table does not exist, then create the table.
			if ( $logs_table->exists() ) {
				$logs_table->truncate();
			}
		}

		return array( 'success' => true );
	}

	/**
	 * Export logs as CSV.
	 *
	 * @since 3.0.0
	 *
	 * @return void Outputs CSV file and exits.
	 */
	public function export_logs() {
		// Check if logging features are enabled.
		$enabled = $this->check_logging_enabled();
		if ( true !== $enabled ) {
			// Return empty CSV with just headers.
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="logs-empty.csv"' );
			echo "\xEF\xBB\xBF"; // UTF-8 BOM.
			echo "ID,Date Created,Level,Message\n";
			exit;
		}

		$logs_query = new Logs_Query();

		// Get all logs for export - use 0 for unlimited.
		$logs = $logs_query->query(
			array(
				'number'  => 0, // 0 means no limit.
				'order'   => 'DESC',
				'orderby' => 'date_created',
			)
		);

		// Generate CSV content.
		$csv_data = "ID,Date Created,Level,Message\n";

		if ( ! empty( $logs ) ) {
			foreach ( $logs as $log ) {
				// Escape quotes and newlines in message.
				$message = str_replace( '"', '""', $log->message );
				$message = str_replace( array( "\r\n", "\r", "\n" ), ' ', $message );

				$csv_data .= sprintf(
					'"%s","%s","%s","%s"' . "\n",
					$log->id,
					$log->date_created,
					$log->level,
					$message
				);
			}
		}

		// Set headers for download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="logs-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output UTF-8 BOM for Excel compatibility.
		echo "\xEF\xBB\xBF";
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV data is properly sanitized above.
		echo $csv_data;
		exit;
	}
}
