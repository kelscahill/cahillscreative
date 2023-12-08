<?php


namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Managers\ApiManager;
use SendinblueWoocommerce\Clients\SendinblueClient;

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class OrdersManager
 *
 * @package SendinblueWoocommerce\Managers
 */
class OrdersManager
{
    private $api_manager;

    private const ORDER_PAID_KEY = 'processing';

    private const ORDER_CANCELLED_KEY = 'cancelled';

    private const ORDER_REFUND_KEY = 'refunded';

    private const ORDER_PENDING_KEY = 'pending';

    private const ORDER_FAILED_KEY = 'failed';

    private const ORDER_ONHOLD_KEY = 'on-hold';

    private const ORDER_COMPLETED_KEY = 'completed';

    private const ORDER_DEFAULT_KEYS = [
        'id',
        'status',
        'currency',
        'discount_total',
        'discount_tax',
        'shipping_total',
        'shipping_tax',
        'cart_tax',
        'total',
        'total_tax',
        'customer_ip_address',
        'customer_note',
        'billing',
        'shipping',
        'payment_method',
    ];
  
    function __construct()
    {
        $this->api_manager = new ApiManager();
    }

    private function order_sync_enabled()
    {
        $settings = $this->api_manager->get_settings();

        return empty($settings[SendinblueClient::IS_ORDERS_SYNC_ENABLED]) ? false : true;
    }

    private function is_valid_action($order_id)
    {
        $order = wc_get_order($order_id);
        if (is_object($order)) {
            return $order;
        }

        return [];
    }

    public function order_created($order_id)
    {
        $order = $this->is_valid_action($order_id);
        if (!empty($order) && $this->order_sync_enabled()) {
            $client = new SendinblueClient();
            $client->eventsSync(SendinblueClient::ORDER_CREATE, $this->prepare_payload($order));
        }

    }

    public function order_events($order_id, $status = 'pending', $new_status = 'on-hold')
    {
        $order = $this->is_valid_action($order_id);
        $event = '';
        $create_status = [
            self::ORDER_ONHOLD_KEY,
            self::ORDER_PENDING_KEY,
            self::ORDER_FAILED_KEY
        ];
        $paid_status = [
            self::ORDER_PAID_KEY,
            self::ORDER_COMPLETED_KEY
        ];

        if (in_array($new_status, $paid_status)) {
            $event = SendinblueClient::ORDER_PAID;
        } elseif ($new_status === self::ORDER_CANCELLED_KEY) {
            $event = SendinblueClient::ORDER_CANCELLED;
        } elseif ($new_status === self::ORDER_REFUND_KEY) {
            $event = SendinblueClient::ORDER_REFUND;
        } elseif (in_array($new_status, $create_status)) {
            $event = SendinblueClient::ORDER_CREATE;
        }

        if (!empty($order) && $this->order_sync_enabled() && !empty($event)) {
            $client = new SendinblueClient();
            $client->eventsSync($event, $this->prepare_payload($order));
        }
    }

    public function prepare_payload($order)
    {
        $data = [];

        foreach ($order->get_data() as $key => $value) {
            if (in_array($key, self::ORDER_DEFAULT_KEYS)) {
                $data[$key] = $value;
            }
        }

        $data['email'] = $order->get_billing_email();
        $data['phone'] = $order->get_billing_phone();
        $data['final_amount'] = $order->get_total();
        $data['date_created'] = gmdate('Y-m-d\TH:i:s', strtotime($order->get_date_created()));
        $data['date_modified'] = gmdate('Y-m-d\TH:i:s', strtotime($order->get_date_modified()));


        if (!empty($order->get_coupon_codes())) {
            $data['coupon_code'] = [];
            foreach ($order->get_coupon_codes() as $code) {
                $data['coupon_code'][] = [
                    'code' => $code
                ];
            }
        }
        if (!empty($data['billing']['country'])) {
            $data['billing']['country_code'] = $data['billing']['country'];
            $data['billing']['country'] = WC()->countries->countries[$data['billing']['country']]; 
        }
        if (!empty($data['shipping']['country'])) {
            $data['shipping']['country_code'] = $data['shipping']['country'];
            $data['shipping']['country'] = WC()->countries->countries[$data['shipping']['country']]; 
        }
        if (!empty($order->get_total_refunded())) {
            $data['final_amount'] = (string) ($data['final_amount'] - $order->get_total_refunded());
        }
        if (!empty($order->get_customer_id())) {
            $data['email'] = get_userdata($order->get_customer_id())->user_email;
        }

        $data['line_items'] = [];
        foreach ($order->get_items() as $item_id => $item ) {
            $items = [
                'name' => $item->get_name(),
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'variant_title' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'subtotal' => $item->get_subtotal()
            ];
            array_push($data['line_items'], $items);
        }

        return $data;
    }
}
