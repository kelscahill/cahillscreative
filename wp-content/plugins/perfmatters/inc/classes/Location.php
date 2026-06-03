<?php
namespace Perfmatters;

class Location
{
    //match a location input string/array against the current request
    public static function matches($locations): bool
    {
        $normalized_locations = is_array($locations) ? $locations : self::normalize_tokens($locations);

        if(empty($normalized_locations)) {
            return false;
        }

        $context = self::get_context();

        //front page (must be checked before singular for static front pages)
        if($context['is_front_page']) {
            if(in_array('front', $normalized_locations, true)) {
                return true;
            }

            //allow matching by front-page ID as well
            if($context['front_page_id'] !== '0' && in_array($context['front_page_id'], $normalized_locations, true)) {
                return true;
            }
        }

        //single post
        if($context['is_singular']) {
            if($context['queried_id'] !== '0' && in_array($context['queried_id'], $normalized_locations, true)) {
                return true;
            }
        }
        //posts page
        elseif($context['is_home'] && in_array('blog', $normalized_locations, true)) {
            return true;
        }
        //woocommerce shop archive
        elseif(!empty($context['shop_id']) && in_array($context['shop_id'], $normalized_locations, true)) {
            return true;
        }

        return false;
    }

    //normalize comma-separated location input into lowercase tokens
    public static function normalize_tokens($locations): array
    {
        return array_filter(
            array_map('strtolower', array_map('trim', explode(',', (string) $locations))),
            static function($location) { return $location !== ''; }
        );
    }

    //cache and return shared request-level location context
    public static function get_context(): array
    {
        static $context = null;

        if($context === null) {
            $shop_id = '';
            if(function_exists('is_shop') && is_shop() && function_exists('wc_get_page_id')) {
                $shop_id = (string) wc_get_page_id('shop');
            }

            $context = array(
                'is_front_page' => is_front_page(),
                'is_singular'   => is_singular(),
                'is_home'       => is_home(),
                'queried_id'    => (string) get_queried_object_id(),
                'front_page_id' => (string) ((int) get_option('page_on_front')),
                'shop_id'       => $shop_id
            );
        }

        return $context;
    }
}