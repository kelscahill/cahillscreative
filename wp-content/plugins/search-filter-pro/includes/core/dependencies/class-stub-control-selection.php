<?php

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependencies stubs.
 */
class Stub_Control_Selection extends Stub {
	public static $type       = 'control';
	public static $input_type = 'selection';
}
