<?php
/**
 * Gutenberg Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Integrations
 */

namespace Search_Filter\Integrations\Gutenberg;

use Search_Filter\Admin\Screens;
use Search_Filter\Fields;
use Search_Filter\Fields\Field;
use Search_Filter\Integrations;
use Search_Filter\Integrations\Gutenberg as Gutenberg_Integration;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Legacy Blocks functionality.
 */
class Legacy_Blocks {

	/**
	 * Init
	 *
	 * @since    3.2.0
	 */
	public static function init() {
		// Only hook once we know the settings (features) are loaded.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'setup' ), 1 );

		// Preload the legacy block option (and set a default to prevent repeat lookups).
		add_filter( 'search-filter/options/preload', array( __CLASS__, 'preload_option' ) );
	}

	/**
	 * Setup the legacy blocks integration.
	 *
	 * @since 3.2.0
	 */
	public static function setup() {
		if ( ! Integrations::is_enabled( 'blockeditor' ) ) {
			return;
		}

		// Pass legacy block data to editor settings.
		add_filter( 'block_editor_settings_all', array( __CLASS__, 'filter_editor_settings' ), 100, 2 );

		add_action( 'init', array( __CLASS__, 'register_legacy_blocks' ), 21 ); // Make sure we register after our regular blocks.

		// Update fields when blocks are updated.
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 20, 2 );

