<?php
/**
 * Admin page : dashboard
 *
 * @package SIB_Page_Form
 */

if ( ! class_exists( 'SIB_Page_Push' ) ) {
	/**
	 * Page class that handles backend page <i>dashboard ( for admin )</i> with push notification settings
	 *
	 * @package SIB_Page_Form
	 */
	class SIB_Page_Push {
		/** Page slug */
		const PAGE_ID = 'sib_page_push';

		/**
		 * Page hook
		 *
		 * @var false|string
		 */
		protected $page_hook;

		/**
		 * Constructs new page object and adds entry to WordPress admin menu
		 */
		function __construct() {
			global $wp_roles;
			$wp_roles->add_cap( 'administrator', 'view_custom_menu' );
			$wp_roles->add_cap( 'editor', 'view_custom_menu' );

			$title = get_bloginfo('name');
			$settings = SIB_Push_Settings::getSettings();
			$show_push = $settings->getShowPush();
			if ($show_push) {
				$this->page_hook = add_submenu_page( SIB_Page_Home::PAGE_ID, __( 'Web push', 'mailin' ), __( 'Web push', 'mailin' ), 'view_custom_menu', self::PAGE_ID, array( &$this, 'generate' ) );
			}
			add_action( 'admin_print_scripts-' . $this->page_hook, array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles-' . $this->page_hook, array( $this, 'enqueue_styles' ) );
			add_action( 'load-' . $this->page_hook, array( &$this, 'init' ) );
		}

		/**
		 * Init Process
		 */
		function Init() {
			SIB_Manager::is_done_validation();
		}

		/**
		 * Enqueue scripts of plugin
		 */
		function enqueue_scripts() {
			wp_enqueue_script( 'sib-push-js' );
			wp_enqueue_script( 'sib-bootstrap-js' );
			wp_localize_script(
				'sib-push-js', 'ajax_sib_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( SIB_Push_API::NONCE_ACTION ),
					'site_title' => get_bloginfo('name'),
//					NOTE: deactivate woocommerce
//					'woocommerce' => SIB_Push_Utils::get_woocommerce() ? true : false,
					'woocommerce' => false,
					'amp' => SIB_Push_Utils::is_amp_installed() ? true : false,
				)
			);
		}

		/**
		 * Enqueue style sheets of plugin
		 */
		function enqueue_styles() {
			wp_enqueue_style( 'sib-admin-css' );
			wp_enqueue_style( 'sib-bootstrap-css' );
			wp_enqueue_style( 'sib-chosen-css' );
			wp_enqueue_style( 'sib-fontawesome-css' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'sib-font-face' );
		}

		/** Generate page script */
		function generate() {
			?>
            <div id="wrap" class="wrap box-border-box container-fluid">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" viewBox="0 0 32 32">
                    <circle cx="16" cy="16" r="16" fill="#0B996E"/>
                    <path fill="#fff"
                          d="M21.002 14.54c.99-.97 1.453-2.089 1.453-3.45 0-2.814-2.07-4.69-5.19-4.69H9.6v20h6.18c4.698 0 8.22-2.874 8.22-6.686 0-2.089-1.081-3.964-2.998-5.174Zm-8.62-5.538h4.573c1.545 0 2.565.877 2.565 2.208 0 1.513-1.329 2.663-4.048 3.54-1.854.574-2.688 1.059-2.997 1.634l-.094.001V9.002Zm3.151 14.796h-3.152v-3.085c0-1.362 1.175-2.693 2.813-3.208 1.453-.484 2.657-.969 3.677-1.482 1.36.787 2.194 2.148 2.194 3.57 0 2.42-2.35 4.205-5.532 4.205Z"/>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="25" fill="currentColor" viewBox="0 0 90 31">
                    <path fill="#0B996E"
                          d="M73.825 19.012c0-4.037 2.55-6.877 6.175-6.877 3.626 0 6.216 2.838 6.216 6.877s-2.59 6.715-6.216 6.715c-3.626 0-6.175-2.799-6.175-6.715Zm-3.785 0c0 5.957 4.144 10.155 9.96 10.155 5.816 0 10-4.198 10-10.155 0-5.957-4.143-10.314-10-10.314s-9.96 4.278-9.96 10.314ZM50.717 8.937l7.81 19.989h3.665l7.81-19.989h-3.945L60.399 24.37h-.08L54.662 8.937h-3.945Zm-15.18 9.354c.239-3.678 2.67-6.156 5.977-6.156 2.867 0 5.02 1.84 5.338 4.598h-6.614c-2.35 0-3.626.28-4.58 1.56h-.12v-.002Zm-3.784.6c0 5.957 4.183 10.274 9.96 10.274 3.904 0 7.33-1.998 8.804-5.158l-3.187-1.6c-1.115 2.08-3.267 3.319-5.618 3.319-2.83 0-5.379-2.16-5.379-4.238 0-1.08.718-1.56 1.753-1.56h12.63v-1.079c0-5.997-3.825-10.155-9.323-10.155-5.497 0-9.641 4.279-9.641 10.195M20.916 28.924h3.586V16.653c0-2.639 1.632-4.518 3.905-4.518.956 0 1.951.32 2.43.758.36-.96.917-1.918 1.753-2.878-.957-.799-2.59-1.32-4.184-1.32-4.382 0-7.49 3.279-7.49 7.956v12.274-.001Zm-17.33-13.23V5.937h5.896c1.992 0 3.307 1.16 3.307 2.919 0 1.998-1.713 3.518-5.218 4.677-2.39.759-3.466 1.399-3.865 2.16h-.12Zm0 9.794v-4.077c0-1.799 1.514-3.558 3.626-4.238 1.873-.64 3.425-1.28 4.74-1.958 1.754 1.04 2.829 2.837 2.829 4.717 0 3.198-3.028 5.556-7.132 5.556H3.586ZM0 28.926h7.968c6.057 0 10.597-3.798 10.597-8.835 0-2.759-1.393-5.237-3.864-6.836 1.275-1.28 1.873-2.76 1.873-4.559 0-3.717-2.67-6.196-6.693-6.196H0v26.426Z"/>
                </svg>
                <div class="row">
                    <div id="wrap-left" class="box-border-box col-md-9 ">
                        <div id="root" style="margin-top: 20px"></div>
                    </div>
                    <div id="wrap-right-side" class="box-border-box col-md-3">
						<?php SIB_Page_Home::generate_side_bar(); ?>
                    </div>
                </div>
            </div>
			<?php
		}
	}
}
