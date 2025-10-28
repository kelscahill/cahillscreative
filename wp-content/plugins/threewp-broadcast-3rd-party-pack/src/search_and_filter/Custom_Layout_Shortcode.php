<?php

namespace threewp_broadcast\premium_pack\search_and_filter;

/**
	@brief		Handle the [custom-layout] shortcode.
	@since		2022-12-08 21:16:04
**/
class Custom_Layout_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'custom-layout';
	}
}
