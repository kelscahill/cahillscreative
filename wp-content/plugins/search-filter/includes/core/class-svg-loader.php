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
	 * @param bool   $user Whether this is a user-uploaded SVG requiring sanitization.
	 */
	public static function register( $name, $path, $user = true ) {
		self::$registered_svgs[ $name ] = array(
			'path' => $path,
			'user' => $user,
		);
	}

	/**
	 * Checks if an SVG is registered.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The SVG name.
	 * @return bool True if registered, false otherwise.
	 */
	public static function is_registered( $name ) {
		return isset( self::$registered_svgs[ $name ] );
	}

	/**
	 * Checks if an SVG is enqueued.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The SVG name.
	 * @return bool True if enqueued, false otherwise.
	 */
	public static function is_enqueued( $name ) {
		return in_array( $name, self::$svgs_to_load, true );
	}

	/**
	 * Enqueues an SVG for loading.
	 *
	 * @since 3.0.0
	 *
	 * @param string $svg_name The SVG name to enqueue.
	 */
	public static function enqueue( $svg_name ) {
		if ( ! in_array( $svg_name, self::$svgs_loaded, true ) && ! in_array( $svg_name, self::$svgs_to_load, true ) ) {
			array_push( self::$svgs_to_load, $svg_name );
		}
	}

	/**
	 * Enqueues multiple SVGs for loading.
	 *
	 * @since 3.0.0
	 *
	 * @param array $svgs Array of SVG names to enqueue.
	 */
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
	 * Flush the queue of SVGs to be loaded.
	 *
	 * @return void
	 */
	public static function flush_queue() {
		self::$svgs_to_load = array();
	}

	/**
	 * Prints the SVGs and resets the arrays.
	 *
	 * Should be used in the footer where we can add inline scripts / templates.
	 */
	public static function output() {
		// Return if empty.
		if ( empty( self::$svgs_to_load ) ) {
			return;
		}
		ob_start();
		// Now we have some to load, so include + hide them - use inline display to prevent flicker.
		// TODO - put style back to display: none; when chrome bug is fixed.
		$styles = 'clip: rect(1px, 1px, 1px, 1px); clip-path: inset(50%); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute;	width: 1px !important; word-wrap: normal !important;';
		echo '<div id="search-filter-svg-template" aria-hidden="true" style="' . esc_attr( $styles ) . '">';
		foreach ( self::$svgs_to_load as $svg_name ) {
			$svg  = self::$registered_svgs[ $svg_name ];
			$path = $svg['path'];
			$user = $svg['user'];

			if ( file_exists( $path ) ) {
				if ( ! $user ) {
					// Don't santize or add ID to non ugc svgs.
					include $path;
					continue;
				}
				// If its a user SVG we need to run it through wp_kses it and add the ID.
				ob_start();
				include $path;
				$svg_output = ob_get_clean();

				// Remove XML declaration if present.
				$svg_output = preg_replace( '/^\s*<\?xml[^>]*>\s*/', '', $svg_output );

				// Use WordPress HTML Tag Processor to add/update the ID.
				if ( class_exists( '\WP_HTML_Tag_Processor' ) ) {
					$processor = new \WP_HTML_Tag_Processor( $svg_output );
					if ( $processor->next_tag( array( 'tag_name' => 'svg' ) ) ) {
						$processor->set_attribute( 'id', 'sf-svg-' . $svg_name );
						$svg_output = $processor->get_updated_html();
					}
				}

				// Sanitize SVG output, allowing all SVG tags and attributes.
				echo wp_kses( $svg_output, self::get_allowed_svg_tags() );
				array_push( self::$svgs_loaded, $svg_name );
			}
		}
		echo '</div>';
		$output = ob_get_clean();

		// Flush the queue.
		self::flush_queue();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content is sanitized before buffering.
		echo $output;
	}

	/**
	 * Gets the list of allowed SVG tags and attributes for sanitization.
	 *
	 * @since 3.0.0
	 *
	 * @return array Array of allowed SVG tags and attributes.
	 */
	public static function get_allowed_svg_tags() {
		$allowed_svg_tags = array(
			'svg'                 => array(
				'class'       => true,
				'id'          => true,
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewBox'     => true,
				'fill'        => true,
				'stroke'      => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'role'        => true,
				'style'       => true,
				'version'     => true,
				'xml:space'   => true,
			),
			'g'                   => array(
				'class'     => true,
				'id'        => true,
				'fill'      => true,
				'stroke'    => true,
				'style'     => true,
				'transform' => true,
			),
			'path'                => array(
				'd'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
				'transform'    => true,
			),
			'circle'              => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'rect'                => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'polygon'             => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'polyline'            => array(
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'ellipse'             => array(
				'cx'           => true,
				'cy'           => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'line'                => array(
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
			),
			'title'               => array(),
			'desc'                => array(),
			'use'                 => array(
				'xlink:href' => true,
				'href'       => true,
				'class'      => true,
				'id'         => true,
				'style'      => true,
			),
			'defs'                => array(),
			'linearGradient'      => array(
				'id'            => true,
				'x1'            => true,
				'y1'            => true,
				'x2'            => true,
				'y2'            => true,
				'gradientUnits' => true,
				'class'         => true,
				'style'         => true,
			),
			'stop'                => array(
				'offset'       => true,
				'stop-color'   => true,
				'stop-opacity' => true,
				'class'        => true,
				'style'        => true,
			),
			'filter'              => array(
				'id'          => true,
				'x'           => true,
				'y'           => true,
				'width'       => true,
				'height'      => true,
				'filterUnits' => true,
				'class'       => true,
				'style'       => true,
			),
			'feGaussianBlur'      => array(
				'in'           => true,
				'stdDeviation' => true,
				'class'        => true,
				'style'        => true,
			),
			'feOffset'            => array(
				'in'    => true,
				'dx'    => true,
				'dy'    => true,
				'class' => true,
				'style' => true,
			),
			'feBlend'             => array(
				'in'    => true,
				'in2'   => true,
				'mode'  => true,
				'class' => true,
				'style' => true,
			),
			'feColorMatrix'       => array(
				'in'     => true,
				'type'   => true,
				'values' => true,
				'class'  => true,
				'style'  => true,
			),
			'feMerge'             => array(),
			'feMergeNode'         => array(
				'in'    => true,
				'class' => true,
				'style' => true,
			),
			'feComponentTransfer' => array(),
			'feFuncR'             => array(
				'type'      => true,
				'slope'     => true,
				'intercept' => true,
				'class'     => true,
				'style'     => true,
			),
			'feFuncG'             => array(
				'type'      => true,
				'slope'     => true,
				'intercept' => true,
				'class'     => true,
				'style'     => true,
			),
			'feFuncB'             => array(
				'type'      => true,
				'slope'     => true,
				'intercept' => true,
				'class'     => true,
				'style'     => true,
			),
			'feFuncA'             => array(
				'type'      => true,
				'slope'     => true,
				'intercept' => true,
				'class'     => true,
				'style'     => true,
			),
			'metadata'            => array(),
			'symbol'              => array(
				'id'      => true,
				'viewBox' => true,
				'class'   => true,
				'style'   => true,
			),
			'image'               => array(
				'xlink:href'          => true,
				'href'                => true,
				'x'                   => true,
				'y'                   => true,
				'width'               => true,
				'height'              => true,
				'preserveAspectRatio' => true,
				'class'               => true,
				'id'                  => true,
				'style'               => true,
			),
			'style'               => array(),
			'animate'             => array(
				'attributeName' => true,
				'from'          => true,
				'to'            => true,
				'dur'           => true,
				'repeatCount'   => true,
				'class'         => true,
				'id'            => true,
				'style'         => true,
			),
			'animateTransform'    => array(
				'attributeName' => true,
				'type'          => true,
				'from'          => true,
				'to'            => true,
				'dur'           => true,
				'repeatCount'   => true,
				'class'         => true,
				'id'            => true,
				'style'         => true,
			),
			'marker'              => array(
				'id'           => true,
				'markerWidth'  => true,
				'markerHeight' => true,
				'refX'         => true,
				'refY'         => true,
				'orient'       => true,
				'class'        => true,
				'style'        => true,
			),
			'text'                => array(
				'x'            => true,
				'y'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'id'           => true,
				'style'        => true,
				'font-size'    => true,
				'font-family'  => true,
				'text-anchor'  => true,
			),
			'tspan'               => array(
				'x'           => true,
				'y'           => true,
				'fill'        => true,
				'stroke'      => true,
				'class'       => true,
				'id'          => true,
				'style'       => true,
				'font-size'   => true,
				'font-family' => true,
				'text-anchor' => true,
			),
		);

		return $allowed_svg_tags;
	}
}
