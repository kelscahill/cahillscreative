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

namespace Search_Filter_Pro\Core\Extensions;

use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instance of the extension class.
 */
class Extension {

	/**
	 * The extension name.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * Path to the main plugin file.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $file = '';


	/**
	 * The extension ID.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $id = '';

	/**
	 * The extension version.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * The extension version registered in the database.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $registered_version = '';

	/**
	 * The extension license.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $license = '';

	/**
	 * Whether the extension is a beta version.
	 *
	 * @since 3.0.5
	 *
	 * @var boolean
	 */
	private $beta = false;

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 *
	 * @param string $extension_name The extension name.
	 * @param array  $args {
	 *     @type string $file           The path to the main plugin file.
	 *     @type string $id             The extension ID.
	 *     @type string $version        The extension version.
	 *     @type string $license        The extension license.
	 *     @type boolean $beta          Whether the extension is a beta version.
	 * }
	 */
	public function __construct( $extension_name, $args ) {
		$defaults      = array(
			'file'    => '',
			'id'      => '',
			'version' => '',
			'license' => 'search-filter-extension-free',
			'beta'    => false,
		);
		$args          = wp_parse_args( $args, $defaults );
		$this->name    = $extension_name;
		$this->file    = $args['file'];
		$this->id      = $args['id'];
		$this->version = $args['version'];
		$this->license = $args['license'];
		$this->beta    = $args['beta'];

		$known_version = Options::get_option_value( 'extension-' . $this->name . '_version' );
		if ( $known_version ) {
			$this->registered_version = $known_version;
		}
	}

	/**
	 * Checks whether the extension was upgrade
	 *
	 * When the database version is older than the active version.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public function has_upgraded() {

		if ( empty( $this->registered_version ) ) {
			return true;
		}

		return version_compare( $this->registered_version, $this->version, '<' );
	}

	/**
	 * Gets the registered version.
	 *
	 * @since 3.0.5
	 *
	 * @return string
	 */
	public function get_registered_version() {
		return $this->registered_version;
	}

	/**
	 * Upgrades the extension.
	 *
	 * @since 3.0.5
	 *
	 * @return string
	 */
	public function upgrade() {
		Options::update_option_value( 'extension-' . $this->name . '_version', $this->version );
	}
}
