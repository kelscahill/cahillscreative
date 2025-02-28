<?php
/**
 * Features settings for admin screens.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settings for features.
 */
class Settings_Data {
	/**
	 * Returns the settings groups (name + label)
	 *
	 * @return array
	 */
	public static function get_groups() {
		$groups_data = array();
		return $groups_data;
	}
	/**
	 * Returns all the settings.
	 *
	 * @return array
	 */
	public static function get() {

		$settings_data = array(
			array(
				'name'        => 'lazyLoadJavaScript',
				'label'       => __( 'Lazy load JavaScript', 'search-filter' ),
				'description' => __( 'Ensures that Search & Filter JavaScript files are loaded only when needed - speeding up the other pages on your site.  Some classic themes may not support this.', 'search-filter' ),
				'default'     => false,
				'type'        => 'string',
				'inputType'   => 'SettingToggle',
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'        => 'wordpress',
				'iconColor'   => '#0073aa',
			),
			array(
				'name'        => 'removeDataOnUninstall',
				'label'       => __( 'Remove data on uninstall', 'search-filter' ),
				'description' => __( 'Removes all Search & Filter data when uninstalling this plugin.  Disable this to to keep your data if you plan to re-install later.', 'search-filter' ),
				'default'     => false,
				'type'        => 'string',
				'inputType'   => 'SettingToggle',
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'        => 'wordpress',
				'iconColor'   => '#0073aa',
			),
			array(
				'name'          => 'debugMode',
				'label'         => __( 'Debugging tools', 'search-filter' ),
				'description'   => __( 'Enables the debugging admin bar menu and logging options - helping to provide additional information when troubleshooting issues.', 'search-filter' ),
				'default'       => true,
				'type'          => 'string',
				'inputType'     => 'SettingToggle',
				'settingsGroup' => 'debugger',
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'          => 'wordpress',
				'iconColor'     => '#0073aa',
			),
			array(
				'name'        => 'shortcodes',
				'label'       => __( 'Shortcodes', 'search-filter' ),
				'description' => __( 'Use shortcodes to display fields across your site.  Adds various options to the admin UI.', 'search-filter' ),
				'default'     => true,
				'type'        => 'string',
				'inputType'   => 'SettingToggle',
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'        => 'wordpress',
				'iconColor'   => '#0073aa',
			),
			/*
			array(
				'name'        => 'useNonce',
				'label'       => __( 'Use a nonce for the REST API', 'search-filter' ),
				'description' => __( 'Using a nonce is better for security, but if you need to access your REST API from another site you might want to disable this.', 'search-filter' ),
				'default'     => true,
				'type'        => 'string',
				'inputType'   => 'SettingToggle',
				'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'        => 'wordpress',
				'iconColor'   => '#0073aa',
			), */
		);
		return $settings_data;
	}
}
