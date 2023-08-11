<?php


namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Models\ApiSchema;
use SendinblueWoocommerce\Clients\SendinblueClient;
use SendinblueWoocommerce\Managers\CartEventsManagers;
use WP_Error;
use WP_REST_Response;

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/cart-events-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/models/api-schema.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class ApiManager
 *
 * @package SendinblueWoocommerce\Managers
 */
class ApiManager
{
    private const ROUTE_METHODS = 'methods';
    private const ROUTE_PATH = 'path';
    private const ROUTE_CALLBACK = 'callback';
    private const ROUTE_PERMISSION_CALLBACK = 'permission_callback';
    private const HTTP_STATUS = 'status';
    public const API_NAMESPACE = "sendinblue-woo/v1";
    public const EVENT_TYPE_ORDER = "order";
    public const EVENT_TYPE_CUSTOMER = "customer";
    public const EVENT_TYPE_NOTE = "note";
    public const EVENT_GROUP_SIB = "sib";
    public const EVENT_GROUP_WOOCOMERCE = "woo";
    public const FILE_UPLOADS_PATH = '/woocommerce-sendinblue-newsletter-subscription/uploads/';

    public function add_hooks()
    {
        $cart_events_manager = new CartEventsManagers();
        add_action('woocommerce_checkout_after_terms_and_conditions', array($cart_events_manager, 'add_optin_terms'));
        add_filter('woocommerce_checkout_fields', array($cart_events_manager, 'add_optin_billing'));
        add_action('woocommerce_checkout_update_order_meta', array($cart_events_manager, 'add_optin_order'));
        add_action('wp_login', array($cart_events_manager, 'wp_login_action'), 11, 2);
        add_action('wp_footer', array($cart_events_manager, 'ws_cart_custom_fragment_load'));
        add_filter('woocommerce_add_to_cart_fragments', array($cart_events_manager, 'ws_cart_custom_fragment'), 10, 1);
        add_action('woocommerce_thankyou', array($cart_events_manager, 'ws_checkout_completed'));
        add_action('woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 3 );
        add_action('woocommerce_order_status_refunded', array($this, 'on_order_status_refunded'), 10, 1 ); 
        add_action('woocommerce_order_note_added', array($this, 'on_new_customer_note'), 10, 2);
        add_action('woocommerce_created_customer', array($this, 'on_new_customer_creation'), 10 , 3);
    }

    public function add_rest_endpoints()
    {
        $routes = array(
            array(
                self::ROUTE_PATH       => '/configs',
                self::ROUTE_METHODS    => 'PUT',
                self::ROUTE_CALLBACK   => function ($request) {
                    return $this->modify_response($this->save_settings($request));
                }
            ),
            array(
                self::ROUTE_PATH       => '/disconnect',
                self::ROUTE_METHODS    => 'GET',
                self::ROUTE_CALLBACK   => function ($request) {
                    return $this->modify_response($this->disconnect_connection());
                }
            ),
            array(
                self::ROUTE_PATH       => '/emailsettings',
                self::ROUTE_METHODS    => 'PUT',
                self::ROUTE_CALLBACK   => function ($request) {
                    return $this->modify_response($this->email_settings($request));
                }
            ),
            array(
                self::ROUTE_PATH       => '/testconnection',
                self::ROUTE_METHODS    => 'GET',
                self::ROUTE_CALLBACK   => function () {
                    return $this->modify_response($this->test_connection());
                }
            ),
            array(
                self::ROUTE_PATH       => '/pluginversion',
                self::ROUTE_METHODS    => 'GET',
                self::ROUTE_CALLBACK   => function () {
                    return $this->modify_response($this->plugin_version());
                }
            ),
            array(
                self::ROUTE_PATH       => '/getfilecontents',
                self::ROUTE_METHODS    => 'GET',
                self::ROUTE_CALLBACK   => function () {
                    return $this->modify_response($this->get_file_contents());
                }
            ),
            array(
                self::ROUTE_PATH       => '/deleteAttachment',
                self::ROUTE_METHODS    => 'DELETE',
                self::ROUTE_CALLBACK   => function () {
                    return $this->modify_response($this->delete_attachment());
                }
            ),
            array(
                self::ROUTE_PATH       => '/settings',
                self::ROUTE_METHODS    => 'GET',
                self::ROUTE_CALLBACK   => function () {
                    return $this->modify_response($this->get_plugin_settings());
                }
            )
        );

        foreach ($routes as $route) {
            $this->register_route($route);
        }
    }

    private function register_route(array $route)
    {
        $path = $route[self::ROUTE_PATH];
        $methods = $route[self::ROUTE_METHODS];
        $callback = $route[self::ROUTE_CALLBACK];

        if(empty($path)) {
            return;
        }

        if(empty($methods)) {
            $methods = 'GET';
        }

        $arguments = array(
            self::ROUTE_METHODS    => $methods,
            self::ROUTE_CALLBACK   => $callback,
            self::ROUTE_PERMISSION_CALLBACK   => array($this, 'validate_api_key')
        );

        register_rest_route(self::API_NAMESPACE, $path, $arguments);
    }

    private function get_plugin_settings()
    {
        return new WP_REST_Response(
            array(
                'settings' => $this->get_settings(),
                'email_settings' => $this->get_email_settings(),
            ), 200);
    }

    private function get_file_contents()
    {
        $file_name = $_GET['file_name'];

        if (empty($file_name)) {
            return new WP_REST_Response(array('file_content' => ""), 404);
        }
        $file_path = wp_upload_dir()['basedir'] .  self::FILE_UPLOADS_PATH . $file_name;
        $base64_file_data = base64_encode(file_get_contents($file_path));
        return new WP_REST_Response(array('file_content' => $base64_file_data), 200);
    }

    private function delete_attachment()
    {
        $file_name = $_GET['file_name'];
        if ($file_name == "") {
            return new WP_REST_Response([
                'message' => 'File not found',
            ], 400);
        }
        $file_path =wp_upload_dir()['basedir'] . self::FILE_UPLOADS_PATH . $file_name;
        wp_delete_file($file_path);
        return new WP_REST_Response([
                'message' => 'File deleted successfully',
            ], 200);
    }

    private function test_connection()
    {
        return new WP_REST_Response(array('success' => true), 200);
    }

    private function disconnect_connection()
    {
        $this->flush_option_keys(SENDINBLUE_WC_USER_CONNECTION_ID);
        $this->flush_option_keys(SENDINBLUE_WC_SETTINGS);
        $this->flush_option_keys(SENDINBLUE_WC_EMAIL_SETTINGS);
        $this->flush_option_keys(SENDINBLUE_WOOCOMMERCE_UPDATE);
        return new WP_REST_Response(array('success' => true), 200);
    }

    private function plugin_version()
    {
        return new WP_REST_Response(array('version' => SENDINBLUE_WC_PLUGIN_VERSION), 200);
    }

    private function save_settings($request)
    {
        (get_option(SENDINBLUE_WC_SETTINGS, null) !== null) ? update_option(SENDINBLUE_WC_SETTINGS, $request->get_body()) : add_option(SENDINBLUE_WC_SETTINGS, $request->get_body());

        return new WP_REST_Response(array('success' => true), 201);
    }

    private function email_settings($request)
    {
        (get_option(SENDINBLUE_WC_EMAIL_SETTINGS, null) !== null) ? update_option(SENDINBLUE_WC_EMAIL_SETTINGS, $request->get_body()) : add_option(SENDINBLUE_WC_EMAIL_SETTINGS, $request->get_body());

        return new WP_REST_Response(array('success' => true), 201);
    }

    private function modify_response($response)
    {
        return $response;
    }

    public function on_order_status_changed($id, $status = 'pending', $new_status = 'on-hold')
    {
        $order = $this->wc_get_order($id);

        if (strpos(SendinblueClient::NEW_ORDER_STATUS, $new_status) !== false && $status != "on-hold") {
            $this->trigger_admin_email_on_new_order($order);
        }

        if ($new_status == "processing") {
            $this->on_order_status_processing($id);
        }

        if ($new_status == "on-hold") {
            $this->on_order_status_on_hold($id);
        }

        if ($new_status == "completed") {
            $this->on_order_status_completed($id);
        }

        if (in_array($status, ['on-hold', 'processing']) && strpos(SendinblueClient::CANCELLED_ORDER_STATUS, $new_status) !== false) {
            $this->trigger_admin_email_on_cancelled_order($order);
        }

        if (in_array($status, ['on-hold', 'pending']) && strpos(SendinblueClient::FAILED_ORDER_STATUS, $new_status) !== false) {
            $this->trigger_admin_email_on_failed_order($order);
        }

        $settings = $this->get_settings();

        if (empty($settings)) {
            return;
        }

        $opt_in_checked = false;
        if (empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED]) || get_post_meta($id, 'ws_opt_in', true)) {
            $opt_in_checked = true;
        }

