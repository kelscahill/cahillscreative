<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester extends Ezoic_Feature
{
	const WORDS_PER_PARAGRAPH = 150;

	const INIT_ENDPOINT	= EZOIC_URL . "/pub/v1/wordpressintegration/v1/initialize?d=";
	const ADS_ENDPOINT	= EZOIC_URL . "/pub/v1/wordpressintegration/v1/publisherads?d=";

	private $publisherAds;

	public function __construct()
	{
		// Activate feature if enabled
		$activated = $this->enable();
	}

	/**
	 * Register public hooks (mostly for ad insertion)
	 */
	public function register_public_hooks( $loader )
	{
		$loader->add_action( 'wp', $this, 'fetch_placeholders' );

		$loader->add_filter( 'ez_under_page_title', $this, 'set_under_page_title_placeholder' );
		$loader->add_filter( 'ez_top_of_page', $this, 'set_top_of_page_placeholder' );
		$loader->add_filter( 'ez_content_paragraph', $this, 'set_paragraph_placeholder', 10, 2 );
		$loader->add_filter( 'ez_bottom_of_page', $this, 'set_bottom_of_page_placeholder' );

	}

	/**
	 * Register admin hooks (mostly for placeholder initialization)
	 */
	public function register_admin_hooks( $loader )
	{
		$loader->add_action( 'ez_after_activate', $this, 'initialize' );
	}

	public function fetch_placeholders() {
		if ( is_admin() ) {
			return;
		}

		$this->publisherAds = new Ezoic_AdTester_PublisherAds();
	}

	/**
	 * Note the initial activation of the plugin
	 */
	public function initialize() {
		// Initialize default placeholders
		$init = new Ezoic_AdTester_Init();
		$init->initialize();
	}

	/**
	 * Insert placeholder: under_page_title
	 */
	public function set_under_page_title_placeholder()
	{
		if ( is_null( $this->publisherAds ) ) {
			return;
		}

		return $this->publisherAds->get_embed_code( 'under_page_title' );
	}

	/**
	 * Insert placeholder: top_of_page
	 */
	public function set_top_of_page_placeholder()
	{
		if ( is_null( $this->publisherAds ) ) {
			return;
		}

		return $this->publisherAds->get_embed_code( 'top_of_page' );
	}

	/**
	 * Insert placeholder: bottom_of_page
	 */
	public function set_bottom_of_page_placeholder()
	{
		if ( is_null( $this->publisherAds ) ) {
			return;
		}

		return $this->publisherAds->get_embed_code( 'bottom_of_page' );
	}

	// Total number of words found in paragraphs after the second one
	private $word_count = 0;

	// Last incontent placeholder index
	private $last_incontent_index = 0;

	/**
	 * Insert placeholder: [first|second]-paragraph and in_content
	 */
	public function set_paragraph_placeholder( $raw_text ) {
		if ( is_null( $this->publisherAds ) ) {
			return;
		}

		// A contentful paragraph has more than 100 characters
		if (strlen($raw_text) < 100) {
			return '';
		}

		// Count words
		$this->word_count += str_word_count( $raw_text );

		// Insert in_content placeholders every 150 words
		if (floor($this->word_count / self::WORDS_PER_PARAGRAPH) > $this->last_incontent_index) {
			$this->last_incontent_index++;

			$incontent_id = floor($this->word_count / self::WORDS_PER_PARAGRAPH) + 5;
			$ad = $this->publisherAds->get_next_in_content();

			if ( !is_null($ad) ) {
				return $this->publisherAds->get_embed_code( $ad->positionType );
			}

			return '';
		}
	}

	/**
	 * Insert placeholder: various in_content placeholders
	 */
	public function set_content_placeholders( $content )
	{
		if ( is_null( $this->publisherAds ) ) {
			return $content;
		}

		$inserter = new Ezoic_AdTester_Content_Inserter( $this->publisherAds, $content );
		$newContent = $inserter->insert_placeholders();

		return $newContent;
	}

	/**
	 * Determine if the feature is enabled
	 */
	private function enable() {
		$value = \get_option( 'ez_ad_integration_enabled', 'false' );

		// If feature header is present, set option accordingly
		if ( isset( $_SERVER[ 'HTTP_X_EZOIC_WP_ADS' ] ) ) {
			$value = $_SERVER[ 'HTTP_X_EZOIC_WP_ADS' ];

			\update_option( 'ez_ad_integration_enabled', $value );
		}

		// Enable feature if needed
		$this->is_public_enabled	= $value == 'true';
		$this->is_admin_enabled		= $value == 'true';
	}
}
