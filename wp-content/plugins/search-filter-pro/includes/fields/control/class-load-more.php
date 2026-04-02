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

		'fieldMargin'                  => true,
		'inputMargin'                  => true,
		'inputBorderRadius'            => true,

		'inputScale'                   => true,
		'inputColor'                   => true,
		'inputBackgroundColor'         => true,
		'inputSelectedColor'           => true,
		'inputSelectedBackgroundColor' => true,
		'inputBorder'                  => true,
		'inputBorderHoverColor'        => true,
		'inputBorderFocusColor'        => true,
		'inputShadow'                  => true,
		'inputIconColor'               => true,
		'inputPadding'                 => true,
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
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to load more results after their current results list.' );
	}

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
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		return '';
	}
}
