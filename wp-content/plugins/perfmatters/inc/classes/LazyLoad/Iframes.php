<?php

namespace Perfmatters\LazyLoad;

use Perfmatters\Config;
use Perfmatters\Utilities;
use Perfmatters\LazyLoad\Attributes;
use Perfmatters\LazyLoad\Integrations\Elementor;

class Iframes
{
	private static ?array $youtube_thumbnail_resolution_cache = null;

	//queue iframe functions
	public static function queue_iframes()
	{
		//check filters
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['lazy_loading_iframes'])))) {
			return;
		}

		if(\Perfmatters\LazyLoad::should_skip_request()) {
			return;
		}

		add_action('perfmatters_output_buffer', array(Iframes::class, 'iframes_buffer'));
	}

	//process iframe buffer
	public static function iframes_buffer($html) {

		self::$youtube_thumbnail_resolution_cache = null;

		$clean_html = Utilities::clean_html($html);

		$html = self::lazyload_iframes($html, $clean_html);
		$html = self::lazyload_videos($html, $clean_html);

		return $html;
	}

	//lazy load iframes
	public static function lazyload_iframes($html, &$buffer) {

		//convert elementor video widget placeholders to iframes
		if((stripos($buffer, 'elementor-widget-video') !== false || stripos($buffer, 'e-youtube-base') !== false) && function_exists('is_plugin_active') && is_plugin_active('elementor/elementor.php')) {
			$html = Elementor::convert_videos($html, $buffer);
		}

		//nothing to process if there are still no iframes after optional elementor conversion
		if(stripos($buffer, '<iframe') === false) {
			return $html;
		}

		//match all iframes
		preg_match_all('#<iframe(\s.+)>.*</iframe>#iUs', $buffer, $iframes, PREG_SET_ORDER);

		if(!empty($iframes)) {

			foreach($iframes as $iframe) {

				//get iframe attributes array
				$iframe_atts = Utilities::get_atts_array($iframe[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($iframe[1], Attributes::forced())) {

					//skip if exluded attribute was found
					if(Utilities::match_in_array($iframe[1], Attributes::excluded())) {
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

				//youtube prep pass first: add generic poster attrs from youtube id if setting is enabled
				if(!empty(Config::$options['lazyload']['youtube_preview_thumbnails'])) {
					$iframe_atts = self::prep_youtube_poster_atts($iframe_atts);
				}

				//single generic render path for any prepped poster
				if(!empty($iframe_atts['data-perfmatters-poster'])) {
					$iframe_lazyload = self::lazyload_prepped_video_iframe($iframe, $iframe_atts);
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
					if(\Perfmatters\LazyLoad::is_lazyload_noscript_enabled()) {
						$iframe_lazyload.= '<noscript>' . $iframe[0] . '</noscript>';
					}
				}

				//replace iframe with placeholder (single occurrence only)
				$html = Utilities::replace_first_occurrence($html, $iframe[0], $iframe_lazyload);

				unset($iframe_lazyload);
			}
		}

		return $html;
	}

	//prep generic poster attrs from youtube iframe src
	private static function prep_youtube_poster_atts($iframe_atts = []) {

		if(empty($iframe_atts['src'])) {
			return $iframe_atts;
		}
		if(!empty($iframe_atts['data-perfmatters-poster'])) {
			return $iframe_atts;
		}

		//cheap skip before youtube-specific regex
		if(stripos($iframe_atts['src'], 'youtu') === false) {
			return $iframe_atts;
		}

		//attempt to get the id based on url
		$result = preg_match('#^(?:https?:)?(?://)?(?:www\.)?(?:youtu\.be|youtube\.com|youtube-nocookie\.com)/(?:embed/|v/|watch/?\?v=)?([\w-]{11})#iU', $iframe_atts['src'], $matches);

		//return unchanged if there is no usable id
		if(!$result || $matches[1] === 'videoseries') {
			return $iframe_atts;
		}

		$thumb = self::youtube_thumbnail_resolution();

		$iframe_atts['data-perfmatters-poster'] = 'https://i.ytimg.com/vi/' . esc_attr($matches[1]) .'/' . $thumb['size'] . '.jpg';
		$iframe_atts['data-perfmatters-poster-width'] = $thumb['width'];
		$iframe_atts['data-perfmatters-poster-height'] = $thumb['height'];
		$iframe_atts['data-perfmatters-provider'] = 'youtube';

		//normalize src by removing autoplay from query; generic renderer will rebuild src from data-src + data-query
		$parsed_url = wp_parse_url(htmlspecialchars_decode($iframe_atts['src']));
		if(!empty($parsed_url)) {
			$query_params = [];
			if(!empty($parsed_url['query'])) {
			    parse_str($parsed_url['query'], $query_params);
			    unset($query_params['autoplay']);
			}
			$query = http_build_query($query_params);
			$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '//';
			$host   = $parsed_url['host'] ?? '';
			$path   = $parsed_url['path'] ?? '';
			$iframe_atts['src'] = $scheme . $host . $path . (!empty($query) ? '?' . $query : '');
		}

		return $iframe_atts;
	}

	//prep generic iframe for lazy video placeholder when attrs already provide poster details
	public static function lazyload_prepped_video_iframe($iframe, $iframe_atts = []) {
		if(!$iframe || empty($iframe_atts['src']) || empty($iframe_atts['data-perfmatters-poster'])) {
			return false;
		}

		//parse iframe src url and split query so generic click handler can reconstruct src
		$parsed_url = wp_parse_url(htmlspecialchars_decode($iframe_atts['src']));

		$query = '';
		if(!empty($parsed_url['query'])) {
			parse_str($parsed_url['query'], $query_params);
			$query = http_build_query($query_params);
		}

		$scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '//';
		$host   = $parsed_url['host'] ?? '';
		$path   = $parsed_url['path'] ?? '';
		$video_url = $scheme . $host . $path;

		$thumbnail_src = $iframe_atts['data-perfmatters-poster'];

		$thumbnail_width  = null;
		$thumbnail_height = null;
		if(isset($iframe_atts['data-perfmatters-poster-width'], $iframe_atts['data-perfmatters-poster-height'])) {
			$w = (int) $iframe_atts['data-perfmatters-poster-width'];
			$h = (int) $iframe_atts['data-perfmatters-poster-height'];
			if($w > 0 && $h > 0) {
				$thumbnail_width  = $w;
				$thumbnail_height = $h;
			}
		}

		$provider = !empty($iframe_atts['data-perfmatters-provider']) ? $iframe_atts['data-perfmatters-provider'] : 'video';

		return self::lazyload_video_placeholder($iframe, $iframe_atts, $video_url, $video_url, $query, $thumbnail_src, $thumbnail_width, $thumbnail_height, $provider);
	}

	//shared video placeholder renderer for youtube/elementor prepared data
	private static function lazyload_video_placeholder($iframe, $iframe_atts, $video_url, $video_id, $query, $thumbnail_src, $thumbnail_width = null, $thumbnail_height = null, $provider = 'video') {
		$lazy_video = '<div class="perfmatters-lazy-video" data-provider="' . esc_attr($provider) . '" data-src="' . esc_attr($video_url) . '" data-id="' . esc_attr($video_id) . '" data-query="' . esc_attr($query) . '" data-referrerpolicy="' . ($iframe_atts['referrerpolicy'] ?? '') . '" onclick="perfmattersLazyLoadVideo(this);">';
			$lazy_video.= '<div>';
				$lazy_video.= '<img src="' . esc_url($thumbnail_src) . '" alt="' . esc_attr(ucfirst($provider)) . ' ' . __('video', 'perfmatters') . '"';
				if(!empty($thumbnail_width) && !empty($thumbnail_height)) {
					$lazy_video.= ' width="' . (int) $thumbnail_width . '" height="' . (int) $thumbnail_height . '"';
				}
				$lazy_video.= ' data-pin-nopin="true" nopin="nopin">';
				$lazy_video.= '<div class="play"></div>';
			$lazy_video.= '</div>';
		$lazy_video.= '</div>';

		//noscript tag
		if(\Perfmatters\LazyLoad::is_lazyload_noscript_enabled()) {
			$lazy_video.= '<noscript>' . $iframe[0] . '</noscript>';
		}

		return $lazy_video;
	}

	//lazy load videos
	public static function lazyload_videos($html, $buffer) {
		if(stripos($buffer, '<video') === false) {
			return $html;
		}

		//match all videos
		preg_match_all('#<video(\s.+)>.*</video>#iUs', $buffer, $videos, PREG_SET_ORDER);

		if(!empty($videos)) {
			foreach($videos as $video) {
				//get video attributes array
				$video_atts = Utilities::get_atts_array($video[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($video[1], Attributes::forced())) {
					//skip if exluded attribute was found
					if(Utilities::match_in_array($video[1], Attributes::excluded())) {
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
				if(\Perfmatters\LazyLoad::is_lazyload_noscript_enabled()) {
					$video_lazyload .= '<noscript>' . $video[0] . '</noscript>';
				}

				//replace video with placeholder (single occurrence only)
				$html = Utilities::replace_first_occurrence($html, $video[0], $video_lazyload);

				unset($video_lazyload);
			}
		}

		return $html;
	}

	//size = i.ytimg filename key; width/height for poster <img>; cached per buffer pass
	private static function youtube_thumbnail_resolution(): array {
		if(self::$youtube_thumbnail_resolution_cache !== null) {
			return self::$youtube_thumbnail_resolution_cache;
		}

		$resolutions = array(
			'default'       => array('width'  => 120, 'height' => 90),
			'mqdefault'     => array('width'  => 320, 'height' => 180),
			'hqdefault'     => array('width'  => 480, 'height' => 360),
			'sddefault'     => array('width'  => 640, 'height' => 480),
			'maxresdefault' => array('width'  => 1280, 'height' => 720)
		);

		$candidate = apply_filters('perfmatters_lazyload_youtube_thumbnail_resolution', 'hqdefault');
		$size      = isset($resolutions[$candidate]) ? $candidate : 'hqdefault';

		self::$youtube_thumbnail_resolution_cache = array(
			'size'   => $size,
			'width'  => (int) $resolutions[$size]['width'],
			'height' => (int) $resolutions[$size]['height'],
		);

		return self::$youtube_thumbnail_resolution_cache;
	}

	//video placeholder assets are needed for youtube previews and elementor prepped posters
	public static function should_enqueue_video_placeholder_assets() {
		if(empty(Config::$options['lazyload']['lazy_loading_iframes'])) {
			return false;
		}
		if(!empty(Config::$options['lazyload']['youtube_preview_thumbnails'])) {
			return true;
		}
		return function_exists('is_plugin_active') && is_plugin_active('elementor/elementor.php');
	}
}