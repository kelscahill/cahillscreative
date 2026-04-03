<?php
namespace Perfmatters;

class Preload
{
    private static $fetch_priority;
    private static $preloads;
    private static $critical_images;
    private static $preloads_array = array();
    private static $preloads_ready = array();
    private static $used_srcs = array();
    public static $snippet_optimizations = [];

    //initialize preload functions
    public static function init() 
    {
        add_action('perfmatters_queue', array('Perfmatters\Preload', 'queue'));

        //compatibility
        if(!empty(Config::$options['preload']['preload']) || !empty(Config::$options['preload']['critical_images'])) {
            add_filter('rocket_above_the_fold_optimization', '__return_false', 999);
        }

        //instant page
        if(!empty(Config::$options['preload']['instant_page']) && version_compare(get_bloginfo('version'), '6.8' , '<')) {
            self::instant_page();
        }

        //preconnect
        if(!empty(Config::$options['preload']['preconnect'])) {
            self::preconnect();
        }

        //dns prefetch
        if(!empty(Config::$options['preload']['dns_prefetch'])) {
            self::dns_prefetch();
        }
    }

    //queue functions
    public static function queue() 
    {
        self::$fetch_priority = apply_filters('perfmatters_fetch_priority', Config::$options['preload']['fetch_priority'] ?? array());
        self::$preloads = apply_filters('perfmatters_preloads', Config::$options['preload']['preload'] ?? array());

        if(!empty(self::$snippet_optimizations)) {
            self::$preloads = array_merge(self::$preloads, self::$snippet_optimizations);
        }

        self::$critical_images = apply_filters('perfmatters_preload_critical_images', Config::$options['preload']['critical_images'] ?? 0);

        //disable core fetch
        if(!empty(Config::$options['preload']['disable_core_fetch'])) {
            add_filter('wp_get_loading_optimization_attributes', array('Perfmatters\Preload', 'disable_core_fetch'));
        }
        
        //fetch priority
        if(!empty(self::$fetch_priority)) {
            add_action('perfmatters_output_buffer', array('Perfmatters\Preload', 'add_fetch_priority'));
        }

        //preloads
        if(!empty(self::$preloads) || !empty(self::$critical_images)) {
            add_action('perfmatters_output_buffer', array('Perfmatters\Preload', 'add_preloads'));
        }

        //speculative loading
        self::speculative_loading();
    }

    //add fetch priority
    public static function add_fetch_priority($html) {

        $selectors = array();
        $parent_selectors = array();

        foreach(self::$fetch_priority as $line) {

            //device type check
            if(!empty($line['device'])) {
                $device_type = wp_is_mobile() ? 'mobile' : 'desktop';
                if($line['device'] != $device_type) {
                    continue;
                }
            }

            //location check
            if(!empty($line['locations'])) {
                if(!self::location_check($line['locations'])) {
                    continue;
                }
            }

            //add selector
            if(!empty($line['parent'])) {
                $parent_selectors[] = $line['selector'];
            }
            else {
                $selectors[] = $line['selector'];
            }
        }

        //parent selectors
        if(!empty($parent_selectors)) {

            $elements = HTML::get_selector_elements($html, $parent_selectors);

            if(!empty($elements)) {
                foreach($elements as $element) {

                    preg_match_all('#<(?>link|img|script)(\s[^>]*?)>#is', $element['html'], $tags, PREG_SET_ORDER);

                    if(!empty($tags)) {

                        $key = array_search($element['selector'], array_column(self::$fetch_priority, 'selector'));

                        foreach($tags as $tag) {

                            $atts = Utilities::get_atts_array($tag[1]);
                            $atts['fetchpriority'] = self::$fetch_priority[$key]['priority'];

                            //replace video attributes string
                            $new_tag = str_replace($tag[1], ' ' . Utilities::get_atts_string($atts), $tag[0]);

                            //replace video with placeholder
                            $html = str_replace($tag[0], $new_tag, $html);
                        }
                    }
                }
            }
        }

        //selectors
        if(!empty($selectors)) {

            preg_match_all('#<(?>link|img|script)(\s[^>]*?(' . implode('|', $selectors) . ').*?)>#is', $html, $matches, PREG_SET_ORDER);

            if(!empty($matches)) {
                foreach($matches as $tag) {

                    $atts = Utilities::get_atts_array($tag[1]);
                    $key = array_search($tag[2], array_column(self::$fetch_priority, 'selector'));
                    $atts['fetchpriority'] = self::$fetch_priority[$key]['priority'];;

                    //replace video attributes string
                    $new_tag = str_replace($tag[1], ' ' . Utilities::get_atts_string($atts), $tag[0]);

                    //replace video with placeholder
                    $html = str_replace($tag[0], $new_tag, $html);
                }
            }
        }

        return $html;
    }

