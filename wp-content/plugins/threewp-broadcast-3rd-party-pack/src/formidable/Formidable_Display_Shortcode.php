<?php

namespace threewp_broadcast\premium_pack\formidable;

/**
	@brief		Handle the form display / view shortcode.
	@since		2020-08-17 17:19:07
**/
class Formidable_Display_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'display-frm-data';
	}
}
