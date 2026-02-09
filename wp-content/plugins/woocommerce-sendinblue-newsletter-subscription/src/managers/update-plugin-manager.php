<?php

namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Clients\SendinblueClient;
use SendinblueWoocommerce\Managers\ApiManager;

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class UpdatePluginManagers
 *
 * @package SendinblueWoocommerce\Managers
 */
class UpdatePluginManagers
{
    private $client_manager;

    private $api_manager;

    function __construct()
    {
        $this->client_manager = new SendinblueClient();
        $this->api_manager = new ApiManager();
    }

    private function is_old_connection()
    {
        if (empty(get_option(API_KEY_V3_OPTION_NAME, null))) {
            return false;
        }

        if (get_option(SENDINBLUE_WOOCOMMERCE_UPDATE, null) === null) {
            add_option(SENDINBLUE_WOOCOMMERCE_UPDATE, false);
        }

        return true;
    }

    private function fetch_old_settings()
    {
        return get_option('wc_sendinblue_settings', null);
    }

    private function defaultSettings()
    {
        $settings = array();
        $settings['isAutoSyncEnabled'] = false;
        $settings['isAutoMatchGroupEnabled'] = false;
        $settings['isAutoMatchRecipientAttributeEnabled'] = false;
        $settings['isAutoMatchItemAttributeEnabled'] = false;
        $settings['listId'] = null;
        $settings['isPageTrackingEnabled'] = false;
        $settings['isAbandonedCartTrackingEnabled'] = false;
        $settings['isContactStateSyncEnabled'] = false;
        $settings['subscriptionMailing'] = false;
        $settings['subscriptionMailingType'] = null;
        $settings['subscriptionRedirectUrlEnabled'] = false;
        $settings['subscriptionRedirectUrl'] = false;
        $settings['subscriptionSimpleConfirmationId'] = null;
        $settings['subscriptionDoiConfirmationId'] = null;
        $settings['subscriptionFinalConfirmationEnabled'] = false;
        $settings['subscriptionFinalConfirmationId'] = null;
        $settings['contactSyncType'] = null;
        $settings['isSmtpEnabled'] = false;
        $settings['isCustomerSyncEnabled'] = false;
        $settings['smtpSender'] = null;
        $settings['isMultipleListsSelected'] = false;
        $settings['isSubscribeEventEnabled'] = null;
        $settings['isDisplayOptInChecked'] = false;
        $settings['displayOptInLabel'] = '';
        $settings['isDisplayOptInEnabled'] = false;
        $settings['displayOptInLocation'] = 1;
        $settings['isSmsFeatureEnabled'] = false;
        $settings['isOrderShipmentSmsEnable'] = false;
        $settings['isOrderConfirmationSmsEnable'] = false;
        $settings['smsForOrderConfirmation'] = null;
        $settings['smsForOrderShipment'] = null;

        return $settings;
    }

    private function defaultEmailSettings()
    {
        $settings = array();
        $settings['isSmtpEnabled'] = false;
        $settings['isEmailOptionsEnabled'] = false;

        return $settings;
    }

