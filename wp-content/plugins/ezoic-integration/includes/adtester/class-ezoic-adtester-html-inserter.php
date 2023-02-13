<?php

namespace Ezoic_Namespace;

class Ezoic_AdTester_HTML_Inserter extends Ezoic_AdTester_Inserter {
	public function __construct( $config ) {
		parent::__construct( $config );
	}

	private function get_rules() {
		$rules = array();

		foreach ( $this->config->placeholder_config as $ph_config ) {
			if ( $ph_config->page_type == $this->page_type && ( $ph_config->display == 'before_element' || $ph_config->display == 'after_element') ) {
				$rules[] = $ph_config;
			}
		}

		return $rules;
	}

	/**
	 * Perform a server-side element insertion
	 */
	public function insert_server( $content ) {
		// Do not run if dom module not loaded
		if ( !extension_loaded( "dom" ) ) {
			Ezoic_AdTester::log( "server-side element insertion enabled, but 'dom' module not found" );
			return $content;
		}

		$rules = $this->get_rules();

		// If no rules to process, move on
		if ( empty( $rules ) ) {
			return $content;
		}

		$body_tag_matches = preg_split ('/(<body.*?'.'>)/i', $content, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		if ( \count( $body_tag_matches ) !== 3 ) {
			return $content;
		}

		// Pull-in phpQuery to parse the document
		require_once( dirname( __FILE__ ) . '/../vendor/phpQuery/phpQuery.php' );

		// Parse document
		\libxml_use_internal_errors( true );
		$content = \phpQuery::newDocumentHTML( $body_tag_matches[2] );
		\libxml_use_internal_errors( false );

		foreach ( $rules as $rule ) {
			$nodes = @\pq( $rule->display_option );

			foreach ( $nodes as $found_node ) {
				$placeholder = $this->config->placeholders[ $rule->placeholder_id ];

				if ( $rule->display === 'before_element' ) {
					\pq( $found_node )->prepend( $placeholder->embed_code() );
				} elseif ( $rule->display === 'after_element' ) {
					\pq( $found_node )->append( $placeholder->embed_code() );
				}
			}
		}

		return $body_tag_matches[0] . $body_tag_matches[1] . $content->htmlOuter();
	}

	/**
	 * Imports elements into the DOM
	 * @param $nodes Nodes to import
	 * @param $parent Parent node of the $target element
	 * @param $target Target element before which nodes should be inserted
	 */
	private function insert_nodes( $nodes, $parent, $target ) {
		$reversed_nodes = array_reverse( $nodes );
		$current_node = $target;
		foreach ( $reversed_nodes as $node ) {
			$parent->insertBefore( $node, $current_node );
			$current_node = $node;
		}
	}

	/**
	 * Creates a DOMNode from markup
	 */
	private function create_nodes( $markup ) {
		$node = new \DOMDocument();

		@$node->loadHTML( '<span>' . $markup . '</span>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$nodesToInsert = $node->getElementsByTagName( 'span' )->item( 0 )->childNodes;

		return $nodesToInsert;
	}
}
