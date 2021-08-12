<?php

namespace threewp_broadcast\premium_pack\woocommerce;

/**
	@brief		Handle the add_to_cart shortcode.
	@since		2020-01-28 21:44:16
**/
class Add_To_Cart_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'add_to_cart';
	}
}
