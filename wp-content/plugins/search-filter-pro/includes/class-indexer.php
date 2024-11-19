<?php
/**
 * The main indexer class.
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 */

namespace Search_Filter_Pro;

use Search_Filter\Core\Notices;
use Search_Filter\Features;
use Search_Filter\Fields\Field;
use Search_Filter\Options;
use Search_Filter\Queries\Query as Search_Filter_Query;
use Search_Filter_Pro\Task_Runner\Task;
use Search_Filter_Pro\Indexer\Database\Index_Query;
use Search_Filter_Pro\Indexer\Database\Index_Table;
use Search_Filter_Pro\Indexer\Field_Queries;
use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Query_Cache;
use Search_Filter_Pro\Indexer\Query_Store;
use Search_Filter_Pro\Indexer\Rest_API;
use Search_Filter_Pro\Indexer\Settings_Data;
use Search_Filter_Pro\Indexer\Settings as Indexer_Settings;
use Search_Filter_Pro\Task_Runner\Database\Tasks_Query;
use Search_Filter\Fields\Settings as Fields_Settings;
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main indexer class.
 */
final class Indexer extends Task_Runner {

	/**
	 * The task type for the task runner.
	 *
	 * @var string
	 */
	protected static $type = 'indexer';

	/**
	 * The size of the page when fetching posts to index.
	 *
	 * @var string
	 */
	private static $query_page_size = 200;

	/**
	 * The queue of posts to resync.
	 *
	 * @var array
	 */
	private static $resync_queue = array();

	/**
	 * The post types we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_post_types = array();
	/**
	 * The post stati we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_post_stati = array();
	/**
	 * The post stati for each post type we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_post_stati_matrix = array();

	/**
	 * The fields that need to be indexed for each post type.
	 *
	 * @var array
	 */
	private static $indexed_fields_by_post_type = array();

	/**
	 * The queries we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_queries = array();

	/**
	 * The queries we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_queries_by_post_type = array();

	/**
	 * The queries we're indexing.
	 *
	 * @var array
	 */
	private static $indexed_fields = array();

	/**
	 * Has init
	 *
	 * @var bool
	 */
	private static $has_init_sync_data = false;

	/**
	 * The queue of tasks to run.
	 *
	 * @var array
	 */
	protected static $tasks = array();

	/**
	 * Strings for messaging.
	 *
	 * @since    3.0.0
	 *
	 * @var      array    $strings
	 */
	private static $strings = array();

