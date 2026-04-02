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
use Search_Filter\Util as Base_Util;
use Search_Filter\Queries\Query as Search_Filter_Query;
use Search_Filter\Queries as Search_Filter_Queries;
use Search_Filter_Pro\Indexer\Field_Queries;
use Search_Filter_Pro\Indexer\Query as Indexer_Query;
use Search_Filter_Pro\Indexer\Legacy\Query as Legacy_Indexer_Query;
use Search_Filter_Pro\Indexer\Query_Store;
use Search_Filter_Pro\Indexer\Rest_API;
use Search_Filter_Pro\Indexer\Settings_Data;
use Search_Filter_Pro\Indexer\Settings as Indexer_Settings;
use Search_Filter\Fields\Settings as Fields_Settings;
use Search_Filter\Core\Async;
use Search_Filter_Pro\Indexer\Stats;
use Search_Filter_Pro\Indexer\Parent_Map\Database\Query as Parent_Map_Query;
use Search_Filter_Pro\Indexer\Strategy\Index_Strategy_Factory;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use Search_Filter_Pro\Indexer\Post_Sync;
use Search_Filter_Pro\Indexer\Bitmap\Manager as Bitmap_Manager;
use Search_Filter_Pro\Indexer\Bucket\Manager as Bucket_Manager;
use Search_Filter_Pro\Indexer\Search\Manager as Search_Manager;
use Search_Filter_Pro\Indexer\Legacy\Manager as Legacy_Manager;
use Search_Filter_Pro\Indexer\Parent_Map\Manager as Parent_Map_Manager;
use Search_Filter_Pro\Cache\Tiered_Cache;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main indexer class.
 */
final class Indexer {

	/**
	 * The task type for the task runner.
	 *
	 * @var string
	 */
	protected static $type = 'indexer';

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
	 * Init the indexer.
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		// Register orchestrators.
		Bitmap_Manager::register();
		Bucket_Manager::register();
		Search_Manager::register();
		Legacy_Manager::register();
		Parent_Map_Manager::register();

		// Preload the migration completed option.
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_migration_completed_option' ) );

		// Init strings.
		add_action( 'init', array( __CLASS__, 'init_strings' ), 2 );

		// Make sure we load after the indexer setting is added (in the features class), so use priority 11.
		add_action( 'search-filter/settings/features/init', array( __CLASS__, 'load_indexer' ), 11 );
		add_action( 'search-filter/settings/fields/init', array( __CLASS__, 'add_count_to_order_field' ), 11 );

