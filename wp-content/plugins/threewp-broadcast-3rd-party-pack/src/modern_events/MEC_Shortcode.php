<?php

namespace threewp_broadcast\premium_pack\modern_events;

class MEC_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'MEC';
	}

	/**
		@brief		Add the post type, for manual broadcast.
		@since		2016-07-26 19:07:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'mec_calendars' );
	}
}
