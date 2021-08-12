<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Sync a taxonomy from one blog to others.
	@since		2018-05-23 21:03:52
**/
trait sync_taxonomy_trait
{
	/**
		@brief		Get the first best post type for this taxonomy.
		@since		2018-05-23 21:09:28
	**/
	public function get_taxonomy_post_type( $taxonomy )
	{
		// Find the post type for this taxonomy.
		global $wp_taxonomies;
		$the_taxonomy = $wp_taxonomies[ $taxonomy ];
		$post_type = reset( $the_taxonomy->object_type );
		return $post_type;
	}

	/**
		@brief		Convenience method to sync a taxonomy from the parent blog in the bcd.
		@return		A broadcasting_data object used during syncing.
		@since		2018-05-23 20:33:39
	**/
	public function sync_taxonomy_from_parent_blog( $bcd, $taxonomy )
	{
		switch_to_blog( $bcd->parent_blog_id );
		$new_bcd = $this->sync_taxonomy_to_blogs( $taxonomy, [ $bcd->current_child_blog_id ] );
		restore_current_blog();
	}

	/**
		@brief		Sync this taxonomy to other blogs.
		@return		A broadcasting_data object used during syncing.
		@since		2018-05-23 21:06:21
	**/
	public function sync_taxonomy_to_blogs( $taxonomy, $blogs, $post_type = null )
	{
		if ( $post_type === null )
			$post_type = $this->get_taxonomy_post_type( $taxonomy );

		$this->debug( 'Syncing taxonomy %s for post type %s.', $taxonomy, $post_type );

		$post = (object)[
			'ID' => 0,
			'post_type' => $post_type,
			'post_status' => 'publish',
		];

		$bcd = new \threewp_broadcast\broadcasting_data( [
			'parent_post_id' => -1,
			'post' => $post,
		] );
		$bcd->add_new_taxonomies = true;
		unset( $bcd->post->ID );		// This is so that collect_post_type_taxonomies returns ALL the terms, not just those from the non-existent post.
		ThreeWP_Broadcast()->collect_post_type_taxonomies( $bcd );

		foreach( $blogs as $blog_id )
		{
			$this->debug( 'Switching to blog %s', $blog_id );
			switch_to_blog( $blog_id );
			ThreeWP_Broadcast()->sync_terms( $bcd, $taxonomy );
			$this->debug( 'Done syncing.' );
			restore_current_blog();
		}

		return $bcd;
	}
}
