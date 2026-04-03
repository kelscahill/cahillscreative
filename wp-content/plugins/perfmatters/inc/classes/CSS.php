<?php
namespace Perfmatters;

use Perfmatters\Vendor\Sabberworm\CSS\CSSList\AtRuleBlockList;
use Perfmatters\Vendor\Sabberworm\CSS\CSSList\CSSBlockList;
use Perfmatters\Vendor\Sabberworm\CSS\CSSList\Document;
use Perfmatters\Vendor\Sabberworm\CSS\OutputFormat;
use Perfmatters\Vendor\Sabberworm\CSS\Parser as CSSParser;
use Perfmatters\Vendor\Sabberworm\CSS\Property\Charset;
use Perfmatters\Vendor\Sabberworm\CSS\RuleSet\DeclarationBlock;
use Perfmatters\Vendor\Sabberworm\CSS\Settings;
use Perfmatters\Vendor\Sabberworm\CSS\Value\URL;
use WP_Admin_Bar;

class CSS
{
    private static $run = [];
    private static $data = [];
    private static $url_type;
    private static $used_selectors;
    private static $excluded_selectors;
    public static $snippet_optimizations = [];

    //initialize css functions
    public static function init()
    {
        if(isset($_GET['perfmatterscssoff'])) {
            return;
        }

        add_action('perfmatters_queue', array('Perfmatters\CSS', 'queue'));

        if(!empty(Config::$options['assets']['remove_unused_css'])) {
            add_action('wp_ajax_perfmatters_clear_post_used_css', array('Perfmatters\CSS', 'clear_post_used_css_ajax'));
            add_action('admin_bar_menu', array('Perfmatters\CSS', 'admin_bar_menu'));
            add_action('admin_notices', array('Perfmatters\CSS', 'admin_notices'));
            add_action('admin_post_perfmatters_clear_used_css', array('Perfmatters\CSS', 'admin_bar_clear_used_css'));
        }

        if(!empty(Config::$options['assets']['minify_css'])) {
            Minify::queue_admin_bar();
        }

        //ajax actions
        add_action('wp_ajax_perfmatters_clear_used_css', array('Perfmatters\CSS', 'clear_used_css_ajax'));
        add_action('wp_ajax_perfmatters_clear_minified_css', array('Perfmatters\CSS', 'clear_minified_css_ajax'));
    }

    //queue functions
    public static function queue() 
    {
        //skip woocommerce
        if(Utilities::is_woocommerce()) {
            return;
        }

        //setup optimizations to run
        self::$run['rucss'] = !empty(apply_filters('perfmatters_remove_unused_css', !empty(Config::$options['assets']['remove_unused_css'])))
            && !Utilities::get_post_meta('perfmatters_exclude_unused_css')
            && (!is_user_logged_in() || apply_filters('perfmatters_rucss_logged_in', false))
            && self::$url_type = self::get_url_type();
        self::$run['minify'] = !empty(apply_filters('perfmatters_minify_css', !empty(Config::$options['assets']['minify_css']))) && !Utilities::get_post_meta('perfmatters_exclude_minify_css');

        //pmcs css optimizations
        self::$run['snippets'] = !empty(self::$snippet_optimizations);

        if(array_filter(self::$run)) {

            //add to buffer
            add_filter('perfmatters_output_buffer', array('Perfmatters\CSS', 'optimize'));
        }
    }

    //optimize css
    public static function optimize($html)
    {
        //strip comments before search
        $html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);
        
        //match all stylesheets
        preg_match_all('#<link(\s[^>]*?href=[\'"]([^\'"]+?\.css.*?)[\'"][^>]*?)\/?>#i', $html_no_comments, $stylesheets, PREG_SET_ORDER);

        //no stylesheets found
        if(empty($stylesheets)) {
            return $html;
        }  

        //global styles array in case we need to update registered src
        global $wp_styles;

