<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Trait to run a find unlinked children post action on a single child blog.
	@since		2017-10-01 23:40:52
**/
trait find_unlinked_children_on_blog
{
	/**
		@brief		The target ID of the newly created blog.
		@since		2017-06-26 17:30:46
	**/
	public $find_unlinked_children_on_blog_child_blog_id = 0;

	/**
		@brief		Run the post action between two blogs.
		@param		$options	Array / object.
						parent_blog_id is the parent blog
						child_blog_id is the child blog.
		@since		2017-10-01 23:42:24
	**/
	public function find_unlinked_children_on_blog( $options )
	{
		$options = (object) $options;

		$this->find_unlinked_children_on_blog_child_blog_id = $options->child_blog_id;

		$this->add_action( 'threewp_broadcast_find_unlinked_posts_blogs', 'find_unlinked_children_on_blog_filter' );

		// Find all posts on this newly created blog.
		global $wpdb;
		$this->debug( 'We are on blog %d', get_current_blog_id() );

		// Find all of the post types we care about.
		$get_post_types = ThreeWP_Broadcast()->new_action( 'get_post_types' );
		$get_post_types->execute();

		switch_to_blog( $options->parent_blog_id );
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` IN ( '%s' )",
			$wpdb->posts,
			implode( "','", $get_post_types->post_types )
		);
		$this->debug( $query );
		$posts = $wpdb->get_results( $query );

		$this->debug( 'Query %s replied with %s', $query, $posts );

		foreach( $posts as $post )
		{
			$bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post->ID );
			if ( $bcd->has_linked_child_on_this_blog() )
			{
				$this->debug( 'Post %s is linked from %s / %s.', $post->ID, $bcd->blog_id, $bcd->post_id );
				switch_to_blog( $bcd->blog_id );
				$action = new \threewp_broadcast\actions\post_action();
				$action->action = 'find_unlinked';
				$action->post_id = $bcd->post_id;
				$action->execute();
				restore_current_blog();
			}
			else
			{
				// Create a new "find unlinked children" post action.
				$action = new \threewp_broadcast\actions\post_action();
				$action->action = 'find_unlinked';
				$action->post_id = $post->ID;
				$action->execute();
			}
		}

		restore_current_blog();

		$this->remove_action( 'threewp_broadcast_find_unlinked_posts_blogs', 'find_unlinked_children_on_blog_filter' );
	}

	/**
		@brief		Remove all blogs to search on except for this one.
		@since		2017-06-26 17:26:55
	**/
	public function find_unlinked_children_on_blog_filter( $action )
	{
		// Convenience
		$the_blog_id = $this->find_unlinked_children_on_blog_child_blog_id;

		// Remove all blogs that are not The One.
		foreach( $action->blogs as $blog_id => $blog )
			if ( $blog_id != $the_blog_id )
				$action->blogs->forget( $blog_id );

		// Since we have been requested to use this filter on The One, ensure that the blog exists in the list.
		if ( $action->blogs->has( $the_blog_id ) )
			return;

		$blog = get_blog_details( $the_blog_id, true );
		$blog = \threewp_broadcast\broadcast_data\blog::make( $blog );
		$action->blogs->set( $the_blog_id, $blog );
	}
}
