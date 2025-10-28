<?php

namespace threewp_broadcast\premium_pack\dear_flipbook;

/**
	* @brief		Handle the shortcode.
	* @since		2025-05-14 22:35:23
**/
class Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-06-20 22:10:34
	**/
	public function get_shortcode_name()
	{
		return 'dflip';
	}
}
