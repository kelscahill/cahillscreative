<?php
namespace Perfmatters;

class JS
{
	private static $run = [];
	private static $data = [];

	//initialize js
	public static function init()
	{
		if(isset($_GET['perfmattersjsoff'])) {
			return;
		}

		add_action('perfmatters_queue', array('Perfmatters\JS', 'queue'));

		//minify admin bar
		if(!empty(Config::$options['assets']['minify_js'])) {
            Minify::queue_admin_bar();
        }


		//ajax actions
		add_action('wp_ajax_perfmatters_clear_minified_js', array('Perfmatters\JS', 'clear_minified_js_ajax'));
	}

	//queue functions
	public static function queue()
	{
		//skip woocommerce
		if(Utilities::is_woocommerce()) {
			return;
		}

		//setup optimizations to run
		self::$run['defer'] = !empty(apply_filters('perfmatters_defer_js', !empty(Config::$options['assets']['defer_js']))) && !Utilities::get_post_meta('perfmatters_exclude_defer_js');
		self::$run['delay'] = !empty(apply_filters('perfmatters_delay_js', !empty(Config::$options['assets']['delay_js']))) && !Utilities::get_post_meta('perfmatters_exclude_delay_js');
		self::$run['minify'] = !empty(apply_filters('perfmatters_minify_js', !empty(Config::$options['assets']['minify_js']))) && !Utilities::get_post_meta('perfmatters_exclude_minify_js');

		if(array_filter(self::$run)) {

			//add to buffer
			add_filter('perfmatters_output_buffer_template_redirect', array('Perfmatters\JS', 'optimize'));

			//fastclick
			if(self::$run['delay'] && !empty(apply_filters('perfmatters_delay_js_fastclick', !empty(Config::$options['assets']['fastclick'])))) {
				add_filter('wp_head', array('Perfmatters\JS', 'print_fastclick'));
			}
		}
	}

	//optimize js
	public static function optimize($html)
	{
		//strip comments before search
		$html_no_comments = preg_replace('/<!--(.*)-->/Uis', '', $html);

		//match all script tags
		preg_match_all('#(<script\s?([^>]+)?\/?>)(.*?)<\/script>#is', $html_no_comments, $matches);

		//no script tags found
		if(!isset($matches[0])) {
			return $html;
		}

		self::populate_data();

		//loop through scripts
		foreach($matches[0] as $i => $tag) {

			$atts_array = !empty($matches[2][$i]) ? Utilities::get_atts_array($matches[2][$i]) : array();
			
			//skip if type is not javascript
			if(isset($atts_array['type']) && stripos($atts_array['type'], 'javascript') == false) {
				continue;
			}

			$delay_flag = false;
			$atts_array_new = $atts_array;

			//minify
			if(self::$run['minify']) {
				if(!empty($atts_array['src']) && $minified_src = Minify::minify($atts_array['src'])) {
					$atts_array_new['src'] = $minified_src;
				}
			}

			//delay
			if(self::$run['delay']) {

				if(empty(self::$data['delay']['behavior'])) {
					$delay_flag = Utilities::match_in_array($tag, self::$data['delay']['inclusions']);
				}
				else {
					$delay_flag = !Utilities::match_in_array($tag, self::$data['delay']['exclusions']);
				}

				if($delay_flag) {

					if(!empty($atts_array['type'])) {
	    				$atts_array_new['data-perfmatters-type'] = $atts_array['type'];
	    			}

	    			$atts_array_new['type'] = 'pmdelayedscript';
	    			$atts_array_new['data-cfasync'] = "false";
	    			$atts_array_new['data-no-optimize'] = "1";
	    			$atts_array_new['data-no-defer'] = "1";
	    			$atts_array_new['data-no-minify'] = "1";

	    			//wp rocket compatability
					if(defined('WP_ROCKET_VERSION')) {
						$atts_array_new['data-rocketlazyloadscript'] = "1";
					}
				}
			}

			//defer
			if(self::$run['defer'] && !$delay_flag) {

				//inline script
				if(empty($atts_array['src'])) {

					//script content
					if(!empty(Config::$options['assets']['defer_inline']) && !empty($matches[3][$i])) {
					
						//exclusion check
						if(!Utilities::match_in_array($tag, self::$data['defer']['exclusions'])) {
							$atts_array_new['defer'] = '';
							$atts_array_new['src'] = 'data:text/javascript;base64,' . base64_encode($matches[3][$i]);
							$matches[3][$i] = '';
						}
					}	
				}
				//standard script
				else {

					//async check
					if(!isset($atts_array['async']) && (empty($atts_array['data-wp-strategy']) || $atts_array['data-wp-strategy'] != 'async')) {
						
						//exclusion check
						if(!Utilities::match_in_array($tag, self::$data['defer']['exclusions'])) {
							$atts_array_new['defer'] = '';
						}
					}
				}
			}

			//replace script tag
			if($atts_array_new !== $atts_array) {
				$atts_array_new = array_merge($atts_array, $atts_array_new);
				$new_atts_string = Utilities::get_atts_string($atts_array_new);
	            $new_tag = sprintf('<script %1$s>', $new_atts_string) . $matches[3][$i] . '</script>';
				$html = str_replace($tag, $new_tag, $html);
			}
		}

		//print delay js
		if(self::$run['delay']) {
            $pos = strpos($html, '</body>');
            if($pos !== false) {
            	$html = substr_replace($html, self::print_delay_js() . '</body>', $pos, 7);
            }
		}

		return $html;
	}

