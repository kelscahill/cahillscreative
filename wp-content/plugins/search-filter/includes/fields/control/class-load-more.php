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

use Search_Filter\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Load_More extends Field {

	/**
	 * The input type.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $input_type = 'load_more';


	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Gets the label for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public static function get_label() {
		return __( 'Load more', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to load more results after their current results list.' );
	}

	/**
	 * If this field rquires pro or not.
	 *
	 * @since    3.0.0
	 *
	 * @var      bool
	 */
	protected static $requires_pro = true;
}
