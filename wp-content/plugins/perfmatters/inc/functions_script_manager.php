<?php

//actions and filters
if(!empty($perfmatters_extras['script_manager'])) {
	add_action('admin_bar_menu', 'perfmatters_script_manager_admin_bar', 1000);
	add_filter('post_row_actions', 'perfmatters_script_manager_row_actions', 10, 2);
	add_filter('page_row_actions', 'perfmatters_script_manager_row_actions', 10, 2);
	add_action('wp_footer', 'perfmatters_script_manager', 1000);
	add_action('script_loader_src', 'perfmatters_dequeue_scripts', 1000, 2);
	add_action('style_loader_src', 'perfmatters_dequeue_scripts', 1000, 2);
	add_action('update_option_perfmatters_script_manager_settings', 'perfmatters_script_manager_update_option', 10, 3);
	add_action('wp_enqueue_scripts', 'perfmatters_script_manager_scripts');
	add_action('init', 'perfmatters_script_manager_force_admin_bar');
	add_action('wp_ajax_pmsm_save', 'perfmatters_script_manager_update');
	add_action('admin_notices', 'perfmatters_script_manager_mu_notice');
	add_filter('autoptimize_filter_js_exclude', 'perfmatters_script_manager_exclude_autoptimize');
}

//Script Manager Admin Bar Link
function perfmatters_script_manager_admin_bar($wp_admin_bar) {

	//check for proper access
	if(!current_user_can('manage_options') || !perfmatters_network_access()) {
		return;
	}

	if(is_admin()) {

		if(function_exists('get_current_screen')) {
			$current_screen = get_current_screen();
			$permalink = get_permalink();
			if($current_screen->base == 'post' && $current_screen->action != 'add' && !empty($permalink)) {

				global $post;

				//get public post types
				$post_types = get_post_types(array('public' => true));

				if(!empty($post->post_type) && in_array($post->post_type, $post_types)) {

					$href = add_query_arg('perfmatters', '', $permalink);
					$menu_text = __('Script Manager', 'perfmatters');
				}
				else {
					return;
				}
			}
			else {
				return;
			}
		}
		else {
			return;
		}
	}
	else {
		global $wp;

		$href = add_query_arg(str_replace(array('&perfmatters', 'perfmatters'), '', $_SERVER['QUERY_STRING']), '', home_url($wp->request));

		if(!isset($_GET['perfmatters'])) {
			$href.= !empty($_SERVER['QUERY_STRING']) ? '&perfmatters' : '?perfmatters';
			$menu_text = __('Script Manager', 'perfmatters');
		}
		else {
			$menu_text = __('Close Script Manager', 'perfmatters');
		}
	}

	//build node and add to admin bar
	if(!empty($menu_text) && !empty($href)) {
		$args = array(
			'id'    => 'perfmatters_script_manager',
			'title' => $menu_text,
			'href'  => $href
		);
		$wp_admin_bar->add_node($args);
	}
}

//script manage links in row actions
function perfmatters_script_manager_row_actions($actions, $post) {

	//check for proper access
	if(!current_user_can('manage_options') || !perfmatters_network_access()) {
		return $actions;
	}

	//get post permalink
	$permalink = get_permalink($post->ID);

	if(!empty($permalink)) {

		//get public post types
		$post_types = get_post_types(array('public' => true));

		if(!empty($post->post_type) && in_array($post->post_type, $post_types)) {

			//add perfmatters query arg
	    	$script_manager_link = add_query_arg('perfmatters', '', $permalink);

	    	//merge link array with existing row actions
		    $actions = array_merge($actions, array(
		        'script_manager' => sprintf('<a href="%1$s">%2$s</a>', esc_url($script_manager_link), __('Script Manager', 'perfmatters'))
		    ));
		}
	}
 
    return $actions;
}

//Script Manager Front End
function perfmatters_script_manager() {
	include('script_manager.php');
}

//Script Manager Force Admin Bar
function perfmatters_script_manager_force_admin_bar() {
	if(!current_user_can('manage_options') || is_admin() || !isset($_GET['perfmatters']) || !perfmatters_network_access() || is_admin_bar_showing()) {
		return;
	}
	add_filter('show_admin_bar', '__return_true' , 9999);
}

//Script Manager Scripts
function perfmatters_script_manager_scripts() {
	if(!current_user_can('manage_options') || is_admin() || !isset($_GET['perfmatters']) || !perfmatters_network_access()) {
		return;
	}

	wp_register_script('perfmatters-script-manager-js', plugins_url('js/script-manager.js', dirname(__FILE__)), array('jquery-core'), PERFMATTERS_VERSION);
	wp_enqueue_script('perfmatters-script-manager-js');

	//pass some data to our js file
	$pmsm = array(
		'currentID' => perfmatters_get_current_ID(),
		'ajaxURL'   => admin_url('admin-ajax.php'),
		'messages'  => array(
			'buttonSave'     => __('Save', 'perfmatters'),
			'buttonSaving'   => __('Saving', 'perfmatters'),
			'updateSuccess'  => __('Settings saved successfully!', 'perfmatters'),
			'updateFailure'  => __('Settings failed to update.', 'perfmatters'),
			'updateNoChange' => __('No options were changed.', 'perfmatters')
		)
	);
	wp_localize_script('perfmatters-script-manager-js', 'pmsm', $pmsm);
}