	private static function populate_data() {

		//delay exclusions/inclusions
		if(self::$run['delay']) {

			//behavior
			self::$data['delay']['behavior'] = apply_filters('perfmatters_delay_js_behavior', Config::$options['assets']['delay_js_behavior'] ?? '');

			if(empty(self::$data['delay']['behavior'])) {

				//inclusions for individual delay
				self::$data['delay']['inclusions'] = apply_filters('perfmatters_delayed_scripts', Config::$options['assets']['delay_js_inclusions']);
			}
			else {

				//exclusions for delay all
				self::$data['delay']['exclusions'] = array(
					'perfmatters-delayed-scripts-js',
					'lazyload.min.js',
					'lazyLoadInstance',
					'lazysizes',
					'customize-support',
					'fastclick',
					'jqueryParams',
					'et_link_options_data'
				);

				//add quick exclusions
				if(!empty(Config::$options['assets']['delay_js_quick_exclusions'])) {

				    $master = self::get_quick_exclusions_master();

					foreach(Config::$options['assets']['delay_js_quick_exclusions'] as $type => $items) {
						foreach($items as $key => $val) {
							if(!empty($master[$type][$key])) {
								self::$data['delay']['exclusions'] = array_merge(self::$data['delay']['exclusions'], $master[$type][$key]['exclusions']);
							}
						}
					}
				}

				//add manual exclusions
				if(!empty(Config::$options['assets']['delay_js_exclusions']) && is_array(Config::$options['assets']['delay_js_exclusions'])) {
					self::$data['delay']['exclusions'] = array_merge(self::$data['delay']['exclusions'], Config::$options['assets']['delay_js_exclusions']);
				}

				//final filter
				self::$data['delay']['exclusions'] = apply_filters('perfmatters_delay_js_exclusions', self::$data['delay']['exclusions']);
			}
		}

		//defer exclusions
		if(self::$run['defer']) {

			//base exclusions
			self::$data['defer']['exclusions'] = array(
				'perfmatters-lazy-load-js',
				'jqueryParams'
			);

			//add jquery
			if(empty(apply_filters('perfmatters_defer_jquery', !empty(Config::$options['assets']['defer_jquery'])))) {
				self::$data['defer']['exclusions'] = array_merge(self::$data['defer']['exclusions'], array('jquery.js', 'jquery.min.js'));
			}

			//add manual exclusions
			if(!empty(Config::$options['assets']['js_exclusions']) && is_array(Config::$options['assets']['js_exclusions'])) {
				self::$data['defer']['exclusions'] = array_merge(self::$data['defer']['exclusions'], Config::$options['assets']['js_exclusions']);
			}

			//final filter
			self::$data['defer']['exclusions'] = apply_filters('perfmatters_defer_js_exclusions', self::$data['defer']['exclusions']);
		}
	}

