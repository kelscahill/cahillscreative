<?php

namespace threewp_broadcast\premium_pack\vimeography;

use Exception;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/vimeography/">Vimeography</a> plugin.
	@plugin_group	3rd party compatability
	@since			2020-02-06 22:55:44
**/
class Vimeography
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		// Vimeography is not multisite aware, so it doesn't keep track of the prefix.
		$table = $wpdb->prefix . 'vimeography_gallery';

		// Not installed on this blog? ID 0.
		if ( ! $this->database_table_exists( $table ) )
			return 0;

		$id = $item->id;
		$v = $bcd->vimeography;

		$row = $v->collection( 'gallery' )->get( $id );
		$title = $row->title;
		$row = (array) $row;
		unset( $row[ 'id' ] );

		$query = sprintf( "SELECT * FROM `%s` WHERE `title` = '%s'", $table, $title );
		$this->debug( $query );
		$gallery = $wpdb->get_row( $query );
		if ( ! $gallery )
		{
			$wpdb->insert( $table, $row );
			$new_gallery_id = $wpdb->insert_id;
			$this->debug( 'Created gallery %s', $new_gallery_id );
		}
		else
			$new_gallery_id = $gallery->id;

		// Update the existing row.
		$this->debug( 'Updating gallery %s', $new_gallery_id );
		$wpdb->update( $table, $row, [ 'id' => $new_gallery_id ] );

		// Delete existing meta.
		$table = $wpdb->prefix . 'vimeography_gallery_meta';
		$query = sprintf( "DELETE FROM `%s` WHERE `gallery_id` = '%s'", $table, $new_gallery_id );
		$this->debug( $query );
		$wpdb->get_results( $query );

		// Insert the meta.
		$metas = $v->collection( 'gallery_meta' )->get( $id );
		foreach( $metas as $meta )
		{
			$meta = (array) $meta;
			unset( $meta[ 'id' ] );
			$meta[ 'gallery_id' ] = $new_gallery_id;
			$this->debug( 'Inserting gallery meta: %s', $meta );
			$wpdb->insert( $table, $meta );
		}

		return $new_gallery_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'vimeography';
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		// Find the vimeography item with this id.
		$id = $item->id;

		if ( ! isset( $bcd->vimeography ) )
			$bcd->vimeography = ThreeWP_Broadcast()->collection();

		// Conv
		$v = $bcd->vimeography;

		// Fetch the gallery from the db.
		global $wpdb;

		$table = $wpdb->vimeography_gallery;
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $id );
		$this->debug( $query );
		$gallery = $wpdb->get_row( $query );

		if ( ! $gallery )
			throw new Exception( sprintf( 'Gallery %s does not exist.', $id ) );

		$v->collection( 'gallery' )
			->set( $id, $gallery );

		// Retrieve any meta.
		$table = $wpdb->vimeography_gallery_meta;
		$query = sprintf( "SELECT * FROM `%s` WHERE `gallery_id` = '%s'", $table, $id );
		$this->debug( $query );
		$rows = $wpdb->get_results( $query );
		$v->collection( 'gallery_meta' )
			->set( $id, $rows );
	}
}
