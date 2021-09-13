<?php

namespace Ezoic_Namespace;

/**
 * A data object which represents a single ad placeholder
 */
class Ezoic_AdTester_PublisherAd {
	//TODO: Send this from the server
	const EMBED_CODE_TEMPLATE = '<!-- Ezoic - %s - %s --><div id="ezoic-pub-ad-placeholder-%d"></div><!-- End Ezoic - %s - %s -->';

	public $id;
	public $domainId;
	public $adTypeId;
	public $adPositionId;
	public $name;
	public $positionType;

	// Not exposing these properties publicly unless they need to be used
	private $adCode;
	private $isActive;
	private $isAdPicker;
	private $isAutoInsert;
	private $isPathFinder;
	private $replaceRegex;
	private $createdBy;
	private $isUnwrappedAd;
	private $isMultisize;
	private $allowedAdditionalSizes;
	private $disableDurations;
	private $allowFluid;
	private $adDisclosureId;
	private $disclosureText;

	/**
	 * Creates a placeholder based on a json payload
	 */
	public function __construct($payload) {
		if ($payload) $this->set($payload);
	}

	/**
	 * Calculates the correct embed code to inject into the page
	 */
	public function embed_code() {
		return sprintf(self::EMBED_CODE_TEMPLATE, $this->name, $this->positionType, $this->adPositionId, $this->name, $this->positionType);
	}

	/**
	 * Decode publisher ads from an array
	 *
	 * @access private
	 * @param array $data Array containing publisher ad collection
	 */
	private function set($data) {
		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}
}

/**
 * A data object representing a lit of ad placeholders
 */
class Ezoic_AdTester_PublisherAds {
	private $ads					= array();

	private $content_ads			= array( 'in' => array(), 'above' => array() );
	private $content_indexes	= array( 'in' => 0, 'above' => 0);

	/**
	 * Initializes the list via a back-channel call to the backend systems
	 */
	public function __construct() {
		// Fetch domain and TLD (e.g. example.com from www.example.com)
		$domain = Ezoic_Integration_Request_Utils::get_domain();

		// Fetch autentication token
		$token = Ezoic_Integration_Authentication::get_token();

		if ( $token != '' ) {
			// Build request
			$requestURL = Ezoic_AdTester::ADS_ENDPOINT . $domain;

			// Send request to Ezoic
			$response = wp_remote_get( $requestURL, array(
				'method'		=> 'GET',
				'timeout'	=> '10',
				'headers'	=> array(
					'Authentication' => 'Bearer ' . $token
				)
			) );
			if ( !is_wp_error( $response )) {
				$body = wp_remote_retrieve_body( $response );

				// Deserialize response
				$deserialized = json_decode( $body );

				// Initialize $ads
				$this->set( $deserialized );
			}
		}
	}

	/**
	 * Decode publisher ads from an array
	 *
	 * @access private
	 * @param array $data Array containing publisher ad collection
	 */
	private function set( $data ) {
		if ( $data ) {
			// Above content ads
			foreach ( $data->aboveContent as $ad ) {
				$new_ad = new Ezoic_AdTester_PublisherAd( $ad );

				array_push( $this->ads, $new_ad );
				array_push( $this->content_ads[ 'above' ], $new_ad );
			}

			// In-content ads
			foreach ( $data->inContent as $ad ) {
				$new_ad = new Ezoic_AdTester_PublisherAd( $ad );

				array_push( $this->ads, $new_ad );
				array_push( $this->content_ads[ 'in' ], $new_ad );
			}
		}
	}

	/**
	 * Returns the total number of ads for this domain
	 */
	public function count() {
		return count($this->ads);
	}

	/**
	 * Indicates if any of the default ads are contained in the list
	 * (default ad names start with 'wp_')
	 */
	public function has_default_ads() {
		foreach ( $this->ads as $ad ) {
			if ( substr( $ad->name, 0, 3) === 'wp_' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the next available in content placeholder
	 * @access public
	 */
	public function get_next_in_content() {
		return $this->get_next_available_ad( 'in' );
	}

	/**
	 * Gets the next available above content placeholder
	 * @access public
	 */
	public function get_next_above_content() {
		return $this->get_next_available_ad( 'above' );
	}

	/**
	 * Gets the next available ad based on type
	 */
	private function get_next_available_ad( $type ) {
		$index = $this->content_indexes[ $type ];

		// If no more ads of this type available, return NULL
		if ($index > count( $this->content_ads[ $type ] )) {
			return NULL;
		}

		$this->content_indexes[ $type ] += 1;

		return $this->content_ads[ $type ][ $index ];
	}

	/**
	 * Creates the embed code from the collection of ads
	 *
	 * @access private
	 * @param string $positionType Position type description
	 */
	public function get_embed_code($positionType) {
		$embedCode = "";

		if ( !is_null( $this->ads ) ) {
			foreach ( $this->ads as $ad ) {
				if ( $ad->positionType == $positionType ) {
					$embedCode = $embedCode . $ad->embed_code();
				}
			}
		}

		return $embedCode;
	}
}