//create array of all assets for the script manager
function perfmatters_script_manager_load_master_array() {

	if(!function_exists('get_plugins')) {
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	global $wp_scripts;
	global $wp_styles;
	global $perfmatters_script_manager_settings;

	$master_array = array('plugins' => array(), 'themes' => array(), 'misc' => array());

	//mu mode
	if(!empty($perfmatters_script_manager_settings['mu_mode'])) {

		//grab global from mu plugin file
		global $pmsm_active_plugins;

		if(!empty($pmsm_active_plugins)) {

			foreach($pmsm_active_plugins as $key => $path) {

				$explode = explode('/', $path);

				$data = get_plugins("/" . $explode[0]);

				$master_array['plugins'][$explode[0]] = array('name' => $data[key($data)]['Name']);
			}
		}
	}

	$perfmatters_filters = array(
		"js" => array (
			"title" => "JS",
			"scripts" => $wp_scripts
		),
		"css" => array(
			"title" => "CSS",
			"scripts" => $wp_styles
		)
	);

	$loaded_plugins = array();
	$loaded_themes = array();

	foreach($perfmatters_filters as $type => $data) {

		if(!empty($data["scripts"]->done)) {
			$plug_org_scripts = array_unique($data["scripts"]->done);

			uasort($plug_org_scripts, function($a, $b) use ($type) {
				global $perfmatters_filters;
			    if($perfmatters_filters[$type]['scripts']->registered[$a]->src == $perfmatters_filters[$type]['scripts']->registered[$b]->src) {
			        return 0;
			    }
			    return ($perfmatters_filters[$type]['scripts']->registered[$a]->src < $perfmatters_filters[$type]['scripts']->registered[$b]->src) ? -1 : 1;
			});

			foreach($plug_org_scripts as $key => $val) {
				$src = $perfmatters_filters[$type]['scripts']->registered[$val]->src;

				if(strpos($src, "/wp-content/plugins/") !== false) {
					$explode = explode("/wp-content/plugins/", $src);
					$explode = explode('/', $explode[1]);
					if(!array_key_exists($explode[0], $loaded_plugins)) {
						$file_plugin = get_plugins('/' . $explode[0]);
						$loaded_plugins[$explode[0]] = $file_plugin;
						$master_array['plugins'][$explode[0]] = array('name' => $file_plugin[key($file_plugin)]['Name']);
					}
					else {
						$file_plugin = $loaded_plugins[$explode[0]];
					}
			    	$master_array['plugins'][$explode[0]]['assets'][] = array('type' => $type, 'handle' => $val);
			    }
			    elseif(strpos($src, "/wp-content/themes/") !== false) {
					$explode = explode("/wp-content/themes/", $src);
					$explode = explode('/', $explode[1]);
					if(!array_key_exists($explode[0], $loaded_themes)) {
						$file_theme = wp_get_theme('/' . $explode[0]);
						$loaded_themes[$explode[0]] = $file_theme;
						$master_array['themes'][$explode[0]] = array('name' => $file_theme->get('Name'));
					}
					else {
						$file_theme = $loaded_themes[$explode[0]];
					}
					
			    	$master_array['themes'][$explode[0]]['assets'][] = array('type' => $type, 'handle' => $val);
			    }
			    else {
			    	$master_array['misc'][] = array('type' => $type, 'handle' => $val);
			    }
			}
		}
	}

	//don't show perfmatters in the list
	if(isset($master_array['plugins']['perfmatters'])) {
		unset($master_array['plugins']['perfmatters']);
	}

	return $master_array;
}

//print script manager section
function perfmatters_script_manager_print_section($category, $group, $scripts = false) {
	global $perfmatters_script_manager_options;
	global $currentID;
	$options = $perfmatters_script_manager_options;
	$settings = get_option('perfmatters_script_manager_settings');

	$mu_mode = !empty($settings['mu_mode']) && $category == 'plugins';

	$statusDisabled = false;
	if(isset($options['disabled'][$category][$group]['everywhere']) || (isset($options['disabled'][$category][$group]['current']) && in_array($currentID, $options['disabled'][$category][$group]['current'], TRUE)) || !empty($options['disabled'][$category][$group]['regex']) || (!empty($options['disabled'][$category][$group]['404']) && $currentID === 'pmsm-404' && !$mu_mode)) {
		$statusDisabled = true;
	}

	echo "<div class='perfmatters-script-manager-section'>";
		if(!empty($scripts)) {
			echo "<table " . ($statusDisabled ? "style='display: none;'" : "") . ">";
				echo "<thead>";
					echo "<tr>";
						echo "<th style='width: 120px;'>" . __('Status', 'perfmatters') . "</th>";
						echo "<th style=''>" . __('Script', 'perfmatters') . "</th>";
						echo "<th style='width: 100px; text-align: center;'>" . __('Type', 'perfmatters') . "</th>";
						echo "<th style='width: 100px; text-align: center;'>" . __('Size', 'perfmatters') . "</th>";
					echo "</tr>";
				echo "</thead>";
				echo "<tbody>";
					foreach($scripts as $key => $details) {
						perfmatters_script_manager_print_script($category, $group, $details['handle'], $details['type']);
					}
				echo "</tbody>";
			echo "</table>";
		}

		if($category != "misc") {
			
			echo "<div class='perfmatters-script-manager-assets-disabled' " . (!$statusDisabled ? "style='display: none;'" : "") . ">";
				echo "<div class='perfmatters-script-manager-controls'>";

					//Disable
					perfmatters_script_manager_print_disable($category, $group);

					//Enable
					perfmatters_script_manager_print_enable($category, $group);

				echo "</div>";

				//group disabled message
				if($mu_mode) {
					echo "<p>" . __('MU Mode is currently enabled, the above settings will apply to the entire plugin.', 'perfmatters') . "</p>";
				}
				else {
					echo "<p>" . __('The above settings will apply to all assets in this group.', 'perfmatters') . "</p>";
				}
				
			echo "</div>";
		}
	echo "</div>";
}

//print script manager script
function perfmatters_script_manager_print_script($category, $group, $script, $type) {

	global $perfmatters_extras;
	global $perfmatters_script_manager_settings;
	global $perfmatters_filters;
	global $perfmatters_disables;
	global $perfmatters_script_manager_options;
	global $currentID;
	global $statusDisabled;
	global $pmsm_jquery_disabled;

	$options = $perfmatters_script_manager_options;

	$data = $perfmatters_filters[$type];

	if(!empty($data["scripts"]->registered[$script]->src)) {

		//Check for disables already set
		if(!empty($perfmatters_disables)) {
			foreach($perfmatters_disables as $key => $val) {
				if(strpos($data["scripts"]->registered[$script]->src, $val) !== false) {
					return;
				}
			}
		}

		$handle = $data["scripts"]->registered[$script]->handle;
		echo "<tr>";	

			//Status
			echo "<td class='perfmatters-script-manager-status'>";

				perfmatters_script_manager_print_status($type, $handle);

			echo "</td>";

			//Script Cell
			echo "<td class='perfmatters-script-manager-script'>";

				//Script Handle
				echo "<span>" . $handle . "</span>";

				//Script Path
				echo "<a href='" . $data["scripts"]->registered[$script]->src . "' target='_blank'>" . str_replace(get_home_url(), '', $data["scripts"]->registered[$script]->src) . "</a>";

				echo "<div class='perfmatters-script-manager-controls' " . (!$statusDisabled ? "style='display: none;'" : "") . ">";

					//Disable
					perfmatters_script_manager_print_disable($type, $handle);

					//Enable
					perfmatters_script_manager_print_enable($type, $handle);

				echo "</div>";

				//jquery override message
				if($type == 'js' && $handle == 'jquery-core' && $pmsm_jquery_disabled) {
					echo "<div id='jquery-message'>jQuery has been temporarily enabled in order for the Script Manager to function properly.</div>";
				}
				
			echo "</td>";

			//Type
			echo "<td class='perfmatters-script-manager-type'>";
				if(!empty($type)) {
					echo $type;
				}
			echo "</td>";

			//Size					
			echo "<td class='perfmatters-script-manager-size'>";
				if(file_exists(ABSPATH . str_replace(get_home_url(), '', $data["scripts"]->registered[$script]->src))) {
					echo round(filesize(ABSPATH . str_replace(get_home_url(), '', $data["scripts"]->registered[$script]->src)) / 1024, 1 ) . ' KB';
				}
			echo "</td>";

		echo "</tr>";

	}
}

//print status toggle
function perfmatters_script_manager_print_status($type, $handle) {
	global $perfmatters_extras;
	global $perfmatters_script_manager_options;
	global $currentID;
	$options = $perfmatters_script_manager_options;
	$settings = get_option('perfmatters_script_manager_settings');

	$mu_mode = !empty($settings['mu_mode']) && $type == 'plugins';

	global $statusDisabled;
	$statusDisabled = false;

	//get disabled status
	if(isset($options['disabled'][$type][$handle]['everywhere']) || (isset($options['disabled'][$type][$handle]['current']) && in_array($currentID, $options['disabled'][$type][$handle]['current'], TRUE)) || !empty($options['disabled'][$type][$handle]['regex']) || (!empty($options['disabled'][$type][$handle]['404']) && $currentID === 'pmsm-404' && !$mu_mode)) {
		$statusDisabled = true;
	}

	//mu mode label
	if($mu_mode) {
		echo "<span class='pmsm-mu-mode-badge'" . (!$statusDisabled ? " style='display: none;'" : "") . ">" . __('MU Mode', 'perfmatters') . "</span>";
	}

	//print status input
	if(!empty($perfmatters_extras['accessibility_mode']) && $perfmatters_extras['accessibility_mode'] == "1") {
		echo "<select name='pmsm_status[" . $type . "][" . $handle . "]' class='perfmatters-status-select " . ($statusDisabled ? "disabled" : "") . "'>";
			echo "<option value='enabled' class='perfmatters-option-enabled'>" . __('ON', 'perfmatters') . "</option>";
			echo "<option value='disabled' class='perfmatters-option-everywhere' " . ($statusDisabled ? "selected" : "") . ">" . __('OFF', 'perfmatters') . "</option>";
		echo "</select>";
	}
	else {
		echo "<div class='pmsm-checkbox-container'>";
			echo "<input type='hidden' name='pmsm_status[" . $type . "][" . $handle . "]' value='enabled' />";
	        echo "<label for='pmsm_status_" . $type . "_" . $handle . "' class='perfmatters-script-manager-switch'>";
	        	echo "<input type='checkbox' id='pmsm_status_" . $type . "_" . $handle . "' name='pmsm_status[" . $type . "][" . $handle . "]' value='disabled' " . ($statusDisabled ? "checked" : "") . " class='perfmatters-status-toggle'>";
	        	echo "<div class='perfmatters-script-manager-slider'></div>";
	       	echo "</label>";
	    echo "</div>";
	}
}

//print disable options
function perfmatters_script_manager_print_disable($type, $handle) {
	global $perfmatters_script_manager_settings;
	global $perfmatters_script_manager_options;
	global $currentID;
	$options = $perfmatters_script_manager_options;

	echo "<div class='perfmatters-script-manager-disable'>";
		echo "<div style='font-size: 16px;'>" . __('Disabled', 'perfmatters') . "</div>";
		echo "<label for='pmsm_disabled-" . $type . "-" . $handle . "-everywhere'>";
			echo "<input type='radio' name='pmsm_disabled[" . $type . "][" . $handle . "]' id='pmsm_disabled-" . $type . "-" . $handle . "-everywhere' class='perfmatters-disable-select' value='everywhere' ";
			echo (!empty($options['disabled'][$type][$handle]['everywhere']) ? "checked" : "");
			echo " />";
			echo __('Everywhere', 'perfmatters');
		echo "</label>";

		if(!empty($currentID) || $currentID === 0) {

			//404 check
			if($currentID === "pmsm-404") {
				if(empty($perfmatters_script_manager_settings['mu_mode']) || $type != 'plugins') {

					echo "<label for='pmsm_disabled-" . $type . "-" . $handle . "-404'>";
						echo "<input type='radio' name='pmsm_disabled[" . $type . "][" . $handle . "]' id='pmsm_disabled-" . $type . "-" . $handle . "-404' class='perfmatters-disable-select' value='404' ";
						echo (!empty($options['disabled'][$type][$handle]['404']) ? "checked" : "");
						echo " />";
						echo __("404 Template", 'perfmatters');
					echo "</label>";
				}
			}
			else {

				echo "<label for='pmsm_disabled-" . $type . "-" . $handle . "-current'>";
					echo "<input type='radio' name='pmsm_disabled[" . $type . "][" . $handle . "]' id='pmsm_disabled-" . $type . "-" . $handle . "-current' class='perfmatters-disable-select' value='current' ";
					echo (isset($options['disabled'][$type][$handle]['current']) && in_array($currentID, $options['disabled'][$type][$handle]['current'], true) ? "checked" : "");
					echo " />";
					echo __("Current URL", 'perfmatters');
				echo "</label>";
			}
		}

		echo "<label for='pmsm_disabled-" . $type . "-" . $handle . "-regex'>";
			echo "<input type='radio' name='pmsm_disabled[" . $type . "][" . $handle . "]' id='pmsm_disabled-" . $type . "-" . $handle . "-regex' class='perfmatters-disable-select' value='regex' ";
			echo (!empty($options['disabled'][$type][$handle]['regex']) ? "checked" : "");
			echo " />";
			echo __('Regex', 'perfmatters');
		echo "</label>";

		echo "<div class='pmsm-disable-regex'" . (empty($options['disabled'][$type][$handle]['regex']) ? " style='display: none;'" : "") . ">";
			echo "<label for='pmsm_disabled-" . $type . "-" . $handle . "-regex-value'>";
				echo "<span style='display: block; font-size: 10px; font-weight: bold; margin: 5px 0px 0px 0px;'>" . __('Regex', 'perfmatters') . "</span>";
				echo "<input type='text' name='pmsm_disabled[" . $type . "][" . $handle . "][regex]' id='pmsm_disabled-" . $type . "-" . $handle . "-regex-value' value='" . (!empty($options['disabled'][$type][$handle]['regex']) ? esc_attr($options['disabled'][$type][$handle]['regex']) : "") . "' />";
			echo "</label>";
		echo "</div>";
	echo "</div>";
}

//print enable options
function perfmatters_script_manager_print_enable($type, $handle) {
	global $perfmatters_script_manager_settings;
	global $perfmatters_script_manager_options;
	global $currentID;

	$options = $perfmatters_script_manager_options;

	echo "<div class='perfmatters-script-manager-enable'"; if(empty($options['disabled'][$type][$handle]['everywhere'])) { echo " style='display: none;'"; } echo">";

		echo "<div style='font-size: 16px;'>" . __('Exceptions', 'perfmatters') . "</div>";

		//Current URL
		if(!empty($currentID) || $currentID === 0) {

			//404 check
			if($currentID === "pmsm-404") {
				if(empty($perfmatters_script_manager_settings['mu_mode']) || $type != 'plugins') {

					echo "<div class='pmsm-checkbox-container'>";
						echo "<input type='hidden' name='pmsm_enabled[" . $type . "][" . $handle . "][404]' value='' />";
						echo "<label for='" . $type . "-" . $handle . "-enable-404'>";
							echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][404]' id='" . $type . "-" . $handle . "-enable-404' value='404' ";
								if(!empty($options['enabled'][$type][$handle]['404'])) {
									echo "checked";
								}
							echo " />";
							echo __("404 Template", 'perfmatters');
						echo "</label>";
					echo "</div>";
				}
			}
			else {

				echo "<div class='pmsm-checkbox-container'>";
					echo "<input type='hidden' name='pmsm_enabled[" . $type . "][" . $handle . "][current]' value='' />";
					echo "<label for='" . $type . "-" . $handle . "-enable-current'>";
						echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][current]' id='" . $type . "-" . $handle . "-enable-current' value='" . $currentID ."' ";
							if(isset($options['enabled'][$type][$handle]['current'])) {
								if(in_array($currentID, $options['enabled'][$type][$handle]['current'])) {
									echo "checked";
								}
							}
						echo " />";
						echo __("Current URL", 'perfmatters');
					echo "</label>";
				echo "</div>";
			}
		}

		//Post Types
		$post_types = get_post_types(array('public' => true), 'objects', 'and');
		if(!empty($post_types)) {
			if(isset($post_types['attachment'])) {
				unset($post_types['attachment']);
			}
			echo "<span style='display: block; font-size: 10px; font-weight: bold; margin: 0px;'>Post Types:</span>";
			echo "<div class='pmsm-checkbox-container'>";
				echo "<input type='hidden' name='pmsm_enabled[" . $type . "][" . $handle . "][post_types]' value='' />";
				foreach($post_types as $key => $value) {
					echo "<label for='" . $type . "-" . $handle . "-enable-" . $key . "'>";
						echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][post_types][]' id='" . $type . "-" . $handle . "-enable-" . $key . "' value='" . $key ."' ";
							if(isset($options['enabled'][$type][$handle]['post_types'])) {
								if(in_array($key, $options['enabled'][$type][$handle]['post_types'])) {
									echo "checked";
								}
							}
						echo " />" . $value->label;
					echo "</label>";
				}
			echo "</div>";
		}

		//Archives
		if(!empty($perfmatters_script_manager_settings['separate_archives']) && (empty($perfmatters_script_manager_settings['mu_mode']) || $type != 'plugins')) {
			echo "<span style='display: block; font-size: 10px; font-weight: bold; margin: 0px;'>Archives:</span>";
			echo "<div class='pmsm-checkbox-container'>";
				echo "<input type='hidden' name='pmsm_enabled[" . $type . "][" . $handle . "][archives]' value='' />";

				//Built-In Tax Archives
				//$wp_archives = array('category' => 'Categories', 'post_tag' => 'Tags', 'author' => 'Authors', 'date' => 'Dates');
				$wp_archives = array('category' => 'Categories', 'post_tag' => 'Tags', 'author' => 'Authors');
				foreach($wp_archives as $key => $value) {
					echo "<label for='" . $type . "-" . $handle . "-enable-archive-" . $key . "' title='" . $key . " (WordPress Taxonomy Archive)'>";
						echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][archives][]' id='" . $type . "-" . $handle . "-enable-archive-" . $key . "' value='" . $key ."' ";
							if(isset($options['enabled'][$type][$handle]['archives'])) {
								if(in_array($key, $options['enabled'][$type][$handle]['archives'])) {
									echo "checked";
								}
							}
						echo " />" . $value;
					echo "</label>";
				}

				//Custom Tax Archives
				$taxonomies = get_taxonomies(array('public' => true, '_builtin' => false), 'objects', 'and');
				if(!empty($taxonomies)) {
					foreach($taxonomies as $key => $value) {
						echo "<label for='" . $type . "-" . $handle . "-enable-archive-" . $key . "' title='" . $key . " (Custom Taxonomy Archive)'>";
							echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][archives][]' id='" . $type . "-" . $handle . "-enable-archive-" . $key . "' value='" . $key ."' ";
								if(isset($options['enabled'][$type][$handle]['archives'])) {
									if(in_array($key, $options['enabled'][$type][$handle]['archives'])) {
										echo "checked";
									}
								}
							echo " />" . $value->label;
						echo "</label>";
					}
				}

				//Post Type Archives
				$archive_post_types = get_post_types(array('public' => true, 'has_archive' => true), 'objects', 'and');
				if(!empty($archive_post_types)) {
					foreach($archive_post_types as $key => $value) {
						echo "<label for='" . $type . "-" . $handle . "-enable-archive-" . $key . "' title='" . $key . " (Post Type Archive)'>";
							echo "<input type='checkbox' name='pmsm_enabled[" . $type . "][" . $handle . "][archives][]' id='" . $type . "-" . $handle . "-enable-archive-" . $key . "' value='" . $key ."' ";
								if(isset($options['enabled'][$type][$handle]['archives'])) {
									if(in_array($key, $options['enabled'][$type][$handle]['archives'])) {
										echo "checked";
									}
								}
							echo " />" . $value->label;
						echo "</label>";
					}
				}
			echo "</div>";
		}

		//Regex
		echo "<div class='pmsm-enable-regex'>";
			echo "<label for='" . $type . "-" . $handle . "-enable-regex-value'>";
				echo "<span style='display: block; font-size: 10px; font-weight: bold; margin: 0px;'>" . __('Regex', 'perfmatters') . "</span>";
				echo "<input type='text' name='pmsm_enabled[" . $type . "][" . $handle . "][regex]' id='" . $type . "-" . $handle . "enable-regex-value' value='" . (!empty($options['enabled'][$type][$handle]['regex']) ? esc_attr($options['enabled'][$type][$handle]['regex']) : "") . "' />";
			echo "</label>";
		echo "</div>";

	echo "</div>";
}

