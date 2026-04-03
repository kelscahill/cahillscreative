<?php
namespace Perfmatters\PMCS;

class Snippet
{
	//save snippet
	public static function save()
	{
		//permission check
		if(!current_user_can('manage_options')) {
			return PMCS::admin_notice('permission_denied', __('Permission denied.', 'perfmatters'), 'error');
		}

		//nonce check
		if(!check_ajax_referer('pmcs-nonce', 'nonce', false)) {
			return PMCS::admin_notice('invalid_code', __('Nonce is invalid.', 'perfmatters'), 'error');
		}

		//get rid of wordpress' auto escaping
		$_POST = wp_unslash($_POST);

		//name check
		if(empty($_POST['name'])) {
			return PMCS::admin_notice('missing_name', __('No snippet name given.', 'perfmatters'), 'error');
		}

		//new file
		if(empty($_POST['file_name'])) {

			//get file count
	        $file_count = count(glob(PMCS::get_storage_dir() . '/*.php'));
	        if(!$file_count) {
	            PMCS::build_snippet_config();
	            $file_count = 1;
	        }

	        //first four words max
	        $file_title = $_POST['name'];
	        $name_words = explode(' ', $file_title);
	        if(count($name_words) > 4) {
	            $name_words = array_slice($name_words, 0, 4);
	            $file_title = implode(' ', $name_words);
	        }

	        //get file name
	        $file_title = sanitize_title($file_title, 'snippet');
	        $file_name = $file_count . '-' . $file_title . '.php';
	        $file_name = sanitize_file_name($file_name);

	        //file path
	        $file = PMCS::get_storage_dir() . '/' . $file_name;

	        //check it
	        if(is_file($file)) {
	            return PMCS::admin_notice('file_exists', __('File already exists.', 'perfmatters'), 'error');
	        }
		}

		//existing file
		else {

			//check file from existing file name
			$file_name = PMCS::normalize_snippet_file_name($_POST['file_name']);
			if(empty($file_name)) {
				return PMCS::admin_notice('file_not_found', __('File not found.', 'perfmatters'), 'error');
			}

			$file = PMCS::get_storage_dir() . '/' . $file_name;
			if(!is_file($file)) {
	            return PMCS::admin_notice('file_not_found', __('File not found.', 'perfmatters'), 'error');
	        }

	        //get existing file data
        	[$existing_meta, $existing_code] = PMCS::parse_doc_block(file_get_contents($file));
		}

       	//prep our meta data
        $meta = array(
        	'file_name' => $file_name ?? '',
        	'name' => $_POST['name'] ?? '',
        	'type' => $_POST['type'] ?? '',
        	'active' => $_POST['active'] ?? 0,
        	'location' => $_POST['location'] ?? '',
        	'priority' => PMCS::get_priority($_POST['priority'] ?? null),
        	'optimizations' => $_POST['optimizations'] ?? [],
        	'tags' => $_POST['tags'] ? array_filter(array_map('trim', explode(',', $_POST['tags']))) : [],
        	'description' => $_POST['description'] ?? '',
        	'conditions' => $_POST['conditions'] ?? [],
        	'created' => $existing_meta['created'] ?? ''
        );

        //handle code separate
        $code = $_POST['code'] ?? '';

        //initial checks for unwanted tags
        if($_POST['type'] == 'php') {

            //check start for <?php
            if(preg_match('/^<\?php/', $code)) {
            	return PMCS::admin_notice('invalid_code', __('Please remove <?php from the beginning of the code.', 'perfmatters'), 'error');
            }
            
            //cleanup
            $code = '<?php' . PHP_EOL . rtrim($code, '?>');

            //validate code
	        $validated = PMCS::validate_php($code);

	        //handle validation error
	        if(is_wp_error($validated)) {

	            $message = ucfirst($validated->get_error_message());
	            $data = $validated->get_error_data();

	            if($data['line']) {
	            	if (is_numeric($data['line']) && $data['line'] > 1) {
	                    $lineNumber = $data['line'] - 1;
	                    $data['line'] = $lineNumber;
	                }
	                $message .= ' on line ' . $lineNumber;
	            }

	            return PMCS::admin_notice('invalid_code', $message, 'error');
	        }
        } 
        else if($_POST['type'] == 'js') {

        	//check for <script> tags
        	if(preg_match('/<\/?script[^>]*>/', $code)) {
        		return PMCS::admin_notice('invalid_code', __('Please remove &lt;script&gt;&lt;/script&gt; tags from the code.', 'perfmatters'), 'error');
        	}
        }
        else if($_POST['type'] == 'css') {

        	//check for <style> tags
        	if(preg_match('/<\/?style[^>]*>/', $code)) {
        		return PMCS::admin_notice('invalid_code', __('Please remove &lt;style&gt;&lt;/style&gt; tags from the code.', 'perfmatters'), 'error');
        	}
        }

        //clear previous snippet error
        $config = PMCS::get_snippet_config();
        if(isset($config['error_files'][$file_name])) {
        	unset($config['error_files'][$file_name]);
        }

        PMCS::update_snippet_config($config);

        //update snippet file
        if(self::update($file_name, $code, $meta)) {

        	//save notice
        	PMCS::admin_notice('snippet_saved', __('Snippet saved.', 'perfmatters'), 'success');

        	return $file_name;
        }

        return false;
	}

