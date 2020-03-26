<?php

namespace threewp_broadcast\premium_pack\classes\gutenberg_items\actions;

/**
	@brief		The parse (actually preparse) when a generic item is found.
	@details	Should be called preparse, but it's too late for that.
	@since		2018-11-14 15:00:20
**/
class parse_find
	extends \threewp_broadcast\premium_pack\classes\generic_items\actions\parse_find
{
	public function get_prefix()
	{
		return 'broadcast_gutenberg_items_';
	}
}
