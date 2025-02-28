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
	 * Track whether we've already declared the global `window.searchAndFilter` object.
	 */
	private static $has_init_global = false;

	/**
	 * Track which views have already been registered.
	 */
	private static $script_views = array();

	/**
	 * If the global has not been init yet, then init it.
	 */
	public static function output_init_js( $return_only = false ) {
		if ( self::has_init_global() ) {
			if ( $return_only ) {
				return '';
			}
			return;
		}
		self::$has_init_global = true;

		/*
		 * We need to check if the global has already been init before setting it.
		 * Usually we wouldn't need to do this, but in Beaver Builder with the way
		 * it does its previews (reloads part of the page but not all of it).
		 *
		 * It might be useful in other scenarios too, so leave it in for now.
		 */
		ob_start();
		?>
		<script type="text/javascript">
		if ( ! Object.hasOwn( window, 'searchAndFilter' ) ) {
			window.searchAndFilter = {};
		}
		</script>
		<?php
		$js = trim( ob_get_clean() );

		if ( $return_only ) {
			return $js;
		}

		echo $js;
	}

	public static function has_init_global() {
		return self::$has_init_global;
	}
	/**
	 * If the global has not been init yet, then init it.
	 */
	public static function init_view_js() {
		ob_start();
		if ( ! self::$has_init_global ) {
			self::$has_init_global = true;
			?>
			window.searchAndFilter = {};
			<?php
		}
		return trim( ob_get_clean() );
	}

	/**
	 * Attach a global variables to the window object and add them inline to a script handle.
	 *
	 * They will always be attached to the `window.searchAndFilter` object then under the name of the view.
	 *
	 * @param string $script_handle The name of the script to attach the global to.
	 * @param string $view          The name of the global variable.
	 * @param array  $data          The data to attach to the global.
	 * @param string $position      The position of the inline script in relation the the handle.
	 */
	public static function attach_globals( $script_handle, $view, $data, $position = 'before' ) {
		// Don't load the same script handle / view twice as we create the variable and don't want to overwrite it.
		// This helps us out when we call the frontend register scripts more than once, but we should
		// probably figure out a better way to ensure uniqueness.
		if ( isset( self::$script_views[ $script_handle ], self::$script_views[ $script_handle ][ $view ] ) ) {
			return;
		}
		$js = self::get_globals( $view, $data );
		wp_add_inline_script( $script_handle, $js, $position );

		// Store the data.
		if ( ! isset( self::$script_views[ $view ] ) ) {
			self::$script_views[ $view ] = array();
		}
		self::$script_views[ $script_handle ][ $view ] = $data;
	}

	public static function get_globals( $view, $data ) {
		// Don't load the same script handle / view twice as we create the variable and don't want to overwrite it.
		// This helps us out when we call the frontend register scripts more than once, but we should
		// probably figure out a better way to ensure uniqueness.
		ob_start();
		?>
		window.searchAndFilter.<?php echo sanitize_key( $view ); ?> = <?php echo wp_json_encode( $data ); ?>;
		<?php
		$js = trim( ob_get_clean() );
		return $js;
	}

	/**
	 * Gets the plugins assets URL path.
	 *
	 * @return string The URL path to the assets.
	 */
	public static function get_admin_assets_url() {
		$assets_url = SEARCH_FILTER_URL . 'assets/';
		if ( defined( 'SEARCH_FILTER_ADMIN_ASSETS_URL' ) ) {
			$assets_url = SEARCH_FILTER_ADMIN_ASSETS_URL;
		}
		return $assets_url;
	}
	/**
	 * Gets the plugins frontend assets URL path.
	 *
	 * @return string The URL path to the assets.
	 */
	public static function get_frontend_assets_url() {
		$assets_url = SEARCH_FILTER_URL . 'assets/';
		if ( defined( 'SEARCH_FILTER_FRONTEND_ASSETS_URL' ) ) {
			$assets_url = SEARCH_FILTER_FRONTEND_ASSETS_URL;
		}
		return $assets_url;
	}
	/**
	 * Preload the API requests.
	 *
	 * @param array $preload_paths The paths to preload.
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
			// If the key starts with '/search-filter/v1/settings/options/`
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
				'window.searchAndFilter.admin.preload.routeData =  ' . wp_json_encode( $preloaded_settings_data ),
				'after'
			);
		}
	}
}
