<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_Excerpt_Inserter extends Ezoic_AdTester_Inserter {
	private $excerpt_number;

	public function __construct( $config, $excerpt_number ) {
		parent::__construct( $config );

		$this->excerpt_number	= $excerpt_number;
	}

	public function insert( $content ) {
		$rules = array();
		foreach ( $this->config->placeholder_config as $ph_config ) {
			if ( $ph_config->page_type == $this->page_type ) {
				$rules[ $ph_config->placeholder_id ] = $ph_config;
			}
		}

		// Insert placeholders
		foreach ( $rules as $rule ) {
			if ( $rule->display != 'disabled' ) {
				$placeholder = $this->config->placeholders[ $rule->placeholder_id ];

				switch ( $rule->display ) {
					case 'before_excerpt':
						$content = $this->relative_to_excerpt( $placeholder, $rule->display_option, $content, 'before' );
						break;

					case 'after_excerpt':
						$content = $this->relative_to_excerpt( $placeholder, $rule->display_option, $content, 'after' );
						break;
				}
			}
		}

		return $content;
	}

	/**
	 * Inserts a placeholder either before or after a paragraph
	 */
	private function relative_to_excerpt( $placeholder, $excerpt_number, $content, $mode = 'before' ) {
		$placeholder_markup = $placeholder->embed_code();

		// If the excerpt number is not specified or is not a number, exit
		if ( strlen( $excerpt_number ) == 0 || !is_numeric( $excerpt_number ) ) {
			return $content;
		}

		// Convert option to a number
		$placement = (int) $excerpt_number;

		// If this is not the correct excerpt, exit
		if ( $placement != $this->excerpt_number ) {
			return $content;
		}

		foreach ( $this->config->excerpt_tags as $excerpt_tag ) {

			// Most of the time, the position of the tag will be 0. The code below makes this generic enough to
			// use a different tag in the case of a fairly complicated excerpt.
			if ( $mode == 'before' ) {
				$tag = '<' . $excerpt_tag;
				$position = ez_stripos( $content, $tag );

				$content = ez_substr_replace( $content, $placeholder_markup, $position, 0 );

				return $content;
			}

			// Place after element
			$tag = '</' . $excerpt_tag . '>';
			$position = ez_stripos( $content, $tag );

			$content = ez_substr_replace( $content, $placeholder_markup, $position + ez_strlen( $tag ), 0 );

			return $content;
		}

		// For each paragraph position, insert the content above the location
		foreach ( $paragraph_pos as $position ) {
			$cur_paragraph_number++;
			$placement_valid = false;

			// If no option specified, always insert
			if ( ez_strlen( $paragraph_number ) == 0 ) {
				$placement_valid = true;

				// If paragraph numbers specified, insert for specific paragraphs
			} elseif ( empty( $valid_placements ) || in_array( $cur_paragraph_number, $valid_placements ) ) {
				$placement_valid = true;
			}

			// If we have a valid placement, insert placeholder
			if ( $placement_valid ) {
				$content = ez_substr_replace( $content, $placeholder_markup, $position + $offset, 0 );
				$offset += $placeholder_markup_len;
			}
		}

		// $content;

		return $content;
	}
}
