<?php

namespace threewp_broadcast\broadcasting_data\taxonomies;

/**
	@brief		An index of all used terms.
	@since		2021-10-11 20:31:45
**/
class Term_Index
	extends \threewp_broadcast\collection
{
	/**
		@brief		Add this term to the index.
		@since		2021-10-11 20:37:49
	**/
	public function add( $term )
	{
		$term_id = $term->term_id;
		$this->set( $term_id, $term );
		return $this;
	}

	/**
		@brief		Convenience method to add several terms at once.
		@since		2021-10-11 20:38:47
	**/
	public function add_terms( $terms )
	{
		foreacH( $terms as $term )
			$this->add( $term );
		return $this;
	}

	/**
		@brief		Return a term.
		@details	We override this function in order to be able to index unknown terms on the fly.
		@since		2021-11-11 21:44:39
	**/
	public function get( $term_id )
	{
		$term = parent::get( $term_id );
		if ( ! $term )
			$term = $this->learn( $term_id );
		return $term;
	}

	/**
		@brief		Get, index and return this term.
		@since		2021-11-11 21:42:43
	**/
	public function learn( $term_id )
	{
		$term = get_term( $term_id );
		$this->add( $term );
		return $term;
	}
}
