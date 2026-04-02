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

use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\Component_Loader;
use Search_Filter\Core\CSS_Loader;
use Search_Filter\Core\Deprecations;
use Search_Filter\Util;
use Search_Filter\Database\Queries\Fields as Field_Query;
use Search_Filter\Fields;
use Search_Filter\Styles;
use Search_Filter\Styles\Style;
use Search_Filter\Record_Base;
use Search_Filter\Core\Exception;
use Search_Filter\Features;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;

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
	 * Usually we only want to instantiate & lookup a record once,
	 * so store the instance for easy re-use later.
	 *
	 * @var array
	 */
	protected static $instances = array();

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
	 * List of component dependencies.
	 *
	 * @var array
	 */
	public $components = array();

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
	 * The processed (cached) styles.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_styles    The processed styles, null if not processed yet.
	 */
	protected static $processed_styles = null;
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
	 * The processed (cached) setting support.
	 *
	 * @since 3.2.0
	 * @access private
	 * @var array|null $processed_setting_support    The processed settings, null if not processed yet.
	 */
	protected static $processed_setting_support = null;

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
	 * The labels for the values
	 *
	 * @var array
	 */
	protected $value_labels = array();

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
	 * Whether the options have been initialised for this field.
	 *
	 * @var bool
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
	 * Track if the regiseterd function has been run.
	 *
	 * This is used to prevent the function from running multiple times.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	protected static $has_registered = false;

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
	 * @var array
	 */
	private $connected_data = array();

	/**
	 * Handlers for the field class.
	 *
	 * A way to extend the field with custom functionality.
	 *
	 * @var array
	 */
	public static $handlers = array();

	/**
	 * The locations the field is used in.
	 *
	 * @var array|null
	 */
	private $locations = null;

	/**
	 * The render attributes.
	 *
	 * @var array
	 */
	private $render_attributes = array();

	/**
	 * Whether or not the field requires the pro plugin.
	 *
	 * @since    3.0.0
	 *
	 * @var      bool
	 */
	protected static $requires_pro = false;

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
	 * Get the supported styles for the field.
	 *
	 * @return array
	 */
	public static function get_styles_support() {

		if ( static::$processed_styles ) {
			return static::$processed_styles;
		}

		$styles_support = static::$styles;

		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/fields/field/get_style_support', '3.2.0', 'search-filter/fields/field/get_styles_support' );
		$styles_support = apply_filters( 'search-filter/fields/field/get_style_support', $styles_support, static::$type, static::$input_type );

		$styles_support = apply_filters( 'search-filter/fields/field/get_styles_support', $styles_support, static::$type, static::$input_type );

		static::$processed_styles = $styles_support;
		return static::$processed_styles;
	}

	/**
	 * Get the supported settings for the field.
	 *
	 * @return array
	 */
	public static function get_setting_support() {

		if ( static::$processed_setting_support ) {
			return static::$processed_setting_support;
		}
		$parsed_setting_support = array();

		foreach ( static::$setting_support as $setting_name => $support ) {
			$parsed_setting_support[ $setting_name ] = array();

			// We can have `values` and `conditions` keys, or it can just be true or false.
			if ( is_bool( $support ) ) {
				$parsed_setting_support[ $setting_name ] = $support;
			} elseif ( isset( $support['conditions'] ) ) {
				// Always wrap the conditions in an OR relation, so we can insert alternative
				// routes when we extend the conditions.
				$parsed_setting_support[ $setting_name ]['conditions'] = array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'relation' => 'AND',
							'rules'    => $support['conditions'],
						),
					),
				);
			}
			if ( isset( $support['values'] ) ) {
				$parsed_setting_support[ $setting_name ]['values'] = $support['values'];
			}
		}

		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/get_setting_support', '3.2.0', 'search-filter/fields/field/get_setting_support' );
		$parsed_setting_support = apply_filters( 'search-filter/field/get_setting_support', $parsed_setting_support, static::$type, static::$input_type );

		$parsed_setting_support = apply_filters( 'search-filter/fields/field/get_setting_support', $parsed_setting_support, static::$type, static::$input_type );

		static::$processed_setting_support = $parsed_setting_support;
		return static::$processed_setting_support;
	}

	/**
	 * Get the names of the settings that the field supports.
	 *
	 * Ignores conditions so we know what the complete set of settings.
	 *
	 * @return array
	 */
	public static function get_setting_support_names() {
		return array_keys( static::get_setting_support() );
	}
	/**
	 * Get the names of the settings that the field supports.
	 *
	 * Ignores conditions so we know what the complete set of settings.
	 *
	 * @return array
	 */
	public static function get_style_support_names() {
		return array_keys( static::get_styles_support() );
	}

	/**
	 * Get the interaction type for this field.
	 *
	 * Returns how this field interacts with data:
	 * - 'choice': Discrete value selection
	 * - 'range': Numeric or date ranges
	 * - 'search': Full-text search
	 * - null: No data interaction
	 *
	 * This method calls calc_interaction_type() to get the raw value, then applies
	 * the filter hook. Subclasses should override calc_interaction_type() instead
	 * of this method.
	 *
	 * @since 3.2.0
	 *
	 * @return string|null The interaction type.
	 */
	public function get_interaction_type(): ?string {
		$interaction_type = $this->calc_interaction_type();

		/**
		 * Filter the interaction type for a field.
		 *
		 * @since 3.2.0
		 *
		 * @param string|null $interaction_type The interaction type ('choice', 'range', 'search', or null).
		 * @param Field       $field            The field instance.
		 */
		return apply_filters(
			'search-filter/fields/field/get_interaction_type',
			$interaction_type,
			$this
		);
	}

	/**
	 * Calculate the interaction type for this field.
	 *
	 * Subclasses should override this method to return their specific interaction type.
	 * The base implementation returns the field's type attribute for backwards compatibility.
	 *
	 * @since 3.2.0
	 *
	 * @return string|null The raw interaction type before filtering.
	 */
	protected function calc_interaction_type(): ?string {
		// Default: return field type for 3rd party backwards compatibility.
		return $this->get_attribute( 'type' );
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
	 * @param bool   $is_required     Whether the setting is required.
	 *
	 * @return array The updated setting support.
	 */
	public static function add_setting_support_condition( $setting_support, $setting_name, $new_conditions, $is_required = true ) {

		$conditions = array(
			'relation' => 'OR',
			'rules'    => array(
				array(
					'relation' => 'AND',
					'rules'    => array(), // Push here to add required conditions.
				),
				// Push here to add alternative logic routes.
			),
		);

		// Preserve existing conditions.
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
	 * Add values to a setting support.
	 *
	 * @param array  $setting_support The setting support to add values to.
	 * @param string $setting_name    The setting name to add values to.
	 * @param array  $values          The values to add.
	 *
	 * @return array The updated setting support.
	 */
	public static function add_setting_support_value( $setting_support, $setting_name, $values ) {
		// Progressively init values so can preserve existing values.
		if ( ! isset( $setting_support[ $setting_name ] ) || is_bool( $setting_support[ $setting_name ] ) ) {
			$setting_support[ $setting_name ] = array();
		}
		if ( ! isset( $setting_support [ $setting_name ]['values'] ) ) {
			$setting_support[ $setting_name ]['values'] = array();
		}

		$setting_support[ $setting_name ]['values'] = array_merge(
			$setting_support[ $setting_name ]['values'],
			$values
		);

		return $setting_support;
	}
	/**
	 * Init the field from already loaded attributes.
	 */
	public function init() {

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

		parent::init();
	}

	/**
	 * Inits the data from a DB record.
	 *
	 * @param \Search_Filter\Database\Rows\Field $item Database record.
	 */
	public function load_record( $item ) {
		$this->set_id( $item->get_id() );
		$this->set_status( $item->get_status() );
		$this->set_name( $item->get_name() );
		$this->set_record( $item );
		$this->set_attributes( $item->get_attributes() );
		$this->set_date_modified( $item->get_date_modified() );
		$this->set_date_created( $item->get_date_created() );

		$this->set_context( $item->get_context() );
		$this->set_context_path( $item->get_context_path() );
		$this->set_css( $item->get_css() );

		$this->init();
	}

	/**
	 * Generate the unique name to be used in the URL.
	 *
	 * @return string The name to be used in the URL
	 */
	public function get_url_name() {
		$url_name = 'field_' . $this->get_id();

		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/url_name', '3.2.0', 'search-filter/fields/field/url_name' );
		$url_name = apply_filters( 'search-filter/field/url_name', $url_name, $this );
		// Filter the URL name.
		$url_name = apply_filters( 'search-filter/fields/field/url_name', $url_name, $this );

		return $url_name;
	}

	/**
	 * Generates a results URL for the field.
	 *
	 * We usually want this to be empty, but sometimes a field
	 * will want to link to something like a taxonomy archive.
	 *
	 * @return array The name to be used in the URL
	 */
	public function get_url_template() {
		// Filter the URL template.
		return apply_filters( 'search-filter/fields/field/url_template', array(), $this );
	}

	/**
	 * Parses a value from the URL.
	 */
	public function parse_url_value() {
		$url_param_name = self::url_prefix() . $this->get_url_name();

		// Allow override via hook.
		$values = apply_filters( 'search-filter/fields/field/parse_url_value', '', $this );
		if ( ! empty( $values ) ) {
			// Split by comma, then restore any unit separators back to commas.
			$parsed_values = explode( ',', $values );
			$parsed_values = array_map(
				function ( $v ) {
					return str_replace( "\x1F", ',', $v );
				},
				$parsed_values
			);
			$this->set_values( $parsed_values );
		}

		// Notice: the request var has not been sanitized yet, its the raw value from the either $_GET or $_POST
		// with wp_unslash already applied.
		$request_var = Util::get_request_var( $url_param_name );

		// Proceed as normal by trying to get the value from the URL.
		if ( $request_var === null ) {
			return;
		}

		$values = sanitize_text_field( $request_var );

		if ( $values !== '' ) {
			// Split by comma, then restore any unit separators back to commas.
			$parsed_values = explode( ',', $values );
			$parsed_values = array_map(
				function ( $v ) {
					return str_replace( "\x1F", ',', $v );
				},
				$parsed_values
			);
			$this->set_values( $parsed_values );
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
		// Legacy support for incorrectly named filter.
		Deprecations::add_filter( 'search-filter/field/wp_query_args', '3.2.0', 'search-filter/fields/field/wp_query_args' );
		$query_args = apply_filters( 'search-filter/field/wp_query_args', $query_args, $this );
		// Filter the WP_Query args.
		$query_args = apply_filters( 'search-filter/fields/field/wp_query_args', $query_args, $this );
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
	 * @param string $position   Position to add the class ('after' or 'before').
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
	 *
	 * @return array  The html attributes array.
	 */
	public function get_html_attributes() {
		return $this->html_attributes;
	}

	/**
	 * Get the html classes
	 *
	 * @since    3.0.4
	 *
	 * @return array  The html classes array.
	 */
	public function get_html_classes() {
		return $this->html_classes;
	}


	/**
	 * Get the values that are set for this field
	 *
	 * @since    3.0.0
	 *
	 * @return array  The values array.
	 */
	public function get_values() {
		return apply_filters( 'search-filter/fields/field/values', $this->values, $this );
	}

	/**
	 * Set the values labels.
	 *
	 * @since 3.0.0
	 *
	 * @param array $value_labels The value labels to set.
	 * @param bool  $replace      Whether to replace the existing value labels.
	 */
	public function set_value_labels( $value_labels, $replace = false ) {
		if ( $replace ) {
			$this->value_labels = $value_labels;
		} else {
			$this->value_labels = array_replace( $this->value_labels, $value_labels );
		}
	}
	/**
	 * Get the values that are set for this field
	 *
	 * @since    3.0.0
	 *
	 * @return array  The value + label pairs array.
	 */
	public function get_value_labels() {
		return apply_filters( 'search-filter/fields/field/value_labels', $this->value_labels, $this );
	}

	/**
	 * If there are any values set for this field.
	 *
	 * @since    3.0.0
	 *
	 * @return bool  True if the field has values, false otherwise.
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
	 * @param mixed  $data The data to store.
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

	/**
	 * Initializes render data for the field.
	 *
	 * Override in child classes to set up field-specific render data.
	 */
	protected function init_render_data() {
		// Override in child classes.
	}
	/**
	 * Display the HTML output of the filter
	 *
	 * @since    3.0.0
	 *
	 * @param bool $return_output Whether to return the output or echo it.
	 *
	 * @return string|void Nothing if $return_output is false, otherwise the field HTML.
	 */
	public function render( $return_output = false ) {
		if ( ! $this->has_init() ) {
			return;
		}
		if ( ! $this->is_enabled() ) {
			return;
		}
		// We don't want to modify the internal values.
		$attributes = $this->get_attributes();

		$this->init_render_data();

		// Trigger action when starting.
		do_action( 'search-filter/fields/field/render/before', $attributes, $this->name );
		do_action( "search-filter/fields/{$this->name}/render/before", $attributes );

		// Modify args before render.
		$attributes = apply_filters( 'search-filter/fields/field/render/attributes', $attributes, $this );
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Util::get_attributes_html() handles escaping.
		echo '<div ' . Util::get_attributes_html( $html_attributes ) . '>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- build() returns escaped HTML.
		echo $this->build();
		echo '</div>';

		$output = ob_get_clean();

		// Modify output html.
		$output = apply_filters( 'search-filter/fields/field/render/output', $output, $this->name, $attributes );
		$output = apply_filters( "search-filter/fields/{$this->name}/render/output", $output, $attributes );

		if ( ! $return_output ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output already escaped by filters.
			echo $output;
		}

		// Trigger action when finished.
		do_action( 'search-filter/fields/field/render/after', $attributes, $this->name );
		do_action( "search-filter/fields/{$this->name}/render/after", $attributes );

		if ( Features::is_enabled( 'dynamicAssetLoading' ) ) {
			$this->enqueue_assets();
		}

		if ( $return_output ) {
			return $output;
		}
	}

	/**
	 * Enqueue the assets for the field.
	 *
	 * @since 3.1.3
	 */
	public function enqueue_assets() {
		// Try to enqueue any assets required by the field.
		$components_to_load = $this->get_components();

		// Ensure the frontend is enqueued, we can't solely rely on the dependencies of the
		// components to trigger the frontend because there might not be any individual components
		// that need loading.
		Asset_Loader::enqueue( array( 'search-filter-frontend', 'search-filter-frontend-ugc' ) );

		foreach ( $components_to_load as $component_name ) {
			$component_handle = Component_Loader::get_handle( $component_name );
			Asset_Loader::enqueue( array( $component_handle ) );
		}
	}

	/**
	 * Return this fields input type
	 *
	 * @return string  The attribute input type.
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
	 *
	 * @return array  The options array with value/label pairs.
	 */
	public function get_options() {
		return $this->options;
	}
	/**
	 * Gets a fields options.
	 *
	 * @return bool
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

	/*
	 * Updates the render data.
	 *
	 * @param array $data An associative array of data.
	 *
	public function update_render_data( $data ) {
		$existing_data = $this->get_render_data();
		$combined_data = wp_parse_args( $data, $existing_data );
		$this->set_render_data( $combined_data );
	}
	*/
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
		if ( ! $this->get_attribute( 'type' ) ) {
			return '';
		}
		$field_type = $this->get_attribute( 'type' );
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
		if ( ! $this->get_attribute( 'type' ) ) {
			return '';
		}
		$field_type   = $this->get_attribute( 'type' );
		$input_type   = $field_type === 'control' ? $this->get_attribute( 'controlType' ) : self::get_input_type();
		$template_dir = $this->get_template_dir();

		$data            = $this->get_attributes();
		$data['options'] = $this->get_options();

		// TODO - not every label needs an ID, maybe this should be moved into the individual field classes?
		$data['labelUid']                           = self::get_instance_id( 'label' );
		$this->render_escape_callbacks['labelUid']  = 'absint';
		$this->render_escape_callbacks['label']     = 'esc_html';
		$this->render_escape_callbacks['showLabel'] = '';

		$data_raw     = wp_parse_args( $this->render_data, $data );
		$data_escaped = $this->escape_render_data( $data_raw, $this->render_escape_callbacks );

		$data            = apply_filters( 'search-filter/field/build/data', $data_escaped, $this->name );
		$template_output = '';
		if ( file_exists( $template_dir . DIRECTORY_SEPARATOR . $input_type . '.php' ) ) {
			ob_start();
			include $template_dir . DIRECTORY_SEPARATOR . $input_type . '.php';
			$template_output = ob_get_clean();
		}
		// TODO - throw warning about extending the Field class and implementing this method.
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
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}

	/**
	 * Sets the Field context path - eg 'post/123'
	 *
	 * @param string $path The path of the context.
	 */
	public function set_context_path( $path ) {
		$this->context_path = $path;
	}

	/**
	 * Gets the Field context.
	 *
	 * @return string The context.
	 */
	public function get_context() {
		return $this->context;
	}
	/**
	 * Gets the Field context path.
	 *
	 * @return string The context path.
	 */
	public function get_context_path() {
		return $this->context_path;
	}

	/**
	 * Get the ID of the connected query for the field.
	 *
	 * @return int The query ID.
	 */
	public function get_query_id() {
		return $this->query_id;
	}

	/**
	 * Get the connected query instance.
	 *
	 * @return \Search_Filter\Queries\Query|null
	 */
	public function get_query() {
		$query = Query::get_instance( $this->get_query_id() );
		if ( is_wp_error( $query ) ) {
			return null;
		}
		return $query;
	}

	/**
	 * Refresh the query ID from the field attributes.
	 *
	 * @since 3.0.0
	 */
	public function refresh_query_id() {
		$this->query_id = absint( $this->get_attribute( 'queryId' ) );
	}

	/**
	 * Saves the field
	 *
	 * @param array $args The arguments to save the field with.
	 * @return int The saved field ID.
	 */
	public function save( array $args = array() ) {
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
		return apply_filters( 'search-filter/fields/field/url_prefix', '_' );
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
		$new_field->init();
		return $new_field;
	}

	/**
	 * Find a field by conditions
	 *
	 * @param array  $conditions  Column name => value pairs.
	 * @param string $return_type  Whether to return the object or the record.
	 * @return \Search_Filter\Fields\Field|\Search_Filter\Database\Rows\Field|\WP_Error|null
	 *
	 * @throws Exception If the conditions are not an array.
	 */
	public static function find( array $conditions, string $return_type = 'object' ) {

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
	 * @param string|int $subject The subject to generate the ID for.
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
	public function add_icon( string $icon_name ) {
		$this->icons[] = $icon_name;
	}

	/**
	 * Gets the components array.
	 *
	 * @return array The components array.
	 */
	public function get_components() {
		$components = apply_filters( 'search-filter/fields/field/get_components', $this->components, $this );
		return $components;
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
	 *
	 * @param string $icon_name The name of the icon to delete.
	 */
	public function remove_icon( string $icon_name ) {
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

		// Prevent updating the CSS until the user has opted-in to version 2.
		$assets_version = Asset_Loader::get_db_version();
		if ( $assets_version !== 2 ) {
			return;
		}

		$this->css = $this->generate_css();
	}

	/**
	 * Generates the CSS for the style preset.
	 *
	 * @return string The generated CSS.
	 */
	private function generate_css() {
		$css = '';

		$attributes_css = Style::create_attributes_css( $this->get_attributes() );

		if ( empty( $attributes_css ) ) {
			return $css;
		}

		// Get the base styles class for the ID.
		// Increase specificity to override the template styles which are added using styles + input types.
		$styles_class = '.search-filter-field.search-filter-field--id-' . intval( $this->get_id() );
		// Ensure styles are added to the popup too.
		$styles_class .= ', .search-filter-field__popup.search-filter-field__popup--id-' . intval( $this->get_id() );

		$css .= $styles_class . '{';
		// Now try to parse any styles attributes.
		$css .= CSS_Loader::clean_css( $attributes_css );
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
	public function create_attributes_css( array $attributes ) {
		$css = '';
		$css = apply_filters( 'search-filter/styles/style/create_attributes_css', $css, $attributes );
		return $css;
	}

	/**
	 * Gets the attributes as an array.
	 *
	 * @param bool $unfiltered Whether to return the unfiltered attributes.
	 * @return array
	 */
	public function get_attributes( bool $unfiltered = false ) {
		$attributes = parent::get_attributes( $unfiltered );
		if ( ! $unfiltered ) {
			$attributes = apply_filters( 'search-filter/fields/field/get_attributes', $attributes, $this );
		}
		return $attributes;
	}

	/**
	 * Adds a location for the field.
	 *
	 * @since   3.1.7
	 *
	 * @param string $location The location to add.
	 */
	public function add_location( string $location ) {
		$location  = (string) $location;
		$locations = $this->get_locations();
		if ( ! in_array( $location, $locations, true ) ) {
			$locations[] = $location;
			$this->set_locations( $locations );
		}
	}

	/**
	 * Removes a location for the field.
	 *
	 * @since   3.1.7
	 *
	 * @param string $location The location to remove.
	 */
	public function remove_location( string $location ) {
		$location  = (string) $location;
		$locations = $this->get_locations();
		if ( in_array( $location, $locations, true ) ) {
			$index = array_search( $location, $locations, true );
			if ( false !== $index ) {
				unset( $locations[ $index ] );
			}
			$this->set_locations( $locations );
		}
	}

	/**
	 * Sets the locations for the field.
	 *
	 * TODO - we should require save to commit locations but right now it just
	 * commits the location to meta immediately.
	 *
	 * @since   3.1.7
	 *
	 * @param array $locations The locations to set.
	 */
	public function set_locations( array $locations ) {
		self::delete_meta( $this->get_id(), 'locations' );
		$locations = array_unique( $locations );

		foreach ( $locations as $location ) {
			self::add_meta( $this->get_id(), 'locations', $location );
		}
		$this->locations = $locations;
	}

	/**
	 * Gets the locations for the field.
	 *
	 * @return array The locations.
	 */
	public function get_locations() {
		if ( $this->locations !== null ) {
			return $this->locations;
		}
		$this->locations = array();
		$locations       = self::get_meta( $this->get_id(), 'locations' );
		if ( ! empty( $locations ) ) {
			$locations = array_unique( $locations );
			foreach ( $locations as $location ) {
				$this->locations[] = (string) $location;
			}
		}
		return $this->locations;
	}

	/**
	 * Checks if the field has a location.
	 *
	 * @param string $location The location to check.
	 * @return bool True if the field has the location, false otherwise.
	 */
	public function has_location( string $location ) {
		$locations = $this->get_locations();
		return in_array( (string) $location, $locations, true );
	}

	/**
	 * Gets an attribute
	 *
	 * @param string $attribute_name   The attribute name to get.
	 * @param bool   $unfiltered       Whether to return the unfiltered attribute.
	 *
	 * @return mixed The attribute value or false if no attribute found.
	 */
	public function get_attribute( string $attribute_name, bool $unfiltered = false ) {
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
	 * Get the description for the input type.
	 *
	 * @return string The label.
	 */
	public static function get_description() {
		return '';
	}

	/**
	 * Stub function for registering any settings/apis etc that need to exist
	 * permanently for this field.
	 */
	public static function register() {}

	/**
	 * Add a handler to the field.
	 *
	 * @param string $name The name of the handler.
	 * @param mixed  $handler The handler.
	 * @return void
	 */
	public static function add_handler( string $name, $handler ) {
		self::$handlers[ $name ] = $handler;
	}

	/**
	 * Get a handler by name.
	 *
	 * @param string $name The name of the handler.
	 * @return mixed The handler.
	 */
	public static function get_handler( string $name ) {
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
	public static function remove_handler( string $name ) {
		if ( isset( self::$handlers[ $name ] ) ) {
			unset( self::$handlers[ $name ] );
		}
	}

	/**
	 * Check if the field requires the Pro version.
	 *
	 * @return bool True if the field requires Pro, false otherwise.
	 */
	public static function requires_pro() {
		return static::$requires_pro;
	}
}