        if (!empty($settings[SendinblueClient::IS_SUBSCRIBE_EVENT_ENABLED])
            && $settings[SendinblueClient::IS_SUBSCRIBE_EVENT_ENABLED] == 1
            && (strpos(SendinblueClient::NEW_ORDER_STATUS, $new_status) !== false)
            && $opt_in_checked
        ) {
            $this->trigger_event_customer_sync($order);
        } elseif (!empty($settings[SendinblueClient::IS_SUBSCRIBE_EVENT_ENABLED])
            && $settings[SendinblueClient::IS_SUBSCRIBE_EVENT_ENABLED] == 2
            && (strpos(SendinblueClient::COMPLETED_ORDER_STATUS, $new_status) !== false)
            && $opt_in_checked
        ) {
            $this->trigger_event_customer_sync($order);
        }

        if (!empty($settings[SendinblueClient::IS_ORDER_CONFIRMATION_SMS])
            && $settings[SendinblueClient::IS_ORDER_CONFIRMATION_SMS]
            && (strpos(SendinblueClient::NEW_ORDER_STATUS, $new_status) !== false)
        ) {
            $this->trigger_event_sms($order, SendinblueClient::SMS_ORDER_CONFIRMATION);
        }

        if (!empty($settings[SendinblueClient::IS_ORDER_SHIPMENT_SMS])
            && $settings[SendinblueClient::IS_ORDER_SHIPMENT_SMS]
            && (strpos(SendinblueClient::COMPLETED_ORDER_STATUS, $new_status) !== false)
        ) {
            $this->trigger_event_sms($order, SendinblueClient::SMS_ORDER_SHIPMENT);
        }
    }

    private function trigger_event_customer_sync($data)
    {
        $data = $this->prepare_customer_payload($data);
        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::ORDER_CREATED, $data);
        $client->eventsSync(SendinblueClient::CONTACT_CREATED, $data);
    }

    private function prepare_customer_payload($order)
    {
        $customer_data = $order->get_data();
        $order_info = array();

        $customer_data['id'] = $customer_data['customer_id'];
        $customer_data['first_name'] = $customer_data['billing']['first_name'];
        $customer_data['last_name'] = $customer_data['billing']['last_name'];
        $customer_data['email'] = $customer_data['billing']['email'];
        if (!empty($customer_data['customer_id'])) {
            $customer_data['date_created_gmt'] = get_userdata($customer_data['customer_id'])->user_registered;
        }
        $customer_data['order_id'] = $order->get_order_number();
        $customer_data['order_date'] = gmdate('Y-m-d', strtotime($order->get_date_created()));
        $customer_data['order_price'] = $order->get_total();

        return $customer_data;
    }

    private function trigger_event_sms($order, $event)
    {
        $data = array();
        $data['firstName'] = $order->get_billing_first_name();
        $data['lastName'] = $order->get_billing_last_name();
        $data['orderPrice'] = $order->get_total();
        $data['orderDate'] = $order->get_date_created()->date("Y-m-d H:i:s");
        $data['recipient'] = $order->get_billing_phone();
        $data['country_code'] = $order->get_billing_country();

        $client = new SendinblueClient();
        $client->eventsSync($event, $data);
    }

    private function wc_get_order($order_id)
    {
        if (function_exists('wc_get_order')) {
            return wc_get_order($order_id);
        } else {
            return new \WC_Order($order_id);
        }
    }

    private function get_email_attachments_path($wc_email) {
        $complete_file_path = wp_upload_dir()['basedir'] . self::FILE_UPLOADS_PATH;
        wp_mkdir_p($complete_file_path);
        $attachments = $wc_email->get_attachments();
        $attachment_path = array();
        if ( is_array( $attachments ) ) {
            foreach ( $attachments as $key => $attachment ) {
                $user_connection_id = get_option(SENDINBLUE_WC_USER_CONNECTION_ID, null);
                $temp_file_name = $user_connection_id . uniqid('_', false) . wp_basename($attachment);
                $file_path = $complete_file_path . $temp_file_name;
                copy($attachment, $file_path);
                $attachment_path[$key]['temp_file_name'] = $temp_file_name;
                $attachment_path[$key]['file_name'] = wp_basename($attachment);
            }
        }
        return $attachment_path;
    }

    public function get_settings()
    {
        $settings = get_option(SENDINBLUE_WC_SETTINGS, null);
        $settings = empty($settings) ? null : json_decode($settings, true);

        return $settings;
    }

    public function get_email_settings()
    {
        $settings = get_option(SENDINBLUE_WC_EMAIL_SETTINGS, null);
        $settings = empty($settings) ? null : json_decode($settings, true);

        return $settings;
    }

    public function validate_api_key()
    {
        nocache_headers();

        $consumer_secret = empty($_GET['consumer_secret']) ? $_SERVER['PHP_AUTH_USER'] : $_GET['consumer_secret'];
        $consumer_key = empty($_GET['consumer_key']) ? $_SERVER['PHP_AUTH_PW'] : $_GET['consumer_key'];

        if (empty($consumer_secret) || empty($consumer_key)) {
            return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to do that.',SENDINBLUE_WC_TEXTDOMAIN), array( self::HTTP_STATUS => 401 ));
        }

        $key = $this->get_key();

        if ($key->consumer_secret === $consumer_secret && $key->consumer_key === $consumer_key) {
            return true;
        }

        return new WP_Error('rest_forbidden', __('Sorry, you are not allowed to do that.',SENDINBLUE_WC_TEXTDOMAIN), array( self::HTTP_STATUS => 401 ));
    }

    public function create_key($user_id = null)
    {
        global $wpdb;

        // if no user is specified, try the current user or find an eligible admin
        if (! $user_id ) {

            $user_id = get_current_user_id();

            // if the current user can't manage WC, try and get the first admin
            if (! user_can($user_id, 'manage_woocommerce') ) {

                $user_id = null;

                $administrator_ids = get_users(
                    array(
                    'role'   => 'administrator',
                    'fields' => 'ID',
                    )
                );

                foreach ( $administrator_ids as $administrator_id ) {

                    if (user_can($administrator_id, 'manage_woocommerce') ) {

                        $user_id = $administrator_id;
                        break;
                    }
                }

                if (! $user_id ) {
                    throw new Exception('No eligible users could be found');
                }
            }

            // otherwise, check the user that's specified
        } elseif (! user_can($user_id, 'manage_woocommerce') ) {

            throw new Exception("User {$user_id} does not have permission");
        }

        $user = get_userdata($user_id);

        if (! $user ) {
            throw new Exception('Invalid user');
        }

        $consumer_key    = 'sw_' . wc_rand_hash();
        $consumer_secret = 'sw_' . wc_rand_hash();

        $result = $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            array(
                'user_id'         => $user->ID,
                'description'     => 'SendinblueWoocommerce',
                'permissions'     => 'read_write',
                'consumer_key'    => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key'   => substr($consumer_key, -7),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        if (! $result ) {
            throw new Exception('The key could not be saved');
        }

        $key = new ApiSchema();

        $key->key_id          = $wpdb->insert_id;
        $key->user_id         = $user->ID;
        $key->consumer_key    = $consumer_key;
        $key->consumer_secret = $consumer_secret;

        // store the new key ID
        get_option(SENDINBLUE_WC_API_KEY_ID, null) !== null ? update_option(SENDINBLUE_WC_API_KEY_ID, $key->key_id) : add_option(SENDINBLUE_WC_API_KEY_ID, $key->key_id);
        get_option(SENDINBLUE_WC_API_CONSUMER_KEY, null) !== null ? update_option(SENDINBLUE_WC_API_CONSUMER_KEY, $key->consumer_key) : add_option(SENDINBLUE_WC_API_CONSUMER_KEY, $key->consumer_key);

        return $key;
    }

    public function revoke_key()
    {
        global $wpdb;

        if ($key_id = get_option(SENDINBLUE_WC_API_KEY_ID, null)) {
            $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ));
        }

        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::DELETE_CONNECTION);

        $this->flush_option_keys(SENDINBLUE_WC_API_KEY_ID);
        $this->flush_option_keys(SENDINBLUE_WC_API_CONSUMER_KEY);
    }

    public function flush_option_keys($key)
    {
        if (get_option($key, null) !== null) {
            delete_option($key);
            return true;
        }
        return false;
    }

    public function get_key()
    {
        global $wpdb;

        $key = null;

        if ($id = get_option(SENDINBLUE_WC_API_KEY_ID, null)) {
            $key = $wpdb->get_row(
                $wpdb->prepare(
                    "
                SELECT key_id, user_id, permissions, consumer_secret
                FROM {$wpdb->prefix}woocommerce_api_keys
                WHERE key_id = %d
            ", $id
                )
            );

            if (isset($key) ) {
                $key->consumer_key = get_option(SENDINBLUE_WC_API_CONSUMER_KEY, null);
            }
        }

        return $key;
    }

    private function wp_mail_template_new_account($new_customer_data) {
        $mailer = WC()->mailer();
        $email = $mailer->emails['WC_Email_Customer_New_Account'];
        $email->is_enabled(false);
        $email_html = "";
        $email_plain = "";

        $email->user_login = $new_customer_data['user_login'];
        $email->user_pass = $new_customer_data['user_pass'];
        if ($email->get_content_type() == "text/plain") {
            $email_plain = $email->get_content();
        } else {
            $email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $email->get_content_html() ) );
        }
        return [$email_html, $email_plain];
    }
    
    private function wp_mail_template_order($order, $email) {
        $email->is_enabled(false);
        $email->object = $order;
        $email_html = "";
        $email_plain = "";
        
        if ($email->get_content_type() == "text/plain") {
            $email_plain = $email->get_content();
        } else {
            $email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $email->get_content_html() ) );
        }
        return [$email_html, $email_plain];
    }

    private function wp_mail_template_customer_note($order, $email) {
        $email->is_enabled(false);
        $email->object = $order;
        $email_html = "";
        $email_plain = "";

        if ($email->get_content_type() == "text/plain") {
            $email_plain = $email->get_content();
        } else {
            $email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $email->get_content_html() ) );
        }
        
        return [$email_html, $email_plain];
    }

    private function is_email_feature_enabled()
    {
        $settings = $this->get_email_settings();
        if (empty($settings)) {
            return;
        }

        if (!isset($settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) || !$settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) {
            return false;
        }
        return $settings;
    }

    public function on_new_customer_creation($customer_id, $new_customer_data, $password_generated) {
        $settings = $this->is_email_feature_enabled();
        
        $email = WC()->mailer()->emails['WC_Email_Customer_New_Account'];
        $attachment_path = $this->get_email_attachments_path($email);

        if (isset($settings[SendinblueClient::IS_NEW_ACCOUNT_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_NEW_ACCOUNT_EMAIL_ENABLED]) {
            $tags = "New Account";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address
            if ($settings[SendinblueClient::IS_NEW_ACCOUNT_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::NEW_ACCOUNT_TEMPLATE_ID])) {
                $data['USER_LOGIN'] = $new_customer_data['user_login'];
                $data['USER_PASSWORD'] = $new_customer_data['user_pass'];
                $this->trigger_event_email_sib($new_customer_data['user_email'], $data, $settings[SendinblueClient::NEW_ACCOUNT_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                $template = $this->wp_mail_template_new_account($new_customer_data);
                $subject = WC()->mailer()->emails['WC_Email_Customer_New_Account']->get_subject();
                $this->trigger_event_email_woocommerce($new_customer_data['user_email'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }

    public function on_new_customer_note($note_id, $order)
    {
        $all_customer_notes = wc_get_order_notes([
            'order_id' => $order->get_order_number(),
            'type' => 'customer',
            'limit' => 1,
        ]);
        if (empty($all_customer_notes) || (!empty($all_customer_notes) && $all_customer_notes[0]->id != $note_id)) {
            return;
        }
        
        $order_details = $this->if_email_enabled_get_order_details($order->get_order_number());
        if (!$order_details)
        {
            return false;
        }

        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_CUSTOMER_NOTE_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_CUSTOMER_NOTE_EMAIL_ENABLED]) {
            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Customer_Note'];
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Customer Note";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address

            if ($settings[SendinblueClient::IS_CUSTOMER_NOTE_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::CUSTOMER_NOTE_TEMPLATE_ID])) {
                $this->trigger_event_email_sib($order_details['BILLING_EMAIL'], $order_details, $settings[SendinblueClient::CUSTOMER_NOTE_TEMPLATE_ID],  self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                $order = $this->wc_get_order($order->get_order_number());
                $template = $this->wp_mail_template_customer_note($order, $email);

                $subject = $email->get_subject();
                $this->trigger_event_email_woocommerce($order_details['BILLING_EMAIL'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }

    public function on_order_status_completed($order_id)
    {
        $order_details = $this->if_email_enabled_get_order_details($order_id);
        if (!$order_details)
        {
            return false;
        }
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_COMPLETED_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_COMPLETED_ORDER_EMAIL_ENABLED]) {
            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Customer_Completed_Order'];
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Completed Order";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address
            if ($settings[SendinblueClient::IS_COMPLETED_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::COMPLETED_ORDER_TEMPLATE_ID])) {
                $this->trigger_event_email_sib($order_details['BILLING_EMAIL'], $order_details, $settings[SendinblueClient::COMPLETED_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                
                $order = $this->wc_get_order($order_id);
                $template = $this->wp_mail_template_order($order, $email);
                $subject = $email->get_subject();
                $this->trigger_event_email_woocommerce($order_details['BILLING_EMAIL'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }

    public function trigger_admin_email_on_failed_order($order)
    {
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) && $settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) {

            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Failed_Order'];
            $admin_email = $email->recipient;
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Failed Order";
            $reply_to = $order->get_billing_email(); //Customer's email address
            if (isset($settings[SendinblueClient::IS_FAILED_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_FAILED_ORDER_EMAIL_ENABLED]) {
                if ($settings[SendinblueClient::IS_FAILED_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::FAILED_ORDER_TEMPLATE_ID])) {
                    $order_details = $this->prepare_order_data($order);
                    $this->trigger_event_email_sib($admin_email, $order_details, $settings[SendinblueClient::FAILED_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
                } else {
                    $template = $this->wp_mail_template_order($order, $email);
                    $subject = $email->get_subject();
                    $this->trigger_event_email_woocommerce($admin_email, $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
                }
            }
        }
    }

    public function trigger_admin_email_on_cancelled_order($order)
    {
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) && $settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) {

            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Cancelled_Order'];
            $admin_email = $email->recipient;
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Cancelled Order";
            $reply_to = $order->get_billing_email(); //Customer's email address
            if (isset($settings[SendinblueClient::IS_CANCELLED_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_CANCELLED_ORDER_EMAIL_ENABLED]) {
                if ($settings[SendinblueClient::IS_CANCELLED_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::CANCELLED_ORDER_TEMPLATE_ID])) {
                    $order_details = $this->prepare_order_data($order);
                    $this->trigger_event_email_sib($admin_email, $order_details, $settings[SendinblueClient::CANCELLED_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
                } else {
                    $template = $this->wp_mail_template_order($order, $email);
                    $subject = $email->get_subject();
                    $this->trigger_event_email_woocommerce($admin_email, $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
                }
            }
        }
    }

    public function on_order_status_on_hold($order_id)
    {
        $order_details = $this->if_email_enabled_get_order_details($order_id);
        if (!$order_details)
        {
            return false;
        }
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_ON_HOLD_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_ON_HOLD_ORDER_EMAIL_ENABLED]) {
            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Customer_On_Hold_Order'];
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Order On-Hold";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address
            if ($settings[SendinblueClient::IS_ON_HOLD_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::ON_HOLD_ORDER_TEMPLATE_ID])) {
                $this->trigger_event_email_sib($order_details['BILLING_EMAIL'], $order_details, $settings[SendinblueClient::ON_HOLD_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                $order = $this->wc_get_order($order_id);
                $template = $this->wp_mail_template_order($order, $email);
                $subject = $email->get_subject();
                $this->trigger_event_email_woocommerce($order_details['BILLING_EMAIL'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }

    public function on_order_status_refunded($order_id)
    {
        $order_details = $this->if_email_enabled_get_order_details($order_id);
        if (!$order_details)
        {
            return false;
        }
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_REFUNDED_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_REFUNDED_ORDER_EMAIL_ENABLED]) {
            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Customer_Refunded_Order'];
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Refunded Order";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address
            if ($settings[SendinblueClient::IS_REFUNDED_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::REFUNDED_ORDER_TEMPLATE_ID])) {
                $this->trigger_event_email_sib($order_details['BILLING_EMAIL'], $order_details, $settings[SendinblueClient::REFUNDED_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                $order = $this->wc_get_order($order_id);
                $template = $this->wp_mail_template_order($order, $email);
                $subject = $email->get_subject();
                $this->trigger_event_email_woocommerce($order_details['BILLING_EMAIL'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }

    public function on_order_status_processing($order_id)
    {
        $order_details = $this->if_email_enabled_get_order_details($order_id);
        if (!$order_details)
        {
            return false;
        }
        $settings = $this->get_email_settings();
        if (isset($settings[SendinblueClient::IS_PROCESSING_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_PROCESSING_ORDER_EMAIL_ENABLED]) {
            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_Customer_Processing_Order'];
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "Processing Order";
            $reply_to =  $this->get_admin_details()['email']; //Admin email address
            if ($settings[SendinblueClient::IS_PROCESSING_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::PROCESSING_ORDER_TEMPLATE_ID])) {
                $this->trigger_event_email_sib($order_details['BILLING_EMAIL'], $order_details, $settings[SendinblueClient::PROCESSING_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
            } else {
                $order = $this->wc_get_order($order_id);
                $template = $this->wp_mail_template_order($order, $email);
                $subject = $email->get_subject();
                $this->trigger_event_email_woocommerce($order_details['BILLING_EMAIL'], $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
            }
        }
    }
    
    public function trigger_admin_email_on_new_order($order)
    {
        $settings = $this->get_email_settings();
        
        if (isset($settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) && $settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) {

            $mailer = WC()->mailer();
            $email = $mailer->emails['WC_Email_New_Order'];
            $admin_email = $email->recipient;
            $attachment_path = $this->get_email_attachments_path($email);
            $tags = "New Order";
            $reply_to = $order->get_billing_email(); //Customer's email address
            if (isset($settings[SendinblueClient::IS_NEW_ORDER_EMAIL_ENABLED]) && $settings[SendinblueClient::IS_NEW_ORDER_EMAIL_ENABLED]) {
                if ($settings[SendinblueClient::IS_NEW_ORDER_TEMPLATE_ENABLED] && !empty($settings[SendinblueClient::NEW_ORDER_TEMPLATE_ID])) {
                    $order_details = $this->prepare_order_data($order);
                    $this->trigger_event_email_sib($admin_email, $order_details, $settings[SendinblueClient::NEW_ORDER_TEMPLATE_ID], self::EVENT_GROUP_SIB, $attachment_path, $tags, $reply_to);
                } else {
                    $template = $this->wp_mail_template_order($order, $email);
                    $subject = $email->get_subject();
                    $this->trigger_event_email_woocommerce($admin_email, $subject, $template, self::EVENT_GROUP_WOOCOMERCE, $attachment_path, $tags, $reply_to);
                }
            }
        }
    }

    private function if_email_enabled_get_order_details($order_id)
    {
        $settings = $this->get_email_settings();
        if (empty($settings)) {
            return;
        }
        if (!isset($settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) || !$settings[SendinblueClient::IS_EMAIL_FEATURE_ENABLED]) {
            return false;
        }

        $order = $this->wc_get_order($order_id);
        $order = $this->prepare_order_data($order);
        return $order;
    }

    private function get_admin_details()
    {
        $admin_email = \WC_Emails::instance()->get_from_address();
        $admin_name  = \WC_Emails::instance()->get_from_name();
        if( $admin_email === '' ) {
            $admin_email = trim( get_bloginfo( 'admin_email' ) );
            $admin_name  = trim( get_bloginfo( 'name' ) );
        }
        return [
            'email' => $admin_email,
            'name' => $admin_name
        ];
    }

    private function trigger_event_email_sib($to, $data, $template_id, $event_group, $attachment_path, $tags, $reply_to)
    {
        $sender_details = $this->get_admin_details();
        $payload['data'] = $data;
        $payload['template_id'] = $template_id;
        $payload['sender_email'] = $sender_details['email'];
        $payload['sender_name'] = $sender_details['name'];
        $payload['event_group'] = $event_group;
        $payload['to'] = $to;
        $payload['reply_to'] = $reply_to;
        $payload['tags'] = $tags;
        if (!empty($attachment_path)) {
            $payload['attachment_path'] = $attachment_path;
        }
        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::EMAIL_SEND, $payload);
    }

    private function trigger_event_email_woocommerce($to, $subject, $template, $event_group, $attachment_path, $tags, $reply_to)
    {
        $sender_email = \WC_Emails::instance()->get_from_address();
        $sender_name  = \WC_Emails::instance()->get_from_name();
        if( $sender_email === '' ) {
            $sender_email = trim( get_bloginfo( 'admin_email' ) );
            $sender_name  = trim( get_bloginfo( 'name' ) );
        }
        $payload['data'] = null;
        $payload['email_html'] = $template[0];
        $payload['email_text'] = $template[1];

        $payload['subject'] = $subject;
        $payload['sender_email'] = $sender_email;
        $payload['sender_name'] = $sender_name;
        $payload['event_group'] = $event_group;
        $payload['to'] = $to;
        $payload['reply_to'] = $reply_to;
        $payload['tags'] = $tags;
        if (!empty($attachment_path)) {
            $payload['attachment_path'] = $attachment_path;
        }
        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::EMAIL_SEND, $payload);
    }
    
    private function prepare_order_data($order)
    {
        if ( null != $order ) {
            $items              = $order->get_items();
            $show_download_link = $order->is_download_permitted();
            $refunded_orders    = $order->get_refunds();
            $refunded_amount    = 0;
            if ( ! empty( $refunded_orders ) ) {
                foreach ( $refunded_orders as $refunded_order ) {
                    $refunded_amount += $refunded_order->get_amount();
                }
            }
            // Get download product link.
            ob_start();
            if ( $show_download_link ) {
                foreach ( $items as $item_id => $item ) {
                    if ( version_compare( get_option( 'woocommerce_db_version' ), '3.0', '>=' ) ) {
                        wc_display_item_downloads( $item );
                    } else {
                        $order->display_item_downloads( $item );
                    }
                }
            }
            $order_download_link = ob_get_contents();
            ob_clean();

            // Get order product details.
            $order_detail = '<table style="padding-left: 0px;width: 100%;text-align: left;"><tr><th>' . __( 'Products', 'wc_sendinblue' ) . '</th><th>' . __( 'Quantity', 'wc_sendinblue' ) . '</th><th>' . __( 'Price', 'wc_sendinblue' ) . '</th></tr>';
            foreach ( $items as $item ) {
                if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
                    $product = new \WC_Product_Variation( $item['variation_id'] );
                } else {
                    $product = new \WC_Product( $item['product_id'] );
                }
                $product_name     = $item['name'];
                $product_quantity = $item['qty'];
                $sub_total        = (float) $product->get_price() * (int) $product_quantity;
                if ( version_compare( get_option( 'woocommerce_db_version' ), '3.0', '>=' ) ) {
                    $product_price = wc_price( $sub_total, array( 'currency' => $order->get_currency() ) );
                } else {
                    $product_price = wc_price( $sub_total, array( 'currency' => $order->order_currency ) );
                }
                $order_detail .= '<tr><td>' . $product_name . '</td><td>' . $product_quantity . '</td><td>' . $product_price . '</td></tr>';
            }
            $order_detail .= '</table>';
            if ( version_compare( get_option( 'woocommerce_db_version' ), '3.0', '>=' ) ) {
                $orders = array(
                    'ORDER_ID'              => $order->get_order_number(),
                    'BILLING_FIRST_NAME'    => $order->get_billing_first_name(),
                    'BILLING_LAST_NAME'     => $order->get_billing_last_name(),
                    'BILLING_COMPANY'       => $order->get_billing_company(),
                    'BILLING_ADDRESS_1'     => $order->get_billing_address_1(),
                    'BILLING_ADDRESS_2'     => $order->get_billing_address_2(),
                    'BILLING_CITY'          => $order->get_billing_city(),
                    'BILLING_STATE'         => $order->get_billing_state(),
                    'BILLING_POSTCODE'      => $order->get_billing_postcode(),
                    'BILLING_COUNTRY'       => $order->get_billing_country(),
                    'BILLING_PHONE'         => $order->get_billing_phone(),
                    'BILLING_EMAIL'         => $order->get_billing_email(),
                    'SHIPPING_FIRST_NAME'   => $order->get_shipping_first_name(),
                    'SHIPPING_LAST_NAME'    => $order->get_shipping_last_name(),
                    'SHIPPING_COMPANY'      => $order->get_shipping_company(),
                    'SHIPPING_ADDRESS_1'    => $order->get_shipping_address_1(),
                    'SHIPPING_ADDRESS_2'    => $order->get_shipping_address_2(),
                    'SHIPPING_CITY'         => $order->get_shipping_city(),
                    'SHIPPING_STATE'        => $order->get_shipping_state(),
                    'SHIPPING_POSTCODE'     => $order->get_shipping_postcode(),
                    'SHIPPING_COUNTRY'      => $order->get_shipping_country(),
                    'CART_DISCOUNT'         => strval($order->get_discount_total()),
                    'CART_DISCOUNT_TAX'     => $order->get_discount_tax(),
                    'SHIPPING_METHOD_TITLE' => $order->get_shipping_method(),
                    'CUSTOMER_USER'         => $order->get_customer_user_agent(),
                    'ORDER_KEY'             => $order->get_order_key(),
                    'ORDER_DISCOUNT'        => wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_TAX'             => wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_SHIPPING_TAX'    => wc_price( $order->get_shipping_tax(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_SHIPPING'        => wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_PRICE'           => wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_DATE'            => gmdate( 'd-m-Y', strtotime( $order->get_date_created() ) ),
                    'ORDER_SUBTOTAL'        => wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ),
                    'ORDER_DOWNLOAD_LINK'   => $order_download_link,
                    'ORDER_PRODUCTS'        => $order_detail,
                    'PAYMENT_METHOD'        => $order->get_payment_method(),
                    'PAYMENT_METHOD_TITLE'  => $order->get_payment_method_title(),
                    'CUSTOMER_IP_ADDRESS'   => $order->get_customer_ip_address(),
                    'CUSTOMER_USER_AGENT'   => $order->get_customer_user_agent(),
                    'REFUNDED_AMOUNT'       => wc_price( $refunded_amount, array( 'currency' => $order->get_currency() ) ),
                );
            } else {
                $orders = array(
                    'ORDER_ID'              => $order->get_order_number(),
                    'BILLING_FIRST_NAME'    => $order->billing_first_name,
                    'BILLING_LAST_NAME'     => $order->billing_last_name,
                    'BILLING_COMPANY'       => $order->billing_company,
                    'BILLING_ADDRESS_1'     => $order->billing_address_1,
                    'BILLING_ADDRESS_2'     => $order->billing_address_2,
                    'BILLING_CITY'          => $order->billing_city,
                    'BILLING_STATE'         => $order->billing_state,
                    'BILLING_POSTCODE'      => $order->billing_postcode,
                    'BILLING_COUNTRY'       => $order->billing_country,
                    'BILLING_PHONE'         => $order->billing_phone,
                    'BILLING_EMAIL'         => $order->billing_email,
                    'SHIPPING_FIRST_NAME'   => $order->shipping_first_name,
                    'SHIPPING_LAST_NAME'    => $order->shipping_last_name,
                    'SHIPPING_COMPANY'      => $order->shipping_company,
                    'SHIPPING_ADDRESS_1'    => $order->shipping_address_1,
                    'SHIPPING_ADDRESS_2'    => $order->shipping_address_2,
                    'SHIPPING_CITY'         => $order->shipping_city,
                    'SHIPPING_STATE'        => $order->shipping_state,
                    'SHIPPING_POSTCODE'     => $order->shipping_postcode,
                    'SHIPPING_COUNTRY'      => $order->shipping_country,
                    'CART_DISCOUNT'         => strval($order->cart_discount),
                    'CART_DISCOUNT_TAX'     => $order->cart_discount_tax,
                    'SHIPPING_METHOD_TITLE' => $order->shipping_method_title,
                    'CUSTOMER_USER'         => $order->customer_user,
                    'ORDER_KEY'             => $order->order_key,
                    'ORDER_DISCOUNT'        => wc_price( $order->order_discount, array( 'currency' => $order->order_currency ) ),
                    'ORDER_TAX'             => wc_price( $order->order_tax, array( 'currency' => $order->order_currency ) ),
                    'ORDER_SHIPPING_TAX'    => wc_price( $order->order_shipping_tax, array( 'currency' => $order->order_currency ) ),
                    'ORDER_SHIPPING'        => wc_price( $order->order_shipping, array( 'currency' => $order->order_currency ) ),
                    'ORDER_PRICE'           => wc_price( $order->order_total, array( 'currency' => $order->order_currency ) ),
                    'ORDER_DATE'            => $order->order_date,
                    'ORDER_SUBTOTAL'        => wc_price( $order->order_total - $order->order_shipping, array( 'currency' => $order->order_currency ) ),
                    'ORDER_DOWNLOAD_LINK'   => $order_download_link,
                    'ORDER_PRODUCTS'        => $order_detail,
                    'PAYMENT_METHOD'        => $order->payment_method,
                    'PAYMENT_METHOD_TITLE'  => $order->payment_method_title,
                    'CUSTOMER_IP_ADDRESS'   => $order->customer_ip_address,
                    'CUSTOMER_USER_AGENT'   => $order->customer_user_agent,
                    'REFUNDED_AMOUNT'       => wc_price( $refunded_amount, array( 'currency' => $order->order_currency ) ),
                );
            }
        }

        return $orders;
    }
}