        //pre loop
        //unused css
        if(self::$run['rucss']) {

            //create our css cache directory
            if(!is_dir(PERFMATTERS_CACHE_DIR . 'css/')) {
                @mkdir(PERFMATTERS_CACHE_DIR . 'css/', 0755, true);
            }

            //setup file variables
            $used_css_path = PERFMATTERS_CACHE_DIR . 'css/' . self::$url_type . '.used.css';
            $used_css_url = PERFMATTERS_CACHE_URL . 'css/' . self::$url_type . '.used.css';
            $used_css_exists = file_exists($used_css_path);

            //populate used selectors
            self::get_used_selectors($html);
            self::get_excluded_selectors();

            //stylesheet exclusions
            self::$data['rucss']['exclusions']['stylesheet'] = array(
                'dashicons.min.css', //core
                '/uploads/elementor/css/post-', //elementor
                'animations.min.css',
                '/animations/',
                '/uploads/oxygen/css/', //oxygen
                '/uploads/bb-plugin/cache/', //beaver builder
                '/uploads/generateblocks/', //generateblocks
                'offside.min.css', //generatepress
                '/uploads/generatepress/fonts/',
                '/et-cache/', //divi
                '/widget-google-reviews/assets/css/public-main.css', //plugin for google reviews
                'google-fonts', //google fonts
                '/astra-local-fonts/', //astra local fonts
                '//fonts.googleapis.com/css',
                '/wp-content/uploads/bricks/css/post-' //bricks
            );
            if(!empty(Config::$options['assets']['rucss_excluded_stylesheets'])) {
                self::$data['rucss']['exclusions']['stylesheet'] = array_merge(self::$data['rucss']['exclusions']['stylesheet'], Config::$options['assets']['rucss_excluded_stylesheets']);
            }
            self::$data['rucss']['exclusions']['stylesheet'] = apply_filters('perfmatters_rucss_excluded_stylesheets', self::$data['rucss']['exclusions']['stylesheet']);

            //async stylesheets
            self::$data['rucss']['async'] = apply_filters('perfmatters_rucss_async_stylesheets', array(
                'dashicons.min.css',
                'animations.min.css',
                '/animations/'
            ));

            //inline stylesheets
            self::$data['rucss']['inline'] = apply_filters('perfmatters_rucss_inline_stylesheets', array());

            $used_css_string = '';
        }

