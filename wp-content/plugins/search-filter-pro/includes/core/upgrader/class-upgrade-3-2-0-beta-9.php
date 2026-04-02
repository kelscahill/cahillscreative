<?php
/**
 * Upgrade routines for version 3.2.0
 *
 * @package Search_Filter_Pro
 */

namespace Search_Filter_Pro\Core\Upgrader;

use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Options;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter_Pro\Database\Engine\Table;
use Search_Filter_Pro\Indexer;
use Search_Filter_Pro\Indexer\Task_Runner as Indexer_Task_Runner;
use Search_Filter_Pro\Task_Runner;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles upgrade to version 3.2.0 Beta 9.
 */
class Upgrade_3_2_0_Beta_9 extends Upgrade_Base {

	/**
	 * Individual table version option keys to migrate.
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
	 * Run the upgrade.
	 *
	 * @since 3.2.0
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Migrate individual table version options to consolidated registry.
		self::migrate_table_versions();

		// While normally we would let tables handle their own upgrades, we need to upgrade
		// the tasks table before its used.
		$tasks_table = Task_Runner::get_table( 'tasks', false );
		if ( $tasks_table && $tasks_table->exists() ) {
			$tasks_table->drop();
		}
		$taskmeta_table = Task_Runner::get_table( 'meta', false );
		if ( $taskmeta_table && $taskmeta_table->exists() ) {
			$taskmeta_table->drop();
		}

		// Re-install by using the tables.
		Task_Runner::get_table( 'tasks', true );
		Task_Runner::get_table( 'meta', true );

		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Populate meta for existing fields (for efficient validation queries).
		$fields = Fields::find( array( 'number' => 0 ) );

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			$interaction_type = $field->get_interaction_type();
			if ( $interaction_type ) {
				Field::update_meta( $field->get_id(), 'interaction_type', $interaction_type );
			}
		}

		// Populate meta for existing queries (for efficient validation queries).
		$queries = Queries::find( array( 'number' => 0 ) );

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}
			$use_indexer = $query->get_attribute( 'useIndexer' ) === 'yes' ? 'yes' : 'no';
			Query::update_meta( $query->get_id(), 'use_indexer', $use_indexer );
		}

		// Set indexer migration flag to "no" for existing installations.
		// This ensures they use legacy indexing until migration is complete.
		// New users (where option doesn't exist) will automatically use new indexing.
		Options::update( 'indexer-migration-completed', 'no' );

		// Handle feature defaults for existing users.
		$features = Options::get_direct( 'features' );
		if ( is_array( $features ) ) {
			// Some versions of the 3.2.0 beta allow us to enable/disable beta features.
			// but this is no longer possible. We need to ensure that that beta features
			// are unset, or enabled, so they fallback to their default of "enabled".
			if ( isset( $features['betaFeatures'] ) ) {
				$features['betaFeatures'] = true;
			}

			// Ensure dynamic asset loading is disabled by default for existing users.
			$features['dynamicAssetLoading'] = false;

			Options::update( 'features', $features );
		}

		// Otherwise if we are on the frontend, then add the task the queue.
		$task_data = array(
			'action' => 'migrate',
			'status' => 'pending',
		);

		Indexer_Task_Runner::add_task( $task_data );
		Indexer::async_process_queue();

		// Important: don't run `CSS_Loader::save_css()` this time, we don't want to build
		// the CSS unless the user opts into it via our UI after 3.2.0.

		// Remove the filter.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		return Upgrade_Result::success();
	}

	/**
	 * Disable CSS save during upgrade.
	 *
	 * @since 3.2.0
	 * @return bool
	 */
	public static function disable_css_save() {
		return false;
	}

	/**
	 * Migrate individual table version options to the consolidated registry.
	 *
	 * @since 3.2.0
	 */
	private static function migrate_table_versions() {
		$options_to_delete = array();

		// Migrate site-level options.
		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_option( $option_name, false );

			if ( false !== $version ) {
				// Add to consolidated registry.
				Table::set_registry_version( $registry_key, $version, false );
				$options_to_delete[] = $option_name;
			}
		}

		// Delete old individual options.
		foreach ( $options_to_delete as $option_name ) {
			delete_option( $option_name );
		}

		// Handle network options for multisite global tables.
		if ( is_multisite() ) {
			self::migrate_network_table_versions();
		}
	}

	/**
	 * Migrate network-level table version options for multisite.
	 *
	 * @since 3.2.0
	 */
	private static function migrate_network_table_versions() {
		$network_id        = get_main_network_id();
		$options_to_delete = array();

		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_network_option( $network_id, $option_name, false );

			if ( false !== $version ) {
				// Add to consolidated network registry.
				Table::set_registry_version( $registry_key, $version, true );
				$options_to_delete[] = $option_name;
			}
		}

		// Delete old individual network options.
		foreach ( $options_to_delete as $option_name ) {
			delete_network_option( $network_id, $option_name );
		}
	}
}
