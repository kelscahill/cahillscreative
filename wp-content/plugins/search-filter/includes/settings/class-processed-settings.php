<?php
/**
 * Settings Management Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter\Settings;

use Search_Filter\Core\Exception;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates processed settings + state given an input of settings and state.
 *
 * This should mirror the logic of `useProcessedSettings` hook in our JS.
 *
 * So far it is setting the state based on default and depends conditions,
 * but it doesn't fetch the `options` for the fields via the rest API.
 *
 * Therefor we don't set the state value to the value of the first option if
 * options are populated via the rest API.
 *
 * There is probably some other nuances we're not copying over, but for now,
 * this generates a solid initial state for a settings section after resolving
 * dependencies.
 *
 * @since    3.0.0
 */
class Processed_Settings {

	/**
	 * The attributes of the settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $attributes = array();

	/**
	 * The processed settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * The valid comparisons in depends conditions.
	 *
	 * Should match the JS code.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private $valid_compares = array( 'EXISTS', 'NOT EXISTS', '=', '!=', 'IN', 'NOT IN', '<', '<=', '>', '>=' );

	/**
	 * Construct the processed settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The settings to process.
	 * @param array $attributes The state of the settings.
	 * @param array $external_store External store data to merge with attributes.
	 * @param array $ghost_attributes State that may not be in resolved settings, but needs to be kept.
	 * @param bool  $get_all_attributes Whether to get all attributes regardless of visibility.
	 */
	public function __construct( $settings, $attributes, $external_store = array(), $ghost_attributes = array(), $get_all_attributes = false ) {

		$processed_settings = $this->process_settings( $settings, $attributes, $external_store, $ghost_attributes, $get_all_attributes );
		$this->attributes   = $processed_settings['attributes'];
		// Now remove the ghost attributes from the final attributes.
		foreach ( $ghost_attributes as $attribute_key ) {
			if ( isset( $this->attributes[ $attribute_key ] ) ) {
				unset( $this->attributes[ $attribute_key ] );
			}
		}
		$this->settings = $processed_settings['settings'];
	}

	/**
	 * Get the processed state.
	 *
	 * @since    3.0.0
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get the processed settings.
	 *
	 * @since    3.0.0
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Process the settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The settings to process.
	 * @param array $attributes The attributes of the settings.
	 * @param array $external_store External store data to merge with attributes.
	 * @param array $ghost_attributes Attributes that may not be in resolved settings, but needs to be used as input state.
	 * @param bool  $get_all_attributes Whether to get all attributes regardless of visibility.
	 * @return array {
	 *     Array of processed settings.
	 *
	 *     @type array $settings The processed settings.
	 *     @type array $attributes The processed attributes.
	 * }
	 */
	public function process_settings( $settings, $attributes, $external_store = array(), $ghost_attributes = array(), $get_all_attributes = false ) {
		$new_settings   = array();
		$new_attributes = array();

		foreach ( $settings as $setting ) {
			// Process the setting.
			$processed_setting = $this->process_setting( $setting, $attributes, $external_store, $get_all_attributes );
			$setting_name      = $setting->get_name();

			// Now process the attributes.
			$setting_is_visible = $processed_setting->get_data( 'isVisible' );

			// If the settings is not visible, then remove the attributes for it if it exists.
			if ( $setting_is_visible === true || $get_all_attributes === true ) {
				if ( isset( $attributes[ $setting_name ] ) ) {
					$new_attributes[ $setting_name ] = $attributes[ $setting_name ];
				} elseif ( $setting->has_prop( 'default' ) && ! empty( $setting->get_prop( 'default' ) ) ) {
					$new_attributes[ $setting_name ] = $setting->get_prop( 'default' );
				} elseif ( $setting->get_prop( 'requireSelection' ) !== true ) {
					$options = array();
					// Try get the value of any resolved dependant options.
					if ( $setting->has_support( 'dependantOptions' ) ) {
						$resolved_options = $setting->get_prop( 'resolvedOptions' );
						if ( ! empty( $resolved_options ) ) {
							$options = $resolved_options;
						}
					} else {
						// Otherwise use the options from the setting.
						$options = $processed_setting->get_options_array();
					}
					if ( count( $options ) > 0 ) {
						if ( $setting->get_prop( 'type' ) === 'object' ) {
							continue;
						} elseif ( $setting->get_prop( 'type' ) === 'array' ) {
							$new_attributes[ $setting_name ] = array( $options[0]['value'] );
						} else {
							$new_attributes[ $setting_name ] = $options[0]['value'];
						}
					}
				}
			}
			$new_settings[] = $processed_setting;
		}

		/**
		 * We might need to keep attributes that don't belong to a setting. This occurs
		 * because we there is a need to supply certain values so that dependencies are
		 * met.
		 */
		foreach ( $ghost_attributes as $attribute_key ) {
			if ( ! isset( $new_attributes[ $attribute_key ] ) ) {
				$new_attributes[ $attribute_key ] = $attributes[ $attribute_key ];
			}
		}

		$updated_settings = $this->updated_keys( $attributes, $new_attributes );

		// State has changed, so need to run through the process again.
		if ( count( $updated_settings ) > 0 ) {
			$next_processed_settings = $this->process_settings( $new_settings, $new_attributes, $external_store, $ghost_attributes, $get_all_attributes );
			$new_settings            = $next_processed_settings['settings'];
			$new_attributes          = $next_processed_settings['attributes'];
		}

		$processed_settings = array(
			'settings'   => $new_settings,
			'attributes' => $new_attributes,
		);

		return $processed_settings;
	}

