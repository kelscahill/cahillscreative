<?php
namespace Perfmatters;

class CDN
{
    private static $cdn_url;

    //initialize cdn
    public static function init() 
    {
        add_action('perfmatters_queue', array(__CLASS__, 'queue'));
    }

    //queue functions
    public static function queue() 
    {
        //cdn rewrite enabled
        if(self::is_enabled()) {

            //cdn url
            if(!empty(self::$cdn_url = self::get_cdn_url())) {

                //add cdn rewrite to the buffer
                add_action('perfmatters_output_buffer', array(__CLASS__, 'rewrite'));
            }
        }
    }

    //return cdn rewrite enabled status
    public static function is_enabled() {
        return apply_filters('perfmatters_cdn', !empty(Config::$options['cdn']['enable_cdn']));
    }

    //return cdn url
    public static function get_cdn_url() {
        return apply_filters('perfmatters_cdn_url', Config::$options['cdn']['cdn_url'] ?? '');
    }

    //rewrite urls in html
    public static function rewrite($html) 
    {
        //prep site url
        $siteURL  = '//' . ((!empty($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : parse_url(home_url(), PHP_URL_HOST));
        $escapedSiteURL = quotemeta($siteURL);
        $regExURL = '(?:https?:)?' . substr($escapedSiteURL, strpos($escapedSiteURL, '//'));

        //prep included directories
        $directories = 'wp\-content|wp\-includes';
        if(!empty(Config::$options['cdn']['cdn_directories'])) {
            $directoriesArray = array_map('trim', explode(',', Config::$options['cdn']['cdn_directories']));
            if(count($directoriesArray) > 0) {
                $directories = implode('|', array_map('quotemeta', array_filter($directoriesArray)));
            }
        }

        //prep included extensions
        $extensions_array = apply_filters('perfmatters_cdn_extensions', array(
            'avif',
            'css',
            'gif',
            'jpeg',
            'jpg',
            'js',
            'json',
            'mp3',
            'mp4',
            'otf',
            'pdf',
            'png',
            'svg',
            'ttf',
            'webp',
            'woff',
            'woff2'
        ));
        $extensions = implode('|', $extensions_array);

        //rewrite urls in html
        $regEx = '#(?<=[(\"\']|&quot;)(?:' . $regExURL . ')?\/(?:[^\"\')]*)(?:(?:' . $directories . ')[^\"\')]+).(' . $extensions . ')[^\"\')]*(?=[\"\')]|&quot;)#';

        //base exclusions
        $exclusions = array('script-manager.js');

        //add user exclusions
        if(!empty(Config::$options['cdn']['cdn_exclusions'])) {
            $exclusions_user = array_map('trim', explode(',', Config::$options['cdn']['cdn_exclusions']));
            $exclusions = array_merge($exclusions, $exclusions_user);
        }

        //set cdn url
        $cdnURL = untrailingslashit(self::$cdn_url);

        //replace urls
        $html = preg_replace_callback($regEx, function($url) use ($siteURL, $cdnURL, $exclusions) {

            //check for exclusions
            if(Utilities::match_in_array($url[0], $exclusions)) {
                return $url[0];
            }

            //replace url with no scheme
            if(strpos($url[0], '//') === 0) {
                return str_replace($siteURL, $cdnURL, $url[0]);
            }

            //replace non relative site url
            if(strstr($url[0], $siteURL)) {
                return str_replace(array('http:' . $siteURL, 'https:' . $siteURL), $cdnURL, $url[0]);
            }

            //replace relative url
            return $cdnURL . $url[0];

        }, $html);

        return $html;
    }
}