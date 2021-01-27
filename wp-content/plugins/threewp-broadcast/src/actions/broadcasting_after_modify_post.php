<?php

namespace threewp_broadcast\actions;

/**
	@brief		Called after [maybe] having modified the post.
	@see		broadcasting_modify_post
	@since		2020-12-14 12:21:09
**/
class broadcasting_after_modify_post
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@details	The BCD contains the ->modified_post object which might have had changes made.
		@since		2020-12-14 12:21:09
	**/
	public $broadcasting_data;

	/**
		@brief		IN: Was the post modified?
		@since		2020-12-14 12:23:43
	**/
	public $post_modified = false;
}
