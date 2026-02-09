<?php
if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_API' ) ) {
	class SIB_Push_API {

		const NONCE_ACTION = 'ajax_sib_admin_nonce';
		const ADMIN_ACCESS = 'admin';
		const EDITOR_ACCESS = 'editor';
		private static $nonce = null;

		public static function init() {
			add_action( 'wp_ajax_sib_get_push_configuration', array( 'SIB_Push_API', 'ajax_get_push_configuration' ) );
			add_action( 'wp_ajax_sib_update_push_configuration', array( 'SIB_Push_API', 'ajax_update_push_configuration' ) );
			add_action( 'wp_ajax_sib_push_get_post_metadata', array('SIB_Push_API', 'ajax_get_post_metadata'));
			add_action( 'wp_ajax_sib_push_set_push_activated', array('SIB_Push_API', 'ajax_set_push_activated'));
			add_action( 'wp_ajax_sib_push_management_api', array('SIB_Push_API', 'ajax_management_api'));
			add_action( 'wp_ajax_sib_push_upload', array('SIB_Push_API', 'ajax_upload'));
			add_action( 'wp_ajax_sib_push_force_create_cart_reminder_campaign', array('SIB_Push_API', 'ajax_force_create_cart_reminder_campaign'));
			add_action('updated_option', array('SIB_Push_API', 'updated_option'), 10, 3);
			self::prepare();
		}

		private static function prepare_cache_key() {
			return 'sib_push_prepare_' . md5( SIB_Manager::$access_key );
		}

		private static function prepare() {
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) return;
			try {
				if ( !$settings->getShowPush() ) {
					$settings->setShowPush ( SIB_Push_Utils::get_show_push() );
					if ( $settings->getShowPush() ) $settings->save();
				}
				if ( get_transient( self::prepare_cache_key() ) === 'prepared' ) {
					return;
				}
				set_transient( self::prepare_cache_key(), 'prepared', 86400 );
				if ( SIB_Push_Utils::get_push_application() ) {
					SIB_Push_Utils::update_settings();
					return;
				}
				SIB_Push_Utils::create_push_application( 'prepare' );
				$settings->save();
			} catch ( \WonderPush\Errors\Server $e ) {
				$code = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
				if ( $code !== 429 ) {
					SIB_Push_Utils::log_warn( 'Error creating application', $e );
				} else {
//					SIB_Push_Utils::log_debug( 'Refusing to create application', $e );
				}
			} catch ( SIB_Push_MissingCredentialsException $e) {
				// Ignore
			} catch ( Exception $e ) {
				SIB_Push_Utils::log_debug('Error creating application', $e);
			}
		}

		public static function updated_option($option, $old_value, $value) {
			if ( $option === 'siteurl' && SIB_Push_Utils::get_push_application() ) {
				// Site url's changed, bust prepare cache hoping that the next request will get the right plugin url
				delete_transient( self::prepare_cache_key() );
			}
		}

		public static function get_nonce() {
			if (self::$nonce === null) self::$nonce = wp_create_nonce(self::NONCE_ACTION);
			return self::$nonce;
		}

		private static function verify_nonce() {
			$nonce = '';
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$nonce = $_POST['nonce'];
			} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
				$nonce = $_GET['nonce'];
			}
			if (!$nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
				wp_die('Forbidden', 403);
			}
		}

		private static function verify_access($access_type = SIB_Push_API::ADMIN_ACCESS) {
			self::verify_nonce();
			if ($access_type === SIB_Push_API::EDITOR_ACCESS) {
				if (!SIB_Push_Utils::can_send_notifications()) {
					wp_die('Forbidden', 403);
				}
			} else {
				if (!SIB_Push_Utils::can_modify_settings()) {
					wp_die('Forbidden', 403);
				}
			}
		}

		private static function returnResult($result) {
			header('Content-Type: application/json');
			$json = json_encode($result);
			echo $json;
			wp_die();
		}

		private static function returnError($msg, $statusCode) {
			header('Content-Type: application/json');
			wp_die(json_encode(array(
				'error' => array(
					'message' => $msg,
					'code' => $statusCode,
				),
			)), $statusCode);
		}

		public static function ajax_upload() {
			self::verify_access();
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			try {
				$app = SIB_Push_Utils::get_push_application(SIB_Push_Utils::DEFAULT_CACHE_TTL);
			} catch (Exception $e) {
				SIB_Push_Utils::log_warn('Could not get application', $e);
				self::returnError('Could not get application', 500);
			}
			$wp = SIB_Push_Utils::management_api_client($credentials);
			$request = $wp->rest()->request('POST', 'applications/' . urlencode($app->getId()) . '/upload');
			$image = $_FILES['image'];
			if (!$image) {
				self::returnError('Missing image', 400);
			}
			$request->addFile('image', $image['name'], $image['tmp_name'], $image['type']);
			$response = $wp->rest()->execute($request);
			$responseHeaders = $response->getHeaders();
			if (isset($responseHeaders['content-type'])) {
				header('Content-Type: ' . $responseHeaders['content-type']);
			}
			wp_die($response->getRawBody(), $response->getStatusCode());
		}

		public static function ajax_management_api() {
			self::verify_access();
			$method = isset($_POST['method']) ? $_POST['method'] : null;
			$url = isset($_POST['url']) ? $_POST['url'] : null;
			$body = isset($_POST['body']) ? wp_unslash($_POST['body']) : null;

			switch ($method) {
				case \WonderPush\Net\Request::GET:
				case \WonderPush\Net\Request::PUT:
				case \WonderPush\Net\Request::POST:
				case \WonderPush\Net\Request::PATCH:
				case \WonderPush\Net\Request::DELETE:
					break;
				default:
					$method = null;
			}
			if (!$method || !$url) {
				self::returnError('Missing method or url', 400);
			}
			$params = $body !== null ? json_decode($body, false) : array();
			if (json_last_error()) {
				self::returnError('Invalid JSON body', 400);
			}

			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();

			$wp = SIB_Push_Utils::management_api_client($credentials);

			$request = $wp->rest()->request($method, '../../' . $url, $params);
			$response = $wp->rest()->execute($request);
			$responseHeaders = $response->getHeaders();
			if (isset($responseHeaders['content-type'])) {
				header('Content-Type: ' . $responseHeaders['content-type']);
			}

			// Intercept certain calls. We'll have to treat the special wonderpush/v1/batch as well
			$reqsToCheck = array();
			if ($method === 'POST' && $url === 'wonderpush/v1/batch' && isset($params->requests)) {
				$reqsToCheck = array_map(function ($req) { return array($req->method, 'wonderpush'.$req->path); }, $params->requests);
			} else {
				$reqsToCheck = array(array($method, $url));
			}

			foreach ($reqsToCheck as $req) {
				$reqMethod = $req[0];
				$reqUrl = $req[1];
				// Intercept cart reminder campaign update to clear the cache
//				NOTE: deactivate woocommerce
				$cartReminderCampaign = null;
//				$cartReminderCampaign = SIB_Push_WooCommerce::ensure_cart_reminder_campaign_exists();
				if ($cartReminderCampaign && ($reqMethod === 'PATCH' || $reqMethod === 'DELETE') && str_starts_with($reqUrl, 'wonderpush/v1/campaigns/'.$cartReminderCampaign->getId())) {
					try {
//						SIB_Push_Utils::log_debug('Clearing cart reminder cache');
						SIB_Push_WooCommerce::clear_cart_reminder_campaign_cache();
					} catch ( Exception $e ) {
						SIB_Push_Utils::log_warn('Could not clear cart reminder cache', $e);
					}
				}

				// Intercept application updates to clear the cache
				if ($reqMethod === 'PATCH' && str_starts_with($reqUrl, 'wonderpush/v1/applications/')) {
					$app = SIB_Push_Utils::get_push_application();
					if ($app && $reqUrl === 'wonderpush/v1/applications/' . $app->getId()) {
						try {
//							SIB_Push_Utils::log_debug('Clearing application cache');
							SIB_Push_Utils::clear_push_application_cache();
						} catch ( Exception $e ) {
							SIB_Push_Utils::log_warn('Could not clear application cache', $e);
						}
					}
				}
			}

			wp_die($response->getRawBody(), $response->getStatusCode());
		}

		public static function ajax_set_push_activated() {
			self::verify_access();
			if (array_key_exists('activated', $_POST)) {
				try {
					$app = null;
					if ($_POST['activated'] === 'true') {
						$app = SIB_Push_Utils::activate_push();
						if ($app && $app->getStatus() === 'creation') {
							$app = SIB_Push_Utils::pollApplicationCreation();
						}
					} else {
						SIB_Push_Utils::deactivate_push();
					}
					self::returnResult(array('application' => $app, 'configuration' => self::get_push_configuration()));
				}	catch (Exception $e) {
					self::returnError($e->getMessage(), 500);
				}
			}
		}

		public static function get_push_configuration() {
			$settings = SIB_Push_Settings::getSettings();
			$app = SIB_Push_Utils::get_push_application();
			return (object)array(
				'applicationId' => $app && $app->getId() ? $app->getId() : null,
				'websiteUrl' => get_site_url(),
				'websiteName' => get_bloginfo('name'),
				'pushOptions' => SIB_Push_Utils::wonderpush_init_options(),
				'imgUrl' => plugins_url('img', dirname(__FILE__)),
				'bypassWordPressHttpClient' => $settings->getBypassWordPressHttpClient(),
				'deliveryTimeSeconds' => $settings->getDeliveryTimeSeconds(),
				'notificationTitle' => $settings->getNotificationTitle(),
				'defaultTargetSegmentId' => (int)$settings->getDefaultTargetSegmentId() ?: null,
				'defaultTargetListId' => (int)$settings->getDefaultTargetListId() ?: null,
				'additionalCustomPostTypes' => $settings->getAdditionalCustomPostTypes(),
				'disableSendOnPublish' => $settings->getDisableSendOnPublish(),
				'disableSendByDefaultOnPublish' => $settings->getDisableSendByDefaultOnPublish(),
				'sendOnThirdPartyPublish' => $settings->getSendOnThirdPartyPublish(),
				'disableFeedbackOnPublish' => $settings->getDisableFeedbackOnPublish(),
				'disableUsePostImageForNotification' => $settings->getDisableUsePostImageForNotification(),
				'preferLargeImageForNotification' => $settings->getPreferLargeImageForNotification(),
				'cartReminderCampaignId' => $settings->getCartReminderCampaignId(),
				'enableOrderCompleteNotifications' => $settings->getEnableOrderCompleteNotifications(),
				'orderCompleteNotificationsMessage' => $settings->getOrderCompleteNotificationsMessage(),
				'enableOrderProcessingNotifications' => $settings->getEnableOrderProcessingNotifications(),
				'orderProcessingNotificationsMessage' => $settings->getOrderProcessingNotificationsMessage(),
				'disableThankYouEvent' => $settings->getDisableThankYouEvent(),
				'thankYouEventName' => $settings->getThankYouEventName(),
				'disableAmpUnsubscribe' => $settings->getDisableAmpUnsubscribe(),
				'ampSubscribeButtonLabel' => $settings->getAmpSubscribeButtonLabel(),
				'ampUnsubscribeButtonLabel' => $settings->getAmpUnsubscribeButtonLabel(),
				'disableAmpBottomSubscribeButton' => $settings->getDisableAmpBottomSubscribeButton(),
				'disableAmpTopSubscribeButton' => $settings->getDisableAmpTopSubscribeButton(),
				'ampButtonWidth' => (int)$settings->getAmpButtonWidth(),
				'ampButtonHeight' => (int)$settings->getAmpButtonHeight(),
				'additionalInitOptionsJson' => $settings->getAdditionalInitOptionsJson(),
				'hideAdminBarShortcut' => $settings->getHideAdminBarShortcut(),
				'sendOnlyToThisDomain' => $settings->getSendOnlyToThisDomain(),
			);
		}

		public static function ajax_get_push_configuration() {
			self::verify_access();
			self::returnResult(self::get_push_configuration());
		}

		public static function ajax_update_push_configuration() {
			self::verify_access();
			$settings = SIB_Push_Settings::getSettings();
			$save = false;
			// Boolean props
			foreach (array(
						 'bypassWordPressHttpClient',
						 'disableSendOnPublish',
						 'disableSendByDefaultOnPublish',
						 'sendOnThirdPartyPublish',
						 'disableFeedbackOnPublish',
						 'disableUsePostImageForNotification',
						 'preferLargeImageForNotification',
						 'enableOrderCompleteNotifications',
						 'enableOrderProcessingNotifications',
						 'disableAmpUnsubscribe',
						 'disableAmpBottomSubscribeButton',
						 'disableAmpTopSubscribeButton',
						 'disableThankYouEvent',
						 'hideAdminBarShortcut',
						 'sendOnlyToThisDomain',
					 ) as $key) {
				if (array_key_exists($key, $_POST)) {
					$settings->{"set" . ucfirst($key)}($_POST[$key] === 'true');
					$save = true;
				}
			}
			// Notification title
			if (array_key_exists('notificationTitle', $_POST)) {

				// Sanitize user input
				$value = $_POST['notificationTitle']
					? stripslashes(trim(sanitize_text_field($_POST['notificationTitle']))) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setNotificationTitle($value);
				$save = true;
			}
			// Default target segment ID
			if (array_key_exists('defaultTargetSegmentId', $_POST)) {

				// Sanitize user input
				$value = $_POST['defaultTargetSegmentId']
					? (int)trim(sanitize_text_field($_POST['defaultTargetSegmentId'])) : null;

				// Validate
				$value = is_int($value) ? $value : null;

				$settings->setDefaultTargetSegmentId($value);
				$save = true;
			}
			// Default target list ID
			if (array_key_exists('defaultTargetListId', $_POST)) {

				// Sanitize user input
				$value = $_POST['defaultTargetListId']
					? (int)trim(sanitize_text_field($_POST['defaultTargetListId'])) : null;

				// Validate
				$value = is_int($value) ? $value : null;

				$settings->setDefaultTargetListId($value);
				$save = true;
			}
			// Additional custom post types
			if (array_key_exists('additionalCustomPostTypes', $_POST)) {

				// Sanitize user input
				$value = $_POST['additionalCustomPostTypes']
					? stripslashes(trim(sanitize_text_field($_POST['additionalCustomPostTypes']))) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setAdditionalCustomPostTypes($value);
				$save = true;
			}
			// Order confirmation notifications message
			if (array_key_exists('orderCompleteNotificationsMessage', $_POST)) {

				// Sanitize user input
				$value = $_POST['orderCompleteNotificationsMessage']
					? stripslashes(trim(sanitize_text_field($_POST['orderCompleteNotificationsMessage']))) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setOrderCompleteNotificationsMessage($value);
				$save = true;
			}
			// Order confirmation notifications message
			if (array_key_exists('orderProcessingNotificationsMessage', $_POST)) {
				// Sanitize user input
				$value = $_POST['orderProcessingNotificationsMessage']
					? stripslashes(trim(sanitize_text_field($_POST['orderProcessingNotificationsMessage']))) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setOrderProcessingNotificationsMessage($value);
				$save = true;
			}

			// Thank you event name
			if (array_key_exists('thankYouEventName', $_POST)) {
				// Sanitize user input
				$value = $_POST['thankYouEventName']
					? trim(sanitize_text_field($_POST['thankYouEventName'])) : '';

				// Validate user input
				$value = $value && strlen($value) > 256 ? substr($value, 0, 256) : $value;

				$settings->setThankYouEventName($value);
				$save = true;
			}

			// Additional init options
			if (array_key_exists('additionalInitOptionsJson', $_POST)) {
				// Sanitize user input
				$value = $_POST['additionalInitOptionsJson']
					? stripslashes(trim(sanitize_text_field($_POST['additionalInitOptionsJson']))) : null;

				// Validate user input
				if ($value && strlen($value) > 2048) {
					self::returnError(__('Additional init options JSON cannot be larger than 2048 bytes.'), 400);
					return;
				}
				// Validate JSON
				if ($value) {
					$jsonValue = json_decode($value);
					if (json_last_error()) {
						self::returnError(__('Additional init options JSON must be valid JSON: ' + json_last_error_msg()), 400);

					}
					if (!is_object($jsonValue) && $jsonValue !== null) {
						self::returnError(__('Additional init options JSON must be an object or null.'), 400);
						return;
					}
				}
				$value = $value ?: '';

				$settings->setAdditionalInitOptionsJson($value);
				$save = true;
			}
			// AMP Subscribe button label
			if (array_key_exists('ampSubscribeButtonLabel', $_POST)) {
				// Sanitize user input
				$value = $_POST['ampSubscribeButtonLabel']
					? trim(sanitize_text_field($_POST['ampSubscribeButtonLabel'])) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setAmpSubscribeButtonLabel($value);
				$save = true;
			}
			// AMP Unsubscribe button label
			if (array_key_exists('ampUnsubscribeButtonLabel', $_POST)) {
				// Sanitize user input
				$value = $_POST['ampUnsubscribeButtonLabel']
					? trim(sanitize_text_field($_POST['ampUnsubscribeButtonLabel'])) : '';

				// Validate user input
				$value = $value && strlen($value) > 1024 ? substr($value, 0, 1024) : $value;

				$settings->setAmpUnsubscribeButtonLabel($value);
				$save = true;
			}
			// AMP Button width
			if (array_key_exists('ampButtonWidth', $_POST)) {

				// Sanitize
				$value = $_POST['ampButtonWidth']
					? (int)trim(sanitize_text_field($_POST['ampButtonWidth'])) : null;

				// Validate
				$value = is_int($value) ? $value : null;

				$settings->setAmpButtonWidth($value);
				$save = true;
			}
			// AMP Button height
			if (array_key_exists('ampButtonHeight', $_POST)) {

				// Sanitize
				$value = $_POST['ampButtonHeight']
					? (int)trim(sanitize_text_field($_POST['ampButtonHeight'])) : null;

				// Validate
				$value = is_int($value) ? $value : null;

				$settings->setAmpButtonHeight($value);
				$save = true;
			}
			// Delivery time seconds
			if (array_key_exists('deliveryTimeSeconds', $_POST)) {

				// Sanitize
				$value = $_POST['deliveryTimeSeconds']
					? (int)trim(sanitize_text_field($_POST['deliveryTimeSeconds'])) : null;

				// Validate
				$value = is_int($value) ? $value : null;

				$settings->setDeliveryTimeSeconds($value);
				$save = true;
			}
			if ($save) $settings->save();
			// NOTE: deactivate woocommerce
//			SIB_Push_WooCommerce::ensure_cart_reminder_campaign_exists();
			self::ajax_get_push_configuration();
		}

		public static function ajax_get_post_metadata() {
			self::verify_access(SIB_Push_API::EDITOR_ACCESS);
			$post_id = intval($_GET['post_id']);

			if(is_null($post_id)){
				self::returnError('Provide post_id query paramter', 400);
				return;
			}

			$info = get_post_meta($post_id, SIB_Push_Admin::POST_META_INFO_MESSAGE);
			if(is_array($info)){
				$info = $info ? $info[0] : null;
			}

			$error = get_post_meta($post_id, SIB_Push_Admin::POST_META_ERROR_MESSAGE);
			if(is_array($error)){
				$error = $error ? $error[0] : null;
			}

			// reset meta
			delete_post_meta($post_id, SIB_Push_Admin::POST_META_INFO_MESSAGE);
			delete_post_meta($post_id, SIB_Push_Admin::POST_META_ERROR_MESSAGE);

			self::returnResult((object)array('error_message' => $error, 'info_message' => $info));
		}

		public static function ajax_force_create_cart_reminder_campaign() {
			self::verify_access();
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') self::returnError('Method not allowed', 405);
			$campaign = SIB_Push_WooCommerce::ensure_cart_reminder_campaign_exists(true);
			self::returnResult(array('success' => true, 'campaign' => $campaign->toData()));
		}

	}

}