	/**
	 * Get the state keys that have been changed.
	 *
	 * @since 3.0.0
	 *
	 * @param array $old_state The old state.
	 * @param array $new_state The new state.
	 * @return array The updated keys.
	 */
	public function updated_keys( $old_state, $new_state ) {
		$updated_keys = array();
		foreach ( $old_state as $state_key => $state_value ) {
			if ( isset( $new_state[ $state_key ] ) ) {
				if ( $state_value !== $new_state[ $state_key ] ) {
					$updated_keys[] = $state_key;
				}
			} else {
				$updated_keys[] = $state_key;
			}
		}

		foreach ( $new_state as $state_key => $state_value ) {
			if ( isset( $old_state[ $state_key ] ) ) {
				if ( $state_value !== $old_state[ $state_key ] ) {
					$updated_keys[] = $state_key;
				}
			} else {
				$updated_keys[] = $state_key;
			}
		}

		return $updated_keys;
	}

	/**
	 * Process a single setting.
	 *
	 * @since 3.0.0
	 *
	 * @param object $setting The setting to process.
	 * @param array  $state The state of the settings.
	 * @param array  $external_store External store data to merge with attributes.
	 * @param bool   $get_all_attributes Whether to get all attributes regardless of visibility.
	 * @return object The processed setting.
	 */
	public function process_setting( $setting, $state, $external_store, $get_all_attributes = false ) {
		// Merge attributes with external store data.
		$store = array(
			'attributes' => $state,
		);
		$store = array_merge( $store, $external_store );

		$is_visible = $this->is_setting_visible( $setting, $store );
		$setting->update( array( 'isVisible' => $is_visible ) );

		if ( ! $is_visible && ! $get_all_attributes ) {
			return $setting;
		}

		if ( $setting->has_support( 'dependantOptions' ) ) {

			$options = $setting->get_options();
			if ( empty( $options ) ) {
				return $setting;
			}

			$resolved_options = array();

			foreach ( $options as $option ) {

				if ( ! isset( $option['dependsOn'] ) ) {
					$resolved_options[] = $option;
					continue;
				}

				if ( $this->conditions_met( $store, $option['dependsOn'], $setting->get_name() . '-' . $option['value'] ) ) {
					$resolved_options[] = $option;
				}
			}
			$setting->set_prop( 'resolvedOptions', $resolved_options );
		}

		return $setting;
	}

