<?php

namespace threewp_broadcast\premium_pack\the_events_calendar;

use \threewp_broadcast\actions;
use \threewp_broadcast\broadcasting_data;

/**
	@brief				Adds support for Modern Tribe's <a href="https://wordpress.org/plugins/the-events-calendar/">The Events Calendar</a> plugin with venues and organisers.
	@plugin_group		3rd party compatability
	@since				2014-10-24 21:12:54
**/
class The_Events_Calendar
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_post_action' );
		$this->add_action( 'tribe_events_update_meta', 100 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->the_events_calendar ) )
			return;

		$this->restore_custom_fields( $bcd );
		$this->restore_organiser( $bcd );
		$this->restore_recurring( $bcd );
		$this->restore_venue( $bcd );
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ 'tribe_events' ] = 'tribe_events';
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->requirement_fulfilled() )
		{
			$this->debug( 'The Events Calendar was not detected.' );
			return;
		}

		$bcd = $action->broadcasting_data;

		if ( ! $this->post_is_event( $bcd->post ) )
		{
			$this->debug( 'This post is not an event.' );
			return;
		}

		$bcd->the_events_calendar = (object)[];;

		$this->save_custom_fields( $bcd );
		$this->save_organiser( $bcd );
		$this->save_recurring( $bcd );
		$this->save_venue( $bcd );
	}

	/**
		@brief		Apply this post action to all recurring events on this blog.
		@since		2016-06-29 19:17:16
	**/
	public function threewp_broadcast_post_action( $action )
	{
		$define = 'BROADCAST_THE_EVENTS_CALENDAR_IGNORE_RECURRING_POST_ACTIONS';
		if ( defined( $define ) )
			return $this->debug( '%s is set, not applying post action to recurring events.', $define );

		$post = get_post( $action->post_id );

		// Is this post an event?
		if ( $post->post_type != 'tribe_events' )
			return $this->debug( 'Post %s is not a tribe event.', $action->post_id );

		// Get all recurring events.
		$recurring_events = get_posts( [
			'postsperpage' => -1,
			'post_status' => $post->post_status,
			'post_type' => $post->post_type,
			'post_parent' => $action->post_id,
		] );

		$this->debug( '%s recurring events found for this event: %s', count( $recurring_events ), $action->post_id );

		foreach( $recurring_events as $recurring_event )
		{
			$post_action = new actions\post_action;
			$post_action->action = $action->action;
			$post_action->post_id = $recurring_event->ID;
			if ( isset( $action->child_blog_id ) )
				$post_action->child_blog_id = $action->child_blog_id;
			$this->debug( 'Applying action %s for recurring event %s', $action->action, $recurring_event->ID );
			$post_action->execute();
		}
	}

	public function tribe_events_update_meta( $post_id )
	{
		ThreeWP_Broadcast()->save_post( $post_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save the custom fields, if any.
		@since		2015-09-16 17:38:11
	**/
	public function save_custom_fields( $bcd )
	{
		$this->debug( 'About to save custom fields.' );
		$custom_fields_to_save = ThreeWP_Broadcast()->collection();
		$tec = $bcd->the_events_calendar;

		foreach( $bcd->custom_fields->original as $key => $value )
		{
			if ( strpos( $key, '_ecp_custom_' ) === false )
				continue;
			$id = str_replace( '_ecp_custom_', '', $key );
			$custom_fields_to_save->set( $id, $id );
		}

		if ( count( $custom_fields_to_save ) < 1 )
			return $this->debug( 'No custom fields found to save.' );

		$this->debug( 'Found some custom fields: %s', $custom_fields_to_save );

		// Extract and save the custom field data in the TEC options.
		$tec->custom_fields = $custom_fields_to_save;
		$tec->options = get_option( 'tribe_events_calendar_options', true );
	}

	/**
		@brief		Save the organiser.
		@since		2015-09-16 17:36:08
	**/
	public function save_organiser( $bcd )
	{
		$tec = $bcd->the_events_calendar;

		// Does this event have an organizer?
		foreach( $bcd->custom_fields->original as $key => $value )
		{
			if ( $key != '_EventOrganizerID' )
				continue;

			$value = reset( $value );
			$organiser_id = $value;
			$this->debug( 'The organiser ID for this event is %s.', $organiser_id );

			$organiser_post = false;
			if ( $organiser_id > 0 )
				$organiser_post = get_post( $organiser_id );
			if ( ! $organiser_post )
				$this->debug( 'No organiser post.' );
			else
			{
				$tec->organiser = (object)[];
				$tec->organiser->id = $organiser_id;
				$tec->organiser->post = $organiser_post;
				$tec->organiser->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $organiser_id );
			}
		}
	}

	/**
		@brief		Save all of the recurrent events.
		@since		2016-04-04 17:01:36
	**/
	public function save_recurring( $bcd )
	{
		// Find all sub events.
		$tec = $bcd->the_events_calendar;
		$tec->recurring_events = get_posts( [
			'post_parent' => $bcd->post->ID,
			'post_type' => 'tribe_events',
			'posts_per_page' => -1,
		] );
	}

	/**
		@brief		Save the venue data.
		@since		2015-09-16 17:34:58
	**/
	public function save_venue( $bcd )
	{
		$tec = $bcd->the_events_calendar;

		// Does this event have a venue?
		foreach( $bcd->custom_fields->original as $key => $value )
		{
			if ( $key != '_EventVenueID' )
				continue;

			$value = reset( $value );
			$venue_id = $value;
			$venue_post = false;

			$this->debug( 'The venue ID for this event is %s.', $venue_id );

			if ( $venue_id > 0 )
				$venue_post = get_post( $venue_id );
			if ( ! $venue_post )
				$this->debug( 'Invalid venue post.' );
			else
			{
				$tec->venue = (object)[];
				$tec->venue->id = $venue_id;
				$tec->venue->post = $venue_post;
				$tec->venue->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $venue_id );
			}
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Restore the custom fields.
		@since		2015-09-16 17:39:00
	**/
	public function restore_custom_fields( $bcd )
	{
		$tec = $bcd->the_events_calendar;

		if ( ! isset( $tec->custom_fields ) )
			return;

		// Update or append the options from the parent blog.
		$equivalents = [];
		$options_modified = false;
		$options = get_option( 'tribe_events_calendar_options', true );

		$this->debug( 'TEC options on this blog: %s', $options );

		if ( ! isset( $options[ 'custom-fields' ] ) )
		{
			$options[ 'custom-fields' ] = [];
			$options[ 'custom-fields-max-index' ] = 0;
		}

		// Go through all of the used custom fields
		foreach( $tec->custom_fields as $id )
		{
			$found = false;
			$original_option = $tec->options[ 'custom-fields' ][ $id ];
			// And look for the equivalent on this blog.
			foreach( $options[ 'custom-fields' ] as $field_id => $custom_field )
			{
				// The label and type must match.
				if ( $custom_field[ 'label' ] != $original_option[ 'label' ] )
					continue;
				if ( $custom_field[ 'type' ] != $original_option[ 'type' ] )
					continue;
				// We have found an equivalent!
				$found = true;
				$equivalents[ $id ] = $field_id;

				// Update it.
				$options_modified = true;
				$this->debug( 'Updating option %s %s', $field_id, $custom_field[ 'name' ] );
				// Don't forget to fix the name.
				$original_option[ 'name' ] = '_ecp_custom_' . $field_id;
				$options[ 'custom-fields' ][ $field_id ] = $original_option;
			}

			if ( $found )
				continue;

			// We did not find this custom field. Add it.
			$options[ 'custom-fields-max-index' ]++;
			$new_field_id = $options[ 'custom-fields-max-index' ];
			$original_option[ 'name' ] = '_ecp_custom_' . $new_field_id;
			$this->debug( 'Creating a new custom field: %s', $original_option );
			$options[ 'custom-fields' ][ $new_field_id ] = $original_option;
			$options_modified = true;

			$equivalents[ $id ] = $new_field_id;
		}

		// Replace the custom field IDs in the custom fields with their new IDs.
		$cf = $bcd->custom_fields();
		foreach( $equivalents as $old_id => $new_id )
		{
			// The value does not change.
			$value = $cf->get_single( '_ecp_custom_' . $old_id );

			$old_key = '_ecp_custom_' . $old_id;
			$this->debug( 'Deleting old key: %s', $old_key );
			$bcd->custom_fields()->child_fields()->delete_meta( $old_key );

			$new_key = '_ecp_custom_' . $new_id;
			$this->debug( 'Creating new key: %s', $new_key );
			$bcd->custom_fields()->child_fields()->update_meta( $new_key, $value );
		}

		if ( $options_modified )
		{
			$this->debug( 'Saving new options: %s', $options );
			update_option( 'tribe_events_calendar_options', $options );
		}
	}

	/**
		@brief		Restore the organiser.
		@since		2015-09-16 17:39:10
	**/
	public function restore_organiser( $bcd )
	{
		$tec = $bcd->the_events_calendar;

		if ( ! isset( $tec->organiser ) )
			return;

		switch_to_blog( $bcd->parent_blog_id );
		$organiser_bcd = ThreeWP_Broadcast()->api()
			->broadcast_children( $tec->organiser->id, [ $bcd->current_child_blog_id ] );
		restore_current_blog();

		$new_organiser_id = $organiser_bcd->new_post( 'ID' );
		$bcd->equivalent_posts()->set( $bcd->parent_blog_id, $tec->organiser->id, get_current_blog_id(), $new_organiser_id );

		update_post_meta( $bcd->new_post( 'ID' ), '_EventOrganizerID', $new_organiser_id );
		$this->debug( 'Setting new organiser ID: %s', $new_organiser_id );
	}

	/**
		@brief		Restore all recurring events.
		@since		2016-04-04 17:03:56
	**/
	public function restore_recurring( $bcd )
	{
		$tec = $bcd->the_events_calendar;
		if ( ! isset( $tec->recurring_events ) )
			return;

		// Broadcast each subevent.
		foreach( $tec->recurring_events as $event )
		{
			$this->debug( 'Broadcasting recurring event %s', $event->ID );
			switch_to_blog( $bcd->parent_blog_id );
			$child_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $event->ID, [ $bcd->current_child_blog_id ] );
			restore_current_blog();

			// Fix the parent of this child event.
			$new_data = [
				'ID' => $child_bcd->new_post( 'ID' ),
				'post_parent' => $bcd->new_post( 'ID' ),
			];
			$this->debug( 'Setting parent of new event %s to %s', $child_bcd->new_post( 'ID' ), $bcd->new_post( 'ID' ) );
			wp_update_post( $new_data );
		}
	}

	/**
		@brief		Restore the venue.
		@since		2015-09-16 17:39:20
	**/
	public function restore_venue( $bcd )
	{
		$tec = $bcd->the_events_calendar;

		if ( ! isset( $tec->venue ) )
			return;

		switch_to_blog( $bcd->parent_blog_id );
		$venue_bcd = ThreeWP_Broadcast()->api()
			->broadcast_children( $tec->venue->id, [ $bcd->current_child_blog_id ] );
		restore_current_blog();

		$new_venue_id = $venue_bcd->new_post( 'ID' );
		$bcd->equivalent_posts()->set( $bcd->parent_blog_id, $tec->venue->id, get_current_blog_id(), $new_venue_id );

		update_post_meta( $bcd->new_post( 'ID' ), '_EventVenueID', $new_venue_id );
		$this->debug( 'Setting new venue ID: %s', $new_venue_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Is the event organiser installed?
		@since		2014-02-22 10:23:22
	**/
	public function requirement_fulfilled()
	{
		return class_exists( '\\Tribe__Events__Main' );
	}

	/**
		@brief		Is this post an event?
		@since		2014-10-24 21:14:58
	**/
	public function post_is_event( $post )
	{
		return $post->post_type == 'tribe_events';
	}
}
