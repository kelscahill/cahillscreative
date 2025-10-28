<?php

namespace threewp_broadcast\premium_pack\the_events_calendar;

use \threewp_broadcast\actions;
use \threewp_broadcast\broadcasting_data;
use TEC\Events\Custom_Tables\V1\Migration\State;

/**
	@brief				Adds support for Modern Tribe's <a href="https://wordpress.org/plugins/the-events-calendar/">The Events Calendar</a> plugin with venues and organisers.
	@plugin_group		3rd party compatability
	@since				2014-10-24 21:12:54
**/
class The_Events_Calendar
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'tec_events_custom_tables_v1_after_save_occurrences' );
		$this->add_action( 'tec_events_custom_tables_v1_update_post_after' );

		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_post_action' );

		if ( ! $this->tec_v6() )
			$this->add_action( 'tribe_events_update_meta', 100 );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		tec_events_custom_tables_v1_update_post_after
		@since		2024-03-23 17:07:13
	**/
	public function tec_events_custom_tables_v1_update_post_after( $post_id )
	{
		$this->debug( 'tec_events_custom_tables_v1_update_post_after' );
		ThreeWP_Broadcast()->api()->update_children( $post_id );
	}

	/**
		@brief		TEC saves the occurrences in the DB table **after** the whole save_post action.
		@since		2023-01-20 13:07:40
	**/
	public function tec_events_custom_tables_v1_after_save_occurrences( $post_id )
	{
		$this->debug( 'tec_events_custom_tables_v1_after_save_occurrences' );
		ThreeWP_Broadcast()->api()->update_children( $post_id );
	}

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->the_events_calendar ) )
			return;

		if ( isset( $bcd->the_events_calendar->v6 ) )
		{
			$this->restore_v6_event( $bcd );
		}
		else
		{
			$this->restore_recurring( $bcd );
		}

		$this->restore_custom_fields( $bcd );
		$this->restore_organiser( $bcd );
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

		$v6 = $this->tec_v6();

		if ( $v6 )
		{
			$bcd->the_events_calendar->v6 = ThreeWP_Broadcast()->collection();
			$this->save_v6_event( $bcd );
		}
		else
		{
			$this->save_recurring( $bcd );
		}

		$this->save_custom_fields( $bcd );
		$this->save_organiser( $bcd );
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

		$key = '_EventOrganizerID';
		$values = $bcd->custom_fields()->get( $key );

		if ( ! $values )
			return;

		$this->debug( 'The organiser IDs for this event are %s.', $values );
		$tec->organisers = $values;
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
		@brief		Save this v6 event.
		@since		2023-01-16 14:56:38
	**/
	public function save_v6_event( $bcd )
	{
		global $wpdb;
		$tec = $bcd->the_events_calendar;

		$tec_events = 'tec_events';
		$source_table = $this->get_prefixed_table_name( $tec_events, $bcd->parent_blog_id );
		$columns = $this->get_database_table_columns_string( $source_table, [
			'except' => [ 'post_id' ],
		] );
		$v6 = $bcd->the_events_calendar->v6;
		$v6->set( 'tec_events_columns', $columns );

		$query = sprintf( "SELECT %s from `%s` WHERE `post_id` = '%s'",
			$columns,
			$source_table,
			$bcd->post->ID
		);
		$event = $wpdb->get_row( $query );

		if ( ! $event )
			return;

		$event_id = $event->event_id;
		unset( $event->event_id );
		$v6->set( 'tec_event', $event );

		$tec_occurrences = 'tec_occurrences';
		$source_table = $this->get_prefixed_table_name( $tec_occurrences, $bcd->parent_blog_id );
		$columns = $this->get_database_table_columns_string( $source_table, [
			'except' => [ 'occurrence_id', 'event_id', 'post_id' ],
		] );
		$v6 = $bcd->the_events_calendar->v6;
		$v6->set( 'tec_events_columns', $columns );

		$query = sprintf( "SELECT %s from `%s` WHERE `event_id` = '%s'",
			$columns,
			$source_table,
			$event_id
		);
		$event = $wpdb->get_results( $query );
		$v6->set( 'tec_occurrences', $event );

		$this->debug( 'V6 data: %s', $v6 );
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

		if ( ! isset( $tec->organisers ) )
			return;

		$new_values = [];
		foreach( $tec->organisers as $organiser_id )
		{
			$new_organiser_id = $bcd->equivalent_posts()->broadcast_once( $bcd->parent_blog_id, $organiser_id );
			$new_values []= $new_organiser_id;
		}

		$this->debug( 'Setting new organiser ID: %s', $new_values );
		$bcd->custom_fields()
			->child_fields()
			->update_metas( '_EventOrganizerID', $new_values );
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
		@brief		Restore the new v6 data of this event.
		@since		2023-01-16 14:39:48
	**/
	public function restore_v6_event( $bcd )
	{
		global $wpdb;

		$new_post_id = $bcd->new_post( 'ID' );
		$v6 = $bcd->the_events_calendar->v6;

		$name = 'tec_events';
		$target_table = $this->get_prefixed_table_name( $name );

		// Delete everything in the target table.
		$this->debug( "Deleting all events for post %s in %s", $new_post_id, $target_table );
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'",
			$target_table,
			$new_post_id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		// Insert the new event.
		$new_data = $v6->get( 'tec_event' );

		if ( ! $new_data )
			return;

		$new_data->post_id = $new_post_id;
		$wpdb->insert( $target_table, (array) $new_data );
		$event_id = $wpdb->insert_id;

		$this->debug( 'Inserted event %s', $event_id );

		$name = 'tec_occurrences';
		$target_table = $this->get_prefixed_table_name( $name );

		// Delete everything in the target table.
		$this->debug( "Deleting all occurrences for event %s in %s", $event_id, $target_table );
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'",
			$target_table,
			$new_post_id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		foreach( $v6->get( 'tec_occurrences' ) as $occurrence )
		{
			// Delete the occurrence with this hash.
			$query = sprintf( "DELETE FROM `%s` WHERE `hash` = '%s'",
				$target_table,
				$occurrence->hash
			);
			$this->debug( $query );
			$wpdb->get_results( $query );

			$new_data = $occurrence;
			$new_data->event_id = $event_id;
			$new_data->post_id = $new_post_id;
			$this->debug( 'Inserting occurrence %s', $new_data );
			$wpdb->insert( $target_table, (array) $new_data );
			$this->debug( 'Inserted occurrence %s', $wpdb->insert_id );
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

	/**
		@brief		Are we running v6 of TEC with the new database?
		@since		2023-01-16 14:54:58
	**/
	public function tec_v6()
	{
		if ( ! $this->requirement_fulfilled() )
			return false;

		return tribe( State::class )->is_migrated();
	}
}
