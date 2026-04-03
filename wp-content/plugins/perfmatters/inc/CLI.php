<?php
namespace Perfmatters;

use WP_CLI;

class CLI {

	/**
	 * Activates a license key.
	 * 
	 * ## OPTIONS
     *
     * [<key>]
     * : The license key to add and activate.
     * 
	 * @subcommand activate-license
	 * 
	 */
	public function activate_license($args, $assoc_args) {

		$network = is_multisite() && empty(WP_CLI::get_config()['url']);

		if(!empty($args[0])) {
			$network ? update_site_option('perfmatters_edd_license_key', trim($args[0])) : update_option('perfmatters_edd_license_key', trim($args[0]), false);
		}

		if(is_multisite()) {

			$license_info = License::check(null, $network);

			if(empty($license_info->activations_left) || $license_info->activations_left !== 'unlimited') {
				WP_CLI::error(__('Unlimited site license required.', 'perfmatters'));
			}
		}

		if(License::activate(null, $network)) {
			WP_CLI::success(__('License activated.', 'perfmatters'));
		}
		else {
			WP_CLI::error(__('License could not be activated.', 'perfmatters'));
		}
	}

	/**
	 * Deactivates a license key.
	 * 
	 * @subcommand deactivate-license
	 */
	public function deactivate_license() {

		$network = is_multisite() && empty(WP_CLI::get_config()['url']);

		if(License::deactivate(null, $network)) {
			WP_CLI::success(__('License deactivated.', 'perfmatters'));
		}
		else {
			WP_CLI::error(__('License could not be deactivated.', 'perfmatters'));
		}
	}

	/**
	 * Deactivates and removes a license key.
	 * 
	 * @subcommand remove-license
	 */
	public function remove_license() {

		$network = is_multisite() && empty(WP_CLI::get_config()['url']);

		if(License::deactivate(null, $network)) {
			WP_CLI::success('License deactivated!');
		}

		$removed = $network ? delete_site_option('perfmatters_edd_license_key') : delete_option('perfmatters_edd_license_key');

		if($removed) {
			WP_CLI::success(__('License removed.', 'perfmatters'));
		}
		else {
			WP_CLI::error(__('License could not be removed.', 'perfmatters'));
		}
	}

	/**
	 * Clears used CSS.
	 * 
	 * ## OPTIONS
     *
     * [--network]
     * : Clear used CSS for all sites in the network.
     * 
	 * @subcommand clear-used-css
	 * 
	 */
	public function clear_used_css($args, $assoc_args) {

		if(!empty($assoc_args['network']) && is_multisite()) {
			foreach(get_sites(array('number' => 500)) as $blog) {
			   	CSS::clear_used_css($blog);
			}
			WP_CLI::success(__('Used CSS cleared for all network sites.', 'perfmatters'));
		}
		else {
			CSS::clear_used_css();
			WP_CLI::success(__('Used CSS cleared.', 'perfmatters'));
		}
	}

	/**
	 * Clears minified CSS.
	 * 
	 * ## OPTIONS
     *
     * [--network]
     * : Clear minified CSS for all sites in the network.
     * 
	 * @subcommand clear-minified-css
	 * 
	 */
	public function clear_minified_css($args, $assoc_args) {

		if(!empty($assoc_args['network']) && is_multisite()) {
			foreach(get_sites(array('number' => 500)) as $blog) {
			   	Minify::clear_minified('css', $blog);
			}
			WP_CLI::success(__('Minified CSS cleared for all network sites.', 'perfmatters'));
		}
		else {
			Minify::clear_minified('css');
			WP_CLI::success(__('Minified CSS cleared.', 'perfmatters'));
		}
	}

	/**
	 * Clears minified JS.
	 * 
	 * ## OPTIONS
     *
     * [--network]
     * : Clear minified JS for all sites in the network.
     * 
	 * @subcommand clear-minified-js
	 * 
	 */
	public function clear_minified_js($args, $assoc_args) {

		if(!empty($assoc_args['network']) && is_multisite()) {
			foreach(get_sites(array('number' => 500)) as $blog) {
			   	Minify::clear_minified('js', $blog);
			}
			WP_CLI::success(__('Minified JS cleared for all network sites.', 'perfmatters'));
		}
		else {
			Minify::clear_minified('js');
			WP_CLI::success(__('Minified JS cleared.', 'perfmatters'));
		}
	}

