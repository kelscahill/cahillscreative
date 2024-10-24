<?php
/**
 *  Define the main autoloader
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

spl_autoload_register( 'search_filter_pro_autoloader' );

/**
 * PHP auto loader function
 *
 * @param string $class_name  PHP Class name with namespace.
 * @return void
 */
function search_filter_pro_autoloader( $class_name ) {
	$parent_namespace = 'Search_Filter_Pro';
	if ( false !== strpos( $class_name, $parent_namespace ) ) {
		$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

		// Project namespace & length.
		$project_namespace = $parent_namespace . '\\';
		$length            = strlen( $project_namespace );

		// Remove top level namespace (that is the current dir).
		$class_file = substr( $class_name, $length );
		// Swap underscores for dashes and lowercase.
		$class_file = str_replace( '_', '-', strtolower( $class_file ) );

		// Prepend `class-` to the filename (last class part).
		$class_parts                = explode( '\\', $class_file );
		$last_index                 = count( $class_parts ) - 1;
		$class_parts[ $last_index ] = 'class-' . $class_parts[ $last_index ];

		// Join everything back together and add the file extension.
		$class_file = implode( DIRECTORY_SEPARATOR, $class_parts ) . '.php';
		$location   = $classes_dir . $class_file;

		if ( ! is_file( $location ) ) {
			return;
		}

		require_once $location;
	}
}
