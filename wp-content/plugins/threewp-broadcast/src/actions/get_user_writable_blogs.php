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
		@details	Parameter can be either an INT or an ( ARRAY of INT ).
		@since		2018-12-13 14:42:11
	**/
	public function add_access( $blog_ids )
	{
		if ( ! is_array( $blog_ids ) )
			$blog_ids = [ $blog_ids ];

		foreach( $blog_ids as $blog_id )
		{
			if ( ! ThreeWP_Broadcast()->blog_exists( $blog_id ) )
				continue;
			$blog = blog::from_blog_id( $blog_id );
			$this->blogs->set( $blog_id, $blog );
		}
		return $this;
	}

	/**
		@brief		Convenience method to remove access from a blog.
		@details	Parameter can be either an INT or an ( ARRAY of INT ).
		@since		2023-09-20 15:04:35
	**/
	public function remove_access( $blog_ids )
	{
		if ( ! is_array( $blog_ids ) )
			$blog_ids = [ $blog_ids ];

		foreach( $blog_ids as $blog_id )
			$this->blogs->forget( $blog_id );

		return $this;
	}
}
