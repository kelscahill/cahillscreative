<?php
/**
 * Cron Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations/Gutenberg
 */

namespace Search_Filter\Integrations\Gutenberg;

use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations\Gutenberg;
use Search_Filter\Options;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the cron tasks.
 *
 * Clears up expired / orphaned data.
 *
 * @since 3.0.0
 */
class Cron {

	/**
	 * Initialize the cron tasks.
	 *
	 * Attaches to the centralized maintenance cron.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		// Attach to the centralized maintenance cron.
		add_action( 'search-filter/cron/maintenance', array( __CLASS__, 'run_task' ) );
	}

	/**
	 * The task to run.
	 *
	 * @since 3.0.0
	 */
	public static function run_task() {
		// Only run if we have legacy blocks.
		if ( Options::get( 'gutenberg-has-legacy-blocks' ) === 'yes' ) {
			add_action( 'shutdown', array( __CLASS__, 'remove_orphaned_fields' ) );
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
				// 2. If a field status is draft, then remove it after 2 days.
				$date_created = $field_record->get_date_created();
				$date_diff    = time() - $date_created;
				if ( $date_diff >= ( DAY_IN_SECONDS * 2 ) ) {
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
				$location_path = str_replace( 'site-editor/', '', $field_context_path );
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
				} elseif ( ! self::sidebars_have_widget( $widget_id ) ) {
					// Now check to see if the widget exists.
					Field::destroy( $field_id );
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
	private static function post_has_field_id( int $post_id, int $field_id ) {
		$post_content_fields = Gutenberg::get_post_content_fields( $post_id );

		foreach ( $post_content_fields as $post_content_field ) {
			// get_post_content_fields returns Field objects, not arrays.
			if ( $post_content_field->get_id() === $field_id ) {
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
