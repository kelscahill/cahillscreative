<?php
/**
 * Figures out, based on saved S&F Queries, which WP Queries to affect, by assigning `sf_query_id` to the
 * appropriate WP Queries
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Query;

use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Query\Template_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Figures out which query to select or attach to.
 */
class Selector {

	/**
	 * Stores a local copy of our queries.
	 *
	 * @var [type]
	 */
	private static $queries = array();

	/**
	 * Register the stylesheets for the public-facing side of the plugin.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'init', 'Search_Filter\\Query\\Selector::init_queries', 21 );
		add_action( 'pre_get_posts', 'Search_Filter\\Query\\Selector::attach_ids', 1 );
		add_action( 'pre_get_posts', 'Search_Filter\\Query\\Selector::attach_queries', 11 );
	}

	/**
	 * Init the queries.
	 */
	public static function init_queries() {
		self::$queries = Queries::find(
			array(
				'status' => 'enabled',
				'number' => 0,
			)
		);
	}

	/**
	 * Attach S&F queries by ID.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $wp_query The WP_Query instance.
	 */
	public static function attach_ids( $wp_query ) {
		$search_filter_id = $wp_query->get( 'search_filter_query_id' );
		if ( empty( $search_filter_id ) ) {
			return;
		}
		// TODO - we probably want to re-use the already looked up self::$queries.
		$query = Query::find(
			array(
				'id'     => $search_filter_id,
				'status' => 'enabled',
			)
		);

		if ( is_wp_error( $query ) ) {
			return;
		}

		$wp_query->set( 'search_filter_queries', array( $query ) );
	}
	/**
	 * Based on saved admin queries, check the current query / page to see if we need to
	 * attach an ID.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 */
	public static function attach_queries( $wp_query ) {
		// TODO - store the integration settings in seperate columns so we can look them up,
		// rather than looping through all of them on every page load.
		foreach ( self::$queries as $saved_query ) {
			$attributes    = $saved_query->get_attributes();
			$should_attach = false;

			if ( ! $should_attach ) {
				// Based on the integration type, check if we need to attach to this query.
				if ( self::should_attach_wp_search_query( $attributes, $wp_query ) ) {
					$should_attach = true;
				} elseif ( self::should_attach_archive_query( $saved_query, $wp_query ) ) {
					$should_attach = true;
				}
			}

			// Allow for custom integration types.
			$should_attach = apply_filters( 'search-filter/query/selector/should_attach', $should_attach, $saved_query, $wp_query );

			if ( $should_attach ) {
				$wp_query->set( 'search_filter_queries', array( $saved_query ) );
			}
		}
	}

	/**
	 * Detect whether the query is the one used on WP Search Results page (yoursite.com/?s=) and
	 *
	 * @since    3.0.0
	 *
	 * @param array     $attributes The query attributes.
	 * @param \WP_Query $query The WP_Query instance.
	 *
	 * @return bool True if the query should be attached, false if not.
	 */
	public static function should_attach_wp_search_query( $attributes, $query ) {

		$integration_type = $attributes['integrationType'];

		if ( $integration_type !== 'search' ) {
			return false;
		}

		if ( is_admin() ) {
			return false;
		}

		if ( ! $query->is_main_query() ) {
			return false;
		}

		if ( ! is_search() ) {
			return false;
		}

		if ( is_archive() ) {
			return false;
		}

		if ( ( $query->is_search() ) && ( isset( $query->query['s'] ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Detect whether the query is the one used on WP Search Results page (yoursite.com/?s=) and
	 *
	 * @since    3.0.0
	 *
	 * @param array  $attributes The query attributes.
	 * @param object $query  The WP Query object.
	 *
	 * @return bool True if the query should be attached, false if not.
	 */
	public static function should_attach_archive_query( $query, $wp_query ) {

		if ( $query->get_attribute( 'integrationType' ) !== 'archive' ) {
			return false;
		}

		// TODO - this should be extendable.
		// It its set to main_query, or its unset, then set to true by default.
		$should_attach_archive = $query->get_attribute( 'archiveIntegration' ) === 'main_query' || empty( $query->get_attribute( 'archiveIntegration' ) );

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
		$archive_type = $query->get_attribute( 'archiveType' );

		// Check for the special case of the blog first.
		if ( $archive_type === 'post_type' ) {
			$post_type = $query->get_attribute( 'postType' );
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
			$post_type = $query->get_attribute( 'postType' );
			if ( $wp_query->is_post_type_archive( $post_type ) ) {
				return true;
			}

			// We should not filter taxonomies that belong to multiple post types.
			$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
			if ( $archive_filter_taxonomies === 'yes' && Template_Data::is_singular_taxonomy_term_archive() && ! Template_Data::taxonomy_term_archive_has_multiple_post_types() ) {
				$taxonomies = get_object_taxonomies( $post_type );
				foreach ( $taxonomies as $taxonomy ) {
					if ( $wp_query->is_tax( $taxonomy ) ) {
						return true;
					}
				}
			}
		} elseif ( $archive_type === 'taxonomy' ) {
			$taxonomy = $query->get_attribute( 'taxonomy' );
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
}
