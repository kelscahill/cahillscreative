<?php

namespace threewp_broadcast\premium_pack\fliphtml5;

/**
	@brief			Adds support for <a href="http://fliphtml5.com/">Flip HTML5</a> plugin.
	@plugin_group	3rd party compatability
	@since			2019-09-24 18:58:29
**/
class FlipHTML5
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;
	use \threewp_broadcast\premium_pack\classes\files_trait;

	/**
		@brief		Copy the item.
		@since		2019-09-24 19:01:16
	**/
	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;
		$source_table = $source_prefix . 'fliphtml5';
		$this->database_table_must_exist( $source_table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $source_table, $item->id );
		$row = $wpdb->get_row( $query );

		$parent_upload_dir = wp_upload_dir();

		restore_current_blog();

		// No form? Invalid shortcode. Too bad.
		if ( ! $row )
			return $this->debug( 'No ROW found.' );

		$target_prefix = $wpdb->prefix;

		$target_table = $target_prefix . 'fliphtml5';
		$this->database_table_must_exist( $target_table );
		// Find a form with the same name.
		$query = sprintf( "SELECT * FROM `%s` WHERE `name` = '%s'", $target_table, $row->name );
		$result = $wpdb->get_row( $query );

		if ( count( $result ) < 1 )
		{
			$columns = $this->get_database_table_columns_string( $target_table, [ 'except' => [ 'id' ] ] );
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` ='%d' )",
				$target_table,
				$columns,
				$columns,
				$source_table,
				$item->id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_row_id = $wpdb->insert_id;
			$this->debug( 'Using new row %s', $new_row_id );
		}
		else
		{
			$new_row_id = $result->id;
			$this->debug( 'Using existing row %s', $new_row_id );

			$new_data = clone( $row );
			$new_data = (array) $new_data;
			unset( $new_data[ 'id' ] );
			$this->debug( 'Setting new data: %s', $new_data );
			$wpdb->update( $target_table, $new_data, [ 'id' => $new_row_id ] );
		}

		// Copy all of the files.
		$child_upload_dir = wp_upload_dir();
		$source_dir = sprintf( '%s/fliphtml5/%s', $parent_upload_dir[ 'basedir' ], $row->id );
		$target_dir = sprintf( '%s/fliphtml5/%s', $child_upload_dir[ 'basedir' ], $new_row_id );

		$this->debug( 'Copying files from %s to %s', $source_dir, $target_dir );
		$this->copy_recursive( $source_dir, $target_dir );

		return $new_row_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'fliphtml5';
	}
}
