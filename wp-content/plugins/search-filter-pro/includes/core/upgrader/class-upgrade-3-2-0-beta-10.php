<?php
/**
 * Upgrade routines for version 3.2.0
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Fields;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.2.0 Beta 10.
 */
class Upgrade_3_2_0_Beta_10 extends Upgrade_Base {

	/**
	 * Run the upgrade.
	 *
	 * @since 3.2.0
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		if ( Asset_Loader::get_db_version() < 2 ) {
			// Disables saving CSS if the user is still on the previous assets version.
			add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );
		}

		// Handle feature defaults for existing users.
		$features = Options::get_direct( 'features' );
		if ( is_array( $features ) ) {
			// Some versions of the 3.2.0 beta allow us to enable/disable beta features.
			// but this is no longer possible. We need to ensure that that beta features
			// are unset, or enabled, so they fallback to their default of "enabled".
			if ( isset( $features['betaFeatures'] ) ) {
				$features['betaFeatures'] = true;
			}

			Options::update( 'features', $features );
		}

		// Enable autoSubmitOnType for existing autocomplete fields to preserve current behavior.
		$fields = Fields::find(
			array(
				'number' => 0,
			)
		);

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$input_type = $field->get_attribute( 'inputType' );

			// Only update autocomplete fields.
			if ( $input_type !== 'autocomplete' ) {
				continue;
			}

			// Enable autoSubmitOnType to preserve existing behavior.
			$field->set_attribute( 'autoSubmitOnType', 'yes' );
			$field->save();
		}

		return Upgrade_Result::success();
	}
}
