<?php
$perfmatters_options = get_option('perfmatters_options');
$perfmatters_cdn = get_option('perfmatters_cdn');
$perfmatters_ga = get_option('perfmatters_ga');
$perfmatters_extras = get_option('perfmatters_extras');

/* Options Actions + Filters
/***********************************************************************/
if(!empty($perfmatters_options['disable_emojis']) && $perfmatters_options['disable_emojis'] == "1") {
	add_action('init', 'perfmatters_disable_emojis');
}
if(!empty($perfmatters_options['disable_embeds']) && $perfmatters_options['disable_embeds'] == "1") {
	add_action('init', 'perfmatters_disable_embeds', 9999);
}
if(!empty($perfmatters_options['remove_query_strings']) && $perfmatters_options['remove_query_strings'] == "1") {
	add_action('init', 'perfmatters_remove_query_strings');
}

/* Disable XML-RPC
/***********************************************************************/
if(!empty($perfmatters_options['disable_xmlrpc']) && $perfmatters_options['disable_xmlrpc'] == "1") {
	add_filter('xmlrpc_enabled', '__return_false');
	add_filter('wp_headers', 'perfmatters_remove_x_pingback');
	add_filter('pings_open', '__return_false', 9999);
}

function perfmatters_remove_x_pingback($headers) {
    unset($headers['X-Pingback'], $headers['x-pingback']);
    return $headers;
}

if(!empty($perfmatters_options['remove_jquery_migrate']) && $perfmatters_options['remove_jquery_migrate'] == "1") {
	add_filter('wp_default_scripts', 'perfmatters_remove_jquery_migrate');
}
if(!empty($perfmatters_options['hide_wp_version']) && $perfmatters_options['hide_wp_version'] == "1") {
	remove_action('wp_head', 'wp_generator');
	add_filter('the_generator', 'perfmatters_hide_wp_version');
}
if(!empty($perfmatters_options['remove_wlwmanifest_link']) && $perfmatters_options['remove_wlwmanifest_link'] == "1") {
	remove_action('wp_head', 'wlwmanifest_link');
}
if(!empty($perfmatters_options['remove_rsd_link']) && $perfmatters_options['remove_rsd_link'] == "1") {
	remove_action('wp_head', 'rsd_link');
}

/* Remove Shortlink
/***********************************************************************/
if(!empty($perfmatters_options['remove_shortlink']) && $perfmatters_options['remove_shortlink'] == "1") {
	remove_action('wp_head', 'wp_shortlink_wp_head');
	remove_action ('template_redirect', 'wp_shortlink_header', 11, 0);
}

/* Disable RSS Feeds
/***********************************************************************/
if(!empty($perfmatters_options['disable_rss_feeds']) && $perfmatters_options['disable_rss_feeds'] == "1") {
	add_action('template_redirect', 'perfmatters_disable_rss_feeds', 1);
}

function perfmatters_disable_rss_feeds() {
	if(!is_feed() || is_404()) {
		return;
	}
	
	global $wp_rewrite;
	global $wp_query;

	//check for GET feed query variable firet and redirect
	if(isset($_GET['feed'])) {
		wp_redirect(esc_url_raw(remove_query_arg('feed')), 301);
		exit;
	}

	//unset wp_query feed variable
	if(get_query_var('feed') !== 'old') {
		set_query_var('feed', '');
	}
		
	//let Wordpress redirect to the proper URL
	redirect_canonical();

	//redirect failed, display error message
	wp_die(sprintf(__("No feed available, please visit the <a href='%s'>homepage</a>!"), esc_url(home_url('/'))));
}

/* Remove RSS Feed Links
/***********************************************************************/
if(!empty($perfmatters_options['remove_feed_links']) && $perfmatters_options['remove_feed_links'] == "1") {
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);
}

/* Disable Self Pingbacks
/***********************************************************************/
if(!empty($perfmatters_options['disable_self_pingbacks']) && $perfmatters_options['disable_self_pingbacks'] == "1") {
	add_action('pre_ping', 'perfmatters_disable_self_pingbacks');
}

function perfmatters_disable_self_pingbacks(&$links) {
	$home = get_option('home');
	foreach($links as $l => $link) {
		if(strpos($link, $home) === 0) {
			unset($links[$l]);
		}
	}
}

/* Disable REST API
/***********************************************************************/
if(!empty($perfmatters_options['disable_rest_api'])) {
	add_filter('rest_authentication_errors', 'perfmatters_rest_authentication_errors', 20);
}

function perfmatters_rest_authentication_errors($result) {
	if(!empty($result)) {
		return $result;
	}
	else{
		global $perfmatters_options;
		$disabled = false;

		//get rest route
		$rest_route = $GLOBALS['wp']->query_vars['rest_route'];

		//check rest route for exceptions
		if(strpos($rest_route, 'contact-form-7') !== false) {
			return;
		}

		//check settings
		if($perfmatters_options['disable_rest_api'] == 'disable_non_admins' && !current_user_can('manage_options')) {
			$disabled = true;
		}
		elseif($perfmatters_options['disable_rest_api'] == 'disable_logged_out' && !is_user_logged_in()) {
			$disabled = true;
		}
	}
	if($disabled) {
		return new WP_Error('rest_authentication_error', __('Sorry, you do not have permission to make REST API requests.', 'perfmatters'), array('status' => 401));
	}
	return $result;
}

/* Remove REST API Links
/***********************************************************************/
if(!empty($perfmatters_options['remove_rest_api_links']) && $perfmatters_options['remove_rest_api_links'] == "1") {
	remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
	remove_action('wp_head', 'rest_output_link_wp_head');
	remove_action('template_redirect', 'rest_output_link_header', 11, 0);
}

/* Disable Google Maps
/***********************************************************************/
if(!empty($perfmatters_options['disable_google_maps']) && $perfmatters_options['disable_google_maps'] == "1") {
	add_action('template_redirect', 'perfmatters_disable_google_maps');
}

function perfmatters_disable_google_maps() {
	ob_start('perfmatters_disable_google_maps_regex');
}

function perfmatters_disable_google_maps_regex($html) {
	$html = preg_replace('/<script[^<>]*\/\/maps.(googleapis|google|gstatic).com\/[^<>]*><\/script>/i', '', $html);
	return $html;
}

/* Disable Google Fonts
/***********************************************************************/
if(!empty($perfmatters_options['disable_google_fonts']) && $perfmatters_options['disable_google_fonts'] == "1") {
	add_action('template_redirect', 'perfmatters_disable_google_fonts');
}

