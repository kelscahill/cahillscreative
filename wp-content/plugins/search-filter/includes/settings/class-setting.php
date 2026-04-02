<?php
/**
 * The instance of an individual Setting
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter\Settings;

use Search_Filter\Core\Exception;
use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Container helper functions for manipulating settings
 * and changing their options
 */
class Setting {

	/**
	 * Contains most of the data for a setting as an assoc array
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array    $data    The data of this setting
	 */
	private $data = array();

	/**
	 * The options are stored as assoc array - in order
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array    $options    The options
	 */
	private $options = array();

	/**
	 * Whether the data has been resolved
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      bool    $has_resolved_data    Whether the data has been resolved
	 */
	private $has_resolved_data = false;

	/**
	 * Initialize the class
	 *
	 * @since    3.0.0
	 * @param    array $args       Initial data for this setting.
	 */
	public function __construct( array $args ) {

		$this->data = $args;
		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			$this->add_options_from_array( $args['options'], $this->options );
		}
		if ( ! isset( $this->data['enabled'] ) ) {
			$this->data['enabled'] = true;
		}
		$this->validate_depends_on();
	}


	/**
	 * Get the data for the setting.
	 *
	 * @param string $key The key to get.
	 * @return mixed The value of the setting.
	 */
	public function get_prop( string $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}
	/**
	 * Set the data for the setting.
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 */
	public function set_prop( string $key, $value ) {
		$this->data[ $key ] = $value;
	}

	/**
	 * Delete data property.
	 *
	 * @param string $key The key to delete.
	 */
	public function delete_prop( string $key ) {
		if ( array_key_exists( $key, $this->data ) ) {
			unset( $this->data[ $key ] );
		}
	}

	/**
	 * Has data property.
	 *
	 * @param string $key The key to check.
	 * @return bool
	 */
	public function has_prop( string $key ) {
		return array_key_exists( $key, $this->data );
	}
	/**
	 * Esnure the `dependsOn` conditions are valid and follow the correct stucture.
	 *
	 * Otherwise throw an exception.
	 *
	 * @since    3.0.0
	 *
	 * @throws Exception If the conditions are invalid.
	 */
	private function validate_depends_on() {
		if ( isset( $this->data['dependsOn'] ) ) {
			$this->validate_conditions( $this->data['dependsOn'] );
		}
	}

	/**
	 * Validate the conditions array.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $conditions    The conditions to validate.
	 * @return   bool True if the conditions are valid.
	 * @throws   Exception If the conditions are invalid.
	 */
	private function validate_conditions( array $conditions ) {
		if ( ! isset( $conditions['rules'] ) ) {
			throw new Exception( esc_html( "Invalid `dependsOn` conditions for field `{$this->get_name()}`" ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}
		if ( ! isset( $conditions['relation'] ) ) {
			throw new Exception( esc_html( "Invalid `dependsOn` conditions for field `{$this->get_name()}`" ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
		}

		$rules = $conditions['rules'];
		foreach ( $rules as $rule ) {
			if ( isset( $rule['relation'] ) ) {
				// Then it is a nested condition so check that on its own.
				if ( ! self::validate_conditions( $rule ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Takes options as an array of options (numeric) and converts into
	 * and ordered assoc array.
	 *
	 * @since    3.0.0
	 * @param    array $options_arr       Initial options.
	 * @param    array $to_target         Where to add the options (to support nested options).
	 */
	private function add_options_from_array( array $options_arr, array &$to_target ) {

		foreach ( $options_arr as $option ) {
			if ( $this->is_valid_option( $option ) ) {
				$to_target[ $option['value'] ] = $option;

			} elseif ( $this->is_valid_option_group( $option ) ) {
				// It's a group, so recurse through the options.
				$to_target[ $option['name'] ]            = $option;
				$to_target[ $option['name'] ]['options'] = array();

				if ( ! is_array( $option['options'] ) ) {
					continue;
				}
				$this->add_options_from_array( $option['options'], $to_target[ $option['name'] ]['options'] );

			} elseif ( $this->is_valid_multi_option( $option ) ) {
				// TODO - this check, and the whole logic around parsing these options needs reworking.
				$to_target[] = $option;

			} else {
				// Translators: %s is the name of the setting.
				Util::error_log( sprintf( __( 'An option in the setting `%1$s` does not have valid values', 'search-filter' ), $this->data['name'] ), 'error' );
			}
		}
	}

	/**
	 * Checks whether an option is valid option (not a group)
	 *
	 * @since    3.0.0
	 * @param    array $option            The option.
	 * @return bool True if the option is a valid option, false otherwise.
	 */
	private function is_valid_option( array $option ) {
		if ( ( ! isset( $option['value'] ) ) || ( ! isset( $option['label'] ) ) ) {
			return false;
		}
		if ( ( empty( $option['value'] ) ) || ( empty( $option['label'] ) ) ) {
			return false;
		}

		return true;
	}
	/**
	 * Checks whether an option is valid option (not a group)
	 *
	 * @since    3.0.0
	 * @param    array $option            The option.
	 * @return bool True if the option is a valid multi option, false otherwise.
	 */
	private function is_valid_multi_option( array $option ) {

		$valid_keys = array( 'name', 'label', 'options' );
		// Check to make sure none of the keys are set, and return early.
		foreach ( $valid_keys as $valid_key ) {
			if ( isset( $option[ $valid_key ] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks if an option is valid option group (based on its properties)
	 *
	 * @since    3.0.0
	 * @param    array $option            The option.
	 * @return bool True if the option is a valid option group, false otherwise.
	 */
	private function is_valid_option_group( array $option ) {
		if ( ( ! isset( $option['name'] ) ) || ( ! isset( $option['label'] ) ) || ( ! isset( $option['options'] ) ) ) {
			return false;
		}
		if ( ( empty( $option['name'] ) ) || ( empty( $option['label'] ) ) ) {
			return false;
		}

		return true;
	}
	/**
	 * Get the default value.
	 *
	 * @return string The default value.
	 */
	public function get_default() {
		return $this->data['default'];
	}

	/**
	 * Get the name.
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Get is enabled.
	 *
	 * @return bool True if the setting is enabled, false otherwise.
	 */
	public function is_enabled() {
		return $this->data['enabled'];
	}
	/**
	 * Based on whether an option is a flat option, or an option group,
	 * returns a unique identifier based on its properties.
	 *
	 * @since    3.0.0
	 * @param    array $option            The option.
	 * @return   string|int The option key or -1 if the option is not valid.
	 */
	private function get_option_key( array $option ) {
		if ( $this->is_valid_option( $option ) ) {
			return $option['value'];
		} elseif ( $this->is_valid_option_group( $option ) ) {
			return $option['name'];
		}
		return -1;
	}

	/**
	 * Return the data
	 *
	 * @since    3.0.0
	 * @param string $name The name of the data to return.
	 * @return mixed The data.
	 */
	public function get_data( string $name = '' ) {
		if ( '' === $name ) {
			return $this->data;
		}
		if ( isset( $this->data[ $name ] ) ) {
			return $this->data[ $name ];
		}
		return '';
	}
	/**
	 * Resolve the data from the API
	 */
	public function resolve_data() {

		if ( $this->has_resolved_data ) {
			return;
		}

		$this->has_resolved_data = true;

		if ( ! isset( $this->data['dataProvider'] ) ) {
			return;
		}

		if ( ! isset( $this->data['dataProvider']['route'] ) ) {
			return;
		}

		if ( empty( $this->data['dataProvider']['route'] ) ) {
			return;
		}

		$route = '/search-filter/v1' . $this->data['dataProvider']['route'];

		$preload_data = array_reduce(
			array( $route ),
			'rest_preload_api_request',
			array()
		);

		if ( empty( $preload_data ) ) {
			return;
		}

		$api_data = $preload_data[ $route ]['body'];
		foreach ( $api_data as $data_key => $data_value ) {
			if ( $data_key === 'options' ) {
				$this->set_options( $data_value );
			} else {
				$this->data[ $data_key ] = $data_value;
			}
		}
	}
	/**
	 * Check if the setting has support for a property
	 *
	 * @param string $property The property to check.
	 * @return bool True if the setting has support for the property, false otherwise.
	 */
	public function has_support( string $property ) {
		if ( ! isset( $this->data['supports'] ) ) {
			return false;
		}

		if ( ! isset( $this->data['supports'][ $property ] ) ) {
			return false;
		}

		if ( $this->data['supports'][ $property ] !== false ) {
			return true;
		}

		return false;
	}
	/**
	 * Map the args back into the data object
	 *
	 * @since    3.0.0
	 * @param    array $args            The args.
	 * @param    bool  $replace         Whether to replace the existing data.
	 */
	public function update( array $args, bool $replace = false ) {
		// TODO - are we sure we want to keep the existing data?
		if ( $replace ) {
			$this->data = $args;
		} else {
			$this->data = wp_parse_args( $args, $this->data );
		}

		if ( isset( $args['dependsOn'] ) ) {
			$this->validate_depends_on();
		}
	}

	/**
	 * Get the internal data + options (as numerical array) for use in JS
	 *
	 * @since    3.0.0
	 *
	 * @return array The setting array.
	 */
	public function get_array() {
		$setting = $this->data;
		if ( ! empty( $this->options ) ) {
			$setting['options'] = $this->create_options_array( $this->options );
		}
		return $setting;
	}

	/**
	 * Creates an options array (as numerical array) from an assoc
	 * array of options.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $options            The assoc array of options.
	 */
	public function create_options_array( array $options ) {
		// TODO - remember to add in "default" options to our select2 fields as we've removed them from config.
		$options_arr = array();
		foreach ( $options as $key => $option ) {
			if ( ! isset( $option['options'] ) ) {
				array_push( $options_arr, $option );
			} else {
				$option['options'] = $this->create_options_array( $option['options'] );
				array_push( $options_arr, $option );
			}
		}

		return $options_arr;
	}
	/**
	 * Returns options (as numerical array) for use in JS
	 *
	 * @since    3.0.0
	 *
	 * @return array The options array.
	 */
	public function get_options_array() {
		return $this->create_options_array( $this->options );
	}
	/**
	 * Returns options (as numerical array) for use in JS
	 *
	 * @since    3.0.0
	 *
	 * @param    array $new_option         The new option data.
	 * @param    array $args               Additional params, such as `parent`.
	 */
	public function add_option( array $new_option, array $args = array() ) {
		// TODO, we need to throw an error if we're replacing an existing options,
		// this function should only be used to add new options, not update.
		$defaults = array(
			'parent'   => -1,
			// Can be `before`, `after`, `first` or `last`.
			'position' => '',
			'after'    => '',
			'before'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$option_key = $this->get_option_key( $new_option );

		if ( ! $option_key ) {
			// If it doesn't have a name/value to be used as a key then return.
			return;
		}

		// Figure out which options array to update (in case of nested).
		$options = $this->get_options( $args['parent'] );
		if ( empty( $options ) ) {
			return;
		}

		$new_options = array();
		if ( $args['position'] === 'first' ) {
			$new_options = array_merge( array( $new_option['value'] => $new_option ), $options );
		} elseif ( $args['position'] === 'last' ) {
			$new_options = array_merge( $options, array( $new_option['value'] => $new_option ) );
		} elseif ( $args['position'] === 'before' ) {
			foreach ( $options as $key => $option ) {
				// Set the new option before the current options is set to insert
				// it before in assoc array.
				if ( $args['before'] === $key ) {
					$new_options[ $option_key ] = $new_option;
				}
				$new_options[ $key ] = $option;
			}
		} elseif ( $args['position'] === 'after' ) {
			foreach ( $options as $key => $option ) {
				$new_options[ $key ] = $option;
				if ( $args['after'] === $key ) {
					$new_options[ $option_key ] = $new_option;
				}
			}
		} else {
			$options[ $option_key ] = $new_option;
			$new_options            = $options;
		}

		$this->set_options( $new_options, $args['parent'] );
	}
	/**
	 * Returns an individual option
	 *
	 * @since    3.0.0
	 *
	 * @param    string $option_name        The option name.
	 * @param    array  $args               Add a parent to look in.
	 *
	 * @return array|bool The option or false if it doesn't exist.
	 */
	public function get_option( string $option_name, array $args = array() ) {

		$defaults = array(
			'parent' => -1,
		);
		$args     = wp_parse_args( $args, $defaults );

		$options = $this->get_options( $args['parent'] );

		if ( empty( $options ) ) {
			return false;
		}

		if ( ! isset( $options[ $option_name ] ) ) {
			// Translators: %1$s is the option name.
			Util::error_log( sprintf( __( 'The option "%1$s" does not exist', 'search-filter' ), esc_html( $option_name ) ), 'error' );
			return false;
		}

		return $options[ $option_name ];
	}

	/**
	 * Sets the options.
	 *
	 * @param array   $options The options to set.
	 * @param integer $parent_setting  The parent option name.
	 * @return void
	 */
	public function set_options( array $options, int $parent_setting = -1 ) {
		if ( $parent_setting === -1 ) {
			$this->options = $options;
		} else {
			$this->options[ $parent_setting ] = $options;
		}
	}
	/**
	 * Returns the options array based on the `parent` argument
	 *
	 * @since    3.0.0
	 *
	 * @param    string|int $parent_setting  The parent name.
	 *
	 * @return   array $options  Returns the options array that belongs to the parent, by reference so it can be modified.
	 */
	public function get_options( $parent_setting = -1 ) {
		if ( -1 === $parent_setting ) {
			$options = $this->options;
		} elseif ( ( $parent_setting ) && ( isset( $this->options[ $parent_setting ] ) ) ) {
			if ( isset( $this->options[ $parent_setting ]['options'] ) ) {
				$options = $this->options[ $parent_setting ]['options'];
			} else {
				// Translators: %1$s is the parent option name.
				Util::error_log( sprintf( __( 'The option "%1$s" does not have a valid options array', 'search-filter' ), esc_html( $parent_setting ) ), 'error' );
				return array();
			}
		} else {
			// Translators: %1$s is the parent name.
			Util::error_log( sprintf( __( 'The option "%1$s" does not exist', 'search-filter' ), esc_html( $parent_setting ) ), 'error' );
			return array();
		}

		return $options;
	}

	/**
	 * Updates a specific option
	 *
	 * @since    3.0.0
	 *
	 * @param    string $option_name             The option name.
	 * @param    array  $option_data             The new option data.
	 * @param    array  $args                    Additional args.
	 */
	public function update_option( string $option_name, array $option_data, array $args = array() ) {

		$defaults = array(
			'parent' => -1,
		);
		$args     = wp_parse_args( $args, $defaults );

		$option = $this->get_option( $option_name, $args );

		if ( ! $option ) {
			return false;
		}

		// Remove value + name from update data to prevent array index issues
		// Essentially these cannot be modified.
		if ( isset( $option_data['value'] ) ) {
			unset( $option_data['value'] );
		}
		if ( isset( $option_data['name'] ) ) {
			unset( $option_data['name'] );
		}

		$option = wp_parse_args( $option_data, $option );

		$options = $this->get_options( $args['parent'] );

		if ( empty( $options ) ) {
			return false;
		}

		// Update the option.
		$options[ $option_name ] = $option;
		$this->set_options( $options, $args['parent'] );
	}

	/**
	 * Remove option by name/value
	 *
	 * @param string $option_name The option name.
	 * @return bool True if the option was removed, false otherwise.
	 */
	public function remove_option( string $option_name ) {
		if ( ! isset( $this->options[ $option_name ] ) ) {
			return false;
		}

		unset( $this->options[ $option_name ] );
		return true;
	}

	/**
	 * Add a depends condition
	 *
	 * @param array $condition The condition to add.
	 */
	public function add_depends_condition( array $condition ) {
		$conditions = $this->get_data( 'dependsOn' );
		if ( ! is_array( $conditions ) ) {
			$conditions = array(
				'relation' => 'AND',
				'rules'    => array(),
			);
		}
		array_push( $conditions['rules'], $condition );
		$this->update( array( 'dependsOn' => $conditions ) );
	}
}
