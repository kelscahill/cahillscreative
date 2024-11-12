<?php


namespace threewp_broadcast\actions;

/**
	@brief		Prepare the data in the meta box.
**/
class prepare_meta_box
	extends action
{
	/**
		@brief		IN/OUT: The meta box data object, ready to be modified.
		@var		$meta_box_data
		@since		20131010
	**/
	public $meta_box_data;

	/**
		@brief		Is this a child post?
		@since		2022-06-09 21:31:56
	**/
	public function is_child_post()
	{
		return ( $this->meta_box_data->broadcast_data->get_linked_parent() !== false );
	}

	/**
		@brief		Convenience method to return whether this post is a parent.
		@since		2017-09-19 08:46:40
	**/
	public function is_parent_post()
	{
		return ( count( $this->meta_box_data->broadcast_data->get_linked_children() ) > 0 );
	}

	/**
		@brief		Is this post completely unlinked?
		@since		2022-06-09 21:32:23
	**/
	public function is_unlinked()
	{
		return ( ! $this->is_child_post() ) && ( ! $this->is_parent_post() );
	}
}
