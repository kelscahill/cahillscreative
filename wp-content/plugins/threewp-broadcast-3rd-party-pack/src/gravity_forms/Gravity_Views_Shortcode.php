<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief		Handle the Gravity Views plugin.
	@since		2020-07-06 22:01:50
**/
class Gravity_Views_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'gravityview';
	}
}
