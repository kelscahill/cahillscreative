<?php


namespace SendinblueWoocommerce\Clients;

/**
 * Class SendinblueClient
 *
 * @package SendinblueWoocommerce\Clients
 */
class SendinblueClient
{
    public const INTEGRATION_URL                        = 'https://app.brevo.com/integrations/';
	public const CONNECT_URL                            = 'connect/WC/';
	public const SETTINGS_URL                           = '/settings';
	public const SMS_CAMPAIGN_URL                       = 'https://my.brevo.com/camp/lists/sms';
	public const SMS_CHAT_URL                           = 'https://account-app.brevo.com/account/apps/?utm_source=woocommerce_plugin&utm_medium=plugin&utm_campaign=module_link';
	public const SMS_STATISTICS_URL                     = 'https://my.brevo.com/camp/lists/sms';
	public const EMAIL_MARKETING_URL                    = 'https://my.brevo.com/dashboard';
	public const DASHBOARD_URL                          = 'https://my.brevo.com/dashboard';
	public const AUTOMATION_URL                         = 'https://automation.brevo.com/';
	public const AUTOMATION_WORKFLOW_URL                = 'https://automation.brevo.com/app/workflow';
	public const CHAT                                   = 'https://app.brevo.com/chat';
	public const CONVERSATIONS_URL                      = 'https://conversations-app.brevo.com/';

	public const DELETE_CONNECTION                      = '/app_uninstalled';
	public const CONTACT_CREATED                        = '/contact_created';
	public const ORDER_CREATED                          = '/order_created';
	public const SMS_ORDER_SHIPMENT                     = '/sms_send?action=order_shipment';
	public const SMS_ORDER_CONFIRMATION                 = '/sms_send?action=order_confirmation';
	public const EMAIL_SEND                             = "/email_send?action=email_event";

	public const PRODUCT_CREATED                        = '/products_sync?action=product_created';
	public const PRODUCT_VIEWED                         = '/product_viewed';
	public const PRODUCT_DELETED                        = '/products_sync?action=product_deleted';
	public const CATEGORY_CREATED                       = '/collection_sync?action=collection_created';
	public const CATEGORY_UPDATED                       = '/collection_sync?action=collection_updated';
	public const CATEGORY_DELETED                       = '/collection_sync?action=collection_deleted';
	public const ORDER_CREATE                           = '/order_tracking?action=order_create';
	public const ORDER_PAID                             = '/order_tracking?action=order_paid';
	public const ORDER_REFUND                           = '/order_tracking?action=order_refund';
	public const ORDER_CANCELLED                        = '/order_tracking?action=order_cancelled';
	public const PLUGIN_UPDATED                         = '/plugin_updated';

