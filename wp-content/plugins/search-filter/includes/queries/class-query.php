<?php
/**
 * Class for handling the frontend display of a field.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Queries;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Fields;
use Search_Filter\Record_Base;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base field all queries.
 */
class Query extends Record_Base {
	/**
	 * The record store name
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      int    $id    ID
	 */
	public static $record_store = 'query';
	/**
	 * The meta type for the meta table.
	 *
	 * @var string
	 */
	public static $meta_table = 'search_filter_query';
	/**
	 * The full string of the class name of the query class for this section.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $records_class    The string class name.
	 */
	public static $records_class = 'Search_Filter\\Database\\Queries\\Queries';

	/**
	 * The class name to handle interacting with the record stores.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $base_class    ID
	 */
	public static $base_class = 'Search_Filter\\Queries';
	/**
	 * Context for the query, such as single, archive, etc.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $context = '';
	/**
	 * The CSS string for this style preset.
	 *
	 * @var string
	 */
	private $css = '';
	/**
	 * Integration path, eg - 'archive/post'
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $integration = '';

	/**
	 * Additional data that can be used when rendering.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $name    Name
	 */
	protected $render_meta_data = array();

	/*
	public function __construct( $id = 0 ) {
		parent::__construct( $id );
	} */
	/**
	 * Init the render config values.
	 *
	 * TODO - there should be a better way to do this.
	 */
	private function init_render_config_values() {
		// First check if they are set.
		if ( ! $this->get_render_config_value( 'currentPage' ) ) {
			$this->set_render_config_value( 'currentPage', 1 );
		}
		if ( ! $this->get_render_config_value( 'maxPages' ) ) {
			$this->set_render_config_value( 'maxPages', 1 );
		}
		if ( ! $this->get_render_config_value( 'postsPerPage' ) ) {
			$this->set_render_config_value( 'postsPerPage', 1 );
		}
		if ( ! $this->get_render_config_value( 'foundPosts' ) ) {
			$this->set_render_config_value( 'foundPosts', 1 );
		}

		$this->init_tax_archive_render_data();
	}
	/**
	 * Setup the data related to taxonomy archives when using post type
	 * archive display method.
	 */
	private function init_tax_archive_render_data() {

		// Add in archive URLs if needed.
		$integration_type = $this->get_attribute( 'integrationType' );

		if ( $integration_type !== 'archive' ) {
			return;
		}
		$archive_type = $this->get_attribute( 'archiveType' );
		if ( $archive_type !== 'post_type' ) {
			return;
		}
		$filter_tax_archives = $this->get_attribute( 'archiveFilterTaxonomies' );
		if ( $filter_tax_archives !== 'yes' ) {
			return;
		}

		global $wp_query;
		if ( ! $wp_query->is_archive() ) {
			return;
		}
		if ( ! $wp_query->is_tax() ) {
			return;
		}
		$archive_post_type = $this->get_attribute( 'postType' );
		if ( $filter_tax_archives === 'yes' && $wp_query->is_archive() && $wp_query->is_tax() ) {

			// Build the term archive URL.
			$queried_object = get_queried_object();
			// Get the postType taxonomies
			$taxonomies      = get_object_taxonomies( $archive_post_type );
			$tax_archive_url = '';
			$tax_slug        = '';
			foreach ( $taxonomies as $taxonomy ) {
				if ( $queried_object->taxonomy === $taxonomy ) {
					$tax_archive_url = get_term_link( $queried_object->term_id );
					$tax_slug        = $taxonomy;
					break;
				}
			}
			$this->set_render_config_value( 'currentTaxonomyArchive', $tax_slug );
			$this->set_render_config_value( 'taxonomyArchiveUrl', $tax_archive_url );
		}
	}
	/**
	 * Overridable function for setting query defaults.
	 *
	 * @param array $defaults Default attributes.
	 *
	 * @return array New default attributes.
	 */
	public function get_default_attributes() {
		$defaults = \Search_Filter\Queries\Settings::get_defaults();
		return $defaults;
	}
	/**
	 * Inits the data from a DB record.
	 *
	 * @param [type] $item Database record.
	 */
	public function load_record( $item ) {
		parent::load_record( $item );
		$this->set_context( $item->get_context() );
		$this->set_integration( $item->get_integration() );
	}
	/**
	 * Process the attributes and run init local vars
	 *
	 * @param array $attributes  Field attributes.
	 *
	 * @since    3.0.0
	 */
	public function set_attributes( $attributes, $replace = false ) {
		parent::set_attributes( $attributes, $replace );
		$this->generate_context();
		// TODO - maybe we need to move init() out of here.
		$this->init();

		// Set sensible defaults for the query data.
		$this->init_render_config_values();
	}
	/**
	 * Get the settings related to the display of the results, based on
	 * query parameters.
	 *
	 * TODO - we need to rethink this function, for taxonomy terms we're checking
	 * if we're in the frontend or not (for dynamic archive urls) - its won't be
	 * flexible enough going forward.
	 */
	public function get_results_data( $is_admin = false ) {
		$results_data = array(
			'type'  => 'url',
			'url'   => $this->get_results_url(),
			'label' => '',
		);

		if ( ! isset( $this->attributes['integrationType'] ) ) {
			return $results_data;
		}

		$integration_type = $this->attributes['integrationType'];
		$error            = false; // TODO - lets use some more specific messaging.
		switch ( $integration_type ) {
			case 'basic':
				$results_data['label'] = '*Not available - fields will allow users to jump between archives in your site.';
				break;
			case 'results_page':
				$post_id = $this->attributes['post_id'];
				if ( 0 === $post_id ) {
					$results_data['label'] = __( 'Choose a Post/Page first', 'search-filter' );
				}
				break;

			case 'archive':
				if ( ! isset( $this->attributes['archiveType'] ) ) {
					break;
				}
				$archive_type = $this->attributes['archiveType'];
				if ( 'post_type' === $archive_type ) {
					$post_type    = $this->attributes['postType'];
					$archive_link = get_post_type_archive_link( $post_type );
					if ( $archive_link ) {
						$results_data['url'] = $archive_link;
					} else {
						$error = true;
					}
				} elseif ( 'taxonomy' === $archive_type && isset( $this->attributes['taxonomy'] ) ) {
					$taxonomy = $this->attributes['taxonomy'];
					// Otherwise, try to generate a term link to get the base URL.
					$tax_terms = get_terms(
						$taxonomy,
						array(
							'hide_empty' => false,
							'parent'     => 0,
						)
					);
					if ( $tax_terms ) {
						// Pick any term.
						$term      = $tax_terms[0];
						$term_link = get_term_link( $term->term_id );

						if ( ! is_wp_error( $term_link ) ) {
							$results_data['url'] = $term_link;

							$term_value = $term->slug;
							if ( ( ! get_option( 'permalink_structure' ) ) && ( 'category' === $term->taxonomy ) ) {
								/**
								 * Permalinks are disabled, in this case, check to see if the taxonomy is a category
								 * because category is an exception to other taxonomies and uses `cat=ID` in the URL
								 */
								$term_value = $term->term_id;
							}
							$results_data['label'] = rtrim( untrailingslashit( $results_data['url'] ), $term_value ) . '...';
						} else {
							$error = true;
						}
					} else {
						$error = true;
					}

					if ( $error ) {
						$results_data['label'] = __( 'Unable to get link - try adding some terms first.', 'search-filter' );
					}
					break;
				}
				break;
			case 'search':
				break;
			case 'single':
				$location  = isset( $this->attributes['singleLocation'] ) ? $this->attributes['singleLocation'] : false;
				$permalink = false;
				if ( $location && $location !== '' ) {
					$permalink = get_permalink( $location );
				}
				if ( $permalink ) {
					$results_data['url'] = $permalink;
				} else {
					$results_data['label'] = __( 'Unable to get link.', 'search-filter' );
				}
				break;
		}
		// TODO - rename according to the correct convention (its also used in the WC integration).
		$results_data = apply_filters( 'search-filter/queries/query/get_results_data', $results_data, $this->attributes );
		return $results_data;
	}

