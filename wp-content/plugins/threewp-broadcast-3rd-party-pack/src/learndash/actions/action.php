<?php

namespace threewp_broadcast\premium_pack\learndash\actions;

/**
	@brief		Base action class.
	@since		2020-12-01 21:56:35
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_learndash_';
	}
}
