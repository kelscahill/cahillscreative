<?php
/**
 * The base class for all settings sections.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter
 */

namespace Search_Filter\Settings;

use Search_Filter\Core\Exception;
use Search_Filter\Settings;
use Search_Filter\Settings\Setting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The base class for all settings sections.
 *
 * @since      3.0.0
 */
abstract class Section_Base {
	/**
	 * The setting section name
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected static $section = '';

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
	 * Get the sections groups.
	 *
	 * @since    3.0.0
	 *
	 * @return   array    The data for the section.
	 */
	public static function get_groups() {
		return static::$groups;
	}

	/**
	 * Gets the processed settings.
	 *
	 * @since    3.0.0
	 *
	 * @return   array    The data for the section.
	 */
	public static function get() {
		return static::$settings;
	}
	/**
	 * Gets the source settings without modifications.
	 *
	 * @since 3.0.0
	 *
	 * @return   array    The settings.
	 */
	public static function get_source_settings() {
		return static::$source_settings;
	}

	/**
	 * Gets the settings in their correct order.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $return_as    The return type.
	 *
	 * @return   array    The ordered settings.
	 */
	public static function get_ordered( $return_as = 'objects' ) {
		$settings_ordered = array();
		foreach ( static::$settings_order as $setting_name ) {
			if ( $return_as === 'objects' ) {
				$settings_ordered[] = static::$settings[ $setting_name ];
			} elseif ( $return_as === 'arrays' ) {
				$settings_ordered[] = static::$settings[ $setting_name ]->get_array();
			}
		}
		return $settings_ordered;
	}

	/**
	 * Get the groups ordered.
	 *
	 * @since 3.0.0
	 *
	 * @return array The groups ordered.
	 */
	public static function get_groups_ordered() {
		$groups_ordered = array();
		foreach ( static::$groups_order as $group_name ) {
				$groups_ordered[] = static::$groups[ $group_name ];
		}
		return $groups_ordered;
	}

	/**
	 * Get the settings defaults.
	 *
	 * @since 3.0.0
	 *
	 * @return array The defaults.
	 */
	public static function get_defaults() {
		$defaults = array();
		$settings = static::get_prepared_settings();
		foreach ( $settings as $setting ) {
			if ( $setting->has_prop( 'default' ) ) {
				$defaults[ $setting->get_name() ] = $setting->get_prop( 'default' );
			} elseif ( count( $setting->get_options_array() ) > 0 ) {
				$defaults[ $setting->get_name() ] = $setting->get_options_array()[0]['value'];
			}
		}
		return $defaults;
	}

	/**
	 * Get the settings by property.
	 *
	 * @since 3.0.0
	 *
	 * @param string $property The property to match.
	 * @param mixed  $value    The value to match.
	 * @param string $return_as The return type.
	 *
	 * @return array The settings.
	 */
	public static function get_settings_by( $property, $value, $return_as = 'objects' ) {
		$settings    = static::get_prepared_settings();
		$settings_by = array();
		foreach ( $settings as $setting ) {
			if ( $setting->get_prop( $property ) === $value ) {
				if ( $return_as === 'objects' ) {
					$settings_by[] = $setting;
				} elseif ( $return_as === 'arrays' ) {
					$settings_by[] = $setting->get_array();
				}
			}
		}
		return $settings_by;
	}

	/**
	 * Get the prepared settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array The prepared settings.
	 */
	protected static function get_prepared_settings() {
		return static::$settings;
	}

	/**
	 * Init.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $settings    The settings to add.
	 * @param    array $groups    The groups to add.
	 */
	public static function init( $settings = array(), $groups = array() ) {
		static::add_settings( $settings );
		static::add_groups( $groups );

		// If a register name is passed, then add it to the register.
		if ( ! empty( static::$section ) ) {
			Settings::register_settings_class( static::$section, static::class );
		}

		$section = static::$section;
		do_action( "search-filter/settings/{$section}/init" );
	}

