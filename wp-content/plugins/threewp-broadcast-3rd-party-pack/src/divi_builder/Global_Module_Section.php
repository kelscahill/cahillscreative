<?php

namespace threewp_broadcast\premium_pack\divi_builder;

class Global_Module_Section
	extends Global_Module
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'et_pb_section';
	}
}
