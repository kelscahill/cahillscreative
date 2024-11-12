<?php
/**
 * Admin page : dashboard
 *
 * @package SIB_Page_Scenarios
 */

if ( ! class_exists( 'SIB_Page_Scenarios' ) ) {
	/**
	 * Page class that handles backend page <i>scenario ( for admin )</i> with form generation and processing
	 *
	 * @package SIB_Page_Scenarios
	 */
	class SIB_Page_Scenarios {

		/**
		 * Page slug
		 */
		const PAGE_ID = 'SIB_Page_Scenarios';

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
			$this->page_hook = add_submenu_page( SIB_Page_Home::PAGE_ID, __( 'Workflows', 'mailin' ), __( 'Workflows', 'mailin' ), 'manage_options', self::PAGE_ID, array( &$this, 'generate' ) );
			add_action( 'load-' . $this->page_hook, array( &$this, 'init' ) );
			add_action( 'admin_print_scripts-' . $this->page_hook, array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles-' . $this->page_hook, array( $this, 'enqueue_styles' ) );
		}

		/**
		 * Init Process
		 */
		function Init() {

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
		}

		/** Generate page script */
		function generate() {
			?>
			<div id="wrap" class="box-border-box container-fluid">
				<h2><img id="logo-img" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/logo.png' ); ?>" alt="Logo Image"></h2>
				<div id="wrap-left" class="box-border-box col-md-9 ">
					<?php
						$this->generate_main_page();
					?>
				</div>
				<div id="wrap-right-side" class="box-border-box col-md-3">
					<?php

					SIB_Page_Home::generate_side_bar();
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
			$ma_link = 'https://automation.brevo.com/scenarios/?create_new_scenario';
			$ma_exist_link = 'https://automation.brevo.com/scenarios/?utm_source=wordpress_plugin&utm_medium=plugin&utm_campaign=module_link';
			$ma_logs_link = 'https://automation.brevo.com/log/scenarios/?utm_source=wordpress_plugin&utm_medium=plugin&utm_campaign=module_link';

			?>
			<h2 style="text-align: center;"><?php esc_attr_e( 'Create your workflow', 'mailin' ); ?></h2>
			<div class="sib-scenarios">
				<div class="col-md-4 ">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Custom workflow','mailin' ); ?></h4>
							<p class="text-center">
                                <img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/custom.png' ); ?>" height="70px" alt="Custom Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Mix and match triggers, conditions, and actions to create a custom workflow.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Welcome Message','mailin' ); ?></h4>
							<p class="text-center">
                                <img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/welcome_message.png' ); ?>" height="70px" alt="Welcome Message Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send a welcome email after a subscriber joins your list.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Anniversary Date','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/anniversary_of_date.png' ); ?>" height="70px" alt="Anniversary of Date Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send an annual email or series of emails based on a special event or birthday.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Page Visit','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/page_visit.png' ); ?>" height="70px" alt="Page Visit Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send emails after a contact visits a specific page on your website.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Website Event','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/website_event.png' ); ?>" height="70px" alt="Website Event Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send emails after a contact performs a specific action on your website.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Product Purchase','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/product_purchase.png' ); ?>" height="70px" alt="Product Purchase Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send emails when a product is purchased on your website.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Marketing Activity','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/newsletter_activity.png' ); ?>" height="70px" alt="Newsletter Activity Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send emails based on whether contacts open or click on an email campaign.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
				<div class="col-md-4">
					<a class="sib-scenario-link" href="<?php echo esc_url( $ma_link ); ?>" target="_blank" rel="noopener">
						<div class="thumbnail thumbanail_focus">
							<h4 class="text-center"><?php esc_attr_e( 'Transactional Activity','mailin' ); ?></h4>
							<p class="text-center">
								<img class="" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/transactional_activity.png' ); ?>" height="70px" alt="Transactional Activity Image">
							</p>
							<p class="text-center"><?php esc_attr_e( 'Send an email based whether contacts open or click on transactional emails.', 'mailin' ); ?></p>
						</div>
					</a>
				</div>
			</div>

			<div class="col-md-6" style="text-align: right;padding: 0 50px;">
				<a href="<?php echo esc_url( $ma_exist_link ); ?>" class="button" target="_blank" rel="noopener" style="width: 200px;text-align: center;"><?php esc_attr_e( 'View my existing workflows', 'mailin' ); ?></a>
				</div>
			<div class="col-md-6" style="text-align: left;padding: 0 50px;">
				<a href="<?php echo esc_url( $ma_logs_link ); ?>" class="button" target="_blank" rel="noopener" style="width: 200px;text-align: center;"><?php esc_attr_e( 'View logs', 'mailin' ); ?></a>
			</div>

			<?php

		}

		/** Generate welcome page */
		function generate_welcome_page() {
			?>
			<img src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/background/lists.png' ); ?>" style="width: 100%;" alt="Lists Background Image">
		<?php
			SIB_Page_Home::print_disable_popup();
		}

	}
}