    private function prepare_settings_collection($settings)
    {
        $new_settings = $this->defaultSettings();

        if (isset($settings['ws_subscription_enabled']) || $settings['ws_subscription_enabled'] == 'yes') {
            $new_settings['subscriptionMailing'] = true;
            $new_settings['isAutoSyncEnabled'] = true;
            $new_settings['contactSyncType'] = 1;
            $new_settings['listId'] = (int)$settings['ws_sendinblue_list'];
            $new_settings['isAutoMatchRecipientAttributeEnabled'] = isset($settings['ws_enable_match_attributes']) && $settings['ws_enable_match_attributes'] == 'yes' ? false : true;
            $new_settings['isSubscribeEventEnabled'] = $settings['ws_order_event'] == 'completed' ? 2 : 1;
            if (!empty($settings['ws_matched_lists'])) {
                $new_settings['mappedAttributes'] = $settings['ws_matched_lists'];
                foreach ($new_settings['mappedAttributes'] as $key => $value) {
                    $new_settings['mappedAttributes'][$key] = str_replace("shipping_","shipping.", $new_settings['mappedAttributes'][$key]);
                    $new_settings['mappedAttributes'][$key] = str_replace("billing_","billing.", $new_settings['mappedAttributes'][$key]);
                }
            }
            $new_settings['subscriptionMailingType'] = isset($settings['ws_dopt_enabled']) && $settings['ws_dopt_enabled'] == 'yes' ? 1 : 2;
            if (!empty($settings['ws_dopt_templates'])) {
                $new_settings['subscriptionDoiConfirmationId'] = (int)$settings['ws_dopt_templates'];
                if ($new_settings['subscriptionDoiConfirmationId'] == 0) {
                    $new_settings['subscriptionDoiConfirmationId'] = 1;
                }
            }
        }

        if (isset($settings['ws_marketingauto_enable']) && $settings['ws_marketingauto_enable'] == 'yes') {
            $new_settings['isPageTrackingEnabled'] = true;
            $new_settings['isAbandonedCartTrackingEnabled'] = true;
        }

        if (isset($settings['ws_opt_field']) && $settings['ws_opt_field'] == 'yes') {
            $new_settings['isDisplayOptInEnabled'] = true;
            $new_settings['displayOptInLabel'] = empty($settings['ws_opt_field_label']) ? '' : $settings['ws_opt_field_label'];
            $new_settings['isDisplayOptInChecked'] = $settings['ws_opt_default_status'] == 'checked' ? true : false;
            $new_settings['displayOptInLocation'] = 1;
            if ($settings['ws_opt_checkbox_location'] == 'terms_condition') {
                $new_settings['displayOptInLocation'] = 3;
            } elseif ($settings['ws_opt_checkbox_location'] == 'order') {
                $new_settings['displayOptInLocation'] = 2;
            }
        }

        if (isset($settings['ws_sms_enable']) && $settings['ws_sms_enable'] == 'yes') {
            $new_settings['isSmsFeatureEnabled'] = true;
            $new_settings['isOrderShipmentSmsEnable'] = isset($settings['ws_sms_send_shipment']) && $settings['ws_sms_send_shipment'] == 'yes' ? true : false;
            $new_settings['isOrderConfirmationSmsEnable'] = isset($settings['ws_sms_send_after']) && $settings['ws_sms_send_after'] == 'yes' ? true : false;
            if ($new_settings['isOrderConfirmationSmsEnable']) {
                $temp = array(
                    'sender' => $settings['ws_sms_sender_after'],
                    'message' => $settings['ws_sms_send_msg_desc_after']
                );
                $new_settings['smsForOrderConfirmation'] = json_encode($temp);
            }
            if ($new_settings['isOrderShipmentSmsEnable']) {
                $temp = array(
                    'sender' => $settings['ws_sms_sender_shipment'],
                    'message' => $settings['ws_sms_send_msg_desc_shipment']
                );
                $new_settings['smsForOrderShipment'] = json_encode($temp);
            }
        }

        return $new_settings;
    }

