<?php

namespace Perfmatters;

class LazyLoad
{
	//initialize lazyload iframe functions
	public static function init_iframes()
	{
		add_action('perfmatters_queue', array('Perfmatters\LazyLoad', 'queue_iframes'));
	}

	//queue iframe functions
	public static function queue_iframes()
	{
		//check filters
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['lazy_loading_iframes'])))) {
			return;
		}

		//skip woocommerce
		if(Utilities::is_woocommerce()) {
			return;
		}

		//check post meta
		if(Utilities::get_post_meta('perfmatters_exclude_lazy_loading')) {
			return;
		}

		add_action('perfmatters_output_buffer', array('Perfmatters\LazyLoad', 'iframes_buffer'));
	}

	//process iframe buffer
	public static function iframes_buffer($html) {

		$clean_html = Utilities::clean_html($html);

		$html = self::lazyload_iframes($html, $clean_html);
		$html = self::lazyload_videos($html, $clean_html);

		return $html;
	}

	//lazy load iframes
	public static function lazyload_iframes($html, $buffer) {

		//match all iframes
		preg_match_all('#<iframe(\s.+)>.*</iframe>#iUs', $buffer, $iframes, PREG_SET_ORDER);

		if(!empty($iframes)) {

			//remove any duplicates
			$iframes = array_unique($iframes, SORT_REGULAR);

			foreach($iframes as $iframe) {

				//get iframe attributes array
				$iframe_atts = Utilities::get_atts_array($iframe[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($iframe[1], self::lazyload_forced_atts())) {

					//skip if exluded attribute was found
					if(Utilities::match_in_array($iframe[1], self::lazyload_excluded_atts())) {
						continue;
					}

					//skip if no-lazy class is found
					if(!empty($iframe_atts['class']) && strpos($iframe_atts['class'], 'no-lazy') !== false) {
						continue;
					}
				}

				//skip if no src is found
				if(empty($iframe_atts['src'])) {
					continue;
				}

				//try rendering youtube preview placeholder if we need to
				if(!empty(Config::$options['lazyload']['youtube_preview_thumbnails'])) {
					$iframe_lazyload = self::lazyload_youtube_iframe($iframe, $iframe_atts);
				}
						
				//default iframe placeholder
				if(empty($iframe_lazyload)) {

					$iframe_atts['class'] = !empty($iframe_atts['class']) ? $iframe_atts['class'] . ' ' . 'perfmatters-lazy' : 'perfmatters-lazy';

					//migrate src
					$iframe_atts['data-src'] = $iframe_atts['src'];
					unset($iframe_atts['src']);

					//unset existing loading attribute
					if(isset($iframe_atts['loading'])) {
						unset($iframe_atts['loading']);
					}

					//replace iframe attributes string
					$iframe_lazyload = str_replace($iframe[1], ' ' . Utilities::get_atts_string($iframe_atts), $iframe[0]);
					
					//add noscript original iframe
					if(apply_filters('perfmatters_lazyload_noscript', false)) {
						$iframe_lazyload.= '<noscript>' . $iframe[0] . '</noscript>';
					}
				}

				//replace iframe with placeholder
				$html = str_replace($iframe[0], $iframe_lazyload, $html);

				unset($iframe_lazyload);
			}
		}

		return $html;
	}

	//prep youtube iframe for lazy loading
	public static function lazyload_youtube_iframe($iframe, $iframe_atts = []) {

		if(!$iframe || empty($iframe_atts['src'])) {
			return false;
		}

		//attempt to get the id based on url
		$result = preg_match('#^(?:https?:)?(?://)?(?:www\.)?(?:youtu\.be|youtube\.com|youtube-nocookie\.com)/(?:embed/|v/|watch/?\?v=)?([\w-]{11})#iU', $iframe_atts['src'], $matches);

		//return false if there is no usable id
		if(!$result || $matches[1] === 'videoseries') {
			return false;
		}

		$youtube_id = $matches[1];

		//parse iframe src url
		$parsed_url = wp_parse_url(htmlspecialchars_decode($iframe_atts['src']));

		//adjust query string
		$query = '';
		if(!empty($parsed_url['query'])) {
			$query_params = [];
		    parse_str($parsed_url['query'], $query_params);
		    unset($query_params['autoplay']);
		    $query = http_build_query($query_params);
		}
		
		//reconstruct base url
		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '//';
		$host   = $parsed_url['host'] ?? '';
		$path   = $parsed_url['path'] ?? '';
		$youtube_url = $scheme . $host . $path;

		//thumbnail resolutions
		$resolutions = array(
			'default'       => array(
				'width'  => 120,
				'height' => 90,
			),
			'mqdefault'     => array(
				'width'  => 320,
				'height' => 180,
			),
			'hqdefault'     => array(
				'width'  => 480,
				'height' => 360,
			),
			'sddefault'     => array(
				'width'  => 640,
				'height' => 480,
			),
			'maxresdefault' => array(
				'width'  => 1280,
				'height' => 720,
			)
		);

		//filter set resolution
		$resolution = apply_filters('perfmatters_lazyload_youtube_thumbnail_resolution', 'hqdefault');

		//finished youtube lazy output
		$youtube_lazyload = '<div class="perfmatters-lazy-youtube" data-src="' . esc_attr($youtube_url) . '" data-id="' . esc_attr($youtube_id) . '" data-query="' . esc_attr($query) . '" data-referrerpolicy="' . ($iframe_atts['referrerpolicy'] ?? '') . '" onclick="perfmattersLazyLoadYouTube(this);">';
			$youtube_lazyload.= '<div>';
				$youtube_lazyload.= '<img src="https://i.ytimg.com/vi/' . esc_attr($youtube_id) .'/' . $resolution . '.jpg" alt="YouTube ' . __('video', 'perfmatters') . '" width="' . $resolutions[$resolution]['width'] . '" height="' . $resolutions[$resolution]['height'] . '" data-pin-nopin="true" nopin="nopin">';
				$youtube_lazyload.= '<div class="play"></div>';
			$youtube_lazyload.= '</div>';
		$youtube_lazyload.= '</div>';

		//noscript tag
		if(apply_filters('perfmatters_lazyload_noscript', false)) {
			$youtube_lazyload.= '<noscript>' . $iframe[0] . '</noscript>';
		}

		return $youtube_lazyload;
	}

	//lazy load videos
	public static function lazyload_videos($html, $buffer) {

		//match all videos
		preg_match_all('#<video(\s.+)>.*</video>#iUs', $buffer, $videos, PREG_SET_ORDER);

		if(!empty($videos)) {

			//remove any duplicates
			$videos = array_unique($videos, SORT_REGULAR);

			foreach($videos as $video) {

				//get video attributes array
				$video_atts = Utilities::get_atts_array($video[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($video[1], self::lazyload_forced_atts())) {

					//skip if exluded attribute was found
					if(Utilities::match_in_array($video[1], self::lazyload_excluded_atts())) {
						continue;
					}

					//skip if no-lazy class is found
					if(!empty($video_atts['class']) && strpos($video_atts['class'], 'no-lazy') !== false) {
						continue;
					}
				}

				//skip if no src is found
				if(empty($video_atts['src'])) {
					continue;
				}

				//add lazyload class
				$video_atts['class'] = !empty($video_atts['class']) ? $video_atts['class'] . ' ' . 'perfmatters-lazy' : 'perfmatters-lazy';

				//migrate src
				$video_atts['data-src'] = $video_atts['src'];
				unset($video_atts['src']);

				//migrate poster
				if(!empty($video_atts['poster'])) {
					$video_atts['data-poster'] = $video_atts['poster'];
					unset($video_atts['poster']);
				}

				//replace video attributes string
				$video_lazyload  = str_replace($video[1], ' ' . Utilities::get_atts_string($video_atts), $video[0]);

				//add noscript original video
				if(apply_filters('perfmatters_lazyload_noscript', false)) {
					$video_lazyload .= '<noscript>' . $video[0] . '</noscript>';
				}

				//replace video with placeholder
				$html = str_replace($video[0], $video_lazyload, $html);

				unset($video_lazyload);
			}
		}

		return $html;
	}

	//initialize lazy loading
	public static function init_images()
	{
		add_action('perfmatters_queue', array('Perfmatters\LazyLoad', 'queue_images'));
	}

	//queue image functions
	public static function queue_images()
	{
		//check filters
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['lazy_loading']) || !empty(Config::$options['lazyload']['lazy_loading_iframes']) || !empty(Config::$options['lazyload']['css_background_images'])))) {
			return;
		}

		//skip woocommerce
		if(Utilities::is_woocommerce()) {
			return;
		}

		//check post meta
		if(Utilities::get_post_meta('perfmatters_exclude_lazy_loading')) {
			return;
		}

		//actions + filters
		add_action('perfmatters_output_buffer', array('Perfmatters\LazyLoad', 'lazyload_buffer'));
		add_action('wp_enqueue_scripts', array('Perfmatters\LazyLoad', 'enqueue_scripts'));
		add_filter('script_loader_tag', array('Perfmatters\LazyLoad', 'async_script'), 10, 2);
		add_action('wp_head', array('Perfmatters\LazyLoad', 'lazyload_css'), PHP_INT_MAX);

		//images only
		if(!empty(Config::$options['lazyload']['lazy_loading'])) {
			add_filter('wp_get_attachment_image_attributes', function($attr) {
				unset($attr['loading']);
			  	return $attr;
			});
		}
	}

	//lazy load buffer
	public static function lazyload_buffer($html) {

		$buffer = Utilities::clean_html($html);

		//replace image tags
		if(!empty(Config::$options['lazyload']['lazy_loading'])) {
			$html = self::lazyload_parent_leading_exclusions($html, $buffer);
			$html = self::lazyload_parent_exclusions($html, $buffer);
			$html = self::lazyload_pictures($html, $buffer);
			$html = self::lazyload_background_images($html, $buffer);
			$html = self::lazyload_images($html, $buffer);
		}

		//replace css background elements
		if(!empty(Config::$options['lazyload']['css_background_images'])) {
			$html = self::lazyload_css_background_images($html, $buffer);
		}
		
		return $html;
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
		if(apply_filters('perfmatters_lazyload_noscript', false)) {
			echo '<noscript><style>.perfmatters-lazy[data-src]{display:none !important;}</style></noscript>';
		}

		$styles = '';

		//youtube thumbnails
		if(!empty(Config::$options['lazyload']['lazy_loading_iframes']) && !empty(Config::$options['lazyload']['youtube_preview_thumbnails'])) {
			$styles.= '.perfmatters-lazy-youtube{position:relative;width:100%;max-width:100%;height:0;padding-bottom:56.23%;overflow:hidden}.perfmatters-lazy-youtube img{position:absolute;top:0;right:0;bottom:0;left:0;display:block;width:100%;max-width:100%;height:auto;margin:auto;border:none;cursor:pointer;transition:.5s all;-webkit-transition:.5s all;-moz-transition:.5s all}.perfmatters-lazy-youtube img:hover{-webkit-filter:brightness(75%)}.perfmatters-lazy-youtube .play{position:absolute;top:50%;left:50%;right:auto;width:68px;height:48px;margin-left:-34px;margin-top:-24px;background:url('.plugins_url('perfmatters/img/youtube.svg').') no-repeat;background-position:center;background-size:cover;pointer-events:none;filter:grayscale(1)}.perfmatters-lazy-youtube:hover .play{filter:grayscale(0)}.perfmatters-lazy-youtube iframe{position:absolute;top:0;left:0;width:100%;height:100%;z-index:99}';
			if(current_theme_supports('responsive-embeds') || in_array('wp-embed-responsive', get_body_class())) {
				$styles.= '.wp-has-aspect-ratio .wp-block-embed__wrapper{position:relative;}.wp-has-aspect-ratio .perfmatters-lazy-youtube{position:absolute;top:0;right:0;bottom:0;left:0;width:100%;height:100%;padding-bottom:0}';
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

		//youtube thumbnails
		if(!empty(Config::$options['lazyload']['lazy_loading_iframes']) && !empty(Config::$options['lazyload']['youtube_preview_thumbnails'])) {
			$autoplay = apply_filters('perfmatters_lazyload_youtube_autoplay', true);
			$output.= 'function perfmattersLazyLoadYouTube(e){var t=document.createElement("iframe"),r="ID?";r+=0===e.dataset.query.length?"":e.dataset.query+"&"' . ($autoplay ? ',r+="autoplay=1"' : '') . ',t.setAttribute("src",r.replace("ID",e.dataset.src)),e.dataset.referrerpolicy && t.setAttribute("referrerpolicy",e.dataset.referrerpolicy),t.setAttribute("frameborder","0"),t.setAttribute("allowfullscreen","1"),t.setAttribute("allow","accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"),e.replaceChild(t,e.firstChild)}';
		}
		return $output;
	}

	//lazy load img tags
	private static function lazyload_images($html, $buffer) {

		//match all img tags
		preg_match_all('#<img([^>]+?)\/?>#is', $buffer, $images, PREG_SET_ORDER);

		if(!empty($images)) {

			$lazy_image_count = 0;
			$exclude_leading_images = apply_filters('perfmatters_exclude_leading_images', Config::$options['lazyload']['exclude_leading_images'] ?? 0);
			$leading_image_exclusions = apply_filters('perfmatters_leading_image_exclusions', array(
				'data-perfmatters-leading-skip'
			));

			//remove any duplicate images
			$images = array_unique($images, SORT_REGULAR);

			//loop through images
	        foreach($images as $image) {

	        	//check for leading image exclusion
	        	if(!Utilities::match_in_array($image[0], $leading_image_exclusions)) {

                	$lazy_image_count++;
		        	if($lazy_image_count <= $exclude_leading_images) {
		        		
		        		//exclude image
		        		$html = str_replace($image[0], self::exclude_image($image), $html);

		        		continue;
		        	}
                }
	        	
	        	//prepare and replace lazy load image
	            $html = str_replace($image[0], self::lazyload_image($image), $html);
	        }
		}
			
		return $html;
	}

	//lazy load picture tags for webp
	private static function lazyload_pictures($html, $buffer) {

		//match all picture tags
		preg_match_all('#<picture(.*)?>(.*)<\/picture>#isU', $buffer, $pictures, PREG_SET_ORDER);

		if(!empty($pictures)) {

			foreach($pictures as $picture) {

				//get picture tag attributes
				$picture_atts = Utilities::get_atts_array($picture[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($picture[1], self::lazyload_forced_atts())) {

					//skip if no-lazy class is found
					if((!empty($picture_atts['class']) && strpos($picture_atts['class'], 'no-lazy') !== false) || Utilities::match_in_array($picture[0], self::lazyload_excluded_atts())) {

						//mark image for exclusion later
						preg_match('#<img([^>]+?)\/?>#is', $picture[0], $image);
						if(!empty($image)) {
							$image_atts = Utilities::get_atts_array($image[1]);
							$image_atts['class'] = (!empty($image_atts['class']) ? $image_atts['class'] . ' ' : '') . 'no-lazy';

							//remove loading attribute
							if(isset($image_atts['loading'])) {
								unset($image_atts['loading']);
							}

							$new_image = sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));                
			                $html = str_replace($image[0], $new_image, $html);
						}
						continue;
					}
				}

				//match all source tags inside the picture
				preg_match_all('#<source(\s.+)>#isU', $picture[2], $sources, PREG_SET_ORDER);

				if(!empty($sources)) {

					//remove any duplicate sources
					$sources = array_unique($sources, SORT_REGULAR);

		            foreach($sources as $source) {

		            	//skip if exluded attribute was found
						if(Utilities::match_in_array($source[1], self::lazyload_excluded_atts())) {
							continue;
						}

						//migrate srcet
		                $new_source = preg_replace('/([\s"\'])srcset/i', '${1}data-srcset', $source[0]);

		                //migrate sizes
		                $new_source = preg_replace('/([\s"\'])sizes/i', '${1}data-sizes', $new_source);

		                //replace source in html	                
		                $html = str_replace($source[0], $new_source, $html);
		            }
				}
				else {
					continue;
				}
			}
		}

		return $html;
	}

	//lazy load background images
	private static function lazyload_background_images($html, $buffer) {

		//match all elements with inline styles
		//preg_match_all('#<(?<tag>div|figure|section|span|li)(\s+[^>]+[\'"\s]?style\s*=\s*[\'"].*?background-image.*?[\'"][^>]*)>#is', $buffer, $elements, PREG_SET_ORDER); //alternate to possibly filter some out that don't have background images???
		preg_match_all('#<(?<tag>div|figure|section|span|li|a)(\s+[^>]*[\'"\s]?style\s*=\s*[\'"].*?[\'"][^>]*)>#is', $buffer, $elements, PREG_SET_ORDER);

		if(!empty($elements)) {

			foreach($elements as $element) {

				//get element tag attributes
				$element_atts = Utilities::get_atts_array($element[2]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($element[2], self::lazyload_forced_atts())) {

					//skip if no-lazy class is found
					if(!empty($element_atts['class']) && strpos($element_atts['class'], 'no-lazy') !== false) {
						continue;
					}

					//skip if exluded attribute was found
					if(Utilities::match_in_array($element[2], self::lazyload_excluded_atts())) {
						continue;
					}
				}

				//skip if no style attribute
				if(!isset($element_atts['style'])) {
					continue;
				}

				//match background-image in style string
				preg_match('#(([^;\s])*background(-(image|url))?)\s*:\s*(\s*url\s*\((?<url>[^)]+)\))\s*;?#is', $element_atts['style'], $url);

				if(!empty($url)) {

					$url['url'] = trim($url['url'], '\'" ');

					//add lazyload class
					$element_atts['class'] = !empty($element_atts['class']) ? $element_atts['class'] . ' ' . 'perfmatters-lazy' : 'perfmatters-lazy';

					//remove background image url from inline style attribute
					$element_atts['style'] = str_replace($url[0], '', $element_atts['style']);

					//migrate src
					$element_atts['data-bg'] = esc_url(trim(strip_tags(html_entity_decode($url['url'], ENT_QUOTES|ENT_HTML5)), '\'" '));

					if(!empty($url[2])) {
						$element_atts['data-bg-var'] = $url[1];
					}

					//build lazy element
					$lazy_element = sprintf('<' . $element['tag'] . ' %1$s >', Utilities::get_atts_string($element_atts));

					//replace element with placeholder
					$html = str_replace($element[0], $lazy_element, $html);

					unset($lazy_element);
				}
				else {
					continue;
				}
			}
		}

		return $html;
	}

	//prep img tag for lazy loading
	private static function lazyload_image($image) {

		//if there are no attributes, return original match
		if(empty($image[1])) {
			return $image[0];
		}

		//get image attributes array
		$image_atts = Utilities::get_atts_array($image[1]);

		//get new attributes
		if(empty($image_atts['src']) || (!Utilities::match_in_array($image[1], self::lazyload_forced_atts()) && ((!empty($image_atts['class']) && strpos($image_atts['class'], 'no-lazy') !== false) || Utilities::match_in_array($image[1], self::lazyload_excluded_atts()) || (!empty($image_atts['fetchpriority']) && $image_atts['fetchpriority'] == 'high')))) {
			
			//remove loading attribute
			if(isset($image_atts['loading'])) {
				unset($image_atts['loading']);
				return sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));
			}

			return $image[0];
		}
		else {

			//add lazyload class
			$image_atts['class'] = !empty($image_atts['class']) ? $image_atts['class'] . ' ' . 'perfmatters-lazy' : 'perfmatters-lazy';

			//migrate src
			$image_atts['data-src'] = $image_atts['src'];

			//add placeholder src
			$width = !empty($image_atts['width']) ? $image_atts['width'] : 0;
			$height = !empty($image_atts['height']) ? $image_atts['height'] : 0;
			$image_atts['src'] = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='" . $width . "'%20height='" . $height . "'%20viewBox='0%200%20" . $width . "%20" . $height . "'%3E%3C/svg%3E";

			//migrate srcset
			if(!empty($image_atts['srcset'])) {
				$image_atts['data-srcset'] = $image_atts['srcset'];
				unset($image_atts['srcset']);
			}
			
			//migrate sizes
			if(!empty($image_atts['sizes'])) {
				$image_atts['data-sizes'] = $image_atts['sizes'];
				unset($image_atts['sizes']);
			}

			//unset existing loading attribute
			if(isset($image_atts['loading'])) {
				unset($image_atts['loading']);
			}
		}

		//replace attributes
		$output = sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));

		//original noscript image
		if(apply_filters('perfmatters_lazyload_noscript', false)) {
			$output.= "<noscript>" . $image[0] . "</noscript>";
		}
		
		return $output;
	}

	//lazy load css background images
	private static function lazyload_css_background_images($html) {

		$selectors = [];

		//add selectors
		if(!empty(Config::$options['lazyload']['css_background_selectors']) && is_array(Config::$options['lazyload']['css_background_selectors'])) {
			$selectors = array_unique(array_merge($selectors, Config::$options['lazyload']['css_background_selectors']));
		}

		//filter selectors
		$selectors = apply_filters('perfmatters_css_background_selectors', $selectors);

		if(!empty($selectors)) {

	        //get all elements with the selector
	        $elements = HTML::get_selector_elements($html, $selectors);

	        if(!empty($elements)) {

	        	//exclude leading
				if(!empty(Config::$options['lazyload']['css_background_exclude_leading'])) {
					$elements = array_slice($elements, Config::$options['lazyload']['css_background_exclude_leading']);
				}

	        	foreach($elements as $element) {

        			//get attributes array
        			$selector_tag_atts = Utilities::get_atts_array($element['selector_tag_atts']);

        			//skip no-lazy
					if(!empty($selector_tag_atts['class']) && strpos($selector_tag_atts['class'], 'no-lazy') !== false) {
						continue;
					}

					//add lazy class
		        	$selector_tag_atts['class'] = !empty($selector_tag_atts['class']) ? $selector_tag_atts['class'] . ' ' . 'perfmatters-lazy-css-bg' : 'perfmatters-lazy-css-bg';

		            //replace attributes string in selector tag
					$new_selector_tag = str_replace($element['selector_tag_atts'], ' ' . Utilities::get_atts_string($selector_tag_atts), $element['selector_tag']);

					//replace first instance of selector tag in element
					$selector_tag_pos = strpos($element['html'], $element['selector_tag']);
					if($selector_tag_pos !== false) {
					    $new_element = substr_replace($element['html'], $new_selector_tag, $selector_tag_pos, strlen($element['selector_tag']));

					    //replace element in html
						$html = str_replace($element['html'], $new_element, $html);
					}
		   		}
	        }
		}

		return $html;
	}

	//initialize lazy loading elements
	public static function init_elements()
	{
		add_action('perfmatters_queue', array('Perfmatters\LazyLoad', 'queue_elements'));
	}

	//queue element functions
	public static function queue_elements()
	{
		//check filters
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['elements'])))) {
			return;
		}

		if(empty(apply_filters('perfmatters_lazy_elements', !empty(Config::$options['lazyload']['elements'])))) {
			return;
		}

		//skip woocommerce
		if(Utilities::is_woocommerce()) {
			return;
		}

		//check post meta
		if(Utilities::get_post_meta('perfmatters_exclude_lazy_loading')) {
			return;
		}

		//actions + filters
		add_action('perfmatters_output_buffer', array('Perfmatters\LazyLoad', 'lazyload_elements'));

		//disable wp rocket lazy render for compatibility
		add_filter('rocket_lrc_optimization', '__return_false', 999);
	}

	//lazy load elements
	public static function lazyload_elements($html) {

		if(!empty(Config::$options['lazyload']['elements'])) {

			$selectors = array(
				'perfmatters-lazy-element',
			);

			//get exclusions added from settings
			if(!empty(Config::$options['lazyload']['element_selectors']) && is_array(Config::$options['lazyload']['element_selectors'])) {
				$selectors = array_unique(array_merge($selectors, Config::$options['lazyload']['element_selectors']));
			}
			
			//filter final exclusions array
			$selectors = apply_filters('perfmatters_lazy_element_selectors', $selectors);

	        //get all elements with the selector
	        $elements = HTML::get_selector_elements($html, $selectors);

	        if(!empty($elements)) {

	        	//remove any duplicate matches
	        	if(count($elements) > 1) {
	        		$elements = array_map("unserialize", array_unique(array_map("serialize", $elements)));
	        	}
	        	
	        	foreach($elements as $element) {

	        		//swap lazy element in content
	                $lazy_element = '<div data-perfmatters-lazy-element style="height:1000px; width:100%;"><noscript>' . str_replace('noscript', 'pmnoscript', $element['html']) . '</noscript></div>';
	        		$html = str_replace($element['html'], $lazy_element, $html);
		   		}
	        }

		    if(!empty($lazy_element)) {

			    $script = '<script id="perfmatters-lazy-elements-js" defer>!function(){var e=document.querySelectorAll("div[data-perfmatters-lazy-element]");if(e.length){var t=new IntersectionObserver((function(e,t){e.forEach((function(e){if(e.isIntersecting){var r=e.target.querySelector("noscript");if(r){var n,o=(new DOMParser).parseFromString(r.textContent.replaceAll("pmnoscript","noscript"),"text/html");(n=e.target).replaceWith.apply(n,o.body.childNodes)}t.unobserve(e.target)}}))}),{root:null,rootMargin:"300px 0px"});e.forEach((function(e){return t.observe(e)}))}}();</script>';

				//add inline script to body tag
				$html = str_replace('</body>', $script . '</body>', $html);
		    }
		}

		return $html;
	}

	//exclude images from leading image exclusions by parent selector
	private static function lazyload_parent_leading_exclusions($html, &$buffer) {

		$parent_exclusions = apply_filters('perfmatters_leading_image_parent_exclusions', array());
        if(!empty($parent_exclusions)) {

        	//get elements with selector
            $elements = HTML::get_selector_elements($buffer, $parent_exclusions);

            if(!empty($elements)) {
                foreach($elements as $element) {

                	//match all img tags
					preg_match_all('#<img([^>]+?)\/?>#is', $element['html'], $images, PREG_SET_ORDER);

					if(!empty($images)) {

						//remove any duplicate images
						$images = array_unique($images, SORT_REGULAR);

						//loop through images
				        foreach($images as $image) {

				        	$image_atts = Utilities::get_atts_array($image[1]);

				        	$image_atts['data-perfmatters-leading-skip'] = 1;

				            //replace attributes string
							$new_image = str_replace($image[1], ' ' . Utilities::get_atts_string($image_atts), $image[0]);

							//replace image
							$buffer = str_replace($image[0], $new_image, $buffer);
							$html = str_replace($image[0], $new_image, $html);
				        }
					}
                }
            }
        }

        return $html;
	}

	//mark images inside parent exclusions as no-lazy
	private static function lazyload_parent_exclusions($html, &$buffer) {

		$parent_exclusions = [];

		//add exclusions
		if(!empty(Config::$options['lazyload']['lazy_loading_parent_exclusions']) && is_array(Config::$options['lazyload']['lazy_loading_parent_exclusions'])) {
			$parent_exclusions = array_unique(array_merge($parent_exclusions, Config::$options['lazyload']['lazy_loading_parent_exclusions']));
		}

		//filter exclusions
		$parent_exclusions = apply_filters('perfmatters_lazyload_parent_exclusions', $parent_exclusions);

        if(!empty($parent_exclusions)) {

        	//get elements with selector
            $elements = HTML::get_selector_elements($buffer, $parent_exclusions);

            if(!empty($elements)) {
                foreach($elements as $element) {

                	//match all img tags
					preg_match_all('#<img([^>]+?)\/?>#is', $element['html'], $images, PREG_SET_ORDER);

					if(!empty($images)) {

						//remove any duplicate images
						$images = array_unique($images, SORT_REGULAR);

						//loop through images
				        foreach($images as $image) {

				        	$image_atts = Utilities::get_atts_array($image[1]);

				        	$image_atts['class'] = !empty($image_atts['class']) ? $image_atts['class'] . ' ' . 'no-lazy' : 'no-lazy';

				            //replace attributes string
							$new_image = str_replace($image[1], ' ' . Utilities::get_atts_string($image_atts), $image[0]);

							//replace image
							$buffer = str_replace($image[0], $new_image, $buffer);
							$html = str_replace($image[0], $new_image, $html);
				        }
					}
                }
            }
		}

		return $html;
	}

	//exclude image from lazy loading
	private static function exclude_image($image) {
		
		$image_atts = Utilities::get_atts_array($image[1]);
		
		if(isset($image_atts['loading'])) {
			unset($image_atts['loading']);
			return sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));
		}

		return $image[0];
	}

	//get forced attributes
	private static function lazyload_forced_atts() {
		return apply_filters('perfmatters_lazyload_forced_attributes', array());
	}

	//get excluded attributes
	private static function lazyload_excluded_atts() {

		//base exclusions
		$attributes = array(
			'data-perfmatters-preload',
			'gform_ajax_frame',
			';base64',
			'skip-lazy',
			'fetchpriority="high"'
		); 

		//get exclusions added from settings
		if(!empty(Config::$options['lazyload']['lazy_loading_exclusions']) && is_array(Config::$options['lazyload']['lazy_loading_exclusions'])) {
			$attributes = array_unique(array_merge($attributes, Config::$options['lazyload']['lazy_loading_exclusions']));
		}

	    return apply_filters('perfmatters_lazyload_excluded_attributes', $attributes);
	}
}