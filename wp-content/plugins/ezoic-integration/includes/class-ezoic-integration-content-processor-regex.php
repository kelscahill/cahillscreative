<?php

namespace Ezoic_Namespace;

class Ezoic_Integration_Content_Processor_Regex {
	private $conten = '';

	function __construct( $content ) {
		$this->content = $content;
	}

	/**
	 * Process filters and render the updated content
	 */
	public function render() {
		$this->process_paragraphs();

		return $this->content;
	}

	/**
	 * Processes all paragraphs in the content
	 */
	private function process_paragraphs() {
		\preg_match_all( '/<p.*?>(.*)<\/p>/i', $this->content, $matches );

		for ($index = 0; $index < count($matches[0]); $index++) {
			$to_append =  \apply_filters( 'ez_content_paragraph', $matches[1][$index] );

			$this->content = \str_replace($matches[0][$index], $matches[0][$index] . $to_append, $this->content);
		}
	}
}
