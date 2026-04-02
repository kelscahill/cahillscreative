<?php
/**
 * Repair upgrade routines for version 3.2.3
 *
 * Conditionally re-applies upgrade operations from beta-9 through beta-11
 * for users who may have had partial/failed upgrades.
 *
 * @package Search_Filter
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Database\Engine\Table;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Options;
use Search_Filter\Queries;
use Search_Filter\Queries\Query;
use Search_Filter\Styles;
use Search_Filter\Styles\Tokens;
use Search_Filter\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles repair upgrade to version 3.2.3.
 *
 * This upgrade conditionally re-applies operations from beta-9 through beta-11
 * that may have failed or been partially applied. Each operation checks its
 * condition before applying, making it safe for ALL users.
 */
class Upgrade_3_2_3 extends Upgrade_Base {

	/**
	 * Individual table version option keys to migrate (from beta-9).
	 *
	 * Maps: old option name => registry key (table name).
	 *
	 * @var array
	 */
	private static $option_to_registry = array(
		'search_filter_fields_table_version'    => 'fields',
		'search_filter_fieldmeta_table_version' => 'fieldmeta',
		'search_filter_queries_table_version'   => 'queries',
		'search_filter_querymeta_table_version' => 'querymeta',
		'search_filter_styles_table_version'    => 'styles',
		'search_filter_stylemeta_table_version' => 'stylemeta',
		'search_filter_logs_table_version'      => 'logs',
		'search_filter_options_table_version'   => 'options',
	);

	/**
	 * Repairs that were applied during this upgrade.
	 *
	 * @var array
	 */
	private static $repairs_applied = array();

	/**
	 * Run the upgrade.
	 *
	 * @since 3.2.3
	 * @return Upgrade_Result
	 */
	protected static function do_upgrade() {
		// Cron migration runs for ALL users (including new installs).
		self::repair_cron_migration();

		// Early exit for new installs (no existing data to repair).
		if ( ! self::has_existing_data() ) {
			self::log_repair_summary();
			return Upgrade_Result::success( self::get_summary_message() );
		}

		// Disable CSS save during repairs.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );

		// Run all repairs (each checks condition internally).
		self::repair_table_versions();
		self::repair_assets_version();
		self::repair_query_attributes();
		self::repair_field_border_attributes();
		self::repair_style_attributes();
		self::repair_legacy_blocks_flag();
		self::repair_feature_defaults();
		self::repair_compatibility_settings();
		self::repair_field_input_enable_search();
		self::repair_style_token_values();
		self::repair_field_option_settings();
		self::repair_style_option_settings();

		remove_filter( 'search-filter/core/css-loader/save-css/can-save', '__return_false', 10 );

		self::log_repair_summary();
		return Upgrade_Result::success( self::get_summary_message() );
	}

