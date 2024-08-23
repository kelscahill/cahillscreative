<?php
/**
 * Admin page : dashboard
 *
 * @package SIB_Page_Statistics
 */

if ( ! class_exists( 'SIB_Page_Statistics' ) ) {
	/**
	 * Page class that handles backend page <i>dashboard ( for admin )</i> with form generation and processing
	 *
	 * @package SIB_Page_Statistics
	 */
	class SIB_Page_Statistics {

		/**
		 * Page slug
		 */
		const PAGE_ID = 'sib_page_statistics';

		const START_DATE_FORMAT = 'Y-m-d\T00:00:00\Z';
		const END_DATE_FORMAT = 'Y-m-d\T23:59:59\Z';
		const END_DATE_FORMAT_NOW = 'Y-m-d\TH:i:s\Z';
		/**
		 * Page hook
		 *
		 * @var string
		 */
		protected $page_hook;

		/**
		 * Page tabs
		 *
		 * @var mixed
		 */
		protected $tabs;

		/**
		 * Constructs new page object and adds entry to WordPress admin menu
		 */
		function __construct() {
            		global $wp_roles;
			$wp_roles->add_cap( 'administrator', 'view_custom_menu' ); 
			$wp_roles->add_cap( 'editor', 'view_custom_menu' );

			$this->page_hook = add_submenu_page( SIB_Page_Home::PAGE_ID, __( 'Statistics', 'mailin' ), __( 'Statistics', 'mailin' ), 'view_custom_menu', self::PAGE_ID, array( &$this, 'generate' ) );
			add_action( 'load-' . $this->page_hook, array( &$this, 'init' ) );
			add_action( 'admin_print_scripts-' . $this->page_hook, array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles-' . $this->page_hook, array( $this, 'enqueue_styles' ) );
		}

		/**
		 * Init Process
		 */
		function Init() {
            SIB_Manager::is_done_validation();
			add_action( 'admin_notices', array( 'SIB_Manager', 'language_admin_notice' ) );
		}

		/**
		 * Enqueue scripts of plugin
		 */
		function enqueue_scripts() {
			wp_enqueue_script( 'sib-admin-js' );
			wp_enqueue_script( 'sib-bootstrap-js' );
			wp_localize_script(
				'sib-admin-js', 'ajax_sib_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}

		/**
		 * Enqueue style sheets of plugin
		 */
		function enqueue_styles() {
			wp_enqueue_style( 'sib-admin-css' );
			wp_enqueue_style( 'sib-bootstrap-css' );
			wp_enqueue_style( 'sib-fontawesome-css' );
			wp_enqueue_style( 'thickbox' );
            wp_enqueue_style( 'sib-jquery-ui-datepicker', SIB_Manager::$plugin_url . '/css/datepicker.css', false, false, false );
		}

		/** Generate page script */
		function generate() {
			?>
			<div id="wrap1" class="box-border-box container-fluid">
				<div id="main-content" class="row">
					<?php
                        if ( SIB_Manager::is_api_key_set() ) {
                            $this->generate_main_page();
                        } else {
                            $this->generate_welcome_page();
                        }
					?>
				</div>
			</div>
			<style>
				#wpcontent {
					margin-left: 160px !important;
				}

				@media only screen and (max-width: 918px) {
					#wpcontent {
						margin-left: 40px !important;
					}
				}
			</style>
		<?php
		}

		/** Generate main page */
		function generate_main_page() {
            /**
             * Statistics on general options
             */
                ?>
                <h3 class="statistics_h3"><?php _e('Statistics', 'wc_sendinblue'); ?></h3>
                <table aria-describedby="statistic-table" id="ws_statistics_table" class="wc_shipping widefat wp-list-table">
                    <tbody class="ui-sortable">
                        <h3 class="statistics_h3"> <a href="https://my.brevo.com/camp/message/stats/sms" class="btn btn-success" target="_blank" rel="noopener" style="margin: 2px 1px 8px 15px;"><?php esc_attr_e( 'View Statistics', 'mailin' );?></a></h3>
                    </tbody>
                </table>
            <?php
		}

		/** Generate welcome page */
		function generate_welcome_page() {
			?>
            <img src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/background/statistics.png' ); ?>" alt="Statistics Background Image" style="width: 100%;">
		<?php
			SIB_Page_Home::print_disable_popup();
		}
	}
}
