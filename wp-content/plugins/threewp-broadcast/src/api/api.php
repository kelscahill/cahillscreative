<?php

namespace threewp_broadcast\api;

/**
	@brief		API methods, to make life easier for developers.
	@details

This is read much easier if you run doxygen...

<h2>Using the API</h2>

Retrieve the API object by asking Broadcast for it.

<code>$api = ThreeWP_Broadcast()->api();</code>

Or call an API method directly.

<code>ThreeWP_Broadcast()->api()->broadcast_children( 123, [ 10, 11, 12 ] );</code>

<h2>Broadcasting</h2>

The methods below refer to the post ID on the current blog.

To broadcast a post to one or more blogs, see broadcast_children(). The posts will be linked as children.

To rebroadcast (update) the post, see update_children(). You can also, optionally, specified an array of new blog IDs which to add the post. Linking is parent <-> child.

Broadcasting is done like so:

<ul>
	<li>A broadcasting_data (bcd) object is created with the specified post_id as the source.</li>
	<li>The bcd will examine the post and prepare itself, setting its internal properties.</li>
	<li>The bcd constructor will create and prepare the meta_box_data object, which allows plugins to do things like force broadcast to blogs or similar.</li>
	<li>Optionally, the prepare_broadcasting_data action is executed in order to parse the meta box, to see which options and blogs are selected. This is only done when input from the user needs to be parsed. This is not run when using the API.</li>
	<li>The threewp_broadcast_broadcast_post action is executed, which will broadcast the post. The queue plugin overrides this hook and stores the bcd for later.</li>
	<li>The bcd object is returned.</li>
</ul>

If you wish to not use the default broadcasting_data values (perhaps you don't want taxonomies broadcasted), then you should create the bcd object yourself, modify it, and then run action.

<h2>Linking</h2>

Get a linking controller for post 943 on blog 34.

<code>$linking = ThreeWP_Broadcast()->api()->linking( 34, 943 );</code>

Link it child post 32 on blog 4.

<code>$linking->link( 4, 32 );</code>

Unlink it. This will unlink the child(ren) also.

<code>$linking->unlink();</code>

	@see		\\threewp_broadcast\\ThreeWP_Broadcast::api()
	@since		2015-06-15 22:12:21
**/
class api
{
	/**
		@brief		High priority for broadcasts? Or allow the queue to intervene and broadcast them later?
		@since		2020-12-15 16:14:18
	**/
	public $high_priority = true;

	/**
		@brief		API version, as the date.
		@since		2015-06-25 16:41:57
	**/
	public static $version = 20181203;

	/**
		@brief		Broadcast a post to one or more blogs. Link the posts as children.
		@param		int		$post_id		ID of post on this blog to broadcast.
		@param		array	$blogs			Array of blog IDs to which to broadcast.
		@see		\\threewp_broadcast\\broadcasting_data for default values, which is to link, broadcast custom fields and taxonomies, etc.
		@since		2015-06-15 22:50:24
	**/
	public function broadcast_children( $post_id, $blogs )
	{
		$bcd = \threewp_broadcast\broadcasting_data::make( $post_id, $blogs );
		$bcd->high_priority = $this->high_priority;
		apply_filters( 'threewp_broadcast_broadcast_post', $bcd );
		return $bcd;
	}

	/**
		@brief		Delete the specified children.
		@param		int		$post_id		ID of post on this blog to use as the parent.
		@param		array	$child_blogs	Array of blog IDs from which to delete the child posts.
		@since		2018-03-02 18:40:09
	**/
	public function delete_children( $post_id, $child_blogs = [] )
	{
		$blog_id = get_current_blog_id();
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
		if ( $broadcast_data->get_linked_children() )
			foreach( $broadcast_data->get_linked_children() as $child_blog_id => $child_post_id )
			{
				if ( count( $child_blogs ) > 0 )
					if ( ! in_array( $child_blog_id, $child_blogs ) )
						continue;
				switch_to_blog( $child_blog_id );
				wp_delete_post( $child_post_id, true );
				restore_current_blog();
			}
	}

	/**
		@brief		Find and link all unlinked children for this post.
		@since		2019-01-09 16:59:11
	**/
	public function find_unlinked_children( $post_id, $requested_blogs = null )
	{
		$action = new \threewp_broadcast\actions\post_action();
		$action->action = 'find_unlinked';
		$action->blogs = $requested_blogs;
		$action->high_priority = $this->high_priority;
		$action->post_id = $post_id;
		$action->execute();
	}

	/**
		@brief		Set the broadcasting to high priority.
		@since		2020-12-15 16:15:20
	**/
	public function high_priority()
	{
		$this->high_priority = true;
		return $this;
	}

