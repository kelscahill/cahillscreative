<?php
//accessibility mode styles
$tools = get_option('perfmatters_tools');
if(!empty($tools['accessibility_mode'])) {
	echo '<style>#perfmatters-admin .perfmatters-tooltip-subtext{display: none;}.perfmatters-tooltip-container{width: 100%;}</style>';
}

//settings wrapper
echo '<div id="perfmatters-admin" class="wrap">';

	//hidden h2 for admin notice placement
	echo '<h2 style="display: none;"></h2>';

	//flex container
	echo '<div id="perfmatters-admin-container">';

		echo '<div id="perfmatters-admin-header">';

			//header
			echo '<div class="perfmatters-admin-block">';

				echo '<div id="perfmatters-logo-bar">';

					//logo
					echo '<svg id="perfmatters-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 428 73"><path d="M190.582 73.966q3.252-3.29 3.252-8.972t-3.252-8.896-7.663-3.215q-4.412 0-7.664 3.252t-3.252 8.934 3.252 8.934q3.252 3.253 7.664 3.253 4.41 0 7.663-3.29m-18.579-23.177q4.411-7.028 12.934-7.028t14.056 5.944q5.532 5.945 5.532 15.327t-5.532 15.364q-5.533 5.98-13.944 5.981-8.41 0-13.046-7.551v26.69h-10.542V44.36h10.542zm57.466 35.588q-9.047 0-14.729-5.869-5.682-5.87-5.682-15.514t5.72-15.438 14.803-5.795q9.084 0 14.915 5.645 5.832 5.645 5.832 15.065 0 2.168-.299 4.037h-30.205q.374 4.038 2.991 6.505t6.654 2.467q5.458 0 8.074-4.486h11.364q-1.794 5.907-6.953 9.645t-12.485 3.738m9.943-24.672q-.224-4.113-3.027-6.579-2.805-2.468-6.767-2.468t-6.616 2.468q-2.655 2.466-3.178 6.579zm27.856-17.345v7.326q4.188-7.925 12.112-7.925v10.766h-2.617q-4.71 0-7.102 2.356t-2.393 8.111v20.785h-10.541V44.36zm36.558 0v8.597h-6.58v32.822h-10.467V52.957h-4.485V44.36h4.485v-2.318q0-7.476 3.963-10.916 3.962-3.438 12.486-3.439v8.822q-3.29 0-4.636 1.271-1.346 1.272-1.346 4.262v2.318zm16.093 0v6.205q3.963-6.804 12.86-6.804 4.71 0 8.411 2.169t5.719 6.205q2.169-3.887 5.944-6.131 3.776-2.243 8.486-2.243 7.401 0 11.925 4.636 4.523 4.635 4.523 13.009v24.373H367.32V62.901q0-4.86-2.467-7.439-2.468-2.58-6.729-2.579t-6.766 2.579q-2.505 2.58-2.505 7.439v22.878h-10.467V62.901q0-4.86-2.467-7.439-2.467-2.58-6.729-2.579-4.261 0-6.766 2.579t-2.505 7.439v22.878h-10.541V44.36zm93.85 29.643q3.29-3.252 3.29-8.934t-3.29-8.934-7.701-3.252-7.663 3.215-3.252 8.896 3.29 8.972 7.663 3.29 7.663-3.253m-23.737 6.355q-5.57-6.018-5.57-15.364t5.532-15.289 14.056-5.944 13.009 7.028V44.36h10.467v41.419h-10.467v-6.953q-4.71 7.551-13.084 7.551t-13.943-6.019m62.633-3.401h5.009v8.822h-6.654q-6.356 0-9.794-2.916-3.44-2.916-3.439-9.645V52.957h-4.412V44.36h4.412V34.117h10.541V44.36h9.271v8.597h-9.271v20.261q0 2.02.972 2.879.972.86 3.365.86m28.157 0h5.009v8.822h-6.654q-6.354 0-9.794-2.916t-3.439-9.645V52.957h-4.411V44.36h4.411V34.117h10.542V44.36h9.27v8.597h-9.27v20.261q0 2.02.972 2.879.972.86 3.364.86m29.428 9.42q-9.047 0-14.729-5.869-5.682-5.87-5.682-15.514t5.72-15.438 14.803-5.795q9.084 0 14.915 5.645 5.832 5.645 5.832 15.065 0 2.168-.299 4.037h-30.205q.374 4.038 2.991 6.505t6.654 2.467q5.458 0 8.074-4.486h11.365q-1.795 5.907-6.953 9.645-5.16 3.738-12.486 3.738m9.944-24.672q-.225-4.113-3.028-6.579-2.804-2.468-6.767-2.468-3.962 0-6.616 2.468-2.655 2.466-3.178 6.579zM548.05 44.36v7.326q4.187-7.925 12.111-7.925v10.766h-2.616q-4.71 0-7.103 2.356t-2.392 8.111v20.785h-10.542V44.36zm15.92 12.111q0-5.382 4.561-9.046 4.56-3.663 12.111-3.664 7.552 0 12.112 3.627t4.86 9.831h-10.766q-.448-5.233-6.43-5.233-2.991 0-4.635 1.196-1.645 1.196-1.645 3.289t2.467 3.29 5.981 1.869a77 77 0 0 1 6.991 1.682q3.476 1.01 5.943 3.589 2.468 2.579 2.468 6.841 0 5.607-4.748 9.121-4.747 3.514-12.112 3.514-7.364 0-12.074-3.477t-5.159-9.981h10.766q.598 5.234 6.654 5.234 2.916 0 4.71-1.308t1.795-3.44q0-2.13-2.467-3.364t-5.982-1.906a86 86 0 0 1-6.99-1.645q-3.477-.972-5.944-3.477t-2.467-6.542" style="fill:#282e34" transform="matrix(.75 0 0 .75 -21.203 -19.966)"/><path d="M62.676 55.56c-3.581-2.099-4.825-6.718-2.72-10.33 3.333-5.753 12.102-4.687 13.8 1.84 1.742 6.531-5.368 11.825-11.08 8.49m13.59-23.49c-15.104-8.626-33.082 5.275-28.69 21.95 2.371 8.919 10.373 14.56 18.84 14.56 12.864 0 22.213-12.166 18.95-24.58-1.34-5.04-4.55-9.27-9.1-11.93m46.64 37.69 8.18 16.88c-23.988 1.532-29.591-.58-39.58 17.44-2.607 4.713-7.008 13.61-18.02 11.45-4.18-.81-7.65-3.31-9.8-7.05l-39.93-69.75c-2.63-4.56-2.62-9.99.02-14.53 2.63-4.52 7.31-7.21 12.54-7.21.03 0 .07.01.11.01l80.4.24c9.378 0 20.196 9.68 10.03 26.11-3.94 6.4-9.34 15.17-3.95 26.41m22.87 19.69-12.06-24.91c-2.43-5.07-.52-8.6 3.36-14.9 2.11-3.41 4.28-6.94 5.33-11.02 2.14-7.81.41-16.43-4.64-23.04-5.01-6.57-12.64-10.34-20.92-10.34l-80.54-.25c-20.403 0-33.139 22.081-22.95 39.72l39.92 69.73c8.048 14.047 26.396 17.485 38.86 8.13 4.375-2.676 8.24-9.744 9.86-12.66 3.55-6.4 5.66-9.75 11.21-10.1l27.56-1.76c4.244-.278 6.872-4.77 5.01-8.6M72.986 52.8c-1.02 1.75-2.66 3-4.61 3.52-1.94.52-3.97.25-5.7-.76a7.5 7.5 0 0 1-3.49-4.61c-.52-1.95-.25-3.97.77-5.72 2.08-3.59 6.72-4.81 10.31-2.76a7.44 7.44 0 0 1 3.49 4.6c.52 1.95.25 3.98-.77 5.73m12.38-8.8c-1.34-5.04-4.55-9.27-9.1-11.93-9.35-5.34-21.32-2.13-26.68 7.13-2.63 4.5-3.34 9.77-2.01 14.82.67 2.52 1.81 4.84 3.34 6.86 1.53 2.01 3.47 3.72 5.72 5.04 3.01 1.76 6.37 2.66 9.78 2.66 1.68 0 3.36-.22 5.03-.66 5.06-1.34 9.3-4.57 11.91-9.09 2.63-4.51 3.34-9.77 2.01-14.83m-12.38 8.8c-1.02 1.75-8.58 3.77-10.31 2.76a7.5 7.5 0 0 1-3.49-4.61c-.52-1.95-.25-3.97.77-5.72 2.08-3.59 6.72-4.81 10.31-2.76a7.44 7.44 0 0 1 3.49 4.6c.52 1.95.25 3.98-.77 5.73m12.38-8.8c-1.34-5.04-4.55-9.27-9.1-11.93-9.35-5.34-21.32-2.13-26.68 7.13-2.63 4.5-3.34 9.77-2.01 14.82.67 2.52 1.81 4.84 3.34 6.86 1.53 2.01 3.47 3.72 5.72 5.04 3.01 1.76 6.37 2.66 9.78 2.66 1.68 0 3.36-.22 5.03-.66 5.06-1.34 9.3-4.57 11.91-9.09 2.63-4.51 3.34-9.77 2.01-14.83" style="fill:#4a89dd" transform="matrix(.59 0 0 .59 -5.754 -2.939)"/></svg>';

					//menu toggle
					echo '<a href="#" id="perfmatters-menu-toggle"><span class="dashicons dashicons-menu"></span></a>';
				echo '</div>';

				//menu
				echo '<div id="perfmatters-menu">';

					if(!is_network_admin()) {

						//options
						echo '<a href="#" rel="options-general"><span class="dashicons dashicons-dashboard"></span>' . __('General', 'perfmatters') . '</a>';
						echo '<a href="#js" rel="options-js"><span class="dashicons dashicons-media-code"></span>' . __('JavaScript', 'perfmatters') . '</a>';
						echo '<a href="#css" rel="options-css"><span class="dashicons dashicons-admin-appearance"></span>' . __('CSS', 'perfmatters') . '</a>';
						echo '<a href="#preload" rel="options-preload"><span class="dashicons dashicons-clock"></span>' . __('Preloading', 'perfmatters') . '</a>';
						echo '<a href="#lazyload" rel="options-lazyload"><span class="dashicons dashicons-images-alt2"></span>' . __('Lazy Loading', 'perfmatters') . '</a>';
						echo '<a href="#fonts" rel="options-fonts"><span class="dashicons dashicons-editor-paste-text"></span>' . __('Fonts', 'perfmatters') . '</a>';
						echo '<a href="#cdn" rel="options-cdn"><span class="dashicons dashicons-admin-site-alt2"></span>' . __('CDN', 'perfmatters') . '</a>';
						echo '<a href="#analytics" rel="options-analytics"><span class="dashicons dashicons-chart-bar"></span>' . __('Analytics', 'perfmatters') . '</a>';

						if(!Perfmatters\Config::$code_disabled) {
							echo '<a href="#code" rel="code-code"><span class="dashicons dashicons-editor-code"></span>' . __('Code', 'perfmatters') . '</a>';
						}

						//spacer
						echo '<hr style="border-top: 1px solid #f2f2f2; border-bottom: 0px; margin: 10px 0px;" />';

						//tools
						echo '<a href="#tools" rel="tools-plugin"><span class="dashicons dashicons-admin-tools"></span>' . __('Tools', 'perfmatters') . '</a>';
						echo '<a href="#database" rel="tools-database"><span class="dashicons dashicons-database"></span>' . __('Database', 'perfmatters') . '</a>';
					}
					else {

						//network
						echo '<a href="#" rel="network-network" class="active"><span class="dashicons dashicons-admin-settings"></span>' . __('Network', 'perfmatters') . '</a>';
					}

					//license
					if(!is_multisite() || is_network_admin()) {
						echo '<a href="#license" rel="license-license"><span class="dashicons dashicons-admin-network"></span>' . __('License', 'perfmatters') . '</a>';
					}

					//support
					echo '<a href="#support" rel="support-support"><span class="dashicons dashicons-editor-help"></span>' . __('Support', 'perfmatters') . '</a>';

				echo '</div>';
			echo '</div>';

			//cta
			if(!get_option('perfmatters_close_cta')) {
				echo '<a href="https://novashare.io/perfmatters-discount/?utm_campaign=plugin-cta&utm_source=perfmatters" target="_blank" id="perfmatters-cta" class="perfmatters-admin-block perfmatters-mobile-hide">';
					echo '<span class="dashicons dashicons-tag" style="margin-right: 10px;"></span>';
					echo '<span>' . __('Get 25% off our social sharing plugin.') . '</span>';
					echo '<span id="perfmatters-cta-close" class="dashicons dashicons-no-alt"></span>';
				echo '</a>';
			}

		echo '</div>';

		echo '<div id="perfmatters-main" style="flex-grow: 1; min-width: 0px;">';
			echo '<div id="perfmatters-settings" class="perfmatters-admin-block" data-pm-tab="options">';

				//version number
				echo '<span id="pm-version" class="perfmatters-mobile-hide">' . __('Version', 'perfmatters') . ' ' . PERFMATTERS_VERSION . '</span>';

				if(!is_network_admin()) {

					//main settings form
					echo '<form method="post" id="perfmatters-options-form" enctype="multipart/form-data" >';

						//options
						echo '<div id="perfmatters-options"' . (empty($tools['show_advanced']) ? ' class="pm-hide-advanced"' : '') . '>';

							//general
							echo '<section id="options-general" class="section-content">';
								perfmatters_settings_header(__('General', 'perfmatters'), 'dashicons-dashboard');
						    	perfmatters_settings_section('perfmatters_options', 'perfmatters_options');
						    	perfmatters_settings_section('perfmatters_options', 'login_url');
						    	perfmatters_settings_section('perfmatters_options', 'perfmatters_woocommerce');
						    echo '</section>';

						    //javascript
						    echo '<section id="options-js" class="section-content">';
						    	perfmatters_settings_header(__('JavaScript', 'perfmatters'), 'dashicons-media-code');
						    	perfmatters_settings_section('perfmatters_options', 'assets_js_defer');
						    	perfmatters_settings_section('perfmatters_options', 'assets_js_delay');
						    	perfmatters_settings_section('perfmatters_options', 'assets_js_minify');
						    echo '</section>';

						    //css
						    echo '<section id="options-css" class="section-content">';
						    	perfmatters_settings_header(__('CSS', 'perfmatters'), 'dashicons-admin-appearance');
						    	perfmatters_settings_section('perfmatters_options', 'assets_css');
						    	perfmatters_settings_section('perfmatters_options', 'assets_css_minify');
						    echo '</section>';

						    //preloading
						    echo '<section id="options-preload" class="section-content">';
						    	perfmatters_settings_header(__('Preloading', 'perfmatters'), 'dashicons-clock');
						    	perfmatters_settings_section('perfmatters_options', 'preload');
						    	if(version_compare(get_bloginfo('version'), '6.8' , '>=')) {
							    	perfmatters_settings_section('perfmatters_options', 'preload_speculative');
							    }
						    	perfmatters_settings_section('perfmatters_options', 'preload_connection');
						    echo '</section>';

						    //lazyload
						    echo '<section id="options-lazyload" class="section-content">';
						    	perfmatters_settings_header(__('Lazy Loading', 'perfmatters'), 'dashicons-images-alt2');
						    	perfmatters_settings_section('perfmatters_options', 'lazyload');
						    	perfmatters_settings_section('perfmatters_options', 'lazyload_css_background_images');
						    	echo '<div class="pm-advanced-option">';
						    		perfmatters_settings_section('perfmatters_options', 'lazyload_elements');
						    	echo '</div>';
						    echo '</section>';

						    //fonts
						    echo '<section id="options-fonts" class="section-content">';
						    	perfmatters_settings_header(__('Fonts', 'perfmatters'), 'dashicons-editor-paste-text');
						    	perfmatters_settings_section('perfmatters_options', 'perfmatters_fonts');
						    echo '</section>';

						    //cdn
						    echo '<section id="options-cdn" class="section-content">';
						    	perfmatters_settings_header(__('CDN', 'perfmatters'), 'dashicons-admin-site-alt2');
						    	perfmatters_settings_section('perfmatters_options', 'perfmatters_cdn');
						    echo '</section>';

						    //analytics
						    echo '<section id="options-analytics" class="section-content">';
						    	perfmatters_settings_header(__('Google Analytics', 'perfmatters'), 'dashicons-chart-bar');
						    	perfmatters_settings_section('perfmatters_options', 'perfmatters_analytics');
						    echo '</section>';

						    //code
						    if(!Perfmatters\Config::$code_disabled) {
							    echo '<section id="code-code" class="section-content" data-pm-subnav="code-snippets">';
							    	require_once('code.php');
							    echo '</section>';
							}

					    echo '</div>';

					    //tools
						echo '<div id="perfmatters-tools">';

							echo '<section id="tools-plugin" class="section-content">';
								perfmatters_settings_header(__('Tools', 'perfmatters'), 'dashicons-admin-tools');
								perfmatters_settings_section('perfmatters_options', 'assets');
								perfmatters_settings_section('perfmatters_tools', 'plugin');
						    	perfmatters_settings_section('perfmatters_tools', 'settings');
						    echo '</section>';

						    echo '<section id="tools-database" class="section-content">';
						    	perfmatters_settings_header(__('Database', 'perfmatters'), 'dashicons-database');
						    	perfmatters_settings_section('perfmatters_tools', 'database');
						    echo '</section>';

						echo '</div>';

						echo '<div id="perfmatters-save" style="margin-top: 20px;">';
							perfmatters_action_button('save_settings', __('Save Changes', 'perfmatters'));
					    echo '</div>';

					echo '</form>';
				}
				else {

					echo '<div id="perfmatters-options">';

						//network
						echo '<section id="network-network" class="section-content">';
							require_once('network.php');
						echo '</section>';

					echo '</div>';
				}

				//license
				if(!is_multisite() || is_network_admin()) {
					echo '<section id="license-license" class="section-content">';					
						require_once('license.php');
					echo '</section>';
				}

				//support
				echo '<section id="support-support" class="section-content">';	
					require_once('support.php');
				echo '</section>';

				//code snippets
				if(!Perfmatters\Config::$code_disabled) {
					echo '<section id="code-snippets" data-pm-parent="code-code" class="section-content pm-child">';	
						require_once('pmcs.php');
					echo '</section>';
				}
				
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

