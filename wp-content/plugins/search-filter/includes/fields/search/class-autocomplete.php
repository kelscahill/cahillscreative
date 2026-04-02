<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter\Fields\Search;

use Search_Filter\Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Autocomplete extends Field {

	/**
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		return 'search';
	}

	/**
	 * The input type.
	 *
	 * @since    3.2.0
	 *
	 * @var      string
	 */
	public static $input_type = 'autocomplete';

	/**
	 * The type of field.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	public static $type = 'search';

	/**
	 * Gets the label for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public static function get_label() {
		return __( 'Autocomplete', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to search with autocomplete suggestions.', 'search-filter' );
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
