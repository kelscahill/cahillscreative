<?php
/**
 * Indexer settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter\Features\Dynamic_Assets;

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
				'name'           => 'dynamicAssetLoadingPreload',
				'label'          => __( 'Preload Dynamic Assets', 'search-filter-pro' ),
				'help'           => __( 'Parses post content early so that assets can be loaded in the header instead of the body.', 'search-filter-pro' ),
				'notice'         => __( 'Preloading is not required for block themes or page builders - usually only needed for classic themes which process the content after the header.', 'search-filter-pro' ),
				'noticePosition' => 'after',
				'noticeLevel'    => 'info',
				'default'        => 'no',
				'type'           => 'string',
				'inputType'      => 'Toggle',
				'options'        => array(
					array(
						'label' => __( 'Yes', 'search-filter-pro' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter-pro' ),
						'value' => 'no',
					),
				),
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
			),
		);
		return $settings_data;
	}
}
