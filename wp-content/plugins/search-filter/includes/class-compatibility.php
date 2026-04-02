<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter;

use Search_Filter\Compatibility\Settings;
use Search_Filter\Compatibility\Settings_Data;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles compatibility checks and notices.
 */
class Compatibility {

	/**
	 * Initialize compatibility checks.
	 */
	public static function init() {
		self::litespeed();
		self::beaver_builder();
		self::site_ground();
	}

	/**
	 * Register compatibility settings.
	 *
	 * @since 3.2.0
	 */
	public static function register() {
		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Preload the compatibility option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );

		add_filter( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );
	}

	/**
	 * Preload the compatibility option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		$options_to_preload[] = 'compatibility';
		return $options_to_preload;
	}

	/**
	 * Initialises and registers the settings.
	 *
	 * @since    3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}

	/**
	 * Add the preload API paths.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $paths    The paths to add.
	 * @return   array    The paths to add.
	 */
	public static function add_preload_api_paths( $paths ) {
		$paths[] = '/search-filter/v1/admin/settings?section=compatibility';
		$paths[] = '/search-filter/v1/settings?section=compatibility';
		return $paths;
	}

	/**
	 * Disable loading of Beaver Builder block editor config.
	 *
	 * We can't include this in our BB Extension plugin as it was
	 * causing our admin screens to break (not load).
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public static function beaver_builder() {

		if ( ! defined( 'FL_BUILDER_VERSION' ) ) {
			return;
		}

		// Prevent BB `render_builder_config` + `enqueue_block_editor_assets` from running.
		add_action(
			'search-filter/core/asset-loader/enqueue',
			function ( $scripts ) {
				remove_action( 'admin_footer', 'FLBuilderModuleBlocks::render_builder_config' );
				remove_action( 'enqueue_block_editor_assets', 'FLBuilderModuleBlocks::enqueue_block_editor_assets' );
				return $scripts;
			}
		);
	}
	/**
	 * Disable LiteSpeed cache for Search & Filter REST API requests.
	 *
	 * @return void
	 */
	public static function litespeed() {

		if ( ! defined( 'LSCWP_V' ) ) {
			return;
		}

		// Disable caching for Search & Filter REST API requests.
		add_action( 'search-filter/rest-api/request', array( __CLASS__, 'disable_litespeed_cache' ) );
	}

	/**
	 * Disable LiteSpeed cache for Search & Filter Pro REST API requests.
	 */
	public static function disable_litespeed_cache() {
		do_action( 'litespeed_control_set_nocache', 'Search & Filter Indexer Update' );
	}

	/**
	 * Disable SiteGround optimizer (combining inline & js files).
	 *
	 * @return void
	 */
	public static function site_ground() {

		add_filter(
			'sgo_javascript_combine_exclude',
			function ( $exclude ) {

				$all_frontend_assets = \Search_Filter\Frontend::get_registered_assets();
				foreach ( $all_frontend_assets as $asset_name => $frontend_asset ) {
					if ( ! empty( $frontend_asset['script']['src'] ) ) {
						$exclude[] = $asset_name;
					}
				}
				return $exclude;
			}
		);

		add_filter(
			'sgo_javascript_combine_excluded_inline_content',
			function ( $exclude_list ) {
				$exclude_list[] = 'window.searchAndFilter';
				$exclude_list[] = 'window.searchAndFilterData';
				$exclude_list[] = 'window.searchAndFilterApiUrl';
				$exclude_list[] = 'window.searchAndFilterPage';
				$exclude_list[] = 'window.searchAndFilterPage.body';
				return $exclude_list;
			}
		);
	}
}