	public const IS_PAGE_TRACKING_ENABLED               = 'isPageTrackingEnabled';
	public const IS_ABANDONED_CART_ENABLED              = 'isAbandonedCartTrackingEnabled';
	public const MA_KEY                                 = 'marketingAutomationKey';
	public const IS_ORDER_CONFIRMATION_SMS              = 'isOrderConfirmationSMSEnable';
	public const IS_ORDER_SHIPMENT_SMS                  = 'isOrderShipmentSMSEnable';
	public const NEW_ORDER_STATUS                       = 'on-hold|processing';
	public const COMPLETED_ORDER_STATUS                 = 'completed';
	public const CANCELLED_ORDER_STATUS                 = 'cancelled';
	public const FAILED_ORDER_STATUS                    = 'failed';
	public const IS_SUBSCRIBE_EVENT_ENABLED             = 'isSubscribeEventEnabled';
	public const IS_SUBSCRIPTION_EMAIL_ENABLED          = 'subscriptionMailing';
	public const SUBSCRIPTION_EMAIL_TYPE                = 'isSubscribeEventEnabled';
	public const IS_EMAIL_FEATURE_ENABLED               = 'isEmailOptionsEnabled';
	public const IS_NEW_ORDER_EMAIL_ENABLED             = "isNewOrderEmailEnabled";
	public const IS_NEW_ORDER_TEMPLATE_ENABLED          = "isNewOrderTemplateEnabled";
	public const NEW_ORDER_TEMPLATE_ID                  = "newOrderTemplateId";
	public const IS_PROCESSING_ORDER_EMAIL_ENABLED      = "isProcessingOrderEmailEnabled";
	public const IS_PROCESSING_ORDER_TEMPLATE_ENABLED   = "isProcessingOrderTemplateEnabled";
	public const PROCESSING_ORDER_TEMPLATE_ID           = "processingOrderTemplateId";
	public const IS_REFUNDED_ORDER_EMAIL_ENABLED        = "isRefundedOrderEmailEnabled";
	public const IS_REFUNDED_ORDER_TEMPLATE_ENABLED     = "isRefundedOrderTemplateEnabled";
	public const REFUNDED_ORDER_TEMPLATE_ID             = "refundedOrderTemplateId";
	public const IS_CANCELLED_ORDER_EMAIL_ENABLED       = "isCancelledOrderEmailEnabled";
	public const IS_CANCELLED_ORDER_TEMPLATE_ENABLED    = "isCancelledOrderTemplateEnabled";
	public const CANCELLED_ORDER_TEMPLATE_ID            = "cancelledOrderTemplateId";
	public const IS_COMPLETED_ORDER_EMAIL_ENABLED       = "isCompletedOrderEmailEnabled";
	public const IS_COMPLETED_ORDER_TEMPLATE_ENABLED    = "isCompletedOrderTemplateEnabled";
	public const COMPLETED_ORDER_TEMPLATE_ID            = "completedOrderTemplateId";
	public const IS_ON_HOLD_ORDER_EMAIL_ENABLED         = "isOnHoldOrderEmailEnabled";
	public const IS_ON_HOLD_ORDER_TEMPLATE_ENABLED      = "isOnHoldOrderTemplateEnabled";
	public const ON_HOLD_ORDER_TEMPLATE_ID              = "onHoldOrderTemplateId";
	public const IS_FAILED_ORDER_EMAIL_ENABLED          = "isFailedOrderEmailEnabled";
	public const IS_FAILED_ORDER_TEMPLATE_ENABLED       = "isFailedOrderTemplateEnabled";
	public const FAILED_ORDER_TEMPLATE_ID               = "failedOrderTemplateId";
	public const IS_CUSTOMER_NOTE_EMAIL_ENABLED         = "isCustomerNoteEmailEnabled";
	public const IS_CUSTOMER_NOTE_TEMPLATE_ENABLED      = "isCustomerNoteTemplateEnabled";
	public const CUSTOMER_NOTE_TEMPLATE_ID              = "customerNoteTemplateId";
	public const IS_NEW_ACCOUNT_EMAIL_ENABLED           = "isNewAccountEmailEnabled";
	public const IS_NEW_ACCOUNT_TEMPLATE_ENABLED        = "isNewAccountTemplateEnabled";
	public const NEW_ACCOUNT_TEMPLATE_ID                = "newAccountTemplateId";
	public const IS_DISPLAY_OPT_IN_ENABLED              = "isDisplayOptInEnabled";
	public const IS_DISPLAY_OPT_IN_CHECKED              = "isDisplayOptInChecked";
	public const DISPLAY_OPT_IN_LABEL                   = "displayOptInLabel";
	public const DISPLAY_OPT_IN_LOCATION                = "displayOptInLocation";
	public const IS_PRODUCT_SYNC_ENABLED                = "isProductsAutoSyncEnabled";
	public const IS_CATEGORY_SYNC_ENABLED               = "isCategoryAutoSyncEnabled";
	public const IS_ORDERS_SYNC_ENABLED                 = "isOrdersAutoSyncEnabled";
	public const IS_ECOMMERCE_ENABLED                   = "isEcommerceEnabled";

	private const INTEGRATION_BACKEND_URL               = 'https://plugin.brevo.com/integrations/api';
	private const HTTP_METHOD_GET                       = 'GET';
	private const HTTP_METHOD_POST                      = 'POST';
	private const INTEGRATION_MIGRATION_URL             = '/migrate/woocommerce';
	private const USER_AGENT                            = 'sendinblue_plugins/woocommerce_common';
	private const ECOMMERCE_PATH                        = '/ecommerce/';

	private function post($endpoint, $data = array())
	{
		return $this->makeHttpRequest(self::HTTP_METHOD_POST, $endpoint, $data);
	}

	private function makeHttpRequest($method, $endpoint, $body = array())
	{
		$url = self::INTEGRATION_BACKEND_URL . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'x-sib-plugin-version' => 'wc-' . SENDINBLUE_WC_PLUGIN_VERSION,
				'x-sib-shop-version' => 'wc-' . SENDINBLUE_WORDPRESS_SHOP_VERSION,
				'User-Agent' => self::USER_AGENT
			),
		);

		if (self::HTTP_METHOD_GET != $method) {
			$args['body'] = wp_json_encode($body);
		}

		$response               = wp_remote_request($url, $args);
		$data                   = wp_remote_retrieve_body($response);
		$response_code          = wp_remote_retrieve_response_code($response);

		return [
			'data' => json_decode($data, true),
			'code' => $response_code
		];
	}

	public function eventsSync($event, $data = array())
	{
		$user_connection_id = get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null);
		if (empty($user_connection_id)) {
			return;
		}

		if (empty($data)) {
			$data = array(
				"userConnectionId" => $user_connection_id
			);
		}

		$endpoint = '/events/' . $user_connection_id . $event;
		$this->post($endpoint, $data);
	}

	public function saveSettings($data = array())
	{
		$endpoint = self::INTEGRATION_MIGRATION_URL;
		return $this->post($endpoint, $data);
	}

	public function enableEcommerce()
	{
		$user_connection_id = get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null);
		if (empty($user_connection_id)) {
			return false;
		}

		$data = array(
			"userConnectionId" => $user_connection_id
		);

		return $this->post(self::ECOMMERCE_PATH . $user_connection_id, $data);
	}
}
