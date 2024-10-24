<?php
/**
 * Register all actions and fields for the plugin
 *
 * @link       http://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper class for using WP hooks
 * // TODO - might need to deprecate? Doesn't work with static methods
 */
class Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of fields registered with WordPress.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      array    $fields    The fields registered with WordPress to fire when the plugin loads.
	 */
	protected $fields;

	/**
	 * Initialize the collections used to maintain the actions and fields.
	 *
	 * @since    3.0.0
	 */
	public function __construct() {

		$this->actions = array();
		$this->fields  = array();

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since 3.0.0
	 * @param string $hook             The name of the WordPress action that is being registered.
	 * @param object $component        A reference to the instance of the object on which the action is defined.
	 * @param string $callback         The name of the function definition on the $component.
	 * @param int    $priority         Optional. he priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since    3.0.0
	 * @param string $hook             The name of the WordPress filter that is being registered.
	 * @param object $component        A reference to the instance of the object on which the filter is defined.
	 * @param string $callback         The name of the function definition on the $component.
	 * @param int    $priority         Optional. he priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->fields = $this->add( $this->fields, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @param array  $hooks            The collection of hooks that is being registered (that is, actions or fields).
	 * @param string $hook             The name of the WordPress filter that is being registered.
	 * @param object $component        A reference to the instance of the object on which the filter is defined.
	 * @param string $callback         The name of the function definition on the $component.
	 * @param int    $priority         The priority at which the function should be fired.
	 * @param int    $accepted_args    The number of arguments that should be passed to the $callback.
	 * @return   array                                  The collection of actions and fields registered with WordPress.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}
	/**
	 * Register the fields and actions with WordPress.
	 *
	 * @since    3.0.0
	 */
	public function run() {
		foreach ( $this->fields as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
