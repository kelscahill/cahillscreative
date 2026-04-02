<?php
/**
 * Submit Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Control
 */

namespace Search_Filter\Fields\Control;

use Search_Filter\Fields\Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Submit extends Control {

	/**
	 * The input type name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'submit';

	/**
	 * The type.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'        => true,
		'width'           => true,
		'queryId'         => true,
		'stylesId'        => true,
		'type'            => true,
		'label'           => true,
		'showDescription' => true,
		'description'     => true,
		'controlType'     => true,
	);

	/**
	 * The processed (cached) setting support.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_setting_support    The processed settings, null if not processed yet.
	 */
	protected static $processed_setting_support = null;

	/**
	 * List of styles the input type supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'             => true,
		// 'fieldPadding'            => true,
		'inputMargin'             => true,
		'descriptionBorderStyle'  => true,
		'descriptionBorderRadius' => true,
		'inputBorderRadius'       => true,


		'inputScale'              => true,
		'inputColor'              => true,
		'inputBackgroundColor'    => true,
		'inputBorder'             => true,
		'inputBorderHoverColor'   => true,
		'inputBorderFocusColor'   => true,
		'inputShadow'             => true,
		'inputPadding'            => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						// Add extra padding to the left and right.
						'input-padding-right' => array(
							'value' => 'calc(0.6 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-padding-left'  => array(
							'value' => 'calc(0.6 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
		),
	);

	/**
	 * The processed (cached) styles.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_styles    The processed styles, null if not processed yet.
	 */
	protected static $processed_styles = null;

	/**
	 * Get the label for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Submit', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to submit their filter choices (only required if auto-submit is not enabled).', 'search-filter' );
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
