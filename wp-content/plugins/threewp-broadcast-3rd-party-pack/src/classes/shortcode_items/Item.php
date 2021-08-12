<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_items;

/**
	@brief		The shortcode object.
	@since		2016-07-14 12:46:32
**/
class Item
	extends \threewp_broadcast\premium_pack\classes\generic_items\Item

{
	/**
		@brief		Retrieves the slug.
		@since		2014-03-12 14:44:00
	**/
	public function get_slug()
	{
		return $this->shortcode;
	}

	/**
		@brief		Sets the slug.
		@details	Uses shortcode for backwards compatability.
		@since		2014-03-12 14:40:52
	**/
	public function set_slug( $key )
	{
		return $this->set_key( 'shortcode', $key );
	}
}