function perfmatters_disable_google_fonts() {
	ob_start('perfmatters_disable_google_fonts_regex');
}

function perfmatters_disable_google_fonts_regex($html) {
	$html = preg_replace('/<link[^<>]*\/\/fonts\.(googleapis|google|gstatic)\.com[^<>]*>/i', '', $html);
	return $html;
}

/* Disable Password Strength Meter
/***********************************************************************/
if(!empty($perfmatters_options['disable_password_strength_meter']) && $perfmatters_options['disable_password_strength_meter'] == "1") {
	add_action('wp_print_scripts', 'perfmatters_disable_password_strength_meter', 100);
}

function perfmatters_disable_password_strength_meter() {

	//skip admin
	if(is_admin()) {
		return;
	}

	//skip wp-login.php
	if((isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') || (isset($_GET['action']) && in_array($_GET['action'], array('rp', 'lostpassword', 'register')))) {
		return;
	}

	//skip specific woocommerce endpoints
	if(class_exists('WooCommerce') && (is_account_page() || is_checkout())) {
		return;
	}

	wp_dequeue_script('zxcvbn-async');
	wp_deregister_script('zxcvbn-async');

	wp_dequeue_script('password-strength-meter');
	wp_deregister_script('password-strength-meter');

	wp_dequeue_script('wc-password-strength-meter');
	wp_deregister_script('wc-password-strength-meter');
}

/* Disable Comments
/***********************************************************************/
if(!empty($perfmatters_options['disable_comments']) && $perfmatters_options['disable_comments'] == "1") {

	//Disable Built-in Recent Comments Widget
	add_action('widgets_init', 'perfmatters_disable_recent_comments_widget');

	//Check for XML-RPC
	if(empty($perfmatters_options['disable_xmlrpc'])) {
		add_filter('wp_headers', 'perfmatters_remove_x_pingback');
	}

	//Check for Feed Links
	if(empty($perfmatters_options['remove_feed_links'])) {
		remove_action('wp_head', 'feed_links_extra', 3);
	}
	
	//Disable Comment Feed Requests
	add_action('template_redirect', 'perfmatters_disable_comment_feed_requests', 9);

	//Remove Comment Links from the Admin Bar
	add_action('template_redirect', 'perfmatters_remove_admin_bar_comment_links'); //front end
	add_action('admin_init', 'perfmatters_remove_admin_bar_comment_links'); //admin

	//Finish Disabling Comments
	add_action('wp_loaded', 'perfmatters_wp_loaded_disable_comments');
}

//Disable Recent Comments Widget
function perfmatters_disable_recent_comments_widget() {
	unregister_widget('WP_Widget_Recent_Comments');
	add_filter('show_recent_comments_widget_style', '__return_false');
}

//Disable Comment Feed Requests
function perfmatters_disable_comment_feed_requests() {
	if(is_comment_feed()) {
		wp_die(__('Comments are disabled.', 'perfmatters'), '', array('response' => 403));
	}
}

//Remove Comment Links from the Admin Bar
function perfmatters_remove_admin_bar_comment_links() {
	if(is_admin_bar_showing()) {
		remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		if(is_multisite()) {
			add_action('admin_bar_menu', 'perfmatters_remove_network_admin_bar_comment_links', 500);
		}
	}
}

//Remove Comment Links from the Network Admin Bar
function perfmatters_remove_network_admin_bar_comment_links($wp_admin_bar) {
	if(!function_exists('is_plugin_active_for_network')) {
	    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
	}
	if(is_plugin_active_for_network('perfmatters/perfmatters.php') && is_user_logged_in()) {

		//Remove for All Sites
		foreach($wp_admin_bar->user->blogs as $blog) {
			$wp_admin_bar->remove_menu('blog-' . $blog->userblog_id . '-c');
		}
	}
	else {
		
		//Remove for Current Site
		$wp_admin_bar->remove_menu('blog-' . get_current_blog_id() . '-c');
	}
}

//Finish Disabling Comments
function perfmatters_wp_loaded_disable_comments() {

	//Remove Comment Support from All Post Types
	$post_types = get_post_types(array('public' => true), 'names');
	if(!empty($post_types)) {
		foreach($post_types as $post_type) {
			if(post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}
		}
	}

	//Close Comment Filters
	add_filter('comments_array', function() { return array(); }, 20, 2);
	add_filter('comments_open', function() { return false; }, 20, 2);
	add_filter('pings_open', function() { return false; }, 20, 2);

	if(is_admin()) {

		//Remove Menu Links + Disable Admin Pages 
		add_action('admin_menu', 'perfmatters_admin_menu_remove_comments', 9999);
		
		//Hide Comments from Dashboard
		add_action('admin_print_styles-index.php', 'perfmatters_hide_dashboard_comments_css');

		//Hide Comments from Profile
		add_action('admin_print_styles-profile.php', 'perfmatters_hide_profile_comments_css');
		
		//Remove Recent Comments Meta
		add_action('wp_dashboard_setup', 'perfmatters_remove_recent_comments_meta');
		
		//Disable Pingback Flag
		add_filter('pre_option_default_pingback_flag', '__return_zero');
	}
	else {

		//Replace Comments Template with a Blank One
		add_filter('comments_template', 'perfmatters_blank_comments_template', 20);
		
		//Remove Comment Reply Script
		wp_deregister_script('comment-reply');
		
		//Disable the Comments Feed Link
		add_filter('feed_links_show_comments_feed', '__return_false');
	}
}

//Remove Menu Links + Disable Admin Pages 
function perfmatters_admin_menu_remove_comments() {

	global $pagenow;

	//Remove Comment + Discussion Menu Links
	remove_menu_page('edit-comments.php');
	remove_submenu_page('options-general.php', 'options-discussion.php');

	//Disable Comments Pages
	if($pagenow == 'comment.php' || $pagenow == 'edit-comments.php') {
		wp_die(__('Comments are disabled.', 'perfmatters'), '', array('response' => 403));
	}

	//Disable Discussion Page
	if($pagenow == 'options-discussion.php') {
		wp_die(__('Comments are disabled.', 'perfmatters'), '', array('response' => 403));
	}
}

//Hide Comments from Dashboard
function perfmatters_hide_dashboard_comments_css(){
	echo "<style>
		#dashboard_right_now .comment-count, #dashboard_right_now .comment-mod-count, #latest-comments, #welcome-panel .welcome-comments {
			display: none !important;
		}
	</style>";
}

