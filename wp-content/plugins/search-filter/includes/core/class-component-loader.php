<?php
/**
 * Class to handle the loading of SVG files.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A class for handling loading of component dependencies.
 *
 * Components are usually not part of the main CSS & JS bundles and can be
 * called as and when needed to reduce the page load.
 */
class Component_Loader {

	/**
	 * Associative array of SVGs that have been registered.
	 *
	 * @var array
	 */
	private static $registered_components = array();
	/**
	 * Stores which SVGs should be loaded.
	 *
	 * @var array
	 */
	private static $components_to_load = array();
	/**
	 * Once an SVG has been loaded, it will be added here to avoid duplicate loading.
	 *
	 * @var array
	 */
	private static $components_loaded = array();

	/**
	 * Create a component config.
	 *
	 * @param string $name The name of the component.
	 * @param array  $args The config for the component.
	 * @return array
	 */
	private static function create_component( $name, $args = array() ) {

		if ( empty( $args ) ) {
			return array(
				'name'   => $name,
				'script' => array(
					'src'        => SEARCH_FILTER_URL . 'assets/frontend/components/' . $name . '.js',
					'asset_path' => SEARCH_FILTER_PATH . 'assets/frontend/components/' . $name . '.asset.php',
				),
				'style'  => array(
					'src' => SEARCH_FILTER_URL . 'assets/frontend/components/' . $name . '.css',
				),
			);
		}

		// Otherwise, lets make sure sensible defaults are set.
		$defaults = array(
			'name'   => $name,
			'script' => array(),
			'style'  => array(),
		);
		return wp_parse_args( $args, $defaults );
	}
	/**
	 * Register an SVG to be loaded.
	 *
	 * @param string $name The internal name of the SVG.
	 * @param array  $args The config for the component.
	 */
	public static function register( $name, $args = array() ) {
		$config                               = self::create_component( $name, $args );
		self::$registered_components[ $name ] = $config;
	}

	/**
	 * Gets the script handle for a component.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The component name.
	 * @return string The component's script handle.
	 */
	public static function get_handle( $name ) {
		return 'search-filter-frontend-component-' . $name;
	}

	/**
	 * Checks if a component is registered.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The component name.
	 * @return bool True if registered, false otherwise.
	 */
	public static function is_registered( $name ) {
		return isset( self::$registered_components[ $name ] );
	}

	/**
	 * Enqueues a component for loading.
	 *
	 * @since 3.0.0
	 *
	 * @param string $component_name The component name to enqueue.
	 */
	public static function enqueue( $component_name ) {
		if ( ! in_array( $component_name, self::$components_loaded, true ) && ! in_array( $component_name, self::$components_to_load, true ) ) {
			array_push( self::$components_to_load, $component_name );
		}
	}

	/**
	 * Checks if a component is enqueued.
	 *
	 * @since 3.0.0
	 *
	 * @param string $component_name The component name.
	 * @return bool True if enqueued, false otherwise.
	 */
	public static function is_enqueued( $component_name ) {
		return in_array( $component_name, self::$components_to_load, true );
	}

	/**
	 * Enqueues multiple components for loading.
	 *
	 * @since 3.0.0
	 *
	 * @param array $components Array of component names to enqueue.
	 */
	public static function enqueue_array( $components ) {
		// We don't want to output during an ajax request or rest request (but no way to detect this currently).
		if ( wp_doing_ajax() ) {
			return;
		}

		// Loop through, and only load the ones not yet loaded ( we can't load multiple times, they have unique IDs ).
		foreach ( $components as $component_name ) {
			if ( ! in_array( $component_name, self::$components_loaded, true ) && ! in_array( $component_name, self::$components_to_load, true ) ) {
				array_push( self::$components_to_load, $component_name );
			}
		}
	}
	/**
	 * Get Components to load.
	 *
	 * @return array
	 */
	public static function get_components_to_load() {
		return self::$components_to_load;
	}
	/**
	 * Get Components to load.
	 *
	 * @return array
	 */
	public static function get_registered_components() {
		return self::$registered_components;
	}

	/**
	 * Flush the queue of components to be loaded.
	 *
	 * @return void
	 */
	public static function flush_queue() {
		self::$components_to_load = array();
	}
}
