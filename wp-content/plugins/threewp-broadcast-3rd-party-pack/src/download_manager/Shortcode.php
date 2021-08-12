<?php

namespace threewp_broadcast\premium_pack\download_manager;

/**
	@brief		Handle the DM shortcode.
	@since		2019-06-10 21:12:56
**/
class Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'wpdm_package';
	}
}
