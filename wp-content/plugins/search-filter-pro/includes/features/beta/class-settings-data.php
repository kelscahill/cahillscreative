<?php
/**
 * Indexer settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter_Pro\Features\Beta;

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
				'name'      => 'enhancedSearch',
				'label'     => __( 'Enhanced Search', 'search-filter-pro' ),
				'help'      => __( 'Lightning fast text search within custom fields, taxonomies and more.', 'search-filter-pro' ),
				'default'   => 'no',
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
			),

			array(
				'name'      => 'queryOptimizer',
				'label'     => __( 'Large Query Optimizer', 'search-filter-pro' ),
				'help'      => __( 'Optimizes large queries for better performance on some hosts - use a plugin like Query Monitor to verify results.', 'search-filter-pro' ),
				'default'   => 'no',
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
			),
		);
		return $settings_data;
	}
}
