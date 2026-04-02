<?php
/**
 * Scripts Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A Scripts utility class to be used by both frontend and admin.
 */
class Scripts {

	/**
	 * Preload the API requests.
	 *
	 * @param array  $preload_paths The paths to preload.
	 * @param string $script_handle The script handle to add the preload data to.
	 */
	public static function preload_api_requests( $preload_paths, $script_handle = 'search-filter-admin' ) {
		/* Copied from core - wp-includes/block-editor.php */

		// Restore the global $post as it was before API preloading.
		// Preload common data.
		global $post, $wp_scripts, $wp_styles, $post_id;

		/*
		* Ensure the global $post, $wp_scripts, and $wp_styles remain the same after
		* API data is preloaded.
		* Because API preloading can call the_content and other filters, plugins
		* can unexpectedly modify the global $post or enqueue assets which are not
		* intended for the block editor.
		*/
		$backup_global_post = ! empty( $post ) ? clone $post : $post;
		$backup_wp_scripts  = ! empty( $wp_scripts ) ? clone $wp_scripts : $wp_scripts;
		$backup_wp_styles   = ! empty( $wp_styles ) ? clone $wp_styles : $wp_styles;
		$backup_post_id     = $post_id;

		$all_preload_data = array_reduce(
			$preload_paths,
			'rest_preload_api_request',
			array()
		);

		// Split the data into regular preload data (the WP way) and our settings data, for the
		// processed settings.
		$preload_data            = array();
		$preloaded_settings_data = array();

		foreach ( $all_preload_data as $key => $value ) {
			// If the key starts with '/search-filter/v1/settings/options/'.
			if ( strpos( $key, '/search-filter/v1/settings/options/' ) === 0 ) {
				// Don't an apiFetch middleware to preload this data.
				$preloaded_settings_data[ $key ] = $value;
			} else {
				// All other data should go through the middleware as usual.
				$preload_data[ $key ] = $value;
			}
		}

		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = $backup_global_post;
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_scripts = $backup_wp_scripts;
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_styles = $backup_wp_styles;
		//phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post_id = $backup_post_id;

		if ( ! empty( $preload_data ) ) {
			wp_add_inline_script(
				$script_handle,
				sprintf(
					'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
					wp_json_encode( $preload_data )
				),
				'after'
			);
		}

		if ( ! empty( $preloaded_settings_data ) ) {
			wp_add_inline_script(
				$script_handle,
				'window.searchAndFilter.admin.registry.register(
					[ "preload" ], "routeData",
					' . wp_json_encode( $preloaded_settings_data ) . '
				);',
				'after'
			);
		}
	}

	/**
	 * Stub to prevent errors via extensions.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public static function output_init_js() {
		$message = 'Using deprecated method `Search_Filter\Core\Scripts::output_init_js()` (since 3.2.0). Update Search & Filter and extensions to the latest version to remove this warning.';
		Deprecations::add( $message );
	}
	/**
	 * Stub to prevent errors via extensions.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public static function get_globals() {
		$message = 'Using deprecated method `Search_Filter\Core\Scripts::get_globals()` (since 3.2.0). Update Search & Filter and extensions to the latest version to remove this warning.';
		Deprecations::add( $message );
	}
}
