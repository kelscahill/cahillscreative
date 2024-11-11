<?php

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Fields\Settings as Fields_Settings;

class Upgrade_3_0_4 {

	public static function upgrade() {

		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );
		$fields_records = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);
		foreach ( $fields_records as $field_record ) {

			// Use the processed settings class to parse the existing attributes against
			// the settings and their dependencies.
			$processed_settings = Fields_Settings::get_processed_settings( $field_record->get_attributes() );
			$new_attributes     = $processed_settings->get_attributes();

			// Update the new attributes in the record.
			$record = array(
				'attributes' => wp_json_encode( (object) $new_attributes ),
			);

			$query  = new \Search_Filter\Database\Queries\Fields();
			$result = $query->update_item( $field_record->get_id(), $record );
		}

		// Resave styles.
		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Now build the CSS once.
		CSS_Loader::save_css();
	}

	public static function disable_css_save() {
		return false;
	}
}
