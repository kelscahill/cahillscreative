<?php

namespace threewp_broadcast\premium_pack\classes;

use Exception;

/**
	@brief		Handles things related to blogs.
	@since		2020-03-12 16:08:47
**/
trait blogs_trait
{
	use files_trait;

	/**
		@brief		Change the ID of a blog if possible.
		@since		2020-03-12 16:08:54
	**/
	public function change_blog_id( $old_id, $new_id )
	{
		if ( $old_id == 1 )
			throw new Exception( 'Blog 1 may not be changed.' );

		if ( $new_id == 1 )
			throw new Exception( 'Blog 1 may not be changed.' );

		// The old ID should not exist.
		if ( ! ThreeWP_Broadcast()->blog_exists( $old_id ) )
			throw new Exception( sprintf( 'Old blog %s does not exist.', $old_id ) );

		// The new ID should not exist.
		if ( ThreeWP_Broadcast()->blog_exists( $new_id ) )
			throw new Exception( sprintf( 'New blog %s already exists.', $new_id ) );

		// Ready for rename.
		$this->debug( 'Changing blog ID from %s to %s', $old_id, $new_id );

		global $wpdb;

		$query = sprintf( "UPDATE `%s` SET `blog_id` = '%s' WHERE `blog_id` = '%s'", $wpdb->blogs, $new_id, $old_id );
		$this->debug( $query );
		$wpdb->query( $query );

		// Rename all tables.
		$old_blog_prefix = $wpdb->base_prefix . $old_id . '_';
		$new_blog_prefix = $wpdb->base_prefix . $new_id . '_';
		$tables = $wpdb->get_results( "SHOW TABLES LIKE '${old_blog_prefix}%'", ARRAY_N );
		foreach( $tables as $table )
		{
			$table = reset( $table );

			// Make a check for the exact table name, to ensure that we are only copying those tables from this blog. 12_ 123_
			if ( strpos( $table, $old_blog_prefix ) === false )
				continue;

			$old_table = $table;
			$new_table = str_replace( $old_blog_prefix, $new_blog_prefix, $table );

			// Does this table exist?
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$new_table'" ) == $new_table )
			{
				$this->debug( 'Table %s already exists. Replacing.', $new_table );
				$query = "DROP TABLE `$new_table`";
				$wpdb->get_results( $query );
			}

			$query = "ALTER TABLE `$old_table` RENAME `$new_table`";
			$this->debug( $query );
			$wpdb->get_results( $query );
		}

		clean_blog_cache( $old_id );
		clean_blog_cache( $new_id );

		// Fix options table.

		// Update user roles.
		$query = sprintf( "UPDATE `%soptions` SET `option_name` = '%suser_roles' WHERE `option_name` = '%suser_roles'",
			$new_blog_prefix,
			$new_blog_prefix,
			$old_blog_prefix
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Rename upload dir.
		switch_to_blog( $old_id );
		$source_upload_dir = wp_upload_dir();
		restore_current_blog();

		switch_to_blog( $new_id );
		$target_upload_dir = wp_upload_dir();
		restore_current_blog();

		$source_dir = $source_upload_dir[ 'basedir' ];
		$target_dir = $target_upload_dir[ 'basedir' ];

		$this->debug( 'Copying %s to %s', $source_dir, $target_dir );
		static::copy_recursive( $source_dir, $target_dir );

		$this->debug( 'Deleting directory %s', $source_dir );
		static::delete_recursive( $source_dir );
	}
}
