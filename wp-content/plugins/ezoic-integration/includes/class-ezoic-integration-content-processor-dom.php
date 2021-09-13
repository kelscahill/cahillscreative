<?php

namespace Ezoic_Namespace;

class Ezoic_Integration_Content_Processor_DOM {
	private $dom = null;
	private $xpath = null;

	function __construct( $content ) {
		// DOMDocument defaults to a latin encoding, be sure to override
		$this->dom = new \DOMDocument( '1.0', 'UTF-8' );

		$html = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
		@$this->dom->loadHTML( $content /*, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD*/ );

		$this->xpath = new \DOMXpath( $this->dom );
	}

	/**
	 * Process filters and render the updated content
	 */
	public function render() {
		$this->process_paragraphs();

		return $this->saveHTMLExact();
	}

	/**
	 * Processes all paragraphs in the content
	 */
	private function process_paragraphs() {
		$paragraphs = $this->xpath->query( '//p' );

		foreach ( $paragraphs as $paragraph ) {
			$raw_text = '';
			$raw_text_nodes = $this->xpath->query( './/text()', $paragraph );
			foreach ($raw_text_nodes as $node) {
				$raw_text .= $node->nodeValue;
			}

			$this->insertAfter( $paragraph, apply_filters( 'ez_content_paragraph', $raw_text ) );
		}
	}

	/**
	 * Strips headers and tags added by DOMDocument::loadHTML
	 */
	private function saveHTMLExact() {
		$content = preg_replace( array("/^\<\!DOCTYPE.*?<html><body>/si", "!</body></html>$!si" ), "", $this->dom->saveHTML() );

		return $content;
	}

	/**
	 * Finds the next sibling of the given element that is a DOM Element
	 *
	 * Note: Sometimes domNode::nextSibling() may return whitespace if the document contains
	 * whitespace between elements
	 */
	private function nextElementSibling( $node ) {
		while ( $node && ( $node = $node->nextSibling ) ) {
			if ( $node instanceof DOMElement ) {
				break;
			}
		}

		return $node;
	}

	/**
	 * Inserts a new node as a sibling following the passed-in node
	 * If there is no exisitng sibling to insert the new node before,
	 * it is appended as a new child of the parent
	 *
	 * @param DOMNode $domNode Node after which to append the new node
	 */
	private function insertAfter( $domNode, $markup ) {
		// Create new node
		$newNode = $this->create_node( $markup );

		// Import node
		$imported = $this->dom->importNode( $newNode, true );

		// Insert node into the dom
		$parentNode = $domNode->parentNode;

		// Leaving the below just in case, see the comment for nextElementSibling for an explanation
		// $parentNode->insertBefore($imported, $this->nextElementSibling($domNode));

		$parentNode->insertBefore( $imported, $domNode->nextSibling );
	}

	/**
	 * Creates a DOMNode for the given markup
	 * Note: $markup must only have one root node
	 */
	private function create_node( $markup ) {
		$testNode = new \DOMDocument();

		// <span> is used as a placeholder element so that we can extract the first child node and return that
		@$testNode->loadHTML( '<span>' . $markup . '</span>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$parentNode = $testNode->getElementsByTagName( 'span' )->item( 0 );

		return $parentNode;
	}

}
