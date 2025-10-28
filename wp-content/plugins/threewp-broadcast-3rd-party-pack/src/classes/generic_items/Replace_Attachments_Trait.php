<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

use WP_Query;

/**
	@brief		Replace attachment IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Attachments_Trait
{
	/**
		@brief		Add the attachment(s).
		@since		2016-07-14 13:41:10
	**/
	public function parse_find( $bcd, $find )
	{
		foreach( $find->value as $attribute => $old_id )
		{
			$id = intval( $old_id );

			if ( $id < 1 )
			{
				// This could be a url. Try and find the equivalent ID.
				$post_id = $this->get_attachment_id_from_url( $old_id );
				if ( $post_id > 0 )
				{
					$id = $post_id;
					$find->collection( 'old_id_urls' )
						->set( $old_id, $id );
					$this->debug( 'Image ID %s is an attachment: %s', $old_id, $id );
				}
			}

			if ( $bcd->try_add_attachment( $id ) )
				$this->debug( 'Adding single attachment %s', $id );
			else
				$this->debug( 'Unable to add single image %s', $id );
		}

		foreach( $find->values as $attribute => $array )
		{
			foreach( $array as $ids )
			{
				if ( is_array( $ids ) )
				{
					// An exploded array was found.
					foreach( $ids as $id )
						if ( $bcd->try_add_attachment( intval( $id ) ) )
							$this->debug( 'Adding one of several attachments %s', $id );
						else
							$this->debug( 'Unable to add image %s from several.', $id );
				}
				else
				{
					// An array of single values was found.
					if ( $bcd->try_add_attachment( intval( $ids ) ) )
						$this->debug( 'Adding one of several single attachments %s', $ids );
					else
						$this->debug( 'Unable to add image %s', $ids );
				}
			}
		}
	}

	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		$as_url = intval( $old_id ) < 1;

		if ( $as_url )
		{
			if ( ! $find )		// Workaround for custom field attachments.
			{
				$cfa_collection = $broadcasting_data->custom_field_attachments;
				$url = $cfa_collection->collection( 'urls' )->get( $old_id );
				$old_id = $url;
			}
			else
			{
				$old_id = $find->collection( 'old_id_urls' )
					->get( $old_id );
			}
			$new_attachment = $broadcasting_data->copied_attachments()->get_attachment( $old_id );
			$new_id = wp_get_attachment_url( $new_attachment->ID );
			$this->debug( "Retrieved URL %s from %s", $new_id, $old_id );
		}
		else
		{
			$new_id = $broadcasting_data->copied_attachments()->get( $old_id );
			if ( $new_id < 1 )
				$new_id = 0;
		}

		$this->debug( 'Replacing attachment %s with %s', $old_id, $new_id );
		return $new_id;
	}

	/**
		@brief		Find an attachment ID from its URL.
		@see		https://wpscholar.com/blog/get-attachment-id-from-wp-image-url/
		@since		2022-09-29 19:24:06
	**/
	public function get_attachment_id_from_url( $url )
	{

		$attachment_id = 0;

		$dir = wp_upload_dir();

		if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
			$file = basename( $url );

			$query_args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'value'   => $file,
						'compare' => 'LIKE',
						'key'     => '_wp_attachment_metadata',
					),
				)
			);

			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {

				foreach ( $query->posts as $post_id ) {

					$meta = wp_get_attachment_metadata( $post_id );

					$original_file       = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );

					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_id = $post_id;
						break;
					}

				}

			}

		}

		return $attachment_id;
	}
}
