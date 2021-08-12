<?php

namespace threewp_broadcast\premium_pack\classes\gutenberg_items;

/**
	@brief		Handle the sharing of finds so that various add-ons can modify the same data.
	@since		2020-01-31 08:14:38
**/
class Shared_Finds
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Add a find.
		@since		2020-01-31 08:18:29
	**/
	public function add_find( $find )
	{
		$key = static::get_key( $find );
		$block_name = $find->original[ 'blockName' ];
		$col = $this->collection( $block_name )
			->collection( $key )
			->collection( 'source' );
		$col->set( 'find', $find )
			->increase_counter();
		return $col;
	}

	/**
		@brief		Is this find ready to be replaced?
		@since		2020-01-31 08:23:15
	**/
	public function can_be_replaced()
	{
		return $this->get_counter() < 1;
	}

	/**
		@brief		Decrease the counter.
		@since		2020-01-31 08:13:53
	**/
	public function decrease_counter()
	{
		$counter = $this->get( 'counter' );
		$counter--;
		$this->set( 'counter', $counter );
		return $this;
	}

	/**
		@brief		Get the counter value.
		@since		2020-01-31 08:23:44
	**/
	public function get_counter()
	{
		return $this->get( 'counter', 0 );
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
			$source = unserialize( serialize( $source ) );
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

	/**
		@brief		Increase the counter.
		@since		2020-01-31 08:13:53
	**/
	public function increase_counter()
	{
		$counter = $this->get( 'counter' );
		$counter++;
		$this->set( 'counter', $counter );
		return $this;
	}
}