	/**
	 * Check if there is existing data (not a new install).
	 *
	 * @return bool True if existing data found.
	 */
	private static function has_existing_data() {
		// Look for a field.
		$field = Field::find(
			array(
				'number' => 1,
			),
			'record'
		);
		if ( ! is_wp_error( $field ) ) {
			return true;
		}

		// Look for a query.
		$query = Query::find(
			array(
				'number' => 1,
			),
			'record'
		);
		if ( ! is_wp_error( $query ) ) {
			return true;
		}

		// Look for a style.
		$style = Styles\Style::find(
			array(
				'number' => 1,
			),
			'record'
		);
		if ( ! is_wp_error( $style ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Repair: Migrate cron jobs to centralized maintenance cron.
	 *
	 * Removes old Gutenberg cron and schedules new maintenance cron.
	 * Runs for ALL users (including new installs) since the maintenance
	 * cron is needed regardless of existing data.
	 */
	private static function repair_cron_migration() {
		$repaired = false;

		// Remove old Gutenberg cron (replaced by maintenance consumer).
		if ( wp_next_scheduled( 'search-filter/integrations/gutenberg/cron' ) ) {
			wp_clear_scheduled_hook( 'search-filter/integrations/gutenberg/cron' );
			$repaired = true;
		}

		// Schedule new maintenance cron if not already scheduled.
		if ( ! wp_next_scheduled( 'search-filter/cron/maintenance' ) ) {
			wp_schedule_event( time(), 'search_filter_3days', 'search-filter/cron/maintenance' );
			$repaired = true;
		}

		if ( $repaired ) {
			self::$repairs_applied[] = 'cron_migration';
		}
	}

	/**
	 * Repair: Migrate table version options to registry (beta-9).
	 *
	 * Condition: Old option `search_filter_*_table_version` exists.
	 */
	private static function repair_table_versions() {
		$migrated_any = false;

		// Migrate site-level options.
		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_option( $option_name, false );

			if ( false !== $version ) {
				Table::set_registry_version( $registry_key, $version, false );
				delete_option( $option_name );
				$migrated_any = true;
			}
		}

		// Handle network options for multisite global tables.
		if ( is_multisite() ) {
			$network_id = get_main_network_id();

			foreach ( self::$option_to_registry as $option_name => $registry_key ) {
				$version = get_network_option( $network_id, $option_name, false );

				if ( false !== $version ) {
					Table::set_registry_version( $registry_key, $version, true );
					delete_network_option( $network_id, $option_name );
					$migrated_any = true;
				}
			}
		}

		if ( $migrated_any ) {
			self::$repairs_applied[] = 'table_versions';
		}
	}

	/**
	 * Repair: Set assets-version option (beta-9).
	 *
	 * Condition: Option is not set.
	 */
	private static function repair_assets_version() {
		$assets_version = Options::get_direct( 'assets-version' );
		if ( ! $assets_version ) {
			Options::update( 'assets-version', 1 );
			self::$repairs_applied[] = 'assets_version';
		}
	}

	/**
	 * Repair: Query attributes migration (beta-9).
	 *
	 * Conditions:
	 *   - Has postType but not archivePostType
	 *   - Has taxonomy but not archiveTaxonomy
	 *   - Has archiveFilterTaxonomies = yes/no (not all/none)
	 */
	private static function repair_query_attributes() {
		$queries = Queries::find( array( 'number' => 0 ) );
		$count   = 0;

		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}

			$updated = false;

			// Check postType -> archivePostType migration.
			$archive_post_type     = $query->get_attribute( 'postType' );
			$has_archive_post_type = $query->get_attribute( 'archivePostType' );
			if ( $archive_post_type && ! $has_archive_post_type ) {
				$query->set_attribute( 'archivePostType', $archive_post_type );
				$updated = true;
			}

			// Check taxonomy -> archiveTaxonomy migration.
			$archive_taxonomy     = $query->get_attribute( 'taxonomy' );
			$has_archive_taxonomy = $query->get_attribute( 'archiveTaxonomy' );
			if ( $archive_taxonomy && ! $has_archive_taxonomy ) {
				$query->set_attribute( 'archiveTaxonomy', $archive_taxonomy );
				$updated = true;
			}

			// Convert archiveFilterTaxonomies from yes/no to all/none.
			$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
			if ( $archive_filter_taxonomies === 'yes' || $archive_filter_taxonomies === 'no' ) {
				$new_value = $archive_filter_taxonomies === 'yes' ? 'all' : 'none';
				$query->set_attribute( 'archiveFilterTaxonomies', $new_value );
				$updated = true;
			}

			// Check archiveTaxonomyFilterTerms for taxonomy archives.
			$integration_type = $query->get_attribute( 'integrationType' );
			$archive_type     = $query->get_attribute( 'archiveType' );
			$filter_terms     = $query->get_attribute( 'archiveTaxonomyFilterTerms' );
			if ( $integration_type === 'archive' && $archive_type === 'taxonomy' && ! $filter_terms ) {
				$query->set_attribute( 'archiveTaxonomyFilterTerms', 'all' );
				$updated = true;
			}

			if ( $updated ) {
				$query->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'query_attrs(' . $count . ')';
		}
	}

	/**
	 * Repair: Field inputBorder migration (beta-9).
	 *
	 * Condition: Field has inputBorderColor and inputType !== 'slider'.
	 */
	private static function repair_field_border_attributes() {
		$fields = Fields::find( array( 'number' => 0 ) );
		$count  = 0;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$input_type         = $field->get_attribute( 'inputType' );
			$input_border_color = $field->get_attribute( 'inputBorderColor' );

			// Only migrate if inputBorderColor exists and not a slider.
			if ( $input_border_color && $input_type !== 'slider' ) {
				$existing_border = $field->get_attribute( 'inputBorder' );
				if ( ! $existing_border ) {
					$field->set_attribute(
						'inputBorder',
						array(
							'style' => 'solid',
							'width' => '1px',
							'color' => $input_border_color,
						)
					);
					$field->delete_attribute( 'inputBorderColor' );
					$field->save();
					++$count;
				}
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'field_border(' . $count . ')';
		}
	}

	/**
	 * Repair: Style inputBorder migration (beta-9).
	 *
	 * Condition: Style has inputBorderColor for non-slider input types.
	 */
	private static function repair_style_attributes() {
		$styles = Styles::find( array( 'number' => 0 ) );
		$count  = 0;

		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}

			$style_attributes    = $style->get_attributes();
			$has_updated         = false;
			$updated_style_attrs = array();

			foreach ( $style_attributes as $field_type => $input_types ) {
				$updated_style_attrs[ $field_type ] = array();

				if ( ! is_array( $input_types ) ) {
					continue;
				}

				foreach ( $input_types as $input_type => $input_attributes ) {
					$updated_style_attrs[ $field_type ][ $input_type ] = $input_attributes;

					if ( $input_type === 'slider' ) {
						continue;
					}
					if ( ! isset( $input_attributes['inputBorderColor'] ) ) {
						continue;
					}
					if ( isset( $input_attributes['inputBorder'] ) ) {
						continue;
					}

					$has_updated        = true;
					$input_border_color = $input_attributes['inputBorderColor'];

					$updated_style_attrs[ $field_type ][ $input_type ]['inputBorder'] = array(
						'style' => 'solid',
						'width' => '1px',
						'color' => $input_border_color,
					);
					unset( $updated_style_attrs[ $field_type ][ $input_type ]['inputBorderColor'] );
				}
			}

			if ( $has_updated ) {
				$style->set_attributes( $updated_style_attrs );
				$style->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'style_border(' . $count . ')';
		}
	}

