<?php
/**
 * Stemming Tokenizer Class.
 *
 * Extends base tokenizer with stemming support for fuzzy matching.
 * Uses Porter Stemmer algorithm via Snowball stemmers.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search
 */

namespace Search_Filter_Pro\Indexer\Search;

use Search_Filter_Pro\Util;
use Search_Filter_Pro\Vendor\Wamania\Snowball\StemmerFactory;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stemming Tokenizer: Adds stemming capabilities to base tokenizer.
 *
 * Supports 15 languages via Snowball stemmer algorithm:
 * ca, da, de, en, es, fi, fr, it, nl, no, pt, ro, ru, sv, tr
 *
 * @since 3.0.9
 */
class Stemming_Tokenizer extends Tokenizer {

	/**
	 * Language code for stemming.
	 *
	 * @since 3.0.9
	 * @var   string
	 */
	private $language;

	/**
	 * Stemmer instance.
	 *
	 * @since 3.0.9
	 * @var   \Search_Filter_Pro\Vendor\Wamania\Snowball\Stemmer\Stemmer|null
	 */
	private $stemmer;

	/**
	 * Supported languages for stemming.
	 *
	 * Maps language codes to Snowball stemmer language names.
	 * Limited to languages supported by wamania/php-stemmer library.
	 *
	 * Note: Turkish (tr) is not included because wamania/php-stemmer
	 * doesn't implement it, even though the Snowball algorithm exists.
	 *
	 * @since 3.0.9
	 * @var   array
	 */
	private $supported_languages = array(
		'ca' => 'ca', // Catalan.
		'da' => 'da', // Danish.
		'de' => 'de', // German.
		'en' => 'en', // English.
		'es' => 'es', // Spanish.
		'fi' => 'fi', // Finnish.
		'fr' => 'fr', // French.
		'it' => 'it', // Italian.
		'nl' => 'nl', // Dutch.
		'no' => 'no', // Norwegian.
		'pt' => 'pt', // Portuguese.
		'ro' => 'ro', // Romanian.
		'ru' => 'ru', // Russian.
		'sv' => 'sv', // Swedish.
	);

	/**
	 * Constructor.
	 *
	 * @since 3.0.9
	 * @param string     $language  Language code (ISO 639-1).
	 * @param array|null $stopwords Optional stopwords array.
	 */
	public function __construct( $language = 'en', $stopwords = null ) {
		parent::__construct( $stopwords );

		$this->language = $language;
		$this->stemmer  = null;

		// Check if StemmerFactory class exists (scoped vendor may not be loaded).
		if ( ! class_exists( 'Search_Filter_Pro\\Vendor\\Wamania\\Snowball\\StemmerFactory' ) ) {
			// Stemmer library not available - stemming will be skipped.
			return;
		}

		// Initialize stemmer if language is supported.
		if ( isset( $this->supported_languages[ $language ] ) ) {
			try {
				$this->stemmer = StemmerFactory::create( $this->supported_languages[ $language ] );
			} catch ( \Exception $e ) {
				// Log warning but continue (stemming will be skipped).
				Util::error_log( "Failed to initialize stemmer for language '{$language}': " . $e->getMessage(), 'warning', true );
			}
		}
	}

	/**
	 * Tokenize with stemming support.
	 *
	 * Returns array of term data with original term and stem.
	 *
	 * @since 3.0.9
	 * @param string $text Text to tokenize.
	 * @return array Array of ['term' => 'original', 'stem' => 'stemmed']
	 */
	public function tokenize_with_stems( $text ) {
		$tokens = $this->tokenize( $text );
		$result = array();

		foreach ( $tokens as $token ) {
			$stem     = $this->stem( $token );
			$result[] = array(
				'term' => $token,
				'stem' => $stem,
			);
		}

		return $result;
	}

	/**
	 * Stem a single term.
	 *
	 * Returns the stemmed form of the term, or the original if:
	 * - Language not supported
	 * - Stemmer not initialized
	 * - Stemming fails
	 *
	 * @since 3.0.9
	 * @param string $term Term to stem.
	 * @return string Stemmed term or original term.
	 */
	public function stem( $term ) {
		// If no stemmer available, return original.
		if ( null === $this->stemmer ) {
			return $term;
		}

		try {
			// Stem the term.
			$stemmed = $this->stemmer->stem( $term );

			// Return stemmed version, or original if stemming produced empty string.
			return ! empty( $stemmed ) ? $stemmed : $term;

		} catch ( \Exception $e ) {
			// Log warning and return original term.
			Util::error_log( "Stemming error for term '{$term}': " . $e->getMessage(), 'warning' );
			return $term;
		}
	}

