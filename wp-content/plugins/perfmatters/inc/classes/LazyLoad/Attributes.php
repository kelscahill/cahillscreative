<?php

namespace Perfmatters\LazyLoad;

use Perfmatters\Config;

class Attributes
{
	private static ?array $forced_cache = null;
	private static ?array $excluded_cache = null;

	//get forced attributes
	public static function forced()
	{
		if(self::$forced_cache !== null) {
			return self::$forced_cache;
		}

		self::$forced_cache = apply_filters('perfmatters_lazyload_forced_attributes', array());

		return self::$forced_cache;
	}

	//get excluded attributes
	public static function excluded()
	{
		if(self::$excluded_cache !== null) {
			return self::$excluded_cache;
		}

		$attributes = array(
			'data-perfmatters-preload',
			'gform_ajax_frame',
			';base64',
			'skip-lazy',
			'fetchpriority="high"'
		);

		if(!empty(Config::$options['lazyload']['lazy_loading_exclusions']) && is_array(Config::$options['lazyload']['lazy_loading_exclusions'])) {
			$attributes = array_unique(array_merge($attributes, Config::$options['lazyload']['lazy_loading_exclusions']));
		}

		self::$excluded_cache = apply_filters('perfmatters_lazyload_excluded_attributes', $attributes);

		return self::$excluded_cache;
	}
}
