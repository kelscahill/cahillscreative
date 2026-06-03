<?php

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Helpers\File;
use WPForms\Helpers\Transient;
use WPForms\Pro\Admin\Entries\Import\ImportSession;
use WPForms\Tasks\Task;

/**
 * Class ImportCleanupTask.
 *
 * Runs once a day to remove stale import temporary files and abandoned
 * ImportSession transients older than one hour.
 *
 * @since 1.10.1
 */
class ImportCleanupTask extends Task {

	/**
	 * Option name of the tmp file registry.
	 *
	 * Each entry is `absolute_file_path => created_timestamp`. Files are
	 * added by AbstractFileSource::move_to_upload_tmp() and removed by this
	 * task once they pass the TTL or disappear from disk.
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'wpforms_import_tmp_files';

	/**
	 * Action name for this task.
	 *
	 * @since 1.10.1
	 *
	 * @var string
	 */
	public const ACTION = 'wpforms_import_cleanup';

	/**
	 * How often the task runs, in seconds (once a day).
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	public const INTERVAL = DAY_IN_SECONDS;

	/**
	 * Maximum age of import files and sessions before they are removed.
	 *
	 * @since 1.10.1
	 *
	 * @var int
	 */
	private const TTL = HOUR_IN_SECONDS;

	/**
	 * Constructor.
	 *
	 * @since 1.10.1
	 */
	public function __construct() {

		parent::__construct( self::ACTION );

		$this->init();
	}

	/**
	 * Initialize.
	 *
	 * @since 1.10.1
	 */
	private function init(): void {

		$this->hooks();
	}

	/**
	 * Register action hooks.
	 *
	 * @since 1.10.1
	 */
	private function hooks(): void {

		add_action( self::ACTION, [ $this, 'run' ] );
	}

	/**
	 * Run the cleanup.
	 *
	 * @since 1.10.1
	 */
	public function run(): void {

		$this->delete_stale_sessions();
		$this->delete_stale_files();
	}

	/**
	 * Record a file in the tmp-file registry so the cleanup task can find and
	 * delete it later without scanning the uploads directory.
	 *
	 * @since 1.10.1
	 *
	 * @param string $path Absolute path to the file just written into the tmp dir.
	 */
	public static function register_file( string $path ): void {

		$files = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $files ) ) {
			$files = [];
		}

		$files[ $path ] = time();

		update_option( self::OPTION_NAME, $files, false );
	}

	/**
	 * Delete ImportSession transients that have not been updated for over an hour.
	 *
	 * Sessions are stored as WPForms transients. Each has a timeout option equal
	 * to creation/last-update time + DAY_IN_SECONDS. A session last touched more
	 * than TTL seconds ago, therefore has a timeout less than
	 * now + DAY_IN_SECONDS - TTL.
	 *
	 * @since 1.10.1
	 */
	private function delete_stale_sessions(): void {

		global $wpdb;

		$timeout_prefix = Transient::TIMEOUT_PREFIX . ImportSession::KEY_PREFIX;
		$value_prefix   = Transient::OPTION_PREFIX . ImportSession::KEY_PREFIX;

		$threshold = time() + DAY_IN_SECONDS - self::TTL;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE a, b
				FROM $wpdb->options a
				INNER JOIN $wpdb->options b
					ON b.option_name = CONCAT( %s, SUBSTRING( a.option_name, %d ) )
				WHERE a.option_name LIKE %s
				AND a.option_value < %d",
				$value_prefix,
				strlen( $timeout_prefix ) + 1,
				$wpdb->esc_like( $timeout_prefix ) . '%',
				$threshold
			)
		);
	}

	/**
	 * Delete import temporary files older than the TTL.
	 *
	 * The cleanup iterates the `OPTION_NAME` registry populated by
	 * AbstractFileSource::move_to_upload_tmp(). An entry is pruned when its
	 * file is older than TTL (file deleted) or when the file is already
	 * missing from disk. This avoids relying on directory listing, which is
	 * not available on hosts such as WPVIP.
	 *
	 * @since 1.10.1
	 */
	private function delete_stale_files(): void {

		$files = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $files ) || empty( $files ) ) {
			return;
		}

		/**
		 * Filters the maximum age (in seconds) of import temporary files
		 * before they are removed by the cleanup task.
		 *
		 * @since 1.10.1
		 *
		 * @param int $ttl Age threshold in seconds. Default: HOUR_IN_SECONDS.
		 */
		$ttl = (int) apply_filters( 'wpforms_pro_tasks_actions_import_cleanup_task_ttl', self::TTL );

		$now = time();

		foreach ( $files as $path => $created ) {
			if ( ! is_string( $path ) || $path === '' ) {
				unset( $files[ $path ] );

				continue;
			}

			if ( ! File::exists( $path ) ) {
				unset( $files[ $path ] );

				continue;
			}

			if ( ( $now - (int) $created ) < $ttl ) {
				continue;
			}

			if ( ! File::delete( $path ) ) {
				$this->log(
					sprintf(
						'Could not delete stale import file: %s.',
						$path
					)
				);

				continue;
			}

			unset( $files[ $path ] );
		}

		update_option( self::OPTION_NAME, $files, false );
	}
}
