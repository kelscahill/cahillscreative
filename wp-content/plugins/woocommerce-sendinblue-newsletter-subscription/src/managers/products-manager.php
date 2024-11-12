<?php


namespace SendinblueWoocommerce\Managers;

use SendinblueWoocommerce\Managers\ApiManager;
use SendinblueWoocommerce\Clients\SendinblueClient;

require_once SENDINBLUE_WC_ROOT_PATH . '/src/managers/api-manager.php';
require_once SENDINBLUE_WC_ROOT_PATH . '/src/clients/sendinblue-client.php';

/**
 * Class ProductsManager
 *
 * @package SendinblueWoocommerce\Managers
 */
class ProductsManager
{
    private $api_manager;

    private const PRODUCT_DEFAULT_KEYS = [
        'id',
        'name',
        'status',
        'description',
        'short_description',
        'sku',
        'price',
        'regular_price',
        'sale_price',
        'stock_quantity',
        'stock_status',
        'category_ids'
    ];
  
    function __construct()
    {
        $this->api_manager = new ApiManager();
    }

    private function product_sync_enabled()
    {
        $settings = $this->api_manager->get_settings();

        return empty($settings[SendinblueClient::IS_PRODUCT_SYNC_ENABLED]) ? false : true;
    }

    private function is_valid_action($product_id, $object, $is_updated)
    {
        if (
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            (defined('REST_REQUEST') && REST_REQUEST) ||
             wp_is_post_revision($product_id) ||
             wp_is_post_autosave($product_id) ||
             !$is_updated

        ) {
            return [];
        }

        $product = wc_get_product($product_id);
        if (is_object($product) && did_action('save_post_product') === 1) {
            return $product;
        }

        return [];
    }

    public function product_deleted($product_id)
    {
        $product = wc_get_product($product_id);
        if (is_object($product) && $this->product_sync_enabled()) {
            $client = new SendinblueClient();
            $client->eventsSync(SendinblueClient::PRODUCT_DELETED, $this->prepare_payload($product));
        }
    }

    public function product_events($product_id, $object, $is_updated)
    {
        $product = $this->is_valid_action($product_id, $object, $is_updated);
        if (!empty($product) && $this->product_sync_enabled()) {
            $client = new SendinblueClient();
            $client->eventsSync(SendinblueClient::PRODUCT_CREATED, $this->prepare_payload($product));
        }
    }

    public function prepare_payload($product)
    {
        $data = [];

        foreach ($product->get_data() as $key => $value) {
            if (in_array($key, self::PRODUCT_DEFAULT_KEYS)) {
                $data[$key] = $value;
            }
        }

        $data['title'] = $product->get_title();
        $data['type'] = $product->get_type();
        $data['permalink'] = is_string($product->get_permalink()) ? $product->get_permalink() : "" ;
        $data['date_created'] = $product->get_date_created() !== null ? $product->get_date_created()->date(DATE_ATOM) : null;
        $data['date_modified'] = $product->get_date_modified() !== null ? $product->get_date_modified()->date(DATE_ATOM) : null;

        if (!empty($data['category_ids'])) {
            $data['categories'] = [];
            $data['category_names'] = '';
        }

        foreach ($data['category_ids'] as $key => $value) {
            $category = get_term_by('id', $value, 'product_cat');
            if (is_object($category)) {
                $category->id = $category->term_id;
                $data['category_names'] ? $data['category_names'] .= ", " : '';
                $data['category_names'] .= $category->name;
                array_push($data['categories'], $category);
            }
        }

        if (!empty($product->get_image_id())) {
            $data['images'] = [];
            $data['main_image_src'] = '';
            $img_url = wp_get_attachment_image_src(get_post_thumbnail_id($data['id']), 'single-post-thumbnail')[0];
            $img_obj = (object) [
                'id' => (int) $product->get_image_id(),
                'src' => is_string($img_url) ? $img_url : ""
            ];
            $data['main_image_src'] = $img_obj->src;
            array_push($data['images'],$img_obj);
        }

        return $data;
    }

    public function product_viewed($product_id = null)
    {
        $settings = $this->api_manager->get_settings();

        if (empty($settings[SendinblueClient::IS_ABANDONED_CART_ENABLED]) ||
            !$settings[SendinblueClient::IS_ABANDONED_CART_ENABLED]
        ) {
            return false;
        }

        global $product;
        $email_id = $this->user_email();
        if (empty($product) || empty($email_id)) {
            return;
        }
        $id = !empty(wp_get_session_token()) ? wp_get_session_token() : hash('sha256', $email_id);

        $item = $this->prepare_payload($product);
        $item['url'] = $item['permalink'];
        $item['category'] = $item['category_names'] ?? '';
        $item['image'] = $item['main_image_src'] ?? '';
        $data = [
            'items' => $item,
            'currency' => is_string(get_woocommerce_currency()) ? get_woocommerce_currency() : '',
            'shop_name' => get_the_title(get_option('woocommerce_shop_page_id')),
            'shop_url' => get_site_url(),
            'email' => $email_id
        ];

        $client = new SendinblueClient();
        $client->eventsSync(SendinblueClient::PRODUCT_VIEWED, ['id' => $id, 'data' => $data]);
    }

    private function user_email()
    {
        $user = wp_get_current_user();

        if (!empty($user->user_email)) {
            return $user->user_email;
        }
        if (isset($_COOKIE['email_id'])) {
            return $_COOKIE['email_id'];
        }
        if (isset($_COOKIE['tracking_email'])) {
            return  $_COOKIE['tracking_email'];
        }

        return null;
    }
}
