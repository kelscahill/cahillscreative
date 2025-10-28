<?php

namespace threewp_broadcast\premium_pack\woocommerce\actions;

/**
	@brief		Base class.
	@since		2021-05-05 22:30:59
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_woocommerce_';
	}
}
