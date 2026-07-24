<?php

namespace Perfmatters\LazyLoad;

use Perfmatters\Config;
use Perfmatters\HTML;
use Perfmatters\Utilities;

class Images
{
	//queue image functions
	public static function queue_images()
	{
		//check filters
		if(empty(apply_filters('perfmatters_lazyload', !empty(Config::$options['lazyload']['lazy_loading']) || !empty(Config::$options['lazyload']['css_background_images'])))) {
			return;
		}

		if(\Perfmatters\LazyLoad::should_skip_request()) {
			return;
		}

		//actions + filters
		add_action('perfmatters_output_buffer', array(Images::class, 'images_buffer'));

		//images only
		if(!empty(Config::$options['lazyload']['lazy_loading'])) {
			add_filter('wp_get_attachment_image_attributes', function($attr) {
				unset($attr['loading']);
				return $attr;
			});
		}
	}

	//lazy load buffer
	public static function images_buffer($html) {
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

	//lazy load img tags
	private static function lazyload_images($html, $buffer) {
		if(stripos($buffer, '<img') === false) {
			return $html;
		}

		//match all img tags
		preg_match_all('#<img([^>]+?)\/?>#is', $buffer, $images, PREG_SET_ORDER);

		if(!empty($images)) {
			$lazy_image_count = 0;
			$exclude_leading_images = apply_filters('perfmatters_exclude_leading_images', Config::$options['lazyload']['exclude_leading_images'] ?? 0);
			$leading_image_exclusions = apply_filters('perfmatters_leading_image_exclusions', array(
				'data-perfmatters-leading-skip'
			));

			//loop through images
			foreach($images as $image) {
				//check for leading image exclusion
				if(!Utilities::match_in_array($image[0], $leading_image_exclusions)) {
					$lazy_image_count++;
					if($lazy_image_count <= $exclude_leading_images) {
						//exclude image
						$html = Utilities::replace_first_occurrence($html, $image[0], self::exclude_image($image));
						continue;
					}
				}
				
				//prepare and replace lazy load image
				$html = Utilities::replace_first_occurrence($html, $image[0], self::lazyload_image($image));
			}
		}
			
		return $html;
	}

	//lazy load picture tags for webp
	private static function lazyload_pictures($html, $buffer) {
		if(stripos($buffer, '<picture') === false) {
			return $html;
		}

		//match all picture tags
		preg_match_all('#<picture(.*)?>(.*)<\/picture>#isU', $buffer, $pictures, PREG_SET_ORDER);

		if(!empty($pictures)) {
			foreach($pictures as $picture) {
				//get picture tag attributes
				$picture_atts = Utilities::get_atts_array($picture[1]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($picture[1], Attributes::forced())) {
					//skip if no-lazy class is found
					if((!empty($picture_atts['class']) && strpos($picture_atts['class'], 'no-lazy') !== false) || Utilities::match_in_array($picture[0], Attributes::excluded())) {
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
							$html = Utilities::replace_first_occurrence($html, $image[0], $new_image);
						}
						continue;
					}
				}

				//match all source tags inside the picture
				if(stripos($picture[2], '<source') === false) {
					continue;
				}

				preg_match_all('#<source(\s.+)>#isU', $picture[2], $sources, PREG_SET_ORDER);

				if(!empty($sources)) {
					$new_picture_html = $picture[0];

					foreach($sources as $source) {
						//skip if exluded attribute was found
						if(Utilities::match_in_array($source[1], Attributes::excluded())) {
							continue;
						}

						//migrate srcet
						$new_source = preg_replace('/([\s"\'])srcset/i', '${1}data-srcset', $source[0]);

						//migrate sizes
						$new_source = preg_replace('/([\s"\'])sizes/i', '${1}data-sizes', $new_source);

						//replace source instances in picture only
						$new_picture_html = preg_replace(
							'#' . preg_quote($source[0], '#') . '#',
							$new_source,
							$new_picture_html
						);
					}

					//replace updated picture in html (single occurrence only)
					$html = Utilities::replace_first_occurrence($html, $picture[0], $new_picture_html);
				}
			}
		}

		return $html;
	}