	/**
	 * Generate metaphone code for a term.
	 *
	 * Uses PHP's built-in metaphone() function for phonetic matching.
	 * Handles typos like "Stephen" → "Steven", "Sean" → "Shawn".
	 *
	 * @since 3.0.9
	 * @param string $term Term to generate metaphone code for.
	 * @return string Metaphone code (4 characters).
	 */
	public function metaphone( $term ) {
		// Generate 4-character metaphone code.
		// Empty metaphone results fall back to original term.
		$code = metaphone( $term, 4 );
		return ! empty( $code ) ? $code : $term;
	}

	/**
	 * Tokenize with stems and positions.
	 *
	 * Combines stemming with position tracking for phrase search support.
	 *
	 * @since 3.0.9
	 * @param string $text Text to analyze.
	 * @return array ['term' => ['stem' => 'stemmed', 'positions' => [pos1, pos2, ...]], ...].
	 */
	public function tokenize_with_stems_and_positions( $text ) {
		$tokens    = $this->tokenize( $text );
		$term_data = array();

		foreach ( $tokens as $pos => $token ) {
			$stem      = $this->stem( $token );
			$metaphone = $this->metaphone( $token );

			if ( ! isset( $term_data[ $token ] ) ) {
				$term_data[ $token ] = array(
					'stem'      => $stem,
					'metaphone' => $metaphone,
					'positions' => array(),
				);
			}

			$term_data[ $token ]['positions'][] = $pos;
		}

		return $term_data;
	}

	/**
	 * Tokenize for exact-match sources with stems and positions.
	 *
	 * Returns preserved term + tokenized parts, each with stem, metaphone, and positions.
	 * Used for hybrid indexing of identifier-style sources (SKUs, product codes).
	 *
	 * @since 3.2.0
	 * @param string $text Text to analyze.
	 * @return array ['term' => ['stem' => 'stemmed', 'metaphone' => 'code', 'positions' => [pos1, ...]], ...].
	 */
	public function tokenize_hybrid_with_stems_and_positions( $text ) {
		$tokens    = $this->tokenize_hybrid( $text );
		$term_data = array();

		foreach ( $tokens as $pos => $token ) {
			$stem      = $this->stem( $token );
			$metaphone = $this->metaphone( $token );

			if ( ! isset( $term_data[ $token ] ) ) {
				$term_data[ $token ] = array(
					'stem'      => $stem,
					'metaphone' => $metaphone,
					'positions' => array(),
				);
			}

			$term_data[ $token ]['positions'][] = $pos;
		}

		return $term_data;
	}

	/**
	 * Check if stemming is available for current language.
	 *
	 * @since 3.0.9
	 * @return bool True if stemming is available.
	 */
	public function has_stemming_support() {
		return null !== $this->stemmer;
	}

	/**
	 * Get current language code.
	 *
	 * @since 3.0.9
	 * @return string Language code.
	 */
	public function get_language() {
		return $this->language;
	}

	/**
	 * Get list of supported languages.
	 *
	 * Returns 14 languages supported by wamania/php-stemmer.
	 * Turkish (tr) excluded - Snowball algorithm exists but library doesn't implement it.
	 *
	 * @since 3.2.0
	 * @return array Array of supported language codes.
	 */
	public static function get_supported_languages() {
		return array( 'ca', 'da', 'de', 'en', 'es', 'fi', 'fr', 'it', 'nl', 'no', 'pt', 'ro', 'ru', 'sv' );
	}

	/**
	 * Get the language code from WordPress locale.
	 *
	 * Extracts the 2-letter language code from WordPress locale.
	 * Handles special cases like Norwegian (nb, nn -> no).
	 *
	 * @since 3.1.0
	 * @return string Language code (ISO 639-1).
	 */
	public static function get_wp_language_code() {
		$locale = get_locale();

		// Extract first 2 characters (language code).
		$lang = substr( $locale, 0, 2 );

		// Handle Norwegian variants (nb_NO, nn_NO -> no).
		if ( in_array( $lang, array( 'nb', 'nn' ), true ) ) {
			$lang = 'no';
		}

		return $lang;
	}

	/**
	 * Check if the current WordPress language supports stemming.
	 *
	 * @since 3.1.0
	 * @return bool True if the current WP language has stemming support.
	 */
	public static function wp_language_supports_stemming() {
		$lang            = self::get_wp_language_code();
		$supported_langs = self::get_supported_languages();

		return in_array( $lang, $supported_langs, true );
	}

	/**
	 * Check if the current WordPress language supports metaphone.
	 *
	 * Metaphone works best with English. For other languages, it provides
	 * limited but still useful phonetic matching.
	 *
	 * @since 3.1.0
	 * @return bool True if metaphone should be used for the current language.
	 */
	public static function wp_language_supports_metaphone() {
		// Metaphone is primarily designed for English but provides
		// some benefit for other Latin-script languages.
		$metaphone_langs = array( 'en', 'de', 'nl', 'da', 'sv', 'no', 'fi', 'es', 'pt', 'it', 'fr', 'ca', 'ro' );
		$lang            = self::get_wp_language_code();

		return in_array( $lang, $metaphone_langs, true );
	}
}
