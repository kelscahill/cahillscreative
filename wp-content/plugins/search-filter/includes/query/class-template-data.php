<?php

namespace Search_Filter\Query;

use Search_Filter\Core\CSS_Loader;
use Search_Filter\Util;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A controller for managing all things to do with template data
 *
 * This detects what archive we're on, if we should be filtering a taxonomy archive
 * etc.
 */
class Template_Data {

	/**
	 * The current archive type
	 *
	 * @var string
	 */
	private static $archive_type = '';

	/**
	 * The current taxonomy
	 *
	 * @var string
	 */
	private static $taxonomy = '';

	/**
	 * The current term
	 *
	 * @var string
	 */
	private static $term = '';

	/**
	 * The current post type
	 *
	 * @var string
	 */
	private static $post_type = '';

	/**
	 * The current post type
	 *
	 * @var string
	 */
	private static $post_id = '';

	/**
	 * The current post type
	 *
	 * @var string
	 */
	private static $is_search = false;

	/**
	 * Tracks and pre-calculated templates.
	 *
	 * @var array
	 */
	private static $taxonomy_templates = array();
	/**
	 * Initialize the class
	 *
	 * @since    3.0.0
	 */
	public static function init() {
	}

	public static function is_taxonomy_archive() {
		return self::$archive_type === 'taxonomy';
	}

	private static function get_tax_archive_terms() {
		$terms = array();
		$term  = get_queried_object();

		// Check if $term is a term object.
		if ( ! is_a( $term, 'WP_Term' ) ) {
			return $terms;
		}

		global $wp_query;
		$taxonomy = $term->taxonomy;

		if ( ! isset( $wp_query->tax_query->queried_terms[ $taxonomy ] ) ) {
			return $terms;
		}

		return $wp_query->tax_query->queried_terms[ $taxonomy ]['terms'];
	}

	/**
	 * Detect whether the current tax archive, is a single term archive
	 * or multiple terms eg:
	 *    yoursite.com/category/term
	 *    yoursite.com/category/term1+term2
	 *
	 * @return boolean
	 */
	public static function is_singular_taxonomy_term_archive() {
		if ( ! is_tax() ) {
			return false;
		}
		if ( count( self::get_tax_archive_terms() ) !== 1 ) {
			return false;
		}
		return true;
	}

	public static function taxonomy_term_archive_has_multiple_post_types() {
		if ( ! is_tax() ) {
			return false;
		}
		if ( ! self::is_singular_taxonomy_term_archive() ) {
			return false;
		}

		$taxonomy_name = get_queried_object()->taxonomy;

		return self::taxonomy_term_has_multiple_post_types( $taxonomy_name );
	}

	public static function taxonomy_term_has_multiple_post_types( $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );
		if ( ! $taxonomy ) {
			return false;
		}
		$post_types = $taxonomy->object_type;
		if ( count( $post_types ) > 1 ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the URL structure for a taxonomy..
	 *
	 * @since 3.0.0
	 *
	 * @param  string $taxonomy_name The taxonomy to get the URL for.
	 * @return string
	 */
	public static function get_term_template_link( $taxonomy_name ) {

		if ( isset( self::$taxonomy_templates[ $taxonomy_name ] ) ) {
			return self::$taxonomy_templates[ $taxonomy_name ];
		}

		/**
		 * TODO - keep an eye on this, we're fetching only top level terms
		 * and assuming that all hierarchical terms work the same way.
		 *
		 * We need to make sure that if we're using a query string to set
		 * the term, then we do that for all terms in the hierarchy but if
		 * we're using pretty permalinks then we need to make sure that they
		 * are added in the correct order.
		 */
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'hide_empty' => false,
				'depth'      => 0,
				'number'     => 1,
			)
		);
		$term  = ( count( $terms ) === 1 ) ? $terms[0] : null;
		if ( $term === null ) {
			return '';
		}
		$term_template_link = self::get_term_link_template( $term );
		return array( $term_template_link );
	}

	/**
	 * If the setting is enabled, supply a URL for the field
	 * when its a taxonomy.
	 *
	 * @since 3.0.0
	 *
	 * @param  \WP_Term $term The term to get the URL for.
	 * @return string
	 */
	private static function get_term_link_template( $term ) {
		$taxonomy_name = $term->taxonomy;

		$term_link          = get_term_link( $term, $taxonomy_name );
		$term_template_link = self::parse_term_link_template( $term_link, $term );
		return $term_template_link;
	}

	private static function parse_term_link_template( $url, $term ) {

		$taxonomy_name = $term->taxonomy;
		$term_slug     = $term->slug;
		$term_id       = $term->term_id;

		$home_url_removed = false;
		if ( strpos( $url, home_url() ) === 0 ) {
			$url              = substr( $url, strlen( home_url() ) );
			$home_url_removed = true;
		}
		$has_permalink_structure = ! empty( get_option( 'permalink_structure' ) );

		$replace_part   = $has_permalink_structure ? $term_slug : $term_id;
		$replace_symbol = $has_permalink_structure ? '[slug]' : '[id]';
		$url            = Util::string_lreplace( $replace_part, $replace_symbol, $url );

		if ( $home_url_removed === true ) {
			$url = home_url() . $url;
		}

		if ( $term->parent > 0 ) {
			$url = self::parse_term_link_template( $url, get_term( $term->parent, $taxonomy_name ) );
		}

		return $url;
	}
	/**
	 * Gets all the taxonomies belonging to the post type that don't also belong
	 * to another post type.
	 *
	 * @since 3.0.4
	 *
	 * @param string $post_type The post type
	 */
	public static function get_post_type_only_taxonomies( $post_type ) {

		$post_type_only_taxonomies = array();
		$all_post_type_taxonomies  = get_object_taxonomies( $post_type, 'objects' );

		foreach ( $all_post_type_taxonomies as $taxonomy_name ) {
			if ( self::taxonomy_term_has_multiple_post_types( $taxonomy_name ) ) {
				continue;
			}
			$post_type_only_taxonomies[] = $taxonomy_name;
		}
		// Check to make sure the tax
		return $post_type_only_taxonomies;
	}

	public static function get_taxonomy_template( $taxonomy_name ) {
		if ( isset( self::$taxonomy_templates[ $taxonomy_name ] ) ) {
			return self::$taxonomy_templates[ $taxonomy_name ];
		}
	}
	public static function set_taxonomy_template( $taxonomy_name, $depth, $term ) {

		if ( ! isset( self::$taxonomy_templates[ $taxonomy_name ] ) ) {
			self::$taxonomy_templates[ $taxonomy_name ] = array();
		}

		// Don't need multiples at the same depth.
		if ( isset( self::$taxonomy_templates[ $taxonomy_name ][ $depth ] ) ) {
			return;
		}
		self::$taxonomy_templates[ $taxonomy_name ][ $depth ] = self::get_term_link_template( $term );
	}
}
