<?php
/**
 * Class for handling the frontend display of a field.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Fields;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Util;
use Search_Filter\Database\Queries\Fields as Field_Query;
use Search_Filter\Fields;
use Search_Filter\Styles;
use Search_Filter\Styles\Style;
use Search_Filter\Record_Base;
use Search_Filter\Core\Exception;
use Search_Filter\Queries;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base field for frontend fields (should be extended).
 */
class Field extends Record_Base {
	/**
	 * The record store name
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	public static $record_store = 'field';

	/**
	 * The meta type for the meta table.
	 *
	 * @var string
	 */
	public static $meta_table = 'search_filter_field';
	/**
	 * The full string of the class name of the query class for this section.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $records_class    The string class name.
	 */
	public static $records_class = 'Search_Filter\\Database\\Queries\\Fields';

	/**
	 * The class name to handle interacting with the record stores.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $base_class    ID
	 */
	public static $base_class = 'Search_Filter\\Fields';
	/**
	 * Reference to the query ID
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      integer    $query_id    Maintains and registers all hooks for the plugin.
	 */
	private $query_id = 0;

	/**
	 * Unique ID for referencing on the frontend
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	protected $uid = 0;

	/**
	 * Array of icon names to load.
	 *
	 * @since    3.0.0
	 * @access   public
	 * @var      array    $icons    An array of strings (icon names)
	 */
	public $icons = array();

	/**
	 * Array of styles settings the field supports.
	 *
	 * @since    3.0.0
	 * @access   public
	 * @var      array    $styles    An array of styles (setting names)
	 */
	public static $styles = array();
	/**
	 * Assoc array of data type settings the field supports.
	 *
	 * @since    3.0.0
	 * @access   public
	 * @var      array    $data_support    A nested array of data type settings
	 */
	public static $data_support = array();
	/**
	 * Assoc array of settings (and their options) the field supports.
	 *
	 * @since    3.0.0
	 * @access   public
	 * @var      array    $setting_support    A nested array of setting types.
	 */
	public static $setting_support = array();

	/**
	 * The input type name.
	 *
	 * @var string
	 */
	public static $input_type = '';

	/**
	 * The type of input type (parent category).
	 *
	 * E.g., search, choice, range, advanced, control, etc.
	 *
	 * @var string
	 */
	public static $type = '';

	/**
	 * The user selected values
	 *
	 * @var array
	 */
	private $values = array();

	/**
	 * Saved field html attributes
	 *
	 * @var array
	 */
	private $html_attributes = array();

	/**
	 * The html classes to be added to the field.
	 *
	 * @var array
	 */
	private $html_classes = array();

	/**
	 * Options for fields that have multiple choice
	 *
	 * @var array
	 */
	protected $options = array();
	/**
	 * Whether or not we have already calculated the options.
	 *
	 * @var array
	 */
	private $options_init = false;

	/**
	 * Context for the field, such as admin, block-editor, elementor, etc.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $context = '';

	/**
	 * Context path, eg - 'edit/123'
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $context_path = '';

	/**
	 * Array of apis that the field supports.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	public $supports = array();

	/**
	 * Minimum settings to init.
	 *
	 * @var array
	 */
	private $default_attributes = array();

	/**
	 * Extra render data to be passed to to the render function.
	 *
	 * @var array
	 */
	private $render_data = array();
	/**
	 * Array of callbacks matching the render_data keys.
	 *
	 * @var array
	 */
	private $render_escape_callbacks = array();

	/**
	 * Keeps track of used IDs to ensure we can generate unique IDs.
	 *
	 * @var array
	 */
	private static $instance_ids = array();

	/**
	 * A unique name to be used in the URL.
	 *
	 * @var string
	 */
	private $url_name = '';

	/**
	 * The CSS string for this style preset.
	 *
	 * @var string
	 */
	private $css = '';

	/**
	 * Additional data that can be used to generate the field.
	 *
	 * @var string
	 */
	private $connected_data = array();

	/**
	 * The nice name of the input type, used for labels etc.
	 *
	 * @var string
	 */
	public $labels = array();

	/**
	 * Handlers for the field class.
	 *
	 * A way to extend the field with custom functionality.
	 *
	 * @var array
	 */
	public static $handlers = array();


	/**
	 * The render attributes.
	 */
	private $render_attributes = array();

	/**
	 * Query type to use for the field.
	 *
	 * @var string
	 */
	private $query_type = 'wp_query';

	/**
	 * Get the generated CSS.
	 *
	 * @return string The CSS.
	 */
	public function get_css() {
		return $this->css;
	}
	/**
	 * Set the status of the query
	 *
	 * @param string $css The CSS string to set.
	 */
	public function set_css( $css ) {
		$this->css = $css;
	}

