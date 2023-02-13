<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Sidebar_Inserter extends Ezoic_AdTester_Inserter {
	public function __construct( $config ) {
		parent::__construct( $config );

		// Default to 'post' as the page is not correctly being detected
		if ( !isset( $this->page_type ) || $this->page_type == "" ) {
			$this->page_type = 'post';
		}
	}

	/**
	 * Insert sidebar placeholders
	 */
	public function insert() {
		// Fetch sidebar and widgets
		$sidebars = get_option( 'sidebars_widgets' );

		// If the dynamic sidebar is empty, return
		if ( !array_key_exists( $this->config->sidebar_id, $sidebars ) || !is_array( $sidebars[ $this->config->sidebar_id ] ) || count( $sidebars[ $this->config->sidebar_id ] ) === 0 ) {
			return;
		}

		// If the custom sidebar widget was not defined, do not attempt to add
		if ( !class_exists( 'Ezoic_Namespace\Ezoic_AdTester_Widget' ) ) {
			return;
		}

		// Find relevent rules
		$rules = array();
		foreach ( $this->config->placeholder_config as $ph_config ) {
			if ( $ph_config->page_type == $this->page_type ) {
				$rules[ $ph_config->placeholder_id ] = $ph_config;
			}
		}

		// Create array of placeholders to insert
		$to_insert = array();
		foreach ( $rules as $rule ) {
			if ( $rule->display != 'disabled' ) {
				$placeholder = $this->config->placeholders[ $rule->placeholder_id ];

				// Currently only support after_widget
				if ( $rule->display == 'after_widget' ) {
					$to_insert[] = array( 'index' => (int) $rule->display_option, 'placeholder' => $placeholder );
				}
			}
		}

		// If there are no sidebar placeholders, exit
		if ( count( $to_insert ) === 0 ) {
			return;
		}

		// Sort array of placeholders to insert
		\usort( $to_insert, function( $a, $b ) { if ( $a[ 'index' ] < $b[ 'index' ] ) { return -1; } else { return 1; } } );

		// Remove any existing adtester placeholders
		$to_delete = array();
		foreach ( $sidebars[ $this->config->sidebar_id ] as $widget ) {
			if ( \ez_stripos( $widget, 'ezoic_adtester' ) === 0 ) {
					$to_delete[] = $widget;
			}
		}

		// Remove any existing adtester widgets
		$sidebars[ $this->config->sidebar_id ] = \array_diff( $sidebars[ $this->config->sidebar_id ], $to_delete );

		// Register adtester widget
		register_widget('Ezoic_Namespace\Ezoic_AdTester_Widget');

		// Insert widgets into the sidebar
		$counter = 1;
		$widget_count = count( $sidebars[ $this->config->sidebar_id ] );
		$widget_options = array();
		foreach ( $to_insert as $rule ) {
			// Do not insert widgets that fall outside of the range of existing widgets
			if ( $rule[ 'index' ] <= $widget_count ) {
				\array_splice( $sidebars[ $this->config->sidebar_id ], $rule[ 'index' ] + $counter - 1, 0, 'ezoic_adtester_widget-' . $counter );
				$widget_options[ $counter ] = array(
					'embed_code' => $rule[ 'placeholder' ]->embed_code()
				);

				$counter++;
			} elseif ( $rule[ 'index' ] > $widget_count ) {
				$sidebars[ $this->config->sidebar_id ][] = 'ezoic_adtester_widget-' . $counter;
				$widget_options[ $counter ] = array(
					'embed_code' => $rule[ 'placeholder' ]->embed_code()
				);

				$counter++;
			}
		}

		// Save changes
		update_option( 'widget_ezoic_adtester_widget', $widget_options );
		update_option( 'sidebars_widgets', $sidebars );
	}
}
