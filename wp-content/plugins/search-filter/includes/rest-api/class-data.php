<?php
/**
 * Description of class
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/public
 */

namespace Search_Filter\Rest_API;

use Search_Filter\Core\WP_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class Data {
	public function __construct() {}
	/**
	 * Check request permissions
	 *
	 * TODO
	 *
	 * @return bool
	 */
	public function permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get post types
	 *
	 * @return void
	 */
	public function get_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}
		return rest_ensure_response( $post_types );
	}
	/**
	 * Get taxonomies
	 *
	 * TODO - this is almost a duplicate of the get_taxonomies_options in /includes/class-rest-api.php
	 *
	 * @return void
	 */
	public function get_taxonomies( \WP_REST_Request $request ) {
		$taxonomies      = WP_Data::get_taxonomies();
		$json_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			$json_taxonomies[] = array(
				'value' => $taxonomy->name,
				'label' => $taxonomy->label,
			);
		}
		return rest_ensure_response( $json_taxonomies );
	}
	/**
	 * Get taxonomies
	 *
	 * TODO - this is almost a duplicate of the get_taxonomies_options in /includes/class-rest-api.php
	 *
	 * @return void
	 */
	public function get_taxonomy_terms( \WP_REST_Request $request ) {
		$json_terms = array();
		$args       = array(
			'taxonomy'   => $request->get_param( 'taxonomy' ),
			'hide_empty' => false,
		);
		$terms      = WP_Data::get_terms( $args );
		foreach ( $terms as $term ) {
			$json_terms[] = array(
				'value' => $term->slug,
				'label' => $term->name,
			);
		}
		return rest_ensure_response( $json_terms );
	}
	/**
	 * Get post by ID
	 *
	 * @return void
	 */
	public function get_post( \WP_REST_Request $request ) {
		$id   = $request->get_param( 'id' );
		$post = get_post( $id );
		if ( $post ) {
			$post->permalink = get_permalink( $post->ID );
			return rest_ensure_response( $post );
		} else {
			return new \WP_Error( 'no_post', 'No post found', array( 'status' => 404 ) );
		}
	}
	/**
	 * Get posts by query args
	 *
	 * @return void
	 */
	public function get_query( \WP_REST_Request $request ) {
		$args['s']              = $request->get_param( 'search' );
		$args['post_type']      = $request->get_param( 'postType' );
		$args['posts_per_page'] = $request->get_param( 'per_page' );
		$args['paged']          = $request->get_param( 'paged' );
		$args['orderby']        = $request->get_param( 'orderby' );
		$args['order']          = $request->get_param( 'order' );
		$args['post_status']    = 'publish';
		$query                  = new \WP_Query( $args );
		$posts                  = array();

		// Add permalink to each post.
		foreach ( $query->posts as $post ) {
			$updated_post            = $post;
			$updated_post->permalink = get_permalink( $post->ID );
			$posts[]                 = $updated_post;
		}
		$query = array(
			'foundPosts' => $query->found_posts,
			'totalPages' => $query->max_num_pages,
			'posts'      => $posts,
		);
		return rest_ensure_response( $query );
	}
	/**
	 * Add rest routes.
	 */
	public function add_routes() {

		register_rest_route(
			'search-filter/v1',
			'/data/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/data/taxonomies',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_taxonomies' ),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/data/taxonomy_terms',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_taxonomy_terms' ),
				'args'                => array(
					'taxonomy' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);

		register_rest_route(
			'search-filter/v1',
			'/data/post',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post' ),
				'args'                => array(
					'id' => array(
						'type'              => 'number',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
		register_rest_route(
			'search-filter/v1',
			'/data/query',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_query' ),
				'args'                => array(
					'paged'    => array(
						'type'              => 'number',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
					'orderby'  => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'order'    => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'type'              => 'number',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'postType' => array(
						'type'              => 'array',
						'required'          => false,
						'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
					),
				),
				'permission_callback' => array( $this, 'permissions' ),
			)
		);
	}
}