        //loop through stylesheets
        foreach($stylesheets as $key => $stylesheet) {

            //get attribute array
            $atts_array = Utilities::get_atts_array($stylesheet[1] ?? '');

            //stylesheet check
            if(empty($atts_array['rel']) || $atts_array['rel'] != 'stylesheet') {
                continue;
            }

            //copy atts array
            $atts_array_new = $atts_array;

            //snippet optimizations
            if(!empty(self::$snippet_optimizations) && !empty($atts_array['id'])) {
                if(isset(self::$snippet_optimizations[$atts_array['id']])) {

                    //async
                    if(self::$snippet_optimizations[$atts_array['id']] == 'async') {
                        $atts_array_new['media'] = 'print';
                        $atts_array_new['onload'] = 'this.media=\'all\';this.onload=null;';
                    }
                }
            }

            //minify
            if(self::$run['minify']) {
                if(!empty($atts_array['href']) && !Utilities::match_in_array($stylesheet[1], Minify::get_exclusions('css')) && $minified_src = Minify::minify($atts_array['href'])) {
                    $atts_array_new['href'] = $minified_src;

                    //update registered src
                    if(!empty($atts_array['id'])) {
                        $handle = rtrim($atts_array['id'], '-css');
                        if(isset($wp_styles->registered[$handle])) {
                            $wp_styles->registered[$handle]->src = $minified_src;
                        }
                    }
                }
            }

            //unused css
            if(self::$run['rucss']) {

                $skip = false;

                //print check
                if(!empty($atts_array_new['media']) && $atts_array_new['media'] == 'print') {
                    $skip = true;
                }

                //exclusion check
                if(!$skip && !Utilities::match_in_array($stylesheet[0], self::$data['rucss']['exclusions']['stylesheet'])) {
                
                    //need to generate used css
                    if(!$used_css_exists) {

                        //get local stylesheet file path
                        $file = self::get_local_stylesheet_path($atts_array['href']);

                        //make sure local file exists
                        if(file_exists($file)) {

                            //get used css from stylesheet
                            $used_css = self::clean_stylesheet($atts_array['href'], @file_get_contents($file));

                            //wrap in media query if needed
                            if(!empty($atts_array['media']) && $atts_array['media'] != 'all') {
                                $used_css = '@media ' . $atts_array['media'] . '{' . $used_css . '}';
                            }

                            //add used stylesheet css to total used
                            $used_css_string.= $used_css;
                        }
                        else {
                            $skip = true;
                        }
                        
                    }

                    if(!$skip) {
                
                        //delay stylesheets
                        if(empty(Config::$options['assets']['rucss_stylesheet_behavior'])) {
                            $atts_array_new['data-pmdelayedstyle'] = $atts_array_new['href'];
                            unset($atts_array_new['href']);
                        }
                        //async stylesheets
                        elseif(Config::$options['assets']['rucss_stylesheet_behavior'] == 'async') {
                            $atts_array_new['media'] = 'print';
                            $atts_array_new['onload'] = 'this.media=\'all\';this.onload=null;';
                        }
                        //remove stylesheets
                        elseif(Config::$options['assets']['rucss_stylesheet_behavior'] == 'remove') {
                            $html = str_replace($stylesheet[0], '', $html);
                            continue;
                        }
                    }
                }
                else {

                    //inline fallback
                    if(Utilities::match_in_array($stylesheet[0], self::$data['rucss']['inline'])) {

                        //get local stylesheet file path
                        $file = self::get_local_stylesheet_path($atts_array_new['href']);

                        //make sure local file exists
                        if(file_exists($file)) {

                            //get used css from stylesheet
                            $content = @file_get_contents($file);

                            if($content) {

                                //rewrite relative urls without using the parser
                                $css = self::rewrite_relative_urls_inline($content, $atts_array_new['href']);

                                //swap inline css in html
                                $inline_style = '<style' . (!empty($atts_array_new['id']) ? ' id="' . $atts_array_new['id'] . '"' : '') . '>' . $css . '</style>';
                                $html = str_replace($stylesheet[0], $inline_style, $html);

                                continue;
                            }
                        }
                    }
                    //async fallback
                    elseif(Utilities::match_in_array($stylesheet[0], self::$data['rucss']['async'])) {
                        $atts_array_new['media'] = 'print';
                        $atts_array_new['onload'] = 'this.media=\'all\';this.onload=null;';
                    }
                }
            }

            //replace stylesheet
            if($atts_array_new !== $atts_array) {
                $new_atts_string = Utilities::get_atts_string($atts_array_new);
                $new_link = sprintf('<link %1$s>', $new_atts_string);
                $html = str_replace($stylesheet[0], $new_link, $html);
            }
        }

