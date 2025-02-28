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
	public $icons             = array(
		'event',
		'clear',
	);
	public static $input_type = 'date_picker';
	public static $type       = 'advanced';

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

	public static $data_support = array(
		// Each entry is a group of settings that need to have certain conditions.
		array(
			'dataType'          => 'post_attribute',
			'dataPostAttribute' => array( 'post_published_date' ),
		),
	);

	public static $setting_support = array(
		/*
		'dataType'                => array(
			'values' => array(
				'post_attribute',
			),
		),
		'dataPostAttribute'       => array(
			'values' => array(
				'post_published_date',
			),
		), */
		'showLabel'               => true,
		'placeholder'             => true,
		'dateDisplayFormat'       => true,
		'dateDisplayFormatCustom' => true,
		'dateShowMonth'           => true,
		'dateShowYear'            => true,
		'inputShowIcon'           => true,
	);

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

		$values      = $this->get_values();
		$value       = isset( $values[0] ) ? $values[0] : '';
		$render_data = array(
			'uid'   => self::get_instance_id( 'date-picker' ),
			'value' => wp_date( $this->attributes['dateDisplayFormat'], strtotime( $value ) ),
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
		if ( ! $this->has_init() ) {
			return parent::get_url_name();
		}
		if ( 'post_attribute' === $this->attributes['dataType'] ) {
			$data_source = $this->attributes['dataPostAttribute'];
			return $data_source;
		}
		return parent::get_url_name();
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		if ( $this->get_attribute( 'dataType' ) !== 'post_attribute' ) {
			return parent::apply_wp_query_args( $query_args );
		}

		if ( $this->get_attribute( 'dataPostAttribute' ) !== 'post_published_date' ) {
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
