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
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Field_Factory;
use Search_Filter\Query\Template_Data;
use Search_Filter\Record_Base;
use Search_Filter\Database\Rows\Query as Query_Row;
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
	 * Usually we only want to instantiate & lookup a record once,
	 * so store the instance for easy re-use later.
	 *
	 * @var array
	 */
	protected static $instances = array();

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

	/**
	 * The connected fields list
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      null|array    $fields    Fields
	 */
	private $fields = null;

	/**
	 * The connected WP_Query
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      null|\WP_Query    $wp_query    WP_Query
	 */
	private $wp_query = null;

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
			$this->set_render_config_value( 'foundPosts', 0 );
		}

		$this->init_tax_archive_render_data();

		do_action( 'search-filter/queries/query/init_render_config_values', $this );
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
		$archive_filter_taxonomies = $this->get_attribute( 'archiveFilterTaxonomies' );
		if ( ! $archive_filter_taxonomies || $archive_filter_taxonomies === 'none' ) {
			return;
		}

		global $wp_query;

		// TODO: we're having to check if $wp_query is set because sometinmes this is caused in admin.
		// We should probably move function to a point where its not called in admin in the first place.
		if ( ! $wp_query ) {
			return;
		}
		if ( ! Template_Data::is_taxonomy_archive() ) {
			return;
		}

		$archive_post_type = $this->get_attribute( 'archivePostType' );

		// Build the term archive URL.
		$queried_object = get_queried_object();

		$taxonomies = array();
		// Get all the taxonomies for the post type.
		if ( $archive_filter_taxonomies === 'all' ) {
			$taxonomies = get_object_taxonomies( $archive_post_type );
		} elseif ( $archive_filter_taxonomies === 'custom' ) {
			// Get the custom list of taxonomies.
			$taxonomies = $this->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
		}

		$tax_archive_url = '';
		$tax_slug        = '';
		$term_slug       = '';
		foreach ( $taxonomies as $taxonomy ) {
			if ( $queried_object->taxonomy === $taxonomy ) {
				$tax_archive_url = get_term_link( $queried_object->term_id );
				$tax_slug        = $taxonomy;
				$term_slug       = $queried_object->slug;
				break;
			}
		}

		$this->set_render_config_value( 'currentTaxonomyArchive', $tax_slug );
		$this->set_render_config_value( 'currentTaxonomyTermArchive', $term_slug );
		$this->set_render_config_value( 'taxonomyArchiveUrl', $tax_archive_url );
	}




	/**
	 * Inits the data from a DB record.
	 *
	 * @param Query_Row $item The database record.
	 */
	public function load_record( $item ) {
		$this->set_id( $item->get_id() );
		$this->set_status( $item->get_status() );
		$this->set_name( $item->get_name() );
		$this->set_record( $item );
		$this->set_attributes( $item->get_attributes() );
		$this->set_date_modified( $item->get_date_modified() );
		$this->set_date_created( $item->get_date_created() );

		$this->set_context( $item->get_context() );
		$this->set_integration( $item->get_integration() );

		$this->init();
	}
	/**
	 * Process the attributes and run init local vars
	 *
	 * @param array $attributes  Field attributes.
	 * @param bool  $replace     Whether to replace all attributes.
	 *
	 * @since    3.0.0
	 */
	public function set_attributes( array $attributes, bool $replace = false ) {
		parent::set_attributes( $attributes, $replace );
		$this->generate_context();
	}
	/**
	 * Get the settings related to the display of the results, based on
	 * query parameters.
	 *
	 * @param bool $is_admin Whether the request is from admin context.
	 *
	 * TODO - we need to rethink this function, for taxonomy terms we're checking
	 * if we're in the frontend or not (for dynamic archive urls) - its won't be
	 * flexible enough going forward.
	 */
	public function get_results_data( bool $is_admin = false ) {
		$results_data = array(
			'type'  => 'url',
			'url'   => $this->get_results_url(),
			'label' => '',
		);

		$integration_type = $this->get_attribute( 'integrationType' );
		$error            = false; // TODO - lets use some more specific messaging.
		switch ( $integration_type ) {
			case 'basic':
				$results_data['label'] = '*Not available - fields will allow users to jump between archives in your site.';
				break;
			case 'results_page':
				$post_id = absint( $this->get_attribute( 'post_id' ) );
				if ( 0 === $post_id ) {
					$results_data['label'] = __( 'Choose a Post/Page first', 'search-filter' );
				}
				break;

			case 'archive':
				$archive_type = $this->get_attribute( 'archiveType' );
				if ( 'post_type' === $archive_type ) {
					$post_type    = $this->get_attribute( 'archivePostType' );
					$archive_link = get_post_type_archive_link( $post_type );
					if ( $archive_link ) {
						$results_data['url'] = $archive_link;
					} else {
						$error = true;
					}
				} elseif ( 'taxonomy' === $archive_type && $this->get_attribute( 'archiveTaxonomy' ) !== null ) {
					$taxonomy = $this->get_attribute( 'archiveTaxonomy' );
					// Otherwise, try to generate a term link to get the base URL.
					$tax_terms = get_terms(
						array(
							'taxonomy'   => $taxonomy,
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
				$location  = $this->get_attribute( 'singleLocation' );
				$permalink = false;
				if ( ! empty( $location ) ) {
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
		$results_data = apply_filters( 'search-filter/queries/query/get_results_data', $results_data, $this->get_attributes() );
		return $results_data;
	}

	/**
	 * Get the results URL based on the integration type.
	 *
	 * @return string The results URL.
	 */
	public function get_results_url() {
		// TODO - rename according to the correct convention (its also used in the WC integration).
		$override_url = apply_filters( 'search-filter/queries/query/get_results_url/override_url', false, $this->get_attributes() );

		if ( $override_url ) {
			return $override_url;
		}
		$integration_type = $this->get_attribute( 'integrationType' );

		switch ( $integration_type ) {
			case 'basic':
				break;
			case 'results_page':
				$post_id = $this->get_attribute( 'post_id' );
				if ( 0 !== $post_id ) {
					return get_permalink( $post_id );
				}
				break;
			case 'archive':
				if ( null === $this->get_attribute( 'archiveType' ) ) {
					return '';
				}
				$archive_type = $this->get_attribute( 'archiveType' );
				if ( 'post_type' === $archive_type ) {
					$post_type = $this->get_attribute( 'archivePostType' );
					return get_post_type_archive_link( $post_type );

				} elseif ( 'taxonomy' === $archive_type && $this->get_attribute( 'archiveTaxonomy' ) !== null ) {
					$taxonomy       = $this->get_attribute( 'archiveTaxonomy' );
					$queried_object = get_queried_object();
					if ( isset( $queried_object->taxonomy ) && $queried_object->taxonomy === $taxonomy ) {
						return get_term_link( $queried_object->term_id );
					}
				}
				break;
			case 'search':
				return add_query_arg( 's', '', trailingslashit( get_home_url() ) );
			case 'single':
				$location  = $this->get_attribute( 'singleLocation' );
				$permalink = false;
				if ( ! empty( $location ) ) {
					$permalink = get_permalink( $location );
				}
				if ( $permalink ) {
					return $permalink;
				}
				break;
			case 'dynamic':
				if ( is_home() ) {
					if ( is_front_page() ) {
						return get_home_url();
					}
					$permalink = get_permalink( get_option( 'page_for_posts' ) );
					if ( $permalink ) {
						return $permalink;
					}
					return get_home_url();

				} elseif ( is_singular() ) {
					$permalink = get_permalink( get_queried_object()->ID );
					if ( $permalink ) {
						return $permalink;
					}
				} elseif ( is_post_type_archive() ) {
					return get_post_type_archive_link( get_queried_object()->name );
				} elseif ( is_tag() ) {
					return get_tag_link( get_queried_object()->term_id );
				} elseif ( is_category() ) {
					return get_category_link( get_queried_object()->term_id );
				} elseif ( is_tax() ) {
					return get_term_link( get_queried_object()->term_id );
				} elseif ( is_date() ) {
					return get_year_link( get_query_var( 'year' ) );
				} elseif ( is_author() ) {
					return get_author_posts_url( get_query_var( 'author' ) );
				} elseif ( is_search() ) {
					return add_query_arg( 's', '', trailingslashit( get_home_url() ) );
				}
				break;
		}
		return '';
	}

	/**
	 * Apply the wp query args.
	 *
	 * @param array $query_args The query args.
	 * @return array The updated query args.
	 */
	public function apply_wp_query_args( array $query_args = array() ) {
		$query_map = array(
			'post_status'    => 'postStatus',
			'posts_per_page' => 'postsPerPage',
		);
		foreach ( $query_map as $key => $data_key ) {
			$query_args[ $key ] = $this->get_attribute( $data_key );
		}

		$query_args = $this->get_post_type_args( $query_args );
		$query_args = $this->get_post_paged_args( $query_args );
		$query_args = $this->get_sort_order_args( $query_args );
		$query_args = $this->get_tax_query_args( $query_args );
		$query_args = $this->get_sticky_posts_args( $query_args );
		$query_args = $this->restrict_post_ids_args( $query_args );
		$query_args = $this->exclude_current_post_args( $query_args );

		$query_args = apply_filters( 'search-filter/queries/query/apply_wp_query_args', $query_args, $this );
		return $query_args;
	}

	/**
	 * Get the pagination and offset args for the query.
	 *
	 * Handles the relationship between offset and pagination - when offset is used,
	 * it must be recalculated for each page to maintain correct positioning.
	 *
	 * @since 3.0.0
	 *
	 * @param array $query_args The query args to add pagination args to.
	 * @return array The updated query args with paged/offset.
	 */
	private function get_post_paged_args( array $query_args ) {

		$offset = $this->get_query_offset();

		$already_has_paged = isset( $query_args['paged'] ) && absint( $query_args['paged'] ) > 0;
		// Always set the paged (for pagination).
		if ( ! $already_has_paged ) {
			// Override the paged value from the WP Query if its set.
			$query_paged = $this->get_query_paged();
			// Query paged = 0 means we were unable to determine the page, so skip.
			if ( $query_paged > 0 ) {
				$query_args['paged'] = $query_paged;
			}
		}

		// If there is an offset `paged` no longer works as expected and the offset
		// is used to apply the correct positioning.
		if ( $offset > 0 ) {
			$query_args['offset'] = $offset;
		}

		return $query_args;
	}
	/**
	 * Get the paged value for the query.
	 *
	 * @return int The paged value.
	 */
	public function get_query_paged() {
		// No WP_Query set - unable to accurately get page number.
		if ( ! $this->get_wp_query() ) {
			return 0;
		}

		$paged = 0;
		// Check if query_vars has the key 'paged'.
		$query_vars = $this->get_wp_query()->query_vars;
		if ( array_key_exists( 'paged', $query_vars ) ) {
			$paged = $query_vars['paged'];
		}

		// If $paged is 0, then we might be on the first page, or we could be
		// using offset to create pagination (ie the Query Loop Block).
		if ( $paged === 0 ) {
			// Some plugins might use a custom pagination key which we set as a render
			// config value, so try to use that.
			$pagination_key = $this->get_render_config_value( 'paginationKey' );
			if ( $pagination_key ) {
				$paged = (int) Util::get_request_var( $pagination_key );
			}
		}
		return (int) $paged;
	}

	/**
	 * Get the posts per page for a query.
	 *
	 * Resolves the query setting as well as any potential 'per page' control field
	 * that might be applied.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_query_posts_per_page() {
		$posts_per_page = $this->get_attribute( 'postsPerPage' );
		// Check if a per page field is set.
		$fields = $this->get_fields();
		foreach ( $fields as $field ) {
			if ( $field->get_attribute( 'type' ) !== 'control' ) {
				continue;
			}

			if ( $field->get_attribute( 'controlType' ) !== 'per_page' ) {
				continue;
			}

			$posts_per_page = $field->get_value();
		}

		return (int) $posts_per_page;
	}

	/**
	 * Get the calculated offset for the current page.
	 *
	 * When using offset with pagination, the actual offset must be calculated as:
	 * (posts_per_page * (current_page - 1)) + base_offset
	 *
	 * This ensures consistent results across paginated pages.
	 *
	 * @since 3.0.0
	 *
	 * @return int The calculated offset value, or 0 if no offset is configured.
	 */
	public function get_query_offset() {

		$query_offset = (int) ( $this->get_attribute( 'offset' ) ?? 0 );

		if ( $query_offset === 0 ) {
			return 0;
		}

		$per_page  = $this->get_query_posts_per_page();
		$paged     = $this->get_query_paged();
		$real_page = $paged > 0 ? $paged : 1;

		return ( $per_page * ( $real_page - 1 ) ) + $query_offset;
	}
	/**
	 * Loop through the fields and add their query args.
	 *
	 * @param array $query_args The query args.
	 *
	 * @return array The updated query args.
	 */
	public function apply_fields_wp_query_args( array $query_args = array() ) {
		$fields = $this->get_fields();
		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			if ( ! $field->is_enabled() ) {
				continue;
			}

			if ( count( $field->get_values() ) > 0 ) {
				Fields::register_active_field( $field );
				$query_args = $field->apply_wp_query_args( $query_args );
			}
		}
		return $query_args;
	}

	/**
	 * Sticky posts args.
	 *
	 * @param array $query_args The query args.
	 *
	 * @return array The updated query args.
	 */
	public function get_sticky_posts_args( array $query_args ) {

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

	/**
	 * Get the tax query args.
	 *
	 * @param array $query_args The query args.
	 *
	 * @return array The updated query args.
	 */
	public function get_tax_query_args( array $query_args ) {

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
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
			$query_args['tax_query'] = array(
				'relation' => 'AND',
			);
		}

		if ( ! isset( $query_args['tax_query']['relation'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for taxonomy filtering.
				$query_args['tax_query'][] = $new_item;
			}
		}
		return $query_args;
	}

	/**
	 * Check if the query has any active fields.
	 *
	 * @param array $exclude_ids The IDs to exclude.
	 * @return bool
	 */
	public function has_active_fields( array $exclude_ids = array() ) {
		$fields = $this->get_fields();
		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			if ( in_array( $field->get_id(), $exclude_ids, true ) ) {
				continue;
			}
			if ( ! empty( $field->get_values() ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if we are on an archive of one of the passed in taxonomy names.
	 *
	 * @param array $taxonomies The taxonomies to check.
	 * @return bool
	 */
	private function is_tax_archive( array $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy === 'category' && is_category() ) {
				return true;
			}
			if ( $taxonomy === 'post_tag' && is_tag() ) {
				return true;
			}
			if ( is_tax( $taxonomy ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if the query can be applied at the current location.
	 *
	 * @return bool
	 */
	public function can_apply_at_current_location() {
		$location = $this->get_attribute( 'integrationType' );

		// Get the queried object.
		$queried_object = get_queried_object();

		// Dynamic will always be true because its wherever the field/query is placed.
		if ( $location === 'dynamic' ) {
			return true;
		}
		// If the location is a single location, we need to check if the current location is the same as the single location.
		if ( $location === 'single' ) {
			$single_location_post_id = absint( $this->get_attribute( 'singleLocation' ) );
			if ( ! is_singular() ) {
				return false;
			}
			if ( ! is_a( $queried_object, 'WP_Post' ) ) {
				return false;
			}
			// Get the post ID.
			$post_id = $queried_object->ID;
			return $post_id === $single_location_post_id;
		}

		// If the location is an archive then check if it matches.
		if ( $location === 'archive' ) {
			$archive_type = $this->get_attribute( 'archiveType' );
			if ( $archive_type === 'post_type' ) {
				$post_type = $this->get_attribute( 'archivePostType' );

				if ( $post_type === 'post' ) {
					if ( is_home() ) {
						return true;
					}
				} elseif ( is_post_type_archive( $post_type ) ) {
					return true;
				}

				// Check if query is filtering taxonomy archives.
				$supported_taxonomy_archives = $this->get_post_type_archive_taxonomies();
				return $this->is_tax_archive( $supported_taxonomy_archives );

			} elseif ( $archive_type === 'taxonomy' ) {
				$taxonomy = $this->get_attribute( 'archiveTaxonomy' );

				if ( $taxonomy === 'category' ) {
					return is_category();
				}
				if ( $taxonomy === 'post_tag' ) {
					return is_tag();
				}
				return is_tax( $taxonomy );
			}
		}

		// Notice - this might return a false positive in certain locations, we
		// want to specifically check that we're on the main search, not just if
		// `?s=...` is in the URL.
		if ( $location === 'search' ) {
			return is_search() && ! is_archive() && ! is_tax() && ! is_singular() && ! is_home() && ! is_category() && ! is_tag();
		}

		return apply_filters( 'search-filter/queries/query/can_apply_at_current_location', false, $this );
	}


	/**
	 * Has a search query.
	 *
	 * @return bool
	 */
	public function has_search() {
		$fields = $this->get_fields();

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			$type = $field->get_attribute( 'type' );

			if ( $type !== 'search' ) {
				continue;
			}
			$values = $field->get_values();
			if ( ! empty( $values ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the order direction for a given order.
	 *
	 * @param string $order The order to get the direction for.
	 * @return string
	 */
	private static function get_order_direction( string $order ) {
		if ( $order === 'desc' ) {
			return 'DESC';
		}
		return 'ASC';
	}

	/**
	 * Get the sort params from a sort setting.
	 *
	 * @param array      $sort_setting   The sort setting to get the params for.
	 * @param Query|null $query          Optionally pass the query for use in the filter hook.
	 *
	 * @return array
	 */
	public static function get_sort_params_from_setting( array $sort_setting, $query = null ) {
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
			// Handle rand with seed.
			if ( $order_by === 'rand()' ) {
				$seed     = apply_filters( 'search-filter/queries/query/order/random_seed', 123, $query );
				$order_by = 'rand(' . absint( $seed ) . ')';
			}
			$order_params[ $order_by ] = self::get_order_direction( $order_direction );
		}

		return array(
			'order_option' => $order_params,
			'meta_query'   => $sort_meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
		);
	}

	/**
	 * Get the post type args for a query.
	 *
	 * @param array $query_args The query args to get the post type args for.
	 * @return array
	 */
	public function get_post_type_args( array $query_args ) {
		$post_types = $this->get_attribute( 'postTypes' );

		if ( empty( $post_types ) ) {
			return $query_args;
		}

		$integration_type = $this->get_attribute( 'integrationType' );

		// Archives by default don't usually pass an array as the post_type argument,
		// while its completely valid (ie, an array with a single value) it breaks
		// integrations with other plugins that seem to rely on this not being an array
		// (ie BB Themer plugin) - so lets opt to make this a single value if we know
		// this is an archive query and only 1 post type is set.
		if ( $integration_type === 'archive' && count( $post_types ) === 1 ) {
			$query_args['post_type'] = $post_types[0];
			return $query_args;
		}

		$query_args['post_type'] = $post_types;

		// If the post types includes attachments, we need to make sure to set
		// the post status to 'inherit' if its not already set to something else.
		if ( in_array( 'attachment', $post_types, true ) ) {
			if ( ! isset( $query_args['post_status'] ) ) {
				$query_args['post_status'] = 'inherit';
			} else {
				if ( ! is_array( $query_args['post_status'] ) ) {
					$query_args['post_status'] = array( $query_args['post_status'] );
				}
				if ( ! in_array( 'inherit', $query_args['post_status'], true ) ) {
					$query_args['post_status'][] = 'inherit';
				}
			}
		}
		return $query_args;
	}
	/**
	 * Get the sort order args for a query.
	 *
	 * @param array $query_args The query args to get the sort order args for.
	 * @return array
	 */
	public function get_sort_order_args( array $query_args ) {

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
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
			$query_args['meta_query'] = array(
				'relation' => 'AND',
			);
		}

		// Add our meta query as a nested meta query, ignoring the relationship etc.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for custom field sorting.
		$query_args['meta_query'][] = $sort_meta_query;
		return $query_args;
	}

	/**
	 * Exclude the current post from the query args.
	 *
	 * @param array $query_args The query args to exclude the current post from.
	 * @return array
	 */
	public function exclude_current_post_args( array $query_args ) {

		if ( $this->get_attribute( 'excludeCurrentPost' ) !== 'yes' ) {
			return $query_args;
		}

		if ( ! is_singular() ) {
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
	 * Handles the post include/exclude settings.
	 *
	 * @param array $query_args The query args to exclude the current post from.
	 * @return array
	 */
	public function restrict_post_ids_args( array $query_args ) {

		if ( $this->get_attribute( 'queryPostsIncludeExclude' ) === 'include' ) {
			$query_args['post__in'] = $this->get_attribute( 'queryRestrictPostIds' );
		}

		if ( $this->get_attribute( 'queryPostsIncludeExclude' ) === 'exclude' ) {

			$excluded_ids = $this->get_attribute( 'queryExcludePostIds' );
			if ( empty( $excluded_ids ) ) {
				return $query_args;
			}

			// If the query is using `post__in` to restrict the posts (which takes precedence over `post__not_in`)
			// then we can remove our excluded IDs from the array, otherwise, we can just add them to the `post__not_in` array.
			if ( isset( $query_args['post__in'] ) ) {
				$query_args['post__in'] = array_diff( $query_args['post__in'], $excluded_ids );
			} else {
				if ( ! isset( $query_args['post__not_in'] ) ) {
					$query_args['post__not_in'] = array();
				}
				$query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $excluded_ids );
			}

			$query_args['post__not_in'] = $this->get_attribute( 'queryExcludePostIds' );
		}

		return $query_args;
	}
	/**
	 * Sets the Query context (single, archive etc)
	 *
	 * @param string $context The context to set.
	 */
	public function set_context( string $context ) {
		$this->context = $context;
	}
	/**
	 * Gets the Query context (theme)
	 *
	 * @return string The context.
	 */
	public function get_context() {
		return $this->context;
	}
	/**
	 * Get the css for the query
	 *
	 * @return string The css.
	 */
	public function get_css() {
		return $this->css;
	}
	/**
	 * Set the css for the query
	 *
	 * @param string $css The css to set.
	 */
	public function set_css( string $css ) {
		$this->css = $css;
	}

	/**
	 * Invalidate the cached fields for this query.
	 *
	 * Use this when field instances have been updated in the singleton
	 * and you need the Query to fetch fresh instances on next get_fields() call.
	 *
	 * Example usage:
	 * ```php
	 * $field->set_values( ['new', 'values'] );
	 * Field::set_instance( $field );
	 * $query->flush_fields();  // Force query to use new instance
	 * ```
	 *
	 * @since 3.0.1
	 * @return void
	 */
	public function flush_fields() {
		$this->fields = null;
	}

	/**
	 * Get the fields for the query.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args The arguments to get the fields with.
	 *                    Supports 'force_refresh' => true to bypass cache.
	 *
	 * @return array The fields.
	 */
	public function get_fields( array $args = array() ) {

		// Check for force_refresh parameter before processing.
		$force_refresh = isset( $args['force_refresh'] ) && $args['force_refresh'];

		// Remove force_refresh from args before passing to query.
		// This ensures it doesn't interfere with Fields::find().
		if ( isset( $args['force_refresh'] ) ) {
			unset( $args['force_refresh'] );
		}

		$using_custom_args = ! empty( $args );

		// Only re-use the fields if we're not passing any args and not forcing refresh.
		if ( ! $using_custom_args && ! $force_refresh && $this->fields !== null ) {
			return $this->fields;
		}

		$defaults = array(
			'query_id' => $this->get_id(),
			'status'   => 'enabled',
			'number'   => 0,
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( $args['status'] === 'any' ) {
			unset( $args['status'] );
		}

		// Find the records first so we can init the fields that need
		// to be initialized, and we can re-use already instantiated
		// versions.
		$fields_records = Fields::find( $args, 'records' );

		$fields = array();
		foreach ( $fields_records as $field_record ) {

			if ( is_wp_error( $field_record ) ) {
				continue;
			}

			if ( Field::has_instance( $field_record->get_id() ) ) {
				$fields[] = Field::get_instance( $field_record->get_id() );
			} else {
				try {
					$field_instance = Field_Factory::create_from_record( $field_record );
					$fields[]       = $field_instance;
					Field::set_instance( $field_instance );
				} catch ( \Exception $e ) {
					continue;
				}
			}
		}
		if ( ! $using_custom_args ) {
			$this->fields = $fields;
		}
		return $fields;
	}

	/**
	 * Sets the integration.
	 *
	 * @param string $integration The path of the context.
	 */
	public function set_integration( string $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Gets the Query integration path
	 *
	 * @return string The integration path.
	 */
	public function get_integration() {
		return $this->integration;
	}
	/**
	 * Generate the query context from the attributes
	 * and set the integration.
	 */
	public function generate_context() {
		$integration_type = $this->get_attribute( 'integrationType' );

		if ( ! $integration_type ) {
			return;
		}

		$integration = $integration_type;
		if ( $integration_type === 'archive' ) {
			$archive_type = $this->get_attribute( 'archiveType' );
			if ( $archive_type === 'post_type' ) {
				$integration .= "/{$this->get_attribute( 'archivePostType' )}";
			} elseif ( $archive_type === 'taxonomy' ) {
				$integration .= "/{$this->get_attribute( 'archiveTaxonomy' )}";
			}
		} elseif ( $integration_type === 'single' ) {
			$integration .= "/{$this->get_attribute( 'singleLocation' )}";
		}

		$this->set_integration( $integration );
	}

	/**
	 * Saves the query
	 *
	 * @param array $args Additional arguments to save the query with.
	 *
	 * @return int The saved query ID.
	 */
	public function save( array $args = array() ) {

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
		$integration_type    = $this->get_attribute( 'integrationType' ) ?? '';
		$single_location     = $this->get_attribute( 'singleLocation' ) ?? '';
		$query_integration   = $this->get_attribute( 'queryIntegration' ) ?? '';
		$archive_type        = $this->get_attribute( 'archiveType' ) ?? '';
		$archive_integration = $this->get_attribute( 'archiveIntegration' ) ?? '';
		$archive_post_type   = $this->get_attribute( 'archivePostType' ) ?? '';

		self::update_meta( $id, 'integration_type', $integration_type );
		self::update_meta( $id, 'single_location', $single_location );
		self::update_meta( $id, 'query_integration', $query_integration );
		self::update_meta( $id, 'archive_type', $archive_type );
		self::update_meta( $id, 'archive_integration', $archive_integration );
		self::update_meta( $id, 'archive_post_type', $archive_post_type );

		// Update instance cache with current data.
		// Ensures downstream code (e.g., table lifecycle validation) sees fresh attributes.
		// Critical for validation logic that uses get_instance() to check useIndexer.
		self::set_instance( $this );

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

		$attributes_css = $this->create_attributes_css( $this->get_attributes() );
		if ( empty( $attributes_css ) ) {
			return $css;
		}

		// Get the base styles class for the ID.
		$query_class = '.search-filter-query--id-' . intval( $this->get_id() );
		$css        .= $query_class . '{';
		$css        .= CSS_Loader::clean_css( $attributes_css );
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
			$attributes = apply_filters( 'search-filter/queries/query/get_attributes', $attributes, $this );
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
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
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
		// Set sensible defaults for the query data.
		$this->init_render_config_values();

		$render_settings = Query_Render_Store::get_render_data( $this->get_id() );
		return apply_filters( 'search-filter/queries/query/get_render_settings', $render_settings, $this );
	}

	/**
	 * Check if the query should be attached to the given WP_Query.
	 *
	 * @param \WP_Query $wp_query The WP_Query instance to check.
	 * @return bool True if the query should attach, false otherwise.
	 */
	public function should_attach_to_query( \WP_Query $wp_query ) {

		$should_attach = false;
		// Based on the integration type, check if we need to attach to this query.
		if ( $this->should_attach_wp_search_query( $wp_query ) ) {
			$should_attach = true;
		} elseif ( $this->should_attach_archive_query( $wp_query ) ) {
			$should_attach = true;
		}

		return $should_attach;
	}

	/**
	 * Detect whether the query is the one used on WP Search Results page (yoursite.com/?s=) and
	 *
	 * @since    3.0.0
	 *
	 * @param \WP_Query $wp_query The WP_Query instance.
	 *
	 * @return bool True if the query should be attached, false if not.
	 */
	private function should_attach_wp_search_query( \WP_Query $wp_query ) {

		$integration_type = $this->get_attribute( 'integrationType' );

		if ( $integration_type !== 'search' ) {
			return false;
		}

		if ( is_admin() ) {
			return false;
		}

		if ( ! $wp_query->is_main_query() ) {
			return false;
		}

		if ( ! is_search() ) {
			return false;
		}

		if ( is_archive() ) {
			return false;
		}

		if ( ( $wp_query->is_search() ) && ( isset( $wp_query->query['s'] ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the supported taxonomy archives associated with the post type archive and user
	 * settings.
	 *
	 * @return array The taxonomies.
	 */
	private function get_post_type_archive_taxonomies() {
		// Get the archive post type for the query.
		$post_type                 = $this->get_attribute( 'archivePostType' );
		$archive_filter_taxonomies = $this->get_attribute( 'archiveFilterTaxonomies' );
		if ( $archive_filter_taxonomies === 'all' ) {
			// Get all the taxonomies connected  to the post type but we should
			// not support taxonomies that belong to multiple post types.
			return Template_Data::get_only_post_type_taxonomies( $post_type );
		}
		// If custom taxonomies are selected, return those.
		if ( $archive_filter_taxonomies === 'custom' ) {
			$archive_post_type_taxonomies = $this->get_attribute( 'archivePostTypeTaxonomies' ) ?? array();
			return $archive_post_type_taxonomies;
		}
		return array();
	}
	/**
	 * Detect whether the query is the one used on WP Search Results page (yoursite.com/?s=) and
	 *
	 * @since    3.0.0
	 *
	 * @param \WP_Query $wp_query   The WP Query object.
	 *
	 * @return bool True if the query should be attached, false if not.
	 */
	private function should_attach_archive_query( \WP_Query $wp_query ) {

		if ( $this->get_attribute( 'integrationType' ) !== 'archive' ) {
			return false;
		}

		// TODO - this should be extendable.
		// It its set to main_query, or its unset, then set to true by default.
		$should_attach_archive = $this->get_attribute( 'archiveIntegration' ) === 'main_query' || empty( $this->get_attribute( 'archiveIntegration' ) );

		if ( ! $should_attach_archive ) {
			return false;
		}

		if ( is_admin() ) {
			return false;
		}

		if ( ! $wp_query->is_main_query() ) {
			return false;
		}

		// Now check if we need to attach to a specific taxonomy archive or post type archive.
		$archive_type = $this->get_attribute( 'archiveType' );

		// Check for the special case of the blog first.
		if ( $archive_type === 'post_type' ) {
			$post_type = $this->get_attribute( 'archivePostType' );
			if ( $post_type === 'post' ) {
				// If the reading setting "homepage displays" is set to "posts".
				if ( is_home() ) {
					return true;
				}
			}
		}
		// So its not the blog, so bail if its not an archive.
		if ( ! $wp_query->is_archive() ) {
			return false;
		}
		if ( $archive_type === 'post_type' ) {
			$post_type = $this->get_attribute( 'archivePostType' );
			if ( $wp_query->is_post_type_archive( $post_type ) ) {
				return true;
			}

			$supported_taxonomy_archives = $this->get_post_type_archive_taxonomies();
			foreach ( $supported_taxonomy_archives as $taxonomy ) {
				if ( Template_Data::is_taxonomy_archive( $taxonomy ) ) {
					return true;
				}
			}
		} elseif ( $archive_type === 'taxonomy' ) {
			$taxonomy = $this->get_attribute( 'archiveTaxonomy' );
			if ( $taxonomy === 'category' ) {
				if ( $wp_query->is_category() ) {
					return true;
				}
			} elseif ( $taxonomy === 'post_tag' ) {
				if ( $wp_query->is_tag() ) {
					return true;
				}
			} elseif ( $wp_query->is_tax( $taxonomy ) && Template_Data::is_singular_taxonomy_term_archive() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Set the connected WP_Query instance.
	 *
	 * @param \WP_Query $wp_query The WP_Query instance to set.
	 */
	public function set_wp_query( \WP_Query $wp_query ) {
		$this->wp_query = $wp_query;
	}

	/**
	 * Get the connected WP_Query instance.
	 *
	 * @return null|\WP_Query The connected WP_Query instance or null.
	 */
	public function get_wp_query() {
		return $this->wp_query;
	}
}
