<?php

namespace threewp_broadcast\premium_pack\divi_builder;

use Exception;

/**
	* @brief		Handle the blog shortcode with the terms in categories.
	* @since		2024-11-02 07:01:06
**/
class et_pb_blog
	extends \threewp_broadcast\premium_pack\classes\shortcode_preparsers\Term
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'et_pb_blog';
	}

	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'include_categories';
	}
}
