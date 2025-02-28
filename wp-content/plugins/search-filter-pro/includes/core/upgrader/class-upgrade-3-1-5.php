<?php

namespace Search_Filter_Pro\Core\Upgrader;

class Upgrade_3_1_5 {

	public static function upgrade() {
		// Delete option 'license-server' as we no longer need it.
		\Search_Filter\Options::delete_option( 'license-server' );
	}
}
