<?php
/**
 * Handles the loading and generation of CSS for the plugin.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter\Core;

use Search_Filter\Options;
use Search_Filter\Util;

/**
 * Fired during plugin activation
 *
 * @link       http://codeamp.com
 * @since      1.0.0
 *
 * @package    Custom_Layouts
 * @subpackage Custom_Layouts/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the loading and generation of CSS for the plugin.
 */
class CSS_Loader {


	/**
	 * An array of CSS loaders.  A loader is a function that returns CSS.
	 *
	 * @var array
	 */
	private static $loaders = array();

	/**
	 * Whether CSS regeneration is already queued for this request.
	 *
	 * @since 3.0.0
	 * @var bool
	 */
	private static $regeneration_queued = false;

	/**
	 * Initializes the CSS loader.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		do_action( 'search-filter/core/css-loader/init' );

		// Preload the asset version option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );
	}

	/**
	 * Preload the css version option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		// Preload and set the default in case it doesn't exist.
		$options_to_preload[] = array( 'css-mode', 'file-system' );
		$options_to_preload[] = array( 'css-version-id', 0 );
		return $options_to_preload;
	}

	/**
	 * Geenerates the CSS files from saved style settings.
	 *
	 * @since    3.0.0
	 *
	 * @param string $section The section to generate CSS for.
	 * @return string The generated CSS.
	 */
	private static function generate( $section = '' ) {
		$css = '';

		/*
		 * Uncomment this when we split the CSS into multiple files.
		 * if( $section !== '' && isset( self::$loaders[ $section ] ) ) {
		 *     $css .= self::$loaders[ $section ]();
		 * } else {
		 */
		foreach ( self::$loaders as $section => $handler ) {
			$css .= $handler();
		}
		// }

		$css = apply_filters( 'search-filter/core/css-loader/generate/css', $css );
		do_action( 'search-filter/core/css-loader/generate', $css );
		return $css;
	}

	/**
	 * Registers a CSS handler for a section.
	 *
	 * @since 3.0.0
	 *
	 * @param string   $section The section name.
	 * @param callable $handler The handler function that returns CSS.
	 */
	public static function register_handler( $section, $handler ) {
		self::$loaders[ $section ] = $handler;
	}

	/**
	 * Checks if a handler is registered for a section.
	 *
	 * @since 3.0.0
	 *
	 * @param string $section The section name.
	 * @return bool True if handler exists, false otherwise.
	 */
	public static function has_handler( $section ) {
		return isset( self::$loaders[ $section ] );
	}

	/**
	 * Removes a CSS handler for a section.
	 *
	 * @since 3.0.0
	 *
	 * @param string $section The section name.
	 */
	public static function remove_handler( $section ) {
		unset( self::$loaders[ $section ] );
	}

	/**
	 * Reset the CSS loader.
	 *
	 * Clears all registered CSS handlers.
	 *
	 * @since 3.0.0
	 */
	public static function reset() {
		self::$loaders             = array();
		self::$regeneration_queued = false;
	}
	/**
	 * Cleas the CSS from scripts and markup.
	 *
	 * @param string $css The CSS to clean.
	 * @return string The cleaned CSS.
	 */
	public static function clean_css( $css ) {
		$css = wp_strip_all_tags( $css );
		$css = preg_replace( '/\/\*((?!\*\/).)*\*\//', '', $css );
		$css = preg_replace( '/\s{2,}/', ' ', $css );
		$css = preg_replace( '/\s*([:;{}])\s*/', '$1', $css );
		$css = preg_replace( '/;}/', '}', $css );
		return $css;
	}