	/**
	 * Overridable function for setting field defaults.
	 *
	 * @return array New default attributes.
	 */
	public function get_default_attributes() {
		// TODO - defaults should be set based on the fields settings.
		// We need to apply the `dependsOn`/ conditional logic to get the necessary defaults.
		// We should probably also "clean" the attributes before saving to remove settings/keys
		// that are not needed anymore by the field.
		$defaults = \Search_Filter\Fields\Settings::get_defaults();
		$defaults = apply_filters( 'search-filter/field/default_attributes', $defaults, $this );
		return $defaults;
	}

	/**
	 * Get the supported styles for the field.
	 *
	 * @return array
	 */
	public static function get_styles_support() {
		return apply_filters( 'search-filter/field/get_style_support', static::$styles, static::$type, static::$input_type );
	}

	/**
	 * Get the supported data types for the input type.
	 *
	 * @return array
	 */
	public static function get_data_support() {
		return apply_filters( 'search-filter/field/get_data_support', static::$data_support, static::$type, static::$input_type );
	}

	/**
	 * Get the supported settings for the field.
	 *
	 * @return array
	 */
	public static function get_setting_support() {
		$parsed_setting_support = array();

		foreach ( static::$setting_support as $setting_name => $support ) {
			$parsed_setting_support[ $setting_name ] = array();

			// We can have `values` and `conditions` keys, or it can just be true or false.
			if ( is_bool( $support ) ) {
				$parsed_setting_support[ $setting_name ] = $support;
			} elseif ( isset( $support['conditions'] ) ) {
				// Always wrap the conditions in an OR relation, so we can insert alternative
				// routes when we extend the conditions.
				// And always wrap the current set conditions in an AND relation as all of them
				// should be met.
				$parsed_setting_support[ $setting_name ]['conditions'] = array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'relation' => 'AND',
							'rules'    => $support['conditions'],
						),
					),
				);
			} elseif ( isset( $support['values'] ) ) {
				$parsed_setting_support[ $setting_name ]['values'] = $support['values'];
			}
		}

		return apply_filters( 'search-filter/field/get_setting_support', $parsed_setting_support, static::$type, static::$input_type );
	}

	/**
	 * Checks if the setting already has support conditions, then creates a wrapper
	 * to extend it by new conditions.
	 *
	 * TODO - we should refactor this logic so we can call `addSettingSupport` and
	 * it will automatically handle this.
	 *
	 * @param array  $setting_support The setting support to add the conditions to.
	 * @param string $setting_name    The setting name to add the conditions to.
	 * @param array  $new_conditions  The new conditions to add.
	 *
	 * @return array The updated setting support.
	 */
	public static function add_setting_support_condition( $setting_support, $setting_name, $new_conditions, $is_required = true ) {

		$conditions = array(
			'relation' => 'OR',
			'rules'    => array(
				array(
					'relation' => 'AND',
					'rules'    => array(), // Push here to add required conditions
				),
				// Push here to add alternative logic routes.
			),
		);

		if ( isset( $setting_support[ $setting_name ] ) ) {
			if ( is_array( $setting_support[ $setting_name ] ) && isset( $setting_support[ $setting_name ]['conditions'] ) ) {
				$conditions = $setting_support[ $setting_name ]['conditions'];
			}
		}

		if ( $is_required ) {
			$conditions['rules'][0]['rules'][] = $new_conditions;
		} else {
			$conditions['rules'][] = $new_conditions;
		}

		return $conditions;
	}
	/**
	 * Init the field from already loaded attributes.
	 */
	public function init() {

		parent::init();

		if ( $this->uid === 0 ) {
			$this->uid = self::get_instance_id( 'field' );
		}
		$this->refresh_query_id();

		if ( $this->url_name === '' ) {
			$this->url_name = $this->get_url_name();
		}

		// Set the field values.
		$this->values = array();

		// Init classes.
		$this->set_html_classes();

		// Attributes needs has_init to be true so we can use `get_values`.
		$this->set_html_attributes();

		// Setup values from the URL.
		$this->parse_url_value();
	}

	/**
	 * Inits the data from a DB record.
	 *
	 * @param [type] $item Database record.
	 */
	public function load_record( $item ) {
		parent::load_record( $item );
		$this->set_context( $item->get_context() );
		$this->set_context_path( $item->get_context_path() );
		$this->set_css( $item->get_css() );
	}

	/**
	 * Generate the unique name to be used in the URL.
	 *
	 * @return string The name to be used in the URL
	 */
	public function get_url_name() {
		$url_name = 'field_' . $this->get_id();
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		return $url_name;
	}

	/**
	 * Generates a results URL for the field.
	 *
	 * We usually want this to be empty, but sometimes a field
	 * will want to link to something like a taxonomy archive.
	 *
	 * @return string The name to be used in the URL
	 */
	public function get_url_template() {
		return apply_filters( 'search-filter/field/url_template', array(), $this );
	}

	/**
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Allow override via hook.
		$values = apply_filters( 'search-filter/field/parse_url_value', '', $this );
		if ( ! empty( $values ) ) {
			$this->set_values( explode( ',', $values ) );
		}

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST.
		$request_var = Util::get_request_var( $url_param_name );

		// Proceed as normal by trying to get the value from the URL.
		if ( $request_var === null ) {
			return;
		}

		$values = sanitize_text_field( wp_unslash( $request_var ) );

		if ( $values !== '' ) {
			$this->set_values( explode( ',', $values ) );
		}
	}

	/**
	 * Sets the value of the field.
	 *
	 * @param array $values The values to set.
	 */
	public function set_values( $values ) {
		$this->values = $values;
	}

	/**
	 * Get the ID of the field
	 */
	public function get_id() {
		if ( $this->id !== 0 ) {
			return $this->id;
		}

		// Blocks use fieldId to reference their DB equivalent.
		if ( isset( $this->attributes['fieldId'] ) ) {
			// TODO - we should probably normalise this so they all use the `id` attribute.
			return absint( $this->attributes['fieldId'] );
		}

		return $this->id;
	}
	/**
	 * Get the supports array for the field.
	 *
	 * @return array
	 */
	public function get_supports() {
		return $this->supports;
	}

	/**
	 * Add a support to the field.
	 *
	 * @param string $support The support to add.
	 */
	public function add_support( $support ) {
		if ( ! in_array( $support, $this->supports, true ) ) {
			$this->supports[] = $support;
		}
	}

	/**
	 * Gets the WP_Query args based on the field value.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The WP_Query args.
	 *
	 * @return array The updated WP_Query args.
	 */
	public function apply_wp_query_args( $query_args = array() ) {
		$query_args = apply_filters( 'search-filter/field/wp_query_args', $query_args, $this );
		return $query_args;
	}

	/**
	 * Apply the WP_Query posts where clause.
	 *
	 * @since 3.0.0
	 *
	 * @param string $where The WHERE clauses.
	 *
	 * @return string The updated WHERE clauses.
	 */
	public function apply_wp_query_posts_where( $where ) {
		return $where;
	}

	/**
	 * Apply the WP_Query posts join clause.
	 *
	 * @since 3.0.0
	 *
	 * @param string $join The JOIN clauses.
	 *
	 * @return string The updated JOIN clauses.
	 */
	public function apply_wp_query_posts_join( $join ) {
		return $join;
	}

	/**
	 * Sets the `attributes` var, which are used in the container markup.
	 *
	 * @since    3.0.0
	 */
	private function set_html_classes() {

		$this->html_classes = array();

		$this->html_classes[] = 'search-filter-base';
		$this->html_classes[] = 'search-filter-field';
		$this->html_classes[] = 'search-filter-field--id-' . $this->get_id();

		$field_type   = $this->get_attribute( 'type' );
		$input_type   = $this->get_attribute( 'inputType' );
		$control_type = $this->get_attribute( 'controlType' );

		$this->html_classes[] = 'search-filter-field--type-' . $field_type;

		if ( $field_type === 'control' ) {
			$this->html_classes[] = 'search-filter-field--control-type-' . $control_type;

		} else {
			// TODO - this is getting a bit messy, get_input_type will return something like:
			// `select` but the get_attribute will return `select-checkbox` or `select-radio`.
			// we need to unify this instead of adding in edge cases and extra calc.
			$this->html_classes[] = 'search-filter-field--input-type-' . self::get_input_type();

		}

		$this->html_classes[] = 'search-filter-style--id-' . $this->get_calc_styles_id();
		// TODO - seperating controls and input types like this is more.
		$resolved_input_type  = $field_type === 'control' ? $control_type : $input_type;
		$this->html_classes[] = 'search-filter-style--' . $field_type . '-' . $resolved_input_type;

		if ( isset( $this->attributes['width'] ) && ! empty( $this->attributes['width'] ) ) {
			$this->html_classes[] = 'search-filter-field--width-' . $this->get_attribute( 'width' );
		}
		if ( isset( $this->attributes['alignment'] ) && ! empty( $this->attributes['alignment'] ) ) {
			$this->html_classes[] = 'search-filter-field--align-text-' . $this->get_attribute( 'alignment' );
		}

		// Add block editor class.
		if ( isset( $this->attributes['align'] ) && ! empty( $this->attributes['align'] ) ) {
			$this->html_classes[] = 'search-filter-field--align-' . $this->get_attribute( 'align' );
		}

		// Add user defined custom classes.
		$add_class = $this->get_attribute( 'addClass' );
		if ( $add_class ) {
			$this->html_classes[] = $add_class;
		}

		$this->html_classes = apply_filters( 'search-filter/fields/field/html_classes', $this->html_classes, $this );
	}
	/**
	 * Sets the `attributes` var, which are used in the container markup.
	 *
	 * @since    3.0.0
	 */
	private function set_html_attributes() {

		// Reset class attribute.
		$this->html_attributes['class'] = '';

		// TODO: add query id class.
		$this->html_attributes['data-search-filter-id'] = $this->get_id();
		$this->html_attributes                          = apply_filters( 'search-filter/fields/field/html_attributes', $this->html_attributes, $this );
	}

	/**
	 * Get the unique ID for the field.
	 *
	 * @return int
	 */
	public function get_uid() {
		return $this->uid;
	}
	/**
	 * Get a style attribute.
	 *
	 * First looks to see if we've overriden the attribute locally,
	 * if not, then try to load it from the style record.
	 *
	 * @param string $name The attribute name.
	 *
	 * @return mixed The attribute value.
	 */
	public function get_style_attribute( $name ) {
		if ( isset( $this->attributes[ $name ] ) ) {
			return $this->attributes[ $name ];
		} else {
			$style = Style::find( array( 'id' => $this->get_calc_styles_id() ) );
			if ( ! is_wp_error( $style ) ) {
				return $style->get_attribute_by_type( $this->get_attribute( 'type' ), self::get_input_type(), $name );
			}
		}
		return null;
	}
	/**
	 * Get the styles ID.  If its 0, its the default styles ID, so use that.
	 *
	 * @since 3.0.0
	 *
	 * @return int The styles ID.
	 */
	private function get_calc_styles_id() {
		// Add styles class, if its `0` (default) then fetch the default ID.
		$styles_id = isset( $this->attributes['stylesId'] ) ? absint( $this->attributes['stylesId'] ) : 0;
		if ( 0 === $styles_id ) {
			// TODO - need a wrapper for getting default styles.
			$styles_id = absint( Styles::get_default_styles_id() );
		}
		return $styles_id;
	}

	/**
	 * Add a class to the field
	 *
	 * @param string $class_name Class name.
	 *
	 * @since    3.0.0
	 */
	public function add_html_class( $class_name, $position = 'after' ) {
		if ( $position === 'after' ) {
			$this->html_classes[] = $class_name;
		} else {
			array_unshift( $this->html_classes, $class_name );
		}
	}
	/**
	 * Add a html attribute to the filter - expects unescaped input
	 *
	 * @param string $attribute_name    Name of the attribute.
	 * @param string $attribute_value   Value of the attribute.
	 *
	 * @since    3.0.0
	 */
	protected function add_html_attribute( $attribute_name, $attribute_value ) {
		$this->html_attributes[ $attribute_name ] = $attribute_value;
	}
	/**
	 * Get the html attributes
	 *
	 * @since    3.0.0
	 */
	public function get_html_attributes() {
		return $this->html_attributes;
	}

	/**
	 * Get the html classes
	 *
	 * @since    3.0.4
	 */
	public function get_html_classes() {
		return $this->html_classes;
	}


	/**
	 * Get the values that are set for this field
	 *
	 * @since    3.0.0
	 */
	public function get_values() {
		return apply_filters( 'search-filter/fields/field/values', $this->values, $this );
	}

	/**
	 * If there are any values set for this field
	 *
	 * @since    3.0.0
	 */
	protected function has_values() {
		return count( $this->get_values() ) > 0;
	}

	/**
	 * Quick way to retreive the single field value.
	 *
	 * Many field types have only one possible value.
	 *
	 * @return mixed The value of the field.
	 */
	public function get_value() {
		$values = $this->get_values();
		$value  = isset( $values[0] ) ? $values[0] : '';
		return $value;
	}
	/**
	 * Set the related data for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The key to set.
	 * @param array  $data An associative array of data.
	 */
	public function update_connected_data( $key, $data ) {
		$this->connected_data[ $key ] = $data;
	}

	/**
	 * Get the related data for the field.
	 *
	 * @since 3.0.0
	 *
	 * @return array The related data.
	 */
	public function get_connected_data() {
		return apply_filters( 'search-filter/fields/field/connected_data', $this->connected_data, $this );
	}
	/**
	 * Gets the JS data for rendering the field.
	 *
	 * @since 3.0.0
	 *
	 * @return array The JSON data.
	 */
	public function get_render_data() {

		// TODO - we are mixing up terms. We have a variable called
		// render_data, which is used in 'build()' and ultimately
		// 'render()', but then we have this function, 'get_render_data()'
		// which doesn't use that variable at all, and is used for
		// building the JS data.
		// We also _now_ have 'get_render_attributes()' as a way to pass
		// in custom attributes only at the time of building the JS data.
		$attributes = $this->get_render_attributes();
		// Resolve the styles ID if its set to default.
		$attributes['stylesId'] = $this->get_calc_styles_id();

		$render_data = array(
			'attributes'    => $attributes,
			'options'       => $this->get_options(),
			'values'        => $this->get_values(),
			'uid'           => $this->get_uid(),
			'urlName'       => $this->get_url_name(),
			'icons'         => $this->get_icons(),
			'urlTemplate'   => $this->get_url_template(),
			'id'            => $this->get_id(),
			'connectedData' => $this->get_connected_data(),
			'supports'      => $this->supports,
		);

		return apply_filters( 'search-filter/fields/field/render_data', $render_data, $this );
	}

	/**
	 * Get the attributes for the render data.
	 *
	 * @since 3.1.3
	 */
	public function get_render_attributes() {
		if ( empty( $this->render_attributes ) ) {
			return $this->get_attributes();
		}
		return $this->render_attributes;
	}
	/**
	 * Set the attributes for the render data.
	 *
	 * @since 3.1.3
	 *
	 * @param array $attributes The attributes to set.
	 */
	public function set_render_attributes( $attributes ) {
		$this->render_attributes = $attributes;
	}
	/**
	 * Add the options to the json data object.
	 *
	 * TODO - we're using this mostly for our admin previews,
	 * it should at least be renamed, replaced with get_js_data
	 * or refactored.
	 *
	 * @since 3.0.0
	 *
	 * @return array The JSON data.
	 */
	public function get_json_data() {
		if ( ! $this->has_init() ) {
			return array();
		}
		$json_data = parent::get_json_data();
		// Add the connected data.

		$json_data['connectedData'] = (object) $this->get_connected_data();
		return $json_data;
	}

	protected function init_render_data() {
		// Override in child classes.
	}
	/**
	 * Display the HTML output of the filter
	 *
	 * @param string $return_output   Whether to echo or return the output.
	 *
	 * @since    3.0.0
	 */
	public function render( $return_output = false ) {
		// We don't want to modify the internal values.
		$attributes = $this->get_attributes();

		$this->init_render_data();

		// Trigger action when starting.
		do_action( 'search-filter/fields/field/render/before', $attributes, $this->name );
		do_action( "search-filter/fields/{$this->name}/render/before", $attributes );

		// Modify args before render.
		$attributes = apply_filters( 'search-filter/fields/field/render/attributes', $attributes, $this->name );
		$attributes = apply_filters( "search-filter/fields/{$this->name}/render/attributes", $attributes );

		// Copy the modified attributes to the render data object to keep
		// in sync with the JSON data.
		$this->set_render_attributes( $attributes );
		Fields::register_active_field( $this );

		if ( $this->get_query_id() !== 0 ) {
			Queries::register_connected_query( $this->get_query_id() );
		}

		ob_start();

		$html_attributes = apply_filters( 'search-filter/fields/field/render/html_attributes', $this->get_html_attributes(), $this );
		$html_classes    = apply_filters( 'search-filter/fields/field/render/html_classes', $this->get_html_classes(), $this );

		$html_attributes['class'] = implode( ' ', $html_classes );

		echo '<div ' . Util::get_attributes_html( $html_attributes ) . '>';
		echo $this->build();
		echo '</div>';
		$output = ob_get_clean();

		// Modify output html.
		$output = apply_filters( 'search-filter/fields/field/render/output', $output, $this->name, $attributes );
		$output = apply_filters( "search-filter/fields/{$this->name}/render/output", $output, $attributes );

		if ( ! $return_output ) {
			echo $output;
		}

		// Trigger action when finished.
		do_action( 'search-filter/fields/field/render/after', $attributes, $this->name );
		do_action( "search-filter/fields/{$this->name}/render/after", $attributes );

		if ( $return_output ) {
			return $output;
		}
	}

	/**
	 * Return this fields input type
	 */
	public static function get_input_type() {
		return static::$input_type;
	}

	/**
	 * Sets a fields options (for fields that have multiple choices)
	 *
	 * @param array $options Array of options.
	 */
	public function set_options( $options ) {
		$this->options_init = true;
		$this->options      = $options;
	}

	/**
	 * Gets a fields options.
	 */
	public function get_options() {
		return $this->options;
	}
	/**
	 * Gets a fields options.
	 */
	public function has_options() {
		return $this->options_init;
	}

	/**
	 * Sets data to be used in the SSR pre-render.
	 *
	 * @param array $data An associative array of data.
	 */
	public function set_render_data( $data ) {
		$this->render_data = $data;
	}
	/**
	 * Updates the render data.
	 *
	 * @param array $data An associative array of data.
	 */
	/*
	public function update_render_data( $data ) {
		$existing_data = $this->get_render_data();
		$combined_data = wp_parse_args( $data, $existing_data );
		$this->set_render_data( $combined_data );
	} */
	/**
	 * Sets the callbacks to be used for escaping the render data.
	 *
	 * @param array $callbacks Associative array of callbacks names (as strings).
	 */
	public function set_render_escape_callbacks( $callbacks ) {
		$this->render_escape_callbacks = $callbacks;
	}
	/**
	 * Get the template base directory for this field.
	 *
	 * Override when extending this class to supply your own folders.
	 *
	 * @return string The template base directory.
	 */
	public function get_template_dir() {
		$field_type = $this->attributes['type'];
		return SEARCH_FILTER_PATH . 'includes' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $field_type;
	}

	/**
	 * Constructs the markup of the field input.
	 *
	 * This should be overriden in custom field classes.
	 *
	 * @since    3.0.0
	 *
	 * @return string The field markup.
	 */
	public function build() {
		$field_type   = $this->attributes['type'];
		$input_type   = $field_type === 'control' ? $this->get_attribute( 'controlType' ) : self::get_input_type();
		$template_dir = $this->get_template_dir();

		$data            = $this->attributes;
		$data['options'] = $this->get_options();

		// TODO - not every label needs an ID, maybe this should be moved into the individual field classes?
		$data['labelUid']                           = self::get_instance_id( 'label' );
		$this->render_escape_callbacks['labelUid']  = 'absint';
		$this->render_escape_callbacks['label']     = 'esc_html';
		$this->render_escape_callbacks['showLabel'] = '';

		$data_raw     = wp_parse_args( $this->render_data, $data );
		$data_escaped = $this->escape_render_data( $data_raw, $this->render_escape_callbacks );

		// TODO.
		$data            = apply_filters( 'search-filter/field/build/data', $data_escaped, $this->name );
		$template_output = '';
		if ( file_exists( $template_dir . DIRECTORY_SEPARATOR . $input_type . '.php' ) ) {
			ob_start();
			include $template_dir . DIRECTORY_SEPARATOR . $input_type . '.php';
			$template_output = ob_get_clean();
		} else {
			// TODO - throw warning about extending the Field class and implementing this method.
		}
		return $template_output;
	}

	/**
	 * Escape the render data, ignore keys that don't have callbacks.
	 *
	 * @param array $data The data to be escaped.
	 * @param array $callbacks The callbacks used to escape the data.
	 *
	 * @return array The escaped data object.
	 */
	private function escape_render_data( $data, $callbacks ) {
		$escaped_data = array();
		foreach ( $data as $key => $value ) {
			if ( isset( $callbacks[ $key ] ) ) {
				$esc_callback = $callbacks[ $key ];
				if ( is_array( $value ) && is_array( $esc_callback ) ) {
					$escaped_data[ $key ] = array();
					foreach ( $value as $child ) {
						$escaped_child = $this->escape_render_data( $child, $esc_callback );
						array_push( $escaped_data[ $key ], $escaped_child );
					}
				} elseif ( $esc_callback === '' ) {
					// Then there was no callback specified.
					$escaped_data[ $key ] = $value;
				} else {
					// Run an escape callback if it exists.
					$escaped_data[ $key ] = call_user_func( $callbacks[ $key ], $data[ $key ] );
				}
			}
		}
		// Skip any keys that don't have a callback.
		return $escaped_data;
	}

	/**
	 * Sets the Field context (admin, block-editor, elementor, beaverbuilder etc)
	 *
	 * @param string $context The context to set.
	 * @param string $path The path of the context.
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}
	/**
	 * Sets the Field context path - eg 'post/123'
	 *
	 * @param string $context The context to set.
	 * @param string $path The path of the context.
	 */
	public function set_context_path( $path ) {
		$this->context_path = $path;
	}
	/**
	 * Gets the query ID based on the query integration setting.
	 *
	 * @return string The URL prefix.
	 */
	public function get_query_id() {
		return $this->query_id;
	}
	public function refresh_query_id() {
		if ( isset( $this->attributes['queryId'] ) ) {
			$this->query_id = absint( $this->attributes['queryId'] );
		}
	}

	/**
	 * Saves the field
	 *
	 * @return int The saved field ID.
	 */
	public function save( $args = array() ) {
		$this->refresh_query_id();
		$this->regenerate_css();

		$extra_args = array(
			'context'      => $this->context,
			'context_path' => $this->context_path,
			'query_id'     => $this->query_id,
			'css'          => $this->get_css(),
		);

		return parent::save( $extra_args );
	}

	/**
	 * Gets the prefix to be added to URLs to avoid collisions.
	 *
	 * @return string The URL prefix.
	 */
	public static function url_prefix() {
		// TODO.
		return '_';
	}

	/**
	 * Creates a new instance of the field class with given data.
	 *
	 * // TODO - this needs reworking - we probably want to pass a name, type and attributes at least.
	 *
	 * @param array $attributes The init field attributes.
	 * @param array $context Context data of the field, contains keys 'context' and 'path'.
	 * @return Field The new instance of the field class.
	 */
	public static function create( $attributes = array(), $context = array() ) {
		$static_class = static::class;
		$new_field    = new $static_class();
		$new_field->set_attributes( $attributes );

		if ( isset( $context['context'] ) ) {
			$new_field->set_context( $context['context'] );
		}
		if ( isset( $context['path'] ) ) {
			$new_field->set_context_path( $context['path'] );
		}
		return $new_field;
	}

	/**
	 * Process the attributes and run init local vars
	 *
	 * @param array $attributes  Field attributes.
	 *
	 * @since    3.0.0
	 */
	public function set_attributes( $attributes, $replace = false ) {
		parent::set_attributes( $attributes, $replace );
		$this->refresh_query_id();
		$this->init();
	}
	/**
	 * Creates a new instance of the field from a database record.
	 *
	 * @param StdClass $item The database record.
	 *
	 * @return Field The new instance of the field class.
	 */
	public static function create_from_record( $item ) {
		$static_class = static::class;
		$new_field    = new $static_class();
		$new_field->load_record( $item );
		return $new_field;
	}

	/**
	 * Find a field by conditions
	 *
	 * @param array  $conditions  Column name => value pairs.
	 * @param string $return_type  Whether to return the object or the record.
	 * @return self|\Search_Filter\Database\Rows\Field|\WP_Error
	 *
	 * @throws Exception If the conditions are not an array.
	 */
	public static function find( $conditions, $return_type = 'object' ) {
		// If conditions are not an array then throw an exception.
		if ( ! is_array( $conditions ) ) {
			throw new Exception( 'Conditions must be an array.', SEARCH_FILTER_EXCEPTION_BAD_FIND_CONDITIONS );
		}
		$query_args = array(
			'number'  => 1, // Only retrieve a single record.
			'orderby' => 'date_published',
			'order'   => 'asc',
		);
		$query_args = wp_parse_args( $conditions, $query_args );
		/**
		 * TODO - we probably want to wrap this in our settings API
		 * so we never call the same field twice (maybe we need to
		 * update the API to support searching for fields without
		 * query ID)
		 */
		$query = new Field_Query( $query_args );

		// Bail if nothing found.
		if ( empty( $query->items ) ) {
			return new \WP_Error( 'search_filter_field_not_found', __( 'Field not found.', 'search-filter' ), array( 'status' => 404 ) );
		}

		if ( $return_type === 'record' ) {
			return $query->items[0];
		}

		// Create the field.
		$field = Field_Factory::create_from_record( $query->items[0] );
		return $field;
	}

	/**
	 * Generates a unique ID based on the subject.
	 *
	 * @param array $subject The subject to generate the ID for.
	 *
	 * @return int The instance ID.
	 */
	public static function get_instance_id( $subject ) {
		if ( ! isset( self::$instance_ids[ $subject ] ) ) {
			self::$instance_ids[ $subject ] = 0;
		} else {
			++self::$instance_ids[ $subject ];
		}
		return self::$instance_ids[ $subject ];
	}

	/**
	 * Adds an icon name to the icons array.
	 *
	 * @param string $icon_name The name of the icon to add.
	 */
	public function add_icon( $icon_name ) {
		$this->icons[] = $icon_name;
	}

	/**
	 * Gets the icons array.
	 *
	 * @return array The icons array.
	 */
	public function get_icons() {
		$icons = apply_filters( 'search-filter/fields/field/get_icons', $this->icons, $this );
		return $icons;
	}

	/**
	 * Deletes an icon name from the icons array.
	 */
	public function remove_icon( $icon_name ) {
		$index = array_search( $icon_name, $this->icons, true );
		if ( false !== $index ) {
			unset( $this->icons[ $index ] );
		}
	}

	/**
	 * Regenerates the CSS for the style preset.
	 *
	 * @since   3.0.0
	 */
	public function regenerate_css() {
		$this->css = $this->generate_css();
	}


	/**
	 * Gets the query mode for the field.
	 *
	 * @return string The query mode.
	 */
	public function get_query_type() {
		return apply_filters( 'search-filter/fields/field/get_query_type', $this->query_type, $this );
	}

	/**
	 * Sets the query mode for the field.
	 *
	 * @param string $query_type The query mode.
	 */
	public function set_query_type( $query_type ) {
		$this->query_type = $query_type;
	}

	/**
	 * Generates the CSS for the style preset.
	 *
	 * @return string The generated CSS.
	 */
	private function generate_css() {
		$css = '';
		// Get the base styles class for the ID.
		// Increase specificity to override the template styles which are added using styles + input types.
		$styles_class = '.search-filter-field.search-filter-field--id-' . intval( $this->get_id() );
		// Ensure styles are added to the popup too.
		$styles_class .= ', .search-filter-field__popup.search-filter-field__popup--id-' . intval( $this->get_id() );

		$css .= $styles_class . '{';
		// Now try to parse any styles attributes.
		$css .= CSS_Loader::clean_css( Style::create_attributes_css( $this->get_attributes() ) );
		// Normally we only generate CSS vars for style attributes, but setting the margin
		// upfront on any field overrides block spacing, so lets only set the margin if it
		// the property is set.
		if ( $this->get_attribute( 'fieldMargin' ) ) {
			$css .= 'margin: var(--search-filter-field-margin, inherit );';
		}
		$css .= '}';

		return $css;
	}

	/**
	 * Parses the attributes into CSS styles (variables).
	 *
	 * Currently a stub as we now use the style class to generate the styles
	 * CSS.  We might still need this in the future for things that are not
	 * included in the styles section.
	 *
	 * @since   3.0.0
	 *
	 * @param array $attributes  The saved style attributes.
	 *
	 * @return string The generated CSS.
	 */
	public function create_attributes_css( $attributes ) {
		$css = '';
		$css = apply_filters( 'search-filter/styles/style/create_attributes_css', $css, $attributes );
		return $css;
	}

	/**
	 * Gets the attributes as an array.
	 *
	 * @return array
	 */
	public function get_attributes( $unfiltered = false ) {
		$attributes = parent::get_attributes( $unfiltered );
		if ( ! $unfiltered ) {
			$attributes = apply_filters( 'search-filter/fields/field/get_attributes', $attributes, $this );
		}
		return $attributes;
	}

	/**
	 * Gets an attribute
	 *
	 * @param string $attribute_name   The attribute name to get.
	 * @param bool   $unfiltered       Whether to return the unfiltered attribute.
	 *
	 * @return mixed The attribute value or false if no attribute found.
	 */
	public function get_attribute( $attribute_name, $unfiltered = false ) {
		$attribute = parent::get_attribute( $attribute_name, $unfiltered );
		if ( ! $unfiltered ) {
			$attribute = apply_filters( 'search-filter/fields/field/get_attribute', $attribute, $attribute_name, $this );
		}

		return $attribute;
	}

	/**
	 * Get the label for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_label() {
		return '';
	}

	/**
	 * Stub function for registering any settings/apis etc that need to exist
	 * permanently for this field.
	 *
	 * TODO - this is probably being overwritten by the last class to call this?
	 */
	public static function register() {
	}

	/**
	 * Add a handler to the field.
	 *
	 * @param string $name The name of the handler.
	 * @param mixed  $handler The handler.
	 * @return void
	 */
	public static function add_handler( $name, $handler ) {
		self::$handlers[ $name ] = $handler;
	}

	/**
	 * Get a handler by name.
	 *
	 * @param string $name The name of the handler.
	 * @return mixed The handler.
	 */
	public static function get_handler( $name ) {
		if ( isset( self::$handlers[ $name ] ) ) {
			return self::$handlers[ $name ];
		}
		return null;
	}

	/**
	 * Get all handlers.
	 *
	 * @return array The handlers.
	 */
	public static function get_handlers() {
		return self::$handlers;
	}

	/**
	 * Remove a handler by name.
	 *
	 * @param string $name The name of the handler.
	 * @return void
	 */
	public static function remove_handler( $name ) {
		if ( isset( self::$handlers[ $name ] ) ) {
			unset( self::$handlers[ $name ] );
		}
	}
}
