<?php
namespace Perfmatters\Admin;

class CodeMirror
{
	//Bundled CodeMirror CSS (under plugin directory).
	public const BUNDLED_THEME_REL = 'css/codemirror-theme';

	//Directory for optional custom CodeMirror themes (under wp-content/uploads/).
	public const CUSTOM_THEME_SUBDIR = 'perfmatters/codemirror-theme';

	//apply selected theme to CodeMirror settings.
	public static function apply_theme_to_settings($settings, $handle_prefix = 'perfmatters-codemirror-theme') {
		$selected_theme = self::enqueue_selected_theme($handle_prefix);
		if(!empty($selected_theme) && !empty($settings['codemirror'])) {
			$settings['codemirror']['theme'] = $selected_theme;
		}

		return $settings;
	}








	//absolute path to the custom theme directory, or empty if uploads is unavailable.
	public static function get_custom_theme_dir() {
		$uploads = wp_upload_dir();
		if(!empty($uploads['error']) || empty($uploads['basedir'])) {
			return '';
		}

		return path_join($uploads['basedir'], self::CUSTOM_THEME_SUBDIR);
	}

	//absolute path to the first readable .css in the directory, or empty.
	public static function get_custom_theme_path() {
		foreach(self::custom_theme_css_paths(self::get_custom_theme_dir()) as $path) {
			if(is_file($path) && is_readable($path)) {
				return $path;
			}
		}

		return '';
	}

	//delete all .css in the custom theme directory.
	public static function delete_custom_theme_stylesheets() {
		array_map('wp_delete_file', self::custom_theme_css_paths(self::get_custom_theme_dir()));
	}

	//Save a custom CodeMirror theme from a single $_FILES entry; returns true or WP_Error.
	public static function save_custom_theme_upload($file) {
		if(empty($file['tmp_name'])) {
			return new \WP_Error('perfmatters_cm_no_file', __('No file uploaded.', 'perfmatters'));
		}

		if(!is_uploaded_file($file['tmp_name'])) {
			return new \WP_Error('perfmatters_cm_upload', __('Invalid upload.', 'perfmatters'));
		}

		$dir = self::get_custom_theme_dir();
		if(empty($dir)) {
			return new \WP_Error('perfmatters_cm_dir', __('Unable to resolve upload directory.', 'perfmatters'));
		}

		if(!wp_mkdir_p($dir)) {
			return new \WP_Error('perfmatters_cm_mkdir', __('Unable to create upload directory.', 'perfmatters'));
		}

		self::delete_custom_theme_stylesheets();

		$basename = sanitize_file_name(pathinfo($file['name'], PATHINFO_BASENAME));
		if(empty($basename) || strtolower((string) pathinfo($basename, PATHINFO_EXTENSION)) !== 'css') {
			return new \WP_Error('perfmatters_cm_name', __('Please upload a valid .css file.', 'perfmatters'));
		}

		$dest = path_join($dir, $basename);
		if(!@move_uploaded_file($file['tmp_name'], $dest)) {
			return new \WP_Error('perfmatters_cm_move', __('The uploaded file could not be moved.', 'perfmatters'));
		}

		$stat = stat(dirname($dest));
		if(false !== $stat) {
			chmod($dest, $stat['mode'] & 0000666);
		}

		return true;
	}

	//available CodeMirror theme options.
	public static function get_theme_options() {
		$options = array(
			'' => __('Default', 'perfmatters')
		);

		foreach(glob(path_join(PERFMATTERS_PATH, self::BUNDLED_THEME_REL . '/*.css')) ?: array() as $theme_file) {
			$theme_slug = sanitize_key(pathinfo($theme_file, PATHINFO_FILENAME));
			if(empty($theme_slug) || $theme_slug === 'custom') {
				continue;
			}

			$options[$theme_slug] = ucwords(str_replace(array('-', '_'), ' ', $theme_slug));
		}

		ksort($options);
		$options['custom'] = __('Custom', 'perfmatters');
		return $options;
	}

	//CodeMirror theme slug: first .cm-s-* in the file, else the stylesheet filename stem.
	public static function get_custom_theme_name($path = null) {
		if(null === $path) {
			$path = self::get_custom_theme_path();
		}

		if('' === $path) {
			return '';
		}

		$css = @file_get_contents($path);
		if(false !== $css && preg_match('/\.cm-s-([a-z0-9\-_]+)/i', $css, $m)) {
			return sanitize_key($m[1]);
		}

		return sanitize_key(pathinfo($path, PATHINFO_FILENAME));
	}

	//get selected CodeMirror theme.
	public static function get_selected_theme() {
		$options = get_option('perfmatters_options');
		$selected_theme = sanitize_key($options['code']['editor_theme'] ?? '');
		$theme_options = self::get_theme_options();

		return array_key_exists($selected_theme, $theme_options) ? $selected_theme : '';
	}

	//enqueue selected CodeMirror theme CSS and return selected theme slug.
	public static function enqueue_selected_theme($handle_prefix = 'perfmatters-codemirror-theme') {
		$selected_theme = self::get_selected_theme();
		if(empty($selected_theme)) {
			return '';
		}

		if($selected_theme === 'custom') {
			$path = self::get_custom_theme_path();
			$url = self::get_custom_theme_url($path);
			if('' === $url) {
				return '';
			}

			wp_enqueue_style($handle_prefix . '-custom', $url, array('wp-codemirror'), (string) filemtime($path));

			return self::get_custom_theme_name($path);
		}

		wp_enqueue_style($handle_prefix . '-' . $selected_theme, PERFMATTERS_URL . self::BUNDLED_THEME_REL . '/' . $selected_theme . '.css', array('wp-codemirror'), PERFMATTERS_VERSION);

		return $selected_theme;
	}

	//*.css paths in the custom theme directory (empty when missing or not a directory).
	private static function custom_theme_css_paths($dir) {
		if(empty($dir) || !is_dir($dir)) {
			return array();
		}

		return glob(path_join($dir, '*.css'), GLOB_NOSORT) ?: array();
	}

	//public URL for a custom theme file under uploads.
	private static function get_custom_theme_url($path) {
		if('' === $path) {
			return '';
		}

		$uploads = wp_upload_dir();
		if(!empty($uploads['error']) || empty($uploads['baseurl'])) {
			return '';
		}

		return esc_url_raw(path_join($uploads['baseurl'], self::CUSTOM_THEME_SUBDIR . '/' . wp_basename($path)));
	}
}