//script manager update funciton triggered by ajax call
function perfmatters_script_manager_update() {

	if(!empty($_POST['pmsm_data'])) {

		//parse the data
		$pmsm_data = array();
		parse_str($_POST['pmsm_data'], $pmsm_data);

		//grab current ID
		if(isset($_POST['current_id'])) {
			if($_POST['current_id'] === 'pmsm-404') {
				$currentID = $_POST['current_id'];
			}
			else {
				$currentID = (int)$_POST['current_id'];
			}
		}
		else {
			$currentID = "";
		}

		//get script manager settings
		$settings = get_option('perfmatters_script_manager_settings');

		//get existing script manager options
		$options = get_option('perfmatters_script_manager');

		//setup filters to walk through
		$perfmatters_filters = array("js", "css", "plugins", "themes");

		foreach($perfmatters_filters as $type) {

			//check status array
			if(isset($pmsm_data['pmsm_status'][$type])) {
				foreach($pmsm_data['pmsm_status'][$type] as $handle => $status) {

					//status toggle was enabled
					if($status == 'enabled') {

						//remove current url disable
						if(isset($options['disabled'][$type][$handle]['current'])) {
							$current_key = array_search($currentID, $options['disabled'][$type][$handle]['current']);
							if($current_key !== false) {
								unset($options['disabled'][$type][$handle]['current'][$current_key]);
							}
						}

						//remove current url exception
						if(isset($options['enabled'][$type][$handle]['current'])) {
							$current_key = array_search($currentID, $options['enabled'][$type][$handle]['current']);
							if($current_key !== false) {
								unset($options['enabled'][$type][$handle]['current'][$current_key]);
							}
						}

						//remove disables
						if(isset($options['disabled'][$type][$handle])) {
							unset($options['disabled'][$type][$handle]['everywhere']);
							unset($options['disabled'][$type][$handle]['regex']);
							if($currentID === 'pmsm-404') {
								unset($options['disabled'][$type][$handle]['404']);
							}
						}

						//remove exceptions
						if(isset($options['enabled'][$type][$handle])) {
							unset($options['enabled'][$type][$handle]['post_types']);
							unset($options['enabled'][$type][$handle]['archives']);
							unset($options['enabled'][$type][$handle]['regex']);
							if($currentID === 'pmsm-404') {
								unset($options['enabled'][$type][$handle]['404']);
							}
						}
					}
				}
			}

			//check disabled array
			if(isset($pmsm_data['pmsm_disabled'][$type])) {
				foreach($pmsm_data['pmsm_disabled'][$type] as $handle => $value) {

					$disabled_trash = array();

					//make sure status is disabled and we have a value to set
					if((empty($pmsm_data['pmsm_status'][$type][$handle]) || $pmsm_data['pmsm_status'][$type][$handle] != 'enabled') && !empty($value)) {
						if($value == "everywhere") {
							$options['disabled'][$type][$handle]['everywhere'] = 1;
							$disabled_trash = array('current', 'regex', '404');
						}
						elseif($value == "current") {
							if(!isset($options['disabled'][$type][$handle]['current']) || !is_array($options['disabled'][$type][$handle]['current'])) {
								$options['disabled'][$type][$handle]['current'] = array();
							}
							if(!in_array($currentID, $options['disabled'][$type][$handle]['current'], TRUE)) {
								array_push($options['disabled'][$type][$handle]['current'], $currentID);
							}
							$disabled_trash = array('everywhere', 'regex');
						}
						elseif($value == "404") {
							$options['disabled'][$type][$handle]['404'] = 1;
							$disabled_trash = array('everywhere', 'regex');
						}
						elseif(is_array($value) && key($value) == "regex") {
							if(!empty($value['regex'])) {
								$options['disabled'][$type][$handle]['regex'] = $value['regex'];
								$disabled_trash = array('everywhere', 'current', '404');
							}
							else {
								$disabled_trash = array('regex');
							}
						}
					}

					//empty disabled trash
					if(!empty($disabled_trash) && isset($options['disabled'][$type][$handle])) {
						foreach($disabled_trash as $trash) {
							unset($options['disabled'][$type][$handle][$trash]);
						}
					}
				}
			}

			//check enabled array
			if(isset($pmsm_data['pmsm_enabled'][$type])) {
				foreach($pmsm_data['pmsm_enabled'][$type] as $handle => $value) {

					//make sure status is disabled and we have a value to set
					if((empty($pmsm_data['pmsm_status'][$type][$handle]) || $pmsm_data['pmsm_status'][$type][$handle] != 'enabled') && !empty($value)) {

						//set current url exception
						if(isset($value['current'])) {
							if(!empty($value['current']) || $value['current'] === "0") {
								if(!isset($options['enabled'][$type][$handle]['current']) || !is_array($options['enabled'][$type][$handle]['current'])) {
									$options['enabled'][$type][$handle]['current'] = array();
								}
								if(!in_array($value['current'], $options['enabled'][$type][$handle]['current'], TRUE)) {
									array_push($options['enabled'][$type][$handle]['current'], $value['current']);
								}
							}
							else {
								if(isset($options['enabled'][$type][$handle]['current'])) {
									$current_key = array_search($currentID, $options['enabled'][$type][$handle]['current']);
									if($current_key !== false) {
										unset($options['enabled'][$type][$handle]['current'][$current_key]);
									}
								}
							}
						}

						//set 404 exception
						if(isset($value['404'])) {
							if(!empty($value['404'])) {
								$options['enabled'][$type][$handle]['404'] = 1;
							}
							else {
								unset($options['enabled'][$type][$handle]['404']);
							}
						}

						//set post types exception
						if(isset($value['post_types'])) {
							if(!empty($value['post_types'])) {
								$options['enabled'][$type][$handle]['post_types'] = array();
								foreach($value['post_types'] as $key => $post_type) {
									if(isset($options['enabled'][$type][$handle]['post_types'])) {
										if(!in_array($post_type, $options['enabled'][$type][$handle]['post_types'])) {
											array_push($options['enabled'][$type][$handle]['post_types'], $post_type);
										}
									}
								}
							}
							else {
								unset($options['enabled'][$type][$handle]['post_types']);
							}
						}

						//set archives exception
						if(!empty($settings['separate_archives']) && $settings['separate_archives'] == "1") {
							if(isset($value['archives'])) {
								if(is_array($value['archives'])) {
									$value['archives'] = array_filter($value['archives']);
								}
								if(!empty($value['archives'])) {
									$options['enabled'][$type][$handle]['archives'] = array();
									foreach($value['archives'] as $key => $archive) {
										if(!in_array($archive, $options['enabled'][$type][$handle]['archives'])) {
											array_push($options['enabled'][$type][$handle]['archives'], $archive);
										}
									}
								}
								else {
									unset($options['enabled'][$type][$handle]['archives']);
								}
							}
						}

						//set regex exception
						if(isset($value['regex'])) {
							if(!empty($value['regex'])) {
								$options['enabled'][$type][$handle]['regex'] = $value['regex'];
							}
							else {
								unset($options['enabled'][$type][$handle]['regex']);
							}
						}
					}
				}
			}
		}

		//clean up the options array before saving
		perfmatters_script_manager_filter_options($options);

		if(update_option('perfmatters_script_manager', $options)) {
			echo 'update_success';
		}
		else {
			echo 'update_failure';
		}
	}
	else {
		echo 'update_nochange';
	}
	wp_die();
}

