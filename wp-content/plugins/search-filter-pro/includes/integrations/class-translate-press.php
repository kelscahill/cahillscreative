<?php
/**
 * ACF Integration Class
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 * @package    Search_Filter_Pro/Integrations
 */

namespace Search_Filter_Pro\Integrations;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fix for applying Translate Press to text search input.
 */
class Translate_Press {

	/**
	 * Init
	 *
	 * @since    3.0.0
	 */
	public static function init() {
		add_filter( 'search-filter/query/query_args', array( __CLASS__, 'update_search_query_args' ), 10, 1 );
		// TODO - we need to allow the user to enable/disable this, right now it just
		// disabled the ajax translation anywhere we have a query. We need to find out
		// why this is return an error.
		add_filter( 'trp_enable_dynamic_translation', '__return_false' );
	}

	/**
	 * Update the search query args for TranslatePress.
	 *
	 * @param array $query_args The query arguments.
	 * @return array The updated query arguments.
	 */
	public static function update_search_query_args( $query_args ) {
		// TODO - doesn't work here, seems its too late.
		// Disabled: add_filter( 'trp_enable_dynamic_translation', '__return_false' );.

		if ( ! class_exists( '\TRP_Translate_Press' ) ) {
			return $query_args;
		}

		if ( ! isset( $query_args['s'] ) ) {
			return $query_args;
		}

		if ( empty( $query_args['s'] ) ) {
			return $query_args;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Third-party global variable.
		global $TRP_LANGUAGE;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Third-party global variable.
		$trp_language = $TRP_LANGUAGE;

		$trp = \TRP_Translate_Press::get_trp_instance();

		$trp_settings = $trp->get_component( 'settings' );

		$settings = $trp_settings->get_settings();

		if ( $trp_language !== $settings['default-language'] ) {
			$trp_search        = $trp->get_component( 'search' );
			$search_result_ids = $trp_search->get_post_ids_containing_search_term( $query_args['s'], null );
			unset( $query_args['s'] );
			if ( ! empty( $search_result_ids ) ) {
				$query_args['post__in'] = $search_result_ids;
			} else {
				$query_args['post__in'] = array( 0 );
			}
		}
		return $query_args;
	}
}
