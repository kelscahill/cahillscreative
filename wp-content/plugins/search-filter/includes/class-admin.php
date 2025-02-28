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
use Search_Filter\Core\Exception;
use Search_Filter\Core\Scripts;
use Search_Filter\Util;
use Search_Filter\Core\Icons;
use Search_Filter\Fields\Field;
use Search_Filter\Queries\Query;
use Search_Filter\Styles\Style;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all Admin facing functionality
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param  string $plugin_name The name of this plugin.
	 * @param  string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Remove UGC styles from admin.
	 */
	public function remove_ugc_styles( $styles ) {

		$position = array_search( 'search-filter-ugc-styles', $styles, true );
		if ( $position !== false ) {
			unset( $styles[ $position ] );
		}
		return $styles;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {

		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}
		Icons::load();

		// TODO - I'm not sure instantiating the frontend class is the best way to do this.
		// I prefer to keep the frontend or script loader class as a singleton.
		add_filter( 'search-filter/frontend/enqueue_styles', array( $this, 'remove_ugc_styles' ) );
		$plugin_frontend = new \Search_Filter\Frontend( $this->plugin_name, $this->version );
		$plugin_frontend->enqueue_styles();
		remove_filter( 'search-filter/frontend/enqueue_styles', array( $this, 'remove_ugc_styles' ) );

		$registered_styles = array(
			$this->plugin_name . '-flatpickr' => array(
				'src'     => Scripts::get_admin_assets_url() . 'css/vendor/flatpickr.min.css',
				'deps'    => array(),
				'version' => $this->version,
				'media'   => 'all',
			),
			$this->plugin_name . '-screen'    => array(
				'src'     => Scripts::get_admin_assets_url() . 'css/admin/app.css',
				'deps'    => array( 'wp-components' ),
				'version' => $this->version,
				'media'   => 'all',
			),
			$this->plugin_name . '-admin'     => array(
				'src'     => Scripts::get_admin_assets_url() . 'css/admin/admin.css',
				'deps'    => array( 'wp-components', 'wp-block-editor' ),
				'version' => $this->version,
				'media'   => 'all',
			),
		);

		$registered_styles = apply_filters( 'search-filter/admin/register_styles', $registered_styles );

		foreach ( $registered_styles as $handle => $args ) {
			wp_register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
		}

		do_action( 'enqueue_block_editor_assets' );

		$enqueued_styles = array();

		foreach ( $registered_styles as $handle => $args ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
				$enqueued_styles[] = $handle;
			}
		}

		$enqueued_styles = apply_filters( 'search-filter/admin/enqueue_styles', $enqueued_styles );

		foreach ( $enqueued_styles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Get the rest api paths to preload based current screen and url args.
	 *
	 * @return array The rest api paths to preload.
	 */
	private function get_preload_api_paths() {
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
			// '/search-filter/v1/features', // TODO - for some settings we're switching over to a new settings endpoint.
			'/search-filter/v1/admin/field-input-types',
			'/search-filter/v1/admin/styles/default',
			'/search-filter/v1/admin/settings?section=queries',
			'/search-filter/v1/admin/settings?section=fields',
			'/search-filter/v1/admin/settings?section=styles',
			'/search-filter/v1/admin/settings?section=features',
			'/search-filter/v1/admin/settings?section=debugger',
			'/search-filter/v1/settings?section=features', // This is the new endpoint to load the data.
			'/search-filter/v1/settings?section=debugger',
			'/search-filter/v1/admin/settings?section=integrations',
			'/search-filter/v1/admin/screen/options',
			'/search-filter/v1/admin/screen/dashboard',
			'/search-filter/v1/admin/notices',
		);

		// Parse URL args.
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
			$preload_paths[] = '/search-filter/v1/settings/options/taxonomies?queryId=' . absint( $id );

			$query = Query::find( array( 'id' => $id ) );
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
						$post_type = $query->get_attribute( 'postType' );
						if ( $post_type && $post_type !== '' ) {
							$preload_paths[] = "/search-filter/v1/settings/results-url?integrationType={$integration_type}&archiveType={$archive_type}&postType={$post_type}";
						}
					} elseif ( $archive_type === 'taxonomy' ) {
						$taxonomy = $query->get_attribute( 'taxonomy' );
						if ( $taxonomy && $taxonomy !== '' ) {
							$preload_paths[] = "/search-filter/v1/settings/results-url?integrationType={$integration_type}&archiveType={$archive_type}&taxonomy={$taxonomy}";
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
						$preload_paths[] = '/search-filter/v1/settings/options/taxonomies?queryId=' . absint( $query_id );
					}
				}
			}
		} elseif ( 'styles' === $section ) {
			$preload_paths[] = '/search-filter/v1/admin/styles/default';
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
	public function plugin_action_links( $links, $plugin_file ) {
		if ( $plugin_file !== 'search-filter/search-filter.php' ) {
			return $links;
		}

		$links['settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=search-filter' ) ) . '">' . esc_html__( 'Settings', 'search-filter' ) . '</a>';
		return $links;
	}
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

		if ( ! Screens::is_search_filter_screen() ) {
			return;
		}

		// TODO - I'm not sure instantiating the frontend class is the best way to do this.
		// I prefer to keep the frontend or script loader class as a singleton.
		$plugin_frontend = new \Search_Filter\Frontend( $this->plugin_name, $this->version );
		$plugin_frontend->enqueue_scripts();
		$plugin_frontend->add_js_data();

		$asset = require SEARCH_FILTER_PATH . 'assets/js/admin/app.asset.php';

		$registered_scripts = array(
			'search-filter-admin' => array(
				'src'     => Scripts::get_admin_assets_url() . 'js/admin/app.js',
				'deps'    => $asset['dependencies'],
				'version' => $asset['version'],
				'footer'  => true,
			),
		);
		$registered_scripts = apply_filters( 'search-filter/admin/register_scripts', $registered_scripts );

		foreach ( $registered_scripts as $handle => $args ) {
			wp_register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
		}

		$enqueued_scripts = array_keys( $registered_scripts );
		$enqueued_scripts = apply_filters( 'search-filter/admin/enqueue_scripts', $enqueued_scripts );

		foreach ( $enqueued_scripts as $handle ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				wp_enqueue_script( $handle );
			}
		}

		// Setup the JS data for the admin screen.
		$admin_data                   = array();
		$admin_data['editorSettings'] = wp_parse_args( $this->get_editor_settings(), $this->get_default_editor_settings() );
		$admin_data['editor']         = array(
			'previewContainerClasses' => array(),
		);
		$admin_data['editor']         = apply_filters( 'search-filter/admin/editor/settings', $admin_data['editor'] );
		$admin_data['path']           = wp_parse_url( admin_url(), PHP_URL_PATH );
		$admin_data['restNonce']      = wp_create_nonce( 'wp_rest' );

		Scripts::attach_globals(
			'search-filter-admin',
			'admin',
			(object) $admin_data
		);

		$preload_paths = $this->get_preload_api_paths();
		Scripts::preload_api_requests( $preload_paths );

		// Set our admin screen as "block editor" to auto load assets.
		$current_screen = get_current_screen();
		$current_screen->is_block_editor( true );

		// Remove directory assets we don't need.
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}

	/**
	 * Gets the editor settings for use in our JS editors
	 *
	 * TODO - keep an eye on global styles project as this will all probably change
	 */
	public function get_editor_settings() {

		$editor_settings = array();

		wp_enqueue_script( 'wp-format-library' );
		wp_enqueue_style( 'wp-format-library' );

		// Need this to trigger so we load theme block editor assets.
		// TODO - we have an issue with using this + Woocommerce...
		$custom_settings = array(
			'siteUrl' => \site_url(),
			'styles'  => \get_block_editor_theme_styles(),
		);
		$editor_settings = \get_block_editor_settings( $custom_settings, null );

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
	public function get_default_editor_settings() {
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
			/*
			'styles' => array(
				array(
					'css' => '',
					'__unstableType' => 'presets',
					'isGlobalStyles' => true,
				),
				array(
					'assets' => '', // This can be a string of any html, like <svg>...</svg> - which we need.
					'__unstableType' => 'svgs',
					'isGlobalStyles' => false,
				)
			), */
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
	 * Check for plugin updates.
	 *
	 * @since 3.0.0
	 */
	public static function update_plugin() {
		// Setup the updater.
		$edd_updater = new \Search_Filter\Core\Plugin_Updater(
			'https://license.searchandfilter.com',
			SEARCH_FILTER_BASE_FILE,
			array(
				'version' => SEARCH_FILTER_VERSION,
				'license' => 'search-filter-extension-free',
				'item_id' => 514539,
				'author'  => 'Search & Filter',
				'beta'    => false,
			)
		);
	}
}
