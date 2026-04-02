<?php
/**
 * Handles queries
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\SVG_Loader;
use Search_Filter\Queries\Query;
use Search_Filter\Database\Queries\Queries as Queries_Query;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter_Pro\Cache\Tiered_Cache;
use Search_Filter_Pro\Indexer\Stats;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use Search_Filter_Pro\Indexer\Table_Validator;
use Search_Filter_Pro\Fields\Indexer as Fields_Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A controller for managing all thigns to do with saved queries
 */
class Queries {
	/**
	 * Init.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'register_query_settings' ), 1 );
		add_filter( 'search-filter/queries/settings/prepare_setting/before', array( __CLASS__, 'update_query_container_settings_group' ), 10, 1 );
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'register_spinner_settings' ), 1 );
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'register_infinite_scroll_spinner_settings' ), 1 );
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'register_groups' ), 1 );
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'add_single_integrations' ), 1 );
		add_action( 'search-filter/settings/queries/init', array( __CLASS__, 'upgrade_sort_order' ), 1 );

		// Check if a queries data has updated, and if we need to resync the data.
		add_action( 'search-filter/record/pre_save', array( __CLASS__, 'query_check_for_indexer_changes' ), 10, 2 );
		// We can't use pre_save for new posts because there is no ID, so check the save action to see if there  is new indexer data.
		add_action( 'search-filter/record/save', array( __CLASS__, 'query_check_for_new_indexer_data' ), 10, 3 );
		// Remove the indexer data for a query on record delete.
		add_action( 'search-filter/record/pre_destroy', array( __CLASS__, 'query_remove_indexer_data' ), 10, 2 );

		add_filter( 'search-filter/queries/query/apply_wp_query_args', array( __CLASS__, 'add_meta_query' ), 1, 2 );
		add_filter( 'search-filter/queries/query/create_attributes_css', array( __CLASS__, 'create_attributes_css' ), 10, 2 );
		add_filter( 'search-filter/record/set_attributes', array( __CLASS__, 'set_scroll_to_attributes' ), 1, 3 );
		add_filter( 'search-filter/record/set_attributes', array( __CLASS__, 'set_results_update_page_attributes' ), 1, 2 );

		// Run queued table validations.
		// Validate tables after query is updated.
		add_action( 'search-filter/record/save', array( __CLASS__, 'query_post_save' ), 11, 2 );
		// Validate tables after query is deleted (query is gone, counts are accurate).
		add_action( 'search-filter/record/destroy', array( __CLASS__, 'query_post_destroy' ), 10, 2 );

		// TODO - we need to load icons permanently in admin screens, and only enqueue when needed
		// in the frontend.
		$icons = array(
			'spinner-circle' => SEARCH_FILTER_PRO_PATH . 'assets/images/svg/spinner-circle.svg',
		);
		foreach ( $icons as $icon => $file ) {
			SVG_Loader::register( $icon, $file, false );
			SVG_Loader::enqueue( $icon );
		}
	}

	/**
	 * Check if a query is changing to no longer use the indexer, and delete any related data.
	 *
	 * Important: we need to do this on the pre_save, beecause otherwise the save will overwrite
	 * the data and the DB call to check the old value will match the new value.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query  $updated_instance    The query being saved.
	 * @param    string $section  The section being saved.
	 * @return   void
	 */
	public static function query_check_for_indexer_changes( $updated_instance, $section ) {
		if ( $section !== 'query' ) {
			return;
		}

		// ID of 0 means a new query.
		if ( $updated_instance->get_id() === 0 ) {
			return;
		}

		/**
		 * Filters whether query resync detection is enabled.
		 *
		 * When disabled, query saves will not trigger automatic index rebuilds.
		 * Useful for tests that manually control indexing.
		 *
		 * @since 3.2.0
		 *
		 * @param bool  $enabled          Whether resync detection is enabled. Default true.
		 * @param Query $updated_instance The query being updated.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$updated_instance
		);

		if ( ! $resync_enabled ) {
			return;
		}

		$should_resync_query = false;

		if ( self::instance_attribute_will_change( $updated_instance, 'useIndexer' ) ) {
			// So we're going to either enabled or disabled.

			if ( $updated_instance->get_attribute( 'useIndexer' ) === 'yes' ) {
				// Queue indexing for the query via the task runner.
				$should_resync_query = true;
			} else {
				// Indexing has been disabled, so clear all the data & tasks.
				self::remove_query_indexer_data( $updated_instance );
			}
		}

		if ( self::instance_attribute_will_change( $updated_instance, 'postTypes' ) ) {
			// Post types have changed, so we should resync the query.
			$should_resync_query = true;
		}

		// Check if the status of the query will change from non indexable to indexable
		// and visa versa.
		$status_change = self::instance_index_status_change( $updated_instance );
		if ( $status_change === 'add' ) {
			$should_resync_query = true;
		} elseif ( $status_change === 'remove' ) {
			self::remove_query_indexer_data( $updated_instance );
		}

		if ( $should_resync_query ) {
			self::rebuild_query_indexer_data( $updated_instance );
		}

		// We used to do this by watching for specific attribute changes.
		// Instead, always clear the caches after saving a query, so many settings
		// can influence counts its not worth it to try to do it more efficiently.
		// These are regenerated frequently enough, its not going to be a big impact.
		Tiered_Cache::invalidate_query_cache( $updated_instance->get_id() );
	}

	/**
	 * Check for newly created queries to see if we need to index them.
	 *
	 * Note: this won't usually be necessary until because new queries do not
	 * have fields assigned to them. However, it is possible to do this
	 * programmatically so we should check for it anyway.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query  $query    The query being saved.
	 * @param    string $section  The section being saved.
	 * @param    bool   $is_new   Whether the record is new or not.
	 */
	public static function query_check_for_new_indexer_data( $query, $section, $is_new ) {
		if ( $section !== 'query' ) {
			return;
		}

		if ( ! $is_new ) {
			return;
		}

		/**
		 * Filters whether query resync detection is enabled.
		 *
		 * When disabled, new query creation will not trigger automatic indexing.
		 * Useful for tests that manually control indexing.
		 *
		 * @since 3.2.0
		 *
		 * @param bool  $enabled Whether resync detection is enabled. Default true.
		 * @param Query $query   The newly created query.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$query
		);

		if ( ! $resync_enabled ) {
			return;
		}

		if ( $query->get_attribute( 'useIndexer' ) === 'yes' && $query->get_status() === 'enabled' ) {
			// Queue indexing for the query via the task runner.
			self::rebuild_query_indexer_data( $query );
		}
	}

	/**
	 * Remove the indexer data for a query on record pre_destroy.
	 *
	 * We want to hook in just before its destroyed so we can create an instance
	 * and get the fields to remove.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $query_id  The query ID being deleted.
	 * @param    string $section   The section being deleted from.
	 */
	public static function query_remove_indexer_data( $query_id, $section ) {
		if ( $section !== 'query' ) {
			return;
		}

		$query = Query::get_instance( $query_id );
		if ( is_wp_error( $query ) ) {
			return;
		}

		/**
		 * Filters whether query resync detection is enabled.
		 *
		 * When disabled, query deletion will not trigger index data removal.
		 * Useful for tests that manually control indexing.
		 *
		 * @since 3.2.0
		 *
		 * @param bool  $enabled Whether resync detection is enabled. Default true.
		 * @param Query $query   The query being deleted.
		 */
		$resync_enabled = apply_filters(
			'search-filter-pro/indexer/enable_resync_detection',
			true,
			$query
		);

		if ( ! $resync_enabled ) {
			return;
		}

		self::remove_query_indexer_data( $query );
	}

	/**
	 * Handle post-save event for query updates.
	 *
	 * Stores useIndexer as meta for efficient validation queries,
	 * then flags tables for validation.
	 *
	 * @since 3.2.0
	 *
	 * @param Query  $query   The saved query instance.
	 * @param string $section The section being saved.
	 */
	public static function query_post_save( $query, $section ) {
		if ( $section !== 'query' ) {
			return;
		}

		// Store use_indexer as meta for efficient validation queries.
		$use_indexer = $query->get_attribute( 'useIndexer' ) === 'yes' ? 'yes' : 'no';
		Query::update_meta( $query->get_id(), 'use_indexer', $use_indexer );

		// This will drop tables based on if queries or fields need them.
		Table_Validator::needs_revalidating();
	}

	/**
	 * Handle post-destroy event for query deletion.
	 *
	 * Triggers table validation after query is deleted so that
	 * unused tables can be dropped.
	 *
	 * @since 3.2.0
	 *
	 * @param int    $query_id The ID of the deleted query.
	 * @param string $section  The section being deleted from.
	 */
	public static function query_post_destroy( $query_id, $section ) {
		if ( $section !== 'query' ) {
			return;
		}

		// Query is now deleted, run validation on all dynamic indexer tables.
		// This will drop tables if no fields use a particular strategy
		// anymore or if there are no queries using the indexer we'll remove
		// indexer tables entirely.
		Table_Validator::needs_revalidating();
	}

	/**
	 * Remove the indexer data for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query $query    The query being saved.
	 * @return   void
	 */
	private static function remove_query_indexer_data( $query ) {
		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_query task (so we clear field data twice).
		Indexer::clear_all_query_data( $query );

		// Then queue a remove task.
		Indexer_Task_Runner::add_task(
			array(
				'action' => 'remove_query',
				'meta'   => array(
					'query_id' => $query->get_id(),
				),
			)
		);

		Indexer_Task_Runner::try_clear_status();

		self::clear_fields_wp_cache( $query );

		Indexer::async_process_queue();

		// Invalidate stats cache so query/field counts recalculate.
		Stats::flag_refresh();
	}
	/**
	 * Rebuild the query index.
	 *
	 * @since 3.0.0
	 *
	 * @param    Query $query    The query ID to rebuild.
	 */
	private static function rebuild_query_indexer_data( $query ) {

		// Clear any existing tasks and index, any in progress tasks should be
		// removed by the rebuild_query task (so we clear field data twice).
		Indexer::clear_all_query_data( $query );

		// Then queue a rebuild task.
		Indexer_Task_Runner::add_task(
			array(
				'action' => 'rebuild_query',
				'meta'   => array(
					'query_id' => $query->get_id(),
				),
			)
		);

		Indexer_Task_Runner::try_clear_status();

		self::clear_fields_wp_cache( $query );

		Indexer::async_process_queue();

		// Invalidate stats cache so query/field counts recalculate.
		Stats::flag_refresh();
	}

	/**
	 * Clear any wp_cache's for the queries related fields.
	 *
	 * @since 3.0.0
	 *
	 * @param Query $query    The query to clear the fields cache for.
	 */
	private static function clear_fields_wp_cache( $query ) {
		// We also need to clear any wp_cache's for the individual fields as some
		// of them may change their options based on the query settings.
		$fields = $query->get_fields();
		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				Util::error_log( 'Field error when clearing fields wp cache.', 'error' );
				continue;
			}
			Fields_Indexer::clear_field_wp_cache( $field );
		}
	}

