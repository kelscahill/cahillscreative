<?php

namespace threewp_broadcast\premium_pack\tao_schedule_update;

/**
	@brief				Adds support for <a href="https://wordpress.org/plugins/tao-schedule-update/">TAO Schedule Update</a> content update scheduler.
	@plugin_group		3rd party compatability
	@since				2016-06-01 15:44:19
**/
class Tao_Schedule_Update
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_allowed_post_statuses' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- CALLBACKS
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Add the special publish post status to allow Broadcast to broadcast update posts.
		@since		2016-06-01 16:26:08
	**/
	public function threewp_broadcast_allowed_post_statuses( $statuses )
	{
		$statuses []= 'tao_sc_publish';
		return $statuses;
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-06-01 15:50:40
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->tao_schedule_update ) )
			return;

		$cf = $bcd->custom_fields()
			->child_fields();

		$tsu = $bcd->tao_schedule_update;

		// Do we get to restore the original post reference?
		$original_post = $tsu->get( 'original_post' );
		if ( $original_post )
		{
			$linked_post = $original_post->get_linked_post_on_this_blog();
			if ( $linked_post )
			{
				$key = 'tao_sc_publish_original';
				$cf->update_meta( $key, $linked_post );
				$this->debug( 'Updating original post reference %s to %s', $key, $linked_post );

			}
			else
				$this->debug( 'No original post linked on this blog.' );
		}

		$timestamp = $tsu->get( 'tao_publish_post' );
		$schedule_key = 'tao_publish_post';
		if ( $timestamp )
		{
			$this->debug( 'Setting the %s schedule to %s.', $schedule_key, $timestamp);
			wp_schedule_single_event( $timestamp, $schedule_key, [ 'ID' => $bcd->new_post( 'ID' ) ] );
		}
		else
		{
			$this->debug( 'Clearing the %s schedule.', $schedule_key );
			wp_clear_scheduled_hook( $schedule_key, [ 'ID' => $bcd->new_post( 'ID' ) ] );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-06-01 15:50:15
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return $this->debug( 'TAO Schedule Update not active.' );

		$bcd = $action->broadcasting_data;

		// Is this an updated post?
		$tsu = ThreeWP_Broadcast()->collection();

		// Original post reference?

		// I'd love to use their $TAO_PUBLISH_STATUS property but it's protected. >:(
		// THAT'S why everything I write is public.
		$key = 'tao_sc_publish_original';
		$original_post_id = $bcd->custom_fields()->get_single( $key );
		if ( $original_post_id > 0 )
		{
			$original_post = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $original_post_id );
			$tsu->set( 'original_post', $original_post );
			$this->debug( 'Saving original post broadcast data for %s: %s', $original_post_id, $original_post );
		}
		else
			$this->debug( 'No original post reference.' );

		// Is there an update scheduled?
		$timestamp = wp_next_scheduled( 'tao_publish_post', [ 'ID' => $bcd->post->ID ] );
		if ( $timestamp )
		{
			$tsu->set( 'tao_publish_post', $timestamp );
			$this->debug( 'Saving publish post schedule: %s', $timestamp );
		}
		else
			$this->debug( 'Nothing scheduled.' );

		// Is there anything to save?
		if ( count( $tsu ) < 1 )
			return $this->debug( 'Nothing to save.' );

		$bcd->tao_schedule_update = $tsu;
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Is TAO installed?
		@since		2016-06-01 15:49:13
	**/
	public function requirement_fulfilled()
	{
		return class_exists( 'TAO_ScheduleUpdate' );
	}
}
