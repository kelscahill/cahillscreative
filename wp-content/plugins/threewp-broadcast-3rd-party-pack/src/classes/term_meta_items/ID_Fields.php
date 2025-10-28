<?php

namespace threewp_broadcast\premium_pack\classes\term_meta_items;

/**
	@brief		Convenience class for quickly searching the id fields.
	@since		2021-04-11 20:19:21
**/
class ID_Fields
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Is this taxonomy interesting?
		@since		2021-04-11 20:31:10
	**/
	public function has_taxonomy( $taxonomy )
	{
		return $this->collection( 'taxonomy' )->collection( $taxonomy )->count() > 0;
	}

	/**
		@brief		Does this taxonomy term exist?
		@since		2021-04-11 20:38:31
	**/
	public function has_taxonomy_term( $taxonomy, $term_slug )
	{
		$terms = $this->collection( 'taxonomy' )
			->collection( $taxonomy )
			->collection( 'term' );
		foreach( $terms->to_array() as $key => $ignore )
			if ( static::slug_matches_key( $term_slug, $key ) )
				return true;
		return false;
	}

	/**
		@brief		Does this meta key exist for this taxonomy / term combo?
		@since		2021-04-11 21:23:48
	**/
	public function has_taxonomy_term_meta( $taxonomy, $term_slug, $meta_key_slug )
	{
		$terms = $this->collection( 'taxonomy' )
			->collection( $taxonomy )
			->collection( 'term' );
		foreach( $terms as $key => $meta_keys )
			if ( static::slug_matches_key( $term_slug, $key ) )
			{
				$meta_keys = $meta_keys->collection( 'key' );
				foreach( $meta_keys as $meta_key )
					if ( static::slug_matches_key( $meta_key_slug, $meta_key ) )
						return true;
			}
		return false;
	}

	/**
		@brief		Load the array.
		@since		2021-04-11 20:21:06
	**/
	public function load( $lines )
	{
		$lines = array_filter( $lines );

		foreach( $lines as $line )
		{
			$columns = explode( " ", $line );

			// We need exactly 3 values
			if ( count( $columns ) != 3 )
				continue;

			$this->collection( 'taxonomy' )
				->collection( $columns[ 0 ] )
				->collection( 'term' )
				->collection( $columns[ 1 ] )
				->collection( 'key' )
				->set( $columns[ 2 ], $columns[ 2 ] );
		}
	}

	/**
		@brief		Does this slug match the key (with or without wildcards).
		@since		2021-04-11 21:18:33
	**/
	public static function slug_matches_key( $slug, $key )
	{
		if ( $slug == '' )
			return false;
		// No wildcard = straight match
		if ( strpos( $key, '*' ) === false )
		{
			if ( $key == $slug )
				return true;
		}
		else
		{
			$preg = str_replace( '*', '.*', $key );
			$preg = sprintf( '/%s/', $preg );
			$rand = md5( microtime() );
			$result = preg_replace( $preg, $rand, $slug );
			if ( strpos( $result, $rand ) !== false )
				return true;
		}
		return false;
	}
}
