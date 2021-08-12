<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_items;

/**
	@brief		The container for shortcodes.
	@since		2016-07-14 12:46:32
**/
class Shortcodes
	extends \plainview\sdk_broadcast\collections\collection
{
	/**
		@brief		Return an array of all shortcode names.
		@since		2014-03-13 21:07:04
	**/
	public function get_shortcodes()
	{
		$names = [];
		foreach( $this->items as $item )
			$names[ $item->get_shortcode() ] = $item->get_shortcode();
		return $names;
	}
}
