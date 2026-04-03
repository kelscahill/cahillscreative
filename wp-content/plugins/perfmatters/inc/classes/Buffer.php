<?php
namespace Perfmatters;

class Buffer
{
    //initialize buffer class
    public static function init()
    {
        if(is_admin()) {
            return;
        }

        $buffer_action = self::get_buffer_action();

        //add buffer action
        add_action($buffer_action, array('Perfmatters\Buffer', 'start'), 1);
    }

    //start buffer
    public static function start()
    {
        //open the output buffer
        ob_start(array('Perfmatters\Buffer', 'process'));
    }

    //process buffer html
    public static function process($html)
    {
        //buffer is allowed
        if(!apply_filters('perfmatters_allow_buffer', true)) {
            return $html;
        }
        
        //do we have any filters to apply?
        if(!has_filter('perfmatters_output_buffer')) {
            return $html;   
        }
            
        //exclude certain requests
        if(is_embed() || is_feed() || is_preview() || is_customize_preview() || is_singular(array('bricks_template'))) {
            return $html;
        }

        //don't buffer amp
        if(function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return $html;
        }
            
        //check for valid buffer
        if(!self::is_valid_buffer($html)) {
            return $html;
        }

        //run buffer filters
        $html = (string) apply_filters('perfmatters_output_buffer', $html);

        //return processed html
        return $html;
    }

    //make sure buffer content is valid
    private static function is_valid_buffer($html)
    {
        //check for valid/invalid tags
        if(stripos($html, '<html') === false || stripos($html, '</body>') === false || stripos($html, '<xsl:stylesheet') !== false) {
            return false;
        }

        //check for invalid urls
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $matches = apply_filters('perfmatters_buffer_excluded_extensions', array('.xml', '.txt', '.php'));
        foreach($matches as $match) {
            if(stripos($current_url, $match) !== false) {
                return false;
            }
        }

        return true;
    }

    //return action hook for buffer start
    public static function get_buffer_action()
    {
        //siteground optimizer
        //hummingbird
        if(defined('SiteGround_Optimizer\VERSION') || defined('WPHB_VERSION')) {
            return 'template_redirect';
        }
        
        //default
        return 'after_setup_theme';
    }

    //check viewport meta tag position in relation to the title tag
    public static function check_viewport_position($html)
    {   
        //tag positions
        $title_pos = strpos($html, '<title');
        $viewport_pos = strpos($html, '<meta name="viewport"');

        //both tags exist but are in the wrong order
        if($title_pos !== false && $viewport_pos !== false && $title_pos < $viewport_pos) {

            //match full viewport tag string
            if(preg_match('/<meta[^>]+name=["\']viewport["\'][^>]*>/is', $html, $match)) {

                //remove original viewport tag and add right before the title tag
                $viewport_tag_string = $match[0];
                $html = str_replace($match[0], '', $html);
                $html = substr_replace($html, $match[0], $title_pos, 0);
            }
        }

        return $html;
    }
}