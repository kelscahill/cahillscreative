<?php
/**
 * ACF Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

use Search_Filter\Core\Exception;
use Search_Filter\Features;
use Search_Filter\Fields\Choice;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Queries\Query;
use Search_Filter\Settings;
use Search_Filter_Pro\Fields\Data_Types\Custom_Field;
use Search_Filter_Pro\Fields\Indexer as Fields_Indexer;
use Search_Filter\Util;
use Search_Filter_Pro\Indexer\Bitmap\Database\Index_Query_Direct as Bitmap_Index_Query_Direct;
use Search_Filter_Pro\Indexer\Bitmap\Manager as Bitmap_Manager;
use Search_Filter_Pro\Integrations\Acf\Indexer;
use Search_Filter_Pro\Integrations\Acf\Search_Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * All Acf integration functionality
 * Add options to admin, integrate with frontend queries
 */
class Acf {

	/**
	 * The supported ACF fields types when using WP_Query filtering.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $wp_query_supported_fields = array(
		'search'   => array(
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'password',
		),
		'choice'   => array(
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'password',
			'radio',
		),
		'range'    => array(
			'number',
			'text',
		),
		'advanced' => array(
			'date_picker',
			'date_time_picker',
		),
	);

	/**
	 * The supported ACF fields types when using the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $indexer_supported_fields = array(
		'search'   => array(
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'password',
			'radio',
			'checkbox',
			'select',
			'button',
		),
		'choice'   => array(
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'password',
			'radio',
			'checkbox',
			'select',
			'button',
			'post_object',
			'relationship',
			'taxonomy',
			'range',
		),
		'range'    => array(
			'number',
			'text',
			'range',
		),
		'advanced' => array(
			'date_picker',
			'date_time_picker',
		),
	);

	/**
	 * The field types that are nested (have child fields)
	 *
	 * @var array
	 */
	private static $nested_field_types = array( 'group', 'repeater', 'flexible_content' );

	/**
	 * Track which fields are part of a repeater.
	 *
	 * @var array
	 */
	private static $children_of_repeaters = array();

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/settings/init', array( __CLASS__, 'update_integration' ), 10 );

		// Need to update field support before field settings are setup.
		// We are already inside the `search-filter/settings/integrations/init` hook.
		if ( ! self::acf_enabled() ) {
			return;
		}
		if ( ! Integrations::is_enabled( 'acf' ) ) {
			return;
		}