	/**
	 * Saves and generates CSS, to a file if possible.
	 *
	 * If not possible, changes the CSS mode of the plugin.
	 *
	 * @param string $section The section to save the CSS for.
	 */
	public static function save_css( string $section = '' ) {
		// TODO - need to figure out if we hyphenate the function and variable names...
		$can_save = apply_filters( 'search-filter/core/css-loader/save-css/can-save', true, $section );
		if ( ! $can_save ) {
			return;
		}
		$css = self::generate( $section );
		// Stash CSS in uploads directory.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			// Load the filesystem class if its not yet available.
			$file_path = ABSPATH . 'wp-admin/includes/file.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
		$upload_dir = wp_upload_dir(); // Grab uploads folder array.
		$sf_dir     = trailingslashit( $upload_dir['basedir'] ) . 'search-filter/'; // Set storage directory path.

		// Try to create the folder if it doesn't exist.
		if ( ! is_dir( $sf_dir ) ) {
			$mk_dir_result = wp_mkdir_p( $sf_dir ); // Try to create the folder.
			if ( ! $mk_dir_result ) {
				// Log error.
				// translators: %s is the error message.
				Util::error_log( __( 'Unable to write folder `search-filter` in `uploads` directory.' ), 'error' );
				self::set_mode( 'inline' );
				return;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Using file_put_contents for better compatibility with various hosting environments.
		$file_result = file_put_contents( $sf_dir . 'style.css', $css, LOCK_EX ); // Finally, store the file with exclusive lock.
		if ( $file_result !== false ) {
			// Success.
			self::set_mode( 'file-system' );
			self::set_version_id(); // Update the ID so the request won't be cached.
			return;
		}

		// Failed.
		// translators: %s is the error message.
		Util::error_log( __( 'Unable to write file with `file_put_contents`.' ), 'error' );
		self::set_mode( 'inline' );
	}

	/**
	 * Queue CSS regeneration on shutdown.
	 *
	 * Sets a dirty flag in the database and registers a shutdown callback.
	 * Multiple calls in the same request only register once.
	 * Multiple concurrent requests coordinate via the dirty flag.
	 *
	 * @since 3.0.0
	 */
	public static function queue_regeneration() {
		if ( self::$regeneration_queued ) {
			return; // Already queued for this request.
		}
		self::$regeneration_queued = true;

		// Set dirty flag in database (cross-process coordination).
		Options::update( 'css-needs-regeneration', 'yes' );

		// Register shutdown callback.
		Async::register_callback( array( __CLASS__, 'maybe_regenerate_css' ) );
	}

	/**
	 * Conditionally regenerate CSS after delay.
	 *
	 * Waits 2 seconds to let concurrent requests settle, then checks
	 * the dirty flag with a fresh database read. If still dirty,
	 * clears the flag and regenerates CSS.
	 *
	 * @since 3.0.0
	 */
	public static function maybe_regenerate_css() {
		// Delay to let concurrent requests settle.
		sleep( 2 );

		// Fresh read bypassing Options cache.
		$dirty_value = Options::get_direct( 'css-needs-regeneration' );

		if ( ! $dirty_value || $dirty_value !== 'yes' ) {
			return; // Already handled by another process.
		}

		// Clear flag BEFORE generating (new saves will re-set it).
		Options::update( 'css-needs-regeneration', 'no' );

		// Generate and save CSS.
		self::save_css();
	}

	/**
	 * Set the CSS mode (file-system or inline).
	 *
	 * @param string $mode The mode to set - file-system or inline.
	 */
	private static function set_mode( string $mode ) {
		Options::update( 'css-mode', sanitize_key( $mode ) );
	}

	/**
	 * Updates the CSS version ID to bust the cache.
	 */
	private static function set_version_id() {
		$version_id = absint( Options::get( 'css-version-id', 0 ) );
		++$version_id;
		// I guess we don't want this number to grow forever, so when it hits 1000 reset it.
		if ( $version_id === 1000 ) {
			$version_id = 1;
		}
		Options::update( 'css-version-id', $version_id );
	}

	/**
	 * Gets the CSS version ID.
	 *
	 * @return string The CSS version ID.
	 */
	public static function get_version_id() {
		return Options::get( 'css-version-id', 0 );
	}

	/**
	 * Gets the CSS version.
	 *
	 * @return string The CSS version.
	 */
	public static function get_version() {
		if ( self::get_mode() === 'file-system' ) {
			$version = self::get_version_id();
			if ( empty( $version ) ) {
				$version = '0';
			}
			return $version;
		}
		return SEARCH_FILTER_VERSION;
	}

	/**
	 * Gets the CSS mode (file-system or inline).
	 *
	 * @return string The CSS mode.
	 */
	public static function get_mode() {
		return Options::get( 'css-mode' );
	}

	/**
	 * Returns a url to the static CSS file, or url to an ajax action for generating
	 * the CSS on the fly
	 *
	 * @since    1.0.0
	 */
	private static function uploads_url() {
		$upload_dir = wp_get_upload_dir();
		$upload_url = $upload_dir['baseurl'];
		if ( is_ssl() ) {
			return str_replace( 'http://', 'https://', $upload_url );
		}
		return str_replace( 'https://', 'http://', $upload_url );
	}

	/**
	 * Returns the generated CSS file URL.
	 *
	 * @return string The CSS file URL.
	 */
	public static function get_css_url() {
		$url = trailingslashit( self::uploads_url() ) . 'search-filter/style.css';
		return $url;
	}
}
