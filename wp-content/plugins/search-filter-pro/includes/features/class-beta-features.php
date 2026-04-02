<?php
/**
 * Sets up the support for the beta features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter_Pro\Features;

use Search_Filter_Pro\Features\Beta\Settings;
use Search_Filter_Pro\Features\Beta\Settings_Data;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\Woocommerce;
use Search_Filter\Features;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages beta features for Search & Filter Pro.
 *
 * @since 3.0.0
 */
class Beta_Features {

	/**
	 * Initialize the beta features.
	 */
	public static function init() {
		// Setup the beta features once features are initialized.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'setup' ), 10 );

		// Preload the option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );

		add_filter( 'search-filter-pro/indexer/strategy/supports', array( __CLASS__, 'filter_search_strategy_supports' ), 10, 3 );
	}

	/**
	 * Preload the beta features option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array The updated options array.
	 */
	public static function preload_option( $options_to_preload ) {
		// Preload the beta features option.
		$options_to_preload[] = 'beta-features';
		return $options_to_preload;
	}

	/**
	 * Filter whether a search strategy supports a given field.
	 *
	 * @since 3.2.0
	 *
	 * @param bool|mixed $supports Whether the strategy supports the field.
	 * @param object     $field The field object.
	 * @param object     $strategy The strategy object.
	 * @return bool|mixed Whether the strategy supports the field.
	 */
	public static function filter_search_strategy_supports( $supports, $field, $strategy ) {

		// Only filter for search strategy.
		if ( $strategy->get_type() !== 'search' ) {
			return $supports;
		}

		// Disable all search strategy support if beta features and enhanced search is not enabled.
		if ( ! Features::is_enabled( 'betaFeatures' ) ) {
			return false;
		}

		if ( Features::get_setting_value( 'beta-features', 'enhancedSearch' ) !== 'yes' ) {
			return false;
		}

		// Now its enabled, validate the field as normal.

		$interaction_type = $field->get_interaction_type();

		return $strategy->supports_interaction_type( $interaction_type );
	}

	/**
	 * Setup the beta features.
	 */
	public static function setup() {

		// TODO - remove once the search indexer is out of beta.
		add_filter( 'search-filter-pro/indexer/search/should_use', '__return_false', 11 );

		add_filter( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );

		Settings::init( Settings_Data::get(), Settings_Data::get_groups() );

		add_action( 'search-filter/settings/setting/updated', array( __CLASS__, 'handle_enhanced_search_setting_updated' ), 10, 3 );

		if ( Features::get_setting_value( 'beta-features', 'enhancedSearch' ) === 'yes' ) {
			// Add data sources setting - run on settings/init because some other settings are registered there (custom fields, acf etc).
			add_action( 'search-filter/settings/init', array( __CLASS__, 'register_field_data_sources_setting' ), 11 );

			// Add post attributes for the advanced search field.
			add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_search_post_attributes' ), 11, 1 );

			add_filter( 'search-filter-pro/indexer/search/should_use', '__return_true', 12 );

			// Add support for taxonomy related settings.
			add_filter(
				'search-filter/fields/field/get_setting_support',
				array(
					__CLASS__,
					'add_enhanced_search_data_sources',
				),
				10,
				2
			);

		} else {
			// Disable the search tables until this feature is enabled.
			add_filter( 'search-filter-pro/indexer/search/should_use', '__return_false', 11 );
		}

		if ( Features::get_setting_value( 'beta-features', 'queryOptimizer' ) === 'yes' ) {
			// Add data sources setting - run on settings/init because some other settings are registered there (custom fields, acf etc).
			add_filter( 'search-filter-pro/database/query_optimizer/enable', '__return_true' );
		}
	}

	/**
	 * Add the preload API paths.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $paths    The paths to add.
	 * @return   array    The paths to add.
	 */
	public static function add_preload_api_paths( $paths ) {
		$paths[] = '/search-filter/v1/admin/settings?section=beta-features';
		$paths[] = '/search-filter/v1/settings?section=beta-features';
		return $paths;
	}