	//lazy load background images
	private static function lazyload_background_images($html, $buffer) {
		
		if(!str_contains($buffer, 'style=') || !str_contains($buffer, 'background')) {
			return $html;
		}

		//inline background lazyload regex looks for url(...); skip full scan when impossible
		if(stripos($buffer, 'url(') === false) {
			return $html;
		}

		//match all elements with inline styles
		preg_match_all('#<(?<tag>div|figure|section|span|li|a)(\s+[^>]*[\'"\s]?style\s*=\s*[\'"].*?[\'"][^>]*)>#is', $buffer, $elements, PREG_SET_ORDER);

		if(!empty($elements)) {
			foreach($elements as $element) {
				//get element tag attributes
				$element_atts = Utilities::get_atts_array($element[2]);

				//dont check excluded if forced attribute was found
				if(!Utilities::match_in_array($element[2], Attributes::forced())) {
					//skip if no-lazy class is found
					if(!empty($element_atts['class']) && strpos($element_atts['class'], 'no-lazy') !== false) {
						continue;
					}

					//skip if exluded attribute was found
					if(Utilities::match_in_array($element[2], Attributes::excluded())) {
						continue;
					}
				}

				//skip if no style attribute
				if(!isset($element_atts['style'])) {
					continue;
				}

				if(stripos($element_atts['style'], 'url(') === false) {
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

		//skip/exclude image
		if((empty($image_atts['src']) && empty($image_atts['srcset'])) || (!Utilities::match_in_array($image[1], Attributes::forced()) && ((!empty($image_atts['class']) && strpos($image_atts['class'], 'no-lazy') !== false) || Utilities::match_in_array($image[1], Attributes::excluded()) || (!empty($image_atts['fetchpriority']) && $image_atts['fetchpriority'] == 'high')))) {
			//remove loading attribute
			if(isset($image_atts['loading'])) {
				unset($image_atts['loading']);
				return sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));
			}

			return $image[0];
		}

		//add lazyload class
		$image_atts['class'] = !empty($image_atts['class']) ? $image_atts['class'] . ' ' . 'perfmatters-lazy' : 'perfmatters-lazy';

		//migrate src
		$image_atts['data-src'] = $image_atts['src'] ?? '';

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

		//replace attributes
		$output = sprintf('<img %1$s />', Utilities::get_atts_string($image_atts));

		//original noscript image
		if(\Perfmatters\LazyLoad::is_lazyload_noscript_enabled()) {
			$output.= '<noscript>' . $image[0] . '</noscript>';
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

	//exclude images from leading image exclusions by parent selector
	private static function lazyload_parent_leading_exclusions($html, &$buffer) {
		$parent_exclusions = apply_filters('perfmatters_leading_image_parent_exclusions', array());
		if(!empty($parent_exclusions)) {
			//get elements with selector
			$elements = HTML::get_selector_elements($buffer, $parent_exclusions);

			if(!empty($elements)) {
				foreach($elements as $element) {
					if(stripos($element['html'], '<img') === false) {
						continue;
					}

					//match all img tags
					preg_match_all('#<img([^>]+?)\/?>#is', $element['html'], $images, PREG_SET_ORDER);

					if(!empty($images)) {
						//loop through images
						foreach($images as $image) {
							$image_atts = Utilities::get_atts_array($image[1]);
							$image_atts['data-perfmatters-leading-skip'] = 1;

							//replace attributes string
							$new_image = str_replace($image[1], ' ' . Utilities::get_atts_string($image_atts), $image[0]);

							//replace image (single occurrence only)
							$html = Utilities::replace_first_occurrence($html, $image[0], $new_image);
							$buffer = Utilities::replace_first_occurrence($buffer, $image[0], $new_image);
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
					if(stripos($element['html'], '<img') === false) {
						continue;
					}

					//match all img tags
					preg_match_all('#<img([^>]+?)\/?>#is', $element['html'], $images, PREG_SET_ORDER);

					if(!empty($images)) {
						//loop through images
						foreach($images as $image) {
							$image_atts = Utilities::get_atts_array($image[1]);
							$image_atts['class'] = !empty($image_atts['class']) ? $image_atts['class'] . ' ' . 'no-lazy' : 'no-lazy';

							//replace attributes string
							$new_image = str_replace($image[1], ' ' . Utilities::get_atts_string($image_atts), $image[0]);

							//replace image (single occurrence only)
							$html = Utilities::replace_first_occurrence($html, $image[0], $new_image);
							$buffer = Utilities::replace_first_occurrence($buffer, $image[0], $new_image);
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

}
