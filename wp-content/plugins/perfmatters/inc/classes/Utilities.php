<?php
namespace Perfmatters;

class Utilities
{
    //get given post meta option for current post
    public static function get_post_meta(string $option) {

        global $post;

        if(!is_object($post)) {
            return false;
        }

        if(is_home()) {
            $post_id = get_queried_object_id();
        }

        if(is_singular() && isset($post)) {
            $post_id = $post->ID;
        }

        return (isset($post_id)) ? get_post_meta($post_id, $option, true) : false;
    }

    //remove unecessary bits from html for search
    public static function clean_html(string $html): string {

        //remove existing script and noscript tags (unrolled possessive pattern to avoid backtracking limits)
        $cleaned = preg_replace(
            '#<script\b[^>]*>(?:[^<]++|<(?!\/script\b))*+</script>|<noscript>(?:[^<]++|<(?!\/noscript\b))*+</noscript>#si',
            '',
            $html
        );

        return $cleaned !== null ? $cleaned : $html;
    }

    //get array of element attributes from attribute string
    public static function get_atts_array(string $atts_string): array {
    
        $atts_array = [];

        if(!empty($atts_string)) {

            //identify key="value", key='value', key=value, or key patterns
            //uses (?|...) branch reset group so value is always captured in group 2 regardless of quote style
            preg_match_all('/([a-zA-Z0-9-_:.]+)(?:\s*=\s*(?|(?:"([^"]*)")|(?:\'([^\']*)\')|(\S+)))?/s', $atts_string, $matches, PREG_SET_ORDER);

            foreach($matches as $match) {
                if(!empty($match[1])) {
                    $atts_array[$match[1]] = isset($match[2]) ? $match[2] : '';
                }
            }
        }

        return $atts_array;
    }

    //get attribute string from array of element attributes
    public static function get_atts_string(array $atts_array) {

        if(!empty($atts_array)) {
            $assigned_atts_array = array_map(
            function($name, $value) {
                    if($value === '') {
                        return $name;
                    }
                    return sprintf('%s="%s"', $name, esc_attr($value));
                },
                array_keys($atts_array),
                $atts_array
            );
            $atts_string = implode(' ', $assigned_atts_array);

            return $atts_string;
        }

        return false;
    }