	/**
	 * Add a setting.
	 *
	 * @since 3.0.0
	 *
	 * @param    array $setting    The setting to add.
	 * @param    array $args       The args to pass to the setting.
	 */
	public static function add_setting( $setting, $args = array() ) {
		static::$source_settings[]         = $setting;
		$setting_name                      = $setting['name'];
		static::$settings[ $setting_name ] = static::prepare_setting( $setting, $args );

		$setting_added_to_order = false;

		if ( isset( $args['position'] ) ) {
			$default_order = array(
				'placement' => 'end', // Can be `start`, `end` or `before` or `after`.
				'setting'   => '', // If position is `before` or `after`, then this is the setting name to insert before or after.
			);

			$position = wp_parse_args( $args['position'], $default_order );

			if ( $position['placement'] === 'start' ) {
				// Insert the setting at the start of the array.
				array_unshift( static::$settings_order, $setting_name );
				$setting_added_to_order = true;

			} elseif ( $position['placement'] === 'end' ) {
				// Add the setting at the end of the array.
				static::$settings_order[] = $setting_name;
				$setting_added_to_order   = true;

			} elseif ( $position['placement'] === 'before' ) {
				// Find the position of the `setting` in the `settings_order` array.
				$position = array_search( $position['setting'], static::$settings_order, true );
				if ( false !== $position ) {
					array_splice( static::$settings_order, $position, 0, $setting_name );
					$setting_added_to_order = true;
				}
			} elseif ( $position['placement'] === 'after' ) {
				// Find the position of the `setting` in the `settings_order` array.
				$position = array_search( $position['setting'], static::$settings_order, true );
				if ( false !== $position ) {
					array_splice( static::$settings_order, $position + 1, 0, $setting_name );
					$setting_added_to_order = true;
				}
			}
		}

		// If the setting was not added to the settings order yet, then add it at the end.
		if ( ! $setting_added_to_order ) {
			static::$settings_order[] = $setting_name;
		}
	}

	/**
	 * Get a setting by name.
	 *
	 * @since 3.0.0
	 *
	 * @param    string $name    The name of the setting to get.
	 *
	 * @return   object|false    The setting object or false if not found.
	 */
	public static function get_setting( $name ) {
		if ( ! isset( static::$settings[ $name ] ) ) {
			return false;
		}
		return static::$settings[ $name ];
	}

	/**
	 * Add multiple settings.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The settings to add.
	 */
	public static function add_settings( $settings ) {
		foreach ( $settings as $setting ) {
			static::add_setting( $setting );
		}
	}

	/**
	 * Prepare the setting.
	 *
	 * @since 3.0.0
	 *
	 * @param array $setting The setting to prepare.
	 * @param array $args The args to pass to the setting.
	 *
	 * @return Setting The prepared setting.
	 * @throws Exception If the setting is invalid.
	 */
	protected static function prepare_setting( $setting, $args = array() ) {
		if ( ! isset( $setting['name'] ) || empty( trim( $setting['name'] ) ) ) {
			throw new Exception( esc_html__( 'Your setting must have a name', 'search-filter' ), SEARCH_FILTER_EXCEPTION_SETTING_INVALID_NAME );
		}

		$name = $setting['name'];

		if ( isset( static::$settings[ $name ] ) ) {
			/* translators: %s is the setting name. */
			throw new Exception( sprintf( esc_html__( 'A setting with the name `%1$s` already exists', 'search-filter' ), esc_html( $name ) ), SEARCH_FILTER_EXCEPTION_SETTING_EXISTS );
		}

		$section = static::$section;
		if ( ! empty( $section ) ) {
			$setting = apply_filters( "search-filter/settings/{$section}/setting/{$name}", $setting );
		}

		return new Setting( $setting );
	}

