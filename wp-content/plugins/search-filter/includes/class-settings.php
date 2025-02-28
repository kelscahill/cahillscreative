<?php
/**
 * Settings Management Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Exception;
use Search_Filter\Core\WP_Data;

/**
 * The file that defines interactions with S&F settings and edit pages
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep track of all settings loaded, values etc, for specific search forms, and the global settings
 * Provide an API for modifying externally
 */
class Settings {

	/**
	 * Register of settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $registry = array();

	/**
	 * Add settings to the registry.
	 *
	 * @since 3.0.0
	 *
	 * @param string $registry_name The name of the register.
	 * @param array  $class_name The class name of the settings handler.
	 *
	 * @throws Exception If the register name already exists.
	 */
	public static function register_settings_class( $registry_name, $class_name ) {
		if ( ! isset( self::$registry[ $registry_name ] ) ) {
			self::$registry[ $registry_name ] = $class_name;
		} else {
			// translators: %s is the registery identifier name.
			throw new Exception( sprintf( esc_html__( 'There settings registry for `%1$s` already exists.', 'search-filter' ), esc_html( $registry_name ) ), SEARCH_FILTER_EXCEPTION_SETTINGS_REGISTERY_EXISTS ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Get the settings register.
	 *
	 * @since 3.0.0
	 *
	 * @param string $register_name The name of the register.
	 *
	 * @return array
	 */
	public static function get_register_class( $register_name ) {
		if ( isset( self::$registry[ $register_name ] ) ) {
			return self::$registry[ $register_name ];
		}
		return false;
	}

	/**
	 * Add rest routes for interacting with the settings data.
	 *
	 * @since 3.0.0
	 */
	public static function add_routes() {
		register_rest_route(
			'search-filter/v1',
			'/settings',
			array(
				'args' => array(),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_settings_data' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
					'args'                => array(
						'section' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_settings_data' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
					'args'                => array(
						'data'    => array(
							'type'              => 'array',
							'required'          => true,
							'sanitize_callback' => 'Search_Filter\\Core\\Sanitize::deep_clean',
						),
						'section' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}
	/**
	 * Check request permissions
	 *
	 * TODO
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public static function permissions() {
		return current_user_can( 'manage_options' );
	}
	/**
	 * Get settings data for a registered settings section.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function get_settings_data( \WP_REST_Request $request ) {
		$section = $request->get_param( 'section' );

		$settings       = array();
		$settings_class = self::get_register_class( $section );
		if ( $settings_class ) {
			$defaults        = call_user_func( array( $settings_class, 'get_defaults' ) );
			$settings_option = Options::get_option_value( $section );
			if ( $settings_option ) {
				$settings = wp_parse_args( $settings_option, $defaults );
			}
		}

		return rest_ensure_response( $settings );
	}

	/**
	 * Update the settings data.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public static function update_settings_data( \WP_REST_Request $request ) {

		$data    = $request->get_param( 'data' );
		$section = $request->get_param( 'section' );

		if ( ! is_array( $data ) ) {
			return rest_ensure_response( array( 'error' => 'Invalid data' ) );
		}

		$updated_settings_data = array();
		foreach ( $data as $feature => $value ) {
			if ( isset( $data[ $feature ] ) ) {
				$updated_settings_data[ $feature ] = $value;
			}
		}

		// Save the data as in the options table.
		Options::update_option_value( $section, $updated_settings_data );

		return rest_ensure_response( $updated_settings_data );
	}

	/**
	 * Get the taxonomies that have archives.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_taxonomies_w_archive() {

		$args = array();

		$output   = 'objects';
		$operator = 'and';

		$wp_taxonomies = get_taxonomies( $args, $output, $operator );

		$taxonomies = array();
		if ( $wp_taxonomies ) {
			foreach ( $wp_taxonomies  as $taxonomy ) {
				// Taxonomies need to be public, and have a query var (if rewrite is disabled) or have rewrites enabled to have an archive.
				if ( ( $taxonomy->public ) && ( ( $taxonomy->query_var ) || ( $taxonomy->rewrite ) ) ) {
					$item          = array();
					$item['value'] = $taxonomy->name;
					$item['label'] = $taxonomy->label . ' (' . $taxonomy->name . ')';
					array_push( $taxonomies, $item );
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * Get the post types as options.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_post_types( $args = array(), $operator = 'and' ) {
		$post_types = WP_Data::get_post_types( $args, $operator );

		$exclude_post_types = array( 'search-filter', 'revision', 'nav_menu_item', 'shop_webhook' );
		$post_types_options = array();

		foreach ( $post_types as $post_type ) {

			if ( ! in_array( $post_type->name, $exclude_post_types, true ) ) {
				$item          = array();
				$item['value'] = $post_type->name;
				$item['label'] = $post_type->labels->name;
				array_push( $post_types_options, $item );
			}
		}
		return $post_types_options;
	}

	/**
	 * Get the post statuses as options.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_post_stati() {

		$post_stati_objects = get_post_stati( array(), 'objects' );
		$post_stati_ignore  = array( 'auto-draft', 'inherit' );

		$post_stati = array();

		foreach ( $post_stati_objects as $post_status_key => $post_status ) {

			// Don't add any from the ignore list.
			if ( ! in_array( $post_status_key, $post_stati_ignore, true ) ) {

				$post_status = array(
					'value' => $post_status_key,
					'label' => $post_status->label,
				);

				array_push( $post_stati, $post_status );
			}
		}

		return $post_stati;
	}

	/**
	 * Gets post type option for post types that have archives.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_post_types_w_archive() {

		$args = array();

		$wp_post_types = get_post_types( $args, 'objects' );
		$post_types    = array();

		foreach ( $wp_post_types as $post_type ) {

			if ( ( ( $post_type->has_archive ) && ( $post_type->public ) ) || ( 'post' === $post_type->name ) ) {
				$item          = array();
				$item['value'] = $post_type->name;
				$item['label'] = $post_type->labels->name;
				array_push( $post_types, $item );
			}
		}

		return $post_types;
	}

	/**
	 * Get the available taxonomy terms for a particular taxonomy as
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array
	 */
	public static function create_taxonomy_terms_options( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		$options = array();
		foreach ( $terms as $term ) {
			$item = array(
				'value' => $term->term_id,
				'label' => $term->name,
			);
			array_push( $options, $item );
		}
		return $options;
	}
}
