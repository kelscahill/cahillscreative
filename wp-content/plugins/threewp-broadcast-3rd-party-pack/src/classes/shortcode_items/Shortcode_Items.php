<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_items;

use \Exception;

/**
	@brief		Generic handler for items in shortcodes.
	@since		2016-07-14 12:29:31
**/
abstract class Shortcode_Items
	extends \threewp_broadcast\premium_pack\classes\generic_items\Generic_Items
{
	/**
		@brief		Get the data for the type of generic handler.
		@since		2019-06-19 22:02:02
	**/
	public function get_generic_data()
	{
		return (object) [
			'singular' => 'shortcode',
			'plural' => 'shortcodes',
			'Singular' => 'Shortcode',
			'Plural' => 'Shortcodes',
			'option_name' => 'shortcodes',
		];
	}
}