//Hide Comments from Profile
function perfmatters_hide_profile_comments_css(){
	echo "<style>
		.user-comment-shortcuts-wrap {
			display: none !important;
		}
	</style>";
}

//Remove Recent Comments Meta Box
function perfmatters_remove_recent_comments_meta(){
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}

//Return Blank Comments Template
function perfmatters_blank_comments_template() {
	return dirname(__FILE__) . '/comments-template.php';
}

/* Remove Comment URLs
/***********************************************************************/
if(!empty($perfmatters_options['remove_comment_urls']) && $perfmatters_options['remove_comment_urls'] == "1") {
	add_filter('get_comment_author_link', 'perfmatters_remove_comment_author_link', 10, 3);
	add_filter('get_comment_author_url', 'perfmatters_remove_comment_author_url');
	add_filter('comment_form_default_fields', 'perfmatters_remove_website_field', 9999);
}

function perfmatters_remove_comment_author_link($return, $author, $comment_ID) {
    return $author;
}

function perfmatters_remove_comment_author_url() {
    return false;
}

function perfmatters_remove_website_field($fields) {
   unset($fields['url']);
   return $fields;
}

/* Disable Dashicons
/***********************************************************************/
if(!empty($perfmatters_options['disable_dashicons']) && $perfmatters_options['disable_dashicons'] == "1") {
	add_action('wp_enqueue_scripts', 'perfmatters_disable_dashicons');
}

function perfmatters_disable_dashicons() {
	if(!is_user_logged_in()) {
		wp_dequeue_style('dashicons');
	    wp_deregister_style('dashicons');
	}
}

/* Disable WooCommerce Scripts
/***********************************************************************/
if(!empty($perfmatters_options['disable_woocommerce_scripts']) && $perfmatters_options['disable_woocommerce_scripts'] == "1") {
	add_action('wp_enqueue_scripts', 'perfmatters_disable_woocommerce_scripts', 99);
}

function perfmatters_disable_woocommerce_scripts() {
	if(function_exists('is_woocommerce')) {
		if(!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page() && !is_product() && !is_product_category() && !is_shop()) {
			global $perfmatters_options;
			
			//Dequeue WooCommerce Styles
			wp_dequeue_style('woocommerce-general');
			wp_dequeue_style('woocommerce-layout');
			wp_dequeue_style('woocommerce-smallscreen');
			wp_dequeue_style('woocommerce_frontend_styles');
			wp_dequeue_style('woocommerce_fancybox_styles');
			wp_dequeue_style('woocommerce_chosen_styles');
			wp_dequeue_style('woocommerce_prettyPhoto_css');
			wp_dequeue_style('woocommerce-inline');

			//Dequeue WooCommerce Scripts
			wp_dequeue_script('wc_price_slider');
			wp_dequeue_script('wc-single-product');
			wp_dequeue_script('wc-add-to-cart');
			wp_dequeue_script('wc-checkout');
			wp_dequeue_script('wc-add-to-cart-variation');
			wp_dequeue_script('wc-single-product');
			wp_dequeue_script('wc-cart');
			wp_dequeue_script('wc-chosen');
			wp_dequeue_script('woocommerce');
			wp_dequeue_script('prettyPhoto');
			wp_dequeue_script('prettyPhoto-init');
			wp_dequeue_script('jquery-blockui');
			wp_dequeue_script('jquery-placeholder');
			wp_dequeue_script('fancybox');
			wp_dequeue_script('jqueryui');

			//Remove no-js Script + Body Class
			add_filter('body_class', function($classes) {
				remove_action('wp_footer', 'wc_no_js');
				$classes = array_diff($classes, array('woocommerce-no-js'));
				return array_values($classes);
			},10, 1);

			//Dequue Cart Fragmentation Script
			if(empty($perfmatters_options['disable_woocommerce_cart_fragmentation']) || $perfmatters_options['disable_woocommerce_cart_fragmentation'] == "0") {
				wp_dequeue_script('wc-cart-fragments');
				wp_deregister_script('wc-cart-fragments');
			}
		}
	}
}

/* Disable WooCommerce Cart Fragmentation
/***********************************************************************/
if(!empty($perfmatters_options['disable_woocommerce_cart_fragmentation']) && $perfmatters_options['disable_woocommerce_cart_fragmentation'] == "1") {
	add_action('wp_enqueue_scripts', 'perfmatters_disable_woocommerce_cart_fragmentation', 99);
}

function perfmatters_disable_woocommerce_cart_fragmentation() {
	if(function_exists('is_woocommerce')) {
		wp_dequeue_script('wc-cart-fragments');
		wp_deregister_script('wc-cart-fragments');
	}
}

/* Disable WooCommerce Status Meta Box
/***********************************************************************/
if(!empty($perfmatters_options['disable_woocommerce_status']) && $perfmatters_options['disable_woocommerce_status'] == "1") {
	add_action('wp_dashboard_setup', 'perfmatters_disable_woocommerce_status');
}

function perfmatters_disable_woocommerce_status() {
	remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
}

/* Disable WooCommerce Widgets
/***********************************************************************/
if(!empty($perfmatters_options['disable_woocommerce_widgets']) && $perfmatters_options['disable_woocommerce_widgets'] == "1") {
	add_action('widgets_init', 'perfmatters_disable_woocommerce_widgets', 99);
}
function perfmatters_disable_woocommerce_widgets() {
	global $perfmatters_options;

	unregister_widget('WC_Widget_Products');
	unregister_widget('WC_Widget_Product_Categories');
	unregister_widget('WC_Widget_Product_Tag_Cloud');
	unregister_widget('WC_Widget_Cart');
	unregister_widget('WC_Widget_Layered_Nav');
	unregister_widget('WC_Widget_Layered_Nav_Filters');
	unregister_widget('WC_Widget_Price_Filter');
	unregister_widget('WC_Widget_Product_Search');
	unregister_widget('WC_Widget_Recently_Viewed');

	if(empty($perfmatters_options['disable_woocommerce_reviews']) || $perfmatters_options['disable_woocommerce_reviews'] == "0") {
		unregister_widget('WC_Widget_Recent_Reviews');
		unregister_widget('WC_Widget_Top_Rated_Products');
		unregister_widget('WC_Widget_Rating_Filter');
	}
}

