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

use Search_Filter\Fields\Advanced\Date_Picker as Base_Date_Picker;
use Search_Filter\Util as Base_Util;
use Search_Filter_Pro\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the markup for a Text field
 */
class Date_Picker extends Base_Date_Picker {

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
	 * Format the indexer data for the field.
	 *
	 * @since 3.2.0
	 *
	 * @param mixed  $data     The data to format.
	 * @param string $strategy The strategy type (bitmap, bucket, search).
	 *
	 * @return mixed The formatted data.
	 */
	public function prepare_index_data( $data, $strategy ) {
		// Single date picker is always choice.
		// Formate the date to Y-m-d for indexing.
		if ( $strategy === 'bitmap' ) {

			if ( ! is_array( $data ) ) {
				return $data;
			}

			$formatted_values = array();

			foreach ( $data as $v ) {
				try {
					$dt                 = new \DateTime( $v );
					$formatted          = $dt->format( 'Y-m-d' );
					$formatted_values[] = $formatted;
				} catch ( \Exception $e ) {
					// Invalid date format, skip.
					Util::error_log( 'Invalid date format for indexing: ' . $v . ' Error: ' . $e->getMessage() );
				}
			}

			$data = $formatted_values;
		}
		return $data;
	}

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
		'arrow-right',
		'arrow-right-double',
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

		'fieldMargin'                  => true,
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
		'addClass'                     => true,
		'width'                        => true,
		'queryId'                      => true,
		'stylesId'                     => true,
		'type'                         => true,
		'label'                        => true,
		'showLabel'                    => true,
		'showDescription'              => true,
		'description'                  => true,
		'dataType'                     => array(
			'values' => array(
				'post_attribute' => true,
				'custom_field'   => true,
			),
		),
		'dataPostAttribute'            => array(
			'values' => array(
				'post_published_date' => true,
			),
		),
		'inputType'                    => true,
		'placeholder'                  => true,
		'dateDisplayFormat'            => true,
		'dateDisplayFormatCustom'      => true,
		'dateShowMonth'                => true,
		'dateShowYear'                 => true,
		'inputShowIcon'                => true,
		'autoSubmit'                   => true,
		'autoSubmitDelay'              => true,
		'labelInitialVisibility'       => true,
		'labelToggleVisibility'        => true,

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
	 * @since    3.0.0
	 *
	 * @return   string The label.
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
		return __( 'Allow users to filter by choosing a date or date range from a calendar.' );
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

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST
		// but with wp_unslash already applied.
		$request_var = Base_Util::get_request_var( $url_param_name );
		$value       = sanitize_text_field( urldecode_deep( $request_var ?? '' ) );

		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}
