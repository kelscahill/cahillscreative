<?php

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Options;

class Upgrade_3_1_0 {

	public static function upgrade() {

		delete_option( 'search_filter_css_mode' );
		delete_option( 'search_filter_css_version_id' );

		// Move some of the WP options into our own table.
		Options::update_option_value( 'css-mode', 'file-system' );
		Options::update_option_value( 'css-version-id', 1 );
	}

	public static function disable_css_save() {
		return false;
	}
}
