<?php
/**
 * Admin page : dashboard
 *
 * @package SIB_Page_Form
 */

if ( ! class_exists( 'SIB_Page_Form' ) ) {
	/**
	 * Page class that handles backend page <i>dashboard ( for admin )</i> with form generation and processing
	 *
	 * @package SIB_Page_Form
	 */
	class SIB_Page_Form {
		/** Page slug */
		const PAGE_ID = 'sib_page_form';

		/**
		 * Page hook
		 *
		 * @var false|string
		 */
		protected $page_hook;

		/**
		 * Page tabs
		 *
		 * @var mixed
		 */
		protected $tabs;

		/**
		 * Form ID
		 *
		 * @var string
		 */
		public $formID;

        /**
         * Default compliant Note
         *
         * @var string
         */
		public $defaultComplianceNote;

		/**
		 * Constructs new page object and adds entry to WordPress admin menu
		 */
		function __construct() {
		    $title = get_bloginfo('name');
		    $this->defaultComplianceNote = sprintf( esc_attr('Your e-mail address is only used to send you our newsletter and information about the activities of %s. You can always use the unsubscribe link included in the newsletter.', 'mailin'), $title);
			$this->page_hook = add_submenu_page( SIB_Page_Home::PAGE_ID, __( 'Forms', 'mailin' ), __( 'Forms', 'mailin' ), 'manage_options', self::PAGE_ID, array( &$this, 'generate' ) );
			add_action( 'admin_print_scripts-' . $this->page_hook, array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles-' . $this->page_hook, array( $this, 'enqueue_styles' ) );
			add_action( 'load-' . $this->page_hook, array( &$this, 'init' ) );
		}

		/**
		 * Init Process
		 */
		function Init() {
            SIB_Manager::is_done_validation();
            $this->forms = new SIB_Forms_List();
            $this->forms->prepare_items();
		}

		/**
		 * Enqueue scripts of plugin
		 */
		function enqueue_scripts() {
			wp_enqueue_script( 'sib-admin-js' );
			wp_enqueue_script( 'sib-bootstrap-js' );
			wp_enqueue_script( 'sib-chosen-js' );

			wp_localize_script(
				'sib-admin-js', 'ajax_sib_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'ajax_nonce' => wp_create_nonce( 'ajax_sib_admin_nonce' ),
                    'compliance_note' => $this->defaultComplianceNote
				)
			);
			wp_localize_script( 'sib-admin-js', 'sib_img_url', array(SIB_Manager::$plugin_url.'/img/flags/') );
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
		}

		/** Generate page script */
		function generate() {
			?>
			<div id="wrap" class="wrap box-border-box container-fluid">
				<h1><img id="logo-img" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/logo.png' ); ?>">
					<?php
					$return_btn = 'none';
					if (isset( $_GET['id'] ) ) {
						$return_btn = 'inline-block';
					}
					?>
				<a href="<?php echo esc_url( add_query_arg( 'page', self::PAGE_ID, admin_url( 'admin.php' ) ) ); ?>" class="button" style="margin-top: 6px; display: <?php echo esc_attr( $return_btn ); ?>;"><?php esc_attr_e( 'Back to form\'s list' ,'mailin' ); ?></a>
				</h1>
				<div class="row">
					<div id="wrap-left" class="box-border-box col-md-9 ">
						<input type="hidden" class="sib-dateformat" value="<?php echo esc_attr( 'yyyy-mm-dd' ); ?>">
						<?php
						if ( SIB_Manager::is_api_key_set() ) {
							if ( ( isset( $_GET['action'] ) && 'edit' === sanitize_text_field($_GET['action'] )) || ( isset( $_GET['action'] ) && 'duplicate' === sanitize_text_field($_GET['action'] )) ) {
								$this->formID = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : 'new';
								$this->generate_form_edit();
							} else {
								$this->generate_forms_page();
							}
						} else {
							$this->generate_welcome_page();
						}
						?>
					</div>
					<div id="wrap-right-side" class="box-border-box col-md-3">
						<?php

						SIB_Page_Home::generate_side_bar();
						?>
					</div>
				</div>
			</div>
			<div id="sib_modal" class="modal fade" role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal">&times;</button>
							<h4><?php esc_attr_e( 'You are about to change the language', 'mailin' ); ?></h4>
						</div>
						<div class="modal-body">
							<p><?php esc_attr_e( "Please make sure that you've saved all the changes. We will have to reload the page.", 'mailin' ); ?></p>
							<p><?php esc_attr_e( 'Do you want to continue?', 'mailin' ); ?></p>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" id="sib_modal_ok"><?php esc_attr_e( 'Ok', 'mailin' ); ?></button>
							<button type="button" class="btn btn-default" id="sib_modal_cancel"><?php esc_attr_e( 'Cancel', 'mailin' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		/** Generate forms page */
		function generate_forms_page() {
			?>
			<div id="main-content" class="sib-content">
				<div class="card sib-small-content">
				<div class="card-header"><strong><?php esc_attr_e( 'Forms', 'mailin' ); ?></strong></div>

					<form method="post" class="sib-forms-wrapper" style="padding:20px;min-height: 500px;">
						<i style="font-size: 13px;"><?php esc_attr_e( "Note: Forms created in Sendinblue plugin for WordPress won't be displayed in Forms section in Sendinblue application", 'mailin' ); ?></i>
			<?php
			$this->forms->display();
			?>
					</form>
				</div>
			</div>
			<?php
		}
		/** Generate form edit page */
		function generate_form_edit() {
			$is_activated_smtp = SIB_API_Manager::get_smtp_status() == 'disabled' ? 0 : 1;
			$formData = SIB_Forms::getForm( $this->formID );
			$invisibleCaptcha = '1';
			if ( ! empty( $formData ) ) {
				if ( isset( $_GET['action'] ) && 'duplicate' === sanitize_text_field($_GET['action']) ) {
					$this->formID = 'new';
					$formData['title'] = '';
				}
				if ( 'new' === $this->formID && isset( $_GET['pid'] ) ) {
					$parent_formData = SIB_Forms::getForm( sanitize_text_field( $_GET['pid'] ) );
					$formData['title'] = $parent_formData['title'];
				}
				if ( ! isset( $formData['gCaptcha'] ) ) {
					$gCaptcha = '0';
				}
				else {
					if( '0' == $formData['gCaptcha'] ) {
						$gCaptcha = '0';
					}
					else {
						$gCaptcha = '1';
					}
					if ( '3' == $formData['gCaptcha'] ) {
						$invisibleCaptcha = '0';
					}
					else {
						$invisibleCaptcha = '1';
					}
				}
				if ( ! isset( $formData['termAccept'] ) ) {
					$formData['termAccept'] = '0';
				}

				?>
				<div id="main-content" class="sib-content">
					<form action="admin.php" class="" method="post" role="form">
						<input type="hidden" name="action" value="sib_setting_subscription">
						<input type="hidden" name="sib_form_id" value="<?php echo esc_attr( $this->formID ) ; ?>">
						<input type="hidden" id="is_smtp_activated" value="<?php echo esc_attr( $is_activated_smtp ) ; ?>">
						<?php
						if ( isset( $_GET['pid'] ) ) {
							?>
							<input type="hidden" name="pid" value="<?php echo esc_attr( $_GET['pid'] ); ?>">
							<?php 
							$lang = isset( $_GET['lang'] ) ? sanitize_text_field( $_GET['lang'] ) : '';
							if ( $lang ) { ?>
								<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">
							<?php
                            }
						}
						?>
						<?php wp_nonce_field( 'sib_setting_subscription' ); ?>
						<!-- Subscription form -->
						<div class="card sib-small-content">
							<div class="card-header">
								<strong><?php esc_attr_e( 'Subscription form', 'mailin' ); ?></strong>&nbsp;<i
									id="sib_setting_form_spin" class="fa fa-spinner fa-spin fa-fw fa-lg fa-2x"></i>
							</div>
							<div id="sib_setting_form_body" class="card-body">
								<div class="row <!--small-content-->">
									<div style="margin: 12px 0 34px 0px;">
										<b><?php esc_attr_e( 'Form Name : ', 'mailin' ); ?></b>&nbsp; <input type="text"
																									 name="sib_form_name"
																									 value="<?php echo esc_attr( $formData['title'] ); ?>"
																									 style="width: 60%;"
																									 required="required"/>
									</div>
									<div class="col-md-6">

										<?php
										if ( function_exists( 'wp_editor' ) ) {
											// phpcs:ignore
											wp_editor(
												esc_html(stripcslashes($formData['html'])), 'sibformmarkup', array(
													'tinymce' => false,
													'media_buttons' => true,
													'textarea_name' => 'sib_form_html',
													'textarea_rows' => 15,
												)
											);
										} else {
											?>
											<textarea class="widefat" cols="160" rows="20" id="sibformmarkup"
														name="sib_form_html"><?php 
														// phpcs:ignore
														echo esc_html( $formData['html'] ); ?></textarea>
																						<?php
										}
										?>
										<br>

										<p>
											<?php
											esc_attr_e( 'Use the shortcode', 'mailin' );
											if ( isset( $_GET['pid'] ) ) {
												$id = sanitize_text_field( $_GET['pid'] );
											} else {
												$id = 'new' !== $this->formID ? $this->formID : '';
											}
											?>
											<i style="background-color: #eee;padding: 3px;">[sibwp_form
												id=<?php echo esc_attr( $id ); ?>]</i>
											<?php
											esc_attr_e( 'inside a post, page or text widget to display your sign-up form.', 'mailin' );
											?>
											<b><?php esc_attr_e( 'Do not copy and paste the above form mark up, that will not work', 'mailin' ); ?></b>
										</p>
										<div id="sib-field-form" class="card form-field"
											 style="padding-bottom: 20px;">

											<div class="small-content2"
												 style="margin-top: 15px;">
												<b><?php esc_attr_e( 'Add a new Field', 'mailin' ); ?></b>&nbsp;
												<?php SIB_Page_Home::get_narration_script( __( 'Add a New Field', 'mailin' ), __( 'Choose an attribute and add it to the subscription form of your Website', 'mailin' ) ); ?>
											</div>
											<div id="sib_sel_attribute_area" class="small-content2"
												 style="margin-top: 20px;">
											</div>
											<div id="sib-field-content">
												<div style="margin-top: 30px;">
													<div class="sib-attr-normal sib-attr-category small-content2"
														 style="margin-top: 10px;" id="sib_field_label_area">
														<?php esc_attr_e( 'Label', 'mailin' ); ?>
														<small>(<?php esc_attr_e( 'Optional', 'mailin' ); ?>)</small>
														<input type="text" class="col-md-12 sib_field_changes" id="sib_field_label">
													</div>
													<div class="sib-attr-normal small-content2"
														 style="margin-top: 10px;" id="sib_field_placeholder_area">
														<span><?php esc_attr_e( 'Place holder', 'mailin' ); ?>
															<small>(<?php esc_attr_e( 'Optional', 'mailin' ); ?>)
															</small> </span>
														<input type="text" class="col-md-12 sib_field_changes" id="sib_field_placeholder">
													</div>
													<div class="sib-attr-normal small-content2"
														 style="margin-top: 10px;" id="sib_field_initial_area">
														<span><?php esc_attr_e( 'Initial value', 'mailin' ); ?>
															<small>(<?php esc_attr_e( 'Optional', 'mailin' ); ?>)
															</small> </span>
														<input type="text" class="col-md-12 sib_field_changes" id="sib_field_initial">
													</div>
													<div class="sib-attr-other small-content2"
														 style="margin-top: 10px;" id="sib_field_button_text_area">
														<span><?php esc_attr_e( 'Button Text', 'mailin' ); ?></span>
														<input type="text" class="col-md-12 sib_field_changes" id="sib_field_button_text">
													</div>
												</div>
												<div style="margin-top: 20px;">

													<div class="sib-attr-normal sib-attr-category small-content2" style="margin-top: 5px;" id="sib_field_required_area">
														<label style="font-weight: normal;"><input type="checkbox" class="sib_field_changes" id="sib_field_required">&nbsp;&nbsp;<?php esc_attr_e( 'Required field ?', 'mailin' ); ?>
														</label>
													</div>
													<div class="sib-attr-category small-content2"
														 style="margin-top: 5px;" id="sib_field_type_area">
														<label style="font-weight: normal;"><input type="radio" class="sib_field_changes" name="sib_field_type" value="select"
																								   checked>&nbsp;<?php esc_attr_e( 'Drop-down List', 'mailin' ); ?>
														</label>&nbsp;&nbsp;
														<label style="font-weight: normal;"><input type="radio" class="sib_field_changes" name="sib_field_type"
																								   value="radio">&nbsp;<?php esc_attr_e( 'Radio List', 'mailin' ); ?>
														</label>
													</div>
												</div>
												<div class="small-content2" style="margin-top: 20px;"
													 id="sib_field_add_area">
													<button type="button" id="sib_add_to_form_btn"
															class="btn btn-primary sib-add-to-form"><span
															class="sib-large-icon"><</span> <?php esc_attr_e( 'Add to form', 'mailin' ); ?>
													</button>&nbsp;&nbsp;
                                                    <div style="display:none">
                                                        <input id="getDomain" value="<?php
                                                        echo plugins_url('mailin/img/flags/') ?>"></input>
                                                    </div>
													<?php SIB_Page_Home::get_narration_script( __( 'Add to form', 'mailin' ), __( 'Please click where you want to insert the field and click on this button. By default, the new field will be added at top.', 'mailin' ) ); ?>
												</div>
												<div class="small-content2" style="margin-top: 20px;"
													 id="sib_field_html_area">
													<span><?php esc_attr_e( 'Generated HTML', 'mailin' ); ?></span>
													<textarea class="col-md-12" style="height: 140px;"
															  id="sib_field_html"></textarea>
												</div>
											</div>
										</div>
                                        <!---- multi list per interest ----->
                                        <div id="sib-multi-lists" class="card form-field"
                                             style="padding-bottom: 20px;">

                                            <div class="small-content2"
                                                 style="margin-top: 15px">
                                                <b><?php esc_attr_e( 'Add Multi-List Subscription', 'mailin' ); ?></b>&nbsp;
                                                <?php SIB_Page_Home::get_narration_script( __( 'Add Multi-List Subscription', 'mailin' ), __( 'Enable your contacts to subscribe to content based on specific interests or preferences. Create a contact list for each interest and allow them to subscribe using this field', 'mailin' ) ); ?>
                                            </div>
                                            <div id="sib_sel_multi_list_area" class="small-content2"
                                                 style="margin-top: 20px;">
                                                <input type="hidden" id="sib_selected_multi_list_id" value="">
                                                <select data-placeholder="<?php esc_attr_e( 'Please select the lists', 'mailin' ); ?>" id="sib_select_multi_list"
                                                        class="col-md-12 chosen-select" name="multi_list_ids[]" multiple=""
                                                        tabindex="-1"></select>
                                            </div>
                                            <div id="sib_multi_list_field" style="display: none;">
                                                <div style="margin-top: 30px;">
                                                    <div class="sib-attr-normal sib-attr-category small-content2"
                                                         style="margin-top: 10px;" id="sib_multi_field_label_area">
                                                        <?php esc_attr_e( 'Label', 'mailin' ); ?>
                                                        <small>(<?php esc_attr_e( 'Optional', 'mailin' ); ?>)</small>
                                                        <input type="text" class="col-md-12 sib_field_changes" id="sib_multi_field_label">
                                                    </div>
                                                </div>
                                                <div style="margin-top: 20px;">
                                                    <div class="sib-attr-normal sib-attr-category small-content2" style="margin-top: 5px;" id="sib_multi_field_required_area">
                                                        <label style="font-weight: normal;"><input type="checkbox" class="sib_field_changes" id="sib_multi_field_required">&nbsp;&nbsp;<?php esc_attr_e( 'Required field ?', 'mailin' ); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="small-content2" style="margin-top: 20px;"
                                                     id="sib_multi_field_add_area">
                                                    <button type="button" id="sib_multi_lists_add_form_btn"
                                                            class="btn btn-primary sib-add-to-form"><span
                                                                class="sib-large-icon"><</span> <?php esc_attr_e( 'Add to form', 'mailin' ); ?>
                                                    </button>&nbsp;&nbsp;
                                                    <?php SIB_Page_Home::get_narration_script( __( 'Add to form', 'mailin' ), __( 'Please click where you want to insert the field and click on this button. By default, the new field will be added at top.', 'mailin' ) ); ?>
                                                </div>
                                                <div class="small-content2" style="margin-top: 20px;"
                                                     id="sib_field_html_area">
                                                    <span><?php esc_attr_e( 'Generated HTML', 'mailin' ); ?></span>
                                                    <textarea class="col-md-12" style="height: 140px;"
                                                              id="sib_multi_field_html"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="sib-gdpr-block" class="card form-field" style="padding-bottom: 20px;">
                                            <div class="small-content2"
                                                 style="margin-top: 15px;margin-bottom: 15px;">
                                                <b><?php esc_attr_e( 'Compliance Note', 'mailin' ); ?></b>&nbsp;
                                                <?php SIB_Page_Home::get_narration_script( __( 'Add compliance note', 'mailin' ), __( 'Create GDPR-compliant subscription forms for collecting email addresses.', 'mailin' ) ); ?>
                                            </div>
                                            <div class="small-content2" style="margin-top: 0px;">
                                                <input type="radio" name="sib_add_compliant_note" class="sib-add-compliant-note" value="1" ><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'Yes', 'mailin');?></label>
                                                <input type="radio" name="sib_add_compliant_note" class="sib-add-compliant-note" value="0" checked><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'No', 'mailin');?></label>
                                            </div>
                                            <div class="small-content2 sib-gdpr-block-area" style="display: none;">
                                                <textarea id="sib_gdpr_text" class="col-md-12" rows="5"><?php echo trim( $this->defaultComplianceNote ); ?></textarea>
                                                <label id="set_gdpr_default"><?php esc_attr_e('Reset to Default', 'mailin');?>&nbsp;<i class="fa fa-refresh"></i></label>
                                            </div>
                                            <div class="small-content2 sib-gdpr-block-btn" style="display: none;">
                                                <button type="button" id="sib_add_compliance_note"
                                                        class="btn btn-primary sib-add-to-form"><span
                                                            class="sib-large-icon"><</span> <?php esc_attr_e( 'Add to form', 'mailin' ); ?>
                                                </button>&nbsp;&nbsp;
                                                <?php SIB_Page_Home::get_narration_script( __( 'Add to form', 'mailin' ), __( 'Please click where you want to insert the field and click on this button. By default, the new field will be added at top.', 'mailin' ) ); ?>
                                            </div>
                                        </div>

                                        <!---- end block ------>
										<div id="sib_form_captcha" class="card form-field"
											 style="padding-bottom: 20px;">
											<div class="alert alert-danger" style="margin:5px;display: none;"></div>
											<div class="small-content2" style="margin-top: 15px;margin-bottom: 15px;">
												<b><?php esc_attr_e( 'Add Captcha', 'mailin' ); ?></b>&nbsp;
												<?php SIB_Page_Home::get_narration_script( __( 'Add Captcha', 'mailin' ), __( 'We are using Google reCaptcha for this form. To use Google reCaptcha on this form, you should input site key and secret key.' , 'mailin' ) ); ?>
											</div>
											<div class="small-content2" style="margin-top: 0px;">
												<input type="radio" name="sib_add_captcha" class="sib-add-captcha" value="1" <?php checked( $gCaptcha, '1' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'Yes', 'mailin' ); ?></label>
												<input type="radio" name="sib_add_captcha" class="sib-add-captcha" value="0" <?php checked( $gCaptcha, '0' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'No', 'mailin' ); ?></label>
											</div>
											<div class="small-content2 sib-captcha-key" 
											<?php
											if ( '1' !== $gCaptcha ) {
												echo("style='display: none;'");}
											?>
											>
												<i><?php esc_attr_e( 'Site Key', 'mailin' ); ?></i>&nbsp;
												<input type="text" class="col-md-12" id="sib_captcha_site" name="sib_captcha_site" value="<?php
												if ( isset( $formData['gCaptcha_site'] ) && ! empty( $formData['gCaptcha_site'] ) ) {
													echo esc_attr( $formData['gCaptcha_site'] );
												} else {
													echo '';
												}
												?>">
											</div>
											<div class="small-content2 sib-captcha-key"
											<?php
											if ( '1' !== $gCaptcha ) {
												echo("style='display: none;'");}
											?>
											>
												<i><?php esc_attr_e( 'Secret Key', 'mailin' ); ?></i>&nbsp;
												<input type="text" class="col-md-12" id="sib_captcha_secret" name="sib_captcha_secret" value="<?php
												if ( isset( $formData['gCaptcha_secret'] ) && ! empty( $formData['gCaptcha_secret'] ) ) {
													echo esc_attr( $formData['gCaptcha_secret'] );
												} else {
													echo '';
												}
												?>">
											</div>
											<div class="small-content2 sib-captcha-key"
												<?php
												if ( '1' !== $gCaptcha ) {
													echo("style='display: none;'");}
												?>
											>
												<input type="radio" name="sib_recaptcha_type" class="sib-captcha-type" value="0" <?php checked( $invisibleCaptcha, '0' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'Google Captcha', 'mailin');?></label>
												<input type="radio" name="sib_recaptcha_type" class="sib-captcha-type" value="1" <?php checked( $invisibleCaptcha, '1' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'Google Invisible Captcha', 'mailin');?></label>
											</div>
											<div class="small-content2 sib-captcha-key" 
											<?php
											if ( '1' !== $gCaptcha ) {
												echo("style='display: none;'");}
											?>
											>
												<button type="button" id="sib_add_captcha_btn"
														class="btn btn-primary sib-add-to-form"><span
														class="sib-large-icon"><</span> <?php esc_attr_e( 'Add to form', 'mailin' ); ?>
												</button>&nbsp;&nbsp;
												<?php SIB_Page_Home::get_narration_script( __( 'Add Captcha', 'mailin' ), __( 'Please click where you want to insert the field and click on this button. By default, the new field will be added at top.', 'mailin' ) ); ?>
											</div>
											</div>
										<div id="sib_form_terms" class="card form-field"
											 style="padding-bottom: 20px;">
											<div class="alert alert-danger" style="margin:5px;display: none;"></div>
											<!-- for terms -->
											<div class="small-content2" style="margin-top: 15px;margin-bottom: 15px;">
												<b><?php esc_attr_e( 'Add a Term acceptance checkbox', 'mailin' ); ?></b>&nbsp;
												<?php SIB_Page_Home::get_narration_script( __( 'Add a Term acceptance checkbox', 'mailin' ), __( 'If the terms and condition checkbox is added to the form, the field will be mandatory for subscription.' , 'mailin' ) ); ?>
											</div>
											<div class="small-content2" style="margin-top: 0px;">
												<input type="radio" name="sib_add_terms" class="sib-add-terms" value="1" <?php checked( $formData['termAccept'], '1' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'Yes', 'mailin');?></label>
												<input type="radio" name="sib_add_terms" class="sib-add-terms" value="0" <?php checked( $formData['termAccept'], '0' ); ?>><label class="sib-radio-label">&nbsp;<?php esc_attr_e( 'No', 'mailin');?></label>
											</div>
											<div class="small-content2 sib-terms-url" 
											<?php
											if ( '1' !== $formData['termAccept'] ) {
												echo("style='display: none;'");}
											?>
											>
												<i><?php esc_attr_e( 'URL to terms and conditions', 'mailin' ); ?></i>&nbsp;
												<input type="text" class="col-md-12" id="sib_terms_url" name="sib_terms_url" value="<?php
												if ( isset( $formData['termsURL'] ) && ! empty( $formData['termsURL'] ) ) {
													echo esc_attr( $formData['termsURL'] );
												} else {
													echo '';
												}
												?>">
											</div>
											<div class="small-content2 sib-terms-url" 
											<?php
											if ( '1' !== $formData['termAccept'] ) {
												echo("style='display: none;'");}
											?>
											>
												<button type="button" id="sib_add_termsUrl_btn"
														class="btn btn-primary sib-add-to-form"><span
														class="sib-large-icon"><</span> <?php esc_attr_e( 'Add to form', 'mailin' ); ?>
												</button>&nbsp;&nbsp;
												<?php SIB_Page_Home::get_narration_script( __( 'Add Terms URL', 'mailin' ), __( 'Please click where you want to insert the field and click on this button. By default, the new field will be added at top.', 'mailin' ) ); ?>
											</div>

										</div>
										<!-- use css of custom or theme -->
										<div class="card form-field">
											<div class="small-content2" style="margin-top: 15px;margin-bottom: 10px;">
												<b><?php esc_attr_e( 'Form Style', 'mailin' ); ?>&nbsp;</b>
												<?php SIB_Page_Home::get_narration_script( __( 'Form Style', 'mailin' ), __( 'Select the style you favorite. Your custom css will be applied to form only.', 'mailin' ) ); ?>
											</div>
											<div id="sib_form_css_area" class="small-content2" style="margin-bottom: 15px;">
												<label style="font-weight: normal;"><input type="radio" name="sib_css_type" value="1" <?php checked( $formData['dependTheme'], '1' ); ?>>&nbsp;<?php esc_attr_e( 'Current Theme', 'mailin' ); ?>
												</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<label style="font-weight: normal;"><input type="radio" name="sib_css_type" value="0" <?php checked( $formData['dependTheme'], '0' ); ?>>&nbsp;<?php esc_attr_e( 'Custom style', 'mailin' ); ?>
												</label>
												<textarea class="widefat" cols="60" rows="10" id="sibcssmarkup" style="margin-top: 10px; font-size: 13px; display: <?php echo '0' == $formData['dependTheme'] ? 'block' : 'none'; ?>;"
														  name="sib_form_css"><?php echo esc_textarea( $formData['css'] ); ?></textarea>

											</div>

										</div>
									</div>
									<div class="col-md-6">
										<!-- hidden fields for attributes -->
										<input type="hidden" id="sib_hidden_email" data-type="email" data-name="email"
											   data-text="<?php esc_attr_e( 'Email Address', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_submit" data-type="submit"
											   data-name="submit" data-text="<?php esc_attr_e( 'Subscribe', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_message_1"
											   value="<?php esc_attr_e( 'Select Sendinblue Attribute', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_message_2"
											   value="<?php esc_attr_e( 'Sendinblue merge fields : Normal', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_message_3"
											   value="<?php esc_attr_e( 'Sendinblue merge fields : Category', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_message_4"
											   value="<?php esc_attr_e( 'Other', 'mailin' ); ?>">
										<input type="hidden" id="sib_hidden_message_5"
											   value="<?php esc_attr_e( 'Submit Button', 'mailin' ); ?>">

										<!-- preview field -->

										<div class="card form-field">
											<div class="small-content2" style="margin-top: 15px;margin-bottom: 15px;">
												<b><?php esc_attr_e( 'Preview', 'mailin' ); ?>&nbsp;
												<i id="sib-preview-form-refresh" class="fa fa-refresh" style="cursor:pointer;font-weight: bold;" aria-hidden="true"></i>
												</b>
											</div>
											<iframe id="sib-preview-form"
													src="<?php echo esc_url( site_url() . '/?sib_form=' . esc_attr( $this->formID ) ); ?>"
													width="300px" height="428"></iframe>
										</div>
									</div>
								</div>
								<div class="sib-small-content" style="margin-top: 30px;">
									<div class="col-md-3">
										<button class="btn btn-primary"><?php esc_attr_e( 'Save', 'mailin' ); ?></button>
									</div>
								</div>
							</div>
						</div> <!-- End Subscription form-->

						<!-- Sign up Process -->

						<div class="card sib-small-content">

							<!-- Adding security through hidden referrer field -->
							<div class="card-header">
								<strong><?php esc_attr_e( 'Sign up process', 'mailin' ); ?></strong>&nbsp;<i
									id="sib_setting_signup_spin" class="fa fa-spinner fa-spin fa-fw fa-lg fa-2x"></i>
							</div>
							<div id="sib_setting_signup_body" class="card-body">
								<div id="sib_form_alert_message" class="alert alert-danger alert-dismissable fade in"
									 role="alert" style="display: none;">
									<span id="sib_disclaim_smtp"
										  style="display: none;"><?php _e( 'Confirmation emails will be sent through your own email server, but you have no guarantees on their deliverability. <br/> <a href="https://app-smtp.sendinblue.com/" target="_blank">Click here</a> to send your emails through Sendinblue in order to improve your deliverability and get statistics', 'mailin' ); ?></span>
									<span id="sib_disclaim_do_template"
										  style="display: none;"><?php _e( 'The template you selected does not include a link [DOUBLEOPTIN] to allow subscribers to confirm their subscription. <br/> Please edit the template to include a link with [DOUBLEOPTIN] as URL.', 'mailin' ); ?></span>
                                    <span id="sib_disclaim_confirm_template"
                                          style="display: none;"><?php _e( 'You cannot select a template with the tag [DOUBLEOPTIN]', 'mailin' ); ?></span>
								</div>

								<!-- Linked List -->
								<div class="row sib-small-content">
									<span class="col-md-3">
										<?php esc_attr_e( 'Linked List', 'mailin' ); ?>&nbsp;
										<?php SIB_Page_Home::get_narration_script( __( 'Linked List', 'mailin' ), __( 'Select the list where you want to add your new subscribers', 'mailin' ) ); ?>
									</span>
									<div id="sib_select_list_area" class="col-md-4">

										<input type="hidden" id="sib_selected_list_id" value="">
                                        <select data-placeholder="Please select the list" id="sib_select_list"
                                                class="col-md-12 chosen-select" name="list_id[]" multiple=""
                                                tabindex="-1"></select>
									</div>
									<div class="col-md-5">
										<small
											style="font-style: italic;"><?php esc_attr_e( 'You can use Marketing Automation to create specific workflow when a user is added to the list.', 'mailin' ); ?></small>
									</div>

								</div>
								<!-- confirmation email -->
								<div class="row small-content">
									<span class="col-md-3"><?php esc_attr_e( 'Send a confirmation email', 'mailin' ); ?><?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Confirmation message', 'mailin' ), __( 'You can choose to send a confirmation email. You will be able to set up the template that will be sent to your new suscribers', 'mailin' ) ) ); ?></span>

									<div class="col-md-4">
										<label class="col-md-6" style="font-weight: normal;"><input type="radio"
																									id="is_confirm_email_yes"
																									name="is_confirm_email"
																									value="1" <?php checked( $formData['isOpt'], '1' ); ?>>&nbsp;<?php esc_attr_e( 'Yes', 'mailin' ); ?>
										</label>
										<label class="col-md-5" style="font-weight: normal;"><input type="radio"
																									id="is_confirm_email_no"
																									name="is_confirm_email"
																									value="0" <?php checked( $formData['isOpt'], '0' ); ?>>&nbsp;<?php esc_attr_e( 'No', 'mailin' ); ?>
										</label>
									</div>
									<div class="col-md-5">
										<small
											style="font-style: italic;"><?php esc_attr_e( 'Select "Yes" if you want your subscribers to receive a confirmation email', 'mailin' ); ?></small>
									</div>
								</div>
								<!-- select template id for confirmation email -->
								<div class="row" id="sib_confirm_template_area">
									<input type="hidden" id="sib_selected_template_id"
										   value="<?php echo esc_attr( $formData['templateID'] ); ?>">
									<input type="hidden" id="sib_default_template_name"
										   value="<?php esc_attr_e( 'Default', 'mailin' ); ?>">

									<div class="col-md-3" id="sib_template_id_area">
									</div>
									<div class="col-md-4">
										<a href="https://my.sendinblue.com/camp/lists/template" class="col-md-12"
										   target="_blank"><i
												class="fa fa-angle-right"></i> <?php esc_attr_e( 'Set up my templates', 'mailin' ); ?>
										</a>
									</div>
								</div>
								<!-- double optin confirmation email -->
								<div class="row sib-small-content mt-3">
									<span
										class="col-md-3"><?php esc_attr_e( 'Double Opt-In', 'mailin' ); ?><?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Double Opt-In', 'mailin' ), __( 'Your subscribers will receive an email inviting them to confirm their subscription. Be careful, your subscribers are not saved in your list before confirming their subscription.', 'mailin' ) ) ); ?></span>

									<div class="col-md-4">
										<label class="col-md-6" style="font-weight: normal;"><input type="radio"
																									id="is_double_optin_yes"
																									name="is_double_optin"
																									value="1" <?php checked( $formData['isDopt'], '1' ); ?>>&nbsp;<?php esc_attr_e( 'Yes', 'mailin' ); ?>
										</label>
										<label class="col-md-5" style="font-weight: normal;"><input type="radio"
																									id="is_double_optin_no"
																									name="is_double_optin"
																									value="0" <?php checked( $formData['isDopt'], '0' ); ?>>&nbsp;<?php esc_attr_e( 'No', 'mailin' ); ?>
										</label>
									</div>
									<div class="col-md-5">
										<small
											style="font-style: italic;"><?php esc_attr_e( 'Select "Yes" if you want your subscribers to confirm their email address', 'mailin' ); ?></small>
									</div>
								</div>
								<!-- select template id for double optin confirmation email -->
								<div class="row" id="sib_doubleoptin_template_area">
									<input type="hidden" id="sib_selected_do_template_id" value="<?php echo esc_attr( $formData['templateID'] ); ?>">
									<div class="col-md-3" id="sib_doubleoptin_template_id_area">
									</div>
									<div class="col-md-4">
										<a href="https://my.sendinblue.com/camp/lists/template"
										   class="col-md-12" target="_blank"><i
												class="fa fa-angle-right"></i> <?php esc_attr_e( 'Set up my templates', 'mailin' ); ?>
										</a>
									</div>
								</div>
								<div class="row sib-small-content mt-3" id="sib_double_redirect_area">
									<span class="col-md-3"><?php esc_attr_e( 'Redirect to this URL after clicking in the email', 'mailin' ); ?></span>

									<div class="col-md-8">
										<input type="url" class="col-md-11" name="redirect_url" value="<?php echo esc_attr( $formData['redirectInEmail'] ); ?>">
									</div>
								</div>
                                <div class="row sib-small-content mt-3" id="sib_final_confirm_template_area">
									<span class="col-md-3"><?php esc_attr_e( 'Select final confirmation email template', 'mailin' ); ?><?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Final confirmation', 'mailin' ), __( 'This is the final confirmation email your contacts will receive once they click on the double opt-in confirmation link. You can select one of the default templates we have created for you, e.g. \'Default template - Final confirmation\'.
For your information, you cannot select a template with the tag [DOUBLEOPTIN].', 'mailin' ) ) ); ?></span>
                                    <div class="row col-md-8">
                                        <input type="hidden" id="sib_selected_confirm_template_id" value="<?php echo esc_attr( $formData['confirmID'] );?>">
                                        <div class="col-md-5" id="sib_final_confirm_template_id_area">
                                        </div>
                                        <div class="col-md-4">
                                            <a href="https://my.sendinblue.com/camp/lists/template"
                                               class="col-md-12" target="_blank"><i
                                                        class="fa fa-angle-right"></i> <?php esc_attr_e( 'Set up my templates', 'mailin' ); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>

								<div class="row sib-small-content mt-3">
									<span
										class="col-md-3"><?php esc_attr_e( 'Redirect to this URL after subscription', 'mailin' ); ?></span>

									<div class="col-md-4">
										<label class="col-md-6" style="font-weight: normal;"><input type="radio"
																									id="is_redirect_url_click_yes"
																									name="is_redirect_url_click"
																									value="1" checked>&nbsp;<?php esc_attr_e( 'Yes', 'mailin' ); ?>
										</label>
										<label class="col-md-5" style="font-weight: normal;"><input type="radio"
																									id="is_redirect_url_click_no"
																									name="is_redirect_url_click"
																									value="0" <?php checked( $formData['redirectInForm'], '' ); ?>>&nbsp;<?php esc_attr_e( 'No', 'mailin' ); ?>
										</label>

									</div>
									<div class="col-md-5">
										<small
											style="font-style: italic;"><?php esc_attr_e( 'Select "Yes" if you want to redirect your subscribers to a specific page after they fullfill the form', 'mailin' ); ?></small>
									</div>
								</div>
								<div class="row" style="margin-top: 10px;
								<?php
								if ( '' == $formData['redirectInForm'] ) {
									echo 'display:none;';
								}
								?>
								" id="sib_subscrition_redirect_area">
									<span class="col-md-3"></span>

									<div class="col-md-8">
										<input type="url" class="col-md-11" name="redirect_url_click"
											   value="<?php echo esc_attr( $formData['redirectInForm'] ); ?>">
									</div>
								</div>

								<div class="row sib-small-content" style="margin-top: 30px;">
									<div class="col-md-3">
										<button class="btn btn-primary"><?php esc_attr_e( 'Save', 'mailin' ); ?></button>
									</div>
								</div>

							</div>
						</div><!-- End Sign up process form-->

						<!-- Confirmation message form -->
						<div class="card sib-small-content">
							<div class="card-header">
								<strong><?php esc_attr_e( 'Confirmation message', 'mailin' ); ?></strong>
							</div>
							<div class="card-body">
								<div class="row sib-small-content mt-3">
									<!-- <span class="col-md-3"></span> -->
									<label for="inputEmail3" class="col-md-3"><?php esc_attr_e( 'Success message', 'mailin' ); ?></label>
									<div class="col-md-8">
										<input type="text" class="col-md-11" name="alert_success_message"
											   value="<?php echo esc_attr( $formData['successMsg'] ); ?>" required>&nbsp;
										<?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Success message', 'mailin' ), __( 'Set up the success message that will appear when one of your visitors surccessfully signs up', 'mailin' ) ) ); ?>
									</div>
								</div>
								<div class="row sib-small-content mt-3">
									<span class="col-md-3"><?php esc_attr_e( 'General error message', 'mailin' ); ?></span>

									<div class="col-md-8">
										<input type="text" class="col-md-11" name="alert_error_message"
											   value="<?php echo esc_attr( $formData['errorMsg'] ); ?>" required>&nbsp;
										<?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'General message error', 'mailin' ), __( 'Set up the message that will appear when an error occurs during the subscritpion process', 'mailin' ) ) ); ?>
									</div>
								</div>
								<!--
								<div class="row sib-small-content mt-3">
									<span class="col-md-3"><?php esc_attr_e( 'Existing subscribers', 'mailin' ); ?></span>

									<div class="col-md-8">
										<input type="text" class="col-md-11" name="alert_exist_subscriber"
											   value="<?php echo esc_attr( $formData['existMsg'] ); ?>" required>&nbsp;
										<?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Existing Suscribers', 'mailin' ), __( 'Set up the message that will appear when a suscriber is already in your database', 'mailin' ) ) ); ?>
									</div>
								</div>
								-->
								<div class="row sib-small-content mt-3">
									<span class="col-md-3"><?php esc_attr_e( 'Invalid email address', 'mailin' ); ?></span>

									<div class="col-md-8">
										<input type="text" class="col-md-11" name="alert_invalid_email"
											   value="<?php echo esc_attr( $formData['invalidMsg'] ); ?>" required>&nbsp;
										<?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Invalid email address', 'mailin' ), __( 'Set up the message that will appear when the email address used to sign up is not valid', 'mailin' ) ) ); ?>
									</div>
								</div>
                                <div class="row sib-small-content mt-3">
                                    <span class="col-md-3"><?php esc_attr_e( 'Required Field', 'mailin' ); ?></span>

                                    <div class="col-md-8">
                                        <input type="text" class="col-md-11" name="alert_required_message"
                                               value="<?php echo esc_attr( $formData['requiredMsg'] ); ?>" required>&nbsp;
                                        <?php echo esc_html( SIB_Page_Home::get_narration_script( __( 'Required Field', 'mailin' ), __( 'Set up the message that will appear when the required field is empty', 'mailin' ) ) ); ?>
                                    </div>
                                </div>
								<div class="row sib-small-content" style="margin-top: 30px;">
									<div class="col-md-3">
										<button class="btn btn-primary"><?php esc_attr_e( 'Save', 'mailin' ); ?></button>
									</div>
								</div>
							</div>
						</div> <!-- End Confirmation message form-->
					</form>
				</div>
				<script>
					jQuery(document).ready(function () {
						jQuery('#sib_add_to_form_btn').click(function () {
							//var field_html = jQuery('#sib_field_html').html();

							// tinyMCE.activeEditor.selection.setContent(field_html);

							return false;
						});
					});
				</script>
				<?php
			} else {
				// If empty?
				?>
				<div id="main-content" class="sib-content">
					<div class="card sib-small-content">
						<div class="card-header">
							<strong><?php esc_attr_e( 'Subscription form', 'mailin' ); ?></strong>
						</div>
						<div style="padding: 24px 32px; margin-bottom: 12px;">
							<?php esc_attr_e( 'Sorry, you selected invalid form ID. Please check again if the ID is right', 'mailin' ); ?>
						</div>
					</div>
				</div>
				<?php
			}
		}

		/** Generate welcome page */
		function generate_welcome_page() {
		?>
			<div id="main-content" class="row">
				<img class="small-content" src="<?php echo esc_url( SIB_Manager::$plugin_url . '/img/background/setting.png' ); ?>" style="width: 100%;">
			</div>
		<?php
			SIB_Page_Home::print_disable_popup();
		}

		/** Save subscription form setting */
		public static function save_setting_subscription() {
			// Check user role.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Not allowed' );
			}

			// Check secret through hidden referrer field.
			check_admin_referer( 'sib_setting_subscription' );

			//Handling of backslash added by WP because magic quotes are enabled by default
			array_walk_recursive( $_POST, function(&$value) {
				$value = stripslashes($value);
			});
			
			// Subscription form.
			$formID = isset( $_POST['sib_form_id'] ) ? sanitize_text_field( $_POST['sib_form_id'] ) : '';
			$form_name = isset( $_POST['sib_form_name'] ) ? sanitize_text_field( $_POST['sib_form_name'] ) : '';
			// phpcs:disable
			$form_html = isset( $_POST['sib_form_html'] ) ? wp_kses($_POST['sib_form_html'], SIB_Manager::wordpress_allowed_attributes()) : '';
			$list_ids = '';

			if (!empty($_POST['list_id']) && is_array($_POST['list_id'])) {
				$list_ids = array_filter($_POST['list_id'], 'intval');
				$list_ids = maybe_serialize($list_ids);
			}

			// phpcs:enable
			$dependTheme = isset( $_POST['sib_css_type'] ) ? sanitize_text_field( $_POST['sib_css_type'] ) : '';
			$customCss = isset( $_POST['sib_form_css'] ) ? sanitize_text_field( $_POST['sib_form_css'] ) : '';
			$gCaptcha = isset( $_POST['sib_add_captcha'] ) ? sanitize_text_field( $_POST['sib_add_captcha'] ) : '0';
			$gCaptchaSecret = isset( $_POST['sib_captcha_secret'] ) ? sanitize_text_field( $_POST['sib_captcha_secret'] ) : '';
			$gCaptchaSite = isset( $_POST['sib_captcha_site'] ) ? sanitize_text_field( $_POST['sib_captcha_site'] ) : '';
			$termAccept = isset( $_POST['sib_add_terms'] ) ? sanitize_text_field( $_POST['sib_add_terms'] ) : '0';
			$termURL = isset( $_POST['sib_terms_url'] ) ? sanitize_text_field( $_POST['sib_terms_url'] ) : '';
			$gCaptchaType = isset( $_POST['sib_recaptcha_type'] ) ? sanitize_text_field( $_POST['sib_recaptcha_type'] ) : '0';
			if ( $gCaptcha != '0' ) {
				if ( $gCaptchaType == '0' ) {
					$gCaptcha = '3';  // google recaptcha.
				}
				elseif ( $gCaptchaType == '1' ) {
					$gCaptcha = '2';   // google invisible recaptcha.
				}
			}
			// for wpml plugins.
			$pid = isset( $_POST['pid'] ) ? sanitize_text_field( $_POST['pid'] ) : '';
			$lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : '';
			// sign up process.
			$templateID = '-1';
			$confirmID = '-1';
			$redirectInForm = '';

			$isOpt = isset( $_POST['is_confirm_email'] ) ? sanitize_text_field( $_POST['is_confirm_email'] ) : false;
			if ( $isOpt ) {
				$templateID = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '-1';
			}
			$isDopt = isset( $_POST['is_double_optin'] ) ? sanitize_text_field( $_POST['is_double_optin'] ) : false;
			if ( $isDopt ) {
				$templateID = isset( $_POST['doubleoptin_template_id'] ) ? sanitize_text_field( $_POST['doubleoptin_template_id'] ) : '-1';
                $confirmID = isset( $_POST['confirm_template_id'] ) ? sanitize_text_field( $_POST['confirm_template_id'] ) : '-1';
			}
			$redirectInEmail = isset( $_POST['redirect_url'] ) ? sanitize_text_field( $_POST['redirect_url'] ) : '';
			$isRedirectInForm = isset( $_POST['is_redirect_url_click'] ) ? sanitize_text_field( $_POST['is_redirect_url_click'] ) : false;
			if ( $isRedirectInForm ) {
				$redirectInForm = isset( $_POST['redirect_url_click'] ) ? sanitize_text_field( $_POST['redirect_url_click'] ) : '';
			}

			// get available attributes list.
			$attributes = SIB_API_Manager::get_attributes();
			$attributes = array_merge( $attributes['attributes']['normal_attributes'],$attributes['attributes']['category_attributes'] );
			$available_attrs = array( 'email' );
			if ( isset( $attributes ) && is_array( $attributes ) ) {
				foreach ( $attributes as $attribute ) {
					$pos = strpos( $form_html, 'sib-' . $attribute['name'] . '-area' );
					if ( false !== $pos ) {
						$available_attrs[] = $attribute['name'];
					}
				}
			}
			$successMsg = isset( $_POST['alert_success_message'] ) ? sanitize_text_field( esc_attr ($_POST['alert_success_message'] ) ) : '';
			$errorMsg = isset( $_POST['alert_error_message'] ) ? sanitize_text_field( esc_attr( $_POST['alert_error_message'] ) ) : '';
			$existMsg = isset( $_POST['alert_exist_subscriber'] ) ? sanitize_text_field( esc_attr( $_POST['alert_exist_subscriber'] ) ) : '';
			$invalidMsg = isset( $_POST['alert_invalid_email'] ) ? sanitize_text_field( esc_attr( $_POST['alert_invalid_email'] ) ) : '';
			$requiredMsg = isset( $_POST['alert_required_message']) ? sanitize_text_field( esc_attr($_POST['alert_required_message'])) : '';
			$formData = array(
				'title' => $form_name,
				'html' => $form_html,
				'css' => $customCss,
				'listID' => $list_ids,
				'dependTheme' => $dependTheme,
				'isOpt' => $isOpt,
				'isDopt' => $isDopt,
				'templateID' => $templateID,
				'confirmID' => $confirmID,
				'redirectInEmail' => $redirectInEmail,
				'redirectInForm' => $redirectInForm,
				'successMsg' => $successMsg,
				'errorMsg' => $errorMsg,
				'existMsg' => $existMsg,
				'invalidMsg' => $invalidMsg,
				'requiredMsg' => $requiredMsg,
				'attributes' => implode( ',', $available_attrs ),
				'gcaptcha'   => $gCaptcha,
				'gcaptcha_secret' => $gCaptchaSecret,
				'gcaptcha_site'   => $gCaptchaSite,
				'termAccept'      => $termAccept,
				'termsURL'        => $termURL,
			);
			if ( 'new' === $formID ) {
				$formID = SIB_Forms::addForm( $formData );
				if ( '' !== $pid ) {
					$transID = SIB_Forms_Lang::add_form_ID( $formID, $pid, $lang );
				}
			} else {
				SIB_Forms::updateForm( $formID, $formData );
			}
			if ( '' !== $pid ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => self::PAGE_ID,
							'action' => 'edit',
							'id' => $formID,
							'pid' => $pid,
							'lang' => $lang,
						), admin_url( 'admin.php' )
					)
				);
				exit();
			} else {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => self::PAGE_ID,
							'action' => 'edit',
							'id' => $formID,
						), admin_url( 'admin.php' )
					)
				);
				exit();
			}
		}

		/** Ajax process when change template id */
		public static function ajax_change_template() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
			$mailin = new SendinblueApiClient( );
			$data = array(
				'id' => $template_id,
			);
			$response = $mailin->getEmailTemplate( $data["id"] );

			$ret_email = '-1';
			if ( $mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK) {
				$from_email = $response[0]['sender']['email'];
				if ( '[DEFAULT_FROM_EMAIL]' == $from_email ) {
					$ret_email = '-1';
				} else {
					$ret_email = $from_email;
				}
			}
			wp_send_json( $ret_email );
		}

		/**
		 * Ajax module to get all lists.
		 */
		public static function ajax_get_lists() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$lists = SIB_API_Manager::get_lists();
			$frmID = isset( $_POST['frmid'] ) ? sanitize_text_field( $_POST['frmid'] ) : '';
			$formData = SIB_Forms::getForm( $frmID );
			$result = array(
				'lists' => $lists,
				'selected' => $formData['listID'],
			);
			wp_send_json( $result );
		}

		/**
		 * Ajax module to get all templates.
		 */
		public static function ajax_get_templates() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$templates = SIB_API_Manager::get_templates();
			$result = array(
				'templates' => $templates,
			);
			wp_send_json( $result );
		}

		/**
		 * Ajax module to get all attributes.
		 */
		public static function ajax_get_attributes() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$attrs = SIB_API_Manager::get_attributes();
			$result = array(
				'attrs' => $attrs,
			);
			wp_send_json( $result );
		}

		/**
		 * Ajax module to update form html for preview
		 */
		public static function ajax_update_html() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$gCaptchaType = isset( $_POST['gCaptchaType']) ? sanitize_text_field($_POST['gCaptchaType']) : '1';
			$gCaptcha = isset( $_POST['gCaptcha'] ) ? sanitize_text_field($_POST['gCaptcha']) : '0';
			if ( $gCaptcha != '0' ) {
				if( $gCaptchaType == '1' ) {
					$gCaptcha = '2';
				}
				elseif ( $gCaptchaType == '0' ) {
					$gCaptcha = '3';
				}
			}
			$formData = array(
				'html' => isset( $_POST['frmData'] ) ? wp_kses($_POST['frmData'], SIB_Manager::wordpress_allowed_attributes()) : '',// phpcs:ignore
				'css' => isset( $_POST['frmCss'] ) ? sanitize_text_field($_POST['frmCss']) : '',
				'dependTheme' => isset( $_POST['isDepend'] ) ? sanitize_text_field($_POST['isDepend']) : '',
				'gCaptcha' => $gCaptcha,
				'gCaptcha_site' => isset( $_POST['gCaptchaSite'] ) ? sanitize_text_field($_POST['gCaptchaSite']) : ''
			);

			update_option( SIB_Manager::PREVIEW_OPTION_NAME, $formData );
			die;
		}

		/**
		 * Ajax module to copy content from origin form for translation
		 */
		public static function ajax_copy_origin_form() {
			check_ajax_referer( 'ajax_sib_admin_nonce', 'security' );
			$pID = isset( $_POST['pid'] ) ? sanitize_text_field( $_POST['pid'] ) : 1;
			$formData = SIB_Forms::getForm( $pID );
			// phpcs:ignore
			$html = $formData['html'];

			wp_send_json( $html );
		}
	}
}
