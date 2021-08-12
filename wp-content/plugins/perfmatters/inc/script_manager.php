<?php
//Security Check
if(!current_user_can('manage_options') || is_admin() || !isset($_GET['perfmatters']) || !perfmatters_network_access()) {
	return;
}

global $pmsm_print_flag;

//script manager already printed
if($pmsm_print_flag) {
	return;
}

$pmsm_print_flag = true;

//Set Variables
global $perfmatters_extras;
global $wp;
global $wp_scripts;
global $wp_styles;
global $perfmatters_options;
global $currentID;
$currentID = perfmatters_get_current_ID();
$copyright = "© " . date("Y") . " Perfmatters";

//Process Settings Form
if(isset($_POST['perfmatters_script_manager_settings'])) {

	//Validate Settings Nonce
	if(!isset($_POST['perfmatters_script_manager_settings_nonce']) || !wp_verify_nonce($_POST['perfmatters_script_manager_settings_nonce'], 'perfmatter_script_manager_save_settings')) {
		print 'Sorry, your nonce did not verify.';
	    exit;
	} else {

		//Update Settings
		update_option('perfmatters_script_manager_settings', $_POST['perfmatters_script_manager_settings']);
	}
}

//Process Reset Form
if(isset($_POST['perfmatters_script_manager_settings_reset'])) {
	delete_option('perfmatters_script_manager');
	delete_option('perfmatters_script_manager_settings');
}

//Load Script Manager Settings
global $perfmatters_script_manager_settings;
$perfmatters_script_manager_settings = get_option('perfmatters_script_manager_settings');

//Build Array of Existing Disables
global $perfmatters_disables;
$perfmatters_disables = array();
if(!empty($perfmatters_options['disable_google_maps']) && $perfmatters_options['disable_google_maps'] == "1") {
	$perfmatters_disables[] = 'maps.google.com';
	$perfmatters_disables[] = 'maps.googleapis.com';
	$perfmatters_disables[] = 'maps.gstatic.com';
}
if(!empty($perfmatters_options['disable_google_fonts']) && $perfmatters_options['disable_google_fonts'] == "1") {
	$perfmatters_disables[] = 'fonts.googleapis.com';
}

//Setup Filters Array
global $perfmatters_filters;
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

//Load Script Manager Options
global $perfmatters_script_manager_options;
$perfmatters_script_manager_options = get_option('perfmatters_script_manager');

//Load Styles
include('script_manager_css.php');

