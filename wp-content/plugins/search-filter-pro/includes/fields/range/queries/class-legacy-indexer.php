<?php
/**
 * Field queries for indexer counts.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Indexer
 */

namespace Search_Filter_Pro\Fields\Range\Queries;

use Search_Filter_Pro\Indexer\Legacy\Query as Legacy_Indexer_Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the field counts for the indexer.
 *
 * @since 3.0.0
 */
class Legacy_Indexer {


	/**
	 * Get the field value matched IDs.
	 *
	 * Figures out which IDs to query against for generating counts based
	 * on the field relationship setting and match mode.
	 *
	 * @since 3.0.0
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to get the IDs for.
	 * @param    array                       $unfiltered_result_ids    The unfiltered result IDs.
	 * @param    string                      $field_relationship    The field relationship.
	 * @param    Legacy_Indexer_Query        $indexer_query    The indexer query.
	 * @return   array    The field value matched IDs.
	 */
	private static function get_field_range_value_matched_ids( $field, $unfiltered_result_ids, $field_relationship, $indexer_query ) {

		if ( $field_relationship === 'all' ) {

			// Field relationship is set to 'all', match mode 'any'.

			// If we require any match, then we need to get the IDs of all the other fields
			// combined and intersect that with the unfiltered result IDs.
			$combined_result_ids = $indexer_query->get_combined_result_field_ids_excluding( $field->get_id() );

			if ( $combined_result_ids !== null ) {
				$combined_result_ids = Legacy_Indexer_Query::array_intersect( $combined_result_ids, $unfiltered_result_ids );
			} else {
				// If combined IDs is null then there were no fields applied to the query other than
				// possibly the current field.
				$combined_result_ids = $unfiltered_result_ids;
			}

			return $combined_result_ids;
		}

		// Field relationship is set to 'any'.

		// We can get this from the indexer query as the combine has already been done.
		$field_result_ids = $indexer_query->get_field_result_ids( $field->get_id() );
		// Combine with the unfiltered result IDs if its set, will be null if there are no active values.
		$result_ids = $unfiltered_result_ids;
		if ( $field_result_ids !== null ) {
			$result_ids = Legacy_Indexer_Query::array_intersect( $field_result_ids, $unfiltered_result_ids );
		}
		return $result_ids;
	}

	/**
	 * Get the filtered min and max values for a range field.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Fields\Field $field The field to get min/max for.
	 * @param Legacy_Indexer_Query        $indexer_query The indexer query.
	 * @param string                      $field_relationship The field relationship (all|any).
	 * @return array Array with 'min' and 'max' keys.
	 */
	public static function get_filtered_min_max( $field, $indexer_query, $field_relationship ) {
		// There is no cached item in the DB so build it and store it.
		$unfiltered_result_ids = $indexer_query->get_unfiltered_result_ids();

		// Figure out which IDs we need to compare against.
		$result_ids = self::get_field_range_value_matched_ids( $field, $unfiltered_result_ids, $field_relationship, $indexer_query );

		// Sanitize IDs.
		$result_ids = array_map( 'absint', $result_ids );
		$field_id   = absint( $field->get_id() );

		if ( empty( $result_ids ) ) {
			// Then we have no results to combine with, use ID of 0 to force no matches.
			$result_ids = array( 0 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'search_filter_index';

		// Create placeholders for IN clause.
		$placeholders = implode( ',', array_fill( 0, count( $result_ids ), '%d' ) );

		// Prepare the query with proper placeholders.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic IN clause with safe %d placeholders.
		$sql = $wpdb->prepare(
			"SELECT MIN( CAST( value AS DECIMAL(12,6) ) ) as rangeMin, MAX( CAST( value AS DECIMAL(12,6) ) ) as rangeMax FROM %i
			WHERE field_id = %d
			AND value != ''
			AND object_id IN ($placeholders)",
			array_merge( array( $table_name, $field_id ), $result_ids )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Performance-critical query for range filtering, query is prepared above.
		$cached_min_max = $wpdb->get_row( $sql, ARRAY_A );

		return array(
			'min' => $cached_min_max['rangeMin'],
			'max' => $cached_min_max['rangeMax'],
		);
	}

	/**
	 * Get the unfiltered min and max values for a range field.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Fields\Field $field The field to get min/max for.
	 * @param Legacy_Indexer_Query        $indexer_query The indexer query.
	 * @return array Array with 'min' and 'max' keys.
	 */
	public static function get_unfiltered_min_max( $field, $indexer_query ) {
		// Run the query for min/max on the index table.
		$field_id = $field->get_id();

		$autodetect_min = $field->get_attribute( 'rangeAutodetectMin' );
		$autodetect_max = $field->get_attribute( 'rangeAutodetectMax' );

		$return_values = array(
			'min' => $field->get_attribute( 'rangeMin' ),
			'max' => $field->get_attribute( 'rangeMax' ),
		);

		// We need to get the list of unfiltered IDs for situations like archives, where the query has been
		// filtered, but not by one of our fields.
		$unfiltered_result_ids = $indexer_query->get_unfiltered_result_ids();
		$result_ids            = array_map( 'absint', $unfiltered_result_ids );
		if ( empty( $result_ids ) ) {
			// Then we have no results to combine with, return default values.
			return $return_values;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'search_filter_index';

		// Create placeholders for IN clause.
		$placeholders = implode( ',', array_fill( 0, count( $result_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance-critical queries for range filtering.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic IN clause with safe %d placeholders.
		if ( $autodetect_min === 'yes' && $autodetect_max === 'yes' ) {
			$min_max = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT MIN( CAST( value AS DECIMAL(12,6) ) ) as rangeMin,
					MAX( CAST( value AS DECIMAL(12,6) ) ) as rangeMax
					FROM %i
					WHERE field_id = %d
					AND value != ''
					AND object_id IN ($placeholders)",
					array_merge( array( $table_name, $field_id ), $result_ids )
				),
				ARRAY_A
			);
			if ( $min_max ) {
				$return_values['min'] = $min_max['rangeMin'];
				$return_values['max'] = $min_max['rangeMax'];
			}
		} elseif ( $autodetect_min === 'yes' ) {
			$min = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MIN( CAST( value AS DECIMAL(12,6) ) ) FROM %i
					WHERE field_id = %d
					AND value != ''
					AND object_id IN ($placeholders)",
					array_merge( array( $table_name, $field_id ), $result_ids )
				)
			);
			if ( $min !== null ) {
				$return_values['min'] = $min;
			}
		} elseif ( $autodetect_max === 'yes' ) {
			$max = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX( CAST( value AS DECIMAL(12,6) ) ) FROM %i
					WHERE field_id = %d
					AND value != ''
					AND object_id IN ($placeholders)",
					array_merge( array( $table_name, $field_id ), $result_ids )
				)
			);
			if ( $max !== null ) {
				$return_values['max'] = $max;
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return $return_values;
	}
}
