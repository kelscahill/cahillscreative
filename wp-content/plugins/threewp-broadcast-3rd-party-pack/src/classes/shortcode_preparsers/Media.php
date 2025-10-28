<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_preparsers;

use Exception;

/**
	* @brief		Handles media items in shortcodes.
	* @since		2024-11-02 06:53:23
**/
class Media
	extends Base
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

		// Allow plugins to override the forced broadcasting of new items, or to use get_or_broadcast and then not broadcast anything.
		$broadcast_children = apply_filters( 'shortcode_preparser_broadcast_children', true, $bcd, $item );
		if ( $broadcast_children )
		{
			switch_to_blog( $bcd->parent_blog_id );
			$item_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $item->id, [ $bcd->current_child_blog_id ] );
			$new_id = $item_bcd->new_post( 'ID' );
			restore_current_blog();
		}
		else
		{
			$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $item->id, get_current_blog_id() );
		}
		return $new_id;
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		// If available, save the broadcast_data of this item.
		if ( isset( $item->id ) )
			$item->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $item->id );
	}
}
