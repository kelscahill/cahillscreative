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
class Reset extends Control {

	/**
	 * The input type name.
	 *
	 * @var string
	 */
	public static $input_type = 'reset';

	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		// 'inputSelectedColor',
		// 'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
	);

	public static function get_label() {
		return __( 'Reset', 'search-filter' );
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
