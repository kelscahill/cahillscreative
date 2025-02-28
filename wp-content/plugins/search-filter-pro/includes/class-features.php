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
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'init_features' ), 10 );
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

		// Init the shortcodes features. TODO - this should probably go somewhere else.
		Shortcodes::init();
	}
}
