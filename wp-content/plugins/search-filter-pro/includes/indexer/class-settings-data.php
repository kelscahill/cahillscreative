<?php
/**
 * Indexer settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter_Pro\Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found for indexer
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
				'name'      => 'useBackgroundProcessing',
				'label'     => __( 'Use background processing', 'search-filter-pro' ),
				'help'      => __( 'Run the indexer in the background. Otherwise, you will need to keep the dashboard open until the indexing process completes.', 'search-filter-pro' ),
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter-pro' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter-pro' ),
					),
				),
			   // 'link'        => '',
			),
			array(
				'name'      => 'enableOnFrontend',
				'label'     => __( 'Enable on frontend', 'search-filter-pro' ),
				'help'      => __( 'Watch for updates to posts on the frontend and resync them.', 'search-filter-pro' ),
				'default'   => 'yes',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter-pro' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter-pro' ),
					),
				),
			   // 'link'        => '',
			),
		);
		return $settings_data;
	}
}
