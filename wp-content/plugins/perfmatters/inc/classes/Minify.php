<?php
namespace Perfmatters;

use WP_Admin_Bar;

class Minify 
{
	private static $data = [];

	//minify, save to cache, and return minified url
	public static function minify($src) {

		//no src
		if(empty($src)) {
		    return;
		}

		//parse given url
		$parsed_url = parse_url($src);

		//extension check
		$ext = strtolower(pathinfo($parsed_url['path'], PATHINFO_EXTENSION));
		if(empty($ext) || ($ext != 'js' && $ext != 'css')) {
			return;
		}

		//already minified
		if(stripos($src, '.min.' . $ext) !== false) {
		    return;
		}

		//exclusion check
		if(Utilities::match_in_array($src, self::get_exclusions($ext))) {
		   return;
		}

		//check if file is accessible
		$file_relative_path = $parsed_url['path'];
		$file_path = Utilities::get_root_dir_path() . ltrim($file_relative_path, '/');
		if(!is_file($file_path)) {
		    return;
		}

		//generate hashed file name
		$hash = substr(hash_file('md5', $file_path), 0, 12);
		$file_name = $hash . '.' . pathinfo($file_path, PATHINFO_FILENAME) . '.min.' . $ext;

		//minified vars
		$minified_path = PERFMATTERS_CACHE_DIR . 'minify/' . $file_name;
		$minified_url = PERFMATTERS_CACHE_URL . 'minify/' . $file_name . (!empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '');

		//check if minified file already exists
		if(!is_file($minified_path)) {

			//check minify cache directory
            if(!is_dir(PERFMATTERS_CACHE_DIR . 'minify/')) {
                @mkdir(PERFMATTERS_CACHE_DIR . 'minify/', 0755, true);
            }

            //minify and save file
			$minifier_class = "\\MatthiasMullie\\Minify\\" . strtoupper($ext);
			$minifier = new $minifier_class($file_path);
		    $minifier->minify($minified_path);
		}

		//check if minified file is smaller than original
		$file_size = filesize($file_path);
		if(empty($file_size)) {
			return;
		}
		$file_size_min = filesize($minified_path);
		$bytes_wasted = $file_size - $file_size_min;
		$percent_wasted = ($bytes_wasted / $file_size) * 100;

		//still point to original file
		if($bytes_wasted < 1024 || $percent_wasted < 5) {
		    return;
		}

		return $minified_url;
	}

	//return exclusions array
	private static function get_exclusions($type) {

		if(!isset(self::$data['exclusions'][$type])) {
			
			//base exclusions
		    self::$data['exclusions'][$type] = array(
		    	'autoptimize_single'
		    );

		    //js
		    if($type == 'js') {
		    	self::$data['exclusions']['js'] = array_merge(self::$data['exclusions']['js'], array(
		    		'uploads/perfmatters'
		    	));
		    }
		    //css
		    elseif($type == 'css') {
		    	self::$data['exclusions']['css'] = array_merge(self::$data['exclusions']['css'], array(
		    		'/uploads/elementor/css/post-'
		    	));
		    }

		    //add manual exclusions
		    if(!empty(Config::$options['assets']['minify_' . $type . '_exclusions']) && is_array(Config::$options['assets']['minify_' . $type . '_exclusions'])) {
		        self::$data['exclusions'][$type] = array_merge(self::$data['exclusions'][$type], Config::$options['assets']['minify_' . $type . '_exclusions']);
		    }

		    //final filter
		    self::$data['exclusions'][$type] = apply_filters('perfmatters_minify_' . $type . '_exclusions', self::$data['exclusions'][$type]);
		}

		return self::$data['exclusions'][$type];
	}

	//delete all files in the minify cache directory
    public static function clear_minified($ext = '', $site = null)
    {      
        $path = '';

        //add site path if specified
        if(is_object($site) && !empty($site->path)) {
           $path = ltrim($site->path, '/');
        }

        $files = glob(PERFMATTERS_CACHE_DIR . $path . 'minify/*' . ($ext ? '.' . $ext : ''));
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
    }

    //queue admin bar actions
    public static function queue_admin_bar() {
    	add_action('admin_bar_menu', array('Perfmatters\Minify', 'admin_bar_menu'));
        add_action('admin_notices', array('Perfmatters\Minify', 'admin_notices'));
        add_action('admin_post_perfmatters_clear_minified', array('Perfmatters\Minify', 'admin_bar_clear_minified'));
    }

    //add admin bar menu item
    public static function admin_bar_menu(WP_Admin_Bar $wp_admin_bar) {

        if(!current_user_can('manage_options') || !perfmatters_network_access()) {
            return;
        }

        $menu_item = array(
            'parent' => 'perfmatters',
            'id'     => 'perfmatters-clear-minified',
            'title'  => __('Clear Minified JS/CSS', 'perfmatters'),
            'href'   => add_query_arg(array(
                'action'           => 'perfmatters_clear_minified',
                '_wp_http_referer' => rawurlencode($_SERVER['REQUEST_URI']),
                '_wpnonce'         => wp_create_nonce('perfmatters_clear_minified')
            ), 
            admin_url('admin-post.php'))
        );

        $wp_admin_bar->add_menu($menu_item);
    }

    //display admin notices
    public static function admin_notices() {

        if(get_transient('perfmatters_minified_cleared') === false) {
            return;
        }

        delete_transient('perfmatters_minified_cleared');
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Minified JS/CSS cleared.', 'perfmatters' ) . '</strong></p></div>';
    }

    //clear minified JS/CSS from admin bar
    public static function admin_bar_clear_minified() {

        if(!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'perfmatters_clear_minified')) {
            wp_nonce_ays('');
        }

        self::clear_minified();
        if(is_admin()) {
            set_transient('perfmatters_minified_cleared', 1);
        }

        //go back to url where button was pressed
        wp_safe_redirect(esc_url_raw(wp_get_referer()));
        exit;
    }
}