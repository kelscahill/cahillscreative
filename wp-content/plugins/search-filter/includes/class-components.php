<?php
/**
 * The main components class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter;

use Search_Filter\Core\Component_Loader;
use Search_Filter\Core\Asset_Loader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles components initialisation.
 */
class Components {

	/**
	 * Initialize the components.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		$components = array(
			// Don't set any args to use the default config.
			'combobox'    => array(),
			// Manually configure the checkbox component as it doesn't have a stylesheet.
			'checkbox'    => array(
				'name'   => 'checkbox',
				'script' => array(
					'src'        => SEARCH_FILTER_URL . 'assets/frontend/components/checkbox.js',
					'asset_path' => SEARCH_FILTER_PATH . 'assets/frontend/components/checkbox.asset.php',
				),
				'style'  => array(),
			),
			'date-picker' => array(),
		);
		foreach ( $components as $component_name => $component_config ) {
			Component_Loader::register( $component_name, $component_config );
		}

		do_action( 'search-filter/components/init' );
	}
	/**
	 * Get the component asset configs.
	 *
	 * @since 3.2.0
	 *
	 * @return array The component asset configs.
	 */
	private static function get_asset_configs() {

		$asset_configs = array();

		$components = Component_Loader::get_registered_components();

		foreach ( $components as $component ) {
			// Prefix components names with `search-filter-component-`.
			$asset_configs[] = array(
				'name'   => Component_Loader::get_handle( $component['name'] ),
				'script' => $component['script'],
				'style'  => $component['style'],
			);
		}
		return $asset_configs;
	}

	/**
	 * Get the component assets.
	 *
	 * @since 3.2.0
	 * @return array The component assets.
	 */
	public static function get_assets() {
		$component_asset_configs = self::get_asset_configs();
		$component_assets        = Asset_Loader::create( $component_asset_configs );
		return $component_assets;
	}

	/**
	 * Get the component assets handles.
	 *
	 * @since 3.2.0
	 * @return array The component assets handles.
	 */
	public static function get_assets_handles() {
		$component_assets = self::get_assets();

		$handles = array(
			'scripts' => array(),
			'styles'  => array(),
		);
		foreach ( $component_assets as $asset_name => $component_asset ) {
			if ( ! empty( $component_asset['script']['src'] ) ) {
				$handles['scripts'][] = $asset_name;
			}
			if ( ! empty( $component_asset['style']['src'] ) ) {
				$handles['styles'][] = $asset_name;
			}
		}
		return $handles;
	}

	/**
	 * Register the component assets.
	 *
	 * @since 3.2.0
	 */
	public static function register_assets() {
		$component_assets = self::get_assets();
		Asset_Loader::register( $component_assets );

		foreach ( $component_assets as $asset_name => $component_asset ) {
			if ( ! empty( $component_asset['script']['src'] ) ) {
				wp_register_script( $asset_name, $component_asset['script']['src'], $component_asset['script']['dependencies'], $component_asset['script']['version'], $component_asset['script']['footer'] );
			}
			if ( ! empty( $component_asset['style']['src'] ) ) {
				wp_register_style( $asset_name, $component_asset['style']['src'], $component_asset['style']['dependencies'], $component_asset['style']['version'], $component_asset['style']['media'] );
			}
		}
	}

	/**
	 * Enqueue the component assets.
	 *
	 * @since 3.2.0
	 * @param bool $all Whether to enqueue all components.
	 */
	public static function enqueue_assets( $all = false ) {

		$component_asset_handes = array();
		if ( $all ) {
			$component_assets       = self::get_assets();
			$component_asset_handes = array_keys( $component_assets );
			foreach ( $component_asset_handes as $asset_handle ) {
				Asset_Loader::enqueue( array( $asset_handle ) );
			}
			return;
		}

		// Get only the components we need to load.
		$component_names = Component_Loader::get_components_to_load();
		foreach ( $component_names as $component_name ) {
			$component_handle = Component_Loader::get_handle( $component_name );
			Asset_Loader::enqueue( array( $component_handle ) );
		}

		// Now we've loaded the components, flush the queue.
		Component_Loader::flush_queue();
	}
}
