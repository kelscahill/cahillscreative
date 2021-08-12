<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_items\actions;

/**
	@brief		Replace the ID / IDs of the item.
	@since		2018-11-14 15:00:20
**/
class replace_id
	extends \threewp_broadcast\premium_pack\classes\generic_items\actions\replace_id
{
	public function get_prefix()
	{
		return 'broadcast_shortcode_items_';
	}
}
