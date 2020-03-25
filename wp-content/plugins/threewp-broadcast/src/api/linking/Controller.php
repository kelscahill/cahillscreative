<?php

namespace threewp_broadcast\api\linking;

/**
	@brief		The linking controller.
	@since		2019-08-06 20:26:20
**/
class Controller
{
	/**
		@brief		The blog ID of the post we are working on.
		@since		2019-08-06 22:32:57
	**/
	public $blog_id;

	/**
		@brief		The post ID of the post we are working on.
		@since		2019-08-06 22:33:09
	**/
	public $post_id;

	/**
		@brief		Constructor.
		@since		2019-08-06 22:32:37
	**/
	public function __construct( $blog_id, $post_id )
	{
		$this->blog_id = $blog_id;
		$this->post_id = $post_id;
	}

	/**
		@brief		Retrieve an array of children of this post.
		@return		An array of [ blog_id => $post_id ] or an empty array if no children are linked.
		@since		2019-08-06 22:49:22
	**/
	public function children()
	{
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $this->blog_id, $this->post_id );
		$children = $broadcast_data->get_linked_children();
		if ( ! $children )
			return [];
		return $children;
	}

	/**
		@brief		Is this post a child post?
		@since		2019-08-06 22:52:46
	**/
	public function is_child()
	{
		return $this->parent() !== false;
	}

	/**
		@brief		Is this post linked at all?
		@since		2019-08-06 22:54:26
	**/
	public function is_linked()
	{
		return $this->is_child() || $this->is_parent();
	}

	/**
		@brief		Is this a parent post?
		@since		2019-08-06 22:53:07
	**/
	public function is_parent()
	{
		return $this->parent() === false;
	}

	/**
		@brief		Link this post to the post specified.
		@since		2019-08-06 22:38:40
	**/
	public function link( $blog_or_post_id, $post_id = false )
	{
		if ( ! $post_id )
		{
			$post_id = $blog_or_post_id;
			$blog_or_post_id = get_current_blog_id();
		}

		$link_from_blog_id = $this->blog_id;
		$link_from_post_id = $this->post_id;

		$link_to_blog_id = $blog_or_post_id;
		$link_to_post_id = $post_id;

		// Check the source.
		switch_to_blog( $link_from_blog_id, $link_from_post_id );
		$from_post = get_post( $link_from_post_id );
		restore_current_blog();
		if ( ! $from_post )
			return ThreeWP_Broadcast()->debug( 'Linking: Cannot link from nonexistent %s %s', $link_from_blog_id, $link_from_post_id );

		// Check the target.
		switch_to_blog( $link_to_blog_id, $link_to_post_id );
		$to_post = get_post( $link_to_post_id );
		restore_current_blog();
		if ( ! $to_post )
			return ThreeWP_Broadcast()->debug( 'Linking: Cannot link to nonexistent %s %s', $link_to_blog_id, $link_to_post_id );

		// What is the status of the post we are linking to?
		$other_post = ThreeWP_Broadcast()->api()->linking( $link_to_blog_id, $link_to_post_id );

		// We can't link to a child.
		if ( $other_post->is_child() )
			return ThreeWP_Broadcast()->debug( 'Linking: Cannot link to existing child %s %s', $link_to_blog_id, $link_to_post_id );

		ThreeWP_Broadcast()->debug( 'Linking %s %s to child %s %s',
			$link_from_blog_id,
			$link_from_post_id,
			$link_to_blog_id,
			$link_to_post_id
		);

		// Save for the parent.
		$parent_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $link_from_blog_id, $link_from_post_id );
		$parent_broadcast_data->add_linked_child( $link_to_blog_id, $link_to_post_id );
		ThreeWP_Broadcast()->set_post_broadcast_data( $link_from_blog_id, $link_from_post_id, $parent_broadcast_data );

		// And now for the child.
		$child_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $link_to_blog_id, $link_to_post_id );
		$child_broadcast_data->set_linked_parent( $link_from_blog_id, $link_from_post_id );
		ThreeWP_Broadcast()->set_post_broadcast_data( $link_to_blog_id, $link_to_post_id, $child_broadcast_data );
	}

	/**
		@brief		Return the parent post of this post.
		@return		An array of [ blog_id, post_id ] of the parent post, or false if not a child.
		@since		2019-08-06 22:53:35
	**/
	public function parent()
	{
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $this->blog_id, $this->post_id );
		return $broadcast_data->get_linked_parent();
	}

	/**
		@brief		Unlink this post from the other posts.
		@since		2019-08-06 22:43:57
	**/
	public function unlink( $post_ids = [] )
	{
		switch_to_blog( $this->blog_id);
		ThreeWP_Broadcast()->api()->unlink( $this->post_id, $post_ids );
		restore_current_blog();
		$post_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $this->blog_id, $this->post_id );
		return $this;
	}
}
