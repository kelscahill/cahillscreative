<?php
/**
 * Selection Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields\Control;

use Search_Filter\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Selection control class for displaying active filters.
 *
 * @since 3.0.0
 */
class Selection extends Field {

	/**
	 * The input type name.
	 *
	 * @var string
	 */
	public static $input_type = 'selection';

	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Get the label for the input type.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Selection', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users see their current active filters and remove them.' );
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