function perfmatters_script_manager_filter_options(&$options) {
	foreach($options as $key => $item) {
        is_array($item) && $options[$key] = perfmatters_script_manager_filter_options($item);
        if(empty($options[$key]) && $options[$key] != 0) {
        	unset($options[$key]);
        }
    }
    return $options;
}

//after script manager settings option update
function perfmatters_script_manager_update_option($old_value, $value, $option) {

	//trigger success popup message
	add_action('wp_footer', function() {
		echo "<script>pmsmPopupMessage('" . __('Settings saved successfully!', 'perfmatters') . "');</script>";    
	}, 9999);

	//mu mode was enabled
	if(!empty($value['mu_mode']) && empty($old_value['mu_mode'])) {

		$mu_version_match = false;

		//make sure mu directory exists
		if(!file_exists(WPMU_PLUGIN_DIR)) {
			@mkdir(WPMU_PLUGIN_DIR);
		}

		//remove existing mu plugin file
		if(file_exists(WPMU_PLUGIN_DIR . "/perfmatters_mu.php")) {

			if(!function_exists('get_plugin_data')) {
		        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		    }

		    //get plugin data
		    $mu_plugin_data = get_plugin_data(WPMU_PLUGIN_DIR . "/perfmatters_mu.php");

			if(!empty($mu_plugin_data['Version']) && defined('PERFMATTERS_VERSION') && $mu_plugin_data['Version'] == PERFMATTERS_VERSION) {
				$mu_version_match = true;
			}
			else {
				@unlink(WPMU_PLUGIN_DIR . "/perfmatters_mu.php");
			}
		}
		
		//copy current mu plugin file
		if(file_exists(plugin_dir_path(__FILE__) . "/perfmatters_mu.php") && !$mu_version_match) {
			@copy(plugin_dir_path(__FILE__) . "/perfmatters_mu.php", WPMU_PLUGIN_DIR . "/perfmatters_mu.php");
		}
	}
}

