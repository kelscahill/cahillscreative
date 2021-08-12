<?php

namespace threewp_broadcast\premium_pack\calendarize_it;

/**
	@brief				Adds support for <a href="http://codecanyon.net/item/calendarize-it-for-wordpress/2568439">Calendarize It!</a> events.
	@plugin_group		3rd party compatability
	@since				2016-06-15 20:56:53
**/
class Calendarize_It
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		All of the custom fields that contain images.
		@since		2016-06-15 21:03:11
	**/
	public static $image_custom_fields = [
		'rhc_dbox_image',
		'rhc_month_image',
		'rhc_tooltip_image',
		'rhc_top_image',
	];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-06-15 20:56:03
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( !function_exists( 'rhc_handle_delete_events_cache' ) )
			return ;

		$new_post_id = $bcd->new_post( 'ID' );
		$key = 'postinfo_boxes';
		$data = $bcd->custom_fields()->get_single( $key );
		$data = maybe_unserialize( $data );
		foreach( $data as $box => $subdata )
		{
			foreach( $subdata->data as $index => $box_data )
				if ( isset( $box_data->post_ID ) )
				{
					$box_data->post_ID = $new_post_id;
				}
		}
		$bcd->custom_fields()->child_fields()->update_meta( $key, $data );

		// Clear the cache to make the blog display the events.
		$this->debug( 'Clearing events cache.' );
		apply_filters( 'generate_calendarize_meta', $bcd->new_post( 'ID' ), [] );

		if ( ! isset( $bcd->calendarize_it ) )
			return;

		$cit = $bcd->calendarize_it;

		// Restore the images.
		foreach( static::$image_custom_fields as $key )
		{
			$old_image_id = $cit->collection( 'images' )->get( $key );
			if ( ! $old_image_id )
				continue;
			$new_image_id = $bcd->copied_attachments()->get( $old_image_id );
			$this->debug( 'Replacing old image %s in %s with new image %s.',
				$old_image_id,
				$key,
				$new_image_id
			);
			$bcd->custom_fields()->child_fields()->update_meta( $key, $new_image_id );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-06-15 21:02:39
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'events' )
			return;

		$bcd->calendarize_it = ThreeWP_Broadcast()->collection();
		$cit = $bcd->calendarize_it;

		// Check and save any images in the fields.
		foreach( static::$image_custom_fields as $key )
		{
			$image_id = $bcd->custom_fields()->get_single( $key );
			$this->debug( '%s contains the ID %s', $key, $image_id );
			if ( $image_id > 0 )
			{
				if ( $bcd->try_add_attachment( $image_id ) )
					$cit->collection( 'images' )
						->set( $key, $image_id );
			}
		}
	}

	/**
		@brief		Add our post type.
		@since		2019-02-04 23:06:51
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'events' );
	}
}