	/**
	 * Gets processed settings + attributes for a section.
	 *
	 * This should mirror the logic of `useProcessedSettings` hook in our JS.
	 *
	 * So far it is just setting the attributes settings based on default and depends conditions,
	 * but we don't fetch `options` for fields just yet via the rest API - and we also
	 * therefor don't set the attributes value to the value of the first option.
	 *
	 * There is probably some other nuance here we're not copying over, but for now, this generates
	 * a solid initial attributes for a settings section after resolving dependencies.
	 *
	 * @param string $attributes    The curent attributes of the section.
	 * @param array  $args     Args for parsing the settings.
	 *                        - `filters` - is an array of:
	 *                            - `type` & 'value' objects, type can be `tab`, `context`, `group`,
	 *                        - `ghost_state` - an array of keys to keep in the attributes when resolving.
	 *
	 * @since    3.0.0
	 */
	public static function get_processed_settings( $attributes, $args = array() ) {
		$settings = static::get_ordered();

		if ( isset( $args['filters'] ) && is_array( $args['filters'] ) ) {
			$filtered_settings = array();
			foreach ( $args['filters'] as $filter ) {
				if ( isset( $filter['type'] ) && isset( $filter['value'] ) ) {
					if ( $filter['type'] === 'tab' ) {
						$tab_name = isset( $filter['value'] ) ? $filter['value'] : '';
						foreach ( $settings as $setting ) {
							if ( $setting->get_data( 'tab' ) === $tab_name ) {
								$filtered_settings[] = $setting;
							}
						}
					} elseif ( $filter['type'] === 'context' ) {
						$context = isset( $filter['value'] ) ? $filter['value'] : '';
						foreach ( $settings as $setting ) {
							if ( ! is_array( $setting->get_data( 'context' ) ) ) {
								continue;
							}
							if ( in_array( $context, $setting->get_data( 'context' ), true ) ) {
								$filtered_settings[] = $setting;
							}
						}
					} elseif ( $filter['type'] === 'group' ) {
						$group = isset( $filter['value'] ) ? $filter['value'] : '';
						foreach ( $settings as $setting ) {
							if ( $setting->get_data( 'group' ) === $group ) {
								$filtered_settings[] = $setting;
							}
						}
					}
				}
			}
			$settings = $filtered_settings;
		}

		$ghost_state = isset( $args['ghost_state'] ) ? $args['ghost_state'] : array();

		$external_store     = static::get_external_store( $settings, $attributes );
		$processed_settings = new Processed_Settings( $settings, $attributes, $external_store, $ghost_state );
		return $processed_settings;
	}

	/**
	 * Get the external store.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings The settings.
	 * @param array $attributes The attributes.
	 *
	 * @return array The external store.
	 */
	protected static function get_external_store( $settings, $attributes ) {
		return array();
	}
	/**
	 * Add a group.
	 *
	 * @since 3.0.0
	 *
	 * @param array $group   The group to add.
	 * @param array $args    The args to pass to the group.
	 */
	public static function add_group( $group, $args = array() ) {
		static::$source_groups[]       = $group;
		$group_name                    = $group['name'];
		static::$groups[ $group_name ] = static::prepare_group( $group );

		$group_added_to_order = false;

		if ( isset( $args['position'] ) ) {
			$default_order = array(
				'placement' => 'end', // Can be `start`, `end` or `before` or `after`.
				'group'     => '', // If position is `before` or `after`, then this is the group name to insert before or after.
			);

			$position = wp_parse_args( $args['position'], $default_order );

			if ( $position['placement'] === 'start' ) {
				// Insert the group at the start of the array.
				array_unshift( static::$groups_order, $group_name );
				$group_added_to_order = true;

			} elseif ( $position['placement'] === 'end' ) {
				// Add the group at the end of the array.
				static::$groups_order[] = $group_name;
				$group_added_to_order   = true;

			} elseif ( $position['placement'] === 'before' ) {
				// Find the position of the `group` in the `groups_order` array.
				$position = array_search( $position['group'], static::$groups_order, true );
				if ( false !== $position ) {
					array_splice( static::$groups_order, $position, 0, $group_name );
					$group_added_to_order = true;
				}
			} elseif ( $position['placement'] === 'after' ) {
				// Find the position of the `group` in the `groups_order` array.
				$position = array_search( $position['group'], static::$groups_order, true );
				if ( false !== $position ) {
					array_splice( static::$groups_order, $position + 1, 0, $group_name );
					$group_added_to_order = true;
				}
			}
		}

		// If the group was not added to the groups order yet, then add it at the end.
		if ( ! $group_added_to_order ) {
			static::$groups_order[] = $group_name;
		}
	}
	/**
	 * Add multiple groups.
	 *
	 * @since 3.0.0
	 *
	 * @param array $groups   The groups to add.
	 */
	public static function add_groups( $groups ) {
		foreach ( $groups as $group ) {
			static::add_group( $group );
		}
	}
	/**
	 * Prepare the data for the group.
	 *
	 * @param array $group    The group to prepare.
	 *
	 * @return array The prepared group.
	 */
	protected static function prepare_group( $group ) {
		return $group;
	}
}
