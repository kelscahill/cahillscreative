<?php
/**
 * Debugger settings data.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Settings
 */

namespace Search_Filter\Compatibility;

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
				'name'      => 'cssIncreaseSpecificity',
				'label'     => __( 'Override Styles', 'search-filter' ),
				'help'      => __( 'Prevent styling issues by increasing CSS specificity. This can make it more difficult to write custom CSS.', 'search-filter' ),
				'default'   => 'no',
				'type'      => 'string',
				'inputType' => 'Toggle',
				// 'link'        => 'https://searchandfilter.com/documentation/using-the-block-editor/',
				'icon'      => 'wordpress',
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
				'name'           => 'popoverNode',
				'label'          => __( 'Popover Placement', 'search-filter' ),
				'help'           => __( 'Attach popovers inline with the source element for better accessibility. When set to body, popovers attach to the document body instead.', 'search-filter' ),
				'notice'         => __( "It's best to leave this inline unless the popovers are being hidden or cropped due to CSS overflow.", 'search-filter' ),
				'noticePosition' => 'after',
				'noticeType'     => 'info',
				'default'        => 'inline',
				'type'           => 'string',
				'inputType'      => 'Select',
				'icon'           => 'wordpress',
				'iconColor'      => '#0073aa',
				'options'        => array(
					array(
						'value' => 'inline',
						'label' => __( 'Inline', 'search-filter' ),
					),
					array(
						'value' => 'body',
						'label' => __( 'Body', 'search-filter' ),
					),
				),
			),
		);
		return $settings_data;
	}
}
