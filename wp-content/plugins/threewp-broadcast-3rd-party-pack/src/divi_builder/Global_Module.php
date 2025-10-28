<?php

namespace threewp_broadcast\premium_pack\divi_builder;

class Global_Module
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'global_module';
	}

	/**
		@brief		Add the post type, for manual broadcast.
		@since		2016-07-26 19:07:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'et_pb_layout' );
	}
}
