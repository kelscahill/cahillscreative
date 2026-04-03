<?php
namespace Perfmatters;

class General {
    
    //init
    public static function init() {

        //load options
        $options = Config::$options;

        //disable emojis
        if(!empty($options['disable_emojis'])) {
            self::disable_emojis();
        }

        //disable dashicons
        if(!empty($options['disable_dashicons'])) {
            self::disable_dashicons();
        }

        //disable embeds
        if(!empty($options['disable_embeds'])) {
            self::disable_embeds();
        }

        //disable xml-rpc
        if(!empty($options['disable_xmlrpc'])) {
            self::disable_xmlrpc();
            remove_action('wp_head', 'rsd_link');
        }

        //remove rsd link
        if(!empty($options['remove_rsd_link'])) {
            remove_action('wp_head', 'rsd_link');
        }

        //remove jquery migrate
        if(!empty($options['remove_jquery_migrate']) && !Utilities::is_page_builder()) {
            self::remove_jquery_migrate();
        }

        //hide wp version
        if(!empty($options['hide_wp_version'])) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        //remove shortlink
        if(!empty($options['remove_shortlink'])) {
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('template_redirect', 'wp_shortlink_header', 11, 0);
        }

        //disable rss feeds
        if(!empty($options['disable_rss_feeds'])) {
            self::disable_rss_feeds();
            self::remove_feed_links();
        }

        //remove rss feed links
        if(!empty($options['remove_feed_links'])) {
            self::remove_feed_links();
        }

        //disable self pingbacks
        if(!empty($options['disable_self_pingbacks'])) {
            self::disable_self_pingbacks();
        }

        //disable rest api
        if(!empty($options['disable_rest_api'])) {
            self::disable_rest_api();
        }

        //remove rest api links
        if(!empty($options['remove_rest_api_links'])) {
            remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('template_redirect', 'rest_output_link_header', 11, 0);
        }

        //disable google maps
        if(!empty($options['disable_google_maps'])) {
            self::disable_google_maps();
        }

        //disable google fonts
        if(!empty($options['fonts']['disable_google_fonts'])) {
            self::disable_google_fonts();
        }

        //disable password strength meter
        if(!empty($options['disable_password_strength_meter'])) {
            self::disable_password_strength_meter();
        }

        //disable comments
        if(!empty($options['disable_comments'])) {
            self::disable_comments();
        }

        //remove comment urls
        if(!empty($options['remove_comment_urls'])) {
            self::remove_comment_urls();
        }
        //blank favicon
        if(!empty($options['blank_favicon'])) {
            add_action('wp_head', function() {
                echo '<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />';
            });
        }

        //remove global styles
        if(!empty($options['remove_global_styles'])) {
            add_action('after_setup_theme', function() {
                remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
                remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
            });
        }

        //block style behavior (wp 6.9+ uses combined assets by default; pre-6.9 we enable separate)
        if(!empty($options['separate_block_styles'])) {
            add_filter('should_load_separate_core_block_assets', function(): bool {
                return version_compare(get_bloginfo('version'), '6.9', '<');
            });
        }
        //disable heartbeat
        if(!empty($options['disable_heartbeat'])) {
            self::disable_heartbeat();
        }

        //heartbeat frequency
        if(!empty($options['heartbeat_frequency'])) {
            self::heartbeat_frequency();
        }
        
        //limit post revisions
        if(!empty($options['limit_post_revisions'])) {
            self::limit_post_revisions();
        }

        //autosave interval
        if(!empty($options['autosave_interval'])) {
            self::autosave_interval();
        }

        //disable woocommerce scripts
        if(!empty($options['disable_woocommerce_scripts'])) {
            add_action('wp_enqueue_scripts', [__CLASS__, 'disable_woocommerce_scripts'], 99);
        }

        //disable woocommerce cart fragmentation
        if(!empty($options['disable_woocommerce_cart_fragmentation'])) {
            add_action('wp_enqueue_scripts', [__CLASS__, 'disable_woocommerce_cart_fragmentation'], 99);
        }

        //disable woocommerce status meta box
        if(!empty($options['disable_woocommerce_status'])) {
            add_action('wp_dashboard_setup', [__CLASS__, 'disable_woocommerce_status']);
        }

        //disable woocommerce widgets
        if(!empty($options['disable_woocommerce_widgets'])) {
            add_action('widgets_init', [__CLASS__, 'disable_woocommerce_widgets'], 99);
        }

        //disable capital_P_dangit filter
        $filters = ['the_content', 'the_title', 'wp_title', 'comment_text'];
        foreach($filters as $filter) {
            $priority = has_filter($filter, 'capital_P_dangit');
            if($priority !== false) {
                remove_filter($filter, 'capital_P_dangit', $priority);
            }
        }

        //change login url
        if(!empty($options['login_url'])) {
            self::login_url_plugins_loaded();
            add_action('wp_loaded', [__CLASS__, 'wp_loaded']);
            add_action('setup_theme', [__CLASS__, 'disable_customize_php'], 1);
            add_filter('site_url', [__CLASS__, 'site_url'], 10, 4);
            add_filter('network_site_url', [__CLASS__, 'network_site_url'], 10, 3);
            add_filter('wp_redirect', [__CLASS__, 'wp_redirect'], 10, 2);
            add_filter('site_option_welcome_email', [__CLASS__, 'welcome_email']);
            add_filter('admin_url', [__CLASS__, 'admin_url']);
            remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
        }

        //global scripts
        if(!Config::$code_disabled) {
            if(!empty($options['assets']['header_code'])) {
                self::insert_header_code();
            }
            if(!empty($options['assets']['body_code'])) {
                self::insert_body_code();
            }
            if(!empty($options['assets']['footer_code'])) {
                self::insert_footer_code();
            }
        }

        //update options
        add_action('admin_init', function() {
            add_filter('pre_update_option_perfmatters_options', [__CLASS__, 'pre_update_option_perfmatters_options'], 10, 2);
        });
    }

