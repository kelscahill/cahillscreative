<?php

namespace threewp_broadcast\actions;

/**
	@brief		Does this attachment match the attachment we are looking for?
	@since		2020-02-18 17:49:50
**/
class attachment_matches
	extends action
{
	/**
		@brief		IN: The post object of the attachment we are currently looking at.
		@since		2020-02-18 17:51:27
	**/
	public $attachment_post;

	/**
		@brief		IN: The array of post objects that we have found.
		@since		2020-02-18 17:51:27
	**/
	public $attachment_posts;

	/**
		@brief		OUT: Does this attachment match the original?
		@since		2020-02-18 17:50:04
	**/
	public $matches;

	/**
		@brief		IN: The attachment_data object that we are looking for.
		@since		2020-02-18 17:51:08
	**/
	public $original_attachment_data;
}
