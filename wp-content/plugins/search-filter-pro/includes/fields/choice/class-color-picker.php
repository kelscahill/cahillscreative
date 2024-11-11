<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Choice;

use Search_Filter\Fields\Choice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Color_Picker extends Choice {

	public $icons = array();

	public $supports = array(
		'autoSubmit',
	);

	public static function get_label() {
		return __( 'Color Picker', 'search-filter' );
	}

	public static $styles = array(
		'inputColor',
		'inputBackgroundColor',
		'inputBorderColor',
		'inputBorderHoverColor',
		'inputBorderFocusColor',
		'inputIconColor',
		'inputActiveIconColor',
		'inputInactiveIconColor',
		'inputClearColor',
		'inputClearHoverColor',
	);

	public function __construct() {
		parent::__construct();
		$this->set_labels(
			array(
				'name' => __( 'Color Picker', 'search-filter-pro' ),
			)
		);
	}

	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		parent::init();

		$value       = $this->get_value();
		$render_data = array(
			'uid'   => self::get_instance_id( 'color-picker' ),
			'value' => $value,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'uid'   => 'absint',
			'value' => 'esc_attr',
		);
		$this->set_render_escape_callbacks( $esc_callbacks );

	}
	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}
		$url_name = '';
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();
		$value          = isset( $_GET[ $url_param_name ] ) ? urldecode_deep( sanitize_text_field( wp_unslash( $_GET[ $url_param_name ] ) ) ) : '';

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}
