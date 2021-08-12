<?php

namespace threewp_broadcast\premium_pack\create;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/mediavine-create/">Create</a> plugin from Mediavine.
	@plugin_group	3rd party compatability
	@since			2020-07-12 21:35:14
**/
class Create
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		The post type.
		@since		2020-07-13 14:02:31
	**/
	public static $post_type = 'mv_create';

	/**
		@brief		Constructor.
		@since		2019-05-09 20:42:01
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new Create_Shortcode();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2019-05-13 15:00:41
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->mv_create ) )
			return;

		global $wpdb;
		$mvc = $bcd->mv_create;
		$old_creation = $mvc->get( 'creation' );

		$table = Create::table_name( 'mv_creations' );
		$this->database_table_must_exist( $table );
		$query = sprintf( "SELECT * FROM `%s` WHERE `object_id` = '%s'", $table, $bcd->new_post( 'ID' ) );

		// Is there a row in the DB for this post?
		$new_creation = $wpdb->get_row( $query );

		if ( ! $new_creation )
		{
			// Insert a new one.
			$new_creation = $mvc->get( 'creation' );
			$new_creation->object_id = $bcd->new_post( 'ID' );
			unset( $new_creation->id );
			$new_creation_id = $wpdb->insert( $table, (array)$new_creation );
			$this->debug( 'Inserted new creation ID: %s', $new_creation_id );
		}
		else
		{
			$new_creation_id = $new_creation->id;
			$this->debug( 'Using existing creation ID %s', $new_creation_id );
		}

		$new_data = (object)[];

		$new_data->thumbnail_id = $bcd->copied_attachments()->get( $old_creation->thumbnail_id );

		foreach( [ 'original_post_id', 'canonical_post_id' ] as $type )
		{
			$type_bcd = $mvc->get( $type . '_bcd' );
			$new_data->$type = $type_bcd->get_linked_post_on_this_blog();
		}

		$aps = $mvc->get( 'associated_posts_bcd' );
		$new_aps = [];
		foreach( $aps as $post_id => $post_bcd )
		{
			$new_post_id = $post_bcd->get_linked_post_on_this_blog();
			if ( ! $new_post_id )
				continue;
			$new_aps []= $new_post_id;
		}
		$new_aps = json_encode( $new_aps );
		$new_data->associated_posts = $new_aps;

		$this->debug(' Updating creation: %s', $new_data );
		$wpdb->update( $table, (array)$new_data, [ 'id' => $new_creation_id ] );

		// Delete all supplies
		$table = Create::table_name( 'mv_supplies' );
		$query = sprintf( "DELETE FROM `%s` WHERE `creation` = '%s'", $table, $new_creation_id );

		// And insert them all anew.
		foreach( $mvc->get( 'supplies' ) as $supply )
		{
			unset( $supply->id );
			$supply->creation = $new_creation_id;
			$this->debug( 'Inserting supply: %s', $supply );
			$wpdb->insert( $table, (array)$supply );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2019-05-13 15:00:52
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != static::$post_type )
			return;

		global $wpdb;
		$bcd->mv_create = ThreeWP_Broadcast()->collection();
		$mvc = $bcd->mv_create;

		// We need to save the table row.
		$table = Create::table_name( 'mv_creations' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `object_id` = '%s'", $table, $bcd->post->ID );
		$creation = $wpdb->get_row( $query );

		$mvc->set( 'creation', $creation );

		// Remember the BCD of the various posts.
		foreach( [ 'original_post_id', 'canonical_post_id' ] as $type )
			$mvc->set( $type . '_bcd', ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $creation->$type ) );

		$associated_posts = json_decode( $creation->associated_posts );
		$ap_bcd = [];
		foreach( $associated_posts as $associated_post_id )
			$ap_bcd[ $associated_post_id ] = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $associated_post_id );
		$mvc->set( 'associated_posts_bcd', $ap_bcd );

		$bcd->try_add_attachment( $creation->thumbnail_id );

		// Save supplies
		$table = Create::table_name( 'mv_supplies' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `creation` = '%s'", $table, $creation->id );
		$supplies = $wpdb->get_results( $query );
		$mvc->set( 'supplies', $supplies );

	}

	/**
		@brief		Add the views.
		@since		2019-05-09 20:42:39
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( static::$post_type );
	}

	// ----
	// SAVE
	// ----

	// -------
	// RESTORE
	// -------

	// -------
	// MISC
	// -------

	/**
		@brief		Return the table name.
		@since		2018-08-20 10:42:15
	**/
	public static function table_name( $table, $prefix = null )
	{
		if ( ! $prefix )
		{
			global $wpdb;
			$prefix = $wpdb->prefix;
		}
		return sprintf( '%s%s',
			$prefix,
			$table
		);
	}
}
