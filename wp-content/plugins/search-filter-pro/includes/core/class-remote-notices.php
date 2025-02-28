<?php
/**
 * Handles license server endpoint selection and health checks.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

use Search_Filter\Core\Notices;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles license server availability checks and endpoint selection.
 */
class Remote_Notices {

	/**
	 * The cron hook name.
	 */
	const CRON_HOOK = 'search-filter-pro/core/notices/fetch';

	/**
	 * The cron interval name.
	 */
	const CRON_INTERVAL_NAME = 'search_filter_3days';

	/**
	 * The cron interval.
	 */
	const CRON_INTERVAL = DAY_IN_SECONDS * 7;

	/**
	 * The option name for storing the server test results.
	 */
	const OPTION_NOTICES = 'remote-notices';

	/**
	 * Initialize the license server checks.
	 */
	public static function init() {

		// Setup CRON job for checking for expired items.
		add_action( 'init', array( __CLASS__, 'validate_cron_schedule' ) );
	   
		// Create the schedule
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		
		// Add the cron job action
		add_action( self::CRON_HOOK, array( __CLASS__, 'schedule_fetch' ) );
		
		// Attach activation/deactivation hooks
		add_action( 'search-filter-pro/core/activator/activate', array( __CLASS__, 'activate' ) );
		add_action( 'search-filter-pro/core/deactivator/deactivate', array( __CLASS__, 'deactivate' ) );


		// Add notices when there are errors with connecting to the servers.
		add_action( 'init', array( __CLASS__, 'add_notices' ) );
	}


	/**
	 * Setup the interval for the cron job.
	 *
	 * @param array $schedules The existing cron schedules.
	 * @return array Modified cron schedules.
	 */
	public static function schedules( $schedules ) {
		if ( ! isset( $schedules[ self::CRON_INTERVAL_NAME ] ) ) {
			$schedules[ self::CRON_INTERVAL_NAME ] = array(
				'interval' => self::CRON_INTERVAL,
				'display'  => __( 'Once every 3 days', 'search-filter-pro' ),
			);
		}
		return $schedules;
	}

	/**
	 * Activate the cron job.
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
		}
	}

	/**
	 * Deactivate the cron job.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	
	/**
	 * Hook the task into shutdown so we don't affect the request.
	 */
	public static function schedule_fetch() {
		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'fetch' ) );
	}

	/**
	 * Refresh health.
	 *
	 * @param string $endpoint The endpoint URL to test.
	 */
	public static function fetch() {

		$api_params = array(
			'edd_action'   => 'get_notices',
			'item_id'  => 526297,
			'url'      => home_url(),
			'license'  => '',
		);
		
		$license_data = License_Server::get_license_data();
		if ( ! empty( $license_data['license'] ) ) {
			$api_params['license'] = $license_data['license'];
		}
		
		$endpoint = License_Server::get_endpoint();
		
		// Call the custom API.
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body' => $api_params,
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );
	
		if ( $code < 200 || $code >= 300 ) {
			return;
		}

		$notice_response = json_decode( $body, true );

		// No message broadcasted.
		if ( empty( $notice_response ) ) {
			Options::update_option_value( self::OPTION_NOTICES, array() );
			return;
		}

		// Validate notice before saving.
        if ( ! self::validate_notice( $notice_response ) ) {
            return;
        }
        // Sanitize each $key and $value in the $notice_response array.
        foreach( $notice_response as $key => $value ) {
			if ( is_bool( $value ) ) {
				$notice_response[ sanitize_text_field( $key ) ] = (bool) $value;
			} else {
				$notice_response[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
        }
        Options::update_option_value( self::OPTION_NOTICES, $notice_response );
		return;
	}

    private static function validate_notice( $notice ) {
        if ( ! is_array( $notice ) ) {
            return false;
        }

		$allowed_keys = array( 'id', 'message', 'type', 'actionText', 'actionLink', 'dismissible' );

		$received_keys = array_keys( $notice );
		// Don't allow extra keys.
		foreach( $received_keys as $key ) {
			if ( ! in_array( $key, $allowed_keys ) ) {
				return false;
			}
		}

		// Ensure at least id, message and type are set.
        if ( ! isset( $notice['id'] ) ) {
            return false;
        }
        
        if ( ! isset( $notice['message'] ) ) {
            return false;
        }

        if ( ! isset( $notice['type'] ) ) {
            return false;
        }

        return $notice;
    }
	
	/**
	 * Validate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function validate_cron_schedule() {
		
		$next_event = wp_get_scheduled_event( self::CRON_HOOK );
		if ( ! $next_event ) {
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
			return;
		}

		$time_diff      = $next_event->timestamp - time();
		$time_5_minutes = 5 * MINUTE_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_5_minutes ) {
			// This means our scheduled event has been missed by more then 5 minutes.
			// So lets run manually and reschedule.
			self::schedule_fetch();
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
		}
	}

	/**
	 * Add error notices if the license server cannot be reached.
	 */
	public static function add_notices() {

		if ( ! class_exists( '\Search_Filter\Options' ) ) {
			return;
		}

		add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'display_search_filter_notices' ), 10 );
	
	}


	/**
	 * Display a notice if the connection to the license server fails
	 * in the S&F dashboard.
	 */
	public static function display_search_filter_notices() {
		
		$remote_notices = \Search_Filter\Options::get_option_value( self::OPTION_NOTICES );

		if ( empty( $remote_notices ) ) {
			return;
		}

		if ( ! self::validate_notice( $remote_notices ) ) {
			return;
		}

		$notice_string = wp_kses_post( $remote_notices['message'] );
		$dismissible = isset( $remote_notices['dismissible'] ) ? $remote_notices['dismissible'] : true;

		if ( isset( $remote_notices['actionText'], $remote_notices['actionLink'] ) ) {
			$actions = array(
				'remote_notice_' . sanitize_key( $remote_notices['id'] )  => array(
					'label'         => esc_html( $remote_notices['actionText'], 'search-filter-pro' ),
					'type'     => 'link',
					'location' => $remote_notices['actionLink'],
					'variant'  => 'secondary',
				),
				'dismiss' => $dismissible,
			);
		}

		$type = isset( $remote_notices['type'] ) ? $remote_notices['type'] : 'info';
		Notices::add_notice( $notice_string, $type, 'search-filter-remote-notice-' . sanitize_key( $remote_notices['id'] ), $actions );
	}
}
