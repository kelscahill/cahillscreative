<?php

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Options;

class Upgrade_3_1_3 {

	public static function upgrade() {

		// Reset the WPML integration if its enabled to support the new WPML extension.
		$integrations = Options::get_option_value( 'integrations' );
		if ( ! $integrations ) {
			return;
		}

		if ( isset( $integrations['wpml'] ) ) {
			if ( $integrations['wpml'] === true ) {
				$integrations['wpml'] = false;
				Options::update_option_value( 'integrations', $integrations );
			}
		}
	}

	public static function disable_css_save() {
		return false;
	}
}
