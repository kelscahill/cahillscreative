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

use Search_Filter\Core\Deprecations;
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

		'fieldMargin'                => true,
		'inputMargin'                => true,
		'labelBorderStyle'           => true,
		'labelBorderRadius'          => true,
		'descriptionBorderStyle'     => true,
		'descriptionBorderRadius'    => true,
		'inputClearPadding'          => true,
		'inputBorderRadius'          => true,

		'inputScale'                 => true,
		'inputColor'                 => true,
		'inputBackgroundColor'       => true,
		'inputPlaceholderColor'      => true,
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
		'inputIconSize'              => true,
		'inputIconPadding'           => true,
		'inputClearSize'             => true,
		'inputIconPosition'          => true,

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
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to search by text input.', 'search-filter' );
	}

	/**
	 * The setting support for the field.
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
		'showLabelNotice'              => true,
		'showDescription'              => true,
		'description'                  => true,
		'dataType'                     => array(
			'values' => array(
				'post_attribute' => true,
				'taxonomy'       => true,
				'custom_field'   => true,
			),
		),
		'dataPostAttribute'            => array(
			'values' => array(
				'default'     => true,
				'post_type'   => true,
				'post_status' => true,
			),
		),
		'dataTaxonomy'                 => true,
		'inputType'                    => true,
		'placeholder'                  => true,
		'inputShowIcon'                => true,
		'autoSubmit'                   => true,
		'autoSubmitDelay'              => true,
		'labelInitialVisibility'       => true,
		'labelToggleVisibility'        => true,

		'dataUrlName'                  => true,
		'dataCustomField'              => true,
		'dataCustomFieldIndexerNotice' => true,

		'defaultValueType'             => true,
		'defaultValueInheritArchive'   => true,
		'defaultValueInheritSearch'    => true,
		'defaultValueInheritPost'      => true,
		'defaultValueCustom'           => true,
		'defaultValueApplyToQuery'     => true,
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
		$url_name = 's';
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );

		return $url_name;
	}

	/**
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST,
		// but with wp_unslash already applied.
		$request_var = Util::get_request_var( $url_param_name );
		$value       = $request_var ?? '';
		// Support multibyte space as a single space.
		$value = str_replace( '　', ' ', $value );
		// Santize after str_replace.
		$value = sanitize_text_field( $value );
		// And trim any whitespace.
		$value = trim( $value );
		if ( $value !== '' ) {
			$this->set_values( array( $value ) );
		}
	}
}
