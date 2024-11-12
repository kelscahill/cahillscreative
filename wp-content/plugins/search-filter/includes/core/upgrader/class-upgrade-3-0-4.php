<?php

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Fields\Settings as Fields_Settings;

class Upgrade_3_0_4 {

	public static function upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );

		/*
		 * Due to changes in the way we tidy up unused attributes, we need to do an
		 * initial pass and remove any unused attributes, otherwise we'll trigger
		 * some unexpected conditions with field dependencies.
		 *
		 * We have to be careful though, if we run this and S&F Pro is disabled, then we'll lose
		 * a lot of valid attributes.  Lets first check to see if the plugin exists (is installed,
		 * regardless of whether it's enabled or not) and if we have the version number set in
		 * the DB, if its set, but the plugin is disabled then skip this upgrade and defer it to
		 * the pro plugin upgrade routine.
		 */
		if ( ! \Search_Filter\Core\Dependants::is_search_filter_pro_installed() ) {
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
			$old_sticky_posts = $query->get_attribute( 'stickyPosts' );

			if ( empty( $old_sticky_posts ) ) {
				continue;
			}

			if ( $old_sticky_posts === 'default' ) {
				$query->set_attribute( 'stickyPosts', 'show' );
				$query->save();
				continue;
			}
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
