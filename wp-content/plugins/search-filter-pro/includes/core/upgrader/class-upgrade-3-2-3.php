<?php
/**
 * Repair upgrade routines for version 3.2.3
 *
 * Conditionally re-applies upgrade operations from beta-9 through beta-12
 * for users who may have had partial/failed upgrades.
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Features;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Options;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Util;
use Search_Filter_Pro\Cache\Database_Cache;
use Search_Filter_Pro\Database\Engine\Table;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Indexer\Bitmap\Manager as Bitmap_Manager;
use Search_Filter_Pro\Indexer\Bucket\Manager as Bucket_Manager;
use Search_Filter_Pro\Indexer\Parent_Map\Manager as Parent_Map_Manager;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use Search_Filter_Pro\Task_Runner;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles repair upgrade to version 3.2.3.
 *
 * This upgrade conditionally re-applies operations from beta-9 through beta-12
 * that may have failed or been partially applied. Each operation checks its
 * condition before applying, making it safe for ALL users.
 */
class Upgrade_3_2_3 extends Upgrade_Base {

	/**
	 * Individual table version option keys to migrate (from beta-9).
	 *
	 * Maps: old option name => registry key (table name).
	 *
	 * @var array
	 */
	private static $option_to_registry = array(
		'search_filter_pro_tasks_table_version'            => 'tasks',
		'search_filter_pro_taskmeta_table_version'         => 'taskmeta',
		'search_filter_pro_index_cache_table_version'      => 'index_cache',
		'search_filter_pro_index_table_version'            => 'index',
		'search_filter_pro_bitmap_index_table_version'     => 'bitmap_index',
		'search_filter_pro_bucket_index_table_version'     => 'bucket_index',
		'search_filter_pro_bucket_metadata_table_version'  => 'bucket_metadata',
		'search_filter_pro_bucket_overflow_table_version'  => 'bucket_overflow',
		'search_filter_pro_parent_map_index_table_version' => 'parent_map_index',
	);

	/**
	 * Repairs that were applied during this upgrade.
	 *
	 * @var array
	 */
	private static $repairs_applied = array();

	/**
	 * Run the upgrade.
	 *
	 * @since 3.2.3
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Cron migration runs for ALL users (including new installs).
		self::repair_cron_migration();

		// Disable CSS save during repairs.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );

		// Try dropping and recreating the cache table for all users.
		self::repair_drop_cache_table();
		self::repair_acf_search_fields();
		self::repair_tasks_table_columns();
		self::repair_bitmap_index_table_columns();
		self::repair_bucket_index_table_columns();
		self::repair_parent_map_table_columns();

		$current_flag = Options::get_direct( 'indexer-migration-completed' );

		// If the flag doesn't exist at all, lets assume its a new user who
		// doesn't need the upgrade.
		if ( ! $current_flag ) {
			self::log_repair_summary();
			return Upgrade_Result::success( self::get_summary_message() );
		}
		// Run all repairs (each checks condition internally).
		self::repair_table_versions();
		self::repair_tasks_tables();
		self::repair_field_interaction_type_meta();
		self::repair_query_use_indexer_meta();
		self::repair_indexer_migration_flag();
		self::repair_feature_defaults();
		self::repair_autocomplete_auto_submit();
		self::repair_caching_setting_migration();
		self::repair_drop_old_cache_table();
		self::repair_queue_indexer_migration();

		remove_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );

		self::log_repair_summary();
		return Upgrade_Result::success( self::get_summary_message() );
	}

	/**
	 * Repair: Migrate cron jobs after centralized maintenance rework.
	 *
	 * Removes old License Server cron (now a maintenance consumer) and
	 * fixes Remote Notices recurrence name (search_filter_3days -> search_filter_7days).
	 * Runs for ALL users since stale crons affect everyone.
	 */
	private static function repair_cron_migration() {
		$repaired = false;

		// Remove old License Server cron (replaced by maintenance consumer).
		if ( wp_next_scheduled( 'search-filter-pro/core/license-server/health-check' ) ) {
			wp_clear_scheduled_hook( 'search-filter-pro/core/license-server/health-check' );
			$repaired = true;
		}

		// Fix Remote Notices recurrence name change.
		// Old event used 'search_filter_3days' which now maps to 3-day interval
		// (claimed by Core\Cron), causing notices to fire every 3 days instead of 7.
		// Clear it and let validate_cron_schedule() recreate with 'search_filter_7days'.
		$notices_event = wp_get_scheduled_event( 'search-filter-pro/core/notices/fetch' );
		if ( $notices_event && $notices_event->schedule === 'search_filter_3days' ) {
			wp_clear_scheduled_hook( 'search-filter-pro/core/notices/fetch' );
			$repaired = true;
		}

		if ( $repaired ) {
			self::$repairs_applied[] = 'cron_migration';
		}
	}