	/**
	 * Check if a setting is visible.
	 *
	 * @since 3.0.0
	 *
	 * @param Setting $setting The setting to check.
	 * @param array   $store The store of the settings.
	 * @return bool True if the setting is visible after checking dependencies.
	 */
	public function is_setting_visible( Setting $setting, array $store ) {
		$depends_on = $setting->get_data( 'dependsOn' );
		if ( empty( $depends_on ) ) {
			return true;
		}

		$setting_name = $setting->get_name();

		if ( $this->conditions_met( $store, $depends_on, $setting_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if conditions are met.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $store The state of the settings.
	 * @param array  $conditions The conditions to check.
	 * @param string $setting_name The name of the setting.
	 * @return bool True if conditions are met.
	 *
	 * @throws Exception If the conditions are invalid.
	 */
	public function conditions_met( array $store, array $conditions, string $setting_name ) {
		// If there are no valid rules lets assume the condition is met.
		if ( ! isset( $conditions['rules'] ) || count( $conditions['rules'] ) === 0 || ! isset( $conditions['relation'] ) ) {
			// No conditions so return true.
			Util::error_log( 'Found empty conditions array for setting `' . $setting_name . '`' );
			return true;
		}

		$rules    = $conditions['rules'];
		$relation = $conditions['relation'];

		$matches_needed = 0; // How many conditions need to be matched to count as a success.
		$matches        = 0; // The current number of matched conditions.

		if ( $relation === 'OR' ) {
			$matches_needed = 1; // Only 1 condition needs to be matched.
		} else {
			// Default to AND - all conditions need to be matched.
			$matches_needed = count( $rules );
		}

		foreach ( $rules as $rule ) {

			if ( isset( $rule['relation'] ) ) {
				// Then it is a nested condition so check that on its own.
				if ( $this->conditions_met( $store, $rule, $setting_name ) ) {
					++$matches;
				}
			} else {
				// For now only support flat depends array.
				$compare    = isset( $rule['compare'] ) ? $rule['compare'] : '';
				$option     = isset( $rule['option'] ) ? $rule['option'] : '';
				$value      = isset( $rule['value'] ) ? $rule['value'] : '';
				$store_name = isset( $rule['store'] ) ? $rule['store'] : 'attributes';

				/*
				 * Note: using phpcs:ignore after the exceptions because the rule `WordPress.Security.EscapeOutput.ExceptionNotEscaped`
				 * is being triggered because the last argument is not escaped - but this is not used in the message or displayed to the user,
				 * it's a constant/error code used in our custom exception class.
				 */
				if ( ! isset( $store[ $store_name ] ) ) {
					// Translators: %1$s is the store name, %2$s is the setting name.
					throw new Exception( esc_html( sprintf( __( 'Store `%1$s` missing for setting `%2$s`', 'search-filter' ), $store_name, $setting_name ) ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				}
				if ( ! isset( $rule['option'] ) ) {
					// Translators: %s is the setting name.
					throw new Exception( esc_html( sprintf( __( 'Invalid `dependsOn` conditions for setting `%1$s`, option missing.', 'search-filter' ), $setting_name ) ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				}
				if ( ! isset( $rule['compare'] ) ) {
					// Translators: %s is the setting name.
					throw new Exception( esc_html( sprintf( __( 'Invalid `dependsOn` conditions for setting `%1$s`, compare missing.', 'search-filter' ), $setting_name ) ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				}
				if ( ! isset( $rule['value'] ) && $compare !== 'EXISTS' && $compare !== 'NOT EXISTS' ) {
					// Translators: %s is the setting name.
					throw new Exception( esc_html( sprintf( __( 'Invalid `dependsOn` conditions for setting `%1$s`, value missing.', 'search-filter' ), $setting_name ) ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				}
				if ( ! in_array( $compare, $this->valid_compares, true ) ) {
					// Translators: %s is the setting name.
					throw new Exception( esc_html( sprintf( __( 'Invalid `dependsOn` conditions for setting `%1$s`, compare invalid.', 'search-filter' ), $setting_name ) ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_CONDITIONS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception code is a constant.
				}

				if ( $compare === 'EXISTS' ) {
					if ( $this->exists( $option, $store[ $store_name ] ) ) {
						++$matches;
					}
				} elseif ( $compare === 'NOT EXISTS' ) {
					if ( $this->not_exists( $option, $store[ $store_name ] ) ) {
						++$matches;
					}
				} elseif ( $compare === '=' ) {
					if ( $this->is_equal( $option, $value, $store[ $store_name ] ) ) {
						++$matches;
					}
				} elseif ( $compare === '!=' ) {
					if ( $this->is_not_equal( $option, $value, $store[ $store_name ] ) ) {
						++$matches;
					}
				} elseif ( $compare === 'IN' ) {
					if ( $this->is_in( $option, $value, $store[ $store_name ] ) ) {
						++$matches;
					}
				} elseif ( $compare === 'NOT IN' ) {
					if ( $this->is_not_in( $option, $value, $store[ $store_name ] ) ) {
						++$matches;
					}
				}

				if ( $matches >= $matches_needed ) {
					// Break early if the conditions and relation have been met.
					break;
				}
			}
		}
		return $matches >= $matches_needed;
	}

	/**
	 * Check if a state value is equal to a setting value.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of the state value.
	 * @param mixed  $value The value to check.
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value is equal to the setting value.
	 */
	private function is_equal( $name, $value, $state ) {
		if ( isset( $state[ $name ] ) ) {
			if ( $value === $state[ $name ] ) {
				return true;
			}
		}
		return false;
	}
	/**
	 * Check if a state value is not equal to a setting value.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of the state value.
	 * @param mixed  $value The value to check.
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value is not equal to the setting value.
	 */
	private function is_not_equal( $name, $value, $state ) {
		if ( isset( $state[ $name ] ) ) {
			if ( $value === $state[ $name ] ) {
				return false;
			}
		}
		return true;
	}
	/**
	 * Check if a state value is in a setting value.
	 *
	 * Returns true if the state value (or any of its values if it's an array)
	 * exists within the provided value array.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name  The name of the state value.
	 * @param mixed  $value The value(s) to check against (should be an array).
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value is in the setting value.
	 */
	private function is_in( $name, $value, $state ) {
		if ( ! isset( $state[ $name ] ) ) {
			return false;
		}

		$state_value = $state[ $name ];

		// Ensure $value is an array for comparison.
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		// If state value is an array, check if any of its values are in $value.
		if ( is_array( $state_value ) ) {
			foreach ( $state_value as $item ) {
				if ( in_array( $item, $value, true ) ) {
					return true;
				}
			}
			return false;
		}

		// State value is scalar, check if it's in $value array.
		return in_array( $state_value, $value, true );
	}
	/**
	 * Check if a state value is not in a setting value.
	 *
	 * Returns true if the state value (and all of its values if it's an array)
	 * does not exist within the provided value array.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name  The name of the state value.
	 * @param mixed  $value The value(s) to check against (should be an array).
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value is not in the setting value.
	 */
	private function is_not_in( $name, $value, $state ) {
		// If the state value doesn't exist, it's not in the value array.
		if ( ! isset( $state[ $name ] ) ) {
			return true;
		}

		// Simply return the inverse of is_in().
		return ! $this->is_in( $name, $value, $state );
	}
	/**
	 * Check if a state value exists.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of the state value.
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value exists.
	 */
	private function exists( $name, $state ) {
		if ( isset( $state[ $name ] ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Check if a state value does not exist.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of the state value.
	 * @param array  $state The state of the settings.
	 * @return bool True if the state value does not exist.
	 */
	private function not_exists( $name, $state ) {
		if ( ! isset( $state[ $name ] ) ) {
			return true;
		}
		return false;
	}
}
