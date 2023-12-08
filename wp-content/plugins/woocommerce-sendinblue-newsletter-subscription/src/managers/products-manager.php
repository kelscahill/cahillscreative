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
        }

        foreach ($data['category_ids'] as $key => $value) {
            $category = get_term_by('id', $value, 'product_cat');
            if (is_object($category)) {
                $category->id = $category->term_id;
                array_push($data['categories'], $category);
            }
        }

        if (!empty($product->get_image_id())) {
            $data['images'] = [];
            $img_url = wp_get_attachment_image_src(get_post_thumbnail_id($data['id']), 'single-post-thumbnail')[0];
            $img_obj = (object) [
                'id' => (int) $product->get_image_id(),
                'src' => is_string($img_url) ? $img_url : ""
            ];
            array_push($data['images'],$img_obj);
        }

        return $data;
    }
}