/* Limit Post Revisions
/***********************************************************************/
if(!empty($perfmatters_options['limit_post_revisions'])) {
	if(defined('WP_POST_REVISIONS')) {
		add_action('admin_notices', 'perfmatters_admin_notice_post_revisions');
	}
	define('WP_POST_REVISIONS', $perfmatters_options['limit_post_revisions']);
}

function perfmatters_admin_notice_post_revisions() {
	echo "<div class='notice notice-error'>";
		echo "<p>";
			echo "<strong>" . __('Perfmatters Warning', 'perfmatters') . ":</strong> ";
			echo __('WP_POST_REVISIONS is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'perfmatters');
		echo "</p>";
	echo "</div>";
}

/* Autosave Interval
/***********************************************************************/
if(!empty($perfmatters_options['autosave_interval'])) {
	if(defined('AUTOSAVE_INTERVAL')) {
		add_action('admin_notices', 'perfmatters_admin_notice_autosave_interval');
	}
	define('AUTOSAVE_INTERVAL', $perfmatters_options['autosave_interval']);
}

function perfmatters_admin_notice_autosave_interval() {
	echo "<div class='notice notice-error'>";
		echo "<p>";
			echo "<strong>" . __('Perfmatters Warning', 'perfmatters') . ":</strong> ";
			echo __('AUTOSAVE_INTERVAL is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'perfmatters');
		echo "</p>";
	echo "</div>";
}

/* Disable Emojis
/***********************************************************************/
function perfmatters_disable_emojis() {
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_styles', 'print_emoji_styles');	
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');	
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('tiny_mce_plugins', 'perfmatters_disable_emojis_tinymce');
	add_filter('wp_resource_hints', 'perfmatters_disable_emojis_dns_prefetch', 10, 2);
	add_filter('emoji_svg_url', '__return_false');
}

function perfmatters_disable_emojis_tinymce($plugins) {
	if(is_array($plugins)) {
		return array_diff($plugins, array('wpemoji'));
	} else {
		return array();
	}
}

function perfmatters_disable_emojis_dns_prefetch( $urls, $relation_type ) {
	if('dns-prefetch' == $relation_type) {
		$emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2.2.1/svg/');
		$urls = array_diff($urls, array($emoji_svg_url));
	}
	return $urls;
}

/* Disable Embeds
/***********************************************************************/
function perfmatters_disable_embeds() {
	global $wp;
	$wp->public_query_vars = array_diff($wp->public_query_vars, array('embed',));
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );
	add_filter( 'embed_oembed_discover', '__return_false' );
	remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	add_filter( 'tiny_mce_plugins', 'perfmatters_disable_embeds_tiny_mce_plugin' );
	add_filter( 'rewrite_rules_array', 'perfmatters_disable_embeds_rewrites' );
	remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}

function perfmatters_disable_embeds_tiny_mce_plugin($plugins) {
	return array_diff($plugins, array('wpembed'));
}

function perfmatters_disable_embeds_rewrites($rules) {
	foreach($rules as $rule => $rewrite) {
		if(false !== strpos($rewrite, 'embed=true')) {
			unset($rules[$rule]);
		}
	}
	return $rules;
}

/* Remove Query Strings
/***********************************************************************/
function perfmatters_remove_query_strings() {
	if(!is_admin()) {
		add_filter('script_loader_src', 'perfmatters_remove_query_strings_split', 15);
		add_filter('style_loader_src', 'perfmatters_remove_query_strings_split', 15);
	}
}

function perfmatters_remove_query_strings_split($src) {

	//keep query strings on script manager js
	if(strpos($src, '/perfmatters/js/script-manager.js')) {
		return $src;
	}

	//strip query strings
	$output = preg_split("/(&ver|\?ver)/", $src);
	return $output[0];
}

/* Remove jQuery Migrate
/***********************************************************************/
function perfmatters_remove_jquery_migrate(&$scripts) {
    if(!is_admin()) {
        $scripts->remove('jquery');
        $scripts->add('jquery', false, array( 'jquery-core' ), '1.12.4');
    }
}

/* Hide WordPress Version
/***********************************************************************/
function perfmatters_hide_wp_version() {
	return '';
}

/* Disable Heartbeat
/***********************************************************************/
if(!empty($perfmatters_options['disable_heartbeat'])) {
	add_action('init', 'perfmatters_disable_heartbeat', 1);
}

function perfmatters_disable_heartbeat() {

	//check for exception pages in admin
	if(is_admin()) {
		global $pagenow;
		if(!empty($pagenow) && in_array($pagenow, array('admin.php'))) {
			if(!empty($_GET['page'])) {
				$exceptions = array(
					'gf_edit_forms',
					'gf_entries',
					'gf_settings'
				);
				if(in_array($_GET['page'], $exceptions)) {
					return;
				}
			}
		}
	}

	//disable hearbeat
	global $perfmatters_options;
	if(!empty($perfmatters_options['disable_heartbeat'])) {
		if($perfmatters_options['disable_heartbeat'] == 'disable_everywhere') {
			perfmatters_replace_hearbeat();
		}
		elseif($perfmatters_options['disable_heartbeat'] == 'allow_posts') {
			global $pagenow;
			if($pagenow != 'post.php' && $pagenow != 'post-new.php') {
				perfmatters_replace_hearbeat();
			}
		}
	}
}

function perfmatters_replace_hearbeat() {
	wp_deregister_script('heartbeat');
	//wp_dequeue_script('heartbeat');
	if(is_admin()) {
		wp_register_script('hearbeat', plugins_url('js/heartbeat.js', dirname(__FILE__)));
		wp_enqueue_script('heartbeat', plugins_url('js/heartbeat.js', dirname(__FILE__)));
	}
}

/* Heartbeat Frequency
/***********************************************************************/
if(!empty($perfmatters_options['heartbeat_frequency'])) {
	add_filter('heartbeat_settings', 'perfmatters_heartbeat_frequency');
}

function perfmatters_heartbeat_frequency($settings) {
	global $perfmatters_options;
	if(!empty($perfmatters_options['heartbeat_frequency'])) {
		$settings['interval'] = $perfmatters_options['heartbeat_frequency'];
	}
	return $settings;
}

/* Change Login URL
/***********************************************************************/
$perfmatters_wp_login = false;

if(!empty($perfmatters_options['login_url']) && !defined('WP_CLI')) {
	add_action('plugins_loaded', 'perfmatters_plugins_loaded', 2);
	add_action('wp_loaded', 'perfmatters_wp_loaded');
	add_action('setup_theme', 'perfmatters_disable_customize_php', 1);
	add_filter('site_url', 'perfmatters_site_url', 10, 4);
	add_filter('network_site_url', 'perfmatters_network_site_url', 10, 3);
	add_filter('wp_redirect', 'perfmatters_wp_redirect', 10, 2);
	add_filter('site_option_welcome_email', 'perfmatters_welcome_email');
	add_filter('admin_url', 'perfmatters_admin_url');
	remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
}

function perfmatters_site_url($url, $path, $scheme, $blog_id) {
	return perfmatters_filter_wp_login($url, $scheme);
}

function perfmatters_network_site_url($url, $path, $scheme) {
	return perfmatters_filter_wp_login($url, $scheme);
}

function perfmatters_wp_redirect($location, $status) {
	return perfmatters_filter_wp_login($location);
}

function perfmatters_filter_wp_login($url, $scheme = null) {

	//wp-login.php Being Requested
	if(strpos($url, 'wp-login.php') !== false) {

		//Set HTTPS Scheme if SSL
		if(is_ssl()) {
			$scheme = 'https';
		}

		//Check for Query String and Craft New Login URL
		$query_string = explode('?', $url);
		if(isset($query_string[1])) {
			parse_str($query_string[1], $query_string);
			$url = add_query_arg($query_string, perfmatters_login_url($scheme));
		} 
		else {
			$url = perfmatters_login_url($scheme);
		}
	}

	//Return Finished Login URL
	return $url;
}

function perfmatters_login_url($scheme = null) {

	//Return Full New Login URL Based on Permalink Structure
	if(get_option('permalink_structure')) {
		return perfmatters_trailingslashit(home_url('/', $scheme) . perfmatters_login_slug());
	} 
	else {
		return home_url('/', $scheme) . '?' . perfmatters_login_slug();
	}
}

function perfmatters_trailingslashit($string) {

	//Check for Permalink Trailing Slash and Add to String
	if((substr(get_option('permalink_structure'), -1, 1)) === '/') {
		return trailingslashit($string);
	}
	else {
		return untrailingslashit($string);
	}
}

function perfmatters_login_slug() {

	//Declare Global Variable
	global $perfmatters_options;

	//Return Login URL Slug if Available
	if(!empty($perfmatters_options['login_url'])) {
		return $perfmatters_options['login_url'];
	} 
}

function perfmatters_plugins_loaded() {

	//Declare Global Variables
	global $pagenow;
	global $perfmatters_wp_login;

	//Parse Requested URI
	$URI = parse_url($_SERVER['REQUEST_URI']);
	$path = !empty($URI['path']) ? untrailingslashit($URI['path']) : '';
	$slug = perfmatters_login_slug();

	//Non Admin wp-login.php URL
	if(!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-login.php') !== false || $path === site_url('wp-login', 'relative'))) {

		//Set Flag
		$perfmatters_wp_login = true;

		//Prevent Redirect to Hidden Login
		$_SERVER['REQUEST_URI'] = perfmatters_trailingslashit('/' . str_repeat('-/', 10));
		$pagenow = 'index.php';
	} 
	//Hidden Login URL
	elseif($path === home_url($slug, 'relative') || (!get_option('permalink_structure') && isset($_GET[$slug]) && empty($_GET[$slug]))) {
		
		//Override Current Page w/ wp-login.php
		$pagenow = 'wp-login.php';
	}
}

function perfmatters_wp_loaded() {

	//Declare Global Variables
	global $pagenow;
	global $perfmatters_wp_login;

	//Parse Requested URI
	$URI = parse_url($_SERVER['REQUEST_URI']);

	//Disable Normal WP-Admin
	if(is_admin() && !is_user_logged_in() && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && (isset($_GET) && empty($_GET['adminhash']) && empty($_GET['newuseremail']))) {
        wp_die(__('This has been disabled.', 'perfmatters'), 403);
	}

	//Requesting Hidden Login Form - Path Mismatch
	if($pagenow === 'wp-login.php' && $URI['path'] !== perfmatters_trailingslashit($URI['path']) && get_option('permalink_structure')) {

		//Local Redirect to Hidden Login URL
		$URL = perfmatters_trailingslashit(perfmatters_login_url()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
		wp_safe_redirect($URL);
		die();
	}
	//Requesting wp-login.php Directly, Disabled
	elseif($perfmatters_wp_login) {
		wp_die(__('This has been disabled.', 'perfmatters'), 403);
	} 
	//Requesting Hidden Login Form
	elseif($pagenow === 'wp-login.php') {

		//Declare Global Variables
		global $error, $interim_login, $action, $user_login;
		
		//User Already Logged In
		if(is_user_logged_in() && !isset($_REQUEST['action'])) {
			wp_safe_redirect(admin_url());
			die();
		}

		//Include Login Form
		@require_once ABSPATH . 'wp-login.php';
		die();
	}
}

function perfmatters_disable_customize_php() {

	//Declare Global Variable
	global $pagenow;

	//Disable customize.php from Redirecting to Login URL
	if(!is_user_logged_in() && $pagenow === 'customize.php') {
		wp_die(__('This has been disabled.', 'perfmatters'), 403);
	}
}

function perfmatters_welcome_email($value) {

	//Declare Global Variable
	global $perfmatters_options;

	//Check for Custom Login URL and Replace
	if(!empty($perfmatters_options['login_url'])) {
		$value = str_replace(array('wp-login.php', 'wp-admin'), trailingslashit($perfmatters_options['login_url']), $value);
	}

	return $value;
}

function perfmatters_admin_url($url) {

	//Check for Multisite Admin
	if(is_multisite() && ms_is_switched() && is_admin()) {

		global $current_blog;

		//Get Current Switched Blog
		$switched_blog_id = get_current_blog_id();

		if($switched_blog_id != $current_blog->blog_id) {

			$perfmatters_blog_options = get_blog_option($switched_blog_id, 'perfmatters_options');

			//Swap Custom Login URL Only with Base /wp-admin/ Links
			if(!empty($perfmatters_blog_options['login_url'])) {
				$url = preg_replace('/\/wp-admin\/$/', '/' . $perfmatters_blog_options['login_url'] . '/', $url);
			} 
		}
	}

	return $url;
}

/* Instant Page
/***********************************************************************/
if(!empty($perfmatters_extras['instant_page'])) {
	add_action('wp_enqueue_scripts', 'perfmatters_enqueue_instant_page', PHP_INT_MAX);
}

function perfmatters_enqueue_instant_page() {

	$exclude_instant_page = perfmatters_get_post_meta('perfmatters_exclude_instant_page');

	if(!$exclude_instant_page) {
		wp_register_script('perfmatters-instant-page', plugins_url('vendor/instant-page/instantpage.js', dirname(__FILE__)), array(), PERFMATTERS_VERSION, true);
		wp_enqueue_script('perfmatters-instant-page');
	}
}

/* CDN Rewrite URLs
/***********************************************************************/
if(!empty($perfmatters_cdn['enable_cdn']) && $perfmatters_cdn['enable_cdn'] == "1" && !empty($perfmatters_cdn['cdn_url'])) {
	add_action('template_redirect', 'perfmatters_cdn_rewrite');
}

function perfmatters_cdn_rewrite() {
	ob_start('perfmatters_cdn_rewriter');
}

function perfmatters_cdn_rewriter($html) {
	global $perfmatters_cdn;

	//Prep Site URL
    $escapedSiteURL = quotemeta(get_option('home'));
	$regExURL = '(https?:|)' . substr($escapedSiteURL, strpos($escapedSiteURL, '//'));

	//Prep Included Directories
	$directories = 'wp\-content|wp\-includes';
	if(!empty($perfmatters_cdn['cdn_directories'])) {
		$directoriesArray = array_map('trim', explode(',', $perfmatters_cdn['cdn_directories']));
		if(count($directoriesArray) > 0) {
			$directories = implode('|', array_map('quotemeta', array_filter($directoriesArray)));
		}
	}
  
  	//Rewrite URLs + Return
	$regEx = '#(?<=[(\"\'])(?:' . $regExURL . ')?/(?:((?:' . $directories . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
	$cdnHTML = preg_replace_callback($regEx, 'perfmatters_cdn_rewrite_url', $html);
	return $cdnHTML;
}

function perfmatters_cdn_rewrite_url($url) {
	global $perfmatters_cdn;

	//Make Sure CDN URL is Set
	if(!empty($perfmatters_cdn['cdn_url'])) {

		//Don't Rewrite if Excluded
		if(!empty($perfmatters_cdn['cdn_exclusions'])) {
			$exclusions = array_map('trim', explode(',', $perfmatters_cdn['cdn_exclusions']));
			foreach($exclusions as $exclusion) {
	            if(!empty($exclusion) && stristr($url[0], $exclusion) != false) {
	                return $url[0];
	            }
	        }
		} 

	    //Don't Rewrite if Previewing
	    if(is_admin_bar_showing() && isset($_GET['preview']) && $_GET['preview'] == 'true') {
	        return $url[0];
	    }

	    //Prep Site URL
	    $siteURL = get_option('home');
	    $siteURL = substr($siteURL, strpos($siteURL, '//'));

	    //Replace URL w/ No HTTP/S Prefix
	    if(strpos($url[0], '//') === 0) {
	        return str_replace($siteURL, $perfmatters_cdn['cdn_url'], $url[0]);
	    }

	    //Found Site URL, Replace Non Relative URL w/ HTTP/S Prefix
	    if(strstr($url[0], $siteURL)) {
	        return str_replace(array('http:' . $siteURL, 'https:' . $siteURL), $perfmatters_cdn['cdn_url'], $url[0]);
	    }

	    //Replace Relative URL
    	return $perfmatters_cdn['cdn_url'] . $url[0];
    }

    //Return Original URL
    return $url[0];
}

/* Google Analytics
/***********************************************************************/

//enable/disable local analytics scheduled event
if(!empty($perfmatters_ga['enable_local_ga']) && $perfmatters_ga['enable_local_ga'] == "1") {

	$print_analytics = true;

	if(empty($perfmatters_ga['script_type'])) {
		if(!wp_next_scheduled('perfmatters_update_ga')) {
			wp_schedule_event(time(), 'daily', 'perfmatters_update_ga');
		}
		if(!empty($perfmatters_ga['use_monster_insights'])) {
			$print_analytics = false;
			add_filter('monsterinsights_frontend_output_analytics_src', 'perfmatters_monster_ga', 1000);
		}
	}
	else {
		if(wp_next_scheduled('perfmatters_update_ga')) {
			wp_clear_scheduled_hook('perfmatters_update_ga');
		}
	}

	if($print_analytics) {

		if(!empty($perfmatters_ga['tracking_code_position']) && $perfmatters_ga['tracking_code_position'] == 'footer') {
			$tracking_code_position = 'wp_footer';
		}
		else {
			$tracking_code_position = 'wp_head';
		}
		
		add_action($tracking_code_position, 'perfmatters_print_ga', 0);
	}
}
else {
	if(wp_next_scheduled('perfmatters_update_ga')) {
		wp_clear_scheduled_hook('perfmatters_update_ga');
	}
}

//update analytics.js
function perfmatters_update_ga() {
	//paths
	$local_file = dirname(dirname(__FILE__)) . '/js/analytics.js';
	$host = 'www.google-analytics.com';
	$path = '/analytics.js';

	//open connection
	$fp = @fsockopen($host, '80', $errno, $errstr, 10);

	if($fp){	
		//send headers
		$header = "GET $path HTTP/1.0\r\n";
		$header.= "Host: $host\r\n";
		$header.= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6\r\n";
		$header.= "Accept: */*\r\n";
		$header.= "Accept-Language: en-us,en;q=0.5\r\n";
		$header.= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
		$header.= "Keep-Alive: 300\r\n";
		$header.= "Connection: keep-alive\r\n";
		$header.= "Referer: https://$host\r\n\r\n";
		fwrite($fp, $header);
		$response = '';
		
		//get response
		while($line = fread($fp, 4096)) {
			$response.= $line;
		}

		//close connection
		fclose($fp);

		//remove headers
		$position = strpos($response, "\r\n\r\n");
		$response = substr($response, $position + 4);

		//create file if needed
		if(!file_exists($local_file)) {
			fopen($local_file, 'w');
		}

		//write response to file
		if(is_writable($local_file)) {
			if($fp = fopen($local_file, 'w')) {
				fwrite($fp, $response);
				fclose($fp);
			}
		}
	}
}
add_action('perfmatters_update_ga', 'perfmatters_update_ga');

//print analytics script
function perfmatters_print_ga() {
	global $perfmatters_ga;

	//dont print for logged in admins
	if(current_user_can('manage_options') && empty($perfmatters_ga['track_admins'])) {
		return;
	}

	//make sure we have a tracking id
	if(empty($perfmatters_ga['tracking_id'])) {
		return;
	}

	if(empty($perfmatters_ga['script_type'])) {
		echo "<!-- Local Analytics generated with perfmatters. -->";
		echo "<script>";
		    echo "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
					(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
					m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
					})(window,document,'script','" . plugins_url() . "/perfmatters/js/analytics.js','ga');";
		    echo "ga('create', '" . $perfmatters_ga['tracking_id'] . "', 'auto');";

		    //disable display features
		    if(!empty($perfmatters_ga['disable_display_features']) && $perfmatters_ga['disable_display_features'] == "1") {
		    	echo "ga('set', 'allowAdFeatures', false);";
		    }

		    //anonymize ip
		   	if(!empty($perfmatters_ga['anonymize_ip']) && $perfmatters_ga['anonymize_ip'] == "1") {
		   		echo "ga('set', 'anonymizeIp', true);";
		   	}

		    echo "ga('send', 'pageview');";

		    //adjusted bounce rate
		    if(!empty($perfmatters_ga['adjusted_bounce_rate'])) {
		    	echo 'setTimeout("ga(' . "'send','event','adjusted bounce rate','" . $perfmatters_ga['adjusted_bounce_rate'] . " seconds')" . '"' . "," . $perfmatters_ga['adjusted_bounce_rate'] * 1000 . ");";
		    }
	    echo "</script>";
	}
	elseif($perfmatters_ga['script_type'] == 'minimal') {

		echo '<!-- Local Analytics generated with perfmatters. --><script>window.pmGAID="' . $perfmatters_ga['tracking_id'] . '";' . (!empty($perfmatters_ga['anonymize_ip']) ? 'window.pmGAAIP=true;' : '') . '</script>';
		echo '<script src="' . plugins_url() . '/perfmatters/js/analytics-minimal.js"></script>';

	}
	elseif($perfmatters_ga['script_type'] == 'minimal_inline') {
		echo '<!-- Local Analytics generated with perfmatters. --><script>(function(a,b,c){var d=a.history,e=document,f=navigator||{},g=localStorage,h=encodeURIComponent,i=d.pushState,k=function(){return Math.random().toString(36)},l=function(){return g.cid||(g.cid=k()),g.cid},m=function(r){var s=[];for(var t in r)r.hasOwnProperty(t)&&void 0!==r[t]&&s.push(h(t)+"="+h(r[t]));return s.join("&")},n=function(r,s,t,u,v,w,x){var z="https://www.google-analytics.com/collect",A=m({v:"1",ds:"web",aip:c.anonymizeIp?1:void 0,tid:b,cid:l(),t:r||"pageview",sd:c.colorDepth&&screen.colorDepth?screen.colorDepth+"-bits":void 0,dr:e.referrer||void 0,dt:e.title,dl:e.location.origin+e.location.pathname+e.location.search,ul:c.language?(f.language||"").toLowerCase():void 0,de:c.characterSet?e.characterSet:void 0,sr:c.screenSize?(a.screen||{}).width+"x"+(a.screen||{}).height:void 0,vp:c.screenSize&&a.visualViewport?(a.visualViewport||{}).width+"x"+(a.visualViewport||{}).height:void 0,ec:s||void 0,ea:t||void 0,el:u||void 0,ev:v||void 0,exd:w||void 0,exf:"undefined"!=typeof x&&!1==!!x?0:void 0});if(f.sendBeacon)f.sendBeacon(z,A);else{var y=new XMLHttpRequest;y.open("POST",z,!0),y.send(A)}};d.pushState=function(r){return"function"==typeof d.onpushstate&&d.onpushstate({state:r}),setTimeout(n,c.delay||10),i.apply(d,arguments)},n(),a.ma={trackEvent:function o(r,s,t,u){return n("event",r,s,t,u)},trackException:function q(r,s){return n("exception",null,null,null,null,r,s)}}})(window,"' . $perfmatters_ga['tracking_id'] . '",{' . (!empty($perfmatters_ga['anonymize_ip']) ? 'anonymizeIp:true,' : '') . 'colorDepth:true,characterSet:true,screenSize:true,language:true});</script>';
	}
}

//return local anlytics url for Monster Insights
function perfmatters_monster_ga($url) {
	return plugins_url() . "/perfmatters/js/analytics.js";
}

/* Preload
/***********************************************************************/
if(!empty($perfmatters_extras['preload'])) {
	add_action('wp_head', 'perfmatters_preload', 1);
}

function perfmatters_preload() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['preload']) && is_array($perfmatters_extras['preload'])) {

		$mime_types = array(
			'svg'   => 'image/svg+xml',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'woff'  => 'font/woff',
			'woff2' => 'font/woff2',
			'eot'   => 'application/vnd.ms-fontobject',
			'sfnt'  => 'font/sfnt'
		);

		foreach($perfmatters_extras['preload'] as $line) {

			$mime_type = "";

			if(!empty($line['as']) && $line['as'] == 'font') {
				$path_info = pathinfo($line['url']);
				$mime_type = !empty($path_info['extension']) && isset($mime_types[$path_info['extension']]) ? $mime_types[$path_info['extension']] : "";
			}
			
			echo "<link rel='preload' href='" . $line['url'] . "'" . (!empty($line['as']) ? " as='" . $line['as'] . "'" : "") . (!empty($mime_type) ? " type='" . $mime_type . "'" : "") . (!empty($line['crossorigin']) ? " crossorigin" : "") . ">" . "\n";
		}
	}
}

