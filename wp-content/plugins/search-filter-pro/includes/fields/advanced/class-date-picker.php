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
use Search_Filter\Fields\Advanced\Date_Picker as Base_Date_Picker;
use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Date_Picker extends Base_Date_Picker {

	/**
	 * The supported icons.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $icons = array(
		'event',
		'clear',
	);

	/**
	 * The supports.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $supports = array();

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
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		if ( ! method_exists( '\Search_Filter\Util', 'get_request_var' ) ) {
			return;
		}
		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = $request_var !== null ? urldecode_deep( sanitize_text_field( wp_unslash( $request_var ) ) ) : '';

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}
