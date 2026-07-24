<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Pro\Tasks\Actions\MigrationOrphanEntryMetaTask;

/**
 * Class upgrade for the 1.10.2 release.
 *
 * @since 1.10.2
 *
 * @noinspection PhpUnused
 */
class Upgrade1_10_2 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * We run the cleanup as an Action Scheduler task because affected installs
	 * may have hundreds of thousands of orphan rows, and a synchronous DELETE
	 * during plugin upgrade would block the request.
	 *
	 * @since 1.10.2
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		return $this->run_async( MigrationOrphanEntryMetaTask::class );
	}
}
