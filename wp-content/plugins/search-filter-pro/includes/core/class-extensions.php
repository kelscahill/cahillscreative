<?php
/**
 * Fired during plugin activation
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/Core
 */

namespace Search_Filter_Pro\Core;

use Search_Filter\Options;
use Search_Filter_Pro\Core\Extensions\Extension;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage updates for external extensions.
 *
 * The idea is we keep the update checks in sync with the main plugin
 * so when there is a new release, we know if the extensions also need
 * and update and can be updated together.
 */
class Extensions {

	/**
	 * Array of installed extensions.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $registered_extensions = array();


	/**
	 * Init
	 */
	public static function init() {}

	/**
	 * Add an extension.
	 *
	 * @since 3.0.0
	 *
	 * @param string $extension_name The extension name.
	 * @param array  $args {
	 *     The extension arguments.
	 *
	 *     @type string $id             The extension item ID.
	 *     @type string $version        The extension version.
	 *     @type string $file           The extension file.
	 *     @type string $license        The extension license.
	 *     @type bool   $beta           Whether the extension is a beta version.
	 * }
	 */
	public static function add( $extension_name, $args ) {
		$defaults = array(
			'file'    => '',
			'id'      => '',
			'version' => '',
			'license' => 'search-filter-extension-free',
			'beta'    => false,
		);
		$args     = wp_parse_args( $args, $defaults );
		self::$registered_extensions[ $extension_name ] = new Extension( $extension_name, $args );
		Update_Manager::add( $args );

	}

	/**
	 * Get the installed extension by name.
	 *
	 * @since 3.0.0
	 *
	 * @return Extension|false
	 */
	public static function get( $extension_name ) {
		if ( ! isset( self::$registered_extensions[ $extension_name ] ) ) {
			return false;
		}
		return self::$registered_extensions[ $extension_name ];
	}

	/**
	 * Check if the extension was upgraded - if the current version is higher
	 * than the one stored in the database.
	 *
	 * @since 3.0.5
	 *
	 * @param string $extension_name The extension name.
	 * @param string $active_version The active version.
	 * @return boolean
	 */
	public static function has_upgraded( $extension_name, $active_version ) {
		$database_version = Options::get_option_value( 'extension-' . $extension_name . '_version' );
		if ( ! $database_version ) {
			return true;
		}
		return version_compare( $database_version, $active_version, '<' );
	}


	/**
	 * Get the registered extensions.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_registered_extensions() {
		return self::$registered_extensions;
	}
}
