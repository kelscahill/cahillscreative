<?php

namespace threewp_broadcast\premium_pack\classes\gutenberg_items;

use Exception;

/**
	@brief		Handle the sharing of finds so that various add-ons can modify the same data.
	@since		2020-01-31 08:14:38
**/
#[\AllowDynamicProperties]
class Shared_Finds
	extends \threewp_broadcast\premium_pack\classes\generic_items\Shared_Finds
{
	/**
		@brief		Add a find.
		@since		2020-01-31 08:18:29
	**/
	public function add_find( $find )
	{
		$key = static::get_key( $find );
		$block_name = $find->original[ 'blockName' ];
		$block_collection = $this->collection( $block_name );

		if ( ! $block_collection->has( $key ) )
		{
			ThreeWP_Broadcast()->debug( 'Shared finds: adding block %s', $key );

			$col = $block_collection->collection( $key )
				->collection( 'source' );

			$col->set( 'find', $find );
		}
		else
		{
			$col = $block_collection->collection( $key )->collection( 'source' );
			ThreeWP_Broadcast()->debug( 'Duplicate counter for %s is now %s', $key, $col->get_counter() + 1 );
		}

		// For the sake of optimization, call this only once, once we have the $col.
		$col->increase_counter();

		return $col;
	}

	/**
		@brief		Return the collection containing this find.
		@since		2020-01-31 08:31:07
	**/
	public function get_find_collection( $find )
	{
		$key = static::get_key( $find );
		$block_name = $find->original[ 'blockName' ];
		$r = $this->collection( $block_name )
			->collection( $key );

		// This is to keep the modified finds unique per blog.
		$blog_key = 'blog' . get_current_blog_id();
		if ( ! $r->has( $blog_key ) )
		{
			$source = $r->collection( 'source' );
			// This is a deep clone, since we have objects stored in here also.
			@ $source = unserialize( serialize( $source ) );
			ThreeWP_Broadcast()->debug( 'Cloning new %s for blog %s: %s', $key, get_current_blog_id(), $source );
			$r->set( $blog_key, $source );
		}

		return $r->collection( $blog_key );
	}

	/**
		@brief		Return the unique key for this block.
		@since		2020-01-31 08:18:47
	**/
	public function get_key( $find )
	{
		$key = $find->original[ 'blockName' ];
		$key .= json_encode( $find->original[ 'original' ] );
		return md5( $key );
	}
}
