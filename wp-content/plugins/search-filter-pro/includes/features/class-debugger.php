<?php
/**
 * Sets up the support for the shortcode features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter_Pro\Features;

use Search_Filter\Features;
use Search_Filter\Debugger\Settings as Debugger_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shortcode functionality for the plugin.
 */
class Debugger {

	/**
	 * Initialize the debugger features.
	 *
	 * @since 3.2.0
	 */
	public static function init() {
		// Setup the debugger features once features are initialized.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'setup' ), 10 );
	}

	/**
	 * Setup the debugger features.
	 *
	 * @since 3.2.0
	 */
	public static function setup() {

		// Check to make sure the shortcodes feature is enabled.
		if ( ! Features::is_enabled( 'debugMode' ) ) {
			return;
		}

		// Add the debugging settings.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'add_debugging_settings' ), 2 );
	}

	/**
	 * Add additional debugging settings.
	 *
	 * @since 3.2.0
	 */
	public static function add_debugging_settings() {
		// Placeholder for future debugging settings.
	}
}
