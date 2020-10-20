<?php 
//if no tab is set, default to options tab
$tab = !empty($_GET['tab']) ? $_GET['tab'] : 'options';

//restore defaults
if(!empty($_POST['restore'])) {
	if($tab == 'options') {
		$defaults = perfmatters_default_options();
		if(!empty($defaults)) {
			update_option("perfmatters_options", $defaults);
		}
	}
	elseif($tab == 'cdn') {
		$defaults = perfmatters_default_cdn();
		if(!empty($defaults)) {
			update_option("perfmatters_cdn", $defaults);
		}
	}
	elseif($tab == 'ga') {
		$defaults = perfmatters_default_ga();
		if(!empty($defaults)) {
			update_option("perfmatters_ga", $defaults);
		}
	}
	elseif($tab == 'extras') {
		$defaults = perfmatters_default_extras();
		if(!empty($defaults)) {
			update_option("perfmatters_extras", $defaults);
		}
	}
}

//plugin settings wrapper
echo "<div id='perfmatters-admin' class='wrap'>";

	//hidden h2 for admin notice placement
	echo "<h2 style='display: none;'></h2>";

    //tab navigation
	echo "<div class='nav-tab-wrapper'>";
		echo "<a href='?page=perfmatters&tab=options' class='nav-tab " . ($tab == 'options' || '' ? 'nav-tab-active' : '') . "'>" . __('Options', 'perfmatters') . "</a>";
		echo "<a href='?page=perfmatters&tab=cdn' class='nav-tab " . ($tab == 'cdn' ? 'nav-tab-active' : '') . "'>" . __('CDN', 'perfmatters') . "</a>";
		echo "<a href='?page=perfmatters&tab=ga' class='nav-tab " . ($tab == 'ga' ? 'nav-tab-active' : '') . "'>" . __('Google Analytics', 'perfmatters') . "</a>";
		echo "<a href='?page=perfmatters&tab=extras' class='nav-tab " . ($tab == 'extras' ? 'nav-tab-active' : '') . "'>" . __('Extras', 'perfmatters') . "</a>";
		if(!is_plugin_active_for_network('perfmatters/perfmatters.php')) {
			echo "<a href='?page=perfmatters&tab=license' class='nav-tab " . ($tab == 'license' ? 'nav-tab-active' : '') . "'>" . __('License', 'perfmatters') . "</a>";
		}
	echo "</div>";

	//plugin options form
	echo "<form method='post' action='options.php' id='perfmatters-options-form'" . ($tab == 'extras' ? " enctype='multipart/form-data'" : "") . ">";

		//main options tab
		if($tab == 'options') {

			//options subnav
			echo "<input type='hidden' name='section' id='subnav-section' />";
			echo "<div class='perfmatters-subnav'>";
				echo "<a href='#options-general' id='general-section' rel='general' class='active'><span class='dashicons dashicons-dashboard'></span>" . __('General', 'perfmatters') . "</a>";
				echo "<a href='#options-lazyloading' id='lazyloading-section' rel='lazyloading'><span class='dashicons dashicons-images-alt2'></span>" . __('Lazy Loading', 'perfmatters') . "</a>";
				echo "<a href='#options-woocommerce' id='woocommerce-section' rel='woocommerce'><span class='dashicons dashicons-cart'></span>" . __('WooCommerce', 'perfmatters') . "</a>";
			echo "</div>";

			settings_fields('perfmatters_options');

			echo "<section id='options-general' class='section-content active'>";
		    	perfmatters_settings_section('perfmatters_options', 'perfmatters_options');
		    	submit_button();
		    echo "</section>";

		    echo "<section id='options-lazyloading' class='section-content hide'>";
		    	perfmatters_settings_section('perfmatters_options', 'perfmatters_lazy_loading');
		    	submit_button();
		    echo "</section>";

		    echo "<section id='options-woocommerce' class='section-content hide'>";
		    	perfmatters_settings_section('perfmatters_options', 'perfmatters_woocommerce');
		    	submit_button();
		    echo "</section>";

		//cdn tab
		} elseif($tab == 'cdn') {

			settings_fields('perfmatters_cdn');
			do_settings_sections('perfmatters_cdn');
			submit_button();

		//google analytics tab
		} elseif($tab == 'ga') {

			settings_fields('perfmatters_ga');
			do_settings_sections('perfmatters_ga');
			submit_button();

		//extras tab
		} elseif($tab == 'extras') {

			//extras subnav
			echo "<input type='hidden' name='section' id='subnav-section' />";
			echo "<div class='perfmatters-subnav'>";
				echo "<a href='#extras-general' id='general-section' rel='general' class='active'><span class='dashicons dashicons-dashboard'></span>" . __('General', 'perfmatters') . "</a>";
				echo "<a href='#extras-assets' id='assets-section' rel='assets'><span class='dashicons dashicons-editor-code'></span>" . __('Assets', 'perfmatters') . "</a>";
				echo "<a href='#extras-preloading' id='preloading-section' rel='preloading'><span class='dashicons dashicons-clock'></span>" . __('Preloading', 'perfmatters') . "</a>";
				echo "<a href='#extras-database' id='database-section' rel='database'><span class='dashicons dashicons-networking'></span>" . __('Database', 'perfmatters') . "</a>";
				echo "<a href='#extras-tools' id='tools-section' rel='tools'><span class='dashicons dashicons-admin-tools'></span>" . __('Tools', 'perfmatters') . "</a>";
			echo "</div>";

			settings_fields('perfmatters_extras');

			echo "<section id='extras-general' class='section-content active'>";
		    	perfmatters_settings_section('perfmatters_extras', 'general');
		    	submit_button();
		    echo "</section>";

		    echo "<section id='extras-assets' class='section-content hide'>";
		    	perfmatters_settings_section('perfmatters_extras', 'assets');
		    	perfmatters_settings_section('perfmatters_extras', 'assets_js');
		    	submit_button();
		    echo "</section>";

		    echo "<section id='extras-preloading' class='section-content hide'>";
		    	perfmatters_settings_section('perfmatters_extras', 'preloading');
		    	submit_button();
		    echo "</section>";

		    echo "<section id='extras-database' class='section-content hide'>";
				perfmatters_settings_section('perfmatters_extras', 'database');
				submit_button();
			echo "</section>";

			echo "<section id='extras-tools' class='section-content hide'>";
				perfmatters_settings_section('perfmatters_extras', 'tools');
				submit_button();
			echo "</section>";

			echo "<script>
				jQuery(document).ready(function($) {
					var optimizeSchedule = $('#perfmatters-admin #optimize_schedule');
					var previousValue = $(optimizeSchedule).val();
					$(optimizeSchedule).change(function() {
						var newValue = $(this).val();
						if(newValue && newValue != previousValue) {
							$('#perfmatters-optimize-schedule-warning').show();
						}
						else {
							$('#perfmatters-optimize-schedule-warning').hide();
						}
					});
				});
			</script>";
		}

	echo "</form>";

	if($tab != 'license') {

		//restore defaults button
		echo "<form method='post' action='' id='perfmatters-restore' onsubmit=\"return confirm('" . __('Restore default settings?', 'perfmatters') . "');\">";
			echo "<input type='submit' id='restore' name='restore' class='button button-secondary' value='" . __('Restore Defaults', 'perfmatters') . "'>";
		echo "</form>";

	}
	else {

		//license custom form output
		require_once('license.php');
	}

echo "</div>";