<?php
/**
 * Stub for autocomplete search field compatibility.
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
 * Stub for autocomplete search field to prevent fatal errors during upgrades.
 */
class Stub_Search_Autocomplete extends Stub {
	/**
	 * Field type.
	 *
	 * @var string
	 */
	public static $type = 'search';

	/**
	 * Input type.
	 *
	 * @var string
	 */
	public static $input_type = 'autocomplete';
}