    //add preloads to html
    public static function add_preloads($html) {

        if(!empty(self::$critical_images)) {

            //check viewport position
            $html = Buffer::check_viewport_position($html);

            self::add_critical_image_preloads($html, Utilities::clean_html($html));
        }

        if(!empty(self::$preloads)) {

            $mime_types = array(
                'svg'   => 'image/svg+xml',
                'ttf'   => 'font/ttf',
                'otf'   => 'font/otf',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'eot'   => 'application/vnd.ms-fontobject',
                'sfnt'  => 'font/sfnt'
            );

            foreach(self::$preloads as $line) {

                //type check
                if(empty($line['as'])) {
                    continue;
                }

                //device type check
                if(!empty($line['device'])) {
                    $device_type = wp_is_mobile() ? 'mobile' : 'desktop';
                    if($line['device'] != $device_type) {
                        continue;
                    }
                }

                //location check
                if(!empty($line['locations'])) {
                    if(!self::location_check($line['locations'])) {
                        continue;
                    }
                }

                $mime_type = '';
                $crossorigin = false;

                if($line['as'] == 'font') {
                    $path_info = pathinfo($line['url']);
                    $mime_type = !empty($path_info['extension']) && isset($mime_types[$path_info['extension']]) ? $mime_types[$path_info['extension']] : "";
                    $crossorigin = true;
                }
                //print script/style handle as preload
                elseif(in_array($line['as'], array('script', 'style'))) {
                    if(strpos($line['url'], '.') === false) {

                        global $wp_scripts;
                        global $wp_styles;

                        $scripts_arr = $line['as'] == 'script' ? $wp_scripts : $wp_styles;

                        if(!empty($scripts_arr)) {
                            $scripts_arr = $scripts_arr->registered;

                            if(array_key_exists($line['url'], $scripts_arr)) {

                                $url = $scripts_arr[$line['url']]->src;

                                $parsed_url = parse_url($scripts_arr[$line['url']]->src);
                                if(empty($parsed_url['host'])) {
                                    $url = site_url($url);
                                }

                                $ver = '';

                                if(strpos($url, '?') === false) {

                                    $ver = $scripts_arr[$line['url']]->ver;

                                    if(empty($ver)) {
                                        $ver = get_bloginfo('version');
                                    }
                                }

                                $line['url'] = $url . (!empty($ver) ? '?ver=' . $ver : '');
                            }
                            else {
                                continue;
                            }
                        }
                        else {
                            continue;
                        }
                    }
                }
                elseif($line['as'] == 'fetch') {
                    $crossorigin = true;
                }

                $preload = '<link rel="preload" href="' . trim($line['url']) . '" as="' . $line['as'] . '"' . (!empty($mime_type) ? ' type="' . $mime_type . '"' : '') . ($crossorigin ? ' crossorigin' : '') . ($line['as'] == 'style' ? ' onload="this.rel=\'stylesheet\';this.removeAttribute(\'onload\');"' : '') . (!empty($line['priority']) ? ' fetchpriority="' . $line['priority'] . '"' : '') . '>';

                if($line['as'] == 'image') {
                    array_unshift(self::$preloads_ready, $preload);
                }
                else {
                    self::$preloads_ready[] = $preload;
                }

                //add to preloads array
                self::$preloads_array[] = $line;
            }
        }

        if(!empty(self::$preloads_ready)) {

            //filter preloads array
            apply_filters('perfmatters_preloads_array', self::$preloads_array);

            //add early hints
            if(!empty(Config::$options['preload']['early_hints']) && !empty(Config::$options['preload']['early_hint_types'])) {

                foreach(self::$preloads_array as $preload) {
                    
                    if(empty($preload['url'])) {
                        continue;
                    }

                    if(empty($preload['as']) || !in_array($preload['as'], Config::$options['preload']['early_hint_types'])) {
                        continue;
                    }

                    if(!empty($preload['srcset']) || !empty($preload['imagesrcset']) || !empty($preload['sizes']) || !empty($preload['imagesizes']) || !empty($preload['media'])) {
                        continue;
                    }

                    header("Link: <" . $preload['url'] . ">; rel=preload; as=" . $preload['as'] . (!empty($preload['priority']) ? "; fetchpriority=" . $preload['priority'] : '') . ($preload['as'] == 'font' ? '; crossorigin' : ''), false);
                }
            }

            $preloads_string = '';
            foreach(apply_filters('perfmatters_preloads_ready', self::$preloads_ready) as $preload) {
                $preloads_string.= $preload;
            }
            $pos = strpos($html, '</title>');
            if($pos !== false) {
                $html = substr_replace($html, '</title>' . $preloads_string, $pos, 8);
            }
        }
        
        return $html;
    }