//Script Manager Wrapper
echo "<div id='perfmatters-script-manager-wrapper' " . (isset($_GET['perfmatters']) ? "style='display: block;'" : "") . ">";

	echo "<div id='perfmatters-script-manager'>";

		$master_array = perfmatters_script_manager_load_master_array();

		//Header
		echo "<div id='perfmatters-script-manager-header'>";

			//Logo
			echo "<img src='" . plugins_url('img/logo.svg', dirname(__FILE__)) . "' title='Perfmatters' id='perfmatters-logo' />";
		
			//Main Navigation Form
			echo "<form method='POST'>";
				echo "<div id='perfmatters-script-manager-tabs'>";
					echo "<button name='tab' value='' class='"; if(empty($_POST['tab'])){echo "active";} echo "' title='" . __('Script Manager', 'perfmatters') . "'>" . __('Script Manager', 'perfmatters') . "</button>";
					echo "<button name='tab' value='global' class='"; if(!empty($_POST['tab']) && $_POST['tab'] == "global"){echo "active";} echo "' title='" . __('Global View', 'perfmatters') . "'>" . __('Global View', 'perfmatters') . "</button>";
					echo "<button name='tab' value='settings' class='"; if(!empty($_POST['tab']) && $_POST['tab'] == "settings"){echo "active";} echo "' title='" . __('Settings', 'perfmatters') . "'>" . __('Settings', 'perfmatters') . "</button>";
				echo "</div>";
			echo "</form>";

		echo "</div>";

		//Disclaimer
		if(empty($perfmatters_script_manager_settings['hide_disclaimer']) || $perfmatters_script_manager_settings['hide_disclaimer'] != "1") {
			echo "<div id='perfmatters-script-manager-disclaimer'>";
				echo "<p>";
					_e("Below you can disable/enable CSS and JS files on a per page/post basis, as well as by custom post types. We recommend testing this locally or on a staging site first, as you could break the appearance of your live site. If you aren't sure about a certain script, you can try clicking on it, as a lot of authors will mention their plugin or theme in the header of the source code.", 'perfmatters');
				echo "</p>";
				echo "<p>";
					_e("If for some reason you run into trouble, you can always enable everything again to reset the settings. Make sure to check out the <a href='https://perfmatters.io/docs/' target='_blank' title='Perfmatters Knowledge Base'>Perfmatters knowledge base</a> for more information.", 'perfmatters');
				echo "</p>";
			echo "</div>";
		}

		echo "<div id='perfmatters-script-manager-container'>";

			//Default/Main Tab
			if(empty($_POST['tab'])) {

				echo "<div class='perfmatters-script-manager-title-bar'>";
					echo "<h1>" . __('Script Manager', 'perfmatters') . "</h1>";
					echo "<p>" . __('Manage scripts loading on the current page.', 'perfmatters') . "</p>";
				echo "</div>";

				//Form
				echo "<form method='POST' id='pmsm-main-form'>";

					foreach($master_array as $category => $groups) {
						if(!empty($groups)) {
							echo "<h3>" . $category . "</h3>";
							if($category != "misc") {
								echo "<div style='background: #ffffff; padding: 10px;'>";
								foreach($groups as $group => $details) {
									//if(!empty($details['assets'])) {
										echo "<div class='perfmatters-script-manager-group'>";
											echo "<h4 class='pmsm-group-heading'>" . $details['name'];

												//Status
												echo "<div class='perfmatters-script-manager-status' style='float: right;'>";
												    perfmatters_script_manager_print_status($category, $group);
												echo "</div>";

											echo "</h4>";

											//if(!empty($details['assets'])) {

											$assets = !empty($details['assets']) ? $details['assets'] : false;

											perfmatters_script_manager_print_section($category, $group, $assets);

											//}

										echo "</div>";
									//}
								}
								echo "</div>";
							}
							else {
								if(!empty($groups)) {
									perfmatters_script_manager_print_section($category, $category, $groups);
								}
							}
						}
					}

					echo "<div class='perfmatters-script-manager-toolbar'>";

						echo "<div class='perfmatters-script-manager-toolbar-wrapper'>";

							echo "<div class='perfmatters-script-manager-toolbar-container'>";

								//Save Button
								echo "<div id='pmsm-save'>";
									echo "<input type='submit' name='perfmatters_script_manager' value='" . __('Save', 'perfmatters') . "' />";
									echo "<span class='pmsm-spinner'></span>";
								echo "</div>";

								//Copyright
								echo "<div class='pmsm-copyright'>" . $copyright . "</div>";

							echo "</div>";

							//Message
							echo "<div class='pmsm-message'></div>";

						echo "</div>";

					echo "</div>";

					//Loading Wrapper
					echo "<div id='pmsm-loading-wrapper'>";

						echo "<span class='pmsm-loading-text'>" . __('Loading Scripts', 'perfmatters') . "<span class='pmsm-spinner'></span></span>";

					echo "</div>";

				echo "</form>";

			}
			//Global View Tab
			elseif(!empty($_POST['tab']) && $_POST['tab'] == "global") {

				echo "<div class='perfmatters-script-manager-title-bar'>";
					echo "<h1>" . __('Global View', 'perfmatters') . "</h1>";
					echo "<p>" . __('This is a visual representation of the Script Manager configuration across your entire site.', 'perfmatters') . "</p>";
				echo "</div>";
				
				if(!empty($perfmatters_script_manager_options)) {
					foreach($perfmatters_script_manager_options as $category => $types) {
						echo "<h3>" . $category . "</h3>";
						if(!empty($types)) {
							echo "<table>";
								echo "<thead>";
									echo "<tr>";
										echo "<th>" . __('Type', 'perfmatters') . "</th>";
										echo "<th>" . __('Script', 'perfmatters') . "</th>";
										echo "<th>" . __('Setting', 'perfmatters') . "</th>";
									echo "</tr>";
								echo "</thead>";
								echo "<tbody>";
									foreach($types as $type => $scripts) {
										if(!empty($scripts)) {
											foreach($scripts as $script => $details) {
												if(!empty($details)) {
													foreach($details as $detail => $values) {
														echo "<tr>";
															echo "<td><span style='font-weight: bold;'>" . $type . "</span></td>";
															echo "<td><span style='font-weight: bold;'>" . $script . "</span></td>";
															echo "<td>";
																echo "<span style='font-weight: bold;'>" . $detail . "</span>";
																if($detail == "current" || $detail == "post_types") {
																	if(!empty($values)) {
																		echo " (";
																		$valueString = "";
																		foreach($values as $key => $value) {
																			if($detail == "current") {
																				if($value !== 0) {
																					if($value == 'pmsm-404') {
																						$valueString.= '404, ';
																					}
																					else {
																						$valueString.= "<a href='" . get_page_link($value) . "' target='_blank'>" . $value . "</a>, ";
																					}
																				}
																				else {
																					$valueString.= "<a href='" . get_home_url() . "' target='_blank'>homepage</a>, ";
																				}
																			}
																			elseif($detail == "post_types") {
																				$valueString.= $value . ", ";
																			}
																		}
																		echo rtrim($valueString, ", ");
																		echo ")";
																	}
																}
															echo "</td>";
														echo "</tr>";
													}
												}
											}
										}
									}
								echo "</tbody>";
							echo "</table>";
						}
					}
				}
				else {
					echo "<div class='perfmatters-script-manager-section'>";
						echo "<p style='margin: 20px; text-align: center;'>" . __("You don't have any scripts disabled yet.") . "</p>";
					echo "</div>";
				}

				echo "<div class='perfmatters-script-manager-toolbar'>";

					echo "<div class='perfmatters-script-manager-toolbar-wrapper'>";

						echo "<div class='perfmatters-script-manager-toolbar-container'>";

							//Spacer
							echo "<div></div>";

							//Message
							echo "<div class='pmsm-copyright'>" . $copyright . "</div>";

						echo "</div>";

					echo "</div>";

				echo "</div>";
			}
			//Settings Tab
			elseif(!empty($_POST['tab']) && $_POST['tab'] == "settings") {

				echo "<div class='perfmatters-script-manager-title-bar'>";
					echo "<h1>" . __('Settings', 'perfmatters') . "</h1>";
					echo "<p>" . __('View and manage all of your Script Manager settings.', 'perfmatters') . "</p>";
				echo "</div>";

				echo "<div class='perfmatters-script-manager-section'>";
					
					//Form
					echo "<form method='POST' id='script-manager-settings'>";
					
						echo "<input type='hidden' name='tab' value='settings' />";

						echo "<table>";
							echo "<tbody>";
								echo "<tr>";
									echo "<th>" . perfmatters_title(__('Hide Disclaimer', 'perfmatters'), 'hide_disclaimer') . "</th>";
									echo "<td>";
										echo "<input type='hidden' name='perfmatters_script_manager_settings[hide_disclaimer]' value='0' />";
										$args = array(
								            'id' => 'hide_disclaimer',
								            'option' => 'perfmatters_script_manager_settings'
								        );
										perfmatters_print_input($args);
										echo "<div>" . __('Hide the disclaimer message box across all Script Manager views.', 'perfmatters') . "</div>";
									echo "</td>";
								echo "</tr>";
								echo "<tr>";
									echo "<th>" . perfmatters_title(__('Display Archives', 'perfmatters'), 'separate_archives') . "</th>";
									echo "<td>";
										$args = array(
								            'id' => 'separate_archives',
								            'option' => 'perfmatters_script_manager_settings'
								        );
										perfmatters_print_input($args);
										echo "<div>" . __('Add WordPress archives to your Script Manager selection options. Archive posts will no longer be grouped with their post type.', 'perfmatters') . "</div>";
									echo "</td>";
								echo "</tr>";
								echo "<tr>";
									echo "<th>" . perfmatters_title(__('Enable MU Mode', 'perfmatters') . "<span class='perfmatters-beta'>BETA</span>", 'mu_mode') . "</th>";
									echo "<td>";

										$args = array(
								            'id' => 'mu_mode',
								            'option' => 'perfmatters_script_manager_settings'
								        );
										perfmatters_print_input($args);
										echo "<div>" . __('Must-use (MU) mode requires elevated permissions and a file to be copied into the mu-plugins directory. This gives you more control and the ability to disable plugin queries, inline CSS, etc.', 'perfmatters') . ' <a href="https://perfmatters.io/docs/mu-mode/" target="_blank">' . __('View Documentation', 'perfmatters') . '</a>' . "</div>";

										echo "<div style='background: #faf3c4; padding: 10px; margin-top: 7px;'><strong>" . __('Warning', 'perfmatters') . ":</strong> " . __('Any previous plugin-level script disables will now disable the entire plugin. Please review your existing Script Manager settings before enabling this option.', 'perfmatters') . "</div>";

										//mu plugin file check
										if(!empty($perfmatters_script_manager_settings['mu_mode'])) {
											if(file_exists(WPMU_PLUGIN_DIR . "/perfmatters_mu.php")) {

												//$mu_plugins = get_mu_plugins();
												if(!function_exists('get_plugin_data')) {
											        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
											    }

											    //get plugin data
											    $mu_plugin_data = get_plugin_data(WPMU_PLUGIN_DIR . "/perfmatters_mu.php");

												if(empty($mu_plugin_data['Version']) || !defined('PERFMATTERS_VERSION') || ($mu_plugin_data['Version'] != PERFMATTERS_VERSION)) {
													$mu_message = __("MU plugin version mismatch.", 'perfmatters');
													$mu_class = "pmsm-mu-mismatch";
												}
												else {
													$mu_message = __("MU plugin installed.", 'perfmatters');
													$mu_class = "pmsm-mu-found";
												}
											}
											else {
												$mu_message = __("MU plugin file not found.", 'perfmatters');
												$mu_class = "pmsm-mu-missing";
											}

											echo "<div class='" . $mu_class . "'>" . $mu_message . "</div>";
										}

									echo "</td>";
								echo "</tr>";
								echo "<tr>";
									echo "<th>" . perfmatters_title(__('Reset Script Manager', 'perfmatters'), 'reset_script_manager') . "</th>";
									echo "<td>";
										//Reset Form
										echo "<div>";
											echo "<input type='submit' name='pmsm-reset' class='pmsm-reset' value='" . __('Reset Script Manager', 'perfmatters') . "' />";
										echo "</div>";
										echo "<div>";
											echo "<span class='perfmatters-tooltip-text'>" . __('Remove and reset all of your existing Script Manager settings.', 'perfmatters') . "</span>";
										echo "</div>";
									echo "</td>";
								echo "</tr>";
							echo "</tbody>";
						echo "</table>";

						//Nonce
						wp_nonce_field('perfmatter_script_manager_save_settings', 'perfmatters_script_manager_settings_nonce');

						echo "<div class='perfmatters-script-manager-toolbar'>";

							echo "<div class='perfmatters-script-manager-toolbar-wrapper'>";

								echo "<div class='perfmatters-script-manager-toolbar-container'>";

									//Save Button
									echo "<input type='submit' name='perfmatters_script_manager_settings_submit' value='" . __('Save', 'perfmatters') . "' />";

									//Copyright
									echo "<div class='pmsm-copyright'>" . $copyright . "</div>";

								echo "</div>";

								//Message
								echo "<div class='pmsm-message'></div>";

							echo "</div>";

						echo "</div>";

					echo "</form>";	

				echo "<div>";

				//Hidden Reset Form
				echo "<form method='POST' id='pmsm-reset-form' onSubmit=\"return confirm('" . __('Are you sure? This will remove and reset all of your existing Script Manager settings and cannot be undone!') . "');\">";
					echo "<input type='hidden' name='tab' value='settings' />";
					echo "<input type='hidden' name='perfmatters_script_manager_settings_reset' class='pmsm-reset' value='submit' />";
				echo "</form>";
			}
		echo "</div>";
	echo "</div>";
echo "</div>";