    private function prepare_email_settings_collection($settings)
    {
        $new_settings = $this->defaultEmailSettings();

        if (isset($settings['ws_smtp_enable']) && $settings['ws_smtp_enable'] == 'yes') {
            $new_settings['isSmtpEnabled'] = true;
            $new_settings['isEmailOptionsEnabled'] = true;
        }

        $is_sendinblue_email = false;
        if (isset($settings['ws_email_templates_enable']) && $settings['ws_email_templates_enable'] == 'yes') {
            $is_sendinblue_email = true;
        }

        $email_enabled = array(
            'WC_Email_New_Order' => ['isNewOrderEmailEnabled',
            'isNewOrderTemplateEnabled'],
            'WC_Email_Customer_Processing_Order' => ['isProcessingOrderEmailEnabled',
            'isProcessingOrderTemplateEnabled'],
            'WC_Email_Customer_Refunded_Order' => ['isRefundedOrderEmailEnabled',
            'isRefundedOrderTemplateEnabled'],
            'WC_Email_Cancelled_Order' => ['isCancelledOrderEmailEnabled',
            'isCancelledOrderTemplateEnabled'],
            'WC_Email_Customer_Completed_Order' => ['isCompletedOrderEmailEnabled',
            'isCompletedOrderTemplateEnabled'],
            'WC_Email_Customer_New_Account' => ['isNewAccountEmailEnabled',
            'isNewAccountTemplateEnabled'],
            'WC_Email_Customer_On_Hold_Order' => ['isOnHoldOrderEmailEnabled',
            'isOnHoldOrderTemplateEnabled'],
            'WC_Email_Failed_Order' => ['isFailedOrderEmailEnabled',
            'isFailedOrderTemplateEnabled'],
            'WC_Email_Customer_Note' => ['isCustomerNoteEmailEnabled',
            'isCustomerNoteTemplateEnabled']
        );

        $wc_emails = (array) \WC_Emails::instance()->emails;
        foreach ($email_enabled as $key => $value) {
            $enabled = (array) $wc_emails[$key] ;
            if ($enabled["enabled"] == 'no') {
                $new_settings[$value[0]] = false;
                $new_settings[$value[1]] = false;
            }
            else {
                $new_settings[$value[0]] = true;
                $new_settings[$value[1]] = $is_sendinblue_email;
            }
        }

        $email_template = array(
            'ws_new_order_template' => 'newOrderTemplateId',
            'ws_processing_order_template' => 'processingOrderTemplateId',
            'ws_refunded_order_template' => 'refundedOrderTemplateId',
            'ws_cancelled_order_template' => 'cancelledOrderTemplateId',
            'ws_completed_order_template' => 'completedOrderTemplateId',
            'ws_failed_order_template' => 'failedOrderTemplateId',
            'ws_new_account_template' => 'newAccountTemplateId',
            'ws_on_hold_order_template' => 'onHoldOrderTemplateId',
            'ws_customer_note_template' => 'customerNoteTemplateId'

        );

        foreach ($email_template as $key => $value) {
            if (!empty($settings[$key])) {
                $new_settings[$value] = (int)$settings[$key];
            }
            else {
                $new_settings[$value] = null;
            }
        }

        $new_settings_template = array(
            'newOrderTemplateId' => 'isNewOrderTemplateEnabled',
            'processingOrderTemplateId' => 'isProcessingOrderTemplateEnabled',
            'refundedOrderTemplateId' => 'isRefundedOrderTemplateEnabled',
            'cancelledOrderTemplateId' => 'isCancelledOrderTemplateEnabled',
            'completedOrderTemplateId' => 'isCompletedOrderTemplateEnabled',
            'newAccountTemplateId' => 'isNewAccountTemplateEnabled',
            'onHoldOrderTemplateId' => 'isOnHoldOrderTemplateEnabled',
            'failedOrderTemplateId' => 'isFailedOrderTemplateEnabled',
            'customerNoteTemplateId' => 'isCustomerNoteTemplateEnabled'
        );

        foreach ($new_settings_template as $key => $value) {
            $new_settings[$value] = is_null($new_settings[$key]) ? false : true;
        }

        return $new_settings;
    }

    private function get_settings()
    {
        $new_settings = array(
            'connection_settings' => array() ,
            'email_settings' => array()
        );

        if (!$this->is_old_connection()) {
            return $new_settings;
        }

        $settings = $this->fetch_old_settings();

        if (empty($settings)) {
            return $new_settings;
        }

        $new_settings['connection_settings'] = $this->prepare_settings_collection($settings);
        $new_settings['email_settings'] = $this->prepare_email_settings_collection($settings);

        return $new_settings;
    }

