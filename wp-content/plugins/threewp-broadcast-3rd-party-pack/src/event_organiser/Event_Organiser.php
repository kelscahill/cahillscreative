<?php

namespace threewp_broadcast\premium_pack\event_organiser;

/**
	@brief				Adds support for Stephen Harris' <a href="http://wordpress.org/plugins/event-organiser/">Event Organiser plugin</a>, with events and venues.
	@plugin_group		3rd party compatability
	@since				2014-04-05 01:06:53
**/
class Event_Organiser
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_sync_taxonomies_get_info' );
		$this->add_action( 'threewp_broadcast_wp_insert_term' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->eo_installed() )
			return;

		// Conv
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->event_organiser ) )
			return;

		// Conv
		$eo = $bcd->event_organiser->get( 'data' );

		if ( $eo->is_event )
		{
			global $wpdb;
			// Is there an event in the events table?
			$query = sprintf( "SELECT `event_id` FROM `%s` WHERE `post_id` = '%s'", $wpdb->eo_events, $bcd->new_post( 'ID' ) );
			$this->debug( 'Find the event on this blog: %s', $query );
			$results = $this->query( $query );

			if ( count( $results ) < 1 )
			{
				$query = sprintf( "INSERT INTO `%s` SET `post_id` = %s", $wpdb->eo_events, $bcd->new_post( 'ID' ) );
				$this->debug( 'Inserting event: %s', $query );
				$event_id = $this->query_insert_id( $query );
			}
			else
			{
				$result = reset( $results );
				$event_id = $result[ 'event_id' ];
				$this->debug( 'Event ID has been found to be %s.', $event_id );
			}

			// Update the row in the DB.
			$updates = [ 'post_id' => $bcd->new_post( 'ID' ) ];
			foreach( $eo->event_data as $key => $value )
			{
				if ( $key == 'event_id' )
					continue;
				if ( $key == 'post_id' )
					continue;
				$updates[ $key ] = $value;
			}

			$this->debug( 'Updating event.' );
			$wpdb->update( $wpdb->eo_events, $updates, [ 'event_id' => $event_id ] );
		}

		// Update the venue meta data.
		if ( $eo->has_venue )
		{
			$venue_id = eo_get_venue( $bcd->new_post( 'ID' ) );
			if ( $venue_id < 1 )
			{
				$this->debug( 'Error! This post does not have a venue assigned to it, even though one should already exist!' );
			}
			else
			{
				$this->debug( 'The new venue ID is %s.', $venue_id );
				$meta = [];
				foreach( $eo->venue_meta as $key => $value )
				{
					$value = $value[ 0 ];
					$this->debug( 'Updating venue %s: %s = %s', $venue_id, $key, $value );
					eo_update_venue_meta( $venue_id, $key, $value );
				}
			}
		}
	}

	/**
		@brief		Save venue meta if necessary.
		@since		2019-07-18 21:04:49
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->event_organiser ) )
			$bcd->event_organiser = ThreeWP_Broadcast()->collection();

		if ( ! isset( $bcd->parent_post_taxonomies[ 'event-venue' ] ) )
			return;

		foreach( $bcd->parent_post_taxonomies[ 'event-venue' ] as $term )
		{
			$term_id = $term->term_id;
			$this->debug( 'Saving venue_meta for %s', $term_id );
			$bcd->event_organiser->collection( 'venue_meta' )->set( $term_id, eo_get_venue_meta( $term_id ) );
		}
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ 'event' ] = 'event';
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->eo_installed() )
		{
			$this->debug( 'Event Organiser was not detected.' );
			return;
		}

		$bcd = $action->broadcasting_data;

		if ( ! $this->post_is_event( $bcd->post ) )
		{
			$this->debug( 'This post is not an event.' );
			return;
		}

		$eo = new \stdClass;
		$eo->is_event = true;

		// Save the event data
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $wpdb->eo_events, $bcd->post->ID );
		$this->debug( 'Saving event data: %s', $query );
		$results = $this->query( $query );
		$eo->event_data = reset( $results );

		$this->debug( 'Handling event for post ID %s', $bcd->post->ID );
		$venue_id = eo_get_venue( $bcd->post->ID );
		$eo->has_venue = ( $venue_id > 0 );
		if ( $eo->has_venue )
		{
			$this->debug( 'The venue ID is %s', $venue_id );
			$eo->venue_meta = eo_get_venue_meta( $venue_id );
			$this->debug( 'The venue meta data is: %s', var_export( $eo->venue_meta, true ) );
		}
		else
			$this->debug( 'The event has no venue.' );

		if ( ! isset( $bcd->event_organiser ) )
			$bcd->event_organiser = ThreeWP_Broadcast()->collection();

		$bcd->event_organiser->set( 'data', $eo );
	}

	public function threewp_broadcast_sync_taxonomies_get_info( $action )
	{
		$action->text .= $this->p( __( '%s: Venues can only be broadcasted, not updated.', 'threewp_broadcast' ),
			sprintf( '<strong>Event organiser</strong>' ) );
	}

	/**
		@brief		Update the new term with venue data.
		@since		2014-04-08 16:42:30
	**/
	public function threewp_broadcast_wp_insert_term( $action )
	{
		if ( $action->taxonomy != 'event-venue' )
			return;

		$term_id = $action->new_term->term_id;
		$venue_id = intval( $term_id );
		$this->debug( 'This term is an event venue with the ID %s.', $venue_id );

		foreach( $action->term as $key => $value )
		{
			if ( strpos( $key, 'venue_' ) !== 0 )
				continue;
			$key = preg_replace( '/^venue/', '', $key );
			$this->debug( 'Setting %s to %s.', $key, $value );
			eo_update_venue_meta( $venue_id, $key, $value );
		}
	}

	/**
		@brief		Maybe update the new term with venue data.
		@since		2014-04-08 16:42:30
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		if ( $action->taxonomy != 'event-venue' )
			return;

		foreach( $action->new_term as $key => $value )
		{
			if ( strpos( $key, 'venue_' ) !== 0 )
				continue;

			$update = true;
			if ( $action->has_old_term() )
			{
				$update = false;
				if ( $action->old_term->$key != $action->new_term->$key )
					$update = true;
			}

			if ( $update )
			{
				$venue_id = $action->new_term->term_id;
				$key = preg_replace( '/^venue/', '', $key );
				$this->debug( 'Setting %s to %s.', $key, $value );
				eo_update_venue_meta( $venue_id, $key, $value );
			}
		}

		$bcd = $action->broadcasting_data;

		if ( isset( $bcd->event_organiser ) )
			foreach( $bcd->event_organiser->collection( 'venue_meta' )->get( $action->old_term->term_id, [] ) as $key => $value )
			{
				$venue_id = $action->new_term->term_id;
				$value = $value[ 0 ];
				$this->debug( 'Updating venue %s: %s = %s', $venue_id, $key, $value );
				eo_update_venue_meta( $venue_id, $key, $value );
			}

		$term_id = $action->new_term->term_id;
		$venue_id = intval( $term_id );
		$this->debug( 'This term is an event venue with the ID %s.', $venue_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Is the event organiser installed?
		@since		2014-02-22 10:23:22
	**/
	public function eo_installed()
	{
		return defined( 'EVENT_ORGANISER_VER' );
	}

	/**
		@brief		Is this post object an event organiser event?
		@since		2014-02-22 10:44:43
	**/
	public function post_is_event( $post )
	{
		return $post->post_type == 'event';
	}
}
