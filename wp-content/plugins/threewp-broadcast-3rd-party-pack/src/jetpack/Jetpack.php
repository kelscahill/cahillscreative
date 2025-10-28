<?php

namespace threewp_broadcast\premium_pack\jetpack;

/**
	@brief				OBSOLETE: Adds support for <a href="https://wordpress.org/plugins/jetpack/">Automattic's Jetpack plugin</a>.
	@plugin_group		3rd party compatability
	@since				2017-04-02 00:17:22
**/
class Jetpack
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'publicize_should_publicize_published_post' );
	}


	/**
		@brief		publicize_should_publicize_published_post
		@since		2023-11-11 18:00:05
	**/
	public function publicize_should_publicize_published_post( $should_publicize )
	{
		if ( ThreeWP_Broadcast()->is_broadcasting() )
		{
			$this->debug( 'Broadcasting so publicize_should_publicize_published_post is false.' );
			return false;
		}
		return $should_publicize;
	}
}
