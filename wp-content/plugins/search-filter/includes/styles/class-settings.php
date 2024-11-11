<?php
/**
 * Styles settings for admin screens.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Styles
 */

namespace Search_Filter\Styles;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settingsfor styles
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
	protected static $section = 'styles';

	/**
	 * Init the settings.
	 *
	 * @param    array $settings    The settings to add.
	 * @param    array $groups    The groups to add.
	 */
	public static function init( $settings = array(), $groups = array() ) {
		// Fetch field settings and filter by settings tab.
		$parsed_settings = array();
		foreach ( $settings as $field_setting ) {
			if ( ( ! array_key_exists( 'excludeFromStyles', $field_setting ) ) || $field_setting['excludeFromStyles'] === false ) {

				if ( array_key_exists( 'stylesDefault', $field_setting ) ) {
					$field_setting['default'] = $field_setting['stylesDefault'];
					unset( $field_setting['stylesDefault'] );
				}

				$field_setting['allowEmpty'] = false;
				$parsed_settings[]           = $field_setting;
			}
		}

		parent::init( $parsed_settings, $groups );
	}
}
