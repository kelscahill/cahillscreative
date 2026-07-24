<?php

namespace WPForms\Pro\Migrations;

use WPForms\Helpers\DB;
use WPForms\Migrations\UpgradeBase;

/**
 * Class upgrade for the 2.0.0 release.
 *
 * Table creation is now handled by the self-healing custom-tables registry
 * via DB::create_custom_tables(), which covers both fresh installs and
 * version-gated upgrade paths idempotently.
 *
 * @since 2.0.0
 *
 * @noinspection PhpUnused
 */
class Upgrade2_0_0 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 2.0.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run(): ?bool {

		// Tables are created by the self-healing custom-tables registry. This
		// also covers fresh installs where this migration is version-gated out.
		DB::create_custom_tables( true );

		return true;
	}
}