    //disable emojis (removes wp-emoji script/styles everywhere; keeps OS emoji)
    public static function disable_emojis(): void {

        //run on init
        add_action('init', function(): void {

            //front: head script, print styles, enqueue
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');

            //admin: print scripts/styles, enqueue
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');

            //embed: detection script and styles
            remove_action('embed_head', 'print_emoji_detection_script');
            remove_action('embed_head', 'wp_enqueue_emoji_styles');

            //feed and mail: no staticize
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

            //tinymce: strip wpemoji plugin
            add_filter('tiny_mce_plugins', function($plugins) {
                return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
            });

            //block emoji svg url on front (admin keeps it for editor)
            add_filter('emoji_svg_url', function($url) {
                return is_admin() ? $url : false;
            });
        });
    }

    //disable dashicons (no dashicons on front when not logged in)
    public static function disable_dashicons(): void {

        add_action('wp_enqueue_scripts', function(): void {

            //front only when logged out: dequeue and deregister
            if(!is_admin() && !is_user_logged_in()) {
                wp_dequeue_style('dashicons');
                wp_deregister_style('dashicons');
            }
        });
    }

    //disable embeds (removes wp-embed.min.js, oembed discovery, and embed route)
    public static function disable_embeds(): void {

        //run on init
        add_action('init', function(): void {

            global $wp;

            //remove embed from public query vars
            $wp->public_query_vars = array_diff($wp->public_query_vars, ['embed']);

            //no oembed discovery; no parsing result; no pre_oembed
            add_filter('embed_oembed_discover', '__return_false');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
            remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);

            //wp_head: discovery at 4 and 10, host js
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 4);
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
            remove_action('wp_head', 'wp_oembed_add_host_js');

            //embed context: no enqueue
            remove_action('embed_head', 'enqueue_embed_scripts');

            //tinymce: strip wpembed plugin
            add_filter('tiny_mce_plugins', function($plugins) {
                return is_array($plugins) ? array_diff($plugins, ['wpembed']) : [];
            });

            //rewrite: drop rules that serve embed
            add_filter('rewrite_rules_array', function($rules) {
                foreach($rules as $rule => $rewrite) {
                    if(is_string($rewrite) && strpos($rewrite, 'embed=true') !== false) {
                        unset($rules[$rule]);
                    }
                }
                return $rules;
            });
        }, 9999);
    }

    //disable xml-rpc (disables endpoint, removes RSD/X-Pingback, blocks direct xmlrpc.php)
    public static function disable_xmlrpc(): void {

        //core switch and options
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('pre_update_option_enable_xmlrpc', '__return_false');
        add_filter('pre_option_enable_xmlrpc', '__return_zero');

        //strip X-Pingback from response headers
        if(!has_filter('wp_headers', [__CLASS__, 'strip_x_pingback_header'])) {
            add_filter('wp_headers', [__CLASS__, 'strip_x_pingback_header']);
        }

        //disable pings
        add_filter('pings_open', '__return_false', 9999);

        //strip pingback link from buffered html
        add_filter('perfmatters_output_buffer_template_redirect', function(string $html): string {
            return preg_replace('#<link[^>]+rel=["\']pingback["\'][^>]*/?>#i', '', $html);
        }, 2);

        //block direct request to xmlrpc.php with 403
        add_action('init', function(): void {
            if(empty($_SERVER['SCRIPT_FILENAME']) || basename($_SERVER['SCRIPT_FILENAME']) !== 'xmlrpc.php') {
                return;
            }
            status_header(403);
            exit;
        });
    }

    //common helper to strip X-Pingback from response headers
    public static function strip_x_pingback_header($headers): array {
        if(!is_array($headers)) {
            $headers = (array) $headers;
        }
        unset($headers['X-Pingback'], $headers['x-pingback']);
        return $headers;
    }

    //remove jquery migrate
    public static function remove_jquery_migrate(): void {

        add_filter('wp_default_scripts', function(&$scripts) {

            //front only: re-register jquery without migrate dependency
            if(!is_admin()) {
                $scripts->remove('jquery');
                $scripts->add('jquery', false, ['jquery-core'], '1.12.4');
            }

            return $scripts;
        });
    }

    //disable rss feeds (redirect feed requests to home; show message if redirect fails)
    public static function disable_rss_feeds(): void {

        add_action('template_redirect', function(): void {

            if(!is_feed() || is_404()) {
                return;
            }

            //return 410 for all feed requests
            wp_die(__('RSS feeds have been disabled.', 'perfmatters'), '', ['response' => 410]);
        }, 1);
    }

    //remove rss feed links (strip feed link tags from head)
    public static function remove_feed_links(): void {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    //disable self pingbacks (strip same-site URLs from the list of URLs to ping)
    public static function disable_self_pingbacks(): void {

        add_filter('pre_ping', function(array $links): array {
            $home = home_url('/');

            foreach($links as $key => $link) {
                if(strpos($link, $home) === 0) {
                    unset($links[$key]);
                }
            }

            return $links;
        });
    }

    //disable rest api (block or restrict by role/login; allow exceptions for known plugins)
    public static function disable_rest_api(): void {

        add_filter('rest_authentication_errors', function($result) {
            if(!empty($result)) {
                return $result;
            }

            $options = Config::$options;
            $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';

            //allow listed routes (plugins that need REST)
            $exceptions = apply_filters('perfmatters_rest_api_exceptions', [
                'contact-form-7',
                'wordfence',
                'elementor',
                'ws-form',
                'litespeed',
                'wp-recipe-maker',
                'iawp',
                'sureforms',
                'surecart',
                'sliderrevolution',
                'mollie'
            ]);

            if(Utilities::match_in_array($rest_route, $exceptions)) {
                return $result;
            }

            //apply restriction: non-admins or logged-out users
            $disabled = false;
            if($options['disable_rest_api'] === 'disable_non_admins' && !current_user_can('manage_options')) {
                $disabled = true;
            }
            elseif($options['disable_rest_api'] === 'disable_logged_out' && !is_user_logged_in()) {
                $disabled = true;
            }

            if($disabled) {
                return new \WP_Error('rest_authentication_error', __('Sorry, you do not have permission to make REST API requests.', 'perfmatters'), ['status' => 401]);
            }

            return $result;
        }, 20);
    }

    //disable google maps (strip maps scripts from buffered output; optional post ID exclusions)
    public static function disable_google_maps(): void {

        add_action('template_redirect', function(): void {
            $options = Config::$options;

            //exclusions: skip stripping on listed post IDs or blog home
            if(!empty($options['disable_google_maps_exclusions'])) {
                $exclusions = array_map('trim', explode(',', $options['disable_google_maps_exclusions']));

                if(is_singular()) {
                    global $post;
                    if(!empty($post->ID) && in_array((string) $post->ID, $exclusions, true)) {
                        return;
                    }
                }

                if(is_home() && in_array('blog', $exclusions, true)) {
                    return;
                }
            }

            //remove google maps script tags from html
            ob_start(function(string $html): string {
                return preg_replace('/<script[^>]*\/\/maps\.(googleapis|google|gstatic)\.com\/[^>]*><\/script>/i', '', $html);
            });
        });
    }

    //disable google fonts (strip google font link tags from buffered output)
    public static function disable_google_fonts(): void {

        add_action('template_redirect', function(): void {
            ob_start(function(string $html): string {
                return preg_replace('/<link[^>]*\/\/fonts\.(googleapis|google|gstatic)\.com[^>]*>/i', '', $html);
            });
        });
    }

    //disable password strength meter (strip WP/Woo meter scripts except on login, reset, Woo pages)
    public static function disable_password_strength_meter(): void {

        add_action('wp_print_scripts', function(): void {

            if(is_admin()) {
                return;
            }

            //keep on login, reset password, register
            $pagenow = $GLOBALS['pagenow'] ?? '';
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            if($pagenow === 'wp-login.php' || in_array($action, ['rp', 'lostpassword', 'register'], true)) {
                return;
            }

            //keep on woocommerce
            if(Utilities::is_woocommerce()) {
                return;
            }

            wp_dequeue_script('zxcvbn-async');
            wp_deregister_script('zxcvbn-async');
            wp_dequeue_script('password-strength-meter');
            wp_deregister_script('password-strength-meter');
            wp_dequeue_script('wc-password-strength-meter');
            wp_deregister_script('wc-password-strength-meter');
        }, 100);
    }

    //disable comments (remove UI, close comments, block feeds and admin pages)
    public static function disable_comments(): void {

        $options = Config::$options;

       //disable built-in recent comments widget
        add_action('widgets_init', function(): void {
            unregister_widget('WP_Widget_Recent_Comments');
            add_filter('show_recent_comments_widget_style', '__return_false');
        });

        //always remove X-Pingback when comments disabled
        if(!has_filter('wp_headers', [__CLASS__, 'strip_x_pingback_header'])) {
            add_filter('wp_headers', [__CLASS__, 'strip_x_pingback_header']);
        }

        //remove feed links
        if(empty($options['remove_feed_links'])) {
            remove_action('wp_head', 'feed_links_extra', 3);
        }

        //disable comment feed requests
        add_action('template_redirect', function(): void {
            if(is_comment_feed()) {
                wp_die(__('Comments are disabled.', 'perfmatters'), '', ['response' => 403]);
            }
        }, 9);

        //remove comment links from the admin bar
        add_action('template_redirect', [__CLASS__, 'remove_admin_bar_comment_links']);
        add_action('admin_init', [__CLASS__, 'remove_admin_bar_comment_links']);

        //finish disabling comments
        add_action('wp_loaded', [__CLASS__, 'wp_loaded_disable_comments']);
    }

    //remove comment links from the admin bar
    public static function remove_admin_bar_comment_links(): void {
        if(is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);

            //network admin bar links
            if(is_multisite()) {
                add_action('admin_bar_menu', function($wp_admin_bar) {
                    if(!function_exists('is_plugin_active_for_network')) {
                        require_once(ABSPATH . '/wp-admin/includes/plugin.php');
                    }
                    if(is_plugin_active_for_network('perfmatters/perfmatters.php') && is_user_logged_in()) {
                        //remove for all sites
                        foreach($wp_admin_bar->user->blogs as $blog) {
                            $wp_admin_bar->remove_menu('blog-' . $blog->userblog_id . '-c');
                        }
                    }
                    else {
                        //remove for current site
                        $wp_admin_bar->remove_menu('blog-' . get_current_blog_id() . '-c');
                    }
                }, 500);
            }
        }
    }

    //disable comments
    public static function wp_loaded_disable_comments(): void {

        //remove comment support from all post types
        $post_types = get_post_types(['public' => true], 'names');
        if(!empty($post_types)) {
            foreach($post_types as $post_type) {
                if(post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        }

        //close comment filters
        add_filter('comments_array', function() { return []; }, 20, 2);
        add_filter('comments_open', function() { return false; }, 20, 2);
        add_filter('pings_open', function() { return false; }, 20, 2);

        if(is_admin()) {

            //remove menu links + disable admin pages
            add_action('admin_menu', [__CLASS__, 'admin_menu_remove_comments'], 9999);

            //hide comments from dashboard
            add_action('admin_print_styles-index.php', function() {
                echo '<style>#dashboard_right_now .comment-count,#dashboard_right_now .comment-mod-count,#latest-comments,#welcome-panel .welcome-comments{display:none!important}</style>';
            });

            //hide comments from profile
            add_action('admin_print_styles-profile.php', function() {
                echo '<style>.user-comment-shortcuts-wrap{display:none!important}</style>';
            });

            //remove recent comments meta
            add_action('wp_dashboard_setup', function() {
                remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            });

            //disable pingback flag
            add_filter('pre_option_default_pingback_flag', '__return_zero');
        }
        else {

            //replace comments template with blank one
            add_filter('comments_template', function() {
                return dirname(__DIR__) . '/comments-template.php';
            }, 20);

            //remove comment reply script
            wp_deregister_script('comment-reply');
            
            //disable the comments feed link
            add_filter('feed_links_show_comments_feed', '__return_false');
        }
    }

    //remove menu links + disable admin pages
    public static function admin_menu_remove_comments(): void {

        global $pagenow;

        //remove comment + discussion menu links
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');

        //disable comments pages
        if($pagenow == 'comment.php' || $pagenow == 'edit-comments.php') {
            wp_die(__('Comments are disabled.', 'perfmatters'), '', ['response' => 403]);
        }

        //disable discussion page
        if($pagenow == 'options-discussion.php') {
            wp_die(__('Comments are disabled.', 'perfmatters'), '', ['response' => 403]);
        }
    }

    //remove comment urls (author link + website field)
    public static function remove_comment_urls(): void {

        add_action('template_redirect', function() {

            //compatibility
            if(defined('KADENCE_VERSION')) {
                return;
            }

            add_filter('get_comment_author_link', function($return, $author, $comment_id): string {
                return (string) $author;
            }, 10, 3);

            add_filter('get_comment_author_url', function() {
                return false;
            });

            add_filter('comment_form_default_fields', function(array $fields): array {
                unset($fields['url']);
                return $fields;
            }, 9999);
        });
    }

    //disable woocommerce scripts
    public static function disable_woocommerce_scripts() {
        if(class_exists('WooCommerce')) {

            if(!apply_filters('perfmatters_disable_woocommerce_scripts', true)) {
                return;
            }

            if(!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page() && !is_product() && !is_product_category() && !is_shop()) {
                
                //Dequeue WooCommerce Styles
                $styles = [
                    'woocommerce-general',
                    'woocommerce-layout',
                    'woocommerce-smallscreen',
                    'woocommerce_frontend_styles',
                    'woocommerce_fancybox_styles',
                    'woocommerce_chosen_styles',
                    'woocommerce_prettyPhoto_css',
                    'woocommerce-inline',
                    'wc-blocks-style',
                    'wc-blocks-vendors-style'
                ];
                foreach($styles as $style) {
                    wp_dequeue_style($style);
                    wp_deregister_style($style);
                }

                //Dequeue WooCommerce Scripts
                $scripts = [
                    'wc_price_slider',
                    'wc-single-product',
                    'wc-add-to-cart',
                    'wc-checkout',
                    'wc-add-to-cart-variation',
                    'wc-single-product',
                    'wc-cart',
                    'wc-chosen',
                    'woocommerce',
                    'prettyPhoto',
                    'prettyPhoto-init',
                    'jquery-blockui',
                    'jquery-placeholder',
                    'fancybox',
                    'jqueryui'
                ];
                foreach($scripts as $script) {
                    wp_dequeue_script($script);
                    wp_deregister_script($script);
                }

                //Remove no-js Script + Body Class
                add_filter('body_class', function($classes) {
                    remove_action('wp_footer', 'wc_no_js');
                    $classes = array_diff($classes, ['woocommerce-no-js']);
                    return array_values($classes);
                },10, 1);
            }
        }
    }

    //disable woocommerce cart fragmentation
    public static function disable_woocommerce_cart_fragmentation() {
        if(class_exists('WooCommerce')) {

            global $wp_scripts;

            if(!empty($wp_scripts->registered['wc-cart-fragments'])) {

                $cart_fragments_src = $wp_scripts->registered['wc-cart-fragments']->src;
                $wp_scripts->registered['wc-cart-fragments']->src = null;

                add_action('wp_head', function() use ($cart_fragments_src) {

                    echo '<script>function perfmatters_check_cart_fragments(){if(null!==document.getElementById("perfmatters-cart-fragments"))return!1;if(document.cookie.match("(^|;) ?woocommerce_cart_hash=([^;]*)(;|$)")){var e=document.createElement("script");e.id="perfmatters-cart-fragments",e.src="' . $cart_fragments_src . '",e.async=!0,document.head.appendChild(e)}}perfmatters_check_cart_fragments(),document.addEventListener("click",function(){setTimeout(perfmatters_check_cart_fragments,1e3)});</script>';
                });
            }
        }
    }

    //disable woocommerce status
    public static function disable_woocommerce_status() {
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
    }

    //disable woocommerce widgets
    public static function disable_woocommerce_widgets() {
        $options = Config::$options;

        $widgets = [
            'WC_Widget_Products',
            'WC_Widget_Product_Categories',
            'WC_Widget_Product_Tag_Cloud',
            'WC_Widget_Cart',
            'WC_Widget_Layered_Nav',
            'WC_Widget_Layered_Nav_Filters',
            'WC_Widget_Price_Filter',
            'WC_Widget_Product_Search',
            'WC_Widget_Recently_Viewed'
        ];

        if(empty($options['disable_woocommerce_reviews'])) {
            $widgets = array_merge($widgets, ['WC_Widget_Recent_Reviews', 'WC_Widget_Top_Rated_Products', 'WC_Widget_Rating_Filter']);
        }

        foreach($widgets as $widget) {
            unregister_widget($widget);
        }
    }

    private static function admin_notice_error(string $title, string $message): void {
        echo '<div class="notice notice-error"><p><strong>' . esc_html($title) . ':</strong> ' . esc_html($message) . '</p></div>';
    }

    //disable heartbeat (replace with no-op script in admin; full remove on front)
    public static function disable_heartbeat(): void {
        add_action('init', function() {

            //exception pages in admin: keep real heartbeat
            if(is_admin()) {
                global $pagenow;
                if(!empty($pagenow)) {
                    if($pagenow === 'admin.php' && !empty($_GET['page'])) {
                        $exceptions = ['gf_edit_forms', 'gf_entries', 'gf_settings'];
                        if(in_array($_GET['page'], $exceptions, true)) {
                            return;
                        }
                    }
                    if($pagenow === 'site-health.php') {
                        return;
                    }
                }
            }

            //allow_posts: only replace when not on post edit screens
            $options = Config::$options;
            $mode = $options['disable_heartbeat'] ?? '';
            if($mode === 'allow_posts') {
                global $pagenow;
                if($pagenow === 'post.php' || $pagenow === 'post-new.php') {
                    return;
                }
            }

            //skip during dynamic requests (AJAX, REST, cron) – script APIs may be unavailable
            if(Utilities::is_dynamic_request()) {
                return;
            }
            if(!function_exists('wp_deregister_script')) {
                return;
            }

            wp_deregister_script('heartbeat');
            if(is_admin()) {
                wp_register_script('heartbeat', plugins_url('js/heartbeat.js', dirname(__DIR__)));
                wp_enqueue_script('heartbeat');
            }
        }, 1);
    }

    //heartbeat frequency
    public static function heartbeat_frequency(): void {
        add_filter('heartbeat_settings', function(array $settings): array {
            $interval = (int) (Config::$options['heartbeat_frequency'] ?? 0);
            if($interval > 0) {
                $settings['interval'] = $interval;
                $settings['minimalInterval'] = $interval;
            }
            return $settings;
        });
    }

    //limit post revisions (define WP_POST_REVISIONS or show notice if already defined elsewhere)
    public static function limit_post_revisions(): void {
        $options = Config::$options;
        if(defined('WP_POST_REVISIONS')) {
            add_action('admin_notices', function() {
                self::admin_notice_error(
                    __('Perfmatters Warning', 'perfmatters'),
                    __('WP_POST_REVISIONS is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'perfmatters')
                );
            });
        }
        else {
            define('WP_POST_REVISIONS', $options['limit_post_revisions']);
        }
    }

    //autosave interval (define AUTOSAVE_INTERVAL or show notice if already defined elsewhere)
    public static function autosave_interval(): void {
        $options = Config::$options;
        if(defined('AUTOSAVE_INTERVAL')) {
            add_action('admin_notices', function() {
                self::admin_notice_error(
                    __('Perfmatters Warning', 'perfmatters'),
                    __('AUTOSAVE_INTERVAL is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'perfmatters')
                );
            });
        }
        else {
            define('AUTOSAVE_INTERVAL', $options['autosave_interval']);
        }
    }

    //login url
    public static function site_url($url, $path, $scheme, $blog_id) {
        return self::filter_wp_login($url, $scheme);
    }

    public static function network_site_url($url, $path, $scheme) {
        return self::filter_wp_login($url, $scheme);
    }

    public static function wp_redirect($location, $status) {

        //prevent logged out auth redirect from going to hidden slug
        if(!is_user_logged_in()) {
            $parsed_url = wp_parse_url($location);
            $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';

            if($path === self::login_slug()) {
                $parsed_query = [];
                if(isset($parsed_url['query'])) {
                    wp_parse_str($parsed_url['query'], $parsed_query);

                    //reauth redirect
                    if(!empty($parsed_query['redirect_to']) && !empty($parsed_query['reauth'])) {
                        $redirect_url = wp_parse_url($parsed_query['redirect_to']);
                        $redirect_query = [];
                        if(!empty($redirect_url['query'])) {
                            wp_parse_str($redirect_url['query'], $redirect_query);
                        }

                        //allow certain queries through
                        if(!empty($redirect_query) && isset($redirect_query['newuseremail'])) {
                            return self::filter_wp_login($location);
                        }

                        self::disable_login_url();
                    }
                }
            }
        }

        return self::filter_wp_login($location);
    }

    public static function filter_wp_login($url, $scheme = null) {

        //wp-login.php Being Requested
        if(is_string($url) && strpos($url, 'wp-login.php') !== false) {

            //Set HTTPS Scheme if SSL
            if(is_ssl()) {
                $scheme = 'https';
            }

            //Check for Query String and Craft New Login URL
            $query_string = explode('?', $url);
            if(isset($query_string[1])) {
                parse_str($query_string[1], $query_string);
                if(isset($query_string['login'])) {
                    $query_string['login'] = rawurlencode($query_string['login']);
                }
                $url = add_query_arg($query_string, self::login_url($scheme));
            } 
            else {
                $url = self::login_url($scheme);
            }
        }

        //Return Finished Login URL
        return $url;
    }

    public static function login_url($scheme = null) {

        //Return Full New Login URL Based on Permalink Structure
        if(get_option('permalink_structure')) {
            return Utilities::trailingslashit(home_url('/', $scheme) . self::login_slug());
        } 
        else {
            return home_url('/', $scheme) . '?' . self::login_slug();
        }
    }

    public static function login_slug() {

        $options = Config::$options;

        //Return Login URL Slug if Available
        if(!empty($options['login_url'])) {
            return $options['login_url'];
        } 
    }

    public static function login_url_plugins_loaded() {

        //Declare Global Variables
        global $pagenow;
        global $perfmatters_wp_login;

        //Parse Requested URI
        $URI = parse_url($_SERVER['REQUEST_URI']);
        $path = !empty($URI['path']) ? untrailingslashit($URI['path']) : '';
        $slug = self::login_slug();

        //Non Admin wp-login.php URL
        if(!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-login.php') !== false || $path === site_url('wp-login', 'relative'))) {

            //Set Flag
            $perfmatters_wp_login = true;

            //Prevent Redirect to Hidden Login
            $_SERVER['REQUEST_URI'] = Utilities::trailingslashit('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } 
        //wp-register.php
        elseif(!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-register.php') !== false || strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-signup.php') !== false || $path === site_url('wp-register', 'relative'))) {

            //Set Flag
            $perfmatters_wp_login = true;

            //Prevent Redirect to Hidden Login
            $_SERVER['REQUEST_URI'] = Utilities::trailingslashit('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        }
        //Hidden Login URL
        elseif($path === home_url($slug, 'relative') || (!get_option('permalink_structure') && isset($_GET[$slug]) && empty($_GET[$slug]))) {
            
            //Override Current Page w/ wp-login.php
            $pagenow = 'wp-login.php';
        }
    }

    public static function wp_loaded() {

        if(!apply_filters('perfmatters_login_url', true)) {
            return;
        }

        //Declare Global Variables
        global $pagenow;
        global $perfmatters_wp_login;

        //Parse Requested URI
        $URI = parse_url($_SERVER['REQUEST_URI']);

        //Disable Normal WP-Admin
        if(is_admin() && !is_user_logged_in() && !defined('WP_CLI') && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && (isset($_GET) && empty($_GET['adminhash']) && empty($_GET['newuseremail']))) {
            self::disable_login_url();
        }

        //Requesting Hidden Login Form - Path Mismatch
        if($pagenow === 'wp-login.php' && $URI['path'] !== Utilities::trailingslashit($URI['path']) && get_option('permalink_structure')) {

            //Local Redirect to Hidden Login URL
            $URL = Utilities::trailingslashit(self::login_url()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            wp_safe_redirect($URL);
            die();
        }
        //Requesting wp-login.php Directly, Disabled
        elseif($perfmatters_wp_login) {
            self::disable_login_url();
        } 
        //Requesting Hidden Login Form
        elseif($pagenow === 'wp-login.php') {

            //Declare Global Variables
            global $error, $interim_login, $action, $user_login;
            
            //User Already Logged In
            if(is_user_logged_in() && !isset($_REQUEST['action'])) {
                wp_safe_redirect(admin_url());
                die();
            }

            //Include Login Form
            @require_once ABSPATH . 'wp-login.php';
            die();
        }
    }

    public static function disable_customize_php() {

        //Declare Global Variable
        global $pagenow;

        //Disable customize.php from Redirecting to Login URL
        if(!is_user_logged_in() && $pagenow === 'customize.php') {
            self::disable_login_url();
        }
    }

    public static function welcome_email(string $value): string {

        $options = Config::$options;

        //Check for Custom Login URL and Replace
        if(!empty($options['login_url'])) {
            $value = str_replace(['wp-login.php', 'wp-admin'], Utilities::trailingslashit($options['login_url']), $value);
        }

        return $value;
    }

    public static function admin_url(string $url): string {

        //Check for Multisite Admin
        if(is_multisite() && ms_is_switched() && is_admin()) {

            global $current_blog;

            //Get Current Switched Blog
            $switched_blog_id = get_current_blog_id();

            if($switched_blog_id != $current_blog->blog_id) {

                $perfmatters_blog_options = get_blog_option($switched_blog_id, 'perfmatters_options');

                //Swap Custom Login URL Only with Base /wp-admin/ Links
                if(!empty($perfmatters_blog_options['login_url'])) {
                    $url = preg_replace('/\/wp-admin\/$/', '/' . $perfmatters_blog_options['login_url'] . '/', $url);
                } 
            }
        }

        return $url;
    }

    //choose what to do when disabling a login url endpoint
    public static function disable_login_url() {

        $options = Config::$options;

        if(!empty($options['login_url_behavior'])) {
            if($options['login_url_behavior'] == '404') {
                $template = get_query_template('404');
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
                if(!empty($template)) {
                    include($template);
                }
                die();
            }
            elseif($options['login_url_behavior'] == 'home') {
                wp_safe_redirect(home_url());
                die();
            }
            elseif($options['login_url_behavior'] == 'redirect' && !empty($options['login_url_redirect'])) {
                wp_safe_redirect(home_url($options['login_url_redirect']));
                die();
            }
        }

        $message = !empty($options['login_url_message']) ? $options['login_url_message'] : __('This has been disabled.', 'perfmatters');
        wp_die($message, 403);
    }

    //pre update options
    public static function pre_update_option_perfmatters_options($new_value, $old_value) {

        //clear used css
        if((empty($new_value['assets']['rucss_excluded_stylesheets']) !== empty($old_value['assets']['rucss_excluded_stylesheets'])) || (empty($new_value['assets']['rucss_excluded_selectors']) !== empty($old_value['assets']['rucss_excluded_selectors']))) {
            CSS::clear_used_css();
        }

        //clear local fonts
        if((empty($new_value['fonts']['display_swap']) !== empty($old_value['fonts']['display_swap'])) || (isset($new_value['fonts']['cdn_url']) && isset($old_value['fonts']['cdn_url']) && $new_value['fonts']['cdn_url'] !== $old_value['fonts']['cdn_url']) || (($new_value['fonts']['subsets'] ?? '') !== ($old_value['fonts']['subsets'] ?? ''))) {
            Fonts::clear_local_fonts();
        }

        //update analytics local files
        $new_script_type = $new_value['analytics']['script_type'] ?? '';
        $old_script_type = $old_value['analytics']['script_type'] ?? '';

        $update_flag = false;

        if($new_script_type != $old_script_type && empty($new_script_type)) {
            $update_flag = true;
        }

        if(!empty($new_value['analytics']['tracking_id']) && $new_value['analytics']['tracking_id'] != ($old_value['analytics']['tracking_id'] ?? '') && empty($new_script_type)) {
            $update_flag = true;
        }

        if(empty($old_value['analytics']['use_monster_insights']) && !empty($new_value['analytics']['use_monster_insights'])) {
            $update_flag = true;
        }

        if($update_flag) {
            Analytics::update_ga();
        }

        return $new_value;
    }

    //global scripts
    public static function insert_header_code(): void {
        add_action('wp_head', function() {
            if(!empty(Config::$options['assets']['header_code'])) {
                echo Config::$options['assets']['header_code'];
            }
        });
    }

    public static function insert_body_code(): void {
        if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=')) {
            add_action('wp_body_open', function() {
                if(!empty(Config::$options['assets']['body_code'])) {
                    echo Config::$options['assets']['body_code'];
                }
            });
        }
    }

    public static function insert_footer_code(): void {
        add_action('wp_footer', function() {
            if(!empty(Config::$options['assets']['footer_code'])) {
                echo Config::$options['assets']['footer_code'];
            }
        });
    }
}