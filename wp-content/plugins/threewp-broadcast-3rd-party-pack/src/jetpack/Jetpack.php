<?php

namespace threewp_broadcast\premium_pack\jetpack;

/**
	@brief				Adds support for <a href="https://wordpress.org/plugins/jetpack/">Automattic's Jetpack plugin</a>.
	@plugin_group		3rd party compatability
	@since				2017-04-02 00:17:22
**/
class Jetpack
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		There is specific hook that needs to be disabled in order for Publicize to publish the parent post.
		@since		2017-04-02 00:19:10
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		global $wp_filter;
		$hook = 'transition_post_status';
		$filters = $wp_filter[ $hook ];
		if ( is_object( $filters ) )
			$filters = $filters->callbacks;
		else
			return;

		foreach( $filters as $priority => $callbacks )
		{
			foreach( $callbacks as $callback )
			{
				// We're looking for a class.
				$function = $callback[ 'function' ];
				if ( ! is_array( $function ) )
					continue;
				$callback_class = $function[ 0 ];
				if ( ! is_object( $callback_class ) )
					continue;
				// We are looking for Jetpack.
				if ( get_class( $callback_class ) != 'Jetpack_Sync_Module_Posts' )
					continue;
				$function_name = $function[ 1 ];
				// And this specific function.
				if ( $function_name != 'save_published' )
					continue;

				// Found it. Remove it.
				$this->debug( 'Removing transition_post_status action for the post sync module.' );
				remove_action( $hook, $callback[ 'function' ], $priority, $callback[ 'accepted_args' ] );
			}
		}
	}
}