	/**
	 * Check if a Record instance value will change given the current instance object.
	 *
	 * @param mixed $updated_instance   The current/updated instance object.
	 * @param mixed $attribute_to_check The attribute name to check.
	 * @return void|bool True if the value will change, false if not.
	 */
	private static function instance_attribute_will_change( $updated_instance, $attribute_to_check ) {
		// Get old value.
		$db_query      = new Queries_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return;
		}
		$db_query_item = $db_query->items[0];
		$db_attributes = $db_query_item->get_attributes();
		$old_value     = isset( $db_attributes[ $attribute_to_check ] ) ? $db_attributes[ $attribute_to_check ] : null;
		$new_value     = $updated_instance->get_attribute( $attribute_to_check );

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
	 * @param    Query $updated_instance    The query being saved.
	 * @return   string    The type of change to the index.
	 */
	private static function instance_index_status_change( $updated_instance ) {
		// Get old value.
		$db_query      = new Queries_Query( array( 'id' => $updated_instance->get_id() ) );
		$db_query_item = null;
		if ( count( $db_query->items ) === 0 ) {
			return 'ignore';
		}
		$db_query_item = $db_query->items[0];
		$old_value     = $db_query_item->get_status();
		$new_value     = $updated_instance->get_status();

		$index_stati = array(
			'enabled',
			'disabled',
		);

		if ( $old_value === $new_value ) {
			return 'ignore';
		}

		// If we went from a non indexable status to an indexable status.
		if ( ! in_array( $old_value, $index_stati, true ) && in_array( $new_value, $index_stati, true ) ) {
			return 'add';
		}

		if ( ! in_array( $new_value, $index_stati, true ) && in_array( $old_value, $index_stati, true ) ) {
			return 'remove';
		}

		return 'ignore';
	}

