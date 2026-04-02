<?php
/**
 * Upgrade routines for version 3.0.4
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Fields\Settings as Fields_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.0.4
 */
class Upgrade_3_0_4 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.0.4
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		$fields_records = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);

		$errors = array();
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

			if ( false === $result ) {
				$errors[] = sprintf( 'Field %d: Update returned false', $field_record->get_id() );
			}
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Now build the CSS once.
		CSS_Loader::save_css();

		if ( ! empty( $errors ) ) {
			return Upgrade_Result::failed( 'Some fields failed to update', $errors );
		}

		return Upgrade_Result::success();
	}

	/**
	 * Disable CSS save during upgrade.
	 *
	 * @since 3.0.4
	 * @return bool
	 */
	public static function disable_css_save() {
		return false;
	}
}
