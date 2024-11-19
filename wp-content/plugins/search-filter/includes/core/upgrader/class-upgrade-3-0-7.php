<?php

namespace Search_Filter\Core\Upgrader;

class Upgrade_3_0_7 {

	public static function upgrade() {
		// Disable CSS save so we don't rebuild the CSS file for every field, query and style resaving.
		add_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10, 2 );

		// When we upgrade to 3.0.6, try to invalidate all the update caches manually.
		// Because the Upgrade Manager doesn't exist yet in the extension plugins, we
		// should do this manually for this upgrade only, showing any related updates asap.
		$plugins_slugs = array( 'search-filter-pro', 'search-filter', 'search-filter-bb', 'search-filter-elementor' );
		foreach ( $plugins_slugs as $plugin_slug ) {
			$cache_key             = 'edd_sl_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			$api_request_cache_key = 'edd_api_request_' . md5( serialize( $plugin_slug . 'search-filter-extension-free' . false ) );
			delete_option( $cache_key );
			delete_option( $api_request_cache_key );
		}

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

			// Convert achiveIntegration into queryIntegration.
			$archive_integration = $query->get_attribute( 'archiveIntegration' );
			$query->delete_attribute( 'archiveIntegration' );
			if ( $archive_integration !== null ) {
				$query->set_attribute( 'queryIntegration', $archive_integration );
			}

			// Convert singleIntegration into queryIntegration.
			$single_integration = $query->get_attribute( 'singleIntegration' );
			$query->delete_attribute( 'singleIntegration' );
			if ( $single_integration !== null ) {
				$query->set_attribute( 'queryIntegration', $single_integration );
			}

			// Now update integration single + location dynamic, into just dynamic.
			$integration_type = $query->get_attribute( 'integrationType' );
			$single_location  = $query->get_attribute( 'singleLocation' );

			// Handle upgrade for removal of "dynamic" toggle from singe integrations.
			// Instead convert it to the new dynamic integration method.
			if ( $integration_type === 'single' && $single_location === 'dynamic' ) {
				$query->delete_attribute( 'singleLocation' );
				$query->set_attribute( 'integrationType', 'dynamic' );
			}

			$query->save();
		}

		// Remove the filter to renable CSS save.
		remove_filter( 'search-filter/core/css-loader/save-css/can-save', array( __CLASS__, 'disable_css_save' ), 10 );
	}

	public static function disable_css_save() {
		return false;
	}
}
