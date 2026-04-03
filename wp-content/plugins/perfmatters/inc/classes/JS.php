<?php
namespace Perfmatters;

class JS
{
	private static $run = [];
	private static $data = [];
	public static $snippet_optimizations = [];

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

		//pmcs js optimizations
        self::$run['snippets'] = !empty(self::$snippet_optimizations);

		if(array_filter(self::$run)) {

			//add to buffer
			add_filter('perfmatters_output_buffer', array('Perfmatters\JS', 'optimize'));

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

		//global scripts array in case we need to update registered src
        global $wp_scripts;

		self::populate_data();

		$print_delay_js = false;

		//loop through scripts
		foreach($matches[0] as $i => $tag) {

			$atts_array = Utilities::get_atts_array($matches[2][$i] ?? '');
			
			//skip if type is not javascript
			if(isset($atts_array['type']) && !Utilities::match_in_array($atts_array['type'], array('javascript', 'module'))) {
				continue;
			}

			$delay_flag = false;
			$defer_flag = false;
			$atts_array_new = $atts_array;

			//snippet optimizations
            if(!empty(self::$snippet_optimizations) && !empty($atts_array['id'])) {
                if(isset(self::$snippet_optimizations[$atts_array['id']])) {

                    //delay flag
                    if(self::$snippet_optimizations[$atts_array['id']] == 'delay') {
                        $delay_flag = true;
                        $print_delay_js = true;
                    }
                    elseif(self::$snippet_optimizations[$atts_array['id']] == 'defer') {
                    	$defer_flag = true;
                    }
                }
            }

			//minify
			if(self::$run['minify']) {
				if(!empty($atts_array['src']) && !Utilities::match_in_array($matches[2][$i], Minify::get_exclusions('js')) && $minified_src = Minify::minify($atts_array['src'])) {
					$atts_array_new['src'] = $minified_src;

					//update registered src
                    if(!empty($atts_array['id'])) {
                        $handle = rtrim($atts_array['id'], '-js');
                        if(isset($wp_scripts->registered[$handle])) {
                            $wp_scripts->registered[$handle]->src = $minified_src;
                        }
                    }
				}
			}

			//delay
			if(self::$run['delay'] || $delay_flag) {

				if(!$delay_flag) {
					if(empty(self::$data['delay']['behavior'])) {
						$delay_flag = Utilities::match_in_array($tag, self::$data['delay']['inclusions']);
					}
					else {
						$delay_flag = !Utilities::match_in_array($tag, self::$data['delay']['exclusions']);
					}
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
			if((self::$run['defer'] || $defer_flag) && !$delay_flag) {

				//inline script
				if(empty($atts_array['src'])) {

					//script content
					if((!empty(Config::$options['assets']['defer_inline']) && !empty($matches[3][$i])) || $defer_flag) {
					
						//exclusion check
						if(!Utilities::match_in_array($tag, self::$data['defer']['exclusions']) || $defer_flag) {
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
						if(!Utilities::match_in_array($tag, self::$data['defer']['exclusions']) || $defer_flag) {
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
		if(self::$run['delay'] || $print_delay_js) {
            $pos = strpos($html, '</body>');
            if($pos !== false) {
            	$html = substr_replace($html, self::print_delay_js() . '</body>', $pos, 7);
            }
		}

		return $html;
	}

	private static function populate_data() {

		self::$data = [
			'delay' => [
				'inclusions' => [],
				'exclusions' => []
			],
			'defer' => [
				'exclusions' => []
			]
		];

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
					'pmcs-',
					'lazyload.min.js',
					'lazyLoadInstance',
					'lazysizes',
					'customize-support',
					'fastclick',
					'jqueryParams',
					'et_link_options_data',
					'document.write(',
					'cmp.min.js', //ezoic
					'sa.min.js',
					'ShowAds',
					'ezstandalone',
					'ezoic'
				);

				//add quick exclusions
				if(!empty(Config::$options['assets']['delay_js_quick_exclusions'])) {

				    $master = self::get_quick_exclusions_master();
				    $delay_defer_exclusions = array();

					foreach(Config::$options['assets']['delay_js_quick_exclusions'] as $type => $items) {
						foreach($items as $key => $val) {
							if(!empty($master[$type][$key])) {
								self::$data['delay']['exclusions'] = array_merge(self::$data['delay']['exclusions'], $master[$type][$key]['exclusions']);

								//save deferral exclusions if needed
								if(!empty($master[$type][$key]['deferral_exclusions'])) {
									$delay_defer_exclusions = array_merge($delay_defer_exclusions, $master[$type][$key]['deferral_exclusions']);
								}
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
				'pmcs-',
				'jqueryParams',
				'cmp.min.js', //ezoic
				'sa.min.js',
				'ShowAds',
				'ezstandalone',
				'ezoic',
				'cloudflare.com/turnstile' //turnstile
			);

			//add deferral exclusions from delay quick exclusions
			if(!empty($delay_defer_exclusions)) {
				self::$data['defer']['exclusions'] = array_merge(self::$data['defer']['exclusions'], $delay_defer_exclusions);
			}

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

		$timeout = apply_filters('perfmatters_delay_js_timeout', !empty(Config::$options['assets']['delay_timeout']) ? 15 : '');

		if(self::$run['delay'] && (!empty(apply_filters('perfmatters_delay_js_behavior', Config::$options['assets']['delay_js_behavior'] ?? '')))) {
			$delay_click = (int)apply_filters('perfmatters_delay_js_delay_click', empty(Config::$options['assets']['disable_click_delay']));
		}
		else {
			$delay_click = 0;
		}

	  	//if(!empty(apply_filters('perfmatters_delay_js', !empty(Config::$options['assets']['delay_js'])))) {

	  		$script = '<script id="perfmatters-delayed-scripts-js">';

	  			$script.= '(function(){';

		  			$script.= 'window.pmDC=' . $delay_click . ';';
		  			if(!empty($timeout)) {
		  				$script.= 'window.pmDT=' . $timeout . ';';
		  			}

	  				$script.= 'if(window.pmDT){var e=setTimeout(d,window.pmDT*1e3)}const t=["keydown","mousedown","mousemove","wheel","touchmove","touchstart","touchend"];const n={normal:[],defer:[],async:[]};const o=[];const i=[];var r=false;var a="";window.pmIsClickPending=false;t.forEach(function(e){window.addEventListener(e,d,{passive:true})});if(window.pmDC){window.addEventListener("touchstart",b,{passive:true});window.addEventListener("mousedown",b)}function d(){if(typeof e!=="undefined"){clearTimeout(e)}t.forEach(function(e){window.removeEventListener(e,d,{passive:true})});if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",s)}else{s()}}async function s(){c();u();f();m();await w(n.normal);await w(n.defer);await w(n.async);await p();document.querySelectorAll("link[data-pmdelayedstyle]").forEach(function(e){e.setAttribute("href",e.getAttribute("data-pmdelayedstyle"))});window.dispatchEvent(new Event("perfmatters-allScriptsLoaded")),E().then(()=>{h()})}function c(){let o={};function e(t,e){function n(e){return o[t].delayedEvents.indexOf(e)>=0?"perfmatters-"+e:e}if(!o[t]){o[t]={originalFunctions:{add:t.addEventListener,remove:t.removeEventListener},delayedEvents:[]};t.addEventListener=function(){arguments[0]=n(arguments[0]);o[t].originalFunctions.add.apply(t,arguments)};t.removeEventListener=function(){arguments[0]=n(arguments[0]);o[t].originalFunctions.remove.apply(t,arguments)}}o[t].delayedEvents.push(e)}function t(t,n){const e=t[n];Object.defineProperty(t,n,{get:!e?function(){}:e,set:function(e){t["perfmatters"+n]=e}})}e(document,"DOMContentLoaded");e(window,"DOMContentLoaded");e(window,"load");e(document,"readystatechange");t(document,"onreadystatechange");t(window,"onload")}function u(){let n=window.jQuery;Object.defineProperty(window,"jQuery",{get(){return n},set(t){if(t&&t.fn&&!o.includes(t)){t.fn.ready=t.fn.init.prototype.ready=function(e){if(r){e.bind(document)(t)}else{document.addEventListener("perfmatters-DOMContentLoaded",function(){e.bind(document)(t)})}};const e=t.fn.on;t.fn.on=t.fn.init.prototype.on=function(){if(this[0]===window){function t(e){e=e.split(" ");e=e.map(function(e){if(e==="load"||e.indexOf("load.")===0){return"perfmatters-jquery-load"}else{return e}});e=e.join(" ");return e}if(typeof arguments[0]=="string"||arguments[0]instanceof String){arguments[0]=t(arguments[0])}else if(typeof arguments[0]=="object"){Object.keys(arguments[0]).forEach(function(e){delete Object.assign(arguments[0],{[t(e)]:arguments[0][e]})[e]})}}return e.apply(this,arguments),this};o.push(t)}n=t}})}function f(){document.querySelectorAll("script[type=pmdelayedscript]").forEach(function(e){if(e.hasAttribute("src")){if(e.hasAttribute("defer")&&e.defer!==false){n.defer.push(e)}else if(e.hasAttribute("async")&&e.async!==false){n.async.push(e)}else{n.normal.push(e)}}else{n.normal.push(e)}})}function m(){var o=document.createDocumentFragment();[...n.normal,...n.defer,...n.async].forEach(function(e){var t=e.getAttribute("src");if(t){var n=document.createElement("link");n.href=t;if(e.getAttribute("data-perfmatters-type")=="module"){n.rel="modulepreload"}else{n.rel="preload";n.as="script"}o.appendChild(n)}});document.head.appendChild(o)}async function w(e){var t=e.shift();if(t){await l(t);return w(e)}return Promise.resolve()}async function l(t){await v();return new Promise(function(e){const n=document.createElement("script");[...t.attributes].forEach(function(e){let t=e.nodeName;if(t!=="type"){if(t==="data-perfmatters-type"){t="type"}n.setAttribute(t,e.nodeValue)}});if(t.hasAttribute("src")){n.addEventListener("load",e);n.addEventListener("error",e)}else{n.text=t.text;e()}t.parentNode?t.parentNode.replaceChild(n,t):e()})}async function p(){r=true;await v();document.dispatchEvent(new Event("perfmatters-DOMContentLoaded"));await v();window.dispatchEvent(new Event("perfmatters-DOMContentLoaded"));await v();document.dispatchEvent(new Event("perfmatters-readystatechange"));await v();if(document.perfmattersonreadystatechange){document.perfmattersonreadystatechange()}await v();window.dispatchEvent(new Event("perfmatters-load"));await v();if(window.perfmattersonload){window.perfmattersonload()}await v();o.forEach(function(e){e(window).trigger("perfmatters-jquery-load")})}async function v(){return new Promise(function(e){requestAnimationFrame(e)})}function h(){window.removeEventListener("touchstart",b,{passive:true});window.removeEventListener("mousedown",b);i.forEach(e=>{if(e.target.outerHTML===a){e.target.dispatchEvent(new MouseEvent("click",{view:e.view,bubbles:true,cancelable:true}))}})}function E(){return new Promise(e=>{window.pmIsClickPending?g=e:e()})}function y(){window.pmIsClickPending=true}function g(){window.pmIsClickPending=false}function L(e){e.target.removeEventListener("click",L);C(e.target,"pm-onclick","onclick");i.push(e),e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();g()}function b(e){if(e.target.tagName!=="HTML"){if(!a){a=e.target.outerHTML}window.addEventListener("touchend",A);window.addEventListener("mouseup",A);window.addEventListener("touchmove",k,{passive:true});window.addEventListener("mousemove",k);e.target.addEventListener("click",L);C(e.target,"onclick","pm-onclick");y()}}function k(e){window.removeEventListener("touchend",A);window.removeEventListener("mouseup",A);window.removeEventListener("touchmove",k,{passive:true});window.removeEventListener("mousemove",k);e.target.removeEventListener("click",L);C(e.target,"pm-onclick","onclick");g()}function A(e){window.removeEventListener("touchend",A);window.removeEventListener("mouseup",A);window.removeEventListener("touchmove",k,{passive:true});window.removeEventListener("mousemove",k)}function C(e,t,n){if(e.hasAttribute&&e.hasAttribute(t)){event.target.setAttribute(n,event.target.getAttribute(t));event.target.removeAttribute(t)}}';

	  			$script.= '})();';

	  			//trigger elementor animations
	  			if(function_exists('\is_plugin_active') && \is_plugin_active('elementor/elementor.php')) {
	  				$script.= '(function(){var e,a,s;function t(){(e=document.createElement("span")).id="elementor-device-mode",e.setAttribute("class","elementor-screen-only"),document.body.appendChild(e),requestAnimationFrame(n)}function n(){a=o(getComputedStyle(e,":after").content.replace(/"/g,"")),document.querySelectorAll(".elementor-invisible[data-settings]").forEach(e=>{let t=e.getBoundingClientRect();if(t.bottom>=0&&t.top<=window.innerHeight)try{i(e)}catch(e){}})}function i(e){let t=JSON.parse(e.dataset.settings),n=t._animation_delay||t.animation_delay||0,i=t[a.find(e=>t[e])];if("none"===i)return void e.classList.remove("elementor-invisible");e.classList.remove(i),s&&e.classList.remove(s),s=i;let o=setTimeout(()=>{e.classList.remove("elementor-invisible"),e.classList.add("animated",i),l(e,t)},n);window.addEventListener("perfmatters-startLoading",function(){clearTimeout(o)})}function o(e="mobile"){let n=[""];switch(e){case"mobile":n.unshift("_mobile");case"tablet":n.unshift("_tablet");case"desktop":n.unshift("_desktop")}let i=[];return["animation","_animation"].forEach(t=>{n.forEach(e=>{i.push(t+e)})}),i}function l(e,t){o().forEach(e=>delete t[e]),e.dataset.settings=JSON.stringify(t)}document.addEventListener("DOMContentLoaded",t)})();';
				}

		  	$script.= '</script>';

	  		return $script;
	  	//}
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