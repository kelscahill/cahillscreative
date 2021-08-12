<?php
namespace threewp_broadcast\premium_pack\wp_rocket;

/**
	@brief				Adds support for the <a href="https://wp-rocket.me/">WP Rocket</a> plugin.
	@plugin_group		3rd party compatability
**/
class WP_Rocket
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	/**
		@brief		Clear the cache.
		@since		2020-05-24 14:10:12
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! function_exists( 'rocket_clean_post' ) )
			return;

		$bcd = $action->broadcasting_data;
		$post = $bcd->new_post;
		$post_id = $bcd->new_post( 'ID' );

		$this->debug( 'WP Rocket: Clearing cache for post %s.', $post_id );

		// We have to do this manually do to the lovely $done variable in rocket_clean_post().

		$purge_urls = rocket_get_purge_urls( $post_id, $post );
		$purge_urls = apply_filters( 'rocket_post_purge_urls', $purge_urls, $post );
		rocket_clean_files( $purge_urls );
		rocket_clean_home_feeds();
	}
}