	//update snippet file
	public static function update($file_name, $code, $meta)
    {
    	$file_name = PMCS::normalize_snippet_file_name($file_name);
    	if(empty($file_name)) {
    		return false;
    	}

    	//file path
        $file = PMCS::get_storage_dir() . '/' . $file_name;
        
        //doc block
        $doc_block_string = PMCS::get_doc_block($meta);

        //save file content
       	if(file_put_contents($file, $doc_block_string . $code) === false) {
       		return false;
       	}

       	//save local js/css if needed
        if(in_array($meta['type'], ['css', 'js'])) {
            PMCS::cache_js_css($file_name, $meta, $code);
        }

        //update config
        PMCS::build_snippet_config();

        return $file_name;
    }

	//activate snippet
	public static function activate($file_name)
	{
    	$snippet = self::get($file_name);

		$snippet['meta']['active'] = 1;

		return self::update($file_name, $snippet['code'], $snippet['meta']);
	}

	//deactivate snippet
	public static function deactivate($file_name)
	{
		$snippet = self::get($file_name);

		$snippet['meta']['active'] = '';

		return self::update($file_name, $snippet['code'], $snippet['meta']);
	}

	//delete snippet, supports multiple
	public static function delete($file_names)
	{
		//get config
        $config = PMCS::get_snippet_config();

        foreach(PMCS::normalize_snippet_file_names($file_names) as $file_name) {

        	//get snippet data
        	$snippet = self::get($file_name);

        	//delete cached files if needed
        	if(!empty($snippet['meta']['type']) && in_array($snippet['meta']['type'], ['js', 'css'])) {
        		$cached_files = PMCS::get_storage_dir() . '/' . $snippet['meta']['type'] . '/' . str_replace('.php', '*', $file_name);
        		foreach(glob($cached_files) as $cached_file) {
				    unlink($cached_file);
				}
        	}

        	//delete file
        	$file = PMCS::get_storage_dir() . '/' . $file_name;
	        unlink($file);

	        //remove file references from config
	        foreach(['active', 'inactive', 'error_files'] as $status) {
	        	if(isset($config[$status][$file_name])) {
		        	unset($config[$status][$file_name]);
		        }
	        }
        }

        //save config
        PMCS::build_snippet_config();
	}

	//get snippet data for the given file name
    public static function get($file_name)
    {
        $file_name = PMCS::normalize_snippet_file_name($file_name);
        if(empty($file_name)) {
            return PMCS::admin_notice('file_not_found', __('File not found.', 'perfmatters'), 'error');
        }

        $file = PMCS::get_storage_dir() . '/' . $file_name;

        if(!is_file($file) || $file_name === 'index.php') {
            return PMCS::admin_notice('file_not_found', __('File not found.', 'perfmatters'), 'error');
        }

        [$meta, $code] = PMCS::parse_doc_block(file_get_contents($file));

        return [
            'meta'   => $meta,
            'code'   => $code
        ];
    }

    //return docblock line count
    public static function get_docblock_line_count($file_path) {
	    
	    if(!file_exists($file_path)) {
	        return 0;
	    }

	    $content = file_get_contents($file_path);
	    $lines = explode("\n", $content);
	    
	    foreach ($lines as $index => $line) {
	        // Look for your specific "User Code Start" marker or the end of the docblock
	        if (strpos($line, '<Internal Doc End>') !== false) {
	            return $index + 2; // Returns the line number where the docblock ends
	        }
	    }

	    return 0; 
	}
}