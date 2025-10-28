<?php

namespace threewp_broadcast\premium_pack\classes\generic_items\actions;

/**
	@brief		Replace the ID / IDs of the item.
	@since		2018-11-14 15:00:20
**/
class replace_id
	extends \threewp_broadcast\actions\action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2018-11-14 14:59:56
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The find array.
		@details	The $find->original string should be replaced.
		@since		2018-11-14 15:00:07
	**/
	public $find;

	/**
		@brief		IN/OUT: The updated item.
		@since		2018-11-14 15:44:29
	**/
	public $item;

	public function get_prefix()
	{
		return 'broadcast_generic_items_';
	}
}
