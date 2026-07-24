<?php
namespace Perfmatters\PMCS;

use WP_Admin_Bar;

class PMCS
{
    //default snippet meta schema
    private static $meta_defaults = [
        'name'          => '',
        'type'          => 'php',
        'description'   => '',
        'active'        => 0,
        'priority'      => 10,
        'location'      => '',
        'optimizations' => [],
        'conditions'    => [],
        'tags'          => [],
        'author'        => 0,
        'updated_by'    => 0,
        'created'       => '',
        'modified'      => '',
    ];

    //meta keys that need int values
    private static $meta_int_keys = [
        'active',
        'priority',
        'minify',
        'author',
        'updated_by'
    ];

    //cached snippet config from index.php
    private static $snippet_config_cache = null;

    //init
    public static function init()
    {
        //check for disable constant
        if(\Perfmatters\Config::$code_disabled) {
            return;
        }

        //guard against unwanted static asset requests
        if(isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if(is_string($path) && preg_match('/\.(map|js|css|mjs|json|png|jpg|jpeg|gif|svg|woff2?|ttf)$/i', $path)) {
                return; 
            }
        }

        //add pmcs settings loader to perfmatters settings page
        add_action('admin_menu', function() {
            global $perfmatters_settings_page;
            add_action('load-' . $perfmatters_settings_page, array(__CLASS__, 'settings_load'));
        }, 10);

        //filter screen options save
        add_filter('set-screen-option', function($status, $option, $value) {
            if($option === 'snippets_per_page') {
                self::save_snippet_sort_order_screen_options();
                return $value;
            }
            return $status;
        }, 10, 3);

        //data handler
        add_action('admin_init', array(__CLASS__, 'action_handler'));

        new Ajax();

        //safe mode check
        if(!self::disabled()) {
            self::run();
        }
    }

    /**
     * Whether to register PMCS global error handling for this request.
     * True when at least one active snippet is php or html (types that execute server-side code).
     */
    private static function should_init_pmcs_error_handler(array $config) {
        foreach($config['active'] as $snippet) {
            $type = $snippet['type'] ?? 'php';
            if($type === 'php' || $type === 'html') {
                return true;
            }
        }

        return false;
    }

    //load pmcs specific settings
    public static function settings_load() {

        //enqueue admin pmcs scripts + styles
        add_action('admin_enqueue_scripts', function() {

            //pmcs styles
            wp_enqueue_style('pmcs', PERFMATTERS_URL . 'css/pmcs.css', [], PERFMATTERS_VERSION);

            //pmcs script args
            $pmcs_js_args = array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('perfmatters-nonce'),
                'strings' => array(
                    'delete_snippet' => __('This will permanently delete the code snippet. Are you sure you want to proceed?', 'perfmatters'),
                    'copy' => __('Copy', 'perfmatters'),
                    'copied' => __('Copied', 'perfmatters')
                ),
                'code_types' => array(
                    'php' => array(
                        'mime' => Editor::get_mime_for_code_type('php'),
                        'lint' => Editor::is_lint_enabled('php')
                    ),
                    'js' => array(
                        'mime' => Editor::get_mime_for_code_type('js'),
                        'lint' => Editor::is_lint_enabled('js')
                    ),
                    'css' => array(
                        'mime' => Editor::get_mime_for_code_type('css'),
                        'lint' => Editor::is_lint_enabled('css')
                    ),
                    'html' => array(
                        'mime' => Editor::get_mime_for_code_type('html'),
                        'lint' => Editor::is_lint_enabled('html')
                    )
                )
            );

            //snippet edit: enqueue code editor before pmcs.js so initializer runs before our reveal logic
            $pmcs_deps = array('jquery');
            if(!empty($_GET['snippet'])) {

                $editor_type = 'php';

                if($_GET['snippet'] !== 'create') {
                    $current_snippet = Snippet::get($_GET['snippet']);
                    $editor_type     = $current_snippet['meta']['type'] ?? 'php';
                }
                elseif(!empty($_POST['type'])) {

                    //on create (including validation error re-render), prefer the submitted type from our form
                    $editor_type = $_POST['type'];
                }

                Editor::init($editor_type);
                $pmcs_deps[] = 'code-editor';

                wp_enqueue_script('pmcs-conditions', PERFMATTERS_URL . 'js/pmcs-conditions.js', [], time());
                wp_enqueue_script('pmcs-tags', PERFMATTERS_URL . 'js/pmcs-tags.js', [], time());
                $pmcs_js_args['tags'] = self::get_snippet_tags();
            }

            wp_enqueue_script('pmcs', PERFMATTERS_URL . 'js/pmcs.js', $pmcs_deps, PERFMATTERS_VERSION);

            wp_localize_script('pmcs', 'PMCS', $pmcs_js_args);

        });

        //add screen option for pagination
        $args = array(
            'label' => __('Code Snippets per page', 'perfmatters'),
            'default' => 10,
            'option' => 'snippets_per_page'
        );
        add_screen_option('per_page', $args);

        //sort order by screen options
        add_filter('screen_settings', array(__CLASS__, 'snippet_sort_order_screen_settings'), 10, 2);

        //default hidden columns
        add_filter('default_hidden_columns', function($hidden) {
            return array('author', 'tags', 'created', 'priority');
        });

