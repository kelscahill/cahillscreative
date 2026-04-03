<?php
namespace Perfmatters\PMCS;

class Editor
{
	//editor instance settings array
	private static $settings;

	//initialize
	public static function init($code_type = 'php')
	{	
		self::load_code_mirror($code_type);
		self::load_hint_scripts();
		self::init_editor();
	}

	//load code mirror editor instance
	private static function load_code_mirror($code_type)
	{
		if(!function_exists('wp_enqueue_code_editor')) {
			return false;
		}

		//set up gutters
		$gutters = array('CodeMirror-linenumbers', 'CodeMirror-foldgutter'); 

		//lint gutter for non-php types
		if(self::is_lint_enabled($code_type)) {
		    array_unshift($gutters, 'CodeMirror-lint-markers');
		}

		$editor_args = array(
			'type'       => self::get_mime_for_code_type($code_type),
			'showHint'   => true,
			'codemirror' => array(
				'autoCloseTags'             => true,
				'matchTags'                 => array('bothTags' => true),
				'matchBrackets'             => true,
				'gutters'                   => $gutters,
				'foldGutter'                => true,
				'autoCloseBrackets'         => true,
				'highlightSelectionMatches' => true,
				'lint'                      => self::is_lint_enabled($code_type)
			)
		);

		//force syntax highlighting
		add_filter('get_user_metadata', array('Perfmatters\PMCS\Editor', 'force_syntax_highlighting'), 10, 4);

		//enqueue code editor assets
		self::$settings = wp_enqueue_code_editor($editor_args);

		//remove filter
		remove_filter('get_user_metadata', array('Perfmatters\PMCS\Editor', 'force_syntax_highlighting'));

		return self::$settings;
	}

	//force syntax highlighting
	public static function force_syntax_highlighting($value, $object_id, $meta_key, $single)
	{
		if($meta_key === 'syntax_highlighting') {
			return true;
		}

		return $value;
	}

	//load editor hint scripts
	public static function load_hint_scripts()
	{
		wp_enqueue_script('jshint');
		wp_enqueue_script('htmlhint');
	}

	//inline js to initialize editor
	public static function init_editor()
	{
		wp_add_inline_script(
			'code-editor',
			sprintf(
				'jQuery(function() {
					wp.codeEditor.initialize("pmcs-code", %1$s);
				});',
				wp_json_encode(self::$settings)
			)
		);
	}

	//get mime type
	public static function get_mime_for_code_type($code_type) {

		//default type (php)
		$mime = 'application/x-httpd-php-open';

		//mime for set code type
		if(!empty($code_type)) {
			switch($code_type) {
				case 'js':
					$mime = 'text/javascript';
					break;
				case 'css':
					$mime = 'text/css';
					break;
				case 'html':
					$mime = 'application/x-httpd-php';
					break;
			}
		}

		return $mime;
	}

	//get lint status for code type
	public static function is_lint_enabled($code_type) {
		return in_array($code_type, array('js', 'html'));
	}
}