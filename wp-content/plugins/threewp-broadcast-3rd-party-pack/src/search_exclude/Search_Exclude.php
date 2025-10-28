<?php

namespace threewp_broadcast\premium_pack\search_exclude;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/search-exclude/">Search Exclude</a> plugin.
	@plugin_group	3rd party compatability
	@since			2023-01-16 09:36:34

**/
class Search_Exclude
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2023-01-16 09:40:37
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( ! isset( $bcd->search_exclude ) )
			return;

		$excluded = get_option( 'sep_exclude' );
		if ( ! is_array( $excluded ) )
			$excluded = [];
		$new_post_id = $bcd->new_post( 'ID' );

		$currently_excluded = in_array( $new_post_id , $excluded );

		if ( $bcd->search_exclude )
		{
			if ( ! $currently_excluded )
				$excluded [] = $new_post_id;
		}
		else
		{
			if ( $currently_excluded )
			{
				$excluded = array_flip( $excluded );
				unset( $excluded[ $new_post_id ] );
				$excluded = array_flip( $excluded );
			}
		}
		$this->debug( 'Saving new sep_exclude: %s', $excluded );
		update_option( 'sep_exclude', $excluded );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2023-01-16 09:37:31
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( ! class_exists( 'SearchExclude' ) )
			return;

		$excluded = get_option( 'sep_exclude' );
		$bcd->search_exclude = in_array( $bcd->post->ID, $excluded );
		$this->debug( 'Excluded? %s', $bcd->search_exclude );
	}
}
