<?php

// phpcs:ignore  Generic.Commenting.DocComment.MissingShort
/** @noinspection AutoloadingIssuesInspection */

use WPForms\Helpers\Transient;
use WPForms\Admin\Notice;

/**
 * License key fun.
 *
 * @since 1.0.0
 */
class WPForms_License {

	/**
	 * License update time option name.
	 *
	 * @since 1.8.6
	 */
	const LICENSE_UPDATE_TIME_OPTION = 'wpforms_license_updates';

	/**
	 * License ajax count option name.
	 *
	 * @since 1.8.7
	 */
	const LICENSE_AJAX_COUNT_OPTION = 'wpforms_license_ajax_count_';

	/**
	 * License ajax lock option name.
	 *
	 * @since 1.8.7
	 */
	const LICENSE_AJAX_LOCK_OPTION = 'wpforms_license_ajax_lock_';

	/**
	 * License ajax lock time (in minutes).
	 *
	 * @since 1.8.7
	 */
	const LOCK_TIME = 5;

	/**
	 * Store any license error messages.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $errors = [];

	/**
	 * Store any license success messages.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $success = [];

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.1.2
	 */
	public function hooks() {

		// Admin notices.
		add_action( 'admin_notices', [ $this, 'notices' ] );

		// Periodic background license check.
		$this->maybe_validate_key();
	}

	/**
	 * Retrieve the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get() {

		return wpforms_get_license_key();
	}

	/**
	 * Check how license key is provided.
	 *
	 * @since 1.6.3
	 *
	 * @return string
	 */
	public function get_key_location() {

		$key = wpforms_setting( 'key', '', 'wpforms_license' );

		if ( ! empty( $key ) ) {
			return 'option';
		}

		if ( defined( 'WPFORMS_LICENSE_KEY' ) && WPFORMS_LICENSE_KEY ) {
			return 'constant';
		}

		return 'missing';
	}

	/**
	 * Load the license key level.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function type() {

		return wpforms_setting( 'type', '', 'wpforms_license' );
	}

	/**
	 * Verify a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  License key.
	 * @param bool   $ajax True if this is an ajax request.
	 *
	 * @return bool
	 */
	public function verify_key( $key = '', $ajax = false ) {

		if ( empty( $key ) ) {
			return false;
		}

		if ( $ajax ) {
			$this->cache_ajax_request( 'verify-key' );
		}

		// Perform a request to verify the key.
		$verify = $this->perform_remote_request( 'verify-key', [ 'tgm-updater-key' => $key ] );

		// If the verification request returns false, send back a generic error message and return.
		if ( ! $verify ) {
			$msg = esc_html__( 'There was an error connecting to the remote key API. Please try again later.', 'wpforms' );

			if ( $ajax ) {
				wp_send_json_error( $msg );
			} else {
				$this->errors[] = $msg;

				return false;
			}
		}

		// If an error is returned, set the error and return.
		if ( ! empty( $verify->error ) ) {
			if ( $ajax ) {
				wp_send_json_error( $verify->error );
			} else {
				$this->errors[] = $verify->error;

				return false;
			}
		}

		$success = $verify->success ?? esc_html__( 'Congratulations! This site is now receiving automatic updates.', 'wpforms' );

		// Otherwise, the user's license has been verified successfully, update the option and set the success message.
		$option          = (array) get_option( 'wpforms_license', [] );
		$option['key']   = $key;
		$option['type']  = $verify->type ?? $option['type'];
		$this->success[] = $success;

		// Reset all flags.
		$this->reset_license_flags( $option );

		update_option( 'wpforms_license', $option );

		$this->clear_cache();

		if ( $ajax ) {
			wp_send_json_success(
				[
					'type' => $option['type'],
					'msg'  => $success,
				]
			);
		}
	}

	/**
	 * Clear license cache routine.
	 *
	 * @since 1.6.8
	 */
	private function clear_cache() {

		Transient::delete( 'addons' );
		Transient::delete( 'addons_urls' );

		wp_clean_plugins_cache();
	}

	/**
	 * Reset all license flags.
	 *
	 * @since 1.9.5
	 *
	 * @param array $option License option.
	 */
	private function reset_license_flags( &$option ) {

		$option['is_expired']       = false;
		$option['is_disabled']      = false;
		$option['is_invalid']       = false;
		$option['is_limit_reached'] = false;
		$option['is_flagged']       = false;
	}

