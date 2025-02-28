<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      1.0.0
 * @package    Custom_Layouts
 */

namespace Search_Filter\Integrations;

use Search_Filter\Features;
use Search_Filter\Integrations\Legacy\Plugin;
use Search_Filter\Features\Settings as Features_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The legacy shortcodes class used in S&F 1.x on WordPress.org.
 *
 * Included to keep supporting users who have not migrated yet.
 */
class Legacy {
	private static $legacy_option;

	public static function has_legacy_version() {
		if ( ! isset( self::$legacy_option ) ) {
			self::$legacy_option = get_option( 'searchandfilter_version' );
		}
		if ( self::$legacy_option ) {
			return true;
		}
		return false;
	}

	public static function init() {
		if ( ! self::has_legacy_version() ) {
			return;
		}
		// Add the legacy support option regardless of whether the feature is enabled or not.
		add_action( 'search-filter/settings/features/init', '\\Search_Filter\\Integrations\\Legacy::add_legacy_setting', 10 );
		// Can't use `Features::is_enabled` until after the features are registered.
		add_action( 'search-filter/settings/features/init', '\\Search_Filter\\Integrations\\Legacy::load_plugin', 10 );
	}

	public static function load_plugin() {
		if ( ! Features::is_enabled( 'legacyShortcodes' ) ) {
			return;
		}

		// Run the legacy plugin.
		new Plugin();
	}

	public static function add_legacy_setting() {
		$setting = array(
			'name'        => 'legacyShortcodes',
			'label'       => __( 'Legacy shortcodes', 'search-filter' ),
			'notice'      => __( 'Notice: this feature will be removed by October 2024.', 'search-filter' ),
			'description' => __( 'Enables the old Search & Filter shortcodes used on the wordpress.org version of the plugin - it is made available to ensure a smooth upgrade process. Disable when the migration is complete.', 'search-filter' ),
			'default'     => true,
			'type'        => 'string',
			'inputType'   => 'SettingToggle',
			'link'        => 'https://searchandfilter.com/documentation/block-editor/',
			'icon'        => 'wordpress',
			'iconColor'   => '#0073aa',
		);

		Features_Settings::add_setting( $setting );
	}
}
