<?php
/**
 * Selection Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Control;

use Search_Filter\Fields\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Selection extends Control {
	public static $input_type = 'selection';
	// public static $control_type = 'selection';
	public static $type = 'control';

	public $icons = array(
		'clear',
	);

	public static $styles       = array(
		'inputColor',
		'inputBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputClearColor',
		'inputClearHoverColor',

		'labelColor',
		'labelBackgroundColor',
		'labelPadding',
		'labelMargin',
		'labelScale',

		'descriptionColor',
		'descriptionBackgroundColor',
		'descriptionPadding',
		'descriptionMargin',
		'descriptionScale',
	);
	public static $data_support = array();

	public static $setting_support = array(
		'showLabel'              => true,
		'labelInitialVisibility' => true,
		'labelToggleVisibility'  => true,
	);

	public static function get_label() {
		return __( 'Selection', 'search-filter' );
	}
	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {

		parent::init();

		// Setup extra render data for options.
		$options        = $this->get_options();
		$render_options = array();
		$values         = $this->get_values();

		// TOOD - this should be moved upto checkable class and IDs need to use
		// a global ID generation function.
		foreach ( $options as $option ) {
			$is_pressed = false;
			if ( in_array( $option['value'], $values, true ) ) {
				$is_pressed = true;
			}
			$render_options[] = array(
				'label'     => $option['label'],
				'isPressed' => $is_pressed,
			);
		}

		$render_data = array(
			'options' => $render_options,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'options' => array(
				'label'     => 'esc_html',
				'value'     => 'esc_attr',
				'isPressed' => 'boolval',
			),
		);
		$this->set_render_escape_callbacks( $esc_callbacks );

	}
}
