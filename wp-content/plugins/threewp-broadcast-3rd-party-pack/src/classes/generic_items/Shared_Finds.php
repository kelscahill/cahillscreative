<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

use Exception;

/**
	@brief		Handle the sharing of finds so that various add-ons can modify the same data.
	@since		2020-01-31 08:14:38
**/
abstract class Shared_Finds
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Add a find.
		@since		2020-01-31 08:18:29
	**/
	public abstract function add_find( $find );

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
	public abstract function get_find_collection( $find );

	/**
		@brief		Return the unique key for this find.
		@since		2020-01-31 08:18:47
	**/
	public function get_key( $find )
	{
		return md5( $find->original );
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
