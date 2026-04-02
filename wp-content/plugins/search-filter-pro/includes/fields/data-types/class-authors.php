<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields\Data_Types;

use Search_Filter\Core\WP_Data;
use Search_Filter\Fields\Choice;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Fields;
use Search_Filter_Pro\Util as Search_Filter_Pro_Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Authors {

	/**
	 * Init the fields.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Author attributes and settings.
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_author_attributes_and_settings' ), 10 );
		// Add filtering by author to the WP Query.
		add_filter( 'search-filter/fields/choice/wp_query_args', array( __CLASS__, 'get_author_choice_wp_query_args' ), 10, 2 );
		// Add options to choice fields for authors.
		add_filter( 'search-filter/fields/choice/options', array( __CLASS__, 'add_author_options' ), 10, 2 );
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
				// TODO - remove the rule so we can support this in search fields.
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
				'context'   => array( 'admin/field', 'block/field' ),
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
				'context'      => array( 'admin/field', 'block/field' ),
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
				'context'      => array( 'admin/field', 'block/field' ),
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
				'context'      => array( 'admin/field', 'block/field' ),
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
			$add_setting_args = array();
			Fields_Settings::add_setting( $setting, $add_setting_args );
		}
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
		$author_ids = Search_Filter_Pro_Util::get_author_ids_from_slugs( $values );

		if ( empty( $author_ids ) ) {
			return $query_args;
		}

		$query_args['author__in'] = $author_ids;
		return $query_args;
	}


	/**
	 * Add options to the author field.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $options    The options to add to.
	 * @param    Field $field      The field to get the options for.
	 * @return   array    The options to add to.
	 */
	public static function add_author_options( $options, $field ) {
		if ( count( $options ) > 0 ) {
			return $options;
		}
		if ( $field->get_attribute( 'dataType' ) !== 'post_attribute' ) {
			return $options;
		}
		if ( $field->get_attribute( 'dataPostAttribute' ) !== 'post_author' ) {
			return $options;
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
			$query = Query::get_instance( absint( $query_id ) );
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
			function ( $a, $b ) use ( $order_direction ) {
				if ( $order_direction === 'asc' ) {
					return strcmp( $a->display_name, $b->display_name );
				} else {
					return strcmp( $b->display_name, $a->display_name );
				}
			}
		);

		$values = $field->get_values();
		foreach ( $post_authors as $post_author ) {
			$item               = array();
			$item['indexValue'] = $post_author->ID;
			$item['value']      = $post_author->user_nicename;
			$item['label']      = $post_author->display_name;

			Choice::add_option_to_array( $options, $item, $field->get_id() );

			if ( in_array( $post_author->user_nicename, $values, true ) ) {
				$field->set_value_labels( array( $post_author->user_nicename => $post_author->display_name ) );
			}
		}
		return $options;
	}
}
