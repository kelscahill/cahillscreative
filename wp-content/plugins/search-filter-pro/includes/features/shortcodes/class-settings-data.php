<?php
/**
 * Indexer settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter_Pro\Features\Shortcodes;

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
				'name'      => 'shortcodesEnableResults',
				'label'     => __( 'Results Shortcodes', 'search-filter-pro' ),
				'help'      => __( 'Enable support for the classic results shortcodes.', 'search-filter-pro' ),
				// 'notice'    => __( '', 'search-filter' ),
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'options'   => array(
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
