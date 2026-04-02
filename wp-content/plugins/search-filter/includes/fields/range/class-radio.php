<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter\Fields\Range;

use Search_Filter\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Radio extends Field {

	/**
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		return 'range';
	}

	/**
	 * The input type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'radio';

	/**
	 * The type of field.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	public static $type = 'range';

	/**
	 * The type of the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public static function get_label() {
		return __( 'Radio', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by ranges using radio buttons.', 'search-filter' );
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
