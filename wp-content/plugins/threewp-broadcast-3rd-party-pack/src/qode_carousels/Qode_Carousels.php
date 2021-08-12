<?php

namespace threewp_broadcast\premium_pack\qode_carousels;

/**
	@brief			Adds support for <a href="http://www.qodethemes.com/">Qode Carousels from Qode Themes</a> plugin.
	@details		The Shortcode_Preparse
	@plugin_group	3rd party compatability
	@since			2016-08-10 15:16:34
**/
class Qode_Carousels
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		switch_to_blog( $bcd->parent_blog_id );

		foreach( $item->carousels as $carousel )
		{
			$this->debug( 'Broadcasting carousel %s from %s', $carousel->ID, $data->category );
			ThreeWP_Broadcast()->api()->broadcast_children( $carousel->ID, [ $bcd->current_child_blog_id ] );
		}

		restore_current_blog();

		// The ID is the category, which is unchanged.
		return $item->id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'qode_carousel';
	}

	/**
		@brief		Add the post type, for manual broadcast.
		@since		2016-07-26 19:07:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'carousels' );
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		$item->category = $item->attributes[ 'carousel' ];

		// Find all carousels with this category.
		$item->carousels = get_posts( [
			'posts_per_page' => -1,		// Get all posts
			'post_type' => 'carousels',
			'carousels_category' => $item->category,
		] );
	}
}
