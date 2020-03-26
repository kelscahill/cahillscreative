<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_items;

use \Exception;

/**
	@brief		Generic handler for items in shortcodes.
	@since		2016-07-14 12:29:31
**/
abstract class Items
	extends \threewp_broadcast\premium_pack\classes\generic_items\Generic_Items
{
	/**
		@brief		Return the name of the option that stores the collection.
		@since		2019-06-19 17:23:29
	**/
	public function get_option_name()
	{
		return 'shortcodes';
	}
}
