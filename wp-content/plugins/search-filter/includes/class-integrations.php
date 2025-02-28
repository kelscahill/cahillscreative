<?php
/**
 * The main class for initialising integrations.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Core\Dependants;
use Search_Filter\Core\Notices;
use Search_Filter\Integrations\Gutenberg;
use Search_Filter\Integrations\Legacy;
use Search_Filter\Integrations\Woocommerce;
use Search_Filter\Integrations\Themes;
use Search_Filter\Integrations\Wpml;
use Search_Filter\Integrations\Settings as Integrations_Settings;
use Search_Filter\Integrations\Settings_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads all 3rd party integrations
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 * @subpackage Search_Filter/includes
 */
class Integrations {

	/**
	 * Track if we've initialized our settings.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private static $settings_init = false;

	/**
	 * Track which integrations are enabled.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $enabled_integrations = array();

	/**
	 * Init the integrations.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Run the integrations.
		self::init_integrations();

		add_action( 'shutdown', array( __CLASS__, 'validate_integrations' ) );
		add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'add_notices' ) );
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Integrations_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
	}
	/**
	 * Init the individual integrations.
	 *
	 * @since 3.0.0
	 */
	public static function init_integrations() {
		Legacy::init();
		Gutenberg::init();
		Woocommerce::init();
		Themes::init();
		Wpml::init();
	}

	/**
	 * Get the integrations.
	 *
	 * @since 3.0.0
	 *
	 * @return array The integrations.
	 */
	public static function get_integrations() {
		$defaults            = Integrations_Settings::get_defaults();
		$integrations_option = Options::get_option_value( 'integrations' );
		$integrations        = $defaults;
		if ( $integrations_option ) {
			$integrations = wp_parse_args( $integrations_option, $defaults );
		}

		return $integrations;
	}
	/**
	 * Get the enabled integrations.
	 *
	 * @since 3.0.0
	 *
	 * @return array The enabled integrations.
	 */
	public static function get_enabled_integrations() {
		if ( self::$settings_init ) {
			return self::$enabled_integrations;
		}
		$integrations         = self::get_integrations();
		$enabled_integrations = array();
		foreach ( $integrations as $integration => $enabled ) {
			if ( $enabled ) {
				$enabled_integrations[] = $integration;
			}
		}
		self::$enabled_integrations = $enabled_integrations;
		self::$settings_init        = true;
		return self::$enabled_integrations;
	}

	/**
	 * Check if an integration is enabled.
	 *
	 * @since 3.0.0
	 *
	 * @param string $integration The integration to check.
	 *
	 * @return bool True if the integration is enabled.
	 */
	public static function is_enabled( $integration ) {
		if ( self::$enabled_integrations ) {
			return in_array( $integration, self::$enabled_integrations );
		}
		$integrations = self::get_enabled_integrations();
		return in_array( $integration, $integrations );
	}

	/**
	 * Enable an integration.
	 *
	 * @since 3.0.6
	 *
	 * @param string $integration The integration to enable.
	 */
	public static function enable( $integration, $bypass_hook = false ) {
		$integrations                 = self::get_integrations();
		$integrations[ $integration ] = true;
		Options::update_option_value( 'integrations', $integrations );
		if ( ! $bypass_hook ) {
			do_action( 'search-filter/integrations/enable', $integration );
		}
	}

	/**
	 * Disable an integration.
	 *
	 * @since 3.0.6
	 *
	 * @param string $integration The integration to disable.
	 */
	public static function disable( $integration, $bypass_hook = false ) {
		$integrations                 = self::get_integrations();
		$integrations[ $integration ] = false;
		Options::update_option_value( 'integrations', $integrations );
		if ( ! $bypass_hook ) {
			do_action( 'search-filter/integrations/disable', $integration );
		}
	}

	/**
	 * Validate the integrations.
	 *
	 * @since 3.0.6
	 */
	public static function validate_integrations() {
		// Only validate in the admin.
		if ( ! is_admin() ) {
			return;
		}
		do_action( 'search-filter/integrations/validate' );
	}

	/**
	 * Track which integrations the user has installed.
	 *
	 * Useful for showing notices to the user about which integrations they might want
	 * to enable.
	 */
	public static function get_integrations_can_be_enabled() {

		$integrations_data = Options::get_option_value( 'integrations' );
		$all_integrations  = Integrations_Settings::get();

		$integrations_notices = array();
		foreach ( $all_integrations as $integration_slug => $integration_setting ) {
			// Default to true.
			$plugin_files = $integration_setting->get_prop( 'pluginFile' );

			// Skip any integrations that don't need installing / have files.
			if ( empty( $plugin_files ) ) {
				continue;
			}

			// Start with the assumption that the integration plugin is not installed.
			$plugin_status = 'not_installed';

			// Now check to see if the plugin is installed or not.
			foreach ( $plugin_files as $plugin_file ) {
				if ( Dependants::is_plugin_installed( $plugin_file ) ) {
					$plugin_status = 'installed';

					// Now check to see if the plugin is enabled.
					if ( Dependants::is_plugin_enabled( $plugin_file ) ) {
						$plugin_status = 'enabled';
						break;
					}
				}
			}

			// Now check to see if the plugin is enabled, but the integration is not.
			if ( $plugin_status === 'enabled' && ( ! isset( $integrations_data[ $integration_slug ] ) || ! $integrations_data[ $integration_slug ] ) ) {
				// The we can add a notice for it prompting the user to enable the integration.
				$integrations_notices[] = $integration_slug;
			}
		}

		return $integrations_notices;
	}
	/**
	 * Add notices for integrations.
	 *
	 * We might want to show a notice when a plugin has been enabled,
	 * encouraging the user to enable an integration.
	 */
	public static function add_notices() {

		$integrations_which_can_be_enabled = self::get_integrations_can_be_enabled();

		foreach ( $integrations_which_can_be_enabled as $integration_slug ) {
			// Check to make sure the integration notice has not been dismissed.
			if ( Notices::is_notice_dismissed( 'search-filter-integration-available-' . $integration_slug ) ) {
				continue;
			}

			// Get the integration setting so we can get the nice label:
			$integration_setting = Integrations_Settings::get_setting( $integration_slug );

			if ( ! $integration_setting ) {
				continue;
			}

			$integration_label = $integration_setting->get_prop( 'label' );

			// Add notice to our admin screen.
			$manage_plugins_link = sprintf( '<a href="%s">%s</a>.', esc_url( admin_url( 'plugins.php' ) ), esc_html__( 'Manage plugins', 'search-filter' ) );

			$notice_text = sprintf(
				// Translators: 1: Integration name, ie "WooCommerce".
				esc_html__( "Looks like you're using %s, enable the integration?", 'search-filter' ),
				'<strong>' . $integration_label . '</strong>'
			);

			$actions = array(
				'enable'  => array(
					'label'         => esc_html__( 'Enable', 'search-filter' ),
					'type'          => 'enable_integration',
					'integration'   => $integration_slug,
					'shouldDismiss' => true,
				),
				'manage'  => array(
					'label'    => esc_html__( 'Manage integrations', 'search-filter' ),
					'type'     => 'navigate',
					'location' => '?page=search-filter&section=integrations',
					'variant'  => 'tertiary',
				),
				'dismiss' => true,
			);

			Notices::add_notice( $notice_text, 'notice', 'search-filter-integration-available-' . $integration_slug, $actions );
		}
	}
}