		add_filter( 'search-filter/fields/settings/prepare_setting/before', array( __CLASS__, 'add_acf_data_type' ), 10, 1 );
		// Enable the various input types for when a ACF is selected.
		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 12, 3 );

		add_filter( 'search-filter/fields/field/url_name', array( __CLASS__, 'add_custom_field_url_name' ), 10, 2 );

		Indexer::init();
		Search_Indexer::init();
	}



	/**
	 * Update the ACF integration in the integrations section.
	 *
	 * @since 3.0.0
	 */
	public static function update_integration() {
		// We want to disable coming soon notice and enable the integration toggle.
		$acf_integration = Integrations_Settings::get_setting( 'acf' );
		if ( ! $acf_integration ) {
			return;
		}
		$is_acf_enabled = self::acf_enabled();

		$update_integration_settings = array(
			'isIntegrationEnabled' => $is_acf_enabled,
			'isExtensionInstalled' => true,
		);

		// If we detect ACF is enabled, then lets also set the plugin installed
		// property to true - in case someone renamed the folder.
		if ( $is_acf_enabled ) {
			$update_integration_settings['isIntegrationInstalled'] = true;
		}

		$acf_integration->update( $update_integration_settings );

		if ( ! self::acf_enabled() ) {
			return;
		}

		if ( ! Integrations::is_enabled( 'acf' ) ) {
			return;
		}

		self::setup();
	}


	/**
	 * Setup the main hooks for the ACF integration.
	 *
	 * @since 3.0.0
	 */
	public static function setup() {
		// Add WC options to the admin UI.
		add_action( 'rest_api_init', array( __CLASS__, 'add_routes' ) );
		add_filter( 'search-filter-pro/field/search/autocomplete/suggestions', array( __CLASS__, 'get_autocomplete_suggestions' ), 10, 3 );
		add_filter( 'search-filter/fields/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/wp_query_args', array( __CLASS__, 'get_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/range/wp_query_args', array( __CLASS__, 'get_range_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/advanced/wp_query_args', array( __CLASS__, 'get_advanced_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/fields/choice/options', array( __CLASS__, 'add_field_choice_options' ), 10, 2 );
		add_filter( 'search-filter/fields/range/auto_detect_custom_field', array( __CLASS__, 'auto_detect_custom_field' ), 10, 2 );

		// Legacy search indexing to keep ACF search data in the legacy indexer.
		// Only needed up until the migration process has completed.
		add_filter( 'search-filter/indexer/legacy/get_query_type', array( __CLASS__, 'enable_legacy_indexer_search' ), 2, 2 ); // Priority of 2 after the indexer has done its thing.

		self::register_settings();
	}

	/**
	 * Filter the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @param    string $input_type    The input type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function get_field_setting_support( $setting_support, $type, $input_type ) {

		$supported_matrix = array(
			'choice'   => array( 'select', 'radio', 'checkbox', 'button' ),
			'search'   => array( 'text', 'autocomplete' ),
			'range'    => array( 'select', 'slider', 'number', 'radio' ),
			'advanced' => array( 'date_picker' ),
		);

		if ( isset( $supported_matrix[ $type ] ) && in_array( $input_type, $supported_matrix[ $type ], true ) ) {

			// Add support for the custom field data type.
			$setting_support = Field::add_setting_support_value( $setting_support, 'dataType', array( 'acf_field' => true ) );
			// Add support for the ACF settings.
			$setting_support['dataAcfGroup']         = true;
			$setting_support['dataAcfField']         = true;
			$setting_support['dataAcfIndexerNotice'] = true;
		}

		return $setting_support;
	}

	/**
	 * Check if ACF is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if ACF is enabled.
	 */
	private static function acf_enabled() {
		return class_exists( 'ACF' );
	}

	/**
	 * Add the routes for the ACF integration.
	 *
	 * @since 3.0.0
	 */
	public static function add_routes() {
		register_rest_route(
			'search-filter/v1',
			'/settings/options/acf-groups',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_rest_acf_groups_options' ),
				'args'                => array(
					'queryId'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'dataAcfGroup' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( __CLASS__, 'permissions' ),
				'allow_batch'         => true,
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/settings/options/acf-fields',
			array(
				'methods'             => array( \WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_acf_fields_options' ),
				'args'                => array(
					'queryId'      => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'dataAcfGroup' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'type'         => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'permission_callback' => array( __CLASS__, 'permissions' ),
				'allow_batch'         => true,
			)
		);
	}

	/**
	 * Check if the user has the permissions to access the settings.
	 *
	 * @since 3.0.0
	 *
	 * @return bool    True if the user has the permissions.
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}
	/**
	 * Register acfFields field settings
	 *
	 * @return void
	 */
	private static function register_settings() {

		$add_setting_args = array();
		// Group setting.
		$setting                      = array(
			'name'         => 'dataAcfGroup',
			'type'         => 'string',
			'default'      => '',
			'inputType'    => 'Select',
			'label'        => __( 'Group / Parent', 'search-filter' ),
			'placeholder'  => __( 'Choose Group', 'search-filter' ),
			'group'        => 'data',
			'tab'          => 'settings',
			'context'      => array( 'admin/field', 'block/field' ),
			'options'      => array(),
			'isDataType'   => true, // Flag data types for the indexer to detect changes.
			'dependsOn'    => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'acf_field',
					),
				),
			),
			'dataProvider' => array(
				'route' => '/settings/options/acf-groups',
				'args'  => array(
					'queryId',
				),
			),
		);
		$add_setting_args['position'] = array(
			'placement' => 'after',
			'setting'   => 'dataType',
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );

		// Indexer notice.
		$setting                      = array(
			'name'      => 'dataAcfIndexerNotice',
			'content'   => __( 'Showing limited ACF field types. Enable the indexer unlock more field types and improve performance.', 'search-filter-pro' ),
			'group'     => 'data',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Notice',
			'status'    => 'warning',
			'context'   => array( 'admin/field', 'block/field' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'acf_field',
					),
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			),
		);
		$add_setting_args['position'] = array(
			'placement' => 'before',
			'setting'   => 'dataAcfGroup',
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );

		// Field setting.
		$setting                      = array(
			'name'         => 'dataAcfField',
			'type'         => 'string',
			'default'      => '',
			'inputType'    => 'Select',
			'label'        => __( 'Field', 'search-filter' ),
			'placeholder'  => __( 'Choose Field', 'search-filter' ),
			'group'        => 'data',
			'tab'          => 'settings',
			'context'      => array( 'admin/field', 'block/field' ),
			'options'      => array(),
			'isDataType'   => true, // Flag data types for the indexer to detect changes.
			'dependsOn'    => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'acf_field',
					),
					array(
						'option'  => 'dataAcfGroup',
						'compare' => '!=',
						'value'   => '',
					),
				),
			),
			'dataProvider' => array(
				'route' => '/settings/options/acf-fields',
				'args'  => array(
					'queryId',
					'dataAcfGroup',
					'type',
				),
			),
			'supports'     => array(
				'previewAPI' => true,
			),
		);
		$add_setting_args['position'] = array(
			'placement' => 'after',
			'setting'   => 'dataAcfGroup',
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );
	}

	/**
	 * Add custom field data type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function add_acf_data_type( array $setting ) {

		if ( $setting['name'] !== 'dataType' ) {
			return $setting;
		}

		if ( ! is_array( $setting['options'] ) ) {
			return $setting;
		}

		$setting['options'][] = array(
			'label' => __( 'ACF Field', 'search-filter' ),
			'value' => 'acf_field',
		);

		return $setting;
	}
	/**
	 * Get the ACF groups.
	 *
	 * @since 3.0.0
	 *
	 * @return \WP_REST_Response|null
	 */
	public static function get_rest_acf_groups_options() {
		if ( ! self::acf_enabled() ) {
			return null;
		}
		if ( ! function_exists( '\acf_get_field_groups' ) ) {
			return null;
		}
		$acf_groups_options = self::get_acf_groups_options();
		$return             = array(
			'options' => $acf_groups_options,
		);
		return rest_ensure_response( $return );
	}
	/**
	 * Get the ACF groups hierarchically.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The ACF groups options.
	 */
	public static function get_acf_groups_options() {
		$options      = array();
		$field_groups = \acf_get_field_groups();

		foreach ( $field_groups as $group ) {
			$label      = isset( $group['title'] ) ? $group['title'] : $group['label'];
			$option     = array(
				'label' => $label,
				'value' => $group['key'],
				'depth' => 0,
			);
			$options[]  = $option;
			$acf_fields = \acf_get_fields( $group['key'] );
			$options    = array_merge( $options, self::get_acf_nested_group_fields_options( $acf_fields ) );
		}
		return $options;
	}

	/**
	 * Get the ACF nested group fields options.
	 *
	 * @since 3.0.0
	 *
	 * @param array $fields    The fields to get the options for.
	 * @param int   $depth     The depth of the group.
	 * @return array    The ACF nested group fields options.
	 */
	public static function get_acf_nested_group_fields_options( $fields, $depth = 0 ) {
		$options = array();
		++$depth;

		foreach ( $fields as $field ) {

			if ( ! in_array( $field['type'], self::$nested_field_types, true ) ) {
				// Skip non-supported nested types.
				continue;
			}

			$option = array(
				'label' => $field['label'],
				'value' => $field['key'],
				'depth' => $depth,
			);

			$options[] = $option;

			if ( $field['type'] === 'flexible_content' ) {
				$layouts = $field['layouts'];
				foreach ( $layouts as $layout ) {
					$options = array_merge( $options, self::get_acf_nested_group_fields_options( $layout['sub_fields'], $depth ) );
				}
			} else {
				$options = array_merge( $options, self::get_acf_nested_group_fields_options( $field['sub_fields'], $depth ) );
			}
		}

		return $options;
	}

	/**
	 * Get the options for acf-fields by group.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request    The request object.
	 * @return \WP_REST_Response|null    The options for acf-fields by group.
	 */
	public static function get_acf_fields_options( \WP_REST_Request $request ) {

		$query_id   = $request->get_param( 'queryId' );
		$group_key  = $request->get_param( 'dataAcfGroup' );
		$field_type = $request->get_param( 'type' );

		if ( ! self::acf_enabled() ) {
			return null;
		}

		$is_parent_group = strpos( $group_key, 'group_' ) === 0;

		$options = array();

		if ( ! function_exists( '\acf_get_fields' ) ) {
			return null;
		}

		if ( $group_key === '' ) {
			return rest_ensure_response( array( 'options' => $options ) );
		}

		if ( $is_parent_group ) {
			$acf_fields = \acf_get_fields( $group_key );
			$options    = self::build_sub_field_options( $acf_fields, $field_type, $query_id );
		} else {
			$acf_group_field = \get_field_object( $group_key );

			if ( ! $acf_group_field ) {
				return rest_ensure_response( array( 'options' => $options ) );
			}
			if ( ! in_array( $acf_group_field['type'], self::$nested_field_types, true ) ) {
				return rest_ensure_response( array( 'options' => $options ) );
			}

			if ( $acf_group_field['type'] === 'flexible_content' ) {
				$layouts = $acf_group_field['layouts'];
				foreach ( $layouts as $layout ) {
					$options = array_merge( $options, self::build_sub_field_options( $layout['sub_fields'], $field_type, $query_id ) );
				}
			} else {
				$options = self::build_sub_field_options( $acf_group_field['sub_fields'], $field_type, $query_id );
			}
		}

		if ( count( $options ) === 0 ) {
			$options[] = array(
				'label' => __( 'No fields found', 'search-filter-pro' ),
				'value' => '',
			);
		}
		$return = array(
			'options' => $options,
		);
		return rest_ensure_response( $return );
	}

	/**
	 * Build sub field options for ACF field groups.
	 *
	 * @param array  $sub_fields  The sub fields to process.
	 * @param string $field_type  The field type to filter by.
	 * @param int    $query_id    The query ID.
	 * @return array The formatted field options.
	 */
	private static function build_sub_field_options( $sub_fields, $field_type, $query_id ) {
		$options          = array();
		$supported_fields = self::get_supported_field_types( $query_id );
		foreach ( $sub_fields as $field ) {
			if ( ! in_array( $field['type'], $supported_fields[ $field_type ], true ) ) {
				continue;
			}
			$options[] = array(
				'label' => $field['label'] !== '' ? $field['label'] : __( '(no label)', 'search-filter-pro' ),
				'value' => $field['key'],
			);
		}

		return $options;
	}

	/**
	 * Get the supported field types for a query.
	 *
	 * If the query is using the indexer, then we need to check if the fields
	 * are using the indexer or not.
	 *
	 * @since 3.0.0
	 *
	 * @param int $query_id    The query ID.
	 * @return array    The supported field types matrix.
	 */
	private static function get_supported_field_types( $query_id ) {
		$query = Query::get_instance( absint( $query_id ) );
		if ( is_wp_error( $query ) ) {
			return self::$wp_query_supported_fields;
		}

		if ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
			return self::$wp_query_supported_fields;
		}

		return self::$indexer_supported_fields;
	}
	/**
	 * Get the autocomplete suggestions for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array                       $suggestions    The suggestions to get the autocomplete suggestions for.
	 * @param string                      $search_term    The search term.
	 * @param \Search_Filter\Fields\Field $field    The field.
	 * @return array    The autocomplete suggestions.
	 */
	public static function get_autocomplete_suggestions( $suggestions, $search_term, $field ) {
		if ( ! self::acf_enabled() ) {
			return $suggestions;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $suggestions;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		if ( ! $field_key ) {
			return $suggestions;
		}

		global $wpdb;

		if ( Fields_Indexer::field_is_connected_to_indexer( $field ) ) {
			$new_suggestions = array();
			// Then lets get the suggestions from the indexer directly.
			$table_name = Bitmap_Manager::get_table_name();
			$order      = Custom_Field::build_sql_order_by( $field, 'value' );

			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( 'SELECT DISTINCT value FROM %i WHERE field_id = %d AND value LIKE %s', $table_name, $field->get_id(), $search_term . '%' ) . $order
			);

			if ( $results === null ) {
				return array();
			}

			foreach ( $results as $result_item ) {
				$new_suggestions[] = $result_item->value;
			}

			return $new_suggestions;
		}

		// Now do a WP DB query on the post meta table for the field meta key using the search term to partially match the beginning of the value.
		$field_meta_key = self::generate_field_key( $field_key );
		$order          = Custom_Field::build_sql_order_by( $field, 'meta_value' );

		if ( self::field_key_has_repeater_parent( $field_key ) ) {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key LIKE %s AND meta_value LIKE %s", $field_meta_key, $search_term . '%' ) . $order
			);
		} else {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s", $field_meta_key, $search_term . '%' ) . $order
			);
		}

		if ( $results === null ) {
			return array();
		}

		$new_suggestions = array();

		foreach ( $results as $result ) {
			$new_suggestions[] = $result->meta_value;
		}

		return $new_suggestions;
	}

	/**
	 * Enable the legacy indexer search for search fields connected to ACF
	 * data types.
	 *
	 * @param string $query_type The query type.
	 * @param Field  $field      The field to check.
	 * @return string The query type.
	 */
	public static function enable_legacy_indexer_search( $query_type, $field ) {

		if ( $field->get_attribute( 'type' ) !== 'search' ) {
			return $query_type;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $query_type;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );
		if ( ! $field_key ) {
			return $query_type;
		}

		// Support using the indexer for search fields with ACF.
		if ( ! Fields_Indexer::field_is_connected_to_indexer( $field ) ) {
			return $query_type;
		}

		// Set the query type to indexer.
		return 'indexer';
	}


	/**
	 * Get the WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the WP query args for.
	 * @param Field $field    The field.
	 * @return array    The WP query args.
	 */
	public static function get_search_wp_query_args( $query_args, $field ) {

		if ( ! self::acf_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $query_args;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );
		if ( ! $field_key ) {
			return $query_args;
		}

		// Support using the indexer for search fields with ACF,
		// so return early and disable the wp_query args.
		if ( Fields_Indexer::field_is_connected_to_indexer( $field ) ) {
			return $query_args;
		}

		$search_term = $field->get_value();
		if ( $search_term === '' ) {
			return $query_args;
		}

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$field_meta_key = self::generate_field_key( $field_key );

		// Add the field meta query.
		$query_args['meta_query'][] = array(
			'key'     => $field_meta_key,
			'value'   => $search_term,
			'compare' => 'LIKE',
		);

		return $query_args;
	}

	/**
	 * Get the choice WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the choice WP query args for.
	 * @param Field $field    The field.
	 * @return array    The choice WP query args.
	 */
	public static function get_choice_wp_query_args( $query_args, $field ) {
		if ( ! self::acf_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $query_args;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		if ( ! $field_key ) {
			return $query_args;
		}

		$values = $field->get_values();
		if ( empty( $values ) ) {
			return $query_args;
		}

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$custom_field_key = self::generate_field_key( $field_key );

		$compare_type = 'IN';
		$match_mode   = $field->get_attribute( 'multipleMatchMethod' );
		$values       = $field->get_values();

		/**
		 * We are checking for multiple values to determine the query logic,
		 * but we don't check the field settigs itself.  This might be ok
		 * though, keep an eye on this.
		 *
		 * TODO - we could apply the same logic to the tax queries.
		 */
		$is_mutiple   = count( $values ) > 1;
		$compare_type = $match_mode === 'all' ? 'AND' : 'IN';

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( $is_mutiple && $compare_type === 'AND' ) {
			$sub_meta_query = array(
				'relation' => 'AND',
			);
			foreach ( $values as $value ) {
				$sub_meta_query[] = array(
					'key'     => sanitize_text_field( $custom_field_key ),
					'compare' => '=',
					'value'   => $value,
					'type'    => 'CHAR',
				);
			}
			$query_args['meta_query'][] = $sub_meta_query;
		} else {
			$query_args['meta_query'][] = array(
				array(
					'key'     => sanitize_text_field( $custom_field_key ),
					'value'   => array_map( 'sanitize_text_field', $values ),
					'compare' => 'IN',
					'type'    => 'CHAR',
				),
			);
		}
		return $query_args;
	}

	/**
	 * Get the range WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the range WP query args for.
	 * @param Field $field    The field.
	 * @return array    The range WP query args.
	 */
	public static function get_range_wp_query_args( $query_args, $field ) {
		if ( ! self::acf_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $query_args;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		if ( ! $field_key ) {
			return $query_args;
		}

		$values = $field->get_values();
		if ( count( $values ) !== 2 ) {
			return $query_args;
		}

		$from = $values[0];
		$to   = $values[1];

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$custom_field_key = self::generate_field_key( $field_key );
		$decimal_places   = $field->get_attribute( 'rangeDecimalPlaces' );

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query_args['meta_query'][] = array(
			'key'     => sanitize_text_field( $custom_field_key ),
			'value'   => array( sanitize_text_field( $from ), sanitize_text_field( $to ) ),
			'compare' => 'BETWEEN',
			'type'    => 'DECIMAL(12,' . absint( $decimal_places ) . ')',
		);
		return $query_args;
	}

	/**
	 * Get the range WP query args for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args    The query args to get the range WP query args for.
	 * @param Field $field    The field.
	 * @return array    The range WP query args.
	 */
	public static function get_advanced_wp_query_args( $query_args, $field ) {
		if ( ! self::acf_enabled() ) {
			return $query_args;
		}

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $query_args;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );

		if ( ! $field_key ) {
			return $query_args;
		}

		// So far there is only a date input type.
		if ( $field->get_attribute( 'inputType' ) !== 'date_picker' ) {
			return $query_args;
		}

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$values = $field->get_values();

		if ( count( $values ) === 1 ) {

			$custom_field_key = self::generate_field_key( $field_key );

			$query_args['meta_query'][] = array(
				'key'     => sanitize_text_field( $custom_field_key ),
				'value'   => sanitize_text_field( $values[0] ),
				'compare' => '=',
				'type'    => 'DATE',
			);
		}

		if ( count( $values ) === 2 ) {

			$from = $values[0];
			$to   = $values[1];

			// If there is no meta query key then create one.
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			// If there is no relation add it.
			if ( ! isset( $query_args['meta_query']['relation'] ) ) {
				$query_args['meta_query']['relation'] = 'AND';
			}

			$custom_field_key = self::generate_field_key( $field_key );

			$query_args['meta_query'][] = array(
				'key'     => sanitize_text_field( $custom_field_key ),
				'value'   => array( sanitize_text_field( $from ), sanitize_text_field( $to ) ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			);
		}

		return $query_args;
	}

	/**
	 * Generate the meta key for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param string $field_key    The field key.
	 * @return string|array|false    The meta key.
	 */
	public static function generate_field_key( $field_key ) {
		$field = \acf_maybe_get_field( $field_key );

		if ( empty( $field ) || ! isset( $field['parent'], $field['name'] ) ) {
			return $field;
		}

		$ancestors = array();
		while ( ! empty( $field['parent'] ) && ! in_array( $field['name'], $ancestors, true ) ) {
			$parent = \acf_get_field( $field['parent'] );

			// Repeaters can have any number after the field name for each entry.
			if ( $field['type'] === 'repeater' || $field['type'] === 'flexible_content' ) {
				// Track which field keys have repeater ancestors to change the meta queries.
				self::$children_of_repeaters[ $field_key ] = true;
				$ancestors[]                               = $field['name'] . '_%';

			} else {
				$ancestors[] = $field['name'];
			}
			$field = $parent;
		}

		$formatted_key = array_reverse( $ancestors );
		$formatted_key = implode( '_', $formatted_key );

		return $formatted_key;
	}

	/**
	 * Check if the field key has a repeater parent.
	 *
	 * @param string $field_key The field key to check.
	 * @return bool True if the field has a repeater parent.
	 */
	private static function field_key_has_repeater_parent( $field_key ) {
		return isset( self::$children_of_repeaters[ $field_key ] );
	}

	/**
	 * Check if the field uses the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field    The field to check.
	 * @return bool    True if the field uses the indexer.
	 *
	 * @throws Exception If the field is not a choice field.
	 */
	private static function field_uses_indexer( $field ) {
		$query_id = $field->get_attribute( 'queryId' );
		if ( ! $query_id ) {
			return false;
		}

		$query = Query::get_instance( absint( $query_id ) );
		if ( is_wp_error( $query ) ) {
			return false;
		}
		return $query->get_attribute( 'useIndexer' ) === 'yes';
	}

	/**
	 * Add the field choice options for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $options    The options to add the field choice options for.
	 * @param Field $field    The field.
	 * @return array    The updated options.
	 */
	public static function add_field_choice_options( $options, $field ) {

		$data_type = $field->get_attribute( 'dataType' );
		if ( $data_type !== 'acf_field' ) {
			return $options;
		}

		if ( count( $options ) > 0 ) {
			return $options;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );
		if ( ! $field_key ) {
			return $options;
		}

		$acf_field = \acf_maybe_get_field( $field_key );

		if ( empty( $acf_field ) ) {
			return $options;
		}

		$values = $field->get_values();

		if ( isset( $acf_field['choices'] ) ) {

			// Sort according to the order direction.
			$acf_choices     = $acf_field['choices'];
			$order           = $field->get_attribute( 'inputOptionsOrder' );
			$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';

			$acf_choices = Util::sort_assoc_array( $acf_choices, $order, $order_direction );
			foreach ( $acf_choices as $key => $label ) {

				$key   = (string) $key;
				$label = (string) $label;

				Choice::add_option_to_array(
					$options,
					array(
						'value' => $key,
						'label' => $label,
					),
					$field->get_id()
				);

				if ( in_array( $key, $values, true ) ) {
					$field->set_value_labels( array( $key => $label ) );
				}
			}
		} elseif ( $acf_field['type'] === 'post_object' || $acf_field['type'] === 'relationship' ) {
			$options = self::get_post_relationship_options( $field, $acf_field );
		} elseif ( $acf_field['type'] === 'taxonomy' ) {
			$options = self::get_taxonomy_relationship_options( $field, $acf_field );
		} else {
			// If the field didn't have choices and it wasn't a relationship or taxonomy, lets assume its a
			// single value string (ie text input).
			$options = self::get_single_value_options( $field, $acf_field );
		}

		// Support ordering by count.
		$field_order = $field->get_attribute( 'inputOptionsOrder' );
		if ( $field_order === 'count' ) {
			$field_order_dir = $field->get_attribute( 'inputOptionsOrderDir' );
			$options         = Util::sort_assoc_array_by_property( $options, 'count', 'numerical', $field_order_dir );
		}

		return $options;
	}

	/**
	 * Get the options for a relationship field.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field    The field to get the options for.
	 * @param array $acf_field    The ACF field to get the options for.
	 * @return array    The options for the field.
	 */
	public static function get_post_relationship_options( $field, $acf_field ) {

		$options         = array();
		$order           = $field->get_attribute( 'inputOptionsOrder' );
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';

		if ( self::field_uses_indexer( $field ) && $field->get_id() !== 0 ) {

			$cache_key = Fields_Indexer::get_field_options_cache_key( $field );

			$cached_field_posts = wp_cache_get( $cache_key, 'search-filter-pro' );

			if ( $cached_field_posts === false ) {
				// Use index query direct for better performance with optimized indexes.
				$ids = Bitmap_Index_Query_Direct::get_unique_field_values( $field->get_id() );

				if ( empty( $ids ) ) {
					return $options;
				}

				// Array map the IDs, and run $wpdb->prepare with the number placehold on each item.
				$ids     = array_map( 'absint', $ids );
				$ids_sql = '(' . implode( ',', $ids ) . ')';
				// Use $wpdb to get post titles only based on the IDs.
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$cached_field_posts = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						"SELECT ID, post_title FROM %i WHERE ID IN $ids_sql",
						$wpdb->posts
					)
				);
				wp_cache_set( $cache_key, $cached_field_posts, 'search-filter-pro' );
			}

			$cached_field_posts = Util::sort_objects_by_property( $cached_field_posts, 'post_title', $order, $order_direction );
			$values             = $field->get_values();
			foreach ( $cached_field_posts as $post ) {
				$value = (string) $post->ID;
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $post->post_title,
					),
					$field->get_id()
				);
				if ( in_array( $value, $values, true ) ) {
					$field->set_value_labels( array( $value => $post->post_title ) );
				}
			}

			return $options;
		}

		// We should only get here in field previews, or unsaved fields which have
		// no index yet.
		$post_type   = $acf_field['post_type'];
		$post_status = $acf_field['post_status'];
		// TODO, support taxonomies in the preview.
		// $taxonomy = $acf_field['taxonomy'].
		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => $post_status === '' ? 'any' : $post_status,
				'posts_per_page' => 30,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids,title',
			)
		);

		$posts = $query->posts;
		$posts = Util::sort_objects_by_property( $posts, 'post_title', $order, $order_direction );

		foreach ( $posts as $post ) {
			Choice::add_option_to_array(
				$options,
				array(
					'value' => (string) $post->ID,
					'label' => $post->post_title,
				),
				$field->get_id()
			);
		}
		return $options;
	}
	/**
	 * Get the options for a relationship field.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field    The field to get the options for.
	 * @param array $acf_field    The ACF field to get the options for.
	 * @return array    The options for the field.
	 */
	public static function get_taxonomy_relationship_options( $field, $acf_field ) {

		$options         = array();
		$order           = $field->get_attribute( 'inputOptionsOrder' );
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
		$values          = $field->get_values();

		if ( self::field_uses_indexer( $field ) && $field->get_id() !== 0 ) {

			$cache_key = Fields_Indexer::get_field_options_cache_key( $field );

			$cached_field_terms = wp_cache_get( $cache_key, 'search-filter-pro' );

			if ( $cached_field_terms === false ) {
				// Use index query direct for better performance with optimized indexes.
				$ids = Bitmap_Index_Query_Direct::get_unique_field_values( $field->get_id() );

				if ( empty( $ids ) ) {
					return $options;
				}

				// Array map the IDs, and run $wpdb->prepare with the number placehold on each item.
				$ids     = array_map( 'absint', $ids );
				$ids_sql = '(' . implode( ',', $ids ) . ')';
				// Use $wpdb to get post titles only based on the IDs.
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$cached_field_terms = $wpdb->get_results(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
						"SELECT term_id, name FROM %i WHERE term_id IN $ids_sql",
						$wpdb->terms
					)
				);
				wp_cache_set( $cache_key, $cached_field_terms, 'search-filter-pro' );
			}

			$cached_field_terms = Util::sort_objects_by_property( $cached_field_terms, 'name', $order, $order_direction );

			foreach ( $cached_field_terms as $term ) {
				$value = (string) $term->term_id;
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $term->name,
					),
					$field->get_id()
				);

				if ( in_array( $value, $values, true ) ) {
					$field->set_value_labels( array( $value => $term->name ) );
				}
			}

			return $options;
		}

		// We should only get here in field previews, or unsaved fields which have
		// no index yet.
		$taxonomy     = $acf_field['taxonomy'];
		$term_options = Settings::create_taxonomy_terms_options( $taxonomy );
		$term_options = Util::sort_assoc_array_by_property( $term_options, 'label', $order, $order_direction );

		foreach ( $term_options as $term_option ) {
			// Ensure term IDs are strings for frontend JS compatibility.
			$value = (string) $term_option['value'];
			Choice::add_option_to_array(
				$options,
				array(
					'value' => $value,
					'label' => $term_option['label'],
				),
				$field->get_id()
			);

			if ( in_array( $value, $values, true ) ) {
				$field->set_value_labels( array( $value => $term_option['label'] ) );
			}
		}
		return $options;
	}

	/**
	 * Get the options for a single value fields.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field    The field to get the options for.
	 * @param array $acf_field    The ACF field to get the options for.
	 * @return array    The options for the field.
	 */
	public static function get_single_value_options( $field, $acf_field ) {

		$options         = array();
		$order           = $field->get_attribute( 'inputOptionsOrder' );
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';

		if ( self::field_uses_indexer( $field ) && $field->get_id() !== 0 ) {

			// Use index query direct for better performance with optimized indexes.
			$unique_values = Bitmap_Index_Query_Direct::get_unique_field_values( $field->get_id() );

			if ( empty( $unique_values ) ) {
				return $options;
			}

			$item_values = Util::sort_array( $unique_values, $order, $order_direction );
			$values      = $field->get_values();

			foreach ( $item_values as $value ) {
				if ( $value === '' ) {
					continue;
				}
				$value = (string) $value;
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $value,
					),
					$field->get_id()
				);

				if ( in_array( $value, $values, true ) ) {
					$field->set_value_labels( array( (string) $value => $value ) );
				}
			}

			return $options;
		}

		// Otherwise lookup in the meta query table.
		$cache_key                = Fields_Indexer::get_field_options_cache_key( $field );
		$cached_field_meta_values = wp_cache_get( $cache_key, 'search-filter-pro' );

		if ( $cached_field_meta_values === false ) {
			$field_key = $acf_field['key'];
			global $wpdb;
			$custom_field_key = self::generate_field_key( $field_key );

			$where = '';
			if ( self::field_key_has_repeater_parent( $field_key ) ) {
				$where = $wpdb->prepare( " WHERE meta_key LIKE %s AND meta_value!='' ", $custom_field_key );
			} else {
				$where = $wpdb->prepare( " WHERE meta_key=%s AND meta_value!='' ", $custom_field_key );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$cached_field_meta_values = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"SELECT DISTINCT(`meta_value`) FROM %i $where ORDER BY `meta_value` ASC	LIMIT 0, 100",
					$wpdb->postmeta
				)
			);

			wp_cache_set( $cache_key, $cached_field_meta_values, 'search-filter-pro' );
		}
		$cached_field_meta_values = Util::sort_objects_by_property( $cached_field_meta_values, 'meta_value', $order, $order_direction );

		foreach ( $cached_field_meta_values as $k => $v ) {
			Choice::add_option_to_array(
				$options,
				array(
					'value' => (string) $v->meta_value,
					'label' => $v->meta_value,
				),
				$field->get_id()
			);
		}

		return $options;
	}

	/**
	 * Add the custom field URL name.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $url_name    The URL name to add.
	 * @param    Field  $field       The field to add the URL name to.
	 * @return   string    The URL name.
	 */
	public static function add_custom_field_url_name( $url_name, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
			return $url_name;
		}
		$field_key = $field->get_attribute( 'dataAcfField' );

		if ( ! $field_key ) {
			return $url_name;
		}

		$field = \acf_maybe_get_field( $field_key );

		if ( empty( $field ) || ! isset( $field['name'] ) ) {
			return $url_name;
		}
		return $field['name'];
	}

	/**
	 * Get the custom field key for the range fields.
	 *
	 * @since 3.0.0
	 *
	 * @param string $custom_field_key  The custom field key.
	 * @param array  $attributes        The field attributes.
	 * @return string|array|false    The custom field key.
	 */
	public static function auto_detect_custom_field( $custom_field_key, $attributes ) {
		if ( ! isset( $attributes['dataType'] ) ) {
			return $custom_field_key;
		}
		if ( $attributes['dataType'] !== 'acf_field' ) {
			return $custom_field_key;
		}
		if ( ! isset( $attributes['dataAcfField'] ) ) {
			return $custom_field_key;
		}

		$custom_field_key = self::generate_field_key( $attributes['dataAcfField'] );

		return $custom_field_key;
	}
}
