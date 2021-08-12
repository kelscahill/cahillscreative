<?php

namespace threewp_broadcast\premium_pack\user_access_manager;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/user-access-manager/">User Access Manager</a> plugin.
	@plugin_group	3rd party compatability
	@since			2018-08-07 10:19:00
**/
class User_Access_Manager
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		Is UAM activated on this blog?
		@since		2020-06-30 09:27:12
	**/
	public function has_uam()
	{
		global $wpdb;
		$table = sprintf( "%suam_accessgroup_to_object", $wpdb->prefix );
		return $this->database_table_exists( $table );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2018-08-08 22:09:20
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( ! isset( $bcd->user_access_manager ) )
			return;

		$apply = apply_filters( 'broadcast_user_access_mananger_apply_on_this_blog', true );
		if ( ! $apply )
			return;

		if ( ! $this->has_uam() )
			return;

		global $wpdb;
		$uam = $bcd->user_access_manager;

		// Sync the groups, if any.
		$groups = [];
		$table = 'uam_accessgroups';
		foreach( $uam->collection( $table ) as $group )
		{
			// Find the equivalent group, if any.
			$query = sprintf( "SELECT * FROM `%s%s` WHERE `groupname` = '%s'",
				$wpdb->prefix,
				$table,
				$group->groupname
			);
			$row = $wpdb->get_row( $query );
			if ( ! $row )
			{
				$data = (array) $group;
				unset( $data[ 'ID' ] );
				$new_group_id = $wpdb->insert( $wpdb->prefix . $table, $data );
			}
			else
			{
				$new_group_id = $row->ID;
			}
			$groups[ $group->ID ] = $new_group_id;
			$this->debug( 'New group ID for %s is %s', $group->groupname, $new_group_id );
		}

		$this->debug( 'Group IDs are: %s', $groups );

		$table = 'uam_accessgroup_to_object';
		// Delete existing rows for this post.
		$query = sprintf( "DELETE FROM `%s%s` WHERE `object_id` = '%d'",
			$wpdb->prefix,
			$table,
			$bcd->new_post( 'ID' )
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		// Insert the new rows for the post.
		foreach( $uam->collection( 'post' ) as $row )
		{
			$row = clone( $row );		// Work with a copy, since we might be broadcasting to other blogs.
			// Update the ID.
			$row->object_id = $bcd->new_post( 'ID' );
			if ( $row->group_id > 0 )
				$row->group_id = $groups[ $row->group_id ];
			$this->debug( 'Inserting %s', $row );
			$wpdb->insert( $wpdb->prefix . $table, (array) $row );
		}

		// Insert the new rows for the attachments.
		foreach( $uam->collection( 'attachment' ) as $row )
		{
			$row = clone( $row );		// Work with a copy, since we might be broadcasting to other blogs.
			$old_attachment_id = $row->object_id;
			// Update the ID.
			$row->object_id = $bcd->copied_attachments()->get( $old_attachment_id );
			if ( $row->group_id > 0 )
				$row->group_id = $groups[ $row->group_id ];
			$this->debug( 'Inserting %s', $row );
			$wpdb->insert( $wpdb->prefix . $table, (array) $row );
		}

		// Insert the new rows for the terms.
		foreach( $uam->collection( 'term' ) as $row )
		{
			$row = clone( $row );		// Work with a copy, since we might be broadcasting to other blogs.
			// Update the ID.
			$row->object_id = $bcd->terms()->get( $row->object_id );
			if ( $row->group_id > 0 )
				$row->group_id = $groups[ $row->group_id ];
			$this->debug( 'Inserting %s', $row );
			$wpdb->insert( $wpdb->prefix . $table, (array) $row );
		}

		// And update the group contents.

		// Delete old group contents.
		$query = sprintf( "DELETE FROM `%s%s` WHERE `group_id` IN ('%s') AND `object_type` = '_role_'",
			$wpdb->prefix,
			$table,
			implode( "','", $groups )
		);
		$wpdb->get_results( $query );

		foreach( $uam->collection( 'uam_accessgroup_to_object_groups' ) as $old_group )
		{
			$new_group = (array) $old_group;
			$new_group[ 'group_id' ] = $groups[ $new_group[ 'group_id' ] ];

			// For some reason, wpdb insert absolutely refuses to insert the text object_id. So we temporarily insert a number...
			$time = time();
			$new_group[ 'object_id' ] = $time;
			$this->debug( 'Inserting group data %s', $new_group );
			$wpdb->insert( $wpdb->prefix . $table, $new_group );

			// Force a rename of the object id number.
			$query = sprintf( "UPDATE `%s%s` SET `object_id` = '%s' WHERE `object_id` = '%s'",
				$wpdb->prefix,
				$table,
				$old_group->object_id,
				$time
			);
			$this->debug( $query );
			$wpdb->query( $query );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2018-08-07 10:24:18
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( ! $this->has_uam() )
			return;

		$uam = ThreeWP_Broadcast()->collection();
		$bcd->user_access_manager = $uam;

		global $wpdb;
		$group_ids = [];

		// Is the post itself restricted?
		$query = sprintf( "SELECT * FROM `%suam_accessgroup_to_object` WHERE `object_id` = '%d' AND `general_object_type` = '_post_'",
			$wpdb->prefix,
			$bcd->post->ID
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		if ( count( $results ) > 0 )
		{
			$uam->collection( 'post' )->import_array( $results );

			// Fetch any groups.
			foreach( $results as $row )
			{
				if ( $row->group_id < 1 )
					continue;
				$group_ids []= $row->group_id;
			}
		}

		// How about any attachments we know of?
		$attachment_ids = array_keys( $bcd->attachment_data );
		$query = sprintf( "SELECT * FROM `%suam_accessgroup_to_object` WHERE `object_id` IN ('%s') AND `object_type` = 'attachment'",
			$wpdb->prefix,
			implode( ",", $attachment_ids )
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		if ( count( $results ) > 0 )
		{
			$uam->collection( 'attachment' )->import_array( $results );

			$this->debug( 'Saved UAM data for %d attachments.', count( $results ) );

			// Fetch any groups.
			foreach( $results as $row )
			{
				if ( $row->group_id < 1 )
					continue;
				$group_ids []= $row->group_id;
			}
		}

		// Terms restricted?
		$term_ids = [];
		foreach( $bcd->parent_post_taxonomies as $taxonomy => $terms )
			foreach( $terms as $term_id => $term )
				$term_ids []= $term_id;
		$query = sprintf( "SELECT * FROM `%suam_accessgroup_to_object` WHERE `object_id` IN ( '%s' ) AND `general_object_type` = '_term_'",
			$wpdb->prefix,
			implode( "','", $term_ids ),
			$bcd->post->post_type
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		if ( count( $results ) > 0 )
		{
			$uam->collection( 'term' )->import_array( $results );

			// Fetch any groups.
			foreach( $results as $row )
			{
				if ( $row->group_id < 1 )
					continue;
				$group_ids []= $row->group_id;
			}
		}

		$group_ids = array_unique( $group_ids );

		if ( count( $group_ids ) > 0 )
		{
			$query = sprintf( "SELECT * FROM `%suam_accessgroup_to_object` WHERE `group_id` IN ('%s') AND `object_type` = '_role_'",
				$wpdb->prefix,
				implode( "','", $group_ids )
			);
			$results = $wpdb->get_results( $query );
			$uam->collection( 'uam_accessgroup_to_object_groups' )->import_array( $results );

			$query = sprintf( "SELECT * FROM `%suam_accessgroups` WHERE `ID` IN ('%s')",
				$wpdb->prefix,
				implode( "','", $group_ids )
			);
			$results = $wpdb->get_results( $query );

			foreach( $results as $result )
				$uam->collection( 'uam_accessgroups' )->set( $result->ID, $result );
		}

		$this->debug( 'User Access Manager data for this post: %s', $uam );
	}
}
