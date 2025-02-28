<?php
/**
 * Plugin Name:       Search & Filter Pro
 * Plugin URI:        http://searchandfilter.com
 * Description:       Build powerful search experiences for your website or store with powerful pro features.
 * Version:           3.1.7
 * Author:            Code Amp
 * Author URI:        https://codeamp.com
 * Update URI:        https://searchandfilter.com
 * Text Domain:       search-filter
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Tested up to: 6.7
 * WC requires at least: 9.1
 * WC tested up to: 9.6
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'constants.php';

define( 'SEARCH_FILTER_PRO_BASE_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 */
function activate_search_filter_pro() {
	Search_Filter_Pro\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_search_filter_pro() {
	Search_Filter_Pro\Core\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_search_filter_pro' );
register_deactivation_hook( __FILE__, 'deactivate_search_filter_pro' );

/**
 * The core plugin class.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-search-filter-pro.php';

/**
 * Begins execution of the plugin.
 *
 * @since    3.0.0
 */
function run_search_filter_pro() {
	new Search_Filter_Pro();
}
run_search_filter_pro();



/**
 * Add compatibility with WooCommerce Custom Order Tables (HPOS).
 *
 * We don't do anything with the order tables, but without this users cannot use
 * custom order tables.  The alternative is to remove the WC tested upto version
 * from the plugin readme.
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SEARCH_FILTER_PRO_BASE_FILE, true );
		}
	}
);