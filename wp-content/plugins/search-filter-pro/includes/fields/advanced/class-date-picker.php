<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Advanced;

use Search_Filter\Fields\Advanced;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Date_Picker extends Advanced {

	/**
	 * The supported icons.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $icons = array(
		'event',
	);

	/**
	 * The supports.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $supports = array(
		'autoSubmit',
	);

	/**
	 * The styles.
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
		'inputInteractiveColor',
		'inputInteractiveHoverColor',
		'inputClearColor',
		'inputClearHoverColor',
	);

	/**
	 * The type of the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $type = 'advanced';

	/**
	 * The input type.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $input_type = 'date_picker';

	/**
	 * The setting support.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $setting_support = array(
		'autoSubmit'              => true,
		'autoSubmitDelay'         => true,
		'showLabel'               => true,
		'labelInitialVisibility'  => true,
		'labelToggleVisibility'   => true,
		'placeholder'             => true,
		'dateDisplayFormat'       => true,
		'dateDisplayFormatCustom' => true,
		'dateShowMonth'           => true,
		'dateShowYear'            => true,
		'inputShowIcon'           => true,
	);

	/**
	 * The data support.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static function get_label() {
		return __( 'Date Picker', 'search-filter' );
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
			'uid'   => self::get_instance_id( 'range-date-picker' ),
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
