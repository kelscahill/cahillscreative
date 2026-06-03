<?php

namespace Perfmatters\LazyLoad;

use Perfmatters\Config;
use Perfmatters\HTML;
use Perfmatters\Utilities;

class Elements
{
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

		if(\Perfmatters\LazyLoad::should_skip_request()) {
			return;
		}

		//actions + filters
		add_action('perfmatters_output_buffer', array('Perfmatters\LazyLoad\Elements', 'lazyload_elements'));

		//disable wp rocket lazy render for compatibility
		add_filter('rocket_lrc_optimization', '__return_false', 999);
	}

	//lazy load elements
	public static function lazyload_elements($html) {

		if(!empty(Config::$options['lazyload']['elements'])) {

			$lazy_element = null;

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
}
