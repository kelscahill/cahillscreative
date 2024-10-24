<?php
/**
 * Indexer settings class.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Settings
 */

namespace Search_Filter_Pro\Indexer;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settings for the indexer.
 */
class Settings extends \Search_Filter\Settings\Section_Base {
	/**
	 * The source settings before they have been processed.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $source_settings = array();

	/**
	 * The prepared settings.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * The settings order.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $settings_order = array();

	/**
	 * The source groups.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $source_groups = array();

	/**
	 * The prepared groups.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $groups = array();

	/**
	 * The groups order.
	 *
	 * @since 3.0.0
	 *
	 * @var array
	 */
	protected static $groups_order = array();

	/**
	 * The setting section name
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected static $section = 'indexer';
}
