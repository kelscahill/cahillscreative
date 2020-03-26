<?php

namespace threewp_broadcast\premium_pack\onesignal;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/onesignal-free-web-push-notifications/">OneSignal – Free Web Push Notifications</a> plugin.
	@plugin_group	3rd party compatability
	@since			2018-02-28 21:29:54
**/
class OneSignal
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\wordpress_actions_finder_trait;

	/**
		@brief		Constructor.
		@since		2018-02-28 21:31:15
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2018-02-28 21:31:25
	**/
	public function threewp_broadcast_broadcasting_started()
	{
		// Find and remove OneSignal's save post method.
		$this->find_and_remove_action( 'save_post', [ 'OneSignal_Admin', 'on_save_post' ] );
	}
}