	public function get_results_url() {
		// TODO - rename according to the correct convention (its also used in the WC integration).
		$override_url = apply_filters( 'search-filter/queries/query/get_results_url/override_url', false, $this->attributes );

		if ( $override_url ) {
			return $override_url;
		}

		$integration_type = $this->attributes['integrationType'];
		switch ( $integration_type ) {
			case 'basic':
				break;
			case 'results_page':
				$post_id = $this->attributes['post_id'];
				if ( 0 !== $post_id ) {
					return get_permalink( $post_id );
				}
				break;
			case 'archive':
				if ( ! isset( $this->attributes['archiveType'] ) ) {
					return '';
				}
				$archive_type = $this->attributes['archiveType'];
				if ( 'post_type' === $archive_type ) {
					$post_type = $this->attributes['postType'];
					return get_post_type_archive_link( $post_type );

				} elseif ( 'taxonomy' === $archive_type && isset( $this->attributes['taxonomy'] ) ) {
					$taxonomy       = $this->attributes['taxonomy'];
					$queried_object = get_queried_object();
					if ( isset( $queried_object->taxonomy ) && $queried_object->taxonomy === $taxonomy ) {
						return get_term_link( $queried_object->term_id );
					}
				}
				break;
			case 'search':
				return add_query_arg( 's', '', trailingslashit( get_home_url() ) );
			case 'single':
				$location  = isset( $this->attributes['singleLocation'] ) ? $this->attributes['singleLocation'] : false;
				$permalink = false;
				if ( $location === 'dynamic' && ! is_admin() && is_singular() ) {
					global $post;
					$permalink = get_permalink( $post->ID );
				} elseif ( $location !== '' ) {
					$permalink = get_permalink( $location );
				}
				if ( $permalink ) {
					return $permalink;
				}
				break;
		}
		return '';
	}

