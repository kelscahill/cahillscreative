<?php

namespace threewp_broadcast\premium_pack\wpforms;

/**
	@brief		Handle the wp_forms_selector shortcode found in the Divi builder.
	@since		2022-03-09 21:43:21
**/
class WPForms_Selector_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'form_id';
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'wpforms_selector';
	}
}
