<?php

namespace threewp_broadcast\premium_pack\events_manager;

use \threewp_broadcast\broadcasting_data;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/events-manager/">Events Manager</a> plugin.
	@plugin_group		3rd party compatability
	@since				2016-06-08 17:34:17
**/
/**
	@brief
**/
class Events_Manager
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'broadcast_events_manager_generate_recurring' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Tell EM to regenerate the recurring events of this post.
		@since		2019-02-14 14:15:51
	**/
	public function broadcast_events_manager_generate_recurring( $post_id )
	{
		if ( ! class_exists( 'EM_Event' ) )
			return $this->debug( 'EM_Event class not found on blog %s for event post %s.', get_current_blog_id(), $post_id );
		// Crons aren't real users, but tell EM that cron may manage events.
		add_filter( 'em_event_can_manage', '__return_true' );
		$this->debug( 'Regenerating recurring events for post %s on blog %s', $post_id, get_current_blog_id() );
		$event = new \EM_Event( $post_id, 'post_id' );
		$event->save_events();
		$this->debug( 'Finished regenerating recurring events' );
	}

	/**
		@brief		Restore events.
		@since		2016-06-08 17:41:55
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->events_manager ) )
			return;

		switch( $bcd->post->post_type )
		{
			case 'event':
			case 'event-recurring':
				$this->restore_event( $bcd );
				$this->restore_event_location( $bcd );
			break;
			case 'location':
				$this->restore_location( $bcd );
			break;
		}
	}

	/**
		@brief		Detect events.
		@since		2016-06-08 17:41:45
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return;

		$bcd = $action->broadcasting_data;
		$bcd->events_manager = ThreeWP_Broadcast()->collection();

		switch( $bcd->post->post_type )
		{
			case 'event':
			case 'event-recurring':
				// Prevent Events Manager from creating duplicate SQL rows.
				global $EM_SAVING_EVENT;
				$EM_SAVING_EVENT = true;
				$this->save_event( $bcd );
				$this->save_event_location( $bcd );
			break;
			case 'location':
				// Prevent Events Manager from creating duplicate SQL rows.
				global $EM_SAVING_LOCATION;
				$EM_SAVING_LOCATION = true;
				$this->save_location( $bcd );
			break;
		}
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2016-06-08 18:18:49
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'event' );
		$action->add_type( 'event-recurring' );
		$action->add_type( 'location' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save the event SQL row.
		@since		2016-06-09 21:39:20
	**/
	public function save_event( $bcd )
	{
		$event_id = $bcd->custom_fields()->get_single( '_event_id' );
		$this->get_event( $bcd, $event_id );
	}

	/**
		@brief		Save the event's location for later broadcasting.
		@since		2016-06-09 17:46:37
	**/
	public function save_event_location( $bcd )
	{
		$location_id = $bcd->custom_fields()->get_single( '_location_id' );
		if ( $location_id < 1 )
			return $this->debug( 'No location available.' );

		$this->get_location( $bcd, $location_id );
		$this->debug( 'Saving location %s: %s',
			$location_id,
			$bcd->events_manager->get( 'location_sql_row' )
		);

		// Save the broadcast data of the location.
		// The post ID is not the same as the location ID, of course.
		$location_post_id = $bcd->events_manager->get( 'location_sql_row' )->post_id;
		$location_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $location_post_id );
		$parent = $location_bcd->get_linked_parent();
		if ( $parent !== false )
		{
			// Load the parent's bcd.
			$location_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $parent[ 'blog_id' ], $parent[ 'post_id' ] );
		}

		$bcd->events_manager->set( 'location_bcd', $location_bcd );
		$this->debug( 'Saving location broadcast data: %s', $location_bcd );
	}

	/**
		@brief		Save the location SQL.
		@since		2016-06-08 19:26:12
	**/
	public function save_location( $bcd )
	{
		// We need to retrieve the associated location ID.
		global $wpdb;
		$query = sprintf( 'SELECT * FROM `%sem_locations` WHERE `post_id` = %s',
			$wpdb->prefix,
			$bcd->post->ID
		);
		$location_id = $wpdb->get_var( $query );

		$this->get_location( $bcd, $location_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Restore the event SQL.
		@since		2016-06-09 19:16:03
	**/
	public function restore_event( $bcd )
	{
		global $wpdb;
		$new_event_post_id = $bcd->new_post( 'ID' );
		$table = $wpdb->prefix . 'em_events';

		// Create or update the SQL row.
		global $wpdb;
		$query = sprintf( 'SELECT `event_id` FROM `%s` WHERE `post_id` = %s',
			$table,
			$new_event_post_id
		);
		$this->debug( 'Find the event on this blog: %s', $query );
		$results = $this->query( $query );

		if ( count( $results ) < 1 )
		{
			$data = [
				'post_id' => $new_event_post_id,
				'blog_id' => get_current_blog_id(),
			];
			$this->debug( 'Inserting event: %s', $data );
			$wpdb->insert( $table, $data );
			$event_id = $wpdb->insert_id;
		}
		else
		{
			$result = reset( $results );
			$event_id = $result[ 'event_id' ];
			$this->debug( 'Event ID has been found to be %s.', $event_id );
		}

		// Update the row in the DB.
		$updates = [ 'post_id' => $new_event_post_id ];
		foreach( $bcd->events_manager->get( 'event_sql_row' ) as $key => $value )
		{
			// Skip values that are unique to this blog.
			if ( in_array( $key, [
				'blog_id',
				'event_id',
				'post_id',
			] ) )
				continue;
			$updates[ $key ] = $value;
		}

		$this->debug( 'Updating event %s with new data.', $event_id );
		$wpdb->update( $table, $updates, [ 'event_id' => $event_id ] );

		if ( $this->is_recurring_event( $bcd ) )
		{
			// Delete all existing posts
			$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `recurrence_id` = '%s'", $table, $event_id );
			$this->debug( $query );
			$post_ids = $wpdb->get_col( $query );
			foreach( $post_ids as $post_id )
				wp_delete_post( $post_id, true );
			// And the sql table rows
			$wpdb->delete( $table, [ 'recurrence_id' => $event_id ] );
			// And now schedule a recurring event rebuild.
			$this->debug( 'Scheduling broadcast_events_manager_generate_recurring' );
			wp_schedule_single_event( time() + 10, 'broadcast_events_manager_generate_recurring', [ $new_event_post_id ] );
		}

		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_blog_id', get_current_blog_id() )
			->update_meta( '_event_id', $event_id );
	}

	/**
		@brief		Restore the event's location by maybe broadcasting its post.
		@since		2016-06-09 17:56:55
	**/
	public function restore_event_location( $bcd )
	{
		$location_sql_row = $bcd->events_manager->get( 'location_sql_row' );
		if ( ! $location_sql_row )
			return $this->debug( 'No location SQL row.' );

		// Ask the BCD if there is a linked location post here.
		$location_bcd = $bcd->events_manager->get( 'location_bcd' );

		$new_location_post_id = false;
		if ( $location_bcd )
			$new_location_post_id = $location_bcd->get_linked_post_on_this_blog();

		if ( ! $new_location_post_id )
		{
			// No location BCD available?
			if ( ! $location_bcd->blog_id )
			{
				// Fake a bcd.
				$location_bcd = (object)[
					'blog_id' => $bcd->parent_blog_id,
					'post_id' => $location_sql_row->post_id,
				];
			}

			$this->debug( 'Broadcasting location %s', $location_bcd->post_id );

			switch_to_blog( $location_bcd->blog_id );
			$new_location_bcd = ThreeWP_Broadcast()
				->api()
				->broadcast_children( $location_bcd->post_id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();

			$new_location_post_id = $new_location_bcd->broadcast_data->get_linked_post_on_this_blog();
		}

		$this->debug( 'Location ID on this blog is %s', $new_location_post_id );

		// As usual, location ID and post ID are not the same.
		$location_id = $this->get_location_id_for_post( $new_location_post_id );

		// Set our location_id to the correct post.
		$this->debug( 'Setting new location post ID to %s', $new_location_post_id );

		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_location_id', $location_id );

		// Set the correct new location also in the Events table.
		global $wpdb;
		$table = $wpdb->prefix . 'em_events';
		$query = sprintf( "UPDATE `%s` SET `location_id` = '%s' WHERE `post_id` = '%s'",
			$table,
			$new_location_post_id,
			$bcd->new_post( 'ID' )
		);
		$this->debug( 'Updating event location ID: %s', $query );
		$this->query( $query );
	}

	/**
		@brief		Restore the location SQL.
		@since		2016-06-09 19:16:03
	**/
	public function restore_location( $bcd )
	{
		global $wpdb;
		$new_location_post_id = $bcd->new_post( 'ID' );
		$table = $wpdb->prefix . 'em_locations';

		// Create or update the SQL row.
		global $wpdb;
		$query = sprintf( 'SELECT `location_id` FROM `%s` WHERE `post_id` = %s',
			$table,
			$new_location_post_id
		);
		$this->debug( 'Find the location on this blog: %s', $query );
		$results = $this->query( $query );

		if ( count( $results ) < 1 )
		{
			$data = [
				'post_id' => $new_location_post_id,
				'blog_id' => get_current_blog_id(),
			];
			$this->debug( 'Inserting location: %s', $data );
			$wpdb->insert( $table, $data );
			$location_id = $wpdb->insert_id;
		}
		else
		{
			$result = reset( $results );
			$location_id = $result[ 'location_id' ];
			$this->debug( 'Location ID has been found to be %s.', $location_id );
		}

		// Update the row in the DB.
		$updates = [ 'post_id' => $new_location_post_id ];
		foreach( $bcd->events_manager->get( 'location_sql_row' ) as $key => $value )
		{
			// Skip values that are unique to this blog.
			if ( in_array( $key, [
				'blog_id',
				'location_id',
				'post_id',
			] ) )
				continue;
			$updates[ $key ] = $value;
		}

		$this->debug( 'Updating location %s with new data.', $location_id );
		$wpdb->update( $table, $updates, [ 'location_id' => $location_id ] );

		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_blog_id', get_current_blog_id() )
			->update_meta( '_location_id', $location_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save the data of this event post.
		@since		2016-06-09 17:19:27
	**/
	public function get_event( $bcd, $event_id )
	{
		$this->prepare_bcd( $bcd );

		// Save the SQL row.
		global $wpdb;
		$query = sprintf( 'SELECT * FROM `%sem_events` WHERE `event_id` = %s',
			$wpdb->prefix,
			$event_id
		);
		$row = $wpdb->get_row( $query );

		$bcd->events_manager->set( 'event_sql_row', $row );
	}

	/**
		@brief		Save the data of this location post.
		@since		2016-06-09 17:19:27
	**/
	public function get_location( $bcd, $location_id )
	{
		$this->prepare_bcd( $bcd );

		// Save the SQL row.
		global $wpdb;
		$query = sprintf( 'SELECT * FROM `%sem_locations` WHERE `location_id` = %s',
			$wpdb->prefix,
			$location_id
		);
		$row = $wpdb->get_row( $query );

		$bcd->events_manager->set( 'location_sql_row', $row );
	}

	/**
		@brief		Retrieve the location ID for a location post ID.
		@since		2016-06-09 20:28:23
	**/
	public function get_location_id_for_post( $location_post_id )
	{
		global $wpdb;
		$query = sprintf( 'SELECT `location_id` FROM `%sem_locations` WHERE `post_id` = %s',
			$wpdb->prefix,
			$location_post_id
		);
		return $wpdb->get_var( $query );
	}

	/**
		@brief		Is this a recurring event?
		@since		2019-02-14 14:09:52
	**/
	public function is_recurring_event( $broadcasting_data )
	{
		return $broadcasting_data->post->post_type = 'event-recurring';
	}

	/**
		@brief		Insert an events_manager property into the broadcasting_data object, if necessary.
		@since		2016-06-09 17:23:09
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->events_manager ) )
			$bcd->events_manager = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Is the plugin installed?
		@since		2016-06-08 17:40:59
	**/
	public function requirement_fulfilled()
	{
		return defined( 'EM_VERSION' );
	}
}