    //check for string match inside array
    public static function match_in_array(string $string, $array): bool {
        
        if(!empty($array)) {
            foreach((array) $array as $item) {
                if(stripos($string, $item) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    //check for specific woocommerce pages
    public static function is_woocommerce(): bool {
        return apply_filters('perfmatters_is_woocommerce', class_exists('WooCommerce') && (is_cart() || is_checkout() || is_account_page()));
    }

    //return root directory path
    public static function get_root_dir_path(): string {
        $wp_content_relative_path = str_replace(array(trailingslashit(home_url()), trailingslashit(site_url())), '', content_url(), $count);

        //try pathless home url if nothing matched so far
        if(empty($count)) {
            $parsed_url = trailingslashit(($path = wp_parse_url(home_url(), PHP_URL_PATH)) ? str_replace($path, '', home_url()) : home_url());
            $wp_content_relative_path = str_replace($parsed_url, '', content_url());
        }

        $pos = strrpos(WP_CONTENT_DIR, $wp_content_relative_path);
        if($pos !== false) {
            $root_dir_path = substr_replace(WP_CONTENT_DIR, '', $pos, strlen($wp_content_relative_path));
        }
        else {
            $root_dir_path = WP_CONTENT_DIR;
        }
        return trailingslashit($root_dir_path);
    }

    //get local file path from url
    public static function get_file_path(string $url) {
        
        //get image path
        $parsed_url = @parse_url($url);
        if(empty($parsed_url['path'])) {
            return false;
        }

        //parse base path to strip
        $base_path = parse_url(home_url('/'), PHP_URL_PATH);
    
        //make sure base_path is not empty and ends with a slash
        $base_path = ($base_path && $base_path !== '/') ? rtrim($base_path, '/') . '/' : '/';

        $file_path = Utilities::get_root_dir_path() . ltrim($parsed_url['path'], $base_path);

        return $file_path;
    }

    //gets path to uploads directory, can pass in directory or file to add if needed
    public static function get_uploads_dir(string $subdir = ''): string {

        $upload_dir = wp_get_upload_dir(); 
        $dir = $upload_dir['basedir'];

        if(!empty($subdir)) {
            $dir = trailingslashit($dir) . ltrim($subdir, '/');
        }

        return $dir;
    }

    //gets url to uploads directory, can pass in directory or file to add if needed
    public static function get_uploads_url(string $subdir = ''): string {

        $upload_dir = wp_get_upload_dir(); 
        $url = $upload_dir['baseurl'];

        if(!empty($subdir)) {
            $url = trailingslashit($url) . ltrim($subdir, '/');
        }

        return $url;
    }

    //check for page builder query args
    public static function is_page_builder(): bool {
        static $is_page_builder;

        if(isset($is_page_builder)) {
            return $is_page_builder;
        }

        $page_builders = apply_filters('perfmatters_page_builders', [
            'customizer',
            'elementor-preview', //elementor
            'fl_builder', //beaver builder
            'et_fb', //divi
            'et_pb_preview',
            'ct_builder', //oxygen
            'tve', //thrive
            'tge',
            'app', //flatsome
            'uxb_iframe',
            'fb-edit', //fusion builder
            'builder',
            'bricks', //bricks
            'vc_editable', //wp bakery
            'op3editor', //optimizepress
            'cs_preview_state', //cornerstone
            'breakdance', //breakdance
            'breakdance_iframe',
            'givewp-route', //givewp
            'gb-template-viewer', //generateblocks
            'trp-edit-translation', //translatepress
            'td_action', //tagdiv
            'gform_ajax', //gravity forms
            'etch' //etch
        ]);

        if(!empty($page_builders)) {
            foreach($page_builders as $page_builder) {
                if(isset($_REQUEST[$page_builder])) {
                    $is_page_builder = true;
                    return true;
                }
            }
        }

        $is_page_builder = false;
        return false;
    }

    //check if the current request is dynamic
    public static function is_dynamic_request(): bool {
        if((defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request() && !self::prefer_html_request()) || wp_doing_ajax() || wp_doing_cron()) {
            return true;
        }

        return false;
    }

    //check if html/xhtml is the preferred request
    public static function prefer_html_request(): bool {

        static $prefer_html;

        if(isset($prefer_html)) {
            return $prefer_html;
        }

        //check accept header
        if(empty($_SERVER['HTTP_ACCEPT'])) {
            $prefer_html = false;
            return false;
        }

        //get content types set in header
        $content_types = explode(',', $_SERVER['HTTP_ACCEPT']);
        $html_preference = 0;
        $xhtml_preference = 0;
        $highest_preference = 0;

        //loop through accepted types
        foreach($content_types as $type) {

            //split parts
            $type_parts = explode(';', trim($type));
            $mime_type = $type_parts[0];

            //default quality factor of 1 if not set
            $q = 1.0;
            if(isset($type_parts[1]) && strpos($type_parts[1], 'q=') === 0) {
                $q = floatval(substr($type_parts[1], 2));
            }

            //update highest preference
            if($q > $highest_preference) {
                $highest_preference = $q;
            }

            //check mime type
            if($mime_type === 'text/html') {
                $html_preference = $q;
            }
            elseif($mime_type === 'application/xhtml+xml') {
                $xhtml_preference = $q;
            }
        }

        //return true if text/html or application/xhtml+xml has the highest preference
        $prefer_html = ($html_preference === $highest_preference || $xhtml_preference === $highest_preference);
        return $prefer_html;
    }

    //trailing slash
    public static function trailingslashit(string $string): string {

        static $use_trailing_slash;

        if(!isset($use_trailing_slash)) {
             $use_trailing_slash = (substr(get_option('permalink_structure'), -1, 1) === '/');
        }

        //check for permalink trailing slash and add to string
        if($use_trailing_slash) {
            return trailingslashit($string);
        }
        else {
            return untrailingslashit($string);
        }
    }
}