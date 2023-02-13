<?php

namespace Ezoic_Namespace;

class Ezoic_Integration_Ad_Settings {
	private $adtester;

	public function __construct() {
	}

	private function initialize() {
		// Load config
		$this->adtester = new Ezoic_AdTester();

		$this->initialize_ad_settings();
	}

	/**
	 * Fetches ad data from backend and initializes configuraiton if needed
	 */
	public function initialize_ad_settings() {
		// Only initialize if on the settings tab
		if ( !isset( $_GET[ 'tab' ] ) || ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] !== 'ad_settings' ) ) {
			return;
		}

		$this->adtester->initialize_config();
	}

	/**
	 * Register RESTful endpoint for saving configuration
	 */
	public function register_rest() {
		// Save placeholder rule
		\register_rest_route( 'ezoic/v1', 'save-rule', array(
			'methods'					=> \WP_REST_SERVER::CREATABLE,
			'callback'					=> array( $this, 'save_rule' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );

		// Save general configuration
		\register_rest_route( 'ezoic/v1', 'save-settings', array(
			'methods'					=> \WP_REST_SERVER::CREATABLE,
			'callback'					=> array( $this, 'save_settings' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );

		// Reset general configuration
		\register_rest_route( 'ezoic/v1', 'reset-settings', array(
			'methods'					=> \WP_REST_SERVER::CREATABLE,
			'callback'					=> array( $this, 'reset_settings' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );

		// Clear default configuration
		\register_rest_route( 'ezoic/v1', 'clear-defaults', array(
			'methods'					=> \WP_REST_SERVER::CREATABLE,
			'callback'					=> array( $this, 'clear_default_configuration' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );

		// Get placeholder data
		\register_rest_route( 'ezoic/v1', 'retrieve-placeholders', array(
			'methods'					=> \WP_REST_SERVER::READABLE,
			'callback'					=> array( $this, 'retrieve_placeholders' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return true; },
			'show_in_index'			=> false
		) );

		// Domain status
		\register_rest_route( 'ezoic/v1', 'domain-status', array(
			'methods'					=> \WP_REST_SERVER::READABLE,
			'callback'					=> array( $this, 'domain_status' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );

		// Force Generate
		\register_rest_route( 'ezoic/v1', 'force-generate', array(
			'methods'					=> \WP_REST_SERVER::CREATABLE,
			'callback'					=> array( $this, 'force_generate' ),
			'args'						=> array(),
			'permission_callback'	=> function() { return $this->validate_user(); },
			'show_in_index'			=> false
		) );
	}

	/**
	 * Ensures that the current request is from a user with the administrator role
	 */
	private function validate_user() {
		$data = \get_userdata( \get_current_user_id() );

		// Verify there is a user and that they have roles
		if ( !$data || !$data->roles || !is_array( $data->roles ) ) {
			return false;
		}

		$role = $data->roles;
		return ( \is_super_admin() || ( in_array( 'administrator', $role ) ) );
	}

	/**
	 * Force default ad placeholders to be generated
	 */
	public function force_generate() {
		$this->adtester = new Ezoic_AdTester();
		$this->adtester->force_generate_placeholders();
	}

	/**
	 * Obtains domain status, including placeholder creation status
	 */
	public function domain_status() {
		$status = new Ezoic_AdTester_Domain_Status( true );
		$status->fetch();

		$plugin_initialized = \get_option( 'ez_ad_initialized' );
		if ( $plugin_initialized && ( intval( $plugin_initialized ) + 5 * 60 ) > \time() ) {
			$status->placeholders_created = false;
		}

		return $status;
	}


	/** 
	 * Retrieves placeholders w/ optional date range filter
	 */
	public function retrieve_placeholders(){
		$this->adtester = new Ezoic_AdTester();
		
		$dateFrom = false;
		$dateTo = false;
		//make sure both of these are set properly, expecting a positive timestamp integer, 
		//we don't want to allow willy-nilly vals through which could lead to xss/db inject
		if(
			//date from
			array_key_exists('dateFrom', $_GET) && 
			isset($_GET['dateFrom']) && 
			is_numeric($_GET['dateFrom']) &&
			$_GET['dateFrom'] > 0 &&

			//date to
			array_key_exists('dateTo', $_GET) && 
			isset($_GET['dateTo']) && 
			is_numeric($_GET['dateTo']) && 
			$_GET['dateTo'] > 0
		) {
			$dateFrom = $_GET['dateFrom'];
			$dateTo = $_GET['dateTo'];
		}

		return $this->adtester->retrieve_placeholders($dateFrom, $dateTo);
	}

	/**
	 * Clears recommended configuration
	 */
	public function clear_default_configuration() {
		$this->initialize();

		$configs = $this->adtester->config->placeholder_config;

		$new_config = [];
		foreach ( $configs as $config ) {
			if ( !$config->is_default ) {
				$new_config[] = $config;
			}
		}

		$this->adtester->config->placeholder_config = $new_config;

		Ezoic_AdTester::log('clearing default config');

		$this->adtester->update_config();
	}

	/**
	 * Resets any configuration set for ad placeholders
	 */
	public function reset_settings() {
		$this->initialize();

		// Reset configuration
		$this->adtester->config->reset();

		Ezoic_AdTester::log( 'resetting settings' );

		// Store config
		$this->adtester->update_config();
	}

	/**
	 * Save placeholder configuration
	 */
	public function save_rule( $request_data ) {
		$this->initialize();

		// Parse payload
		$payload = \json_decode( $request_data->get_body() );

		// Load config
		$config = $this->adtester->config;

		// Find the specific placeholder configuration to modify
		$edit_config = null;
		foreach ( $config->placeholder_config as $ph_config ) {
			if ( $ph_config->page_type == $payload->pageType && $ph_config->placeholder_id == $payload->placeholderId ) {
				$edit_config = $ph_config;
			}
		}

		// If no configuration was found, create a new one
		if ( !is_null( $edit_config ) ) {
			$edit_config->display = $payload->display;
			$edit_config->display_option = $payload->displayOption;
		} else {
			// Update existing configuration
			$edit_config = new Ezoic_AdTester_Placeholder_Config( $payload->pageType, $payload->placeholderId, $payload->display, $payload->displayOption, false );
			$config->placeholder_config[] = $edit_config;
		}

		Ezoic_AdTester::log( 'saving new rule' );

		// Save configuration
		$this->adtester->update_config();

		// Flush cache
		$cdn = new Ezoic_Cdn();
		$cdn->ezoic_cdn_purge( $cdn->ezoic_cdn_get_domain() );
	}

	/**
	 * Saves general (top-level) configuration changes
	 */
	public function save_settings( $request_data ) {
		$this->initialize();

		// Parse payload
		$payload = \json_decode( $request_data->get_body() );

		// Load config
		$config = $this->adtester->config;

		// Update data
		$config->paragraph_tags					= $payload->paragraphTags;
		$config->excerpt_tags					= $payload->excerptTags;
		$config->parent_filters					= $payload->excludeParents;
		$config->exclude_class_list				= $payload->excludeClasses;
		$config->sidebar_id						= $payload->sidebarId;
		$config->user_roles_with_ads_disabled 	= $payload->userRolesWithAdsDisabled;
		$config->meta_tags 						= $payload->metaTags;

		if ( $payload->excludeWordCount !== '' && \is_numeric( $payload->excludeWordCount ) ) {
			$config->skip_word_count = $payload->excludeWordCount;
		} else {
			$config->skip_word_count = null;
		}

		Ezoic_AdTester::log( 'saving general settings' );

		// Store configuration
		$this->adtester->update_config();

		// Flush cache
		$cdn = new Ezoic_Cdn();
		$cdn->ezoic_cdn_purge( $cdn->ezoic_cdn_get_domain() );
	}

	/**
	 * Renders the settings page for ad placeholders
	 */
	public function render_settings_page_content() {
		$this->initialize();

		// Fetch domain status
		$domain_status = new Ezoic_AdTester_Domain_Status( true );

		// Load Vue-based interface
		wp_enqueue_script( 'ezoic_integration', plugin_dir_url( __FILE__ ) . 'js/ad-settings.js', array(), rand() );

		// Remove script which causes vuejs conflicts
		wp_deregister_script( 'js_files_for_wp_admin' );

		// Fetch recent article for element picker, if needed
		$recent_post_url = '/';
		$recent_post_args = array( 'numberposts' => 1 );
		$recent_posts = \wp_get_recent_posts( $recent_post_args );
		if ( \count( $recent_posts ) > 0 ) {
			$recent_post_url = \get_permalink( $recent_posts[0]['ID'] );
		}

		// Disable ads on page
		if ( \strpos( $recent_post_url, '?' ) ) {
			$recent_post_url .= '&ez_orig=1';
		} else {
			$recent_post_url .= '?ez_orig=1';
		}
		// Properly format config so the editor can deserialize it
		$excerptTags = '[]';
		if ( isset( $this->adtester->config->excerpt_tags ) ) {
			$excerptTags = '["' . implode( '","', $this->adtester->config->excerpt_tags ) . '"]';
		}

		$paragraphTags = '[]';
		if ( isset( $this->adtester->config->paragraph_tags ) ) {
			$paragraphTags = '["' . implode( '","', $this->adtester->config->paragraph_tags ) . '"]';
		}

		$excludeClasses = '[]';
		if ( isset( $this->adtester->config->exclude_class_list ) ) {
			$excludeClasses = '["' . implode( '","', $this->adtester->config->exclude_class_list ) . '"]';
		}

		$excludeParents = '[]';
		if ( isset( $this->adtester->config->parent_filters ) ) {
			$excludeParents = '["' . implode( '","', $this->adtester->config->parent_filters ) . '"]';
		}

		$excludeWordCount = '';
		if ( isset( $this->adtester->config->skip_word_count ) && $this->adtester->config->skip_word_count > 0 ) {
			$excludeWordCount = $this->adtester->config->skip_word_count;
		}

		$userRolesWithAdsDisabled = '[]';
		if ( isset( $this->adtester->config->user_roles_with_ads_disabled ) ) {
			$userRolesWithAdsDisabled = \json_encode( $this->adtester->config->user_roles_with_ads_disabled );
		}

		$metaTags = '[]';
		if ( isset( $this->adtester->config->meta_tags ) ) {
			$metaTags = \json_encode( $this->adtester->config->meta_tags );
		}

		$placeholderArray = array();
		foreach ( $this->adtester->config->placeholders as $placeholder ) {
			$revenue = 'null';
			if ( isset( $this->adtester->revenues[$placeholder->position_id]->revenue_percentage ) ) {
				$revenue = $this->adtester->revenues[$placeholder->position_id]->revenue_percentage;
			}
			$placeholderArray[] =
				'{"id":' . $placeholder->id
				. ',"positionId":' . $placeholder->position_id
				. ',"name":"' . $placeholder->name
				. '","revenue":' . $revenue . '}';
		}

		$plugin_initialized = \get_option( 'ez_ad_initialized' );
		$placeholders_generated = $domain_status->placeholders_created;
		if ( $plugin_initialized && ( intval( $plugin_initialized ) + 5 * 60 ) > \time() ) {
			$placeholders_generated = false;
		}

		// Pass configuration values and set mount-point
?>
<link href="//fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">

<script id="placeholder-payload" type="application/json">
{
	"status": {
		"hasError": <?php if ( $domain_status->has_error ) { echo 'true'; } else { echo 'false'; } ?>,
		"errorMessage": <?php echo \json_encode( $domain_status->error_message, JSON_HEX_QUOT ) ?>,
		"monetizationEligible": <?php if ( $domain_status->monetization_eligible === true ) { echo 'true'; } else { echo 'false'; } ?>,
		"placeholdersCreated": <?php if ( $placeholders_generated === true ) { echo 'true'; } else { echo 'false'; } ?>,
		"placeholderCountOther": <?php echo $domain_status->placeholder_count_other ?>,
		"placeholderCountWp": <?php echo $domain_status->placeholder_count_wp ?>
	},
	"endpoints": {
		"saveRule": "<?php echo \get_rest_url( null, "ezoic/v1/save-rule" ) ?>",
		"saveSettings": "<?php echo \get_rest_url( null, "ezoic/v1/save-settings" ) ?>",
		"resetSettings": "<?php echo \get_rest_url( null, "ezoic/v1/reset-settings" ) ?>",
		"retrievePlaceholders": "<?php echo \get_rest_url( null, "ezoic/v1/retrieve-placeholders" ) ?>",
		"clearDefaults": "<?php echo \get_rest_url( null, "ezoic/v1/clear-defaults" ) ?>",
		"domainStatus": "<?php echo \get_rest_url( null, "ezoic/v1/domain-status" ) ?>",
		"forceGenerate": "<?php echo \get_rest_url( null, "ezoic/v1/force-generate" ) ?>"
	},
	"nonce": "<?php echo \wp_create_nonce( 'wp_rest' ) ?>",
	"baseURL": "<?php echo \plugin_dir_url( __FILE__ ) ?>",
	"recentPostUrl": "<?php echo $recent_post_url ?>",
	"pageTypes":[{"type":"post","name":"Post","displayOptions":[{"id":"disabled","name":"Disabled"},{"id":"before_content","name":"Before Content","hasOption":false},{"id":"after_content","name":"After Content","hasOption":false},{"id":"before_paragraph","name":"Before Paragraph","hasOption":true},{"id":"after_paragraph","name":"After Paragraph","hasOption":true},{"id":"after_widget","name":"After Widget","hasOption":true},{"id":"before_element","name":"Before HTML Element","hasOption":true},{"id":"after_element","name":"After HTML Element","hasOption":true}]},{"type":"page","name":"Page","displayOptions":[{"id":"disabled","name":"Disabled"},{"id":"before_content","name":"Before Content","hasOption":false},{"id":"after_content","name":"After Content","hasOption":false},{"id":"before_paragraph","name":"Before Paragraph","hasOption":true},{"id":"after_paragraph","name":"After Paragraph","hasOption":true},{"id":"after_widget","name":"After Widget","hasOption":true},{"id":"before_element","name":"Before HTML Element","hasOption":true},{"id":"after_element","name":"After HTML Element","hasOption":true}]},{"type":"home","name":"Home Page","displayOptions":[{"id":"disabled","name":"Disabled"},{"id":"before_paragraph","name":"Before Paragraph","hasOption":true},{"id":"after_paragraph","name":"After Paragraph","hasOption":true},{"id":"before_excerpt","name":"Before Excerpt","hasOption":true},{"id":"after_excerpt","name":"After Excerpt","hasOption":true},{"id":"before_element","name":"Before HTML Element","hasOption":true},{"id":"after_element","name":"After HTML Element","hasOption":true}]},{"type":"category","name":"Category","displayOptions":[{"id":"disabled","name":"Disabled"},{"id":"before_paragraph","name":"Before Paragraph","hasOption":true},{"id":"after_paragraph","name":"After Paragraph","hasOption":true},{"id":"before_excerpt","name":"Before Excerpt","hasOption":true},{"id":"after_excerpt","name":"After Excerpt","hasOption":true},{"id":"before_element","name":"Before HTML Element","hasOption":true},{"id":"after_element","name":"After HTML Element","hasOption":true}]}],
	"placeholderConfig": [
		<?php

		$configArray = array();
		if ( !empty($this->adtester->config->placeholder_config) ) {
			foreach ( $this->adtester->config->placeholder_config as $config ) {
				$configArray[] = '{"pageType":"' . $config->page_type . '","placeholderId":' . $config->placeholder_id . ',"display":"' . $config->display . '","displayOption":"' . $config->display_option . '"}';
			}

			echo implode( ',', $configArray );
		}

		?>
	],
	"placeholders": [
		<?php echo implode( ',', $placeholderArray ); ?>
	],
	"general": {
		"paragraphTags": <?php echo $paragraphTags ?>,
		"excerptTags": <?php echo $excerptTags ?>,
		"excludeParents": <?php echo $excludeParents ?>,
		"excludeClasses": <?php echo $excludeClasses ?>,
		"excludeWordCount": "<?php echo $excludeWordCount ?>",
		"sidebarId": "<?php echo $this->adtester->config->sidebar_id ?>",
		"userRoles": <?php echo \json_encode( $this->get_user_roles() ) ?>,
		"userRolesWithAdsDisabled": <?php echo $userRolesWithAdsDisabled ?>,
		"metaTags": <?php echo $metaTags ?>
	}
}
</script>

<div id="app"></div>
<?php
	}

	/**
	 * Fetches a list of role names from the global variable
	 * $wp_roles and returns them sorted alphabetically.
	 *
	 * @return array
	 */
	public function get_user_roles() {
		global $wp_roles;

		// If $wp_roles doesn't exist for some reason
		// return an empty array
		if (   !isset($wp_roles)
			|| !isset($wp_roles->role_names) ) {
			return array();
		}

		// Clone role names into a new array because we don't
		// want to change the original array when we sort it
		$userRoles = \array_merge(array(), $wp_roles->role_names);
		\sort($userRoles);

		return $userRoles;
	}
}
