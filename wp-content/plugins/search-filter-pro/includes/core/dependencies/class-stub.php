<?php

namespace Search_Filter_Pro\Core\Dependencies;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependencies stubs.
 */
class Stub extends \Search_Filter\Fields\Field {

	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array();
	public static $type            = 'control';
	public static $input_type      = 'submit';

	public static function get_setting_support() {
		return self::$setting_support;
	}

	public static $styles = array();

	public static function get_styles_support() {
		return self::$styles;
	}

	public static $data_support = array();
	public static function get_data_support() {
		return self::$data_support;
	}
	public static function get_label() {
		return 'rand_' . rand( 1, 100000 );
	}
	public $icons = array();
	// The beta used a static method, the release doesn't.
	public static function get_icons() {
		return self::$icons;
	}

}
