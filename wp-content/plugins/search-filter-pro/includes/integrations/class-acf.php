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
use Search_Filter\Fields\Choice;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Fields;
use Search_Filter_Pro\Indexer\Database\Index_Query;
use Search_Filter\Util;

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
			/*
			 'select',
			'button', */
		),
		'range'    => array(
			'number',
		),
		'advanced' => array(),
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
		),
		'range'    => array(
			'number',
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
		// We are already inside the `search-filter/integrations/init` hook.
		if ( ! self::acf_enabled() ) {
			return;
		}
		if ( ! Integrations::is_enabled( 'acf' ) ) {
			return;
		}

		add_filter( 'search-filter/field/get_data_support', array( __CLASS__, 'get_field_data_support' ), 10, 3 );
		add_filter( 'search-filter/indexer/sync_field_index/override_values', array( __CLASS__, 'index_values' ), 10, 3 );
		add_filter( 'search-filter/field/url_name', array( __CLASS__, 'add_custom_field_url_name' ), 10, 2 );
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
		$acf_integration->update(
			array(
				'comingSoon' => false,
				'disabled'   => self::acf_enabled() ? false : true,
			)
		);

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
		add_filter( 'search-filter/field/search/wp_query_args', array( __CLASS__, 'get_search_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/choice/wp_query_args', array( __CLASS__, 'get_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/range/wp_query_args', array( __CLASS__, 'get_range_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/choice/options', array( __CLASS__, 'add_field_choice_options' ), 10, 2 );
		add_filter( 'search-filter/field/range/auto_detect_custom_field', array( __CLASS__, 'auto_detect_custom_field' ), 10, 2 );

		self::register_settings();
	}

	/**
	 * On S&F settings register, add a new setting + update others
	 *
	 * @since    3.0.0
	 */
	public static function register_settings() {
		self::add_acf_field_option_to_data_type();
		self::add_acf_fields_settings();
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
	 * Add the ACF field option to the data type setting.
	 *
	 * @since 3.0.0
	 */
	protected static function add_acf_field_option_to_data_type() {
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );
		if ( ! $data_type_setting ) {
			return;
		}

		$acf_data_type_option = array(
			'label' => __( 'ACF Field', 'search-filter' ),
			'value' => 'acf_field',
		);
		$data_type_setting->add_option( $acf_data_type_option );
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
	private static function add_acf_fields_settings() {

		$all_types        = array( 'search', 'choice', 'range', 'advanced', 'control' );
		$add_setting_args = array(
			'extend_block_types' => $all_types,
		);
		// Group setting.
		$setting                      = array(
			'name'        => 'dataAcfGroup',
			'type'        => 'string',
			'default'     => '',
			'inputType'   => 'Select',
			'label'       => __( 'Group / Parent', 'search-filter' ),
			'placeholder' => __( 'Choose Group', 'search-filter' ),
			'group'       => 'data',
			'tab'         => 'settings',
			'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/search', 'block/field/search' ),
			'options'     => array(),
			'isDataType'  => true,
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'compare' => '=',
						'value'   => 'acf_field',
					),
				),
			),
			'store'       => array(
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
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
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
			'name'        => 'dataAcfField',
			'type'        => 'string',
			'default'     => '',
			'inputType'   => 'Select',
			'label'       => __( 'Field', 'search-filter' ),
			'placeholder' => __( 'Choose Field', 'search-filter' ),
			'group'       => 'data',
			'tab'         => 'settings',
			'context'     => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/search', 'block/field/search' ),
			'options'     => array(),
			'isDataType'  => true,
			'dependsOn'   => array(
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
			'store'       => array(
				'route' => '/settings/options/acf-fields',
				'args'  => array(
					'queryId',
					'dataAcfGroup',
					'type',
				),
			),
			'supports'    => array(
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
	 * Get the field data support for the ACF integration.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $data_support    The data support.
	 * @param string $type            The type of the field.
	 * @param string $input_type      The input type of the field.
	 * @return array    The updated data support.
	 */
	public static function get_field_data_support( $data_support, $type, $input_type ) {
		$supported_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
			'search' => array( 'text', 'autocomplete' ),
			'range'  => array( 'select', 'slider', 'number', 'radio' ),
		);

		if ( ! isset( $supported_matrix[ $type ] ) ) {
			return $data_support;
		}

		if ( ! in_array( $input_type, $supported_matrix[ $type ], true ) ) {
			return $data_support;
		}

		$data_support[] = array(
			'dataType' => 'acf_field',
		);

		return $data_support;
	}

	/**
	 * Get the ACF groups.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request    The request object.
	 * @return array
	 */
	public static function get_rest_acf_groups_options( \WP_REST_Request $request ) {
		if ( ! self::acf_enabled() ) {
			return;
		}
		if ( ! function_exists( '\acf_get_field_groups' ) ) {
			return;
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
		$depth++;

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
	 * @return array    The options for acf-fields by group.
	 */
	public static function get_acf_fields_options( \WP_REST_Request $request ) {

		$query_id   = $request->get_param( 'queryId' );
		$group_key  = $request->get_param( 'dataAcfGroup' );
		$field_type = $request->get_param( 'type' );

		if ( ! self::acf_enabled() ) {
			return;
		}

		$is_parent_group = strpos( $group_key, 'group_' ) === 0;

		$options = array();

		if ( ! function_exists( '\acf_get_fields' ) ) {
			return;
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
		$query = Query::find( array( 'id' => $query_id ) );
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

		if ( ! $field_key || $field_key === '' ) {
			return $suggestions;
		}

		$field_meta_key = self::generate_field_key( $field_key );

		// Now do a WP DB query on the post meta table for the field meta key using the search term to partially match the beginning of the value.
		global $wpdb;

		$results = array();
		if ( self::field_key_has_repeater_parent( $field_key ) ) {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key LIKE %s AND meta_value LIKE %s", $field_meta_key, $search_term . '%' ) );
		} else {
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s", $field_meta_key, $search_term . '%' ) );
		}

		$new_suggestions = array();

		foreach ( $results as $result ) {
			$new_suggestions[] = $result->meta_value;
		}
		return $new_suggestions;
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

		if ( ! $field_key || $field_key === '' ) {
			return $query_args;
		}

		$search_term = $field->get_value();
		if ( $search_term === '' ) {
			return $query_args;
		}

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
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

		if ( ! $field_key || $field_key === '' ) {
			return $query_args;
		}

		$values = $field->get_values();
		if ( empty( $values ) ) {
			return $query_args;
		}

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
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
			$query_args['meta_query'] = array();
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

		if ( ! $field_key || $field_key === '' ) {
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
			$query_args['meta_query'] = array();
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$custom_field_key = self::generate_field_key( $field_key );
		$decimal_places   = $field->get_attribute( 'rangeDecimalPlaces' );

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
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
	 * Generate the meta key for the field.
	 *
	 * @since 3.0.0
	 *
	 * @param string $field_key    The field key.
	 * @return array    The meta key.
	 */
	public static function generate_field_key( $field_key ) {
		$field = \acf_maybe_get_field( $field_key );

		if ( empty( $field ) || ! isset( $field['parent'], $field['name'] ) ) {
			return $field;
		}

		$ancestors = array();
		while ( ! empty( $field['parent'] ) && ! in_array( $field['name'], $ancestors ) ) {
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
		$query = Query::find( array( 'id' => $query_id ) );
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
		if ( ! $field_key || $field_key === '' ) {
			return $options;
		}

		$acf_field = \acf_maybe_get_field( $field_key );

		if ( empty( $acf_field ) ) {
			return $options;
		}

		if ( isset( $acf_field['choices'] ) ) {
			$options = array();

			// Sort according to the order direction.
			$acf_choices     = $acf_field['choices'];
			$order           = $field->get_attribute( 'inputOptionsOrder' );
			$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';

			$acf_choices = Util::sort_assoc_array( $acf_choices, $order, $order_direction );

			foreach ( $acf_choices as $key => $value ) {
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $key,
						'label' => $value,
					),
					$field->get_id()
				);
			}
			return $options;
		}

		if ( $acf_field['type'] === 'post_object' || $acf_field['type'] === 'relationship' ) {
			$options = self::get_relationship_options( $field, $acf_field );
			return $options;
		}

		// If the field didn't have choices and it wasn't a relationship, lets assume its a
		// single value string (ie text input).
		$options = self::get_single_value_options( $field, $acf_field );
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
	public static function get_relationship_options( $field, $acf_field ) {

		$options         = array();
		$order           = $field->get_attribute( 'inputOptionsOrder' );
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';

		if ( self::field_uses_indexer( $field ) && $field->get_id() !== 0 ) {

			$cache_key = Fields::get_field_options_cache_key( $field );

			$cached_field_posts = wp_cache_get( $cache_key, 'search-filter-pro' );

			if ( $cached_field_posts === false ) {
				$query = new Index_Query(
					array(
						'fields'   => 'value',
						'groupby'  => 'value',
						'field_id' => $field->get_id(),
						'number'   => 0,
					)
				);

				if ( is_wp_error( $query ) ) {
					return $options;
				}
				$ids = $query->items;

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

			foreach ( $cached_field_posts as $post ) {
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

				$query = new Index_Query(
					array(
						'fields'   => 'value',
						'groupby'  => 'value',
						'field_id' => $field->get_id(),
						'number'   => 0,
					)
				);

			if ( is_wp_error( $query ) ) {
				return $options;
			}

			$values = Util::sort_array( $query->items, $order, $order_direction );

			foreach ( $values as $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				Choice::add_option_to_array(
					$options,
					array(
						'value' => $value,
						'label' => $value,
					),
					$field->get_id()
				);
			}

			return $options;
		}

		// Otherwise lookup in the meta query table.
		$cache_key                = Fields::get_field_options_cache_key( $field );
		$cached_field_meta_values = wp_cache_get( $cache_key, 'search-filter-pro' );

		if ( $cached_field_meta_values === false ) {
			$field_key = $acf_field['key'];
			global $wpdb;
			$options          = array();
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
					'value' => $v->meta_value,
					'label' => $v->meta_value,
				),
				$field->get_id()
			);
		}

		return $options;
	}

	/**
	 * Override the index values and add ACF values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $values    The values to index.
	 * @param    Field $field    The field to get the values for.
	 * @param    int   $object_id    The object ID to get the values for.
	 * @return   array    The values to index.
	 */
	public static function index_values( $values, $field, $object_id ) {
		if ( $field->get_attribute( 'dataType' ) !== 'acf_field' ) {
			return $values;
		}

		$field_key = $field->get_attribute( 'dataAcfField' );
		$values    = self::get_post_field_values( $field_key, $object_id );
		return $values;
	}

	/**
	 * Get the field values for a post.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $field_key    The ACF field key.
	 * @param    int    $object_id    The object ID.
	 * @return   array    The field values.
	 */
	public static function get_post_field_values( $field_key, $object_id ) {

		$acf_field = \acf_get_field( $field_key );

		if ( empty( $acf_field ) ) {
			return array();
		}

		// Track the order of the field names in the hierarchy.
		$field_keys_hierarchy = array();
		// Traverse until we get to the top level parent field.
		$parent_field     = $acf_field;
		$top_parent_field = $acf_field;
		while ( isset( $parent_field['parent'] ) && ! empty( $parent_field['parent'] ) ) {
			$field_keys_hierarchy[] = $parent_field['key'];
			// Now move up the hierarchy to the parent.
			$parent_field = \acf_get_field( $parent_field['parent'] );
			// The top parent field should return false as it would be the to level field group.
			if ( $parent_field ) {
				$top_parent_field = $parent_field;
			}
		}

		$field_keys_hierarchy = array_reverse( $field_keys_hierarchy );

		$values = array();
		// We need to check if the to level field is a repeater.  If so, we can get the values from it directly.
		if ( $top_parent_field['key'] === $field_key ) {
			// Then there is no nesting, so we can just get the values from the field directly.
			$field_values = \get_field( $field_key, $object_id, false, false );
			$values       = self::normalise_index_field_values( $field_values );
		} elseif ( $top_parent_field['type'] === 'repeater' ) {
			// Then we have a repeater, so get the rows/values, and plant to iterate through them.
			// Need to make a recursive function to keep iterationg through sub repeaters until we find the field we want.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = self::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'group' ) {
			// Then we have a repeater, so get the rows/values, and plant to iterate through them.
			// Need to make a recursive function to keep iterationg through sub repeaters until we find the field we want.
			$values = \get_field( $top_parent_field['name'], $object_id, false, false );

			if ( $values ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_group_values( $field_keys_hierarchy, $values );
				$values       = self::normalise_index_field_values( $field_values );
			}
		} elseif ( $top_parent_field['type'] === 'flexible_content' ) {
			// Then we have a repeater, so get the rows/values, and plant to iterate through them.
			// Need to make a recursive function to keep iterationg through sub repeaters until we find the field we want.
			$rows = \get_field( $top_parent_field['name'], $object_id, false, false );
			if ( $rows ) {
				// The first field name in the hierarchy will be this one, so remove it.
				array_shift( $field_keys_hierarchy );
				$field_values = self::get_nested_content_values( $field_keys_hierarchy, $rows );
				$values       = self::normalise_index_field_values( $field_values );
			}
		}

		$values = self::parse_field_values( $values, $acf_field );

		return array_unique( $values );
	}

	/**
	 * Normalise the index field values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $field_values    The field values.
	 * @return   array    The normalised field values.
	 */
	public static function normalise_index_field_values( $field_values ) {
		$values = array();

		// ACF returns an empty string when there are no values for many field types.
		if ( empty( $field_values ) ) {
			return $values;
		}
		if ( ! is_array( $field_values ) ) {
			$values[] = $field_values;
		} else {
			$values = $field_values;
		}
		return $values;
	}
	/**
	 * Parse the index field values.
	 *
	 * Some data types need to be parsed into a format that can be indexed.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $field_values    The field values.
	 * @return   array    The normalised field values.
	 */
	public static function parse_field_values( $field_values, $acf_field ) {

		if ( empty( $field_values ) ) {
			return $field_values;
		}

		if ( $acf_field['type'] === 'date_time_picker' ) {
			$parsed_values = array();
			foreach ( $field_values as $value ) {
				// Convert a date time value like `2024-10-08 21:00:00` to date only, like `20241008`
				// making sure to remove the time.
				$date            = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value );
				$parsed_values[] = $date->format( 'Ymd' );
			}
			return $parsed_values;
		}

		return $field_values;
	}

	/**
	 * Get the nested content values.
	 *
	 * Works with repeaters and flexible content types.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $hierarchy    The hierarchy.
	 * @param    array $field_rows    The field rows.
	 * @return   array    The nested repeater values.
	 */
	public static function get_nested_content_values( $hierarchy, $field_rows ) {
		$values            = array();
		$current_hierarchy = array_shift( $hierarchy );
		foreach ( $field_rows as $field_row ) {
			// Remove first element from hierarchy.
			if ( isset( $field_row[ $current_hierarchy ] ) ) {
				// Then we have a match, so recurse.

				// If there is no more hierarchies left, then we are on the final row, so return the value.
				if ( count( $hierarchy ) === 0 ) {
					// Then we are on the final row, so return the value.
					if ( is_array( $field_row[ $current_hierarchy ] ) ) {
						$values = array_merge( $values, $field_row[ $current_hierarchy ] );
					} else {
						$values[] = $field_row[ $current_hierarchy ];
					}
				} else {
					$values = array_merge( $values, self::get_nested_content_values( $hierarchy, $field_row[ $current_hierarchy ] ) );
				}
			}
		}
		return $values;
	}

	/**
	 * Get the nested group values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $hierarchy    The hierarchy.
	 * @param    array $field_values    The field values.
	 * @return   array    The nested group values.
	 */
	public static function get_nested_group_values( $hierarchy, $field_values ) {
		$values            = array();
		$current_hierarchy = array_shift( $hierarchy );
		// Remove first element from hierarchy.
		if ( isset( $field_values[ $current_hierarchy ] ) ) {
			// Then we have a match, so recurse.
			// If there is no more hierarchies left, then we are on the final value, so return it.
			if ( count( $hierarchy ) === 0 ) {
				// Then we are on the final row, so return the value.
				if ( is_array( $field_values[ $current_hierarchy ] ) ) {
					$values = array_merge( $values, $field_values[ $current_hierarchy ] );
				} else {
					$values[] = $field_values[ $current_hierarchy ];
				}
			} else {
				$values = array_merge( $values, self::get_nested_group_values( $hierarchy, $field_values[ $current_hierarchy ] ) );
			}
		}
		return $values;
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

		if ( ! $field_key || $field_key === '' ) {
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
	 * @param Field  $field    The field record
	 * @return string    The custom field key.
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