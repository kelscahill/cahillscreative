<?php
/**
 * Integrations settings for admin screens.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */


namespace Search_Filter\Integrations;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found for integrations
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
				'name'                 => 'blockeditor',
				'label'                => __( 'Block Editor', 'search-filter' ),
				'description'          => __( 'Add blocks for search, filter and control fields and a re-usable field block.  Integrate fields directly with the query block.', 'search-filter' ),
				'ariaLabelEnable'      => __( 'Enable Block Editor integration', 'search-filter' ),
				'ariaLabelDisable'     => __( 'Disable Block Editor integration', 'search-filter' ),
				'default'              => true,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'link'                 => 'https://searchandfilter.com/documentation/block-editor/',
				'icon'                 => 'wordpress',
				'iconColor'            => '#0073aa',
				'isPluginInstalled'    => true,
				'isPluginEnabled'      => true,
				'isExtensionInstalled' => true,
			),
			array(
				'name'                 => 'woocommerce',
				'label'                => __( 'WooCommerce', 'search-filter' ),
				'description'          => __( 'Create filters using WooCommerce data and add support for filtering the shop, products query block and shortcodes.', 'search-filter' ),
				'default'              => true,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/woocommerce/',
				'icon'                 => 'woocommerce',
				'iconColor'            => '#0073aa',
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'woocommerce/woocommerce.php' ),
				'isExtensionInstalled' => true,
			),
			array(
				'name'                 => 'acf',
				'label'                => __( 'Advanced Custom Fields', 'search-filter' ),
				'description'          => __( 'Create search and filter fields powered by your ACF data.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'acf',
				'iconColor'            => '#0073aa',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/advanced-custom-fields-acf/',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ),
				'isExtensionInstalled' => true,
				'comingSoon'           => false,
			),
			array(
				'name'                 => 'elementor',
				'label'                => __( 'Elementor', 'search-filter' ),
				'description'          => __( 'Adds Elementor widgets for search, filter and control fields. Integrate queries with the grid widget, shop widget and portfolio widget.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'elementor',
				'iconColor'            => '#0073aa',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/elementor/',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'elementor/elementor.php', 'elementor-pro/elementor-pro.php' ),
				'isExtensionInstalled' => false,
				'comingSoon'           => false,
				'disabled'             => true,
			),
			array(
				'name'                 => 'beaverbuilder',
				'label'                => __( 'Beaver Builder', 'search-filter' ),
				'description'          => __( 'Adds Beaver Builder modules for search, filter and control fields. Integrate fields and queries with the grid module, shop module and portfolio module.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'beaverbuilder',
				'iconColor'            => '#0073aa',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'link'                 => 'https://searchandfilter.com/documentation/integrations/beaver-builder/',
				'pluginFile'           => array( 'bb-plugin/fl-builder.php' ),
				'isExtensionInstalled' => false,
				'comingSoon'           => false,
				'disabled'             => true,
			),
			array(
				'name'                 => 'wpml',
				'label'                => __( 'WPML', 'search-filter' ),
				'description'          => __( 'Add multilingual support with the WPML plugin. Enables translation of the strings used across the user interface.', 'search-filter' ),
				'default'              => true,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/wpml/',
				'icon'                 => 'wpml',
				'iconColor'            => '#0073aa',
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'sitepress-multilingual-cms/sitepress.php' ),
				'isExtensionInstalled' => false,
			),
			array(
				'name'                 => 'relevanssi',
				'label'                => __( 'Relevanssi', 'search-filter' ),
				'description'          => __( 'Adds Relevanssi integration for search fields.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'relevanssi',
				'iconColor'            => '#0073aa',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/relevanssi/',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'relevanssi/relevanssi.php' ),
				'isExtensionInstalled' => false,
				'comingSoon'           => false,
				'disabled'             => true,
			),
			array(
				'name'                 => 'divi',
				'label'                => __( 'Divi', 'search-filter' ),
				'description'          => __( 'Adds Divi modules for search, filter and control fields. Integrate queries with the WooCommerce products module, blog module and portfolio module.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'divi',
				'iconColor'            => '#0073aa',
				'link'                 => 'https://searchandfilter.com/documentation/integrations/divi/',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'divi/divi.php' ),
				'isExtensionInstalled' => false,
				'comingSoon'           => true,
			),
			array(
				'name'                 => 'bricks',
				'label'                => __( 'Bricks Builder', 'search-filter' ),
				'description'          => __( 'Adds elements for search, filter and control fields. Integrates queries with the query loop.', 'search-filter' ),
				'default'              => false,
				'type'                 => 'string',
				'inputType'            => 'FeatureToggle',
				'icon'                 => 'bricks',
				'iconColor'            => '#0073aa',
				// 'link'        => 'https://searchandfilter.com/documentation/integrations/bricks/',
				'isPro'                => true,
				'isPluginInstalled'    => false,
				'isPluginEnabled'      => false,
				'pluginFile'           => array( 'bricks/bricks.php' ),
				'isExtensionInstalled' => false,
				'comingSoon'           => true,
			),

		);
		return $settings_data;
	}
}
