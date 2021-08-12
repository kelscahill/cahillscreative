<?php

namespace threewp_broadcast\actions;

/**
	@brief		Execute an action on a post.
	@details	The action could be delete, trash, unlink, etc.
	@since		2014-11-02 16:25:57
**/
class post_action
	extends action
{
	/**
		@brief		IN: The action to execute: delete, untrash, etc.
		@since		2014-11-02 16:29:59
	**/
	public $action;

	/**
		@brief		[IN]: An optional array of blog IDs on which to run this action.
		@details	Not all post actions use this variable.
		@since		2019-01-09 19:32:22
	**/
	public $blogs = [];

	/**
		@brief		Run this post action with high priority?
		@details	Mostly affects the find_unlinked_children subaction.
		@since		2020-12-16 13:22:33
	**/
	public $high_priority = true;

	/**
		@brief		IN: The ID of the post on which to execute this action.
		@since		2014-11-02 16:28:00
	**/
	public $post_id;
}
