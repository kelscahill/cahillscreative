<?php
/**
 * Plugin Name: Brevo - WooCommerce Email Marketing
 * Plugin URI: https://www.brevo.com/?r=wporg
 * Description: Allow users to subscribe to your newsletter via the checkout page and a client to send SMS campaign.
 * Author: Brevo
 * Text Domain: woocommerce-sendinblue-newsletter-subscription
 * Domain Path: /languages
 * Version: 4.0.30
 * Author URI: https://www.brevo.com/?r=wporg
 * Requires at least: 4.3
 * Tested up to: 6.6.2
 * Requires PHP: 5.6
 *
 * WC requires at least: 3.1
 * WC tested up to: 6.9.0
 * License: GPLv2 or later
 *
 * @package SendinblueWoocommerce
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

use SendinblueWoocommerce\Managers\AdminManager;
use SendinblueWoocommerce\Managers\ApiManager;
use SendinblueWoocommerce\Managers\UpdatePluginManagers;

define('SENDINBLUE_WC_ROOT_PATH', dirname(__FILE__));
define('SENDINBLUE_WC_TEXTDOMAIN', 'woocommerce-sendinblue-newsletter-subscription');
define('SENDINBLUE_WC_API_KEY_ID', 'sendinblue_woocommerce_api_key_id');
define('SENDINBLUE_WC_API_CONSUMER_KEY', 'sendinblue_woocommerce_consumer_key');
define('SENDINBLUE_WC_USER_CONNECTION_ID', 'sendinblue_woocommerce_user_connection_id');
define('SENDINBLUE_WC_SETTINGS', 'sendinblue_woocommerce_user_connection_settings');
define('SENDINBLUE_WC_EMAIL_SETTINGS', 'sendinblue_woocommerce_email_options_settings');
define('SENDINBLUE_WC_VERSION_SENT', 'sendinblue_woocommerce_version_sent');
define('API_KEY_V3_OPTION_NAME', 'sib_wc_api_key_v3');
define('SENDINBLUE_WC_PLUGIN_VERSION', '4.0.29');
define('SENDINBLUE_WORDPRESS_SHOP_VERSION', $GLOBALS['wp_version']);
define('SENDINBLUE_WOOCOMMERCE_UPDATE', 'sendinblue_plugin_update_call_apiv3');
define('SENDINBLUE_REDIRECT', 'sendinblue_woocommerce_redirect');
define('SENDINBLUE_WC_ECOMMERCE_REQ', 'sendinblue_woocommerce_ecommerce_requires');
define('SENDINBLUE_ECOMMERCE_CALLED_TIME', 'ecommerce_called_time');

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/admin-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/update-plugin-manager.php';

function update_woocom_email_settings()
{
    $email_settings = get_option(SENDINBLUE_WC_EMAIL_SETTINGS, null);
    $email_settings = empty($email_settings) ? null : json_decode($email_settings, true);

    $is_email_options_enabled = isset($email_settings['isEmailOptionsEnabled']) ? $email_settings['isEmailOptionsEnabled'] : false;
    if (!$is_email_options_enabled) {
        return;
    }

    if ($email_settings['isNewOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_new_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_new_order_settings', $order_settings);
    }

    if ($email_settings['isFailedOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_failed_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_failed_order_settings', $order_settings);
    }

    if ($email_settings['isCancelledOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_cancelled_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_cancelled_order_settings', $order_settings);
    }

    if ($email_settings['isOnHoldOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_customer_on_hold_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_customer_on_hold_order_settings', $order_settings);
    }

    if ($email_settings['isProcessingOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_customer_processing_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_customer_processing_order_settings', $order_settings);
    }

    if ($email_settings['isRefundedOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_customer_refunded_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_customer_refunded_order_settings', $order_settings);
    }

    if ($email_settings['isCompletedOrderEmailEnabled']) {
        $order_settings = get_option('woocommerce_customer_completed_order_settings');
        $order_settings['enabled'] = "no";
        update_option('woocommerce_customer_completed_order_settings', $order_settings);
    }

    if ($email_settings['isCustomerNoteEmailEnabled']) {
        $customer_note_settings = get_option('woocommerce_customer_note_settings');
        $customer_note_settings['enabled'] = "no";
        update_option('woocommerce_customer_note_settings', $customer_note_settings);
    }

    if ($email_settings['isNewAccountEmailEnabled']) {
        $new_account_settings = get_option('woocommerce_customer_new_account_settings');
        $new_account_settings['enabled'] = "no";
        update_option('woocommerce_customer_new_account_settings', $new_account_settings);
    }
}

function sendinblue_woocommerce_load()
{
    do_action('update_to_sendinblue_new_plugin');
    $api_manager = new ApiManager();
    $api_manager->add_hooks();
    update_woocom_email_settings();
}

//Declare HPOS Compatibility
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

function sendinblue_woocommerce_init()
{
    if (get_option(SENDINBLUE_REDIRECT, false)) {
        delete_option(SENDINBLUE_REDIRECT);
        wp_redirect(add_query_arg('page', 'sendinblue', admin_url('admin.php')));
    }
    add_filter('rewrite_rules_array', 'sendinblue_woocommerce_rewrites');
    add_action('template_redirect', 'sendinblue_woocommerce_callback');

    load_plugin_textdomain( SENDINBLUE_WC_TEXTDOMAIN , false, dirname(plugin_basename(__FILE__)) . '/languages');
    $admin_manager = new AdminManager();
    $admin_manager->run();
}

function sendinblue_woocommerce_rewrites($wp_rules)
{
    add_rewrite_rule("sendinblue-callback\$", "index.php?pagename=sendinblue-callback");

    return $wp_rules;
}

function sendinblue_woocommerce_callback()
{
    $pageNameVar = get_query_var('pagename');
    if ($pageNameVar == 'sendinblue-callback') {
        $result = array('status' => false);
        $user_connection_id = filter_input(INPUT_POST, 'user_connection_id');

        if(empty($user_connection_id)) {
            $query_string = $_SERVER['QUERY_STRING'] ?? $_SERVER['QUERY_STRING'];

            if (!empty($query_string)) {
                parse_str($query_string, $queries);
                $user_connection_id = $queries['user_connection_id'] ?? $queries['user_connection_id'];
            }
        }

        if(isset($user_connection_id) && !empty($user_connection_id)) {
            (get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null) !== null) ? update_option(SENDINBLUE_WC_USER_CONNECTION_ID, $user_connection_id) : add_option(SENDINBLUE_WC_USER_CONNECTION_ID, $user_connection_id);
            header('HTTP/1.1 200 OK', true);
            $result = array('status' => true);
        }
        wp_send_json($result);
    }
}

function sendinblue_woocommerce_activate()
{
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . '/wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active( 'woocommerce/woocommerce.php' )) {
        echo 'This plugin version requires WooCommerce 3.1 or newer. Please update WooCommerce to version 3.1 or newer';
        @trigger_error(__('Please install and activate WooCommerce 3.1 or newer before this.', 'ap'), E_USER_ERROR);

        return;
    }
    global $wp_rewrite;
    add_filter('rewrite_rules_array', 'sendinblue_woocommerce_rewrites');
    $wp_rewrite->flush_rules();
    (get_option(SENDINBLUE_REDIRECT, null) !== null) ? update_option(SENDINBLUE_REDIRECT, true) : add_option(SENDINBLUE_REDIRECT, true);
}

function sendinblue_woocommerce_deactivate()
{
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function sendinblue_woocommerce_uninstall()
{
    $api_manager = new ApiManager();
    $api_manager->revoke_key();
    $api_manager->flush_option_keys(SENDINBLUE_WC_USER_CONNECTION_ID);
    $api_manager->flush_option_keys(SENDINBLUE_WC_SETTINGS);
    $api_manager->flush_option_keys(SENDINBLUE_WC_EMAIL_SETTINGS);
    $api_manager->flush_option_keys(SENDINBLUE_WOOCOMMERCE_UPDATE);
    $api_manager->flush_option_keys(SENDINBLUE_WC_ECOMMERCE_REQ);
}

function sendinblue_woocommerce_update()
{
    $update_manager = new UpdatePluginManagers();
    $update_manager->send_settings();
    $update_manager->enable_ecommerce();
    $update_manager->post_update();
}

add_action('plugins_loaded', 'sendinblue_woocommerce_load');
add_action('init', 'sendinblue_woocommerce_init');
add_action('update_to_sendinblue_new_plugin', 'sendinblue_woocommerce_update');
register_activation_hook(SENDINBLUE_WC_ROOT_PATH . '/woocommerce-sendinblue.php', 'sendinblue_woocommerce_activate');
register_deactivation_hook(SENDINBLUE_WC_ROOT_PATH . '/woocommerce-sendinblue.php', 'sendinblue_woocommerce_deactivate');
register_uninstall_hook(SENDINBLUE_WC_ROOT_PATH . '/woocommerce-sendinblue.php', 'sendinblue_woocommerce_uninstall');
