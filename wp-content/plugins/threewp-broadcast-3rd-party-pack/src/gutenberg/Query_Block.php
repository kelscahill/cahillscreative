<?php

namespace threewp_broadcast\premium_pack\gutenberg;

class Gutenberg
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2024-06-17 20:14:05
	**/
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		threewp_broadcast_preparse_content
		@since		2024-06-17 20:14:31
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		$blocks = ThreeWP_Broadcast()->gutenberg()->parse_blocks( $content, [
			'dump_blocks_once' => $action->id,
			'stripslashes' => false,		// We will want to be keeping as much of the original json as possible.
		] );

		if ( count( $blocks ) < 1 )
			return;

		if ( ! isset( $bcd->gutenberg_misc ) )
			$bcd->gutenberg_misc = ThreeWP_Broadcast()->collection();

		foreach( $blocks as $block )
		{
			if ( $block[ 'blockName' ] == 'query' )
			{
				$this->debug( 'Found query block!' );
				$bcd->gutenberg_misc->collection( 'query' )->append( $block );
				$taxQuery = $block[ 'attrs' ][ 'query' ][ 'taxQuery' ];
				foreach( $taxQuery as $taxonomy => $term_ids )
				{
					$this->debug( 'Resyncing taxonomy %s', $taxonomy );
					$bcd->taxonomies()->also_sync( null, $taxonomy );
					foreach( $term_ids as $term_id )
						$bcd->taxonomies()->use_term( $term_id );
				}
			}
		}
	}

	/**
		@brief		threewp_broadcast_parse_content
		@since		2024-06-17 20:23:56
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		if ( ! isset( $bcd->gutenberg_misc ) )
			return;

		foreach( $bcd->gutenberg_misc->collection( 'query' ) as $block )
		{
			$terms = $bcd->terms();
			$taxQuery = $block[ 'attrs' ][ 'query' ][ 'taxQuery' ];
			foreach( $taxQuery as $taxonomy => $term_ids )
			{
				foreach( $term_ids as $index => $term_id )
				{
					$new_term_id = $terms->get( $term_id );
					$taxQuery[ $taxonomy ][ $index ] = $new_term_id;
					$this->debug( 'Replacing term %s for taxonomy %s with %s', $term_id, $taxonomy, $new_term_id );
				}
			}
			$block[ 'attrs' ][ 'query' ][ 'taxQuery' ] = $taxQuery;

			$this->debug( 'New block taxQuery: %s', $block[ 'attrs' ][ 'query' ][ 'taxQuery' ] );

			$render_options = [
				'force_json_options' => true,
				'json_options' => JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
			];

			$action->content = ThreeWP_Broadcast()->gutenberg()->replace_text_with_block( $block[ 'original' ], $block, $action->content, $render_options );
		}
	}
}
