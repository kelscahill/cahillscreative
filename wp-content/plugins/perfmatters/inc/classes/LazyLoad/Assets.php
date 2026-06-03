<?php

namespace Perfmatters\LazyLoad;

use Perfmatters\Config;

class Assets
{
	//queue shared lazyload frontend assets
	public static function queue_assets()
	{
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['lazy_loading']) || !empty(Config::$options['lazyload']['lazy_loading_iframes']) || !empty(Config::$options['lazyload']['css_background_images'])))) {
			return;
		}

		if(\Perfmatters\LazyLoad::should_skip_request()) {
			return;
		}

		//disable core native lazy loading
		add_filter('wp_lazy_loading_enabled', '__return_false');
		add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

		add_action('wp_enqueue_scripts', array(Assets::class, 'enqueue_scripts'));
		add_filter('script_loader_tag', array(Assets::class, 'async_script'), 10, 2);
		add_action('wp_head', array(Assets::class, 'lazyload_css'), PHP_INT_MAX);
	}

	//enqueue lazy load script
	public static function enqueue_scripts() {
		wp_register_script('perfmatters-lazy-load', plugins_url('perfmatters/js/lazyload.min.js'), array(), PERFMATTERS_VERSION, true);
		wp_enqueue_script('perfmatters-lazy-load');
		wp_add_inline_script('perfmatters-lazy-load', self::inline_js(), 'before');
	}

	//add async tag to enqueued script
	public static function async_script($tag, $handle) {
		if($handle !== 'perfmatters-lazy-load') {
			return $tag;
		}
		return str_replace(' src', ' async src', $tag);
	}

	//print lazy load styles
	public static function lazyload_css() {
		//print noscript styles
		if(\Perfmatters\LazyLoad::is_lazyload_noscript_enabled()) {
			echo '<noscript><style>.perfmatters-lazy[data-src]{display:none !important;}</style></noscript>';
		}

		$styles = '';

		//video placeholders (youtube setting on, or elementor video conversion can prep custom thumbnail attrs)
		if(Iframes::should_enqueue_video_placeholder_assets()) {
			$styles.= '.perfmatters-lazy-video{position:relative;width:100%;max-width:100%;height:0;padding-bottom:56.23%;overflow:hidden}.perfmatters-lazy-video img{position:absolute;top:0;right:0;bottom:0;left:0;display:block;width:100%;height:100%;max-width:none;margin:0;object-fit:cover;object-position:center;border:none;cursor:pointer;transition:.5s all;-webkit-transition:.5s all;-moz-transition:.5s all}.perfmatters-lazy-video img:hover{-webkit-filter:brightness(75%)}.perfmatters-lazy-video .play{position:absolute;top:50%;left:50%;right:auto;width:68px;height:48px;margin-left:-34px;margin-top:-24px;background:url('.plugins_url('perfmatters/img/youtube.svg').') no-repeat;background-position:center;background-size:cover;pointer-events:none;filter:grayscale(1)}.perfmatters-lazy-video:hover .play{filter:grayscale(0)}.perfmatters-lazy-video iframe{position:absolute;top:0;left:0;width:100%;height:100%;z-index:99}';
			
			//elementor fix
			if(function_exists('is_plugin_active') && is_plugin_active('elementor/elementor.php')) {
				$styles.= '.elementor-widget-html:has(.perfmatters-lazy-video),.elementor-widget-text-editor:has(.perfmatters-lazy-video){width:100%}';
			}

			if(current_theme_supports('responsive-embeds') || in_array('wp-embed-responsive', get_body_class())) {
				$styles.= '.wp-block-embed.wp-has-aspect-ratio .wp-block-embed__wrapper:has(.perfmatters-lazy-video)::before{display:none;}';
			}
		}

		//fade in effect
		if(!empty(Config::$options['lazyload']['fade_in'])) {
			$styles.= '.perfmatters-lazy.pmloaded,.perfmatters-lazy.pmloaded>img,.perfmatters-lazy>img.pmloaded,.perfmatters-lazy[data-ll-status=entered]{animation:' . apply_filters('perfmatters_fade_in_speed', 500) . 'ms pmFadeIn}@keyframes pmFadeIn{0%{opacity:0}100%{opacity:1}}';
		}

		//css background images
		if(!empty(Config::$options['lazyload']['css_background_images'])) {
			$styles.='body .perfmatters-lazy-css-bg:not([data-ll-status=entered]),body .perfmatters-lazy-css-bg:not([data-ll-status=entered]) *,body .perfmatters-lazy-css-bg:not([data-ll-status=entered])::before,body .perfmatters-lazy-css-bg:not([data-ll-status=entered])::after,body .perfmatters-lazy-css-bg:not([data-ll-status=entered]) *::before,body .perfmatters-lazy-css-bg:not([data-ll-status=entered]) *::after{background-image:none!important;will-change:transform;transition:opacity 0.025s ease-in,transform 0.025s ease-in!important;}';
		}
			
		//print styles
		if(!empty($styles)) {
			echo '<style>' . $styles . '</style>';
		}
	}

	//inline lazy load js
	private static function inline_js() {
		$threshold = apply_filters('perfmatters_lazyload_threshold', !empty(Config::$options['lazyload']['threshold']) ? Config::$options['lazyload']['threshold'] : '0px');
		if(ctype_digit($threshold)) {
			$threshold.= 'px';
		}

		$data_src = apply_filters('perfmatters_lazyload_data_src', true) ? 'img[data-src],' : '';

		//declare lazy load options
		$output = 'window.lazyLoadOptions={elements_selector:"' . $data_src . '.perfmatters-lazy,.perfmatters-lazy-css-bg",thresholds:"' . $threshold . ' 0px",class_loading:"pmloading",class_loaded:"pmloaded",callback_loaded:function(element){if(element.tagName==="IFRAME"){if(element.classList.contains("pmloaded")){if(typeof window.jQuery!="undefined"){if(jQuery.fn.fitVids){jQuery(element).parent().fitVids()}}}}}};';

		//lazy loader initialized
		$output.= 'window.addEventListener("LazyLoad::Initialized",function(e){var lazyLoadInstance=e.detail.instance;';

		//dom monitoring
		if(!empty(Config::$options['lazyload']['lazy_loading_dom_monitoring']) || !empty(Config::$options['lazyload']['elements'])) {
			$output.= 'var target=document.querySelector("body");var observer=new MutationObserver(function(mutations){lazyLoadInstance.update()});var config={childList:!0,subtree:!0};observer.observe(target,config);';
		}

		$output.= '});';

		//video placeholders (youtube setting on, or elementor conversion with custom thumbnail attrs)
		if(Iframes::should_enqueue_video_placeholder_assets()) {
			$autoplay = apply_filters('perfmatters_lazyload_youtube_autoplay', true);
			$output.= 'function perfmattersLazyLoadVideo(e){var t=document.createElement("iframe"),r="ID?";r+=0===e.dataset.query.length?"":e.dataset.query+"&"' . ($autoplay ? ',r+="autoplay=1"' : '') . ',t.setAttribute("src",r.replace("ID",e.dataset.src)),e.dataset.referrerpolicy && t.setAttribute("referrerpolicy",e.dataset.referrerpolicy),t.setAttribute("frameborder","0"),t.setAttribute("allowfullscreen","1"),t.setAttribute("allow","accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"),e.replaceChild(t,e.firstChild)};function perfmattersLazyLoadYouTube(e){perfmattersLazyLoadVideo(e)}';
		}
		return $output;
	}
}
