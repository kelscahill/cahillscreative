<?php
/**
 * This class setups stubs for the input types, to prevent fatal errors when
 * upgrading to beta-2.
 */

namespace Search_Filter_Pro\Core\Dependencies;

use Search_Filter\Fields\Field_Factory;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependencies stubs.
 */
class Stubs {

	/**
	 * Init the dependencies stubs.
	 */
	public static function init() {
		add_action( 'search-filter/fields/register', array( __CLASS__, 'register_fields' ), 10 );

	}

	public static function register_fields() {
		// Load stubs to prevent fatal errors if S&F free is still on 3.0.0-beta-2.

		Field_Factory::register_field_input( 'search', 'autocomplete', 'Search_Filter_Pro\Core\Dependencies\Stub_Search_Autocomplete' );
		Field_Factory::register_field_input( 'control', 'selection', 'Search_Filter_Pro\Core\Dependencies\Stub_Control_Selection' );
		Field_Factory::register_field_input( 'control', 'load_more', 'Search_Filter_Pro\Core\Dependencies\Stub_Control_Load_More' );

		Field_Factory::register_field_input( 'range', 'slider', 'Search_Filter_Pro\Core\Dependencies\Stub_Range_Slider' );
		Field_Factory::register_field_input( 'range', 'select', 'Search_Filter_Pro\Core\Dependencies\Stub_Range_Select' );
		Field_Factory::register_field_input( 'range', 'radio', 'Search_Filter_Pro\Core\Dependencies\Stub_Range_Radio' );
		Field_Factory::register_field_input( 'range', 'number', 'Search_Filter_Pro\Core\Dependencies\Stub_Range_Number' );
		Field_Factory::update_field_input( 'advanced', 'date_picker', 'Search_Filter_Pro\Core\Dependencies\Stub_Advanced_Date_Picker' );
	}
}
