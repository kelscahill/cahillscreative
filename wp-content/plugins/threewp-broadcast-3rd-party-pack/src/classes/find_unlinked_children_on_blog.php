<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Trait to run a find unlinked children post action on a single child blog.
	@since		2017-10-01 23:40:52
**/
trait find_unlinked_children_on_blog
{
	/**
		@brief		Run the post action between two blogs.
		@param		$options	Array / object.
						parent_blog_id is the parent blog
						child_blog_id is the child blog.
		@since		2017-10-01 23:42:24
	**/
	public function find_unlinked_children_on_blog( $options )
	{
		$options = array_merge( [
			'high_priority' => true,
			'parent_blog_id' => false,
		], $options );
		$options = (object) $options;

		$this->debug( 'We are on blog %d. Priority: %d', get_current_blog_id(), $options->high_priority );

		global $wpdb;

		// Find all of the post types we care about.
		$get_post_types = ThreeWP_Broadcast()->new_action( 'get_post_types' );
		$get_post_types->execute();

		if ( $options->parent_blog_id )
			switch_to_blog( $options->parent_blog_id );

		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` IN ( '%s' )",
			$wpdb->posts,
			implode( "','", $get_post_types->post_types )
		);
		$this->debug( $query );
		$post_ids = $wpdb->get_col( $query );

		$this->debug( 'Query %s replied with %s', $query, $post_ids );

		foreach( $post_ids as $post_id )
		{
			$action = new \threewp_broadcast\actions\post_action();
			$action->action = 'find_unlinked';
			$action->blogs = [ $options->child_blog_id ];
			$action->high_priority = $options->high_priority;
			$action->post_id = $post_id;
			$action->execute();
		}

		if ( $options->parent_blog_id )
			restore_current_blog();
	}
}
