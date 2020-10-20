<?php

namespace threewp_broadcast\actions;

/**
	@brief		Allow the equivalent_posts->get_or_broadcast function to be overriden.
	@details	Created because someone needed equivalent posts to be ignored during initial broadcast.
	@since		2020-09-29 07:02:15
**/
class get_or_broadcast
	extends action
{
	/**
		@brief		IN: The ID of the child blog.
		@since		2020-09-29 07:05:12
	**/
	public $child_blog_id;

	/**
		@brief		OUT: The whole child post object.
		@since		2020-09-29 07:05:48
	**/
	public $child_post_id;

	/**
		@brief		IN: The ID of the parent blog.
		@since		2020-09-29 07:05:12
	**/
	public $parent_blog_id;

	/**
		@brief		IN: The ID of the parent post.
		@since		2020-09-29 07:05:12
	**/
	public $parent_post_id;

	/**
		@brief		[OUT]: Should the child post be broadcasted?
		@since		2020-09-29 07:04:20
	**/
	public $broadcast_child_post = true;
}
