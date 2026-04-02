<?php
/**
 * Upgrade routine for version 3.2.0 Beta.
 *
 * @package Search_Filter
 * @since 3.2.0
 */

namespace Search_Filter\Core\Upgrader;

use Search_Filter\Database\Engine\Table;
use Search_Filter\Options;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Queries\Query;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database and settings upgrades for version 3.2.0 Beta 9.
 *
 * @since 3.2.0
 */
class Upgrade_3_2_0_Beta_9 extends Upgrade_Base {

	/**
	 * Stores locations where legacy blocks were found during migration.
	 * Format: array( 'post/123', 'post/456', 'widget/sidebar-1', ... )
	 *
	 * @var array
	 */
	private static $legacy_block_locations = array();

	/**
	 * Individual table version option keys to migrate.
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
	 * Performs the upgrade routine for version 3.2.0 Beta.
	 *
	 * @since 3.2.0
	 *
	 * @return Upgrade_Result The result of the upgrade.
	 */
	protected static function do_upgrade() {

		// Migrate table versions from individual options to consolidated registry.
		self::migrate_table_versions();

		// Handle network options for multisite global tables.
		if ( is_multisite() ) {
			self::migrate_network_table_versions();
		}

		// Set the styles update opt-in to the old version to begin with.
		// Use the direct query to bypass the preloaded value.
		$assets_version = Options::get_direct( 'assets-version' );
		if ( ! $assets_version ) {
			Options::update( 'assets-version', 1 );
		}

		// Disable CSS save so we don't rebuild the CSS file for every field, query and style.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Resave queries.
		$queries = \Search_Filter\Queries::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $queries as $query ) {
			if ( is_wp_error( $query ) ) {
				continue;
			}

			$query_updated = false;
			// Rename the old `postType` (achive option) to `archivePostType`.
			$archive_post_type = $query->get_attribute( 'postType' );

			if ( $archive_post_type ) {
				$query->set_attribute(
					'archivePostType',
					$archive_post_type
				);
				$query_updated = true;
				// Don't delete the old attribute for now (so it continues to work in our extensions).
				// $query->delete_attribute( 'postType' );.
			}
			// Rename the old `taxonomy` (achive option) to `archiveTaxonomy`.
			$archive_taxonomy = $query->get_attribute( 'taxonomy' );

			if ( $archive_taxonomy ) {
				$query->set_attribute(
					'archiveTaxonomy',
					$archive_taxonomy
				);
				// Don't delete the old attribute for now (so it continues to work in our extensions).
				// $query->delete_attribute( 'taxonomy' );.
				$query_updated = true;
			}

			// Convert `archiveFilterTaxonomies` values from yes/no values to all/none.
			$archive_filter_taxonomies = $query->get_attribute( 'archiveFilterTaxonomies' );
			if ( $archive_filter_taxonomies === 'yes' || $archive_filter_taxonomies === 'no' ) {
				$new_value = $archive_filter_taxonomies === 'yes' ? 'all' : 'none';
				$query->set_attribute(
					'archiveFilterTaxonomies',
					$new_value
				);
				$query_updated = true;
			}

			// For any queriees set to display using tax archives, we need to add the "all term"
			// as a default value for the new `archiveTaxonomyFilterTerms` setting.
			$integration_type = $query->get_attribute( 'integrationType' );
			$archive_type     = $query->get_attribute( 'archiveType' );
			$filter_terms     = $query->get_attribute( 'archiveTaxonomyFilterTerms' );
			if ( $integration_type === 'archive' && $archive_type === 'taxonomy' && ! $filter_terms ) {
				$query->set_attribute(
					'archiveTaxonomyFilterTerms',
					'all'
				);
				$query_updated = true;
			}

			if ( $query_updated ) {
				$query->save();
			}
		}

		// As we're about to handle block migrations to our new block system we need to first cleanup
		// and fields that no longer exist as blocks.
		if ( class_exists( 'Search_Filter\Integrations\Gutenberg\Cron' ) ) {
			\Search_Filter\Integrations\Gutenberg\Cron::remove_orphaned_fields();
		}

		// Find fields, save and update any changed attributes.
		// Needs to be after the queries this time so we can use the updated attribute
		// `archiveTaxonomy`.
		$fields = Fields::find(
			array(
				'number' => 0,
			)
		);

		// Track if we find any legacy blocks so we can easily enable/disable support for them.
		$has_legacy_blocks = false;