//display correct section based on URL hash
?>
<script>
	(function($) {

		function pmHashChange() {

			//url hash
		    var hash = window.location.hash.trim();

		    if(hash) {

		    	//hash variables
		    	var hashParts = hash.substring(1).split('/');
		    	var tab = hashParts[0];
		    	var subnav = hashParts[1];

		    	//selected variables
		    	var selectedNav = $('#perfmatters-menu a[rel][href="#' + tab + '"]');
		    	var selectedContent = $('#' + selectedNav.attr('rel'));
		    	
		    	//invalid selection
		    	if(selectedNav.length === 0 && selectedContent.length === 0) {
		    		return;
		    	}

		    	//deactivate previous active tab
		    	$('#perfmatters-menu a[rel].active').removeClass("active");
				$('#perfmatters-options .section-content.active').removeClass('active');

		    	//activate selected tab
		    	
		    	selectedNav.addClass('active');
		    	selectedContent.addClass('active');

		    	//display selected subnav
		    	var subNavContent = subnav ? tab + '-' + subnav : selectedContent.data('pm-subnav');
		    	if(subNavContent) {
		    		$('#' + subNavContent).addClass('active');
		    	}

		    	//update selected section in settings container
		    	$('#perfmatters-settings').attr('data-pm-option', selectedNav.attr('rel').split('-')[0]);
		    }

			else {
				// no hash: default to the first tab with an href starting with "#"
				var selectedNav = $("#perfmatters-menu a[rel][href^=\'#\']").first();
				if(selectedNav.length) {
					$("#perfmatters-menu a[rel].active").removeClass("active");
					$(selectedNav).addClass("active");

					var rel = selectedNav.attr("rel");
					if(rel) {
						$("#perfmatters-options-form").attr("data-pm-option", rel.split("-")[0]);
						$("#" + rel).addClass("active");
					}
				}
			}

			$('#perfmatters-main').addClass('pm-loaded');
		}
		pmHashChange();

	    window.addEventListener("hashchange", () => {
	    	pmHashChange();
		});

	})(jQuery);
</script>