<?php

namespace threewp_broadcast\premium_pack\elementor;

/**
	@brief		Handle template shortcodes.
	@since		2017-08-08 22:36:30
**/
class Elementor_Template_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'elementor-template';
	}
}
