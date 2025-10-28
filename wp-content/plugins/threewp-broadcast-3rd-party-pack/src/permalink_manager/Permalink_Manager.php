<?php

namespace threewp_broadcast\premium_pack\permalink_manager;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/permalink-manager/">Permalink Manager</a> plugin.
	@plugin_group	3rd party compatability
	@since			2021-02-05 18:31:59
**/
class Permalink_Manager
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Options key.
		@since		2021-02-05 18:31:14
	**/
	public static $options_key = 'permalink-manager-uris';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-10-18 11:35:18
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->permalink_manager ) )
			return;

		$permalinks = get_option( static::$options_key );
		if ( ! is_array( $permalinks ) )
			$permalinks = [];

		$permalink = $bcd->permalink_manager->get( 'custom_permalink' );
		$permalinks[ $bcd->new_post( 'ID' ) ] = $permalink;

		update_option( static::$options_key, $permalinks );
		$this->debug( 'Restored custom permalink: %s', $permalink );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-10-18 11:35:34
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! $this->has_requirement() )
			return;

		// Is there a permalink stored?
		$permalinks = get_option( static::$options_key );
		if ( ! is_array( $permalinks ) )
			return;

		if ( ! isset( $permalinks[ $bcd->post->ID ] ) )
			return;

		$bcd->permalink_manager = ThreeWP_Broadcast()->collection();
		$permalink = $permalinks[ $bcd->post->ID ];
		$bcd->permalink_manager->set( 'custom_permalink', $permalink );
		$this->debug( 'Saved custom permalink: %s', $permalink );
	}

	/**
		@brief		Is the requirement installed?
		@since		2021-02-05 18:30:36
	**/
	public function has_requirement()
	{
		return class_exists( 'Permalink_Manager_Class' );
	}
}
