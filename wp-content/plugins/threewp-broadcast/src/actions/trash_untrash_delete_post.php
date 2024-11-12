<?php

namespace threewp_broadcast\actions;

/**
	@brief		Run a trash / untrash / delete command on a child post.
	@since		2022-09-12 21:48:01
**/
class trash_untrash_delete_post
	extends action
{
	/**
		@brief		IN: The broadcast_data of the parent post.
		@since		2022-09-12 21:48:26
	**/
	public $broadcast_data;

	/**
		@brief		IN: The ID of the child blog on which to run this command.
		@since		2022-09-12 21:46:33
	**/
	public $child_blog_id;

	/**
		@brief		IN: The ID of the child post on which to run this command.
		@since		2022-09-12 21:46:33
	**/
	public $child_post_id;

	/**
		@brief		IN: The command to run: wp_trash_post, wp_delete_post, etc.
		@since		2022-09-12 21:47:34
	**/
	public $command;
}