        //post loop
        if(self::$run['rucss']) {

            //store used css file
            if(!empty($used_css_string)) {
                if(file_put_contents($used_css_path, apply_filters('perfmatters_used_css', $used_css_string)) !== false) {

                    $time = get_option('perfmatters_used_css_time', array());

                    $time[self::$url_type] = time();

                    //update stored timestamp
                    update_option('perfmatters_used_css_time', $time, false);
                }
            }

            //print used css
            if(file_exists($used_css_path)) {
                $tag = !apply_filters('perfmatters_used_css_below', false) ? '</title>' : '</head>';
                $pos = strpos($html, $tag);
                if($pos !== false) {

                    //print file
                    if(!empty(Config::$options['assets']['rucss_method']) && Config::$options['assets']['rucss_method'] == 'file') {
                        //grab stored timestamp for query string
                        $time = get_option('perfmatters_used_css_time', array());
                        if(!empty($time[self::$url_type])) {
                            $used_css_url = add_query_arg('ver', $time[self::$url_type], $used_css_url);
                        }
                        $used_css_output = '<link rel="preload" href="' . $used_css_url . '" as="style" />';
                        $used_css_output.= '<link rel="stylesheet" id="perfmatters-used-css" href="' . $used_css_url . '" media="all" />';

                        //add early hint if needed
                        if(!empty(Config::$options['preload']['early_hints']) && !empty(Config::$options['preload']['early_hint_types']) && in_array('style', Config::$options['preload']['early_hint_types'])) {
                            header("Link: <" . $used_css_url. ">; rel=preload; as=style", false);
                        }
                    }
                    //print inline
                    else {
                        $used_css_output = '<style id="perfmatters-used-css">' . file_get_contents($used_css_path) . '</style>';
                    }

                    if($tag == '</title>') {
                        $html = substr_replace($html, '</title>' . $used_css_output, $pos, 8);
                    }
                    else {
                        $html = str_replace('</head>', $used_css_output . '</head>', $html);
                    }
                }

                //delay stylesheet script
                if(empty(Config::$options['assets']['rucss_stylesheet_behavior'])) {

                    $delay_check = !empty(apply_filters('perfmatters_delay_js', !empty(Config::$options['assets']['delay_js']))) && !Utilities::get_post_meta('perfmatters_exclude_delay_js');

                    if(!$delay_check || isset($_GET['perfmattersjsoff'])) {
                        $script = '<script type="text/javascript" id="perfmatters-delayed-styles-js">!function(){const e=["keydown","mousemove","wheel","touchmove","touchstart","touchend"];function t(){document.querySelectorAll("link[data-pmdelayedstyle]").forEach(function(e){e.setAttribute("href",e.getAttribute("data-pmdelayedstyle"))}),e.forEach(function(e){window.removeEventListener(e,t,{passive:!0})})}e.forEach(function(e){window.addEventListener(e,t,{passive:!0})})}();</script>';
                        $html = str_replace('</body>', $script . '</body>', $html);
                    }
                }
            }
        }

