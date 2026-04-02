<?php
/**
 * Class to handle the creation and loading of assets.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

use Search_Filter\Options;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A class for handling loading of assets.
 */
class Asset_Loader {

	/**
	 * Collection of registered assets.
	 *
	 * @var array
	 */
	private static $registered_assets = array();

	/**
	 * The latests/current assets version.
	 *
	 * Note: not necessarily the same as the active version.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	private static $version = 2;

	/**
	 * Init the asset loader.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public static function init() {
		// Preload the asset version option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );
	}

	/**
	 * Preload the assets version option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array The updated options to preload.
	 */
	public static function preload_option( $options_to_preload ) {
		// Preload and set the default in case it doesn't exist.
		$options_to_preload[] = array( 'assets-version', self::$version );
		return $options_to_preload;
	}

	/**
	 * Get the latest/current assets version.
	 *
	 * Note: not necessarily the same as the active version, this is the current code version.
	 *
	 * @return int The current assets version.
	 */
	public static function get_version() {
		return self::$version;
	}

	/**
	 * Get the current assets version.
	 *
	 * @return int The current assets version.
	 */
	public static function get_db_version() {
		$option_assets_version = Options::get( 'assets-version', self::$version );
		return (int) $option_assets_version;
	}

	/**
	 * Set the assets version.
	 *
	 * @param int $version The assets version.
	 */
	public static function set_db_version( $version ) {
		Options::update( 'assets-version', $version );
	}

	/**
	 * Create an asset from an asset config.
	 *
	 * @since 3.2.0
	 *
	 * @param array $config The script configuration.
	 */
	private static function build( $config ) {

		$defaults = array(
			'script' => array(
				'src'          => '',
				'dependencies' => array(),
				'footer'       => false,
				'asset_path'   => '',
				'version'      => '',
				'data'         => array(),
			),
			'style'  => array(
				'src'          => '',
				'dependencies' => array(),
				'media'        => 'all',
				'version'      => '',
			),
		);

		$config_script = isset( $config['script'] ) ? $config['script'] : array();
		$config_style  = isset( $config['style'] ) ? $config['style'] : array();

		$asset = array(
			'script' => wp_parse_args( $config_script, $defaults['script'] ),
			'style'  => wp_parse_args( $config_style, $defaults['style'] ),
		);

		$script_has_asset = isset( $config['script']['asset_path'] ) && ! empty( $config['script']['asset_path'] );

		// Try to load the asset data from the asset path if it exists.
		if ( $script_has_asset && file_exists( $config['script']['asset_path'] ) ) {
			$script_asset_data               = require $config['script']['asset_path'];
			$asset['script']['dependencies'] = array_unique( array_merge( $asset['script']['dependencies'], $script_asset_data['dependencies'] ) );
			$asset['script']['version']      = $script_asset_data['version'];
		}

		$asset = apply_filters( 'search-filter/core/asset-loader/build', $asset );
		return $asset;
	}

	/**
	 * Create assets array from configs.
	 *
	 * @since 3.2.0
	 *
	 * @param array $asset_configs Array of asset configuration arrays.
	 * @param array $exclude       Array of asset names to exclude.
	 * @return array The asset configs.
	 */
	public static function create( $asset_configs, $exclude = array() ) {

		$assets = array();
		foreach ( $asset_configs as $asset_config ) {
			if ( in_array( $asset_config['name'], $exclude, true ) ) {
				continue;
			}
			$asset = self::build( $asset_config );
			if ( ! $asset ) {
				continue;
			}
			$assets[ $asset_config['name'] ] = $asset;
		}

		return $assets;
	}

