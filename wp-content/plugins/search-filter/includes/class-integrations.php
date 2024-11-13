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

use Search_Filter\Integrations\Gutenberg;
use Search_Filter\Integrations\Legacy;
use Search_Filter\Integrations\WooCommerce;
use Search_Filter\Integrations\Themes;
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
		self::register_settings();

		// Run the integrations.
		self::init_integrations();
		do_action( 'search-filter/integrations/init' );

		add_action( 'shutdown', array( __CLASS__, 'validate_integrations' ) );
	}

	/**
	 * Register the settings.
	 *
	 * @since 3.0.0
	 */
	public static function register_settings() {
		// Register settings.
		Integrations_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
		do_action( 'search-filter/settings/register/integrations' );
	}
	/**
	 * Init the individual integrations.
	 *
	 * @since 3.0.0
	 */
	public static function init_integrations() {
		Legacy::init();
		Gutenberg::init();
		WooCommerce::init();
		Themes::init();
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
		// TODO: Validate the integrations.
		do_action( 'search-filter/integrations/validate' );
	}
}
