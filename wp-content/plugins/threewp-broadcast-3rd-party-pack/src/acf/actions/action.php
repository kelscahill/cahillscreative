<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Base action class for the ACF plugin.
	@since		2015-01-21 22:35:30
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_acf_';
	}
}
