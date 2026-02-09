<?php

if (!defined( 'ABSPATH' )) { http_response_code(403); exit(); }

if ( ! class_exists( 'SIB_Push_Utils' ) ) {
	class SIB_Push_Utils {
		const DEFAULT_CACHE_TTL = 300;

		private static $thread = null;

		static function random_bytes($length) {
			$bytes = '';
			for ($i = 0; $i < $length; $i++) {
				$bytes .= chr(mt_rand(0, 255));
			}
			return $bytes;
		}

		static public function format(/* arguments */) {
			return self::formatv(func_get_args());
		}

		static public function formatv($args, $separator = ' ') {
			$parts = array();
			if (self::$thread === null) self::$thread = bin2hex(self::random_bytes(3));
			$parts[] = '[thread:'.self::$thread.']';
			foreach ($args as $arg) {
				if ($arg instanceof Exception) {
					$parts[] = 'Exception ' . get_class($arg) . ': ' . $arg->getMessage()
						. ' at ' . $arg->getFile() . ':' . $arg->getLine()
						. "\nTrace : " . $arg->getTraceAsString();
					while (($arg = $arg->getPrevious()) !== null) {
						$parts[] = "\nCaused by " . get_class($arg) . ': ' . $arg->getMessage()
							. ' at ' . $arg->getFile() . ':' . $arg->getLine()
							. "\nTrace : " . $arg->getTraceAsString();
					}
				} else if (is_string($arg)) {
					$parts[] = $arg;
				} else if ($arg instanceof \WonderPush\Obj\BaseObject) {
					$parts[] = json_encode($arg->toData());
				} else if (is_resource($arg)) {
					$parts[] = 'res_' . get_resource_type($arg) . '#' . preg_replace('/^.*#/', '', print_r($arg, true));
				} else {
					$parts[] = var_export($arg, 1);
				}
			}

			return join($separator, $parts);
		}

		/** @noinspection ForgottenDebugOutputInspection */
		static public function log_debug(/* arguments */) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				 error_log(self::formatv(array_merge(['[DEBUG]'], func_get_args())));
			}
		}

		/** @noinspection ForgottenDebugOutputInspection */
		static public function log_info(/* arguments */) {
			error_log(self::formatv(array_merge(array('[INFO]'), func_get_args())));
		}

		/** @noinspection ForgottenDebugOutputInspection */
		static public function log_warn(/* arguments */) {
			error_log(self::formatv(array_merge(array('[WARN]'), func_get_args())));
		}

		/** @noinspection ForgottenDebugOutputInspection */
		static public function log_error(/* arguments */) {
			error_log(self::formatv(array_merge(array('[ERROR]'), func_get_args())));
		}

		/** @noinspection ForgottenDebugOutputInspection */
		static public function log_critical(/* arguments */) {
			error_log(self::formatv(array_merge(array('[CRITICAL]'), func_get_args())));
		}

		private static function merge_push_options_with_additional_init_options($push_options) {
			$push_settings = SIB_Push_Settings::getSettings();
			$additional_push_init_options_json = $push_settings->getAdditionalInitOptionsJson();
			$additional_push_init_options = $additional_push_init_options_json
				? (json_decode($additional_push_init_options_json, true) ?: array())
				: array();
			if (json_last_error()) $additional_push_init_options = array();
			return array_merge($push_options, $additional_push_init_options);
		}

		public static function brevo_init_options() {
			$general_settings = get_option( SIB_Manager::MAIN_OPTION_NAME, array() );
			$ma_key = isset( $general_settings['ma_key'] ) ? sanitize_text_field($general_settings['ma_key']) : null;


			$current_user = wp_get_current_user();
			$ma_email = null;
			if ( $current_user instanceof WP_User ) { // FIXME: conditional to "Sync new WordPress users to Brevo contacts"
				$ma_email = $current_user->user_email ?: null;
			}
			try {
				$push_app = SIB_Push_Utils::get_push_application();
			} catch (Exception $t) {
				SIB_Push_Utils::log_warn('Could not get application', $t);
				$push_app = null;
			}
			$web_key = $push_app ? $push_app->getWebKey() : null;
			$push_options = self::merge_push_options_with_additional_init_options( array_filter( array(
				'customDomain' => SIB_Manager::$plugin_url . '/',
				'_rootApiUrl' => SIB_Manager::is_staging() ? 'api-dev.wonderpush.com' : null,
				'_measurementApiUrl' => SIB_Manager::is_staging() ? 'https://measurements-api-dev.wonderpush.com/v1' : null,
				'userId' => $ma_email ?: null, // TODO: conditional to "Sync new WordPress users to Brevo contacts"
			) ) );
			return array(
				'client_key' => $ma_key,
				'email_id' => $ma_email,
				'push' => $push_options,
				'service_worker_url' => $web_key ? 'wonderpush-worker-loader.min.js?webKey='.urlencode($web_key) : 'sw.js?key=${key}',
				'frame_url' => 'brevo-frame.html',
			);

		}

		public static function wonderpush_init_options() {
			try {
				$push_app = SIB_Push_Utils::get_push_application();
			} catch (Exception $t) {
				SIB_Push_Utils::log_warn('Could not get application', $t);
				$push_app = null;
			}
			$web_key = $push_app ? $push_app->getWebKey() : null;
			if (!$web_key) return null;
			$current_user = wp_get_current_user();
			if ( $current_user instanceof WP_User ) { // FIXME: conditional to "Sync new WordPress users to Brevo contacts"
				$ma_email = $current_user->user_email;
			}
			return self::merge_push_options_with_additional_init_options( array_filter( array(
				'customDomain' => SIB_Manager::$plugin_url . '/',
				'serviceWorkerUrl' => 'wonderpush-worker-loader.min.js?webKey=' . urlencode($web_key),
				'frameUrl' => 'wonderpush.min.html',
				'_rootApiUrl' => SIB_Manager::is_staging() ? 'api-dev.wonderpush.com' : null,
				'_measurementApiUrl' => SIB_Manager::is_staging() ? 'https://measurements-api-dev.wonderpush.com/v1' : null,
				'userId' => $ma_email ?: null, // TODO: conditional to "Sync new WordPress users to Brevo contacts"
				'webKey' => $web_key,
			) ) );
		}

		/**
		 * Returns true when:
		 * - there's a push application
		 * - and user hasn't explicitly deactivated push (pushDeactivated in settings is false)
		 * - and push subscription is enabled
		 * @return bool
		 */
		public static function is_push_active() {
			$settings = SIB_Push_Settings::getSettings();

			// Check push application
			try {
				$app = self::get_push_application();
				if (!$app) return false;
				return self::application_is_active($app);
			} catch (Exception $e) {
				SIB_Push_Utils::log_warn('Could not get application', $e);
				return false;
			}
		}

		/**
		 * @return bool
		 */
		public static function is_contact_sync_active() {
			// Check push application
			try {
				$app = self::get_push_application();
				if (!$app) return true;
				$brevoContactSync = $app->getBrevoContactSync();
				return $brevoContactSync ? $brevoContactSync->getEnabled() : true;
			} catch (Exception $e) {
				SIB_Push_Utils::log_warn('Could not get application', $e);
				return true;
			}
		}

		/**
		 * Activates push.
		 * @throws SIB_Push_MissingCredentialsException
		 * @throws Exception
		 * @return \WonderPush\Obj\Application
		 */
		public static function activate_push() {
			$app = self::get_push_application(self::DEFAULT_CACHE_TTL, true);
			if (!$app) {
				$app = self::create_push_application();
			}
			self::ensure_app_active($app);
			return $app;
		}

		/**
		 * Deactivates push application if it exists.
		 * @return void
		 */
		public static function deactivate_push() {
			try {
				$app = self::get_push_application(SIB_Push_Utils::DEFAULT_CACHE_TTL, true);
			} catch (Exception $t) {
				SIB_Push_Utils::log_warn('Could not get application', $t);
			}
			if (!$app) return;
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) {
				throw new Exception('Cannot deactivate push application without API credentials');
			}
			$webSdkInitOptions = new \WonderPush\Obj\WebSdkInitOptions();
			$webSdkInitOptions->setResubscribe(false);
			$payload = array(
				'wordPressSnippetDeactivated' => true,
				'webSdkInitOptions' => $webSdkInitOptions,
				'pushDisabledPlatforms' => array(
					'Web' => true,
				),
			);
			$wp = self::management_api_client($credentials);
			$app = $wp->applications()->patch($app->getId(), $payload);
			// Cache the result
			self::update_push_application_cache($app, 'deactivate');
		}

		/**
		 * Returns true when:
		 * - there's a push application
		 * We don't care if subscription is enabled here, we need to support existing installations.
		 * That's the condition we'll use to inject the WonderPush SDK.
		 * @return bool
		 */
		public static function is_push_sdk_enabled() {
			$settings = SIB_Push_Settings::getSettings();

			// Check push application
			try {
				$app = self::get_push_application(30 * 86400); // maxAge 30 days to use API key on a regular basis and avoid deactivation
				if (!$app) return false;
				return !$app->getWordPressSnippetDeactivated();
			} catch (Exception $e) {
				SIB_Push_Utils::log_warn('Could not get application', $e);
				return false;
			}
		}


		/**
		 * @param \WonderPush\Obj\Application $app
		 * @return \WonderPush\Obj\Application
		 * @throws Exception
		 */
		public static function ensure_app_active($app) {
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) {
				throw new Exception('Cannot activate push application without API credentials');
			}
			if (self::application_is_active($app)) {
				// App is active
				return $app;
			}
			// App needs activation
			$webSdkInitOptions = new \WonderPush\Obj\WebSdkInitOptions();
			$webSdkInitOptions->setResubscribe(true);
			$payload = array(
				'wordPressSnippetDeactivated' => false,
				'webSdkInitOptions' => $webSdkInitOptions,
				'pushDisabledPlatforms' => array(
					'Web' => false,
				),
			);
			$wp = self::management_api_client($credentials);
			$app = $wp->applications()->patch($app->getId(), $payload);
			self::update_push_application_cache($app, 'activate');
			return $app;
		}

		public static function update_settings() {
			try {
				$settings = SIB_Push_Settings::getSettings();
				$credentials = $settings->getWonderPushCredentials();
				if (!$credentials) {
					throw new Exception('Cannot update push application settings without API credentials');
				}
				$wp = self::management_api_client($credentials);
				$payload = array(
					'brevoInitOptions' => self::brevo_init_options(),
					'wonderPushInitOptions' => self::wonderpush_init_options(),
				);
				$wp->rest()->post('/brevoWordPressPlugin/updateSettings', $payload);
			} catch (Exception $t) {
				SIB_Push_Utils::log_warn('Could not update settings', $t);
			}
		}

		/**
		 * @param \WonderPush\Obj\Application $app
		 * @return bool
		 */
		public static function application_is_active($app) {
			$wordPressSnippetDeactivated = $app->getWordPressSnippetDeactivated();
			if ($wordPressSnippetDeactivated) return false;
			$webSdkInitOptions = $app->getWebSdkInitOptions() ?: new \WonderPush\Obj\WebSdkInitOptions();
			$pushDisabledPlatforms = $app->getPushDisabledPlatforms() ?: array();
			$pushDisabled = isset($pushDisabledPlatforms['Web']) && $pushDisabledPlatforms['Web'];
			return $webSdkInitOptions->getResubscribe() && !$pushDisabled;
		}

		/**
		 * Fetches the application repeatedly until its status is not "creation", waiting for $sleepMicroSeconds between each attempt.
		 * @param int $sleepMicroSeconds Microseconds to wait between each attempt
		 * @return null|\WonderPush\Obj\Application
		 * @throws SIB_Push_MissingCredentialsException
		 * @throws Exception
		 */
		public static function pollApplicationCreation($sleepMicroSeconds = 500000) {
			$settings = SIB_Push_Settings::getSettings();
			$app = self::get_push_application(self::DEFAULT_CACHE_TTL, true);
			if ($app && $app->getStatus() === 'creation') {
				usleep($sleepMicroSeconds); // 0.5s
				return self::pollApplicationCreation($sleepMicroSeconds); // Force fetch for subsequent calls
			}
			return $app;
		}

		/**
		 * Returns the webSdkInitOptions with relevant site information and default prompts activated.
		 * @return array
		 */
		protected static function default_web_sdk_init_options() {
			$parsed_url = parse_url(home_url());
			$site_origin = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/';
			$site_icon = get_site_icon_url(64);
			$name = get_bloginfo('name') ?: __('My WordPress Site', 'mailin');
			return array(
				'customDomain' => $site_origin,
				'notificationDefaultUrl' => $site_origin,
				'applicationName' => $name,
				'notificationIcon' => $site_icon ?: null,
				'subscriptionNative' => array(
					'triggers' => array(
						'delay' => 10000,
					),
				),
			);

		}

		public static function get_show_push() {
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) {
				return false;
			}
			$cache_key = 'sib_push_show_push_' . $credentials->apiKey;
			$cached = get_transient($cache_key);
			if ($cached) {
				$cached = $cached === 'true';
//				self::log_debug('Using cached value of /brevoWordPressPlugin/showPush', $cached);
				return $cached;
			}
			try {
				$webSdkInitOptions = self::default_web_sdk_init_options();
				$customDomain = $webSdkInitOptions['customDomain'];
				$wp = self::management_api_client($credentials);
				self::log_debug('Calling /brevoWordPressPlugin/showPush', $credentials->apiKey, $customDomain);
				$response = $wp->rest()->get('/brevoWordPressPlugin/showPush?customDomain=' . urlencode($customDomain ?: ''));
				$body = $response->parsedBody();
				$body = $body ? (array)$body : $body;
				$show_push = is_array($body) && isset($body['showPush']) ? $body['showPush'] : false;
				self::log_debug('Caching response of /brevoWordPressPlugin/showPush', $show_push, $body, $credentials->apiKey, $customDomain);
				set_transient($cache_key, $show_push ? 'true' : 'false', 86400);
				return $show_push;
			} catch ( Exception $e ) {
				SIB_Push_Utils::log_warn('Could not get showPush', $e);
				return false;
			}
		}

		/**
		 * Calls the API to create a push application
		 * @throws Exception
		 * @return \WonderPush\Obj\Application
		 */
		public static function create_push_application($context = null) {
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) {
				throw new SIB_Push_MissingCredentialsException('Cannot create push application without API credentials');
			}
			// Get the full home URL
			$full_url = home_url();

			// Parse the URL and extract scheme and host
			$parsed_url = parse_url($full_url);
			$site_origin = $parsed_url['scheme'] . '://' . $parsed_url['host'];

			// Check HTTPS
			if (stripos($site_origin, 'https:') !== 0) {
				throw new Exception('Only HTTPS websites can activate push.');
			}

			// Create the application
			$name = get_bloginfo('name') ?: __('My WordPress Site', 'mailin');
			$payload = array('name' => $name, 'webSdkInitOptions' => self::default_web_sdk_init_options());
			$wp = self::management_api_client($credentials);
			$response = $wp->rest()->post('/brevoWordPressPlugin/createApplication?context=' . urlencode($context ?: ''), $payload);
			$app = $response->checkedResult('\WonderPush\Obj\Application', 'application');
			self::update_push_application_cache($app, 'create');
			return $app;
		}

		public static function can_modify_settings() {
			return self::is_admin_user();
		}

		public static function can_send_notifications() {
			return current_user_can('publish_posts') || current_user_can('edit_published_posts');
		}

		public static function is_admin_user() {
			return current_user_can('delete_users');
		}

		/* If >= PHP 5.4, ENT_HTML401 | ENT_QUOTES will correctly decode most entities including both double and single quotes.
		 In PHP 5.3, ENT_HTML401 does not exist, so we have to use `str_replace("&apos;","'", $value)` before feeding it to html_entity_decode(). */
		public static function decode_entities($string) {
			if (!$string) return '';
			$HTML_ENTITY_DECODE_FLAGS = ENT_QUOTES;
			if (defined('ENT_HTML401')) {
				// @codingStandardsIgnoreLine
				$HTML_ENTITY_DECODE_FLAGS = ENT_HTML401 | $HTML_ENTITY_DECODE_FLAGS;
			}
			return html_entity_decode(str_replace("&apos;", "'", $string), $HTML_ENTITY_DECODE_FLAGS, 'UTF-8');
		}

		/**
		 * Creates a new Management API client
		 * @param \WonderPush\Credentials $credentials
		 * @return \WonderPush\WonderPush
		 */
		public static function management_api_client($credentials) {
			$client = new \WonderPush\WonderPush($credentials);
			$settings = SIB_Push_Settings::getSettings();
			if ( !$settings->getBypassWordPressHttpClient() ) {
				$client->setHttpClient( new SIB_Push_HttpClient( $client ) );
			} else {
				// Force ipv4
				$client->setHttpClient(new \WonderPush\Net\CurlHttpClient($client, array('ipv4' => true)));
			}
			return $client;
		}

		/**
		 * @param \WonderPush\BrevoAPIKeyV3Credentials $credentials
		 * @param $expiration
		 * @param $limit
		 * @return array|mixed|string[]
		 * @throws \WonderPush\Errors\Base
		 */
		public static function list_tags($credentials, $expiration = 30, $limit = 100) {
			// Cached value ?
			$cache_key = "WonderPush:list_tags:" . $credentials->apiKey;
			$cached = get_transient($cache_key);
			if ($cached) {
				return $cached;
			}

			// Check access token with the API
			$wp = self::management_api_client($credentials);
			try {
				$params = new \WonderPush\Params\FrequentFieldValuesParams();
				$params->setKind('installations')
					->setField('custom.tags');
				$response = $wp->stats()->frequentFieldValues($params);
				$tags = array_map(function($elt) { return $elt->getValue(); }, $response->getData());

				if ($tags && count($tags) > 0) {
					set_transient($cache_key, $tags, $expiration);
					return $tags;
				}
				// Access token not associated with any app
				delete_transient($cache_key);
				return array();
			} catch (Exception $e) {
				delete_transient($cache_key);
				if ($e instanceof \WonderPush\Errors\Server
					&& ($e->getResponse()->getStatusCode() == 403 || $e->getCode() === 11003)) {
					// Invalid access token
					return array();
				}
				throw $e;
			}

		}

		/**
		 * @param \WonderPush\BrevoAPIKeyV3Credentials $credentials
		 * @param $expiration
		 * @param $fields
		 * @param $limit
		 * @return false|mixed|\WonderPush\Obj\Segment[]
		 * @throws \WonderPush\Errors\Base
		 */
		public static function list_segments($credentials, $expiration = 30, $fields = array('name', 'id'), $limit = 100) {
			// Cached value ?
			$cache_key = "WonderPush:list_segments:" . $credentials->apiKey;
			$cached = get_transient($cache_key);
			if ($cached) {
				return $cached;
			}

			// Check access token with the API
			$wp = self::management_api_client($credentials);
			try {
				$segments = $wp->segments()->all(array('fields' => $fields ? implode(',', $fields) : '', 'sort' => '-updateDate', 'limit' => $limit));

				if ($segments && $segments->getCount() > 0) {
					$data = $segments->getData();
					set_transient($cache_key, $data, $expiration);
					return $data;
				}
				// Access token not associated with any app
				delete_transient($cache_key);
				return false;
			} catch (Exception $e) {
				delete_transient($cache_key);
				if ($e instanceof \WonderPush\Errors\Server
					&& ($e->getResponse()->getStatusCode() == 403 || $e->getCode() === 11003)) {
					// Invalid access token
					return false;
				}
				throw $e;
			}

		}

		/**
		 * @param WonderPush\Credentials $credentials
		 * @return string
		 */
		private static function get_application_cache_key($credentials) {
			return "sib_push_app_" . ($credentials instanceof \WonderPush\AccessTokenCredentials ? $credentials->accessToken : $credentials->apiKey);
		}

		/**
		 * @param \WonderPush\Obj\Application|string $app 'miss' or an app object
		 * @param $expiration
		 * @return void
		 * @throws SIB_Push_MissingCredentialsException
		 */
		private static function update_push_application_cache($app, $reason = 'init') {
			self::log_debug('Updating application cache', $reason, $app);
			// Check creds
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) throw new SIB_Push_MissingCredentialsException('No push credentials');

			// Cache value
			$cache_key = self::get_application_cache_key($credentials);
			set_transient($cache_key, array('ts' => time(), 'app' => $app), $app === 'error' ? 300 : 0);
		}

		/**
		 * @return void
		 * @throws SIB_Push_MissingCredentialsException
		 */
		public static function clear_push_application_cache() {
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) throw new SIB_Push_MissingCredentialsException('No push credentials');
			$cache_key = self::get_application_cache_key($credentials);
			delete_transient($cache_key);
		}

		/**
		 * Returns the first application associated with the provided access token,
		 * or false if the access token is not valid.
		 * This method uses the WordPress transient API to cache the application and avoid network calls if possible.
		 * Throws exception is validity could not be determined (network error for instance).
		 * @param int|null $maxAge How old the result is allowed to be. Pass null for no check.
		 * @param bool $forceFetch defaults to false
		 * @return null|\WonderPush\Obj\Application
		 */
		public static function get_push_application( $maxAge = null, $forceFetch = false  ) {

			// Check creds
			$settings = SIB_Push_Settings::getSettings();
			$credentials = $settings->getWonderPushCredentials();
			if (!$credentials) return null;

			// Cached value ?
			$cache_key = self::get_application_cache_key($credentials);
			$cached = $forceFetch ? null : get_transient($cache_key);
			$now = time();
			if ($cached && is_array($cached)) {
				$app = isset($cached['app']) ? $cached['app'] : null;
				$ts = isset($cached['ts']) ? $cached['ts'] : 0;
				if ($maxAge === null || $maxAge > ($now - $ts)) {
					if ($app instanceof \WonderPush\Obj\Application) {
//						self::log_debug('Getting application from cache', $app->getId(), 'key', $cache_key, 'maxAge', $maxAge, 'ts', $ts, 'now', $now);
						return $app;
					}
//					self::log_debug('Getting application from cache (null)', $app, 'key', $cache_key, 'maxAge', $maxAge, 'ts', $ts, 'now', $now);
					return null;
				}
			}

//			self::log_debug('Getting application from network', $cache_key, 'forceFetch', $forceFetch);
			// Check access token with the API
			$wp = self::management_api_client($credentials);
			try {
				$applications = $wp->applications()->all();
				if ($applications && $applications->getCount() > 0) {
					$data = $applications->getData();
					$app = $data[0];
					self::update_push_application_cache($app);
					return $app;
				}
				// Cache misses
				self::update_push_application_cache('miss');
				return null;
			} catch (Exception $e) {
				self::log_warn('Error getting application', $e);

				// If we have a cached value, return it regardless of maxAge
				if ($cached && is_array($cached) && isset($cached['app']) && $cached['app'] instanceof \WonderPush\Obj\Application) {
					return $cached['app'];
				}

				// Cache errors for 5 minutes
				self::update_push_application_cache('error');
				return null;
			}
		}

		/**
		 * Tracks an event by calling the WonderPush API
		 * @param \WonderPush\BrevoAPIKeyV3Credentials $credentials
		 * @param string $event_type
		 * @param array|object|null $payload
		 * @param string $user_id
		 * @return boolean Success
		 * @throws \WonderPush\Errors\Base
		 */
		public static function track_event($credentials, $event_type, $payload, $user_id = null) {
			if ($user_id === null) $user_id = self::get_user_id();
			if (!$credentials) {
				return false;
			}
			$wp = self::management_api_client($credentials);
			$installation_id = self::get_installation_id();
			if (!$installation_id) {
				return false;
			}
			$params = new \WonderPush\Params\TrackEventParams($event_type, $installation_id, strval($user_id));
			$params->setCustom($payload);
			try {
				$result = $wp->events()->track($params);
				return $result->isSuccess();
			} catch (Exception $e) {
				self::log_warn("Error tracking event", $e);
				return false;
			}
		}

		/**
		 * Puts the given properties on the current installation by calling WonderPush Management API
		 * @param \WonderPush\BrevoAPIKeyV3Credentials $credentials
		 * @param array|object $properties
		 * @return boolean Success
		 * @throws \WonderPush\Errors\Base
		 */
		public static function put_current_installation_properties( $credentials, $properties, $user_id = '') {
			if (!$credentials) {
				return false;
			}
			$wp = self::management_api_client($credentials);
			$installation_id = self::get_installation_id();
			if (!$installation_id) {
				return false;
			}
			$params = new \WonderPush\Params\PatchInstallationParams($installation_id, strval($user_id));
			$params->setProperties($properties);
			$result = $wp->installations()->patch($params);
			return $result->isSuccess();
		}

		/**
		 * Returns the WonderPush installation ID for the current request
		 * by looking at the cookie named `SIB_Push_Public::INSTALLATION_ID_COOKIE_NAME`
		 * @return string|null
		 */
		public static function get_installation_id() {
			if (!array_key_exists(SIB_Push_Public::INSTALLATION_ID_COOKIE_NAME, $_COOKIE)) {
				return null;
			}
			$installation_id = $_COOKIE[SIB_Push_Public::INSTALLATION_ID_COOKIE_NAME];
			return $installation_id ? $installation_id : null;
		}

		/**
		 * @return WooCommerce | null
		 */
		public static function get_woocommerce() {
			if (!function_exists('WC')) return null;
			return WC();
		}

		public static function is_amp_installed() {
			if (defined('AMP__FILE__')) return true;
			if (defined('AMPFORWP_VERSION')) return true;
			return false;
		}

		public static function is_amp_request() {
			if (function_exists('amp_is_request')) {
				return amp_is_request();
			}
			return false;
		}


		/**
		 * Converts a DateTime object to a string representing its date.
		 * @param DateTime $date_time
		 * @return string
		 */
		public static function datetime_to_date_string($date_time) {
			return $date_time->format('Ymd');
		}

		/**
		 * Converts a date string obtained with `datetime_to_date_string` to a DateTime object.
		 * @param string $date_string A date_string in the form YYYYMMDD (8 digits exactly)
		 * @return DateTime|null
		 */
		public static function date_string_to_datetime($date_string) {
			if (!self::is_valid_date_string($date_string)) return null;
			$year = substr($date_string, 0, 4);
			$month = substr($date_string, 4, 2);
			$day = substr($date_string, 6, 2);
			$result = new DateTime();
			$result->setDate($year, $month, $day);
			$result->setTime(0, 0, 0, 0);
			return $result;
		}

		/**
		 * Returns true when provided date string is exactly 8 digits.
		 * @param $date_string
		 * @return bool
		 */
		public static function is_valid_date_string($date_string) {
			return preg_match('/\d{8}/', $date_string) ? true : false;
		}

		/**
		 * Returns true when provided string only has digits.
		 * @param $str
		 * @return bool
		 */
		public static function is_int_string($str) {
			return (bool)preg_match('/^[0-9]+$/', $str);
		}

		/**
		 * Returns true when provided time string is an integer between 0 and 23 inclusive.
		 * @param $time_string
		 * @return bool
		 */
		public static function is_valid_time_string($time_string) {
			return preg_match('/^[0-2]?[0-9]$/', $time_string) && (int)$time_string < 24 ? true : false;
		}

		public static function utm_parameters() {
			return array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
		}

		public static function user_segmentation_keys() {
			return array('first_name', 'last_name', 'user_login', 'display_name');
		}

		public static function inject_query_string_params($url, $params) {
			$parsed_url = parse_url($url);
			if ($parsed_url === false) return $url;
			return http_build_url($parsed_url, array('query' => http_build_query($params)), HTTP_URL_JOIN_QUERY);
		}

		public static function is_curl_installed() {
			return function_exists('curl_init');
		}

		public static function get_user_id() {
			$current_user = wp_get_current_user();
			if ( $current_user instanceof WP_User ) {
				return $current_user->user_email;
			}
			return null;
		}
	}

}

if ( ! class_exists( 'SIB_Push_MissingCredentialsException' ) ) {
	class SIB_Push_MissingCredentialsException extends Exception {}
}