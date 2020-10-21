<?php

/**
 * Admin class for privacy settings.
 */
class Advanced_Ads_Privacy_Admin {
	/**
	 * Singleton instance of the plugin
	 *
	 * @var Advanced_Ads_Privacy_Admin
	 */
	protected static $instance;

	/**
	 * Initialize the module
	 */
	private function __construct() {
		// add module settings to Advanced Ads settings page
		add_action( 'advanced-ads-settings-init', array( $this, 'settings_init' ), 20 );
		add_filter( 'advanced-ads-setting-tabs', array( $this, 'setting_tabs' ), 20 );

		// additional ad options
		add_action( 'advanced-ads-ad-params-after', array( $this, 'render_ad_options' ), 20 );
		add_filter( 'advanced-ads-save-options', array( $this, 'save_ad_options' ), 10, 2 );
	}

	/**
	 * Return an instance of Advanced_Ads_Privacy_Admin
	 *
	 * @return Advanced_Ads_Privacy_Admin
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add tracking settings tab.
	 *
	 * @param array $tabs existing setting tabs.
	 *
	 * @return array $tabs setting tabs with AdSense tab attached
	 * @since 1.8.30
	 */
	public function setting_tabs( array $tabs ) {
		$tabs['privacy'] = array(
			'page'  => ADVADS_PRIVACY_SLUG . '-settings',
			'group' => ADVADS_PRIVACY_SLUG,
			'tabid' => 'privacy',
			'title' => __( 'Privacy', 'advanced-ads' ),
		);

		return $tabs;
	}

	/**
	 * Add settings to settings page
	 */
	public function settings_init() {
		register_setting( ADVADS_PRIVACY_SLUG, Advanced_Ads_Privacy::OPTION_KEY, array( $this, 'sanitize_settings' ) );

		add_settings_section(
			ADVADS_PRIVACY_SLUG . '_settings_section',
			'',
			'__return_empty_string',
			ADVADS_PRIVACY_SLUG . '-settings'
		);

		add_settings_field(
			'enable-privacy-module',
			__( 'Enable Privacy module', 'advanced-ads' ),
			array( $this, 'render_settings_enable_module' ),
			ADVADS_PRIVACY_SLUG . '-settings',
			ADVADS_PRIVACY_SLUG . '_settings_section',
			array( 'label_for' => Advanced_Ads_Privacy::OPTION_KEY . '_enabled' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $options Privacy options.
	 *
	 * @return array
	 */
	public function sanitize_settings( $options ) {
		$options['custom-cookie-name']  = isset( $options['custom-cookie-name'] ) ? trim( $options['custom-cookie-name'] ) : '';
		$options['custom-cookie-value'] = isset( $options['custom-cookie-value'] ) ? trim( $options['custom-cookie-value'] ) : '';

		return $options;
	}

	/**
	 * Render enable module setting
	 */
	public function render_settings_enable_module() {
		$options                       = Advanced_Ads_Privacy::get_instance()->options();
		$module_enabled                = isset( $options['enabled'] );
		$methods                       = array(
			''           => array(
				'label' => __( 'Show all ads even without consent', 'advanced-ads' ),
			),
			'custom'     => array(
				'label'      => __( 'Cookie', 'advanced-ads' ),
				'manual_url' => ADVADS_URL . 'manual/ad-cookie-consent/#utm_source=advanced-ads&utm_medium=link&utm_campaign=privacy-tab',
			),
			'iab_tcf_20' => array(
				'label'      => sprintf(
					// translators: %s is a string with various CMPs (companies) that support the TCF standard
					__( 'TCF v2.0 integration (e.g., %s)', 'advanced-ads' ),
					'Quantcast Choices'
				),
				'manual_url' => ADVADS_URL . 'manual/tcf-consent-wordpress/#utm_source=advanced-ads&utm_medium=link&utm_campaign=privacy-tab',
			),
		);
		$current_method                = isset( $options['consent-method'] ) ? $options['consent-method'] : '';
		$custom_cookie_name            = isset( $options['custom-cookie-name'] ) ? $options['custom-cookie-name'] : '';
		$custom_cookie_value           = isset( $options['custom-cookie-value'] ) ? $options['custom-cookie-value'] : '';
		$show_non_personalized_adsense = isset( $options['show-non-personalized-adsense'] );
		$link_default_attrs            = array(
			'href'   => esc_url( ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=privacy-cache' ),
			'target' => '_blank',
		);
		$pro_link_attrs                = apply_filters( 'advanced-ads-privacy-custom-link-attributes', $link_default_attrs );
		if ( ! array_key_exists( 'href', $pro_link_attrs ) ) {
			$pro_link_attrs = wp_parse_args( $pro_link_attrs, $link_default_attrs );
		}
		$opening_link_to_pro = sprintf(
			'<a %s>',
			implode(
				' ',
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs get escaped below.
				array_map(
					function( $key, $value ) {
						return sprintf( '%s="%s"', $key, esc_attr( $value ) );
					},
					array_keys( $pro_link_attrs ),
					$pro_link_attrs
				)
			)
		);

		wp_enqueue_script( Advanced_Ads_Privacy::OPTION_KEY, ADVADS_PRIVACY_BASE_URL . 'admin/assets/js/privacy.js', array( 'jquery' ), '1.19.1', true );
		wp_localize_script( Advanced_Ads_Privacy::OPTION_KEY, 'advads_privacy', array( 'option_key' => Advanced_Ads_Privacy::OPTION_KEY ) );

		require ADVADS_PRIVACY_BASE_PATH . 'admin/views/setting-general.php';
	}

	/**
	 * Add options to ad edit page
	 *
	 * @param Advanced_Ads_Ad $ad Ad object.
	 */
	public function render_ad_options( Advanced_Ads_Ad $ad ) {
		if ( empty( $ad->id ) ) {
			return;
		}

		$privacy         = Advanced_Ads_Privacy::get_instance();
		$privacy_options = $privacy->options();
		// module is not enabled.
		if ( ! isset( $privacy_options['enabled'] ) ) {
			return;
		}

		// Don't add override option if the ad is adsense and tcf is enabled, or if the ad is image or dummy.
		$skip_option = ( $ad->type === 'adsense' && $privacy_options['consent-method'] === 'iab_tcf_20' ) || ! $privacy->ad_type_needs_consent( $ad->type );

		if ( (bool) apply_filters( 'advanced-ads-ad-privacy-hide-ignore-consent', $skip_option, $ad, $privacy_options ) ) {
			return;
		}

		$ignore_consent = isset( $ad->options()['privacy']['ignore-consent'] );

		include ADVADS_PRIVACY_BASE_PATH . 'admin/views/setting-ad-ignore-consent.php';
	}

	/**
	 * Save ad options.
	 *
	 * @param array $options Current options, default empty.
	 *
	 * @return array
	 */
	public function save_ad_options( $options = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['advanced_ad']['privacy'] ) ) {
			$options['privacy'] = $_POST['advanced_ad']['privacy'];
		} else {
			unset( $options['privacy'] );
		}
		// phpcs:enable

		return $options;
	}
}