	/**
		@brief		Return an instance of the Linking controller.
		@since		2019-08-06 20:24:52
	**/
	public function linking( $blog_or_post_id, $post_id = false )
	{
		if ( ! $post_id )
		{
			$post_id = $blog_or_post_id;
			$blog_or_post_id = get_current_blog_id();
		}
		return new linking\Controller( $blog_or_post_id, $post_id );
	}

	/**
		@brief		Set the broadcasting to low priority.
		@since		2020-12-15 16:15:20
	**/
	public function low_priority()
	{
		$this->high_priority = false;
		ThreeWP_Broadcast()->debug( 'API: Setting low priority.' );
		return $this;
	}

	/**
		@brief		Restore child posts.
		@details	Optionally, only restore the selected child blogs.
					Doesn't do anything on the parent post.
		@since		2018-12-03 14:36:03
	**/
	public function restore_children( $post_id, $child_blogs = [] )
	{
		$blog_id = get_current_blog_id();
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
		if ( $broadcast_data->get_linked_children() )
			foreach( $broadcast_data->get_linked_children() as $child_blog_id => $child_post_id )
			{
				if ( count( $child_blogs ) > 0 )
					if ( ! in_array( $child_blog_id, $child_blogs ) )
						continue;
				switch_to_blog( $child_blog_id );
				wp_publish_post( $child_post_id );
				restore_current_blog();
			}
	}

	/**
		@brief		Trashes child posts.
		@details	Optionally, only trash the selected child blogs.
					Doesn't do anything on the parent post.
		@since		2018-12-03 14:36:03
	**/
	public function trash_children( $post_id, $child_blogs = [] )
	{
		$blog_id = get_current_blog_id();
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );
		if ( $broadcast_data->get_linked_children() )
			foreach( $broadcast_data->get_linked_children() as $child_blog_id => $child_post_id )
			{
				if ( count( $child_blogs ) > 0 )
					if ( ! in_array( $child_blog_id, $child_blogs ) )
						continue;
				switch_to_blog( $child_blog_id );
				wp_trash_post( $child_post_id );
				restore_current_blog();
			}
	}

	/**
		@brief		Unlink a post from another post. Can be run on a child or a parent.
		@details	Optionally, if this is a parent post, only specify specific blogs to unlink from. If empty, all children will be unlinked.
		@since		2018-12-03 14:07:14
	**/
	public function unlink( $post_id, $child_blogs = [] )
	{
		$blog_id = get_current_blog_id();
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $blog_id, $post_id );

		$parent = $broadcast_data->get_linked_parent();
		if ( $parent !== false )
		{
			// Remove the link to this child from the parent.
			$parent = (object)$parent;
			$parent_broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $parent->blog_id, $parent->post_id );
			$parent_broadcast_data->remove_linked_child( $blog_id );
			ThreeWP_Broadcast()->set_post_broadcast_data( $parent->blog_id, $parent->post_id, $parent_broadcast_data );

			// And now we remove the link to the parent.
			$broadcast_data->remove_linked_parent();
		}

		if ( $broadcast_data->has_linked_children() )
		{
			$linked_children = $broadcast_data->get_linked_children();
			foreach( $linked_children as $child_blog_id => $child_post_id )
			{
				if ( count( $child_blogs ) > 0 )
					if ( ! in_array( $child_blog_id, $child_blogs ) )
						continue;
				$broadcast_data->remove_linked_child( $child_blog_id );
				ThreeWP_Broadcast()->delete_post_broadcast_data( $child_blog_id, $child_post_id );
			}
		}
		ThreeWP_Broadcast()->set_post_broadcast_data( $blog_id, $post_id, $broadcast_data );
	}

	/**
		@brief		Rebroadcasts a parent post to its existing children.
		@details	Optionally adds children on new blogs.
		@param		int		$post_id	The ID of the post to rebroadcast.
		@param		array	$new_blogs	Optional array of blog IDs to which to add new children.
		@since		2015-06-24 18:44:46
	**/
	public function update_children( $post_id, $new_blogs = [] )
	{
		$bcd = \threewp_broadcast\broadcasting_data::make( $post_id );
		$bcd->high_priority = $this->high_priority;
		foreach( $this->_get_post_children( $post_id ) as $blog_id )
			$bcd->broadcast_to( $blog_id );

		foreach( $new_blogs as $blog_id )
			$bcd->broadcast_to( $blog_id );

		apply_filters( 'threewp_broadcast_broadcast_post', $bcd );
		return $bcd;
	}

	/**
		@brief		Convenience method to return an array of blog IDs on which the post has children.
		@since		2015-06-25 16:32:56
	**/
	public function _get_post_children( $post_id )
	{
		$r = [];

		// Retrieve the broadcast_data of this post.
		$broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $post_id );

		if ( ! $broadcast_data->has_linked_children() )
			return $r;

		foreach( $broadcast_data->get_linked_children() as $child_blog_id => $child_post_id )
			$r[] = $child_blog_id;

		return $r;
	}
}
