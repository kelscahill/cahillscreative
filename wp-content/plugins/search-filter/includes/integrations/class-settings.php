<?php
/**
 * Integrations settings for admin screens.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */

namespace Search_Filter\Integrations;

use Search_Filter\Core\Dependants;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settings for integrations.
 */
class Settings extends \Search_Filter\Settings\Section_Base {

	/**
	 * The source settings before they have been processed.
	 *
	 * @var array
	 */
	protected static $source_settings = array();

	/**
	 * The prepared settings.
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * The settings order.
	 *
	 * @var array
	 */
	protected static $settings_order = array();

	/**
	 * The source groups.
	 *
	 * @var array
	 */
	protected static $source_groups = array();

	/**
	 * The prepared groups.
	 *
	 * @var array
	 */
	protected static $groups = array();

	/**
	 * The groups order.
	 *
	 * @var array
	 */
	protected static $groups_order = array();

	/**
	 * The setting section name
	 */
	protected static $section = 'integrations';


	/**
	 * Init the settings.
	 *
	 * @param    array $settings    The settings to add.
	 * @param    array $groups    The groups to add.
	 */
	public static function init( $settings = array(), $groups = array() ) {

		$parsed_settings = array();
		foreach ( $settings as $setting ) {

			// Update isPluginInstalled based on  the file supplied.
			if ( array_key_exists( 'pluginFile', $setting ) ) {
				if ( is_array( $setting['pluginFile'] ) ) {
					foreach ( $setting['pluginFile'] as $plugin_file ) {
						if ( Dependants::is_plugin_installed( $plugin_file ) ) {
							$setting['isPluginInstalled'] = true;
							// Bail at the first match.
							break;
						}
					}
				}
			}

			$parsed_settings[] = $setting;
		}

		parent::init( $parsed_settings, $groups );
	}
}