/* Preconnect
/***********************************************************************/
if(!empty($perfmatters_extras['preconnect'])) {
	add_action('wp_head', 'perfmatters_preconnect', 1);
}

function perfmatters_preconnect() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['preconnect']) && is_array($perfmatters_extras['preconnect'])) {
		foreach($perfmatters_extras['preconnect'] as $line) {
			if(is_array($line)) {
				echo "<link rel='preconnect' href='" . $line['url'] . "' " . (isset($line['crossorigin']) && $line['crossorigin'] ? "crossorigin" : "") . ">" . "\n";
			}
			else {
				echo "<link rel='preconnect' href='" . $line . "' crossorigin>" . "\n";
			}
			
		}
	}
}

/* DNS Prefetch
/***********************************************************************/
if(!empty($perfmatters_extras['dns_prefetch'])) {
	add_action('wp_head', 'perfmatters_dns_prefetch', 1);
}

function perfmatters_dns_prefetch() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['dns_prefetch']) && is_array($perfmatters_extras['dns_prefetch'])) {
		foreach($perfmatters_extras['dns_prefetch'] as $url) {
			echo "<link rel='dns-prefetch' href='" . $url . "'>" . "\n";
		}
	}
}

/* Blank Favicon
/***********************************************************************/
if(!empty($perfmatters_extras['blank_favicon'])) {
	add_action('wp_head', 'perfmatters_blank_favicon');
}

