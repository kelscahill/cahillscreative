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
	 * Track extended block settings    .
	 *
	 * @var array
	 */
	private static $extended_blocks = array();

	/**
	 * Init the settings.
	 *
	 * @param    array  $settings    The settings to add.
	 * @param    array  $groups    The groups to add.
	 * @param    string $register_name    The name of settings in the register.
	 */
	public static function init( $settings = array(), $groups = array(), $register_name = '' ) {
		parent::init( $settings, $groups, $register_name );
	}


	/**
	 * Get the external store.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The settings.
	 * @param array $attributes The attributes.
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

		$query = Query::find( array( 'id' => $attributes['queryId'] ), 'record' );
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
	 *
	 * @return \Search_Filter\Settings\Setting The prepared setting.
	 */
	protected static function prepare_setting( $setting, $args = array() ) {

		$setting = apply_filters( 'search-filter/fields/settings/prepare_setting/before', $setting, $args );

		// Update the dependsOn conditions based on field support.
		// Build the conditions based on the various supporting properties.
		$setting_name = $setting['name'];
		if ( $setting['tab'] === 'styles' ) {
			$setting = static::build_styles_conditions( $setting );
		} else {
			$setting = static::build_settings_conditions( $setting );
		}

		// Now build the dynamic options.
		if ( $setting_name === 'controlType' ) {
			$setting['options'] = static::build_control_type_options();
		} elseif ( $setting_name === 'inputType' ) {
			$setting['options'] = static::build_input_type_options();
		}

		if ( array_key_exists( 'extend_block_types', $args ) ) {
			$types = $args['extend_block_types'];
			// Then we need to add the setting to the extensions list.
			foreach ( $types as $type ) {
				if ( ! array_key_exists( $type, self::$extended_blocks ) ) {
					self::$extended_blocks[ $type ] = array();
				}
				$modified_setting = $setting;

				// Unset defaults for block editor attributes.  In our blocks we
				// have tons of extra attributes that may or may not be needed.
				if ( isset( $modified_setting['default'] ) ) {
					unset( $modified_setting['default'] );
				}
				self::$extended_blocks[ $type ][] = $modified_setting;
			}
		}
		$setting = parent::prepare_setting( $setting );
		return $setting;
	}

	/**
	 * Get the extended blocks.
	 */
	public static function get_extended_blocks() {
		return self::$extended_blocks;
	}

	/**
	 * Build the style conditions based on the style support settings.
	 *
	 * @param array $setting The setting to build the conditions for.
	 * @return array The style setting with conditions.
	 */
	protected static function build_styles_conditions( $setting ) {

		$input_type_matrix = Field_Factory::get_field_input_types();
		$setting_name      = $setting['name'];

		$support_matrix = array();

		foreach ( $input_type_matrix as $field_type => $input_types ) {

			foreach ( $input_types as $input_type => $input_type_class ) {

				// Build the conditions.
				$style_supports = $input_type_class::get_styles_support();

				if ( ! in_array( $setting_name, $style_supports, true ) ) {
					continue;
				}

				if ( ! isset( $support_matrix[ $field_type ] ) ) {
					$support_matrix[ $field_type ] = array();
				}
				$support_matrix[ $field_type ][] = $input_type;
			}
		}

		$field_type_depends_conditions = array();
		foreach ( $support_matrix as $field_type => $input_types ) {
			$input_type_depends_conditions = array();
			foreach ( $input_types as $input_type ) {
				// Build depends conditions for each field type, search, filter, or control.
				$option                          = $field_type === 'control' ? 'controlType' : 'inputType';
				$input_type_depends_conditions[] = array(
					'option'  => $option,
					'compare' => '=',
					'value'   => $input_type,
				);
			}

			$field_type_depends_conditions[] = array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => $field_type,
					),
					array(
						'relation' => 'OR',
						'rules'    => $input_type_depends_conditions,
					),
				),
			);
		}

		if ( count( $field_type_depends_conditions ) > 0 ) {
			$setting['dependsOn'] = array(
				'relation' => 'OR',
				'rules'    => $field_type_depends_conditions,
			);
		}

		return $setting;
	}

	/**
	 * Build the settings conditions based on the settings support settings.
	 *
	 * @param array $setting The setting to build the conditions for.
	 * @return array The setting with conditions.
	 */
	protected static function build_settings_conditions( $setting ) {

		$input_type_matrix = Field_Factory::get_field_input_types();
		$setting_name      = $setting['name'];

		$support_matrix = array(
			'matrix'  => array(),
			'options' => array(),
		);
		foreach ( $input_type_matrix as $field_type => $input_types ) {

			foreach ( $input_types as $input_type => $input_type_class ) {

				// Build the conditions.
				$setting_supports = $input_type_class::get_setting_support();

				if ( ! array_key_exists( $setting_name, $setting_supports ) ) {
					continue;
				}

				$setting_config = $setting_supports[ $setting_name ];

				$setting_conditions = isset( $setting_config['conditions'] ) ? $setting_config['conditions'] : array();
				$setting_values     = isset( $setting_config['values'] ) ? $setting_config['values'] : array();

				// Only support settings that have been set to true, or an array of values
				// which means they support specific options only.
				$is_reserved = isset( $setting['isReserved'] ) && $setting['isReserved'] === true;
				if ( ( $setting_values !== true && ! is_array( $setting_values ) ) && ! $is_reserved ) {
					continue;
				}

				if ( ! $is_reserved ) {
					// Reserved settings don't need a dependency matrix.
					if ( ! isset( $support_matrix['matrix'][ $field_type ] ) ) {
						$support_matrix['matrix'][ $field_type ] = array();
					}
					$support_matrix['matrix'][ $field_type ][] = array( $input_type, $setting_conditions );
				}

				// Now loop through the options and build their matrix.
				if ( is_array( $setting_values ) ) {
					foreach ( $setting_values as $setting_value ) {
						if ( ! array_key_exists( $setting_value, $support_matrix['options'] ) ) {
							$support_matrix['options'][ $setting_value ] = array();
						}

						if ( ! isset( $support_matrix['options'][ $setting_value ][ $field_type ] ) ) {
							$support_matrix['options'][ $setting_value ][ $field_type ] = array();
						}

						$support_matrix['options'][ $setting_value ][ $field_type ][] = array( $input_type, $setting_conditions );
					}
				}
			}
		}

		// Now loop throught the support matrix and build the depends conditions.
		$setting_matrix = $support_matrix['matrix'];
		$options_matrix = $support_matrix['options'];

		$setting_depends_conditions = static::get_depends_conditions_from_matrix( $setting_matrix );
		if ( ! empty( $setting_depends_conditions ) ) {
			if ( isset( $setting['dependsOn'] ) ) {
				// Then there are already some conditions we need to honor.
				$old_conditions = $setting['dependsOn'];
				// Combine the old conditions and the new ones using AND relationship.
				$setting['dependsOn'] = array(
					'relation' => 'AND',
					'rules'    => array( $old_conditions, $setting_depends_conditions ),
				);
			} else {
				$setting['dependsOn'] = $setting_depends_conditions;
			}
		}

		// Now loop through options that may have conditions.
		if ( isset( $setting['options'] ) && ! empty( $setting['options'] ) ) {

			$setting_options = $setting['options'];
			foreach ( $setting_options as $option_key => $option ) {
				if ( ! isset( $option['value'] ) ) {
					continue;
				}
				if ( isset( $options_matrix[ $option['value'] ] ) ) {
					$option_depends_conditions = static::get_depends_conditions_from_matrix( $options_matrix[ $option['value'] ] );
					if ( ! empty( $option_depends_conditions ) ) {
						if ( isset( $option['dependsOn'] ) ) {
							// Then there are already some conditions we need to honor.
							$old_conditions = $option['dependsOn'];
							// Combine the old conditions and the new ones using AND relationship.
							$option['dependsOn'] = array(
								'relation' => 'AND',
								'rules'    => array( $old_conditions, $option_depends_conditions ),
							);
						} else {
							$option['dependsOn'] = $option_depends_conditions;
						}
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
	 * Build the input type options based the field input types
	 * data support setting.
	 *
	 * @return array The input type options.
	 */
	protected static function build_input_type_options() {
		$input_type_matrix = Field_Factory::get_field_input_types();
		// Now build the inputType options and conditions from the matrix.
		$input_type_options_list = array();
		foreach ( $input_type_matrix as $field_type => $input_types ) {
			foreach ( $input_types as $input_type => $input_type_class ) {
				// Build the data type conditions.
				$data_supports = $input_type_class::get_data_support();

				$all_criteria_conditions = array();
				foreach ( $data_supports as $match_criteria ) {
					$match_conditions = array();
					foreach ( $match_criteria as $setting_name => $setting_criteria ) {
						$condition = array();

						if ( is_scalar( $setting_criteria ) ) {
							$condition = array(
								'option'  => $setting_name,
								'compare' => '=',
								'value'   => $setting_criteria,
							);
						} elseif ( is_array( $setting_criteria ) ) {
							$conditions = array();
							foreach ( $setting_criteria as $setting_value ) {
								$condition    = array(
									'option'  => $setting_name,
									'compare' => '=',
									'value'   => $setting_value,
								);
								$conditions[] = $condition;
							}
							$condition = array(
								'relation' => 'OR',
								'rules'    => $conditions,
							);
						}
						$match_conditions[] = $condition;

					}

					$all_criteria_conditions[] = array(
						'relation' => 'AND',
						'rules'    => $match_conditions,
					);
				}

				// Always add the condition that the field type must match.
				$option_depends_rules = array(
					// Make the type of field mandatory.
					array(
						'option'  => 'type',
						'compare' => '=',
						'value'   => $field_type,
					),
				);

				// If we have generated conditions, add them.
				if ( ! empty( $all_criteria_conditions ) ) {
					$option_depends_rules[] = array(
						'relation' => 'OR',
						'rules'    => $all_criteria_conditions,
					);
				}

				if ( ! isset( $input_type_options_list[ $input_type ] ) ) {
					$input_type_options_list[ $input_type ] = array(
						'label'           => $input_type_class::get_label(),
						'value'           => $input_type,
						'conditions_list' => array(),
					);
				}

				// Collect all the conditions for this input type.
				$input_type_options_list[ $input_type ]['conditions_list'][] = array(
					'relation' => 'AND',
					'rules'    => $option_depends_rules,
				);
			}
		}
		$input_type_options = array();
		// Check for any input types that are used multiple times and
		// create combined conditions for them.
		foreach ( $input_type_options_list as $input_type => $option_to_combine ) {
			$combined_option      = array(
				'label'     => $option_to_combine['label'],
				'value'     => $option_to_combine['value'],
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => $option_to_combine['conditions_list'],
				),
			);
			$input_type_options[] = $combined_option;
		}
		return $input_type_options;
	}

	/**
	 * Build the control type options based the control input types.
	 *
	 * @return array The control type options.
	 */
	protected static function build_control_type_options() {
		$input_type_matrix = Field_Factory::get_field_input_types();
		// Now build the controlType options and conditions from the matrix.
		$control_type_options = array();
		foreach ( $input_type_matrix['control'] as $control_type => $control_type_class ) {
			$option = array(
				'label' => $control_type_class::get_label(),
				'value' => $control_type,
			);

			$control_type_options[] = $option;
		}
		return $control_type_options;
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

		foreach ( $matrix as $field_type => $input_types ) {

			$input_type_depends_conditions = array();
			foreach ( $input_types as $input_data ) {
				$input_type       = $input_data[0];
				$extra_conditions = $input_data[1];
				$option           = $field_type === 'control' ? 'controlType' : 'inputType';
				$option_condition = array(
					'option'  => $option,
					'compare' => '=',
					'value'   => $input_type,
				);
				if ( count( $extra_conditions ) > 0 ) {
					$input_type_depends_conditions[] = array(
						'relation' => 'AND',
						'rules'    => array(
							$option_condition,
							$extra_conditions,
						),
					);
				} else {
					$input_type_depends_conditions[] = $option_condition;
				}
			}

			$conditions = array(
				array(
					'option'  => 'type',
					'compare' => '=',
					'value'   => $field_type,
				),
				array(
					'relation' => 'OR',
					'rules'    => $input_type_depends_conditions,
				),
			);

			$field_type_depends_conditions[] = array(
				'relation' => 'AND',
				'rules'    => $conditions,
			);
		}

		if ( count( $field_type_depends_conditions ) > 0 ) {
			return array(
				'relation' => 'OR',
				'rules'    => $field_type_depends_conditions,
			);
		}
		return array();
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
	 *
	 * @return array The settings.
	 */
	public static function get_settings_by_tab( $tab, $return_as = 'objects' ) {
		return static::get_settings_by( 'tab', $tab, $return_as );
	}
}
