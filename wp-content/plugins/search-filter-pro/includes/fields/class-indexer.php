<?php
/**
 * Handles the frontend display of the fields
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro\Fields;

use Search_Filter\Database\Queries\Fields as Fields_Query;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Fields;
use Search_Filter_Pro\Indexer as Search_Filter_Pro_Indexer;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use Search_Filter_Pro\Indexer\Table_Validator;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with fields
 */
class Indexer {

	/**
	 * The indexable record statuses.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	private static $indexable_stati = array(
		'enabled',
		'disabled',
	);

	/**
	 * Initialize the indexer.
	 */
	public static function init() {
		// Check if a fields data has updated, and if we need to resync indexer the data.
		add_action( 'search-filter/record/pre_save', array( __CLASS__, 'field_check_for_indexer_changes' ), 10, 2 );
		// We can't use pre_save for new fields because there is no ID yet, so check the save action to see if there is new indexer data.
		add_action( 'search-filter/record/save', array( __CLASS__, 'field_check_for_new_indexer_data' ), 10, 3 );
		// Remove the indexer data for a field on record delete.
		add_action( 'search-filter/record/pre_destroy', array( __CLASS__, 'field_remove_indexer_data' ), 10, 2 );

		// Run queued table validations.
		// Validate tables after field is updated.
		add_action( 'search-filter/record/save', array( __CLASS__, 'field_post_save' ), 11, 2 );
		// Validate tables after field is deleted (field is gone, counts are accurate).
		add_action( 'search-filter/record/destroy', array( __CLASS__, 'field_post_destroy' ), 10, 2 );
	}

	/**
	 * Check if a field is changing to no longer use the indexer, and delete any related data.
	 *
	 * Important: we need to do this on the pre_save, because otherwise the save will overwrite
	 * the data and the DB call to check the old value will match the new value.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field  $updated_instance    The field being saved.
	 * @param    string $section  The section being saved.
	 * @return   void
	 */
	public static function field_check_for_indexer_changes( $updated_instance, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		// ID of 0 means a new field.
		if ( $updated_instance->get_id() === 0 ) {
			return;
		}

		/**
		 * Filters whether field resync detection is enabled.
		 *
		 * When disabled, field saves will not trigger automatic index rebuilds.
		 * Useful for tests that manually control indexing via Bitmap_Updater.
		 *
		 * @since 3.4.0
		 *
		 * @param bool  $enabled          Whether resync detection is enabled. Default true.
		 * @param Field $updated_instance The field being updated.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$updated_instance
		);

		if ( ! $resync_enabled ) {
			return;
		}

		$should_resync_field = false;

		// Get the previous attributes.
		$previous_attributes = self::get_previous_attributes( $updated_instance );

		// Check first if the queryId changed, as this could mean a field moves from a query being indexed to one thats not.
		if ( self::instance_attribute_will_change( $updated_instance, $previous_attributes, 'queryId' ) ) {
			$old_query_id = isset( $previous_attributes['queryId'] ) ? $previous_attributes['queryId'] : null;
			$field_query  = $updated_instance->get_query();
			// If the query does not use the indexer, we need to check if we came from one that did.
			// In which case we'll need to clear the old data.
			if ( $field_query && $field_query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				$old_query = Query::get_instance( $old_query_id );
				// Old query could be deleted, so an error.
				if ( ! is_wp_error( $old_query ) ) {
					if ( $old_query && $old_query->get_attribute( 'useIndexer' ) === 'yes' ) {
						// We moved from an indexer query to a non indexer query, so remove old data.
						self::remove_field_indexer_data( $updated_instance );
						Tiered_Cache::invalidate_query_cache( $updated_instance->get_query_id() );
						// Bail early.
						return;
					}
				}
			} else {
				// We moved to a query that uses the indexer, so we need to resync.
				$should_resync_field = true;
			}
		}

		if ( ! self::field_should_be_indexed( $updated_instance ) ) {
			// The query + field should not be indexed.
			return;
		}

		// Now check to see if various conditions have been met that require a resync...
		// Basically if the status has changed to enabled or away,
		// or any of the data type attributes have changed.
		$data_type_settings = Fields_Settings::get_settings_by( 'isDataType', true );

		// Build attribute names that if changed will trigger a field rebuild.
		$trigger_refresh_attributes = array(
			'type',
		);
		foreach ( $data_type_settings as $data_type_setting ) {
			$trigger_refresh_attributes[] = $data_type_setting->get_name();
		}
		/**
		 * Loop through the attributes, and compare them to the previous value,
		 * if any changed, update `should_resync_field` to true.
		 */

		foreach ( $trigger_refresh_attributes as $refresh_attribute ) {
			if ( self::instance_attribute_will_change( $updated_instance, $previous_attributes, $refresh_attribute ) ) {
				// Found a changed attribute, so set resync and break early.
				$should_resync_field = true;
				break;
			}
		}

		// Check if the status of the field will change from non indexable to indexable
		// and visa versa.
		$status_change = self::instance_index_status_change( $updated_instance );
		if ( $status_change === 'add' ) {
			$should_resync_field = true;
		} elseif ( $status_change === 'remove' ) {
			self::remove_field_indexer_data( $updated_instance );
		}

		if ( $should_resync_field ) {
			self::rebuild_field_indexer_data( $updated_instance );
		}

		// Always clear the caches after saving a field, so many settings can influence
		// counts its not worth it to try to do it more efficiently.  These are
		// regenerated frequently enough, its not going to be a big impact.
		Tiered_Cache::invalidate_query_cache( $updated_instance->get_query_id() );
	}

