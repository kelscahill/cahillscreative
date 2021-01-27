<?php

/**
 * Class Advanced_Ads_AdSense_Public.
 */
class Advanced_Ads_AdSense_Public {

	private $data; // options

	private static $instance = null;

	private function __construct() {
		$this->data = Advanced_Ads_AdSense_Data::get_instance();
		add_action( 'wp_head', array( $this, 'inject_header' ), 20 );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Print data in the head tag on the front end.
	 */
	public function inject_header() {
		$options = $this->data->get_options();

		// Inject CSS to make AdSense background transparent.
		if ( ! empty( $options['background'] ) ) {
			echo '<style>ins.adsbygoogle { background-color: transparent; padding: 0; }</style>';
		}

		if ( defined( 'ADVADS_ADS_DISABLED' ) || advads_is_amp() ) {
			return;
		}

		$privacy         = Advanced_Ads_Privacy::get_instance();
		$privacy_options = $privacy->options();
		$privacy_enabled = $privacy->get_state() !== 'not_needed';
		$npa_enabled     = ( ! empty( $privacy_options['enabled'] ) && $privacy_options['consent-method'] === 'custom' ) && ! empty( $privacy_options['show-non-personalized-adsense'] );

		// Show non-personalized Adsense ads if non-personalized ads are enabled and consent was not given.
		if ( $privacy_enabled && $npa_enabled ) {
			echo '<script>';
			// If the page is not from a cache.
			if ( $privacy->get_state() === 'unknown' ) {
				echo '(adsbygoogle=window.adsbygoogle||[]).requestNonPersonalizedAds=1;';
			}
			// If the page is from a cache, wait until 'advads.privacy' is available. Execute before cache-busting.
			echo '( window.advanced_ads_ready || jQuery( document ).ready ).call( null, function() {
					var state = ( advads.privacy ) ? advads.privacy.get_state() : "";
					var use_npa = ( state === "unknown" ) ? 1 : 0;
					(adsbygoogle=window.adsbygoogle||[]).requestNonPersonalizedAds=use_npa;
				} )';
			echo '</script>';
		}

		if ( ! apply_filters( 'advanced-ads-can-display-ads-in-header', true ) ) {
			return;
		}

		$pub_id = trim( $this->data->get_adsense_id() );

		if ( $pub_id && isset( $options['page-level-enabled'] ) && $options['page-level-enabled'] ) {
			$pub_id          = $this->data->get_adsense_id();
			$client_id       = 'ca-' . $pub_id;
			$top_anchor      = isset( $options['top-anchor-ad'] ) && $options['top-anchor-ad'];
			$top_anchor_code = sprintf(
				'(adsbygoogle = window.adsbygoogle || []).push({
					google_ad_client: "%s",
					enable_page_level_ads: true,
					overlays: {bottom: true}
				});',
				esc_attr( $client_id )
			);
			$script_src      = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';

			// inject page-level header code.
			include GADSENSE_BASE_PATH . 'public/templates/page-level.php';
		}
	}
}
