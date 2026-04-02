<?php
/**
 * Selection Control Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Fields/Filter
 */

namespace Search_Filter_Pro\Fields\Control;

use Search_Filter\Fields\Control;
use Search_Filter\Fields\Settings as Fields_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Selection control field class.
 *
 * Allows users to see and remove their active filters.
 *
 * @since 3.0.0
 */
class Selection extends Control {

	/**
	 * Track if the regiseterd function has been run.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	protected static $has_registered = false;

	/**
	 * Input type identifier.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $input_type = 'selection';

	/**
	 * Field type identifier.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public static $type = 'control';

	/**
	 * Field icons.
	 *
	 * @var array
	 */
	public $icons = array(
		'clear',
	);

	/**
	 * Supported styles for this field type.
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
		'inputClearPadding'          => true,
		'inputBorderRadius'          => true,

		'inputScale'                 => true,
		'inputColor'                 => true,
		'inputBackgroundColor'       => true,
		'inputBorder'                => true,
		'inputBorderHoverColor'      => true,
		'inputBorderFocusColor'      => true,
		'inputClearColor'            => true,
		'inputClearHoverColor'       => true,
		'inputShadow'                => true,
		'inputPadding'               => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						// Add extra padding to the left and right.
						'input-padding-right' => array(
							'value' => 'calc(0.8 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
						'input-padding-left'  => array(
							'value' => 'calc(0.8 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
		),
		// Add spacing between the buttons.
		'inputGap'                   => array(
			// Empty conditions means its supported.
			'conditions' => array(),
			// Add a variation to override the default styles variables.
			'variation'  => array(
				// Structure must match that of the setting.
				'style' => array(
					'variables' => array(
						'input-gap' => array(
							'value' => 'calc(0.45 * var(--search-filter-scale-base-size))',
							'type'  => 'spacing-unit',
						),
					),
				),
			),
		),

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
	 * Supported settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public static $setting_support = array(
		'addClass'                       => true,
		'width'                          => true,
		'queryId'                        => true,
		'type'                           => true,
		'label'                          => true,
		'showLabel'                      => true,
		'showDescription'                => true,
		'description'                    => true,
		'controlType'                    => true,
		'labelInitialVisibility'         => true,
		'labelToggleVisibility'          => true,
		'dataControlSelectionShowLabels' => true,
		'hideFieldWhenEmpty'             => true,
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
	 * Get the label for the field type.
	 *
	 * @since 3.0.0
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return __( 'Selection', 'search-filter' );
	}

	/**
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return __( 'Allow users see their current active filters and remove them.' );
	}
	/**
	 * Set the available option values for rendering.
	 *
	 * @var array
	 */
	private $available_options = array();

	/**
	 * Initialize the field.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		parent::init();
		if ( ! has_filter( 'search-filter/fields/field/render_data', array( $this, 'filter_render_data' ) ) ) {
			add_filter( 'search-filter/fields/field/render_data', array( $this, 'filter_render_data' ), 10, 2 );
		}
	}

	/**
	 * Override the init and setup render data + escaping functions.
	 *
	 * @since    3.0.0
	 */
	protected function init_render_data() {
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

	/**
	 * Get the list of options based on data attributes
	 *
	 * TODO - we should be able to expect some of these attributes
	 * to be set rather than checking them all.
	 *
	 * This is partly related to the create_field endpoint, it is
	 * hit while fields are being initialised and as a result the
	 * attributes are malformed.
	 *
	 * 1. We need a reliable way to init fields with the correct
	 *    attributes & sensible defaults.
	 * 2. Resolve the settings in PHP before loading the page,
	 *    use it as the default state for the JS so we don't send
	 *    the wrong data to the server when creating new fields.
	 */
	public function create_options() {

		if ( ! $this->has_init() ) {
			return;
		}

		// Before create options hook.
		do_action( 'search-filter/fields/control/create_options/start', $this );

		$query = $this->get_query();

		if ( ! $query ) {
			return;
		}

		$query_fields = $query->get_fields(
			array(
				'id__not_in' => array( $this->get_id() ),
			)
		);

		$options = array();

		foreach ( $query_fields as $query_field ) {

			if ( is_wp_error( $query_field ) ) {
				continue;
			}

			if ( empty( $query_field->get_values() ) ) {
				continue;
			}

			// Create options for for fields that have the function create_options.
			if ( ! $query_field->has_options() && method_exists( $query_field, 'create_options' ) ) {
				// Create the options if they haven't been created yet so we can get their labels.
				$query_field->create_options();
			}

			$value_labels = $query_field->get_value_labels();
			$field_label  = $query_field->get_attribute( 'label' );

			foreach ( $value_labels as $value => $value_label ) {
				$option_value = $query_field->get_id() . '/' . $value;

				$option_label = $value_label;
				if ( $this->get_attribute( 'dataControlSelectionShowLabels' ) === 'yes' && ! empty( $field_label ) ) {
					// translators: %1$s: Is the field label, is 'Category'. %2$s: is the field value, ie "Uncategorized".
					$option_label = sprintf( __( '%1$s: %2$s', 'search-filter-pro' ), $field_label, $value_label );
				}

				$options[] = array(
					'value'      => $option_value,
					'label'      => $option_label,
					/* translators: %s: Filter value label */
					'aria-label' => sprintf( __( 'Remove filter: %s', 'search-filter' ), $value_label ),
				);
			}
		}

		// Allow overriding for custom options.
		$options = apply_filters( 'search-filter/fields/control/options', $options, $this );

		// Set the available options for the field, this will udpate the
		// availableOptions state and trigger the update of the field options
		// after a live search has been completed.
		$this->available_options = array();
		foreach ( $options as $option ) {
			$this->available_options[] = $option['value'];
		}

		// After create options hook.
		do_action( 'search-filter/fields/control/create_options/finish', $this );

		$this->set_options( $options );
	}

	/**
	 * Filter the render data for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $render_data The render data.
	 * @param object $field       The field object.
	 * @return array Modified render data.
	 */
	public function filter_render_data( $render_data, $field ) {
		if ( $field->get_id() === $this->get_id() ) {
			$render_data['availableOptions'] = $this->available_options;
		}
		return $render_data;
	}

	/**
	 * Register the field.
	 *
	 * @since 3.0.0
	 */
	public static function register() {
		if ( self::$has_registered ) {
			return;
		}

		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'setup' ), 2 );

		self::$has_registered = true;
	}

	/**
	 * Setup field settings.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {

		$setting = array(
			'name'      => 'dataControlSelectionShowLabels',
			'label'     => __( 'Show Field Labels', 'search-filter-pro' ),
			'help'      => __( 'Prefix the field label to the selected option value.', 'search-filter-pro' ),
			'default'   => 'no',
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'label' => __( 'Yes', 'search-filter' ),
					'value' => 'yes',
				),
				array(
					'label' => __( 'No', 'search-filter' ),
					'value' => 'no',
				),
			),
			'context'   => array( 'admin/field', 'admin/field/control', 'block/field/control' ),
		);

		Fields_Settings::add_setting( $setting );
	}
}
