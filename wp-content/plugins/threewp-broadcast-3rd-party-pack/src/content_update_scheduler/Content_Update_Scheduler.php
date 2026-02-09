<?php

namespace threewp_broadcast\premium_pack\content_update_scheduler;

/**
	@brief			Adds support for <a href="https://wordpress.org/plugins/content-update-scheduler/">Content Update Scheduler</a> plugin.
	@plugin_group	3rd party compatability
	@since			2026-01-12 18:04:32
**/
class Content_Update_Scheduler
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		add_action( 'ContentUpdateScheduler\after_publish_post', function( \WP_Post $update, \WP_Post $original )
		{
			ThreeWP_Broadcast()->api()->low_priority()->update_children( $original->ID );
		}, 10, 2 );
	}
}
