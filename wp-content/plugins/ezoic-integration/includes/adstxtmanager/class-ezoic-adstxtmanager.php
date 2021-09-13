<?php

namespace Ezoic_Namespace;


/**
 * Class Ezoic_AdsTxtManager
 * @package Ezoic_Namespace
 */
class Ezoic_AdsTxtManager extends Ezoic_Feature {


	public function __construct() {
		$this->is_public_enabled = true;
		$this->is_admin_enabled  = true;
		$this->setup_wp_filesystem();
	}

	public function register_public_hooks( $loader ) {
		// include these for non is_admin() calls
		$loader->add_action('init', $this, 'ezoic_handle_adstxt', 1);
	}

	public function register_admin_hooks( $loader ) {
		$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
		$adsTxtSolution = $solutionFactory->GetBestSolution();
		$loader->add_action( 'admin_notices', $this, 'ezoic_adstxtmanager_display_notice');
		$loader->add_action( 'update_option_adstxtmanager_id', $adsTxtSolution ,'SetupSolution');
	}

	public static function ezoic_adstxtmanager_id($refresh = false) {
		static $adstxtmanager_id = null;
		if ( is_null( $adstxtmanager_id ) || $refresh ) {
			$adstxtmanager_id = (int)get_option('ezoic_adstxtmanager_id');
		}

		return $adstxtmanager_id;
	}

	public static function ezoic_adstxtmanager_status($refresh = false) {
		static $adstxtmanager_status = null;
		if ( is_null( $adstxtmanager_status ) || $refresh ) {
			$adstxtmanager_status = get_option('ezoic_adstxtmanager_status');
		}

		return $adstxtmanager_status;
	}

	/**
	 * Initialize the WP file system.
	 *
	 * @return object
	 */
	private function setup_wp_filesystem()
	{
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->wp_filesystem = $wp_filesystem;
		return $this->wp_filesystem;
	} // setup_wp_filesystem


	public function ezoic_handle_adstxt() {
		global $wp;

		$request = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : false;
		if ('/ads.txt' == $request && get_option('permalink_structure')) {
			$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);

			if (is_int($adstxtmanager_id) && $adstxtmanager_id > 0) {
				$domain = home_url($wp->request);
				$domain = parse_url($domain);
				$domain = $domain['host'];
				$domain = preg_replace('#^(http(s)?://)?w{3}\.#', '$1', $domain);
				header("HTTP/1.1 301 Moved Permanently");
				header('Location: https://srv.adstxtmanager.com/' . $adstxtmanager_id . '/' . $domain);
				exit();
			}
		}
	}

	/**
	 * @return bool
	 */
	public static function ezoic_verify_adstxt_redirect() {
		global $wp;

		$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);

		//create endpoint request
		$response = wp_remote_get(home_url($wp->request) . "/ads.txt", array(
				'timeout' => 5,
				'headers' => array('Cache-Control' => 'no-cache'),
		));

		if (
				!is_wp_error($response)
				&& isset($response['http_response'])
				&& $response['http_response'] instanceof \WP_HTTP_Requests_Response
				&& method_exists($response['http_response'], 'get_response_object')
		) {
			$location_url = $response['http_response']->get_response_object()->url;

			$url_parse = wp_parse_url($location_url);
			if ($url_parse['host'] == "srv.adstxtmanager.com") {
				$adstxtmanager_status['message'] = "";
				update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
				return true;
			} else {
				$adstxtmanager_status['message'] = "The ads.txt is not redirecting to the correct adstxtmanager.com location. Please remove/fix any existing redirections to your <a href=\"" . home_url($wp->request) . "/ads.txt\" target=\"_blank\">ads.txt</a> file.";
				update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
				return false;
			}
		}

		$adstxtmanager_status['message'] = "Unable to verify your ads.txt redirection.";
		update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
		return false;
	}

	function ezoic_adstxtmanager_display_notice() {
		if (self::should_show_adstxtmanager_setting() == false) {
			return;
		}
		global $hook_suffix, $pagenow;
		$adstxtmanager_status = self::ezoic_adstxtmanager_status(true);
		$adstxtmanager_id = self::ezoic_adstxtmanager_id(true);

		if (!is_int($adstxtmanager_id)) {
			delete_option('ezoic_adstxtmanager_id');

		} else if (in_array($pagenow, array('options_general.php')) && $_GET['page'] == 'ezoic-integration' && $_GET['tab'] == 'adstxtmanager_settings') {
			if ((isset($_GET['verify']) && $_GET['verify']) || $adstxtmanager_status['status'] === false) {
				$solutionFactory = new Ezoic_AdsTxtManager_Solution_Factory();
				$adsTxtSolution = $solutionFactory->GetBestSolution();
				$adsTxtSolution->SetupSolution();

				$redirect_status = self::ezoic_verify_adstxt_redirect();
				$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
				$adstxtmanager_status['status'] = $redirect_status;
				update_option('ezoic_adstxtmanager_status', $adstxtmanager_status);
			}

			if ($adstxtmanager_id > 0 && $adstxtmanager_status === true) {
				if ($adstxtmanager_status['status'] === true) {
					?>
					<div class="notice notice-success">
						<p>Success: Your ads.txt redirect is successfully setup.</p>
					</div>
					<?php
				} else {
					?>
					<div class="notice notice-warning">
						<p>Oh no! Your ads.txt redirect is not setup correctly!
							<a href="?page=ezoic-integration&tab=adstxtmanager_settings&verify=1">Rerun setup and recheck redirection</a>.</p>
						<?php if (!empty($adstxtmanager_status['message'])) { ?>
							<hr/><p><?php _e($adstxtmanager_status['message']); ?></p>
						<?php } ?>
					</div>
					<?php
				}
			}
		}

		if (in_array( $hook_suffix, array( 'plugins.php' ) ) ) {

			$has_issue = false;
			$issue_types = array();
			if(!get_option('permalink_structure')) {
				$issue_types['type'] = 'permalinks_disabled';
				$has_issue = true;
			}

			if(!is_int($adstxtmanager_id) || empty($adstxtmanager_id)) {
				if($has_issue) {
					$issue_types['type'] = $issue_types['type'] . "+" . "no_id";
				} else {
					$issue_types['type'] = 'no_id';
					$has_issue = true;
				}
			}

			if($has_issue) {
				$args = apply_filters( 'adstxtmanager_view_arguments', $issue_types, 'adstxtmanager-admin');

				foreach ($args AS $key => $val) {
					$$key = $val;
				}

				$file = EZOIC__PLUGIN_DIR . 'admin/partials/'. 'ezoic-integration-admin-display-adstxtmanager' . '.php';

				include($file);
			}
		}
	}

	public static function should_show_adstxtmanager_setting() {
		$options = \get_option( 'ezoic_integration_status' );
		if ( isset( $options['is_integrated'] ) == false || $options['is_integrated'] == false || Ezoic_Integration_Admin::is_cloud_integrated() ) {
			return false;
		}
		$active_plugins = Ezoic_Integration_Compatibility_Check::get_active_plugins();
		foreach ($active_plugins as $plugin) {
			if ($plugin['name'] == EZOIC_ADSTXT_MANAGER__PLUGIN_NAME) {
				return false;
			}
		}

		return true;
	}
}
