<?php

namespace threewp_broadcast\actions;

use \threewp_broadcast\blog_collection;
use \threewp_broadcast\broadcast_data\blog;

class get_user_writable_blogs
	extends action
{
	/**
		@brief		OUT: A collection of blogs the user has access to.
		@var		$blogs
		@since		20131003
	**/
	public $blogs;

	/**
		@brief		IN: ID of user to query.
		@var		$user_id
		@since		20131003
	**/
	public $user_id;

	public function _construct( $user_id = null )
	{
		$this->blogs = new blog_collection;

		if ( ! $user_id )
			$user_id = ThreeWP_Broadcast()->user_id();

		$this->user_id = $user_id;
	}

	/**
		@brief		Convenience method to add access to a blog.
		@since		2018-12-13 14:42:11
	**/
	public function add_access( $blog_id )
	{
		if ( ! ThreeWP_Broadcast()->blog_exists( $blog_id ) )
			return;
		$blog = blog::from_blog_id( $blog_id );
		$this->blogs->set( $blog_id, $blog );
		return $this;
	}
}
