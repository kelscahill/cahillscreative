<?php


namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Managers\ApiManager;
use SendinblueWoocommerce\Clients\SendinblueClient;
use SendinblueWoocommerce\Managers\CartEventsManagers;

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/cart-events-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class AdminManager
 *
 * @package SendinblueWoocommerce\Managers
 */
class AdminManager
{
    private $api_manager;

    private $cart_events_manager;
  
    function __construct()
    {
        $this->api_manager = new ApiManager();
        $this->cart_events_manager = new CartEventsManagers();
    }

    public function run()
    {
        add_action('admin_menu', array($this, 'adminMenu' ), 110);
        add_action('rest_api_init', array($this->api_manager, 'add_rest_endpoints'));
        add_action('wp_head', array($this, 'install_ma_and_chat_script'));
    }

    public function adminMenu()
    {
        add_submenu_page(
            'woocommerce',
            'Sendinblue', 
            'Sendinblue', 
            'manage_options', 
            'sendinblue', 
            array( &$this, 'adminOptions' )
        );
    }

    public function adminOptions()
    {
    	try {
            $user_connection_id = get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null);
            if (!empty($user_connection_id)) {
                $settingsUrl = SendinblueClient::INTEGRATION_URL . $user_connection_id . SendinblueClient::SETTINGS_URL;
                $smsCampaignUrl = SendinblueClient::SMS_CAMPAIGN_URL;
                $chatUrl = SendinblueClient::SMS_CHAT_URL;
                $statsUrl = SendinblueClient::SMS_STATISTICS_URL;
                $emailMarketingUrl = SendinblueClient::EMAIL_MARKETING_URL;
                $automationWorkflowUrl = SendinblueClient::AUTOMATION_WORKFLOW_URL;
                $automationUrl = SendinblueClient::AUTOMATION_URL;
                $chatsUrl = SendinblueClient::CHAT;
                $dashboardUrl = SendinblueClient::DASHBOARD_URL;
				$conversationsUrl = SendinblueClient::CONVERSATIONS_URL;

                include SENDINBLUE_WC_ROOT_PATH . '/src/views/admin_menus.php';

                return;
            }

    		$key = $this->api_manager->get_key();
	    	if (empty($key)) {
	    		$key = $this->api_manager->create_key();
	    	}

            $query_params['pluginVersion'] = SENDINBLUE_WC_PLUGIN_VERSION;
            $query_params['shopVersion'] = SENDINBLUE_WORDPRESS_SHOP_VERSION;
            $query_params['consumerKey'] = $key->consumer_key;
            $query_params['consumerSecret'] = $key->consumer_secret;
            $query_params['language'] = current(explode("_", get_locale()));
            $query_params['url'] = get_site_url();
            $query_params['callback'] = $query_params['url'] . '/index.php?pagename=sendinblue-callback';

            $connectUrl = SendinblueClient::INTEGRATION_URL . SendinblueClient::CONNECT_URL . '?' . http_build_query($query_params);

            include SENDINBLUE_WC_ROOT_PATH . '/src/views/admin_view.php';
    		
    	} catch (Exception $e) {
    		wp_die(__($e->getMessage()));
    	}
    }

    public function install_ma_and_chat_script()
    {
        $settings = $this->api_manager->get_settings();

        if (
            empty($settings) ||
            !$settings[SendinblueClient::IS_PAGE_TRACKING_ENABLED] ||
            !$settings[SendinblueClient::MA_KEY]
        ) {
            return;
        }
        $output = '<!-- Sendinblue Marketing automation WooCommerce integration and Chat: start -->';
        $output .= '<script type="text/javascript">
                    (function() {window.sib ={equeue:[],client_key:"' . $settings[SendinblueClient::MA_KEY] . '"};';
        $found_email_id = $this->cart_events_manager->get_email_id();
        if ( $found_email_id ) {
            $output .= 'window.sib.email_id = "' . $found_email_id . '";';
        }
        $output .= 'window.sendinblue = {}; for (var j = [\'track\', \'identify\', \'trackLink\', \'page\'], i = 0; i < j.length; i++) { (function(k) { window.sendinblue[k] = function() { var arg = Array.prototype.slice.call(arguments); (window.sib[k] || function() { var t = {}; t[k] = arg; window.sib.equeue.push(t);})(arg[0], arg[1], arg[2]);};})(j[i]);}var n = document.createElement("script"),i = document.getElementsByTagName("script")[0]; n.type = "text/javascript", n.id = "sendinblue-js", n.async = !0, n.src = "https://sibautomation.com/sa.js?key=" + window.sib.client_key, i.parentNode.insertBefore(n, i), window.sendinblue.page();})();</script>';
        $output .= '<!-- Sendinblue Marketing automation WooCommerce integration and Chat: end -->';
        echo $output;
    }
}