    //add critical image preloads
    public static function add_critical_image_preloads(&$html, $clean_html) {

        //exclude images from preloading by parent selector
        $parent_exclusions = apply_filters('perfmatters_critical_image_parent_exclusions', array());
        if(!empty($parent_exclusions)) {

            //get elements with selector
            $elements = HTML::get_selector_elements($clean_html, $parent_exclusions);

            if(!empty($elements)) {
                foreach($elements as $element) {

                    //remove element from clean html
                    $clean_html = str_replace($element['html'], '', $clean_html);
                }
            }
        }

        //match all image formats
        preg_match_all('#(<picture.*?)?(<img([^>]+?)\/?>)(?><\/picture>)?#is', $clean_html, $matches, PREG_SET_ORDER);

        if(!empty($matches)) {

            $exclusions = apply_filters('perfmatters_critical_image_exclusions', array(
                ';base64',
                'w3.org',
                'data-perfmatters-skip-preload',
                'wpml-ls-flag'
            ));

            $count = 0;
            
            foreach($matches as $match) {

                if($count >= self::$critical_images) {
                    break;
                }

                if(strpos($match[0], 'secure.gravatar.com') !== false) {
                    continue;
                }

                if(!empty($exclusions) && is_array($exclusions)) {
                    foreach($exclusions as $exclusion) {
                        if(strpos($match[0], $exclusion) !== false) {
                            continue 2;
                        }
                    }
                }

                //picture tag
                if(!empty($match[1])) {
                    preg_match('#<source([^>]+?image\/(webp|avif)[^>]+?)\/?>#is', $match[0], $source);

                    if(!empty($source)) {
                        if(self::generate_critical_image_preload($source[1], $match[0])) {
                            $new_picture = str_replace('<picture', '<picture data-perfmatters-preload', $match[0]);
                            if(!empty($match[3])) {
                                $atts = Utilities::get_atts_array($match[3]);
                                $atts['fetchpriority'] = 'high';
                                $new_picture = str_replace($match[3], ' data-perfmatters-preload ' . Utilities::get_atts_string($atts), $new_picture);
                            }
                            $html = str_replace($match[0], $new_picture, $html);
                            $count++;
                        }
                        continue;
                    }
                }

                //img tag
                if(!empty($match[3])) {
                    if(self::generate_critical_image_preload($match[3])) {
                        $atts = Utilities::get_atts_array($match[3]);
                        $atts['fetchpriority'] = 'high';
                        $new_image = '<img data-perfmatters-preload ' .  Utilities::get_atts_string($atts) . '>';
                        $new_match = str_replace($match[2], $new_image, $match[0]);
                        $html = str_replace($match[0], $new_match, $html);
                        $count++;
                    }
                }
            }
        }

        if(!empty(self::$preloads_ready)) {
            ksort(self::$preloads_ready);
        }
    }

    //generate preload link from att string
    private static function generate_critical_image_preload($att_string, $picture_html = '') {
        if(!empty($att_string)) {
            $atts = Utilities::get_atts_array($att_string);
            $src = $atts['data-src'] ?? $atts['src'] ?? '';

            //if picture, see if we can grab a src from the fallback img tag
            if(empty($src) && !empty($picture_html)) {
                if(preg_match('#<img([^>]+?)\/?>#is', $picture_html, $match)) {
                    if(!empty($match[1])) {
                        $img_atts = Utilities::get_atts_array($match[1]);
                        $src = $img_atts['data-src'] ?? $img_atts['src'] ?? '';
                    }
                }
            }

            //dont add if src was already used
            if(in_array($src, self::$used_srcs)) {
                return false;
            }

            //generate preload
            self::$preloads_ready[] = '<link rel="preload" href="' . $src . '" as="image"' . (!empty($atts['srcset']) ? ' imagesrcset="' . $atts['srcset'] . '"' : '') . (!empty($atts['sizes']) ? ' imagesizes="' . $atts['sizes'] . '"' : '') . ' fetchpriority="high">';

            //update preloads array
            self::$preloads_array[] = array('url' => $src, 'as' => 'image', 'srcset' => $atts['srcset'] ?? '', 'sizes' => $atts['sizes'] ?? '', 'priority' => 'high');

            //mark src used and return
            if(!empty($src)) {
                self::$used_srcs[] = $src;
            }
            return true;
        }
    }

