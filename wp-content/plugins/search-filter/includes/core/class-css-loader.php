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

use Search_Filter\Styles;
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


	public static function init() {
		do_action( 'search-filter/core/css-loader/init' );
	}

	/**
	 * Geenerates the CSS files from saved style settings.
	 *
	 * @since    3.0.0
	 *
	 * @return string The generated CSS.
	 */
	private static function generate( $section = '' ) {
		$css = '';

		// Uncomment this when we split the CSS into multiple files.
		/*
		if( $section !== '' && isset( self::$loaders[ $section ] ) ) {
			$css .= self::$loaders[ $section ]();
		} else { */
		foreach ( self::$loaders as $section => $handler ) {
			$css .= $handler();
		}
		// }

		$css = apply_filters( 'search-filter/core/css-loader/generate/css', $css );
		do_action( 'search-filter/core/css-loader/generate', $css );
		return $css;
	}

	public static function register_handler( $section, $handler ) {
		self::$loaders[ $section ] = $handler;
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
	 * @param array $regenerate_ids The IDs of the styles to regenerate.
	 */
	public static function save_css( $section = '' ) {
		// TODO - need to figure out if we hyphenate the function and variable names...
		$can_save = apply_filters( 'search-filter/core/css-loader/save-css/can-save', true, $section );
		if ( ! $can_save ) {
			return;
		}
		$css = self::generate( $section );
		// Stash CSS in uploads directory.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			// Load the filesystem class if its not yet available.
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$upload_dir = wp_upload_dir(); // Grab uploads folder array.
		$sf_dir     = trailingslashit( $upload_dir['basedir'] ) . 'search-filter/'; // Set storage directory path.

		global $wp_filesystem;
		// Try to init the file system and watch out for the return value of 'false' or 'null'.
		$is_filesystem_ready = WP_Filesystem();
		if ( $is_filesystem_ready !== true ) {

			if ( WP_DEBUG === true ) {
				Util::error_log( 'Search & Filter: Unable to init file system.', 'search-filter' );
			}
			return;
		}

		$filesystem = $wp_filesystem;
		$dir_exists = $filesystem->exists( $sf_dir );
		if ( ! $dir_exists ) {
			$mk_dir_result = wp_mkdir_p( $sf_dir ); // Try to create the folder.
			if ( ! $mk_dir_result ) {
				// Log error.
				if ( defined( 'WP_DEBUG' ) ) {
					if ( WP_DEBUG === true ) {
						// translators: %s is the error message.
						Util::error_log( sprintf( __( 'Unable to write folder, info: %s' ), $filesystem->errors->get_error_message() ) );
					}
				}
			} else {
				$dir_exists = true;
			}
		}

		$created_file = false;
		if ( $dir_exists ) {
			$file_permission = 0644;
			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$file_permission = FS_CHMOD_FILE;
			}
			$file_result = $filesystem->put_contents( $sf_dir . 'style.css', $css, $file_permission ); // Finally, store the file.
			if ( $file_result ) {
				// Save in an option if this method is successful.
				$created_file = true;
			} else {
				$created_file = false;
				if ( defined( 'WP_DEBUG' ) ) {
					if ( WP_DEBUG === true ) {
						// translators: %s is the error message.
						Util::error_log( sprintf( __( 'Unable to write file, info: %s' ), $filesystem->errors->get_error_message() ) );
					}
				}
			}
		}

		if ( $created_file ) {
			// All good, use the CSS file.
			self::set_mode( 'file-system' );
		} else {
			// Then we need to switch to generating inline CSS.
			self::set_mode( 'inline' );
		}

		self::set_version_id(); // Update the ID so the request won't be cached.
	}

	/**
	 * Set the CSS mode (file-system or inline).
	 *
	 * @param string $mode The mode to set - file-system or inline.
	 */
	private static function set_mode( $mode ) {
		update_option( 'search_filter_css_mode', sanitize_key( $mode ), false );
	}

	/**
	 * Updates the CSS version ID to bust the cache.
	 */
	private static function set_version_id() {
		$version_id = absint( get_option( 'search_filter_css_version_id' ) );
		++$version_id;
		// I guess we don't want this number to grow forever, so when it hits 1000 reset it.
		if ( $version_id === 1000 ) {
			$version_id = 1;
		}
		update_option( 'search_filter_css_version_id', absint( $version_id ), false );
	}
	/**
	 * Gets the CSS version.
	 *
	 * @param int $plugin_version The plugin version to be used as a fallback.
	 *
	 * @return int The CSS version.
	 */
	public static function get_version( $plugin_version = -1 ) {
		$version = 0;
		if ( 'file-system' === self::get_mode() ) {
			$version = absint( get_option( 'search_filter_css_version_id' ) );
		} elseif ( $plugin_version ) {
			$version = $plugin_version;
		}
		return $version;
	}

	/**
	 * Gets the CSS mode (file-system or inline).
	 *
	 * @return string The CSS mode.
	 */
	public static function get_mode() {
		return get_option( 'search_filter_css_mode' );
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
		if ( 'file-system' === self::get_mode() ) {
			$url = trailingslashit( self::uploads_url() ) . 'search-filter/style.css';
			return $url;
		}
	}
}
