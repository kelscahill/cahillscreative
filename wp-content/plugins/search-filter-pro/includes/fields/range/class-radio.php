<?php
/**
 * Text Search Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Range;

use Search_Filter_Pro\Fields\Range;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Radio extends Range {

	/**
	 * The supported icons.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $icons = array(
		'radio',
		'radio-checked',
	);

	/**
	 * The supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $supports = array(
		// 'autoSubmit',
	);

	/**
	 * The styles.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $styles = array(
		'inputColor',
		'inputActiveIconColor',
		'inputInactiveIconColor',
	);

	/**
	 * The input type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'radio';

	/**
	 * The setting support.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'autoSubmit'             => true,
		'autoSubmitDelay'        => true,
		'showLabel'              => true,
		'labelInitialVisibility' => true,
		'labelToggleVisibility'  => true,
		'rangeAutodetectMin'     => true,
		'rangeAutodetectMax'     => true,
		'rangeMin'               => true,
		'rangeMax'               => true,
		'rangeStep'              => true,
		'rangeDecimalPlaces'     => true,
		'rangeDecimalCharacter'  => true,
		'rangeThousandCharacter' => true,
		'rangeValuePrefix'       => true,
		'rangeValueSuffix'       => true,
		'rangeSeparator'         => true,
	);

	/**
	 * The type of the field.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static function get_label() {
		return __( 'Radio', 'search-filter' );
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
			'uid'   => self::get_instance_id( 'range-radio' ),
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
}
