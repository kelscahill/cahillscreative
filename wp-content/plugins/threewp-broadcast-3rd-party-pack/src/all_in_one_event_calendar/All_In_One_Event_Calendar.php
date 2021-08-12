<?php

namespace threewp_broadcast\premium_pack\all_in_one_event_calendar;

use \plainview\sdk_broadcast\collections\collection;

/**
	@brief				Adds support for <a href="http://www.wordpress.org/plugins/all-in-one-event-calendar/">Timely’s All In One Calendar</a> plugin.
	@plugin_group		3rd party compatability
	@since				2014-07-12 23:42:28
**/
class All_In_One_Event_Calendar
	extends \threewp_broadcast\premium_pack\base
{
	const custom_post_type = 'ai1ec_event';

	/**
		@brief		Broadcasting data, if any. Used to help sync the cat metadata.
		@since		2014-07-13 10:48:59
	**/
	public static $broadcasting_data;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Handle updating of the advanced custom fields image fields.
		@param		$action		Broadcast action.
		@since		20131030
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		// Conv
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->ai1ec ) )
			return;

		global $wpdb;
		$db = $this->get_ai1ec_db();
		$new_post_id = $bcd->new_post( 'ID' );

		// Update the event
		// ----------------
		$table = $db->get_table_name( 'ai1ec_events' );

		// Is there an event in the events table?
		$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Find the event on this blog: %s', $query );
		$results = $this->query( $query );

		if ( count( $results ) < 1 )
		{
			$query = sprintf( "INSERT INTO `%s` SET `post_id` = %s", $table, $new_post_id );
			$this->debug( 'Inserting event: %s', $query );
			$this->query( $query );
		}

		// Update the row in the DB.
		$updates = [ 'post_id' => $new_post_id ];
		foreach( $bcd->ai1ec->events as $key => $value )
		{
			if ( $key == 'post_id' )
				continue;
			$updates[ $key ] = $value;
		}

		$this->debug( 'Updating event' );
		$wpdb->update( $table, $updates, [ 'post_id' => $new_post_id ] );

		// Update the event instances.
		// ---------------------------
		// Easiest is to start afresh.
		$table = $db->get_table_name( 'ai1ec_event_instances' );
		$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'", $table, $new_post_id );
		$this->debug( 'Delete the event instances: %s', $query );
		$this->query( $query );

		$inserts = [];
		foreach( $bcd->ai1ec->event_instances as $instance )
		{
			$data = $instance;
			unset( $data[ 'id' ] );
			$data[ 'post_id' ] = $new_post_id;
			$this->debug( 'Inserting instance: %s', serialize( $data ) );
			$wpdb->insert( $table, $data );
		}
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20131030
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_ai1ec() )
			return;

		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != self::custom_post_type )
			return;

		$a1iec = new \stdClass;

		$db = $this->get_ai1ec_db();

		// Save the event data.
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $db->get_table_name( 'ai1ec_events' ), $bcd->post->ID );
		$this->debug( 'Saving event data: %s', $query );
		$results = $this->query( $query );
		$a1iec->events = reset( $results );

		// Save the event instances.
		$query = sprintf( "SELECT * FROM `%s` WHERE `post_id` = '%s'", $db->get_table_name( 'ai1ec_event_instances' ), $bcd->post->ID );
		$this->debug( 'Saving event instances: %s', $query );
		$a1iec->event_instances = $this->query( $query );

		// Save any cat meta.
		$a1iec->terms = new collection;
		foreach( $bcd->parent_post_taxonomies as $parent_post_taxonomy => $parent_post_terms )
		{
			if ( $parent_post_taxonomy != 'events_categories' )
				continue;
			foreach( $parent_post_terms as $parent_post_term )
			{
				$term_id = $parent_post_term->term_id;
				$query = sprintf( "SELECT * FROM `%s` WHERE `term_id` = '%s'", $db->get_table_name( 'ai1ec_event_category_meta' ), $term_id );
				$term_data = $this->query( $query );
				if ( count( $term_data ) < 1 )
					continue;
				$term_data = reset( $term_data );
				$a1iec->terms->set( $term_id, $term_data );
			}
		}

		// Save this data statically in order to help with syncing the category meta.
		static::$broadcasting_data = $bcd;

		$bcd->ai1ec = $a1iec;
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ self::custom_post_type ] = self::custom_post_type;
	}

	/**
		@brief		threewp_broadcast_wp_update_term
		@since		2014-07-13 10:49:25
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		if ( ! static::$broadcasting_data )
			return;

		// Broadcasting data exists!
		$bcd = static::$broadcasting_data;
		$ai1ec = $bcd->ai1ec;

		if ( ! $ai1ec->terms->has( $action->old_term->term_id ) )
			return;

		global $wpdb;
		$db = $this->get_ai1ec_db();
		$new_term_id = $action->new_term->term_id;
		$table = $db->get_table_name( 'ai1ec_event_category_meta' );
		$term = $ai1ec->terms->get( $action->old_term->term_id );

		// Is there already an entry in the table?
		$query = sprintf( "SELECT `term_id` FROM `%s` WHERE `term_id` = '%s'", $table, $new_term_id );
		$this->debug( 'Find the event category meta on this blog: %s', $query );
		$results = $this->query( $query );

		if ( count( $results ) < 1 )
		{
			$query = sprintf( "INSERT INTO `%s` SET `term_id` = %s", $table, $new_term_id );
			$this->debug( 'Inserting event category meta: %s', $query );
			$this->query( $query );
		}

		// Update the row in the DB.
		$term[ 'term_id' ] = $new_term_id ;
		$this->debug( 'Updating event category meta: %s', serialize( $term ) );
		$wpdb->update( $table, $term, [ 'term_id' => $new_term_id ] );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Return the instance of the calendar.
		@since		2014-07-13 09:03:01
	**/
	public function get_ai1ec()
	{
		global $ai1ec_front_controller;
		return $ai1ec_front_controller;
	}

	/**
		@brief		Return the calendar db helper.
		@since		2014-07-13 09:04:33
	**/
	public function get_ai1ec_db()
	{
		return $this->get_ai1ec_registry()->get( 'dbi.dbi' );
	}

	/**
		@brief		Return the calendar registry class.
		@since		2014-07-13 09:04:33
	**/
	public function get_ai1ec_registry()
	{
		return $this->get_ai1ec()->return_registry( true );
	}

	/**
		@brief		Is All In One Event Calendar active?
		@since		2014-07-12 23:54:01
	**/
	public function has_ai1ec()
	{
		return defined( 'AI1EC_VERSION' );
	}

}
