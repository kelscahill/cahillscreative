<?php


namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Clients\AutomationClient;
use SendinblueWoocommerce\Managers\ApiManager;
use SendinblueWoocommerce\Clients\SendinblueClient;
use WP_REST_Response;


require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/automation-client.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class CartEventsManagers
 *
 * @package SendinblueWoocommerce\Managers
 */
class CartEventsManagers
{
    private $automation_manager;

    private $api_manager;

    function __construct()
    {
        $this->automation_manager = new AutomationClient();
        $this->api_manager = new ApiManager();
    }

    public function save_anonymous_user_as_blacklisted(){
        $is_valid_call = $this->the_action_function();

        if (!$is_valid_call) {
            return false;
        }

        $tracking_email = sanitize_text_field($_POST['tracking_email']);
        $email_id = $this->get_email_id($tracking_email);

        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::CONTACT_CREATED, [
            "subscribed"=>"false",
            "email"=>$email_id,
            "is_anonymous_user"=>true,
        ]);
    }

    public function the_action_function()
    {

        if (!isset($_POST['tracking_email']) || empty($_POST['tracking_email'])) {
            return false;
        }

        if (!(WC()->cart)) {
            return false;
        }

        $settings = $this->api_manager->get_settings();

        if (
            empty($settings[SendinblueClient::MA_KEY]) ||
            empty($settings[SendinblueClient::IS_ABANDONED_CART_ENABLED])
        ) {
            return false;
        }

        $ma_key = $settings[SendinblueClient::MA_KEY];

        $tracking_email = sanitize_text_field($_POST['tracking_email']);
        $email_id = $this->get_email_id($tracking_email);

        if (empty($tracking_email) || empty($email_id)) {
            return false;
        }

        return $this->trigger_cart_tracking_anonymous_users($email_id, $ma_key);
    }

    public function trigger_cart_tracking_anonymous_users($email_id, $ma_key)
    {
        try {
            $tracking_event_data = array();
            $cart_id = $this->get_wc_cart_id();

            if (empty(WC()->cart->cart_contents) && !empty(WC()->cart->removed_cart_contents)) {
                $tracking_event_data = $this->get_tracking_data_cart_deleted($cart_id);
                $tracking_event_data['event'] = 'cart_deleted';
            } elseif (!empty(WC()->cart->cart_contents)) {
                $tracking_event_data = $this->get_tracking_data_cart($cart_id, $email_id);
                $tracking_event_data['event'] = 'cart_updated';
            }

            $this->automation_manager->send($tracking_event_data, $ma_key);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public function get_email_id($tracking_email = "")
    {
        $current_user   = wp_get_current_user();
        $found_email_id = '';
        $cookie_email = '';

        if (!empty($tracking_email)) {
            $cookie_email = $tracking_email;
        } else if (isset($_COOKIE['email_id'])) {
            $cookie_email = $_COOKIE['email_id'];
        } else if (isset($_COOKIE['tracking_email'])) {
            $cookie_email = $_COOKIE['tracking_email'];
        }

        if ($this->is_administrator($current_user) && $current_user->user_email == $cookie_email) {

            return $found_email_id;
        } elseif (
            (0 == $current_user->ID || $this->is_administrator($current_user)) &&
            '' != $cookie_email
        ) {
            $found_email_id = esc_attr(sanitize_text_field($cookie_email));
        } else {
            $found_email_id = $current_user->user_email;
        }

        return $found_email_id;
    }

    public function is_administrator($wp_user = null)
    {
        if (!$wp_user) {
            $wp_user = wp_get_current_user();
        }

        return !empty($wp_user->roles) && in_array('administrator', $wp_user->roles);
    }

    public function wp_login_action($username, $obj_WP_User)
    {
        if (!empty(trim($obj_WP_User->data->user_email)) && !$this->is_administrator($obj_WP_User)) {
                $this->set_email_id_cookie(trim($obj_WP_User->data->user_email));
            }
    }

    public function set_email_id_cookie($email = '')
    {
        if('' == $email) {
            $current_user = wp_get_current_user();
            if(0 != $current_user->ID && !$this->is_administrator($current_user)) {
                    $email = $current_user->user_email;
            }
        }
        if('' != $email) {
            setcookie('email_id', $_COOKIE['email_id'] = $email, time() + 86400, '/');
        }
    }

    public function ws_cart_custom_fragment_load()
    {
            echo "<input id='ws_ma_event_type' type='hidden' style='display: none' />";
            echo "<input id='ws_ma_event_data' type='hidden' style='display: none' />";
    }

    public function ws_cart_custom_fragment($cart_fragments)
    {
        $ma_key = $this->get_ma_key();

        if (empty($this->get_email_id()) || empty($ma_key)) {
            return $cart_fragments;
        }

        $tracking_event_data = array();
        $cart_id = $this->get_wc_cart_id();

        if (empty(WC()->cart->cart_contents) && !empty(WC()->cart->removed_cart_contents)) {
            $tracking_event_data = $this->get_tracking_data_cart_deleted($cart_id);
            $tracking_event_data['event'] = 'cart_deleted';
        } else {
            return $cart_fragments;
        }

        $this->automation_manager->send($tracking_event_data, $ma_key);
        return $cart_fragments;
    }

    public function get_ma_key()
    {
        $settings = $this->api_manager->get_settings();

        if (
            empty($settings[SendinblueClient::MA_KEY]) ||
            empty($settings[SendinblueClient::IS_ABANDONED_CART_ENABLED]) ||
            !$settings[SendinblueClient::IS_ABANDONED_CART_ENABLED]
        ) {
            return false;
        }

        return $settings[SendinblueClient::MA_KEY];
    }

    public function handle_cart_update_event($cart_updated)
    {
        $ma_key = $this->get_ma_key();

        if (empty($this->get_email_id()) || empty($ma_key)) {
            return $cart_updated;
        }

        $tracking_event_data = array();
        $cart_id = $this->get_wc_cart_id();

        if (!empty(WC()->cart->cart_contents)) {
            $tracking_event_data = $this->get_tracking_data_cart($cart_id);
            $tracking_event_data['event'] = 'cart_updated';
        } else {
            return $cart_updated;
        }
        $this->automation_manager->send($tracking_event_data, $ma_key);
        return $cart_updated;
    }

    public function ws_checkout_completed($order_id)
    {
        $ma_key = $this->get_ma_key();

        if (empty($ma_key)) {
            return;
        }

        if (!get_post_meta($order_id, '_thankyou_action_done', true)) {
            $order = wc_get_order($order_id);
            $order->update_meta_data('_thankyou_action_done', true, $order_id);
            $order->save();
            $tracking_event_data = $this->get_tracking_data_order($order_id);
            if (!empty($tracking_event_data['email'])) {
                $this->automation_manager->send($tracking_event_data, $ma_key);
            }
        }
    }

    public function get_wc_cart_id()
    {
        $cookie_id = 'wp_woocommerce_session_';
        $cart_id   = '';
        foreach ($_COOKIE as $key => $val) {
            if (false !== strpos($key, $cookie_id)) {
                $cart_id = $key;
            }
        }

        return $cart_id;
    }

    public function get_tracking_data_cart_deleted($cart_id)
    {
        $email = !empty($this->get_email_id()) ? $this->get_email_id() : '';
        $data = array();

        $data['items'] = array();
        $data_track = array(
            'email'     => $email,
            'event'     => '',
            'eventdata' => array(
                'id'   => 'cart:' . $cart_id,
                'data' => $data,
            ),
        );

        return $data_track;
    }

    public function get_tracking_data_cart($cart_id, $email = "")
    {
        $data = array();
        $cartitems = WC()->cart->get_cart();
        $totals = WC()->cart->get_totals();

        if (empty($email)) {
            $email = !empty($this->get_email_id()) ? $this->get_email_id() : '';
        }

        if ('' != $email){
            $this->set_email_id_cookie($email);
        }

        $data['affiliation'] = (!empty(get_bloginfo('name')) && is_string(get_bloginfo('name'))) ? get_bloginfo( 'name' ) : '';
        $data['subtotal'] = (!empty($totals['subtotal']) && is_numeric($totals['subtotal']) && !is_nan($totals['subtotal'])) ? $totals['subtotal'] : 0;
        $data['discount'] = (!empty($totals['discount_total']) && is_numeric($totals['discount_total']) && ! is_nan( $totals['discount_total'])) ? $totals['discount_total'] : 0;
        $data['shipping'] = (!empty($totals['shipping_total']) && is_numeric($totals['shipping_total']) && ! is_nan($totals['shipping_total'])) ? $totals['shipping_total'] : 0;
        $data['total_before_tax'] = (!empty( $totals['cart_contents_total']) && is_numeric($totals['cart_contents_total']) && !is_nan($totals['cart_contents_total'])) ? $totals['cart_contents_total'] : 0;
        $data['tax'] = (!empty($totals['total_tax']) && is_numeric($totals['total_tax']) && !is_nan($totals['total_tax'])) ? $totals['total_tax'] : 0;
        $data['total'] = (!empty( $totals['total']) && is_numeric($totals['total']) && !is_nan($totals['total'])) ? $totals['total'] : 0;
        $data['currency'] = is_string(get_woocommerce_currency()) ? get_woocommerce_currency() : '';
        $data['url'] = is_string(wc_get_cart_url()) ? wc_get_cart_url() : '';
        $data['checkouturl'] = is_string(wc_get_checkout_url()) ? wc_get_checkout_url() : '';

        $data['items'] = array();
        foreach ($cartitems as $key => $cartitem) {
            $item = array();
            $item['name'] = (!empty($cartitem['data']->get_title()) && is_string( $cartitem['data']->get_title() ) ) ? $cartitem['data']->get_title() : '';
            $item['sku'] = (!empty( $cartitem['data']->get_sku()) && is_string($cartitem['data']->get_sku())) ? $cartitem['data']->get_sku() : '';
            $item['id'] = (!empty($cartitem['product_id']) && is_numeric($cartitem['product_id']) && ! is_nan( $cartitem['product_id'])) ? $cartitem['product_id'] : '';
            $cats_array = wp_get_post_terms( $item['id'], 'product_cat', array('fields' => 'names'));
            $item['category'] = is_array($cats_array) ? implode(',', $cats_array) : '';
            $item['variant_id'] = (!empty( $cartitem['variation_id']) && is_numeric($cartitem['variation_id'] ) && ! is_nan($cartitem['variation_id'])) ? $cartitem['variation_id'] : '';
            $variation = new \WC_Product_Variation($item['variant_id']);
            $cartitem['variation_sku'] = $variation->get_sku();
            $item['variant_sku']  = (!empty($cartitem['variation_sku']) && is_string($cartitem['variation_sku'])) ? $cartitem['variation_sku'] : '';
            $variant_name = is_array($cartitem['variation']) ? implode(',', $cartitem['variation']) : $cartitem['variation'] ?? '';
            $item['variant_name'] = (!empty($variant_name) && is_string($variant_name)) ? $variant_name : '';
            $item['quantity'] = (!empty( $cartitem['quantity'] ) && is_numeric($cartitem['quantity']) && ! is_nan($cartitem['quantity'])) ? $cartitem['quantity'] : 0;
            $unit_price = $cartitem['data']->is_on_sale() ? $cartitem['data']->get_sale_price() : $cartitem['data']->get_regular_price();
            $final_price = round((float) $unit_price * (float) $item['quantity'], 2);
            $item['price'] = (is_numeric($final_price) && !is_nan($final_price)) ? $final_price : 0;

            $product = wc_get_product($cartitem['product_id']);
            $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
            $item['image'] = wp_get_attachment_image_url($image_id, 'full');
            $dyn_img = $this->get_dynamic_img($cartitem['data']->get_image());
            if (filter_var($dyn_img, FILTER_VALIDATE_URL)) {
                $item['image'] = $dyn_img;
            }

            $item['url'] = (!empty($cartitem['data']->get_permalink()) && is_string($cartitem['data']->get_permalink())) ? $cartitem['data']->get_permalink() : '';
            array_push($data['items'], $item);
        }

        $data_track = array(
            'email'     => $email,
            'event'     => '',
            'eventdata' => array(
                'id'   => 'cart:' . $cart_id,
                'data' => $data,
            ),
        );

        return $data_track;
    }

    public function get_tracking_data_order($order_id)
    {
        $order = wc_get_order($order_id);
        $data = array();
        $cart_id = $this->get_wc_cart_id();
        $email = !empty($this->get_email_id()) ? $this->get_email_id() : '';

        if (!$this->is_user_logged_in() || $this->is_administrator()) {
            $email = ! empty( $order->get_billing_email() ) ? $order->get_billing_email() : '';
        }

        if ('' != $email){
            $this->set_email_id_cookie($email);
        }

        $data['id'] = $order->get_order_number();
        $data['affiliation'] = (!empty(get_bloginfo('name')) && is_string(get_bloginfo('name'))) ? get_bloginfo('name') : '';
        $data['date'] = (!empty($order->get_date_created()->date(DATE_ATOM)) && is_string($order->get_date_created()->date(DATE_ATOM))) ? $order->get_date_created()->date(DATE_ATOM) : '';
        $data['subtotal'] = (!empty($order->get_subtotal()) && is_numeric($order->get_subtotal()) && !is_nan( $order->get_subtotal())) ? (float) $order->get_subtotal() : 0;
        $data['total'] = (!empty($order->get_total()) && is_numeric($order->get_total()) && !is_nan( $order->get_total())) ? (float) $order->get_total() : 0;
        $data['discount'] = (!empty($order->get_total_discount()) && is_numeric($order->get_total_discount()) && !is_nan($order->get_total_discount())) ? $order->get_total_discount() : 0;
        $data['shipping'] = (!empty($order->get_shipping_total()) && is_numeric($order->get_shipping_total()) && !is_nan($order->get_shipping_total())) ? (float) $order->get_shipping_total() : 0;
        $data['total_before_tax'] = (float) ($data['subtotal'] - $data['discount']);
        $data['tax'] = (!empty($order->get_total_tax()) && is_numeric($order->get_total_tax()) && !is_nan($order->get_total_tax())) ? round($order->get_total_tax(), 2) : 0;
        $data['revenue'] = (!empty($order->get_total()) && is_numeric($order->get_total()) && !is_nan($order->get_total())) ? (float) $order->get_total() : 0;
        $data['currency'] = (!empty($order->get_currency()) && is_string($order->get_currency())) ? $order->get_currency() : '';
        $data['url'] = (!empty($order->get_checkout_order_received_url()) && is_string($order->get_checkout_order_received_url())) ? $order->get_checkout_order_received_url() : '';

        $data['items'] = array();
        foreach ($order->get_items() as $item_key => $orderitem) {
            $product = wc_get_product($orderitem->get_product_id());

            $item = array();
            $item['name'] = (!empty($orderitem->get_name()) && is_string($orderitem->get_name())) ? $orderitem->get_name() : '';
            $item['sku'] = (!empty($product->get_sku()) && is_string($product->get_sku())) ? $product->get_sku() : '';

            $item['id'] = (!empty($orderitem->get_product_id()) && is_numeric($orderitem->get_product_id()) && ! is_nan($orderitem->get_product_id())) ? $orderitem->get_product_id() : '';
            $cats_array = wp_get_post_terms($item['id'], 'product_cat', array('fields' => 'names'));
            $item['category'] = is_array($cats_array) ? implode(',', $cats_array) : '';
            $item['variant_id'] = (!empty($orderitem->get_variation_id()) && is_numeric($orderitem->get_variation_id()) && !is_nan($orderitem->get_variation_id())) ? $orderitem->get_variation_id() : '';
            $variation = new \WC_Product_Variation($item['variant_id']);
            $item['variant_sku'] = (!empty($variation->get_sku()) && is_string($variation->get_sku())) ? $variation->get_sku() : '';
            $attributes = $variation->get_attributes();
            $item['variant_name'] = is_array($attributes) ? implode(',', $attributes) : '';
            $item['price'] = (!empty($orderitem->get_total()) && is_numeric($orderitem->get_total()) && ! is_nan($orderitem->get_total())) ? round($orderitem->get_total(), 2) : '';
            $item['tax'] = (!empty($orderitem->get_total_tax()) && is_numeric($orderitem->get_total_tax()) && ! is_nan($orderitem->get_total_tax())) ? round($orderitem->get_total_tax(), 2) : '';
            $item['quantity'] = (!empty($orderitem->get_quantity()) && is_numeric($orderitem->get_quantity()) && !is_nan($orderitem->get_quantity())) ? (int) $orderitem->get_quantity() : '';
            $product = wc_get_product($orderitem['product_id']);
            $image_id = $variation->get_image_id() ? $variation->get_image_id() : $product->get_image_id();
            $item['image'] = wp_get_attachment_image_url($image_id, 'full');

            $item['url'] = (!empty( $product->get_permalink()) && is_string($product->get_permalink())) ? $product->get_permalink() : '';
            array_push($data['items'], $item);
        }

        $shipping_address = array();
        $shipping_address['firstname'] = (!empty($order->get_shipping_first_name()) && is_string($order->get_shipping_first_name())) ? $order->get_shipping_first_name() : '';
        $shipping_address['lastname']  = (!empty($order->get_shipping_last_name()) && is_string($order->get_shipping_last_name())) ? $order->get_shipping_last_name() : '';
        $shipping_address['company']   = (!empty($order->get_shipping_company()) && is_string($order->get_shipping_company())) ? $order->get_shipping_company() : '';
        $shipping_address['phone']     = '';
        $shipping_address['address1']  = (!empty($order->get_shipping_address_1()) && is_string($order->get_shipping_address_1())) ? $order->get_shipping_address_1() : '';
        $shipping_address['address2']  = (!empty($order->get_shipping_address_2()) && is_string($order->get_shipping_address_2())) ? $order->get_shipping_address_2() : '';
        $shipping_address['city']      = (!empty( $order->get_shipping_city()) && is_string($order->get_shipping_city())) ? $order->get_shipping_city() : '';
        $shipping_address['country']   = (!empty($order->get_shipping_country()) && is_string($order->get_shipping_country())) ? $order->get_shipping_country() : '';
        $shipping_address['state']     = (!empty($order->get_shipping_state()) && is_string($order->get_shipping_state())) ? $order->get_shipping_state() : '';
        $shipping_address['zipcode']   = (!empty($order->get_shipping_postcode()) && is_string($order->get_shipping_postcode())) ? $order->get_shipping_postcode() : '';

        $billing_address = array();
        $billing_address['firstname'] = (!empty($order->get_billing_first_name()) && is_string($order->get_billing_first_name())) ? $order->get_billing_first_name() : '';
        $billing_address['lastname']  = (!empty($order->get_billing_last_name()) && is_string($order->get_billing_last_name())) ? $order->get_billing_last_name() : '';
        $billing_address['company']   = (!empty($order->get_billing_company()) && is_string($order->get_billing_company())) ? $order->get_billing_company() : '';
        $billing_address['phone']     = (!empty( $order->get_billing_phone()) && is_string($order->get_billing_phone())) ? $order->get_billing_phone() : '';
        $billing_address['address1']  = (!empty( $order->get_billing_address_1()) && is_string($order->get_billing_address_1())) ? $order->get_billing_address_1() : '';
        $billing_address['address2']  = (!empty( $order->get_billing_address_2()) && is_string($order->get_billing_address_2())) ? $order->get_billing_address_2() : '';
        $billing_address['city']      = (!empty( $order->get_billing_city()) && is_string($order->get_billing_city())) ? $order->get_billing_city() : '';
        $billing_address['country']   = (!empty( $order->get_billing_country()) && is_string($order->get_billing_country())) ? $order->get_billing_country() : '';
        $billing_address['state']     = (!empty( $order->get_billing_state()) && is_string($order->get_billing_state())) ? $order->get_billing_state() : '';
        $billing_address['zipcode']   = (!empty( $order->get_billing_postcode()) && is_string($order->get_billing_postcode())) ? $order->get_billing_postcode() : '';
        $billing_address['email']     = (!empty( $order->get_billing_email()) && is_string($order->get_billing_email())) ? $order->get_billing_email() : '';
        $data['shipping_address']     = $shipping_address;
        $data['billing_address']      = $billing_address;

        $data['payment_method']       = (!empty( $order->get_payment_method_title()) && is_string($order->get_payment_method_title())) ? $order->get_payment_method_title() : '';
        $data['customer_note']        = (!empty( $order->get_customer_note()) && is_string($order->get_customer_note())) ? $order->get_customer_note() : '';
        $data['shipping_method'] = '';
        foreach ($order->get_shipping_methods() as $item_id => $shipping_item) {
            $data['shipping_method'] = $shipping_item->get_method_title();
        }

        $data['shipping_tax'] = $order->get_shipping_tax() ?? "";
        $data['discount_tax'] = $order->get_discount_tax() ?? "";
        $data['discount_code'] = $order->get_coupon_codes() ?? "";
        $data['fee_lines'] = [];
        $fees = $order->get_fees() ?? "";

        if (!empty($fees)) {
            foreach ($fees as $key => $fee) {
                $data['fee_lines'][$key]['fee_name'] = $fee->get_name() ?? "";
                $data['fee_lines'][$key]['fee_total'] = $fee->get_total() ?? "";
                $data['fee_lines'][$key]['fee_tax'] = $fee->get_total_tax() ?? "";
            }
        }

        $data_track = array(
            'email'     => $email,
            'event'     => 'order_completed',
            'eventdata' => array(
                'id'   => 'cart:' . $cart_id,
                'data' => $data,
            ),
        );

        return $data_track;
    }

    public function is_user_logged_in()
    {
        $current_user = wp_get_current_user();
        return $current_user->ID;
    }

    private function checkout_label($settings)
    {
        $label = __('Add me to the newsletter', 'wc_sendinblue');
        if (!empty($settings[SendinblueClient::DISPLAY_OPT_IN_LABEL])) {
            $label = $settings[SendinblueClient::DISPLAY_OPT_IN_LABEL];
        }

        return $label;
    }

    public function add_optin_terms($checkout_fields)
    {
        $settings = $this->api_manager->get_settings();

        if (
            !empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED]) &&
            $settings[SendinblueClient::DISPLAY_OPT_IN_LOCATION] == 3
        ) {
            ?>
            <p class="form-row terms woocommerce-validated" id="ws_opt_in_field" style="clear:both;">
                <label class="checkbox">
                    <input type="checkbox" class="input-checkbox" name="ws_opt_in" <?php echo ('checked' == empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED]) ? '' : 'checked'); ?>>
            <?php echo esc_attr($this->checkout_label($settings)); ?>
                </label>
            </p>
            <?php
        }
    }

    public function add_optin_billing($checkout_fields)
    {
        $settings = $this->api_manager->get_settings();

        if (
            !empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED]) &&
            $settings[SendinblueClient::DISPLAY_OPT_IN_LOCATION] == 1
        ) {
            $checkout_fields['billing']['ws_opt_in'] = array(
                'type'    => 'checkbox',
                'label'   => esc_attr($this->checkout_label($settings)),
                'default' => 'checked' == empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED]) ? 0 : 1,
            );
        }

        if (
            !empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_ENABLED]) &&
            $settings[SendinblueClient::DISPLAY_OPT_IN_LOCATION] == 2
        ) {
            $checkout_fields['order']['ws_opt_in'] = array(
                'type'    => 'checkbox',
                'label'   => esc_attr($this->checkout_label($settings)),
                'default' => 'checked' == empty($settings[SendinblueClient::IS_DISPLAY_OPT_IN_CHECKED]) ? 0 : 1,
            );
        }

        return $checkout_fields;
    }

    public function add_optin_order($order_id)
    {
        $opt_in = isset($_POST['ws_opt_in']) ? true : false;
        update_post_meta($order_id, 'ws_opt_in', $opt_in);
    }

    private function get_dynamic_img($html_tags)
    {
        if (!class_exists("DOMDocument") || empty($html_tags)) {
            return null;
        }

        $doc = new \DOMDocument();
        $doc->loadHTML($html_tags);
        $tags = $doc->getElementsByTagName('img');
        foreach ($tags as $tag) {
            return $tag->getAttribute('src');
        }

        return null;
    }
}