	/**
	 * Repair: Legacy blocks flag (beta-9).
	 *
	 * Condition: Flag not set but has legacy block fields (empty name + block-editor context).
	 */
	private static function repair_legacy_blocks_flag() {
		$flag = Options::get_direct( 'gutenberg-has-legacy-blocks' );
		if ( $flag !== null && $flag !== '' ) {
			return;
		}

		// Check if there are any legacy block fields.
		$fields            = Fields::find( array( 'number' => 0 ) );
		$has_legacy_blocks = false;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}
			if ( $field->get_name() === '' && $field->get_context() === 'block-editor' ) {
				$has_legacy_blocks = true;
				break;
			}
		}

		if ( $has_legacy_blocks ) {
			Options::update( 'gutenberg-has-legacy-blocks', 'yes' );
		} else {
			Options::update( 'gutenberg-has-legacy-blocks', 'no' );
		}

		self::$repairs_applied[] = 'legacy_blocks_flag';
	}

	/**
	 * Repair: Feature defaults (beta-10).
	 *
	 * Condition: features.dynamicAssetLoading is not set AND has existing queries.
	 */
	private static function repair_feature_defaults() {
		$features = Options::get_direct( 'features' );

		if ( ! is_array( $features ) ) {
			$features = array();
		}

		if ( isset( $features['dynamicAssetLoading'] ) ) {
			return;
		}

		// Check if there are existing queries.
		$queries = Queries::find( array( 'number' => 1 ) );
		if ( empty( $queries ) || is_wp_error( $queries ) ) {
			return;
		}

		$features['dynamicAssetLoading'] = false;
		Options::update( 'features', $features );
		self::$repairs_applied[] = 'feature_defaults';
	}

	/**
	 * Repair: Compatibility settings (beta-11).
	 *
	 * Condition: compatibility option missing cssIncreaseSpecificity or popoverNode.
	 */
	private static function repair_compatibility_settings() {
		$compatibility = Options::get_direct( 'compatibility' );
		$updated       = false;

		if ( ! is_array( $compatibility ) ) {
			$compatibility = array();
		}

		if ( ! isset( $compatibility['cssIncreaseSpecificity'] ) ) {
			$compatibility['cssIncreaseSpecificity'] = 'no';
			$updated                                 = true;
		}

		if ( ! isset( $compatibility['popoverNode'] ) ) {
			$compatibility['popoverNode'] = 'body';
			$updated                      = true;
		}

		if ( $updated ) {
			Options::update( 'compatibility', $compatibility );
			self::$repairs_applied[] = 'compatibility';
		}
	}

	/**
	 * Repair: inputEnableSearch for selects (beta-11).
	 *
	 * Condition: select/sort/per_page field missing inputEnableSearch attribute.
	 */
	private static function repair_field_input_enable_search() {
		$fields = Fields::find(
			array(
				'number' => 0,
			),
			'records'
		);
		$count  = 0;

		foreach ( $fields as $field_record ) {
			if ( is_wp_error( $field_record ) ) {
				continue;
			}

			$has_updated = false;
			$attributes  = $field_record->get_attributes();

			if ( ! isset( $attributes['type'] ) ) {
				continue;
			}

			// Add inputEnableSearch to existing select fields.
			if ( 'choice' === $attributes['type'] ) {
				if ( ! isset( $attributes['inputType'] ) ) {
					continue;
				}
				if ( 'select' === $attributes['inputType'] && ! isset( $attributes['inputEnableSearch'] ) ) {
					$attributes['inputEnableSearch'] = 'yes';
					$has_updated                     = true;
				}
			}

			// Add inputEnableSearch to sort and per_page controls.
			if ( 'control' === $attributes['type'] ) {
				if ( ! isset( $attributes['controlType'] ) ) {
					continue;
				}
				if ( in_array( $attributes['controlType'], array( 'sort', 'per_page' ), true ) ) {
					if ( ! isset( $attributes['inputEnableSearch'] ) ) {
						$attributes['inputEnableSearch'] = 'yes';
						$has_updated                     = true;
					}
				}
			}

			if ( $has_updated ) {
				$record = array(
					'attributes' => wp_json_encode( (object) $attributes ),
				);

				$query = new \Search_Filter\Database\Queries\Fields();
				$query->update_item( $field_record->get_id(), $record );
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'input_search(' . $count . ')';
		}
	}

	/**
	 * Repair: Swap em-based token defaults to calc-based equivalents.
	 *
	 * Part of the font-size/scale decoupling — tokens stored in the DB
	 * still have old em values that need converting to use --search-filter-scale-base-size.
	 */
	private static function repair_style_token_values() {
		$styles         = Styles::find( array( 'number' => 0 ) );
		$token_defaults = Tokens::get_defaults();
		$count          = 0;

		// Maps token name => old (DB) value that needs replacing.
		// New value is read from Tokens::get_defaults() so it stays in sync with definitions.
		$token_old_values = array(
			'border-radius-soft'  => '0.25em',
			'border-radius-round' => '1em',
		);

		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}

			$tokens      = $style->get_tokens();
			$has_updated = false;

			foreach ( $token_old_values as $token_name => $old_value ) {
				if ( isset( $tokens[ $token_name ] )
					&& $tokens[ $token_name ] === $old_value
					&& isset( $token_defaults[ $token_name ] ) ) {
					$tokens[ $token_name ] = $token_defaults[ $token_name ];
					$has_updated           = true;
				}
			}

			if ( $has_updated ) {
				$style->set_tokens( $tokens );
				$style->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'token_values(' . $count . ')';
		}
	}

	/**
	 * Repair: Remove stale inputPadding/inputGap from checkbox/radio fields.
	 *
	 * These settings were never effective for checkbox/radio — the SCSS never consumed
	 * the CSS vars they produced. Removed to clean up DB; SCSS fallbacks supply defaults
	 * for the new inputOption* settings.
	 */
	private static function repair_field_option_settings() {
		$fields = Fields::find( array( 'number' => 0 ) );
		$count  = 0;

		$stale_keys = array( 'inputPadding', 'inputGap' );

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$type       = $field->get_attribute( 'type' );
			$input_type = $field->get_attribute( 'inputType' );

			if ( 'choice' !== $type || ! in_array( $input_type, array( 'checkbox', 'radio' ), true ) ) {
				continue;
			}

			$has_updated = false;

			foreach ( $stale_keys as $key ) {
				if ( null !== $field->get_attribute( $key ) ) {
					$field->delete_attribute( $key );
					$has_updated = true;
				}
			}

			if ( $has_updated ) {
				$field->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'field_option_settings(' . $count . ')';
		}
	}

	/**
	 * Repair: Remove stale inputPadding/inputGap from checkbox/radio style preset entries.
	 *
	 * Same reasoning as repair_field_option_settings() — these were never effective.
	 */
	private static function repair_style_option_settings() {
		$styles = Styles::find( array( 'number' => 0 ) );
		$count  = 0;

		$stale_keys = array( 'inputPadding', 'inputGap' );

		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}

			$style_attributes    = $style->get_attributes();
			$has_updated         = false;
			$updated_style_attrs = array();

			foreach ( $style_attributes as $field_type => $input_types ) {
				$updated_style_attrs[ $field_type ] = array();

				if ( ! is_array( $input_types ) ) {
					continue;
				}

				foreach ( $input_types as $input_type => $input_attributes ) {
					$updated_style_attrs[ $field_type ][ $input_type ] = $input_attributes;

					if ( ! in_array( $input_type, array( 'checkbox', 'radio' ), true ) ) {
						continue;
					}

					foreach ( $stale_keys as $key ) {
						if ( isset( $input_attributes[ $key ] ) ) {
							unset( $updated_style_attrs[ $field_type ][ $input_type ][ $key ] );
							$has_updated = true;
						}
					}
				}
			}

			if ( $has_updated ) {
				$style->set_attributes( $updated_style_attrs );
				$style->save();
				++$count;
			}
		}

		if ( $count > 0 ) {
			self::$repairs_applied[] = 'style_option_settings(' . $count . ')';
		}
	}

	/**
	 * Log repair summary.
	 */
	private static function log_repair_summary() {
		if ( ! empty( self::$repairs_applied ) ) {
			Util::error_log(
				'[S&F Upgrader 3.2.3] Repairs applied: ' . implode( ', ', self::$repairs_applied ),
				'notice'
			);
		} else {
			Util::error_log(
				'[S&F Upgrader 3.2.3] No repairs needed - all systems OK',
				'notice'
			);
		}
	}

	/**
	 * Get summary message for the upgrade result.
	 *
	 * @return string Summary message.
	 */
	private static function get_summary_message() {
		if ( ! empty( self::$repairs_applied ) ) {
			return 'Repairs applied: ' . implode( ', ', self::$repairs_applied );
		}
		return 'No repairs needed';
	}
}
