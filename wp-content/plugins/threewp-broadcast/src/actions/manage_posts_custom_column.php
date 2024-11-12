<?php

namespace threewp_broadcast\actions;

use \threewp_broadcast\blog_collection;

class manage_posts_custom_column
	extends action
{
	public $html;

	/**
		@brief		IN: The Broadcast Data (linking data) of the post.
		@since		2023-04-24 20:43:55
	**/
	public $broadcast_data;

	/**
		@brief		IN: The ID of the parent blog of the post.
		@since		2023-04-24 20:43:25
	**/
	public $parent_blog_id;

	/**
		@brief		IN: The ID of the parent post of the post.
		@since		2023-04-24 20:43:25
	**/
	public $parent_post_id;

	/**
		@brief		IN: The post object we are managing.
		@since		2023-04-24 20:43:08
	**/
	public $post;

	public function _construct()
	{
		$this->html = new \threewp_broadcast\collections\strings_with_metadata;
	}

	public function render()
	{
		$r = '';
		foreach( $this->html as $key => $html )
		{
			$r .= sprintf( '<div class="%s">%s</div>', $key, $html );
		}
		return $r;
	}
}