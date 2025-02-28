<?php
/**
 * Sets up the support for the shortcode features.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter/Features
 */

namespace Search_Filter_Pro\Features;

use Search_Filter\Features;
use Search_Filter\Queries\Settings as Queries_Settings;
use Search_Filter\Features\Settings as Features_Settings;
use Search_Filter_Pro\Features\Shortcodes\Rest_API;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {
	public static function init() {

		// Check to make sure the shortcodes feature is enabled.
		if ( ! Features::is_enabled( 'shortcodes' ) ) {
			return;
		}

		Rest_API::init();

		// Hook into the shortcode and display the results if the `results` attribute is set.
		add_filter( 'search-filter/fields/shortcode/override', array( __CLASS__, 'override_shortcode' ), 10, 2 );

		// Add the query integration option to add "shortcode" the dropdown list.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'add_shortcode_integration_settings' ), 1 );

		// Handle the ajax attributes automatically.
		add_filter( 'search-filter/queries/query/get_attributes', array( __CLASS__, 'update_query_attributes' ), 10, 2 );

		// Hide CSS selector options from query editor.
		add_action( 'search-filter/settings/init', array( __CLASS__, 'hide_css_selector_options' ), 10 );
	}

	/**
	 * Add the shortcode integration settings.
	 *
	 * @since 3.0.0
	 */
	public static function add_shortcode_integration_settings() {

		// Get the single integration setting and add the shortcode integration type to it.
		$integration_type_setting = Queries_Settings::get_setting( 'queryIntegration' );
		if ( $integration_type_setting ) {
			$integration_type_option = array(
				'label'     => __( 'Results shortcode', 'search-filter' ),
				'value'     => 'results_shortcode',
				'dependsOn' => array(
					'relation' => 'OR',
					'rules'    => array(
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'single',
						),
						array(
							'option'  => 'integrationType',
							'compare' => '=',
							'value'   => 'dynamic',
						),
					),
				),
			);
			$integration_type_setting->add_option( $integration_type_option );
		}

		// Add the results shortcode setting to the query settings.
		$setting = array(
			'name'         => 'resultsShortcode',
			'label'        => __( 'Results Shortcode', 'search-filter' ),
			'group'        => 'location',
			'inputType'    => 'Info',
			'dependsOn'    => array(
				'relation' => 'AND',
				'rules'    => array(
					array(
						'option'  => 'queryIntegration',
						'compare' => '=',
						'value'   => 'results_shortcode',
					),
				),
			),
			'supports'     => array(
				'previewAPI' => true,
			),
			'dataProvider' => array(
				'route' => '/settings/results-shortcode',
			),
		);

		$setting_args = array(
			'position' => array(
				'placement' => 'after',
				'setting'   => 'queryIntegration',
			),
		);
		Queries_Settings::add_setting( $setting, $setting_args );

		// Update the shortcode setting description.
		// We want to disable coming soon notice and enable the integration toggle.
		$shortcodes_setting = Features_Settings::get_setting( 'shortcodes' );
		if ( ! $shortcodes_setting ) {
			return;
		}
		$shortcodes_setting->update(
			array(
				'description' => __( 'Use shortcodes to display fields or results on your site.  Adds various options to the admin UI.', 'search-filter' ),
			)
		);
	}


	/**
	 * Automatically set the CSS selector options.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $attributes The attributes.
	 * @param string $id The query ID.
	 * @return array The attributes.
	 */
	public static function update_query_attributes( $attributes, $query ) {

		$id = $query->get_id();
		// We want `queryContainer` and `paginationSelector` to be set automatically.
		if ( ! isset( $attributes['queryIntegration'] ) ) {
			return $attributes;
		}

		$query_integration = $attributes['queryIntegration'];
		if ( $query_integration !== 'results_shortcode' ) {
			return $attributes;
		}

		$attributes['queryContainer'] = '.search-filter-query--id-' . $id;
		$attributes['queryPaginationSelector'] = '.search-filter-query--id-' . $id . ' a.page-numbers';
		
		if ( empty( $attributes['queryPostsContainer'] ) ) {
			$attributes['queryPostsContainer'] = '.search-filter-query--id-' . $id . ' .search-filter-query-posts';
		}

		return $attributes;
	}

	/**
	 * Hide the CSS selector options from the query editor dynamic options tab.
	 *
	 * @since 3.0.0
	 */
	public static function hide_css_selector_options() {

		$depends_conditions = array(
			'relation' => 'AND',
			'action'   => 'hide',
			'rules'    => array(
				array(
					'option'  => 'queryIntegration',
					'compare' => '!=',
					'value'   => 'results_shortcode',
				),
			),
		);

		$query_container = \Search_Filter\Queries\Settings::get_setting( 'queryContainer' );
		if ( $query_container ) {
			$query_container->add_depends_condition( $depends_conditions );
		}

		$pagination_selector = \Search_Filter\Queries\Settings::get_setting( 'queryPaginationSelector' );
		if ( $pagination_selector ) {
			$pagination_selector->add_depends_condition( $depends_conditions );
		}
	}


	/**
	 * Override the shortcode to display the results template.
	 *
	 * @since 3.0.0
	 *
	 * @param string|boolean $override If false then don't override, otherwise return the string of the output.
	 * @param array          $attributes The attributes.
	 *
	 * @return string|boolean The results or false if not overridden.
	 */
	public static function override_shortcode( $override, $attributes ) {

		if ( ! isset( $attributes['action'] ) ) {
			return $override;
		}

		if ( $attributes['action'] !== 'show-results' ) {
			return $override;
		}

		if ( ! isset( $attributes['query'] ) ) {
			return $override;
		}

		$query_id       = absint( $attributes['query'] );

		$theme_template_paths = array(
			'search-filter/' . $query_id . '.php',
			'search-filter/results.php',
		);

		$results_template_path = '';

		// Look for a theme template.
		foreach ( $theme_template_paths as $theme_template_path ) {
			$results_template_path = locate_template( $theme_template_path );
			if ( $results_template_path ) {
				break;
			}
		}
		
		// If no theme template was found, look for the plugin template.
		if ( empty( $results_template_path ) ) {
			$results_template_path = plugin_dir_path( SEARCH_FILTER_PRO_BASE_FILE ) . 'includes/features/shortcodes/template.php';
		}

		$results_template_path = apply_filters( 'search-filter-pro/shortcodes/results/template_path', $results_template_path, $query_id );

		// Get paged variable.  `paged` is used for for most archives.
		// $page is used for static homepage / single pages.
		$paged = 1;
		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$paged = get_query_var( 'page' );
		}
		
		$args = array(
			'post_type'              => 'post',
			'paged'                  => $paged,
			'search_filter_query_id' => absint( $query_id ),
		);

		$query = new \WP_Query( $args );

		// For legacy support, add the old pagination functions which work around issues with WP pagination
		// not only working on the posts post type.
		if ( ! function_exists( 'search_filter_get_previous_posts_link' ) && ! function_exists( 'search_filter_get_next_posts_link' ) ) {
			$template_functions = plugin_dir_path( SEARCH_FILTER_PRO_BASE_FILE ) . 'includes/features/shortcodes/template-functions.php';
			if ( file_exists( $template_functions ) ) {
				require_once $template_functions;
			}
		}

		$output = '<div class="search-filter-query ' . esc_attr( 'search-filter-query--id-' . $query_id ) . '">';

		// Now the query & functions are ready, include the template.
		if ( file_exists( $results_template_path ) ) {

			$template_functions = plugin_dir_path( SEARCH_FILTER_PRO_BASE_FILE ) . 'includes/features/shortcodes/functions.php';
			if ( file_exists( $template_functions ) ) {
				require_once $template_functions;
			}

			ob_start();
			// Include the template.
			include $results_template_path;
			$output .= ob_get_clean();
		}

		$output .= '</div>';

		return $output;
	}
}
