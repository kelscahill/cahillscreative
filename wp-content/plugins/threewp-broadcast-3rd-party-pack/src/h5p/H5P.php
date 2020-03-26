<?php

namespace threewp_broadcast\premium_pack\h5p;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/h5p/">H5P Interactive Content</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-05-01 18:38:26
**/
class H5P
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		$this->prepare_bcd( $bcd );

		$table = $this->h5p_table( 'h5p_contents' );
		$this->database_table_must_exist( $table );

		// Check for an existing row.
		$content_row = $bcd->h5p->collection( 'contents' )->get( $item->id );
		$slug = $content_row->slug;

		$this->debug( 'Trying to find a row with the slug %s', $slug );

		$query = sprintf( "SELECT * FROM `%s` WHERE `slug` = '%s'",
			$table,
			$slug
		);

		$this->debug( $query );
		$result = $wpdb->get_row( $query );

		if ( ! $result )
		{
			$content_row = (array) $content_row;
			unset( $content_row[ 'id' ] );

			// Find the equivalent library ID.
			$library_id = $content_row[ 'library_id' ];
			$library = $bcd->h5p->collection( 'libraries' )
				->get( $library_id );

			$library_name = $library->name;

			$libraries_table = $this->h5p_table( 'h5p_libraries' );
			$this->database_table_must_exist( $libraries_table );
			$query = sprintf( "SELECT * FROM `%s` WHERE `name` = '%s'",
				$libraries_table,
				$library_name
			);
			$this->debug( $query );
			$libary_row = $wpdb->get_row( $query );

			if ( ! $libary_row )
				wp_die( sprintf( 'Unable to find the library %s in %s!', $library_name, $libraries_table ) );

			$libary_id = $libary_row->id;
			$content_row[ 'library_id' ] = $libary_id;
			$this->debug( 'Inserting row %s', $content_row );
			$wpdb->insert( $table, $content_row );
			$new_row_id = $wpdb->insert_id;
		}
		else
		{
			$new_row_id = $result->id;
			// Update the data
			$this->debug( 'Updating H5P row %s.', $new_row_id );
			$wpdb->update( $table, [
				'parameters' => $content_row->parameters,
				'filtered' => $content_row->filtered,
			], [ 'id' => $new_row_id ] );
		}

		// Copy all files associated to this content.
		$upload_dir = wp_upload_dir();
		$path = sprintf( "%s/h5p/content/%s", $upload_dir[ 'basedir' ], $new_row_id );
		$this->recursive_copy( $bcd->h5p->get( 'recursive_copy' ), $path );

		return $new_row_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'h5p';
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		$table = $this->h5p_table( 'h5p_contents' );
		$this->database_table_must_exist( $table );

		$this->prepare_bcd( $bcd );

		// Save the database row.
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%d'",
			$table,
			$item->id
		);
		$row = $wpdb->get_row( $query );
		$this->debug( htmlspecialchars( json_encode( $row ) ) );

		$bcd->h5p->collection( 'contents' )
			->set( $item->id, $row );

		// Remember the library
		$table = $this->h5p_table( 'h5p_libraries' );
		$this->database_table_must_exist( $table );
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%d'",
			$table,
			$row->library_id
		);
		$row = $wpdb->get_row( $query );
		$this->debug( json_encode( $row ) );

		$bcd->h5p->collection( 'libraries' )
			->set( $row->id, $row );

		// Remember this path for later.
		$path = sprintf( "%s/h5p/content/%s", $bcd->upload_dir[ 'basedir' ], $item->id );
		$bcd->h5p->set( 'recursive_copy', $path );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Glob recursively.
		@since		2018-05-01 19:19:52
	**/
	public function glob_recursive( $paths )
	{
		$r = [];
		foreach( $paths as $filename )
		{
			if ( is_dir( $filename ) )
				$r += glob( $filename . '/*' );
			if ( is_file( $filename ) )
				$r []= $filename;
		}
		return $r;
	}

	/**
		@brief		Return the table name on this blog.
		@since		2017-11-22 19:38:53
	**/
	public function h5p_table( $name )
	{
		global $wpdb;
		return sprintf( '%s%s', $wpdb->prefix, $name );
	}

	public function prepare_bcd( $bcd )
	{
		if ( isset( $bcd->h5p ) )
			return;
		$bcd->h5p = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Recursively copy a directory.
		@since		2018-05-01 19:24:11
	**/
	public function recursive_copy( $source, $destination )
	{
		if ( ! is_dir( $destination ) )
			mkdir( $destination, 0755, true );

		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ), \RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $iterator as $item )
		{
			if ( $item->isDir() )
			{
				$dir = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
				$this->debug( 'Creating directory %s', $dir );
				mkdir( $dir );
			}
			else
			{
				$target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
				copy( $item, $target );
				$this->debug( 'Copying %s to %s', $item, $target );
			}
		}
	}
}
