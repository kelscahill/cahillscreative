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
use Search_Filter\Core\Errors;
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
	 * Local integrations array.
	 *
	 * @since 3.0.0
	 *
	 * @var array|null
	 */
	private static $integrations = null;
	/**
	 * Track which integrations are enabled.
	 *
	 * @since 3.0.0
	 *
	 * @var array|null
	 */
	private static $enabled_integrations = null;

	/**
	 * Reset static caches.
	 *
	 * Useful for tests to clear cached values after modifying options.
	 *
	 * @since 3.2.0
	 */
	public static function reset() {
		self::$integrations         = null;
		self::$enabled_integrations = null;
	}

	/**
	 * Init the integrations.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Register settings.
		add_action( 'init', array( __CLASS__, 'register_settings' ), 2 );

		// Preload the features option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );

		// Run the integrations.
		self::init_integrations();

		add_action( 'shutdown', array( __CLASS__, 'validate' ) );
		add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'add_notices' ) );
	}


	/**
	 * Preload the integrations option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		// We can't set any defaults at this stage because Settings haven't been registered yet.
		// After the first call to Options::get() below, we'll store the default values so preloading
		// will work after the first setup.
		$options_to_preload[] = 'integrations';
		return $options_to_preload;
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
	public static function get_all() {

		if ( self::$integrations !== null ) {
			return self::$integrations;
		}

		if ( ! did_action( 'search-filter/settings/integrations/init' ) ) {
			// Show error if we're trying to access integrations before the settings are initialized.
			Errors::add(
				__( 'Trying to access integrations before initialisation.', 'search-filter' )
			);
			return array();
		}

		self::init_option();

		return self::$integrations ?? array();
	}
	/**
	 * Get the enabled integrations.
	 *
	 * @since 3.0.0
	 *
	 * @return array The enabled integrations.
	 */
	public static function get_enabled_integrations() {
		// Return cached value if available.
		if ( self::$enabled_integrations === null ) {
			self::init_option();
		}

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

		$integrations = self::get_enabled_integrations();
		return in_array( $integration, $integrations, true );
	}

	/**
	 * Enable an integration.
	 *
	 * @since 3.0.6
	 *
	 * @param string $integration The integration to enable.
	 * @param bool   $bypass_hook Whether to bypass the enable hook.
	 */
	public static function enable( $integration, $bypass_hook = false ) {
		$integrations                 = self::get_all();
		$integrations[ $integration ] = true;

		self::update_all( $integrations );

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
	 * @param bool   $bypass_hook Whether to bypass the disable hook.
	 */
	public static function disable( $integration, $bypass_hook = false ) {
		$integrations                 = self::get_all();
		$integrations[ $integration ] = false;

		self::update_all( $integrations );

		if ( ! $bypass_hook ) {
			do_action( 'search-filter/integrations/disable', $integration );
		}
	}

	/**
	 * Update the value of a feature sub setting.
	 *
	 * @param array $updated_integrations The updated feature array.
	 */
	public static function update_all( $updated_integrations ) {

		$integrations = self::get_all();

		// Merge the updated integrations with the existing ones.
		$updated_integrations = wp_parse_args( $updated_integrations, $integrations );

		// Save the data as in the options table.
		Options::update( 'integrations', $updated_integrations );

		// Update local cache.
		self::$integrations = $updated_integrations;

		$enabled_integrations = array();
		// Update the enabled integrations cache.
		foreach ( $updated_integrations as $feature => $enabled ) {
			if ( $enabled ) {
				$enabled_integrations[] = $feature;
			}
		}

		self::$enabled_integrations = $enabled_integrations;
	}


	/**
	 * Validate the integrations.
	 *
	 * @since 3.0.6
	 */
	public static function validate() {
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
	private static function get_can_be_enabled() {

		$integrations_data = Options::get( 'integrations' );
		$all_integrations  = Integrations_Settings::get();

		$integrations_notices = array();
		foreach ( $all_integrations as $integration_slug => $integration_setting ) {
			// Default to true.
			$integration_paths = $integration_setting->get_prop( 'integrationPaths' );

			// Skip any integrations that don't need installing or don't have files.
			if ( empty( $integration_paths ) ) {
				continue;
			}

			$type = $integration_setting->get_prop( 'integrationType' );

			// Start with the assumption that the integration plugin is not installed.
			$integration_status = 'not_installed';

			// Now check to see if the plugin is installed or not.
			foreach ( $integration_paths as $integration_file ) {
				if ( $type === 'plugin' ) {
					if ( Dependants::is_plugin_installed( $integration_file ) ) {
						$integration_status = 'installed';

						// Now check to see if the plugin is enabled.
						if ( Dependants::is_plugin_enabled( $integration_file ) ) {
							$integration_status = 'enabled';
							break;
						}
					}
				} elseif ( $type === 'theme' ) {
					if ( Dependants::is_theme_installed( $integration_file ) ) {
						$integration_status = 'installed';

						// Now check to see if the theme is enabled.
						if ( Dependants::is_theme_active( $integration_file ) ) {
							$integration_status = 'enabled';
						}
					}
				}
			}

			// Note: this hook is likely to change name in the future.
			$integration_status = apply_filters( 'search-filter/integrations/integration_status', $integration_status, $integration_slug, $integrations_data );

			// Now check to see if the plugin is enabled, but the integration is not.
			if ( $integration_status === 'enabled' && ( ! isset( $integrations_data[ $integration_slug ] ) || ! $integrations_data[ $integration_slug ] ) ) {
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

		$integrations_which_can_be_enabled = self::get_can_be_enabled();

		foreach ( $integrations_which_can_be_enabled as $integration_slug ) {

			// Get the integration setting.
			$integration_setting = Integrations_Settings::get_setting( $integration_slug );
			if ( ! $integration_setting ) {
				continue;
			}

			// Get the integration label.
			$integration_label = $integration_setting->get_prop( 'label' );
			// Check if the integration is a pro integration.
			$integration_is_pro = $integration_setting->get_prop( 'isPro' );
			// Get the help link if there is one.
			$integration_link = $integration_setting->get_prop( 'link' );

			$notice_name = '';
			$actions     = array();

			// Then we're on the free version only but there is a pro integration that could be enabled.
			if ( ! Dependants::is_search_filter_pro_enabled() && $integration_is_pro ) {
				// Create a notice name for the upgrade notice.
				$notice_name = 'search-filter-integration-upgrade-' . $integration_slug;
				// Check to make sure the upgrade notice has not been dismissed.
				if ( Notices::is_notice_dismissed( $notice_name ) ) {
					continue;
				}

				$notice_text = sprintf(
					// Translators: %1$s is the integration name, ie "WooCommerce". %2$s is the name of the pro plugin - "Search & Filter Pro".
					esc_html__( 'Looks like you\'re using %1$s, upgrade to %2$s to get the official integration.', 'search-filter' ),
					'<strong>' . $integration_label . '</strong>',
					'<strong>Search & Filter Pro</strong>'
				);

				$actions = array(
					'upgrade' => array(
						'label'    => esc_html__( 'Upgrade', 'search-filter' ),
						'type'     => 'navigate',
						'location' => '?page=search-filter&section=pro',
					),
				);
			} else {
				// Create a notice name for the upgrade integration notice.
				$notice_name = 'search-filter-integration-available-' . $integration_slug;

				// Check to make sure the integration notice has not been dismissed.
				if ( Notices::is_notice_dismissed( $notice_name ) ) {
					continue;
				}

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

					'dismiss' => true,
				);
			}

			$actions['manage']  = array(
				'label'    => esc_html__( 'Manage integrations', 'search-filter' ),
				'type'     => 'navigate',
				'location' => '?page=search-filter&section=integrations',
				'variant'  => 'tertiary',
			);
			$actions['dismiss'] = true;

			Notices::add_notice( $notice_text, 'notice', $notice_name, $actions );
		}
	}

	/**
	 * Init the option for features.
	 *
	 * Should only be run if it doesn't already exist.
	 *
	 * @since 3.0.0
	 */
	public static function init_option() {

		// Get the integrations defaults from the settings.
		$defaults = Integrations_Settings::get_defaults();
		// Lookup and create if it doesn't exist.
		$integrations_option = Options::get( 'integrations', $defaults, true );
		$integrations        = $defaults;
		if ( $integrations_option ) {
			$integrations = wp_parse_args( $integrations_option, $defaults );
		}

		// Update local cache.
		self::$integrations = $integrations;

		$enabled_integrations = array();
		// Update the enabled features cache.
		foreach ( $integrations as $feature => $enabled ) {
			if ( $enabled ) {
				$enabled_integrations[] = $feature;
			}
		}

		self::$enabled_integrations = $enabled_integrations;
	}
}