    public function send_settings()
    {
        if (!$this->is_old_connection()) {
            return;
        }

        if (get_option(SENDINBLUE_WOOCOMMERCE_UPDATE, null)) {
            return;
        }

        update_option(SENDINBLUE_WOOCOMMERCE_UPDATE, true);
        $settings = $this->get_settings();
        $settings['apiKeyV3'] = get_option(API_KEY_V3_OPTION_NAME, null);
        $settings['connector'] = 'WC';
        $settings['name'] = 'WooCommerce';
        $settings['shop_version'] = SENDINBLUE_WORDPRESS_SHOP_VERSION;
        $settings['plugin_version'] = SENDINBLUE_WC_PLUGIN_VERSION;
        $settings['language'] = current(explode("_", get_locale()));

        $key = $this
            ->api_manager
            ->get_key();
        if (empty($key)) {
            $key = $this
                ->api_manager
                ->create_key();
        }
        $settings['auth'] = array(
            'url' => get_site_url() ,
            "consumerKey" => $key->consumer_key,
            "consumerSecret" => $key->consumer_secret
        );

        $response = $this
            ->client_manager
            ->saveSettings($settings);

        $this->api_manager->flush_option_keys(API_KEY_V3_OPTION_NAME);
    }

    public function enable_ecommerce()
    {
        $settings = $this->api_manager->get_settings();
        $last_call = get_option(SENDINBLUE_ECOMMERCE_CALLED_TIME, 120);
        if ((time() - $last_call) < 120 || get_option(SENDINBLUE_WC_ECOMMERCE_REQ, false)) {
            return;
        }

        (get_option(SENDINBLUE_WC_ECOMMERCE_REQ, null) !== null) ? update_option(SENDINBLUE_WC_ECOMMERCE_REQ, true) : add_option(SENDINBLUE_WC_ECOMMERCE_REQ, true);

        (get_option(SENDINBLUE_ECOMMERCE_CALLED_TIME, null) !== null) ? update_option(SENDINBLUE_ECOMMERCE_CALLED_TIME, time()) : add_option(SENDINBLUE_ECOMMERCE_CALLED_TIME, time());

        //this will stop the calls as IS_ECOMMERCE_ENABLED is never being set,
        //thus we keep getting incessent calls on MS BE
        if (empty($settings[SendinblueClient::IS_PRODUCT_SYNC_ENABLED])) {
            $response = $this->client_manager->enableEcommerce();
            if (!empty($response) && $response['code'] == 201) {
                update_option(SENDINBLUE_WC_ECOMMERCE_REQ, true);
            } else {
                update_option(SENDINBLUE_WC_ECOMMERCE_REQ, false);
            }
        }
    }

    public function post_update()
    {
        $user_connection_id = get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null);
        $sendinblue_version = get_option(SENDINBLUE_WC_VERSION_SENT, null);

        if (empty($user_connection_id)) {
            return false;
        }

        if (!empty($sendinblue_version) && ($sendinblue_version == SENDINBLUE_WC_PLUGIN_VERSION)) {
            return false;
        }

        $data = array(
            'plugin_version' => SENDINBLUE_WC_PLUGIN_VERSION,
            'shop_version' => SENDINBLUE_WORDPRESS_SHOP_VERSION
        );

        if (class_exists( 'Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) && 
            (\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default())
        ) {
            $data['settings']['is_checkout_block_default'] = true;
        }

        $this->client_manager->eventsSync(SendinblueClient::PLUGIN_UPDATED, $data);
        (get_option(SENDINBLUE_WC_VERSION_SENT, null) !== null) ? update_option(SENDINBLUE_WC_VERSION_SENT, SENDINBLUE_WC_PLUGIN_VERSION) : add_option(SENDINBLUE_WC_VERSION_SENT, SENDINBLUE_WC_PLUGIN_VERSION);
    }
}
