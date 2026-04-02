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

use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Bucket\Updater as Bucket_Updater;
use Search_Filter_Pro\Indexer\Bucket\Query as Bucket_Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the field counts for the indexer.
 *
 * @since 3.0.0
 */
class Indexer {


	/**
	 * Get the field value matched IDs.
	 *
	 * Figures out which IDs to query against for generating counts based
	 * on the field relationship setting and match mode.
	 *
	 * @since 3.0.0
	 *
	 * @param    \Search_Filter\Fields\Field $field    The field to get the IDs for.
	 * @param    string                      $field_relationship    The field relationship.
	 * @param    Indexer_Query               $indexer_query    The indexer query.
	 * @return   \Search_Filter_Pro\Indexer\Bitmap    The field value matched bitmap.
	 */
	private static function get_field_range_value_matched_bitmap( $field, $field_relationship, $indexer_query ) {

		$unfiltered_result_bitmap = $indexer_query->get_unfiltered_result_bitmap();

		// Get combined bitmap excluding this field.
		$excluding_field_result_bitmap = $indexer_query->get_combined_result_field_bitmaps_excluding( $field->get_id() );

		if ( $field_relationship === 'all' ) {

			// Field relationship is set to 'all', match mode 'any'.

			// If we require any match, then we need to get the IDs of all the other fields
			// combined and intersect that with the unfiltered result IDs.

			if ( $excluding_field_result_bitmap && ! $excluding_field_result_bitmap->is_empty() ) {
				return $excluding_field_result_bitmap->intersect( $unfiltered_result_bitmap );
			} else {
				// If combined bitmap was empty then there were no fields applied to the query other than
				// possibly the current field.
				return $unfiltered_result_bitmap;
			}
		}

		// Field relationship is set to 'any'.
		// We can get this from the indexer query as the combine has already been done.
		// Combine with the unfiltered result IDs if its set, will be null if there are no active values.
		if ( $excluding_field_result_bitmap && ! $excluding_field_result_bitmap->is_empty() ) {
			return $excluding_field_result_bitmap->intersect( $unfiltered_result_bitmap );

		}
		return $unfiltered_result_bitmap;
	}

	/**
	 * Get filtered min/max values from the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Fields\Field $field              The field to get min/max for.
	 * @param Indexer_Query               $indexer_query      The indexer query.
	 * @param string                      $field_relationship The field relationship setting.
	 * @return array The min/max values.
	 */
	public static function get_filtered_min_max( $field, $indexer_query, $field_relationship ) {

		$field_id = absint( $field->get_id() );

		// Use bucket system for min/max calculation.
		if ( Bucket_Updater::has_field_data( $field_id ) ) {

			// Get result bitmap from indexer query..
			$result_bitmap = self::get_field_range_value_matched_bitmap( $field, $field_relationship, $indexer_query );

			// Get min/max using bucket system.
			$min_max_result = Bucket_Query::get_min_max( $field_id, $result_bitmap );

			return array(
				'min' => $min_max_result['min'],
				'max' => $min_max_result['max'],
			);
		}

		return array(
			'min' => null,
			'max' => null,
		);
	}

	/**
	 * Get unfiltered min/max values from the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param \Search_Filter\Fields\Field $field         The field to get min/max for.
	 * @param Indexer_Query               $indexer_query The indexer query.
	 * @return array The min/max values.
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

		$unfiltered_bitmap = $indexer_query->get_unfiltered_result_bitmap();

		// Feature flag check.

		if ( ! $unfiltered_bitmap || $unfiltered_bitmap->is_empty() ) {
			return $return_values;
		}

		if ( Bucket_Updater::has_field_data( $field_id ) ) {
			// Use bucket system for unfiltered min/max.
			// Pass unfiltered_bitmap to get min/max within the unfiltered result set.
			if ( $autodetect_min === 'yes' || $autodetect_max === 'yes' ) {
				$min_max_result = Bucket_Query::get_min_max( $field_id, $unfiltered_bitmap );

				if ( $autodetect_min === 'yes' ) {
					$return_values['min'] = $min_max_result['min'];
				}
				if ( $autodetect_max === 'yes' ) {
					$return_values['max'] = $min_max_result['max'];
				}
			}
		}

		return $return_values;
	}
}
