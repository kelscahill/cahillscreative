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
 * Toggle field for binary filtering options.
 */
class Toggle extends Choice {

	/**
	 * Field icons.
	 *
	 * @var array
	 */
	public $icons = array();

	/**
	 * Supported features.
	 *
	 * @var array
	 */
	public $supports = array(
		'autoSubmit',
	);

	/**
	 * Get the label for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Toggle', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by using a toggle.' );
	}

	/**
	 * Supported styles for toggle field.
	 *
	 * @var array
	 */
	public static $styles = array(
		'inputColor'                 => true,
		'inputBackgroundColor'       => true,
		'inputBorder'                => true,
		'inputBorderHoverColor'      => true,
		'inputBorderFocusColor'      => true,
		'inputIconColor'             => true,
		'inputActiveIconColor'       => true,
		'inputInactiveIconColor'     => true,
		'inputClearColor'            => true,
		'inputClearHoverColor'       => true,
		'inputShadow'                => true,
		'inputPadding'               => true,
		'inputGap'                   => true,

		'labelColor'                 => true,
		'labelBackgroundColor'       => true,
		'labelPadding'               => true,
		'labelMargin'                => true,
		'labelScale'                 => true,

		'descriptionColor'           => true,
		'descriptionBackgroundColor' => true,
		'descriptionPadding'         => true,
		'descriptionMargin'          => true,
		'descriptionScale'           => true,
	);

	/**
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST
		// but with wp_unslash already applied.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = sanitize_text_field( $request_var ?? '' );

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}