	//print inline delay js
	public static function print_delay_js() {

		$timeout = apply_filters('perfmatters_delay_js_timeout', !empty(Config::$options['assets']['delay_timeout']) ? 10 : '');

		if(!empty(apply_filters('perfmatters_delay_js_behavior', Config::$options['assets']['delay_js_behavior'] ?? ''))) {
			$delay_click = json_encode(apply_filters('perfmatters_delay_js_delay_click', empty(Config::$options['assets']['disable_click_delay'])));
		}
		else {
			$delay_click = json_encode(false);
		}

	  	if(!empty(apply_filters('perfmatters_delay_js', !empty(Config::$options['assets']['delay_js'])))) {

	  		$script = '<script id="perfmatters-delayed-scripts-js">';
	  			
				$script.= 'const pmDelayClick=' . $delay_click . ';';
				if(!empty($timeout)) {
					$script.= 'const pmDelayTimer=setTimeout(pmTriggerDOMListener,' . $timeout . '*1000);';
				}
	  			$script.= 'const pmUserInteractions=["keydown","mousedown","mousemove","wheel","touchmove","touchstart","touchend"],pmDelayedScripts={normal:[],defer:[],async:[]},jQueriesArray=[],pmInterceptedClicks=[];var pmDOMLoaded=!1,pmClickTarget="";function pmTriggerDOMListener(){"undefined"!=typeof pmDelayTimer&&clearTimeout(pmDelayTimer),pmUserInteractions.forEach(function(e){window.removeEventListener(e,pmTriggerDOMListener,{passive:!0})}),document.removeEventListener("visibilitychange",pmTriggerDOMListener),"loading"===document.readyState?document.addEventListener("DOMContentLoaded",pmTriggerDelayedScripts):pmTriggerDelayedScripts()}async function pmTriggerDelayedScripts(){pmDelayEventListeners(),pmDelayJQueryReady(),pmProcessDocumentWrite(),pmSortDelayedScripts(),pmPreloadDelayedScripts(),await pmLoadDelayedScripts(pmDelayedScripts.normal),await pmLoadDelayedScripts(pmDelayedScripts.defer),await pmLoadDelayedScripts(pmDelayedScripts.async),await pmTriggerEventListeners(),document.querySelectorAll("link[data-pmdelayedstyle]").forEach(function(e){e.setAttribute("href",e.getAttribute("data-pmdelayedstyle"))}),window.dispatchEvent(new Event("perfmatters-allScriptsLoaded")),pmReplayClicks()}function pmDelayEventListeners(){let e={};function t(t,r){function n(r){return e[t].delayedEvents.indexOf(r)>=0?"perfmatters-"+r:r}e[t]||(e[t]={originalFunctions:{add:t.addEventListener,remove:t.removeEventListener},delayedEvents:[]},t.addEventListener=function(){arguments[0]=n(arguments[0]),e[t].originalFunctions.add.apply(t,arguments)},t.removeEventListener=function(){arguments[0]=n(arguments[0]),e[t].originalFunctions.remove.apply(t,arguments)}),e[t].delayedEvents.push(r)}function r(e,t){let r=e[t];Object.defineProperty(e,t,{get:r||function(){},set:function(r){e["perfmatters"+t]=r}})}t(document,"DOMContentLoaded"),t(window,"DOMContentLoaded"),t(window,"load"),t(window,"pageshow"),t(document,"readystatechange"),r(document,"onreadystatechange"),r(window,"onload"),r(window,"onpageshow")}function pmDelayJQueryReady(){let e=window.jQuery;Object.defineProperty(window,"jQuery",{get:()=>e,set(t){if(t&&t.fn&&!jQueriesArray.includes(t)){t.fn.ready=t.fn.init.prototype.ready=function(e){pmDOMLoaded?e.bind(document)(t):document.addEventListener("perfmatters-DOMContentLoaded",function(){e.bind(document)(t)})};let r=t.fn.on;t.fn.on=t.fn.init.prototype.on=function(){if(this[0]===window){function e(e){return e=(e=(e=e.split(" ")).map(function(e){return"load"===e||0===e.indexOf("load.")?"perfmatters-jquery-load":e})).join(" ")}"string"==typeof arguments[0]||arguments[0]instanceof String?arguments[0]=e(arguments[0]):"object"==typeof arguments[0]&&Object.keys(arguments[0]).forEach(function(t){delete Object.assign(arguments[0],{[e(t)]:arguments[0][t]})[t]})}return r.apply(this,arguments),this},jQueriesArray.push(t)}e=t}})}function pmProcessDocumentWrite(){let e=new Map;document.write=document.writeln=function(t){var r=document.currentScript,n=document.createRange();let a=e.get(r);void 0===a&&(a=r.nextSibling,e.set(r,a));var i=document.createDocumentFragment();n.setStart(i,0),i.appendChild(n.createContextualFragment(t)),r.parentElement.insertBefore(i,a)}}function pmSortDelayedScripts(){document.querySelectorAll("script[type=pmdelayedscript]").forEach(function(e){e.hasAttribute("src")?e.hasAttribute("defer")&&!1!==e.defer?pmDelayedScripts.defer.push(e):e.hasAttribute("async")&&!1!==e.async?pmDelayedScripts.async.push(e):pmDelayedScripts.normal.push(e):pmDelayedScripts.normal.push(e)})}function pmPreloadDelayedScripts(){var e=document.createDocumentFragment();[...pmDelayedScripts.normal,...pmDelayedScripts.defer,...pmDelayedScripts.async].forEach(function(t){var r=t.getAttribute("src");if(r){var n=document.createElement("link");n.href=r,n.rel="preload",n.as="script",e.appendChild(n)}}),document.head.appendChild(e)}async function pmLoadDelayedScripts(e){var t=e.shift();return t?(await pmReplaceScript(t),pmLoadDelayedScripts(e)):Promise.resolve()}async function pmReplaceScript(e){return await pmNextFrame(),new Promise(function(t){let r=document.createElement("script");[...e.attributes].forEach(function(e){let t=e.nodeName;"type"!==t&&("data-type"===t&&(t="type"),r.setAttribute(t,e.nodeValue))}),e.hasAttribute("src")?(r.addEventListener("load",t),r.addEventListener("error",t)):(r.text=e.text,t()),e.parentNode.replaceChild(r,e)})}async function pmTriggerEventListeners(){pmDOMLoaded=!0,await pmNextFrame(),document.dispatchEvent(new Event("perfmatters-DOMContentLoaded")),await pmNextFrame(),window.dispatchEvent(new Event("perfmatters-DOMContentLoaded")),await pmNextFrame(),document.dispatchEvent(new Event("perfmatters-readystatechange")),await pmNextFrame(),document.perfmattersonreadystatechange&&document.perfmattersonreadystatechange(),await pmNextFrame(),window.dispatchEvent(new Event("perfmatters-load")),await pmNextFrame(),window.perfmattersonload&&window.perfmattersonload(),await pmNextFrame(),jQueriesArray.forEach(function(e){e(window).trigger("perfmatters-jquery-load")});let e=new Event("perfmatters-pageshow");e.persisted=window.pmPersisted,window.dispatchEvent(e),await pmNextFrame(),window.perfmattersonpageshow&&window.perfmattersonpageshow({persisted:window.pmPersisted})}async function pmNextFrame(){return new Promise(function(e){requestAnimationFrame(e)})}function pmClickHandler(e){e.target.removeEventListener("click",pmClickHandler),pmRenameDOMAttribute(e.target,"pm-onclick","onclick"),pmInterceptedClicks.push(e),e.preventDefault(),e.stopPropagation(),e.stopImmediatePropagation()}function pmReplayClicks(){window.removeEventListener("touchstart",pmTouchStartHandler,{passive:!0}),window.removeEventListener("mousedown",pmTouchStartHandler),pmInterceptedClicks.forEach(e=>{e.target.outerHTML===pmClickTarget&&e.target.dispatchEvent(new MouseEvent("click",{view:e.view,bubbles:!0,cancelable:!0}))})}function pmTouchStartHandler(e){"HTML"!==e.target.tagName&&(pmClickTarget||(pmClickTarget=e.target.outerHTML),window.addEventListener("touchend",pmTouchEndHandler),window.addEventListener("mouseup",pmTouchEndHandler),window.addEventListener("touchmove",pmTouchMoveHandler,{passive:!0}),window.addEventListener("mousemove",pmTouchMoveHandler),e.target.addEventListener("click",pmClickHandler),pmRenameDOMAttribute(e.target,"onclick","pm-onclick"))}function pmTouchMoveHandler(e){window.removeEventListener("touchend",pmTouchEndHandler),window.removeEventListener("mouseup",pmTouchEndHandler),window.removeEventListener("touchmove",pmTouchMoveHandler,{passive:!0}),window.removeEventListener("mousemove",pmTouchMoveHandler),e.target.removeEventListener("click",pmClickHandler),pmRenameDOMAttribute(e.target,"pm-onclick","onclick")}function pmTouchEndHandler(e){window.removeEventListener("touchend",pmTouchEndHandler),window.removeEventListener("mouseup",pmTouchEndHandler),window.removeEventListener("touchmove",pmTouchMoveHandler,{passive:!0}),window.removeEventListener("mousemove",pmTouchMoveHandler)}function pmRenameDOMAttribute(e,t,r){e.hasAttribute&&e.hasAttribute(t)&&(event.target.setAttribute(r,event.target.getAttribute(t)),event.target.removeAttribute(t))}window.addEventListener("pageshow",e=>{window.pmPersisted=e.persisted}),pmUserInteractions.forEach(function(e){window.addEventListener(e,pmTriggerDOMListener,{passive:!0})}),pmDelayClick&&(window.addEventListener("touchstart",pmTouchStartHandler,{passive:!0}),window.addEventListener("mousedown",pmTouchStartHandler)),document.addEventListener("visibilitychange",pmTriggerDOMListener);';

	  			//trigger elementor animations
	  			if(function_exists('\is_plugin_active') && \is_plugin_active('elementor/elementor.php')) {
	  				$script.= 'var pmeDeviceMode,pmeAnimationSettingsKeys,pmeCurrentAnimation;function pmeAnimation(){(pmeDeviceMode=document.createElement("span")).id="elementor-device-mode",pmeDeviceMode.setAttribute("class","elementor-screen-only"),document.body.appendChild(pmeDeviceMode),requestAnimationFrame(pmeDetectAnimations)}function pmeDetectAnimations(){pmeAnimationSettingsKeys=pmeListAnimationSettingsKeys(getComputedStyle(pmeDeviceMode,":after").content.replace(/"/g,"")),document.querySelectorAll(".elementor-invisible[data-settings]").forEach(a=>{let b=a.getBoundingClientRect();if(b.bottom>=0&&b.top<=window.innerHeight)try{pmeAnimateElement(a)}catch(c){}})}function pmeAnimateElement(a){let b=JSON.parse(a.dataset.settings),d=b._animation_delay||b.animation_delay||0,c=b[pmeAnimationSettingsKeys.find(a=>b[a])];if("none"===c)return void a.classList.remove("elementor-invisible");a.classList.remove(c),pmeCurrentAnimation&&a.classList.remove(pmeCurrentAnimation),pmeCurrentAnimation=c;let e=setTimeout(()=>{a.classList.remove("elementor-invisible"),a.classList.add("animated",c),pmeRemoveAnimationSettings(a,b)},d);window.addEventListener("perfmatters-startLoading",function(){clearTimeout(e)})}function pmeListAnimationSettingsKeys(b="mobile"){let a=[""];switch(b){case"mobile":a.unshift("_mobile");case"tablet":a.unshift("_tablet");case"desktop":a.unshift("_desktop")}let c=[];return["animation","_animation"].forEach(b=>{a.forEach(a=>{c.push(b+a)})}),c}function pmeRemoveAnimationSettings(a,b){pmeListAnimationSettingsKeys().forEach(a=>delete b[a]),a.dataset.settings=JSON.stringify(b)}document.addEventListener("DOMContentLoaded",pmeAnimation)';
				}

		  	$script.= '</script>';

	  		return $script;
	  	}
	}

	//print fastclick js
	public static function print_fastclick() {

		if(is_admin()) {
			return;
		}

		if(isset($_GET['perfmattersoff'])) {
			return;
		}

		echo '<script src="' . plugins_url('perfmatters/vendor/fastclick/pmfastclick.min.js') . '"></script>';
		echo '<script>"addEventListener"in document&&document.addEventListener("DOMContentLoaded",function(){FastClick.attach(document.body)},!1);</script>';
	}

	//return quick exclusion data array
	public static function get_quick_exclusions_master() {
		return include(PERFMATTERS_PATH . 'inc/data/delay_js_quick_exclusions.php');
	}

	//clear minified js ajax action
    public static function clear_minified_js_ajax() {

        Ajax::security_check();

        Minify::clear_minified('js');

        wp_send_json_success(array(
            'message' => __('Minified JS cleared.', 'perfmatters'), 
        ));
    }
}