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
			/*
			 array(
				'name'      => 'indexMethod',
				'label'     => __( 'Index method', 'search-filter-pro' ),
				'help'      => __( '', 'search-filter-pro' ),
				'notice'    => __( '', 'search-filter' ),
				'default'   => 'wp_cron',
				'type'      => 'string',
				'inputType' => 'Select',
				'options'   => array(

				),
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
			),*/
		);
		return $settings_data;
	}
}
