<?php
/**
 * Features settings for admin screens.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that contains the settings found in features
 */
class Settings extends \Search_Filter\Settings\Section_Base {
	/**
	 * The source settings before they have been processed.
	 *
	 * @var array
	 */
	protected static $source_settings = array();

	/**
	 * The prepared settings.
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * The settings order.
	 *
	 * @var array
	 */
	protected static $settings_order = array();

	/**
	 * The source groups.
	 *
	 * @var array
	 */
	protected static $source_groups = array();

	/**
	 * The prepared groups.
	 *
	 * @var array
	 */
	protected static $groups = array();

	/**
	 * The groups order.
	 *
	 * @var array
	 */
	protected static $groups_order = array();

	/**
	 * The setting section name
	 */
	protected static $section = 'features';
}
