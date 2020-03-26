<?php

namespace threewp_broadcast\premium_pack\smartslider3;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/smart-slider-3/">Smart Slider 3</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-06-11 18:22:10
**/
class SmartSlider3
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		// Retrieve the item.
		$query = sprintf( "SELECT * FROM `%snextend2_smartslider3_sliders` WHERE `id` = '%d'", $source_prefix, $item->id );
		$item = $wpdb->get_row( $query );

		restore_current_blog();

		// No item? Invalid shortcode. Too bad.
		if ( ! $item )
			throw new \Exception( 'No item found.' );

		$target_prefix = $wpdb->prefix;

		// Find an item with the same id.
		$query = sprintf( "SELECT * FROM `%snextend2_smartslider3_sliders` WHERE `title` = '%s'", $target_prefix, $item->title );
		$this->debug( $query );
		$result = $wpdb->get_row( $query );

		if ( count( $result ) < 1 )
		{
			$columns = '`title`, `type`, `params`, `time`, `thumbnail`, `ordering`';
			$query = sprintf( "INSERT INTO `%snextend2_smartslider3_sliders` ( %s ) ( SELECT %s FROM `%snextend2_smartslider3_sliders` WHERE `id` ='%s' )",
				$target_prefix,
				$columns,
				$columns,
				$source_prefix,
				$item->id
			);
			$wpdb->get_results( $query );
			$new_item_id = $wpdb->insert_id;
			$this->debug( 'Using new item %s', $new_item_id );
		}
		else
		{
			$new_item_id = $result->id;
			$this->debug( 'Using existing item %s', $new_item_id );
			$new_data = (array)$item;
			unset( $new_data[ 'id' ] );
			$wpdb->update( $target_prefix . 'nextend2_smartslider3_sliders', $new_data, [ 'id' => $new_item_id ] );
		}

		// Delete the current slides
		$table = sprintf( '%snextend2_smartslider3_slides', $wpdb->prefix );
		$query = sprintf( "DELETE FROM `%s` WHERE `slider` = '%d'",
			$table,
			$new_item_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// And reinsert the fresh data.
		$columns = '`title`, `publish_up`, `publish_down`, `published`, `first`, `slide`, `description`, `thumbnail`, `params`, `ordering`, `generator_id`';
		$query = sprintf( "INSERT INTO `%s` ( `slider`, %s ) ( SELECT %s, %s FROM `%snextend2_smartslider3_slides` WHERE `slider` ='%d' )",
			$table,
			$columns,
			$new_item_id,
			$columns,
			$source_prefix,
			$item->id
		);
		$wpdb->get_results( $query );

		// Replace the image URLs for each slide.
		$image_urls = $bcd->smartslider3->collection( 'image_urls' );

		$query = sprintf( "SELECT `id`, `thumbnail`, `title`, `params` FROM `%s` WHERE `slider` = '%d'", $table, $new_item_id );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		foreach( $results as $result )
		{
			$params = $result->params;
			$params = json_decode( $params );

			if ( ! isset( $params->backgroundImage ) )
				continue;

			$image_url = $params->backgroundImage;

			if ( strpos( $image_url, '$upload$' ) !== 0 )
				continue;

			$image_url = str_replace( '$upload$/', '', $image_url );
			$old_image_id = $image_urls->get( $image_url );
			if ( ! $old_image_id )
				continue;

			// Get the ID of the copied image.
			$attachment = $bcd->copied_attachments()->get_attachment( $old_image_id );
			$new_image_url = $attachment->attachment_data->post_custom[ '_wp_attached_file' ];
			$new_image_url = reset( $new_image_url );
			$this->debug( 'Replacing URL %s with %s for new slide %s', $image_url, $new_image_url, $result->id );

			$params->backgroundImage = '$upload$/' . $new_image_url;

			$new_data = [];
			$new_data[ 'params' ] = json_encode( $params );

			// The thumbnail might need updating also.
			if ( strpos( $result->thumbnail, $image_url ) !== false )
				$new_data[ 'thumbnail' ] = '$upload$/' . $new_image_url;

			$wpdb->update( $table, $new_data, [ 'id' => $result->id ] );
		}

		return $new_item_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'smartslider3';
	}

	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'slider';
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		global $wpdb;

		// Look up all the slides in this slider and try to find each attachment.
		$query = sprintf( "SELECT `title`, `params` FROM `%snextend2_smartslider3_slides` WHERE `slider` = '%d'", $wpdb->prefix, $item->id );
		$results = $wpdb->get_results( $query );

		if ( ! isset( $bcd->smartslider3 ) )
			$bcd->smartslider3 = ThreeWP_Broadcast()->collection();

		foreach( $results as $result )
		{
			$params = $result->params;
			$params = json_decode( $params );
			if ( ! isset( $params->backgroundImage ) )
			{
				$this->debug( 'Slide %s has no background image.', $result->title );
				continue;
			}

			$image_url = $params->backgroundImage;

			// Is this an upload URL?
			if ( strpos( $image_url, '$upload$' ) !== 0 )
			{
				$this->debug( 'Slide %s is not an internal url: %s', $result->title, $image_url );
				continue;
			}

			// Try to find the attachment that matches this image url.
			$image_url = str_replace( '$upload$/', '', $image_url );
			$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `meta_key` = '_wp_attached_file' AND `meta_value` = '%s'", $wpdb->postmeta, $image_url );
			$row = $wpdb->get_row( $query );

			if ( ! $row )
			{
				$this->debug( 'No attachment found with filename %s for slide %s', $image_url, $result->title );
				continue;
			}

			// Found the id. Tell BCD.
			$this->debug( 'Found attachment %s for slide %s', $row->post_id, $result->title );
			$bcd->try_add_attachment( $row->post_id );

			// Not this url to quickly be able to quickly replace it when recopying the slides during copy_item.
			$bcd->smartslider3->collection( 'image_urls' )->set( $image_url, $row->post_id );
		}
	}
}
