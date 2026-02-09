<?php
if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_Settings' ) ) {
	class SIB_Push_Settings {
		/** @var SIB_Push_Settings */
		static $instance;
		static $defaults = array(
		);
		/** @var array */
		private $settings;

		private function __construct($settings) {
			if (is_array($settings)) $this->settings = $settings;
			else $this->settings = array();
		}

		private function get($name) {
			if (isset($this->settings[$name])) return $this->settings[$name];
			return array_key_exists($name, self::$defaults) ? self::$defaults[$name] : null;
		}

		private function has($name) {
			return isset($this->settings[$name]);
		}

		private function set($name, $value) {
			if ($value === null) unset($this->settings[$name]);
			else $this->settings[$name] = $value;
			return $this;
		}

		public function getWonderPushCredentials() {
			$apiKey = get_option( SIB_Manager::API_KEY_V3_OPTION_NAME );
			if (!$apiKey) return null;
			return new WonderPush\BrevoAPIKeyV3Credentials($apiKey);
		}

		public function getDeliveryTimeSeconds() {
			return $this->get('deliveryTimeSeconds') ?: 0;
		}

		public function setDeliveryTimeSeconds($value) {
			return $this->set('deliveryTimeSeconds', is_int($value) ? $value : 0);
		}

		public function getBypassWordPressHttpClient() {
			$storedValue = $this->get('bypassWordPressHttpClient');
			if ($storedValue === null) return true; // Bypass by default
			return $storedValue ? true : false;
		}

		public function setBypassWordPressHttpClient($value) {
			return $this->set('bypassWordPressHttpClient', (bool)$value);
		}

		public function getDefaultTargetSegmentId() {
			return $this->get('defaultTargetSegmentId');
		}
		public function setDefaultTargetSegmentId($value) {
			return $this->set('defaultTargetSegmentId', $value ?: null); // Prevent 0
		}
		public function getDefaultTargetListId() {
			return $this->get('defaultTargetListId');
		}
		public function setDefaultTargetListId($value) {
			return $this->set('defaultTargetListId', $value ?: null); // Prevent 0
		}
		public function getAdditionalCustomPostTypes() {
			return $this->get('additionalCustomPostTypes');
		}
		public function setAdditionalCustomPostTypes($value) {
			return $this->set('additionalCustomPostTypes', $value);
		}

		public function getDisableSendOnPublish() {
			return $this->get('disableSendOnPublish') ? true : false;
		}

		public function setDisableSendOnPublish($value) {
			return $this->set('disableSendOnPublish', $value ? true : false);
		}

		public function getDisableSendByDefaultOnPublish() {
			return $this->get('disableSendByDefaultOnPublish') ? true : false;
		}

		public function getSendOnThirdPartyPublish() {
			return $this->get('sendOnThirdPartyPublish') ? true : false;
		}

		public function setDisableSendByDefaultOnPublish($value) {
			return $this->set('disableSendByDefaultOnPublish', $value ? true : false);
		}

		public function setSendOnThirdPartyPublish($value) {
			return $this->set('sendOnThirdPartyPublish', $value ? true : false);
		}

		public function getDisableFeedbackOnPublish() {
			return $this->get('disableFeedbackOnPublish') ? true : false;
		}

		public function setDisableFeedbackOnPublish($value) {
			return $this->set('disableFeedbackOnPublish', $value ? true : false);
		}

		public function getDisableUsePostImageForNotification() {
			return $this->get('disableUsePostImageForNotification') ? true : false;
		}

		public function setDisableUsePostImageForNotification($value) {
			return $this->set('disableUsePostImageForNotification', $value ? true : false);
		}

		public function getPreferLargeImageForNotification() {
			return $this->get('preferLargeImageForNotification') ? true : false;
		}

		public function setPreferLargeImageForNotification($value) {
			return $this->set('preferLargeImageForNotification', $value ? true : false);
		}

		public function getNotificationTitle() {
			return $this->get('notificationTitle');
		}

		public function setNotificationTitle($value) {
			return $this->set('notificationTitle', $value);
		}

		public function getEnableOrderCompleteNotifications() {
			return $this->get('enableOrderCompleteNotifications') ? true : false;
		}

		public function setEnableOrderCompleteNotifications($value) {
			return $this->set('enableOrderCompleteNotifications', $value ? true : false);
		}

		public function getOrderCompleteNotificationsMessage() {
			return $this->get('orderCompleteNotificationsMessage');
		}

		public function setOrderCompleteNotificationsMessage($value) {
			$this->set('orderCompleteNotificationsMessage', $value);
			return $this;
		}

		public function getEnableOrderProcessingNotifications() {
			return $this->get('enableOrderProcessingNotifications') ? true : false;
		}

		public function setEnableOrderProcessingNotifications($value) {
			return $this->set('enableOrderProcessingNotifications', $value ? true : false);
		}

		public function getOrderProcessingNotificationsMessage() {
			return $this->get('orderProcessingNotificationsMessage');
		}

		public function setOrderProcessingNotificationsMessage($value) {
			$this->set('orderProcessingNotificationsMessage', $value);
			return $this;
		}

		public function getCartReminderCampaignId() {
			return $this->get('cartReminderCampaignId');
		}

		public function setCartReminderCampaignId($value) {
			return $this->set('cartReminderCampaignId', $value);
		}

		public function getDisableThankYouEvent() {
			return $this->get('disableThankYouEvent') ? true : false;
		}

		public function setDisableThankYouEvent($value) {
			return $this->set('disableThankYouEvent', $value ? true : false);
		}

		public function getThankYouEventName() {
			return $this->get('thankYouEventName');
		}

		public function setThankYouEventName($value) {
			return $this->set('thankYouEventName', $value);
		}

		public function getDisableAmpUnsubscribe() {
			return $this->get('disableAmpUnsubscribe') ? true : false;
		}

		public function setDisableAmpUnsubscribe($value) {
			return $this->set('disableAmpUnsubscribe', $value ? true : false);
		}

		public function getAmpSubscribeButtonLabel() {
			return $this->get('ampSubscribeButtonLabel');
		}

		public function setAmpSubscribeButtonLabel($value) {
			return $this->set('ampSubscribeButtonLabel', $value ? $value : null);
		}

		public function getAmpUnsubscribeButtonLabel() {
			return $this->get('ampUnsubscribeButtonLabel');
		}

		public function setAmpUnsubscribeButtonLabel($value) {
			return $this->set('ampUnsubscribeButtonLabel', $value ? $value : null);
		}

		public function getDisableAmpTopSubscribeButton() {
			return $this->get('disableAmpTopSubscribeButton') ? true : false;
		}

		public function setDisableAmpTopSubscribeButton($value) {
			return $this->set('disableAmpTopSubscribeButton', $value ? true : false);
		}

		public function getDisableAmpBottomSubscribeButton() {
			return $this->get('disableAmpBottomSubscribeButton') ? true : false;
		}

		public function setDisableAmpBottomSubscribeButton($value) {
			return $this->set('disableAmpBottomSubscribeButton', $value ? true : false);
		}

		public function getAmpButtonWidth() {
			return $this->get('ampButtonWidth');
		}

		public function setAmpButtonWidth($value) {
			return $this->set('ampButtonWidth', is_int($value) ? $value : null);
		}

		public function getAmpButtonHeight() {
			return $this->get('ampButtonHeight');
		}

		public function setAmpButtonHeight($value) {
			return $this->set('ampButtonHeight', is_int($value) ? $value : null);
		}

		public function getAdditionalInitOptionsJson() {
			return $this->get('additionalInitOptionsJson');
		}

		public function setAdditionalInitOptionsJson($value) {
			$this->set('additionalInitOptionsJson', $value);
			return $this;
		}

		public function getShowPush() {
			return $this->get('showPush') ? true : false;
		}

		public function setShowPush($value) {
			return $this->set('showPush', $value ? true : false);
		}

		public function getHideAdminBarShortcut() {
			return $this->get('hideAdminBarShortcut') ? true : false;
		}

		public function setHideAdminBarShortcut($value) {
			return $this->set('hideAdminBarShortcut', $value ? true : false);
		}

		public function getSendOnlyToThisDomain() {
			$storedValue = $this->get('sendOnlyToThisDomain');
			if ($storedValue === null) return true; // Default to true
			return $storedValue ? true : false;
		}

		public function setSendOnlyToThisDomain($value) {
			return $this->set('sendOnlyToThisDomain', $value ? true : false);
		}

		public function save() {
			update_option(SIB_Manager::PUSH_SETTINGS_OPTION_NAME, $this->settings);
		}

		/**
		 * @return SIB_Push_Settings
		 */
		public static function getSettings() {
			if (!self::$instance) self::$instance = new SIB_Push_Settings(get_option(SIB_Manager::PUSH_SETTINGS_OPTION_NAME));
			return self::$instance;
		}

		/** Deletes all push related settings */
		public static function clearAllSettings() {
			delete_option(SIB_Manager::PUSH_SETTINGS_OPTION_NAME);
		}

	}

}