	/**
	 * Maybe validates a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @return void Return early if the license update time has not expired yet.
	 */
	public function maybe_validate_key() {

		$key = $this->get();

		if ( ! $key ) {
			// Prevent one extra DB request on delete_option() when it does not exist.
			if ( false !== get_option( self::LICENSE_UPDATE_TIME_OPTION ) ) {
				// Flush timestamp interval when key is missing or not available.
				delete_option( self::LICENSE_UPDATE_TIME_OPTION );
			}

			return;
		}

		// Perform a request to validate the key once a day.
		$time = time();

		if ( $time < (int) get_option( self::LICENSE_UPDATE_TIME_OPTION ) ) {
			return;
		}

		$update = update_option( self::LICENSE_UPDATE_TIME_OPTION, strtotime( '+24 hours' ) );

		// Get option again to check that update_option was successful, and the option was not altered by any filters.
		if (
			$update &&
			$time < (int) get_option( self::LICENSE_UPDATE_TIME_OPTION )
		) {
			$this->validate_key( $key );
		}
	}

	/**
	 * Validate a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key           Key.
	 * @param bool   $forced        Force to set contextual messages (false by default).
	 * @param bool   $ajax          AJAX.
	 * @param bool   $return_status Option to return the license status.
	 *
	 * @return string|bool
	 */
	public function validate_key( $key = '', $forced = false, $ajax = false, $return_status = false ) {

		if ( $ajax ) {
			$this->cache_ajax_request( 'validate-key' );
		}

		$validate = $this->perform_remote_request( 'validate-key', [ 'tgm-updater-key' => $key ] );

		// If there was a basic API error in validation, only set the transient for 10 minutes before retrying.
		if ( ! $validate ) {
			// If forced, set contextual success message.
			if ( $forced ) {
				$msg = esc_html__( 'There was an error connecting to the remote key API. Please try again later.', 'wpforms' );

				if ( $ajax ) {
					wp_send_json_error( $msg );
				} else {
					$this->errors[] = $msg;
				}
			}

			return false;
		}

		return $this->validate_from_response( $validate, $forced, $ajax, $return_status );
	}

	/**
	 * Validate a license key from the response.
	 *
	 * @since 1.8.7
	 *
	 * @param object $validate      Validation response.
	 * @param bool   $forced        Force to set contextual messages (false by default).
	 * @param bool   $ajax          Whether it is an AJAX request.
	 * @param bool   $return_status Option to return the license status.
	 *
	 * @return string|bool
	 */
	public function validate_from_response( $validate, bool $forced, bool $ajax, bool $return_status ) {

		$option = (array) get_option( 'wpforms_license' );

		// Reset all flags.
		$this->reset_license_flags( $option );

		// If a key or author error is returned, the license no longer exists, or the user has been deleted.
		// So, reset the license.
		if ( isset( $validate->key ) || isset( $validate->author ) ) {
			return $this->validate_as_invalid( $ajax, $return_status, $option );
		}

		// If the license has expired, set the transient and expired flag and return.
		if ( isset( $validate->expired ) ) {
			return $this->validate_as_expired( $ajax, $return_status, $option );
		}

		// If the license is disabled, set the transient and disabled flag and return.
		if ( isset( $validate->disabled ) ) {
			return $this->validate_as_disabled( $ajax, $return_status, $option );
		}

		// If the license has no activations left, set the transient and limit_reached flag and return.
		if ( isset( $validate->limit_reached ) ) {
			return $this->validate_as_limit_reached( $ajax, $return_status, $option );
		}

		// At this point, the license is valid, but we need to check if it is flagged (e.g. softcap limit reached).
		if ( isset( $validate->flagged ) ) {
			return $this->validate_as_flagged( $ajax, $return_status, $option );
		}

		return $this->validate_as_valid( $validate, $forced, $ajax, $return_status, $option );
	}

