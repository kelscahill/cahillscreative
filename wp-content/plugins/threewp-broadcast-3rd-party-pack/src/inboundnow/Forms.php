<?php

namespace threewp_broadcast\premium_pack\inboundnow;

/**
	@brief		Handle inbound forms shortcodes.
	@since		2018-03-15 20:14:01
**/
class Forms
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	public function get_shortcode_name()
	{
		return 'inbound_forms';
	}
}
