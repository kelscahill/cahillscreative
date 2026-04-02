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
use Search_Filter_Pro\Core\Dependencies;

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
	 * @phpstan-ignore property.onlyWritten (Reserved for future extensibility)
	 */
	private $file = '';


	/**
	 * The extension ID.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 * @phpstan-ignore property.onlyWritten (Reserved for future extensibility)
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
	 * The tested up to version.
	 *
	 * @since 3.0.5
	 *
	 * @var string
	 */
	private $tested_up_to = '';

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
	 * @phpstan-ignore property.onlyWritten (Reserved for future extensibility)
	 */
	private $license = '';

	/**
	 * Whether the extension is a beta version.
	 *
	 * @since 3.0.5
	 *
	 * @var boolean
	 * @phpstan-ignore property.onlyWritten (Reserved for future extensibility)
	 */
	private $beta = false;

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 *
	 * @param string $extension_name The extension name.
	 * @param array  $args Configuration arguments.
	 *     @type string $file           The path to the main plugin file.
	 *     @type string $id             The extension ID.
	 *     @type string $version        The extension version.
	 *     @type string $license        The extension license.
	 *     @type boolean $beta          Whether the extension is a beta version.
	 * }
	 */
	public function __construct( $extension_name, $args ) {
		$defaults           = array(
			'file'         => '',
			'id'           => '',
			'version'      => '',
			'license'      => 'search-filter-extension-free',
			'beta'         => false,
			'tested_up_to' => '',
		);
		$args               = wp_parse_args( $args, $defaults );
		$this->name         = $extension_name;
		$this->file         = $args['file'];
		$this->id           = $args['id'];
		$this->version      = $args['version'];
		$this->license      = $args['license'];
		$this->beta         = $args['beta'];
		$this->tested_up_to = $args['tested_up_to'];

		/*
		 * Extensions can trigger adding their registration, which uses
		 * the base plugins Options class, bypassing our required version
		 * check on plugin init.
		 *
		 * Return early if we can see that we don't have the required version.
		 */
		if ( ! Dependencies::is_search_filter_required_version() ) {
			return;
		}

		$known_version = Options::get( 'extension-' . $this->name . '_version' );
		if ( $known_version ) {
			$this->registered_version = $known_version;
		}
	}

	/**
	 * Checks whether the extension has upgraded.
	 *
	 * IE - when the current version of the extension is newer than the one stored in our database.
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public function has_upgraded() {
		// Return early if we can see that we don't have the required version.
		if ( ! Dependencies::is_search_filter_required_version() ) {
			return false;
		}

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
	 * Checks whether the extension is compatible with the current version of Search & Filter Pro.
	 *
	 * @since 3.0.5
	 *
	 * @return bool
	 */
	public function requirements_met() {

		// Return early if we can see that we don't have the required version.
		if ( ! Dependencies::is_search_filter_required_version() ) {
			return false;
		}

		/*
		 * This can't be automatic, and will be a manual process each time we update.
		 * Just because an extension might not have a high tested upto version, doesn't necessarily
		 * means its not compatible.
		 *
		 * Used for major upgrades to prevent things breaking allowing us to disable extensions until
		 * they've been updated.
		 *
		 * This function must be implemented in an extension directly to prevent itself from loading.
		 */

		// We're seeing an old extension that doesn't have a tested upto version.
		if ( empty( $this->tested_up_to ) ) {
			return false;
		}

		return version_compare( SEARCH_FILTER_PRO_EXTENSION_REQUIRES_VERSION, $this->tested_up_to, '<=' );
	}

	/**
	 * Upgrades the extension.
	 *
	 * @since 3.0.5
	 */
	public function upgrade() {
		// Return early if we can see that we don't have the required version.
		if ( ! Dependencies::is_search_filter_required_version() ) {
			return;
		}

		Options::update( 'extension-' . $this->name . '_version', $this->version );
	}
}