	/**
	 * Get the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function add_enhanced_search_data_sources( $setting_support, $type ) {

		if ( $type !== 'search' ) {
			return $setting_support;
		}

		$data_type_conditions = array(
			array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '!=',
				'value'   => 'yes',
			),
		);

		$setting_support['dataType'] = array(
			'conditions' => array(
				// Wrap the conditions in a relation to allow for future expansion.
				// Copies the logic from `get_setting_support` which should be refactored
				// later on the in the prepare settings flow.
				'relation' => 'OR',
				'rules'    => array(
					array(
						'relation' => 'AND',
						'rules'    => $data_type_conditions,
					),
				),
			),
			'values'     => array(
				'post_attribute' => true,
				'taxonomy'       => true,
				'custom_field'   => true,
			),
		);

		$data_sources_conditions        = array(
			array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '=',
				'value'   => 'yes',
			),
		);
		$setting_support['dataSources'] = array(
			'conditions' => array(
				// Wrap the conditions in a relation to allow for future expansion.
				// Copies the logic from `get_setting_support` which should be refactored
				// later on the in the prepare settings flow.
				'relation' => 'OR',
				'rules'    => array(
					array(
						'relation' => 'AND',
						'rules'    => $data_sources_conditions,
					),
				),
			),
		);

		// Update post attribute support for the new post attributes, and disable
		// the default attribute.
		// Add support for the WC data type.
		$setting_support = Field::add_setting_support_value(
			$setting_support,
			'dataPostAttribute',
			array(
				'default'      => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'compare' => '!=',
						'value'   => 'yes',
					),
				),
				'post_title'   => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
				'post_content' => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
				'post_excerpt' => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
				'post_type'    => true,
				'post_status'  => true,
			)
		);

		return $setting_support;
	}


	/**
	 * Add the new post data types for advanced search fields.
	 *
	 * @since 3.2.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function add_search_post_attributes( array $setting ) {
		if ( $setting['name'] !== 'dataPostAttribute' ) {
			return $setting;
		}

		if ( ! is_array( $setting['options'] ) ) {
			return $setting;
		}

		$search_data_types = array(
			array(
				'label' => __( 'Post Title + Content', 'search-filter' ),
				'value' => 'default',
			),
			array(
				'label' => __( 'Post Title', 'search-filter' ),
				'value' => 'post_title',
			),
			array(
				'label' => __( 'Post Content', 'search-filter' ),
				'value' => 'post_content',
			),
			array(
				'label' => __( 'Post Excerpt', 'search-filter' ),
				'value' => 'post_excerpt',
			),
		);

		// Prepend the search options.
		$setting['options'] = array_merge( $search_data_types, $setting['options'] );

		return $setting;
	}
	/**
	 * Register the pro field settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_field_data_sources_setting() {

		$add_setting_args = array();

		$setting = array(
			'name'            => 'dataSources',
			'label'           => __( 'Data Sources', 'search-filter' ),
			'group'           => 'data',
			'tab'             => 'settings',
			'type'            => 'array',
			'inputType'       => 'Repeater',
			'isDataType'      => true, // Flag data types for the indexer to detect changes.
			'items'           => array(
				'type' => 'object',
			),
			'default'         => array( array() ),
			'minItems'        => 1,
			'showItemCount'   => false,
			'allowDuplicates' => false,
			'canCollapse'     => true,
			'itemLabel'       => __( 'Data Source', 'search-filter' ),
			'addButtonLabel'  => __( 'Add Data Source', 'search-filter' ),
			'context'         => array( 'admin/field', 'block/field' ),
			'supports'        => array(
				'previewAPI' => true,
			),
			'dependsOn'       => array(
				'action'   => 'hide',
				'relation' => 'AND',
				'rules'    => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
			),
			'settings'        => array(),
		);

		// Prepare sub settings of the repeater.
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );
		if ( $data_type_setting ) {
			$data_type_setting_config = $data_type_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_type_setting_config = self::convert_setting_to_repeater( $data_type_setting_config );

			// Remove the data type conditions.
			if ( isset( $data_type_setting_config['dependsOn'] ) ) {
				unset( $data_type_setting_config['dependsOn'] );
			}

			// Remove Relevanssi option if its there.
			$parsed_options = array();
			foreach ( $data_type_setting_config['options'] as $option ) {
				if ( $option['value'] === 'relevanssi' ) {
					continue;
				}
				$parsed_options[] = $option;
			}
			$data_type_setting_config['options'] = $parsed_options;

			$setting['settings'][] = $data_type_setting_config;
		}

		$data_post_attribute_setting = Fields_Settings::get_setting( 'dataPostAttribute' );
		if ( $data_post_attribute_setting ) {
			$data_post_attribute_setting_config = $data_post_attribute_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_post_attribute_setting_config = self::convert_setting_to_repeater( $data_post_attribute_setting_config );

			$setting['settings'][] = $data_post_attribute_setting_config;
		}

		$data_taxonomy_setting = Fields_Settings::get_setting( 'dataTaxonomy' );
		if ( $data_taxonomy_setting ) {
			$data_taxonomy_setting_config = $data_taxonomy_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_taxonomy_setting_config = self::convert_setting_to_repeater( $data_taxonomy_setting_config );

			$setting['settings'][] = $data_taxonomy_setting_config;
		}
		$data_woocommerce_setting = Fields_Settings::get_setting( 'dataWoocommerce' );
		if ( $data_woocommerce_setting ) {
			$data_woocommerce_setting_config = $data_woocommerce_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_woocommerce_setting_config = self::convert_setting_to_repeater( $data_woocommerce_setting_config );

			$suppored_wc_options = Woocommerce::get_taxonomies_options_values();
			// Also allow SKU for search indexing.
			$suppored_wc_options[] = 'sku';

			$parsed_options = array();
			foreach ( $data_woocommerce_setting_config['options'] as $option ) {
				// If the option is not in the supported options, remove it.
				if ( ! in_array( $option['value'], $suppored_wc_options, true ) ) {
					continue;
				}
				if ( isset( $option['dependsOn'] ) ) {
					unset( $option['dependsOn'] );
				}
				$parsed_options[] = $option;
			}
			$data_woocommerce_setting_config['options'] = $parsed_options;
			$setting['settings'][]                      = $data_woocommerce_setting_config;
		}
		$data_custom_field_setting = Fields_Settings::get_setting( 'dataCustomField' );
		if ( $data_custom_field_setting ) {
			$data_custom_field_setting_config = $data_custom_field_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_custom_field_setting_config = self::convert_setting_to_repeater( $data_custom_field_setting_config );
			$setting['settings'][]            = $data_custom_field_setting_config;
		}

		$data_acf_group_setting = Fields_Settings::get_setting( 'dataAcfGroup' );
		if ( $data_acf_group_setting ) {
			$data_acf_group_setting_config = $data_acf_group_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_acf_group_setting_config = self::convert_setting_to_repeater( $data_acf_group_setting_config );
			$setting['settings'][]         = $data_acf_group_setting_config;
		}
		$data_acf_field_setting = Fields_Settings::get_setting( 'dataAcfField' );
		if ( $data_acf_field_setting ) {
			$data_acf_field_setting_config = $data_acf_field_setting->get_data();
			// Convert setting to use correct scope inside a repeater.
			$data_acf_field_setting_config = self::convert_setting_to_repeater( $data_acf_field_setting_config );
			$setting['settings'][]         = $data_acf_field_setting_config;
		}

		// Add exactMatch toggle for identifier-style data sources (SKUs, product codes, etc.).
		// When enabled, the full value is stored as a single token alongside tokenized parts.
		$exact_match_setting   = array(
			'name'      => 'exactMatch',
			'label'     => __( 'Exact Match', 'search-filter-pro' ),
			'help'      => __( 'Enable for identifiers like SKUs or product codes. Stores the full value as a searchable token.', 'search-filter-pro' ),
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter-pro' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter-pro' ),
				),
			),
			'context'   => array( 'admin/field', 'block/field' ),
		);
		$setting['settings'][] = $exact_match_setting;

		$add_setting_args = array(
			'position' => array(
				'placement' => 'start',
			),
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );
	}
	/**
	 * Convert a setting configuration for use inside a repeater.
	 *
	 * Processes the setting's dependencies and option dependencies (if dependantOptions is enabled)
	 * to use the correct scope inside a repeater field. Also converts dataProvider args to modern format.
	 *
	 * @since 3.2.0
	 *
	 * @param array $setting_config The setting configuration to convert.
	 * @return array The converted setting configuration.
	 */
	private static function convert_setting_to_repeater( $setting_config ) {
		if ( ! is_array( $setting_config ) ) {
			return $setting_config;
		}

		// Convert the setting's own dependencies if they exist.
		if ( isset( $setting_config['dependsOn'] ) && is_array( $setting_config['dependsOn'] ) ) {
			$setting_config['dependsOn'] = self::convert_dependencies_for_repeater( $setting_config['dependsOn'] );
		}

		// Check if this setting has dependant options enabled.
		$has_dependant_options = isset( $setting_config['supports']['dependantOptions'] ) && $setting_config['supports']['dependantOptions'] === true;

		// If it has dependant options, convert each option's dependencies.
		if ( $has_dependant_options && isset( $setting_config['options'] ) && is_array( $setting_config['options'] ) ) {
			$setting_config['options'] = array_map(
				function ( $option ) {
					if ( isset( $option['dependsOn'] ) && is_array( $option['dependsOn'] ) ) {
						$option['dependsOn'] = self::convert_dependencies_for_repeater( $option['dependsOn'] );
					}
					return $option;
				},
				$setting_config['options']
			);
		}

		// Convert dataProvider args to modern format if they exist.
		if ( isset( $setting_config['dataProvider']['args'] ) && is_array( $setting_config['dataProvider']['args'] ) ) {
			$setting_config['dataProvider']['args'] = array_map(
				function ( $arg ) {
					// If already in modern format (array with 'key' property), return as is.
					if ( is_array( $arg ) && isset( $arg['key'] ) ) {
						return $arg;
					}

					// Convert from legacy string format to modern object format.
					if ( is_string( $arg ) ) {
						$converted_arg = array(
							'key' => $arg,
						);

						// Add 'store' => 'attributes' for specific keys.
						if ( in_array( $arg, array( 'inputType', 'type', 'queryId' ), true ) ) {
							$converted_arg['store'] = 'attributes';
						}

						return $converted_arg;
					}

					// Return as is if not a recognized format.
					return $arg;
				},
				$setting_config['dataProvider']['args']
			);
		}

		return $setting_config;
	}

