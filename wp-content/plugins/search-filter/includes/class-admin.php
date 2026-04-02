<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter
 */

namespace Search_Filter;

use Search_Filter\Admin\Screens;
use Search_Filter\Core\Asset_Loader;
use Search_Filter\Core\Scripts;
use Search_Filter\Core\Icons;
use Search_Filter\Fields\Field;
use Search_Filter\Fields\Settings_Data;
use Search_Filter\Queries\Query;
use Search_Filter\Styles\Style;
use Search_Filter\Features;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all Admin facing functionality
 */
class Admin {


	/**
	 * Whether to load the full block editor assets.
	 *
	 * @since 3.2.0
	 * @var bool
	 */
	private static $should_load_full_block_editor = false;

	/**
	 * Initialize the admin functionality.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// Scripts & css.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ), 10 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 10 );
		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );

		// Data.

		// Setup Admin Screens.
		add_filter( 'init', array( Screens::class, 'init' ), 2 );
		add_filter( 'submenu_file', array( Screens::class, 'modify_active_submenu' ), 20, 2 );
		add_action( 'admin_menu', array( Screens::class, 'admin_pages' ), 9 );
		add_action( 'admin_menu', array( Screens::class, 'admin_pages_more_menu_items' ), 10 );
		add_action( 'admin_footer', array( Screens::class, 'admin_footer' ), 20 );
		add_action( 'admin_head', array( Screens::class, 'menu_css' ), 20 );

		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ), 20 );

		// We want to get in as early as possible, and remove all admin notices
		// This is the closest action before admin_notices that we can find.
		add_action( 'in_admin_header', array( Screens::class, 'remove_admin_notices' ), 200 );

		// Block editor hooks to enable the correct assets loading on our screens.
		add_filter( 'should_load_block_editor_scripts_and_styles', array( __CLASS__, 'set_block_editor_script_and_styles' ), 10 );
		add_action( 'current_screen', array( __CLASS__, 'set_screen_to_block_editor' ), 10 );
	}

	/**
	 * Register the assets for the admin area.
	 *
	 * @since 3.2.0
	 */
	public static function register_assets() {

		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		// Register the frontend assets.
		\Search_Filter\Frontend::register_assets();

		// Load all registered components assets.
		\Search_Filter\Components::register_assets();

		// Setup the JS data for the admin screen.
		$admin_data                   = array();
		$admin_data['editorSettings'] = wp_parse_args( self::get_editor_settings(), self::get_default_editor_settings() );

		// Inject frontend + component CSS into editor styles pipeline so
		// EditorStyles scopes our selectors with .editor-styles-wrapper,
		// giving them equal specificity to theme styles in field previews.
		$all_assets  = \Search_Filter\Frontend::get_registered_assets();
		$plugins_url = plugins_url();
		foreach ( $all_assets as $handle => $asset ) {
			if ( strpos( $handle, 'ugc' ) !== false ) {
				continue;
			}
			if ( empty( $asset['style']['src'] ) ) {
				continue;
			}
			$file_path = str_replace( $plugins_url, WP_PLUGIN_DIR, $asset['style']['src'] );
			if ( file_exists( $file_path ) ) {
				$css = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local plugin CSS file, not a remote URL.
				if ( $css ) {
					$admin_data['editorSettings']['styles'][] = array(
						'css' => $css,
					);
				}
			}
		}

		$admin_data['editor']      = array(
			'previewContainerClasses' => array(),
		);
		$admin_data['editor']      = apply_filters( 'search-filter/admin/editor/settings', $admin_data['editor'] );
		$admin_data['path']        = wp_parse_url( admin_url(), PHP_URL_PATH );
		$admin_data['restNonce']   = wp_create_nonce( 'wp_rest' );
		$admin_data['popoverNode'] = Features::get_setting_value( 'compatibility', 'popoverNode' );

		$basic_settings  = Settings_Data::get_basic_settings();
		$layout_settings = Settings_Data::get_layout_settings();
		$color_settings  = array_values(
			array_filter(
				array_merge( $basic_settings, $layout_settings ),
				function ( $setting ) {
					$meta_type = $setting['style']['type'] ?? '';

					return $meta_type === 'color';
				}
			)
		);

		$admin_data['colorSettings'] = array_reduce(
			$color_settings,
			function ( $carry, $item ) {
				$group_value = Settings_Data::get_property( $item, 'style.group' );

				if ( $group_value ) {
					$carry[ $group_value ][] = $item;
				}

				return $carry;
			},
			array()
		);

		// Register dynamic (aka lazy loaded) screens.
		$admin_data['dynamicScreens'] = self::register_dynamic_screens();

		// Register the admin assets.
		$asset_configs = array(
			array(
				'name'   => 'search-filter-admin',
				'script' => array(
					'src'          => SEARCH_FILTER_URL . 'assets/admin/app.js',
					'asset_path'   => SEARCH_FILTER_PATH . 'assets/admin/app.asset.php',
					'dependencies' => array( 'search-filter-frontend' ), // Additional dependencies.
					'footer'       => true,
					'data'         => array(
						'identifier' => 'window.searchAndFilter.admin',
						'value'      => $admin_data,
						'position'   => 'before',
					),
				),
				'style'  => array(
					'src'          => SEARCH_FILTER_URL . 'assets/admin/app.css',
					'dependencies' => array( 'wp-components', 'wp-block-editor', 'search-filter-frontend' ),
				),
			),
		);

		$assets = Asset_Loader::create( $asset_configs );
		Asset_Loader::register( $assets );

		/*
		 * These two scripts should technically only be added if our check to
		 * self::should_load_block_editor_script_and_styles() returns true, however many plugins still try
		 * to load block editor assets based on the screen setting `is_block_editor()` or just the
		 * presence of the action `enqueue_block_editor_assets`
		 *
		 * They're added so that we don't get JS warnings/errors in the console.
		 *
		 * Copied from ./wp-admin/edit-form-blocks.php
		*/

		// Needed to pass the apiVersion to the block editor and prevent warnings.
		wp_add_inline_script(
			'wp-blocks',
			'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' .
			wp_json_encode( get_block_editor_server_block_settings(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) .
			');'
		);

		$editor_settings = $admin_data['editorSettings'];
		// Needed to setup block categories and prevent "The block {name} is registered with an invalid
		// category {category}" warnings.
		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( isset( $editor_settings['blockCategories'] ) ? $editor_settings['blockCategories'] : array(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ),
			'after'
		);
	}

	/**
	 * Setup our admin screen to  `is_block_editor` as soon as the screen
	 * name is ready.
	 *
	 * @since 3.2.0
	 *
	 * @param \WP_Screen $current_screen The current screen object.
	 */
	public static function set_screen_to_block_editor( $current_screen ) {
		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		if ( ! $current_screen->is_block_editor() ) {
			$current_screen->is_block_editor( true );
		}
	}

	/**
	 * Enqueue the assets for the admin area.
	 *
	 * @since 3.0.0
	 */
	public static function enqueue_assets() {
		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		// Enqueue the frontend assets (minus UGC).
		$exclude_handles = array(
			'search-filter-frontend-ugc',
			'search-filter-ugc-styles', // Legacy UGC handle.
		);
		\Search_Filter\Frontend::enqueue_assets( $exclude_handles, true );
		// Enqueue all registered components assets.
		\Search_Filter\Components::enqueue_assets( true );

		// Load the icons.
		Icons::load();

		// Enqueue the block editor assets.
		do_action( 'enqueue_block_editor_assets' );

		// Enqueue the admin assets.
		Asset_Loader::enqueue( array( 'search-filter-admin' ) );

		$preload_paths = self::get_preload_api_paths();
		Scripts::preload_api_requests( $preload_paths );

		// Remove directory assets we don't need.
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}

	/**
	 * Get the rest api paths to preload based current screen and url args.
	 *
	 * @return array The rest api paths to preload.
	 */
	private static function get_preload_api_paths() {
		// Setup defaults.
		$section = '';
		$context = '';
		$status  = '';
		$orderby = '';
		$order   = '';
		$search  = '';
		$paged   = '';
		$id      = -1;

		$preload_paths = array(
			'/search-filter/v1/admin/data',
			'/search-filter/v1/admin/pages',
			'/search-filter/v1/integrations',
			'/search-filter/v1/admin/field-input-types',
			'/search-filter/v1/admin/styles/defaults/preset',
			'/search-filter/v1/admin/styles/tokens',

			// Load the admin settings objects.
			'/search-filter/v1/admin/settings?section=queries',
			'/search-filter/v1/admin/settings?section=fields',
			'/search-filter/v1/admin/settings?section=styles',
			'/search-filter/v1/admin/settings?section=features',
			'/search-filter/v1/admin/settings?section=debugger',
			'/search-filter/v1/admin/settings?section=dynamic-assets',
			'/search-filter/v1/admin/settings?section=integrations',

			// Load the settings data.
			// TODO - this is a newer endpoint for loading data, which also
			// supplies defaults where necessary - we should migrate over
			// to using this where possible.
			'/search-filter/v1/settings?section=features',
			'/search-filter/v1/settings?section=debugger',
			'/search-filter/v1/settings?section=integrations',
			'/search-filter/v1/settings?section=dynamic-assets',

			'/search-filter/v1/admin/screen/options',
			'/search-filter/v1/admin/screen/dashboard',
			'/search-filter/v1/admin/notices',
		);

		// Parse URL args.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading URL parameters for admin display navigation only, no state changes.
		$rest_args = array();
		if ( isset( $_GET['section'] ) ) {
			$section = sanitize_key( $_GET['section'] );
		}
		if ( isset( $_GET['status'] ) ) {
			$status              = sanitize_key( $_GET['status'] );
			$rest_args['status'] = $status;

		}
		if ( isset( $_GET['orderby'] ) ) {
			$orderby              = sanitize_key( $_GET['orderby'] );
			$rest_args['orderby'] = $orderby;
		}
		if ( isset( $_GET['order'] ) ) {
			$order              = sanitize_key( $_GET['order'] );
			$rest_args['order'] = $order;
		}
		if ( isset( $_GET['context'] ) ) {
			$context              = sanitize_key( $_GET['context'] );
			$rest_args['context'] = $context;
		}
		if ( isset( $_GET['search'] ) ) {
			// Try to match what encodeURIComponent does (JS function) as that is the route we need to match.
			$revert              = array(
				'%21' => '!',
				'%2A' => '*',
				'%27' => "'",
				'%28' => '(',
				'%29' => ')',
			);
			$search              = strtr( rawurlencode( sanitize_text_field( stripslashes_deep( $_GET['search'] ) ) ), $revert );
			$rest_args['search'] = $search;
		}
		if ( isset( $_GET['paged'] ) ) {
			$paged              = sanitize_key( $_GET['paged'] );
			$rest_args['paged'] = $paged;
		}
		if ( isset( $_GET['edit'] ) ) {
			$id = absint( $_GET['edit'] );
		} elseif ( isset( $_GET['new'] ) ) {
			$id = 0;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Lets preload the initial set of results for all sections.
		$items_per_page = 10;
		$screen_options = Screens::get_admin_screen_options();

		$sections = array( 'queries', 'fields', 'styles' );
		// Get the items per page setting to add the to api preload path.
		foreach ( $sections as $section_name ) {
			if ( isset( $screen_options[ $section_name ] ) && isset( $screen_options[ $section_name ]['itemsPerPage'] ) ) {
				$items_per_page = absint( $screen_options[ $section_name ]['itemsPerPage'] );
			}
			$preload_paths[] = '/search-filter/v1/records/' . $section_name . '?per_page=' . $items_per_page;
			$preload_paths[] = '/search-filter/v1/records/counts/' . $section_name;
		}
		// The initial dropdown for the query filter on the fields list screen.
		$preload_paths[] = '/search-filter/v1/records/queries?per_page=20&search=';

		$preload_paths[] = '/search-filter/v1/settings/options/post-types';
		$preload_paths[] = '/search-filter/v1/settings/options/taxonomies';
		$preload_paths[] = '/search-filter/v1/settings/options/post-stati';
		$preload_paths[] = '/search-filter/v1/settings/results-url?integrationType=search';
		$preload_paths[] = '/search-filter/v1/settings/options/queries';
		$preload_paths[] = '/search-filter/v1/settings/options/styles';
		$preload_paths[] = '/search-filter/v1/data/post-types';

		// Preload the first query in list of options as that's what's used for new fields.
		$preload_paths[] = '/search-filter/v1/settings/options/queries';

		$first_query_id = Queries::get_queries_list_first_id();
		if ( $first_query_id > 0 ) {
			$preload_paths[] = '/search-filter/v1/records/queries/' . $first_query_id;
		}

		if ( $id > 0 ) {
			$preload_paths[] = '/search-filter/v1/records/' . $section . '/' . $id;
		}

		if ( 'queries' === $section && $id > 0 ) {
			// Then lets preload the connected fields.
			$preload_paths[] = '/search-filter/v1/settings/options/post-types?queryId=' . absint( $id );

			$query = Query::get_instance( $id );
			if ( ! is_wp_error( $query ) ) {
				$integration_type = $query->get_attribute( 'integrationType' );
				if ( $integration_type === 'single' ) {
					$selected_post_id = absint( $query->get_attribute( 'singleLocation' ) );
					if ( $selected_post_id > 0 ) {
						$preload_paths[] = '/search-filter/v1/data/post?id=' . absint( $selected_post_id );
						$preload_paths[] = '/search-filter/v1/settings/results-url?integrationType=single&singleLocation=' . absint( $selected_post_id );
					}
				} elseif ( $integration_type === 'archive' ) {
					$archive_type = $query->get_attribute( 'archiveType' );
					if ( $archive_type === 'post_type' ) {
						$post_type = $query->get_attribute( 'archivePostType' );
						if ( $post_type ) {
							$query_id        = absint( $id );
							$preload_paths[] = "/search-filter/v1/settings/results-url?integrationType={$integration_type}&archiveType={$archive_type}&archivePostType={$post_type}";
							$preload_paths[] = "/search-filter/v1/settings/options/query/archive/taxonomies?queryId={$query_id}&archivePostType={$post_type}";
						}
					} elseif ( $archive_type === 'taxonomy' ) {
						$taxonomy = $query->get_attribute( 'archiveTaxonomy' );
						if ( $taxonomy ) {
							$preload_paths[] = "/search-filter/v1/settings/results-url?integrationType={$integration_type}&archiveType={$archive_type}&archiveTaxonomy={$taxonomy}";
						}
						$taxonomy_filter_terms = $query->get_attribute( 'archiveTaxonomyFilterTerms' );
						if ( $taxonomy_filter_terms === 'custom' ) {
							$preload_paths[] = "/search-filter/v1/settings/options/query/archive/taxonomy_terms?queryId={$id}&archiveTaxonomy={$taxonomy}";
						}
					}
				}
			}
		} elseif ( 'fields' === $section ) {
			// Always preload the default style.
			$default_style_id = Styles::get_default_styles_id();
			$preload_paths[]  = '/search-filter/v1/records/styles/' . $default_style_id;

			// Preload the currently connected style.
			if ( $id > 0 ) {
				$style = Field::find( array( 'id' => absint( $id ) ), 'record' );
				if ( ! is_wp_error( $style ) ) {
					$style_attributes = $style->get_attributes();
					$styles_id        = isset( $style_attributes['stylesId'] ) ? absint( $style_attributes['stylesId'] ) : 0;
					if ( $styles_id !== 0 ) {
						$preload_paths[] = '/search-filter/v1/records/styles/' . $styles_id;
					}
					// Preload the currently connected query.
					$query_id = isset( $style_attributes['queryId'] ) ? absint( $style_attributes['queryId'] ) : 0;
					if ( $query_id !== 0 ) {
						$preload_paths[] = '/search-filter/v1/records/queries/' . $query_id;
						$preload_paths[] = '/search-filter/v1/settings/options/post-types?queryId=' . absint( $query_id );
						$preload_paths[] = '/search-filter/v1/settings/options/query/taxonomies?queryId=' . absint( $query_id );
					}
				}
			}
		} elseif ( 'styles' === $section ) {
			if ( $id > 0 ) {
				$style = Style::find( array( 'id' => absint( $id ) ), 'record' );
				if ( ! is_wp_error( $style ) ) {
					$styles_id = $style->get_id();
					if ( $styles_id !== 0 ) {
						$preload_paths[] = '/search-filter/v1/records/styles/' . $styles_id;
					}
				}
			}
		}

		$preload_paths = apply_filters( 'search-filter/admin/get_preload_api_paths', $preload_paths );

		return $preload_paths;
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @since 3.0.0
	 * @param array  $links      Plugin action links.
	 * @param string $plugin_file Plugin file.
	 * @return array Modified plugin action links.
	 */
	public static function plugin_action_links( $links, $plugin_file ) {
		if ( $plugin_file !== 'search-filter/search-filter.php' ) {
			return $links;
		}

		$links['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=search-filter' ) ) . '">' . esc_html__( 'Settings', 'search-filter' ) . '</a>';
		return $links;
	}

	/**
	 * Add custom CSS class to admin body.
	 *
	 * @since 3.0.0
	 * @param string $classes Space-separated list of CSS classes.
	 * @return string Modified CSS classes.
	 */
	public static function admin_body_class( $classes ) {
		if ( Screens::is_search_filter_screen() ) {
			$classes .= ' search-filter-admin-screen';
		}
		return $classes;
	}

	/**
	 * Register dynamic screens that should be conditionally available.
	 *
	 * @return array Dynamic screens configuration.
	 */
	private static function register_dynamic_screens() {
		$dynamic_screens = array(
			'configs'    => array(),
			'components' => array(),
		);

		// Always register debug logs screen config - lazy loaded only when section=logs.
		// Disabled states (debugMode off, logToDatabase off) handled in REST API.
		$dynamic_screens['configs']['logs'] = array(
			'component'  => 'Logs',
			'scriptUrl'  => SEARCH_FILTER_URL . 'assets/admin-screens/logs.js',
			'cssUrl'     => SEARCH_FILTER_URL . 'assets/admin-screens/logs.css',
			'section'    => 'logs',
			'capability' => 'manage_options',
			'props'      => array(
				'apiNamespace' => 'search-filter/v1',
			),
		);

		// Allow other plugins/extensions to register dynamic screens.
		$dynamic_screens = apply_filters( 'search-filter/admin/screens/dynamic', $dynamic_screens );

		return $dynamic_screens;
	}

	/**
	 * Gets the editor settings for use in our JS editors
	 *
	 * TODO - keep an eye on global styles project as this will all probably change
	 */
	private static function get_editor_settings() {

		$editor_settings = array();

		wp_enqueue_script( 'wp-format-library' );
		wp_enqueue_style( 'wp-format-library' );

		// Need this to trigger so we load theme block editor assets.
		$custom_settings = array(
			'siteUrl' => \site_url(),
			'styles'  => \get_block_editor_theme_styles(),
		);

		// Create a context for our admin screens.
		$context = new \WP_Block_Editor_Context(
			array(
				'name' => 'search-filter/admin',
			)
		);

		$editor_settings = \get_block_editor_settings( $custom_settings, $context );

		// Non GB, lets just support color palette for now.
		if ( empty( $editor_settings['colors'] ) ) {
			$colors = \get_theme_support( 'editor-color-palette' );
			if ( isset( $colors[0] ) ) {
				$editor_settings['colors'] = $colors[0];
			}
		}
		return $editor_settings;
	}

	/**
	 * Get default settings for use in our JS editors
	 *
	 * @return array
	 */
	private static function get_default_editor_settings() {
		$editor_settings = array(
			'alignWide'                   => false,
			'allowedBlockTypes'           => true,
			'isRTL'                       => is_rtl(),
			'imageEditing'                => true,
			'imageSizes'                  => array(
				'thumbnail' => array(
					'name' => 'Thumbnail',
				),
				'medium'    => array(
					'name' => 'Medium',
				),
				'large'     => array(
					'name' => 'Large',
				),
				'full'      => array(
					'name' => 'Full Size',
				),
			),
			'disableCustomColors'         => false,
			'disableCustomFontSizes'      => false,
			'disableCustomGradients'      => false,
			'disableLayoutStyles'         => false,
			'enableCustomLineHeight'      => true,
			'enableCustomSpacing'         => true,
			'enableCustomUnits'           => array(
				'%',
				'px',
				'em',
				'rem',
				'vh',
				'vw',
			),
			'styles'                      => array(),
			'__experimentalFeatures'      => array(
				'appearanceTools'               => false,
				'useRootPaddingAwareAlignments' => true,
				// 'typography' => false, // TODO.
				'border'                        => array(
					'color'  => true,
					'radius' => true,
					'style'  => true,
					'width'  => true,
				),
				'spacing'                       => array(
					'blockGap'            => true,
					'margin'              => true,
					'defaultSpacingSizes' => false,

					'spacingScale'        => array(
						'default' => array(
							'operator'   => '*',
							'increment'  => 1.5,
							'steps'      => 7,
							'mediumStep' => 1.5,
							'unit'       => 'rem',
						),
					),
					'spacingSizes'        => array(
						'default' => array(
							array(
								'name' => '2X-Small',
								'slug' => 'search-filter-20',
								'size' => '0.44rem',
							),
							array(
								'name' => 'X-Small',
								'slug' => 'search-filter-30',
								'size' => '0.67rem',
							),
							array(
								'name' => 'Small',
								'slug' => 'search-filter-40',
								'size' => '1rem',
							),
							array(
								'name' => 'Medium',
								'slug' => 'search-filter-50',
								'size' => '1.5rem',
							),
							array(
								'name' => 'Large',
								'slug' => 'search-filter-60',
								'size' => '2.25rem',
							),
							array(
								'name' => 'X-Large',
								'slug' => 'search-filter-70',
								'size' => '3.38rem',
							),
							array(
								'name' => '2X-Large',
								'slug' => 'search-filter-80',
								'size' => '5.06rem',
							),
						),
						'theme'   => array(
							array(
								'name' => '1',
								'size' => '1rem',
								'slug' => '10',
							),
							array(
								'name' => '2',
								'size' => 'min(1.5rem, 2vw)',
								'slug' => '20',
							),
							array(
								'name' => '3',
								'size' => 'min(2.5rem, 3vw)',
								'slug' => '30',
							),
							array(
								'name' => '4',
								'size' => 'min(4rem, 5vw)',
								'slug' => '40',
							),
							array(
								'name' => '5',
								'size' => 'min(6.5rem, 8vw)',
								'slug' => '50',
							),
							array(
								'name' => '6',
								'size' => 'min(10.5rem, 13vw)',
								'slug' => '60',
							),
						),
					),
				),
			),
			'disableCustomSpacingSizes'   => false,
			'__unstableIsBlockBasedTheme' => true, // TODO.
			'localAutosaveInterval'       => 15,
		);

		return $editor_settings;
	}

	/**
	 * Filter to determine if we should load the full block editor scripts and styles.
	 *
	 * @since 3.2.0
	 *
	 * @param bool $should_load Whether to load the block editor scripts and styles.
	 * @return bool Modified value.
	 */
	public static function set_block_editor_script_and_styles( $should_load ) {
		if ( ! Screens::is_search_filter_screen() ) {
			return $should_load;
		}
		return self::should_load_block_editor_script_and_styles();
	}

	/**
	 * Determine if we should load the full block editor scripts and styles.
	 *
	 * We nearly always have this as false, however in the future we'll likely want to
	 * load the full block editor.
	 *
	 * @since 3.2.0
	 *
	 * @return bool Whether to load the full block editor scripts and styles.
	 */
	public static function should_load_block_editor_script_and_styles() {
		return apply_filters( 'search-filter/admin/should_load_full_block_editor', self::$should_load_full_block_editor );
	}
}
