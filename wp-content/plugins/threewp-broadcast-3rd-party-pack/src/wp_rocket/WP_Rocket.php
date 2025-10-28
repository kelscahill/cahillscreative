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
		$this->add_action( 'threewp_broadcast_trash_untrash_delete_post' );
		add_action( 'broadcast_shortcodes_saved', function()
		{
			rocket_clean_domain();
		} );
	}

	/**
		@brief		Clears the rocket cache of this post ID.
		@since		2022-12-05 17:32:42
	**/
	public static function clear_rocket_cache( $post_id )
	{
		if ( ! function_exists( 'rocket_clean_domain' ) )
			return;
		WP_Rocket::instance()->debug( 'Clearing cache for post %s', $post_id );
		rocket_clean_minify();
		rocket_clean_post( $post_id );
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

		static::clear_rocket_cache( $post_id );
	}

	/**
		@brief		Trash the cache of each child site when any action is run on the parent.
		@since		2022-12-08 21:25:57
	**/
	public function threewp_broadcast_trash_untrash_delete_post( $action )
	{
		$this->debug( 'Clearing post %s cache on site %s',
			$action->child_post_id,
			$action->child_blog_id,
			);
		switch_to_blog( $action->child_blog_id );
		static::clear_rocket_cache( $action->child_post_id );
		restore_current_blog();
	}
}
