<?php

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter_Pro\Indexer\Query_Cache;
use Search_Filter\Fields;

class Upgrade_3_1_0 {

	public static function upgrade() {

		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );

		$fields = Fields::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $fields as $field ) {

			if ( is_wp_error( $field ) ) {
				continue;
			}

			// Check for range fields.
			if ( $field->get_attribute( 'type' ) !== 'range' ) {
				continue;
			}

			// Now check to see if autodetec min/max is set. If so, delete
			// any min/max that might be present.
			if ( $field->get_attribute( 'rangeAutodetectMin' ) === 'yes' ) {
				$field->delete_attribute( 'rangeMin' );
			}

			if ( $field->get_attribute( 'rangeAutodetectMax' ) === 'yes' ) {
				$field->delete_attribute( 'rangeMax' );
			}
		}

		// Resave queries.
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			// Clear query caches.
			Query_Cache::clear_caches_by_query_id( $query->get_id() );
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );
	}
}
