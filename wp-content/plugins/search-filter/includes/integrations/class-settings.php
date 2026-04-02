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
use Search_Filter_Pro\Util;

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
	 * The setting section name.
	 *
	 * @var string
	 */
	protected static $section = 'integrations';


	/**
	 * Init the settings.
	 *
	 * @param    array $settings    The settings to add.
	 * @param    array $groups    The groups to add.
	 */
	public static function init( $settings = array(), $groups = array() ) {

		// We init the settings throughought the frontend and admin of the app.
		// However, we only need to know the integration installation status (which loads
		// some wp-admin specific includes) in our admin screens/endpoints - prevent
		// checking installation status on the frontend or regular ajax requests.
		$is_admin_like_request = ( is_admin() && ! wp_doing_ajax() ) || wp_is_serving_rest_request() || wp_is_json_request();

		if ( ! $is_admin_like_request ) {
			parent::init( $settings, $groups );
			return;
		}

		// We only need to check if an integration is installed in admin.
		$parsed_settings = array();
		foreach ( $settings as $setting ) {
			if ( array_key_exists( 'integrationPaths', $setting ) ) {
				$is_integration_installed = $setting['isIntegrationInstalled'] ?? false;
				if ( is_array( $setting['integrationPaths'] ) ) {
					foreach ( $setting['integrationPaths'] as $integration_path ) {
						if ( $setting['integrationType'] === 'plugin' ) {
							if ( Dependants::is_plugin_installed( $integration_path ) ) {
								$is_integration_installed = true;
								// Bail at the first match.
								break;
							}
						} elseif ( $setting['integrationType'] === 'theme' ) {
							if ( Dependants::is_theme_installed( $integration_path ) ) {
								$is_integration_installed = true;
								// Bail at the first match.
								break;
							}
						}
					}
				}
				$setting['isIntegrationInstalled'] = apply_filters( 'search-filter/integrations/is_installed', $is_integration_installed, $setting['name'] );
			}
			$parsed_settings[] = $setting;
		}

		parent::init( $parsed_settings, $groups );
	}
}
