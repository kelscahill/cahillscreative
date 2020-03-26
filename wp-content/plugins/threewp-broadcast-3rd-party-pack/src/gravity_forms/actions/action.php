<?php

namespace threewp_broadcast\premium_pack\gravity_forms\actions;

/**
	@brief		Base action class for GF.
	@since		2017-11-20 20:07:58
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_gf_';
	}
}
