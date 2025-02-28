<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\Data_Store;
use Search_Filter\Core\WP_Data;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Database\Queries\Fields as Fields_Query;
use Search_Filter\Fields\Choice;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Indexer\Async;
use Search_Filter_Pro\Indexer\Query_Cache;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Fields {

	/**
	 * The block attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $block_attributes = array(
		'search'   => array(),
		'choice'   => array(),
		'range'    => array(),
		'advanced' => array(),
		'control'  => array(),
	);

	/**
	 * The indexable record statuses.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $indexable_stati = array(
		'enabled',
		'disabled',
	);

	/**
	 * The field defaults.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $field_defaults = array(
		'search'   => array(
			'text'         => array(
				'autoSubmitDelay' => '600',
			),
			'autocomplete' => array(
				'autoSubmitDelay' => '1500',
			),
		),
		'choice'   => array(
			'select'   => array(
				'autoSubmitDelay' => '0',
			),
			'radio'    => array(
				'autoSubmitDelay' => '0',
			),
			'checkbox' => array(
				'autoSubmitDelay' => '0',
			),
			'button'   => array(
				'autoSubmitDelay' => '0',
			),
		),
		'range'    => array(
			'slider' => array(
				'autoSubmitDelay' => '400',
			),
			'radio'  => array(
				'autoSubmitDelay' => '0',
			),
			'select' => array(
				'autoSubmitDelay' => '0',
			),

		),
		'advanced' => array(
			'date_picker' => array(
				'autoSubmitDelay' => '0',
			),
		),
		'control'  => array(),
	);

	/**
	 * The query tracking for defaults.
	 *
	 * Before we update the query values based on defaults, we need to track
	 * the query ID to make sure it matches.
	 * 
	 * @var integer
	 */
	private static $defaults_query_tracking = 0;


	/**
	 * All field types.
	 *
	 * TODO - this needs to be dynamic.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $all_field_types = array( 'search', 'choice', 'range', 'advanced', 'control' );

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		add_action( 'search-filter/fields/register', array( __CLASS__, 'register_fields' ), 10 );

		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'register_custom_field_settings' ), 10 );
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'upgrade_sort_field' ), 10 );

		add_filter( 'search-filter/field/choice/options_data', array( __CLASS__, 'add_custom_field_options_data' ), 10, 2 );

		add_filter( 'search-filter/field/url_name', array( __CLASS__, 'add_custom_field_url_name' ), 10, 2 );
		add_filter( 'search-filter/field/choice/wp_query_args', array( __CLASS__, 'get_custom_field_choice_wp_query_args' ), 10, 2 );
		add_filter( 'search-filter/field/range/auto_detect_custom_field', array( __CLASS__, 'range_auto_detect_custom_field' ), 10, 2 );
		add_filter( 'search-filter/field/advanced/wp_query_args', array( __CLASS__, 'get_custom_field_advanced_wp_query_args' ), 10, 2 );

		// Enable the various input types for when a custom field is selected.
		add_filter( 'search-filter/field/get_data_support', array( __CLASS__, 'get_field_custom_field_data_support' ), 10, 3 );
		add_filter( 'search-filter/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );
		// Update a fields registered icons.
		add_filter( 'search-filter/fields/field/get_icons', array( __CLASS__, 'add_field_icons' ), 10, 2 );

		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_data_type_to_search' ), 10 );
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_context_to_data_types' ), 10 );
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'register_field_settings' ), 10 );

		// Handle indexer queries.
		add_filter( 'search-filter-pro/indexer/query/init/start', array( __CLASS__, 'apply_defaults_to_query' ), 10, 1 );
		add_filter( 'search-filter-pro/indexer/query/init/finish', array( __CLASS__, 'remove_defaults_from_query' ), 10, 1 );
		// Handle wp_query queries.
		add_filter( 'search-filter/query/apply_query/start', array( __CLASS__, 'apply_defaults_to_query' ), 10, 1 );
		add_filter( 'search-filter/query/apply_query/finish', array( __CLASS__, 'remove_defaults_from_query' ), 10, 1 );
		// Add default values to the fields.
		add_filter( 'search-filter/fields/field/render_data', array( __CLASS__, 'add_default_values' ), 20, 2 );

		
		add_action( 'search-filter/settings/init', array( __CLASS__, 'register_url_arg_setting' ), 20 );
		add_filter( 'search-filter/field/url_name', array( __CLASS__, 'add_url_arg_name' ), 20, 2 );
		add_filter( 'search-filter/fields/field/render/html_classes', array( __CLASS__, 'add_html_render_classes' ), 20, 2 );
		add_filter( 'search-filter/fields/field/render/html_attributes', array( __CLASS__, 'add_html_render_attributes' ), 20, 2 );
		add_filter( 'search-filter/fields/field/render_data', array( __CLASS__, 'apply_hidden_field_attributes' ), 20, 2 );

		// Author attributes and settings.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_author_attributes_and_settings' ), 10 );
		// Add filtering by author to the WP Query.
		add_filter( 'search-filter/field/choice/wp_query_args', array( __CLASS__, 'get_author_choice_wp_query_args' ), 10, 2 );
		// Add options to choice fields for authors.
		add_filter( 'search-filter/field/choice/options_data', array( __CLASS__, 'add_author_options_data' ), 10, 2 );

		// Add post attributes for the search field.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_default_data_attribute_type' ), 10 );

		// Add block editor attributes for settings.
		add_action( 'search-filter/integrations/gutenberg/add_attributes', '\\Search_Filter_Pro\\Fields::add_block_attributes', 10 );

		// Register the default values for the field/input type combinations.
		add_filter( 'search-filter/fields/field/get_attributes', array( __CLASS__, 'get_attributes' ), 1, 2 );

		// Check if a fields data has updated, and if we need to resync indexer the data.
		add_action( 'search-filter/record/pre_save', array( __CLASS__, 'field_check_for_indexer_changes' ), 10, 2 );
		// We can't use pre_save for new fields because there is no ID yet, so check the save action to see if there is new indexer data.
		add_action( 'search-filter/record/save', array( __CLASS__, 'field_check_for_new_indexer_data' ), 10, 3 );
		// Remove the indexer data for a field on record delete.
		add_action( 'search-filter/record/pre_destroy', array( __CLASS__, 'field_remove_indexer_data' ), 10, 2 );

	}

	/**
	 * Get the field data support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $data_support    The data support to get the data support for.
	 * @param    string $type    The type to get the data support for.
	 * @param    string $input_type    The input type to get the data support for.
	 * @return   array    The data support.
	 */
	public static function get_field_custom_field_data_support( $data_support, $type, $input_type ) {
		$supported_matrix = array(
			'choice'   => array( 'select', 'radio', 'checkbox', 'button' ),
			'search'   => array( 'text', 'autocomplete' ),
			'range'    => array( 'select', 'slider', 'number', 'radio' ),
			'advanced' => array( 'date_picker' ),
		);

		if ( ! isset( $supported_matrix[ $type ] ) ) {
			return $data_support;
		}

		if ( ! in_array( $input_type, $supported_matrix[ $type ], true ) ) {
			return $data_support;
		}

		$data_support[] = array(
			'dataType' => 'custom_field',
		);

		return $data_support;
	}

	/**
	 * Get the field setting support.
	 *
	 * @since 3.0.0
	 *
	 * @param    array  $setting_support    The setting support to get the setting support for.
	 * @param    string $type    The type to get the setting support for.
	 * @param    string $input_type    The input type to get the setting support for.
	 * @return   array    The setting support.
	 */
	public static function get_field_setting_support( $setting_support, $type, $input_type ) {

		// Enable toggle visibility, and initial visibility for existing fields.
		$label_toggle_supported_matrix = array(
			'choice'   => array( 'select', 'radio', 'checkbox', 'button' ),
			'search'   => array( 'text' ),
			'control'  => array( 'sort' ),
			'advanced' => array( 'date_picker' ),
		);

		if ( isset( $label_toggle_supported_matrix[ $type ] ) && in_array( $input_type, $label_toggle_supported_matrix[ $type ], true ) ) {
			$setting_support['labelInitialVisibility'] = true;
			$setting_support['labelToggleVisibility']  = true;
			$setting_support['autoSubmit']             = true;
			$setting_support['autoSubmitDelay']        = true;
		}

		// Add show count + hide empty to choice fields, for indexed queries.
		$count_supported_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
		);
		if ( isset( $count_supported_matrix[ $type ] ) && in_array( $input_type, $count_supported_matrix[ $type ], true ) ) {
			$indexed_fields_conditions = array(
				'store'   => 'query',
				'option'  => 'useIndexer',
				'compare' => '=',
				'value'   => 'yes',
			);

			$setting_support['showCount'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'showCount', $indexed_fields_conditions, false ),
			);
			$setting_support['hideEmpty'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'hideEmpty', $indexed_fields_conditions, false ),
			);
		}

		return $setting_support;
	}

	/**
	 * Update field icons to add support when using `labelToggleVisibility`
	 */
	public static function add_field_icons( $icons, $field ) {

		if ( $field->get_attribute( 'labelToggleVisibility' ) === 'yes' ) {
			$icons[] = 'arrow-down';
		}

		return $icons;
	}

	/**
	 * Filter the field get_attributes() to add in defaults.
	 *
	 * @since 3.0.0
	 *
	 * @param    array                       $attributes    The attributes to set.
	 * @param    \Search_Filter\Fields\Field $field    The field to set the attributes for.
	 *
	 * @return   array    The updated attributes.
	 */
	public static function get_attributes( $attributes, $field ) {
		$field_type       = $field->get_attribute( 'type', true );
		$field_input_type = $field->get_attribute( 'inputType', true );

		if ( ! isset( self::$field_defaults[ $field_type ] ) ) {
			return $attributes;
		}
		if ( ! isset( self::$field_defaults[ $field_type ][ $field_input_type ] ) ) {
			return $attributes;
		}

		$new_attributes = self::$field_defaults[ $field_type ][ $field_input_type ];

		foreach ( $new_attributes as $key => $value ) {
			if ( ! isset( $attributes[ $key ] ) || $attributes[ $key ] === '' ) {
				$attributes[ $key ] = $value;
			}
		}
		return $attributes;
	}

	
	/**
	 * Add the data type to the search field.
	 *
	 * @since 3.0.0
	 */
	public static function add_data_type_to_search() {
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );
		$setting_data      = $data_type_setting->get_data();

		if ( ! isset( $setting_data['context'] ) ) {
			return;
		}

		$setting_data['context'][] = 'block/field/search';
		$setting_data['context'][] = 'admin/field/search';

		$data_type_setting->update( $setting_data );
	}

	/**
	 * Add the author attributes and settings.
	 *
	 * @since 3.0.0
	 */
	public static function add_author_attributes_and_settings() {

		// Add the author option to the dataPostAttribute setting.
		$post_attribute_setting = Fields_Settings::get_setting( 'dataPostAttribute' );

		// but only for search fields.
		$author_option = array(
			'label'     => __( 'Post Author', 'search-filter' ),
			'value'     => 'post_author',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				// TODO - remove the rule, so we can support this in search fields.
				'rules'    => array(
					array(
						'option'  => 'type',
						'value'   => 'choice',
						'compare' => '=',
					),
				),
			),
		);
		$post_attribute_setting->add_option( $author_option, array( 'position' => 'last' ) );

		// Add addition author settings.
		$settings = array(
			array(
				'name'      => 'dataPostAuthorConditions',
				'label'     => __( 'Author conditions', 'search-filter' ),
				'type'      => 'string',
				'inputType' => 'Select',
				'group'     => 'data',
				'tab'       => 'settings',
				'default'   => 'all',
				'context'   => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'options'   => array(
					array(
						'label' => __( 'All authors', 'search-filter' ),
						'value' => 'all',
					),
					array(
						'label' => __( 'Restrict to specific authors', 'search-filter' ),
						'value' => 'restrict_by_authors',
					),
					array(
						'label' => __( 'Exclude specific authors', 'search-filter' ),
						'value' => 'exclude_by_authors',
					),
					array(
						'label' => __( 'Restrict by roles', 'search-filter' ),
						'value' => 'restrict_by_roles',
					),
					array(
						'label' => __( 'Restrict by capabilities', 'search-filter' ),
						'value' => 'restrict_by_capabilities',
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_author',
						),
					),
				),
				'supports'  => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'         => 'dataPostAuthors',
				'label'        => __( 'Post Authors', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'number',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array(),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_author',
						),
						array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'dataPostAuthorConditions',
									'compare' => '=',
									'value'   => 'restrict_by_authors',
								),
								array(
									'option'  => 'dataPostAuthorConditions',
									'compare' => '=',
									'value'   => 'exclude_by_authors',
								),
							),
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/post-authors',
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'         => 'dataPostAuthorRoles',
				'label'        => __( 'Author roles', 'search-filter' ),
				'placeholder'  => __( 'All roles', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array(),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_author',
						),
						array(
							'option'  => 'dataPostAuthorConditions',
							'compare' => '=',
							'value'   => 'restrict_by_roles',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/post-author-roles',
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
			array(
				'name'         => 'dataPostAuthorCapabilities',
				'label'        => __( 'Author capabilities', 'search-filter' ),
				'placeholder'  => __( 'All capabilities', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'inputType'    => 'MultiSelect',
				'group'        => 'data',
				'tab'          => 'settings',
				'options'      => array(),
				'default'      => array(),
				'context'      => array( 'admin/field', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'dataType',
							'compare' => '=',
							'value'   => 'post_attribute',
						),
						array(
							'option'  => 'dataPostAttribute',
							'compare' => '=',
							'value'   => 'post_author',
						),
						array(
							'option'  => 'dataPostAuthorConditions',
							'compare' => '=',
							'value'   => 'restrict_by_capabilities',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/post-author-capabilities',
				),
				'supports'     => array(
					'previewAPI' => true,
				),
			),
		);

		foreach ( $settings as $setting ) {
			$add_setting_args = array(
				'extend_block_types' => self::$all_field_types,
			);
			Fields_Settings::add_setting( $setting, $add_setting_args );
		}

	}

	/**
	 * Add options to the author field.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $options_data    The options to add to.
	 * @param    Field $field      The field to get the options for.
	 * @return   array    The options to add to.
	 */
	public static function add_author_options_data( $options_data, $field ) {
		if ( count( $options_data['options'] ) > 0 ) {
			return $options_data;
		}
		if ( $field->get_attribute( 'dataType' ) !== 'post_attribute' ) {
			return $options_data;
		}
		if ( $field->get_attribute( 'dataPostAttribute' ) !== 'post_author' ) {
			return $options_data;
		}

		// Post stati are generic (not assigned to post types etc), so get them all.
		$query_id                      = $field->get_attribute( 'queryId' );
		$data_post_author_conditions   = $field->get_attribute( 'dataPostAuthorConditions' );
		$data_post_authors             = $field->get_attribute( 'dataPostAuthors' );
		$data_post_author_roles        = $field->get_attribute( 'dataPostAuthorRoles' );
		$data_post_author_capabilities = $field->get_attribute( 'dataPostAuthorCapabilities' );

		$authors_args = array();

		// The the post types from the query.
		if ( ! empty( $query_id ) ) {
			$query = Query::find( array( 'id' => $query_id ) );
			if ( ! is_wp_error( $query ) ) {
				$authors_args['has_published_posts'] = $query->get_attribute( 'postTypes' );
			}
		}

		if ( ! empty( $data_post_author_conditions ) ) {
			if ( $data_post_author_conditions === 'restrict_by_authors' ) {
				$authors_args['include'] = array_map( 'intval', $data_post_authors );
			} elseif ( $data_post_author_conditions === 'exclude_by_authors' ) {
				$authors_args['exclude'] = array_map( 'intval', $data_post_authors );
			} elseif ( $data_post_author_conditions === 'restrict_by_roles' ) {
				$authors_args['role__in'] = $data_post_author_roles;
			} elseif ( $data_post_author_conditions === 'restrict_by_capabilities' ) {
				$authors_args['capability__in'] = $data_post_author_capabilities;
			}
		}
		$post_authors = WP_Data::get_post_authors( $authors_args );

		// Sort according to the order direction.
		$order_direction = $field->get_attribute( 'inputOptionsOrderDir' ) ? $field->get_attribute( 'inputOptionsOrderDir' ) : 'asc';
		usort(
			$post_authors,
			function( $a, $b ) use ( $order_direction ) {
				if ( $order_direction === 'asc' ) {
					return strcmp( $a->display_name, $b->display_name );
				} else {
					return strcmp( $b->display_name, $a->display_name );
				}
			}
		);

		foreach ( $post_authors as $post_author ) {
			$item               = array();
			$item['indexValue'] = $post_author->ID;
			$item['value']      = $post_author->user_nicename;
			$item['label']      = $post_author->display_name;

			Choice::add_option_to_array( $options_data['options'], $item, $field->get_id() );
			$options_data['labels'][ $post_author->user_nicename ] = $post_author->display_name;
		}
		return $options_data;
	}

	/**
	 * Add filtering by author to the WP Query.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The query args to add to.
	 * @param    Field $field         The field to get the args for.
	 * @return   array    The query args to add to.
	 */
	public static function get_author_choice_wp_query_args( $query_args, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'post_attribute' ) {
			return $query_args;
		}
		if ( $field->get_attribute( 'dataPostAttribute' ) !== 'post_author' ) {
			return $query_args;
		}

		$values = $field->get_values();

		// Get author IDs from the values which are author nicenames.
		$author_ids = Util::get_author_ids_from_slugs( $values );

		if ( empty( $author_ids ) ) {
			return $query_args;
		}

		$query_args['author__in'] = $author_ids;
		return $query_args;
	}
	public static function add_default_data_attribute_type() {

		$post_attribute_setting = Fields_Settings::get_setting( 'dataPostAttribute' );
		$setting_data           = $post_attribute_setting->get_data();

		// Enable dependant options for the "dataPostAttribute" setting.
		if ( ! isset( $setting_data['supports'] ) ) {
			$setting_data['supports'] = array();
		}
		$setting_data['supports']['dependantOptions'] = true;
		$post_attribute_setting->update( $setting_data );

		// Allow for "default" setting (usually post title and post content)
		// but only for search fields.
		$default_option = array(
			'label'     => __( 'Post Title + Content', 'search-filter' ),
			'value'     => 'default',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'value'   => 'search',
						'compare' => '=',
					),
				),
			),
		);
		$post_attribute_setting->add_option( $default_option, array( 'position' => 'first' ) );
	}

	/**
	 * Add the search context to the data type settings.
	 *
	 * @since 3.0.0
	 */
	public static function add_context_to_data_types() {
		$data_field_names = array( 'dataPostAttribute', 'dataTaxonomy' );
		foreach ( $data_field_names as $data_field_name ) {
			self::add_search_context_to_field_setting( $data_field_name );
		}
	}

	/**
	 * Add the search context to a field setting.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $setting_name    The name of the setting to add the context to.
	 */
	public static function add_search_context_to_field_setting( $setting_name ) {
		$field_setting = Fields_Settings::get_setting( $setting_name );

		if ( ! $field_setting ) {
			return;
		}

		$setting_data = $field_setting->get_data();
		if ( ! isset( $setting_data['context'] ) ) {
			return;
		}

		$setting_data['context'][] = 'block/field/search';
		$setting_data['context'][] = 'admin/field/search';
		$field_setting->update( $setting_data );
	}

	/**
	 * Add a block attribute.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $name    The name of the attribute.
	 * @param    array  $attribute    The attribute to add.
	 * @param    string $block_type    The block type to add the attribute to.
	 */
	public static function add_block_attribute( $name, $attribute, $block_type = 'all' ) {
		if ( $block_type === 'all' ) {
			foreach ( self::$all_field_types as $type ) {
				self::$block_attributes[ $type ][ $name ] = $attribute;
			}
		} else {
			self::$block_attributes[ $block_type ][ $name ] = $attribute;
		}
	}

	/**
	 * Add block attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $attributes    The attributes to add.
	 */
	public static function add_block_attributes( $attributes ) {

		// Add dataType to search.
		self::add_block_attribute(
			'dataType',
			array(
				'type'    => 'string',
				'default' => '',
			),
			'search'
		);

		// Add dataPostAttribute to search.
		self::add_block_attribute(
			'dataPostAttribute',
			array(
				'type'    => 'string',
				'default' => '',
			),
			'search'
		);

		// Add dataTaxonomy to search.
		self::add_block_attribute(
			'dataTaxonomy',
			array(
				'type' => 'string',
			),
			'search'
		);

		foreach ( self::$block_attributes as $field_type => $field_attributes ) {
			foreach ( $field_attributes as $attribute_name => $attribute ) {
				$attributes[ $field_type ][ $attribute_name ] = $attribute;
			}
		}

		return $attributes;
	}

	/**
	 * Register the fields.
	 *
	 * @since 3.0.0
	 */
	public static function register_fields() {
		// Register Pro fields.

		Field_Factory::register_field_input( 'search', 'autocomplete', 'Search_Filter_Pro\Fields\Search\Autocomplete' );
		Field_Factory::register_field_input( 'control', 'selection', 'Search_Filter_Pro\Fields\Control\Selection' );
		Field_Factory::register_field_input( 'control', 'load_more', 'Search_Filter_Pro\Fields\Control\Load_More' );

		// Replace the text search field to add pro features.
		Field_Factory::update_field_input( 'search', 'text', 'Search_Filter_Pro\Fields\Search\Text' );

		Field_Factory::register_field_input( 'range', 'slider', 'Search_Filter_Pro\Fields\Range\Slider' );
		Field_Factory::register_field_input( 'range', 'select', 'Search_Filter_Pro\Fields\Range\Select' );
		Field_Factory::register_field_input( 'range', 'radio', 'Search_Filter_Pro\Fields\Range\Radio' );
		Field_Factory::register_field_input( 'range', 'number', 'Search_Filter_Pro\Fields\Range\Number' );
		Field_Factory::update_field_input( 'advanced', 'date_picker', 'Search_Filter_Pro\Fields\Advanced\Date_Picker' );

	}

	/**
	 * Register the pro field settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_field_settings() {
		$group_args = array(
			'position' => array(
				'placement' => 'before',
				'group'     => 'advanced',
			),
		);
		
		$add_setting_args = array(
			'extend_block_types' => self::$all_field_types,
		);

		$setting = array(
			'name'      => 'autoSubmit',
			'label'     => __( 'Auto submit', 'search-filter' ),
			'help'      => __( 'Automatically submit after interacting.', 'search-filter' ),
			'default'   => 'yes',
			'offValue'  => 'no',
			'group'     => 'behaviour',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'autoSubmitDelay',
			'label'       => __( 'Auto submit delay', 'search-filter' ),
			'placeholder' => __( 'Leave empty for default.', 'search-filter' ),
			'help'        => __( 'Delay in milliseconds before auto submit.', 'search-filter' ),
			'group'       => 'behaviour',
			'tab'         => 'settings',
			'type'        => 'string',
			// Important - default must be an empty string '' so it will be overriden, but if not set
			// it was cause react to throw an error related to controlled/uncontrolled inputs.
			'default'     => '',
			'inputType'   => 'Number',
			'min'         => 0,
			'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			'dependsOn'   => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'autoSubmit',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'hideFieldWhenEmpty',
			'label'     => __( 'Hide field when empty', 'search-filter' ),
			'help'      => __( 'Hides the field when it has no options available.', 'search-filter' ),
			'group'     => 'input',
			'tab'       => 'settings',
			'type'      => 'string',
			// Important - default must be an empty string '' so it will be overriden, but if not set
			// it was cause react to throw an error related to controlled/uncontrolled inputs.
			'default'   => 'no',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'value'   => 'choice',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		/*
		 Fields_Settings::add_group(
			array(
				'name'  => 'conditions',
				'label' => __( 'Visibility conditions', 'search-filter-pro' ),
			),
			$group_args
		);

		$setting = array(
			'name'      => 'conditions',
			'label'     => __( 'Conditions', 'search-filter' ),
			'help'      => __( 'Choose which conditions need to be valid to display the field.', 'search-filter' ),
			'group'     => 'conditions',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'FieldConditions',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );
 		*/

		Fields_Settings::add_group(
			array(
				'name'  => 'default',
				'label' => __(
					'Default value',
					'search-filter-pro'
				),
			),
			$group_args
		);
		
		$setting = array(
			'name'      => 'defaultValueType',
			'label'     => __( 'Default value', 'search-filter' ),
			'help'      => __( 'Enter a default value for this field.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Select',
			'options'   => array(
				array(
					'value' => 'none',
					'label' => __( 'None', 'search-filter' ),
				),
				array(
					'value' => 'inherit',
					'label' => __( 'Inherit from current location', 'search-filter' ),
					'dependsOn' => array(
						'relation' => 'OR',
						'action'   => 'hide',
						'rules'    => array(
							array(
								'option'  => 'type',
								'value'   => 'search',
								'compare' => '=',
							),
							array(
								'option'  => 'type',
								'value'   => 'choice',
								'compare' => '=',
							),
						),
					),
				),
				array(
					'value' => 'custom',
					'label' => __( 'Custom', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'value'   => 'range',
						'compare' => '!=',
					),
					
				),
			),
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/advanced', 'block/field/advanced' ),
			'supports'  => array(
				'dependantOptions' => true,
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueInheritArchive',
			'label'     => __( 'Inherit from archives', 'search-filter' ),
			'help'      => __( 'Inherit the default value from an archive.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'default'   => 'yes',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueInheritPost',
			'label'     => __( 'Inherit from posts', 'search-filter' ),
			'help'      => __( 'Inherit the default value from single posts, pages or CPTs.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'default'   => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueCustom',
			'label'     => __( 'Custom default value', 'search-filter' ),
			'help'      => __( 'Enter a custom default value for this field.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Text',
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'custom',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'      => 'defaultValueApplyToQuery',
			'label'     => __( 'Initially apply to query', 'search-filter' ),
			'help'      => __( 'Pre-apply the default value to the query when first loading the page.', 'search-filter' ),
			'group'     => 'default',
			'tab'       => 'settings',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'context'   => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'dependsOn' => array(
				'relation' => 'OR',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'defaultValueType',
						'value'   => 'custom',
						'compare' => '=',
					),
					array(
						'option'  => 'defaultValueType',
						'value'   => 'inherit',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'labelToggleVisibility',
			'label'       => __( 'Toggle input visibility', 'search-filter-pro' ),
			'help'        => __( 'Click the label show/hide the input.', 'search-filter-pro' ),
			'group'       => 'label',
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Toggle',
			'default'     => 'no',
			'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter-pro' ),
			'options'     => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter-pro' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter-pro' ),
				),
			),
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'showLabel',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		$setting = array(
			'name'        => 'labelInitialVisibility',
			'label'       => __( 'Initial visibility', 'search-filter' ),
			'group'       => 'label',
			'tab'         => 'settings',
			'type'        => 'string',
			'inputType'   => 'Select',
			'default'     => 'no',
			'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter-pro' ),
			'options'     => array(
				array(
					'value' => 'visible',
					'label' => __( 'Visible', 'search-filter-pro' ),
				),
				array(
					'value' => 'hidden',
					'label' => __( 'Hidden', 'search-filter-pro' ),
				),
			),
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'showLabel',
						'compare' => '=',
						'value'   => 'yes',
					),
					array(
						'option'  => 'labelToggleVisibility',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

	}
	public static function register_url_arg_setting() {
		$setting = array(
			'name'        => 'dataUrlName',
			'label'       => __( 'URL Name', 'search-filter-pro' ),
			'help'        => __( 'Must only use characters a-z, underscores or hyphens.', 'search-filter-pro' ),
			'group'       => 'data',
			'tab'         => 'settings',
			'type'        => 'string',
			'default'     => '',
			'inputType'   => 'Text',
			'regex'       => '/[^0-9A-Za-z_/-]/gi',
			'context'     => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced', 'admin/field/control', 'block/field/control' ),
			'placeholder' => __( 'Leave blank to use default', 'search-filter' ),
			'dependsOn'   => array(
				'relation' => 'OR',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'type',
						'value'   => 'control',
						'compare' => '!=',
					),
					array(
						'relation' => 'AND',
						'action'   => 'hide',
						'rules'    => array(
							array(
								'option'  => 'type',
								'value'   => 'control',
								'compare' => '=',
							),
							array(
								'option'  => 'controlType',
								'value'   => 'sort',
								'compare' => '=',
							),
						),
					),
				),
			),
		);

		$add_setting_args = array(
			'extend_block_types' => self::$all_field_types,
		);
		Fields_Settings::add_setting( $setting, $add_setting_args );

	}

	/**
	 * Support custom url names for fields.
	 *
	 * @param string $url_name    The URL name to add.
	 * @param Field  $field       The field to add the URL name to.
	 * @return string    The URL name.
	 */
	public static function add_url_arg_name( $url_name, $field ) {
		$url_name_attribute = $field->get_attribute( 'dataUrlName' );
		if ( ! $url_name_attribute || $url_name_attribute === '' ) {
			return $url_name;
		}
		return $url_name_attribute;
	}

	/**
	 * Check if the field should be hidden.
	 *
	 * @param Field $field The field to check.
	 * @return boolean
	 */
	private static function should_hide_field( $field ) {
		if ( $field->get_attribute( 'type' ) !== 'choice' ) {
			return false;
		}

		$hide_field_when_empty_attribute = $field->get_attribute( 'hideFieldWhenEmpty' ) === 'yes';
		if ( ! $hide_field_when_empty_attribute ) {
			return false;
		}

		if ( count( $field->get_options() ) > 0 ) {
			return false;
		}

		return true;
	}
	/**
	 * Add classes to the field based on the hide field when empty setting.
	 *
	 * @param string $classes    The classes to add.
	 * @param Field  $field       The field to add the classes to.
	 * @return string    The classes to add.
	 */
	public static function add_html_render_classes( $classes, $field ) {

		if ( ! self::should_hide_field( $field ) ) {
			return $classes;
		}

		$classes[] = 'search-filter-field--hidden';

		return $classes;
	}

	/**
	 * Add the aria-hidden attribute to the field.
	 *
	 * @param array $attributes The attributes to add.
	 * @param Field $field      The field to add the attributes to.
	 * @return array The attributes to add.
	 */
	public static function add_html_render_attributes( $attributes, $field ) {
		if ( ! self::should_hide_field( $field ) ) {
			return $attributes;
		}

		$attributes['aria-hidden'] = 'true';

		return $attributes;
	}

	/**
	 * Set the render data for the field.
	 *
	 * @param array $render_data The render data to update.
	 * @param Field $field       The field to update the render data for.
	 * @return array The updated render data.
	 */
	public static function apply_hidden_field_attributes( $render_data, $field ) {

		if ( ! self::should_hide_field( $field ) ) {
			return $render_data;
		}

		// If a field is  aria-hidden it should not be tabbable, so we need to
		// disable the interactivity which does this for us.
		$render_data['isInteractive'] = false;

		return $render_data;
	}

	/**
	 * Register the custom field settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_custom_field_settings() {
		// Add custom field option to data type setting.
		$data_type_setting = Fields_Settings::get_setting( 'dataType' );

		$custom_field = array(
			'label' => __( 'Custom Field', 'search-filter-pro' ),
			'value' => 'custom_field',
		);

		$data_type_setting->add_option(
			$custom_field,
			array(
				'position' => 'after',
				'after'    => 'taxonomy',
			)
		);

		// Custom field setting for choosing a post meta key.
		$setting = array(
			'name'       => 'dataCustomField',
			'label'      => __( 'Custom Field', 'search-filter-pro' ),
			'help'       => __( 'Start typing to search for a custom field.', 'search-filter-pro' ),
			'group'      => 'data',
			'tab'        => 'settings',
			'type'       => 'string',
			'inputType'  => 'PostMetaSearch',
			'context'    => array( 'admin/field', 'block/field/search', 'admin/field/search', 'admin/field/choice', 'block/field/choice', 'admin/field/range', 'block/field/range', 'admin/field/advanced', 'block/field/advanced' ),
			'isDataType' => true,
			'dependsOn'  => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'custom_field',
						'compare' => '=',
					),
				),
			),
			'supports'   => array(
				'previewAPI' => true,
			),
		);

		$add_setting_args = array(
			'extend_block_types' => self::$all_field_types,
			'position'           => array(
				'placement' => 'after',
				'setting'   => 'dataType',
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );

		// Custom field setting for choosing a post meta key.
		$setting = array(
			'name'      => 'dataCustomFieldIndexerNotice',
			'content'   => __( 'Enable the indexer in the query settings to improve performance and enable more data types.', 'search-filter-pro' ),
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
						'value'   => 'custom_field',
						'compare' => '=',
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

		$add_setting_args = array(
			'extend_block_types' => self::$all_field_types,
			'position'           => array(
				'placement' => 'before',
				'setting'   => 'dataCustomField',
			),
		);

		Fields_Settings::add_setting( $setting, $add_setting_args );
	}
	/**
	 * Update the sort field to add custom field support.
	 *
	 * @since 3.0.0
	 */
	public static function upgrade_sort_field() {
		// Add custom field option to data type setting.
		$sort_options_setting = Fields_Settings::get_setting( 'sortOptions' );

		$custom_field_option = array(
			'label' => __( 'Custom Field', 'search-filter' ),
			'value' => 'custom_field',
		);
		$sort_options_setting->add_option( $custom_field_option );

	}

	/**
	 * Add the custom field options.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $options_data    The options to add.
	 * @param    Field $field      The field to get the options for.
	 * @return   array    The options to add.
	 */
	public static function add_custom_field_options_data( $options_data, $field ) {
		if ( count( $options_data['options'] ) > 0 ) {
			return $options_data;
		}
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $options_data;
		}
		if ( ! $field->get_attribute( 'dataCustomField' ) ) {
			return $options_data;
		}

		$custom_field_key = $field->get_attribute( 'dataCustomField' );

		global $wpdb;
		$where   = $wpdb->prepare( " WHERE meta_key=%s AND meta_value!='' ", $custom_field_key );
		$order   = self::build_sql_order_by( $field, 'meta_value' );

		$query_result = $wpdb->get_results(
			"SELECT DISTINCT(`meta_value`) 
			FROM $wpdb->postmeta
			$where
			$order
			LIMIT 0, 60"
		);

		foreach ( $query_result as $k => $v ) {
			Choice::add_option_to_array(
				$options_data['options'],
				array(
					'value' => $v->meta_value,
					'label' => $v->meta_value,
				),
				$field->get_id()
			);
		}

		return $options_data;
	}

	/**
	 * Build the SQL order by clause from a fields settings.
	 *
	 * @since 3.0.0
	 *
	 * @param Field  $field The field to get the order by for.
	 * @param string $property The property to order by.
	 * @return string The SQL order by clause.
	 */
	public static function build_sql_order_by( $field, $property ) {

		$field_order     = $field->get_attribute( 'inputOptionsOrder' );
		$field_order_dir = $field->get_attribute( 'inputOptionsOrderDir' );

		if ( $field_order === 'inherit' ) {
			return '';
		}

		$query_order = 'ASC';
		if ( $field_order_dir === 'asc' ) {
			$query_order = 'ASC';
		} elseif ( $field_order_dir === 'desc' ) {
			$query_order = 'DESC';
		}

		global $wpdb;
		$order = $wpdb->prepare( " ORDER BY %s {$query_order}", $property );

		if ( $field_order === 'alphabetical' ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$order = $wpdb->prepare( " ORDER BY %s {$query_order}", $property );
		} elseif ( $field_order === 'numerical' ) {
			// Allow negatives so cast to signed.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$order = $wpdb->prepare( " ORDER BY CAST(%s AS SIGNED) {$query_order}", $property );
		}

		return $order;
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
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $url_name;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );

		if ( ! $custom_field_key || $custom_field_key === '' ) {
			return $url_name;
		}

		return $custom_field_key;
	}

	/**
	 * Get the custom field WP query args.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @param    Field $field         The field to get the args for.
	 * @return   array    The updated WP query args.
	 */
	public static function get_custom_field_choice_wp_query_args( $query_args, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $query_args;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );
		if ( ! $custom_field_key || $custom_field_key === '' ) {
			return $query_args;
		}

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
	 * Get the custom field WP query args.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $query_args    The WP query args to update.
	 * @param    Field $field         The field to get the args for.
	 * @return   array    The updated WP query args.
	 */
	public static function get_custom_field_advanced_wp_query_args( $query_args, $field ) {
		if ( $field->get_attribute( 'dataType' ) !== 'custom_field' ) {
			return $query_args;
		}
		$custom_field_key = $field->get_attribute( 'dataCustomField' );
		if ( ! $custom_field_key || $custom_field_key === '' ) {
			return $query_args;
		}

		$values = $field->get_values();

		// If there is no meta query key then create one.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		// If there is no relation add it.
		if ( ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		$values = $field->get_values();

		if ( count( $values ) === 1 ) {
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
				$query_args['meta_query'] = array();
			}

			// If there is no relation add it.
			if ( ! isset( $query_args['meta_query']['relation'] ) ) {
				$query_args['meta_query']['relation'] = 'AND';
			}

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
	 * Get the custom field key for the range field when using price.
	 *
	 * @since 3.0.0
	 *
	 * @param string $custom_field_key    The custom field key.
	 * @param Field  $field    The field.
	 * @return string    The custom field key.
	 */
	public static function range_auto_detect_custom_field( $custom_field_key, $attributes ) {
		if ( ! isset( $attributes['dataType'] ) ) {
			return $custom_field_key;
		}
		if ( $attributes['dataType'] !== 'custom_field' ) {
			return $custom_field_key;
		}
		if ( ! isset( $attributes['dataCustomField'] ) ) {
			return $custom_field_key;
		}
		return $attributes['dataCustomField'];
	}
	/**
	 * Create a cache key for the field options.
	 *
	 * @param Field $field    The field to get the cache key for.
	 * @return string    The cache key.
	 */
	public static function get_field_options_cache_key( $field ) {
		$cache_key = $field->get_id() . '_options_data';
		return $cache_key;
	}
	/**
	 * Clear any associated caches for the field.
	 *
	 * @param Field $field    The field to clear the cache for.
	 */
	public static function clear_field_wp_cache( $field ) {
		// Clear any caches related options data for the field.
		$cache_key = self::get_field_options_cache_key( $field );
		wp_cache_delete( $cache_key, 'search-filter-pro' );

	}

	/**
	 * Check if a field is changing to no longer use the indexer, and delete any related data.
	 *
	 * Important: we need to do this on the pre_save, because otherwise the save will overwrite
	 * the data and the DB call to check the old value will match the new value.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field  $updated_instance    The field being saved.
	 * @param    string $section  The section being saved.
	 * @return   void
	 */
	public static function field_check_for_indexer_changes( $updated_instance, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		// ID of 0 means a new field.
		if ( $updated_instance->get_id() === 0 ) {
			return;
		}

		$should_resync_field = false;

		// Check if the query settings mean we should be indexing this field.
		if ( ! self::field_should_be_indexed( $updated_instance ) ) {
			return;
		}

		// Now check to see if various conditions have been met that require a resync...
		// Basically if the status has changed to enabled or away,
		// or any of the data type attributes have changed.
		$data_type_settings = Fields_Settings::get_settings_by( 'isDataType', true );

		// Build attribute names that if changed will trigger a field rebuild.
		$trigger_refresh_attributes = array(
			'type',
		);
		foreach ( $data_type_settings as $data_type_setting ) {
			$trigger_refresh_attributes[] = $data_type_setting->get_name();
		}
		/**
		 * Loop through the attributes, and compare them to the previous value,
		 * if any changed, update `should_resync_field` to true.
		 */
		// Get the previous attributes.
		$previous_attributes = self::get_previous_attributes( $updated_instance );
		foreach ( $trigger_refresh_attributes as $refresh_attribute ) {
			if ( self::instance_attribute_will_change( $updated_instance, $previous_attributes, $refresh_attribute ) ) {
				// Found a changed attribute, so set resync and break early.
				$should_resync_field = true;
				break;
			}
		}

		// Check if the status of the field will change from non indexable to indexable
		// and visa versa.
		$status_change = self::instance_index_status_change( $updated_instance );
		if ( $status_change === 'add' ) {
			$should_resync_field = true;
		} elseif ( $status_change === 'remove' ) {
			self::remove_field_indexer_data( $updated_instance );
		}

		if ( $should_resync_field ) {
			self::rebuild_field_indexer_data( $updated_instance );
		}

		// Always clear the caches after saving a field, so many settings can influence
		// counts its not worth it to try to do it more efficiently.  These are
		// regenerated frequently enough, its not going to be a big impact.
		Query_Cache::clear_caches_by_field_id( $updated_instance->get_id() );
		// Also clear the caches for the associated query.
		Query_Cache::clear_caches_by_query_id( $updated_instance->get_query_id() );
	}

	/**
	 * Check for newly created fields to see if we need to index them.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query  $query    The query being saved.
	 * @param    string $section  The section being saved.
	 * @param    bool   $is_new   Whether the record is new or not.
	 */
	public static function field_check_for_new_indexer_data( $field, $section, $is_new ) {
		if ( $section !== 'field' ) {
			return;
		}

		if ( ! $is_new ) {
			return;
		}

		// Check to see if the connected query has the indexer enabled.
		if ( ! self::field_should_be_indexed( $field ) ) {
			return;
		}

		// Queue indexing for the field via the task runner.
		self::rebuild_field_indexer_data( $field );
	}

	/**
	 * Get the query object for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to get the query for.
	 * @return   Query|null      The query object or null if not found.
	 */
	public static function get_field_query( $field ) {
		$query_id = $field->get_attribute( 'queryId' );
		if ( ! $query_id ) {
			return null;
		}

		// Try to get the query from the Data_Store first.
		$query = Data_Store::get( 'query', $query_id );
		if ( $query ) {
			return $query;
		}

		// Else look it up.
		$query = Query::find( array( 'id' => $query_id ), 'record' );
		if ( is_wp_error( $query ) ) {
			return null;
		}
		return $query;
	}

	/**
	 * Check if the field should be indexed.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to check.
	 * @return   bool    True if the field should be indexed.
	 */
	private static function field_should_be_indexed( $field ) {

		$query = self::get_field_query( $field );
		if ( ! $query ) {
			return false;
		}
		return $query->get_attribute( 'useIndexer' ) === 'yes' && in_array( $query->get_status(), self::$indexable_stati, true );
	}

	/**
	 * Check if the field is set to use the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to check.
	 * @return   bool    True if the field should be indexed.
	 */
	public static function field_is_connected_to_indexer( $field ) {

		$query = self::get_field_query( $field );
		if ( ! $query ) {
			return false;
		}
		return $query->get_attribute( 'useIndexer' ) === 'yes';
	}

	/**
	 * Remove the indexer data for a field on record pre_destroy.
	 *
	 * We want to hook in just before its destroyed so we can create an instance.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $field_id  The query ID being deleted.
	 * @param    string $section   The section being deleted from.
	 */
	public static function field_remove_indexer_data( $field_id, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		$field = Field::find( array( 'id' => $field_id ) );
		if ( is_wp_error( $field ) ) {
			return;
		}

		self::remove_field_indexer_data( $field );
	}

	/**
	 * Remove the indexer data for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The query being saved.
	 */
	private static function remove_field_indexer_data( $field ) {

		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_field task (so we clear field index data twice).
		Indexer::clear_all_field_data( $field );

		Indexer::add_task(
			array(
				'action' => 'remove_field',
				'meta'   => array(
					'field_id' => $field->get_id(),
				),
			)
		);

		Indexer::try_clear_status();

		self::clear_field_wp_cache( $field );

		Async::hook_dispatch_request();
	}
	/**
	 * Rebuild the query index.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query $field    The query ID to rebuild.
	 */
	private static function rebuild_field_indexer_data( $field ) {
		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_field task (so we clear field data twice).
		Indexer::clear_all_field_data( $field );

		Indexer::add_task(
			array(
				'action' => 'rebuild_field',
				'meta'   => array(
					'query_id' => $field->get_attribute( 'queryId' ),
					'field_id' => $field->get_id(),
				),
			)
		);

		Indexer::try_clear_status();

		self::clear_field_wp_cache( $field );

		Async::hook_dispatch_request();
	}

	/**
	 * Get previous attributes.
	 */
	private static function get_previous_attributes( $updated_instance ) {
		$db_query      = new Fields_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return;
		}
		$db_query_item = $db_query->items[0];
		$old_value     = $db_query_item->get_attributes();
		return $old_value;
	}
	/**
	 * Check if a Record instance value will change given the current instance object.
	 *
	 * @param mixed $updated_instance   The current/updated instance object.
	 * @param array $previous_attributes The previous attributes array.
	 * @param mixed $attribute_to_check The attribute name to check.
	 * @return void|bool True if the value will change, false if not.
	 */
	private static function instance_attribute_will_change( $updated_instance, $previous_attributes, $attribute_to_check ) {
		$db_attributes = $previous_attributes;
		$old_value     = isset( $db_attributes[ $attribute_to_check ] ) ? $db_attributes[ $attribute_to_check ] : null;
		$new_value     = $updated_instance->get_attribute( $attribute_to_check );

		/**
		 * We want to prevent new setting from triggering rebuilds.  This can happen
		 * when we enabled an integration such as ACF, we'll get new values, probably
		 * empty strings (default values), but that doesn't mean we need to rebuild.
		 */
		if ( empty( $old_value ) && empty( $new_value ) ) {
			return false;
		}
		if ( $old_value !== $new_value ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the type of change to the index.
	 *
	 * Decides if the change requires us to add to the index, remove
	 * from the index, or ignore the status change.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $updated_instance    The field being saved.
	 * @return   string    The type of change to the index.
	 */
	private static function instance_index_status_change( $updated_instance ) {
		// Get old value.
		$db_query      = new Fields_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return;
		}
		$db_query_item = $db_query->items[0];
		$old_value     = $db_query_item->get_status();
		$new_value     = $updated_instance->get_status();

		if ( $old_value === $new_value ) {
			return 'ignore';
		}

		// If we went from a non indexable status to an indexable status.
		if ( ! in_array( $old_value, self::$indexable_stati ) && in_array( $new_value, self::$indexable_stati ) ) {
			return 'add';
		}

		if ( ! in_array( $new_value, self::$indexable_stati ) && in_array( $old_value, self::$indexable_stati ) ) {
			return 'remove';
		}

		return 'ignore';
	}

	/**
	 * Apply defaults to the fields when a query is run.
	 *
	 * @since 3.0.0
	 */
	public static function apply_defaults_to_query( $query ) {
		self::$defaults_query_tracking = $query->get_id();
		add_filter( 'search-filter/fields/field/values', array( __CLASS__, 'set_default_values_for_query' ), 10, 2 );
	}
	
	/**
	 * Remove defaults from the fields when a query is finished.
	 *
	 * @since 3.0.0
	 */
	public static function remove_defaults_from_query() {
		self::$defaults_query_tracking = 0;
		remove_filter( 'search-filter/fields/field/values', array( __CLASS__, 'set_default_values_for_query' ), 10, 2 );
	}

	public static function query_can_apply_at_current_location( $query ) {
		// Remove the current filter when checking for active fields & values as we
		// don't wanth the defaults to get applied.
		self::remove_defaults_from_query();
		$can_apply_at_location = false;
		if ( method_exists( $query, 'can_apply_at_current_location' ) ) {
			$can_apply_at_location = $query->can_apply_at_current_location();
		}
		self::apply_defaults_to_query( $query );
		return $can_apply_at_location;
	}
	
	public static function set_default_values_for_query( $values, $field ) {

		// If the query ID doesn't match, then we don't want to apply the defaults.
		// Note - this check might not be necessary if we're filtering a specific field
		// then it's already likely being called by its own query via `get_fields()`.
		if ( $field->get_query_id() !== self::$defaults_query_tracking ) {
			return $values;
		}

		// If the field already has values, then don't override them.
		if ( ! empty( $values ) ) {
			return $values;
		}
	
		// Check if the field is setup to use a default value.
		if ( ! self::can_use_default_value( $field ) ) {
			return $values;
		}
		
		$field_query_record = self::get_field_query( $field );
		$field_query = Query::create_from_record( $field_query_record );
	
		// If the field is not set to apply to the query, and we're on a location with the query then bail.
		if ( $field->get_attribute( 'defaultValueApplyToQuery' ) !== 'yes' && self::query_can_apply_at_current_location( $field_query ) ) {
			return $values;
		}
		
		// If the query param is applied, it means we don't want to set a default
		// because the query has been interacted with already.
		$query_param = '~' . $field->get_query_id();
		if ( isset( $_GET[ $query_param ] ) ) {
			return $values;
		}

		// Otherwise, set the default value.
		$default_value = self::get_default_value( $field );
		if ( $default_value === null ) {
			return $values;
		}
		return array( $default_value );
	}
	public static function get_default_value( $field ) {
		
		// Use custom value.
		if ( $field->get_attribute( 'defaultValueType' ) === 'custom' ) {

			// Use the custom value that was set in the field.
			return $field->get_attribute( 'defaultValueCustom' );

		} else if ( $field->get_attribute( 'defaultValueType' ) === 'inherit' ) {
			// Inherit the values from the current location.

			// Get the queried object.
			$queried_object = get_queried_object();

			// Ensure the connected query is ready otherwise return early.
			$query = self::get_field_query( $field );
			if ( ! $query ) {
				return null;
			}
			
			// Note - we don't check to see the option is available in the field before setting
			// the default.  It could be very expensive to generate all optins (especially if a field
			// has a restrcition on the number of options) but it might cause issue in the future.

			// Special case when the archive is a blog and the we're inheriting the post type.
			if ( is_home() && $field->get_attribute( 'defaultValueInheritArchive' ) === 'yes' ) {
				if ( $field->get_attribute( 'dataType' ) === 'post_attribute' ) {
					$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );
					if ( $data_post_attribute === 'post_type' ) {
						return 'post';
					}
				}
			} elseif ( is_archive() && $field->get_attribute( 'defaultValueInheritArchive' ) === 'yes' ) {
				if ( is_archive() ) {
					// Check to see what type of archive the field would apply to.
					$data_type = $field->get_attribute( 'dataType' );
					if ( $data_type === 'taxonomy' && is_a( $queried_object, 'WP_Term' ) ) {
						return $queried_object->slug;
					} else if ( $data_type === 'post_attribute' ) {
						$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );

						if ( $data_post_attribute === 'post_type' && is_a( $queried_object, 'WP_Post_Type' ) ) {
							$query_post_types = self::get_post_types_from_query( $query );
							if ( ! $query_post_types ) {
								return null;
							}
							if ( in_array( $queried_object->name, $query_post_types, true ) ) {
								return $queried_object->name;
							}
							return null;
						} elseif ( $data_post_attribute === 'post_author' && is_a( $queried_object, 'WP_User' ) ) {
							return $queried_object->user_nicename;
						}
						// TODO - support date archives.
					}
				}
			} elseif ( is_singular() && $field->get_attribute( 'defaultValueInheritPost' ) === 'yes' ) {
				// If we're only a single post, extract the values from the post to potentially
				// use a default value.
				
				$data_type = $field->get_attribute( 'dataType' );
				if ( ! is_a( $queried_object, 'WP_Post' ) ) {
					return null;
				}
				// Get the post ID.
				$post_id = $queried_object->ID;

				// Lets make sure the post author and post type matches the current post.
				$query = self::get_field_query( $field );
				if ( ! $query ) {
					return null;
				}

				$post_type = $queried_object->post_type;
				$post_status = $queried_object->post_status;

				if ( $data_type === 'taxonomy' ) {
					$taxonomy = $field->get_attribute( 'dataTaxonomy' );
					$terms = get_the_terms( $post_id, $taxonomy );
					if ( ! is_array( $terms ) ) {
						return null;
					}
					if ( count( $terms ) === 0 ) {
						return null;
					}
					// Pick the first term and return its slug.
					return $terms[0]->slug;

				} else if ( $data_type === 'post_attribute' ) {
					
					$data_post_attribute = $field->get_attribute( 'dataPostAttribute' );

					if ( $data_post_attribute === 'post_type' ) {
						$query_post_types = $query->get_attribute( 'postTypes' );
						if ( ! $query_post_types ) {
							return null;
						}

						if ( ! in_array( $post_type, $query_post_types, true ) ) {
							return null;
						}
						return $post_type;

					} elseif ( $data_post_attribute === 'post_status' ) {
						$query_post_statuses = $query->get_attribute( 'postStatus' );
						if ( ! $query_post_statuses ) {
							return null;
						}

						if ( ! in_array( $post_status, $query_post_statuses, true ) ) {
							return null;
						}
						return $post_status;
						
					} elseif ( $data_post_attribute === 'post_author' ) {
						$post_author_id = $queried_object->post_author;
						$post_author_nicename = get_the_author_meta( 'user_nicename', $post_author_id );
						return $post_author_nicename;
					}
					// TODO - need to support date archives.
				}
			}
		}

		return null;
	}

	private static function get_post_types_from_query( $query ) {
		if ( ! $query ) {
			return null;
		}
		if ( ! $query->get_attribute( 'postTypes' ) ) {
			return null;
		}
		if ( count( $query->get_attribute( 'postTypes' ) ) === 0 ) {
			return null;
		}
		return $query->get_attribute( 'postTypes' );
	}
	
	private static function can_use_default_value( $field ) {
		if ( ! $field->get_attribute( 'defaultValueType' ) ) {
			return false;
		}
		if ( $field->get_attribute( 'defaultValueType' ) === 'none' ) {
			return false;
		}
		return true;
	}

	/**
	 * Add default values for fields that have them enabled.
	 *
	 * @param array $render_data The render data to update.
	 * @param Field $field       The field to update the render data for.
	 * @return array The updated render data.
	 */
	public static function add_default_values( $render_data, $field ) {
		if ( ! self::can_use_default_value( $field ) ) {
			return $render_data;
		}

		// If a field is set to use default values, add them to the
		// render attributes.
		$default_value = self::get_default_value( $field );
		if ( $default_value === null ) {
			return $render_data;
		}
		
		$render_data['defaultValues'] = array( $default_value );
		return $render_data;
	}

	/**
	 * Figure out whether a fields query is actually run at the
	 * current location.
	 * 
	 * @param mixed $field 
	 * @return void 
	 */
	private static function current_location_has_field_query( $field ) {

		// This is not really working.
		// We are using this after modifying the url values,
		$active_queries = Queries::get_active_query_ids();
		return in_array( $field->get_query_id(), $active_queries, true );
	}
}
