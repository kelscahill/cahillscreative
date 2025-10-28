<?php

namespace threewp_broadcast\premium_pack\litespeed_cache;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/litespeed-cache/">LiteSpeed Cache</a> plugin.
	@plugin_group	3rd party compatability
	@since			2021-01-22 20:07:50
**/
class LiteSpeed_Cache
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\cache_purge_trait;

	public function _construct()
	{
		$this->init_cache_purge_trait();
	}

	/**
		@brief		Purge the cache.
		@since		2021-01-22 20:56:39
	**/
	public function purge_cache()
	{
		$this->debug( 'Clearing the cache...' );
		$key = 'LSWCP_EMPTYCACHE';
		if ( ! defined( $key ) )
			define( $key, true );
		do_action( 'litespeed_purge_all' );
	}
}
