<?php

namespace Ezoic_Namespace;

/**
 * Helper class used to initialize placeholders upon plugin activation
 */
class Ezoic_AdTester_Init
{
	public function __construct() {
	}

	/**
	 * Initializes the default set of ad placeholders
	 */
	public function initialize() {
		$token = Ezoic_Integration_Authentication::get_token();

		// Only attempt to create placeholders if a token is present
		if ( $token != "" ) {
			// Use auth key to send a request to initialize the domain
			$domain = Ezoic_Integration_Request_Utils::get_domain();
			$requestURL = Ezoic_AdTester::INIT_ENDPOINT . $domain;
			wp_remote_post( $requestURL, array(
				'method'		=> 'POST',
				'timeout'	=> 120,
				'headers'	=> array(
					'Authentication' => 'Bearer ' . $token
				),
				'body'		=> array()
			) );

			// Flush the cache for the site
			if ( Ezoic_Cdn::ezoic_cdn_is_enabled() ) {
				$cdn = new Ezoic_Cdn();
				$cdn->ezoic_cdn_purge( $cdn->ezoic_cdn_get_domain() );
			}
		}
	}
}
