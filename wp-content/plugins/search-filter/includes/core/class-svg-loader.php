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
 * A class for handling loading of SVG files
 *
 * SVGs are reqeusted by svg name, and they are added before the closing body tag
 * of a page.  We then use SVGs throughout the plugin using SVG -> link using
 * their ID - its a pretty efficient way to load the assests, but I've also read
 * its more performant in the browser than using a full SVG with its contents
 * (need to double check the source for this)
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */
class SVG_Loader {

	/**
	 * Associative array of SVGs that have been registered.
	 *
	 * @var array
	 */
	private static $registered_svgs = array();
	/**
	 * Stores which SVGs should be loaded.
	 *
	 * @var array
	 */
	private static $svgs_to_load = array();
	/**
	 * Once an SVG has been loaded, it will be added here to avoid duplicate loading.
	 *
	 * @var array
	 */
	private static $svgs_loaded = array();

	/**
	 * Register an SVG to be loaded.
	 *
	 * @param string $name The internal name of the SVG.
	 * @param string $path The path to the SVG file.
	 */
	public static function register( $name, $path ) {
		self::$registered_svgs[ $name ] = $path;
	}

	public static function is_registered( $name ) {
		return isset( self::$registered_svgs[ $name ] );
	}

	public static function enqueue( $svg_name ) {
		if ( ! in_array( $svg_name, self::$svgs_loaded, true ) && ! in_array( $svg_name, self::$svgs_to_load, true ) ) {
			array_push( self::$svgs_to_load, $svg_name );
		}
	}
	public static function enqueue_array( $svgs ) {
		// We don't want to output during an ajax request or rest request (but no way to detect this currently).
		if ( wp_doing_ajax() ) {
			return;
		}
		// Loop through, and only load the ones not yet loaded ( we can't load multiple times, they have unique IDs ).
		foreach ( $svgs as $svg_name ) {
			if ( ! in_array( $svg_name, self::$svgs_loaded, true ) && ! in_array( $svg_name, self::$svgs_to_load, true ) ) {
				array_push( self::$svgs_to_load, $svg_name );
			}
		}
	}
	/**
	 * Get SVGs to load.
	 *
	 * @return array
	 */
	public static function get_svgs_to_load() {
		return self::$svgs_to_load;
	}

	/**
	 * Reset the queue of SVGs to be loaded.
	 *
	 * @return void
	 */
	public static function reset_svgs_to_load() {
		self::$svgs_to_load = array();
	}

	/**
	 * Prints the SVGs and resets the arrays.
	 *
	 * Should be used in the footer where we can add inline scripts / templates.
	 *
	 * @param boolean $return Whether to return the output or print it.
	 * @return string The output if $return is true.
	 */
	public static function output( $return = false ) {
		// Return if empty.
		if ( empty( self::$svgs_to_load ) ) {
			if ( $return ) {
				return '';
			}
			return;
		}
		ob_start();
		// Now we have some to load, so include + hide them - use inline display to prevent flicker.
		// TODO - put style back to display: none; when chrome bug is fixed.
		$styles = 'clip: rect(1px, 1px, 1px, 1px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute;	width: 1px !important; word-wrap: normal !important;';
		echo '<div id="search-filter-svg-template" aria-hidden="true" style="' . esc_attr( $styles ) . '">';
		foreach ( self::$svgs_to_load as $svg_name ) {
			$path = self::$registered_svgs[ $svg_name ];
			if ( file_exists( $path ) ) {
				include $path;
				array_push( self::$svgs_loaded, $svg_name );
			}
		}
		echo '</div>';
		$output = ob_get_clean();

		// Reset the queue.
		self::reset_svgs_to_load();

		if ( $return ) {
			return $output;
		}
		echo $output;
	}
}
