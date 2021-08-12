<?php
//initialize page buffer
function perfmatters_buffer_init() {
	if(has_filter('perfmatters_output_buffer')) {
		ob_start('perfmatters_buffer_process');
	}
}
add_action('template_redirect', 'perfmatters_buffer_init');

//process buffer
function perfmatters_buffer_process($html) {

	//run buffer filters
	$html = (string) apply_filters('perfmatters_output_buffer', $html);

	//return processed html
	return $html;
}

//initialize assets js functions
function perfmatters_assets_js_init() {

	$perfmatters_extras = get_option('perfmatters_extras');

	if(!empty($perfmatters_extras['defer_js'])) {

		$exclude_defer_js = perfmatters_get_post_meta('perfmatters_exclude_defer_js');

		//check if its ok to continue
		if(!$exclude_defer_js && !is_admin() && !wp_doing_ajax() && !isset($_GET['fl_builder']) && !isset($_GET['et_fb']) && !isset($_GET['ct_builder']) && !is_embed() && !is_feed()) {

			//actions + filters
			add_filter('perfmatters_output_buffer', 'perfmatters_defer_js', 2);
		}
	}
}
add_action('wp', 'perfmatters_assets_js_init');

//add defer tag to js files in html
function perfmatters_defer_js($html) {

	global $post;

	//stop if defer js is disabled for post
	if(perfmatters_get_post_meta('perfmatters_exclude_defer_js')) {
		return $html;
	}

	//strip comments before search
	$html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);

	//match all script tags
	preg_match_all('#(<script\s?([^>]+)?\/?>)(.*?)<\/script>#is', $html_no_comments, $matches);

	//no script tags found
	if(!isset($matches[0])) {
		return $html;
	}

	$perfmatters_extras = get_option('perfmatters_extras');

	//build js exlusions array
	$js_exclusions = array();

	//add jquery if needed
	if(empty($perfmatters_extras['defer_jquery'])) {
		array_push($js_exclusions, 'jquery(?:\.min)?.js');
	}

	//add extra user exclusions
	if(!empty($perfmatters_extras['js_exclusions']) && is_array($perfmatters_extras['js_exclusions'])) {
		foreach($perfmatters_extras['js_exclusions'] as $line) {
			array_push($js_exclusions, preg_quote($line));
		}
	}

	//convert exlusions to string for regex
	$js_exclusions = implode('|', $js_exclusions);

	foreach($matches[0] as $i => $tag) {

		if(!empty($matches[2][$i])) {
			$atts_array = perfmatters_lazyload_get_atts_array($matches[2][$i]);
		}
		
		//skip if type is not javascript
		if(isset($atts_array['type']) && stripos($atts_array['type'], 'javascript') == false) {
			continue;
		}

		//src file is set
		if(!empty($atts_array['src'])) {

			//check src for exclusions
			if(!empty($js_exclusions) && preg_match('#(' . $js_exclusions . ')#i', $atts_array['src'])) {
				continue;
			}

		}

		//inline script
		else {

			//make sure its ok to defer inline
			if(!empty($perfmatters_extras['defer_inline_js'])) {

				//check inline script content for exlusions
				if(!empty($js_exclusions) && preg_match('#(' . $js_exclusions . ')#i', $matches[3][$i])) {
					continue;
				}

			}
			else {
				continue;
			}

		}

		//skip if there is already an async
		if(stripos($matches[2][$i], 'async') !== false) {
			continue;
		}

		//skip if there is already a defer
		if (stripos($matches[2][ $i ], 'defer' ) !== false ) {
			continue;
		}

		//add defer to opening tag
		$deferred_tag_open = str_replace('>', ' defer>', $matches[1][$i]);

		//replace new open tag in original full tag
		$deferred_tag = str_replace($matches[1][$i], $deferred_tag_open, $tag);

		//replace new full tag in html
		$html = str_replace($tag, $deferred_tag, $html);
	}

	return $html;
}

//get given post meta option for current post
function perfmatters_get_post_meta($option) {

	global $post;

	if(!is_object($post)) {
		return false;
	}

	if(is_home()) {
		$post_id = get_queried_object_id();
	}

	if(is_singular() && isset($post)) {
		$post_id = $post->ID;
	}

	return (isset($post_id)) ? get_post_meta($post_id, $option, true) : false;
}