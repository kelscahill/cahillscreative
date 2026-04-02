<?php
/**
 * Tokenizer Class.
 *
 * Converts text into searchable tokens with Unicode support,
 * stopword filtering, and normalization.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.9
 * @package    Search_Filter_Pro/Indexer/Search
 */

namespace Search_Filter_Pro\Indexer\Search;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tokenizer: Converts text into searchable tokens.
 *
 * Handles Unicode, removes punctuation, applies stopwords,
 * normalizes to lowercase. No stemming in Phase 1 (added Phase 3).
 *
 * @since 3.0.9
 */
class Tokenizer {

	/**
	 * Stopwords to filter out.
	 *
	 * @since 3.0.9
	 * @var   array
	 */
	private $stopwords;

	/**
	 * Minimum term length to index.
	 *
	 * @since 3.0.9
	 * @var   int
	 */
	private $min_term_length = 3;

	/**
	 * Maximum term length to index.
	 *
	 * @since 3.0.9
	 * @var   int
	 */
	private $max_term_length = 100;

	/**
	 * Constructor.
	 *
	 * @since 3.0.9
	 * @param array|null $stopwords Optional stopwords array.
	 */
	public function __construct( $stopwords = null ) {
		if ( null === $stopwords ) {
			// Default English stopwords (expand per language in Phase 3).
			$this->stopwords = array_flip(
				array(
					'the',
					'a',
					'an',
					'and',
					'or',
					'but',
					'in',
					'on',
					'at',
					'to',
					'for',
					'of',
					'as',
					'by',
					'be',
					'is',
					'are',
					'was',
					'were',
					'this',
					'that',
					'with',
					'from',
					'not',
					'have',
					'has',
					'had',
				)
			);
		} else {
			$this->stopwords = array_flip( $stopwords );
		}
	}

