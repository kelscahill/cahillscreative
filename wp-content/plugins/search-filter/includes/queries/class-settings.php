<?php
/**
 * Queries settings.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Queries
 */

namespace Search_Filter\Queries;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles settings for queries.
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
	 * The setting section name.
	 *
	 * @var string
	 */
	protected static $section = 'queries';

	/**
	 * Prepare the setting.
	 *
	 * Allow overrides via filter.
	 *
	 * @param array $setting The setting to prepare.
	 * @param array $args Optional. Additional arguments for preparing the setting.
	 *
	 * @return \Search_Filter\Settings\Setting The prepared setting.
	 */
	protected static function prepare_setting( array $setting, array $args = array() ) {
		$setting = apply_filters( 'search-filter/queries/settings/prepare_setting/before', $setting, $args );

		return parent::prepare_setting( $setting );
	}
}