    private static function location_check($locations) {

        $location_match = false;

        $exploded_locations = explode(',', $locations);
        $trimmed_locations = array_map('trim', $exploded_locations);

        //single post exclusion
        if(is_singular()) {
            global $post;
            if(in_array($post->ID, $trimmed_locations)) {
                $location_match = true;
            }
        }
        //posts page exclusion
        elseif(is_home() && in_array('blog', $trimmed_locations)) {
            $location_match = true;
        }
        elseif(is_archive()) {
            //woocommerce shop check
            if(function_exists('is_shop') && is_shop()) {
                if(in_array(wc_get_page_id('shop'), $trimmed_locations)) {
                    $location_match = true;
                }
            }
        }

        return $location_match;
    }

    public static function disable_core_fetch($loading_attrs) {
        unset($loading_attrs['fetchpriority']);
        return $loading_attrs;
    }

    //handle speculative loading
    public static function speculative_loading()
    {   
        //wp 6.8+
        if(version_compare(get_bloginfo('version'), '6.8' , '>=')) {

            //get settings
            $mode = Config::$options['preload']['speculative_mode'] ?? '';
            $eagerness = Config::$options['preload']['speculative_eagerness'] ?? '';

            if(!empty($mode) || !empty($eagerness)) {
                
                //disable entirely
                if($mode == 'disabled') {
                    add_filter('wp_speculation_rules_configuration', '__return_null');
                    return;
                }

                //add woocommerce path exclusions
                if(class_exists('WooCommerce')) {
                    add_filter('wp_speculation_rules_href_exclude_paths', function (array $exclude_paths) : array {
                        $exclude_paths[] = '/checkout/';
                        $exclude_paths[] = '/cart/';
                        return $exclude_paths;
                    });
                }
                
                //adjust config
                add_filter('wp_speculation_rules_configuration', function($config) use($mode, $eagerness) {
                    if(is_array($config)) {
                        if(!empty($mode)) {
                            $config['mode'] = $mode;
                        }
                        if(!empty($eagerness)) {
                            $config['eagerness'] = $eagerness;
                        }
                    }
                    return $config;
                });
            }
        }
    }

    //instant page (fallback for older WP < 6.8)
    public static function instant_page() {
        if(!is_admin()) {
            
            add_action('wp_enqueue_scripts', function() {
                
                if(isset($_GET['perfmattersoff'])) {
                    return;
                }
        
                //exclude specific woocommerce pages
                if(Utilities::is_woocommerce()) {
                    return;
                }
        
                $exclude_instant_page = Utilities::get_post_meta('perfmatters_exclude_instant_page');
        
                if(!$exclude_instant_page) {
                    wp_register_script('perfmatters-instant-page', plugins_url('vendor/instant-page/pminstantpage.min.js', dirname(__DIR__)), [], PERFMATTERS_VERSION, true);
                    wp_enqueue_script('perfmatters-instant-page');
                }
            }, PHP_INT_MAX);
            
            add_filter('script_loader_tag', function($tag, $handle) {
                if($handle !== 'perfmatters-instant-page') {
                    return $tag;
                }
                return str_replace(' src', ' async data-no-optimize="1" src', $tag);
            }, 10, 2);
        }
    }

    //preconnect
    public static function preconnect() {
        add_action('wp_head', function() {
            $options = Config::$options;
            if(!empty($options['preload']['preconnect']) && is_array($options['preload']['preconnect'])) {
                foreach($options['preload']['preconnect'] as $line) {
                    if(is_array($line)) {
                        echo "<link rel='preconnect' href='" . $line['url'] . "' " . (isset($line['crossorigin']) && $line['crossorigin'] ? "crossorigin" : "") . ">" . "\n";
                    }
                    else {
                        echo "<link rel='preconnect' href='" . $line . "' crossorigin>" . "\n";
                    }
                }
            }
        }, 1);
    }

    //dns prefetch
    public static function dns_prefetch() {
        add_action('wp_head', function() {
            $options = Config::$options;
            if(!empty($options['preload']['dns_prefetch']) && is_array($options['preload']['dns_prefetch'])) {
                foreach($options['preload']['dns_prefetch'] as $url) {
                    echo "<link rel='dns-prefetch' href='" . $url . "'>" . "\n";
                }
            }
        }, 1);
    }
}