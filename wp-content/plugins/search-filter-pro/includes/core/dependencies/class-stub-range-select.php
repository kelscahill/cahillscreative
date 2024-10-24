<?php

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependencies stubs.
 */
class Stub_Range_Select extends Stub {
	public static $type       = 'range';
	public static $input_type = 'select';
}
