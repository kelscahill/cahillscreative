<?php
/**
 * Query settings
 * TODO - probably should just be json.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter\Queries;

// If this file is called directly, abort.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings for our queries
 */
class Settings_Data {
	public static function get_groups() {
		$groups_data = array(
			array(
				'name'  => 'integration',
				'label' => __( 'Integration', 'search-filter' ),
			),
			array(
				'name'  => 'query',
				'label' => __( 'Query', 'search-filter' ),
			),
			array(
				'name'  => 'tax_query',
				'label' => __( 'Taxonomies', 'search-filter' ),
			),
		);
		return $groups_data;
	}
	/**
	 * Returns all the settings.
	 *
	 * @return array
	 */
	public static function get() {

		$settings_data = array(
			// Integration group.
			array(
				'name'      => 'integrationType',
				// 'label'     => __( 'Location', 'search-filter' ),
				'label'     => __( 'Integration type', 'search-filter' ),
				'group'     => 'integration',
				'type'      => 'string',

				'help'      => __( 'Choose the location of your query and results.', 'search-filter' ),
				'default'   => 'single',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'value' => 'single',
						'label' => __( 'Single Post / Page / CPT', 'search-filter' ),
					),
					array(
						'value'       => 'archive',
						'label'       => __( 'Archive', 'search-filter' ),
						'icon'        => 'archive',
						'description' => __( 'Includes taxonomy and author archives.', 'search-filter' ),
					),
					array(
						'value' => 'search',
						'label' => __( 'Search (default)', 'search-filter' ),
					),

					/*
					array(
						'value' => 'basic',
						'label' => __( 'Basic (no query) ', 'search-filter' ),
					),*/
					/*
					array(
						'value' => 'custom',
						'label' => 'Manual'
					),*/
				),
			),
			array(
				'name'      => 'archiveType',
				'label'     => __( 'Archive type', 'search-filter' ),
				'group'     => 'integration',
				'type'      => 'string',
				'default'   => 'post_type',
				// 'inputType' => 'Radio',
				'inputType' => 'ButtonGroup',
				'options'   => array(
					array(
						'value' => 'post_type',
						'label' => __( 'Post Type', 'search-filter' ),
					),
					array(
						'value' => 'taxonomy',
						'label' => __( 'Taxonomy', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
					),
				),
			),
			array(
				'name'      => 'postType',
				'label'     => __( 'Post type', 'search-filter' ),
				'help'      => __( "If you don't see your post type, check that `public` and `has_archive` are enabled.", 'search-filter' ),
				'type'      => 'string',
				'group'     => 'integration',
				'inputType' => 'Select',
				'default'   => 'post',
				'options'   => array(),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'post_type',
						),
					),
				),
				'store'     => array(
					'route' => '/settings/options/post-types',
				),
			),

			array(
				'name'      => 'archiveFilterTaxonomies',
				'label'     => __( 'Include taxonomy archives', 'search-filter' ),
				'help'      => __( 'Enables filtering in the related taxonomy archives.', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'integration',
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
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'post_type',
						),
					),
				),
			),
			array(
				'name'      => 'taxonomy',
				'label'     => __( 'Taxonomy', 'search-filter' ),
				'help'      => __( "If you don't see your taxonomy, ensure `public` is enabled", 'search-filter' ),
				'default'   => 'category',
				'type'      => 'string',
				'group'     => 'integration',
				'inputType' => 'Select',
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'taxonomy',
						),
					),
				),
				'store'     => array(
					'route' => '/settings/options/taxonomies',
				),
			),

			array(
				'name'        => 'resultsUrlPostTypeArchive',
				'type'        => 'info',
				'default'     => '',
				'group'       => 'integration',
				'inputType'   => 'Info',

				'label'       => __( 'Results link', 'search-filter' ),
				'help'        => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText' => __( 'Fetching...', 'search-filter' ),

				'store'       => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'archiveType',
						'postType',
					),
				),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'post_type',
						),
					),
				),
			),
			array(
				'name'        => 'resultsUrlTaxonomyArchive',
				'type'        => 'info',
				'group'       => 'integration',
				'label'       => __( 'Term link', 'search-filter' ),
				'help'        => __( 'Taxonomy term archives use this base URL.', 'search-filter' ),
				'loadingText' => __( 'Fetching...', 'search-filter' ),
				'inputType'   => 'Info',
				'store'       => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'archiveType',
						'taxonomy',
					),
				),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'archive',
						),
						array(
							'option'  => 'archiveType',
							'compare' => '=',
							'value'   => 'taxonomy',
						),
					),
				),
			),
			array(
				'name'        => 'resultsUrlSearch',
				'type'        => 'info',
				'group'       => 'integration',
				'label'       => __( 'Results link', 'search-filter' ),
				'help'        => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText' => __( 'Fetching...', 'search-filter' ),
				'inputType'   => 'Info',
				'store'       => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
					),
				),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'search',
						),
					),
				),
			),
			array(
				'name'        => 'singleLocation',
				'label'       => __( 'Single Post / Page / CPT', 'search-filter' ),
				'group'       => 'integration',
				'type'        => 'string',
				'default'     => 'dynamic',
				'inputType'   => 'PostSelect',
				'placeholder' => __( 'Search for a post, page or CPT', 'search-filter' ),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
					),
				),
			),
			array(
				'name'      => 'singleIntegration',
				'label'     => __( 'Query', 'search-filter' ),
				'help'      => __( 'Unlock more methods with the Pro add-on', 'search-filter' ),
				'group'     => 'integration',
				'type'      => 'string',
				'default'   => '',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'value' => 'query_block',
						'label' => __( 'Query Loop block', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
					),
				),
			),

			array(
				'name'      => 'queryLoopAutodetect',
				'label'     => __( 'Autodetect query loop', 'search-filter' ),
				'group'     => 'integration',
				'type'      => 'string',
				'help'      => __( 'Attempts to auto detect any query loops on the current page.', 'search-filter' ),
				'inputType' => 'Toggle',
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
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'singleIntegration',
							'compare' => '=',
							'value'   => 'query_block',
						),
					),
				),
			),
			array(
				'name'        => 'resultsUrlSingle',
				'type'        => 'info',
				'group'       => 'integration',
				'label'       => __( 'Results link', 'search-filter' ),
				'help'        => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText' => __( 'Fetching...', 'search-filter' ),
				'inputType'   => 'Info',
				'store'       => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'singleLocation',
					),
				),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'singleLocation',
							'compare' => '!=',
							'value'   => 'dynamic',
						),
						array(
							'option'  => 'singleLocation',
							'compare' => '!=',
							'value'   => '',
						),
					),
				),
			),
			array(
				'name'      => 'postTypes',
				'label'     => __( 'Posts Types', 'search-filter' ),
				'type'      => 'array',
				'items'     => array(
					'type' => 'string',
				),
				'options'   => array(),
				'default'   => array( 'post' ),
				'help'      => __( 'The post types to use for this query.', 'search-filter' ),

				'inputType' => 'MultiSelect',
				'group'     => 'query',
				'store'     => array(
					'route' => '/settings/options/query_post_types',
					'args'  => array(
						'integrationType',
						'singleIntegration',
						'archiveType',
						'postType',
						'taxonomy',
					),
				),
				'supports'  => array(
					'updateValue' => array(
						'integrationType',
						'archiveType',
						'postType',
						'taxonomy',
					),
				),
			),
			array(
				'name'      => 'postStatus',
				'label'     => __( 'Post Status', 'search-filter' ),
				'type'      => 'array',
				'items'     => array(
					'type' => 'string',
				),
				'group'     => 'query',
				'inputType' => 'MultiSelect',
				'options'   => array(),
				'default'   => array( 'publish' ),
				'store'     => array(
					'route' => '/settings/options/post-stati',
				),
			),
			array(
				'name'      => 'postsPerPage',
				'label'     => __( 'Posts Per Page', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',
				'inputType' => 'Number',
				'min'       => '1',
				'max'       => '100',
				'default'   => '10',
			),
			array(
				'name'            => 'sortOrder',
				'label'           => __( 'Sort Order', 'search-filter' ),
				'type'            => 'array',
				'items'           => array(
					'type' => 'object',
				),
				'options'         => array(
					array(
						'value' => 'ID',
						'label' => __( 'ID', 'search-filter' ),
					),
					array(
						'value' => 'author',
						'label' => __( 'Author', 'search-filter' ),
					),
					array(
						'value' => 'title',
						'label' => __( 'Title', 'search-filter' ),
					),
					array(
						'value' => 'name',
						'label' => __( 'Slug', 'search-filter' ),
					),
					array(
						'value' => 'type',
						'label' => __( 'Post Type', 'search-filter' ),
					),
					array(
						'value' => 'date',
						'label' => __( 'Published Date', 'search-filter' ),
					),
					array(
						'value' => 'modified',
						'label' => __( 'Modified Date', 'search-filter' ),
					),
					array(
						'value' => 'parent',
						'label' => __( 'Parent ID', 'search-filter' ),
					),
					array(
						'value' => 'comment_count',
						'label' => __( 'Comment Count', 'search-filter' ),
					),
					array(
						'value' => 'relevance',
						'label' => __( 'Relevance', 'search-filter' ),
					),
					array(
						'value' => 'menu_order',
						'label' => __( 'Menu Order', 'search-filter' ),
					),
					array(
						'value' => 'post__in',
						'label' => __( 'Restricted Posts Order', 'search-filter' ),
					),
					// TODO - add support for rand with a seed to stop getting different results each page load.
					// array(
					// 'value' => 'rand',
					// 'label' => __( 'Random', 'search-filter' ),
					// ),
				),
				'default'         => array(),
				'inputType'       => 'SortOrder',
				'group'           => 'query',
				'dependantFields' => true,
			),
			array(
				'name'      => 'stickyPosts',
				'label'     => __( 'Sticky Posts', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',

				'inputType' => 'Select',
				'default'   => '',
				'options'   => array(
					array(
						'value' => 'ignore',
						'label' => __( 'Ignore', 'search-filter' ),
					),
					array(
						'value' => 'show',
						'label' => __( 'Show Sticky Posts', 'search-filter' ),
					),
					array(
						'value' => 'exclude',
						'label' => __( 'Exclude', 'search-filter' ),
					),
					array(
						'value' => 'only',
						'label' => __( 'Only', 'search-filter' ),
					),
				),
			),
			array(
				'name'      => 'fieldRelationship',
				'label'     => __( 'Field Relationship', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',
				'inputType' => 'Select',
				'default'   => '',
				'options'   => array(
					array(
						'value' => 'all',
						'label' => __( 'Require all', 'search-filter' ),
					),
					array(
						'value' => 'any',
						'label' => __( 'Require any', 'search-filter' ),
					),
				),
			),
			array(
				'name'      => 'excludeCurrentPost',
				'label'     => __( 'Exclude Current Post', 'search-filter' ),
				'help'      => __( 'Excludes the current post (any post type) from the query.', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',
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
			),
			array(
				'name'      => 'taxonomyQuery',
				'label'     => __( 'Taxonomy conditions', 'search-filter' ),
				'group'     => 'tax_query',
				'type'      => 'object',
				'inputType' => 'TaxonomyQuery',
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '!=',
							'value'   => 'archive',
						),
					),
				),
			),

			/*
			array(
				'name'      => 'excludeIds',
				'label'     => __( 'Exclude Posts', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',

				'inputType' => 'Text',
				'default'   => '',
			), */
		);
		return $settings_data;
	}
}