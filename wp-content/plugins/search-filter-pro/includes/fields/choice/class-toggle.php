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
use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Toggle extends Choice {

	public $icons = array();

	public $supports = array(
		'autoSubmit',
	);
	public static function get_label() {
		return __( 'Toggle', 'search-filter' );
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

	public function __construct() {
		parent::__construct();
		$this->set_labels(
			array(
				'name' => __( 'Toggle', 'search-filter-pro' ),
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
			'uid'   => self::get_instance_id( 'toggle' ),
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