	/**
	 * Clears local fonts.
	 * 
	 * ## OPTIONS
     *
     * [--network]
     * : Clear local fonts for all sites in the network.
     * 
	 * @subcommand clear-local-fonts
	 * 
	 */
	public function clear_local_fonts($args, $assoc_args) {

		if(!empty($assoc_args['network']) && is_multisite()) {
			foreach(get_sites(array('number' => 500)) as $blog) {
			   	Fonts::clear_local_fonts($blog);
			}
			WP_CLI::success(__('Local Fonts cleared for all network sites.', 'perfmatters'));
		}
		else {
			Fonts::clear_local_fonts();
			WP_CLI::success(__('Local fonts cleared.', 'perfmatters'));
		}
	}

	/**
	 * Import settings configuration from an exported .json file.
	 * 
	 * ## OPTIONS
     *
     * <filepath>
     * : The .json settings file to import.
     * 
	 * @subcommand import-settings
	 * 
	 */
	public function import_settings($args) {

		//file check
		if(!file_exists($args[0])) {
			WP_CLI::error(__('File not found.', 'perfmatters'));
		}

		//json check
		$file_parts = explode('.', $args[0]);
		$extension = end($file_parts);
		if($extension != 'json') {
			WP_CLI::error(__('Please use a valid .json file.', 'perfmatters'));
		}

		//unpack and update
		$settings = (array) json_decode(file_get_contents($args[0]), true);
		if(isset($settings['perfmatters_options'])) {
			update_option('perfmatters_options', $settings['perfmatters_options']);
		}
		if(isset($settings['perfmatters_tools'])) {
			update_option('perfmatters_tools', $settings['perfmatters_tools']);
		}

		//success
		WP_CLI::success(__('Settings imported.', 'perfmatters'));
	}

	/**
	 * Enable a plugin option.
	 * 
	 * ## OPTIONS
     *
     * <option>
     * : The option you want to enable. For available options, run the get-options subcommand.
     * 
	 * @subcommand enable
	 * 
	 */
	public function enable($args) {

		$options = self::get_available_options();

		if(!isset($options[$args[0]])) {
			WP_CLI::error(__('Option not found.', 'perfmatters'));
		}

		$data = $options[$args[0]];

		$prev = get_option($data['option_row']);


		if(!empty($data['section'])) {
			$prev[$data['section']][$data['option']] = 1;
		}
		else {
			$prev[$data['option']] = 1;
		}
	
		update_option($data['option_row'], $prev);

		WP_CLI::success(__('Option enabled.', 'perfmatters'));
	}

	/**
	 * Disable a plugin option.
	 * 
	 * ## OPTIONS
     *
     * <option>
     * : The option you want to enable. For available options, run the get-options subcommand.
     * 
	 * @subcommand disable
	 * 
	 */
	public function disable($args) {

		$options = self::get_available_options();

		if(!isset($options[$args[0]])) {
			WP_CLI::error(__('Option not found.', 'perfmatters'));
		}

		$data = $options[$args[0]];

		$prev = get_option($data['option_row']);

		if(!empty($data['section'])) {
			unset($prev[$data['section']][$data['option']]);
		}
		else {
			unset($prev[$data['option']]);
		}
	
		update_option($data['option_row'], $prev);

		WP_CLI::success(__('Option disabled.', 'perfmatters'));
	}

	/**
	 * Returns a list of available plugin options that can be turned on and off via CLI.
     * 
	 * @subcommand get-options
	 */
	public function get_options($args) {

		$options = self::get_available_options();

		WP_CLI::log("\n\033[1mOPTIONS\033[0m");
		WP_CLI::log('---');

		foreach($options as $option => $data) {
			WP_CLI::log($option);
		}

		WP_CLI::log('---');
	}

	//return array of adjustable plugin options 
	private function get_available_options() {

		return array(
			'delay-js' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'assets',
				'option' => 'delay_js'
			),
			'defer-js' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'assets',
				'option' => 'defer_js'
			),
			'minify-js' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'assets',
				'option' => 'minify_js'
			),
			'remove-unused-css' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'assets',
				'option' => 'remove_unused_css'
			),
			'minify-css' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'assets',
				'option' => 'minify_css'
			),
			'lazyload-images' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'lazyload',
				'option' => 'lazy_loading'
			),
			'lazyload-iframes' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'lazyload',
				'option' => 'lazy_loading_iframes'
			),
			'css-background-images' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'lazyload',
				'option' => 'css_background_images'
			),
			'lazyload-elements' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'lazyload',
				'option' => 'elements'
			),
			'local-google-fonts' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'fonts',
				'option' => 'local_google_fonts'
			),
			'cdn-rewrite' => array(
				'option_row' => 'perfmatters_options',
				'section' => 'cdn',
				'option' => 'enable_cdn'
			),
			'script-manager' => array(
				'option_row' => 'perfmatters_tools',
				'option' => 'script_manager'
			),
			'clean-uninstall' => array(
				'option_row' => 'perfmatters_tools',
				'option' => 'clean_uninstall'
			)
		);
	}
}