<?php

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace WPForms\Pro\Migrations;

use WPForms\Helpers\DB;
use WPForms\Migrations\UpgradeBase;

/**
 * Class v1.9.4 upgrade for Pro.
 *
 * @since 1.9.4
 *
 * @noinspection PhpUnused
 */
class Upgrade1_9_4 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.9.4
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() {

		// Re-check that all database tables exist.
		DB::create_custom_tables( true );

		return true;
	}
}
