<?php
/**
 * Helper functions for the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper functions for the plugin.
 *
 * @since 3.0.0
 */
class Helpers {
	/**
	 * Get the post authors.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Additional arguments to pass to get_users.
	 *
	 * @return array
	 */
	public static function get_post_authors( $args = array() ) {
		$authors     = array();
		$author_args = array(
			'fields'  => array( 'ID', 'display_name', 'user_nicename' ),
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'number'  => -1,
			'exclude' => 0,
		);

		$author_args = wp_parse_args( $args, $author_args );

		$users = get_users(
			$author_args
		);

		foreach ( $users as $user ) {
			$item          = array();
			$item['value'] = $user->ID;
			$item['label'] = $user->display_name;
			array_push( $authors, $item );
		}
		return $authors;
	}

	/**
	 * Get the user roles.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_user_roles() {
		$roles = array();

		// Only get roles that have editing capabilities.
		if ( ! function_exists( '\get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$editable_roles = \get_editable_roles();

		foreach ( $editable_roles as $role_key => $role ) {
			$item          = array();
			$item['value'] = $role_key;
			$item['label'] = $role['name'];
			array_push( $roles, $item );
		}
		return $roles;
	}

	/**
	 * Get the user capabilities.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_user_capabilities() {
		$capabilities_list = array();
		// Only get roles that have editing capabilities.
		if ( ! function_exists( '\get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$editable_roles = \get_editable_roles();

		foreach ( $editable_roles as $role_key => $role ) {
			if ( ! isset( $role['capabilities'] ) ) {
				continue;
			}
			$capabilities_list = array_merge( $capabilities_list, array_keys( $role['capabilities'] ) );
		}
		$capabilities_list = array_unique( $capabilities_list );
		$capabilities      = array();
		foreach ( $capabilities_list as $capability ) {
			$item          = array();
			$item['value'] = $capability;
			$item['label'] = $capability;
			array_push( $capabilities, $item );
		}
		return $capabilities;
	}
}