	/**
	 * Register the settings groups.
	 *
	 * @since    3.0.0
	 */
	public static function register_groups() {

		Queries_Settings::add_group(
			array(
				'name'  => 'results',
				'label' => __(
					'Results',
					'search-filter-pro'
				),
			),
			array(
				'position' => array(
					'placement' => 'after',
					'group'     => 'tax_query',
				),
			)
		);

		Queries_Settings::add_group(
			array(
				'name'  => 'meta_query',
				'label' => __(
					'Post Meta',
					'search-filter-pro'
				),
			),
			array(
				'position' => array(
					'placement' => 'after',
					'group'     => 'tax_query',
				),
			)
		);

		$spinner_subgroups = array(
			array(
				'name'  => 'color',
				'label' => __( 'Color', 'search-filter-pro' ),
				'type'  => 'color-panel',
			),
			array(
				'name'  => 'dimensions',
				'label' => __( 'Dimensions', 'search-filter-pro' ),
			),
			array(
				'name'  => 'border',
				'label' => __( 'Border', 'search-filter-pro' ),
			),
		);

		Queries_Settings::add_group(
			array(
				'name'      => 'spinner',
				'label'     => __( 'Loading Icon', 'search-filter-pro' ),
				'type'      => 'editor',
				'subgroups' => $spinner_subgroups,
				'preview'   => array(
					'type'            => 'spinner',
					'attributePrefix' => 'spinner',
				),
			)
		);
		Queries_Settings::add_group(
			array(
				'name'      => 'infinite_scroll_spinner',
				'label'     => __( 'Infinite Scroll Icon', 'search-filter-pro' ),
				'type'      => 'editor',
				'subgroups' => $spinner_subgroups,
				'preview'   => array(
					'type'            => 'spinner',
					'attributePrefix' => 'infiniteScrollSpinner',
				),
			)
		);
		Queries_Settings::add_group(
			array(
				'name'  => 'pagination',
				'label' => __(
					'Pagination',
					'search-filter-pro'
				),
			)
		);
	}

