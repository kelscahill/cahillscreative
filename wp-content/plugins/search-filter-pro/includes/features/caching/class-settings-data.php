<?php
/**
 * Caching settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.2.0
 * @package    Search_Filter_Pro/Settings
 */

namespace Search_Filter_Pro\Features\Caching;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found for caching
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
				'name'      => 'objectCacheNotice',
				'content'   => __( 'For Redis or Memcached support, install a WordPress object cache plugin or use a drop-in. Falls back to database caching when object caching is not available.', 'search-filter-pro' ),
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'info',
			),
			array(
				'name'      => 'enableCaching',
				'label'     => __( 'Enable Caching', 'search-filter-pro' ),
				'help'      => __( 'Caches complex queries, field counting and more. Caching is always disabled for admin users.', 'search-filter-pro' ),
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
			),
			array(
				'name'      => 'disableCacheNotice',
				'content'   => __( 'It is recommended to keep caching enabled for optimum performance -  it should only be disabled for testing purposes.', 'search-filter-pro' ),
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'enableCaching',
							'value'   => 'no',
							'compare' => '=',
						),
					),
				),
			),
			array(
				'name'      => 'enableApcu',
				'label'     => __( 'APCu Caching', 'search-filter-pro' ),
				'help'      => __( 'Use APCu for fast in-memory caching. Requires APCu PHP extension.', 'search-filter-pro' ),
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
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'enableCaching',
							'value'   => 'yes',
							'compare' => '=',
						),
					),
				),
			),
		);
		return $settings_data;
	}
}
