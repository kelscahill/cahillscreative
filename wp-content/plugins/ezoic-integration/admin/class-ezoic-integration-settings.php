<?php

namespace Ezoic_Namespace;

/**
 * The settings of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-compatibility-check.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-integrator.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache.php';

/**
 * Class Ezoic_Integration_Admin_Settings
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_Admin_Settings {

	private $cache_type;
	private $cache_identity;
	private $cache_integrator;
	private $cache;
	private $ads_enabled = false;
	private $ad_settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name 		= $plugin_name;
		$this->version				= $version;
		$this->cache_integrator	= new Ezoic_Integration_Cache_Integrator;
		$this->cache				= new Ezoic_Integration_Cache;
		$this->ad_settings		= new Ezoic_Integration_Ad_Settings();

		// Do not display information if disabled
		$this->ads_enabled = \get_option( 'ez_ad_integration_enabled', 'false' ) == 'true';
	}

	/**
	 * This function introduces the theme options into the 'Appearance' menu and into a top-level menu.
	 */
	public function setup_plugin_options_menu() {

		$options = \get_option( 'ezoic_integration_status' );

		// Check for incompatible plugins with Ezoic
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::get_active_incompatible_plugins();

		$badge_count = count( $incompatible_plugins );
		if ( function_exists( 'is_wpe' ) ) {
			if ( is_wpe() && ( isset( $options['integration_type'] ) && $options['integration_type'] == "wp" ) ) {
				$badge_count ++;
			}
		}

		$incompatible_count   = '';
		if ( $badge_count > 0 ) {
			$incompatible_count = ' <span class="awaiting-mod">' . $badge_count . '</span>';
		}

		// Add the menu to the Plugins set of menu items
		add_options_page(
				EZOIC__PLUGIN_NAME,
				EZOIC__PLUGIN_NAME . $incompatible_count,
				'manage_options',
				EZOIC__PLUGIN_SLUG,
				array(
						$this,
						'render_settings_page_content',
				)
		);

	}

	/**
	 * Provides default values for the Display Options.
	 *
	 * @return array
	 */
	public function default_display_options() {

		$defaults = array(
				'is_integrated' => false,
				'integration_type' => 'off',
				'check_time'    => '',
		);

		return $defaults;

	}

	/**
	 * Provide default values for the Social Options.
	 *
	 * @return array
	 */
	public function default_advanced_options() {

		$defaults = array(
				'verify_ssl' => true,
				'caching' => false,
				'disable_wp_integration' => true, // disable wp integration by default
		);

		return $defaults;

	}

	/**
	 * Renders a settings page
	 *
	 * @param string $active_tab
	 */
	public function render_settings_page_content( $active_tab = '' ) {

		$cdn_warning = "";
		$api_key     = Ezoic_Cdn::ezoic_cdn_api_key();
		if ( ! empty( $api_key ) ) {
			$ping_test = Ezoic_Cdn::ezoic_cdn_ping();
			if ( ! empty( $ping_test ) && is_array( $ping_test ) && $ping_test[0] == false ) {
				$cdn_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
			} elseif ( get_option( 'ezoic_cdn_enabled' ) !== 'on' ) {
				$cdn_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
			}
		}

		$atm_warning = "";
		$atm_id      = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id();
		$atm_status  = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status( true );
		if ( Ezoic_AdsTxtManager::ezoic_should_show_adstxtmanager_setting() ) {
			if ( ! get_option( 'permalink_structure' ) ) {
				$atm_warning = "<span class='dashicons dashicons-warning ez_error'></span>";
			} elseif ( isset( $atm_status['status'] ) && ! $atm_status['status'] ) {
				$atm_warning = "<span class='dashicons dashicons-warning ez_warning'></span>";
			}
		}

		?>
		<div class="wrap" id="ez_integration">
			<?php

			// Handles post requests for cache clearing. Displays an alert message upon success.
			if (empty($_POST)===false) {
				if ($_POST['action'] == 'clear_cache') {
					$this->handle_clear_cache();
					?>
					<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Cache successfully cleared!' ); ?></strong></p></div>
					<?php

				}
			}
			?>

			<p><img src="<?php echo plugins_url( '/admin/img', EZOIC__PLUGIN_FILE ); ?>/ezoic-logo.png" width="190" height="40" alt="Ezoic"/></p>
			<?php
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} elseif ( $active_tab == 'advanced_options' ) {
				$active_tab = 'advanced_options';
			} elseif ( $active_tab == 'cdn_settings' ) {
				$active_tab = 'cdn_settings';
			} elseif ( $active_tab == 'adstxtmanager_settings' ) {
				$active_tab = 'adstxtmanager_settings';
			} else {
				$active_tab = 'integration_status';
			} // end if/else ?>

			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=integration_status"
				   class="nav-tab <?php echo $active_tab == 'integration_status' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Integration Status',
							'ezoic' ); ?></a>

				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=ad_settings"
				   class="nav-tab <?php echo $active_tab == 'ad_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Ad Settings', 'ezoic' ); ?></a>


				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=cdn_settings"
				   class="nav-tab <?php echo $active_tab == 'cdn_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'CDN Settings',
							'ezoic' ); ?> <?php echo $cdn_warning; ?></a>

				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=ezoic_speed_settings"
				   class="nav-tab <?php echo $active_tab == 'ezoic_speed_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Speed Settings',
							'ezoic' ); ?></a>

				<?php if ( Ezoic_AdsTxtManager::ezoic_should_show_adstxtmanager_setting() ) { ?>
					<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=adstxtmanager_settings"
					   class="nav-tab <?php echo $active_tab == 'adstxtmanager_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Ads.txt Manager',
								'ezoic' ); ?> <?php echo $atm_warning; ?></a>
				<?php } ?>

				<a href="?page=<?php echo EZOIC__PLUGIN_SLUG; ?>&tab=advanced_options"
				   class="nav-tab <?php echo $active_tab == 'advanced_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Advanced Settings',
							'ezoic' ); ?></a>

				<?php if ( 'Ezoic' === EZOIC__SITE_NAME ) { ?>
					<a href="https://support.ezoic.com/" target="_blank" class="nav-tab" id="help-tab">
						<?php _e( 'Help Center', 'ezoic' ); ?>
					</a>
					<a href="<?php echo EZOIC__SITE_LOGIN; ?>" target="_blank" class="nav-tab" id="pubdash-tab">
						<?php _e( 'Publisher Dashboard', 'ezoic' ); ?>
					</a>
				<?php } ?>
			</h2>

			<form method="post" action="options.php" id="ezoic_settings">
				<?php

				if ( $active_tab == 'ezoic_speed_settings' ) {
					settings_fields( 'ezoic_speed_settings' );
					do_settings_sections( 'ezoic_speed_settings' );
					submit_button( 'Save Settings' );
				} elseif ( $active_tab == 'advanced_options' ) {
					settings_fields( 'ezoic_integration_options' );
					do_settings_sections( 'ezoic_integration_settings' );
					submit_button( 'Save Settings' );
				} elseif ( $active_tab == 'cdn_settings' ) {
					settings_fields( 'ezoic_cdn' );
					do_settings_sections( 'ezoic_cdn' );
					submit_button( 'Save Settings' );
				} elseif ( $active_tab == 'ad_settings' ) {
					$this->ad_settings->render_settings_page_content();
				} elseif ( $active_tab == 'adstxtmanager_settings' ) {
					settings_fields( 'ezoic_adstxtmanager' );
					do_settings_sections( 'ezoic_adstxtmanager' );
					submit_button( 'Save Settings' );
				} else {
					settings_fields( 'ezoic_integration_status' );
					do_settings_sections( 'ezoic_integration_status' );
				} // end if/else

				?>
			</form>

		</div><!-- /.wrap -->
	<?php }
	public function general_options_callback() {
		$options = \get_option( 'ezoic_integration_status' );

		echo '<hr/>';
		self::display_notice( $options );

		if ( isset( $_GET['create_default'] ) && $_GET['create_default'] ) {
			// $init = new Ezoic_AdTester_Init();
			// $init->initialize();
		}

	} // end general_options_callback

	public function ads_settings_callback() {
		echo 'Hello World!';
	}

	public function advanced_options_callback() {
		echo '<p>' . __( 'These settings can be used to enhance your default WordPress integration. They should only be used if you are an advanced user and know what you are doing. If you have any questions, feel free to reach out to <a href="https://support.ezoic.com/" target="_blank" rel="noreferrer noopener">our support</a>.', 'ezoic' ) . '</p>';
		echo '<hr/>';
	} // end advanced_options_callback

	/**
	 * Initializes options page by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_display_options() {

		$options = \get_option( 'ezoic_integration_status' );

		// If the plugin options don't exist, create them
		$default_array = $this->default_display_options();

		if ( false == $options ) {
			add_option( 'ezoic_integration_status', $default_array );
		} else {
			$array_diff = array_diff_key( $options, $default_array );
			if ( ! empty( $array_diff ) ) {
				$options = array_merge( $default_array, $options );
				\update_option( 'ezoic_integration_status', $options );
			}
		}
		$options = \get_option( 'ezoic_integration_status' );

		// enable WP integration
		if ( isset( $_GET['wp_integration'] ) && $_GET['wp_integration'] ) {
			$integration_options                           = \get_option( 'ezoic_integration_options' );
			$integration_options['disable_wp_integration'] = 0;
			\update_option( 'ezoic_integration_options', $integration_options );
			// clear to recheck integration
			$options['check_time'] = '';
		}

		// Check/update integration type
		Ezoic_Integration_Admin::is_cloud_integrated();

		$time_check = current_time( 'timestamp' ) - 21600; // 6 hours
		if ( !isset($options['is_integrated']) || $options['check_time'] <= $time_check || ( isset( $_GET['recheck'] ) && $_GET['recheck'] ) ) {

			$results = $this->get_integration_check_ezoic_response();

			$update                     = array();
			$update['is_integrated']    = $results['result'];

			if ( $results['result'] == true ) {
				$update['integration_type'] = $results['integration'];
			}

			$update['check_time']       = current_time( 'timestamp' );
			update_option( 'ezoic_integration_status', $update );
		}

		// Re-get options data
		$options    = \get_option( 'ezoic_integration_status' );

		add_settings_section(
				'general_settings_section',
				__( 'Integration Status', 'ezoic' ),
				array( $this, 'general_options_callback' ),
				'ezoic_integration_status'
		);

		add_settings_field(
				'is_integrated',
				__( 'Ezoic Integration', 'ezoic' ),
				array( $this, 'is_integrated_callback' ),
				'ezoic_integration_status',
				'general_settings_section',
				array(
					//__( 'Activate this setting to display the header.', 'ezoic' ),
					//'class' => 'hidden',
				)
		);

		if ( ! empty( $options['integration_type'] ) && ( $options['integration_type'] == "wp" || $options['integration_type'] == "ba" ) ) {
			add_settings_field(
					'adstxt_manager_status',
					__( 'Ads.txt Setup', 'ezoic' ),
					array( $this, 'adstxt_manager_status_callback' ),
					'ezoic_integration_status',
					'general_settings_section'
			);
		}

		// Detect and display any incompatible or potentially incompatible plugins
		$hosting_issue = false;
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::get_active_incompatible_plugins();
		$compatible_plugins = Ezoic_Integration_Compatibility_Check::get_compatible_plugins_with_recommendations();

		if ( function_exists( 'is_wpe' ) ) {
			if ( is_wpe() && ( isset( $options['integration_type'] ) && $options['integration_type'] == "wp" ) ) {
				$hosting_issue = true;
			}
		}

		if ( count( $incompatible_plugins ) > 0 || count( $compatible_plugins ) > 0 || $hosting_issue == true ) {
			add_settings_field(
					'plugin_compatibility',
					__( 'Compatibility Warning', 'ezoic' ),
					array( $this, 'plugin_compatibility_callback' ),
					'ezoic_integration_status',
					'general_settings_section',
					array( $incompatible_plugins, $compatible_plugins )
			);
		}

		add_settings_field(
				'check_time',
				__( 'Last Checked', 'ezoic' ),
				array( $this, 'check_time_callback' ),
				'ezoic_integration_status',
				'general_settings_section',
				array(//'class' => 'last_checked'
				)
		);

		register_setting(
				'ezoic_integration_status',
				'ezoic_integration_status'
		);

	} // end initialize_display_options

	public function handle_update_ezoic_integration_options( $old_value, $new_value ) {

		// Flush the cache for the site
		if ( $old_value !== $new_value ) {
			if ( Ezoic_Cdn::ezoic_cdn_is_enabled() ) {
				$cdn = new Ezoic_Cdn();
				$cdn->ezoic_cdn_purge( $cdn->ezoic_cdn_get_domain() );
			}
		}

		// Return if the caching value has not changed. This occurs when
		// another setting is updated and caching is left alone.
		if ($old_value['caching'] == $new_value['caching']) {
			return;
		}

		// Clear the cache just in case there are old files in it.
		$this->cache->clear();

		// Remove the WP_CACHE define from wp-config.php.
		if ($this->cache_integrator->clean_wp_config() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to clean the wp-config.php file. Please make sure the file exists and has write-able permissions.');
			return;
		}

		// Remove the advanced cache file.
		if ($this->cache_integrator->remove_advanced_cache() === false) {
			$this->handle_caching_update_error($new_value, 'Unable to remove the advanced-cache.php file. Please make sure the file exists and has write-able permissions.');
			return;
		};

		// Only perform these steps if caching was just turned on.
		if ($new_value['caching'] == '1') {

			// Define WP_CACHE in wp-config.php.
			if ($this->cache_integrator->configure_wp_config() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to update the wp-config.php file. Please make sure the file exists and has write-able permissions.');
				return;
			}

			// Insert the advanced cache file.
			if ($this->cache_integrator->insert_advanced_cache() === false) {
				$this->handle_caching_update_error($new_value, 'Unable to insert the advanced-cache.php file. Please make sure the /wp-content directory has write-able permissions.');
				return;
			}
		}
	}

	/**
	 *  If the site is cloud integrated and has caching enabled, disable caching and clean up any
	 *  files created because of it.
	 *
	 */
	public function handle_cloud_integrated_with_caching($plugin_admin) {
		if (!is_admin() || !$plugin_admin->is_cloud_integrated()) {
			return;
		}

		$old_options = \get_option( 'ezoic_integration_options' );
		if (!isset($old_options['caching']) || $old_options['caching'] == 0) {
			return;
		}

		$new_options = $old_options;
		$new_options['caching'] = 0;
		\update_option( 'ezoic_integration_options', $new_options);
		$this->handle_update_ezoic_integration_options( $old_options, $new_options );
	}

	public function handle_caching_update_error($options, $message) {

		// Handle errors while trying to turn on caching.
		add_settings_error('caching', 'caching-error', "Error while configuring Ezoic Caching: $message");
		$options['caching'] = '0';
		\update_option('ezoic_integration_options', $options);

	}

	/**
	 *  Clears the cache when ezoic caching is enabled.
	 *
	 *  This function is registered with the 'post_updated' and 'comment_post' hooks.
	 */
	public function handle_clear_cache() {
		if (defined('EZOIC_CACHE') && EZOIC_CACHE) {
			$this->cache->Clear();
		}
	}

	/**
	 * Initializes the advanced options by registering the Sections, Fields, and Settings.
	 *
	 * This function is registered with the 'admin_init' hook.
	 */
	public function initialize_advanced_options() {

		//delete_option( 'ezoic_integration_options' );
		if ( false == \get_option( 'ezoic_integration_options' ) ) {
			$default_array = $this->default_advanced_options();
			update_option( 'ezoic_integration_options', $default_array );
		} // end if

		add_settings_section(
				'advanced_settings_section',
				__( 'Advanced Settings', 'ezoic' ),
				array( $this, 'advanced_options_callback' ),
				'ezoic_integration_settings'
		);

		add_settings_field(
				'disable_wp_integration',
				'Disable WP Integration',
				array( $this, 'disable_wp_integration_callback' ),
				'ezoic_integration_settings',
				'advanced_settings_section',
				array(
						__( 'When not on Ezoic Cloud integration, this will disable automatic default WordPress integration.', 'ezoic' ),
				)
		);

		/*add_settings_field(
				'caching',
				'WordPress Caching (In Beta)',
				array( $this, 'caching_callback' ),
				'ezoic_integration_settings',
				'advanced_settings_section',
				array()
		);*/

		add_settings_field(
				'verify_ssl',
				'Verify SSL',
				array( $this, 'verify_ssl_callback' ),
				'ezoic_integration_settings',
				'advanced_settings_section',
				array(
						__( 'Turns off SSL verification. Recommended to Yes. Only disable if experiencing SSL errors.', 'ezoic' ),
				)
		);

		register_setting(
				'ezoic_integration_options',
				'ezoic_integration_options',
				array( 'default' => $this->default_advanced_options(), 'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_advanced_options' ) )
		);

	}

	public function sanitize_advanced_options( $settings ) {

		$old_options = get_option( 'ezoic_integration_options' );

		if ( $settings['disable_wp_integration'] != $old_options['disable_wp_integration'] ) {
			// recheck for integration change
			$options               = \get_option( 'ezoic_integration_status' );
			$options['check_time'] = '';
			update_option( 'ezoic_integration_status', $options );
		}

		return $settings;
	}

	public function is_integrated_callback() {

		$options       = \get_option( 'ezoic_integration_status' );
		$ezoic_options = \get_option( 'ezoic_integration_options' );

		$html = '<input type="hidden" id="is_integrated" name="ezoic_integration_status[is_integrated]" value="1" ' . checked( 1,
						isset( $options['is_integrated'] ) ? $options['is_integrated'] : 0, false ) . '/>';

		$html .= '<div>';
		if ( $options['is_integrated'] ) {

			if ( Ezoic_Integration_Admin::is_cloud_integrated() ) {
				// cloud
				$html .= '<p class="text-success"><strong>Cloud Integrated &nbsp;<span class="dashicons dashicons-cloud-saved text-success" title="Cloud Integrated"></span></strong></p>';
			} elseif ( ! empty( $options['integration_type'] ) && $options['integration_type'] == "ba" ) {
				// basic
				$html .= '<p class="text-info"><strong>Basic Integrated &nbsp;<span class="dashicons dashicons-saved"></span></strong></p>';
			} elseif ( isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == true ) {
				// no integration detected
				$html .= '<p class="text-danger"><strong>Waiting on Integration</strong>';
				$html .= '<br/><br/><a class="button button-success" href="https://pubdash.ezoic.com/integration" target="_blank" style="color: white; text-decoration: none; background: #5fa624; border-color: #53951a;">Integration Options</a>&nbsp;';
				$html .= '<a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=integration_status&wp_integration=1" style="color: white; text-decoration: none;">Enable WordPress Integration</a>';
				$html .= '</p>';
			} else {
				// wordpress
				$html .= '<p class="text-success"><strong>WordPress Integrated &nbsp;<span class="dashicons dashicons-wordpress-alt text-success" title="WordPress Integrated"></span></strong></p>';
			}

		} else {
			$html .= '<p class="text-danger"><strong>Waiting on Integration</strong>';
			$html .= '<br/><br/><a class="button button-success" href="https://pubdash.ezoic.com/integration" target="_blank" style="color: white; text-decoration: none; background: #5fa624; border-color: #53951a;">Integration Options</a>';
			if ( isset( $ezoic_options['disable_wp_integration'] ) && $ezoic_options['disable_wp_integration'] == true ) {
				$html .= '&nbsp;<a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=integration_status&wp_integration=1" style="color: white; text-decoration: none;">Enable WordPress Integration</a>';
			}
			$html .= '</p>';
		}
		$html .= '</div>';

		echo $html;

	} // end is_integrated_callback

	public function adstxt_manager_status_callback() {

		$options = \get_option( 'ezoic_integration_status' );
		$adstxtmanager_status = Ezoic_AdsTxtManager::ezoic_adstxtmanager_status(true);
		$adstxtmanager_id = Ezoic_AdsTxtManager::ezoic_adstxtmanager_id(true);

		$html = "";
		if ( ! empty( $options['integration_type'] ) && ( $options['integration_type'] == "wp" || $options['integration_type'] == "ba" ) ) {

			$html .= '<div>';
			if ( isset( $adstxtmanager_status['status'] ) && $adstxtmanager_status['status'] == false ) {
				$html .= '<p class="text-danger"><strong>Redirection Required</strong>';
				$html .= '<br/><br/><a class="button button-primary" href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=adstxtmanager_settings" style="color: white; text-decoration: none; margin-bottom: 12px;">Set up Ads.txt</a>';
			} else {
				$html .= '<p class="text-success"><strong>Successfully Setup</strong> &nbsp;<span class="dashicons dashicons-saved"></span></p>';
			}
			$html .= '</div>';

		}

		echo $html;
	} // end adstxt_manager_status_callback

	public function check_time_callback() {

		$options = \get_option( 'ezoic_integration_status' );

		$html = '<input type="hidden" id="check_time" name="ezoic_integration_status[check_time]" value="' . $options['check_time'] . '"/>';
		$html .= '<div><em>' . ( ! empty( $options['check_time'] ) ? date( 'm/d/Y H:i:s',
						$options['check_time'] ) : '' ) . '</em> &nbsp;<a href="?page=' . EZOIC__PLUGIN_SLUG . '&tab=integration_status&recheck=1"><span class="dashicons dashicons-update" title="WordPress Integrated" style="text-decoration: none;"></span></a></div>';

		echo $html;

	} // end check_time_callback


	public function verify_ssl_callback( $args ) {

		$options = \get_option( 'ezoic_integration_options' );

		$html = '<select id="verify_ssl" name="ezoic_integration_options[verify_ssl]">';
		$html .= '<option value="1" ' . selected( $options['verify_ssl'], 1, false ) . '>' . __( 'Yes',
						'ezoic' ) . '</option>';
		$html .= '<option value="0" ' . selected( $options['verify_ssl'], 0, false ) . '>' . __( 'No',
						'ezoic' ) . '</option>';
		$html .= '</select>';
		$html .= '<td><p>' . $args[0] . '</p></td>';

		echo $html;

	} // end verify_ssl_callback

	public function disable_wp_integration_callback( $args ) {

		$options = \get_option( 'ezoic_integration_options' );

		// disable by default
		if ( ! isset( $options['disable_wp_integration'] ) ) {
			$options['disable_wp_integration'] = 0;
			\update_option( 'ezoic_integration_options', $options );
		}

		$cache_identifier = new Ezoic_Integration_Cache_Identifier();
		if ($options['disable_wp_integration'] == 1) {
			//modify htaccess files
			$cache_identifier->remove_htaccess_file();
			//modify php files
			$cache_identifier->restore_advanced_cache();
		} else {
			//modify htaccess files
			$cache_identifier->generate_htaccess_file();
			//modify php files
			$cache_identifier->modify_advanced_cache();
		}

		$html = '<select id="disable_wp_integration" name="ezoic_integration_options[disable_wp_integration]">';
		$html .= '<option value="0" ' . selected( $options['disable_wp_integration'], 0, false ) . '>' . __( 'No',
						'ezoic' ) . '</option>';
		$html .= '<option value="1" ' . selected( $options['disable_wp_integration'], 1, false ) . '>' . __( 'Yes',
						'ezoic' ) . '</option>';
		$html .= '</select>';
		$html .= '<td><p>' . $args[0] . '</p></td>';

		echo $html;

	} // end disable_wp_integration_callback

	public function caching_callback() {

		$options = \get_option( 'ezoic_integration_options' );
		$disabled_text = '';
		$warning_text = '<td><p>Caches your site\'s pages directly on your WordPress server in order to decrease response time for your users.</p>';

		// If caching is currently turned off, make sure there is no advanced-cache.php file.
		// If there is one, it means that another caching plugin is in use and that we should
		// not allow the user to use Ezoic caching.
		if (!$options['caching']) {

			if (!$this->cache_integrator->has_valid_setup()) {
				$disabled_text = 'disabled';
				$warning_text .= '<br><br/><b>Ezoic\'s WordPress Caching cannot be turned on for the following reason(s):</b><ul>';


				// If the pub is cloud integrated, they do not need to use Ezoic WordPress Caching. Only show that message and disregard any of the other issues because they are not important.
				if (Ezoic_Integration_Admin::is_cloud_integrated()) {
					$warning_text .= "<li>Your site is integrated through an Ezoic Cloud Integration which already handles caching for you. You do not need to use Ezoic's WordPress Caching.</li>";
				} else {
					if ($this->cache_integrator->has_advanced_cache()) {
						$warning_text .= '<li>Ezoic\'s WordPress Caching does not work with other caching plugins. To use caching, please first deactivate your other <a href="' . get_admin_url( null, 'plugins.php' ) . '">caching plugins</a>, and then remove the advanced-cache.php file in the wp-content directory if it still exists.</li>';
					}

					if (!$this->cache_integrator->has_fancy_permalinks()) {
						$warning_text .= '<li>Ezoic\'s WordPress Caching does not work with the WordPress \'Plain\' permalink structure. To use caching, please change to a different <a href="' . get_admin_url( null, 'options-permalink.php' ) . '">permalink URL structure</a> (such as \'Post name\').</li>';
					}

					if (!$this->cache_integrator->has_writeable_wp_config()) {
						$warning_text .= "<li>The wp-config.php file is not write-able. Please update the permissions by running: <b>chmod 777 " . $this->cache_integrator->config_path . "</b> on your server.</li>";
					}

					if (!$this->cache_integrator->has_writeable_wp_content()) {
						$warning_text .= "<li>The /wp-content directory is not write-able. Please update the permissions by running: <b>chmod 777 " . WP_CONTENT_DIR . "</b> on your server.</li>";
					}
				}

				$warning_text .= '</ul>';
			}
		}

		$warning_text .= '</td>';
		$html = '<select id="caching" name="ezoic_integration_options[caching]">';
		$html .= '<option value="0" ' . selected( $options['caching'], 0, false ) . '>' . __( 'Off',
						'ezoic' ) . '</option>';
		$html .= '<option value="1" ' . selected( $options['caching'], 1, false ) . $disabled_text .'>' . __( 'On',
						'ezoic' ) . '</option>';
		$html .= '</select>';
		$html .= $warning_text;

		// If caching is enabled, create a button that will allow the user to clear the cache.
		if ( $options['caching'] ) {
			$html .= '
			</form>
			<td>
				<form action="" method="POST">
					<input type="hidden" name="action" value="clear_cache"/>
					<input class="button button-primary" type="submit" value="Clear Cache"/>
				</form>
			</td>';
		}

		echo $html;
	}

	public function plugin_compatibility_callback( $args ) {
		$html = '';

		$incompatible_plugins = $args[0];
		$compatible_plugins = $args[1];

		$options = \get_option( 'ezoic_integration_status' );

		// Check if running on WPEngine on non cloud sites
		if ( function_exists( 'is_wpe' ) ) {
			if ( is_wpe() && ( isset( $options['integration_type'] ) && $options['integration_type'] == "wp" ) ) {
				$html .= '<h3><span class="dashicons dashicons-warning text-danger"></span> Incompatibility with WPEngine</h3>';
				$html .= 'There are incompatibilities with Ezoic WordPress integration and WPEngine hosting. We recommend switching to Ezoic Cloud integration. <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">Click here to explore other integration options</a>.<br /><br />';
				//$html .= 'Learn how to successfully <a href="https://support.ezoic.com/kb/article/integrating-ezoic-with-wpengine" target="_blank">integrate Ezoic with WPEngine</a>.<br/><br />';
			}
		}

		// incompatible plugins
		if (count( $incompatible_plugins ) > 0) {
			$html .= '<h3><span class="dashicons dashicons-warning text-danger"></span> Incompatible Plugins Detected</h3>';

			if ( Ezoic_Integration_Admin::is_wordpress_integrated() ) {
				$html .= 'The following plugin(s) must be disabled to fully utilize <strong>Ezoic WordPress integration</strong> without issues or conflicts.<br/>We recommend switching to our <a href="' . EZOIC__SITE_LOGIN . '?redirect=%2Fintegration" target="_blank">Cloud Integration</a> for improved speed and compatibility';
				if ( count( $compatible_plugins ) > 0 ) {
					$html .= ', or review additional Ezoic Recommendations below';
				}
				$html .= '.';
			} else {
				$html .= 'The following plugin(s) must be disabled to fully utilize Ezoic without issues or conflicts. ';
				if ( count( $compatible_plugins ) > 0 ) {
					$html .= 'See Ezoic Recommendations below.';
				}
			}
			$html .= '<br /><br /><br/>';

			foreach ( $incompatible_plugins as $plugin ) {
				$html .= '<strong>' . $plugin['name'] . ' (' . $plugin['version'] . ') </strong>';
				$html .= '<br />';
				$html .= $plugin['message'];

				$deactivate_link = Ezoic_Integration_Compatibility_Check::plugin_action_url( $plugin['filename'] );
				$html .= '<br/><p><a class="button button-primary" href="' . $deactivate_link . '">Deactivate Plugin</a></p>';

				$html .= '<br /><br />';
			}
		}

		// show compatible plugins that can be replaced by Ezoic product (eg. Site Speed) and display recommendations
		if (count($compatible_plugins) > 0) {
			if (count($incompatible_plugins) > 0) {
				$html .= '<hr/><br/>';
			}

			$plugin_string = '';
			foreach ($compatible_plugins as $plugin) {
				$plugin_string .= '<strong>'. $plugin['name'] .'</strong><br />';
				$plugin_string .= $plugin['message'] .'<br /><br />';
			}
			$html .= '<h3>Ezoic Recommendations</h3>
				We recommend using <a target="_blank" rel="noopener noreferrer" href="https://www.ezoic.com/products/leap/"><strong>Ezoic\'s Leap</strong></a> features for caching and speed performance improvements.<br /><br />'
					 .  'The following plugin(s) <i>may or may not</i> be compatible with Ezoic:<br /><br />'
					 .   $plugin_string . '<br />';
		}

		echo $html;
	}

	public function display_notice( $options ) {
		$cache_identifier     = new Ezoic_Integration_Cache_Identifier();
		$this->cache_identity = $cache_identifier->get_cache_identity();
		$this->cache_type     = $cache_identifier->get_cache_type();

		// enable WP integration
		if ( isset( $_GET['wp_integration'] ) && $_GET['wp_integration'] ) {
			$integration_options                           = \get_option( 'ezoic_integration_options' );
			$integration_options['disable_wp_integration'] = 0;
			\update_option( 'ezoic_integration_options', $integration_options );
		}

		$time_check = current_time( 'timestamp' ) - 21600; // 6 hours
		if ( !isset($options['is_integrated']) || $options['check_time'] <= $time_check || ( isset( $_GET['recheck'] ) && $_GET['recheck'] ) ) {

			$results = $this->get_integration_check_ezoic_response();

			$update                     = array();
			$update['is_integrated']    = $results['result'];
			$update['integration_type'] = $results['integration'];
			$update['check_time']       = current_time( 'timestamp' );
			update_option( 'ezoic_integration_status', $update );

			if ( false === $results['result'] ) {

				if ( ! empty( $results['error'] ) ) {
					$args = apply_filters(
							'ezoic_view_arguments',
							array( 'type' => 'integration_error' ),
							'ezoic-integration-admin'
					);
				} else {
					$args = apply_filters(
							'ezoic_view_arguments',
							array( 'type' => 'not_integrated' ),
							'ezoic-integration-admin'
					);
				}

				foreach ( $args as $key => $val ) {
					$$key = $val;
				}

			}
			$is_integrated = $results['result'];

			$file = EZOIC__PLUGIN_DIR . 'admin/partials/' . 'ezoic-integration-admin-display' . '.php';
			include( $file );

		} else {
			$is_integrated = $options['is_integrated'];
		}
	}

	/**
	 * @return array
	 */
	private function get_integration_check_ezoic_response() {
		$content  = 'ezoic integration test';
		$response = $this->request_data_from_ezoic( $content );

		// no integration, recheck for sa/ba
		if ( $response['result'] !== true ) {
			$response = $this->request_data_from_ezoic( $content, get_home_url() . '?ezoic_domain_verify=1' );
		}

		return $response;
	}


	/**
	 * @param $final_content
	 * @param $request_url
	 *
	 * @return array
	 */
	private function request_data_from_ezoic( $final_content, $request_url = "" ) {
		$timeout = 5;

		$cache_key = md5( $final_content );

		$request_data = Ezoic_Integration_Request_Utils::get_request_base_data();

		if ( empty( $request_url ) ) {
			$request_url = Ezoic_Integration_Request_Utils::get_ezoic_server_address();
		}

		$request_params = array(
				'cache_key'                    => $cache_key,
				'action'                       => 'get-index-series',
				'content_url'                  => get_home_url() . '?ezoic_domain_verify=1',
				'request_headers'              => $request_data["request_headers"],
				'response_headers'             => $request_data["response_headers"],
				'http_method'                  => $request_data["http_method"],
				'ezoic_api_version'            => $request_data["ezoic_api_version"],
				'ezoic_wp_integration_version' => $request_data["ezoic_wp_plugin_version"],
				'content'                      => $final_content,
				'request_type'                 => 'with_content',
		);

		$ezoic_options = \get_option( 'ezoic_integration_options' );

		if ( $this->cache_type != Ezoic_Cache_Type::NO_CACHE && function_exists('curl_version' )) {

			$settings = array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL            => $request_url, //$request_data["ezoic_request_url"]
					CURLOPT_TIMEOUT        => $timeout,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTPHEADER     => array(
							'X-Wordpress-Integration: true',
							'X-Forwarded-For: ' . $request_data["client_ip"],
							'Content-Type: application/x-www-form-urlencoded',
							'Expect:',
					),
					CURLOPT_POST           => true,
					CURLOPT_HEADER         => true,
					CURLOPT_POSTFIELDS     => http_build_query( $request_params ),
					CURLOPT_USERAGENT      => ! empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			);

			if ( isset($ezoic_options['verify_ssl']) && $ezoic_options['verify_ssl'] == false ) {
				$settings[ CURLOPT_SSL_VERIFYPEER ] = false;
				$settings[ CURLOPT_SSL_VERIFYHOST ] = false;
			}

			$result = Ezoic_Integration_Request_Utils::make_curl_request( $settings );

			if ( ! empty( $result['error'] ) ) {
				return array( "result" => false, "error" => $result['error'], "integration" => "off" );
			}

		} else {

			unset( $request_data["request_headers"]["Content-Length"] );
			$request_data["request_headers"]['X-Wordpress-Integration'] = 'true';

			$settings = array(
					'timeout' => $timeout,
					'body'    => $request_params,
					'headers' => array(
							'X-Wordpress-Integration' => 'true',
							'X-Forwarded-For'         => $request_data["client_ip"],
							'Expect'                  => ''
					),
			);

			if ( isset($ezoic_options['verify_ssl']) && $ezoic_options['verify_ssl'] == false ) {
				$settings['sslverify'] = false;
			}

			$result = wp_remote_post( $request_url, $settings );

			if ( is_wp_error( $result ) ) {
				return array( "result" => false, "error" => $result->get_error_message(), "integration" => "off" );
			}

		}

		if ( is_array( $result ) && isset( $result['body'] ) ) {
			$final = $result['body'];
		} else {
			$final = $result;
		}

		return $this->parse_page_contents( $final );

	}

	/**
	 * @param $contents
	 *
	 * @return array
	 */
	private function parse_page_contents( $contents ) {

		$ezoic_options = \get_option( 'ezoic_integration_options' );

		$results = array( 'result' => false, 'integration' => "off" );

		if ( Ezoic_Integration_Admin::is_cloud_integrated() ) {
			$results['integration'] = "cloud";
			$results['result']      = true;
		} elseif ( ( isset( $ezoic_options['disable_wp_integration'] ) && ! $ezoic_options['disable_wp_integration'] ) && strpos( $contents,
						'This site is operated by Ezoic and Wordpress Integrated' ) !== false ) {
			$results['integration'] = "wp";
			$results['result']      = true;
		} elseif ( strpos( $contents, 'go.ezoic.net/ezoic/ezoic.js' ) !== false ) {
			$results['integration'] = "js";
			$results['result']      = true;
		} elseif ( strpos( $contents, 'g.ezoic.net/ezoic/sa.min.js' ) !== false || strpos( $contents, 'ezojs.com/ezoic/sa.min.js' ) !== false ) {
			$results['integration'] = "sa";
			$results['result']      = true;
		} elseif ( strpos( $contents, 'g.ezoic.net/ez.min.js' ) !== false || strpos( $contents, 'ezojs.com/ez.min.js' ) !== false || strpos( $contents,
						'ezojs.com/basicads.js?d=' ) !== false ) {
			$results['integration'] = "ba";
			$results['result']      = true;
		}

		return $results;
	}
}
