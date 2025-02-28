<?php
/**
 * Debugger settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter\Debugger;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found for debugger.
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
				'name'      => 'logLevel',
				'label'     => __( 'Log level', 'search-filter' ),
				'help'      => __( 'Choose which kinds of messages to log.', 'search-filter' ),
				'default'   => 'errors',
				'type'      => 'string',
				'inputType' => 'Select',
				'options'   => array(
					array(
						'value' => 'all',
						'label' => __( 'All', 'search-filter' ),
					),
					array(
						'value' => 'warnings',
						'label' => __( 'Warnings', 'search-filter' ),
					),
					array(
						'value' => 'errors',
						'label' => __( 'Errors', 'search-filter' ),
					),
				),
			),
			array(
				'name'      => 'logToDatabase',
				'label'     => __( 'Log to database', 'search-filter' ),
				'help'      => __( 'Choose whether to log to the database.', 'search-filter' ),
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'options'   => array(
					array(
						'value' => 'yes',
						'label' => __( 'Yes', 'search-filter' ),
					),
					array(
						'value' => 'no',
						'label' => __( 'No', 'search-filter' ),
					),
				),
			),
		);
		return $settings_data;
	}
}
