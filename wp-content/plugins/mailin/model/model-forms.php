<?php
/**
 * Model class <i>SIB_Forms</i> represents forms
 *
 * @package SIB_Forms
 */

if ( ! class_exists( 'SIB_Forms' ) ) {
	/**
	 * Class SIB_Forms
	 *
	 * @package SIB_Forms
	 */
	class SIB_Forms {

		/**
		 * Tab table name
		 */
		const TABLE_NAME = 'sib_model_forms';
		const DEFAULT_FORM_HTML_PATH = '../form/default-form.html';
		const DEFAULT_FORM_CSS_PATH = '../form/css/default-form.css';
		const DEFAULT_FORM_MESSAGE_CSS_PATH = '../form/css/default-form-message.css';

		/** Create Table */
		public static function createTable() {
			global $wpdb;
			// create list table.
			$creation_query =
				'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . self::TABLE_NAME . ' (
                `id` int(20) NOT NULL AUTO_INCREMENT,
                `title` varchar(120) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `html` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `css` longtext,
                `dependTheme` int(1) NOT NULL DEFAULT 1,
                `listID` longtext,
                `templateID` int(20) NOT NULL DEFAULT -1,
                `confirmID` int(20) NOT NULL DEFAULT -1,
                `isDopt` int(1) NOT NULL DEFAULT 0,
                `isOpt` int(1) NOT NULL DEFAULT 0,
                `redirectInEmail` varchar(255),
                `redirectInForm` varchar(255),
                `successMsg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `errorMsg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `existMsg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `invalidMsg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `requiredMsg` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `attributes` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `date` DATE NOT NULL,
                `isDefault` int(1) NOT NULL DEFAULT 0,
                `gCaptcha` int(1) NOT NULL DEFAULT 0,
                `gCaptcha_secret` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `gCaptcha_site` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                `termAccept` int(1) NOT NULL DEFAULT 0,
                `termsURL` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                PRIMARY KEY (`id`)
                );';
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$wpdb->query($creation_query);
			// create default form.
            $rows = $wpdb->get_results('SELECT * FROM '. $wpdb->prefix . self::TABLE_NAME );
            if (count( $rows ) == 0 )
            {
                self::createDefaultForm();
            }
		}

		/**
		 * Remove table
		 */
		public static function removeTable() {
			global $wpdb;
			$query = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . self::TABLE_NAME . ';';
			$wpdb->query( $query ); // db call ok; no-cache ok.
		}

		/**
		 * Add columns for old versions
		 */
		public static function alterTable() {
			global $wpdb;
			// add columns -gCaptcha, gCaptcha_secret.
			$table_name = $wpdb->prefix . self::TABLE_NAME;

			// check if gCaptcha fields exist
			$gCaptcha = 'gCaptcha';
			$result = $wpdb->query( $wpdb->prepare( "SHOW COLUMNS FROM `$table_name` LIKE %s ", $gCaptcha ) ); // db call ok; no-cache ok.

			if ( empty( $result ) ) {
				$alter_query = 'ALTER TABLE ' . $table_name . '
                            ADD COLUMN gCaptcha int(1) not NULL DEFAULT 0,
                             ADD COLUMN gCaptcha_secret varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
                             ADD COLUMN gCaptcha_site varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci';
				$ret = $wpdb->query( $alter_query );
			}

            // add columns -termAccept, termsURL : version 2.9.0
            $check_query = 'SHOW COLUMNS FROM `' . $table_name . "` LIKE 'termAccept';";
            $result = $wpdb->query( $check_query );
            if ( empty( $result ) ) {
                $alter_query = 'ALTER TABLE ' . $table_name . '
                            ADD COLUMN termAccept int(1) not NULL DEFAULT 1,
                             ADD COLUMN termsURL varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci';
                $ret = $wpdb->query( $alter_query );
            }
            // add columns - confirmID : version 2.9.0
            $check_query = 'SHOW COLUMNS FROM `' . $table_name . "` LIKE 'confirmID';";
            $result = $wpdb->query( $check_query );
            if ( empty( $result ) ) {
                $alter_query = 'ALTER TABLE ' . $table_name . '
                            ADD COLUMN confirmID int(20) not NULL DEFAULT -1';
                $ret = $wpdb->query( $alter_query );
            }
            // add columns - requiredMsg : version 2.9.3
            $check_query = 'SHOW COLUMNS FROM `' . $table_name . "` LIKE 'requiredMsg';";
            $result = $wpdb->query( $check_query );
            if ( empty( $result ) ) {
                $alter_query = 'ALTER TABLE ' . $table_name . '
                            ADD COLUMN requiredMsg varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci';
                $ret = $wpdb->query( $alter_query );
            }
		}

		/**
		 * Get form data
		 *
		 * @param string $frmID - form ID.
		 * @return array|null|object|void
		 */
		public static function getForm( $frmID = 'new' ) {
			global $wpdb;
			if ( 'new' == $frmID ) {
				// default form.
				$formData = self::getDefaultForm();
				$list = maybe_serialize( array( SIB_API_Manager::get_default_list_id() ) );
				$results = array(
					'title' => '',
					'html' => $formData['html'],// phpcs:ignore
					'css' => $formData['css'],
					'listID' => $list,
					'dependTheme' => '1',
					'templateID' => '-1',
					'confirmID' => '-1',
					'isOpt' => '0',
					'isDopt' => '0',
					'redirectInEmail' => '',
					'redirectInForm' => '',
					'date' => date( 'Y-m-d' ),
					'successMsg' => $formData['successMsg'],
					'errorMsg' => $formData['errorMsg'],
					'existMsg' => $formData['existMsg'],
					'invalidMsg' => $formData['invalidMsg'],
					'requiredMsg' => $formData['requiredMsg'],
					'attributes' => 'email,NAME',
				);
			} else {
                $query = $wpdb->prepare('SELECT * from ' . $wpdb->prefix . self::TABLE_NAME . ' where id = %d',array(esc_sql($frmID)));
				$results = $wpdb->get_row( $query, ARRAY_A ); // db call ok; no-cache ok.
			}

			if ( is_array( $results ) && count( $results ) > 0 ) {
				$listIDs = maybe_unserialize( $results['listID'] );
				$results['listID'] = $listIDs;
				return $results;
			}
			return array();
		}

		/**
		 * Get all forms
		 */
		public static function getForms() {
			global $wpdb;

			$query = 'select * from ' . $wpdb->prefix . self::TABLE_NAME . ';';
			$results = $wpdb->get_results( $query, ARRAY_A ); // db call ok; no-cache ok.

			if ( is_array( $results ) && count( $results ) > 0 ) {
				// add list names field to display form table.
				foreach ( $results as $key => $form ) {
					if ( SIB_Forms_Lang::check_form_trans( $form['id'] ) == true ) {
						unset( $results[ $key ] );
						continue;
					}
					$listIDs = maybe_unserialize( $form['listID'] );
					$listIDs = !empty($listIDs) ? $listIDs : array();
					// get names form id array.
					$lists = SIB_API_Manager::get_lists(); // pair of id and name.

					$listNames = array();
					foreach ( $lists as $list ) {
						if ( in_array( $list['id'], $listIDs ) ) {
							$listNames[] = $list['name'];
						}
					}
					$results[ $key ]['listName'] = implode( ',', $listNames );
					$results[ $key ]['listID'] = $listIDs;
				}
				return $results;
			}
			return array();

		}

		/**
		 * Add new form
		 *
		 * @param array $formData - form data.
		 * @return null|string
		 */
		public static function addForm( $formData ) {
			global $wpdb;

			$current_date = date( 'Y-m-d' );

            global $wpdb;
            $query = 'INSERT INTO ' . $wpdb->prefix . self::TABLE_NAME.' (title,html,css,dependTheme,listID,templateID,confirmID,isOpt,isDopt,redirectInEmail,redirectInForm,successMsg,errorMsg,existMsg,invalidMsg,requiredMsg,attributes,date,gCaptcha,gCaptcha_secret,gCaptcha_site,termAccept,termsURL) VALUES ';
            $query .= ' (%s, %s, %s, %d, %s, %d, %d, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %d, %s)';

            $query = $wpdb->prepare($query,array($formData['title'],$formData['html'],$formData['css'],$formData['dependTheme'],$formData['listID'],
                $formData['templateID'],$formData['confirmID'],$formData['isOpt'],$formData['isDopt'],$formData['redirectInEmail'],$formData['redirectInForm'],
                $formData['successMsg'],$formData['errorMsg'],$formData['existMsg'],$formData['invalidMsg'],$formData['requiredMsg'],$formData['attributes'],$current_date,$formData['gcaptcha'],$formData['gcaptcha_secret'] ,$formData['gcaptcha_site'],$formData['termAccept'],$formData['termsURL']));

            $wpdb->query( $query ); // db call ok; no-cache ok.
            $index = $wpdb->get_var( 'SELECT LAST_INSERT_ID();' ); // db call ok; no-cache ok.
            return $index;
        }

		/**
		 * Update form
		 *
		 * @param int   $formID - form ID.
		 * @param array $formData - form data.
		 * @return bool
		 */
		public static function updateForm( $formID, $formData ) {
			global $wpdb;

			$current_date = date( 'Y-m-d' );

            global $wpdb;

            $query = 'UPDATE ' . $wpdb->prefix . self::TABLE_NAME ;
            $query .= " set title = %s, html = %s, css = %s, dependTheme = %d, listID = %s, templateID = %d, confirmID = %d, isOpt = %d, isDopt = %d, redirectInEmail = %s, redirectInForm = %s, successMsg = %s, errorMsg = %s, existMsg = %s, invalidMsg = %s, requiredMsg = %s, attributes = %s, date = %s, gCaptcha = %d, gCaptcha_secret = %s, gCaptcha_site = %s, termAccept = %d, termsURL = %s";
            $query .= ' where id= %d';

            $query = $wpdb->prepare( $query ,array($formData['title'],$formData['html'],$formData['css'],$formData['dependTheme'],$formData['listID'],
                $formData['templateID'],$formData['confirmID'],$formData['isOpt'],$formData['isDopt'],$formData['redirectInEmail'],$formData['redirectInForm'],
                $formData['successMsg'],$formData['errorMsg'],$formData['existMsg'],$formData['invalidMsg'],$formData['requiredMsg'],$formData['attributes'],$current_date,$formData['gcaptcha'],$formData['gcaptcha_secret'] ,$formData['gcaptcha_site'],$formData['termAccept'],$formData['termsURL'], esc_sql($formID)));


            $wpdb->query( $query ); // db call ok; no-cache ok.

            return true;
        }

		/**
		 * Remove form
		 *
		 * @param int $id - target form id.
		 */
		public static function deleteForm( $id ) {
			global $wpdb;

			$wpdb->delete(
				$wpdb->prefix . self::TABLE_NAME,
				array(
					'id' => $id,
				)
			); // db call ok; no-cache ok.
		}

		/** Clear forms data */
		public static function removeAllForms() {
			global $wpdb;
			$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . self::TABLE_NAME ); // db call ok; no-cache ok.
			return true;
		}

		/** Create default form */
		public static function createDefaultForm() {
			$formData = self::getDefaultForm();
			// phpcs:ignore
			$html = $formData['html'];
			$css = $formData['css'];
			$list = maybe_serialize( array( SIB_API_Manager::get_default_list_id() ) );
			$current_date = date( 'Y-m-d' );
			$attributes = 'email,NAME';
			global $wpdb;
			$query = 'INSERT INTO ' . $wpdb->prefix . self::TABLE_NAME . ' ';
			$deafult_form_name  = esc_attr( __( 'Default Form', 'mailin' ) );
			$query .= '(title,html,css,listID,dependTheme,successMsg,errorMsg,existMsg,invalidMsg,requiredMsg,attributes,date,isDefault) ';
			$query .= "VALUES ('{$deafult_form_name}','{$html}','{$css}','{$list}','1','{$formData['successMsg']}','{$formData['errorMsg']}','{$formData['existMsg']}','{$formData['invalidMsg']}','{$formData['requiredMsg']}','{$attributes}','{$current_date}','1')";
			$wpdb->query( $query ); // db call ok; no-cache ok.
		}

		/** Get default form data */
		public static function getDefaultForm() {

			$html = wp_kses(self::get_default_form_html(), SIB_Manager::SIB_ATTRIBUTE);
			$css = wp_kses(self::get_default_css_html(), SIB_Manager::SIB_ATTRIBUTE);

			$result = array(
				'html' => $html,
				'css' => $css,
				'successMsg' => esc_attr( __( 'Thank you, you have successfully registered !', 'mailin' ) ),
				'errorMsg' => esc_attr( __( 'Something wrong occured', 'mailin' ) ),
				'existMsg' => esc_attr( __( 'You have already registered', 'mailin' ) ),
				'invalidMsg' => esc_attr( __( 'Your email address is invalid', 'mailin' ) ),
                'requiredMsg' => esc_attr(__('Please fill out this field', 'mailin'))
			);
			return $result;
		}

		/** Get Default css */
		public static function getDefaultMessageCss() {
			$css = file_get_contents(__DIR__ . '/' . self::DEFAULT_FORM_MESSAGE_CSS_PATH) ?: '';
			return wp_kses($css, SIB_Manager::SIB_ATTRIBUTE);
		}

		/**
		 * Get form data of old version
		 * We suppose that the clients have got own setting values for form.
		 * If the client have default setting only then it will be return error.
		 * This function will be removed after next version
		 */
		public static function get_old_form() {
			// create form from old version.
			$form_settings = get_option( 'sib_subscription_option' );
			$html = $form_settings['sib_form_html'];
			$avail_atts = $form_settings['available_attributes'];

			$signup_settings = get_option( 'sib_signup_option' );
			$is_confirm_email = 'yes' == $signup_settings['is_confirm_email'] ? 1 : 0;
			$is_double_optin = 'yes' == $signup_settings['is_double_optin'] ? 1 : 0;
			$redirect_url = $signup_settings['redirect_url'];
			$redirect_url_click = $signup_settings['redirect_url_click'];
			$template_id = 1 == $is_confirm_email ? $signup_settings['template_id'] : $signup_settings['doubleoptin_template_id'];

			$confirmMsg = get_option( 'sib_confirm_option' );

			$homeSetting = get_option( 'sib_home_option' );
			$sib_list = maybe_serialize( array( (string) $homeSetting['list_id'] ) );

			$formData = array(
				'title' => 'Old Form',
				'html' => $html,
				'css' => '',
				'dependTheme' => '1',
				'listID' => $sib_list,
				'templateID' => $template_id,
				'isOpt' => $is_confirm_email,
				'isDopt' => $is_double_optin,
				'redirectInEmail' => $redirect_url,
				'redirectInForm' => $redirect_url_click,
				'successMsg' => $confirmMsg['alert_success_message'],
				'errorMsg'  => $confirmMsg['alert_error_message'],
				'existMsg' => $confirmMsg['alert_exist_subscriber'],
				'invalidMsg' => $confirmMsg['alert_invalid_email'],
				'attributes' => 'email,' . implode( ',', $avail_atts ),
			);

			return $formData;
		}

		/**
         * Add prefix to the table
         */
		public static function add_prefix() {
			global $wpdb;
			if (self::forms_table_exists()) {
				$query = 'ALTER TABLE ' . self::TABLE_NAME . ' RENAME TO ' . $wpdb->prefix . self::TABLE_NAME . ';';
				$wpdb->query( $query ); // db call ok; no-cache ok.
			}
		}

		/**
         * Change datatype of attribute column
         */
		public static function modify_datatype() {
			global $wpdb;
			if (self::forms_table_exists()) {
                $tableStructure = $wpdb->get_results( "DESC " . $wpdb->prefix . self::TABLE_NAME  );
                foreach ($tableStructure as $key => $value)
                {
                    if($value->Field == "attributes" && $value->Type == "varchar(255)")
                        $wpdb->query("ALTER TABLE ". $wpdb->prefix . self::TABLE_NAME." MODIFY ".$value->Field." TEXT DEFAULT NULL");
                }
            }
		}

        /**
         * @return bool
         */
		public static function forms_table_exists()
        {
            global $wpdb;
		    return $wpdb->get_var( "SHOW TABLES LIKE '" . self::TABLE_NAME . "'" ) == self::TABLE_NAME;
        }

		/**
		 * @return string
		 */
		public static function get_default_form_html()
		{
			return file_get_contents(__DIR__ . '/' . self::DEFAULT_FORM_HTML_PATH) ?: '';
		}

		/**
		 * @return string
		 */
		public static function get_default_css_html()
		{
			return file_get_contents(__DIR__ . '/' . self::DEFAULT_FORM_CSS_PATH) ?: '';
		}
	}
}
