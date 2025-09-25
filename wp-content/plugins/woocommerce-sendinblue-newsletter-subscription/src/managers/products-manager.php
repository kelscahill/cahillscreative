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

    public function product_stock_events($product_id)
    {
        $product = wc_get_product($product_id);
        if (!is_object($product)) {
            return;
        }

        if (!empty($product) && $this->product_sync_enabled()) {
            $client = new SendinblueClient();
            $client->eventsSync(SendinblueClient::PRODUCT_CREATED, $this->prepare_payload($product));
        }
    }

    public function product_stock_update_on_order($order)
    {
        if (!is_object($order) || !$this->product_sync_enabled()) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!is_object($product) || empty($product)) {
                continue;
            }

            $data = $this->prepare_payload($product);
            if (empty($data['stock_quantity'])) {
                continue;
            }

            $client = new SendinblueClient();
            $client->eventsSync(SendinblueClient::PRODUCT_CREATED, $data);
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
        $data['stock_quantity'] = $product->get_stock_quantity();
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
            $img_url_data = wp_get_attachment_image_src(get_post_thumbnail_id($data['id']), 'single-post-thumbnail');

            $img_url = '';
            if (is_array($img_url_data) and count($img_url_data) > 0) {
                $img_url = $img_url_data[0];
            }
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
    public function show_back_in_stock_form() 
    {
        global $product;

        if (!$product || $product->is_type('variable')) return;

        $settings = $this->api_manager->get_settings();
        if (empty($settings[SendinblueClient::IS_BACK_IN_STOCK_ENABLED])) return;

        if ($product->is_in_stock()) return;

        echo $this->get_back_in_stock_form_html($product->get_id());
    }

    public function get_back_in_stock_form_html($product_id)
    {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('sib_back_in_stock_action');

        ob_start(); ?>
        <div id="sib-back-in-stock-form" style="border: 1px solid #e0e0e0; padding: 20px; margin: 30px 0; max-width: 500px;">
            <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 16px;font-weight: 600;">Notify me when available</h3>
            <p style="margin-top: 0; margin-bottom: 20px; font-size: 14px; color: #555;">
                Enter your email address and we'll notify you when this product is back in stock.
            </p>
            <form id="sib-back-in-stock-request" method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input 
                    type="text" 
                    name="back_in_stock_email" 
                    placeholder="Enter your email address" 
                    required 
                    id="sib_bis_email"
                    style="flex: 1; padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; height: 40px; line-height: 20px; box-sizing: border-box;"
                />
                <input type="hidden" id="sib_bis_product_id" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
                <input type="hidden" name="sib_bis_nonce" value="<?php echo esc_attr($nonce); ?>" />
                <button 
                    type="submit" 
                    style="background-color: #000000; color: #fff; border: none; padding: 10px 16px; font-size: 14px; border-radius: 4px; height: 40px; line-height: 20px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; margin-top: 0;"
                >
                    Notify Me
                </button>
            </form>
            <div id="sib-bis-message" style="margin-top: 15px;"></div>
        </div>

        <script type="text/javascript">
        function initSIBBackInStockForm() {
            const form = document.getElementById('sib-back-in-stock-request');
            if (!form || form.dataset.init === "true") return;

            form.dataset.init = "true";

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const email = document.getElementById('sib_bis_email').value.trim();
                const product_id = document.getElementById('sib_bis_product_id').value;
                const nonce = form.querySelector('[name="sib_bis_nonce"]').value;
                const message = document.getElementById('sib-bis-message');
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;

                message.innerHTML = '';
                message.style.display = 'none';

                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailPattern.test(email)) {
                    message.innerHTML = 'Please enter a valid email address.';
                    message.style.cssText = 'margin-top: 15px; padding: 12px 15px; background-color: #fdecea; color: #d32f2f; border-radius: 4px; font-size: 14px; display: block;';
                    submitButton.disabled = false;
                    return;
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo $ajax_url; ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    try {
                        submitButton.disabled = false;
                        const res = JSON.parse(this.responseText);
                        if (res.success) {
                            message.innerHTML = res.data?.message || 'Thank you! You will be notified when this product is back in stock.';
                            message.style.cssText = 'margin-top: 15px; padding: 12px 15px; background-color: #e6f9f0; color: #2e7d32; border-radius: 4px; font-size: 14px; display: block;';
                            form.reset();
                        } else {
                            message.innerHTML = res.data?.message || 'Something went wrong, please try again.';
                            message.style.cssText = 'margin-top: 15px; padding: 12px 15px; background-color: #fdecea; color: #d32f2f; border-radius: 4px; font-size: 14px; display: block;';
                        }
                    } catch (err) {
                        message.innerHTML = 'Unexpected response.';
                        message.style.cssText = 'margin-top: 15px; padding: 12px 15px; background-color: #fdecea; color: #d32f2f; border-radius: 4px; font-size: 14px; display: block;';
                    }
                };
                xhr.onerror = function () {
                    submitButton.disabled = false;
                    message.innerHTML = 'Something went wrong, please try again.';
                    message.style.cssText = 'margin-top: 15px; padding: 12px 15px; background-color: #fdecea; color: #d32f2f; border-radius: 4px; font-size: 14px; display: block;';
                };
                xhr.send('action=sib_back_in_stock&product_id=' + encodeURIComponent(product_id) + '&email=' + encodeURIComponent(email) + '&sib_bis_nonce=' + encodeURIComponent(nonce));
            });
        }

        initSIBBackInStockForm();
        </script>
        <?php
        return ob_get_clean();
    }

    public function sib_get_back_in_stock_form() 
    {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(['message' => 'Missing product ID']);
        }

        $product = wc_get_product($product_id);

        if (!$product || $product->is_in_stock()) {
            wp_send_json_error(['message' => 'Product is in stock']);
        }

        $settings = $this->api_manager->get_settings();
        if (empty($settings[SendinblueClient::IS_BACK_IN_STOCK_ENABLED])) {
            wp_send_json_error(['message' => 'Feature disabled']);
        }

        $html = $this->get_back_in_stock_form_html($product_id);
        wp_send_json_success(['html' => $html]);
    }


    public function render_back_in_stock_placeholder() 
    {
        ?>
        <div id="sib-back-in-stock-form-placeholder"></div>
        <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        jQuery(document).ready(function($) {
            $('form.variations_form').on('found_variation', function(event, variation) {
                const placeholder = $('#sib-back-in-stock-form-placeholder');

                if (!variation || variation.is_in_stock) {
                    placeholder.empty();
                    return;
                }

                $.post(ajaxurl, {
                    action: 'sib_get_back_in_stock_form',
                    product_id: variation.variation_id
                }, function(response) {
                    if (response.success) {
                        placeholder.html(response.data.html);
                    } else {
                        placeholder.empty();
                    }
                });
            });
        });
        </script>
        <?php
    }
	
    public function sib_back_in_stock_ajax_handler() 
    {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($_POST['sib_bis_nonce']) || !wp_verify_nonce($_POST['sib_bis_nonce'], 'sib_back_in_stock_action')) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }

        if (empty($product_id)) {
            wp_send_json_error(['message' => 'Product ID is missing.']);
        }

        $client = new SendinblueClient();
        $response = $client->notifyBackInStock($email, $product_id);

        if ($response['code'] < 200 || $response['code'] >= 300) {
            $message = $response['data']['message'] ?? 'Something went wrong, please try again.';
            wp_send_json_error(['message' => $message]);
        }

        wp_send_json_success(['message' => 'Thank you! You will be notified when this product is back in stock.']);
    }
}
