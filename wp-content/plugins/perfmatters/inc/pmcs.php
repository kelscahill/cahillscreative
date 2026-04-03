<?php
perfmatters_settings_header(__('Code', 'perfmatters'), 'dashicons-editor-code');

echo '<div class="perfmatters-settings-section">';

	if(!empty($_GET['snippet'])) {

		global $pmcs_error;

		echo '<form method="POST">';

			echo '<input type="hidden" name="nonce" value="' . wp_create_nonce('pmcs-nonce') . '" />';
			echo '<input type="hidden" name="file_name" value="' . ($_GET['snippet'] !== 'create' ? $_GET['snippet'] : '') . '" />';

			if(!$pmcs_error && $_GET['snippet'] !== 'create') {

				//get snippet data from config
				$config = Perfmatters\PMCS\PMCS::get_snippet_config(false);
				$snippets = array_merge($config['active'], $config['inactive']);
				$snippet = $snippets[$_GET['snippet']];

				//add code from snippet file
				$file = file_get_contents(Perfmatters\PMCS\PMCS::get_storage_dir() . '/' . $snippet['file_name']);
				$code = Perfmatters\PMCS\PMCS::parse_doc_block($file, true);
				$snippet['code'] = preg_replace('/^<\?php/', '', $code);
			}
			else {

				//use post data for inputs on error
				$snippet = wp_unslash($_POST);
			}

			//control bar
			echo '<div class="pm-control-bar">';

				//name
				echo '<div style="display: flex; align-items: center;">';
					echo '<a href="?page=perfmatters#code" style="text-decoration: none;">' . esc_html__('All Snippets', 'perfmatters') . '</a>';
					echo '<span style="margin: 0px 7px;">/</span>';
					echo '<span id="pmcs-snippet-name">' . (!empty($snippet['name']) ? esc_html__($snippet['name']) : '<span style="opacity: .2;">' . esc_html__('Example Snippet', 'perfmatters') . '</span>') . '</span>';

					if(!empty($snippet['type'])) {
						echo '<a class="pmcs-snippet-type-badge" data-snippet-type="' . $snippet['type'] . '" style="margin-left: 7px; font-size: 12px; height: 26px; box-sizing: border-box; display: flex; align-items: center; padding: 0px 8px;">' . $snippet['type'] . '</a>';
					}
					
				echo '</div>';

				//controls
				echo '<div style="display: flex; align-items: center; gap: 15px;">';

					//active toggle
					echo '<label for="pmcs-active" class="perfmatters-switch" style="display: inline-flex; align-items: center;">';	
						echo '<input type="checkbox" name="active" id="pmcs-active" value="1"' . (!empty($snippet['active']) ? ' checked' : '') . ' style="display: inline-block; margin: 0px;">';
						echo '<div class="perfmatters-slider"></div>';
						echo '<div id="pmcs-active-text" style="position: absolute; font-size: 14px; right: 54px;"></div>';
					echo '</label>';

					//save button
					submit_button('Save Snippet','primary', 'save_snippet', false);

				echo '</div>';
			echo '</div>';

			//snippet content container
			echo '<div id="pmcs-snippet" data-code-type="' . (!empty($snippet['type']) ? strtolower($snippet['type']) : 'php') . '">';

				//snippet loader
				echo '<div id="pmcs-snippet-loader">' . esc_html__('Loading snippet' , 'perfmatters') . '<div class="pmcs-loader"></div></div>';

				//stored snippet error
				if(!empty($config['error_files'][$_GET['snippet']])) {
					Perfmatters\PMCS\PMCS::admin_notice('snippet_error', '<h4 style="margin: 0px auto 10px;">' . __('Snippet Error', 'perfmatters') . '</h4>' . $config['error_files'][$_GET['snippet']], 'error');
				}

				//admin notice display
				echo '<div class="perfmatters-notice-container wrap">';
					do_action('pmcs_admin_notice');
				echo '</div>';

				//top control row
				echo '<div style="display: flex; gap: 15px;">';

					//name
					echo '<div style="flex-grow: 1;">';
						echo '<label for="pmcs-name">Name</label>';
						echo '<input id="pmcs-name" type="text" class="widefat" value="' . (!empty($snippet['name']) ? esc_attr($snippet['name']) : '' ) . '" name="name" placeholder="' . esc_attr__('Example Snippet', 'perfmatters') . '" autocomplete="off" data-1p-ignore="true" data-lpignore="true" data-protonpass-ignore="true" data-bwignore="true" />';
					echo '</div>';

					//location
					echo '<div>';
						echo '<label for="pmcs-location">' . esc_html__('Location', 'perfmatters') . '</label>';

						//visible select
						echo '<select id="pmcs-location" name="location" style="min-width: 160px;"></select>';

						//hidden select used for code type option population
						echo '<select id="pmcs-location-options" class="hidden" disabled>';

							//php
							echo '<option value="" data-code-type="php">' . esc_html__('Everywhere', 'perfmatters') . '</option>';
							echo '<option value="frontend" data-code-type="php"' . (!empty($snippet['location']) && $snippet['location'] == 'frontend' ? ' selected' : '') . '>' . esc_html__('Frontend Only', 'perfmatters') . '</option>';
							echo '<option value="admin" data-code-type="php"' . (!empty($snippet['location']) && $snippet['location'] == 'admin' ? ' selected' : '') . '>' . esc_html__('Admin Only', 'perfmatters') . '</option>';

							//js + css + html
							echo '<option value="wp_head" data-code-type="js,css,html"' . (!empty($snippet['location']) && $snippet['location'] == 'wp_head' ? ' selected' : '') . '>' . esc_html__('Frontend Header', 'perfmatters') . '</option>';
							echo '<option value="wp_footer" data-code-type="js,css,html"' . (!empty($snippet['location']) && $snippet['location'] == 'wp_footer' ? ' selected' : '') . '>' . esc_html__('Frontend Footer', 'perfmatters') . '</option>';
							echo '<option value="admin_head" data-code-type="js,css"' . (!empty($snippet['location']) && $snippet['location'] == 'admin_head' ? ' selected' : '') . '>' . esc_html__('Admin Header', 'perfmatters') . '</option>';
							echo '<option value="admin_footer" data-code-type="js,css"' . (!empty($snippet['location']) && $snippet['location'] == 'admin_footer' ? ' selected' : '') . '>' . esc_html__('Admin Footer', 'perfmatters') . '</option>';

							//html
							echo '<option value="wp_body_open" data-code-type="html"' . (!empty($snippet['location']) && $snippet['location'] == 'wp_body_open' ? ' selected' : '') . '>' . esc_html__('Frontend Body', 'perfmatters') . '</option>';
							echo '<option value="before_content" data-code-type="html"' . (!empty($snippet['location']) && $snippet['location'] == 'before_content' ? ' selected' : '') . '>' . esc_html__('Before Content', 'perfmatters') . '</option>';
							echo '<option value="after_content" data-code-type="html"' . (!empty($snippet['location']) && $snippet['location'] == 'after_content' ? ' selected' : '') . '>' . esc_html__('After Content', 'perfmatters') . '</option>';
							//echo '<option value="shortcode" data-code-type="html"' . (!empty($snippet['location']) && $snippet['location'] == 'shortcode' ? ' selected' : '') . '>' . esc_html__('Shortcode', 'perfmatters') . '</option>';

						echo '</select>';
					echo '</div>';

					//type
					echo '<div' . (($_GET['snippet'] ?? '') !== 'create' ? ' class="hidden"' : '') . '>';
						echo '<label for="pmcs-code-type">' . esc_html__('Type', 'perfmatters') . '</label>';

						//radio bar
						echo '<nav id="pmcs-code-type" class="pmcs-radio-bar">';

							$snippet_type = !empty($snippet['type']) ? $snippet['type'] : 'php';

							foreach(['php', 'js', 'css', 'html'] as $type) {
								echo '<div>';
						    		echo '<input type="radio" id="code-type-' . $type . '" name="type" value="' . $type . '"' . ($snippet_type == $type ? ' checked' : '') . '>';
						    		echo '<label for="code-type-' . $type . '">' . strtoupper($type) . '</label>';
						    	echo '</div>';
							}

						echo '</nav>';
					echo '</div>';
				echo '</div>';

				//code
				echo '<div class="perfmatters-code-snippet">';
					echo '<label for="pmcs-code">' . esc_html__('Code', 'perfmatters') . '</label>';
					echo '<textarea id="pmcs-code" name="code">';

						if(!empty($snippet['code'])) {
							echo esc_textarea($snippet['code']);
						}

					echo '</textarea>';
				echo '</div>';

				//bottom control row
				echo '<div style="display: flex; justify-content: space-between; align-items: center; margin: 0px -20px 20px; border-bottom: 1px solid #f2f2f2; padding: 15px 20px;">';

					//left
					echo '<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">';

						//priority
						echo '<div>';
							echo '<label for="pmcs-priority" class="perfmatters-inline-label-input">';
								echo '<span>' . esc_html__('Priority', 'perfmatters') . '</span>';
								echo '<input type="number" id="pmcs-priority" name="priority" value="' . esc_attr(Perfmatters\PMCS\PMCS::get_priority($snippet['priority'] ?? null)) . '" style="max-width: 75px;">';
							echo '</label>';
						echo '</div>';

					echo '</div>';

					//right
					echo '<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; color: #66686c;">';

						//author meta
						if(!empty($snippet['author'])) {
							echo '<div title="' . esc_attr__('Author', 'perfmatters') . '">';
								echo '<span class="dashicons dashicons-admin-users" style="margin-right: 3px;"></span>';
								echo get_the_author_meta('display_name', $snippet['author']);;
							echo '</div>';
						}
						
						//modified date meta
						if(!empty($snippet['modified'])) {
							echo '<div title="' . esc_attr__('Modified', 'perfmatters') . '">';
								echo '<span class="dashicons dashicons-clock" style="margin-right: 3px;"></span>';
								$date_format = get_option('date_format'); 
								$time_format = get_option('time_format');
								$custom_format = $date_format . ' \a\t ' . $time_format;
								$pretty_date = mysql2date($custom_format, $snippet['modified'], true);
								echo $pretty_date;
							echo '</div>';
						}
							
					echo '</div>';
				echo '</div>';

				//optimizations
				echo '<div id="pmcs-optimizations" style="margin: 0px -20px 20px; border-bottom: 1px solid #f2f2f2; padding: 0px 20px 20px 20px;" data-code-type="js css">';

					//title + tooltip
					echo '<div class="pmcs-title">';
						echo '<label for="pmcs-optimizations">' . esc_html__('Optimizations', 'perfmatters') . '</label>';
						echo '<a href="https://perfmatters.io/docs/code-snippets/#optimizations" class="perfmatters-tooltip" target="_blank"' . (!empty($tools['accessibility_mode']) ? " title='" . esc_attr__("View Documentation", 'perfmatters') . "'" : "") . '>?</a>';
						echo '<div class="perfmatters-tooltip-container">';
							echo perfmatters_tooltip(__('Apply various Perfmatters optimizations to your code snippet to boost performance.', 'perfmatters'));
						echo '</div>';
					echo '</div>';

					//options wrapper
					echo '<div class="pmcs-optimizations-wrapper" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">';

						//print method
						echo '<div data-code-type="js css">';
							echo '<label for="pmcs-method" class="perfmatters-inline-label-input">';
								echo '<span>' . esc_html__('Print Method', 'perfmatters') . '</span>';
								echo '<select id="pmcs-method" name="optimizations[method]">';
									echo '<option value="">Inline</option>';
									echo '<option value="file"' . (!empty($snippet['optimizations']['method']) && $snippet['optimizations']['method'] == 'file' ? ' selected' : '') . '>File</option>';
								echo '</select>';
							echo '</label>';
						echo '</div>';

						//load behavior
						echo '<div data-code-type="js">';
							echo '<label for="pmcs-behavior-js" class="perfmatters-inline-label-input">';
								echo '<span>' . esc_html__('Load Behavior', 'perfmatters') . '</span>';
								echo '<select id="pmcs-behavior-js" name="optimizations[behavior]">';
									echo '<option value="">Default</option>';
									echo '<option value="preload"' . (!empty($snippet['optimizations']['behavior']) && $snippet['optimizations']['behavior'] == 'preload' ? ' selected' : '') . ' data-method="file">Preload</option>';
									echo '<option value="defer"' . (!empty($snippet['optimizations']['behavior']) && $snippet['optimizations']['behavior'] == 'defer' ? ' selected' : '') . ' >Defer</option>';
									echo '<option value="delay"' . (!empty($snippet['optimizations']['behavior']) && $snippet['optimizations']['behavior'] == 'delay' ? ' selected' : '') . '>Delay</option>';
								echo '</select>';
							echo '</label>';
						echo '</div>';

						//load behavior
						echo '<div data-code-type="css">';
							echo '<label for="pmcs-behavior-css" class="perfmatters-inline-label-input">';
								echo '<span>' . esc_html__('Load Behavior', 'perfmatters') . '</span>';
								echo '<select id="pmcs-behavior-css" name="optimizations[behavior]">';
									echo '<option value="">Default</option>';
									echo '<option value="preload"' . (!empty($snippet['optimizations']['behavior']) && $snippet['optimizations']['behavior'] == 'preload' ? ' selected' : '') . ' data-method="file">Preload</option>';
									echo '<option value="async"' . (!empty($snippet['optimizations']['behavior']) && $snippet['optimizations']['behavior'] == 'async' ? ' selected' : '') . ' data-method="file">Async</option>';
								echo '</select>';
							echo '</label>';
						echo '</div>';

						//minify
						echo '<div data-code-type="js css">';
							echo '<label style="margin: 0px; font-weight: normal;">';
								echo esc_html__('Minify', 'perfmatters');
								echo '<input type="checkbox" name="optimizations[minify]" id="pmcs-minify" value="1"' . (!empty($snippet['optimizations']['minify']) ? ' checked' : '') . ' style="margin: 0px 0px 0px 7px;">';
							echo '</label>';
						echo '</div>';

					echo '</div>';
				echo '</div>';

				//conditions
				echo '<div id="pmcs-conditions">';

					//title + tooltip
					echo '<div class="pmcs-title">';
							echo '<label for="pmcs-conditions">' . esc_html__('Conditions', 'perfmatters') . '</label>';
							echo '<a href="https://perfmatters.io/docs/code-snippets/#conditions" class="perfmatters-tooltip" target="_blank"' . (!empty($tools['accessibility_mode']) ? " title='" . esc_attr__("View Documentation", 'perfmatters') . "'" : "") . '>?</a>';
							echo '<div class="perfmatters-tooltip-container">';
								echo perfmatters_tooltip('Choose where and for whom your code snippet is allowed to run.');
							echo '</div>';
					echo '</div>';

					//options wrapper
					echo '<div id="pmcs-conditions-wrapper">';

						$snippet_conditions = $snippet['conditions'] ?? [];
						$conditions = Perfmatters\PMCS\Conditions::get_conditions();

						//include
						echo '<div class="pmcs-condition-type">';

							echo '<div class="pmcs-condition-header" style="border-color: #6FC38C;">' . esc_html__('Include', 'perfmatters') . '</div>';

							echo '<div class="perfmatters-input-row-wrapper">';
								echo '<div class="perfmatters-input-row-container">';

									$row_count = 0;

									if(!empty($snippet_conditions['include'])) {
										foreach($snippet_conditions['include'] as $field => $value) {
											Perfmatters\PMCS\Conditions::print_input_row('include', $conditions, $row_count, $value);
											$row_count++;
										}
									}
									else {
										Perfmatters\PMCS\Conditions::print_input_row('include', $conditions, $row_count, []);
									}

								echo '</div>';

								echo '<a class="perfmatters-add-input-row button button-secondary" rel="' . $row_count . '">' . esc_html__('Add Condition', 'perfmatters') . '</a>';

							echo '</div>';
						echo '</div>';

						//exclude
						echo '<div class="pmcs-condition-type">';

							echo '<div class="pmcs-condition-header" style="border-color: #F3848F;">' . esc_html__('Exclude', 'perfmatters') . '</div>';

							echo '<div class="perfmatters-input-row-wrapper">';
								echo '<div class="perfmatters-input-row-container">';

									$row_count = 0;

									if(!empty($snippet_conditions['exclude'])) {
										foreach($snippet_conditions['exclude'] as $field => $value) {
											Perfmatters\PMCS\Conditions::print_input_row('exclude', $conditions, $row_count, $value);
											$row_count++;
										}
									}
									else {
										Perfmatters\PMCS\Conditions::print_input_row('exclude', $conditions, $row_count, []);
									}

								echo '</div>';

								echo '<a class="perfmatters-add-input-row button button-secondary" rel="' . $row_count . '">' . esc_html__('Add Condition', 'perfmatters') . '</a>';

							echo '</div>';

						echo '</div>';

						//users
						echo '<div class="pmcs-condition-type">';

							echo '<div class="pmcs-condition-header" style="border-color: #7DA7E6;">' . esc_html__('Users', 'perfmatters') . '</div>';

							echo '<div class="perfmatters-input-row-wrapper">';
								echo '<div class="perfmatters-input-row-container">';

									$row_count = 0;

									$conditions = Perfmatters\PMCS\Conditions::get_user_conditions();

									if(!empty($snippet_conditions['users'])) {
										foreach($snippet_conditions['users'] as $field => $value) {
											Perfmatters\PMCS\Conditions::print_input_row('users', $conditions, $row_count, $value);
											$row_count++;
										}
									}
									else {
										Perfmatters\PMCS\Conditions::print_input_row('users', $conditions, $row_count, []);
									}

								echo '</div>';

								echo '<a class="perfmatters-add-input-row button button-secondary" rel="' . $row_count . '">' . esc_html__('Add Condition', 'perfmatters') . '</a>';

							echo '</div>';
						echo '</div>';
					echo '</div>';
				echo '</div>';

				//tags
                echo '<div id="pmcs-tags">';

                    echo '<label for="pmcs-tag-input">' . esc_html__('Tags', 'perfmatters') . '</label>';

                    echo '<div style="border: 0.5px solid #ccc; border-radius: 3px;">';
                    
                    	//tag search input
	                	echo '<input type="text" id="pmcs-tag-input" placeholder="' . esc_attr__('Enter or Search for Tags', 'perfmatters') . '" autocomplete="off">';

	                	echo '<div id="pmcs-tags-wrap">';

	                		//selected tags
		                    echo '<div id="pmcs-tags-container">';

		                    	if(!empty($snippet['tags'])) {
		                    		foreach($snippet['tags'] as $tag) {
		                    			echo '<div class="pmcs-tag" data-tag-name="' . $tag . '">' . esc_html($tag) . '<span class="pmcs-tag-close">×</span></div>';
		                    		}
		                    	}

		                    echo '</div>';

		                    //tag search results
		                    echo '<div id="pmcs-tag-results"></div>';

		               	echo '</div>';
		            echo '</div>';
                    
                    //hidden form input
            		echo '<input type="hidden" name="tags" id="pmcs-selected-tags-input" value="' . (!empty($snippet['tags']) ? implode(',', $snippet['tags']) : '') . '">';
                        
                echo '</div>';

				//description
				echo '<div>';
					echo '<label for="pmcs-description">' . esc_html__('Description', 'perfmatters') . '</label>';
					echo '<textarea id="pmcs-description" name="description" style="min-height: 100px;">';

						if(!empty($snippet['description'])) {
							echo esc_textarea($snippet['description']);
						}

					echo '</textarea>';
				echo '</div>';

				echo '<div style="display: flex; justify-content: space-between; margin: 10px auto;">';

					//save button
					submit_button(__('Save Snippet', 'perfmatters'),'primary', 'save_snippet', false);

					if(!empty($snippet['file_name'])) {
						echo '<div>';
						
							//export button
							$export_url = wp_nonce_url(add_query_arg('export', $snippet['file_name'], add_query_arg('page', 'perfmatters', admin_url('admin.php'))), 'pmcs-action');
							echo '<a href="' . esc_url($export_url) . '#code" class="button button-secondary" style="text-decoration: none; margin-right: 10px;">' . esc_html__('Export Snippet', 'perfmatters') . '</a>';

							//delete button
							$delete_url = wp_nonce_url(add_query_arg('delete', $snippet['file_name'], add_query_arg('page', 'perfmatters', admin_url('admin.php'))), 'pmcs-action');
							echo '<a href="' . esc_url($delete_url) . '#code" class="button button-primary perfmatters-button-warning pmcs-delete" style="text-decoration: none;">' . esc_html__('Delete Snippet', 'perfmatters') . '</a>';

						echo '</div>';
					}

				echo '</div>';	

			echo '</div>';
		echo '</form>';
	}

	//all snippets
	else {

		echo '<form method="GET">';

			//hidden inputs
			echo '<input type="hidden" name="page" value="perfmatters" />';
			echo '<input type="hidden" name="status" value="' . ($_GET['status'] ?? '') . '" />';
			wp_nonce_field('pmcs-action', 'pmcs_nonce');

			//control bar
			echo '<div class="pm-control-bar">';

				//subnav
				echo '<div class="pm-subnav">';
					echo '<a href="#code" rel="code-snippets" class="active"><span class="dashicons dashicons-paperclip"></span>' . esc_html__('Snippets', 'perfmatters') . '</a>';
					echo '<a href="#code/global" rel="code-global"><span class="dashicons dashicons-admin-site-alt3"></span>' . esc_html__('Global Scripts', 'perfmatters') . '</a>';
					echo '<a href="#code/settings" rel="code-settings"><span class="dashicons dashicons-admin-generic"></span>' . esc_html__('Settings', 'perfmatters') . '</a>';
				echo '</div>';

				//controls
				echo '<div style="display: flex; align-items: center; gap: 15px;">';

					//save button
					echo '<a class="button button-primary" href="?page=perfmatters&snippet=create#code" style="display: flex; align-items: center;"><span class="perfmatters-button-text">' . esc_html__('New Snippet', 'perfmatters') . '</span></a>';

				echo '</div>';
			echo '</div>';

			//admin notice display
			echo '<div class="perfmatters-notice-container wrap">';
				do_action('pmcs_admin_notice');
			echo '</div>';

			//snippets table
      		$table = new Perfmatters\PMCS\ListTable();

      		$table->prepare_items();

  			echo '<div id="pmcs-top-bar">';
      			$table->views();
      			$table->search_box(__('Search Snippets', 'perfmatters'), 'snippet');
  			echo '</div>';

      		$table->display();

      	echo '</form>';
	}

echo '</div>';