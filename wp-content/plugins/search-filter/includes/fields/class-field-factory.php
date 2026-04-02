<?php
/**
 * Class for handling the creation of fields.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Fields;

use Search_Filter\Core\Exception;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instantiates fields using the correct class.
 */
class Field_Factory {
	/**
	 * Keeps track of whether `init` function has been called already.
	 *
	 * @var boolean
	 */
	private static $has_registered_types = false;
	/**
	 * The field classes mapped by their types and their input types.
	 *
	 * @var array
	 */
	private static $fields = array(
		'search'   => array(),
		'choice'   => array(),
		'range'    => array(),
		'advanced' => array(),
		'control'  => array(),
	);

	/**
	 * Initialize the field factory and register field types.
	 */
	public static function init() {
		if ( self::$has_registered_types ) {
			return;
		}

		$init_fields = array(
			'search'   => array(
				'text'         => '\Search_Filter\Fields\Search\Text',
				'autocomplete' => '\Search_Filter\Fields\Search\Autocomplete',
			),
			'choice'   => array(
				'select'   => '\Search_Filter\Fields\Choice\Select',
				'radio'    => '\Search_Filter\Fields\Choice\Radio',
				'checkbox' => '\Search_Filter\Fields\Choice\Checkbox',
				'button'   => '\Search_Filter\Fields\Choice\Button',
			),
			'range'    => array(
				'select' => '\Search_Filter\Fields\Range\Select',
				'radio'  => '\Search_Filter\Fields\Range\Radio',
				'number' => '\Search_Filter\Fields\Range\Number',
				'slider' => '\Search_Filter\Fields\Range\Slider',
			),
			'advanced' => array(
				'date_picker' => '\Search_Filter\Fields\Advanced\Date_Picker',
			),
			'control'  => array(
				'submit'    => '\Search_Filter\Fields\Control\Submit',
				'reset'     => '\Search_Filter\Fields\Control\Reset',
				'sort'      => '\Search_Filter\Fields\Control\Sort',
				'per_page'  => '\Search_Filter\Fields\Control\Per_Page',
				'selection' => '\Search_Filter\Fields\Control\Selection',
				'load_more' => '\Search_Filter\Fields\Control\Load_More',
			),
		);

		foreach ( $init_fields as $field_type => $input_types ) {
			foreach ( $input_types as $input_type => $class_name ) {
				self::register_field_input( $field_type, $input_type, $class_name );
			}
		}

		self::register();
		self::$has_registered_types = true;
	}
	/**
	 * Initialises mapping of classes for our different field + input types
	 *
	 * @return void
	 */
	private static function register() {
		if ( self::$has_registered_types ) {
			return;
		}
		do_action( 'search-filter/fields/register' );
	}
	/**
	 * Sets has registered field
	 *
	 * Necessary for testing as we can't assert in hooks so must be disabled manually.
	 *
	 * @param bool $has_registered_types Whether fields have been registered.
	 */
	public static function set_has_registered_types( $has_registered_types ) {
		self::$has_registered_types = $has_registered_types;
	}
	/**
	 * Registers a class to be used for a field type and sub input type
	 *
	 * @param string $field_type  The main field type - eg, search, filter or control.
	 * @param string $input_type  The input type for a field type.
	 * @param string $class_name  The string name of the class.
	 * @return bool
	 * @throws Exception For various conditions, such as invalid field type or input type.
	 */
	public static function register_field_input( $field_type, $input_type, $class_name ) {
		if ( true === self::$has_registered_types ) {
			throw new Exception( esc_html( __( 'You can only register fields inside the action "search-filter/fields/register".', 'search-filter' ) ), SEARCH_FILTER_EXCEPTION_FIELD_NOT_READY ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		if ( ! isset( self::$fields[ $field_type ] ) ) {
			/* translators: %s is the internal field type - eg search/filter/control */
			throw new Exception( esc_html( sprintf( __( 'The field type "%1$s" has not been found.', 'search-filter' ), $field_type ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		if ( isset( self::$fields[ $field_type ][ $input_type ] ) ) {
			/* translators: %1$s is the internal field type - eg search/filter/control', %2$s is the internal input type - eg radio/checkbox/text */
			throw new Exception( esc_html( sprintf( __( 'The input type "%1$s" has already been registered for the "%2$s" field type.', 'search-filter' ), $input_type, $field_type ) ), SEARCH_FILTER_EXCEPTION_FIELD_EXISTS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		// Make sure it exists.
		if ( ! class_exists( $class_name ) ) {
			/* translators: %s is the PHP class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` cannot be found.', 'search-filter' ), $class_name ) ), SEARCH_FILTER_EXCEPTION_FIELD_CLASS_MISSING ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		// Legacy stub loading - mark for deprecation after 3.2.0.
		if ( defined( 'SEARCH_FILTER_PRO_VERSION' ) && version_compare( SEARCH_FILTER_PRO_VERSION, '3.0.2', '<' ) ) {
			// Run the static init function `register` if it exists.
			if ( method_exists( $class_name, 'render' ) ) {
				$class_name::register();
			}
		} elseif ( self::class_ready( $class_name ) === true ) {
			$class_name::register();
		}

		self::$fields[ $field_type ][ $input_type ] = $class_name;
		return true;
	}
	/**
	 * Replaces a field class with the one passed
	 *
	 * @param string $field_type  The main type - eg, search, filter or control.
	 * @param string $input_type  The input type for a field type.
	 * @param string $class_name  The string name of the class.
	 * @return bool
	 * @throws Exception If the fields have already been registered.
	 */
	public static function update_field_input( $field_type, $input_type, $class_name ) {

		if ( true === self::$has_registered_types ) {
			throw new Exception( esc_html( __( 'You can only update fields inside the action "search-filter/fields/register".', 'search-filter' ) ), SEARCH_FILTER_EXCEPTION_FIELD_NOT_READY ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		if ( ! isset( self::$fields[ $field_type ] ) ) {
			/* translators: %s is the internal field type - eg search/filter/control */
			throw new Exception( esc_html( sprintf( __( 'The field type "%1$s" has not been found.', 'search-filter' ), $field_type ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		// Make sure it exists.
		if ( ! class_exists( $class_name ) ) {
			/* translators: %s is the PHP class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` cannot be found.', 'search-filter' ), $class_name ) ), SEARCH_FILTER_EXCEPTION_FIELD_CLASS_MISSING ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		// Legacy stub loading - mark for deprecation after 3.2.0.
		if ( defined( 'SEARCH_FILTER_PRO_VERSION' ) && version_compare( SEARCH_FILTER_PRO_VERSION, '3.0.2', '<' ) ) {
			// Run the static init function `register` if it exists.
			if ( method_exists( $class_name, 'render' ) ) {
				$class_name::register();
			}
		} elseif ( self::class_ready( $class_name ) === true ) {
			$class_name::register();
		}

		self::$fields[ $field_type ][ $input_type ] = $class_name;
		return true;
	}
	/**
	 * Has registered field input.
	 *
	 * @since 3.0.0
	 *
	 * @param string $field_type The field type.
	 * @param string $input_type The input type.
	 *
	 * @return bool True if the field input is registered.
	 */
	public static function has_registered_field_input( $field_type, $input_type ) {
		return isset( self::$fields[ $field_type ][ $input_type ] );
	}

	/**
	 * Gets a field input class by attributes field type and input type.
	 *
	 * @param array $attributes  Field attributes, can vary based on the `type` attribute.
	 * @return string|null
	 */
	private static function get_field_class( $attributes ) {
		$field_atts = self::get_field_atts( $attributes );
		$field_type = $field_atts['fieldType'];
		$input_type = $field_atts['inputType'];
		// TODO - is this working for control fields?

		if ( ! isset( self::$fields[ $field_type ][ $input_type ] ) ) {

			// If class_ref is false it means the field type / input type combination does not exist.
			/* translators: %1$s is the internal field type - eg search/filter/control, %2$s is the internal input type - eg radio/checkbox/text */
			Util::error_log( sprintf( __( 'Class not found for `%1$s` / `%2$s`.', 'search-filter' ), esc_html( $field_type ), esc_html( $input_type ) ), 'error' );
			return null;
		}
		return self::$fields[ $field_type ][ $input_type ];
	}

	/**
	 * Get the field attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes The field attributes.
	 *
	 * @return array The field attributes.
	 *
	 * @throws Exception If the field doesn't have the required attributes.
	 */
	public static function get_field_atts( $attributes ) {

		if ( ! isset( $attributes['type'] ) ) {
			throw new Exception( esc_html( __( 'The field type has not been set.', 'search-filter' ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		$field_type = $attributes['type'];
		if ( ! isset( self::$fields[ $field_type ] ) ) {
			throw new Exception( esc_html( __( 'Invalid field type.', 'search-filter' ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		if ( ! isset( $attributes['inputType'] ) && ! isset( $attributes['controlType'] ) ) {
			throw new Exception( esc_html( __( 'The input or control type has not been set.', 'search-filter' ) ), SEARCH_FILTER_EXCEPTION_FIELD_INVALID_INPUT_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		$input_type = '';
		if ( $field_type === 'control' ) {
			$input_type = $attributes['controlType'];
		} else {
			$input_type = $attributes['inputType'];
		}

		if ( ! isset( self::$fields[ $field_type ][ $input_type ] ) ) {
			/* translators: %1$s is the internal input type - eg radio/checkbox/text, %2$s is the internal field type - eg search/filter/control */
			Util::error_log( sprintf( __( 'The input type `%1$s` does not have a class associated with it for field type `%2$s`. Use `register_field_input`.', 'search-filter' ), esc_html( $input_type ), esc_html( $field_type ) ), 'error' );
		}
		return array(
			'inputType' => $input_type,
			'fieldType' => $field_type,
		);
	}

	/**
	 * Check if a class is ready to be used.
	 *
	 * Fields need at least the render and create methods.
	 *
	 * @since 3.0.0
	 *
	 * @param class-string $class_ref The class reference.
	 * @return bool|\WP_Error True if the class is ready.
	 *
	 * @throws Exception If the class does not have a render method or a create method.
	 */
	private static function class_ready( $class_ref ) {

		if ( empty( $class_ref ) ) {
			return new \WP_Error( 'search_filter_field_class_not_found', __( 'Class not found.', 'search-filter' ), array( 'status' => 404 ) );
		}

		// Make sure that it has a render method.
		if ( ! method_exists( $class_ref, 'render' ) ) {
			/* translators: %s is the class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` does not have a public `render()` method.', 'search-filter' ), $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_RENDER_METHOD ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}
		// Make sure that it has a create method.
		if ( ! method_exists( $class_ref, 'create' ) ) {
			/* translators: %s is the class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` does not have a public static `create()` method.', 'search-filter' ), $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_CREATE_METHOD ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		// Make sure that it has a type property thats not empty.
		if ( empty( $class_ref::$type ) ) {
			/* translators: %s is the class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` does not have a type set.', 'search-filter' ), $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}
		// Make sure that it has an input type property thats not empty.
		if ( empty( $class_ref::$input_type ) ) {
			/* translators: %s is the class name */
			throw new Exception( esc_html( sprintf( __( 'The class `%1$s` does not have a input type set.', 'search-filter' ), $class_ref ) ), SEARCH_FILTER_EXCEPTION_FIELD_NO_INPUT_TYPE ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		return true;
	}

	/**
	 * Creates a field from attributes.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes  Field attributes, can vary based on the `type` attribute.
	 * @param array $context Context for the field.
	 *
	 * @return mixed|false False if no class was found.
	 * @throws Exception For various conditions, such as invalid field type or input type in the attributes.
	 */
	public static function create( $attributes, $context = array() ) {

		// Lookup the class associated with the input type.
		$field_class_ref = self::get_field_class( $attributes );

		if ( self::class_ready( $field_class_ref ) === true ) {
			// Now create an instance of the correct Field class.
			$field = $field_class_ref::create( $attributes, $context );
			return $field;
		}

		return false;
	}

	/**
	 * Creates a field from db record.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @param \Search_Filter\Database\Rows\Field $item  The database record for the field.
	 * @return \Search_Filter\Fields\Field|\WP_Error
	 * @throws Exception For multiple conditions, such as invalid field type or input type.
	 */
	public static function create_from_record( $item ) {
		$attributes = $item->get_attributes();
		// Lookup the class associated with the input type.
		$field_class_ref = self::get_field_class( $attributes );

		if ( self::class_ready( $field_class_ref ) === true ) {
			// Now create an instance of the correct Field class.
			$field = $field_class_ref::create_from_record( $item );
			return $field;
		}

		return new \WP_Error( 'search_filter_field_class_not_found', __( 'Class not found.', 'search-filter' ), array( 'status' => 404 ) );
	}
	/**
	 * Creates a field from the record ID.
	 *
	 * Figures out which class to instantiate and returns the result.
	 *
	 * @param int $id  The database record for the field.
	 * @return Field|\WP_Error
	 * @throws Exception For multiple conditions, such as invalid field type or input type.
	 */
	public static function create_from_id( $id ) {
		$field = Field::get_instance( $id );
		return $field;
	}

	/**
	 * Gets the list of registered field input types
	 */
	public static function get_field_input_types() {
		return self::$fields;
	}

	/**
	 * Gets the field type label.
	 *
	 * @since 3.0.0
	 *
	 * @param string $field_type The field type.
	 * @return string The label for the field type.
	 */
	public static function get_field_type_label( $field_type ) {

		$field_type_labels = array(
			'search'   => __( 'Search', 'search-filter' ),
			'choice'   => __( 'Choice', 'search-filter' ),
			'range'    => __( 'Range', 'search-filter' ),
			'advanced' => __( 'Advanced', 'search-filter' ),
			'control'  => __( 'Control', 'search-filter' ),
		);

		if ( isset( $field_type_labels[ $field_type ] ) ) {
			return $field_type_labels[ $field_type ];
		}

		return '';
	}
}