	/**
	 * Deactivate a license key entered by the user.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $ajax True if this is an ajax request.
	 */
	public function deactivate_key( $ajax = false ) {

		$key = $this->get();

		if ( ! $key ) {
			return;
		}

		// Perform a request to deactivate the key.
		$deactivate = $this->perform_remote_request( 'deactivate-key', [ 'tgm-updater-key' => $key ] );

		// If the deactivation request returns false, send back a generic error message and return.
		if ( ! $deactivate ) {

			$msg = esc_html__( 'There was an error connecting to the remote key API. Please try again later.', 'wpforms' );

			if ( $ajax ) {
				wp_send_json_error(
					[
						'msg' => $msg,
					]
				);
			}

			$this->errors[] = $msg;

			return;
		}

		$success_message = esc_html__( 'You have deactivated the key from this site successfully.', 'wpforms' );

		// If an error is returned, set the error and return.
		if ( ! empty( $deactivate->error ) ) {

			// If the license key is invalid, delete the option to ensure the user doesn't get stuck with a filled input.
			// Doing this here will ensure that the connection to the server is already established successfully.
			if ( $this->get_errors() ) {
				$this->remove_key();
			}

			if ( $ajax ) {
				$has_key = ! empty( $this->get() );

				if ( $has_key ) {
					wp_send_json_error(
						[
							'info' => $this->get_info_message_escaped(),
							'msg'  => $deactivate->error,
						]
					);
				}

				wp_send_json_success(
					[
						'info' => $this->get_info_message_escaped(),
						'msg'  => $success_message,
					]
				);
			}

			$this->errors[] = $deactivate->error;

			return;
		}

		// Otherwise, user's license has been deactivated successfully, reset the option and set the success message.
		$success         = $deactivate->success ?? $success_message;
		$this->success[] = $success;

		$this->remove_key();

		if ( $ajax ) {
			wp_send_json_success(
				[
					'info' => $this->get_info_message_escaped(),
					'msg'  => $success,
				]
			);
		}
	}

	/**
	 * Empty out the license key option and flush the cache.
	 *
	 * @since 1.8.0
	 */
	private function remove_key() {

		update_option( 'wpforms_license', '' );
		$this->clear_cache();
	}

	/**
	 * Return possible license key error flag.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if there are license key errors, false otherwise.
	 */
	public function get_errors() {

		$option = get_option( 'wpforms_license' );

		if ( empty( $option ) || ! is_array( $option ) ) {
			return false;
		}

		// Define the flags that represent errors.
		$error_keys = [
			'is_expired',
			'is_disabled',
			'is_invalid',
			'is_limit_reached',
			'is_flagged',
		];

		// Check if any of the specified flags are not empty.
		return ! empty( array_filter( array_intersect_key( $option, array_flip( $error_keys ) ) ) );
	}

