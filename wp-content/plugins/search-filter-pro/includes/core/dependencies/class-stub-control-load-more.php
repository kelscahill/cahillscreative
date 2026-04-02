<?php
/**
 * Stub for load more control field compatibility.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Core/Dependencies
 */

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stub for load more control field to prevent fatal errors during upgrades.
 */
class Stub_Control_Load_More extends Stub {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public static $input_type = 'load_more';
}