		foreach ( $fields as $field ) {
			if ( is_wp_error( $field ) ) {
				continue;
			}

			$field_updated = false;
			// inputBorderColor was removed for all fields except slider and is now
			// merged with inputBorder.
			$input_type         = $field->get_attribute( 'inputType' );
			$input_border_color = $field->get_attribute( 'inputBorderColor' );

			if ( $input_type && $input_border_color && $input_type !== 'slider' ) {
				$field->set_attribute(
					'inputBorder',
					array(
						'style' => 'solid',
						'width' => '1px',
						'color' => $input_border_color,
					)
				);
				$field->delete_attribute( 'inputBorderColor' );
				$field_updated = true;
			}

			// Convert `taxonomyFilterArchive` to `taxonomyNavigatesArchive`.
			$taxonomy_filter_archive = $field->get_attribute( 'taxonomyFilterArchive' );
			if ( $taxonomy_filter_archive ) {
				$field->set_attribute( 'taxonomyNavigatesArchive', $taxonomy_filter_archive );
				$field->delete_attribute( 'taxonomyFilterArchive' );
				$field_updated = true;
			}

			// If we have a taxonomy field and a query that is set to taxonomy archive, we need to enable
			// the `taxonomyNavigatesArchive` as it was previously implied it would be set but is now optional.
			$data_type = $field->get_attribute( 'dataType' );
			if ( $data_type === 'taxonomy' ) {
				$query          = Query::get_instance( $field->get_query_id() );
				$field_taxonomy = $field->get_attribute( 'taxonomy' );
				if ( ! is_wp_error( $query ) ) {
					$integration_type = $query->get_attribute( 'integrationType' );
					$archive_type     = $query->get_attribute( 'archiveType' );
					if ( $integration_type === 'archive' && $archive_type === 'taxonomy' ) {
						$query_taxonomy = $query->get_attribute( 'archiveTaxonomy' );
						if ( $field_taxonomy === $query_taxonomy ) {
							// So the field taxonomy matches the query taxonomy which implies it should
							// be set to yes.
							$field->set_attribute( 'taxonomyNavigatesArchive', 'yes' );
							$field_updated = true;
						}
					}
				}
			}

			// Now we need to grab any legacy block editor fields.
			if ( self::migrate_legacy_block_field( $field ) ) {
				$has_legacy_blocks = true;
				$field_updated     = true;
			}

			if ( $field_updated ) {
				$field->save();
			}
		}

		// Migrate the actual block content at collected locations (posts/templates).
		$migrated_count = self::migrate_legacy_blocks_at_locations();

		// Also migrate ALL widgets (one-time scan).
		self::migrate_all_widgets();

		// Only set flag if we found legacy blocks but couldn't migrate them.
		if ( $has_legacy_blocks && 0 === $migrated_count ) {
			// Migration failed - keep legacy blocks enabled.
			Options::update( 'gutenberg-has-legacy-blocks', 'yes' );
		} else {
			// Either no legacy blocks found, or they were all migrated successfully.
			Options::update( 'gutenberg-has-legacy-blocks', 'no' );
		}

		// Resave styles.
		$styles = \Search_Filter\Styles::find(
			array(
				'number' => 0,
			)
		);
		foreach ( $styles as $style ) {
			if ( is_wp_error( $style ) ) {
				continue;
			}

			// inputBorderColor was removed for all fields except slider and is now
			// merged with inputBorder.
			$input_type         = $style->get_attribute( 'inputType' );
			$input_border_color = $style->get_attribute( 'inputBorderColor' );

			if ( $input_type && $input_border_color && $input_type !== 'slider' ) {

				$style_attributes             = $style->get_attributes();
				$updated_style_attributes     = array();
				$has_updated_style_attributes = false;

				foreach ( $style_attributes as $field_type => $input_types ) {

					$updated_style_attributes[ $field_type ] = array();

					if ( ! is_array( $input_types ) ) {
						continue;
					}

					foreach ( $input_types as $input_type => $input_attributes ) {

						$updated_style_attributes[ $field_type ][ $input_type ] = $input_attributes;

						if ( $input_type === 'slider' ) {
							continue;
						}
						if ( ! isset( $input_attributes['inputBorderColor'] ) ) {
							continue;
						}

						$has_updated_style_attributes = true;
						$input_border_color           = $input_attributes['inputBorderColor'];

						$updated_style_attributes[ $field_type ][ $input_type ]['inputBorder'] = array(
							'style' => 'solid',
							'width' => '1px',
							'color' => $input_attributes['inputBorderColor'],
						);
						unset( $updated_style_attributes[ $field_type ][ $input_type ]['inputBorderColor'] );
					}
				}

				if ( $has_updated_style_attributes ) {
					$style->set_attributes( $updated_style_attributes );
					$style->save();
				}
			}
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );

		// Normally we'd finish by rebuilding the CSS files, but in this case don't until the user opts-in.

		return Upgrade_Result::success();
	}

	/**
	 * Disables CSS save during upgrade to prevent rebuilding CSS for every change.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Always returns false to disable CSS save.
	 */
	public static function disable_css_save() {
		return false;
	}

	/**
	 * Migrate site-level table version options to the consolidated registry.
	 *
	 * @since 3.2.0
	 */
	private static function migrate_table_versions() {
		$options_to_delete = array();

		// Migrate site-level options.
		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_option( $option_name, false );

			if ( false !== $version ) {
				// Add to consolidated registry.
				Table::set_registry_version( $registry_key, $version, false );
				$options_to_delete[] = $option_name;
			}
		}

		// Delete old individual options.
		foreach ( $options_to_delete as $option_name ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Migrate network-level table version options to the consolidated registry.
	 *
	 * @since 3.2.0
	 */
	private static function migrate_network_table_versions() {
		$network_id        = get_main_network_id();
		$options_to_delete = array();

		foreach ( self::$option_to_registry as $option_name => $registry_key ) {
			$version = get_network_option( $network_id, $option_name, false );

			if ( false !== $version ) {
				// Add to consolidated network registry.
				Table::set_registry_version( $registry_key, $version, true );
				$options_to_delete[] = $option_name;
			}
		}

		// Delete old individual network options.
		foreach ( $options_to_delete as $option_name ) {
			delete_network_option( $network_id, $option_name );
		}
	}

	/**
	 * Migrates a legacy block editor field to the new system.
	 *
	 * Updates field name, context, and locations for legacy block editor fields.
	 *
	 * @since 3.2.0
	 *
	 * @param Field $field The field object to migrate.
	 * @return bool True if migration was needed, false otherwise.
	 */
	private static function migrate_legacy_block_field( $field ) {
		// We need to:
		// - give them a name.
		// - clear their context + path.
		// - set their locations using the newer location system.

		if ( '' !== $field->get_name() ) {
			return false;
		}

		$should_migrate = false;
		$location       = null;

		if ( 'block-editor' === $field->get_context() ) {

			$should_migrate = true;
			$field->set_context( 'block-editor' );
			$field_context_path = $field->get_context_path();
			if ( 0 === strpos( $field_context_path, 'post/' ) ) {
				// 3. If a context path is set to post/[number] - then check the post exists, and check the field exists there.
				$post_id = absint( str_replace( 'post/', '', $field_context_path ) );

				if ( get_post( $post_id ) ) {
					// Now we can set the location on the field.
					$location = 'post/' . $post_id;
				}
			} elseif ( 0 === strpos( $field_context_path, 'site-editor/' ) ) {
				// 4. If a context path is set to site-editor/[theme]//[type]-[post-type] - then check the post exists, and check the field exists there.
				$location_path = str_replace( 'site-editor/', '', $field_context_path );
				$template      = \get_block_template( $location_path, 'wp_template' );
				// Post name is usually in the format: "archive-post".
				if ( $template && \get_post( $template->wp_id ) ) {
					// Set the location on the field.
					$location = 'post/' . $template->wp_id;
				}
			}
		} else {
			// Check to see if we have a widget instead.
			$widget_id = Field::get_meta( $field->get_id(), 'widget_id', true );
			if ( $widget_id ) {
				$should_migrate = true;
				// Widgets are stored against the sidebar ID.
				$location = 'widget/' . $widget_id;
			}

			// Remove old widget meta.
			Field::delete_meta( $field->get_id(), 'widget_id' );
		}

		if ( ! $should_migrate ) {
			return false;
		}

		// Capture the location if it exists.
		if ( $location ) {
			// Store unique locations for later processing.
			if ( ! in_array( $location, self::$legacy_block_locations, true ) ) {
				self::$legacy_block_locations[] = $location;
			}
		}

		// Generate a 5 character UID lowercase letters and numbers.
		$field->set_name( 'Field Block ' . $field->get_id() );
		$field->set_context( '' );
		$field->set_context_path( '' );
		if ( $location ) {
			$field->add_location( $location );
			// $field->save(); // We should require save to commit locations but right now it just commits its immediately.
		}

		return true;
	}

	/**
	 * Migrate legacy blocks at all collected locations.
	 * Processes each unique location only once.
	 *
	 * @return int Number of locations where blocks were migrated.
	 */
	private static function migrate_legacy_blocks_at_locations() {
		if ( empty( self::$legacy_block_locations ) ) {
			return 0;
		}

		$migrated_count = 0;

		foreach ( self::$legacy_block_locations as $location ) {
			// Parse location string.
			$parts = explode( '/', $location, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			list( $type, $id ) = $parts;

			if ( $type === 'post' ) {
				if ( self::migrate_post_blocks( absint( $id ) ) ) {
					++$migrated_count;
				}
			} elseif ( $type === 'widget' ) {
				if ( self::migrate_widget_blocks( $id ) ) {
					++$migrated_count;
				}
			}
		}

		// Log the migration.
		\Search_Filter\Util::error_log( 'Migrated legacy blocks in ' . $migrated_count . ' locations', 'notice' );

		return $migrated_count;
	}

	/**
	 * Migrate legacy blocks in a specific post.
	 *
	 * @param int $post_id The post ID.
	 * @return bool True if blocks were migrated, false otherwise.
	 */
	private static function migrate_post_blocks( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || ! has_blocks( $post->post_content ) ) {
			return false;
		}

		// Use official WordPress block parser.
		$blocks      = parse_blocks( $post->post_content );
		$has_changes = false;

		// Recursively transform blocks.
		$transformed_blocks = self::transform_blocks_recursive( $blocks, $has_changes );

		if ( ! $has_changes ) {
			return false;
		}

		// Serialize back to content using official API.
		$new_content = serialize_blocks( $transformed_blocks );

		// Update post.
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		return true;
	}

	/**
	 * Recursively transform legacy blocks to new block types.
	 *
	 * @param array $blocks      Array of block arrays.
	 * @param bool  $has_changes Reference to track if any changes were made.
	 * @return array Transformed blocks.
	 */
	private static function transform_blocks_recursive( $blocks, &$has_changes ) {
		$transformed = array();

		foreach ( $blocks as $block ) {
			// Skip non-S&F blocks.
			if ( 0 !== strpos( $block['blockName'], 'search-filter/' ) ) {
				$transformed[] = $block;
				continue;
			}

			// Don't auto-migrate reusable-field blocks here.
			if ( $block['blockName'] === 'search-filter/reusable-field' ) {
				$transformed[] = $block;
				continue;
			}

			// Check if it's a legacy block.
			$legacy_blocks = array(
				'search-filter/search',
				'search-filter/choice',
				'search-filter/range',
				'search-filter/advanced',
				'search-filter/control',
			);

			if ( in_array( $block['blockName'], $legacy_blocks, true ) ) {
				// Transform to new block name.
				$new_block = self::transform_legacy_block( $block );
				if ( $new_block ) {
					$transformed[] = $new_block;
					$has_changes   = true;
					continue;
				}
			}

			// Recursively transform inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = self::transform_blocks_recursive(
					$block['innerBlocks'],
					$has_changes
				);
			}

			$transformed[] = $block;
		}

		return $transformed;
	}

	/**
	 * Transform a single legacy block to its new block name.
	 *
	 * @param array $block The block array.
	 * @return array|null The transformed block or null if transformation failed.
	 */
	private static function transform_legacy_block( $block ) {
		$attrs      = $block['attrs'] ?? array();
		$block_name = $block['blockName'];

		$new_block_name = null;

		switch ( $block_name ) {
			case 'search-filter/search':
				$input_type     = $attrs['inputType'] ?? 'text';
				$new_block_name = 'search-filter/search-' . $input_type;
				break;

			case 'search-filter/choice':
				$input_type     = $attrs['inputType'] ?? 'select';
				$new_block_name = 'search-filter/choice-' . $input_type;
				break;

			case 'search-filter/range':
				$input_type     = $attrs['inputType'] ?? 'slider';
				$new_block_name = 'search-filter/range-' . $input_type;
				break;

			case 'search-filter/advanced':
				$input_type     = $attrs['inputType'] ?? 'date_picker';
				$new_block_name = 'search-filter/advanced-' . str_replace( '_', '-', $input_type );
				break;

			case 'search-filter/control':
				$control_type   = $attrs['controlType'] ?? 'submit';
				$new_block_name = 'search-filter/control-' . str_replace( '_', '-', $control_type );
				break;
		}

		if ( ! $new_block_name ) {
			return null;
		}

		// Create new block with transformed name.
		$block['blockName'] = $new_block_name;

		return $block;
	}

	/**
	 * Migrate legacy blocks in widget content.
	 *
	 * @param string $widget_id The widget ID (could be 'block-2' or just '2').
	 * @return bool True if blocks were migrated, false otherwise.
	 */
	private static function migrate_widget_blocks( $widget_id ) {
		// Widgets are stored as serialized arrays in options.
		$widget_option = get_option( 'widget_block' );

		if ( ! is_array( $widget_option ) ) {
			return false;
		}

		// Try using widget_id as-is first (in case it's already numeric).
		$numeric_id = $widget_id;

		// If not found, try parsing the widget ID (e.g., 'block-2' -> '2').
		if ( ! isset( $widget_option[ $numeric_id ] ) && function_exists( 'wp_parse_widget_id' ) ) {
			$parsed = wp_parse_widget_id( $widget_id );

			// Check if this is a block widget and extract numeric ID.
			if ( isset( $parsed['id_base'] ) && $parsed['id_base'] === 'block' && isset( $parsed['number'] ) ) {
				$numeric_id = $parsed['number'];
			}
		}

		// Final check if we have a valid widget.
		if ( ! isset( $widget_option[ $numeric_id ] ) ) {
			return false;
		}

		$widget = $widget_option[ $numeric_id ];

		if ( ! is_array( $widget ) || empty( $widget['content'] ) ) {
			return false;
		}

		// Parse and transform blocks.
		if ( ! has_blocks( $widget['content'] ) ) {
			return false;
		}

		$blocks      = parse_blocks( $widget['content'] );
		$has_changes = false;

		$transformed_blocks = self::transform_blocks_recursive( $blocks, $has_changes );

		if ( ! $has_changes ) {
			return false;
		}

		// Update widget content.
		$widget['content']            = serialize_blocks( $transformed_blocks );
		$widget_option[ $numeric_id ] = $widget;

		update_option( 'widget_block', $widget_option );

		return true;
	}

	/**
	 * Migrate legacy blocks in ALL widgets (one-time scan during upgrade).
	 *
	 * @return int Number of widgets migrated.
	 */
	private static function migrate_all_widgets() {
		$sidebars_widgets    = wp_get_sidebars_widgets();
		$widget_block_option = get_option( 'widget_block', array() );

		if ( ! is_array( $widget_block_option ) ) {
			return 0;
		}

		$migrated_count = 0;

		foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
			if ( in_array( $sidebar_id, array( 'wp_inactive_widgets', 'array_version' ), true ) ) {
				continue;
			}

			if ( ! is_array( $widget_ids ) ) {
				continue;
			}

			foreach ( $widget_ids as $widget_id ) {
				// Only process block widgets.
				if ( 0 !== strpos( $widget_id, 'block-' ) ) {
					continue;
				}

				$numeric_id = (int) str_replace( 'block-', '', $widget_id );

				if ( ! isset( $widget_block_option[ $numeric_id ]['content'] ) ) {
					continue;
				}

				$content = $widget_block_option[ $numeric_id ]['content'];

				// Fast check for ANY S&F blocks (skip if none present).
				if ( ! \Search_Filter\Integrations\Gutenberg\Block_Parser::has_block( $content ) ) {
					continue;
				}

				// Parse and migrate.
				if ( ! has_blocks( $content ) ) {
					continue;
				}

				$blocks      = parse_blocks( $content );
				$has_changes = false;

				$transformed = self::transform_blocks_recursive( $blocks, $has_changes );

				if ( $has_changes ) {
					$widget_block_option[ $numeric_id ]['content'] = serialize_blocks( $transformed );
					++$migrated_count;
				}
			}
		}

		// Save all changes at once.
		if ( $migrated_count > 0 ) {
			update_option( 'widget_block', $widget_block_option );
			\Search_Filter\Util::error_log( 'Migrated legacy blocks in ' . $migrated_count . ' widgets', 'notice' );
		}

		return $migrated_count;
	}
}