        global $table;
        $table = new ListTable();
    }

    //render sort order fields in screen options
    public static function snippet_sort_order_screen_settings($settings, $screen) {

        if($screen->id !== 'settings_page_perfmatters') {
            return $settings;
        }

        $orderby = self::get_snippet_sort_orderby(false);
        $order   = self::get_snippet_sort_order(false);

        $settings .= '<fieldset class="screen-options">';
            $settings .= '<legend>' . esc_html__('Sort Order', 'perfmatters') . '</legend>';

            //order by
            $settings .= '<label for="snippets_orderby">' . esc_html__('Order by', 'perfmatters') . '</label>';
            $settings .= '<select name="snippets_orderby" id="snippets_orderby" style="margin-left: 5px; margin-right: 10px;">';
                foreach(self::get_snippet_sort_orderby_options() as $value => $label) {
                    $settings .= '<option value="' . esc_attr($value) . '"' . selected($orderby, $value, false) . '>' . esc_html($label) . '</option>';
                }
            $settings .= '</select>';

            //order
            $settings .= '<label for="snippets_order" class="screen-options-order">' . esc_html__('Order', 'perfmatters') . '</label>';
            $settings .= '<select name="snippets_order" id="snippets_order" style="margin-left: 5px;">';
                foreach(self::get_snippet_sort_order_options() as $value => $label) {
                    $settings .= '<option value="' . esc_attr($value) . '"' . selected($order, $value, false) . '>' . esc_html($label) . '</option>';
                }
            $settings .= '</select>';
        $settings .= '</fieldset>';

        return $settings;
    }

    //save sort order screen option values
    public static function save_snippet_sort_order_screen_options() {

        if(empty($_POST['screenoptionnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['screenoptionnonce'])), 'screen-options-nonce')) {
            return;
        }

        if(isset($_POST['snippets_orderby'])) {
            $orderby = sanitize_key(wp_unslash($_POST['snippets_orderby']));
            if(array_key_exists($orderby, self::get_snippet_sort_orderby_options())) {
                update_user_meta(get_current_user_id(), 'snippets_orderby', $orderby);
            }
        }

        if(isset($_POST['snippets_order'])) {
            $order = strtolower(sanitize_key(wp_unslash($_POST['snippets_order'])));
            if(array_key_exists($order, self::get_snippet_sort_order_options())) {
                update_user_meta(get_current_user_id(), 'snippets_order', $order);
            }
        }
    }

    //allowed sort order by values for snippet list
    public static function get_snippet_sort_orderby_options() {
        return array(
            'name'     => __('Name', 'perfmatters'),
            'created'  => __('Created', 'perfmatters'),
            'modified' => __('Modified', 'perfmatters'),
        );
    }

    //allowed order values for snippet list
    public static function get_snippet_sort_order_options() {
        return array(
            'asc'  => __('Ascending', 'perfmatters'),
            'desc' => __('Descending', 'perfmatters'),
        );
    }

    //get active snippet list orderby
    public static function get_snippet_sort_orderby($use_request = true) {

        $orderby = 'created';

        if($use_request && !empty($_GET['orderby'])) {
            $orderby = sanitize_key(wp_unslash($_GET['orderby']));
        }
        else {
            $saved = get_user_option('snippets_orderby');
            if(!empty($saved)) {
                $orderby = sanitize_key($saved);
            }
        }

        if(!array_key_exists($orderby, self::get_snippet_sort_orderby_options())) {
            $orderby = 'created';
        }

        return $orderby;
    }

    //get active snippet list order direction
    public static function get_snippet_sort_order($use_request = true) {

        $order = 'desc';

        if($use_request && !empty($_GET['order'])) {
            $order = strtolower(sanitize_key(wp_unslash($_GET['order'])));
        }
        else {
            $saved = get_user_option('snippets_order');
            if(!empty($saved)) {
                $order = strtolower(sanitize_key($saved));
            }
        }

        if(!array_key_exists($order, self::get_snippet_sort_order_options())) {
            $order = 'desc';
        }

        return $order;
    }

    //snippet action handling
    public static function action_handler()
    {
        if(!empty($_GET['page']) && $_GET['page'] == 'perfmatters') {

            //permissions and network scope check
            if(!current_user_can('manage_options') || !perfmatters_network_access()) {
                return;
            }

            global $pmcs_error;

            //bulk actions
            if(!empty($_GET['action2']) && !empty($_GET['snippets'])) {
                self::verify_action_nonce('pmcs_nonce', 'pmcs-action');
                $snippet_files = self::normalize_snippet_file_names($_GET['snippets']);

                if(empty($snippet_files)) {
                    self::admin_notice_redirect('action2', 'file_not_found');
                }

                switch($_GET['action2']) {

                    case 'deactivate':

                        foreach($snippet_files as $snippet) {
                            Snippet::deactivate($snippet);
                        }

                        //success message
                        self::admin_notice_redirect('action2', 'deactivated');

                        break;

                    case 'activate':

                        $config = self::get_snippet_config();

                        foreach($snippet_files as $snippet) {
                            if(!isset($config['error_files'][$snippet])) {
                                Snippet::activate($snippet);
                            }
                        }

                        //success message
                        self::admin_notice_redirect('action2', 'activated');

                        break;

                    case 'export':

                        Transfer::export($snippet_files);

                        break;

                    case 'delete':

                        Snippet::delete($snippet_files);

                        //success message
                        self::admin_notice_redirect('action2', 'deleted');

                        break;
                }
            }

            //export snippet
            if(!empty($_GET['export'])) {
                self::verify_action_nonce('_wpnonce', 'pmcs-action');
                $file_name = self::normalize_snippet_file_name($_GET['export']);

                if(empty($file_name)) {
                    self::admin_notice_redirect('export', 'file_not_found');
                }

                Transfer::export($file_name);
            }

            //delete snippet
            if(!empty($_GET['delete'])) {
                self::verify_action_nonce('_wpnonce', 'pmcs-action');

                //vars
                $file_name = self::normalize_snippet_file_name($_GET['delete']);

                if(empty($file_name)) {
                    self::admin_notice_redirect('delete', 'file_not_found');
                }

                $file = self::get_storage_dir() . '/' . $file_name;

                //file not found
                if(!is_file($file) || $file_name === 'index.php') {
                    self::admin_notice_redirect('delete', 'file_not_found');
                }

                //delete
                Snippet::delete($file_name);

                //success message
                self::admin_notice_redirect('delete', 'deleted');
            }

            //single snippet
            if(!empty($_GET['snippet'])) {

                //file not found
                if($_GET['snippet'] !== 'create') {
                    $file_name = self::normalize_snippet_file_name($_GET['snippet']);

                    if(empty($file_name)) {
                        self::admin_notice_redirect('snippet', 'file_not_found');
                    }

                    $file = self::get_storage_dir() . '/' . $file_name;
                    if(!is_file($file) || $file_name === 'index.php') {
                        self::admin_notice_redirect('snippet', 'file_not_found');
                    }
                }
                
                //save snippet
                if(!empty($_POST['save_snippet'])) {
                    self::verify_action_nonce('nonce', 'pmcs-nonce');

                    $save = Snippet::save();

                    if(!empty($save) && $_GET['snippet'] == 'create') {
                        
                        //redirect to snippet editor for created snippet
                        $url = add_query_arg(array(
                            'snippet' => $save,
                            'message' => 'saved'
                        ));

                        wp_redirect($url);
                        exit;
                    }
                }
            }

            if(!empty($_GET['enable_safe_mode'])) {
                self::verify_action_nonce('_wpnonce', 'pmcs-action');

                $config = self::get_snippet_config();
                $config['meta']['force_disabled'] = 1;
                self::update_snippet_config($config);
                self::admin_notice_redirect('enable_safe_mode', '');
            }

            //disable safe mode
            if(!empty($_GET['disable_safe_mode'])) {
                self::verify_action_nonce('_wpnonce', 'pmcs-action');

                $config = self::get_snippet_config();
                //if($config && hash_equals($config['meta']['secret_key'], $_REQUEST['pmcs_secret'])) {
                $config['meta']['force_disabled'] = 0;
                self::update_snippet_config($config);

                //success message
                self::admin_notice_redirect('disable_safe_mode', 'safe_mode_disabled');
            }

            //config
            $config = self::get_snippet_config();

            if(defined('PMCS_SAFE_MODE') && PMCS_SAFE_MODE) {
                self::admin_notice('safe_mode', __('Safe mode is enabled in wp-config.php.', 'perfmatters'), 'warning', false);
            }
            elseif(!empty($config['meta']['force_disabled'])) {
                $safe_mode_url = wp_nonce_url(add_query_arg('disable_safe_mode', true), 'pmcs-action');
                self::admin_notice('safe_mode', __('Safe mode is enabled.', 'perfmatters') . ' <a href="' . esc_url($safe_mode_url) . '#code">' . __('Disable', 'perfmatters') . '</a>', 'warning', false);
            }

            //notice messages
            $message_key = isset($_GET['message']) ? sanitize_key(wp_unslash($_GET['message'])) : '';

            if(!empty($message_key)) {

                $messages = [
                    'saved'                => ['snippet_saved', __('Snippet saved.', 'perfmatters'), 'success'],
                    'deleted'              => ['delete_success', __('Snippet deleted successfully.', 'perfmatters'), 'success'],
                    'activated'            => ['activate_success', __('Snippet activated successfully.', 'perfmatters'), 'success'],
                    'deactivated'          => ['deactivate_success', __('Snippet deactivated successfully.', 'perfmatters'), 'success'],
                    'file_not_found'       => ['file_not_found', __('File not found.', 'perfmatters'), 'error'],
                    'safe_mode_disabled'   => ['safe_mode_disabled', __('Safe mode disabled.', 'perfmatters'), 'success'],
                    'invalid_nonce'        => ['invalid_nonce', __('Security check failed. Please try again.', 'perfmatters'), 'error'],
                ];

                if(isset($messages[$message_key])) {
                    self::admin_notice(...$messages[$message_key]);
                }
            }
        }
    }

    //verify nonce for the given request key + action
    private static function verify_action_nonce($nonce_key, $action)
    {
        $nonce = isset($_REQUEST[$nonce_key]) ? sanitize_text_field(wp_unslash($_REQUEST[$nonce_key])) : '';

        if(!$nonce || !wp_verify_nonce($nonce, $action)) {
            self::admin_notice_redirect(
                [
                    'action', 
                    'action2', 
                    'snippets', 
                    'delete', 
                    'export', 
                    'enable_safe_mode', 
                    'disable_safe_mode', 
                    '_wpnonce', 
                    '_wp_http_referer', 
                    'nonce', 
                    'save_snippet'
                ], 
                'invalid_nonce'
            );
        }
    }

    //normalize one snippet filename and reject invalid values
    public static function normalize_snippet_file_name($file_name)
    {
        if(!is_scalar($file_name)) {
            return '';
        }

        $file_name = basename(sanitize_file_name((string) $file_name));

        if(empty($file_name) || $file_name === 'index.php' || !preg_match('/\.php$/i', $file_name)) {
            return '';
        }

        return $file_name;
    }

    //normalize a list of snippet filenames
    public static function normalize_snippet_file_names($file_names)
    {
        $normalized = [];

        foreach((array) $file_names as $file_name) {
            $file_name = self::normalize_snippet_file_name($file_name);
            if(!empty($file_name)) {
                $normalized[] = $file_name;
            }
        }

        return array_values(array_unique($normalized));
    }

    //display message in admin notice
	public static function admin_notice($id, $message, $type, $dismissible = true)
	{
		add_action('pmcs_admin_notice', function () use ($id, $message, $type, $dismissible) {
			wp_admin_notice(
				wp_kses_post($message),
				array(
					'id'                 => $id,
					'type'               => $type,
					'dismissible'        => $dismissible,
					'additional_classes' => array( 'inline' ),
				)
			);
		});

		if($type === 'error') {
			global $pmcs_error;
			$pmcs_error = true;
		}
	}

    //redirect with admin notice
	public static function admin_notice_redirect($query_param, $message)
	{
		if(!empty($query_param)) {
			$url = remove_query_arg((array) $query_param);
		}
		
		$url = add_query_arg('message', $message ?? '', $url ?? false);

		wp_redirect($url);
		exit;
	}

    //validate php code
	public static function validate_php($code)
    {
        $validator = new PhpValidator($code);

        $result = $validator->validate();

        if(is_wp_error($result)) {
            return $result;
        }

        return true;
    }

 	//get directory path for saved snippets
    public static function get_storage_dir()
    {
        return \Perfmatters\Utilities::get_uploads_dir('/perfmatters/code-snippets');
    }

    //return snippet config file array
    public static function get_snippet_config($cached = true)
    {
        //return cached config
        if(self::$snippet_config_cache !== null && $cached) {
            return self::$snippet_config_cache;
        }

        //get config from file
        $config_file = self::get_storage_dir() . '/index.php'; 

        if(!is_file($config_file)) {
            return [];
        }

        $loaded_config = include $config_file; 
        
        self::$snippet_config_cache = is_array($loaded_config) ? $loaded_config : []; 

        return self::$snippet_config_cache;
    }

    //clear cached snippet config after index.php is updated
    public static function reset_snippet_config_cache()
    {
        self::$snippet_config_cache = null;
    }

    //generate doc block for given meta array
    public static function get_doc_block($meta)
    {
        $meta = array_intersect_key($meta, self::$meta_defaults);
        $meta = array_merge(self::$meta_defaults, $meta);

        $now = current_time('mysql');
        $user_id = get_current_user_id();

        //apply dynamic defaults and tracking data
        if(empty($meta['name'])) {
            $meta['name'] = 'Snippet Created @ ' . $now;
        }

        if(empty($meta['author'])) {
            $meta['author'] = $user_id;
        }
        $meta['updated_by'] = $user_id;

        if(empty($meta['created'])) {
             $meta['created'] = $now;
        }
        $meta['modified'] = $now;

        $meta['priority'] = self::get_priority($meta['priority'] ?? null);

        //filter optimizations
        if(isset($meta['optimizations']) && is_array($meta['optimizations'])) {
            $meta['optimizations'] = json_encode(array_filter($meta['optimizations']));
        }

        //filter conditions data
        if(isset($meta['conditions']) && is_array($meta['conditions'])) {

            $cleaned_conditions = [];
            
            foreach($meta['conditions'] as $type => $condition_group) {

                if(!is_array($condition_group)) {
                    continue;
                }

                $filtered_group = array_filter($condition_group, function($entry) {
                    return is_array($entry) && !empty($entry['rule']);
                });

                if(!empty($filtered_group)) {
                    $cleaned_conditions[$type] = array_values($filtered_group);
                }
            }
            
            $meta['conditions'] = json_encode($cleaned_conditions);
        }

        if(isset($meta['tags']) && is_array($meta['tags'])) {
            $meta['tags'] = json_encode(array_filter($meta['tags']));
        }
                
        //generate doc block string
        $doc_block_string = '<?php' . PHP_EOL . '// <Internal Doc Start>' . PHP_EOL . '/*';
        foreach($meta as $key => $value) {

            if(!isset(self::$meta_defaults[$key])) {
                continue;
            }

            $sanitized_value = self::sanitize_meta_value($value);

            if($sanitized_value === '') {
                continue;
            }

            $doc_block_string.= PHP_EOL . '* @' . $key . ': ' . $sanitized_value;
        }
        $doc_block_string.= PHP_EOL . '*/' . PHP_EOL . '?>' . PHP_EOL . '<?php if(!defined("ABSPATH")) {return;} // <Internal Doc End> ?>' . PHP_EOL;

        return $doc_block_string;
    }

    //make sure the meta value is safe to save in the doc block
    public static function sanitize_meta_value($value)
    {
        if(empty($value) || is_numeric($value)) {
            return $value;
        }

        $value = str_replace('*/', '', $value);

        return $value;
    }

    //parse doc block from file and convert into formatted meta array
    public static function parse_doc_block($file_content, $code_only = false)
    {
        // get content from // <Internal Doc Start> to // <Internal Doc End>
        $file_content = explode('// <Internal Doc Start>', $file_content);

        if(count($file_content) < 2) {

            if($code_only) {
                return '';
            }

            return [null, null];
        }

        $parts = preg_split('/\/\/ <Internal Doc End> \?>\R/u', $file_content[1], 2);

        //backward compat for snippets saved with a fixed PHP_EOL delimiter
        if(count($parts) < 2) {
            $parts = explode('// <Internal Doc End> ?>' . PHP_EOL, $file_content[1]);
        }

        $doc_block_string = $parts[0];
        $code = $parts[1] ?? '';

        if($code_only) {
            return $code;
        }

        $meta = [];

        //match each * @key: value block; value may span multiple lines until the next field
        if(preg_match_all('/\*\s*@([a-z_]+):\s*(.*?)(?=\R\*\s*@|\*\/)/su', $doc_block_string, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {
                $key = $match[1];
                $val = rtrim($match[2]);

                //backward compat for descriptions saved with encoded newlines
                $val = str_replace('\n', "\n", $val);

                if(!$key || $val === '') {
                    continue;
                }

                if(!isset(self::$meta_defaults[$key])) {
                    continue;
                }

                $meta[$key] = in_array($key, self::$meta_int_keys) ? (int) $val : $val;
            }
        }

        //decode optimizations
        if(!empty($meta['optimizations'])) {
            $data = json_decode($meta['optimizations'], true);
            
            if(is_array($data)) {
                foreach($data as $opt_key => $opt_val) {
                    if (in_array($opt_key, self::$meta_int_keys)) {
                        $data[$opt_key] = (int) $opt_val;
                    }
                }
                $meta['optimizations'] = $data;
            } 
            else {
                $meta['optimizations'] = self::$meta_defaults['optimizations'];
            }
        }

        //decode tags
        if(!empty($meta['tags'])) {
            $data = json_decode($meta['tags'], true);
            if(is_array($data)) {
                $meta['tags'] = $data;
            }
        }

        //decode conditions
        if(!empty($meta['conditions'])) {
            $data = json_decode($meta['conditions'], true);
            if(is_array($data)) {
                $meta['conditions'] = $data;
            }
        }

        $meta = array_merge(self::$meta_defaults, $meta);

        return [$meta, $code];
    }

    //save local js or css file for snippet
    public static function cache_js_css($file_name, $meta = [], $code = '')
    {
        //file type directory
        $type_dir = self::get_storage_dir() . '/' . $meta['type'];
        
        //create directory if needed
        if(!is_file($type_dir)) {
            wp_mkdir_p($type_dir);
        }

        $file_name_sans_ext = str_replace('.php', '', $file_name);
        $type_file_name = $file_name_sans_ext . '.' . $meta['type'];
        $type_file_name_minified = $file_name_sans_ext . '.min.' . $meta['type'];
        $type_file_minified = $type_dir . '/' . $type_file_name_minified;

        //add file
        file_put_contents($type_dir . '/' . $type_file_name, $code);

        //add minified file version
        if(!empty($meta['optimizations']['minify'])) {

			$minifier_class = "\\MatthiasMullie\\Minify\\" . strtoupper($meta['type']);
			$minifier = new $minifier_class($type_dir . '/' . $type_file_name);
		    $minifier->minify($type_file_minified);

		    return $type_file_name_minified;
        }

        //remove minified file
        if(file_exists($type_file_minified)) {
        	@unlink($type_file_minified);
        }

        return $type_file_name;
    }

    //build fresh snippet config
    public static function build_snippet_config()
    {
        $previous_config = self::get_snippet_config(false) ?: [];

        $data = [
            'active' => [],
            'inactive' => [],
            'meta' => [
                'secret_key'      => ($previous_config['meta'] ?? [])['secret_key'] ?? bin2hex(random_bytes(16)),
                'force_disabled'  => ($previous_config['meta'] ?? [])['force_disabled'] ?? 0,
                'cached_at'      => date('Y-m-d H:i:s'),
                'cached_version' => PERFMATTERS_VERSION,
                'cached_domain'  => site_url(),
            ],
            'error_files' => $previous_config['error_files'] ?? []
        ];

        //get the file paths and store them in an array
        $files = glob(self::get_storage_dir() . '/*.php');

        $snippets = [];
        foreach($files as $file) {
            [$doc_block_array, $code] = self::parse_doc_block(file_get_contents($file));

            if(!$doc_block_array) {
                continue;
            }

            $snippets[] = [
                'meta'   => $doc_block_array,
                'code'   => $code,
                'file'   => $file
            ];
        }

        if(!empty($snippets)) {

            usort($snippets, function ($a, $b) {
                return $a['meta']['priority'] <=> $b['meta']['priority'];
            });

            foreach($snippets as $snippet) {

                $file_name = basename($snippet['file']);

                //filter out invalid keys
                $filtered_meta = array_intersect_key($snippet['meta'] ?? [], self::$meta_defaults);
                $meta = array_merge(self::$meta_defaults, $filtered_meta);
                
                $meta['file_name'] = $file_name;

                $status = (!empty($meta['active'])) ? 'active' : 'inactive';

                $data[$status][$file_name] = $meta;
            }
        }

        return self::update_snippet_config($data);
    }

    //update snippet config file with given data
    public static function update_snippet_config($data)
    {
        $config_file = self::get_storage_dir() . '/index.php';

        if(!is_file($config_file)) {
            wp_mkdir_p(dirname($config_file));
        }

        $code = <<<PHP
<?php
if(!defined("ABSPATH")) {return;}
/*
 * This file was generated by Perfmatters.
 * Please do not edit manually.
 */

PHP;

        $code.= 'return ' . var_export($data, true) . ';';

        $return = file_put_contents($config_file, $code);

        //clear opcache if the server uses it
        if($return && function_exists('opcache_invalidate')) {
            @opcache_invalidate($config_file, true); 
        }

        self::reset_snippet_config_cache();

        return $return;
    }


    //get array ot tags in all snippets
    public static function get_snippet_tags()
    {
        $config = self::get_snippet_config();

        if(!$config || empty($config['meta'])) {
            return [];
        }

        if(empty($config['active']) && empty($config['inactive'])) {
            return [];
        }

        $snippets = array_merge($config['active'], $config['inactive']);

        $all_tags = [];

        foreach($snippets as $snippet) {
            if(!empty($snippet['tags'])) {
                $all_tags = array_merge($all_tags, $snippet['tags']);
            }
        }

        $all_tags = array_unique($all_tags);
        asort($all_tags);

        return array_values($all_tags);
    }

    //run snippets
    public static function run()
    {
    	//no config
    	if(!is_file(self::get_storage_dir() . '/index.php')) {
            return;
        }

        //load config
        $config = include self::get_storage_dir() . '/index.php';

        //no valid config
        if(empty($config)) {
            return;
        }

        //no snippets
        if(empty($config['active']) && empty($config['inactive'])) {
            return;
        }

        //add admin bar menu item for snippets
        add_action('admin_bar_menu', array('Perfmatters\PMCS\PMCS', 'admin_bar_menu'), 1);

        //register before active check; map populated in loop below (by reference)
        $shortcode_snippets = [];

        add_shortcode('pmcs', function($atts) use (&$shortcode_snippets) {
            return self::render_shortcode($atts, $shortcode_snippets);
        });

        //no active snippets
        if(empty($config['active']) || !is_array($config['active'])) {
            return;
        }

        //forcefully disabled via URL
        if(!empty($config['meta']['force_disabled'])) {
            return;
        }

        if(self::should_init_pmcs_error_handler($config)) {
            Error::init();
        }

        $invalid_files = false;

        $content_filters = [
            'before_content' => [
                'hook'   => 'the_content',
                'insert' => 'before'
            ],
            'after_content'  => [
                'hook'   => 'the_content',
                'insert' => 'after'
            ]
        ];

        foreach($config['active'] as $file_name => $snippet) {

            //dont run the snippet if it is being saved
            if(!empty($_GET['page']) && $_GET['page'] == 'perfmatters' && !empty($_POST['file_name']) && $_POST['file_name'] === $file_name) {
                continue;
            }

            //snippet has an error
            if($config['error_files'] && isset($config['error_files'][$file_name])) {
                continue;
            }

            $file = self::get_storage_dir() . '/' . sanitize_file_name($file_name);

            //mark invalid file
            if(!is_file($file)) {
                $invalid_files = true;
                continue;
            }

            switch($snippet['type']) {

                case 'php':

                    //need to run later if we have conditions
                    $hook = !empty($snippet['conditions']) ? 'wp' : 'setup_theme';

                    //action hook
                    add_action($hook, function() use($file, $snippet) {

                        //location check
                        if(!empty($snippet['location'])) {

                        	//admin only
                        	if($snippet['location'] == 'admin' && !is_admin()) {
                        		return;
                        	}

                        	//frontend only
                        	elseif($snippet['location'] == 'frontend' && is_admin()) {
                        		return;
                        	}
                        }

                        //condition check
                        if(!Conditions::evaluate($snippet['conditions'])) {
                            return;
                        }

                        //load snippet file
                        require_once($file);

                    }, self::get_priority($snippet['priority'] ?? null));

                    break;

                case 'js':

                    $method = $snippet['optimizations']['method'] ?? '';
                    $minify = $snippet['optimizations']['minify'] ?? 0;

                	//cached file + path
                	$script_name = str_replace('.php', '', $file_name);
                	$cached_file_name = $script_name . ($minify ? '.min' : '') . '.' . $snippet['type'];
                    $cached_file_path = self::get_storage_dir() . '/' . $snippet['type'] . '/' . $cached_file_name;

                    //cached file exists
                    if(file_exists($cached_file_path)) {

                    	//action variables
                		$location = $snippet['location'] ?? 'wp_footer';
                		$cached_file_url = '';
                        $is_footer = ($location == 'wp_footer' || $location == 'admin_footer');

                        //file method adjustments
                        if($method == 'file') {

                        	//cached file url
                    	 	if($cached_file_url = self::get_cached_file_url($cached_file_name, $snippet['type'])) {

                                $location = ($location == 'admin_head' || $location == 'admin_footer') ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';

                                //file method behaviors
                                if(!empty($snippet['optimizations']['behavior'])) {

                                    //preload behavior
                                    if($snippet['optimizations']['behavior'] == 'preload') {

                                        \Perfmatters\Preload::$snippet_optimizations[] = array(
                                            'as' => 'script',
                                            'url' => 'pmcs-' . $script_name
                                        );
                                    }
                                }
                    	 	}
                        }

                        //inline + file method behaviors
                        if(!empty($snippet['optimizations']['behavior'])) {
                            if($snippet['optimizations']['behavior'] == 'delay') {
                                \Perfmatters\JS::$snippet_optimizations['pmcs-' . $script_name . '-js'] = 'delay';
                            }
                            elseif($snippet['optimizations']['behavior'] == 'defer') {
                                \Perfmatters\JS::$snippet_optimizations['pmcs-' . $script_name . '-js'] = 'defer';
                            }
                        }

                        //script enqueues
                        add_action($location, function() use($snippet, $method, $script_name, $cached_file_url, $cached_file_path, $is_footer) {

                            //condition check
                            if(!Conditions::evaluate($snippet['conditions'])) {
                                return;
                            }

                            //enqueue cached file
                            if($method == 'file') {
                                wp_enqueue_script('pmcs-' . $script_name, $cached_file_url, [], strtotime($snippet['modified']), $is_footer);
                            } 

                            //print cached file inline
                            else {
                                $code = file_get_contents($cached_file_path);
                                if($code) {
                                	$tag = $snippet['type'] == 'js' ? 'script' : 'style';
                                	echo '<script id="pmcs-' . $script_name . '-' . $snippet['type'] . '">' . $code . '</script>';
                                }
                            }

                        }, self::get_priority($snippet['priority'] ?? null));
                	}

                    break;

                case 'css':

                    $method = $snippet['optimizations']['method'] ?? '';
                    $minify = $snippet['optimizations']['minify'] ?? 0;

                    //cached file + path
                    $script_name = str_replace('.php', '', $file_name);
                    $cached_file_name = $script_name . ($minify ? '.min' : '') . '.' . $snippet['type'];
                    $cached_file_path = self::get_storage_dir() . '/' . $snippet['type'] . '/' . $cached_file_name;

                    //cached file exists
                    if(file_exists($cached_file_path)) {

                        //action variables
                        $location = $snippet['location'] ?? 'wp_head';
                        $cached_file_url = '';
                        $is_footer = ($location == 'wp_footer' || $location == 'admin_footer');

                        //file method adjustments
                        if($method == 'file') {

                            //cached file url
                            if($cached_file_url = self::get_cached_file_url($cached_file_name, 'css')) {

                                //file method behaviors
                                if(!empty($snippet['optimizations']['behavior'])) {

                                    //css preload behavior
                                    if($snippet['optimizations']['behavior'] == 'preload') {

                                        \Perfmatters\Preload::$snippet_optimizations[] = array(
                                            'as' => 'style',
                                            'url' => 'pmcs-' . $script_name
                                        );
                                    }
                                    //css async behavior
                                    elseif($snippet['optimizations']['behavior'] == 'async') {
                                        \Perfmatters\CSS::$snippet_optimizations['pmcs-' . $script_name . '-css'] = 'async';
                                    }
                                    //css delay behavior
                                    elseif($snippet['optimizations']['behavior'] == 'delay') {
                                        \Perfmatters\CSS::$snippet_optimizations['pmcs-' . $script_name . '-css'] = 'delay';
                                    }
                                }
                            }
                        }

                        //enqueue css file
                        if($method == 'file' && !$is_footer) {

                            $enqueue_hook = ($location == 'admin_head') ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
                            add_action($enqueue_hook, function() use($snippet, $script_name, $cached_file_url) {

                                //condition check
                                if(!Conditions::evaluate($snippet['conditions'])) {
                                    return;
                                }
                                
                                //enqueue stylesheet
                                wp_enqueue_style('pmcs-' . $script_name, $cached_file_url, [], strtotime($snippet['modified']));

                            }, self::get_priority($snippet['priority'] ?? null));

                            break;
                        }

                        //print css on action hook
                        add_action($location, function() use($snippet, $method, $script_name, $cached_file_url, $cached_file_path) {

                            //condition check
                            if(!Conditions::evaluate($snippet['conditions'])) {
                                return;
                            }

                            //file
                            if($method == 'file') {
                                wp_register_style('pmcs-' . $script_name, $cached_file_url, [], strtotime($snippet['modified']));
                                echo '<link rel="stylesheet" id="pmcs-' . $script_name . '-css" href="' . $cached_file_url . '" media="all">';
                            }

                            //inline
                            else {
                                $code = file_get_contents($cached_file_path);
                                if($code) {
                                    echo '<style id="pmcs-' . $script_name . '-css">' . $code . '</style>';
                                }
                            }

                        }, self::get_priority($snippet['priority'] ?? null));
                    }

                    break;

                case 'html':

                    $location = $snippet['location'];

                    if($location === 'shortcode') {

                        $shortcode_snippets[str_replace('.php', '', $file_name)] = [
                            'file'    => $file,
                            'snippet' => $snippet
                        ];

                        break;
                    }

                    if(in_array($location, ['wp_head', 'wp_body_open', 'wp_footer'])) {

                        add_action($location, function() use($file, $snippet) {

                           //condition check
                            if(!Conditions::evaluate($snippet['conditions'])) {
                                return;
                            }

                            require_once $file;

                        }, self::get_priority($snippet['priority'] ?? null));
                    }

                    //content filters
                    elseif(isset($content_filters[$location])) {

                        $filter = $content_filters[$location];

                        add_filter($filter['hook'], function($content) use($file, $snippet, $filter) {

                            //only singular post content
                            if(!is_singular() || !in_the_loop() || !is_main_query()) {
                                return $content;
                            }

                            //condition check
                            if(!Conditions::evaluate($snippet['conditions'])) {
                                return $content;
                            }

                            ob_start();
                            require_once $file;
                            $result = ob_get_clean();

                            if($result) {
                                if($filter['insert'] == 'before') {
                                    return $result . $content;
                                }

                                return $content . $result;
                            }
                            return $content;
                        }, self::get_priority($snippet['priority'] ?? null));
                    }

                    break;

                default:
                    break;
            }
        }

        if($invalid_files) {
            self::build_snippet_config();
        }
    }

    //render pmcs shortcode output
    public static function render_shortcode($atts, $shortcode_snippets)
    {
        $atts = shortcode_atts(['id' => ''], $atts, 'pmcs');

        if(empty($atts['id'])) {
            return '';
        }

        $id = preg_replace('/\.php$/i', '', sanitize_file_name($atts['id']));

        if(empty($id) || !isset($shortcode_snippets[$id])) {
            return '';
        }

        $entry = $shortcode_snippets[$id];

        if(!Conditions::evaluate($entry['snippet']['conditions'])) {
            return '';
        }

        if(!is_file($entry['file'])) {
            return '';
        }

        ob_start();
        require $entry['file'];
        return ob_get_clean();
    }

    //get url of cached js or css file
    private static function get_cached_file_url($file_name, $code_type)
    {
        $file = self::get_storage_dir() . '/' . $code_type . '/' . $file_name;

        if(!file_exists($file)) {
            return false;
        }

        return \Perfmatters\Utilities::get_uploads_url('/perfmatters/code-snippets/' . $code_type . '/' . $file_name);
    }

    //format and return a date string for better readability
    public static function human_date(string $date) : string {

        if(empty($date)) {
            return '';
        }

        try {

            //target datetime in local time
            $target_local = new \DateTimeImmutable($date, wp_timezone());

            //current datetime in local time
            $now_local = current_datetime();

            //timestamps
            $target_local_timestamp = $target_local->getTimestamp();
            $now_local_timestamp = $now_local->getTimestamp();
            
            //time difference
            $time_diff = $now_local_timestamp - $target_local_timestamp;

        }
        catch(Exception $e) {
            
            //log parsing error
            error_log('Date processing error in human_date: ' . $e->getMessage());
            return sprintf('<span title="%s">%s</span>', $date, $date);
        }
        
        //time difference less than a year
        if($time_diff >= 0 && $time_diff < YEAR_IN_SECONDS) {
            
            //human_time_diff with local timestamps
            $human_time = sprintf(__('%s ago', 'perfmatters'), human_time_diff($target_local_timestamp, $now_local_timestamp));
        }

        //too old or in the future, display orginal target date in local time
        else {
            $human_time = wp_date(get_option('date_format'), $target_local_timestamp);
        }

        //return human time with original date in the title attribute
        return sprintf('<span title="%s">%s</span>', $date, $human_time);
    }

    //check if snippets have been forcefully disabled
    public static function disabled() {
        $result = (defined('PMCS_SAFE_MODE') && PMCS_SAFE_MODE) || !apply_filters('perfmatters_code_snippets', true);

        if($result) {
            return true;
        }

        if(isset($_REQUEST['pmcs_secret'])) {
            $config = self::get_snippet_config();
            if($config && hash_equals($config['meta']['secret_key'], $_REQUEST['pmcs_secret'])) {
                $config['meta']['force_disabled'] = 1;
                self::update_snippet_config($config);
                header('Location: ' . admin_url('admin.php?page=perfmatters#code'));
                die();
            }
        }

        return false;
    }

    //add admin bar menu item
    public static function admin_bar_menu(WP_Admin_Bar $wp_admin_bar) {

        if(!current_user_can('manage_options') || !perfmatters_network_access()) {
            return;
        }

        $menu_item = array(
            'parent' => 'perfmatters',
            'id'     => 'perfmatters-code-snippets',
            'title'  => __('Code Snippets', 'perfmatters'),
            'href'   => admin_url('options-general.php?page=perfmatters#code')
        );

        $wp_admin_bar->add_menu($menu_item);
    }

    //check and validate snippet priority value
    public static function get_priority($priority, $default = 10) {
        return is_numeric($priority) ? (int) $priority : $default;
    }
}