<?php

namespace threewp_broadcast\premium_pack\divi_builder;

use Exception;

/**
	@brief		Handle the image with a URL as source.
	@since		2023-08-18 20:18:23
**/
class et_pb_image
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		if ( ! isset( $item->id ) )
			throw new Exception( $this->debug( 'Unable to copy the item since it has no ID.' ) );

		$new_id = $bcd->copied_attachments()->get( $item->id );

		$new_url = wp_get_attachment_url( $new_id );

		return $new_url;
	}
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'et_pb_image';
	}

	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'src';
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		global $wpdb;

		$src = $item->id;
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `guid` = '%s' AND `post_type` = 'attachment'",
			$wpdb->posts,
			$src,
		);
		$this->debug( 'Looking for attachment with the guid: %s', $src );

		$results = $this->query( $query );

		if ( count( $results ) != 1 )
			throw new Exception( sprintf( 'No file found with the guid: %s', $src ) );

		$results = reset( $results );
		$post_id = $results[ 'ID' ];

		$item->id = $post_id;

		$this->debug( 'Adding attachment %s', $post_id );
		$bcd->add_attachment( $post_id );
	}
}
