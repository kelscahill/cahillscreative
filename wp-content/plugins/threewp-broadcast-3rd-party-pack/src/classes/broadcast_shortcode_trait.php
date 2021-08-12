<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Convenience method to broadcast a shortcode.
	@details	Uses a temporary post.
	@since		2019-05-16 19:30:05
**/
trait broadcast_shortcode_trait
{
	/**
		@brief		Broadcast a shortcode to one or more blogs.
		@since		2019-05-16 19:30:30
	**/
	public function broadcast_shortcode( $shortcode, $blogs )
	{
		// Create a post on this blog containing the shortcode.
		$post_id = wp_insert_post( [
			'post_content' => $shortcode,
			'post_title' => microtime(),
		] );

		ThreeWP_Broadcast()->api()->broadcast_children( $post_id, $blogs );

		// Now that the post has been broadcasted, we're done with it.
		wp_delete_post( $post_id, true );
	}
}