		// When a post is deleted, remove entries from our tables.
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), 10, 1 );
		add_action( 'rest_delete_widget', array( __CLASS__, 'delete_widget' ), 20, 1 );
	}


	/**
	 * Preload the features option.
	 *
	 * @since 3.2.0
	 *
	 * @param array $options_to_preload The options to preload.
	 * @return array
	 */
	public static function preload_option( $options_to_preload ) {
		$options_to_preload[] = array( 'gutenberg-has-legacy-blocks', 'no' );
		return $options_to_preload;
	}

	/**
	 * Add custom category to block inserter.
	 *
	 * @since 3.0.0
	 *
	 * @param array $categories Array of block categories.
	 * @return array
	 */
	public static function block_categories( $categories ) {
		$categories[] = array(
			'slug'  => 'search-filter',
			'title' => __( 'Search & Filter', 'search-filter' ),
		);
		return $categories;
	}

	/**
	 * Register the legacy pre 3.2.0 blocks.
	 *
	 * @since    3.2.0
	 */
	public static function register_legacy_blocks() {
		// If we're on one of our admin screens, then we don't need to load our own blocks.
		if ( Screens::is_search_filter_screen() ) {
			return;
		}

		$blocks_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'legacy-blocks' . DIRECTORY_SEPARATOR;

		$asset_handle = Gutenberg_Integration::get_asset_handle();

		// Always register the re-usable field block via PHP as we can't
		// automatically migrate them and we need them on the frontend.
		register_block_type(
			$blocks_dir . 'reusable' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_reusable_field' ),
			)
		);

		// Determine which legacy blocks to register.
		$has_general_legacy = \Search_Filter\Options::get( 'gutenberg-has-legacy-blocks', 'no' );

		if ( $has_general_legacy === 'no' ) {
			return;
		}

		// Only register general legacy blocks if we found them (ie via the upgrader/migration).
		register_block_type(
			$blocks_dir . 'search' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				// Don't use style_handles so we can manually lazy load our frontend styles, otherwise
				// the block editor loads `search-filter-frontend` on every page even if the block is not
				// used.
				// 'style_handles'        => array( 'search-filter-frontend' ).
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_search_field' ),
			)
		);
		register_block_type(
			$blocks_dir . 'choice' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_choice_field' ),
			)
		);
		register_block_type(
			$blocks_dir . 'range' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_range_field' ),
			)
		);
		register_block_type(
			$blocks_dir . 'advanced' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_advanced_field' ),
			)
		);
		register_block_type(
			$blocks_dir . 'control' . DIRECTORY_SEPARATOR,
			array(
				'editor_script'        => $asset_handle,
				'editor_style_handles' => array( $asset_handle, 'search-filter-frontend' ),
				'render_callback'      => array( __CLASS__, 'render_control_field' ),
			)
		);
	}

	/**
	 * Renders a fields html using block attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_field( $block_attributes ) {
		$args = array();
		$args = wp_parse_args( $args, $block_attributes );

		if ( ! isset( $block_attributes['fieldId'] ) ) {
			return '';
		}
		ob_start();
		$field = Field::get_instance( absint( $block_attributes['fieldId'] ) );
		if ( is_wp_error( $field ) ) {
			echo esc_html( $field->get_error_message() );
			return ob_get_clean();
		}

		$wrapper_attributes = \WP_Block_Supports::get_instance()->apply_block_supports();

		// We can have `style`, `class`, `id`, or `aria-label` in the wrapper attributes.
		foreach ( $wrapper_attributes as $attribute_name => $attribute_value ) {
			if ( $attribute_name === 'class' ) {
				$field->add_html_class( $attribute_value, 'before' );
			} else {
				$field->add_html_attribute( $attribute_name, $attribute_value );
			}
		}
		$field->render();
		$output = ob_get_clean();

		return $output;
	}


	/**
	 * Renders a search field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_search_field( $block_attributes ) {
		$block_attributes['type'] = 'search';
		if ( ! isset( $block_attributes['inputType'] ) ) {
			$block_attributes['inputType'] = 'search-text';
		}
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a choice field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_choice_field( $block_attributes ) {
		$block_attributes['type'] = 'choice';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a range field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_range_field( $block_attributes ) {
		$block_attributes['type'] = 'range';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a advanced field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_advanced_field( $block_attributes ) {
		$block_attributes['type'] = 'advanced';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a control field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_control_field( $block_attributes ) {
		$block_attributes['type'] = 'control';
		return self::render_field( $block_attributes );
	}
	/**
	 * Renders a reusable field.
	 *
	 * @since 3.0.0
	 *
	 * @param array $block_attributes  The saved block attributes.
	 * @return string
	 */
	public static function render_reusable_field( $block_attributes ) {
		$field_id = absint( $block_attributes['fieldId'] );
		$field    = Field::get_instance( $field_id );

		$display_errors = is_user_logged_in() && current_user_can( 'manage_options' );

		if ( is_wp_error( $field ) ) {
			if ( $display_errors ) {
				return '<em>' . __( 'Field not found', 'search-filter' ) . '</em>';
			}
			return '';
		}

		if ( $field->get_status() !== 'enabled' ) {
			if ( $display_errors ) {
				return '<em>' . __( 'Field not enabled', 'search-filter' ) . '</em>';
			}
			return '';
		}

		$wrapper_attributes = \WP_Block_Supports::get_instance()->apply_block_supports();

		// We can have `style`, `class`, `id`, or `aria-label` in the wrapper attributes.
		foreach ( $wrapper_attributes as $attribute_name => $attribute_value ) {
			if ( $attribute_name === 'class' ) {
				$field->add_html_class( $attribute_value, 'before' );
			} else {
				$field->add_html_attribute( $attribute_name, $attribute_value );
			}
		}

		return $field->render( true );
	}

	/**
	 * Get the post fields records.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array
	 */
	public static function get_post_field_records( $post_id ) {
		$query_args                  = array();
		$query_args['context']       = 'block-editor';
		$query_args['context_path']  = 'post/' . $post_id;
		$block_editor_fields_records = Fields::find( $query_args, 'records' );
		return $block_editor_fields_records;
	}

	/**
	 * Detects when our blocks are saved and stores the settings in our fields table.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $post_id The post ID.
	 * @param object $post The post object.
	 */
	public static function save_post( $post_id, $post ) {

		if ( wp_is_post_revision( $post ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post ) ) {
			return;
		}

		// Edge case - sometimes a post can be NULL and still trigger `save_post`.
		if ( ! $post ) {
			return;
		}

		// Edge case - sometimes a post's content can be NULL.
		if ( ! $post->post_content ) {
			return;
		}

		$updated_field_ids = array();
		if ( has_blocks( $post ) ) {
			// Check for any saved blocks and update their attributes.
			$updated_field_ids = self::get_field_ids_from_blocks_content( $post->post_content );
		}

		if ( empty( $updated_field_ids ) ) {
			return;
		}

		// TODO - although not strictly necessary right now, we probably want to add IDs for fields that
		// don't have fieldId, usually this would be considered an error as one should be generated when
		// adding a post, but in the future it may be possible to create block editor posts server side,
		// and doing this would support that use case.

		// We also need to check our DB for any fields that are no longer in use and delete them.
		// TODO - check how this is working with the site editor updates.
		$post_field_records = self::get_post_field_records( $post_id );

		foreach ( $post_field_records as $item ) {
			// If there are fields assigned to this post, but no longer connected to any blocks
			// then we need to delete them.
			if ( ! in_array( $item->get_id(), $updated_field_ids, true ) ) {
				Field::destroy( $item->get_id() );
			}
		}
	}

	/**
	 * Returns an array containing the references of
	 * the passed blocks and their inner blocks.
	 * Taken from wp-includes/block-template-utils.phjp
	 *
	 * @since 3.0.0
	 *
	 * @param array $blocks array of blocks.
	 *
	 * @return array block references to the passed blocks and their inner blocks.
	 */
	public static function flatten_blocks( &$blocks ) {
		$all_blocks = array();
		$queue      = array();
		foreach ( $blocks as &$block ) {
			$queue[] = &$block;
		}

		$queue_count = count( $queue );
		while ( $queue_count > 0 ) {
			$block = &$queue[0];
			array_shift( $queue );
			$queue_count  = count( $queue );
			$all_blocks[] = &$block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as &$inner_block ) {
					$queue[] = &$inner_block;
				}
			}
		}

		return $all_blocks;
	}

	/**
	 * Update fields from blocks content.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content from the post.
	 *
	 * @return array The updated fields.
	 */
	public static function get_field_ids_from_blocks_content( $content ) {
		// Check for any saved blocks and update their attributes.
		$blocks = parse_blocks( $content );
		// TODO - maybe we should just parse this ourselves (the code for this is already on
		// github), and only look for our blocks.
		// Would surely be a faster regex as we don't care about the rest, or if blocks
		// are nested.

		$blocks = self::flatten_blocks( $blocks );
		// TODO - we should be able to extend these - so we can
		// include the range and advanced only in the pro plugin.
		$block_types_to_find = array(
			'search-filter/search',
			'search-filter/choice',
			'search-filter/range',
			'search-filter/advanced',
			'search-filter/control',
		);

		// Track which fields are in use.
		$updated_field_ids = array();
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && in_array( $block['blockName'], $block_types_to_find, true ) ) {
				$attributes = $block['attrs'];
				// Update saved field attributes based on block attributes.
				if ( isset( $attributes['fieldId'] ) ) {
					$updated_field_ids[] = absint( $attributes['fieldId'] );
				}
			}
		}

		return $updated_field_ids;
	}

	/**
	 * On delete widget, delete related fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $widget_id The widget ID.
	 */
	public static function delete_widget( $widget_id ) {
		// Lookup field with this widget ID.
		$query_args            = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for widget field lookup.
			'meta_query' => array(
				array(
					'key'     => 'widget_id',
					'value'   => $widget_id,
					'compare' => '=',
				),
			),
		);
		$widget_fields_records = Fields::find( $query_args, 'records' );

		if ( count( $widget_fields_records ) === 0 ) {
			return;
		}

		// Delete the field.
		foreach ( $widget_fields_records as $item ) {
			Field::destroy( $item->get_id() );
		}
	}


	/**
	 * Remove fields from our tables when a post is deleted.
	 *
	 * @param number $post_id The post ID.
	 * @return void
	 */
	public static function delete_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$post_field_records = self::get_post_field_records( $post_id );

		foreach ( $post_field_records as $item ) {
			Field::destroy( $item->get_id() );
		}
	}

	/**
	 * Filter block editor settings to get the context and setup our block editor data.
	 *
	 * Block_editor_settings_all cannot be used to add new/custom settings to the editor
	 * so we'll use it to grab the context and add our own inline script with the relevant
	 * data.
	 *
	 * @param array                    $settings       Default editor settings.
	 * @param \WP_Block_Editor_Context $editor_context The current block editor context.
	 * @return array Modified editor settings.
	 */
	public static function filter_editor_settings( $settings, $editor_context ) {

		$supported_contexts = array(
			'core/edit-post',
			'core/edit-site',
			'core/edit-widgets',
		);
		if ( ! in_array( $editor_context->name, $supported_contexts, true ) ) {
			return $settings;
		}

		$has_general_legacy = \Search_Filter\Options::get( 'gutenberg-has-legacy-blocks', 'no' );
		$has_reusable_field = false;

		// Check current context for reusable-field blocks.
		// Note: Widgets are already migrated during upgrade, so we only check posts/templates.
		if ( 'core/edit-site' === $editor_context->name ) {
			// Site editor context - check the template being edited.
			// Right now there is no way to check which template is being edited
			// as they are loaded via ajax and there could be multiple.
			$has_reusable_field = true;
		} elseif ( ! empty( $editor_context->post ) ) {
			// Then we're on a single post edit screen.
			$has_reusable_field = Block_Parser::has_block(
				$editor_context->post->post_content,
				'reusable-field'
			);
		}

		$data   = array(
			'hasGeneralLegacyBlocks' => $has_general_legacy === 'no' ? false : true,
			'hasReusableField'       => $has_reusable_field,
		);
		$script = '
		if ( ! window.searchAndFilter.admin ) {
			window.searchAndFilter.admin = {};
		}
		window.searchAndFilter.admin.legacyBlockData = ' . wp_json_encode( $data ) . ';';
		wp_add_inline_script( 'search-filter-gutenberg', $script, 'before' );

		return $settings;
	}
}
