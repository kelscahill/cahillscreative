<?php

namespace threewp_broadcast\premium_pack\wpforms;

/**
	@brief		Handle the shortcode found in post content.
	@since		2019-07-12 09:07:49
**/
class WPForms_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'wpforms';
	}
}
