<?php
/**
 * Task Signal constants.
 *
 * Returned by run_task() to communicate execution outcome to the task loop.
 * These are process-level signals, not task-level status — use task status
 * (complete/error/pending) for the task's own state.
 *
 * @package    Search_Filter_Pro\Task_Runner
 * @since      3.2.3
 */

namespace Search_Filter_Pro\Task_Runner;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Task Signal class.
 *
 * @since 3.2.3
 */
class Task_Signal {

	/**
	 * Task attempted its work. Check task status for outcome.
	 */
	const FINISHED = 1;

	/**
	 * Task couldn't run — insufficient time remaining.
	 */
	const TIME_LIMITED = 2;
}