		// Add support for orderby setting for post attribute fields.
		add_filter( 'search-filter/fields/field/get_setting_support', array( __CLASS__, 'get_field_setting_support' ), 10, 3 );
	}

	/**
	 * Queue async processing of the indexer task queue.
	 *
	 * Registers a shutdown callback to start the task runner.
	 * This centralizes the async processing logic in one place.
	 *
	 * @since 3.0.0
	 */
	public static function async_process_queue() {
		Async::register_callback( array( Indexer_Task_Runner::class, 'maybe_start_process' ) );
	}

	/**
	 * Initialize indexer strings.
	 *
	 * @since 3.0.0
	 */
	public static function init_strings() {
		self::$strings = array(
			'indexer_error' => __( 'There has been an issue with the indexing process. Check the error log for more information.', 'search-filter-pro' ),
		);
	}

	/**
	 * Check if we need to keep using the legacy indexing methods.
	 *
	 * @since 3.2.0
	 * @return bool True if legacy indexing should be used, false for new.
	 */
	public static function migration_completed() {

		$flag = Options::get( 'indexer-migration-completed', 'yes' );

		// Option is "no" = upgrading user not yet migrated.
		if ( $flag === 'no' ) {
			return false;
		}

		// Else 'yes' = migrated user or new user.
		return true;
	}

	/**
	 * Preload the migration completed option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array The updated options array.
	 */
	public static function preload_migration_completed_option( $options_to_preload ) {
		// Preload the migration completed option.
		$options_to_preload[] = array( 'indexer-migration-completed', 'yes' );
		return $options_to_preload;
	}
	/**
	 * Load the indexer.
	 *
	 * @since 3.0.0
	 */
	public static function load_indexer() {

		// Register indexer sub settings.
		Indexer_Settings::init( Settings_Data::get(), Settings_Data::get_groups() );

		if ( ! Features::is_enabled( 'indexer' ) ) {
			return;
		}

		Indexer\Cron::init();

		do_action( 'search-filter/settings/register/indexer' );

		// Preload indexer settings paths.
		add_filter( 'search-filter/admin/get_preload_api_paths', array( __CLASS__, 'add_preload_api_paths' ) );

		// Init managers.
		Bitmap_Manager::init(); // @phpstan-ignore staticMethod.resultUnused (Registers hooks and initializes state)
		Bucket_Manager::init(); // @phpstan-ignore staticMethod.resultUnused (Registers hooks and initializes state)
		Search_Manager::init(); // @phpstan-ignore staticMethod.resultUnused (Registers hooks and initializes state)

		// Init REST API endpoints.
		Rest_API::init();

		add_action( 'search-filter/settings/init', array( __CLASS__, 'load_field_queries' ), 11 );

		// Initialize indexing strategies (bitmap, bucket, search).
		Index_Strategy_Factory::init();

		// Hook bucket rebuild automation.
		add_action( 'search-filter-pro/indexer/bucket/rebuild', array( __CLASS__, 'schedule_bucket_rebuild' ), 10, 1 );

		// Reset the objects count when tasks have finished.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
		// add_action( 'search-filter-pro/task_runner/finished', array( __CLASS__, 'finish_tasks' ) );

		// Prevent running queries as a WP_Query when the indexer is enabled.
		add_filter( 'search-filter/query/run_wp_query', array( __CLASS__, 'remove_wp_query' ), 1, 2 );
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
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
		// add_action( 'added_term_relationship', array( __CLASS__, 'added_term_relationship' ), 10, 1 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- Commented out code.
		// add_action( 'deleted_term_relationship', array( __CLASS__, 'deleted_term_relationship' ), 10, 1 );

		add_action( 'shutdown', array( __CLASS__, 'resync_queue' ), 0 ); // Must run BEFORE Async::run_callbacks (priority 100).

		// Check for errors and display notices.
		add_action( 'init', array( __CLASS__, 'add_hooks' ), 10 );
	}

	/**
	 * Load the field queries.
	 *
	 * Field Queries depend on Debugger settings to be loaded first, so we have to
	 * init on the debug/init action hook or regular settings/init hook.
	 *
	 * @since 3.2.0
	 */
	public static function load_field_queries() {

		// Initialize Field_Queries (orchestrator pattern).
		// Field_Queries will check migration status at runtime and delegate
		// to Legacy\Field_Queries if needed.
		Field_Queries::init();
	}

	/**
	 * Add count to order field options.
	 *
	 * @since 3.0.0
	 */
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

	/**
	 * Get field setting support based on field type and input type.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $setting_support The setting support configuration.
	 * @param string $type            The field type.
	 * @param string $input_type      The input type.
	 * @return array The modified setting support configuration.
	 */
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
		$paths[] = '/search-filter-pro/v1/indexer/processing';
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
		if ( ! Indexer_Task_Runner::has_reached_error_limit() ) {
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
		self::maybe_schedule_resync( $post_id );
	}

	/**
	 * Maybe schedule a post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID to check.
	 */
	private static function maybe_schedule_resync( $post_id ) {
		if ( ! self::should_resync( $post_id ) ) {
			return;
		}

		if ( ! Base_Util::is_frontend_only() ) {
			// If we're in the admin, then resync in the current proces, usually
			// get done in the shutdown hook.  We don't want to run this on the
			// frontend.
			self::resync_post_queue( $post_id );
			return;
		}

		if ( ! Indexer_Task_Runner::is_enabled_on_frontend() ) {
			return;
		}
		// Otherwise if we are on the frontend, then add the task the queue
		// and try to process it.
		$task_data = array(
			'action'    => 'sync_post',
			'status'    => 'pending',
			'object_id' => $post_id,
		);
		Indexer_Task_Runner::add_task( $task_data );
		self::async_process_queue();
	}

	/**
	 * On attachment added schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function attachment_added( $post_id ) {
		self::maybe_schedule_resync( $post_id );
	}

	/**
	 * On attachment updated schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    array    $form_fields    The form fields.
	 * @param    \WP_Post $post_before    The post before.
	 * @param    \WP_Post $post_after     The post after.
	 */
	public static function attachment_updated( $form_fields, $post_before, $post_after ) {
		self::maybe_schedule_resync( $post_after->ID );
	}

	/**
	 * On set object terms schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function set_object_terms( $post_id ) {
		self::maybe_schedule_resync( $post_id );
	}

	/**
	 * When post meta data is added/changed/deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param int $meta    The meta ID.
	 * @param int $post_id The post ID to save.
	 */
	public static function changed_post_meta( $meta, $post_id ) {
		self::maybe_schedule_resync( $post_id );
	}
	/**
	 * When post meta data is added/changed/deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to save.
	 */
	public static function changed_post_terms( $post_id ) {
		self::maybe_schedule_resync( $post_id );
	}

	/**
	 * On delete post schedule the post to be resynced.
	 *
	 * @since 3.0.0
	 *
	 * @param    int $post_id    The post ID to delete.
	 */
	public static function delete_post( $post_id ) {
		self::remove_post( $post_id );
	}

	/**
	 * Init the sync data.
	 *
	 * @since    3.0.0
	 */
	public static function init_sync_data() {

		if ( self::$has_init_sync_data ) {
			return;
		}

		do_action( 'search-filter-pro/indexer/init_sync_data/start' );

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

			$query_post_types = $query->get_attribute( 'postTypes' );
			if ( is_array( $query_post_types ) ) {
				$post_types_to_index = array_unique( array_merge( $post_types_to_index, $query_post_types ) );
			}

			foreach ( $post_types_to_index as $post_type ) {
				// Map the post stati to the post type.
				if ( ! isset( self::$indexed_post_stati_matrix[ $post_type ] ) ) {
					self::$indexed_post_stati_matrix[ $post_type ] = array();
				}
				$query_post_status = $query->get_attribute( 'postStatus' );
				if ( is_array( $query_post_status ) ) {
					self::$indexed_post_stati_matrix[ $post_type ] = array_unique( array_merge( self::$indexed_post_stati_matrix[ $post_type ], $query_post_status ) );
				}

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

			$query_post_status = $query->get_attribute( 'postStatus' );
			if ( is_array( $query_post_status ) ) {
				$indexed_post_stati = array_merge( $indexed_post_stati, $query_post_status );
			}

			$fields = $query->get_fields();
			foreach ( $fields as $field ) {
				if ( is_wp_error( $field ) ) {
					continue;
				}
				self::$indexed_fields[] = $field;
			}
		}
		self::$indexed_post_types = array_unique( $post_types_to_index );
		self::$indexed_post_stati = array_unique( $indexed_post_stati );

		do_action( 'search-filter-pro/indexer/init_sync_data/finish' );

		return array(
			'post_types'          => self::$indexed_post_types,
			'post_stati'          => self::$indexed_post_stati,
			'queries'             => self::$indexed_queries,
			'fields'              => self::$indexed_fields,
			'fields_by_post_type' => self::$indexed_fields_by_post_type,
		);
	}


	/**
	 * Get the indexed post types.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The indexed post types.
	 */
	public static function get_indexed_post_types() {
		self::init_sync_data();
		return self::$indexed_post_types;
	}

	/**
	 * Get the indexed fields by post type.
	 *
	 * @since 3.0.0
	 *
	 * @return array    The indexed fields keyed by post type.
	 */
	public static function get_indexed_fields_by_post_type() {
		self::init_sync_data();
		return self::$indexed_fields_by_post_type;
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

		self::init_sync_data();

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
	 * @param    bool  $clear_caches      Whether to clear the object caches after resyncing.
	 */
	public static function resync_post( $post_id, $args = array(), $clear_caches = true ) {
		// Delegate to Post_Sync.
		Post_Sync::resync_post( $post_id, $args, $clear_caches );
	}

	/**
	 * Schedule indexing a post, usually waiting for the shutdown hook so we don't
	 * try to add multiple of the same item to the queue.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $post_id           The post ID to resync.
	 */
	public static function resync_post_queue( $post_id ) {
		if ( ! self::should_resync( $post_id ) ) {
			return;
		}
		array_push( self::$resync_queue, $post_id );
	}

	/**
	 * Remove a post from the index.
	 *
	 * @since    3.0.0
	 *
	 * @param    int $post_id           The post ID to remove.
	 */
	public static function remove_post( $post_id ) {
		if ( ! self::should_resync( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		// Clear legacy index if migration not complete (non-search fields only).
		if ( ! self::migration_completed() ) {
			// Bulk delete all references to the object.
			Indexer\Legacy\Updater::clear_object_index( $post_id );
		}

		// Clear indexes for all relevant fields.
		// TODO - we could bulk clear these in each index table rather than looping
		// through each field.
		if ( $post_type ) {
			$indexed_fields_by_post_type = self::get_indexed_fields_by_post_type();

			if ( isset( $indexed_fields_by_post_type[ $post_type ] ) ) {
				foreach ( $indexed_fields_by_post_type[ $post_type ] as $field ) {

					// Clear new indexes (bitmap/bucket/search) via strategy.
					$strategy = Index_Strategy_Factory::for_field( $field );
					if ( $strategy ) {
						$strategy->clear( $field->get_id(), $post_id );
					}
				}
			}
		}

		// Remove from parent mapping table (for variations).
		Parent_Map_Query::delete_mapping( $post_id );

		// Clear caches.
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
		self::init_sync_data();

		// Clear any cached results for the query.
		if ( ! isset( self::$indexed_queries_by_post_type[ $post_type ] ) ) {
			return;
		}

		foreach ( self::$indexed_queries_by_post_type[ $post_type ] as $query ) {
			Tiered_Cache::invalidate_query_cache( $query->get_id() );
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

		$items              = apply_filters( 'search-filter-pro/indexer/resync_queue/items', self::$resync_queue );
		self::$resync_queue = array();

		// Flag indexed objects stats as needing refresh (batched per request).
		Stats::flag_refresh();

		// Add tasks to queue and try to launch a background process.
		if ( Indexer_Task_Runner::get_processing_method() === 'background' ) {
			// Otherwise if we are on the frontend, then add the task the queue
			// and try to process it.
			foreach ( $items as $sync_item ) {
				$task_data = array(
					'action'    => 'sync_post',
					'status'    => 'pending',
					'object_id' => $sync_item,
				);
				Indexer_Task_Runner::add_task( $task_data );
			}

			self::async_process_queue();

		} else {
			foreach ( $items as $sync_item ) {
				Post_Sync::process_post_sync( $sync_item );
			}
			// The task runner already clears caches automatically
			// so manually clear them here seen as we're bypassing it.
			Util::clear_object_caches();
		}
	}

	/**
	 * Clear the index.
	 *
	 * Clears all index data: legacy index (if migration not complete),
	 * bitmap index, bucket index, and search index.
	 *
	 * @since    3.0.0
	 */
	public static function clear_index() {
		// Only clear legacy if migration NOT complete.
		if ( ! self::migration_completed() ) {
			Indexer\Legacy\Updater::reset();
		}

		// Always clear new indexes.
		Indexer\Bucket\Updater::reset();
		Indexer\Bitmap\Updater::reset();
		Indexer\Search\Indexer::reset();

		// Clear query cache to prevent stale cached results.
		Tiered_Cache::reset();

		// Invalidate stats cache.
		Stats::flag_refresh();
	}

	/**
	 * Clear all the index data for a field.
	 *
	 * @since 3.0.0
	 *
	 * @param Field $field The field to clear the index for.
	 * @param array $args  Optional arguments.
	 */
	public static function clear_all_field_data( $field, $args = array() ) {
		$field_id = $field->get_id();

		// Clear ALL index types to handle type transitions.
		// When a field type changes (e.g., range→choice), we need to clear the old
		// index type's data, not just the current one.
		foreach ( Index_Strategy_Factory::get_all() as $strategy ) {
			$strategy->clear( $field_id );
		}

		// Clear legacy if migration not complete.
		if ( ! self::migration_completed() ) {
			Indexer\Legacy\Updater::clear_field_index( $field_id );
		}

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

		Indexer_Task_Runner::clear_tasks( $clear_tasks_args );

		// Increment generation to invalidate any opportunistic process that starts
		// during the window between clearing tasks and building new ones.
		// With multiple admin tabs polling, this window could be exploited.
		Indexer_Task_Runner::increment_generation();

		// Invalidate running process to prevent race conditions.
		// Any running process will detect the generation mismatch and exit.
		Indexer_Task_Runner::reset_process_locks();

		// Clear query cache for this field's query.
		Tiered_Cache::invalidate_query_cache( $field->get_query_id() );
	}

	/**
	 * Clear all the index data for a query.
	 *
	 * @since 3.0.0
	 *
	 * @param Search_Filter_Query $query The query to clear the index for.
	 * @param array               $args  Optional arguments.
	 */
	public static function clear_all_query_data( $query, $args = array() ) {
		// Increment generation FIRST to invalidate any opportunistic process
		// that starts during the window between clearing tasks and building new ones.
		Indexer_Task_Runner::increment_generation();

		// Invalidate running process to prevent race conditions.
		// This ensures any running process will detect the change and exit
		// before we modify the task queue.
		Indexer_Task_Runner::reset_process_locks();

		// Loop through the queries fields, and delete tasks and index data.
		$query_fields = $query->get_fields(
			array(
				'status' => 'any',
			)
		);
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

		Indexer_Task_Runner::clear_tasks( $clear_tasks_args );

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
	 * Schedule bucket rebuild task.
	 *
	 * Callback for bucket rebuild action hooks. Adds task to queue
	 * for automated bucket rebuilding when overflow threshold exceeded.
	 *
	 * @since 3.0.9
	 *
	 * @param int $field_id Field ID that needs rebuild.
	 */
	public static function schedule_bucket_rebuild( $field_id ) {
		// Add rebuild task to queue.
		$task_data = array(
			'action' => 'rebuild_bucket',
			'status' => 'pending',
			'meta'   => array(
				'field_id' => $field_id,
			),
		);

		Indexer_Task_Runner::add_task( $task_data );

		// Trigger async processing.
		self::async_process_queue();
	}

	/**
	 * Prevent running queries as WP_Query's when the indexer is enabled.
	 *
	 * @param mixed               $run_wp_query Whether to run the query as a regular WP_Query.
	 * @param Search_Filter_Query $query       The S&F query to check.
	 * @return bool       Whether to run as a WP_Query or not.
	 */
	public static function remove_wp_query( $run_wp_query, $query ) {
		if ( $query->get_attribute( 'useIndexer' ) === 'yes' ) {
			return false;
		}
		return $run_wp_query;
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

		// Get the original query args and preserve them.
		$indexer_query_args = array();

		foreach ( $queries as $query ) {
			$attached_query_ids[] = $query->get_id();

			// Make sure the query is using the indexer.
			if ( $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				continue;
			}

			Search_Filter_Queries::register_active_query( $query->get_id() );

			$query->set_wp_query( $wp_query );

			do_action_ref_array( 'search-filter/query/attach_wp_query', array( &$wp_query, &$query ) );

			$indexer_query = Query_Store::get_query( $query->get_id() );

			if ( $indexer_query === null ) {
				// Create a new indexer query.
				if ( self::migration_completed() ) {
					$indexer_query = new Indexer_Query( $query );
				} else {
					$indexer_query = new Legacy_Indexer_Query( $query );
				}
				// Add to the store.
				Query_Store::add_query( $indexer_query );
			}
			// Apply the query args.
			$indexer_query_args = wp_parse_args( $indexer_query->get_query_args(), $indexer_query_args );
		}

		// Now try to update the query from the provided args.
		foreach ( $indexer_query_args as $key => $value ) {
			$wp_query->set( $key, $value );
		}

		if ( count( $attached_query_ids ) > 1 ) {
			Util::error_log( 'Detected conflicting queries: ' . implode( ', ', $attached_query_ids ), 'error' );
		}
	}
}
