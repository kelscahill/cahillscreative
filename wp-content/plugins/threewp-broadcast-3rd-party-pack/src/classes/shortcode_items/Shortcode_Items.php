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

	/**
		@brief		Create a parse find action.
		@since		2021-02-05 16:49:19
	**/
	public function new_parse_find_action()
	{
		$r = parent::new_parse_find_action();
		$r->set_prefix_override( 'broadcast_shortcode_items_' );
		return $r;
	}

	/**
		@brief		Create a replace_id action.
		@since		2021-02-05 16:52:10
	**/
	public function new_replace_id_action()
	{
		$r = parent::new_replace_id_action();
		$r->set_prefix_override( 'broadcast_shortcode_items_' );
		return $r;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Shared Finds
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Create the shared finds instance.
		@since		2023-08-02 00:11:55
	**/
	public function create_shared_finds()
	{
		return new Shared_Finds();
	}

	/**
		@brief		Return the key where the shared finds are stored.
		@since		2023-08-02 00:06:22
	**/
	public static function shared_finds_key()
	{
		return 'shortcode_items_shared_finds';
	}
}
