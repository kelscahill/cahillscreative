<?php

namespace WPForms\Pro\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Pro\Tasks\Actions\Migration199Task;

/**
 * Class upgrade for 1.9.9 release.
 *
 * @since 1.9.9
 *
 * @noinspection PhpUnused
 */
class Upgrade1_9_9 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * We run migration as Action Scheduler task.
	 * Class Tasks does not exist at this point, so here we can only check task completion status.
	 *
	 * @since 1.9.9
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		return $this->run_async( Migration199Task::class );
	}
}
