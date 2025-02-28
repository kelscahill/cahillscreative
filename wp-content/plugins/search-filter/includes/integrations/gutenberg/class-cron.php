<?php
namespace Search_Filter\Integrations\Gutenberg;

use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations\Gutenberg;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the cron tasks.
 *
 * Mostly clears up expired / orphaned data.
 *
 * @since 3.0.0
 */
class Cron {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'validate' ) );
		// Create the schedule.
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		// Add the cron job action.
		add_action( 'search-filter/integrations/gutenberg/cron', array( __CLASS__, 'run_task' ) );
		// Attach the cron job to the init action.
		add_action( 'search-filter/core/activator/activate', array( __CLASS__, 'activate' ) );
		// Remove the scheduled cron job on plugin deactivation.
		add_action( 'search-filter/core/deactivator/deactivate', array( __CLASS__, 'deactivate' ) );
	}
	/**
	 * Setup the interval/frequency for the cron job.
	 *
	 * @param array $schedules
	 */
	public static function schedules( $schedules ) {
		// Create a search_filter_2days interval.
		if ( ! isset( $schedules['search_filter_2days'] ) ) {
			$schedules['search_filter_2days'] = array(
				'interval' => DAY_IN_SECONDS * 2,
				'display'  => __( 'Once every 2 days', 'search-filter' ),
			);
		}
		return $schedules;
	}

	/**
	 * Make sure the cron job is scheduled.
	 */
	public static function activate() {
		// If the cron job is not scheduled, schedule it.
		if ( ! wp_next_scheduled( 'search-filter/integrations/gutenberg/cron' ) ) {
			wp_schedule_event( time(), 'search_filter_2days', 'search-filter/integrations/gutenberg/cron' );
		}
	}

	/**
	 * Deactivate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'search-filter/integrations/gutenberg/cron' );
	}

	/**
	 * The task to run.
	 *
	 * @since 3.0.0
	 */
	public static function run_task() {
		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'remove_orphaned_fields' ) );
	}

	/**
	 * Validate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function validate() {
		$next_event = wp_get_scheduled_event( 'search-filter/integrations/gutenberg/cron' );
		if ( ! $next_event ) {
			return;
		}

		$time_diff   = $next_event->timestamp - time();
		$time_3_days = 3 * DAY_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_3_days ) {
			// This means our scheduled event has been missed by more then 3 days.
			// So lets run manually and reschedule.
			self::run_task();
			Util::error_log( 'Expired cron job found, re-running and rescheduling.', 'error' );
			wp_clear_scheduled_hook( 'search-filter/integrations/gutenberg/cron' );
			wp_schedule_event( time(), 'search_filter_2days', 'search-filter/integrations/gutenberg/cron' );
		}
	}

	/**
	 * Remove orphaned fields.
	 *
	 * @since 3.0.0
	 */
	public static function remove_orphaned_fields() {
		$found_field_records = Fields::find(
			array(
				'context' => 'block-editor',
				'number'  => 0,
			),
			'records'
		);

		foreach ( $found_field_records as $field_record ) {
			$field_context_path = $field_record->get_context_path();
			$field_id           = $field_record->get_id();
			// Based on the path, check to make sure the field exists where it is supposed to.
			if ( $field_context_path === '' ) {
				// 1. If the context path is empty, then its likely an error / legacy, so remove it.
				Field::destroy( $field_id );
			} elseif ( $field_record->get_status() === 'draft' ) {
				// 2. If a field status is draft, then remove it after 7 days.
				$date_created = $field_record->get_date_created();
				$date_diff    = time() - $date_created;
				if ( $date_diff >= ( WEEK_IN_SECONDS * 2 ) ) {
					Field::destroy( $field_id );
				}
			} elseif ( strpos( $field_context_path, 'post/' ) === 0 ) {
				// 3. If a context path is set to post/[number] - then check the post exists, and check the field exists there.
				$post_id = absint( str_replace( 'post/', '', $field_context_path ) );

				if ( get_post( $post_id ) ) {
					// Post exists, remove any fields which are no longer in the post,
					// this should be kept in sync already via the save_post hook.
					if ( ! self::post_has_field_id( $post_id, $field_id ) ) {
						// Field does not exist, so remove it.
						Field::destroy( $field_id );
					}
				} else {
					// Post does not exist, so remove the field.
					Field::destroy( $field_id );
				}
			} elseif ( strpos( $field_context_path, 'site-editor/' ) === 0 ) {
				// 4. If a context path is set to site-editor/[theme]//[type]-[post-type] - then check the post exists, and check the field exists there.
				$location_path = absint( str_replace( 'site-editor/', '', $field_context_path ) );
				$template      = \get_block_template( $location_path, 'wp_template' );
				// Post name is usually in the format: "archive-post".
				if ( $template && \get_post( $template->wp_id ) ) {
					// Post exists, remove any fields which are no longer in the post,
					// this should be kept in sync already via the save_post hook.
					if ( ! self::post_has_field_id( $template->wp_id, $field_id ) ) {
						// Field does not exist, so remove it.
						Field::destroy( $field_id );
					}
				} else {
					// Post does not exist, so remove the field.
					Field::destroy( $field_id );
				}
			} elseif ( $field_context_path === 'widgets' ) {
				// 5. If a context path is set to "widgets" - then check the field exists there.
				// First check to see if the widget has a valid ID.
				$widget_id = Field::get_meta( $field_id, 'widget_id', true );
				if ( $widget_id === '' ) {
					Field::destroy( $field_id );
				} else {
					// Now check to see if the widget exists.
					if ( ! self::sidebars_have_widget( $widget_id ) ) {
						Field::destroy( $field_id );
					}
				}
			}
		}
	}

	/**
	 * Check if a post has a field ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $field_id The field ID.
	 *
	 * @return bool True if the post has the field ID, false if not.
	 */
	private static function post_has_field_id( $post_id, $field_id ) {
		$post_content_fields = Gutenberg::get_post_content_fields( $post_id );

		if ( ! isset( $post_content_field['fieldId'] ) ) {
			return false;
		}

		foreach ( $post_content_fields as $post_content_field ) {
			if ( absint( $post_content_field['fieldId'] ) === $field_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a sidebar has a widget.
	 *
	 * @since 3.0.0
	 *
	 * @param int $widget_id The widget ID.
	 *
	 * @return bool True if the sidebar has the widget, false if not.
	 */
	private static function sidebars_have_widget( $widget_id ) {
		// Even when we change to a theme without classic sidebars, the widgets get re-assigned
		// to inactive, so this should still be safe.
		$sidebars_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebars_widgets as $sidebar ) {
			if ( is_array( $sidebar ) && in_array( $widget_id, $sidebar, true ) ) {
				return true;
			}
		}
		return false;
	}
}
