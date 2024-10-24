<?php

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependencies stubs.
 */
class Stub_Search_Autocomplete extends Stub {
	public static $type       = 'search';
	public static $input_type = 'autocomplete';
}
