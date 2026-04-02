<?php
/**
 * Field settings for use in blocks and admin pages
 * TODO - probably could just be json - dynamic data
 * is fetched via the rest api.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter\Fields;

use Search_Filter\Queries\Query;
use Search_Filter\Settings\Setting;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settings for fields
 */
class Settings extends \Search_Filter\Settings\Section_Base {
	/**
	 * The source settings before they have been processed.
	 *
	 * @var array
	 */
	protected static $source_settings = array();

	/**
	 * The prepared settings.
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * The settings order.
	 *
	 * @var array
	 */
	protected static $settings_order = array();

	/**
	 * The source groups.
	 *
	 * @var array
	 */
	protected static $source_groups = array();

	/**
	 * The prepared groups.
	 *
	 * @var array
	 */
	protected static $groups = array();

	/**
	 * The groups order.
	 *
	 * @var array
	 */
	protected static $groups_order = array();

	/**
	 * The setting section name.
	 *
	 * @var string
	 */
	protected static $section = 'fields';

	/**
	 * Legacy mapped data support.
	 *
	 * @since 3.2.0
	 *
	 * @var array
	 */
	private static $legacy_data_support = array();

	/**
	 * Whether to use legacy data support.
	 *
	 * @since 3.2.0
	 * @var bool
	 */
	private static $use_legacy_data_support = false;

	/**
	 * Init the settings.
	 *
	 * @param    array $settings    The settings to add.
	 * @param    array $groups    The groups to add.
	 */
	public static function init( $settings = array(), $groups = array() ) {
		parent::init( $settings, $groups );

		// Init the upgrades for legacy data type support.
		self::init_upgrades();
	}


	/**
	 * Get the external store.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $settings The settings (unused).
	 * @param array<string, mixed> $attributes The attributes.
	 *
	 * @return array The external store.
	 */
	protected static function get_external_store( $settings, $attributes ) {
		$store = array(
			'query' => array(),
		);

		if ( ! isset( $attributes['queryId'] ) ) {
			return $store;
		}

		$query = Query::get_instance( absint( $attributes['queryId'] ) );
		if ( is_wp_error( $query ) ) {
			return $store;
		}
		$store['query'] = $query->get_attributes();
		return $store;
	}

	/**
	 * Prepare the setting.
	 *
	 * @param array $setting The setting to prepare.
	 * @param array $args Optional. Additional arguments for preparing the setting.
	 *
	 * @return Setting The prepared setting.
	 */
	protected static function prepare_setting( array $setting, array $args = array() ) {

		$setting = apply_filters( 'search-filter/fields/settings/prepare_setting/before', $setting, $args );

		// Handle any setting upgrades.
		$setting = self::apply_setting_upgrades( $setting );

		// Update the dependsOn conditions based on field support.
		// Build the conditions based on the various supporting properties.
		$setting = static::build_settings_conditions( $setting );
		// Allow for setting variations making it possible to override any setting
		// based in field type & input type.
		$setting = static::build_variations( $setting );

		$setting = parent::prepare_setting( $setting );
		return $setting;
	}

