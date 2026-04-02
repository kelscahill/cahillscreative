<?php
/**
 * Description of class
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Rest_API;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility helper functions for REST API operations.
 *
 * @since 3.0.0
 */
class Util {

	/**
	 * Get the count of records for each status.
	 *
	 * @since 3.0.0
	 *
	 * @param object $query      The query object.
	 * @param array  $extra_args Optional. Additional query arguments. Default empty array.
	 * @return array Array of status counts.
	 */
	public static function get_records_section_status_counts( $query, $extra_args = array() ) {
		// Figure out how many results there are for each record status -
		// Enabled, Disabled, Trashed.
		$status_count = array(
			'enabled'  => 0,
			'disabled' => 0,
			'trashed'  => 0,
		);

		foreach ( $status_count as $status => $count ) {
			$args            = array(
				'count'  => true,
				'status' => $status,
			);
			$args            = wp_parse_args( $extra_args, $args );
			$args['context'] = '';

			$count_query             = $query->query( $args );
			$status_count[ $status ] = $count_query;
		}

		return $status_count;
	}

	/**
	 * Prepare query arguments for fetching records.
	 *
	 * @since 3.0.0
	 *
	 * @param array $extra_args Optional. Additional query arguments. Default empty array.
	 * @return array Prepared query arguments.
	 */
	public static function get_records_query_args( $extra_args = array() ) {
		$per_page = 10;
		if ( isset( $extra_args['per_page'] ) ) {
			$per_page = $extra_args['per_page'];
			unset( $extra_args['per_page'] );
		}

		$paged = 1;
		if ( isset( $extra_args['paged'] ) ) {
			$paged = $extra_args['paged'];
			unset( $extra_args['paged'] );
			$extra_args['offset'] = ( $paged - 1 ) * $per_page;
		}

		// If no status is applied, then default to enabled.
		if ( ! isset( $extra_args['status'] ) ) {
			$status               = array( 'enabled' );
			$extra_args['status'] = $status;
		}

		$defaults   = array(
			'number'        => absint( $per_page ),
			'no_found_rows' => false, // We want the total, for pagination.
		);
		$query_args = wp_parse_args( $extra_args, $defaults );
		return $query_args;
	}
}
