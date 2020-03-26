<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		Replace post IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Posts_Trait
{
	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		if ( $old_id < 1 )
			$new_id = 0;
		else
		{
			$bcd = $broadcasting_data;	// Conv
			$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
		}
		$this->debug( 'Replacing post %s with %s', $old_id, $new_id );
		return $new_id;
	}
}
