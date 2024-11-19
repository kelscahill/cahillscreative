<?php
/**
 * Plugin Name:       Search & Filter
 * Plugin URI:        http://searchandfilter.com
 * Description:       Build powerful search experiences for your website or store.
 * Version:           3.0.7
 * Author:            Code Amp
 * Author URI:        http://codeamp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://searchandfilter.com
 * Text Domain:       search-filter
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Tested up to: 6.7
 * WC requires at least: 9.1
 * WC tested up to: 9.3
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'constants.php';

define( 'SEARCH_FILTER_BASE_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 */
function activate_search_filter() {
	Search_Filter\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_search_filter() {
	Search_Filter\Core\Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_search_filter' );
register_deactivation_hook( __FILE__, 'deactivate_search_filter' );

/**
 * The core plugin class.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-search-filter.php';

/**
 * Begins execution of the plugin.
 *
 * @since    3.0.0
 */
function run_search_filter() {

	$plugin = new Search_Filter();
	$plugin->run();
}
run_search_filter();


/**
 * Add compatibility with WooCommerce Custom Order Tables.
 *
 * We don't actually do anything with the order tables, but without this users cannot use
 * custom order tables.  The alternative is to remove the WC tested upto version from the
 * plugin readme.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