	/**
	 * Option name for indexer progress.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private static $progress_data_option_name = 'indexer-progress';

	/**
	 * Init the indexer.
	 *
	 * @since    3.0.0
	 */
	public static function init() {

		// Init strings.
		self::$strings = array(
			'indexer_error' => __( 'There has been an issue with the indexing process. Check the error log for more information.', 'search-filter-pro' ),
		);

		// Make sure we load after the indexer setting is added (in the features class), so use priority 11.
		add_action( 'search-filter/settings/register/features', array( __CLASS__, 'load_indexer' ), 11 );
		add_action( 'search-filter/settings/register/fields', array( __CLASS__, 'add_count_to_order_field' ), 11 );

		// Add support for orderby setting for post attribute fields.
		add_filter( 'search-filter/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );
	}

	/**
	 * Load the indexer.
	 *
	 * @since 3.0.0
	 */
	public static function load_indexer() {
		if ( ! Features::is_enabled( 'indexer' ) ) {
			return;
		}

		Indexer\Cron::init();

		// Register indexer sub settings.
		Indexer_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );
		do_action( 'search-filter/settings/register/indexer' );

		// Preload indexer settings paths.
		add_action( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );

		// Init REST API endpoints.
		Rest_API::init();

		// Handles field queries, counts etc.
		Field_Queries::init();

		// Init the query cache.
		Query_Cache::init();

		// Reset the objects count when tasks have finished.
		add_action( 'search-filter/task_runner/finished', array( __CLASS__, 'finish_task' ) );

		// Prevent running queries as a WP_Query when the indexer is enabled.
		add_filter( 'search-filter/query/is_wp_query', array( __CLASS__, 'remove_wp_query' ), 1, 2 );

		// Build the indexer posts query.
		add_action( 'pre_get_posts', array( __CLASS__, 'build_posts_query' ), 100, 1 );

		// Detect wp data changes to keep the index in sync.
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 100, 1 );
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), 10, 1 );
		add_action( 'attachment_added', array( __CLASS__, 'attachment_added' ), 20, 1 );
		add_action( 'attachment_updated', array( __CLASS__, 'attachment_updated' ), 20, 3 );
		// add_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), 20, 1 ); // TODO - we don't know what type of object it is.
		add_action( 'added_post_meta', array( __CLASS__, 'changed_post_meta' ), 10, 2 );
		add_action( 'updated_post_meta', array( __CLASS__, 'changed_post_meta' ), 10, 2 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'changed_post_meta' ), 10, 2 );
		// add_action( 'added_term_relationship', array( __CLASS__, 'added_term_relationship' ), 10, 1 );
		// add_action( 'deleted_term_relationship', array( __CLASS__, 'deleted_term_relationship' ), 10, 1 );

		add_action( 'shutdown', array( __CLASS__, 'resync_queue' ), 100 );

		// Check for errors and display notices.
		add_action( 'init', array( __CLASS__, 'add_hooks' ), 10 );
	}


	public static function add_count_to_order_field() {
		// Add the count to order field.
		// Add the author option to the dataPostAttribute setting.
		$options_order_setting = Fields_Settings::get_setting( 'inputOptionsOrder' );

		if ( ! $options_order_setting ) {
			return;
		}

		$count_option = array(
			'label'     => __( 'Count', 'search-filter' ),
			'value'     => 'count',
			'dependsOn' => array(
				'relation' => 'AND',
				'action'   => 'hide',
				'rules'    => array(
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'value'   => 'yes',
						'compare' => '=',
					),
					// TODO - remove the rule, so we can support this in search fields.
					array(
						'option'  => 'type',
						'value'   => 'choice',
						'compare' => '=',
					),
				),
			),
		);
		$options_order_setting->add_option( $count_option, array( 'position' => 'last' ) );

		// Now update the setting so it knows it has options with dependencies.
		$setting_data = $options_order_setting->get_data();

		// Enable dependant options for the "dataPostAttribute" setting.
		if ( ! isset( $setting_data['supports'] ) ) {
			$setting_data['supports'] = array();
		}
		$setting_data['supports']['dependantOptions'] = true;
		$options_order_setting->update( $setting_data );
	}


	public static function get_field_setting_support( $setting_support, $type, $input_type ) {

		// Add show count + hide empty to choice fields, for indexed queries.
		$order_options_matrix = array(
			'choice' => array( 'select', 'radio', 'checkbox', 'button' ),
			'search' => array( 'autocomplete' ),
		);
		// Build conditions for non taxonomy options.
		if ( isset( $order_options_matrix[ $type ] ) && in_array( $input_type, $order_options_matrix[ $type ], true ) ) {

			$order_options_conditions = array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'dataType',
						'value'   => 'post_attribute',
						'compare' => '=',
					),
					array(
						'option'  => 'dataPostAttribute',
						'value'   => 'default',
						'compare' => '!=',
					),
					array(
						'store'   => 'query',
						'option'  => 'useIndexer',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			);

			$setting_support['inputOptionsOrder'] = array(
				'conditions' => Field::add_setting_support_condition( $setting_support, 'inputOptionsOrder', $order_options_conditions, false ),
			);
		}

		return $setting_support;

	}
	/**
	 * Add the preload API paths.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $paths    The paths to add.
	 * @return   array    The paths to add.
	 */
	public static function add_preload_api_paths( $paths ) {
		$paths[] = '/search-filter/v1/admin/settings?section=indexer';
		$paths[] = '/search-filter/v1/settings?section=indexer';
		$paths[] = '/search-filter-pro/v1/license'; // TODO - license should't be extended in the indexer class.
		// $paths[] = '/search-filter-pro/v1/indexer'; // This can sometimes be an expensive call.
		return $paths;
	}

	/**
	 * Add hooks.
	 *
	 * @since    3.0.0
	 */
	public static function add_hooks() {
		add_action( 'search-filter/core/notices/get_notices', array( __CLASS__, 'add_notices' ), 10 );
	}

	/**
	 * Init the admin hooks & messages.
	 */
	public static function add_notices() {
		if ( ! self::has_reached_error_limit() ) {
			return;
		}
		// Display a message to the user if there are any issues with the task runner.
		Notices::add_notice( self::$strings['indexer_error'], 'error', 'search-filter-pro-indexer-error' );
	}

	/**
	 * On save post schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function save_post( $post_id ) {
		self::resync_post( $post_id );

	}
	/**
	 * On attachment added schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function attachment_added( $post_id ) {
		self::resync_post( $post_id );

	}

	/**
	 * On attachment updated schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    array   $form_fields    The form fields.
	 * @param    WP_Post $post_before    The post before.
	 * @param    WP_Post $post_after    The post after.
	 */
	public static function attachment_updated( $form_fields, $post_before, $post_after ) {
		self::resync_post( $post_after->ID );
	}

	/**
	 * On set object terms schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function set_object_terms( $post_id ) {
		self::resync_post( $post_id );
	}

	/**
	 * When post meta data is added/changed/deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function changed_post_meta( $meta, $post_id ) {
		self::resync_post( $post_id );
	}
	/**
	 * When post meta data is added/changed/deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function changed_post_terms( $post_id ) {
		self::resync_post( $post_id );
	}

	/**
	 * On delete post schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to delete.
	 */
	public static function delete_post( $post_id ) {
		// TODO - remove the post from the index.
		self::remove_post( $post_id );
	}

	/**
	 * Init the sync data.
	 *
	 * @since    3.0.0
	 */
	public static function maybe_init_sync_data() {

		if ( self::$has_init_sync_data ) {
			return;
		}

		self::$has_init_sync_data           = true;
		self::$indexed_post_types           = array();
		self::$indexed_queries              = array();
		self::$indexed_queries_by_post_type = array();

		/*
		 * Check to make sure we are tracking this post type, by looping through
		 * queries and cross checking their post types.
		 *
		 * TODO - we should keep the unique post types being indexed in a separate table
		 * so we can do a quicker check.
		 */
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
				'status' => 'enabled',
			)
		);

		$post_types_to_index = array();
		// Track which post stati we're index and for which post types.
		$indexed_post_stati = array();

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			if ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				continue;
			}

			$query_fields = $query->get_fields();

			self::$indexed_queries[ $query->get_id() ] = $query;

			$post_types_to_index = array_merge( $post_types_to_index, $query->get_attribute( 'postTypes' ) );

			foreach ( $post_types_to_index as $post_type ) {
				// Map the post stati to the post type.
				if ( ! isset( self::$indexed_post_stati_matrix[ $post_type ] ) ) {
					self::$indexed_post_stati_matrix[ $post_type ] = array();
				}
				self::$indexed_post_stati_matrix[ $post_type ] = array_unique( array_merge( self::$indexed_post_stati_matrix[ $post_type ], $query->get_attribute( 'postStatus' ) ) );

				// Map the queries to the post type.
				if ( ! isset( self::$indexed_queries_by_post_type[ $post_type ] ) ) {
					self::$indexed_queries_by_post_type[ $post_type ] = array();
				}
				self::$indexed_queries_by_post_type[ $post_type ][] = $query;

				// Map the fields to the post type.
				if ( ! isset( self::$indexed_fields_by_post_type[ $post_type ] ) ) {
					self::$indexed_fields_by_post_type[ $post_type ] = array();
				}
				self::$indexed_fields_by_post_type[ $post_type ] = array_merge( self::$indexed_fields_by_post_type[ $post_type ], $query_fields );
			}

			$indexed_post_stati = array_merge( $indexed_post_stati, $query->get_attribute( 'postStatus' ) );

			$fields = $query->get_fields();
			foreach ( $fields as $field ) {
				self::$indexed_fields[] = $field;
			}
		}
		self::$indexed_post_types = array_unique( $post_types_to_index );
		self::$indexed_post_stati = array_unique( $indexed_post_stati );
	}


	/**
	 * Get the indexed post types.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The indexed post types.
	 */
	public static function get_indexed_post_types() {
		self::maybe_init_sync_data();
		return self::$indexed_post_types;
	}

	/**
	 * Get the individual object indexed count.
	 *
	 * @since 3.0.0
	 *
	 * @return int    The number of objects in the count result.
	 */
	public static function get_indexed_objects_count() {

		// Add the filter to do a custom select.
		add_filter( 'search_filter_index_rows_query_clauses', array( __CLASS__, 'update_index_query_unique_count_clauses' ) );

		$query = new Index_Query(
			array(
				// Need to set this to count so the query is handled a count
				// query in BerlinDB.
				'count'  => true,
				'number' => -1,
			)
		);

		// Remove the filter after the query is run.
		remove_filter( 'search_filter_index_rows_query_clauses', array( __CLASS__, 'update_index_query_unique_count_clauses' ) );

		// Items contains the count of the objects.
		return $query->items;
	}

	/**
	 * To avoid big queries, better we update the SQL to select distinct columns.
	 *
	 * @since 3.0.0
	 *
	 * @param array $clauses The clauses to update.
	 * @return array    The updated clauses.
	 */
	public static function update_index_query_unique_count_clauses( $clauses ) {
		// Make sure we use distinct on the object ID to only count unique object_ids.
		$clauses['fields'] = 'COUNT( DISTINCT object_id ) as COUNT';
		return $clauses;
	}

	/**
	 * Get the number of rows in the index.
	 *
	 * @since 3.0.0
	 *
	 * @return int    The number of rows in the index.
	 */
	public static function get_indexed_rows_count() {
		$query = new Index_Query(
			array(
				// Need to set this to count so the query is handled as a count
				// query in BerlinDB.
				'count'  => true,
				'number' => -1,
			)
		);

		// Items contains the count of the objects.
		return $query->items;
	}

	/**
	 * Check if a post should be resynced.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $post_id    The post ID to check.
	 * @return   bool             True if the post should be resynced.
	 */
	public static function should_resync( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		self::maybe_init_sync_data();

		$post_type = $post->post_type;

		if ( ! in_array( $post_type, self::$indexed_post_types, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Resync of the index for a particular post.
	 *
	 * @since    3.0.0
	 *
	 * @param    int   $post_id           The post ID to resync.
	 * @param    array $args              Additional arguments, such as specific fields to sync.
	 * @param    bool  $run_immediately   Whether to run the sync immediately or not.
	 */
	public static function resync_post( $post_id, $args = array(), $run_immediately = false ) {
		if ( ! self::should_resync( $post_id ) ) {
			return;
		}
		if ( ! $run_immediately ) {
			array_push( self::$resync_queue, array( $post_id, $args ) );
		} else {
			self::process_post_sync( $post_id, $args );
		}
	}

	/**
	 * Remove a post from the index.
	 *
	 * @since    3.0.0
	 *
	 * @param    int   $post_id           The post ID to resync.
	 * @param    array $args              Additional arguments, such as specific fields to sync.
	 */
	public static function remove_post( $post_id ) {
		if ( ! self::should_resync( $post_id ) ) {
			return;
		}

		$query = new Index_Query();
		// TODO - we need to add the object type to the index.
		// Delete the items from the index.
		$query->delete_items( array( 'object_id' => $post_id ) );

		// Figure out which caches to clear base on the post type.
		$post_type = get_post_type( $post_id );
		self::clear_caches_by_post_type( $post_type );
	}

	/**
	 * Clear the caches for a post type.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type    The post type to clear the caches for.
	 */
	private static function clear_caches_by_post_type( $post_type ) {

		// Init sync data so we know which queries are connected to the post type.
		self::maybe_init_sync_data();

		// Clear any cached results for the query.
		if ( ! isset( self::$indexed_queries_by_post_type[ $post_type ] ) ) {
			return;
		}

		foreach ( self::$indexed_queries_by_post_type[ $post_type ] as $query ) {
			Query_Cache::delete_items(
				array(
					'query_id' => $query->get_id(),
				)
			);
		}
	}



	/**
	 * Resync any posts in the queue.
	 *
	 * @since    3.0.0
	 */
	public static function resync_queue() {

		if ( empty( self::$resync_queue ) ) {
			return;
		}

		$sync_items         = self::$resync_queue;
		self::$resync_queue = array();
		foreach ( $sync_items as $sync_item ) {
			self::process_post_sync( $sync_item[0], $sync_item[1] );
		}
	}

	/**
	 * Resync a post.
	 *
	 * @since    3.0.0
	 *
	 * @param    int   $post_id    The post ID to resync.
	 * @param    array $args       Additional arguments to pass to the sync.
	 *                             Currently 'fields' and 'action' is supported.
	 */
	private static function process_post_sync( $post_id, $args = array() ) {

		wp_using_ext_object_cache( false );

		$post = get_post( $post_id );
		if ( ! $post ) {
			// Then remove any data for this post.
			return;
		}

		/**
		 * Get the fields to sync, if '$args['fields']' is not set, then
		 * sync all the fields that are connected to the post type.
		 */
		$fields_to_sync = self::$indexed_fields_by_post_type[ $post->post_type ];
		if ( isset( $args['fields'] ) ) {
			$fields_to_sync = $args['fields'];
		}

		// absint will convert the false value to 0 in the event of an issue.
		$post_parent_id = absint( wp_get_post_parent_id( $post_id ) );

		$query_ids = array();
		// Loop through the fields synced to the post.
		foreach ( $fields_to_sync as $field ) {
			// Then get the new sync data for this field.
			self::sync_field_index( $field, $post_id, $post_parent_id );
			// Track which connected queries have been updated.
			$query_ids[] = $field->get_query_id();
		}

		// Clear the caches for the associated queries.
		foreach ( $query_ids as $query_id ) {
			Query_Cache::clear_caches_by_query_id( $query_id );
		}
	}

	/**
	 * Clear the index for a field.
	 *
	 * @since    3.0.0
	 *
	 * @param    Field $field    The field to clear the index for.
	 * @param    int   $object_id  The post ID to clear the index for.
	 * @param    int   $object_parent_id  The post parent ID to clear the index for.
	 */
	public static function clear_field_index( $field, $object_id = -1, $object_parent_id = -1 ) {
		$query = new Index_Query();

		$delete_where = array(
			'field_id' => $field->get_id(),
		);
		if ( $object_id !== -1 ) {
			$delete_where['object_id'] = $object_id;
		}
		if ( $object_parent_id !== -1 ) {
			$delete_where['object_parent_id'] = $object_parent_id;
		}

		$query->delete_items( $delete_where );
	}

	/**
	 * Sync the index for a field + post.
	 *
	 * @since    3.0.0
	 *
	 * @param    Field $field             The field to sync.
	 * @param    int   $object_id         The object ID to sync.
	 * @param    int   $object_parent_id  The object parent ID to sync.
	 */
	private static function sync_field_index( $field, $object_id, $object_parent_id = 0 ) {

		// Now clear them as we'll rebuild them.
		self::clear_field_index( $field, $object_id );

		$item_attributes = array(
			'object_id'        => $object_id,
			'object_parent_id' => $object_parent_id,
			'field_id'         => $field->get_id(),
		);

		// Allow the build of the index items to be short circuited.
		$values = apply_filters( 'search-filter/indexer/sync_field_index/override_values', null, $field, $object_id );

		// If we get an array instead of null, then it's an override.
		if ( is_array( $values ) ) {

			// Build items from the values.
			$items = self::build_index_items_from_values( $item_attributes, $values );

			// Add the items to the DB.
			self::add_items_to_index( $items );

			// Clear any caches related to this field - make sure this is after
			// the items have been added to the index in case of collisions.
			Query_Cache::clear_caches_by_field_id( $field->get_id() );

			return;
		}

		// The index items to add to the DB.
		$values = array();

		$type = $field->get_attribute( 'type' );
		if ( $type === 'search' || $type === 'control' ) {
			// Don't do anything with search or control.
			return;
		}

		// Get the options.
		$data_type = $field->get_attribute( 'dataType' );

		if ( $data_type === 'taxonomy' ) {
			// Generate taxonomy insert data.
			$values = self::get_post_taxonomy_values( $object_id, $field->get_attribute( 'dataTaxonomy' ) );

		} elseif ( $data_type === 'post_attribute' ) {

			$attribute_data_type = $field->get_attribute( 'dataPostAttribute' );

			if ( $attribute_data_type === 'post_type' ) {
				// Generate post type insert data.
				$values = self::get_post_type_values( $object_id );

			} elseif ( $attribute_data_type === 'post_status' ) {
				// Generate post status insert data.
				$values = self::get_post_status_values( $object_id );

			} elseif ( $attribute_data_type === 'post_author' ) {
				// Generate post status insert data.
				$values = self::get_post_author_values( $object_id );
			}
		} elseif ( $data_type === 'custom_field' ) {
			$values = self::get_post_custom_field_values( $object_id, $field->get_attribute( 'dataCustomField' ) );
		}

		// Build items from the values.
		$items = self::build_index_items_from_values( $item_attributes, $values );

		// Add the items.
		self::add_items_to_index( $items );

		// Clear any caches related to this field - make sure this is after
		// the items have been added to the index in case of collisions.
		Query_Cache::clear_caches_by_field_id( $field->get_id() );
	}

	/**
	 * Build the index items from the values.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $item_attributes    The item attributes to get the data for.
	 * @param    array $values    The values to get the index items for.
	 * @return   array    The index items for the values.
	 */
	private static function build_index_items_from_values( $item_attributes, $values ) {
		$items = array();
		foreach ( $values as $value ) {
			$items[] = self::build_index_item_with_value( $item_attributes, $value );
		}
		return $items;
	}

	/**
	 * Get the index items for a field + post.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $field_id    The field ID to get the data for.
	 * @param    int $object_id    The object ID to get the data for.
	 * @return   array    The index items for the field + post.
	 */
	public static function get_field_index_items( $field_id, $object_id = -1 ) {
		$query_args = array(
			'field_id' => $field_id,
		);
		if ( $object_id !== -1 ) {
			$query_args['object_id'] = $object_id;
		}
		$query = new Index_Query( $query_args );

		if ( is_wp_error( $query ) ) {
			return array();
		}
		return $query->items;
	}

	/**
	 * Get the index values for a post taxonomy.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $post_id    The post ID to get the data for.
	 * @param    string $taxonomy_name    The taxonomy name to get the data for.
	 * @return   array    The index values for the post taxonomy.
	 */
	private static function get_post_taxonomy_values( $post_id, $taxonomy_name ) {
		$terms = get_the_terms( $post_id, $taxonomy_name );
		if ( is_wp_error( $terms ) || $terms === false ) {
			return array();
		}

		$values = array();
		foreach ( $terms as $term ) {
			// Add each term to the item.
			$values[] = $term->slug;

			// Loop through and attach all parents they exist.
			$parent_id = $term->parent;
			while ( $parent_id !== 0 ) {
				$parent_term = get_term( $parent_id, $taxonomy_name );
				$parent_id   = $parent_term->parent;
				if ( ! in_array( $parent_term->slug, $values, true ) ) {
					$values[] = $parent_term->slug;
				}
			}
		}
		return $values;
	}


	/**
	 * Get the index values for a posts post type.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post type.
	 */
	private static function get_post_type_values( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( $post_type === false ) {
			return array();
		}
		return array( $post_type );
	}

	/**
	 * Get the index values for a posts post status.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post status.
	 */
	private static function get_post_status_values( $post_id ) {
		$post_status = get_post_status( $post_id );
		if ( $post_status === false ) {
			return array();
		}
		return array( $post_status );
	}

	/**
	 * Get the index values for a post author.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to get the data for.
	 * @return   array    The index values for the post author.
	 */
	private static function get_post_author_values( $post_id ) {
		$post_author = get_post_field( 'post_author', $post_id );
		if ( $post_author === false ) {
			return array();
		}
		return array( $post_author );
	}

	/**
	 * Get the index values for a post custom field.
	 *
	 * @since 3.0.0
	 *
	 * @param    int    $post_id    The post ID to get the data for.
	 * @param    string $custom_field_key    The custom field key to get the data for.
	 * @return   array    The index values for the custom field.
	 */
	private static function get_post_custom_field_values( $post_id, $custom_field_key ) {
		// Get the custom field data.
		$custom_field_data = get_post_meta( $post_id, $custom_field_key );

		if ( $custom_field_data === false ) {
			return array();
		}

		$values = array();
		foreach ( $custom_field_data as $custom_field_value ) {
			if ( is_scalar( $custom_field_value ) ) {
				// Add the item to the list.
				$values[] = $custom_field_value;

			} elseif ( is_array( $custom_field_value ) ) {
				// Loop through the array and add each value to the list..
				foreach ( $custom_field_value as $array_value ) {
					if ( is_scalar( $custom_field_value ) ) {
						// Build the item and add it to the list.
						$values[] = $array_value;
					}
				}
			}
		}
		return $values;
	}

	/**
	 * Add items to the index.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $items    The items to add to the index.
	 */
	private static function add_items_to_index( $items ) {

		if ( empty( $items ) ) {
			return;
		}

		$query = new Index_Query();
		// Add the items to the DB.
		$query->add_items( $items );
	}

	/**
	 * Build an index item and with its value.
	 *
	 * @since    3.0.0
	 *
	 * @param    array $item    The item to build.
	 * @param    mixed $value   The value to add to the item.
	 * @return   array          The item with the value added.
	 */
	public static function build_index_item_with_value( $item, $value ) {
		$item['value'] = $value;
		return $item;
	}

	/**
	 * Clear the index.
	 *
	 * @since    3.0.0
	 */
	public static function clear_index() {
		$index_table = new Index_Table();
		// If the table does not exist, then create the table.
		if ( $index_table->exists() ) {
			$index_table->truncate();
		}
	}

	/**
	 * Run a task.
	 *
	 * @since    3.0.0
	 *
	 * @param    \Search_Filter_Pro\Task_Runner\Task $task    The task to process.
	 */
	protected static function run_task( &$task ) {

		Util::error_log( 'Run task: ' . $task->get_action(), 'notice' );

		switch ( $task->get_action() ) {
			case 'clear_index':
				self::clear_index();
				break;
			case 'rebuild':
				self::task_rebuild( $task );
				break;
			case 'rebuild_query':
				self::task_rebuild_query( $task );
				break;
			case 'remove_query':
				self::task_remove_query( $task );
				break;
			case 'rebuild_field':
				self::task_rebuild_field( $task );
				break;
			case 'remove_field':
				self::task_remove_field( $task );
				break;
			case 'sync_post':
				self::task_post_sync( $task );
				break;
			default:
				Util::error_log( 'Unknown task action: ' . $task->get_action(), 'error' );
				break;
		}
	}
	/**
	 * Rebuild the query index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild_query( $task ) {

		$query_id = $task->get_meta( 'query_id', true );
		if ( ! $query_id ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query ID not set for rebuild query task.', 'error' );
			return;
		}

		// Lookup the query and get the post types.
		$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for rebuild query task.', 'error' );
			return;
		}

		// Make sure there are post types set.
		$post_types = $query->get_attribute( 'postTypes' );
		if ( empty( $post_types ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Post types not found for rebuild query task.', 'error' );
			return;
		}

		// Check to see if we're just starting the rebuild.
		$page_number = $task->get_meta( 'page_number', true );
		if ( ! $page_number ) {
			// Clear any existing tasks and index, this should have been done
			// already, the first time wouldn't catch any tasks in progress.
			self::clear_all_query_data( $query, array( 'id__not_in' => array( $task->get_id() ) ) );
		}

		/*
		 * Add query ID to the task so we don't rebuild the indexes for
		 * all connected fields to an object.
		 */
		$additional_task_data = array(
			'meta' => array( 'query_id' => $query_id ),
		);
		self::generate_rebuild_index_tasks( $task, $post_types, $additional_task_data );
	}

	/**
	 * Removes the index for a query (and clears up any related tasks).
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_remove_query( $task ) {

		// Get the query ID.
		$query_id = $task->get_meta( 'query_id' );
		if ( ! $query_id ) {
			Util::error_log( 'Query ID not found for remove query task.', 'error' );
			$task->set_status( 'error' );
			$task->save();
			return;
		}

		// Get the query.
		$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for remove query task.', 'error' );
			return;
		}

		// Clear any existing tasks and index, this should have been done
		// already, the first time wouldn't catch any tasks in progress.
		self::clear_all_query_data( $query, array( 'id__not_in' => array( $task->get_id() ) ) );

		$task->set_status( 'complete' );
		$task->save();
	}

	/**
	 * Rebuild the field index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild_field( $task ) {

		$field_id = $task->get_meta( 'field_id', true );
		if ( ! $field_id ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field ID not set for rebuild field task.', 'error' );
			return;
		}

		// Lookup the query and get the post types.
		$field = Field::find( array( 'id' => $field_id ) );
		if ( is_wp_error( $field ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field not found for rebuild field task.', 'error' );
			return;
		}

		// Lookup the query and get the post types.
		$query = Search_Filter_Query::find( array( 'id' => $field->get_query_id() ) );
		if ( is_wp_error( $query ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Query not found for rebuild field task.', 'error' );
			return;
		}

		// Make sure there are post types set.
		$post_types = $query->get_attribute( 'postTypes' );
		if ( empty( $post_types ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Post types not found for rebuild field task.', 'error' );
			return;
		}

		// Check to see if we're just starting the rebuild.
		$page_number = $task->get_meta( 'page_number', true );
		if ( ! $page_number ) {
			// If so clear out the existing tasks and index.
			self::clear_all_field_data( $field, array( 'id__not_in' => array( $task->get_id() ) ) );
		}

		/*
		 * Add query ID to the task so we don't rebuild the indexes for
		 * all connected fields to an object.
		 */
		$additional_task_data = array(
			'meta' => array( 'field_id' => $field_id ),
		);
		self::generate_rebuild_index_tasks( $task, $post_types, $additional_task_data );
	}

	/**
	 * Clear all the index data for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param    Field $field    The field to clear the index for.
	 */
	public static function clear_all_field_data( $field, $args = array() ) {

		// If so clear out the old data.
		self::clear_field_index( $field );

		// Delete any existing tasks for this field.
		$clear_tasks_args = wp_parse_args(
			$args,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => array(
					array(
						'key'     => 'field_id',
						'value'   => $field->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		self::clear_tasks( $clear_tasks_args );
	}

	/**
	 * Clear all the index data for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param    Search_Filter_Query $query    The query to clear the index for.
	 */
	public static function clear_all_query_data( $query, $args = array() ) {
		// Loop through the queries fields, and delete tasks and index data.
		$query_fields = $query->get_fields();
		// Delete tasks for the query.

		$clear_tasks_args = wp_parse_args(
			$args,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => array(
					array(
						'key'     => 'query_id',
						'value'   => $query->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		self::clear_tasks( $clear_tasks_args );

		foreach ( $query_fields as $field ) {
			// Clear any existing tasks and index, this should have been done
			// already, the first time wouldn't catch any tasks in progress.
			if ( is_wp_error( $field ) ) {
				Util::error_log( 'Field error when clearing query fields.', 'error' );
				continue;
			}
			self::clear_all_field_data( $field );
		}
	}

	/**
	 * Removes the index for a field (and clears up any related tasks).
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_remove_field( $task ) {

		// Get the query ID.
		$field_id = $task->get_meta( 'field_id' );
		if ( ! $field_id ) {
			Util::error_log( 'Field ID not found for remove field task.', 'error' );
			$task->set_status( 'error' );
			$task->save();
			return;
		}

		// Get the field.
		$field = Field::find( array( 'id' => $field_id ) );
		if ( is_wp_error( $field ) ) {
			$task->set_status( 'error' );
			$task->save();
			Util::error_log( 'Field not found for remove field task.', 'error' );
			return;
		}

		// Clear any existing tasks and index, this should have been done
		// already, the first time wouldn't catch any tasks in progress.
		self::clear_all_field_data( $field, array( 'id__not_in' => array( $task->get_id() ) ) );

		$task->set_status( 'complete' );
		$task->save();
	}

	/**
	 * Rebuild the index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_rebuild( &$task ) {
		self::maybe_init_sync_data();
		$page_number = $task->get_meta( 'page_number', true );

		if ( ! $page_number ) {
			self::clear_index();
			// Clear any other indexer related tasks.
			self::clear_tasks(
				array(
					'id__not_in' => array( $task->get_id() ),
				)
			);
		}
		self::generate_rebuild_index_tasks( $task, self::$indexed_post_types );
	}

	/**
	 * Rebuild the index from scratch.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task  $task                    The task to process.
	 * @param    array $post_types              The post types to rebuild.
	 * @param    array $additional_task_data    Additional data to pass to the task.
	 */
	private static function generate_rebuild_index_tasks( &$task, $post_types, $additional_task_data = array() ) {
		$page_number = $task->get_meta( 'page_number', true );
		$is_starting = false;
		if ( ! $page_number ) {
			$page_number = 1;
			$is_starting = true;
		}
		$page_number = absint( $page_number );

		$page_size = self::$query_page_size;
		$query     = new \WP_Query(
			array(
				'post_type'              => $post_types,
				'posts_per_page'         => $page_size,
				'paged'                  => $page_number,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'cache_results'          => false,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'lang'                   => '',
			)
		);

		if ( $is_starting ) {
			$data = array(
				'total'   => $query->found_posts,
				'current' => 0,
			);
			self::update_progress_data( $data );
		}

		foreach ( $query->posts as $post_id ) {
			$task_data = array(
				'action'    => 'sync_post',
				'status'    => 'pending',
				'object_id' => $post_id,
			);
			$task_data = wp_parse_args( $task_data, $additional_task_data );
			self::add_task( $task_data );
		}

		// If we've reached the max number of pages, then we're done.
		if ( $page_number >= $query->max_num_pages ) {
			$task->set_status( 'complete' );
			$task->save();
			$task->delete_meta( 'page_number' );
			return;
		}

		// Else, update the page number and run again.
		$task->update_meta(
			'page_number',
			$page_number + 1,
		);

	}


	/**
	 * Get the indexer progress option name.
	 *
	 * @since 3.0.0
	 *
	 * @return string The indexer progress option name.
	 */
	private static function get_progress_data_option_name() {
		return self::$progress_data_option_name;
	}

	/**
	 * Get the indexer progress.
	 *
	 * @since 3.0.0
	 *
	 * @return array The indexer progress.
	 */
	public static function get_progress_data() {
		$option_name = self::get_progress_data_option_name();
		return Options::get_option_value( $option_name );
	}

	/**
	 * Clear the indexer progress.
	 *
	 * @since 3.0.0
	 */
	public static function clear_progress_data() {
		$option_name = self::get_progress_data_option_name();
		Options::delete_option( $option_name );
	}

	/**
	 * Update the indexer progress.
	 *
	 * @since 3.0.0
	 *
	 * @param array $data The data to update.
	 */
	private static function update_progress_data( $data ) {
		$indexer_progress = self::get_progress_data();
		$default          = array(
			'current' => 0,
			'total'   => 0,
			'time'    => time(),
		);
		if ( ! $indexer_progress ) {
			$indexer_progress = $default;
		}
		$new_indexer_progress = wp_parse_args( $data, $indexer_progress );

		$option_name = self::get_progress_data_option_name();
		Options::update_option_value( $option_name, $new_indexer_progress );
	}


	/**
	 * Clear the objects count when tasks have finished.
	 *
	 * @since 3.0.0
	 *
	 * @param Task $task The task that has finished.
	 */
	public static function finish_task( $task ) {
	}

	/**
	 * Sync a post.
	 *
	 * @since    3.0.0
	 *
	 * @param    Task $task    The task to process.
	 */
	private static function task_post_sync( $task ) {
		$post_id = $task->get_object_id();

		$fields = array();

		$field_id = $task->get_meta( 'field_id', true );
		$query_id = $task->get_meta( 'query_id', true );

		// Check field ID before query ID, because field resync attaches
		// the query_id for easier management and deletion.
		if ( $field_id ) {
			$field = Field::find( array( 'id' => $field_id ) );
			if ( ! is_wp_error( $field ) ) {
				$fields[] = $field;
			}
		} elseif ( $query_id ) {
			// If no specific field IDs found, then see if we have a query.
			$query = Search_Filter_Query::find( array( 'id' => $query_id ) );
			if ( ! is_wp_error( $query ) ) {
				$query_fields = $query->get_fields();
				foreach ( $query_fields as $query_field ) {
					$fields[] = $query_field;
				}
			}
		} else {
			self::maybe_init_sync_data();
			$fields = self::$indexed_fields;
		}

		$args = array(
			'fields' => $fields,
		);

		self::resync_post( $post_id, $args, true );

		$task->set_status( 'complete' );
		$task->save();

		// Update the indexer progress.
		$indexer_progress = self::get_progress_data();
		$default          = array(
			'current' => 0,
			'total'   => 0,
			'time'    => time(),
		);
		if ( ! $indexer_progress ) {
			$indexer_progress = $default;
		}

		$indexer_progress['current']++;
		self::update_progress_data( $indexer_progress );
	}

	/**
	 * Reset the indexer and related tasks.
	 *
	 * @since 3.0.0
	 */
	public static function reset() {
		self::reset_tasks();
		self::clear_index();
		self::clear_progress_data();
		self::reset_error_count();
		self::reset_process_locks();
	}

	/**
	 * Run the indexer process.
	 *
	 * @since 3.0.0
	 *
	 * @param string $process_key Optional process key to run.
	 */
	public static function run_processing( $process_key = null ) {

		$index_method = self::get_method();

		self::set_status( 'processing' );

		if ( $process_key === null ) {
			$process_key = self::create_process_key();
		}

		if ( $process_key ) {
			$index_method = self::get_method();

			if ( $index_method === 'background' ) {
				self::spawn_run_process( $process_key );
			} else {
				// Run manually.
				self::run_tasks( $process_key );
				self::reset_process_locks();
			}
		}
	}

	/**
	 * Get the indexer processing method.
	 *
	 * @since 3.0.6
	 *
	 * @return string The indexer method.
	 */
	public static function get_method() {

		$default_method = 'background';

		$indexer_options_value = Options::get_option_value( 'indexer' );
		if ( $indexer_options_value && isset( $indexer_options_value['useBackgroundProcessing'] ) ) {
			return $indexer_options_value['useBackgroundProcessing'] === 'yes' ? 'background' : 'manual';
		}

		return $default_method;
	}

	/**
	 * Spawn a new indexer process.
	 *
	 * @since 3.0.0
	 *
	 * @param string $process_key The process key to spawn.
	 */
	public static function spawn_run_process( $process_key ) {

		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Abort if we have errored.
		if ( self::get_status() === 'error' ) {
			return;
		}

		// Try get and pass any http auth credentials if they exist to send in our rest api request.
		$credentials = \Search_Filter_Pro\Core\Authentication::get_http_auth_credentials();
		if ( ! empty( $credentials ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . base64_encode( $credentials['username'] . ':' . $credentials['password'] );
		}

		$options = array(
			'method'    => 'GET',
			'headers'   => $headers,
			'timeout'   => 0.01,
			'blocking'  => false,
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$rest_url = add_query_arg( 'process_key', $process_key, get_rest_url( null, 'search-filter-pro/v1/indexer/process' ) );
		$result   = wp_remote_post( $rest_url, $options );
		return $result;
	}

	public static function validate_process() {
		// Lookout for errors and set the status to error if we have reached the limit.
		$errors = self::error_count();
		if ( $errors >= self::$error_count_limit ) {
			self::set_status( 'error' );
		}
	}

	/**
	 * Check if a process should be started and if so, start it.
	 *
	 * @since 3.0.0
	 */
	public static function maybe_start_process() {

		wp_using_ext_object_cache( false );

		// No tasks so don't do anything.
		if ( self::has_finished_tasks() ) {
			return;
		}

		if ( self::get_status() === 'paused' ) {
			return;
		}

		// Always check if the process time is ok.
		self::validate_process_lock_time();

		// Already a lock in place, so a process is running.
		if ( self::has_process_key() ) {
			return;
		}

		// Try to spawn a new process.
		self::run_processing();
	}
	/**
	 * Get the indexer progress for a given action.
	 *
	 * @since 3.0.0
	 *
	 * @param string $action The action to get the progress for.
	 * @param bool   $refresh    Whether to refresh the progress.
	 * @return array    The indexer progress.
	 */
	public static function get_progress( $action, $refresh = false ) {
		/*
		 * Checking progress on large installs can be expensive so better
		 * to cache the value for a short period of time.
		 */

		// We've reached the max time, then we need to recalculate the progress.

		// TODO - I don't think we need this anymore?

		// Get number of completed tasks.
		$query_args      = array(
			'count'  => true,
			'type'   => static::$type,
			'status' => 'complete',
			'action' => $action,
		);
		$completed_query = new Tasks_Query( $query_args );

		// Get number of pending tasks.
		$query_args    = array(
			'count'  => true,
			'type'   => static::$type,
			'status' => 'pending',
			'action' => $action,
		);
		$pending_query = new Tasks_Query( $query_args );

		$progress = array(
			'current' => $completed_query->items,
			'total'   => $pending_query->items + $completed_query->items,
			'time'    => time(),
		);

		return $progress;
	}

	/**
	 * Is calculating returns whether the indexer is currently calculating
	 * which posts to index.
	 */
	public static function get_task_type() {
		$current_indexer_task = self::get_next_task();

		if ( ! $current_indexer_task ) {
			return '';
		}

		$task_action = $current_indexer_task->get_action();

		$rebuild_tasks = array( 'rebuild', 'rebuild_field', 'rebuild_query' );
		if ( in_array( $task_action, $rebuild_tasks, true ) ) {
			return 'rebuild';
		}

		$remove_tasks = array( 'remove_query', 'remove_field' );
		if ( in_array( $task_action, $remove_tasks, true ) ) {
			return 'remove';
		}

		$sync_tasks = array( 'sync_post' );
		if ( $current_indexer_task && in_array( $current_indexer_task->get_action(), $sync_tasks, true ) ) {
			return 'sync';
		}

		return '';
	}

	/**
	 * Prevent running queries as WP_Query's when the indexer is enabled.
	 *
	 * @param mixed               $is_wp_query Whether to run the query as a regular WP_Query.
	 * @param Search_Filter_Query $query       The S&F query to check.
	 * @return bool       Whether to run as a WP_Query or not.
	 */
	public static function remove_wp_query( $is_wp_query, $query ) {
		if ( $query->get_attribute( 'useIndexer' ) === 'yes' ) {
			return false;
		}
		return $is_wp_query;
	}

	/**
	 * Build the indexer posts query.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Query $wp_query The WP_Query object.
	 */
	public static function build_posts_query( $wp_query ) {
		// Apply the queries.
		if ( empty( $wp_query->get( 'search_filter_queries' ) ) ) {
			return;
		}
		// The return the fields for the query.
		$queries = $wp_query->get( 'search_filter_queries' );

		// Track the IDs of the queries so we can log an error if there is more than one.
		$attached_query_ids = array();

		$wp_query_args = array();

		foreach ( $queries as $query ) {
			$attached_query_ids[] = $query->get_id();

			// Make sure the query is using the indexer.
			if ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				continue;
			}

			$indexer_query = Query_Store::get_query( $query->get_id() );

			if ( $indexer_query === null ) {
				// Create a new indexer query.
				$indexer_query = new Indexer_Query( $query );
				// Add to the store.
				Query_Store::add_query( $indexer_query );
			}

			// Apply the query args.
			$wp_query_args = wp_parse_args( $indexer_query->get_query_args(), $wp_query_args );
		}

		// Now try to update the query from the provided args.
		foreach ( $wp_query_args as $key => $value ) {
			$wp_query->set( $key, $value );
		}

		if ( count( $attached_query_ids ) > 1 ) {
			Util::error_log( 'Detected possible conflicting queries: ' . implode( ', ', $attached_query_ids ), 'error' );
		}
	}
}
