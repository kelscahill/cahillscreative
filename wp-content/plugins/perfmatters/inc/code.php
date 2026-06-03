<?php

use Perfmatters\Config;

//code global
echo '<section id="code-global" class="section-content pm-child" data-pm-parent="code-code">';

	perfmatters_settings_header(__('Code', 'perfmatters'), 'dashicons-editor-code');

	echo '<div class="pm-control-bar">';

		//subnav
		echo '<div class="pm-subnav">';
			echo '<a href="#code" rel="code-snippets"><span class="dashicons dashicons-paperclip"></span>' . esc_html__('Snippets', 'perfmatters') . '</a>';
			echo '<a href="#code/global" rel="code-global" class="active"><span class="dashicons dashicons-admin-site-alt3"></span>' . esc_html__('Global Scripts', 'perfmatters') . '</a>';
			echo '<a href="#code/settings" rel="code-settings"><span class="dashicons dashicons-admin-generic"></span>' . esc_html__('Settings', 'perfmatters') . '</a>';
		echo '</div>';
		
	echo '</div>';

	perfmatters_settings_section('perfmatters_options', 'assets_code');
echo '</section>';

//code settings
echo '<section id="code-settings" class="section-content pm-child" data-pm-parent="code-code">';

	perfmatters_settings_header(__('Code', 'perfmatters'), 'dashicons-editor-code');

	echo '<div class="pm-control-bar">';

		//subnav
		echo '<div class="pm-subnav">';
			echo '<a href="#code" rel="code-snippets"><span class="dashicons dashicons-paperclip"></span>' . esc_html__('Snippets', 'perfmatters') . '</a>';
			echo '<a href="#code/global" rel="code-global"><span class="dashicons dashicons-admin-site-alt3"></span>' . esc_html__('Global Scripts', 'perfmatters') . '</a>';
			echo '<a href="#code/settings" rel="code-settings" class="active"><span class="dashicons dashicons-admin-generic"></span>' . esc_html__('Settings', 'perfmatters') . '</a>';
		echo '</div>';
		
	echo '</div>';

	echo '<div class="perfmatters-settings-section">';

		$config = Perfmatters\PMCS\PMCS::get_snippet_config();

		echo '<table class="form-table">';
			echo '<tbody>';

				//export all snippets
				echo '<tr>';
					echo '<th>' . perfmatters_title(__('Export Snippets', 'perfmatters') , false, 'https://perfmatters.io/docs/code-snippets/#export') . '</th>';
					echo '<td>';
						perfmatters_action_button('export_snippets', __('Export All Snippets', 'perfmatters'), 'secondary');
						perfmatters_tooltip(__('Export your Perfmatters code snippets for this site as a .json file. This lets you easily import the configuration into another site.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

				//import snippets
				echo '<tr>';
					echo '<th>' . perfmatters_title(__('Import Snippets', 'perfmatters') , false, 'https://perfmatters.io/docs/code-snippets/#import') . '</th>';
					echo '<td>';

						//input + button
    					echo '<input type="file" id="pmcs-import-file" name="pmcs_import_file" /><br />';
    					perfmatters_action_button('import_snippets', __('Import Code Snippets', 'perfmatters'), 'secondary');
						perfmatters_tooltip(__('Import Perfmatters code snippets from an exported .json file.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

				//editor theme
				echo '<tr class="perfmatters-input-controller">';
					echo '<th>' . perfmatters_title(__('Editor Theme', 'perfmatters'), false, 'https://perfmatters.io/docs/code-snippets/#editor-theme') . '</th>';
					echo '<td>';

						$selected_theme = Config::$options['code']['editor_theme'] ?? '';
						$theme_options = \Perfmatters\Admin\CodeMirror::get_theme_options();

						echo '<select id="code-editor_theme" name="perfmatters_options[code][editor_theme]">';
							foreach($theme_options as $theme_slug => $theme_label) {
								echo '<option value="' . esc_attr($theme_slug) . '"' . selected($selected_theme, $theme_slug, false) . '>' . esc_html($theme_label) . '</option>';
							}
						echo '</select>';
						perfmatters_tooltip(__('Choose the theme used in code editors throughout the plugin.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

				//custom editor theme stylesheet: .css in uploads/perfmatters/codemirror-theme/
				echo '<tr class="code-editor_theme perfmatters-select-control-custom' . ($selected_theme === 'custom' ? '' : ' hidden') . '">';
					echo '<th>' . perfmatters_title(__('Custom Theme Stylesheet', 'perfmatters'), false, 'https://perfmatters.io/docs/code-snippets/#custom-theme-stylesheet') . '</th>';
					echo '<td>';

						$custom_theme_path = \Perfmatters\Admin\CodeMirror::get_custom_theme_path();

						echo '<input type="file" id="code-custom-theme-file" name="code_custom_theme_file" accept=".css,text/css" />';
						if($custom_theme_path) {

							$custom_theme_name = \Perfmatters\Admin\CodeMirror::get_custom_theme_name();

							echo '<div>';
								echo __('Current custom theme', 'perfmatters') . ': <code>' . esc_html(!empty($custom_theme_name) ? $custom_theme_name : __('unknown', 'perfmatters')) . '</code>';
							echo '</div>';
						}
						perfmatters_tooltip(__('Upload a custom .css CodeMirror 5 theme stylesheet.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

			echo '</tbody>';
		echo '</table>';

		echo '<h2>' . esc_html__('Safe Mode', 'perfmatters') . '</h2>';

		echo '<table class="form-table">';
			echo '<tbody>';

				//enable safe mode
				echo '<tr>';
					echo '<th>' . perfmatters_title(__('Enable Safe Mode', 'perfmatters') , false, 'https://perfmatters.io/docs/code-snippets/#safe-mode') . '</th>';
					echo '<td>';
						if(!defined('PMCS_SAFE_MODE')) {
							if(empty($config['meta']['force_disabled'])) {
								$enable_safe_mode_url = wp_nonce_url(add_query_arg('enable_safe_mode', true), 'pmcs-action');
								echo '<a href="' . esc_url($enable_safe_mode_url) . '#code" name="pmcs_enable_safe_mode" class="button button-secondary" style="margin-right: 10px;">' . esc_html__('Enable Safe Mode', 'perfmatters') . '</a>';
							}
							else {
								$disable_safe_mode_url = wp_nonce_url(add_query_arg('disable_safe_mode', true), 'pmcs-action');
								echo '<a href="' . esc_url($disable_safe_mode_url) . '#code" name="pmcs_enable_safe_mode" class="button button-secondary" style="margin-right: 10px;">' . esc_html__('Disable Safe Mode', 'perfmatters') . '</a>';
								echo '<span style="color:#dba617;line-height:30px;"><span class="dashicons dashicons-warning" style="line-height:30px;"></span> ' . esc_html__('Safe mode is enabled.', 'perfmatters') . '</span>';
							}
						}
						else {
							echo '<span style="color:#dba617;line-height:30px;"><span class="dashicons dashicons-warning" style="line-height:30px;"></span> ' . esc_html__('Safe mode enabled via PMCS_SAFE_MODE constant.', 'perfmatters') . '</span>';
						}

						perfmatters_tooltip(__('Enable safe mode to prevent all code snippets from running regardless of active status. This can also be done using your recovery URL or by defining our PMCS_SAFE_MODE constant.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

				//recovery url
				echo '<tr>';
					echo '<th>' . perfmatters_title(__('Recovery URL', 'perfmatters') , false, 'https://perfmatters.io/docs/code-snippets/#safe-mode') . '</th>';
					echo '<td>';
						echo '<label class="perfmatters-inline-label-input pmcs-copy-input">';
							echo '<input type="text" value="' . esc_attr(!empty($config['meta']['secret_key']) ? site_url('index.php?pmcs_secret=' . $config['meta']['secret_key']) : '') . '" placeholder="' . esc_html__('Create a snippet first.', 'perfmatters') . '" readonly>';
							echo '<span>' . esc_html__('Copy', 'perfmatters') . '</span>';
						echo '</label>';

						perfmatters_tooltip(__('This URL can be used to enable safe mode in the event of an unrecoverable snippet error.', 'perfmatters'));
					echo '</td>';
				echo '</tr>';

			echo '</tbody>';
		echo '</table>';
	echo '</div>';
echo '</section>';