	public function apply_wp_query_args( $query_args = array() ) {
		$query_map = array(
			'post_type'      => 'postTypes',
			'post_status'    => 'postStatus',
			'posts_per_page' => 'postsPerPage',
		);
		foreach ( $query_map as $key => $data_key ) {
			$query_args[ $key ] = $this->attributes[ $data_key ];
		}

		$query_args = $this->get_sort_order_args( $query_args );
		$query_args = $this->get_tax_query_args( $query_args );
		$query_args = $this->get_sticky_posts_args( $query_args );
		$query_args = $this->exclude_current_post_args( $query_args );

		$query_args = apply_filters( 'search-filter/queries/query/apply_wp_query_args', $query_args, $this );

		return $query_args;
	}

	public function get_sticky_posts_args( $query_args ) {

		$sticky_posts = $this->get_attribute( 'stickyPosts' );

		if ( empty( $sticky_posts ) ) {
			return $query_args;
		}

		if ( $sticky_posts === 'ignore' ) {
			$query_args['ignore_sticky_posts'] = true;

		} elseif ( $sticky_posts === 'show' ) {
			$query_args['ignore_sticky_posts'] = false;

		} elseif ( $sticky_posts === 'exclude' ) {
			$query_args['ignore_sticky_posts'] = true;
			$query_args['post__not_in']        = get_option( 'sticky_posts' );

		} elseif ( $sticky_posts === 'only' ) {
			$query_args['ignore_sticky_posts'] = true;
			$query_args['post__in']            = get_option( 'sticky_posts' );
		}

		return $query_args;
	}

