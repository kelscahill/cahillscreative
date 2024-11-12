<?php

namespace threewp_broadcast\actions;

/**
	@brief		An action for finding unlinked children.
	@details	This is a subaction called by post_action that specializes in finding unlinked children.

				It was created so that other add-ons could hook into the action so that found posts could be filtered.
	@since		2022-12-17 14:50:52
**/
class find_unlinked_children_post_action
	extends action
{
	/**
		@brief		IN: The original post_action action.
		@since		2022-12-17 14:52:17
	**/
	public $post_action;

	/**
		@brief		The callbacks for after get_posts.
		@details	Each callback is called with this action as the only parameter.
					From there you can access the posts array.
		@since		2022-12-17 14:53:13
	**/
	public $post_get_posts_callbacks = [];

	/**
		@brief		IN/OUT: The posts array, from the get_posts call.
		@details	Mostly used in conjunction with the callbacks for get posts.
		@see		$post_get_posts_callbacks
		@since		2022-12-17 14:54:57
	**/
	public $posts;

	/**
		@brief		IN: Which blogs do we run this action on?
		@since		2022-12-17 15:02:01
	**/
	public $requested_blogs;
}