        return $html;
    }

    //get url type
    private static function get_url_type($post_id = '')
    {
        $type = '';

        if(!empty($post_id)) {
            $post_id_type = get_post_type($post_id);
            if($post_id_type == 'page') {
                $type = get_option('page_on_front') == $post_id ? 'front' : (get_option('page_for_posts') == $post_id ? 'home' : 'page-' . $post_id);
            }
            else {
                $type = $post_id_type !== false ? $post_id_type : 'single';
            }
        }
        else {

            global $wp_query;

            if($wp_query->is_page) {
                $type = is_front_page() ? 'front' : (!empty($wp_query->post) ? 'page-' . $wp_query->post->ID : 'page');
            }
            elseif($wp_query->is_home) {
                $type = 'home';
            }
            elseif($wp_query->is_single) {

                //single post type
                $post_type = get_post_type();
                $type = $post_type ?: 'single';

                //add wocommerce product type
                if($post_type === 'product' && function_exists('wc_get_product')) {
                    $product = wc_get_product(get_the_ID());
                    if($product) {
                        $product_type = $product->get_type();
                        if(in_array($product_type, ['variable', 'grouped', 'external'])) {
                            $type.= '-' . $product_type;
                        }
                    } 
                }
            }
            elseif($wp_query->is_category) {
                $type = 'category';
            }
            elseif($wp_query->is_tag) {
                $type = 'tag';
            } 
            elseif($wp_query->is_tax) {
                $type = 'tax';
            }
            elseif($wp_query->is_archive) {
                $type = $wp_query->is_post_type_archive() ? 'archive-' . get_post_type() : ($wp_query->is_day ? 'day' : ($wp_query->is_month ? 'month' : ($wp_query->is_year ? 'year' : ($wp_query->is_author ? 'author' : 'archive'))));
            } 
            elseif($wp_query->is_search) {
                $type = 'search';
            }
            elseif($wp_query->is_404) {
                $type = '404';
            }
        }

        //logged in version
        if(apply_filters('perfmatters_rucss_logged_in', false) && is_user_logged_in() && !empty($type)) {
            $type.= '-loggedin';
        }

        return $type;
    }

    //get used selectors in html
    private static function get_used_selectors($html) {

        if(!$html) {
            return;
        }

        //get dom document
        $libxml_previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $result = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous);

        if($result) {

            //setup used selectors array
            self::$used_selectors = array('tags' => array(), 'ids' => array(), 'classes' => array());

            //search for used selectors in dom
            $classes = array();
            foreach($dom->getElementsByTagName('*') as $tag) {

                //add tag
                self::$used_selectors['tags'][$tag->tagName] = 1;

                //add add tag id
                if($tag->hasAttribute('id')) {
                    self::$used_selectors['ids'][$tag->getAttribute('id')] = 1;
                }

                //store tag classes
                if($tag->hasAttribute('class')) {
                    $class = $tag->getAttribute('class');
                    $tag_classes = preg_split('/\s+/', $class);
                    array_push($classes, ...$tag_classes);
                }
            }

            //add classes
            $classes = array_filter(array_unique($classes));
            if($classes) {
                self::$used_selectors['classes'] = array_fill_keys($classes, 1);
            }
        }
    }

    //get excluded selectors
    private static function get_excluded_selectors() {
        
        //dynamic selectors added by js
        self::$excluded_selectors = array(
            '.ast-header-break-point', //astra
            '.elementor-popup-modal', //elementor
            '.elementor-has-item-ratio',
            '#elementor-device-mode',
            '.elementor-sticky--active',
            '.dialog-type-lightbox',
            '.dialog-widget-content',
            '.lazyloaded',
            '.elementor-nav-menu',
            '.elementor-motion-effects-container',
            '.elementor-motion-effects-layer',
            '.animated',
            '.elementor-animated-content',
            '.elementor-background-slideshow',
            '.elementor-background-slideshow__slide__image',
            '.gb-menu-container--mobile', //generateblocks
            '.toggled', //generatepress
            '.splide-initialized', //splide
            '.splide',
            '.splide-slider',
            '.kb-splide', //kadence
            '.active',
            '.dropdown-nav-special-toggle',
            'rs-fw-forcer', //rev slider
            '#altEmail_container' //wp armour
        );

        //shared type selectors
        if(!self::$url_type == 'front' && !self::$url_type == 'home' && strpos(self::$url_type, 'page-') === false) {
            self::$excluded_selectors = array_merge(self::$excluded_selectors, array(
                '.wp-embed-responsive', //core
                '.wp-block-embed',
                '.wp-block-embed__wrapper',
                '.wp-caption'
            ));
        }

        //product selectors (all single product types)
        if(strpos(self::$url_type, 'product') === 0) {
            self::$excluded_selectors = array_merge(self::$excluded_selectors, array(
                'div.product' //woocommerce
            ));
        }

        if(!empty(Config::$options['assets']['rucss_excluded_selectors'])) {
            self::$excluded_selectors = array_merge(self::$excluded_selectors, Config::$options['assets']['rucss_excluded_selectors']);
        }
        self::$excluded_selectors = apply_filters('perfmatters_rucss_excluded_selectors', self::$excluded_selectors);
    }

    //get local stylesheet file path from a stylesheet url
    public static function get_local_stylesheet_path($stylesheet_url) {

        if(!empty($stylesheet_url)) {

            //get any custom set url
            $custom_url = apply_filters('perfmatters_local_stylesheet_url', !empty(Config::$options['assets']['rucss_cdn_url']) ? Config::$options['assets']['rucss_cdn_url'] : '');

            //prep local url
            $local_url = !empty($custom_url) ? trailingslashit($custom_url) : array(trailingslashit(home_url()), trailingslashit(site_url()));

            //get local stylesheet path
            $url = str_ireplace($local_url, '', explode('?', $stylesheet_url)[0]);

            $file = Utilities::get_root_dir_path() . ltrim($url, '/');

            return $file;
        }
        
        return false;
    }

    //remove unusde css from stylesheet
    private static function clean_stylesheet($url, $css) 
    {
        //https://github.com/sabberworm/PHP-CSS-Parser/issues/150
        $css = preg_replace('/^\xEF\xBB\xBF/', '', $css);

        // Normalize @layer name1, name2; (no block) to @layer name1, name2 {} so the parser doesn't consume the rest of the file
        $css = preg_replace('/@layer\s+([^;{}]+);/', '@layer $1 {}', $css);

        //setup css parser
        $settings = Settings::create()->withMultibyteSupport(false);
        $parser = new CSSParser($css, $settings);
        $parsed_css = $parser->parse();

        //convert relative urls to full urls
        self::fix_relative_urls($url, $parsed_css);

        $css_data = self::prep_css_data($parsed_css);

        return self::remove_unused_selectors($css_data);
    }

    //convert relative urls to full urls
    private static function fix_relative_urls($stylesheet_url, Document $data)
    {
        //get base url from stylesheet
        $base_url = preg_replace('#[^/]+(\?.*)?$#', '', $stylesheet_url);

        //search css for urls
        $values = $data->getAllValues();
        foreach($values as $value) {

            if(!($value instanceof URL)) {
                continue;
            }

            $url = $value->getURL()->getString();

            //not relative
            if(preg_match('/^(https?|data):/', $url)) {
                continue;
            }

            $parsed_url = parse_url($url);

            //final checks
            if(!empty($parsed_url['host']) || empty($parsed_url['path']) || $parsed_url['path'][0] === '/') {
                continue;
            }

            //create full url and replace
            $new_url = $base_url . $url;
            $value->getUrl()->setString($new_url);
        }
    }

    //convert relative urls to full urls for inline css without using the parser
    private static function rewrite_relative_urls_inline($css, $stylesheet_url)
    {
        //get base url from stylesheet (same logic as fix_relative_urls)
        $base_url = preg_replace('#[^/]+(\?.*)?$#', '', $stylesheet_url);

        if(empty($base_url)) {
            return $css;
        }

        return preg_replace_callback(
            '/url\(\s*([\'"]?)([^)\'"]+)\1\s*\)/i',
            function($matches) use ($base_url) {

                $quote = $matches[1];
                $url   = trim($matches[2]);

                //skip absolute and data urls
                if(preg_match('/^(https?:|data:|\/\/)/i', $url)) {
                    return $matches[0];
                }

                //skip root-relative paths
                if(isset($url[0]) && $url[0] === '/') {
                    return $matches[0];
                }

                //basic relative url handling (no host, non-empty path)
                $parsed_url = parse_url($url);
                if(!empty($parsed_url['host']) || empty($parsed_url['path'])) {
                    return $matches[0];
                }

                $new_url = $base_url . $url;

                return 'url(' . $quote . $new_url . $quote . ')';
            },
            $css
        );
    }

    //prep parsed css for cleaning
    private static function prep_css_data(CSSBlockList $data)
    {
        $items = array();

        foreach($data->getContents() as $content) {

            //remove charset objects since were printing inline
            if($content instanceof Charset) {
                continue;
            }

            if($content instanceof AtRuleBlockList) {
                $items[] = array(
                    'rulesets' => self::prep_css_data($content),
                    'at_rule' => "@{$content->atRuleName()} {$content->atRuleArgs()}",
                );
            }
            else {
                $item = array('css' => $content->render(OutputFormat::createCompact()));

                if($content instanceof DeclarationBlock) {
                    $item['selectors'] = self::sort_selectors($content->getSelectors());
                }

                $items[] = $item;
            }
        }

        return $items;
    }

    //sort selectors into different categories we need
    private static function sort_selectors($selectors)
    {
        $selectors = array_map(
            function($sel) {
                return $sel->getSelector();
            },
            $selectors
        );

        $selectors_data = array();
        foreach($selectors as $selector) {

            //setup selector data array
            $data = array(
                'selector' => trim($selector),
                'classes' => array(),
                'ids' => array(),
                'tags' => array(),
                'atts' => array()
            );

            //eliminate false negatives (:not(), pseudo, etc...)
            $selector = preg_replace('/(?<!\\\\)::?[a-zA-Z0-9_-]+(\(.+?\))?/', '', $selector);

            //atts
            $selector = preg_replace_callback(
                '/\[([A-Za-z0-9_:-]+)(\W?=[^\]]+)?\]/', 
                function($matches) use (&$data) {
                    $data['atts'][] = $matches[1];
                    return '';
                },
                $selector
            );

            //classes
            $selector = preg_replace_callback(
                '/\.((?:[a-zA-Z0-9_-]+|\\\\.)+)/',
                function($matches) use (&$data) {
                    $data['classes'][] = stripslashes($matches[1]);
                    return '';
                },
                $selector
            );

            //ids
            $selector = preg_replace_callback(
                '/#([a-zA-Z0-9_-]+)/',
                function($matches) use (&$data) {
                    $data['ids'][] = $matches[1];
                    return '';
                },
                $selector
            );

            //tags
            $selector = preg_replace_callback(
                '/[a-zA-Z0-9_-]+/',
                function($matches) use (&$data) {
                    $data['tags'][] = $matches[0];
                    return '';
                },
                $selector
            );

            //add selector data to main array
            $selectors_data[] = array_filter($data);
        }

        return array_filter($selectors_data);
    }

    //remove unused selectors from css data
    private static function remove_unused_selectors($data)
    {
        $rendered = [];

        foreach($data as $item) {

            //has css
            if(isset($item['css'])) {

                //need at least one selector match
                $should_render = !isset($item['selectors']) || 0 !== count(array_filter($item['selectors'],
                    function($selector) {
                        return self::is_selector_used($selector);
                    }
                ));

                if($should_render) {
                    $rendered[] = $item['css'];
                }

                continue;
            }

            //nested rulesets
            if(!empty($item['rulesets'])) {
                $child_rulesets = self::remove_unused_selectors($item['rulesets']);

                if($child_rulesets) {
                    $rendered[] = sprintf('%s{%s}', $item['at_rule'], $child_rulesets);
                }
                elseif(preg_match('/^@layer\s/i', trim($item['at_rule']))) {
                    // @layer with empty block was normalized from declaration form (no block); output as @layer ... ;
                    $rendered[] = trim($item['at_rule']) . ';';
                }
            }
        }

        return implode("", $rendered);
    }
  
    //check if selector is used
    private static function is_selector_used($selector)
    {
        //:root selector
        if($selector['selector'] === ':root') {
            return true;
        }

        //lone attribute selector
        if(!empty($selector['atts']) && (empty($selector['classes']) && empty($selector['ids']) && empty($selector['tags']))) {
            return true;
        }

        //search for excluded selector match
        if(!empty(self::$excluded_selectors)) {
            foreach(self::$excluded_selectors as $key => $value) {
                if(preg_match('#(' . preg_quote($value) . ')(?=\s|\.|\:|,|\[|$)#', $selector['selector'])) {
                    return true;
                }
            }
        }

        //is selector used in the dom
        foreach(array('classes', 'ids', 'tags') as $type) {
            if(!empty($selector[$type])) {

                //cast array if needed
                $targets = (array)$selector[$type];
                foreach($targets as $target) {

                    //bail if a target doesn't exist
                    if(!isset(self::$used_selectors[$type][$target])) { 
                        return false;
                    }
                }
            }
        }

        return true;
    }

    //delete all files in the css cache directory
    public static function clear_used_css($site = null)
    {      
        $path = '';

        //add site path if specified
        if(is_object($site) && !empty($site->path)) {
           $path = ltrim($site->path, '/');
        }

        $files = glob(PERFMATTERS_CACHE_DIR . $path . 'css/*');
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
        delete_option('perfmatters_used_css_time');
    }

    //clear post used css ajax action
    public static function clear_post_used_css_ajax() {
        if(empty($_POST['action']) || empty($_POST['nonce']) || empty($_POST['post_id'])) {
            return;
        }

        if($_POST['action'] != 'perfmatters_clear_post_used_css') {
            return;
        }

        if(!wp_verify_nonce($_POST['nonce'], 'perfmatters_clear_post_used_css')) {
            return;
        }

        $post_id = (int)$_POST['post_id'];

        self::clear_post_used_css($post_id);

        wp_send_json_success();
        exit;
    }

    //clear used css file for specific post or post type
    public static function clear_post_used_css($post_id) {

        $type = self::get_url_type($post_id);

        if(!empty($type)) {
            $file = PERFMATTERS_CACHE_DIR . 'css/' . $type . '.used.css';
            if(is_file($file)) {
                unlink($file);
            }
        }
    }

    //add admin bar menu items
    public static function admin_bar_menu(WP_Admin_Bar $wp_admin_bar) {

        if(!current_user_can('manage_options') || !perfmatters_network_access()) {
            return;
        }

        //clear all used css
        $wp_admin_bar->add_menu(array(
            'parent' => 'perfmatters',
            'id'     => 'perfmatters-clear-used-css',
            'title'  => __('Clear Used CSS', 'perfmatters') . ' (' . __('All', 'perfmatters') . ')',
            'href'   => add_query_arg(array(
                'action'           => 'perfmatters_clear_used_css',
                '_wp_http_referer' => rawurlencode($_SERVER['REQUEST_URI']),
                '_wpnonce'         => wp_create_nonce('perfmatters_clear_used_css')
            ), 
            admin_url('admin-post.php'))
        ));

        //clear current used css
        if(!is_admin() && !empty($type = self::get_url_type())) {
            $wp_admin_bar->add_menu(array(
                'parent' => 'perfmatters',
                'id'     => 'perfmatters-clear-used-css-current',
                'title'  => __('Clear Used CSS', 'perfmatters') . ' (' . __('Current', 'perfmatters') . ')',
                'href'   => add_query_arg(array(
                    'action'           => 'perfmatters_clear_used_css',
                    '_wp_http_referer' => rawurlencode($_SERVER['REQUEST_URI']),
                    '_wpnonce'         => wp_create_nonce('perfmatters_clear_used_css'),
                    'type'             => $type
                ), 
                admin_url('admin-post.php'))
            ));
        }
    }

    //display admin notices
    public static function admin_notices() {

        if(get_transient('perfmatters_used_css_cleared') === false) {
            return;
        }

        delete_transient('perfmatters_used_css_cleared');
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Used CSS cleared.', 'perfmatters' ) . '</strong></p></div>';
    }

    //clear used css from admin bar
    public static function admin_bar_clear_used_css() {

        if(!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'perfmatters_clear_used_css')) {
            wp_nonce_ays('');
        }

        if(!empty($_GET['type'])) {

            //clear specific type
            $file = PERFMATTERS_CACHE_DIR . 'css/' . $_GET['type'] . '.used.css';
            if(is_file($file)) {
                unlink($file);
            }
        }
        else {

            //clear all
            self::clear_used_css();
            if(is_admin()) {
                set_transient('perfmatters_used_css_cleared', 1);
            }
        }

        //go back to url where button was pressed
        wp_safe_redirect(esc_url_raw(wp_get_referer()));
        exit;
    }

    //clear used css ajax action
    public static function clear_used_css_ajax() {

        Ajax::security_check();

        self::clear_used_css();

        wp_send_json_success(array(
            'message' => __('Used CSS cleared.', 'perfmatters'), 
        ));
    }

    //clear minified css ajax action
    public static function clear_minified_css_ajax() {

        Ajax::security_check();

        Minify::clear_minified('css');

        wp_send_json_success(array(
            'message' => __('Minified CSS cleared.', 'perfmatters'), 
        ));
    }
}