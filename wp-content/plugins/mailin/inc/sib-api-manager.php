<?php
/**
 * Manage Sendinblue API
 *
 * Use wp API transient to reduce loading time of API call
 *
 * @package SIB_API_Manager
 */

if ( ! class_exists( 'SIB_API_Manager' ) ) {
	/**
	 * Class SIB_API_Manager.
	 * Main API class for sendinblue module.
	 */
	class SIB_API_Manager {

		/** Transient delay time */
		const DELAYTIME = 900;
		/** Constant for Plugin name */
		const PLUGIN_NAME = 'wordpress';

		/**
		 * SIB_API_Manager constructor.
		 */
		function __construct() {

		}

		/** Get account info */
		public static function get_account_info() {
			// get account's info.
			$account_info = get_transient( 'sib_credit_' . md5( SIB_Manager::$access_key ) );
			if ( false === $account_info || false == $account_info ) {
				$client = new SendinblueApiClient();
                $account = $client->getAccount();
				if ($client->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK && !empty($account['email'])) {
                    $account_email = $account['email'];

					$account_info = array(
						'account_email' => $account_email,
						'account_user_name' => $account['firstName'] . ' ' . $account['lastName'],
						'account_data' => $account['plan'],
					);
                    set_transient( 'sib_credit_' . md5( SIB_Manager::$access_key ), $account_info, self::DELAYTIME );
				} elseif ($client->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_UNAUTHORIZED) {
				    delete_option(SIB_Manager::API_KEY_V3_OPTION_NAME);
                }
			}
			return $account_info;
		}

		/** Get smtp status */
		public static function get_smtp_status() {
			$status = get_transient( 'sib_smtp_status_' . md5( SIB_Manager::$access_key ) );
			if ( false === $status || false == $status ) {
                $client = new SendinblueApiClient();
                $account = $client->getAccount();
				$status = 'disabled';
				if ($client->getLastResponseCode() == 200) {
					$status = $account['relay']['enabled'] ? 'enabled' : 'disabled';
					set_transient( 'sib_smtp_status_' . md5( SIB_Manager::$access_key ), $status, self::DELAYTIME );

					// get Marketing Automation API key.
					if ( isset( $account['marketingAutomation']['enabled'] ) && true == $account['marketingAutomation']['enabled'] ) {
						$ma_key = $account['marketingAutomation']['key'];
					} else {
						$ma_key = '';
					}
					$general_settings = get_option( SIB_Manager::MAIN_OPTION_NAME, array() );
					$general_settings['ma_key'] = $ma_key;
					update_option( SIB_Manager::MAIN_OPTION_NAME, $general_settings );
				}
			}
			return $status;
		}

		/** Get all attributes */
		public static function get_attributes() {
			// get attributes.
			$attrs = get_transient( 'sib_attributes_' . md5( SIB_Manager::$access_key ) );

			if ( false === $attrs || false == $attrs ) {
				$mailin = new SendinblueApiClient();
				$response = $mailin->getAttributes();
				$attributes = $response['attributes'];
				$attrs = array(
					'attributes' => array(
						'normal_attributes' => array(),
						'category_attributes' => array(),
					)
				);

				if (!empty($attributes) && count( $attributes ) > 0 ) {
					foreach ($attributes as $key => $value) {
						if ($value["category"] == "normal") {
							$attrs['attributes']['normal_attributes'][] = $value;
						}
						elseif ($value["category"] == "category") {
							$value["type"] = "category";
							$attrs['attributes']['category_attributes'][] = $value;
						}

					}
				}

				set_transient( 'sib_attributes_' . md5( SIB_Manager::$access_key ), $attrs, self::DELAYTIME );
			}

			return $attrs;

		}

		/** Get all smtp templates */
		public static function get_templates() {

			// get templates.
			$templates = get_transient( 'sib_template_' . md5( SIB_Manager::$access_key ) );

			if ( false === $templates || false == $templates ) {
				$mailin        = new SendinblueApiClient();
				$templates     = $mailin->getAllEmailTemplates();
				$template_data = array();

				if ( $mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK ) {

					foreach ( $templates['templates'] as $template ) {
						$is_dopt = 0;
						if ( strpos( $template['htmlContent'], 'DOUBLEOPTIN' ) != false  || strpos( $template['htmlContent'], 'doubleoptin' ) != false) {
							$is_dopt = 1;
						}
						$template_data[] = array(
							'id' => $template['id'],
							'name' => $template['name'],
							'is_dopt' => $is_dopt,
						);

					}
				}
				$templates = $template_data;
				if ( count( $templates ) > 0 ) {
					set_transient( 'sib_template_' . md5( SIB_Manager::$access_key ), $templates, self::DELAYTIME );
				}
			}

			return $templates;
		}

		/** Get default list id after install */
		public static function get_default_list_id() {
			$lists = self::get_lists();
			return strval( $lists[0]['id'] );
		}

		/** Get all lists */
		public static function get_lists() {
			// get lists.
			$lists = get_transient( 'sib_list_' . md5( SIB_Manager::$access_key ) );
			if ( false === $lists || false == $lists ) {
				
				$mailin = new SendinblueApiClient();
				$lists = array();
				$list_data = $mailin->getAllLists();

				if (!empty($list_data['lists'])) {
                    foreach ( $list_data['lists'] as $value ) {
                        if ( 'Temp - DOUBLE OPTIN' == $value['name'] ) {
                            continue;
                        }
                        $lists[] = array(
                            'id' => $value['id'],
                            'name' => $value['name'],
                        );
                    }
                }

				if ( count( $lists ) > 0 ) {
					set_transient( 'sib_list_' . md5( SIB_Manager::$access_key ), $lists, self::DELAYTIME );
				}
			}
			return $lists;
		}

		/** Get all sender of sendinblue */
		public static function get_sender_lists() {
			$senders = get_transient( 'sib_senders_' . md5( SIB_Manager::$access_key ) );
			if ( false === $senders || false == $senders ) {
				$mailin = new SendinblueApiClient();
				$response = $mailin->getSenders();
				$senders = array();
				if ($mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK) {
					// reorder by id.
					foreach ( $response['senders'] as $sender ) {
						$senders[] = array(
							'id' => $sender['id'],
							'from_name' => $sender['name'],
							'from_email' => $sender['email'],
						);
					}
				}
				if ( count( $senders ) > 0 ) {
					set_transient( 'sib_senders_' . md5( SIB_Manager::$access_key ), $senders, self::DELAYTIME );
				}
			}
			return $senders;
		}
		/** Remove all transients */
		public static function remove_transients() {
			// remove all transients.
			delete_transient( 'sib_list_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_totalusers_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_credit_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_campaigns_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_smtp_status_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_attributes_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_template_' . md5( SIB_Manager::$access_key ) );
			delete_transient( 'sib_senders_' . md5( SIB_Manager::$access_key ) );
		}

		/**
		 * Send Identify User for MA
		 *
		 * @param array $data - data.
		 */
		public static function identify_user( $data ) {
			$general_settings = get_option( SIB_Manager::MAIN_OPTION_NAME, array() );
			if (isset($general_settings['ma_key'])) {
                try {
                    $event = new Sendinblue( $general_settings['ma_key'] );
                    $event->identify( $data );
                } catch (Exception $exception) {
                    echo $exception->getMessage() . "\n";
                }
            }
		}

		/**
		 * Send email through Sendinblue
		 *
		 * @param array $data - mail data.
		 * @return array|mixed|object
		 */
		public static function send_email( $data ) {
			$mailin = new SendinblueApiClient( );
            try {
                if (isset($data['headers'])) {
                    $emailHeaders = $data['headers'];
                    unset($data['headers']);

                    if (!is_array($emailHeaders) && !is_string($emailHeaders)) {
                        return new WP_Error('email headers are not valid');
                    }

                    if (is_string($emailHeaders)) {
                        $emailHeaders = preg_split("/\r\n|\n|\r/", $emailHeaders);
                    }
                    $preparedHeaders = [];
                    foreach ($emailHeaders as $header) {
                        $header = explode(': ', $header);
                        if (is_array($header) && 2 == count($header)) {
                            if ($header[0] == 'X-Mailin-Tag') {
                                $data['tags'][] = $header[1];
                            }
                            $preparedHeaders[$header[0]] = $header[1];
                        }
                    }
                    $data['headers'] = $preparedHeaders;
                }
            } catch (Exception $exception) {
                return new WP_Error($exception->getMessage());
            }

            $home_options = get_option( SIB_Manager::HOME_OPTION_NAME);
            if (!empty($home_options['from_email'])) {
                $data['sender']['email'] = $home_options['from_email'];
                if (!empty($home_options['from_name'])) {
                    $data['sender']['name'] = $home_options['from_name'];
                }
            }
            $mail_setting  = get_option('wc_sendinblue_settings', array());
            $sib_wc_plugin = is_plugin_active(
                'woocommerce-sendinblue-newsletter-subscription/woocommerce-sendinblue.php'
            );

            if ( ! empty($mail_setting) && isset($mail_setting['ws_smtp_enable']) && 'yes' == $mail_setting['ws_smtp_enable'] && $sib_wc_plugin === true) {
                $from_email              = trim(get_bloginfo('admin_email'));
                $from_name               = trim(get_bloginfo('name'));
                $data['sender']['email'] = apply_filters('wp_mail_from', $from_email);
                $data['sender']['name']  = apply_filters('wp_mail_from_name', $from_name);
            }

			$result = $mailin->sendEmail( $data );
            if (SendinblueApiClient::RESPONSE_CODE_CREATED == $mailin->getLastResponseCode()) {
                return ['code' => 'success'];
            }

			return $result;
		}

        /**
         * Validation the email if it exist in contact list
         *
         * @param $res
         * @param string $type - form type.
         * @param string $email - email.
         * @param array $list_id - list ids.
         * @return array
         */
		static function validation_email( $res, $email, $list_id, $type = 'simple' ) {

			$isDopted = false;

			$desired_lists = $list_id;

			if ( 'double-optin' == $type ) {
				$list_id = array();
			}

			// new user.
			if ( isset($res['code']) && $res['code'] == 'document_not_found' ) {
				$ret = array(
					'code' => 'new',
					'isDopted' => $isDopted,
					'listid' => $list_id,
				);
				return $ret;
			}

			$listid = $res['listIds'];

			// update user when listid is empty.
			if ( ! isset( $listid ) || ! is_array( $listid ) ) {
				$ret = array(
					'code' => 'update',
					'isDopted' => $isDopted,
					'listid' => $list_id,
				);
				return $ret;
			}

			$attrs = $res['attributes'];
			if ( isset( $attrs['DOUBLE_OPT-IN'] ) && '1' == $attrs['DOUBLE_OPT-IN'] ) {
				$isDopted = true;
			}

			$diff = array_diff( $desired_lists, $listid );
			if ( ! empty( $diff ) ) {
				$status = 'update';
				if ( 'double-optin' != $type ) {
					$listid = array_unique( array_merge( $listid, $list_id ) );
				}
			} else {
				if ( '1' == $res['emailBlacklisted'] ) {
					$status = 'update';
				} else {
					$status = 'already_exist';
				}
			}

			$ret = array(
				'code' => $status,
				'isDopted' => $isDopted,
				'listid' => $listid,
			);
			return $ret;
		}

		/**
		 * Signup process
		 *
		 * @param string                     $type - simple, confirm, double-optin / subscribe.
		 * @param $email - subscriber email.
		 * @param $list_id - desired list ids.
		 * @param $info - user's attributes.
		 * @param null                       $list_unlink - remove temp list.
		 * @return string
		 */
		public static function create_subscriber( $email, $list_id, $info, $type = 'simple', $list_unlink = null ) {
            $mailin = new SendinblueApiClient();
            $user = $mailin->getUser($email);

			$response = self::validation_email( $user, $email, $list_id, $type );
			$exist = '';

			if ( 'already_exist' == $response['code'] ) {
				$exist = 'already_exist';
			}

			if ( 'subscribe' == $type ) {
				$info['DOUBLE_OPT-IN'] = '1'; // Yes.
			} else {
				if ( 'double-optin' == $type ) {
					if ( ( 'new' == $response['code'] && ! $response['isDopted']) || ( 'update' == $response['code'] && ! $response['isDopted']) ) {
						$info['DOUBLE_OPT-IN'] = '2'; // No.
					}
				}
			}

			$listid = $response['listid'];
            if ( $list_unlink != null ) {
                $listid = array_diff( $listid, $list_unlink );
            }

            $attributes = SIB_API_Manager::get_attributes();
            if( !empty($attributes["attributes"]["normal_attributes"]) ) {
                foreach ( $attributes["attributes"]["normal_attributes"] as $key => $value ) {
                    if( "boolean" == $value["type"] && array_key_exists($value["name"], $info) )
                        if( in_array($info[ $value["name"] ], array("true","True","TRUE",1)) ) {
                            $info[ $value["name"] ] = true;
                        }
                        else {
                            $info[ $value["name"] ] = false;
						}
						if( "date" == $value["type"] && array_key_exists($value["name"], $info) ) {
							$date = $info[ $value["name"] ];
							$tempDate = explode('-', $date);
							$error = false;
							foreach ( $tempDate as $key => $val ) {
								if ( $val == "0" || $val == "00" || $val == "0000" ) {
									$error = true;
								}
							}
							if ( $error ) {
								wp_send_json(
                                    array(
                                        'status' => 'failure',
                                        'msg' => [
                                            'errorMsg' => 'Date format is invalid',
                                        ]
                                    )
                                );
							} else {
									try {
										$dateCheck = (new DateTime($date))->format('Y-m-d');
										$info[ $value["name"] ] =  $dateCheck;
									} catch (Exception $exception) {
										wp_send_json(
											array(
												'status' => 'failure',
												'msg' => [
													'errorMsg' => 'Date format is invalid',
												]
											)
										);
									}
							}
						}
                }
            }

			if ($mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK && isset($user['email'])) {
                unset($info["email"]);
                if(!($type == 'double-optin')){
					$data = [
						'email' => $email,
						'attributes' => $info,
						'emailBlacklisted' => false,
						'smsBlacklisted' => false,
						'listIds' => $listid,
						'unlinkListIds' => $list_unlink,
						'updateEnabled' => true
					];
				} else { 	
						if($info['DOUBLE_OPT-IN'] == '1'){
							$data = [
								'email' => $email,
								'attributes' => $info,
								'emailBlacklisted' => false,
								'smsBlacklisted' => false,
								'listIds' => $listid,
								'unlinkListIds' => $list_unlink,
								'updateEnabled' => true
							];
						} else {
							$data = [
								'email' => $email,
								'attributes' => $info,
								'emailBlacklisted' => (($user["emailBlacklisted"] == '1') ? $user["emailBlacklisted"] : false),
								'smsBlacklisted' => false,
								'listIds' => $listid,
								'unlinkListIds' => $list_unlink,
								'updateEnabled' => true
							];
						}		
				}
                $mailin->createUser( $data );
                $exist = $mailin->getLastResponseCode() == 204 ? 'success' : '' ;
			} else {
                $info['sibInternalSource'] = self::PLUGIN_NAME;
                $info["internalUserHistory"] = array( array( "action" => "SUBSCRIBE_BY_PLUGIN", "id" => 1, "name" => self::PLUGIN_NAME ) );
				$data = [
                    'email' => $email,
                    'attributes' => $info,
                    'emailBlacklisted' => false,
                    'smsBlacklisted' => false,
                    'listIds' => $listid,
                    'updateEnabled' => true
                ];

				$created_user = $mailin->createUser( $data );
			}

			if ('' !=  $exist) {
				$response['code'] = $exist;
			} else if(isset($created_user['id'])) {
				$response['code'] = "success";
			}

			return $response['code'];
		}

		/**
		 * Send a mail for confirmation through Sendinblue
		 *
		 * @param string                   $type - confirm or double-optin.
		 * @param $to_email - receive email.
		 * @param string                   $template_id - template id.
		 * @param null                     $attributes - attributes.
		 * @param string                   $code - code.
		 */
		public static function send_comfirm_email( $to_email, $type = 'confirm', $template_id = '-1', $attributes = null, $code = '' ) {
			$mailin = new SendinblueApiClient();

			// set subject info.
			if ( 'confirm' == $type ) {
				$subject = __( 'Subscription confirmed', 'mailin' );
			} elseif ( 'double-optin' == $type ) {
				$subject = __( 'Please confirm subscription', 'mailin' );
			}

			// get sender info.
			$home_settings = get_option( SIB_Manager::HOME_OPTION_NAME );
			if ( isset( $home_settings['sender'] ) ) {
				$sender_name = $home_settings['from_name'];
				$sender_email = $home_settings['from_email'];
			} else {
				$sender_email = trim( get_bloginfo( 'admin_email' ) );
				$sender_name = trim( get_bloginfo( 'name' ) );
			}
			if ( '' == $sender_email ) {
				$sender_email = __( 'no-reply@sendinblue.com', 'mailin' );
				$sender_name = __( 'Sendinblue', 'mailin' );
			}

			$template_contents = self::get_email_template( $type );
			$html_content = $template_contents['html_content'];

			$transactional_tags = 'WordPress Mailin';
			$attachment = array();

			// get info from SIB template.
			if ( 'yes' == $home_settings['activate_email'] && intval( $template_id ) > 0 && ( 'confirm' == $type ) ) {
				$data = array(
					'replyTo' => array('email' => $sender_email),
					'to' => array(array('email' => $to_email)),
				);
				$data["templateId"] = intval( $template_id );
				$mailin->sendEmail( $data );
				return;
			}
			else if ( intval( $template_id ) > 0 ) {
				$data = array(
					'id' => $template_id,
				);
				$response = $mailin->getEmailTemplate( $data["id"] );
				if ( $mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK ) {
					$html_content = $response['htmlContent'];
					if ( trim( $response['subject'] ) != '' ) {
						$subject = trim( $response['subject'] );
					}
					if ( ( '[DEFAULT_FROM_NAME]' != $response['sender']['name'] ) &&
						( '[DEFAULT_FROM_EMAIL]' != $response['sender']['email'] ) &&
						( '' != $response['sender']['email'] )
					) {
						$sender_name = $response['sender']['name'];
						$sender_email = $response['sender']['email'];
					}
					$transactional_tags = $response['sender']['name'];

					// pls ask Ekta about attachment of template.
				}
			}

			// send mail.
			$to = array(
				$to_email => '',
			);
			$from = array( $sender_email, $sender_name );

			$site_domain = str_replace( 'https://', '', home_url() );
			$site_domain = str_replace( 'http://', '', $site_domain );

			$html_content = str_replace( '{title}', $subject, $html_content );

			$html_content = str_replace( '{site_domain}', $site_domain, $html_content );
			$encodedEmail = rtrim( strtr( base64_encode( $to_email ), '+/', '-_' ), '=' );
			$search_value = "({{\s*doubleoptin\s*}})";

			// double optin
			$html_content = str_replace( 'https://[DOUBLEOPTIN]', '{subscribe_url}', $html_content );
			$html_content = str_replace( 'http://[DOUBLEOPTIN]', '{subscribe_url}', $html_content );
			$html_content = str_replace( 'https://{{doubleoptin}}', '{subscribe_url}', $html_content );
			$html_content = str_replace( 'http://{{doubleoptin}}', '{subscribe_url}', $html_content );
			$html_content = str_replace( 'https://{{ doubleoptin }}', '{subscribe_url}', $html_content );
			$html_content = str_replace( 'http://{{ doubleoptin }}', '{subscribe_url}', $html_content );
			$html_content = str_replace( '[DOUBLEOPTIN]', '{subscribe_url}', $html_content );
			$html_content = preg_replace($search_value, '{subscribe_url}', $html_content);
			$html_content = str_replace(
				'{subscribe_url}', add_query_arg(
					array(
						'sib_action' => 'subscribe',
						'code' => $code,
					), home_url()
				), $html_content
			);

			if ( 'yes' == $home_settings['activate_email'] ) {

				$data = array(
					'replyTo' => array('email' => $from[0]),
					'to' => array(array('email' => $to_email)),
				);
				$data['sender'] = [ 'email' => $from[0], 'name' => $from[1] ];
				$data['htmlContent'] = $html_content;
				$data['subject'] = $subject;

				$res = $mailin->sendEmail( $data );

			} else {
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$headers[] = "From: $sender_name <$sender_email>";
				@wp_mail( $to_email, $subject, $html_content, $headers );
			}
		}

		/**
		 * Get email template by type (test, confirmation, double-optin).
		 *
		 * @param string $type - email template type.
		 * @return array
		 */
		static function get_email_template( $type = 'test' ) {
			$lang = get_bloginfo( 'language' );
			if ( 'fr-FR' == $lang ) {
				$file = 'temp_fr-FR';
			} else {
				$file = 'temp';
			}

			$file_path = SIB_Manager::$plugin_dir . '/inc/templates/' . $type . '/';
			// get html content.
			$html_content = file_get_contents( $file_path . $file . '.html' );
			// get text content.
			$text_content = file_get_contents( $file_path . $file . '.txt' );
			$templates = array(
				'html_content' => $html_content,
				'text_content' => $text_content,
			);
			return $templates;
		}

		/**
		 * Sync wp users to contact list.
		 *
		 * @param string $users_info - user's attributes.
		 * @param array $list_ids - desired lists
		 * @return array|mixed|object
		 */
		public static function sync_users( $users_info, $list_ids ) {
			$client = new SendinblueApiClient();
			$data = array(
				'fileBody' => $users_info,
				'listIds' => $list_ids,
			);
			$client->importContacts($data);
			if (  SendinblueApiClient::RESPONSE_CODE_ACCEPTED == $client->getLastResponseCode() ) {
                $response = array(
                    'code' => 'success',
                    'message' => __( 'Contact synchronization has started.', 'mailin' )
                );
            } else {
                $response = array(
                    'code' => 'failed',
                    'message' => __( 'Something went wrong. PLease try again.', 'mailin' )
                );
            }
			return $response;
		}

		/**
		 * Subscribe process for double optin subscribers
		 */
		public static function subscribe( $contact_info ) {
			if ( false != $contact_info ) {
				$email = $contact_info['email'];
				$info = maybe_unserialize( $contact_info['info'] );
				$list_id = maybe_unserialize( $contact_info['listIDs'] );
                $form_id = $contact_info['frmid'];
                $current_form = SIB_Forms::getForm( $form_id );
                $unlinkedLists = null;
                if( isset( $info['unlinkedLists'] ) )
                {
                    $unlinkedLists = $info['unlinkedLists'];
                    unset($info['unlinkedLists']);
                }
                if ( '1' == $current_form['isDopt'] )
                {
                    SIB_API_Manager::send_comfirm_email( $email, 'confirm', $current_form['confirmID'], $info );
                }

                if( $unlinkedLists != null ) {
                    self::create_subscriber( $email, $list_id, $info, 'subscribe', $unlinkedLists );
                }
                else {
                    self::create_subscriber( $email, $list_id, $info, 'subscribe' );
                }

			}

			if ( '' != $contact_info['redirectUrl'] ) {
                wp_redirect( $contact_info['redirectUrl'] );
				exit;
			}

			$type = 'Subscribe';
			self::template_subscribe( $type );
			exit;
		}

		/**
		 * Unsubscribe process
		 */
		function unsubscribe() {
			$mailin = new SendinblueApiClient();
			$code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '' ;
			$list_id = isset( $_GET['li'] ) ? intval( $_GET['li'] ) : '' ;

			$email = base64_decode( strtr( $code, '-_', '+/' ) );
			$data = array(
				'email' => $email,
			);
			$response = $mailin->get_user( $data );

			if ($mailin->getLastResponseCode() === SendinblueApiClient::RESPONSE_CODE_OK) {
				$attributes = $response['attributes'];

				$listid = $response['listIds'];

				$blacklisted = $response['emailBlacklisted'];
				$diff_listid = array_diff( $listid, array( $list_id ) );

				if ( count( $diff_listid ) == 0 ) {
					$blacklisted = true;
					$diff_listid = $listid;
				}
				$data = array(
					'email' => $email,
					'data' =>'{"listIds":'.$diff_listid.',"emailBlacklisted":'.$blacklisted.'}'
				);
				$mailin->updateUser( $data["email"],$data["data"] );
			}
			?>
			<body style="margin:0; padding:0;">
			<table style="background-color:#ffffff" cellpadding="0" cellspacing="0" border="0" width="100%">
				<tbody>
				<tr style="border-collapse:collapse;">
					<td style="border-collapse:collapse;" align="center">
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20"></td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20">
									<div
										style="font-family:arial,sans-serif; color:#61a6f3; font-size:20px; font-weight:bold; line-height:28px;">
										<?php esc_attr_e( 'Unsubscribe', 'mailin' ); ?></div>
								</td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20"></td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td align="left">

									<div
										style="font-family:arial,sans-serif; font-size:14px; margin:0; line-height:24px; color:#555555;">
										<br>
										<?php esc_attr_e( 'Your request has been taken into account.', 'mailin' ); ?><br>
										<br>
										<?php esc_attr_e( 'The user has been unsubscribed', 'mailin' ); ?><br>
										<br>
										-Sendinblue
									</div>
								</td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20">
								</td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
			</body>
			<?php
			exit;
		}

		/** Create list and attribute for double optin */
		public static function create_default_dopt() {

			$mailin = new SendinblueApiClient();
			
			// add attribute.
			$isEmpty = false;
			$ret = $mailin->getAttributes();

            if (isset($ret["attributes"])) {
                foreach ($ret["attributes"] as $key => $value) {
                    if($value["category"] == "category" && 'DOUBLE_OPT-IN' == $value['name'] && ! empty( $value['enumeration'] ) ) {
                        $isEmpty = true;
                    }
                }

                if ( ! $isEmpty ) {
                    $data = [
                        'type' => 'category',
                        'enumeration' => [
                            [
                                'value' => 1,
                                'label' => 'Yes'
                            ],
                            [
                                'value' => 2,
                                'label' => 'No'
                            ],
                        ]
                    ];
                    $mailin->createAttribute('category', 'DOUBLE_OPT-IN', $data);
                }
            }
		}

		/** Template for subscriber and bot event using $type */
		public static function template_subscribe( $type ) {
			$site_domain = str_replace( 'https://', '', home_url() );
			$site_domain = str_replace( 'http://', '', $site_domain );
			?>
			<body style="margin:0; padding:0;">
			<table style="background-color:#ffffff" cellpadding="0" cellspacing="0" border="0" width="100%">
				<tbody>
				<tr style="border-collapse:collapse;">
					<td style="border-collapse:collapse;" align="center">
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20"></td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20">
									<div
										style="font-family:arial,sans-serif; color:#61a6f3; font-size:20px; font-weight:bold; line-height:28px;">
										<?php
										if ( 'Subscribe' === $type ) {
											esc_attr_e( 'Thank you for subscribing', 'mailin' );
										} elseif ( 'Bot Event' === $type ) {
												esc_attr_e( 'Please Try Again', 'mailin' );
										}
										?>
									</div>
								</td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20"></td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td align="left">

									<div
										style="font-family:arial,sans-serif; font-size:14px; margin:0; line-height:24px; color:#555555;">
										<br>
										<?php
										if ( 'Subscribe' === $type ) {
											echo esc_attr__( 'You have just subscribed to the newsletter of ', 'mailin' ) . esc_attr( $site_domain ) . ' .'; }
										?>
										<br><br>
										<?php esc_attr_e( '-Sendinblue', 'mailin' ); ?></div>
								</td>
							</tr>
							</tbody>
						</table>
						<table cellpadding="0" cellspacing="0" border="0" width="540">
							<tbody>
							<tr>
								<td style="line-height:0; font-size:0;" height="20">
								</td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>
				</tbody>
			</table>
			</body>
			<?php
		}
	}
}