function perfmatters_blank_favicon() {
	echo '<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />';
}

/* Header Code
/***********************************************************************/
if(!empty($perfmatters_extras['header_code'])) {
	add_action('wp_head', 'perfmatters_insert_header_code');
}

function perfmatters_insert_header_code() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['header_code'])) {
		echo $perfmatters_extras['header_code'];
	}
}

/* Body Code
/***********************************************************************/
if(!empty($perfmatters_extras['body_code'])) {
	if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=')) {
		add_action('wp_body_open', 'perfmatters_insert_body_code');
	}
}

function perfmatters_insert_body_code() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['body_code'])) {
		echo $perfmatters_extras['body_code'];
	}
}

/* Footer Code
/***********************************************************************/
if(!empty($perfmatters_extras['footer_code'])) {
	add_action('wp_footer', 'perfmatters_insert_footer_code');
}

function perfmatters_insert_footer_code() {
	global $perfmatters_extras;
	if(!empty($perfmatters_extras['footer_code'])) {
		echo $perfmatters_extras['footer_code'];
	}
}

//check option update for custom inputs and modify result
function perfmatters_pre_update_option_perfmatters_extras($new_value, $old_value) {

	//export settings button was pressed
	if(!empty($new_value['export_settings'])) {

		$settings = array();

		$settings['perfmatters_options'] = get_option('perfmatters_options');
		$settings['perfmatters_cdn'] = get_option('perfmatters_cdn');
		$settings['perfmatters_ga'] = get_option('perfmatters_ga');
		$settings['perfmatters_extras'] = get_option('perfmatters_extras');

		ignore_user_abort(true);

		//setup headers
		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename=perfmatters-settings-export-' . date('m-d-Y') . '.json');
		header('Expires: 0');

		//print encoded file
		echo json_encode($settings);
		exit;
	}

	if(!empty($new_value['import_settings']) || !empty($new_value['import_settings_file'])) {

		//get temporary file
		$import_file = $_FILES['perfmatters_import_settings_file']['tmp_name'];

		//cancel if there's no file
		if(empty($import_file)) {
			add_settings_error('perfmatters', 'perfmatters-import-error', __('No import file given.', 'perfmatters'), 'error');
			return $old_value;
		}

		//check if uploaded file is valid
		$file_parts = explode('.', $_FILES['perfmatters_import_settings_file']['name']);
		$extension = end($file_parts);
		if($extension != 'json') {
			add_settings_error('perfmatters', 'perfmatters-import-error', __('Please upload a valid .json file.', 'perfmatters'), 'error');
			return $old_value;
		}

		//unpack settings from file
		$settings = (array) json_decode(file_get_contents($import_file), true);

		if(isset($settings['perfmatters_options'])) {
			update_option('perfmatters_options', $settings['perfmatters_options']);
		}

		if(isset($settings['perfmatters_cdn'])) {
			update_option('perfmatters_cdn', $settings['perfmatters_cdn']);
		}

		if(isset($settings['perfmatters_ga'])) {
			update_option('perfmatters_ga', $settings['perfmatters_ga']);
		}

		if(isset($settings['perfmatters_extras'])) {
			update_option('perfmatters_extras', $settings['perfmatters_extras']);
		}

		//add_action('admin_notices', 'perfmatters_admin_notice_settings_import_success');
		add_settings_error('perfmatters', 'perfmatters-import-success', __('Successfully imported Perfmatters settings.', 'perfmatters'), 'success');

		return $old_value;
	}

	return $new_value;
}