//dequeue scripts based on script manager configuration
function perfmatters_dequeue_scripts($src, $handle) {
	
	if(is_admin() || isset($_GET['elementor-preview']) || isset($_GET['fl_builder'])) {
		return $src;
	}

	//get script type
	$type = current_filter() == 'script_loader_src' ? "js" : "css";

	//load options
	$options = get_option('perfmatters_script_manager');
	$settings = get_option('perfmatters_script_manager_settings');
	$currentID = perfmatters_get_current_ID();

	//get category + group from src
	preg_match('/\/wp-content\/(.*?\/.*?)\//', $src, $match);
	if(!empty($match[1])) {
		$match = explode("/", $match[1]);
		$category = $match[0];
		$group = $match[1];
	}

	//check for group disable settings and override
	if(!empty($category) && !empty($group) && !empty($options['disabled'][$category][$group])) {
		if(!empty($options['disabled'][$category][$group]['everywhere']) || (!empty($options['disabled'][$category][$group]['current']) && in_array($currentID, $options['disabled'][$category][$group]['current'])) || !empty($options['disabled'][$category][$group]['regex']) || (!empty($options['disabled'][$category][$group]['404']) && $currentID === 'pmsm-404')) {
			$type = $category;
			$handle = $group;
		}
	}

	//disable is set, check options
	if(!empty($options['disabled'][$type][$handle]['everywhere']) || (!empty($options['disabled'][$type][$handle]['current']) && in_array($currentID, $options['disabled'][$type][$handle]['current'])) || !empty($options['disabled'][$type][$handle]['regex']) || (!empty($options['disabled'][$type][$handle]['404']) && $currentID === 'pmsm-404')) {

		//jquery override
		if($handle == 'jquery-core' && $type == 'js' && isset($_GET['perfmatters']) && current_user_can('manage_options')) {
			global $pmsm_jquery_disabled;
			$pmsm_jquery_disabled = true;
			return $src;
		}
	
		//current url check
		if(!empty($options['enabled'][$type][$handle]['current']) && in_array($currentID, $options['enabled'][$type][$handle]['current'])) {
			return $src;
		}

		//404 check
		if(!empty($options['enabled'][$type][$handle]['404']) && $currentID === 'pmsm-404') {
			return $src;
		}

		//regex check
		if(!empty($options['disabled'][$type][$handle]['regex'])) {
			global $wp;
  			$current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
			if(!preg_match($options['disabled'][$type][$handle]['regex'], $current_url)) {
				return $src;
			}
			else {
				return false;
			}
		}

		if(!empty($options['enabled'][$type][$handle]['regex'])) {
			global $wp;
  			$current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
  			if(preg_match($options['enabled'][$type][$handle]['regex'], $current_url)) {
				return $src;
			}
		}

		if(!empty($settings['separate_archives']) && $settings['separate_archives'] == "1") {
			if(is_archive()) {
				$object = get_queried_object();
				if(!empty($object)) {
					$objectClass = get_class($object);
					if($objectClass == "WP_Post_Type") {
						if(!empty($options['enabled'][$type][$handle]['archives']) && in_array($object->name, $options['enabled'][$type][$handle]['archives'])) {
							return $src;
						}
						else {
							return false;
						}
					}
					elseif($objectClass == "WP_User")
					{
						if(!empty($options['enabled'][$type][$handle]['archives']) && in_array("author", $options['enabled'][$type][$handle]['archives'])) {
							return $src;
						}
						else {
							return false;
						}
					}
					else {
						if(!empty($options['enabled'][$type][$handle]['archives']) && in_array($object->taxonomy, $options['enabled'][$type][$handle]['archives'])) {
							return $src;
						}
						else {
							return false;
						}
					}
				}
			}
		}

		if(is_front_page() || is_home()) {
			if(get_option('show_on_front') == 'page' && !empty($options['enabled'][$type][$handle]['post_types']) && in_array('page', $options['enabled'][$type][$handle]['post_types'])) {
				return $src;
			}
		}
		else {
			if(!empty($options['enabled'][$type][$handle]['post_types']) && in_array(get_post_type(), $options['enabled'][$type][$handle]['post_types'])) {
				return $src;
			}
		}

		return false;
	}

	//original script src
	return $src;
}

