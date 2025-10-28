<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Functions that are related to broadcast_data.
	@since		2022-02-08 17:43:14
**/
trait broadcast_data_trait
{
	/**
		@brief		Return the broadcast data of this url.
		@since		2022-02-08 17:43:37
	**/
	public function url_to_broadcast_data( $url )
	{
		$post_id = url_to_postid( $url );
		return ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $post_id );
	}

	/**
		@brief		Convert the broadcast_data to a url on this blog.
		@since		2022-02-08 17:44:26
	**/
	public function broadcast_data_to_url( $broadcast_data, $url = '' )
	{
		if ( ! $broadcast_data )
			return $url;
		$new_post_id = $broadcast_data->get_linked_post_on_this_blog();
		if ( ! $new_post_id )
			return $url;

		$new_url = get_permalink( $new_post_id );

		// Keep the hash / anchor, if any.
		$hash = preg_replace( '/.*#/', '', $url );
		if ( strlen( $hash ) > 0 )
			$new_url .= '#' . $hash;

		return $new_url;

	}
}
