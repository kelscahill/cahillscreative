<?php

namespace threewp_broadcast\premium_pack\global_blocks_for_cornerstone;

/**
	@brief		Support for CS v3 and above.
	@since		2019-04-09 20:46:44
**/
class Global_Blocks_For_Cornerstone_3
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-06-20 22:10:34
	**/
	public function get_shortcode_name()
	{
		return 'cs_gb';
	}
}
