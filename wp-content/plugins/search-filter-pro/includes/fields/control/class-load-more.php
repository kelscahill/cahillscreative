<?php
/**
 * Submit Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Control
 */

namespace Search_Filter_Pro\Fields\Control;

use Search_Filter\Fields\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Load_More extends Control {

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
	 * The supported icons.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $icons = array(
		'spinner-circle',
	);

	/**
	 * The supported styles.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputSelectedColor',
		'inputSelectedBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
	);

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
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		parent::init();

		$render_data = array(
			'isPressed' => false,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'label'     => 'esc_html',
			'isPressed' => 'boolval',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );
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