	/**
	 * Registers the assets.
	 *
	 * @since 3.2.0
	 *
	 * @param array $assets The assets configs to register.
	 */
	public static function register( $assets ) {

		// Track version and load legacy assets if needed.
		$assets_version = self::get_db_version();

		// Allow assets to be modified.
		$assets = apply_filters( 'search-filter/core/asset-loader/register', $assets );

		self::$registered_assets = wp_parse_args( $assets, self::$registered_assets );

		// Register the assets with WP.
		foreach ( $assets as $asset_name => $args ) {
			if ( ! empty( $args['script']['src'] ) ) {

				$script_src = $args['script']['src'];
				// Only load on frontend, admin should always use the latest assets.
				if ( $assets_version === 1 && Util::is_frontend_only() ) {
					// Search and replace any srcs with the legacy src.
					$script_src = str_replace( 'assets/frontend/', 'assets-v1/frontend/', $script_src );
				}

				wp_register_script( $asset_name, $script_src, $args['script']['dependencies'], $args['script']['version'], $args['script']['footer'] );
				// Add custom data.
				if ( ! empty( $args['script']['data'] ) ) {
					self::add_script_data( $asset_name, $args['script']['data'] );
				}
			}

			if ( ! empty( $args['style']['src'] ) ) {
				$style_src = $args['style']['src'];
				// Only load on frontend, admin should always use the latest assets.
				if ( $assets_version === 1 && Util::is_frontend_only() ) {
					// Search and replace any srcs with the legacy src.
					$style_src = str_replace( 'assets/frontend/', 'assets-v1/frontend/', $style_src );
				}

				wp_register_style( $asset_name, $style_src, $args['style']['dependencies'], $args['style']['version'], $args['style']['media'] );
			}
		}
	}

	/**
	 * Adds custom data to a registered script.
	 *
	 * @since 3.2.0
	 *
	 * @param string $asset_name The asset name/handle.
	 * @param array  $data       The data to add to the script.
	 */
	private static function add_script_data( $asset_name, $data ) {
		$default_data = array(
			'identifier' => '',
			'value'      => '',
			'position'   => 'before',
		);

		$data = wp_parse_args( $data, $default_data );
		if ( empty( $data['identifier'] ) ) {
			return;
		}
		$script = $data['identifier'] . ' = ' . wp_json_encode( $data['value'] ) . ';';
		wp_add_inline_script( $asset_name, $script, $data['position'] );
	}
	/**
	 * Enqueue the assets.
	 *
	 * @since 3.2.0
	 *
	 * @param array $asset_handles The asset handles to load.
	 */
	public static function enqueue( $asset_handles = array() ) {

		$asset_handles = apply_filters( 'search-filter/core/asset-loader/enqueue', $asset_handles );

		// Convenience hooks to simplify removing CSS or JS assets separately if needed.
		$script_assets = array();
		$style_assets  = array();

		foreach ( $asset_handles as $asset_name ) {
			// Skip assets that are not registered.
			if ( ! isset( self::$registered_assets[ $asset_name ] ) ) {
				continue;
			}

			$args = self::$registered_assets[ $asset_name ];

			if ( ! empty( $args['script']['src'] ) ) {
				$script_assets[] = $asset_name;
			}

			if ( ! empty( $args['style']['src'] ) ) {
				$style_assets[] = $asset_name;
			}
		}

		$script_assets = apply_filters( 'search-filter/core/asset-loader/enqueue_scripts', $script_assets );
		$style_assets  = apply_filters( 'search-filter/core/asset-loader/enqueue_styles', $style_assets );

		// Flip array values to keys.
		$script_assets = array_flip( $script_assets );
		$style_assets  = array_flip( $style_assets );

		// Enqueue the assets.
		foreach ( $asset_handles as $asset_name ) {
			// Skip assets that are not registered.
			if ( ! isset( self::$registered_assets[ $asset_name ] ) ) {
				continue;
			}

			$args = self::$registered_assets[ $asset_name ];

			if ( isset( $script_assets[ $asset_name ] ) ) {
				wp_enqueue_script( $asset_name );
			}

			if ( isset( $style_assets[ $asset_name ] ) ) {
				wp_enqueue_style( $asset_name );
			}
		}
	}
}
