<?php

namespace threewp_broadcast\premium_pack\geo_my_wordpress;

/**
	@brief			Adds support for Eyal Fitoussi's <a href="https://wordpress.org/plugins/geo-my-wp/">GEO my WordPress</a> geolocation plugin.
	@plugin_group	3rd party compatability
	@since			2015-09-04 20:40:21
**/
class GEO_my_WordPress
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2015-09-04 20:49:09
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->geo_my_wp ) )
			return;

		if ( count( $bcd->geo_my_wp ) < 1 )
			return;

		$this->restore_gmw_locations( $bcd );
		$this->restore_places_locator( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-05-20 20:28:14
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_plugin() )
			return;

		$bcd = $action->broadcasting_data;
		$geo_my_wp = ThreeWP_Broadcast()->collection();
		$bcd->geo_my_wp = $geo_my_wp;

		$this->save_gmw_locations( $bcd );
		$this->save_places_locator( $bcd );
	}

	// --------------------------------
	// SAVE
	// --------------------------------

	/**
		@brief		save_gmw_locations
		@since		2018-05-28 19:48:29
	**/
	public function save_gmw_locations( $bcd )
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM {$wpdb->base_prefix}gmw_locations WHERE `blog_id` = %d AND `object_id` = %d",
			$bcd->parent_blog_id,
			$bcd->post->ID
		);
		$results = $this->query( $query );
		if ( count( $results ) < 1 )
			return;
		$result = reset( $results );
		$bcd->geo_my_wp->collection( 'gmw_locations' )->set( $bcd->post->ID, $result );
	}

	/**
		@brief		Save the places locator data.
		@since		2015-09-04 20:58:09
	**/
	public function save_places_locator( $bcd )
	{
		// Extract any post locator data if it exists.
		global $wpdb;
		$query = sprintf( "SELECT * FROM {$wpdb->prefix}places_locator WHERE `post_id` = %d", $bcd->post->ID );
		$results = $this->query( $query );

		// Only one, and exactly one, result is acceptable.
		if ( count( $results ) != 1 )
			return;

		$place = reset( $results );
		$this->debug( 'Saving place: %s', $place );

		$bcd->geo_my_wp->collection( 'places_locator' )->set( 'place', $place );
	}

	// --------------------------------
	// RESTORE
	// --------------------------------

	/**
		@brief		Copy the info in the gmw_locations table.
		@since		2018-05-28 19:44:47
	**/
	public function restore_gmw_locations( $bcd )
	{
		$data = $bcd->geo_my_wp->collection( 'gmw_locations' )->get( $bcd->post->ID );

		global $wpdb;
		$query = sprintf( "SELECT * FROM {$wpdb->base_prefix}gmw_locations WHERE `blog_id` = %d AND `object_id` = %d",
			$bcd->current_child_blog_id,
			$bcd->new_post( 'ID' )
		);

		unset( $data[ 'ID' ] );
		$data[ 'blog_id' ] = $bcd->current_child_blog_id;
		$data[ 'object_id' ] = $bcd->new_post( 'ID' );

		$results = $this->query( $query );
		if ( count( $results ) < 1 )
		{
			$this->debug( 'Inserting gmw_location %s', $data );
			$wpdb->insert( "{$wpdb->base_prefix}gmw_locations", $data );
		}
		else
		{
			$this->debug( 'Updating gmw_location %s', $data );
			$wpdb->update( "{$wpdb->base_prefix}gmw_locations", $data, [
				'blog_id' => $data[ 'blog_id' ],
				'object_id' => $data[ 'object_id' ],
			] );
		}
	}

	/**
		@brief		Restore the places locator data.
		@since		2015-09-04 21:01:56
	**/
	public function restore_places_locator( $bcd )
	{
		if ( count( $bcd->geo_my_wp->collection( 'places_locator' ) ) < 1 )
			return;

		$new_post_id = $bcd->new_post( 'ID' );
		$place = $bcd->geo_my_wp->collection( 'places_locator' )->get( 'place' );

		// Delete the current place.
		global $wpdb;
		$query = sprintf( "DELETE FROM {$wpdb->prefix}places_locator WHERE `post_id` = %d", $new_post_id );
		$this->debug( 'Deleting current place: %s', $query );

		// And insert the new one.
		$place[ 'post_id' ] = $new_post_id;
		$this->debug( 'Reinserting place: %s', $place );
		$wpdb->insert( "{$wpdb->prefix}places_locator", $place );
	}

	// --------------------------------
	// MISC
	// --------------------------------

	/**
		@brief		Is the plugin installed?
		@since		2015-09-04 20:41:55
	**/
	public function has_plugin()
	{
		return class_exists( 'GEO_my_WP' );
	}

}
