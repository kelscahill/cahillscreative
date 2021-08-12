<?php

namespace threewp_broadcast\premium_pack\intagrate;

/**
	@brief				Adds support for the <a href="https://intagrate.io/">Intagrate</a> plugin.
	@plugin_group		3rd party compatability
	@since				2016-08-10 21:22:10
**/
class Intagrate
	extends \threewp_broadcast\premium_pack\base
{
	public static $instagrate_pro_post_type = 'instagrate_pro';
	public static $instagrate_pro_settings_meta_key = '_instagrate_pro_settings';

	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2014-11-12 19:53:51
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->intagrate_pro_data ) )
			return;

		$data = $bcd->intagrate_pro_data;

		$ig_taxonomy = $data[ 'post_taxonomy' ];

		// First sync the selected taxonomy.
		$this->debug( 'Syncing taxonomy %s', $ig_taxonomy );
		ThreeWP_Broadcast()->sync_terms( $bcd, $ig_taxonomy );

		// And get the new term IDs
		$new_terms = [];
		foreach( $data[ 'post_term' ] as $term_id )
			$new_terms []= $bcd->Terms()->get( $term_id );

		$data[ 'post_term' ] = $new_terms;
		$this->debug( 'Replacing Intagrate data with %s', $data );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( static::$instagrate_pro_settings_meta_key, $data );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2014-11-12 19:54:17
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		// We only care about that post type.
		if ( $bcd->post->post_type != static::$instagrate_pro_post_type )
			return;

		$data = $bcd->custom_fields()->get_single( static::$instagrate_pro_settings_meta_key );
		$data = maybe_unserialize( $data );

		if ( ! is_array( $data ) )
			return $this->debug( 'No intagrate settings.' );

		$this->debug( '%s', $data );

		$ig_post_terms = $data[ 'post_term' ];

		if ( count( $ig_post_terms ) > 0 )
		{
			$ig_taxonomy = $data[ 'post_taxonomy' ];
			$bcd->taxonomies()->also_sync( $ig_taxonomy );
			$bcd->intagrate_pro_data = $data;
		}
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2014-11-12 19:54:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( static::$instagrate_pro_post_type );
	}
}