//Script Manager Get Current ID
function perfmatters_get_current_ID() {

	global $currentID;

	//check if global is set and return
	if(!empty($currentID) || $currentID === 0) {
		return $currentID;
	}
	
	global $wp_query;

	//make sure we have a usable query
	if(empty($wp_query->posts) || $wp_query->is_archive()) {

		//404 check
		if(is_404()) {
			return 'pmsm-404';
		} 

		//woocommerce shop check
		if(function_exists('is_shop') && is_shop()) {
			return wc_get_page_id('shop');
		}

		return '';
	}

	$currentID = '';
	
	if(is_object($wp_query)) {
		$currentID = $wp_query->get_queried_object_id();
	}
    
	if($currentID === 0) {
		if(!is_front_page()) {
			$postID = get_the_ID();
			if($postID !== 0) {
				$currentID = $postID;
			}
		}
	}

	if(has_filter('perfmatters_get_current_ID')) {
		$currentID = apply_filters('perfmatters_get_current_ID', $currentID);
	}

	return $currentID;
}

//check if mu mode is on and version is correct
function perfmatters_script_manager_mu_notice() {
	$pmsm_settings = get_option('perfmatters_script_manager_settings');
	if(!empty($pmsm_settings['mu_mode'])) {

		if(!function_exists('get_plugin_data')) {
	        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	    }

	    //get plugin data
	    $mu_plugin_data = get_plugin_data(WPMU_PLUGIN_DIR . "/perfmatters_mu.php");

		//display mu version mismatch notice
		if(defined('PERFMATTERS_VERSION') && !empty($mu_plugin_data['Version']) && $mu_plugin_data['Version'] != PERFMATTERS_VERSION) {
			echo "<div class='notice notice-warning'>";
				echo "<p>";
					echo "<strong>" . __('Perfmatters Warning', 'perfmatters') . ":</strong> ";
					echo __('MU plugin version mismatch.', 'perfmatters') . " <a href='https://perfmatters.io/docs/mu-mode/' target='_blank'>" . __('View Documentation', 'perfmatters') . "</a>";
				echo "</p>";
			echo "</div>";
		}
		elseif(!file_exists(WPMU_PLUGIN_DIR . "/perfmatters_mu.php")) {
			echo "<div class='notice notice-error'>";
				echo "<p>";
					echo "<strong>" . __('Perfmatters Warning', 'perfmatters') . ":</strong> ";
					echo __('MU plugin file not found.', 'perfmatters') . " <a href='https://perfmatters.io/docs/mu-mode/' target='_blank'>" . __('View Documentation', 'perfmatters') . "</a>";
				echo "</p>";
			echo "</div>";
		}
	}
}

//exclude our script manager js from autoptimize
function perfmatters_script_manager_exclude_autoptimize($exclude) {
	if(!strpos($exclude, 'script-manager.js')) {
		$exclude.= ',script-manager.js';
	}
	return $exclude;
}