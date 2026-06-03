<?php

namespace Perfmatters\LazyLoad\Integrations;

use Perfmatters\HTML;
use Perfmatters\Utilities;
use Perfmatters\LazyLoad\Iframes;

class Elementor
{
	//convert elementor video placeholder markup into iframes/placeholders
	public static function convert_videos($html, &$buffer) {
		$elementor_video_widgets = HTML::get_selector_elements($html, array(
			'elementor-widget-video',
			'e-youtube-base'
		));

		if(empty($elementor_video_widgets)) {
			return $html;
		}

		foreach($elementor_video_widgets as $elementor_video_widget) {
			$widget_atts = Utilities::get_atts_array($elementor_video_widget['selector_tag_atts']);
			$is_atomic_youtube = !empty($widget_atts['data-e-type']) && $widget_atts['data-e-type'] === 'e-youtube';

			$settings = self::decode_video_settings($widget_atts);
			if(empty($settings)) {
				continue;
			}

			//atomic youtube is converted directly from a standalone div
			if($is_atomic_youtube) {
				$widget_updated = self::convert_atomic_youtube_widget($settings);
				if(empty($widget_updated)) {
					continue;
				}
				$html = Utilities::replace_first_occurrence($html, $elementor_video_widget['html'], $widget_updated);
				$buffer = Utilities::replace_first_occurrence($buffer, $elementor_video_widget['html'], $widget_updated);
				continue;
			}

			//classic route only supports video.default widgets
			$is_classic_video_widget = !empty($widget_atts['data-widget_type']) && $widget_atts['data-widget_type'] === 'video.default';
			if(!$is_classic_video_widget) {
				continue;
			}

			$video_type = $settings['video_type'] ?? '';
			if($video_type === 'youtube') {
				$widget_updated = self::convert_classic_youtube_widget($elementor_video_widget['html'], $settings);
			}
			elseif($video_type === 'vimeo') {
				$widget_updated = self::convert_classic_vimeo_widget($elementor_video_widget['html'], $settings);
			}
			else {
				continue;
			}

			if(empty($widget_updated)) {
				continue;
			}

			$widget_updated = self::cleanup_classic_widget_markup($widget_updated, $widget_atts, $elementor_video_widget);
			$html = Utilities::replace_first_occurrence($html, $elementor_video_widget['html'], $widget_updated);
			$buffer = Utilities::replace_first_occurrence($buffer, $elementor_video_widget['html'], $widget_updated);
		}

		return $html;
	}

	//decode elementor settings payload from widget attributes
	private static function decode_video_settings($widget_atts) {
		if(empty($widget_atts['data-settings'])) {
			return [];
		}

		$settings_json = html_entity_decode($widget_atts['data-settings'], ENT_QUOTES | ENT_HTML5);
		$settings = json_decode($settings_json, true);
		if(empty($settings) || !is_array($settings)) {
			return [];
		}

		return $settings;
	}

	//convert elementor atomic youtube node into a direct iframe
	private static function convert_atomic_youtube_widget($settings) {
		if(empty($settings['source'])) {
			return '';
		}

		$youtube_id = self::extract_youtube_video_id($settings['source']);
		if(empty($youtube_id)) {
			return '';
		}

		$query_args = array(
			'controls' => !empty($settings['controls']) ? 1 : 0,
			'rel'      => !empty($settings['rel']) ? 1 : 0
		);

		$youtube_host = !empty($settings['privacy']) ? 'www.youtube-nocookie.com' : 'www.youtube.com';
		$iframe_src = add_query_arg($query_args, 'https://' . $youtube_host . '/embed/' . $youtube_id);

		return self::build_youtube_iframe($iframe_src);
	}

