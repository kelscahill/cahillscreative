<?php
/**
 * Submit Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Control
 */

namespace Search_Filter\Fields\Control;

use Search_Filter\Fields\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Submit extends Control {

	/**
	 * The input type name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'submit';

	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array();

	/**
	 * List of styles the input type supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		// 'inputSelectedColor',
		// 'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
	);

	/**
	 * Get the label for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Submit', 'search-filter' );
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		return '';
	}
}