	/**
	 * Register the settings for the query.
	 *
	 * @since    3.0.0
	 */
	public static function register_query_settings() {
		$setting = array(
			'name'      => 'useIndexer',
			'label'     => __( 'Use Indexer', 'search-filter' ),
			'help'      => __( 'Use the indexer instead of the WordPress query.', 'search-filter' ),
			'group'     => 'query',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter-pro' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter-pro' ),
				),
			),
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'start',
			),
		);
		Queries_Settings::add_setting( $setting, $setting_args );

		$setting = array(
			'name'      => 'resultsDynamicUpdate',
			'label'     => __( 'Live Search', 'search-filter' ),
			'help'      => __( 'Loads new results without refreshing the page.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'default'   => 'yes',
			'offValue'  => 'no',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		// Add dummy custom field setting.
		$setting = array(
			'name'      => 'metaQuery',
			'label'     => __( 'Custom Field query', 'search-filter' ),
			'group'     => 'meta_query',
			'type'      => 'string',
			'inputType' => 'MetaQuery',
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsUpdateUrl',
			'label'     => __( 'Update URL', 'search-filter' ),
			'help'      => __( 'Enable the browser history and allow searches to be bookmarkable.', 'search-filter' ),
			'group'     => 'results',
			'default'   => 'yes',
			'type'      => 'string',
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsShowSpinner',
			'label'     => __( 'Show Loading Icon', 'search-filter' ),
			'help'      => __( 'Show the loading icon when fetching new results.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'default'   => 'yes',
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsUpdatePage',
			'label'     => __( 'Update Page', 'search-filter' ),
			'help'      => __( 'Update the page when fetching new results.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'default'   => 'yes',
			'inputType' => 'Hidden',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsFadeResults',
			'label'     => __( 'Fade Results', 'search-filter' ),
			'help'      => __( 'Fade out the results when loading.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'default'   => 'yes',
			'inputType' => 'Toggle',
			'options'   => array(
				array(
					'value' => 'yes',
					'label' => __( 'Yes', 'search-filter' ),
				),
				array(
					'value' => 'no',
					'label' => __( 'No', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		// Note: queryContainer setting moved to base plugin for a11y skip links support.
		// It's now always visible (not just when AJAX is enabled).

		$setting = array(
			'name'      => 'dynamicSections',
			'label'     => __( 'Dynamic Sections', 'search-filter' ),
			'help'      => __( 'Additional CSS selector(s) that also need to be updated dynamically.  Must be unique.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'additionalDynamicSections',
			'label'     => __( 'Additional Dynamic Sections', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Hidden',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsScrollTo',
			'label'     => __( 'Scroll To', 'search-filter' ),
			'help'      => __( 'Scroll the window after fetching new results.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'ScrollTo',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsPaginationType',
			'label'     => __( 'Pagination Type', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Select',
			'options'   => array(
				array(
					'value' => 'default',
					'label' => __( 'Default', 'search-filter' ),
				),
				array(
					'value' => 'load_more',
					'label' => __( 'Load more', 'search-filter' ),
				),
				array(
					'value' => 'infinite_scroll',
					'label' => __( 'Infinite scroll', 'search-filter' ),
				),
			),
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'resultsLoadMoreNotice',
			'content'   => __( 'Add the Load More button using the Control -> Load  More field.', 'search-filter-pro' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Notice',
			'status'    => 'info',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'option'  => 'resultsPaginationType',
						'value'   => 'load_more',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'queryPostsContainer',
			'label'     => __( 'Posts Container', 'search-filter' ),
			'help'      => __( 'The container that only contains the posts (no other query data).', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						'rules'    => array(
							array(
								'option'  => 'resultsPaginationType',
								'value'   => 'load_more',
								'compare' => '=',
							),
							array(
								'option'  => 'resultsPaginationType',
								'value'   => 'infinite_scroll',
								'compare' => '=',
							),
						),
					),
				),
			),
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'        => 'infiniteScrollOffset',
			'label'       => __( 'Scroll Offset', 'search-filter' ),
			'help'        => __( 'CSS margin value for when infinite scroll triggers (e.g., -100px triggers 100px before reaching bottom).', 'search-filter' ),
			'group'       => 'results',
			'type'        => 'string',
			'inputType'   => 'Text',
			'default'     => '-100px',
			'placeholder' => '-100px',
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsPaginationType',
						'value'   => 'infinite_scroll',
						'compare' => '=',
					),
				),
			),
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'queryPaginationSelector',
			'label'     => __( 'Pagination Selector', 'search-filter' ),
			'help'      => __( 'Enter a CSS selector to target pagination links.  Allows dynamic update of results after clicking a pagination link.', 'search-filter' ),
			'group'     => 'results',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => '',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsPaginationType',
						'value'   => 'default',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		// Accessibility announcement settings (for screen readers).
		$live_search_depends_on = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'option'  => 'resultsDynamicUpdate',
					'value'   => 'yes',
					'compare' => '=',
				),
			),
		);

		$setting = array(
			'name'      => 'a11yLoadingText',
			'label'     => __( 'Loading Text', 'search-filter-pro' ),
			'help'      => __( 'Screen reader announcement when results are loading.', 'search-filter-pro' ),
			'group'     => 'accessibility',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => __( 'Loading results...', 'search-filter-pro' ),
			'dependsOn' => $live_search_depends_on,
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'a11yLoadingMoreText',
			'label'     => __( 'Loading More Text', 'search-filter-pro' ),
			'help'      => __( 'Screen reader announcement when loading more results.', 'search-filter-pro' ),
			'group'     => 'accessibility',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => __( 'Loading more results...', 'search-filter-pro' ),
			'dependsOn' => $live_search_depends_on,
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'a11yLoadedMoreText',
			'label'     => __( 'Loaded More Text', 'search-filter-pro' ),
			// translators: %1$d and %2$d are not placeholders but used to explain their usage - keep in tact.
			'help'      => __( 'Screen reader announcement after loading more. Use %1$d for current page and %2$d for total pages.', 'search-filter-pro' ),
			'group'     => 'accessibility',
			'type'      => 'string',
			'inputType' => 'Text',
			// Translators: %1$d is current page number, %2$d is total pages.
			'default'   => __( 'Loaded more results. Page %1$d of %2$d', 'search-filter-pro' ),
			'dependsOn' => $live_search_depends_on,
		);
		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'a11yErrorText',
			'label'     => __( 'Error Text', 'search-filter-pro' ),
			'help'      => __( 'Screen reader announcement when loading fails.', 'search-filter-pro' ),
			'group'     => 'accessibility',
			'type'      => 'string',
			'inputType' => 'Text',
			'default'   => __( 'Error loading results', 'search-filter-pro' ),
			'dependsOn' => $live_search_depends_on,
		);
		Queries_Settings::add_setting( $setting );
	}

	/**
	 * Upgrade the sort order setting.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting.
	 *
	 * @return array The setting.
	 */
	public static function update_query_container_settings_group( array $setting ) {

		$query_container_settings = array(
			'queryContainer',
			'queryContainerNotice',
		);

		if ( ! in_array( $setting['name'], $query_container_settings, true ) ) {
			return $setting;
		}

		// Move the query container setting + notice to the results group.
		$setting['group'] = 'results';

		if ( $setting['name'] === 'queryContainerNotice' ) {
			$setting['content'] = __( 'The results container must be set to use live search & accessibility features.', 'search-filter-pro' );
		}
		return $setting;
	}

	/**
	 * Register the settings for the spinner.
	 *
	 * @since    3.0.0
	 */
	public static function register_spinner_settings() {

		$setting = array(
			'name'        => 'spinnerScale',
			'label'       => __( 'Scale', 'search-filter' ),
			'help'        => __( 'Scale of the loading icon.', 'search-filter' ),
			'default'     => 3,
			'group'       => 'spinner',
			'subgroup'    => 'dimensions',
			'type'        => 'number',
			'inputType'   => 'Range',
			'placeholder' => __( 'Choose a scale', 'search-filter' ),
			'min'         => 1,
			'max'         => 10,
			'step'        => 1,
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'        => 'spinnerForegroundColor',
			'label'       => __( 'Color', 'search-filter' ),
			'group'       => 'spinner',
			'subgroup'    => 'color',
			'type'        => 'string',
			'inputType'   => 'ColorPicker',
			'enableAlpha' => true,
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'        => 'spinnerBackgroundColor',
			'label'       => __( 'Background color', 'search-filter' ),
			'group'       => 'spinner',
			'subgroup'    => 'color',
			'type'        => 'string',
			'inputType'   => 'ColorPicker',
			'enableAlpha' => true,
			'dependsOn'   => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'      => 'spinnerPosition',
			'label'     => __( 'Position', 'search-filter' ),
			'group'     => 'spinner',
			'subgroup'  => 'dimensions',
			'default'   => 'top center',
			'type'      => 'string',
			'inputType' => 'AlignmentMatrix',
			'dependsOn' => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'spinnerPadding',
			'label'      => __( 'Padding', 'search-filter' ),
			'group'      => 'spinner',
			'subgroup'   => 'dimensions',
			'default'    => array(
				'top'    => '12px',
				'right'  => '12px',
				'bottom' => '12px',
				'left'   => '12px',
			),
			'type'       => 'string',
			'inputType'  => 'Box',
			'allowReset' => false,
			'dependsOn'  => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'spinnerMargin',
			'label'      => __( 'Margin', 'search-filter' ),
			'group'      => 'spinner',
			'subgroup'   => 'dimensions',
			'default'    => array(
				'top'    => '12px',
				'right'  => '0px',
				'bottom' => '0px',
				'left'   => '0px',
			),
			'type'       => 'string',
			'inputType'  => 'Box',
			'allowReset' => false,
			'dependsOn'  => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'spinnerBorder',
			'label'      => __( 'Border', 'search-filter' ),
			'group'      => 'spinner',
			'subgroup'   => 'border',
			'default'    => '',
			'type'       => 'object',
			'inputType'  => 'BorderBox',
			'allowReset' => false,
			'dependsOn'  => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'spinnerBorderRadius',
			'label'      => __( 'Border Radius', 'search-filter' ),
			'group'      => 'spinner',
			'subgroup'   => 'border',
			'default'    => '4px',
			'type'       => 'string',
			'inputType'  => 'BorderRadius',
			'allowReset' => false,
			'dependsOn'  => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'resultsShowSpinner',
						'value'   => 'yes',
						'compare' => '=',
					),
					array(
						'option'  => 'resultsDynamicUpdate',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			),
		);

		Queries_Settings::add_setting( $setting );
	}

	/**
	 * Register the settings for the infinite scroll spinner.
	 *
	 * @since    3.0.0
	 */
	public static function register_infinite_scroll_spinner_settings() {

		$infinite_scroll_depends_on = array(
			'relation' => 'AND',
			'rules'    => array(
				array(
					'option'  => 'resultsDynamicUpdate',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'option'  => 'resultsPaginationType',
					'value'   => 'infinite_scroll',
					'compare' => '=',
				),
			),
		);

		$setting = array(
			'name'        => 'infiniteScrollSpinnerScale',
			'label'       => __( 'Scale', 'search-filter' ),
			'help'        => __( 'Scale of the loading icon.', 'search-filter' ),
			'default'     => 3,
			'group'       => 'infinite_scroll_spinner',
			'subgroup'    => 'dimensions',
			'type'        => 'number',
			'inputType'   => 'Range',
			'placeholder' => __( 'Choose a scale', 'search-filter' ),
			'min'         => 1,
			'max'         => 10,
			'step'        => 1,
			'dependsOn'   => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'        => 'infiniteScrollSpinnerForegroundColor',
			'label'       => __( 'Color', 'search-filter' ),
			'group'       => 'infinite_scroll_spinner',
			'subgroup'    => 'color',
			'type'        => 'string',
			'inputType'   => 'ColorPicker',
			'enableAlpha' => true,
			'dependsOn'   => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'        => 'infiniteScrollSpinnerBackgroundColor',
			'label'       => __( 'Background color', 'search-filter' ),
			'group'       => 'infinite_scroll_spinner',
			'subgroup'    => 'color',
			'type'        => 'string',
			'inputType'   => 'ColorPicker',
			'enableAlpha' => true,
			'dependsOn'   => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'infiniteScrollSpinnerPadding',
			'label'      => __( 'Padding', 'search-filter' ),
			'group'      => 'infinite_scroll_spinner',
			'subgroup'   => 'dimensions',
			'default'    => array(
				'top'    => '12px',
				'right'  => '12px',
				'bottom' => '12px',
				'left'   => '12px',
			),
			'type'       => 'string',
			'inputType'  => 'Box',
			'allowReset' => false,
			'dependsOn'  => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'infiniteScrollSpinnerMargin',
			'label'      => __( 'Margin', 'search-filter' ),
			'group'      => 'infinite_scroll_spinner',
			'subgroup'   => 'dimensions',
			'default'    => array(
				'top'    => '12px',
				'right'  => '0px',
				'bottom' => '12px',
				'left'   => '0px',
			),
			'type'       => 'string',
			'inputType'  => 'Box',
			'allowReset' => false,
			'dependsOn'  => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );

		$setting = array(
			'name'       => 'infiniteScrollSpinnerBorderRadius',
			'label'      => __( 'Border Radius', 'search-filter' ),
			'group'      => 'infinite_scroll_spinner',
			'subgroup'   => 'border',
			'default'    => '4px',
			'type'       => 'string',
			'inputType'  => 'BorderRadius',
			'allowReset' => false,
			'dependsOn'  => $infinite_scroll_depends_on,
		);

		Queries_Settings::add_setting( $setting );
	}

	/**
	 * Create the spinner CSS vars from attributes.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $css            The CSS.
	 * @param    array  $attributes     The attributes.
	 * @return   string
	 */
	public static function create_attributes_css( $css, $attributes ) {
		$mapped_css_vars = array(
			'--search-filter-spinner-foreground-color' => array(
				'key'               => 'spinnerForegroundColor',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_hex_color',
			),
			'--search-filter-spinner-background-color' => array(
				'key'               => 'spinnerBackgroundColor',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_hex_color',
			),
			'--search-filter-spinner-scale'            => array(
				'key'               => 'spinnerScale',
				'sanitize_callback' => 'absint',
			),
			'--search-filter-spinner-margin'           => array(
				'key'               => 'spinnerMargin',
				'sanitize_callback' => 'Search_Filter\\Util::sanitize_css_box',
			),
			'--search-filter-spinner-padding'          => array(
				'key'               => 'spinnerPadding',
				'sanitize_callback' => 'Search_Filter\\Util::sanitize_css_box',
			),
			'--search-filter-spinner-h-position'       => array(
				'key'               => 'spinnerPosition',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_horizontal_position',
			),
			'--search-filter-spinner-v-position'       => array(
				'key'               => 'spinnerPosition',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitze_vertical_position',
			),
			'--search-filter-spinner-border-width'     => array(
				'key'               => 'spinnerBorder',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_border_width',
			),
			'--search-filter-spinner-border-style'     => array(
				'key'               => 'spinnerBorder',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::santize_border_style',
			),
			'--search-filter-spinner-border-color'     => array(
				'key'               => 'spinnerBorder',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::santize_border_color',
			),
			'--search-filter-spinner-border-radius'    => array(
				'key'               => 'spinnerBorderRadius',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::santize_border_radius',
			),
			// Infinite scroll spinner CSS vars.
			'--search-filter-infinite-scroll-spinner-foreground-color' => array(
				'key'               => 'infiniteScrollSpinnerForegroundColor',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_hex_color',
			),
			'--search-filter-infinite-scroll-spinner-background-color' => array(
				'key'               => 'infiniteScrollSpinnerBackgroundColor',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::sanitize_hex_color',
			),
			'--search-filter-infinite-scroll-spinner-scale' => array(
				'key'               => 'infiniteScrollSpinnerScale',
				'sanitize_callback' => 'absint',
			),
			'--search-filter-infinite-scroll-spinner-margin' => array(
				'key'               => 'infiniteScrollSpinnerMargin',
				'sanitize_callback' => 'Search_Filter\\Util::sanitize_css_box',
			),
			'--search-filter-infinite-scroll-spinner-padding' => array(
				'key'               => 'infiniteScrollSpinnerPadding',
				'sanitize_callback' => 'Search_Filter\\Util::sanitize_css_box',
			),
			'--search-filter-infinite-scroll-spinner-border-radius' => array(
				'key'               => 'infiniteScrollSpinnerBorderRadius',
				'sanitize_callback' => 'Search_Filter_Pro\\Queries::santize_border_radius',
			),
		);

		foreach ( $mapped_css_vars as $css_var => $attribute ) {
			$attribute_key               = $attribute['key'];
			$attribute_sanitize_callback = $attribute['sanitize_callback'];

			if ( ! isset( $attributes[ $attribute_key ] ) ) {
				continue;
			}

			$css .= $css_var . ':' . call_user_func( $attribute_sanitize_callback, $attributes[ $attribute_key ] );
			$css .= ';';
		}
		return $css;
	}

	/**
	 * Filter the query attributes and set the scroll selector.
	 *
	 * @since    3.0.0
	 *
	 * @param    array  $attributes   The attributes to set the scroll to.
	 * @param    string $record_type  The record type.
	 * @param    Query  $record       The record.
	 * @return   array    The attributes with the scroll to set.
	 */
	public static function set_scroll_to_attributes( $attributes, $record_type, $record ) {
		if ( $record_type !== 'query' ) {
			return $attributes;
		}

		// Generate the css selector for scroll to option.
		$scroll_to_selector = '';

		if ( ! isset( $attributes['resultsScrollTo'] ) ) {
			return $attributes;
		}

		$scroll_parts = explode( '-', $attributes['resultsScrollTo'], 2 );

		if ( count( $scroll_parts ) !== 2 ) {
			return $attributes;
		}

		if ( $scroll_parts[0] === 'top' ) {
			$scroll_to_selector = 'body';
		} elseif ( $scroll_parts[0] === 'query' ) {
			$scroll_to_selector = '.search-filter-query--id-' . absint( $record->get_id() );
		} elseif ( $scroll_parts[0] === 'field' ) {
			$scroll_to_selector = '.search-filter-field--id-' . absint( $scroll_parts[1] );
		} elseif ( $scroll_parts[0] === 'custom' ) {
			$scroll_to_selector = $scroll_parts[1];
		}

		$attributes['resultsScrollToSelector'] = $scroll_to_selector;
		return $attributes;
	}

	/**
	 * Filter the query attributes and set the `resultsUpdatePage` attribute.
	 *
	 * @since    3.0.0
	 *
	 * @param    array  $attributes   The attributes to set the scroll to.
	 * @param    string $record_type  The record type.
	 * @return   array    The attributes with the scroll to set.
	 */
	public static function set_results_update_page_attributes( $attributes, $record_type ) {
		if ( $record_type !== 'query' ) {
			return $attributes;
		}

		// Only set the attribute if live search is enabled.
		$live_search = isset( $attributes['resultsDynamicUpdate'] ) && $attributes['resultsDynamicUpdate'] === 'yes';
		if ( $live_search && ! isset( $attributes['resultsUpdatePage'] ) ) {
			$attributes['resultsUpdatePage'] = 'yes';
		}

		return $attributes;
	}

	/**
	 * Add the meta query to the query args.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $query_args    The query args.
	 * @param    Query $query         The query instance.
	 * @return   array
	 */
	public static function add_meta_query( $query_args, $query ) {

		$attributes = $query->get_attributes();

		if ( ! isset( $attributes['metaQuery'] ) ) {
			return $query_args;
		}

		$meta_query = $attributes['metaQuery'];

		if ( count( $meta_query ) === 0 ) {
			return $query_args;
		}

		$allowed_comparisons = array(
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'BETWEEN',
			'NOT BETWEEN',
			'EXISTS',
			'NOT EXISTS',
			'REGEXP',
			'NOT REGEXP',
			'RLIKE',
		);

		$allowed_types = array(
			'NUMERIC',
			'BINARY',
			'CHAR',
			'DATE',
			'DATETIME',
			'DECIMAL',
			'SIGNED',
			'TIME',
			'UNSIGNED',
		);

		$valid_queries = 0;
		$meta_queries  = array();

		foreach ( $meta_query as $meta_query_item ) {
			if ( empty( $meta_query_item ) ) {
				continue;
			}
			if ( ! isset( $meta_query_item['key'] ) ) {
				continue;
			}
			if ( $meta_query_item['key'] === '' ) {
				continue;
			}
			if ( ! in_array( $meta_query_item['compare'], $allowed_comparisons, true ) ) {
				continue;
			}
			if ( ! in_array( $meta_query_item['type'], $allowed_types, true ) ) {
				continue;
			}

			++$valid_queries;
			$new_item       = array(
				'key'     => sanitize_text_field( $meta_query_item['key'] ),
				'value'   => sanitize_text_field( $meta_query_item['value'] ),
				'compare' => $meta_query_item['compare'],
				'type'    => $meta_query_item['type'],
			);
			$meta_queries[] = $new_item;
		}

		// If there were no valid rules return early.
		if ( $valid_queries === 0 ) {
			return $query_args;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		if ( ! isset( $query_args['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query_args['meta_query'] = array(
				'relation' => 'AND',
			);
		}
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_queries );

		return $query_args;
	}

	/**
	 * Get the horizontal position.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $value    The value.
	 * @return   string
	 */
	public static function sanitize_horizontal_position( $value ) {
		// Value will likely be in the format: "top right"
		// We want to return just the horizontal value but also
		// map it to the flex value.
		$map = array(
			'left'   => 'flex-start',
			'right'  => 'flex-end',
			'center' => 'center',
		);
		if ( trim( $value ) === '' ) {
			return '';
		}

		// Split the value.
		$parts = explode( ' ', $value );
		if ( count( $parts ) !== 2 ) {
			return '';
		}

		$horizontal = $parts[1];
		if ( ! isset( $map[ $horizontal ] ) ) {
			return '';
		}

		return $map[ $horizontal ];
	}

	/**
	 * Sanitize a hex color.
	 *
	 * This is almost the same as the WP `sanitize_hex_color` function
	 * but it allows for 4 or 8 digit hex colors with alpha.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $value    The input hex value.
	 * @return   string
	 */
	public static function sanitize_hex_color( $value ) {
		if ( '' === $value ) {
			return '';
		}
		// 3 or 6 digits standard hex color.
		if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $value ) ) {
			return $value;
		}
		// 4 or 8 digits with alpha.
		if ( preg_match( '|^#([A-Fa-f0-9]{4}){1,2}$|', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Sanitize a CSS var.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $value    The value.
	 * @return   string
	 */
	private static function sanitize_css_var( $value ) {
		if ( '' === $value ) {
			return '';
		}
		return preg_replace( '/[^a-zA-Z0-9\#_.-]/', '', $value );
	}

	/**
	 * Get the vertical position.
	 *
	 * @since    3.0.0
	 *
	 * @param    string $value    The value.
	 * @return   string
	 */
	public static function sanitze_vertical_position( $value ) {
		// Value will likely be in the format: "top right"
		// We want to return just the vertical value but also
		// map it to the flex value.
		$map = array(
			'top'    => 'flex-start',
			'bottom' => 'flex-end',
			'center' => 'center',
		);
		if ( trim( $value ) === '' ) {
			return '';
		}
		// Split the value.
		$parts = explode( ' ', $value );
		if ( count( $parts ) !== 2 ) {
			return '';
		}

		$vertical = $parts[0];
		if ( ! isset( $map[ $vertical ] ) ) {
			return '';
		}

		return $map[ $vertical ];
	}

	/**
	 * Check if a border is a single value.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $value    The value.
	 * @return   boolean
	 */
	private static function border_is_single( $value ) {
		if ( ! isset( $value['top'] ) && ! isset( $value['right'] ) && ! isset( $value['bottom'] ) && ! isset( $value['left'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sanitize the border width.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $value    The value.
	 * @return   string
	 */
	public static function sanitize_border_width( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( self::border_is_single( $value ) ) {
			return self::sanitize_css_var( $value['width'] );
		} else {
			return self::sanitize_css_var( $value['top']['width'] ) . ' ' . self::sanitize_css_var( $value['right']['width'] ) . ' ' . self::sanitize_css_var( $value['bottom']['width'] ) . ' ' . self::sanitize_css_var( $value['left']['width'] );
		}
	}

	/**
	 * Sanitize the border style.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $value    The value.
	 * @return   string
	 */
	public static function santize_border_style( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( self::border_is_single( $value ) ) {
			return self::sanitize_css_var( $value['style'] );
		} else {
			return self::sanitize_css_var( $value['top']['style'] ) . ' ' . self::sanitize_css_var( $value['right']['style'] ) . ' ' . self::sanitize_css_var( $value['bottom']['style'] ) . ' ' . self::sanitize_css_var( $value['left']['style'] );
		}
	}

	/**
	 * Sanitize the border color.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $value    The value.
	 * @return   string
	 */
	public static function santize_border_color( $value ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( self::border_is_single( $value ) ) {
			return self::sanitize_css_var( $value['color'] );
		} else {
			return self::sanitize_css_var( $value['top']['color'] ) . ' ' . self::sanitize_css_var( $value['right']['color'] ) . ' ' . self::sanitize_css_var( $value['bottom']['color'] ) . ' ' . self::sanitize_css_var( $value['left']['color'] );
		}
	}

	/**
	 * Sanitize the border radius.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $value    The value.
	 * @return   string
	 */
	public static function santize_border_radius( $value ) {
		if ( is_string( $value ) ) {
			return self::sanitize_css_var( $value );
		} elseif ( is_array( $value ) ) {
			if ( isset( $value['topLeft'] ) && isset( $value['topRight'] ) && isset( $value['bottomLeft'] ) && isset( $value['bottomRight'] ) ) {
				return self::sanitize_css_var( $value['topLeft'] ) . ' ' . self::sanitize_css_var( $value['topRight'] ) . ' ' . self::sanitize_css_var( $value['bottomLeft'] ) . ' ' . self::sanitize_css_var( $value['bottomRight'] );
			}
		}
		return '';
	}

	/**
	 * Add the single integrations.
	 *
	 * @since    3.0.0
	 */
	public static function add_single_integrations() {
		// Get the object for the data_type setting for its options.
		$integration_type_setting = Queries_Settings::get_setting( 'queryIntegration' );
		if ( ! $integration_type_setting ) {
			return;
		}

		// Hide the "pro" message from the single integration type setting.
		$integration_type_setting->update(
			array(
				'help' => null,
			)
		);

		// Add custom display method.
		$custom_integration_type_option = array(
			'label'     => __( 'Custom', 'search-filter' ),
			'value'     => 'custom',
			'dependsOn' => array(
				'relation' => 'OR',
				'rules'    => array(
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'single',
					),
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'archive',
					),
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'search',
					),
					array(
						'option'  => 'integrationType',
						'compare' => '=',
						'value'   => 'dynamic',
					),
				),
			),
		);
		$integration_type_setting->add_option( $custom_integration_type_option );
	}
	/**
	 * Upgrade the sort order setting.
	 *
	 * @since    3.0.0
	 */
	public static function upgrade_sort_order() {
		// Get the object for the data_type setting for its options.
		$sort_order_setting = Queries_Settings::get_setting( 'sortOrder' );

		if ( ! $sort_order_setting ) {
			return;
		}

		$custom_field_option = array(
			'label' => __( 'Custom Field', 'search-filter' ),
			'value' => 'custom_field',
		);
		$sort_order_setting->add_option( $custom_field_option );
	}
}