	public function get_tax_query_args( $query_args ) {

		// Don't apply the tax query if the query is a tax query archive.
		$integration_type = $this->get_attribute( 'integrationType' );
		$archive_type     = $this->get_attribute( 'archiveType' );

		if ( $integration_type === 'archive' && $archive_type === 'taxonomy' ) {
			return $query_args;
		}

		// Setup tax query.
		$tax_query = $this->get_attribute( 'taxonomyQuery' );

		if ( ! is_array( $tax_query ) ) {
			return $query_args;
		}

		if ( count( $tax_query ) === 0 ) {
			return $query_args;
		}

		if ( ! isset( $query_args['tax_query'] ) ) {
			$query_args['tax_query'] = array(
				'relation' => 'AND',
			);
		}

		if ( ! isset( $query_args['tax_query']['relation'] ) ) {
			$query_args['tax_query']['relation'] = 'AND';
		}

		foreach ( $tax_query as $tax_query_item ) {

			$tax_query_item = array_filter( $tax_query_item );

			if ( ! empty( $tax_query_item ) ) {
				$new_item = array(
					'taxonomy' => $tax_query_item['taxonomy'],
					'operator' => $tax_query_item['operator'],
					'field'    => 'slug',
				);
				if ( isset( $tax_query_item['terms'] ) ) {
					$new_item['terms'] = $tax_query_item['terms'];
				}
				// TODO.
				if ( isset( $tax_query_item['includeChildren'] ) ) {
					$new_item['include_children'] = $tax_query_item['includeChildren'];
				}
				$query_args['tax_query'][] = $new_item;
			}
		}
		return $query_args;
	}

	private static function get_order_direction( $order ) {
		if ( $order === 'desc' ) {
			return 'DESC';
		}
		return 'ASC';
	}

	public static function get_sort_params_from_setting( $sort_setting ) {
		$order_by                   = $sort_setting['orderBy'];
		$order_direction            = $sort_setting['order'];
		$meta_key                   = $sort_setting['metaKey'];
		$meta_type                  = $sort_setting['metaType'];
		$include_posts_without_meta = $sort_setting['includePostsWithoutMeta'];

		$sort_meta_query = array();

		// Build in supported types for meta queries.
		$built_in_meta_sort_types = array(
			'string'   => 'CHAR',
			'number'   => 'DECIMAL(12,4)', // Cast all numeric to decimal for simplicity.
			'date'     => 'DATE',
			'datetime' => 'DATETIME',
		);

		if ( ! empty( $meta_key ) ) {
			// Note: this is used heavily in the sort order field.
			$meta_key_order_clause_name                  = $meta_key . '_clause';
			$order_params[ $meta_key_order_clause_name ] = self::get_order_direction( $order_direction );

			// Add data type to the meta query.
			$type = 'CHAR';
			if ( isset( $built_in_meta_sort_types[ $meta_type ] ) ) {
				$type = $built_in_meta_sort_types[ $meta_type ];
			}

			// TODO - a further optimization could be to check if the meta key has been
			// used at all in the meta query, if so, we don't explicitly need to add
			// and exists clause.
			if ( $include_posts_without_meta === false ) {
				$sort_meta_query[ $meta_key_order_clause_name ] = array(
					'key'     => $meta_key,
					'type'    => $type,
					'compare' => 'EXISTS',
				);
			} else {
				// We need to give an explicit name to the group so we can remove it later.
				// Note: this is used heavily in the sort order field.
				$meta_key_order_base_name                     = $meta_key . '_base';
				$sort_meta_query[ $meta_key_order_base_name ] =
					array(
						'relation'                  => 'OR',
						$meta_key_order_clause_name => array(
							'key'     => $meta_key,
							'type'    => $type,
							'compare' => 'EXISTS',
						),
						array(
							'key'     => $meta_key,
							'compare' => 'NOT EXISTS',
						),
					);
			}
		} else {
			$order_params[ $order_by ] = self::get_order_direction( $order_direction );
		}

		return array(
			'order_option' => $order_params,
			'meta_query'   => $sort_meta_query,
		);
	}

	public function get_sort_order_args( $query_args ) {

		// Just in case our updater didn't upgrade the sortOrder field, lets make
		// sure it's an array.
		$sort_order = $this->get_attribute( 'sortOrder' );

		if ( ! is_array( $sort_order ) ) {
			return $query_args;
		}

		// Build array of order params.
		$order_params = array();
		// We need to create meta queries for any ordering by meta data.
		$sort_meta_query  = array(
			'relation' => 'AND',
		);
		$has_meta_queries = false;

		foreach ( $sort_order as $order ) {

			$params       = self::get_sort_params_from_setting( $order );
			$meta_query   = $params['meta_query'];
			$order_option = $params['order_option'];

			// Combine the order params.
			if ( ! empty( $order_option ) ) {
				$order_params = wp_parse_args( $order_option, $order_params );
			}

			// Combine the meta queries.
			if ( ! empty( $meta_query ) ) {
				$has_meta_queries  = true;
				$sort_meta_query[] = $meta_query;
			}
		}
		$query_args['orderby'] = $order_params;
		if ( isset( $query_args['order'] ) ) {
			unset( $query_args['order'] );
		}

		if ( ! $has_meta_queries ) {
			return $query_args;
		}

		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array(
				'relation' => 'AND',
			);
		}

