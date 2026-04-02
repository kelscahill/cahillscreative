<?php
/**
 * Indexer settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter_Pro\Features\Advanced;

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
				'name'      => 'urlPrefix',
				'label'     => __( 'URL Prefix', 'search-filter-pro' ),
				'help'      => __( 'Use a prefix for compatibility and to prevent collisions with other URL arguments. Must only use characters a-z, underscores or hyphens.', 'search-filter-pro' ),
				'default'   => '_',
				'type'      => 'string',
				'inputType' => 'Text',
				'regex'     => '/[^0-9A-Za-z_/-]/gi',
			),
			array(
				'name'      => 'urlPrefixNotice',
				'content'   => __( "Not using a prefix greatly increases the chances of collisions with other url arguments - ensure unique url names in each field's settings.", 'search-filter-pro' ),
				'type'      => 'string',
				'inputType' => 'Notice',
				'status'    => 'warning',
				'dependsOn' => array(
					'relation' => 'AND',
					'action'   => 'hide',
					'rules'    => array(
						array(
							'option'  => 'urlPrefix',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			),
			array(
				'name'      => 'useAutocompleteNonce',
				'label'     => __( 'Use Autocomplete Nonce', 'search-filter-pro' ),
				'help'      => __( 'Use a nonce for autocomplete suggestions to enhance security - recommended to disable if using caching or a CDN.', 'search-filter-pro' ),
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				'options'   => array(
					array(
						'label' => __( 'Yes', 'search-filter' ),
						'value' => 'yes',
					),
					array(
						'label' => __( 'No', 'search-filter' ),
						'value' => 'no',
					),
				),
			),
			array(
				'name'           => 'subscribeToBetaVersions',
				'label'          => __( 'Beta Version Access', 'search-filter-pro' ),
				'help'           => __( "Upgrades you to our pre-release beta versions. You'll continue to get automatic updates to our beta versions while this is enabled.", 'search-filter-pro' ),
				'default'        => 'no',
				'type'           => 'string',
				'notice'         => __( 'Important: it may not be possible to roll back to an earlier version - always test on a staging or development server.' ),
				'noticePosition' => 'after',
				'noticeLevel'    => 'info',
				'inputType'      => 'Toggle',
				'options'        => array(
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
