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

		'fieldMargin'                => true,
		'inputMargin'                => true,
		'labelBorderStyle'           => true,
		'labelBorderRadius'          => true,
		'descriptionBorderStyle'     => true,
		'descriptionBorderRadius'    => true,

		'inputScale'                 => true,
		'inputLabelColor'            => true,
		'inputActiveIconColor'       => true,
		'inputInactiveIconColor'     => true,

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
	 * The processed (cached) styles.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_styles    The processed styles, null if not processed yet.
	 */
	protected static $processed_styles = null;

	/**
	 * The input type.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'radio';

	/**
	 * List of components this field relies on.
	 *
	 * @var array
	 */

	public $components = array(
		'range',
	);
	/**
	 * The setting support.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'                     => true,
		'width'                        => true,
		'queryId'                      => true,
		'stylesId'                     => true,
		'type'                         => true,
		'label'                        => true,
		'showLabel'                    => true,
		'showDescription'              => true,
		'description'                  => true,
		'inputType'                    => true,
		'dataType'                     => array(
			'values' => array(
				'custom_field' => true,
			),
		),

		'autoSubmit'                   => true,
		'autoSubmitDelay'              => true,
		'labelInitialVisibility'       => true,
		'labelToggleVisibility'        => true,
		'dataMaxRangeOptionsNotice'    => true,
		'rangeAutodetectMin'           => true,
		'rangeAutodetectMax'           => true,
		'rangeMin'                     => true,
		'rangeMax'                     => true,
		'rangeStep'                    => true,
		'rangeDecimalPlaces'           => true,
		'rangeDecimalCharacter'        => true,
		'rangeThousandCharacter'       => true,
		'rangeValuePrefix'             => true,
		'rangeValueSuffix'             => true,
		'rangeSeparator'               => true,
		'hideFieldWhenEmpty'           => true,

		'dataUrlName'                  => true,
		'dataCustomField'              => true,
		'dataCustomFieldIndexerNotice' => true,
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
	 * Get the label for the input type.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Radio', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by ranges using radio buttons.', 'search-filter' );
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
}
