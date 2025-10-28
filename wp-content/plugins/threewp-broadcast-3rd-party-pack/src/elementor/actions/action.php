<?php

namespace threewp_broadcast\premium_pack\elementor\actions;

/**
	* @brief		Base action class for the Elementor add-on.
	* @since		2025-05-09 19:42:53
**/
class action
	extends \threewp_broadcast\actions\action
{
	public function get_prefix()
	{
		return 'broadcast_elementor_';
	}
}
