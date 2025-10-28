<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Convenience class to handle a shortcode that has one attachment.
	@since		2021-01-13 19:19:05
**/
class Shortcode_Attachment
	extends Shortcode_Preparser
{
	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		$this->prepare_to_copy( $bcd, $item );
		if ( ! isset( $item->id ) )
			throw new Exception( $this->debug( 'Unable to copy the item since it has no ID.' ) );

		$new_image_id = $bcd->copied_attachments()->get( $item->id );
		return $new_image_id;
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		$bcd->try_add_attachment( $item->id );
	}
}
