<?php

namespace Ezoic_Namespace;

/**
 * AdTester Configuration
 */
class Ezoic_AdTester_Config {
	const VERSION = '2';

	// IMPORTANT: The constructor doesn't get called when we build a new Ezoic_AdTester_Config object
	// by deserializing the user's saved data in load(), so new variables in this class should be
	// given an initial value here as well as the reset() function or else they won't be set.
	public $version;
	public $paragraph_tags;
	public $excerpt_tags;
	public $placeholders;
	public $exclude_parent_tags;
	public $exclude_class_list;
	public $placeholder_config;
	public $last_placeholder_fetch;
	public $skip_word_count = 10;
	public $sidebar_id = 'sidebar-1';
	public $user_roles_with_ads_disabled = array();
	public $meta_tags = array();

	public function __construct() {
		$this->version	= Ezoic_AdTester_Config::VERSION;

		$this->reset();
	}

	/**
	 * Completely resets the configuration
	 */
	public function reset() {
		$this->placeholders						= array();
		$this->placeholder_config				= array();
		$this->parent_filters					= array( 'blockquote', 'table', '#toc_container', '#ez-toc-container' );
		$this->paragraph_tags					= array( 'p', 'li' );
		$this->excerpt_tags						= array( 'p' );
		$this->sidebar_id						= 'sidebar-1';
		$this->skip_word_count					= -1;
		$this->user_roles_with_ads_disabled 	= array();
	}

	/**
	 * Clears the placeholder configuration
	 */
	public function resetPlaceholderConfigs() {
		$this->placeholders			= array();
		$this->placeholder_config	= array();
	}

	/**
	 * Load configuration from Wordpress options
	 */
	public static function load() {
		// Fetch configuration from storage
		$encoded = \get_option( 'ez_adtester_config' );

		// If no configuration found, return empty configuration
		if ( $encoded == '' ) {
			return new Ezoic_AdTester_Config();
		}

		// Decode configuration
		$decoded = \base64_decode( $encoded );

		// Deserialize configuration
		$config = \unserialize( $decoded );

		// Upgrade if needed
		Ezoic_AdTester_Config::upgrade( $config );

		return $config;
	}

	/**
	 * Store configuration in Wordpress options
	 */
	public static function store( $config ) {
		// Serialize configuration
		$serialized = \serialize($config);

		// Encode configuration
		$encoded = base64_encode( $serialized );

		// Store configuration
		\update_option( 'ez_adtester_config', $encoded );
	}

	/**
	 * Upgrade configuration object
	 */
	private static function upgrade( $config ) {

		$version = \intval( $config->version );

		// Upgrade from version 1 to 2
		if ( $version === 1 ) {

			// Backup config
			// Serialize configuration
			$serialized = \serialize($config);

			// Encode configuration
			$encoded = base64_encode( $serialized );

			// Store configuration
			\update_option( 'ez_adtester_config_bak', $encoded );

			$config->version = '2';

			if ( isset( $config->placeholder_config ) && \is_array( $config->placeholder_config ) ) {
				// Attempt to convert XPath's to CSS selectors
				foreach ( $config->placeholder_config as $ph_config ) {
					if ( $ph_config->display === 'before_element' || $ph_config->display === 'after_element' ) {
						// Trim leading '/'
						$ph_config->display_option = \substr( $ph_config->display_option, 1 );

						// Replace '/' with '>'
						$ph_config->display_option = \str_ireplace( '/', ' > ', $ph_config->display_option );

						// Remove [1]
						$ph_config->display_option = \str_ireplace( '[1]', '', $ph_config->display_option );

						// Replace [\d] with :eq(\d)
						$ph_config->display_option = \preg_replace( '/\[\d+\]/i', ':eq($0)', $ph_config->display_option );
					}
				}
			}

			Ezoic_AdTester_Config::store( $config );
		}
	}
}
