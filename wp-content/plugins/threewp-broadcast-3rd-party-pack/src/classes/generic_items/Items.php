<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		The container for items.
	@since		2016-07-14 12:46:32
**/
class Items
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Return an array of all shortcode names.
		@since		2014-03-13 21:07:04
	**/
	public function get_items()
	{
		$names = [];
		foreach( $this->items as $item )
			$names[ $item->get_key() ] = $item->get_key();
		return $names;
	}
}
