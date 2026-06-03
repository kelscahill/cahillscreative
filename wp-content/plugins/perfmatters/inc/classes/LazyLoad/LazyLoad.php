<?php

namespace Perfmatters;

use Perfmatters\LazyLoad\Assets;
use Perfmatters\LazyLoad\Iframes;
use Perfmatters\LazyLoad\Images;
use Perfmatters\LazyLoad\Elements;

class LazyLoad
{
	private static ?bool $should_skip_request_cache = null;
	private static ?bool $lazyload_noscript_cache = null;

	//initialize shared lazyload frontend assets
	public static function init_assets()
	{
		add_action('perfmatters_queue', array(Assets::class, 'queue_assets'));
	}

	//initialize lazyload iframe functions
	public static function init_iframes()
	{
		add_action('perfmatters_queue', array(Iframes::class, 'queue_iframes'));
	}

	//initialize image lazy loading
	public static function init_images()
	{
		add_action('perfmatters_queue', array(Images::class, 'queue_images'));
	}
	
	//initialize lazy loading elements
	public static function init_elements()
	{
		add_action('perfmatters_queue', array(Elements::class, 'queue_elements'));
	}

	//shared request-level skip checks for lazyload queue handlers
	public static function should_skip_request()
	{
		if(self::$should_skip_request_cache !== null) {
			return self::$should_skip_request_cache;
		}

		self::$should_skip_request_cache = Utilities::is_woocommerce() || Utilities::get_post_meta('perfmatters_exclude_lazy_loading');

		return self::$should_skip_request_cache;
	}

	//shared request-level cache for perfmatters_lazyload_noscript (buffer + assets)
	public static function is_lazyload_noscript_enabled(): bool
	{
		if(self::$lazyload_noscript_cache !== null) {
			return self::$lazyload_noscript_cache;
		}

		self::$lazyload_noscript_cache = (bool) apply_filters('perfmatters_lazyload_noscript', false);

		return self::$lazyload_noscript_cache;
	}
}