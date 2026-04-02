<?php
/**
 * Stub for radio range field compatibility.
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
 * Stub for radio range field to prevent fatal errors during upgrades.
 */
class Stub_Range_Radio extends Stub {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public static $type = 'range';

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public static $input_type = 'radio';
}
