<?php

namespace threewp_broadcast\premium_pack\image_map_pro;

/**
	@brief		Handles the detection of image map shortcodes.
	@since		2017-10-29 16:20:23
**/
class Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		The shortcode that we are listening for.
		@since		2017-10-29 16:20:54
	**/
	public $shortcode = '';

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		// Do nothing.
	}

	/**
		@brief		Finalize the item before it is saved.
		@details	Currently just checks that the ID attribute exists.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		// Set the ID to a number that can't appear in the shortcode, since we only want to save the shortcode name in the bcd in remember_item.
		$item->id = PHP_INT_MAX;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return $this->shortcode;
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		$data = $bcd->image_map_pro->collection( 'shortcodes' )->get( $this->shortcode );
		$bcd->image_map_pro->collection( 'used_shortcodes' )->set( $this->shortcode, $data );
	}
}
