<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Add post ID handling methods.
	@since		2015-10-26 20:46:11
**/
trait post_id_trait
{
	/**
		@brief		IN: The "post ID" into which to store the new ACF value.
		@since		2015-10-26 20:37:03
	**/
	public $post_id;

	/**
		@brief		Return the post ID.
		@since		2015-10-26 21:16:55
	**/
	public function get_post_id()
	{
		return $this->post_id;
	}

	/**
		@brief		Set the post ID for which the ACF data is stored.
		@since		2015-10-26 20:37:31
	**/
	public function set_post_id( $post_id )
	{
		$this->post_id = $post_id;
	}
}