	/**
	 * Handle post-save event for field updates.
	 *
	 * Stores interaction_type as meta for efficient validation queries,
	 * then flags tables for validation.
	 *
	 * @since 3.2.0
	 *
	 * @param Field  $field   The saved field instance.
	 * @param string $section The section being saved.
	 */
	public static function field_post_save( $field, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		// Store interaction_type as meta for efficient validation queries.
		// Use Field_Factory::create() with current attributes to get the proper
		// subclass. This handles type changes where $field is still the old PHP
		// class but has updated attributes (e.g., 'advanced' fields returning
		// 'choice' or 'range' interaction type).
		$typed_field      = \Search_Filter\Fields\Field_Factory::create( $field->get_attributes() );
		$interaction_type = ( $typed_field && ! is_wp_error( $typed_field ) ) ? $typed_field->get_interaction_type() : null;

		if ( $interaction_type ) {
			Field::update_meta( $field->get_id(), 'interaction_type', $interaction_type );
		}

		Table_Validator::needs_revalidating();
	}

	/**
	 * Check for newly created fields to see if we need to index them.
	 *
	 * Also runs any queued table validations from pre_save (for type changes).
	 *
	 * @since 3.0.0
	 *
	 * @param    object $field    The field being saved.
	 * @param    string $section  The section being saved.
	 * @param    bool   $is_new   Whether the record is new or not.
	 */
	public static function field_check_for_new_indexer_data( $field, $section, $is_new ) {
		if ( $section !== 'field' ) {
			return;
		}

		if ( ! $is_new ) {
			return;
		}

		/**
		 * Filters whether field resync detection is enabled.
		 *
		 * When disabled, new field creation will not trigger automatic indexing.
		 * Useful for tests that manually control indexing via Bitmap_Updater.
		 *
		 * @since 3.4.0
		 *
		 * @param bool  $enabled Whether resync detection is enabled. Default true.
		 * @param Field $field   The newly created field.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$field
		);

		if ( ! $resync_enabled ) {
			return;
		}

		// Check to see if the connected query has the indexer enabled.
		if ( ! self::field_should_be_indexed( $field ) ) {
			return;
		}

		// Queue indexing for the field via the task runner.
		self::rebuild_field_indexer_data( $field );
	}

	/**
	 * Remove the indexer data for a field on record pre_destroy.
	 *
	 * We want to hook in just before its destroyed so we can create an instance.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $field_id  The query ID being deleted.
	 * @param    string $section   The section being deleted from.
	 */
	public static function field_remove_indexer_data( $field_id, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		$field = Field::get_instance( $field_id );
		if ( is_wp_error( $field ) ) {
			return;
		}

		/**
		 * Filters whether field resync detection is enabled.
		 *
		 * When disabled, field deletion will not trigger index data removal.
		 * Useful for tests that manually control indexing.
		 *
		 * @since 3.4.0
		 *
		 * @param bool  $enabled Whether resync detection is enabled. Default true.
		 * @param Field $field   The field being deleted.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$field
		);

		if ( ! $resync_enabled ) {
			return;
		}

		self::remove_field_indexer_data( $field );
	}

	/**
	 * Handle post-destroy event for field deletion.
	 *
	 * Triggers table validation after field is deleted so that
	 * unused tables can be dropped.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $field_id The ID of the deleted field.
	 * @param string $section  The section being deleted from.
	 */
	public static function field_post_destroy( $field_id, $section ) {
		if ( $section !== 'field' ) {
			return;
		}

		// Field is now deleted, run validation on all strategy managers.
		// This will drop tables if no fields use that strategy anymore.
		Table_Validator::needs_revalidating();
	}

	/**
	 * Remove the indexer data for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field being removed.
	 */
	private static function remove_field_indexer_data( $field ) {

		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_field task (so we clear field index data twice).
		Search_Filter_Pro_Indexer::clear_all_field_data( $field );

		Indexer_Task_Runner::add_task(
			array(
				'action' => 'remove_field',
				'meta'   => array(
					'field_id' => $field->get_id(),
				),
			)
		);

		Indexer_Task_Runner::try_clear_status();

		self::clear_field_wp_cache( $field );

		Search_Filter_Pro_Indexer::async_process_queue();
	}

	/**
	 * Rebuild the field index.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to rebuild.
	 */
	private static function rebuild_field_indexer_data( $field ) {
		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_field task (so we clear field data twice).
		Search_Filter_Pro_Indexer::clear_all_field_data( $field );

		Indexer_Task_Runner::add_task(
			array(
				'action' => 'rebuild_field',
				'meta'   => array(
					'query_id' => $field->get_attribute( 'queryId' ),
					'field_id' => $field->get_id(),
				),
			)
		);

		Indexer_Task_Runner::try_clear_status();

		self::clear_field_wp_cache( $field );

		Search_Filter_Pro_Indexer::async_process_queue();
	}

	/**
	 * Create a cache key for the field options.
	 *
	 * @param Field $field    The field to get the cache key for.
	 * @return string    The cache key.
	 */
	public static function get_field_options_cache_key( $field ) {
		$cache_key = 'search_filter_field_' . $field->get_id() . '_options_data';
		return $cache_key;
	}

	/**
	 * Clear any associated caches for the field.
	 *
	 * @param Field $field    The field to clear the cache for.
	 */
	public static function clear_field_wp_cache( $field ) {
		// Clear any caches related options data for the field.
		$cache_key = self::get_field_options_cache_key( $field );
		wp_cache_delete( $cache_key, 'search-filter-pro' );
	}

	/**
	 * Get previous attributes.
	 *
	 * @param object $updated_instance The updated instance.
	 */
	private static function get_previous_attributes( $updated_instance ) {
		$db_query      = new Fields_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return;
		}
		$db_query_item = $db_query->items[0];
		$old_value     = $db_query_item->get_attributes();
		return $old_value;
	}

	/**
	 * Check if a Record instance value will change given the current instance object.
	 *
	 * @param mixed $updated_instance   The current/updated instance object.
	 * @param array $previous_attributes The previous attributes array.
	 * @param mixed $attribute_to_check The attribute name to check.
	 * @return bool True if the value will change, false if not.
	 */
	private static function instance_attribute_will_change( $updated_instance, $previous_attributes, $attribute_to_check ) {
		$db_attributes = $previous_attributes;
		$old_value     = isset( $db_attributes[ $attribute_to_check ] ) ? $db_attributes[ $attribute_to_check ] : null;
		$new_value     = $updated_instance->get_attribute( $attribute_to_check );

		/**
		 * We want to prevent new setting from triggering rebuilds.  This can happen
		 * when we enabled an integration such as ACF, we'll get new values, probably
		 * empty strings (default values), but that doesn't mean we need to rebuild.
		 */
		if ( empty( $old_value ) && empty( $new_value ) ) {
			return false;
		}
		if ( $old_value !== $new_value ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the type of change to the index.
	 *
	 * Decides if the change requires us to add to the index, remove
	 * from the index, or ignore the status change.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $updated_instance    The field being saved.
	 * @return   string    The type of change to the index.
	 */
	private static function instance_index_status_change( $updated_instance ) {
		// Get old value.
		$db_query      = new Fields_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return 'ignore';
		}
		$db_query_item = $db_query->items[0];
		$old_value     = $db_query_item->get_status();
		$new_value     = $updated_instance->get_status();

		if ( $old_value === $new_value ) {
			return 'ignore';
		}

		// If we went from a non indexable status to an indexable status.
		if ( ! in_array( $old_value, self::$indexable_stati, true ) && in_array( $new_value, self::$indexable_stati, true ) ) {
			return 'add';
		}

		if ( ! in_array( $new_value, self::$indexable_stati, true ) && in_array( $old_value, self::$indexable_stati, true ) ) {
			return 'remove';
		}

		return 'ignore';
	}

	/**
	 * Check if the field should be indexed.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to check.
	 * @return   bool    True if the field should be indexed.
	 */
	private static function field_should_be_indexed( $field ) {

		$query = Fields::get_field_query( $field );
		if ( ! $query ) {
			return false;
		}
		return $query->get_attribute( 'useIndexer' ) === 'yes' && in_array( $query->get_status(), self::$indexable_stati, true );
	}

	/**
	 * Check if the field is set to use the indexer.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to check.
	 * @return   bool    True if the field should be indexed.
	 */
	public static function field_is_connected_to_indexer( $field ) {

		$query = Fields::get_field_query( $field );
		if ( ! $query ) {
			return false;
		}
		return $query->get_attribute( 'useIndexer' ) === 'yes';
	}
}
