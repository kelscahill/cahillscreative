<?php

namespace threewp_broadcast\premium_pack\modern_events;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/modern-events-calendar-lite/">Modern Events plugin</a>.
	@plugin_group	3rd party compatability
	@since			2020-09-17 22:11:53
**/
class Modern_Events
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-04-28 23:39:15
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->mec_events ) )
			return;

		foreach( [ 'mec_location_id', 'mec_organizer_id' ] as $key )
		{
			$old_term_id = $bcd->custom_fields()->get_single( $key );
			$new_term_id = $bcd->terms()->get( $old_term_id );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $new_term_id );
		}

		// Copy over the extra DB tables.
		$new_post_id = $bcd->new_post( 'ID' );

		global $wpdb;
		foreach( [ 'mec_dates', 'mec_events' ] as $table_name )
		{
			$parent_table = $this->table_name( $table_name, $bcd->parent_blog_id );
			$child_table = $this->table_name( $table_name );
			$columns = $this->get_database_table_columns_string( $child_table, [ 'except' => [ 'id', 'post_id' ] ] );
			// Empty the current tables.
			$query = sprintf( "DELETE FROM `%s` WHERE `post_id` = '%s'",
				$child_table,
				$new_post_id
			);
			$this->debug( $query );
			$wpdb->query( $query );

			// And insert the data from the parent blog.
			$query = sprintf( "INSERT INTO `%s` ( `post_id`, %s ) ( SELECT %d, %s FROM `%s` WHERE `post_id` = '%s' )",
				$child_table,
				$columns,
				$new_post_id,
				$columns,
				$parent_table,
				$bcd->post->ID
			);
			$this->debug( $query );
			$this->debug( $query );
			$wpdb->query( $query );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-04-28 23:39:00
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'mec-events' )
			return;

		$bcd->mec_events = ThreeWP_Broadcast()->collection();

		$bcd->taxonomies()->also_sync( 'mec-events', 'mec_location' );
		$bcd->taxonomies()->also_sync( 'mec-events', 'mec_organizer' );
	}

	/**
		@brief		Add post types.
		@since		2020-09-17 22:12:41
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'mec-events' );
	}

	/**
		@brief		Return the table name (on this blog).
		@since		2020-09-17 22:33:33
	**/
	public function table_name( $table, $blog_id = null )
	{
		if ( ! $blog_id )
		{
			global $wpdb;
			$prefix = $wpdb->prefix;
		}
		else
		{
			global $wpdb;
			switch_to_blog( $blog_id );
			$prefix = $wpdb->prefix;
			restore_current_blog();
		}
		return sprintf( '%s%s',
			$prefix,
			$table
		);
	}
}
