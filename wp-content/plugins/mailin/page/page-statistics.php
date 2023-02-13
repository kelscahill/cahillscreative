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
			$this->page_hook = add_submenu_page( SIB_Page_Home::PAGE_ID, __( 'Statistics', 'mailin' ), __( 'Statistics', 'mailin' ), 'manage_options', self::PAGE_ID, array( &$this, 'generate' ) );
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
			$client = new SendinblueApiClient();

            $date = $this->get_selected_statistics_dates();
			$data = [
                'type' => 'classic',
                'status' => 'sent',
                'startDate' => $date['startDate'],
                'endDate' => $date['endDate'],
                'offset' => 0,
            ];

			$emailCampaigns = $client->getAllCampaignsByType(SendinblueApiClient::CAMPAIGN_TYPE_EMAIL, $data);
            $smsCampaigns = $client->getAllCampaignsByType(SendinblueApiClient::CAMPAIGN_TYPE_SMS, $data);
            /**
             * Statistics on general options
             */
                ?>
                <h3 class="statistics_h3"><?php _e('Statistics', 'wc_sendinblue'); ?></h3>
                <div id="sib-statistics-date-container">
                    <form method="POST" id="sib-statistics-form">
                        <label for="sib-statistics-date"><?php esc_attr_e( 'Date', 'mailin' );?>: </label>
                        <?php if (strtotime($date['statisticsDate']) !== false) { ?>
                        <input id="sib-statistics-date" name="sib-statistics-date" value="<?php echo esc_attr( $date['statisticsDate'] ); ?>" autocomplete="off" class="button show-settings">
                        <?php } else { ?>
                        <input id="sib-statistics-date" name="sib-statistics-date" value="<?php echo date('Y-m-d'); ?>" autocomplete="off" class="button show-settings">
                        <?php } ?>
                        <button id="apply-date-range" class="button action"><?php esc_attr_e( 'Apply', 'mailin'); ?></button>
                        <span class="sib-spinner spinner"></span>
                    </form>
                </div>
            
                <table id="ws_statistics_table" class="wc_shipping widefat wp-list-table" cellspacing="0">
                    <thead>
                    <tr>
                        <th class="sort">&nbsp;</th>
                        <th class=""><?php esc_attr_e( 'Name', 'mailin' );?></th>
                        <th class=""><?php esc_attr_e('Recipients','mailin');?></th>
                        <th class=""><?php esc_attr_e('Deliverability Rate','mailin');?></th>
                        <th class=""><?php esc_attr_e('Opens','mailin');?></th>
                        <th class=""><?php esc_attr_e('Clicks','mailin');?></th>
                        <th class=""><?php esc_attr_e('Unsubscriptions','mailin');?></th>
                        <th class=""><?php esc_attr_e('Bounces','mailin');?></th>
                        <th class=""><?php esc_attr_e('Date','mailin');?></th>
                    </tr>
                    </thead>
                    <tbody class="ui-sortable">

                        <h3 class="statistics_h3"><?php esc_attr_e( 'Email Campaigns', 'mailin' );?></h3>
                    <?php
                    if (!empty($emailCampaigns)) {
                        foreach ($emailCampaigns as $campaign) { ?>
                            <tr id="<?php echo str_replace(' ', '-', esc_attr( $campaign['name'] ));?>">
                                <td width="1%" class="sort ui-sortable-handle">
                                    <input type="hidden" name="method_order[flat_rate]" value="">
                                </td>
                                <td class=""><?php echo esc_attr( $campaign['name'] );?></td>
                                <td class="sib-statistics-data-value"><?php echo esc_attr( $campaign['statistics']['globalStats']['sent'] );?></td>
                                <td class="sib-statistics-data-value"><?php echo empty($campaign['statistics']['globalStats']['sent']) ? 0 : round(esc_attr( $campaign['statistics']['globalStats']['delivered'] ) * 100 / esc_attr( $campaign['statistics']['globalStats']['sent'] ), 2);?>%</td>
                                <td class="sib-statistics-data-value"><?php echo !empty($campaign['statistics']['globalStats']['viewed']) ? esc_attr( $campaign['statistics']['globalStats']['viewed'] ) : 0;?></td>
                                <td class="sib-statistics-data-value"><?php echo !empty($campaign['statistics']['globalStats']['clickers']) ? esc_attr( $campaign['statistics']['globalStats']['clickers'] ) : 0;?></td>
                                <td class="sib-statistics-data-value"><?php echo !empty($campaign['statistics']['globalStats']['unsubscriptions']) ? esc_attr( $campaign['statistics']['globalStats']['unsubscriptions'] ) : 0; ?></td>
                                <td class="sib-statistics-data-value"><?php echo esc_attr( $campaign['statistics']['globalStats']['softBounces'] ) + esc_attr( $campaign['statistics']['globalStats']['hardBounces'] );?></td>
                                <td class="sib-statistics-data-value sib-last-column-value"><?php echo (new DateTime($campaign['sentDate']))->format('Y-m-d H:i:s');?></td>
                            </tr>
                        <?php } ?>
                    <?php } else {  ?>
                        <tr> <td colspan="9" style="text-align:center;"><?php esc_attr_e( 'No Stats Found', 'mailin' ); ?></td></tr>
                    <?php } ?>
                        </tbody>
                    </table>
                <table id="ws_statistics_table" class="wc_shipping widefat wp-list-table" cellspacing="0">
                    <thead>
                    <tr>
                        <th class="sort">&nbsp;</th>
                        <th class=""><?php esc_attr_e( 'Name', 'mailin' );?></th>
                        <th class=""><?php esc_attr_e('Recipients','mailin');?></th>
                        <th class=""><?php esc_attr_e('Deliverability Rate','mailin');?></th>
                        <th class=""><?php esc_attr_e('Answeres','mailin');?></th>
                        <th class=""><?php esc_attr_e('Unsubscriptions','mailin');?></th>
                        <th class=""><?php esc_attr_e('Bounces','mailin');?></th>
                        <th class=""><?php esc_attr_e('Date','mailin');?></th>
                    </tr>
                    </thead>
                    <tbody class="ui-sortable">

                        <h3 class="statistics_h3"><?php esc_attr_e( 'SMS Campaigns', 'mailin' );?></h3>
                    <?php
                    if (!empty($smsCampaigns)) {
                        foreach($smsCampaigns as $smsCampaign){ ?>
                            <tr id="<?php echo str_replace(' ', '-', esc_attr( $smsCampaign['name'] ));?>">
                                <td width="1%" class="sort ui-sortable-handle">
                                    <input type="hidden" name="method_order[flat_rate]" value="">
                                </td>
                                <td class=""><?php echo esc_attr( $smsCampaign['name'] );?></td>
                                <td class="sib-statistics-data-value"><?php echo esc_attr( $smsCampaign['statistics']['sent'] );?></td>
                                <td class="sib-statistics-data-value"><?php echo empty($smsCampaign['statistics']['sent']) ? 0 : round(esc_attr( $smsCampaign['statistics']['delivered'] ) * 100 / esc_attr( $smsCampaign['statistics']['sent'] ), 2);?>%</td>
                                <td class="sib-statistics-data-value"><?php echo !empty($smsCampaign['statistics']['answered']) ? esc_attr( $smsCampaign['statistics']['answered'] ) : 0;?></td>
                                <td class="sib-statistics-data-value"><?php echo !empty($smsCampaign['statistics']['unsubscriptions']) ? esc_attr( $smsCampaign['statistics']['unsubscriptions'] ) : 0;?></td>
                                <td class="sib-statistics-data-value"><?php echo esc_attr( $smsCampaign['statistics']['softBounces'] ) + esc_attr( $smsCampaign['statistics']['hardBounces'] );?></td>
                                <td class="sib-statistics-data-value sib-last-column-value"><?php echo  (new DateTime($smsCampaign['sentDate']))->format('Y-m-d H:i:s');?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr> <td colspan="9" style="text-align:center;"><?php esc_attr_e( 'No Stats Found', 'mailin' ); ?></td></tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php
		}

		/** Generate welcome page */
		function generate_welcome_page() {
			?>
			<img src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/background/statistics.png' ); ?>"  style="width: 100%;">
		<?php
			SIB_Page_Home::print_disable_popup();
		}

		function get_selected_statistics_dates() {
            $startDate = (new DateTime())->format(self::START_DATE_FORMAT);
            $endDate = (new DateTime())->format(self::END_DATE_FORMAT_NOW);

            if (empty($_POST['sib-statistics-date'])) {
                $statisticsDate = date('Y-m-d');
            } else {
                $statisticsDate = sanitize_text_field($_POST['sib-statistics-date']);
                $date = explode(' - ', $statisticsDate);

                if (count($date) === 1) {
                    $date[] = $date[0];
                }

                $startDate =  (new DateTime($date[0]));
                $endDate =  (new DateTime($date[1]));
                if ($date[0] >= date('Y-m-d') || $date[1] >= date('Y-m-d')) {
                    $startDate = $startDate->format(self::START_DATE_FORMAT);
                    $endDate =  (new DateTime())->format(self::END_DATE_FORMAT_NOW);
                } elseif ($date[0] === $date[1]) {
                    $startDate = $startDate->format(self::START_DATE_FORMAT);
                    $endDate = $endDate->format(self::END_DATE_FORMAT);
                } else {
                    $startDate = $startDate->format(self::START_DATE_FORMAT);
                    $endDate =  $endDate->format(self::END_DATE_FORMAT);
                }
            }

            return [
                    'statisticsDate' => $statisticsDate,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
            ];
        }
	}
}