	/**
	 * Apply upgrades to the setting.
	 *
	 * @param array $setting The setting to apply upgrades to.
	 *
	 * @return array The updated setting.
	 */
	protected static function apply_setting_upgrades( $setting ) {

		/**
		 * Pre 3.2.0 settings contexts used to specify each type of field context, ie
		 * `admin/field/search`, `admin/field/advanced`, `block/field/search` etc.
		 *
		 * A bit of a crude check to see if there is more than 2 contexts, but in 99% of cases this will be true.
		 *
		 * Now we only use `admin/field` and `block/admin`.
		 */
		if ( isset( $setting['context'] ) && is_array( $setting['context'] ) && count( $setting['context'] ) > 2 ) {
			foreach ( $setting['context'] as $context ) {
				$has_field_context = false;
				$has_block_context = false;
				if ( strpos( $context, 'admin/field' ) ) {
					$has_field_context = true;
				} elseif ( strpos( $context, 'block/field' ) !== false ) {
					$has_block_context = true;
				}
				$setting['context'] = array();
				if ( $has_field_context ) {
					$setting['context'][] = 'admin/field';
				}
				if ( $has_block_context ) {
					$setting['context'][] = 'block/field';
				}
			}
		}

		return $setting;
	}
	/**
	 * Initialize upgrades for legacy data support.
	 *
	 * @return void
	 */
	protected static function init_upgrades() {
		/**
		 * Pre 3.2.0 we used to build input type conditions from the hook 'search-filter/fields/field/get_data_support',
		 * but now we use the 'search-filter/fields/field/get_setting_support' hook instead.
		 */
		if ( has_filter( 'search-filter/fields/field/get_data_support' ) || has_filter( 'search-filter/field/get_data_support' ) ) {
			self::$use_legacy_data_support = true;

			$input_type_matrix = Field_Factory::get_field_input_types();

			foreach ( $input_type_matrix as $field_type => $input_types ) {

				self::$legacy_data_support[ $field_type ] = array();
				foreach ( $input_types as $input_type => $input_type_class ) {
					$setting_data_support = apply_filters( 'search-filter/fields/field/get_data_support', array(), $field_type, $input_type );
					// Legacy hook name.
					$setting_data_support = apply_filters( 'search-filter/field/get_data_support', $setting_data_support, $field_type, $input_type );

					// Convert this to our expected format: `settingName => values => array( ...values... )`.
					foreach ( $setting_data_support as $data_support ) {
						foreach ( $data_support as $setting_name => $values ) {
							if ( ! isset( self::$legacy_data_support[ $field_type ][ $input_type ][ $setting_name ] ) ) {
								self::$legacy_data_support[ $field_type ][ $input_type ][ $setting_name ] = array();
							}
							// Can be single value or an array of values.
							if ( ! is_array( $values ) ) {
								$values = array( $values );
							}
							foreach ( $values as $value ) {
								self::$legacy_data_support[ $field_type ][ $input_type ][ $setting_name ][ $value ] = true;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Apply legacy data support values to the setting values.
	 *
	 * @since 3.2.0
	 *
	 * @param array  $setting_values The setting values to update.
	 * @param string $setting_name The setting name.
	 * @param string $field_type The field type.
	 * @param string $input_type The input type.
	 * @return array The updated setting values.
	 */
	protected static function apply_setting_support_legacy_values( $setting_values, $setting_name, $field_type, $input_type ) {

		if ( ! self::$use_legacy_data_support ) {
			return $setting_values;
		}
		if ( ! isset( self::$legacy_data_support[ $field_type ] ) ) {
			return $setting_values;
		}
		if ( ! isset( self::$legacy_data_support[ $field_type ][ $input_type ] ) ) {
			return $setting_values;
		}
		if ( ! isset( self::$legacy_data_support[ $field_type ][ $input_type ][ $setting_name ] ) ) {
			return $setting_values;
		}

		return array_merge( self::$legacy_data_support[ $field_type ][ $input_type ][ $setting_name ], $setting_values );
	}

	/**
	 * Applies upgrades to setting support arrays for backwards compatibility.
	 *
	 * @param array $setting_supports The setting supports to upgrade.
	 * @return array The upgraded setting supports.
	 */
	protected static function apply_setting_support_upgrades( $setting_supports ) {
		/**
		 * Legacy: styles settings used to be flat arrays but now are associative arrays that can either be
		 * true, or a nested arrays with properties, such as `conditions` just like regular settings support.
		 * If we have a non-assoc array, convert it to a keyed array with every value set to true.
		 */
		if ( ! Util::is_assoc_array( $setting_supports ) ) {
			$setting_supports = array_fill_keys( $setting_supports, true );
		}

		return $setting_supports;
	}

	/**
	 * Build the styles (variables) overrides based on the field input types.
	 *
	 * @param array $setting The setting to build the overrides for.
	 * @return array The setting with overrides.
	 */
	protected static function build_variations( $setting ) {
		$input_type_matrix = Field_Factory::get_field_input_types();
		$setting_name      = $setting['name'];

		// Note: while this logic is implemented to add variations for any setting prop
		// its only currently used for styles settings, specifically for overriding variables.
		$variations = array();

		foreach ( $input_type_matrix as $field_type => $input_types ) {

			foreach ( $input_types as $input_type => $input_type_class ) {

				$setting_supports = array();

				if ( $setting['tab'] === 'styles' ) {
					$setting_supports = $input_type_class::get_styles_support();
				} else {
					$setting_supports = $input_type_class::get_setting_support();
				}

				// Legacy: styles settings used to be flat arrays but now are associative arrays that can either be
				// true, or a nested arrays with properties, such as `conditions` just like regular settings support.
				// If we have a non-assoc array, convert it to a keyed array with every value set to true.
				if ( ! Util::is_assoc_array( $setting_supports ) ) {
					continue;
				}

				if ( ! array_key_exists( $setting_name, $setting_supports ) ) {
					continue;
				}

				$setting_config = $setting_supports[ $setting_name ];
				if ( ! isset( $setting_config['variation'] ) ) {
					continue;
				}

				$variation = $setting_config['variation'];

				if ( empty( $variation ) ) {
					continue;
				}

				// Add the field type to the overrides if its not already set.
				if ( ! isset( $variations[ $field_type ] ) ) {
					$variations[ $field_type ] = array();
				}

				// Add the variables override.
				$variations[ $field_type ][ $input_type ] = $variation;
			}
		}

		if ( ! empty( $variations ) ) {
			$setting['variations'] = $variations;
		}

		return $setting;
	}

	/**
	 * Gets the variation of a setting based on field attributes.
	 *
	 * @param array $setting The setting to get the variation for.
	 * @param array $attributes The field attributes.
	 * @return array|null The variation if found, null otherwise.
	 */
	public static function get_variation( $setting, $attributes ) {
		if ( ! isset( $setting['variations'] ) ) {
			return null;
		}
		if ( empty( $attributes ) ) {
			return null;
		}
		if ( ! isset( $attributes['type'] ) ) {
			return null;
		}

		$field_type = $attributes['type'];
		if ( ! isset( $setting['variations'][ $field_type ] ) ) {
			return null;
		}

		if ( $field_type === 'control' && ! isset( $attributes['controlType'] ) ) {
			return null;
		} elseif ( ! isset( $attributes['inputType'] ) ) {
			return null;
		}

		$input_type = '';
		if ( $field_type === 'control' ) {
			$input_type = $attributes['controlType'];
		} else {
			$input_type = $attributes['inputType'];
		}

		if ( ! isset( $setting['variations'][ $field_type ][ $input_type ] ) ) {
			return null;
		}

		return $setting['variations'][ $field_type ][ $input_type ];
	}

	/**
	 * Build support matrices for a setting.
	 *
	 * Builds both the setting-level matrix and option-level matrices in a single pass
	 * through all field types and input types, with optimization applied during construction.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting to build matrices for.
	 * @return array Array with 'matrix' and 'options' keys containing optimized dependency matrices.
	 */
	protected static function build_support_matrices( $setting ) {
		$input_type_matrix = Field_Factory::get_field_input_types();
		$setting_name      = $setting['name'];
		$is_reserved       = isset( $setting['isReserved'] ) && $setting['isReserved'] === true;
		$field_type_count  = count( $input_type_matrix );

		$support_matrix = array(
			'matrix'  => array(),
			'options' => array(),
		);

		// Optimization tracking for setting matrix.
		$setting_global_all_true  = 0;
		$setting_global_all_false = 0;

		// Optimization tracking for option matrices (keyed by option value).
		$options_global_counts = array();

		foreach ( $input_type_matrix as $field_type => $input_types ) {
			$input_type_count = count( $input_types );

			// Track counts for optimization (setting matrix).
			$setting_enabled_count  = 0;
			$setting_disabled_count = 0;

			// Track counts for optimization (each option matrix).
			$options_counts = array();

			foreach ( $input_types as $input_type => $input_type_class ) {

				// Get the setting support config for this input type.
				$setting_supports = array();
				if ( $setting['tab'] === 'styles' ) {
					$setting_supports = $input_type_class::get_styles_support();
				} else {
					$setting_supports = $input_type_class::get_setting_support();
				}

				$setting_supports = self::apply_setting_support_upgrades( $setting_supports );

				if ( ! array_key_exists( $setting_name, $setting_supports ) ) {
					continue;
				}

				$setting_config = $setting_supports[ $setting_name ];

				// Setting config can be `true` or an array with `values` and `conditions` props.
				if ( $setting_config === false ) {
					// Then this setting is not supported.
					++$setting_disabled_count;
					continue;
				} elseif ( $setting_config === true ) {
					// Add to setting matrix.
					if ( ! isset( $support_matrix['matrix'][ $field_type ] ) ) {
						$support_matrix['matrix'][ $field_type ] = array();
					}
					$support_matrix['matrix'][ $field_type ][] = array( $input_type, true );
					++$setting_enabled_count;
					continue;
				} elseif ( ! is_array( $setting_config ) ) {
					// Then this setting is not supported.
					continue;
				}

				// If conditions are not set, then values must be, which means the setting is supported.
				if ( ! isset( $setting_config['conditions'] ) ) {
					++$setting_enabled_count;
				}

				$setting_conditions = isset( $setting_config['conditions'] ) ? $setting_config['conditions'] : array();

				// Add to setting matrix (unless it's a reserved setting).
				if ( ! $is_reserved ) {
					if ( ! isset( $support_matrix['matrix'][ $field_type ] ) ) {
						$support_matrix['matrix'][ $field_type ] = array();
					}
					$support_matrix['matrix'][ $field_type ][] = array( $input_type, $setting_conditions );
				}

				// Handle option-specific values.
				$setting_values = isset( $setting_config['values'] ) ? $setting_config['values'] : array();
				// Backwards compat for the old `get_data_support` hook.
				$setting_values = self::apply_setting_support_legacy_values( $setting_values, $setting['name'], $field_type, $input_type );

				if ( ! empty( $setting_values ) && isset( $setting['options'] ) && ! empty( $setting['options'] ) ) {
					$setting_options = $setting['options'];
					foreach ( $setting_options as $option ) {
						if ( ! isset( $option['value'] ) ) {
							continue;
						}
						$option_value = $option['value'];

						// Initialize tracking for this option if needed.
						if ( ! isset( $options_counts[ $option_value ] ) ) {
							$options_counts[ $option_value ] = array(
								'enabled'  => 0,
								'disabled' => 0,
							);
						}

						// Setup the support matrix for this option.
						if ( ! array_key_exists( $option_value, $support_matrix['options'] ) ) {
							$support_matrix['options'][ $option_value ] = array();
						}
						// Init the field type if it doesn't exist.
						if ( ! isset( $support_matrix['options'][ $option_value ][ $field_type ] ) ) {
							$support_matrix['options'][ $option_value ][ $field_type ] = array();
						}

						// Check if this value exists in the options.
						if ( ! array_key_exists( $option_value, $setting_values ) ) {
							// Then the option is not supported.
							$support_matrix['options'][ $option_value ][ $field_type ][] = array( $input_type, false );
							++$options_counts[ $option_value ]['disabled'];
						} else {
							$depends_condition = null;
							if ( is_bool( $setting_values[ $option_value ] ) ) {
								$depends_condition = $setting_values[ $option_value ];
							} elseif ( is_array( $setting_values[ $option_value ] ) ) {
								// Unlike the settings conditions which are pre-parsed, option conditions can be flat arrays,
								// which need to be grouped together as a rule set if there are multiple.

								// If there is only 1 condition, then we can collapse the array.
								if ( count( $setting_values[ $option_value ] ) === 1 ) {
									$depends_condition = $setting_values[ $option_value ][0];
								} else {
									// Otherwise combine them into a ruleset.
									$depends_condition = array(
										'relation' => 'AND',
										'rules'    => $setting_values[ $option_value ],
									);
								}
							} else {
								continue;
							}

							$support_matrix['options'][ $option_value ][ $field_type ][] = array( $input_type, $depends_condition );

							// Track enabled/disabled for optimization.
							if ( $depends_condition === true ) {
								++$options_counts[ $option_value ]['enabled'];
							} elseif ( $depends_condition === false ) {
								++$options_counts[ $option_value ]['disabled'];
							} elseif ( empty( $depends_condition ) ) {
								++$options_counts[ $option_value ]['enabled'];
							}
						}
					}
				}
			}

			// Optimize setting matrix for this field type.
			if ( $input_type_count > 0 && isset( $support_matrix['matrix'][ $field_type ] ) ) {
				if ( $setting_disabled_count === $input_type_count ) {
					$support_matrix['matrix'][ $field_type ] = false;
					++$setting_global_all_false;
				} elseif ( $setting_enabled_count === $input_type_count ) {
					$support_matrix['matrix'][ $field_type ] = true;
					++$setting_global_all_true;
				}
			}

			// Optimize each option matrix for this field type.
			foreach ( $options_counts as $option_value => $counts ) {
				// Initialize global counts for this option if needed.
				if ( ! isset( $options_global_counts[ $option_value ] ) ) {
					$options_global_counts[ $option_value ] = array(
						'all_true'  => 0,
						'all_false' => 0,
					);
				}

				if ( $input_type_count > 0 && isset( $support_matrix['options'][ $option_value ][ $field_type ] ) ) {
					if ( $counts['disabled'] === $input_type_count ) {
						$support_matrix['options'][ $option_value ][ $field_type ] = false;
						++$options_global_counts[ $option_value ]['all_false'];
					} elseif ( $counts['enabled'] === $input_type_count ) {
						$support_matrix['options'][ $option_value ][ $field_type ] = true;
						++$options_global_counts[ $option_value ]['all_true'];
					}
				}
			}
		}

		// Global optimization: setting matrix.
		if ( $setting_global_all_true === $field_type_count || $setting_global_all_false === $field_type_count ) {
			$support_matrix['matrix'] = array();
		}

		// Global optimization: option matrices.

		foreach ( $options_global_counts as $option_value => $global_counts ) {
			if ( $global_counts['all_true'] === $field_type_count || $global_counts['all_false'] === $field_type_count ) {
				$support_matrix['options'][ $option_value ] = array();
			}
		}

		return $support_matrix;
	}

	/**
	 * Build the settings conditions based on the settings support settings.
	 *
	 * @param array $setting The setting to build the conditions for.
	 * @return array The setting with conditions.
	 */
	protected static function build_settings_conditions( $setting ) {
		// Phase 1: Build optimized matrices in a single pass.
		$matrices = self::build_support_matrices( $setting );

		// Phase 2: Build setting-level conditions from optimized matrix.
		$setting_conditions = static::get_depends_conditions_from_matrix( $matrices['matrix'] );
		if ( ! empty( $setting_conditions ) ) {
			$existing             = isset( $setting['dependsOn'] ) ? $setting['dependsOn'] : null;
			$setting['dependsOn'] = self::merge_depends_conditions( $existing, $setting_conditions );

			// Copy the dependency action or set default to 'auto'.
			$dependency_action = 'auto';
			if ( $existing && isset( $existing['action'] ) ) {
				$dependency_action = $existing['action'];
				unset( $existing['action'] );
			}
			$setting['dependsOn']['action'] = $dependency_action;
		}

		// Phase 3: Build option-level conditions from optimized matrices.
		if ( isset( $setting['options'] ) && ! empty( $setting['options'] ) && ! empty( $matrices['options'] ) ) {
			$setting_options = $setting['options'];

			foreach ( $setting_options as $option_key => $option ) {
				if ( ! isset( $option['value'] ) ) {
					continue;
				}

				$option_value = $option['value'];

				if ( isset( $matrices['options'][ $option_value ] ) ) {
					$option_conditions = static::get_depends_conditions_from_matrix( $matrices['options'][ $option_value ] );

					if ( ! empty( $option_conditions ) ) {
						$existing            = isset( $option['dependsOn'] ) ? $option['dependsOn'] : null;
						$option['dependsOn'] = self::merge_depends_conditions( $existing, $option_conditions );

						// Copy the dependency action or set default to 'auto'.
						$dependency_action = 'auto';
						if ( $existing && isset( $existing['action'] ) ) {
							$dependency_action = $existing['action'];
							unset( $existing['action'] );
						}
						$option['dependsOn']['action'] = $dependency_action;
					}
				}

				// Update the option.
				$setting_options[ $option_key ] = $option;
			}

			$setting['options'] = $setting_options;
		}

		return $setting;
	}

	/**
	 * Builds a set of depends conditions based on a settings matrix.
	 *
	 * @param array $matrix The matrix to build the conditions from.
	 *
	 * @return array The depends conditions.
	 */
	public static function get_depends_conditions_from_matrix( $matrix ) {
		$field_type_depends_conditions = array();
		$unique_field_types            = array();

		foreach ( $matrix as $field_type => $supported_input_types ) {
			$unique_field_types[]                    = $field_type;
			$input_type_depends_conditions           = array();
			$input_type_depends_not_equal_conditions = array();

			if ( is_array( $supported_input_types ) ) {
				foreach ( $supported_input_types as $input_data ) {
					$input_type       = $input_data[0];
					$extra_conditions = $input_data[1];
					$is_supported     = true;
					if ( is_bool( $extra_conditions ) && $extra_conditions === false ) {
						// If the extra conditions are false, then we don't need to add any conditions .
						$is_supported = false;
					}
					$option           = $field_type === 'control' ? 'controlType' : 'inputType';
					$option_condition = array(
						'option'  => $option,
						'compare' => $is_supported ? '=' : '!=', // Change the compare type based on if its supported.
						'value'   => $input_type,
					);
					if ( $is_supported ) {
						if ( is_array( $extra_conditions ) && count( $extra_conditions ) > 0 ) {
							$input_type_depends_conditions[] = array(
								'relation' => 'AND',
								'rules'    => array( $option_condition, $extra_conditions ),
							);
						} else {
							$input_type_depends_conditions[] = $option_condition;
						}
					} else {
						// Unsupported conditions need to be grouped together with AND relation (all conditions must be met).
						$input_type_depends_not_equal_conditions[] = $option_condition;
					}
				}

				$conditions = array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => $field_type,
					),
				);
				if ( count( $input_type_depends_conditions ) > 0 ) {
					$conditions[] = array(
						'relation' => 'OR',
						'rules'    => $input_type_depends_conditions,
					);
				}
				if ( count( $input_type_depends_not_equal_conditions ) > 0 ) {
					$conditions[] = array(
						'relation' => 'AND',
						'rules'    => $input_type_depends_not_equal_conditions,
					);
				}

				$field_type_depends_conditions[] = array(
					'relation' => 'AND',
					'rules'    => $conditions,
				);
			} elseif ( is_bool( $supported_input_types ) ) {

				if ( $supported_input_types === true ) {
					// If the field type is supported, then we can just add a simple condition, which
					// is ORed with other field types.
					$field_type_depends_conditions[] = array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => $field_type,
					);
				}
			}
		}

		if ( count( $field_type_depends_conditions ) > 0 ) {
			$field_type_depends_conditions = array(
				'relation' => 'OR',
				'rules'    => $field_type_depends_conditions,
			);
		}

		return $field_type_depends_conditions;
	}

	/**
	 * Get the defaults by context.
	 *
	 * @param string $context The context to get defaults for.
	 *
	 * @return array The defaults.
	 */
	public static function get_defaults_by_context( $context ) {
		// TODO - we should use the actual parsed settings here..
		$defaults = array();
		$settings = static::get_source_settings();
		foreach ( $settings as $setting ) {
			// Ensure that type + context are set.
			if ( ! isset( $setting['context'] ) ) {
				continue;
			}
			if ( ! is_array( $setting['context'] ) ) {
				continue;
			}
			// Ff the setting has a matching context then get the default.
			if ( ! in_array( $context, $setting['context'], true ) ) {
				continue;
			}
			if ( ! isset( $setting['default'] ) ) {
				continue;
			}
			$defaults[ $setting['name'] ] = $setting['default'];
		}
		return $defaults;
	}
	/**
	 * Gets settings based on context.
	 *
	 * @param string $context The context to get settings for.
	 * @param string $return_as Optional. Format to return settings in. Default 'objects'.
	 *
	 * @return array The settings.
	 */
	public static function get_settings_by_context( $context, $return_as = 'objects' ) {
		return static::get_settings_by( 'context', $context, $return_as );
	}

	/**
	 * Gets settings by tab.
	 *
	 * @param string $tab The tab to get settings for.
	 * @param string $return_as Optional. Format to return settings in. Default 'objects'.
	 *
	 * @return array The settings.
	 */
	public static function get_settings_by_tab( $tab, $return_as = 'objects' ) {
		return static::get_settings_by( 'tab', $tab, $return_as );
	}
}
