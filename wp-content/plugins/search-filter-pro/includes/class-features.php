<?php
/**
 * The main class for initialising integrations.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Features\Settings as Features_Settings;
use Search_Filter_Pro\Features\Advanced;
use Search_Filter_Pro\Features\Beta_Features;
use Search_Filter_Pro\Features\Caching;
use Search_Filter_Pro\Features\Debugger;
use Search_Filter_Pro\Features\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds features to the features screen.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/includes
 */
class Features {

	/**
	 * Main entry point for the integrations.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Important - must be run as soon as features are ready so we can modify other settings before they're setup.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'init_features' ), 10 );

		// Init features classes
		// They neeed to be init early so they can hook into settings
		// and preload options as needed.
		Shortcodes::init();
		Beta_Features::init();
		Advanced::init();
		Caching::init();
		Debugger::init();
	}

	/**
	 * Init the integration classes.
	 *
	 * @since    3.0.0
	 */
	public static function init_features() {
		$setting = array(
			'name'          => 'indexer',
			'label'         => __( 'Indexer', 'search-filter-pro' ),
			'description'   => __( 'The indexer enables support for dynamic fields and options, advanced field types and improves query performance on the frontend.', 'search-filter' ),
			'default'       => true,
			'settingsGroup' => 'indexer',
			'type'          => 'string',
			'inputType'     => 'SettingToggle',
			// 'link'          => 'https://searchandfilter.com/documentation/indexer/',
			'icon'          => 'wordpress',
			'iconColor'     => '#0073aa',
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'start',
			),
		);
		Features_Settings::add_setting( $setting, $setting_args );

		$setting = array(
			'name'          => 'betaFeatures',
			'label'         => __( 'Beta Features', 'search-filter' ),
			'description'   => __( 'Early preview access to powerful new (beta) features!', 'search-filter' ),
			'default'       => true,
			'type'          => 'string',
			'inputType'     => 'Group',
			'settingsGroup' => 'beta-features',
			// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
			'icon'          => 'wordpress',
			'iconColor'     => '#0073aa',
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'debugMode',
			),
		);
		Features_Settings::add_setting( $setting, $setting_args );

		$setting = array(
			'name'          => 'advancedFeatures',
			'label'         => __( 'Advanced Settings', 'search-filter' ),
			'description'   => __( 'Configure advanced settings & features. For advanced users and builders.', 'search-filter' ),
			'default'       => true,
			'type'          => 'string',
			'inputType'     => 'Group',
			'settingsGroup' => 'advanced-features',
			// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
			'icon'          => 'wordpress',
			'iconColor'     => '#0073aa',
		);

		Features_Settings::add_setting( $setting, $setting_args );

		$setting = array(
			'name'          => 'caching',
			'label'         => __( 'Caching', 'search-filter-pro' ),
			'description'   => __( 'Configure caching layers to optimize performance across the Search & Filter frontend - caching is always disabled for admin users.', 'search-filter-pro' ),
			'default'       => true,
			'type'          => 'string',
			'inputType'     => 'Group',
			'settingsGroup' => 'caching',
			'icon'          => 'wordpress',
			'iconColor'     => '#0073aa',
		);

		Features_Settings::add_setting( $setting, $setting_args );
	}
}