	//convert elementor classic youtube widget placeholder into an iframe
	private static function convert_classic_youtube_widget($widget_html, $settings) {
		if(empty($settings['youtube_url'])) {
			return '';
		}

		//ensure placeholder div exists for Elementor YouTube markup
		if(!preg_match('#<div\s[^>]*class=(["\'])[^"\']*elementor-video[^"\']*\1[^>]*>\s*</div>#is', $widget_html)) {
			return '';
		}

		$youtube_id = self::extract_youtube_video_id($settings['youtube_url']);
		if(empty($youtube_id)) {
			return '';
		}

		$query_args = array(
			'controls' => (!empty($settings['controls']) && $settings['controls'] === 'yes') ? 1 : 0
		);
		$iframe_src = add_query_arg($query_args, 'https://www.youtube.com/embed/' . $youtube_id);

		$poster_attr = '';
		if(!empty($settings['show_image_overlay']) && $settings['show_image_overlay'] === 'yes' && !empty($settings['image_overlay']['url'])) {
			$poster_attr = ' data-perfmatters-poster="' . esc_attr(esc_url($settings['image_overlay']['url'])) . '"';
		}

		$iframe = self::build_youtube_iframe($iframe_src, $poster_attr);

		//replace only the placeholder div inside this matched widget chunk
		return preg_replace('#<div\s[^>]*class=(["\'])[^"\']*elementor-video[^"\']*\1[^>]*>\s*</div>#is', $iframe, $widget_html, 1);
	}

	//convert elementor classic vimeo widget into prepared click-to-load placeholder markup
	private static function convert_classic_vimeo_widget($widget_html, $settings) {
		if(empty($settings['show_image_overlay']) || $settings['show_image_overlay'] !== 'yes' || empty($settings['image_overlay']['url'])) {
			return '';
		}

		if(!preg_match('#<iframe([^>]*)>\s*</iframe>#is', $widget_html, $iframe_match)) {
			return '';
		}

		$iframe_atts = Utilities::get_atts_array($iframe_match[1]);
		if(empty($iframe_atts['src'])) {
			return '';
		}

		$iframe_atts['src'] = html_entity_decode($iframe_atts['src'], ENT_QUOTES | ENT_HTML5);
		$iframe_atts['data-perfmatters-poster'] = esc_url($settings['image_overlay']['url']);
		$iframe_atts['data-perfmatters-provider'] = 'vimeo';

		$iframe = array($iframe_match[0], $iframe_match[1]);
		$iframe_lazyload = Iframes::lazyload_prepped_video_iframe($iframe, $iframe_atts);
		if(empty($iframe_lazyload)) {
			return '';
		}

		return str_replace($iframe_match[0], $iframe_lazyload, $widget_html);
	}

	//remove overlay and runtime attrs from converted classic elementor video widgets
	private static function cleanup_classic_widget_markup($widget_updated, $widget_atts, $elementor_video_widget) {
		$overlay_elements = HTML::get_selector_elements($widget_updated, array(
			'elementor-custom-embed-image-overlay'
		));
		if(!empty($overlay_elements)) {
			foreach($overlay_elements as $overlay_element) {
				$widget_updated = str_replace($overlay_element['html'], '', $widget_updated);
			}
		}

		unset($widget_atts['data-settings']);
		unset($widget_atts['data-widget_type']);

		$updated_selector_tag = str_replace($elementor_video_widget['selector_tag_atts'], ' ' . Utilities::get_atts_string($widget_atts), $elementor_video_widget['selector_tag']);
		$selector_tag_pos = strpos($widget_updated, $elementor_video_widget['selector_tag']);
		if($selector_tag_pos !== false) {
			$widget_updated = substr_replace($widget_updated, $updated_selector_tag, $selector_tag_pos, strlen($elementor_video_widget['selector_tag']));
		}

		return $widget_updated;
	}

	//extract 11-char youtube video id from common youtube url formats
	private static function extract_youtube_video_id($url) {
		$result = preg_match('#^(?:https?:)?(?://)?(?:www\.)?(?:youtu\.be|youtube\.com|youtube-nocookie\.com)/(?:embed/|v/|watch/?\?v=)?([\w-]{11})#iU', $url, $youtube_matches);
		if(!$result || empty($youtube_matches[1])) {
			return '';
		}
		return $youtube_matches[1];
	}

	//build youtube iframe markup for elementor conversions
	private static function build_youtube_iframe($iframe_src, $extra_atts = '') {
		return '<iframe class="elementor-video-iframe" allowfullscreen="1" title="' . esc_attr__('YouTube video player', 'perfmatters') . '" frameborder="0" src="' . esc_url($iframe_src) . '"' . $extra_atts . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>';
	}
}