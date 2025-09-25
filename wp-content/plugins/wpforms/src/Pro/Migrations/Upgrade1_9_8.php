<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Helpers\File;
use WPForms\Pro\Robots;

/**
 * Class upgrade for 1.9.8 release.
 *
 * @since 1.9.8
 */
class Upgrade1_9_8 extends UpgradeBase {

	/**
	 * Path to the robots.txt file.
	 *
	 * @since 1.9.8
	 *
	 * @var string|null
	 */
	private $home_path;

	/**
	 * Attempt to fix incorrect Disallow rule within WPForms block in robots.txt file.
	 *
	 * @since 1.9.8
	 *
	 * @return bool|null Upgrade result:
	 *                    true - the upgrade completed successfully,
	 *                    false - in the case of failure,
	 *                    null - upgrade started but not yet finished (background task).
	 */
	public function run() {

		$this->run_robots_disallow_migration();

		return true;
	}

	/**
	 * Check and fix incorrect Disallow rule within WPForms block in robots.txt file.
	 *
	 * @since 1.9.8
	 */
	private function run_robots_disallow_migration(): void {

		// There is no physical robots.txt file.
		// Complete the migration silently.
		if ( ! File::exists( $this->get_robots_txt_path() ) ) {
			return;
		}

		// Get the robots.txt content.
		$content = File::get_contents( $this->get_robots_txt_path() );

		// The file cannot be read.
		if ( $content === false ) {
			wpforms_log(
				'robots.txt',
				'The file could not be read during Disallow rule migration. This is usually due to file permissions.',
				[
					'type'  => 'log',
					'force' => true,
				]
			);

			return;
		}

		// The file is empty.
		// Complete the migration silently.
		if ( empty( $content ) ) {
			return;
		}

		$robots_instance       = new Robots();
		$correct_disallow_rule = 'Disallow: ' . $robots_instance->get_upload_root();

		// Extract the current Disallow rule from the WPForms block.
		$current_disallow_rule = $this->get_disallow_rule_from_wpforms_block( $content );

		if ( $current_disallow_rule === false ) {
			wpforms_log(
				'robots.txt',
				'WPForms Disallow rule was not found during migration.',
				[
					'type'  => 'log',
					'force' => true,
				]
			);

			return;
		}

		// If the current Disallow rule is correct or empty, no need to modify.
		if ( $current_disallow_rule === $correct_disallow_rule ) {
			return;
		}

		// Replace it with the correct one.
		$success = $this->replace_disallow_rule( $content, $current_disallow_rule, $correct_disallow_rule );

		wpforms_log(
			'robots.txt',
			$success ? 'WPForms Disallow rule has been corrected in the WPForms block.' : 'WPForms Disallow rule could not be corrected in the WPForms block.',
			[
				'type'  => 'log',
				'force' => true,
			]
		);
	}

	/**
	 * Extract the Disallow rule from the WPForms block.
	 *
	 * @since 1.9.8
	 *
	 * @param string $content File content.
	 *
	 * @return false|string The Disallow rule or false if not found.
	 */
	private function get_disallow_rule_from_wpforms_block( string $content ) {

		// Extract the WPForms block content.
		$start_pos = strpos( $content, '# START WPFORMS BLOCK' );
		$end_pos   = strpos( $content, '# END WPFORMS BLOCK' );

		if ( $start_pos === false || $end_pos === false ) {
			return false;
		}

		$wpforms_block = substr( $content, $start_pos, $end_pos - $start_pos );
		$lines         = explode( PHP_EOL, $wpforms_block );

		// Look for the Disallow line within the WPForms block.
		foreach ( $lines as $line ) {
			$trimmed_line = trim( $line );

			if ( strpos( $trimmed_line, 'Disallow:' ) === 0 ) {
				return $trimmed_line;
			}
		}

		return false;
	}

	/**
	 * Replace incorrect Disallow rule with correct one in WPForms block.
	 *
	 * @since 1.9.8
	 *
	 * @param string $content               The robots.txt content.
	 * @param string $current_disallow_rule The current incorrect Disallow rule.
	 * @param string $correct_disallow_rule The correct Disallow rule.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function replace_disallow_rule( string $content, string $current_disallow_rule, string $correct_disallow_rule ): bool {

		// Create a backup for the current version of the robots.txt file.
		// Better safe than sorry.
		File::copy( $this->get_robots_txt_path(), $this->get_robots_txt_backup_path() );

		// Replace the incorrect Disallow rule with the correct one.
		$new_content = str_replace( $current_disallow_rule, $correct_disallow_rule, $content );

		// Update the robots.txt file.
		return File::put_contents( $this->get_robots_txt_path(), $new_content );
	}

	/**
	 * Retrieves the file path to the robots.txt file.
	 *
	 * @since 1.9.8
	 *
	 * @return string The full path to the robots.txt file.
	 */
	private function get_robots_txt_path(): string {

		return $this->get_home_path() . 'robots.txt';
	}

	/**
	 * Generates the file path for the backup of the robots.txt file.
	 *
	 * @since 1.9.8
	 *
	 * @return string The full file path for the robots.txt backup.
	 */
	private function get_robots_txt_backup_path(): string {

		return $this->get_home_path() . sprintf( 'robots-backup-%s.txt', time() );
	}

	/**
	 * Retrieves the home path for the application, ensuring it is writable if possible.
	 * We can't rely on the ABSPATH constant because WordPress may be installed in a subfolder and robots.txt will be in the root.
	 *
	 * @since 1.9.8
	 *
	 * @return string The determined writable home path.
	 */
	private function get_home_path(): string {

		if ( $this->home_path !== null ) {
			return $this->home_path;
		}

		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$this->home_path = get_home_path();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) && ! is_writable( $this->home_path ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$this->home_path = wp_normalize_path( sanitize_text_field( $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR ) );
		}

		return $this->home_path;
	}
}
