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

	$defer_check = !empty($perfmatters_extras['defer_js']) && !perfmatters_get_post_meta('perfmatters_exclude_defer_js');

	$delay_check = !empty($perfmatters_extras['delay_js']);

	if($defer_check || $delay_check) {

		//check if its ok to continue
		if(!is_admin() && !perfmatters_is_dynamic_request() && !isset($_GET['perfmatters']) && !perfmatters_is_page_builder() && !is_embed() && !is_feed()) {

			//actions + filters
			add_filter('perfmatters_output_buffer', 'perfmatters_optimize_js', 2);

			if($delay_check) {
				add_action('wp_footer', 'perfmatters_print_delay_js', PHP_INT_MAX);
			}
		}
	}
}
add_action('wp', 'perfmatters_assets_js_init');

//add defer tag to js files in html
function perfmatters_optimize_js($html) {

	//strip comments before search
	$html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);

	//match all script tags
	preg_match_all('#(<script\s?([^>]+)?\/?>)(.*?)<\/script>#is', $html_no_comments, $matches);

	//no script tags found
	if(!isset($matches[0])) {
		return $html;
	}

	$perfmatters_extras = get_option('perfmatters_extras');

	$defer_check = !empty($perfmatters_extras['defer_js']) && !perfmatters_get_post_meta('perfmatters_exclude_defer_js');

	//build js exlusions array
	$js_exclusions = array();

	if($defer_check) {

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
	}

	//loop through scripts
	foreach($matches[0] as $i => $tag) {

		$atts_array = !empty($matches[2][$i]) ? perfmatters_lazyload_get_atts_array($matches[2][$i]) : array();
		
		//skip if type is not javascript
		if(isset($atts_array['type']) && stripos($atts_array['type'], 'javascript') == false) {
			continue;
		}

		//delay javascript
		if(!empty($perfmatters_extras['delay_js'])) {

	 		foreach($perfmatters_extras['delay_js'] as $delayed_script) {

        		if(strpos($tag, $delayed_script) !== false) {

        			if(!empty($atts_array['src'])) {
                    	$atts_array['data-pmdelayedscript'] = $atts_array['src'];
                    	unset($atts_array['src']);
        			}
        			else {
        				$atts_array['data-pmdelayedscript'] = "data:text/javascript;base64," . base64_encode($matches[3][$i]);
        			}

        			$delayed_atts_string = perfmatters_lazyload_get_atts_string($atts_array);
                    $delayed_tag = sprintf('<script %1$s></script>', $delayed_atts_string);

        			//replace new full tag in html
					$html = str_replace($tag, $delayed_tag, $html);

					continue 2;
		        }
		    }
		}

		//defer javascript
		if($defer_check) {

			//src is not set
			if(empty($atts_array['src'])) {
				continue;
			}

			//check if src is excluded
			if(!empty($js_exclusions) && preg_match('#(' . $js_exclusions . ')#i', $atts_array['src'])) {
				continue;
			}

			//skip if there is already an async
			if(stripos($matches[2][$i], 'async') !== false) {
				continue;
			}

			//skip if there is already a defer
			if(stripos($matches[2][$i], 'defer' ) !== false ) {
				continue;
			}

			//add defer to opening tag
			$deferred_tag_open = str_replace('>', ' defer>', $matches[1][$i]);

			//replace new open tag in original full tag
			$deferred_tag = str_replace($matches[1][$i], $deferred_tag_open, $tag);

			//replace new full tag in html
			$html = str_replace($tag, $deferred_tag, $html);
		}
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

//print inline delay js
function perfmatters_print_delay_js() {

	$extras = get_option('perfmatters_extras');
	$timeout = !empty($extras['delay_timeout']) ? $extras['delay_timeout'] : '';

  	if(!empty($extras['delay_js'])) {
  		echo '<script type="text/javascript" id="perfmatters-delayed-scripts-js">' . (!empty($timeout) ? 'const perfmattersDelayTimer = setTimeout(pmLoadDelayedScripts,' . $timeout . '*1000);' : '') . 'const perfmattersUserInteractions=["keydown","mouseover","wheel","touchmove","touchstart"];perfmattersUserInteractions.forEach(function(event){window.addEventListener(event,pmTriggerDelayedScripts,{passive:!0})});function pmTriggerDelayedScripts(){pmLoadDelayedScripts();' . (!empty($timeout) ? 'clearTimeout(perfmattersDelayTimer);' : '') . 'perfmattersUserInteractions.forEach(function(event){window.removeEventListener(event, pmTriggerDelayedScripts,{passive:!0});});}function pmLoadDelayedScripts(){document.querySelectorAll("script[data-pmdelayedscript]").forEach(function(elem){elem.setAttribute("src",elem.getAttribute("data-pmdelayedscript"));});}</script>';
  	}
}