	/**
	 * Convert dependency conditions for use inside a repeater.
	 *
	 * Recursively processes dependency conditions and adds 'store' => 'attributes'
	 * to any conditions that depend on 'type' or 'inputType'.
	 *
	 * @since 3.2.0
	 *
	 * @param array $dependencies The dependency conditions to convert.
	 * @return array The converted dependency conditions.
	 */
	private static function convert_dependencies_for_repeater( $dependencies ) {
		if ( ! is_array( $dependencies ) ) {
			return $dependencies;
		}

		// Process the rules array if it exists.
		if ( isset( $dependencies['rules'] ) && is_array( $dependencies['rules'] ) ) {
			$dependencies['rules'] = array_map(
				function ( $rule ) {
					// If this rule has nested rules, recurse into it.
					if ( isset( $rule['rules'] ) && is_array( $rule['rules'] ) ) {
						return self::convert_dependencies_for_repeater( $rule );
					}

					// If this rule depends on 'type' or 'inputType', add the store property.
					if ( isset( $rule['option'] ) && ( $rule['option'] === 'type' || $rule['option'] === 'inputType' || $rule['option'] === 'queryId' ) ) {
						$rule['store'] = 'attributes';
					}

					return $rule;
				},
				$dependencies['rules']
			);
		}

		return $dependencies;
	}

