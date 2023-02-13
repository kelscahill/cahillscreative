<?php

namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();

	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @param $links
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="options-general.php?page=' . EZOIC__PLUGIN_SLUG . '">' . __( 'Settings' ) . '</a>',
			'<a href="' . EZOIC__SITE_LOGIN . '" target="_blank">' .
			sprintf( __( '%s Login' ), EZOIC__SITE_NAME ) . '</a>',
		);

		return array_merge( $links, $settings_link );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ezoic-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ezoic-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ezoic_Integration_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-integration-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-cdn-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-ad-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-adstxtmanager-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/cdn/class-facebook-share-cache.php';

	}

	public function theme_switch_notification() {
		global $pagenow;
		if ( 'themes.php' === $pagenow || 'theme-install.php' === $pagenow ) {
			include_once( EZOIC__PLUGIN_DIR . 'admin/partials/ezoic-integration-admin-theme-notification.php' );
		}
	}

	/**
	 * @param $data
	 * @param bool $status
	 *
	 * @return array
	 */
	public function build_integration_request( $data, $status = 1 ) {
		global $wp;

		$domain = home_url( $wp->request );
		$domain = wp_parse_url( $domain )['host'];

		$request_params = array(
			'domain'    => $domain,
			'title'     => get_bloginfo( 'name' ),
			'url'       => get_bloginfo( 'url' ),
			'data'      => $data,
			'is_active' => (bool) $status,
		);

		$request = array(
			'timeout' => 30,
			'body'    => json_encode( $request_params ),
			'headers' => array(
				'X-Wordpress-Integration' => 'true',
				'Expect'                  => '',
				'X-From-Req'              => 'wp'
			),
		);

		return $request;
	}

	public static function set_debug_to_ezoic() {
		set_transient( 'ezoic_send_debug', array( 1, 1 ) );
	}

	public function send_debug_to_ezoic() {

		if ( $ezoic_send_debug = get_transient( 'ezoic_send_debug' ) ) {

			if ( ! is_array( $ezoic_send_debug ) ) {
				$ezoic_send_debug = array( 1, 1 );
			}

			if ( ! class_exists( 'WP_Debug_Data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
			}
			if ( ! class_exists( 'WP_Site_Health' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
			}

			if ( class_exists( 'WP_Debug_Data' ) ) {
				$debug = new \WP_Debug_Data();
				$debug::check_for_updates();
				$info = ( $debug::debug_data() );

				$info['wp-get-plugins'] = self::get_plugin_data();

				$request = $this->build_integration_request( $info, $ezoic_send_debug[1] );

				//Ezoic_Integration_Request_Utils::GetEzoicServerAddress()
				$response = wp_remote_post( "https://pubdashbackend.ezoic.com/pub/v1/wordpressintegration/v1/wp/debug", $request );
			}

			delete_transient( 'ezoic_send_debug' );

		} // endif;
	}

	/**
	 * Get list of wordpress plugins with status
	 *
	 * @return array[]
	 */
	function get_plugin_data() {

		// Get all plugins
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		// Get active plugins
		$active_plugins = get_option( 'active_plugins' );

		// Add 'Active' boolean to $all_plugins array.
		foreach ( $all_plugins as $key => $value ) {
			$is_active                     = in_array( $key, $active_plugins );
			$all_plugins[ $key ]['Active'] = $is_active;

			$plugin_slug = dirname( plugin_basename( $key ) );
			if ( $plugin_slug == "." ) {
				$plugin_slug = basename( $key, '.php' );
			}
			$all_plugins[ $key ]['Slug'] = $plugin_slug;
		}

		return $all_plugins;
	}

	/**
	 * Checks to see if the site is Ezoic cloud integrated by searching for the x-middleton header.
	 */
	public static function is_cloud_integrated() {
		$headers = getallheaders();
		$header  = array_change_key_case( $headers ); // Convert all keys to lower

		$cloud_integrated = isset( $header['x-middleton'] ) && $header['x-middleton'] == '1';

		$options = \get_option( 'ezoic_integration_status' );
		if (
			( $cloud_integrated && empty( $options['integration_type'] ) )
			|| ( $cloud_integrated && ! empty( $options['integration_type'] ) && in_array( $options['integration_type'], array( "wp", "ba" ) ) )
			|| ( ! $cloud_integrated && ! empty( $options['integration_type'] ) && $options['integration_type'] == "cloud" )
		) {
			// clear to recheck integration
			$options['check_time'] = '';
			update_option( 'ezoic_integration_status', $options );
		}

		return $cloud_integrated;
	}

	public static function is_wordpress_integrated() {
		$options       = \get_option( 'ezoic_integration_status' );
		$ezoic_options = \get_option( 'ezoic_integration_options' );

		if (
			self::is_cloud_integrated() == false
			&& isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == false
			&& ! empty( $options['integration_type'] ) && ( $options['integration_type'] == "wp" )
		) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_basic_integrated() {
		$options       = \get_option( 'ezoic_integration_status' );

		if (
			self::is_cloud_integrated() == false
			&& self::is_wordpress_integrated() == false
			&& ! empty( $options['integration_type'] ) && ( $options['integration_type'] == "ba" )
		) {
			return true;
		} else {
			return false;
		}
	}



	/**
	 * Verify domain exists in Ezoic platform
	 */
	public function verify_domain() {
		// Fetch domain and TLD (e.g. example.com from www.example.com)
		$domain = Ezoic_Integration_Request_Utils::get_domain();

		// Fetch authentication token
		$valid_domain = Ezoic_Integration_Authentication::verify_domain();

		// Validate valid Ezoic domain from token response
		if ( ! $valid_domain ) {

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			\deactivate_plugins( EZOIC__PLUGIN_FILE );

			$title   = 'Ezoic Domain Issue!';
			$message = '<p><a href="https://www.ezoic.com/" target="_blank"><img src="' . plugins_url( '/admin/img',
					EZOIC__PLUGIN_FILE ) . '/ezoic-logo.png" width="190" height="40" alt="Ezoic"/></a></p>';
			$message .= '<h3>Ezoic Domain Issue</h3>';
			$message .= '<p><strong>We could not find the domain <u><em>' . $domain . '</em></u> registered in the Ezoic system.</strong></p>';
			$message .= '<p>If you already have an Ezoic account, please log in and <a href="https://pubdash.ezoic.com/settings?scroll=add_a_site" target="_blank">add this domain</a> to your existing account.</p>';
			$message .= '<p>If you need to signup with Ezoic, you can <a href="https://pubdash.ezoic.com/join" target="_blank">create your account</a> now.</p>';
			$message .= '<p>*Once this domain has been added to your Ezoic account, please return to your WordPress administration and re-activate this Ezoic plugin.</p>';
			$message .= '<p><a href="https://pubdash.ezoic.com/settings?scroll=add_a_site" class="button" target="_blank">Log Into Ezoic</a> <a href="https://pubdash.ezoic.com/settings?scroll=add_a_site" class="button" target="_blank">Create Your Account</a><br/><br/></p>';

			$args = array(
				'response'  => 200,
				'back_link' => true,
			);

			wp_die( $message, $title, $args );
		}
	}
}