	/**
	 * Return license key message if applicable.
	 *
	 * @since 1.7.9
	 *
	 * @return string Returns proper info (error) message depending on the state of the license.
	 */
	public function get_info_message_escaped() {

		if ( ! $this->get() ) {
			return sprintf(
				wp_kses( /* translators: %1$s - WPForms.com Account dashboard URL, %2$s - WPForms.com pricing URL. */
					__( 'Your license key can be found in your <a href="%1$s" target="_blank" rel="noopener noreferrer">WPForms Account Dashboard</a>. Don’t have a license? <a href="%2$s" target="_blank" rel="noopener noreferrer">Sign up today!</a>', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				wpforms_utm_link( 'https://wpforms.com/account/', 'settings-license', 'Account Dashboard' ),
				wpforms_utm_link( 'https://wpforms.com/pricing/', 'settings-license', 'License Key Sign Up' )
			);
		}

		if ( $this->is_expired() ) {
			return wp_kses(
				__( '<strong>Your license has expired.</strong> An active license is needed to create new forms and edit existing forms. It also provides access to new features & addons, plugin updates (including security improvements), and our world class support!', 'wpforms' ),
				[ 'strong' => [] ]
			);
		}

		if ( $this->is_disabled() ) {
			return wp_kses(
				__( '<strong>Your license key has been disabled.</strong> Please use a different key to continue receiving automatic updates.', 'wpforms' ),
				[ 'strong' => [] ]
			);
		}

		if ( $this->is_invalid() ) {
			return wp_kses(
				__( '<strong>Your license key is invalid.</strong> The key no longer exists or the user associated with the key has been deleted. Please use a different key to continue receiving automatic updates.', 'wpforms' ),
				[ 'strong' => [] ]
			);
		}

		if ( $this->is_limit_reached() ) {
			return sprintf(
				wp_kses( /* translators: %1$s - WPForms.com Account licenses URL. */
					__( '<strong>Sorry, but this license has no activations left.</strong> You can manage your site activations, upgrade your license, or purchase a new one in <a href="%1$s" target="_blank" rel="noopener noreferrer">your account</a>.', 'wpforms' ),
					[
						'a'      => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
						'strong' => [],
					]
				),
				wpforms_utm_link( 'https://wpforms.com/account/licenses/', 'settings-license', 'Limit Reached - Account inline' )
			);
		}

		if ( $this->is_flagged() ) {
			return sprintf(
				wp_kses( /* translators: %1$s - WPForms.com license key support URL. */
					__( '<strong>Heads up! Before you can activate this key, we\'d like to check in with you.</strong> Please <a href="%1$s" target="_blank" rel="noopener noreferrer">reach out to support here.</a>', 'wpforms' ),
					[
						'a'      => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
						'strong' => [],
					]
				),
				wpforms_utm_link(
					add_query_arg( [ 'license_key' => $this->get() ], 'https://wpforms.com/account/key-support/' ),
					'settings-license',
					'Verify Key - Reach out to Support inline'
				)
			);
		}

		return '';
	}

	/**
	 * Output any notices generated by the class.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $below_h2 Whether to display a notice below H2.
	 */
	public function notices( $below_h2 = false ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Do not display notices if the user does not have permission or is on the settings page.
		if ( ! wpforms_current_user_can() || wpforms_is_admin_page( 'settings' ) ) {
			return;
		}

		// Grab the option and output any nag dealing with license keys.
		$key    = $this->get();
		$class  = $below_h2 ? 'below-h2 ' : '';
		$class .= 'wpforms-license-notice';

		// If there is no license key, output nag about ensuring key is set for automatic updates.
		if ( ! $key ) {
			$notice = sprintf(
				wp_kses( /* translators: %s - Link to the Settings > General screen in the plugin, where users can enter their license key. */
					__( 'To access all features, addons, and enable automatic updates, please <a href="%s" target="_blank">activate your WPForms license.</a>', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				esc_url( admin_url( 'admin.php?page=wpforms-settings' ) )
			);

			Notice::warning(
				$notice,
				[ 'class' => $class ]
			);

			return; // Bail early, there is no point in going through the rest of the conditional statements, as the key is already missing.
		}

		// Set the renew now url.
		$renew_now_url = wpforms_utm_link(
			'https://wpforms.com/account/licenses/',
			'Admin Notice',
			'Renew Now'
		);

		// Set the "Learn more" url.
		$learn_more_url = wpforms_utm_link(
			'https://wpforms.com/docs/how-to-renew-your-wpforms-license/',
			'Admin Notice',
			'Learn More'
		);

		// If a key has expired, output nag about renewing the key.
		if ( $this->is_expired() ) {
				$notice = sprintf(
					'<h3 style="margin: .75em 0 0 0;">
						<img src="%1$s" style="vertical-align: text-top; width: 20px; margin-right: 7px;">%2$s
					</h3>
					<p>%3$s</p>
					<p>
						<a href="%4$s" class="button-primary">%5$s</a> &nbsp
						<a href="%6$s" class="button-secondary">%7$s</a>
					</p>',
					esc_url( WPFORMS_PLUGIN_URL . 'assets/images/exclamation-triangle.svg' ),
					esc_html__( 'Heads up! Your WPForms license has expired.', 'wpforms' ),
					esc_html__( 'An active license is needed to create new forms and edit existing forms. It also provides access to new features & addons, plugin updates (including security improvements), and our world class support!', 'wpforms' ),
					esc_url( $renew_now_url ),
					esc_html__( 'Renew Now', 'wpforms' ),
					esc_url( $learn_more_url ),
					esc_html__( 'Learn More', 'wpforms' )
				);

				$this->print_error_notices( $notice, 'license-expired', $class );

		}

		// If a key has been disabled, output nag about using another key.
		if ( $this->is_disabled() ) {
			$notice = sprintf(
				'<h3 style="margin: .75em 0 0 0;">
					<img src="%1$s" style="vertical-align: text-top; width: 20px; margin-right: 7px;">%2$s
				</h3>
				<p>%3$s</p>
				<p>
					<a href="%4$s" class="button-primary">%5$s</a> &nbsp
					<a href="%6$s" class="button-secondary">%7$s</a>
				</p>',
				esc_url( WPFORMS_PLUGIN_URL . 'assets/images/exclamation-triangle.svg' ),
				esc_html__( 'Heads up! Your WPForms license has been disabled.', 'wpforms' ),
				esc_html__( 'Your license key for WPForms has been disabled. Please use a different key to continue receiving automatic updates', 'wpforms' ),
				esc_url( $renew_now_url ),
				esc_html__( 'Renew Now', 'wpforms' ),
				esc_url( $learn_more_url ),
				esc_html__( 'Learn More', 'wpforms' )
			);

			$this->print_error_notices( $notice, 'license-diabled', $class );
		}

		// If a key is invalid, output nag about using another key.
		if ( $this->is_invalid() ) {
			$notice = sprintf(
				'<h3 style="margin: .75em 0 0 0;">
					<img src="%1$s" style="vertical-align: text-top; width: 20px; margin-right: 7px;">%2$s
				</h3>
				<p>%3$s</p>
				<p>
					<a href="%4$s" class="button-primary">%5$s</a> &nbsp
					<a href="%6$s" class="button-secondary">%7$s</a>
				</p>',
				esc_url( WPFORMS_PLUGIN_URL . 'assets/images/exclamation-triangle.svg' ),
				esc_html__( 'Heads up! Your WPForms license is invalid.', 'wpforms' ),
				esc_html__( 'The key no longer exists or the user associated with the key has been deleted. Please use a different key to continue receiving automatic updates.', 'wpforms' ),
				esc_url( $renew_now_url ),
				esc_html__( 'Renew Now', 'wpforms' ),
				esc_url( $learn_more_url ),
				esc_html__( 'Learn More', 'wpforms' )
			);

			$this->print_error_notices( $notice, 'license-invalid', $class );
		}

		// If a license has no activations left, show notice about using upgrade or purchase new.
		if ( $this->is_limit_reached() ) {
			$notice = sprintf(
				'<h3 style="margin: .75em 0 0 0;">
					<img src="%1$s" style="vertical-align: text-top; width: 20px; margin-right: 7px;">%2$s
				</h3>
				<p>%3$s</p>
				<p>
					<a href="%4$s" class="button-primary">%5$s</a> &nbsp
					<a href="%6$s" class="button-secondary">%7$s</a>
				</p>',
				esc_url( WPFORMS_PLUGIN_URL . 'assets/images/exclamation-triangle.svg' ),
				esc_html__( 'Heads up! Your WPForms license has no activations left.', 'wpforms' ),
				esc_html__( 'Sorry, but this license has no activations left. You can update the list of your sites, upgrade the license in the Account area or purchase a new license key.', 'wpforms' ),
				esc_url( $renew_now_url ),
				esc_html__( 'Renew Now', 'wpforms' ),
				esc_url( $learn_more_url ),
				esc_html__( 'Learn More', 'wpforms' )
			);

			$this->print_error_notices( $notice, 'license-activation-reached', $class );
		}

		// If a license is flagged, display a notice with a generic message advising the user to contact support.
		if ( $this->is_flagged() ) {

			$key_support_url = wpforms_utm_link(
				add_query_arg( [ 'license_key' => $this->get() ], 'https://wpforms.com/account/key-support/' ),
				'Admin Notice',
				'Verify Key - Contact Support'
			);

			$notice = sprintf(
				'<h3 style="margin: .75em 0 0 0;">
					<img src="%1$s" style="vertical-align: text-top; width: 20px; margin-right: 7px;">%2$s
				</h3>
				<p>%3$s</p>
				<p>
					<a href="%4$s" class="button-primary">%5$s</a>
				</p>',
				esc_url( WPFORMS_PLUGIN_URL . 'assets/images/exclamation-triangle.svg' ),
				esc_html__( 'Heads up!', 'wpforms' ),
				esc_html__( 'Before you can activate this key, we\'d like to check in with you. Please reach out to support.', 'wpforms' ),
				esc_url( $key_support_url ),
				esc_html__( 'Contact Support', 'wpforms' )
			);

			$this->print_error_notices( $notice, 'license-flagged', $class );
		}

		// If there are any license errors, output them now.
		if ( ! empty( $this->errors ) ) {
			Notice::error(
				implode( '<br>', $this->errors ),
				[ 'class' => $class ]
			);
		}

		// If there are any success messages, output them now.
		if ( ! empty( $this->success ) ) {
			Notice::info(
				implode( '<br>', $this->success ),
				[ 'class' => $class ]
			);
		}
	}

	/**
	 * Print error notices generated by the class.
	 *
	 * @since 1.8.2.3
	 *
	 * @param string $notice    Notice html.
	 * @param string $id        Notice id.
	 * @param string $css_class Notice classes.
	 */
	public function print_error_notices( $notice, $id, $css_class = '' ) {

		if ( empty( $notice ) || empty( $id ) ) {
			return;
		}

		Notice::error(
			$notice,
			[
				'class' => $css_class,
				'autop' => false,
				'slug'  => $id,
			]
		);
	}

	/**
	 * Ping the remote server for addons data.
	 *
	 * @since 1.0.0
	 * @since 1.8.0 Added transient cache check and license validation.
	 *
	 * @return array Addons data, maybe an empty array if an error occurred.
	 */
	public function get_addons() {

		$key = $this->get();

		if ( empty( $key ) || ! $this->is_active() ) {
			return [];
		}

		static $addons = null;

		if ( $addons === null ) {
			$addons = Transient::get( 'addons' );
		}

		// We store an empty array if the request isn't valid to prevent spam requests.
		if ( is_array( $addons ) ) {
			return $addons;
		}

		$addons = $this->perform_remote_request( 'get-addons-data', [ 'tgm-updater-key' => $key ] );

		if ( empty( $addons ) || isset( $addons->error ) ) {
			Transient::set( 'addons', [], 10 * MINUTE_IN_SECONDS );

			return [];
		}

		Transient::set( 'addons', $addons, 12 * HOUR_IN_SECONDS );

		return $addons;
	}

	/**
	 * Request the remote URL via wp_remote_get() and return a json decoded response.
	 *
	 * @since 1.0.0
	 * @since 1.7.2 Switch from POST to GET request.
	 *
	 * @param string $action        The name of the request action var.
	 * @param array  $body          The GET query attributes.
	 * @param array  $headers       The headers to send to the remote URL.
	 * @param string $return_format The format for returning content from the remote URL.
	 *
	 * @return mixed Json decoded response on success, false on failure.
	 */
	public function perform_remote_request( $action, $body = [], $headers = [], $return_format = 'json' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Request query parameters.
		$query_params = wp_parse_args(
			$body,
			[
				'tgm-updater-action'      => $action,
				'tgm-updater-key'         => $body['tgm-updater-key'],
				'tgm-updater-wp-version'  => get_bloginfo( 'version' ),
				'tgm-updater-php-version' => PHP_VERSION,
				'tgm-updater-referer'     => site_url(),
				'wpforms_refresh_key'     => (int) $this->is_validate_key_request( (string) $action ),
			]
		);

		$args = [
			'headers'    => $headers,
			'user-agent' => wpforms_get_default_user_agent(),
			'timeout'    => 30,
		];

		$remote_url = WPFORMS_UPDATER_API . '/' . $action;

		// Perform the query and retrieve the response.
		$response      = wp_remote_get( add_query_arg( $query_params, $remote_url ), $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Bail out early if there are any errors.
		if ( (int) $response_code !== 200 || is_wp_error( $response_body ) ) {
			$error_message = is_wp_error( $response ) ? $response->get_error_message() : '';

			$log_data = [
				'action'        => $action,
				'url'           => $remote_url,
				'query_params'  => $query_params,
				'response_code' => $response_code,
				'error'         => $error_message,
				'response'      => $response,
				'server_ip'     => wpforms_get_ip(),
			];

			// Add response body to log if error message is empty.
			if ( empty( $error_message ) && ! is_wp_error( $response_body ) ) {
				$log_data['response_body'] = $response_body;
			}

			wpforms_log(
				'License Remote Request Failed',
				$log_data,
				[
					'type' => [ 'error' ],
				]
			);

			return false;
		}

		// Return the json decoded content.
		return json_decode( $response_body );
	}

	/**
	 * Whether the site is using an active license.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_active() {

		$license = get_option( 'wpforms_license', false );

		return ( ! empty( $license ) && ! $this->get_errors() );
	}

	/**
	 * Whether the site is using an expired license.
	 *
	 * @since 1.7.2
	 *
	 * @return bool
	 */
	public function is_expired() {

		return $this->has_status( 'is_expired' );
	}

	/**
	 * Whether the site is using a disabled license.
	 *
	 * @since 1.7.2
	 *
	 * @return bool
	 */
	public function is_disabled() {

		return $this->has_status( 'is_disabled' );
	}

	/**
	 * Whether the site is using a license with no activations left.
	 *
	 * @since 1.9.5
	 *
	 * @return bool
	 */
	public function is_limit_reached() {

		return $this->has_status( 'is_limit_reached' );
	}

	/**
	 * Whether the site is using a flagged license.
	 *
	 * @since 1.9.5
	 *
	 * @return bool
	 */
	public function is_flagged() {

		return $this->has_status( 'is_flagged' );
	}

	/**
	 * Whether the site is using an invalid license.
	 *
	 * @since 1.7.2
	 *
	 * @return bool
	 */
	public function is_invalid() {

		return $this->has_status( 'is_invalid' );
	}

	/**
	 * Check whether there is a specific license status.
	 *
	 * @since 1.7.2
	 *
	 * @param string $status License status.
	 *
	 * @return bool
	 */
	private function has_status( $status ) {

		$license = get_option( 'wpforms_license', false );

		return ( isset( $license[ $status ] ) && $license[ $status ] );
	}

	/**
	 * Cache ajax requests to prevent spamming the server.
	 *
	 * @since 1.8.7
	 *
	 * @param string $action Action name.
	 * @param int    $tries  Number of tries.
	 *
	 * @noinspection PhpSameParameterValueInspection
	 */
	private function cache_ajax_request( string $action, int $tries = 5 ) {

		$action = sanitize_key( $action );

		$count_transient_name = self::LICENSE_AJAX_COUNT_OPTION . $action;
		$lock_transient_name  = self::LICENSE_AJAX_LOCK_OPTION . $action;

		$ajax_count = (int) Transient::get( $count_transient_name );
		$ajax_lock  = (int) Transient::get( $lock_transient_name );

		++$ajax_count;

		if ( $ajax_count > $tries ) {
			if ( $ajax_lock > time() ) {

				$header = esc_html__( "You've Exceeded the Allowed License Verification Attempts", 'wpforms' );
				$msg    = esc_html__( 'Double-check the license key in your account and try again later. If your license key is no longer valid, please renew or install the free version of WPForms.', 'wpforms' );
				$text   = [
					'header' => $header,
					'msg'    => $msg,
				];

				wp_send_json_error( $text );
			} else {

				$ajax_count = 0;
			}
		}

		Transient::set( $count_transient_name, $ajax_count, self::LOCK_TIME * MINUTE_IN_SECONDS );

		if ( $ajax_count === $tries ) {
			Transient::set(
				$lock_transient_name,
				time() + self::LOCK_TIME * MINUTE_IN_SECONDS,
				self::LOCK_TIME * MINUTE_IN_SECONDS
			);
		}
	}

	/**
	 * Handle case when validate response is invalid.
	 *
	 * @since 1.8.7
	 *
	 * @param bool  $ajax          AJAX.
	 * @param bool  $return_status Option to return the license status.
	 * @param array $option        License option.
	 *
	 * @return string|bool
	 */
	private function validate_as_invalid( bool $ajax, bool $return_status, array $option ) {

		$option['is_invalid'] = true;

		update_option( 'wpforms_license', $option );

		if ( $ajax ) {
			wp_send_json_error( esc_html__( 'Your license key for WPForms is invalid. The key no longer exists or the user associated with the key has been deleted. Please use a different key to continue receiving automatic updates.', 'wpforms' ) );
		}

		return $return_status ? 'invalid' : false;
	}

	/**
	 * Handle case when validate response is expired.
	 *
	 * @since 1.8.7
	 *
	 * @param bool  $ajax          AJAX.
	 * @param bool  $return_status Option to return the license status.
	 * @param array $option        License option.
	 *
	 * @return string|bool
	 */
	private function validate_as_expired( bool $ajax, bool $return_status, array $option ) {

		$option['is_expired'] = true;

		update_option( 'wpforms_license', $option );

		if ( $ajax ) {
			wp_send_json_error( esc_html__( 'Your license key for WPForms has expired. Please renew your license key on WPForms.com to continue receiving automatic updates.', 'wpforms' ) );
		}

		return $return_status ? 'expired' : false;
	}

	/**
	 * Handle case when validate response is disabled.
	 *
	 * @since 1.8.7
	 *
	 * @param bool  $ajax          AJAX.
	 * @param bool  $return_status Option to return the license status.
	 * @param array $option        License option.
	 *
	 * @return string|bool
	 */
	private function validate_as_disabled( bool $ajax, bool $return_status, array $option ) {

		$option['is_disabled'] = true;

		update_option( 'wpforms_license', $option );

		if ( $ajax ) {
			wp_send_json_error( esc_html__( 'Your license key for WPForms has been disabled. Please use a different key to continue receiving automatic updates.', 'wpforms' ) );
		}

		return $return_status ? 'disabled' : false;
	}

	/**
	 * Handle case when validate response is limit_reached.
	 *
	 * @since 1.9.5
	 *
	 * @param bool  $ajax          AJAX.
	 * @param bool  $return_status Option to return the license status.
	 * @param array $option        License option.
	 *
	 * @return string|bool
	 */
	private function validate_as_limit_reached( bool $ajax, bool $return_status, array $option ) {

		$option['is_limit_reached'] = true;

		update_option( 'wpforms_license', $option );

		if ( $ajax ) {
			wp_send_json_error( esc_html__( 'Sorry, but this license has no activations left. You can manage your site activations, upgrade your license, or purchase a new one in your account.', 'wpforms' ) );
		}

		return $return_status ? 'limit reached' : false;
	}

	/**
	 * Handle case when validate response is flagged.
	 *
	 * @since 1.9.5
	 *
	 * @param bool  $ajax          AJAX.
	 * @param bool  $return_status Option to return the license status.
	 * @param array $option        License option.
	 *
	 * @return string|bool
	 */
	private function validate_as_flagged( bool $ajax, bool $return_status, array $option ) {

		$option['is_flagged'] = true;

		update_option( 'wpforms_license', $option );

		if ( $ajax ) {
			wp_send_json_error(
				sprintf(
					wp_kses( /* translators: %1$s - WPForms.com license key support URL. */
						__( 'Heads up! Before you can activate this key, we\'d like to check in with you. Please <a href="%1$s" target="_blank" rel="noopener noreferrer">reach out to support here.</a>', 'wpforms' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
								'rel'    => [],
							],
						]
					),
					wpforms_utm_link( 'https://wpforms.com/account/key-support/', 'settings-license', 'Verify Key - Reach out to Support modal' )
				)
			);
		}

		return $return_status ? 'flagged' : false;
	}

	/**
	 * Handle case when validate response is valid.
	 *
	 * @since 1.8.7
	 *
	 * @param object $validate      Validation response.
	 * @param bool   $forced        Force to set contextual messages (false by default).
	 * @param bool   $ajax          AJAX.
	 * @param bool   $return_status Option to return the license status.
	 * @param array  $option        License option.
	 *
	 * @return string|bool|void
	 */
	private function validate_as_valid( $validate, bool $forced, bool $ajax, bool $return_status, array $option ) {

		// Set transient and update license type and flags.
		$option = $this->update_license_option( $validate, $option );

		if ( ! $forced ) {
			return $return_status ? 'valid' : true;
		}

		$msg             = esc_html__( 'Your key has been refreshed successfully.', 'wpforms' );
		$this->success[] = $msg;

		if ( ! $ajax ) {
			return $return_status ? 'valid' : true;
		}

		wp_send_json_success(
			[
				'type' => $option['type'],
				'msg'  => $msg,
			]
		);
	}

	/**
	 * Check if this is an ajax request to validate the key.
	 *
	 * @since 1.8.7
	 *
	 * @param string $action Action.
	 *
	 * @return bool
	 */
	private function is_validate_key_request( string $action ): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return $action === 'validate-key' &&
			isset( $_REQUEST['action'] ) &&
			$_REQUEST['action'] === 'wpforms_refresh_license';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Updates the license option based on validation results.
	 *
	 * @since 1.8.8
	 *
	 * @param object $validate The validation object.
	 * @param array  $option   The current option array.
	 *
	 * @return array
	 */
	private function update_license_option( $validate, array $option ): array {

		// Otherwise, our check has returned successfully. Set the transient and update our license type and flags.
		$option['type'] = $validate->type ?? $option['type'];

		// Reset all flags.
		$this->reset_license_flags( $option );

		if ( ! empty( $validate->expires ) ) {
			// Note the `expires` value normally returns timestamp in string format;
			// There could be a case when it returns "lifetime" string for licenses with no expiration date.
			$option['expires'] = sanitize_text_field( $validate->expires );
		}

		if ( ! empty( $validate->sub_status ) ) {
			// For users who have a license key but do not have a subscription, the `sub_status` value will be empty.
			$option['sub_status'] = sanitize_text_field( $validate->sub_status );
		}

		update_option( 'wpforms_license', $option );

		return $option;
	}
}
