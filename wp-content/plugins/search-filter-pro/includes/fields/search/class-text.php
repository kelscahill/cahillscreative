<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Search;

use Search_Filter\Util;
use Search_Filter_Pro\Fields\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Text extends Search {


	/**
	 * List of styles the input type supports.
	 *
	 * @var array
	 */
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
	 * The input type.
	 *
	 * @since    3.0.0
	 *
	 * @var      string
	 */
	public static $input_type = 'text';

	/**
	 * The support icons.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public $icons = array(
		'search',
		'clear',
	);

	/**
	 * Gets the label for the field.
	 *
	 * @since    3.0.0
	 *
	 * @return   string
	 */
	public static function get_label() {
		return __( 'Text', 'search-filter' );
	}

	/**
	 * The data support for the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $data_support = array(
		array(
			'dataType'          => 'post_attribute',
			'dataPostAttribute' => array( 'default', 'post_type', 'post_status' ),
		),
		array(
			'dataType' => 'taxonomy',
		),
		array(
			'dataType' => 'custom_field',
		),
		array(
			'dataType' => 'acf_field',
		),
	);

	/**
	 * The setting support for the field.
	 *
	 * @since    3.0.0
	 *
	 * @var      array
	 */
	public static $setting_support = array(
		'placeholder'            => true,
		'inputShowIcon'          => true,
		'autoSubmit'             => true,
		'autoSubmitDelay'        => true,
		'showLabel'              => true,
		'labelInitialVisibility' => true,
		'labelToggleVisibility'  => true,
	);
	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		parent::init();

		$value       = $this->get_value();
		$render_data = array(
			'uid'   => self::get_instance_id( 'text' ),
			'value' => $value,
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'uid'         => 'absint',
			'value'       => 'esc_attr',
			'placeholder' => 'esc_attr',
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
		$url_name = 's';
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
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
		// Support multibyte space as a single space.
		$value = str_replace( '　', ' ', $value );
		// And trim any whitespace.
		$value = trim( $value );
		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}