//add filter to update options
function perfmatters_update_options() {
	add_filter('pre_update_option_perfmatters_extras', 'perfmatters_pre_update_option_perfmatters_extras', 10, 2);
}
add_action('admin_init', 'perfmatters_update_options');

/* EDD License Functions
/***********************************************************************/
function perfmatters_activate_license() {

	//grab existing license data
	$license = is_network_admin() ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key');

	if(!empty($license)) {

		//data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode(PERFMATTERS_ITEM_NAME), // the name of our product in EDD
			'url'       => home_url()
		);

		//Call the custom API.
		$response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

		//make sure the response came back okay
		if(is_wp_error($response)) {
			return false;
		}

		//decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		//update stored option
		if(is_network_admin()) {
			update_site_option('perfmatters_edd_license_status', $license_data->license);
		}
		else {
			update_option('perfmatters_edd_license_status', $license_data->license);
		}
	}
}

function perfmatters_deactivate_license() {

	//grab existing license data
	$license = is_network_admin() ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key');

	if(!empty($license)) {

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'deactivate_license',
			'license' 	=> $license,
			'item_name' => urlencode(PERFMATTERS_ITEM_NAME), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

		// make sure the response came back okay
		if(is_wp_error($response)) {
			return false;
		}

		// decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		// $license_data->license will be either "deactivated" or "failed"
		if($license_data->license == 'deactivated') {
			//update license option
			if(is_network_admin()) {
				delete_site_option('perfmatters_edd_license_status');
			}
			else {
				delete_option('perfmatters_edd_license_status');
			}
		}
	}
}

function perfmatters_check_license() {

	//grab existing license data
	$license = is_network_admin() ? get_site_option('perfmatters_edd_license_key') : get_option('perfmatters_edd_license_key');

	if(!empty($license)) {

		$api_params = array(
			'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode(PERFMATTERS_ITEM_NAME),
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post(PERFMATTERS_STORE_URL, array('timeout' => 15, 'sslverify' => true, 'body' => $api_params));

		//make sure the response came back okay
		if(is_wp_error($response)) {
			return false;
		}

		//decode the license data
		$license_data = json_decode(wp_remote_retrieve_body($response));

		//update license option
		if(is_network_admin()) {
			update_site_option('perfmatters_edd_license_status', $license_data->license);
		}
		else {
			update_option('perfmatters_edd_license_status', $license_data->license);
		}
		
		//return license data for use
		return($license_data);
	}

	return false;
}