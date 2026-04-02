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
use Search_Filter\Queries\Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends `Field` class and add overriders
 */
class Per_Page extends Control {

	/**
	 * Supported style settings for the per page field.
	 *
	 * @var array
	 */
	public static $styles = array(

		'fieldMargin'                   => true,
		// 'fieldPadding'                 => true,
		'inputMargin'                   => true,
		'labelBorderStyle'              => true,
		'labelBorderRadius'             => true,
		'descriptionBorderStyle'        => true,
		'descriptionBorderRadius'       => true,
		'inputClearPadding'             => true,
		'inputBorderRadius'             => true,

		'inputScale'                    => true,
		'inputColor'                    => true,
		'inputBackgroundColor'          => true,
		'inputPlaceholderColor'         => true,
		'inputSelectedColor'            => true,
		'inputSelectedBackgroundColor'  => true,
		'inputBorder'                   => true,
		'inputBorderHoverColor'         => true,
		'inputBorderFocusColor'         => true,
		'inputIconColor'                => true,
		'inputInteractiveColor'         => true,
		'inputInteractiveHoverColor'    => true,
		'inputClearColor'               => true,
		'inputClearHoverColor'          => true,
		'inputPadding'                  => true,
		'inputGap'                      => true,
		'inputTogglePadding'            => true,
		'inputToggleSize'               => true,
		'inputIconSize'                 => true,
		'inputIconPadding'              => true,
		'inputClearSize'                => array(
			'conditions' => array(),
			'variation'  => array(
				'style' => array(
					'variables' => array(
						'input-clear-size' => array(
							'value' => 'var(--search-filter-scale-base-size)',
							'type'  => 'unit',
						),
					),
				),
			),
		),
		'inputNoResultsText'            => true,
		'inputEnableSearch'             => true,
		'inputSingularResultsCountText' => true,
		'inputPluralResultsCountText'   => true,

		'labelColor'                    => true,
		'inputShadow'                   => true,
		'labelBackgroundColor'          => true,
		'labelPadding'                  => true,
		'labelMargin'                   => true,
		'labelScale'                    => true,
		'descriptionColor'              => true,
		'descriptionBackgroundColor'    => true,
		'descriptionPadding'            => true,
		'descriptionMargin'             => true,
		'descriptionScale'              => true,
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
	 * Supported settings for the per page field.
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
		'showLabel'       => true,
		'showDescription' => true,
		'description'     => true,
		'controlType'     => true,
		'placeholder'     => true,
		'perPageOptions'  => true,
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
	 * The input type for the per page field.
	 *
	 * TODO - we don't want to use "input type" to define "control type".
	 *
	 * @var string
	 */
	public static $input_type = 'per_page';

	/**
	 * The field type identifier.
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * List of components this field relies on.
	 *
	 * @var array
	 */
	public $components = array(
		'combobox',
	);

	/**
	 * List of icons used by this field.
	 *
	 * @var array
	 */
	public $icons = array(
		'arrow-down',
		'clear',
	);

	/**
	 * Gets the label for the per page field type.
	 *
	 * @return string The translated label.
	 */
	public static function get_label() {
		return __( 'Per Page', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users to choose how many results to show per page.', 'search-filter' );
	}

	/**
	 * The main function that constructs the main part of the filter,
	 * this could contain a single input or multiple inputs
	 *
	 * @since    3.0.0
	 */
	public function build() {
		return '';
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * Match the value to the sort options, then use those settings
	 * to apply the sort.
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {

		$value = $this->get_value();

		if ( empty( $value ) ) {
			return parent::apply_wp_query_args( $query_args );
		}
		// If its not initialized, bail early.
		$per_page_options = $this->get_attribute( 'perPageOptions' );
		if ( ! is_array( $per_page_options ) ) {
			return parent::apply_wp_query_args( $query_args );
		}

		// Bail early if there are no per page options.
		if ( count( $per_page_options ) === 0 ) {
			return parent::apply_wp_query_args( $query_args );
		}

		$has_option = false;
		// Make sure the option exists before allowing it to be used.
		foreach ( $per_page_options as $per_page_option ) {

			if ( $per_page_option['label'] !== $value ) {
				continue;
			}
			$has_option = true;
			break;
		}

		if ( ! $has_option ) {
			return parent::apply_wp_query_args( $query_args );
		}

		$query_args['posts_per_page'] = $value;

		return $query_args;
	}

	/**
	 * Gets the URL name for the field.
	 *
	 * @return string
	 */
	public function get_url_name() {
		$url_name = 'per-page';
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * Creates the options for the per page select field.
	 */
	public function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		do_action( 'search-filter/fields/control/create_options/start', $this );

		$per_page_options = $this->get_attribute( 'perPageOptions' );

		if ( ! is_array( $per_page_options ) ) {
			return;
		}

		$values                 = $this->get_values();
		$options                = array();
		$existing_option_values = array();

		foreach ( $per_page_options as $per_page_option ) {

			// Skip options without labels.
			if ( empty( $per_page_option['label'] ) ) {
				continue;
			}

			// Make sure we don't have duplicates, this causes errors with unique
			// keys when rendering.
			if ( in_array( $per_page_option['label'], $existing_option_values, true ) ) {
				continue;
			}

			$options[] = array(
				'label' => $per_page_option['label'],
				'value' => $per_page_option['label'],
			);

			if ( in_array( $per_page_option['label'], $values, true ) ) {
				$this->value_labels[ $per_page_option['label'] ] = $per_page_option['label'];
			}

			$existing_option_values[] = $per_page_option['label'];
		}

		// Allow overriding for custom options.
		$options = apply_filters( 'search-filter/fields/control/options', $options, $this );

		// After create options hook.
		do_action( 'search-filter/fields/control/create_options/finish', $this );
		$this->set_options( $options );
	}
	/**
	 * Get the list of options based on data attributes
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ! parent::has_options() ) {
			$this->create_options();
		}

		return $this->options;
	}
}
