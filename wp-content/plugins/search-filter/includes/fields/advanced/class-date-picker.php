<?php
/**
 * Class to display the DatePicker
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter\Fields\Advanced;

use Search_Filter\Fields\Advanced;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the setup + display of the Date Picker
 */
class Date_Picker extends Advanced {

	/**
	 * Calculate the interaction type for this field.
	 *
	 * @since 3.2.0
	 *
	 * @return string The interaction type.
	 */
	protected function calc_interaction_type(): string {
		// Single date picker is always choice.
		return 'choice';
	}

	/**
	 * List of components this field relies on.
	 *
	 * @var array
	 */
	public $components = array(
		'date-picker',
	);

	/**
	 * List of icons the input type supports.
	 *
	 * @var array
	 */
	public $icons = array(
		'event',
		'clear',
	);

	/**
	 * The input type for this field.
	 *
	 * @var string
	 */
	public static $input_type = 'date_picker';

	/**
	 * The type of field.
	 *
	 * @var string
	 */
	public static $type = 'advanced';

	/**
	 * List of supported styles for this field.
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'                  => true,
		// 'fieldPadding'                 => true,
		'inputMargin'                  => true,
		'labelBorderStyle'             => true,
		'labelBorderRadius'            => true,
		'descriptionBorderStyle'       => true,
		'descriptionBorderRadius'      => true,
		'inputClearPadding'            => true,
		'inputBorderRadius'            => true,
		'inputBorderAccentColor'       => true,

		'inputScale'                   => true,
		'inputColor'                   => true,
		'inputBackgroundColor'         => true,
		'inputPlaceholderColor'        => true,
		'inputSelectedColor'           => true,
		'inputSelectedBackgroundColor' => true,
		'inputBorder'                  => true,
		'inputBorderHoverColor'        => true,
		'inputBorderFocusColor'        => true,
		'inputIconColor'               => true,
		'inputInteractiveColor'        => true,
		'inputInteractiveHoverColor'   => true,
		'inputClearColor'              => true,
		'inputClearHoverColor'         => true,
		'inputShadow'                  => true,
		'inputPadding'                 => true,
		'inputGap'                     => true,
		'inputIconSize'                => true,
		'inputIconPadding'             => true,
		'inputClearSize'               => true,


		'labelColor'                   => true,
		'labelBackgroundColor'         => true,
		'labelPadding'                 => true,
		'labelMargin'                  => true,
		'labelScale'                   => true,

		'descriptionColor'             => true,
		'descriptionBackgroundColor'   => true,
		'descriptionPadding'           => true,
		'descriptionMargin'            => true,
		'descriptionScale'             => true,


		// 'dropdownAttachment' => true,
		'dropdownScale'                => true,
		'dropdownMargin'               => true,
		'dropdownBorder'               => true,
		'dropdownBorderRadius'         => true,
		'dropdownOptionPadding'        => true,
		'dropdownOptionIndentDepth'    => true,
		'dropdownShadow'               => true,
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
	 * List of setting support for this field.
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'                => true,
		'width'                   => true,
		'queryId'                 => true,
		'stylesId'                => true,
		'type'                    => true,
		'label'                   => true,
		'showLabel'               => true,
		'showDescription'         => true,
		'description'             => true,
		'inputType'               => true,
		'dataType'                => array(
			'values' => array(
				'post_attribute' => true,
			),
		),
		'dataPostAttribute'       => array(
			'values' => array(
				'post_published_date' => true,
			),
		),
		'placeholder'             => true,
		'dateDisplayFormat'       => true,
		'dateDisplayFormatCustom' => true,
		'dateShowMonth'           => true,
		'dateShowYear'            => true,
		'inputShowIcon'           => true,
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
	 * Get the label for this field type.
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Date Picker', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to filter by choosing a date from a calendar.' );
	}

	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	public function init() {
		parent::init();

		$values      = $this->get_values();
		$value       = isset( $values[0] ) ? $values[0] : '';
		$render_data = array(
			'uid'   => self::get_instance_id( 'date-picker' ),
			'value' => wp_date( $this->get_attribute( 'dateDisplayFormat' ), strtotime( $value ) ),
		);
		$this->set_render_data( $render_data );

		$esc_callbacks = array(
			'uid'         => 'esc_html',
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
		if ( ! $this->get_attribute( 'dataType' ) ) {
			return parent::get_url_name();
		}
		if ( 'post_attribute' === $this->get_attribute( 'dataType' ) ) {
			$data_source = $this->get_attribute( 'dataPostAttribute' );
			return $data_source;
		}
		return parent::get_url_name();
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @param array $query_args The query arguments.
	 * @return array The modified query arguments.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		if ( $this->get_attribute( 'dataType' ) !== 'post_attribute' ) {
			return parent::apply_wp_query_args( $query_args );
		}

		if ( $this->get_attribute( 'dataPostAttribute' ) !== 'post_published_date' ) {
			return parent::apply_wp_query_args( $query_args );
		}
		// Only set if a value is selected.
		if ( ! $this->has_values() ) {
			return parent::apply_wp_query_args( $query_args );
		}
		$value = $this->get_value();
		$date  = explode( '-', $value );
		if ( count( $date ) !== 3 ) {
			return parent::apply_wp_query_args( $query_args );
		}
		if ( ! isset( $query_args['date_query'] ) ) {
			$query_args['date_query'] = array();
		}
		array_push(
			$query_args['date_query'],
			array(
				'year'  => $date[0],
				'month' => $date[1],
				'day'   => $date[2],
			)
		);
		return parent::apply_wp_query_args( $query_args );
	}
}
