<?php
/**
 * Upgrade routine for version 3.2.0 Beta.
 *
 * @package Search_Filter
 * @since 3.2.0
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Features;
use Search_Filter\Fields;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.2.0 Beta 11.
 *
 * @since 3.2.0
 */
class Upgrade_3_2_0_Beta_11 extends Upgrade_Base {

	/**
	 * Performs the upgrade routine for version 3.2.0 Beta.
	 *
	 * @since 3.2.0
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {

		if ( Asset_Loader::get_db_version() < 2 ) {
			// Disables saving CSS if the user is still on the previous assets version.
			add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );
		}

		// We need to add the setting `compatibility->cssIncreaseSpecificity` and set it to 'no' if it does not exist.
		// We also need to add the  setting  `compatibility->popoverNode` and set it to 'body' - to match pre-v3.2.0 behaviour.
		$compatibility_settings                           = Options::get_direct( 'compatibility' ) ?? array();
		$compatibility_settings['cssIncreaseSpecificity'] = 'no';
		$compatibility_settings['popoverNode']            = 'body';

		// Migrate existing CSS setting.
		$has_css_specificity_enabled = Features::is_enabled( 'cssIncreaseSpecificity' );
		if ( $has_css_specificity_enabled ) {
			$compatibility_settings['cssIncreaseSpecificity'] = 'yes';
		}

		// Update the compatibility settings.
		Options::update( 'compatibility', $compatibility_settings );

		$fields = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);
		foreach ( $fields as $field_record ) {

			if ( is_wp_error( $field_record ) ) {
				continue;
			}

			$has_updated = false;

			// Update filter type to choice.
			$attributes = $field_record->get_attributes();

			if ( ! isset( $attributes['type'] ) ) {
				continue;
			}

			// Add `inputEnableSearch` => `yes` to existing select fields.
			if ( $attributes['type'] === 'choice' ) {
				if ( ! isset( $attributes['inputType'] ) ) {
					continue;
				}
				if ( $attributes['inputType'] === 'select' ) {
					$attributes['inputEnableSearch'] = 'yes';
					$has_updated                     = true;
				}
			}

			if ( $attributes['type'] === 'control' ) {
				if ( ! isset( $attributes['controlType'] ) ) {
					continue;
				}
				if ( $attributes['controlType'] === 'sort' || $attributes['controlType'] === 'per_page' ) {
					$attributes['inputEnableSearch'] = 'yes';
					$has_updated                     = true;
				}
			}

			if ( $has_updated ) {
				// Update the new attributes in the record.
				$record = array(
					'attributes' => wp_json_encode( (object) $attributes ),
				);

				$query  = new \Search_Filter\Database\Queries\Fields();
				$result = $query->update_item( $field_record->get_id(), $record );
			}
		}

		return Upgrade_Result::success();
	}
}
