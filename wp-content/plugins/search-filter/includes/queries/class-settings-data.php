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
	/**
	 * Get the settings groups.
	 *
	 * @return array The settings groups data.
	 */
	public static function get_groups() {
		$groups_data = array(
			array(
				'name'  => 'location',
				'label' => __( 'Location', 'search-filter' ),
			),
			array(
				'name'  => 'query',
				'label' => __( 'Query', 'search-filter' ),
			),
			array(
				'name'  => 'tax_query',
				'label' => __( 'Taxonomies', 'search-filter' ),
			),
			array(
				'name'  => 'accessibility',
				'label' => __( 'Accessibility', 'search-filter' ),
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
				'label'     => __( 'Location', 'search-filter' ),
				'group'     => 'location',
				'type'      => 'string',

				'help'      => __( 'Choose the location of your query and results.', 'search-filter' ),
				'default'   => '',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'label' => __( 'Dynamic', 'search-filter' ),
						'value' => 'dynamic',
					),
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
						'label' => __( 'Search (Global)', 'search-filter' ),
					),
				),
			),
			array(
				'name'      => 'archiveType',
				'label'     => __( 'Archive Type', 'search-filter' ),
				'group'     => 'location',
				'type'      => 'string',
				'default'   => 'post_type',
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
				'name'         => 'archivePostType',
				'label'        => __( 'Post Type', 'search-filter' ),
				'help'         => __( "If you don't see your post type, check that `public` and `has_archive` are enabled.", 'search-filter' ),
				'type'         => 'string',
				'group'        => 'location',
				'inputType'    => 'Select',
				'default'      => 'post',
				'options'      => array(),
				'dependsOn'    => array(
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
				'dataProvider' => array(
					'route' => '/settings/options/post-types',
				),
			),

			array(
				'name'      => 'archiveFilterTaxonomies',
				'label'     => __( 'Include Taxonomy Archives', 'search-filter' ),
				'help'      => __( 'Enables filtering in the related taxonomy archives.', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'location',
				'inputType' => 'ButtonGroup',
				'default'   => 'none',
				'options'   => array(
					array(
						'value' => 'none',
						'label' => __( 'None', 'search-filter' ),
					),
					array(
						'value' => 'all',
						'label' => __( 'All Taxonomies', 'search-filter' ),
					),
					array(
						'value' => 'custom',
						'label' => __( 'Selected Taxonomies', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
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
				),
			),
			array(
				'name'         => 'archivePostTypeTaxonomies',
				'label'        => __( 'Taxonomy Archives', 'search-filter' ),
				'help'         => __( 'Allow filtering on connected taxonomy archives.', 'search-filter' ),
				'type'         => 'array',
				'group'        => 'location',
				'inputType'    => 'MultiSelect',
				'placeholder'  => __( 'Select taxonomies', 'search-filter' ),
				'items'        => array(
					'type' => 'string',
				),
				'options'      => array(),
				'default'      => array(),
				'dependsOn'    => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'archiveFilterTaxonomies',
							'compare' => '=',
							'value'   => 'custom',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/query/archive/taxonomies',
					'args'  => array(
						'archivePostType',
						'integrationType',
					),
				),
			),
			array(
				'name'         => 'archiveTaxonomy',
				'label'        => __( 'Taxonomy', 'search-filter' ),
				'help'         => __( "If you don't see your taxonomy, ensure `public` is enabled", 'search-filter' ),
				'default'      => 'category',
				'type'         => 'string',
				'group'        => 'location',
				'inputType'    => 'Select',
				'dependsOn'    => array(
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
				'dataProvider' => array(
					'route' => '/settings/options/taxonomies',
				),
			),
			array(
				'name'      => 'archiveTaxonomyFilterTerms',
				'label'     => __( 'Filter Term Archives', 'search-filter' ),
				'help'      => __( 'Filter taxonomy term archives.', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'location',
				'inputType' => 'ButtonGroup',
				'default'   => 'all',
				'options'   => array(
					array(
						'value' => 'all',
						'label' => __( 'All Terms', 'search-filter' ),
					),
					array(
						'value' => 'custom',
						'label' => __( 'Selected terms', 'search-filter' ),
					),
				),
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
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
				),
			),
			array(
				'name'         => 'archiveTaxonomyTerms',
				'label'        => __( 'Taxonomy Term Archives', 'search-filter' ),
				'help'         => __( 'Enables filtering on specific term archives only.', 'search-filter' ),
				'type'         => 'array',
				'group'        => 'location',
				'inputType'    => 'MultiSelect',
				'placeholder'  => __( 'Choose Term Archives', 'search-filter' ),
				'items'        => array(
					'type' => 'string',
				),
				'options'      => array(),
				'default'      => array(),
				'dependsOn'    => array(
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
						array(
							'option'  => 'archiveTaxonomyFilterTerms',
							'compare' => '=',
							'value'   => 'custom',
						),
					),
				),
				'dataProvider' => array(
					'route' => '/settings/options/query/archive/taxonomy_terms',
					'args'  => array(
						'archiveTaxonomy',
						// 'integrationType',
					),
				),
			),
			array(
				'name'        => 'singleLocation',
				'label'       => __( 'Single Post / Page / CPT', 'search-filter' ),
				'group'       => 'location',
				'type'        => 'string',
				'default'     => '',
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
				'name'      => 'queryIntegration',
				'label'     => __( 'Query', 'search-filter' ),
				'help'      => __( 'Unlock more methods with the Pro add-on', 'search-filter' ),
				'group'     => 'location',
				'type'      => 'string',
				'default'   => '',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'value'     => 'main_query',
						'label'     => __( 'Main query', 'search-filter' ),
						'dependsOn' => array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'archive',
								),
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'search',
								),
							),
						),
					),
					array(
						'value'     => 'query_block',
						'label'     => __( 'Query Loop block', 'search-filter' ),
						'dependsOn' => array(
							'relation' => 'OR',
							'rules'    => array(
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'single',
								),
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'archive',
								),
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'search',
								),
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'dynamic',
								),
							),
						),
					),
				),
				'supports'  => array(
					'dependantOptions' => true,
					'hideWhenEmpty'    => true,
				),
			),

			array(
				'name'      => 'queryLoopAutodetect',
				'label'     => __( 'Autodetect Query', 'search-filter' ),
				'group'     => 'location',
				'type'      => 'string',
				'help'      => __( 'Attempts to auto detect any queries on the current page.', 'search-filter' ),
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
					'relation' => 'OR',
					'rules'    => array(
						array(
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'queryIntegration',
									'compare' => '=',
									'value'   => 'query_block',
								),
							),
						),
					),
				),
			),

			array(
				'name'         => 'postTypes',
				'label'        => __( 'Post Types', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'options'      => array(),
				'default'      => array( 'post' ),
				'help'         => __( 'The post types to use for this query.', 'search-filter' ),

				'inputType'    => 'MultiSelect',
				'group'        => 'query',
				'dataProvider' => array(
					'route' => '/settings/options/query_post_types',
					'args'  => array(
						'integrationType',
						'queryIntegration',
						'archiveType',
						'archivePostType',
						'archiveTaxonomy',
					),
				),
			),
			array(
				'name'         => 'postStatus',
				'label'        => __( 'Post Status', 'search-filter' ),
				'type'         => 'array',
				'items'        => array(
					'type' => 'string',
				),
				'group'        => 'query',
				'inputType'    => 'MultiSelect',
				'options'      => array(),
				'default'      => array( 'publish' ),
				'dataProvider' => array(
					'route' => '/settings/options/post-stati',
				),
			),
			array(
				'name'      => 'postsPerPage',
				'label'     => __( 'Posts Per Page', 'search-filter' ),
				'help'      => __( 'Set to -1 to show all posts (use with caution).', 'search-filter' ),
				'type'      => 'string',
				'group'     => 'query',
				'inputType' => 'Number',
				'min'       => '-1',
				'default'   => '10',
			),
			array(
				'name'      => 'offset',
				'label'     => __( 'Offset', 'search-filter' ),
				'help'      => __( 'Number of posts to skip (offset) in the query results.', 'search-filter' ),
				'type'      => 'number',
				'group'     => 'query',
				'inputType' => 'Number',
				'min'       => '0',
				'max'       => '100',
				'default'   => '0',
				'dependsOn' => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'postsPerPage',
							'compare' => '>',
							'value'   => '0',
						),
					),
				),
			),
			array(
				'name'      => 'postsPerPageNotice',
				'content'   => __( 'Using a high posts per page setting can cause performance issues.', 'search-filter-pro' ),
				'group'     => 'query',
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'postsPerPage',
							'value'   => '100',
							'compare' => '>',
						),

					),
				),
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
					array(
						'value' => 'rand',
						'label' => __( 'Random', 'search-filter' ),
					),
					array(
						'value' => 'rand()',
						'label' => __( 'Random (consistent)', 'search-filter' ),
					),
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
						'label' => __( 'Show', 'search-filter' ),
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
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'dynamic',
						),
					),
				),
			),
			array(
				'name'      => 'queryPostsIncludeExclude',
				'label'     => __( 'Exclude/Restrict Posts', 'search-filter' ),
				'group'     => 'query',
				'type'      => 'string',

				'help'      => __( 'Restrict to specific posts or exclude them.', 'search-filter' ),
				'default'   => '',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'value' => 'none',
						'label' => __( 'No Restriction', 'search-filter' ),
					),
					array(
						'value' => 'include',
						'label' => __( 'Restrict', 'search-filter' ),
					),
					array(
						'value' => 'exclude',
						'label' => __( 'Exclude', 'search-filter' ),
					),
				),
			),

			array(
				'name'        => 'queryRestrictPostIds',
				'label'       => __( 'Restrict Posts', 'search-filter' ),
				'group'       => 'query',
				'type'        => 'string',
				'default'     => '',
				'inputType'   => 'PostMultiSelect',
				'placeholder' => __( 'Search for a post, page or CPT', 'search-filter' ),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'queryPostsIncludeExclude',
							'compare' => '=',
							'value'   => 'include',
						),
					),
				),
			),
			array(
				'name'        => 'queryExcludePostIds',
				'label'       => __( 'Exclude Posts', 'search-filter' ),
				'group'       => 'query',
				'type'        => 'string',
				'default'     => '',
				'inputType'   => 'PostMultiSelect',
				'placeholder' => __( 'Search for a post, page or CPT', 'search-filter' ),
				'dependsOn'   => array(
					'relation' => 'AND',
					'rules'    => array(
						array(
							'option'  => 'queryPostsIncludeExclude',
							'compare' => '=',
							'value'   => 'exclude',
						),
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
							'relation' => 'AND',
							'rules'    => array(
								array(
									'option'  => 'integrationType',
									'compare' => '=',
									'value'   => 'archive',
								),
								array(
									'option'  => 'archiveType',
									'compare' => '!=',
									'value'   => 'taxonomy',
								),
							),
						),
						array(
							'option'  => 'integrationType',
							'compare' => '!=',
							'value'   => 'archive',
						),
					),
				),
			),

			array(
				'name'         => 'resultsUrlPostTypeArchive',
				'type'         => 'info',
				'default'      => '',
				'group'        => 'location',
				'inputType'    => 'Info',

				'label'        => __( 'Results Link', 'search-filter' ),
				'help'         => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText'  => __( 'Fetching...', 'search-filter' ),

				'dataProvider' => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'archiveType',
						'archivePostType',
					),
				),
				'dependsOn'    => array(
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
				'name'         => 'resultsUrlTaxonomyArchive',
				'type'         => 'info',
				'group'        => 'location',
				'label'        => __( 'Term link', 'search-filter' ),
				'help'         => __( 'Taxonomy term archives use this base URL.', 'search-filter' ),
				'loadingText'  => __( 'Fetching...', 'search-filter' ),
				'inputType'    => 'Info',
				'dataProvider' => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'archiveType',
						'archiveTaxonomy',
					),
				),
				'dependsOn'    => array(
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
				'name'         => 'resultsUrlSearch',
				'type'         => 'info',
				'group'        => 'location',
				'label'        => __( 'Results Link', 'search-filter' ),
				'help'         => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText'  => __( 'Fetching...', 'search-filter' ),
				'inputType'    => 'Info',
				'dataProvider' => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
					),
				),
				'dependsOn'    => array(
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
				'name'         => 'resultsUrlSingle',
				'type'         => 'info',
				'group'        => 'location',
				'label'        => __( 'Results Link', 'search-filter' ),
				'help'         => __( 'This is where the results will be shown', 'search-filter' ),
				'loadingText'  => __( 'Fetching...', 'search-filter' ),
				'inputType'    => 'Info',
				'dataProvider' => array(
					'route' => '/settings/results-url',
					'args'  => array(
						'integrationType',
						'singleLocation',
					),
				),
				'dependsOn'    => array(
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
							'value'   => '',
						),
					),
				),
			),

			// Results settings.
			array(
				'name'      => 'queryContainer',
				'label'     => __( 'Results Container', 'search-filter' ),
				'help'      => __( 'CSS selector for your results container.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				'default'   => '',
			),
			array(
				'name'      => 'queryContainerNotice',
				'content'   => __( 'The results container must be set to use accessibility features.', 'search-filter-pro' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'default'   => __( 'Skip to search results', 'search-filter' ),
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'queryContainer',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			),
			array(
				'name'      => 'a11yQueryContainerLabel',
				'label'     => __( 'Results Container Label', 'search-filter' ),
				'help'      => __( 'Accessible name for the results container (aria-label). Helps screen readers identify the region.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				'default'   => __( 'Search Results', 'search-filter' ),
			),
			array(
				'name'      => 'a11yQueryContainerLabelNotice',
				'content'   => __( 'An accessible label must be set for the results container.', 'search-filter-pro' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'default'   => __( 'Skip to search results', 'search-filter' ),
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'a11yQueryContainerLabel',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			),
			array(
				'name'      => 'a11ySkipLinkLabel',
				'label'     => __( 'Skip Link Label', 'search-filter' ),
				'help'      => __( 'Accessible skip link text for screen readers.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				'default'   => __( 'Skip to search results', 'search-filter' ),
			),
			array(
				'name'      => 'a11ySkipLinkClass',
				'label'     => __( 'Skip Link Class', 'search-filter' ),
				'help'      => __( "CSS class for the skip link. Use your theme's skip link class for better integration.", 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				'default'   => 'search-filter-screen-reader-text',
			),
			array(
				'name'      => 'a11yNoResultsText',
				'label'     => __( 'No Results Text', 'search-filter' ),
				'help'      => __( 'Screen reader announcement when no results are found.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				'default'   => __( 'No results found', 'search-filter' ),
			),
			array(
				'name'      => 'a11ySingleResultText',
				'label'     => __( 'Single Result Text', 'search-filter' ),
				/* translators: %d is a placeholder for the result count */
				'help'      => __( 'Screen reader announcement for one result. Use %d for the count.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				/* translators: %d is the number of results */
				'default'   => __( '%d result found', 'search-filter' ),
			),
			array(
				'name'      => 'a11yMultipleResultsText',
				'label'     => __( 'Multiple Results Text', 'search-filter' ),
				/* translators: %d is a placeholder for the result count */
				'help'      => __( 'Screen reader announcement for multiple results. Use %d for the count.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				/* translators: %d is the number of results */
				'default'   => __( '%d results found', 'search-filter' ),
			),
			array(
				'name'      => 'a11yPageText',
				'label'     => __( 'Page Text', 'search-filter' ),
				/* translators: %1$d is the current page placeholder, %2$d is the total pages placeholder */
				'help'      => __( 'Screen reader page context. Use %1$d for current page, %2$d for total pages.', 'search-filter' ),
				'group'     => 'accessibility',
				'type'      => 'string',
				'inputType' => 'Text',
				/* translators: %1$d is the current page number, %2$d is the total number of pages */
				'default'   => __( 'Page %1$d of %2$d', 'search-filter' ),
			),
		);
		return $settings_data;
	}
}