	/**
	 * Tokenize text into searchable terms.
	 *
	 * @since 3.0.9
	 * @param string $text Text to tokenize.
	 * @return array Array of tokens (normalized, filtered).
	 */
	public function tokenize( $text ) {
		// Handle null/empty text.
		if ( $text === null || $text === '' ) {
			return array();
		}

		// Ensure UTF-8 encoding.
		if ( ! mb_check_encoding( $text, 'UTF-8' ) ) {
			$text = mb_convert_encoding( $text, 'UTF-8', 'auto' );
		}

		mb_internal_encoding( 'UTF-8' );

		// Normalize to lowercase.
		$text = mb_strtolower( $text, 'UTF-8' );

		// Split on non-letter/non-number characters.
		// \pL = any letter in any script (Unicode-aware).
		// \pN = any number in any script.
		$tokens = preg_split( '/[^\pL\pN]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Filter tokens.
		$filtered = array();

		/**
		 * Filter whether to allow pure numeric tokens (e.g., SKUs like "12345").
		 *
		 * By default, only tokens containing at least one letter are indexed.
		 * Enable this filter to also index pure numeric tokens.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $allow_numeric Whether to allow pure numeric tokens. Default false.
		 */
		$allow_numeric = apply_filters( 'search-filter-pro/search/allow_numeric_tokens', false );

		foreach ( $tokens as $token ) {
			$len = mb_strlen( $token, 'UTF-8' );

			// Length check.
			if ( $len < $this->min_term_length || $len > $this->max_term_length ) {
				continue;
			}

			// Stopword check.
			if ( isset( $this->stopwords[ $token ] ) ) {
				continue;
			}

			// Keep if contains letters.
			if ( preg_match( '/\pL/u', $token ) ) {
				$filtered[] = $token;
			} elseif ( $allow_numeric && preg_match( '/^\d+$/', $token ) ) {
				// Allow pure numeric tokens if filter enabled (e.g., for SKU search).
				$filtered[] = $token;
			}
		}

		return $filtered;
	}


	/**
	 * Tokenize text for exact-match sources (hybrid indexing).
	 *
	 * Returns the preserved (lowercased) term alongside tokenized parts.
	 * Enables numeric tokens for identifier support (SKUs, product codes).
	 *
	 * Example: "ABC-123" → ['abc-123', 'abc', '123']
	 *
	 * @since 3.2.0
	 * @param string $text Text to tokenize.
	 * @return array Array of tokens (preserved first, then parts).
	 */
	public function tokenize_hybrid( $text ) {
		// Handle null/empty text.
		if ( $text === null || $text === '' ) {
			return array();
		}

		// Ensure UTF-8 encoding.
		if ( ! mb_check_encoding( $text, 'UTF-8' ) ) {
			$text = mb_convert_encoding( $text, 'UTF-8', 'auto' );
		}

		mb_internal_encoding( 'UTF-8' );

		// Get preserved term (lowercased, trimmed).
		$preserved = mb_strtolower( trim( $text ), 'UTF-8' );

		// Get tokenized parts with numeric tokens enabled.
		$tokenized = $this->tokenize_with_numeric( $text );

		// Combine: preserved first, then unique tokenized parts.
		$terms = array( $preserved );
		foreach ( $tokenized as $token ) {
			if ( $token !== $preserved && ! in_array( $token, $terms, true ) ) {
				$terms[] = $token;
			}
		}

		return $terms;
	}

	/**
	 * Tokenize text with numeric tokens always enabled.
	 *
	 * Used internally by tokenize_hybrid() for exact-match sources.
	 *
	 * @since 3.2.0
	 * @param string $text Text to tokenize.
	 * @return array Array of tokens (with numeric tokens allowed).
	 */
	private function tokenize_with_numeric( $text ) {
		// Handle null/empty text.
		if ( $text === null || $text === '' ) {
			return array();
		}

		// Ensure UTF-8 encoding.
		if ( ! mb_check_encoding( $text, 'UTF-8' ) ) {
			$text = mb_convert_encoding( $text, 'UTF-8', 'auto' );
		}

		mb_internal_encoding( 'UTF-8' );

		// Normalize to lowercase.
		$text = mb_strtolower( $text, 'UTF-8' );

		// Split on non-letter/non-number characters.
		$tokens = preg_split( '/[^\pL\pN]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Filter tokens - always allow numeric.
		$filtered = array();

		foreach ( $tokens as $token ) {
			$len = mb_strlen( $token, 'UTF-8' );

			// Length check.
			if ( $len < $this->min_term_length || $len > $this->max_term_length ) {
				continue;
			}

			// Stopword check.
			if ( isset( $this->stopwords[ $token ] ) ) {
				continue;
			}

			// Keep if contains letters OR is pure numeric.
			if ( preg_match( '/\pL/u', $token ) || preg_match( '/^\d+$/', $token ) ) {
				$filtered[] = $token;
			}
		}

		return $filtered;
	}

	/**
	 * Tokenize for exact-match sources and get term positions.
	 *
	 * Returns preserved term + tokenized parts with their positions.
	 * The preserved term gets position 0, tokenized parts get sequential positions.
	 *
	 * @since 3.2.0
	 * @param string $text Text to analyze.
	 * @return array ['term' => [pos1, pos2, ...], ...]
	 */
	public function tokenize_hybrid_with_positions( $text ) {
		$terms     = $this->tokenize_hybrid( $text );
		$positions = array();
		$pos       = 0;

		foreach ( $terms as $term ) {
			if ( ! isset( $positions[ $term ] ) ) {
				$positions[ $term ] = array();
			}
			$positions[ $term ][] = $pos;
			++$pos;
		}

		return $positions;
	}

	/**
	 * Tokenize and get term frequencies.
	 *
	 * @since 3.0.9
	 * @param string $text Text to analyze.
	 * @return array ['term' => frequency, ...]
	 */
	public function tokenize_with_frequencies( $text ) {
		$tokens = $this->tokenize( $text );
		return array_count_values( $tokens );
	}

	/**
	 * Tokenize and get term positions.
	 *
	 * @since 3.0.9
	 * @param string $text Text to analyze.
	 * @return array ['term' => [pos1, pos2, ...], ...]
	 */
	public function tokenize_with_positions( $text ) {
		$tokens    = $this->tokenize( $text );
		$positions = array();

		foreach ( $tokens as $pos => $token ) {
			if ( ! isset( $positions[ $token ] ) ) {
				$positions[ $token ] = array();
			}
			$positions[ $token ][] = $pos;
		}

		return $positions;
	}
}