		// Add our meta query as a nested meta query, ignoring the relationship etc.
		$query_args['meta_query'][] = $sort_meta_query;

		return $query_args;
	}

	public function exclude_current_post_args( $query_args ) {

		if ( $this->get_attribute( 'excludeCurrentPost' ) !== 'yes' ) {
			return $query_args;
		}

		// We don't know whats going on with this query, so first check if post__in is being used,
		// because that overrides the post__not_in - in that case we can make sure to remove
		// the current post from the post__in array.
		if ( isset( $query_args['post__in'] ) ) {
			$query_args['post__in'] = array_diff( $query_args['post__in'], array( get_the_ID() ) );
		} else {
			if ( ! isset( $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'] = array();
			}
			$query_args['post__not_in'][] = get_the_ID();
		}

		return $query_args;
	}
	/**
	 * Sets the Query context (single, archive etc)
	 *
	 * @param string $context The context to set
	 */
	public function set_context( $context ) {
		$this->context = $context;
	}
	/**
	 * Gets the Query context (theme)
	 */
	public function get_context() {
		return $this->context;
	}
	/**
	 * Get the css for the query
	 */
	public function get_css() {
		return $this->css;
	}
	/**
	 * Set the css for the query
	 *
	 * @param string $css The css to set.
	 */
	public function set_css( $css ) {
		$this->css = $css;
	}

	/**
	 * Get the fields for the query.
	 *
	 * @since 3.0.0
	 *
	 * @return array The fields.
	 */
	public function get_fields() {
		$fields = Fields::find(
			array(
				'query_id' => $this->get_id(),
				'status'   => 'enabled',
				'number'   => 0,
			)
		);

		return $fields;
	}


	/**
	 * Sets the Query context path - eg 'post/123'
	 *
	 * @param string $context The context to set.
	 * @param string $path The path of the context.
	 */
	public function set_integration( $path ) {
		$this->integration = $path;
	}


	/**
	 * Gets the Query integration path
	 */
	public function get_integration() {
		return $this->integration;
	}
	/**
	 * Sets the Query context from attributes
	 *
	 * @param string $context The context to set.
	 * @param string $path The path of the context.
	 */
	public function generate_context() {
		$integration_type = $this->attributes['integrationType'];

		$integration = $integration_type;
		if ( $integration_type === 'archive' ) {
			$integration .= "/{$this->attributes['postType']}";
		} elseif ( $integration_type === 'single' ) {
			$integration .= "/{$this->attributes['singleLocation']}";
		}
		$this->set_integration( $integration );
	}

	/**
	 * Saves the query
	 *
	 * @return int The saved query ID.
	 */
	public function save( $args = array() ) {

		if ( $this->get_id() === 0 ) {
			// If this is a new record, save it to get an ID.
			$id = parent::save();
		}
		// Update the CSS using the ID.
		$this->regenerate_css();

		// Prepare the full record.
		$extra_args = array(
			'context'     => $this->get_context(),
			'integration' => $this->get_integration(),
			'css'         => $this->get_css(),
		);

		// TODO - are we clearing our Data_Store once the info has been updated?
		// Or shall we update it rather than clear it?
		$id = parent::save( $extra_args );

		// We want to store the integration data as meta so we can query it.
		// Eg, get all queries that are connected to the query loop block, etc.
		$integration_type    = $this->get_attribute( 'integrationType' ) !== null ? $this->get_attribute( 'integrationType' ) : '';
		$single_location     = $this->get_attribute( 'singleLocation' ) !== null ? $this->get_attribute( 'singleLocation' ) : '';
		$single_integration  = $this->get_attribute( 'singleIntegration' ) !== null ? $this->get_attribute( 'singleIntegration' ) : '';
		$archive_type        = $this->get_attribute( 'archiveType' ) !== null ? $this->get_attribute( 'archiveType' ) : '';
		$archive_integration = $this->get_attribute( 'archiveIntegration' ) !== null ? $this->get_attribute( 'archiveIntegration' ) : '';
		$archive_post_type   = $this->get_attribute( 'archivePostType' ) !== null ? $this->get_attribute( 'archivePostType' ) : '';

		self::update_meta( $id, 'integration_type', $integration_type );
		self::update_meta( $id, 'single_location', $single_location );
		self::update_meta( $id, 'single_integration', $single_integration );
		self::update_meta( $id, 'archive_type', $archive_type );
		self::update_meta( $id, 'archive_integration', $archive_integration );
		self::update_meta( $id, 'archive_post_type', $archive_post_type );

		return $id;
	}

	/**
	 * Regenerates the CSS for the style preset.
	 *
	 * @since   3.0.0
	 */
	public function regenerate_css() {
		$this->css = $this->generate_css();
	}

	/**
	 * Generates the CSS for the query.
	 *
	 * @return string The generated CSS.
	 */
	private function generate_css() {
		$css = '';
		// Get the base styles class for the ID.
		$query_class = '.search-filter-query--id-' . intval( $this->get_id() );
		$css        .= $query_class . '{';
		$css        .= CSS_Loader::clean_css( $this->create_attributes_css( $this->get_attributes() ) );
		$css        .= '}';

		return $css;
	}
	/**
	 * Parses the attributes into CSS styles (variables).
	 *
	 * @since   3.0.0
	 *
	 * @param array $attributes  The saved style attributes.
	 *
	 * @return string The generated CSS.
	 */
	public function create_attributes_css( $attributes ) {
		$css = apply_filters( 'search-filter/queries/query/create_attributes_css', '', $attributes );
		return $css;
	}

	/**
	 * Gets the attributes of the query.
	 *
	 * @since 3.0.0
	 *
	 * @param boolean $unfiltered Whether to return the unfiltered attributes.
	 *
	 * @return array The attributes of the query.
	 */
	public function get_attributes( $unfiltered = false ) {
		$attributes = parent::get_attributes( $unfiltered );
		if ( ! $unfiltered ) {
			if ( defined( 'SEARCH_FILTER_PRO_VERSION' ) && version_compare( SEARCH_FILTER_PRO_VERSION, '3.0.4', '<' ) ) {
				$attributes = apply_filters( 'search-filter/queries/query/get_attributes', $attributes, $this->get_id() );
			} else {
				$attributes = apply_filters( 'search-filter/queries/query/get_attributes', $attributes, $this );
			}
		}
		return $attributes;
	}

	/**
	 * Gets an attribute
	 *
	 * @param string $attribute_name   The attribute name to get.
	 * @param bool   $unfiltered       Whether to return the unfiltered attribute.
	 *
	 * @return mixed The attribute value or false if no attribute found.
	 */
	public function get_attribute( $attribute_name, $unfiltered = false ) {
		$attribute = parent::get_attribute( $attribute_name, $unfiltered );
		if ( ! $unfiltered ) {
			$attribute = apply_filters( 'search-filter/queries/query/get_attribute', $attribute, $attribute_name, $this );
		}

		return $attribute;
	}
	/**
	 * Sets any additional data relevant to rendering.
	 */
	public function set_render_config_value( $key, $value ) {
		Query_Render_Store::set_render_data_value( $this->get_id(), $key, $value );
	}
	/**
	 * Gets a value from the render data.
	 *
	 * @param string $key The key to get.
	 * @return mixed
	 */
	public function get_render_config_value( $key ) {
		return Query_Render_Store::get_render_data_value( $this->get_id(), $key );
	}


	/**
	 * Gets the render data.
	 */
	public function get_render_settings() {
		return Query_Render_Store::get_render_data( $this->get_id() );
	}
}