	/**
	 * Handle enhanced search setting update.
	 *
	 * Converts field data types when enhanced search is enabled/disabled.
	 *
	 * @since 3.2.0
	 *
	 * @param string $section The settings section.
	 * @param string $setting_key The setting key.
	 * @param string $new_value The new setting value.
	 */
	public static function handle_enhanced_search_setting_updated( $section, $setting_key, $new_value ) {
		if ( $section !== 'beta-features' || $setting_key !== 'enhancedSearch' ) {
			return;
		}

		// Enable search indexer filter BEFORE field conversions so rebuilds
		// can populate search tables (the default filter is __return_false).
		if ( $new_value === 'yes' ) {
			add_filter( 'search-filter-pro/indexer/search/should_use', '__return_true', 20 );
			\Search_Filter_Pro\Indexer\Search\Manager::ensure_tables();
		}

		// Populate meta for existing fields (for efficient validation queries).
		$fields = Fields::find( array( 'number' => 0 ) );
		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			// Ensure we only target search fields and those using the indexer.
			if ( $field->get_attribute( 'type' ) !== 'search' ) {
				continue;
			}

			// Check if the field is using the indexer.
			$field_query = $field->get_query();

			if ( ! $field_query || is_wp_error( $field_query ) ) {
				continue;
			}

			if ( $field_query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				continue;
			}

			// Convert classic dataType <> dataSources.
			// If the value changed to yes.
			if ( $new_value === 'yes' ) {
				self::convert_field_data_type_to_sources( $field );
			}

			// If the value changed to no.
			if ( $new_value === 'no' ) {
				// Disable the search indexer.
				self::convert_field_data_sources_to_type( $field );
			}
		}
	}


	/**
	 * Converts legacy dataType attributes to the new dataSources format.
	 *
	 * Stores the original attributes in field meta before converting.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to convert.
	 */
	public static function convert_field_data_type_to_sources( $field ) {

		// If we already have data sources set, return early.
		if ( ! empty( $field->get_attribute( 'dataSources' ) ) ) {
			return;
		}

		// If we don't have a data type set, return early - shouldn't be possible.
		if ( empty( $field->get_attribute( 'dataType' ) ) ) {
			return;
		}

		// Build the new data sources array.
		$data_sources = array();

		// Track legacy attributes for meta storage.
		$legacy_attributes = array();

		$data_type  = $field->get_attribute( 'dataType' );
		$attributes = $field->get_attributes();

		// Store dataType before we delete it.
		$legacy_attributes['dataType'] = $data_type;

		if ( isset( $attributes['dataPostAttribute'] ) ) {

			$legacy_attributes['dataPostAttribute'] = $attributes['dataPostAttribute'];
			$field->delete_attribute( 'dataPostAttribute' );

			if ( $attributes['dataPostAttribute'] === 'default' ) {
				// Default means both title and content so convert to two data sources.
				$data_sources[] = array(
					'dataType'          => $data_type,
					'dataPostAttribute' => 'post_title',
				);
				$data_sources[] = array(
					'dataType'          => $data_type,
					'dataPostAttribute' => 'post_content',
				);

			} else {
				$data_sources[] = array(
					'dataType'          => $data_type,
					'dataPostAttribute' => $attributes['dataPostAttribute'],
				);
			}
		}
		if ( isset( $attributes['dataTaxonomy'] ) ) {
			$legacy_attributes['dataTaxonomy'] = $attributes['dataTaxonomy'];
			$field->delete_attribute( 'dataTaxonomy' );
			$data_sources[] = array(
				'dataType'     => $data_type,
				'dataTaxonomy' => $attributes['dataTaxonomy'],
			);
		}
		if ( isset( $attributes['dataPostTypes'] ) ) {
			$legacy_attributes['dataPostTypes'] = $attributes['dataPostTypes'];
			$field->delete_attribute( 'dataPostTypes' );
			$data_sources[] = array(
				'dataType'      => $data_type,
				'dataPostTypes' => $attributes['dataPostTypes'],
			);
		}
		if ( isset( $attributes['dataPostStati'] ) ) {
			$legacy_attributes['dataPostStati'] = $attributes['dataPostStati'];
			$field->delete_attribute( 'dataPostStati' );
			$data_sources[] = array(
				'dataType'      => $data_type,
				'dataPostStati' => $attributes['dataPostStati'],
			);
		}
		if ( isset( $attributes['dataCustomField'] ) ) {
			$legacy_attributes['dataCustomField'] = $attributes['dataCustomField'];
			$field->delete_attribute( 'dataCustomField' );
			$data_sources[] = array(
				'dataType'        => $data_type,
				'dataCustomField' => $attributes['dataCustomField'],
			);
		}
		if ( isset( $attributes['dataAcfField'] ) ) {
			$legacy_attributes['dataAcfField'] = $attributes['dataAcfField'];
			$field->delete_attribute( 'dataAcfField' );
			$data_sources[] = array(
				'dataType'     => $data_type,
				'dataAcfField' => $attributes['dataAcfField'],
			);
		}
		if ( isset( $attributes['dataAcfGroup'] ) ) {
			$legacy_attributes['dataAcfGroup'] = $attributes['dataAcfGroup'];
			$field->delete_attribute( 'dataAcfGroup' );
			$data_sources[] = array(
				'dataType'     => $data_type,
				'dataAcfGroup' => $attributes['dataAcfGroup'],
			);
		}
		if ( isset( $attributes['dataWoocommerce'] ) ) {
			$legacy_attributes['dataWoocommerce'] = $attributes['dataWoocommerce'];
			$field->delete_attribute( 'dataWoocommerce' );
			$data_sources[] = array(
				'dataType'        => $data_type,
				'dataWoocommerce' => $attributes['dataWoocommerce'],
			);
		}

		// Remove the old data type attribute.
		$field->delete_attribute( 'dataType' );

		// Store legacy attributes in field meta for potential rollback.
		Field::update_meta( $field->get_id(), '_legacy_data_attributes', $legacy_attributes );

		// Set the new data sources attribute.
		$field->set_attribute( 'dataSources', $data_sources );
		$field->save();
	}

	/**
	 * Converts dataSources back to legacy dataType attributes.
	 *
	 * Restores from field meta stored during the original conversion.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field to convert.
	 */
	public static function convert_field_data_sources_to_type( $field ) {

		// If we don't have data sources set, return early.
		if ( empty( $field->get_attribute( 'dataSources' ) ) ) {
			return;
		}

		// Get the legacy attributes from meta.
		$legacy_attributes = Field::get_meta( $field->get_id(), '_legacy_data_attributes', true );

		// If no legacy attributes stored, we can't restore.
		if ( empty( $legacy_attributes ) || ! is_array( $legacy_attributes ) ) {
			return;
		}

		// Restore each legacy attribute.
		foreach ( $legacy_attributes as $key => $value ) {
			$field->set_attribute( $key, $value );
		}

		// Remove the dataSources attribute.
		$field->delete_attribute( 'dataSources' );

		// Delete the meta as it's no longer needed.
		Field::delete_meta( $field->get_id(), '_legacy_data_attributes' );
		$field->save();
	}

	/*
	 * Get a specific data source attribute by index.
	 *
	 * @since 3.2.0
	 *
	 * @param int $index The index of the data source to get.
	 * @return array|null The data source attribute or null if not found.
	 *
	 * public function get_data_source_attribute( $index = 0 ) {
	 *     $data_sources = $this->convert_field_data_type_to_sources();
	 *     if ( ! isset( $data_sources[ $index ] ) ) {
	 *         return null;
	 *     }
	 *     return $data_sources[ $index ];
	 * }
	 */
}