	/**
	 * Repair: Migrate table version options to registry (beta-9).
	 *
	 * Condition: Old option `search_filter_pro_*_table_version` exists.
	 */
	private static function repair_table_versions() {
		$migrated_any = false;

		// Migrate site-level options.
		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_option( $option_name, false );

			if ( false !== $version ) {
				Table::set_registry_version( $registry_key, $version, false );
				delete_option( $option_name );
				$migrated_any = true;
			}
		}

		// Handle network options for multisite global tables.
		if ( is_multisite() ) {
			$network_id = get_main_network_id();

			foreach ( self::$option_to_registry as $option_name => $registry_key ) {
				$version = get_network_option( $network_id, $option_name, false );

				if ( false !== $version ) {
					Table::set_registry_version( $registry_key, $version, true );
					delete_network_option( $network_id, $option_name );
					$migrated_any = true;
				}
			}
		}

		if ( $migrated_any ) {
			self::$repairs_applied[] = 'table_versions';
		}
	}

	/**
	 * Repair: Drop and recreate tasks tables (beta-9).
	 *
	 * Condition: Table missing or wrong structure.
	 * We check if the tables exist via Task_Runner - if they don't exist,
	 * get_table with create=true will create them.
	 */
	private static function repair_tasks_tables() {
		$repaired = false;

		// Check tasks table.
		$tasks_table = Task_Runner::get_table( 'tasks', false );
		if ( ! $tasks_table || ! $tasks_table->exists() ) {
			// Force creation.
			Task_Runner::get_table( 'tasks', true );
			$repaired = true;
		}

		// Check taskmeta table.
		$taskmeta_table = Task_Runner::get_table( 'meta', false );
		if ( ! $taskmeta_table || ! $taskmeta_table->exists() ) {
			// Force creation.
			Task_Runner::get_table( 'meta', true );
			$repaired = true;
		}

		if ( $repaired ) {
			self::$repairs_applied[] = 'tasks_tables';
		}
	}

	/**
	 * Repair: Populate field `interaction_type` meta (beta-9).
	 *
	 * Condition: Any field missing this meta.
	 */
	private static function repair_field_interaction_type_meta() {
		$fields = Fields::find( array( 'number' => 0 ) );
		$count  = 0;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$existing = Field::get_meta( $field->get_id(), 'interaction_type', true );

			// Check if meta is missing/empty.
			if ( $existing === '' || $existing === false || $existing === array() ) {
				$interaction_type = $field->get_interaction_type();
				if ( $interaction_type ) {
					Field::update_meta( $field->get_id(), 'interaction_type', $interaction_type );
					++$count;
				}
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'field_meta(' . $count . ')';
		}
	}

	/**
	 * Repair: Populate query `use_indexer` meta (beta-9).
	 *
	 * Condition: Any query missing this meta.
	 */
	private static function repair_query_use_indexer_meta() {
		$queries = Queries::find( array( 'number' => 0 ) );
		$count   = 0;

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}

			$existing = Query::get_meta( $query->get_id(), 'use_indexer', true );

			// Check if meta is missing/empty.
			if ( $existing === '' || $existing === false || $existing === array() ) {
				$use_indexer = $query->get_attribute( 'useIndexer' ) === 'yes' ? 'yes' : 'no';
				Query::update_meta( $query->get_id(), 'use_indexer', $use_indexer );
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'query_meta(' . $count . ')';
		}
	}

	/**
	 * Repair: Set `indexer-migration-completed = 'no'` (beta-9).
	 *
	 * Condition: Option unset AND has existing queries.
	 * This ensures existing users use legacy indexing until migration completes.
	 * New users (no queries) skip this - they'll use new indexing by default.
	 */
	private static function repair_indexer_migration_flag() {
		$current_flag = Options::get_direct( 'indexer-migration-completed' );

		// Only set if option is not already set.
		if ( $current_flag !== false && $current_flag !== null && $current_flag !== '' ) {
			return;
		}

		// Check if there are existing queries (existing installation).
		$queries = Queries::find( array( 'number' => 1 ) );
		if ( empty( $queries ) || is_wp_error( $queries ) ) {
			// No queries - new installation, skip.
			return;
		}

		Options::update( 'indexer-migration-completed', 'no' );
		self::$repairs_applied[] = 'indexer_flag';
	}

	/**
	 * Repair: Feature defaults (beta-9/beta-10).
	 *
	 * - Set `features.betaFeatures = true` if explicitly set to `false`.
	 * - Set `features.dynamicAssetLoading = false` if unset AND has existing queries.
	 */
	private static function repair_feature_defaults() {
		$features = Options::get_direct( 'features' );
		$changed  = false;
		$repairs  = array();

		if ( ! is_array( $features ) ) {
			$features = array();
		}

		// Beta features must be enabled (can't be disabled).
		if ( isset( $features['betaFeatures'] ) && $features['betaFeatures'] === false ) {
			$features['betaFeatures'] = true;
			$changed                  = true;
			$repairs[]                = 'betaFeatures';
		}

		// Dynamic asset loading should be off for existing users.
		if ( ! isset( $features['dynamicAssetLoading'] ) ) {
			// Check if there are existing queries (existing installation).
			$queries = Queries::find( array( 'number' => 1 ) );
			if ( ! empty( $queries ) && ! is_wp_error( $queries ) ) {
				$features['dynamicAssetLoading'] = false;
				$changed                         = true;
				$repairs[]                       = 'dynamicAssetLoading';
			}
		}

		if ( $changed ) {
			Options::update( 'features', $features );
			self::$repairs_applied[] = 'features(' . implode( ',', $repairs ) . ')';
		}
	}

	/**
	 * Repair: Set `autoSubmitOnType = 'yes'` for autocomplete fields (beta-10).
	 *
	 * Condition: Autocomplete field has no `autoSubmitOnType` attribute.
	 */
	private static function repair_autocomplete_auto_submit() {
		$fields = Fields::find( array( 'number' => 0 ) );
		$count  = 0;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$input_type = $field->get_attribute( 'inputType' );

			// Only update autocomplete fields.
			if ( $input_type !== 'autocomplete' ) {
				continue;
			}

			// Check if autoSubmitOnType is missing.
			$auto_submit = $field->get_attribute( 'autoSubmitOnType' );
			if ( $auto_submit === null || $auto_submit === '' ) {
				$field->set_attribute( 'autoSubmitOnType', 'yes' );
				$field->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'autocomplete(' . $count . ')';
		}
	}

	/**
	 * Repair: Migrate `debugger.disableIndexerQueryCaching` → `caching.enableCaching` (beta-11).
	 *
	 * Condition: Old setting exists AND new doesn't.
	 */
	private static function repair_caching_setting_migration() {
		$debugger_settings = Options::get_direct( 'debugger' );

		// If no debugger settings, nothing to migrate.
		if ( ! is_array( $debugger_settings ) ) {
			return;
		}

		// Check if the old setting exists.
		if ( ! isset( $debugger_settings['disableIndexerQueryCaching'] ) ) {
			return;
		}

		// Check if new setting already exists.
		$caching_settings = Options::get_direct( 'caching' );
		if ( is_array( $caching_settings ) && isset( $caching_settings['enableCaching'] ) ) {
			// New setting exists, just clean up old one.
			unset( $debugger_settings['disableIndexerQueryCaching'] );
			Options::update( 'debugger', $debugger_settings );
			self::$repairs_applied[] = 'caching_cleanup';
			return;
		}

		// Migrate: invert the logic (disabled → not enabled).
		$was_caching_disabled    = $debugger_settings['disableIndexerQueryCaching'] === 'yes';
		$enable_database_caching = $was_caching_disabled ? 'no' : 'yes';

		if ( ! is_array( $caching_settings ) ) {
			$caching_settings = array();
		}

		$caching_settings['enableCaching'] = $enable_database_caching;
		Options::update( 'caching', $caching_settings );

		// Remove the old setting from debugger.
		unset( $debugger_settings['disableIndexerQueryCaching'] );
		Options::update( 'debugger', $debugger_settings );

		self::$repairs_applied[] = 'caching_migration';
	}

	/**
	 * Repair: Drop `wp_search_filter_index_cache` table (beta-12).
	 *
	 * Condition: Table exists.
	 */
	private static function repair_drop_old_cache_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'search_filter_index_cache';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		if ( ! $table_exists ) {
			return;
		}

		// Drop the table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$result = $wpdb->query(
			$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name )
		);

		// Also delete the old option if it exists.
		delete_option( 'search_filter_pro_index_cache_table_version' );

		if ( false !== $result ) {
			self::$repairs_applied[] = 'drop_cache_table';
		}
	}
	/**
	 * Repair: Drop `wp_search_filter_cache` table.
	 *
	 * Users who migrated from Search & Filter v2, have this table already
	 * with a completely different schema. We need to drop it so it can be
	 * rebuilt.
	 *
	 * Condition: Table exists.
	 */
	private static function repair_drop_cache_table() {

		$cache_table = Database_Cache::get_table( false );

		// Use the full table class with `uninstall()` so we deregister our
		// version numbers properly.
		if ( $cache_table && $cache_table->exists() ) {
			$cache_table->uninstall();
			self::$repairs_applied[] = 'drop_v2_cache_table';
		}
	}

	/**
	 * Repair: Queue indexer migration task (beta-9).
	 *
	 * Condition: `indexer-migration-completed = 'no'` AND no pending/running migration task.
	 */
	private static function repair_queue_indexer_migration() {
		$flag = Options::get_direct( 'indexer-migration-completed' );

		// Only queue if migration is explicitly incomplete.
		if ( $flag !== 'no' ) {
			return;
		}

		// Check if migration task already exists (pending or running).
		$has_task = Indexer_Task_Runner::has_task(
			array(
				'action' => 'migrate',
				'status' => array( 'pending', 'running' ),
			)
		);

		if ( $has_task ) {
			return;
		}

		// Queue migration task.
		$task_data = array(
			'action' => 'migrate',
			'status' => 'pending',
		);

		Indexer_Task_Runner::add_task( $task_data );
		Indexer::async_process_queue();

		self::$repairs_applied[] = 'queue_migration';
	}

	/**
	 * Repair: Verify tasks table has all expected columns.
	 *
	 * If any column is missing (e.g. failed ALTER TABLE during schema upgrades),
	 * drop the table and recreate it fresh from the current schema.
	 *
	 * Condition: Table exists but is missing one or more expected columns.
	 */
	private static function repair_tasks_table_columns() {
		$tasks_table = Task_Runner::get_table( 'tasks', false );

		if ( ! $tasks_table || ! $tasks_table->exists() ) {
			return;
		}

		$expected_columns = array( 'id', 'object_id', 'parent_id', 'type', 'action', 'status', 'date_modified', 'batch_id' );

		foreach ( $expected_columns as $column ) {
			if ( ! $tasks_table->column_exists( $column ) ) {
				$tasks_table->drop();
				$tasks_table->install();
				self::$repairs_applied[] = 'tasks_table_columns';
				return;
			}
		}
	}

	/**
	 * Repair: Verify bitmap_index table has all expected columns.
	 *
	 * If any column is missing, drop the table and recreate it fresh.
	 *
	 * Condition: Table exists but is missing one or more expected columns.
	 */
	private static function repair_bitmap_index_table_columns() {
		$table = Bitmap_Manager::get_table( false );

		if ( ! $table || ! $table->exists() ) {
			return;
		}

		$expected_columns = array( 'id', 'field_id', 'value', 'bitmap_data', 'object_count', 'max_object_id', 'last_updated' );

		foreach ( $expected_columns as $column ) {
			if ( ! $table->column_exists( $column ) ) {
				$table->drop();
				$table->install();
				self::$repairs_applied[] = 'bitmap_index_table_columns';
				return;
			}
		}
	}

	/**
	 * Repair: Verify bucket_index table has all expected columns.
	 *
	 * If any column is missing, drop the table and recreate it fresh.
	 *
	 * Condition: Table exists but is missing one or more expected columns.
	 */
	private static function repair_bucket_index_table_columns() {
		$table = Bucket_Manager::get_table( 'index', false );

		if ( ! $table || ! $table->exists() ) {
			return;
		}

		$expected_columns = array( 'id', 'field_id', 'bucket_id', 'bucket_type', 'min_value', 'max_value', 'item_count', 'bitmap_data', 'values_data', 'values_format', 'values_compressed', 'last_updated' );

		foreach ( $expected_columns as $column ) {
			if ( ! $table->column_exists( $column ) ) {
				$table->drop();
				$table->install();
				self::$repairs_applied[] = 'bucket_index_table_columns';
				return;
			}
		}
	}

	/**
	 * Repair: Verify parent_map_index table has all expected columns.
	 *
	 * If any column is missing, drop the table and recreate it fresh.
	 *
	 * Condition: Table exists but is missing one or more expected columns.
	 */
	private static function repair_parent_map_table_columns() {
		$table = Parent_Map_Manager::get_table( false );

		if ( ! $table || ! $table->exists() ) {
			return;
		}

		$expected_columns = array( 'child_id', 'parent_id', 'source', 'last_updated' );

		foreach ( $expected_columns as $column ) {
			if ( ! $table->column_exists( $column ) ) {
				$table->drop();
				$table->install();
				self::$repairs_applied[] = 'parent_map_table_columns';
				return;
			}
		}
	}

	/**
	 * Repair: Queue rebuild for ACF search fields.
	 *
	 * The 3.2.3 release fixed ACF search field indexing via bitmap indexer.
	 * Existing ACF search fields need re-indexing for the fix to take effect.
	 *
	 * Condition: Field type is 'search', dataType is 'acf_field',
	 * parent query has useIndexer enabled, and enhanced search is not enabled.
	 */
	private static function repair_acf_search_fields() {
		// Skip if beta enhanced search is enabled - ACF search works correctly there.
		if ( Features::is_enabled( 'betaFeatures' )
			&& Features::get_setting_value( 'beta-features', 'enhancedSearch' ) === 'yes' ) {
			return;
		}

		$fields = Fields::find( array( 'number' => 0 ) );
		$count  = 0;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			// Check if field is ACF search field.
			$type      = $field->get_attribute( 'type' );
			$data_type = $field->get_attribute( 'dataType' );

			if ( $type !== 'search' || $data_type !== 'acf_field' ) {
				continue;
			}

			// Check if parent query uses indexer.
			$query_id = $field->get_attribute( 'queryId' );
			$query    = Query::find( array( 'id' => $query_id ) );
			if ( is_wp_error( $query ) || $query->get_attribute( 'useIndexer' ) !== 'yes' ) {
				continue;
			}

			// Queue rebuild task.
			Indexer_Task_Runner::add_task(
				array(
					'action' => 'rebuild_field',
					'meta'   => array(
						'query_id' => $query_id,
						'field_id' => $field->get_id(),
					),
				)
			);
			++$count;
		}

		if ( $count > 0 ) {
			Indexer::async_process_queue();
			self::$repairs_applied[] = 'acf_search(' . $count . ')';
		}
	}

	/**
	 * Log repair summary.
	 */
	private static function log_repair_summary() {
		if ( ! empty( self::$repairs_applied ) ) {
			Util::error_log(
				'[S&F Pro Upgrader 3.2.3] Repairs applied: ' . implode( ', ', self::$repairs_applied ),
				'notice'
			);
		} else {
			Util::error_log(
				'[S&F Pro Upgrader 3.2.3] No repairs needed - all systems OK',
				'notice'
			);
		}
	}

	/**
	 * Get summary message for the upgrade result.
	 *
	 * @return string Summary message.
	 */
	private static function get_summary_message() {
		if ( ! empty( self::$repairs_applied ) ) {
			return 'Repairs applied: ' . implode( ', ', self::$repairs_applied );
		}
		return 'No repairs needed';
	}
}
