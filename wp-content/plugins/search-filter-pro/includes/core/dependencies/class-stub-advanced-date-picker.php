<?php
/**
 * Stub for advanced date picker field compatibility.
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
 * Stub for advanced date picker field to prevent fatal errors during upgrades.
 */
class Stub_Advanced_Date_Picker extends Stub {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public static $type = 'advanced';

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public static $input_type = 'date_picker';
}
