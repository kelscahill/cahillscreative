<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage updates for external extensions.
 *
 * The idea is we keep the update checks in sync with the main plugin
 * so when there is a new release, we know if the extensions also need
 * and update and can be updated together.
 */
class Update_Manager {

	/**
	 * Array of installed extensions.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $registered_updaters = array();

	public static function init() {
		// Run at priority of 20 to run after the core updater.
		add_action( 'admin_init', array( __CLASS__, 'init_updates' ), 10 );
		add_action( 'search-filter-pro/core/plugin-updater/found_update', array( __CLASS__, 'found_update' ) );
		add_action( 'search-filter-pro/core/plugin-updater/no_update', array( __CLASS__, 'no_update' ) );
	}

	/**
	 * Init the updates.
	 *
	 * @since 3.0.0
	 */
	public static function init_updates() {
		foreach ( self::$registered_updaters as $updater_name => $update_config ) {
			// Setup the updater.
			self::$registered_updaters[ $updater_name ]['updater'] = new \Search_Filter_Pro\Core\Plugin_Updater(
				License_Server::get_endpoint(),
				$update_config['file'],
				array(
					'version' => $update_config['version'],
					'license' => $update_config['license'],
					'item_id' => $update_config['id'],
					'author'  => 'Search & Filter',
					'beta'    => $update_config['beta'],
				)
			);
		}
	}

	/**
	 * Get the registered updaters.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get() {
		return self::$registered_updaters;
	}

	/**
	 * Add an updater.
	 *
	 * @since 3.0.5
	 *
	 * @param array $args {
	 *     @type string $file    The extension file.
	 *     @type string $id      The extension item ID.
	 *     @type string $version The extension version.
	 *     @type string $license The extension license.
	 *     @type bool   $beta    Whether the extension is a beta version.
	 * }
	 */
	public static function add( $args ) {
		$defaults                           = array(
			'file'    => '',
			'id'      => '',
			'version' => '',
			'license' => 'search-filter-extension-free',
			'beta'    => false,
		);
		$args                               = wp_parse_args( $args, $defaults );
		$name                               = plugin_basename( $args['file'] );
		self::$registered_updaters[ $name ] = $args;
	}

	/**
	 * Invalidate the updater caches.
	 *
	 * @since 3.0.5
	 *
	 * @return void
	 */
	public static function invalidate_updater_caches() {
		foreach ( self::$registered_updaters as $update_config ) {
			if ( ! isset( $update_config['updater'] ) ) {
				continue;
			}
			$update_config['updater']->delete_caches();
		}
	}

	/**
	 * Handle the found update action.
	 *
	 * When an update for one extension is found, invalidate
	 * the caches of the others so we can check for their updates
	 * as soon as possible.
	 *
	 * @param string $updater_name The updater name.
	 */
	public static function found_update( $updater_name ) {
		// Check to see if we already know about this update.
		$known_updates = \Search_Filter\Options::get_option_value( 'update-manager_known-updates' );
		if ( ! $known_updates ) {
			$known_updates = array();
		}

		// If we do, return early.
		if ( in_array( $updater_name, $known_updates, true ) ) {
			return;
		}

		// Otherwise, add it to the list of known updates, and invalide the other caches.
		$known_updates[] = $updater_name;
		\Search_Filter\Options::update_option_value( 'update-manager_known-updates', $known_updates );

		// Loop through all the registered updaters and delete their caches,
		// excluding any we already know about, and excluding the current updater.
		foreach ( self::$registered_updaters as $update_config ) {
			if ( $update_config['updater']->get_name() === $updater_name ) {
				continue;
			}
			// There is no point to delete the caches for updates we already
			// know about.
			if ( in_array( $update_config['updater']->get_name(), $known_updates, true ) ) {
				continue;
			}
			$update_config['updater']->delete_caches();
		}
	}


	/**
	 * Handle the found update action.
	 *
	 * When an update for one extension is found, invalidate
	 * the caches of the others so we can check for their updates
	 * as soon as possible.
	 *
	 * @param string $updater_name The updater name.
	 */
	public static function no_update( $updater_name ) {
		// Check to see if we already know about this update.
		$known_updates = \Search_Filter\Options::get_option_value( 'update-manager_known-updates' );
		if ( ! $known_updates ) {
			$known_updates = array();
		}

		// If we do, return early.
		if ( in_array( $updater_name, $known_updates, true ) ) {
			// Then remove it and update the option value.
			$known_updates = array_diff( $known_updates, array( $updater_name ) );
			\Search_Filter\Options::update_option_value( 'update-manager_known-updates', $known_updates );
		}
	}
}
