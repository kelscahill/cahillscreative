<?php

namespace threewp_broadcast\premium_pack\visual_composer;

use DOMDocument;

/**
	@brief			Adds support for the <a href="https://visualcomposer.com/">Visual Composer</a> page builder.
	@plugin_group	3rd party compatability
	@since			2022-02-03 17:22:37
**/
class Visual_Composer
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Find an image ID by its url.
		@see		https://dzone.com/articles/get-wordpress-image-id-by-url
		@since		2022-02-03 21:54:43
	**/
	public static function get_image_by_url( $url )
	{
		global $wpdb;

		// If the URL is auto-generated thumbnail, remove the sizes and get the URL of the original image
		$url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url );

		$image = $wpdb->get_col($wpdb->prepare("SELECT ID FROM `$wpdb->posts` WHERE guid='%s';", $url ));

		if(!empty($image))
			return $image[0];

		return false;
	}

	/**
		@brief		Prepare the BCD object.
		@since		2022-02-03 22:06:41
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->visual_composer ) )
			$bcd->visual_composer = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Replace the keywords in the content.
		@since		2022-02-03 22:06:41
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		if ( ! isset( $bcd->visual_composer ) )
			return;

		// Handle single images.
		foreach( $bcd->visual_composer->collection( 'img' )->collection( $action->id ) as $image_id => $image_file )
		{
			$new_attachment = $bcd->copied_attachments()->get_attachment( $image_id );

			// Replace the basename.
			$new_file = $new_attachment->attachment_data->file_metadata[ 'file' ];
			$this->debug( 'Replacing %s with /%s in %s.', $image_file, $new_file, $action->id );
			$content = str_replace( $image_file, '/' . $new_file, $content );

			// And now replace all the sizes.
			foreach( $new_attachment->attachment_data->file_metadata[ 'sizes' ] as $size_data )
			{
				$new_file = '/' . $size_data[ 'file' ];
				$this->debug( 'Replacing %s with %s in %s.', $image_file, $new_file, $action->id );
				$content = str_replace( $image_file, $new_file, $content );
			}

		}

		$action->content = $content;
	}

	/**
		@brief		Preparse the content.
		@since		2017-03-06 19:40:01
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;

		$bcd = $action->broadcasting_data;
		$vcvuploadurl = '|!|vcvUploadUrl|!|';

		// Look for Visual Composer things.
		if ( strpos( $content, $vcvuploadurl ) === false )
			return;

		$this->prepare_bcd( $bcd );

		// Create a DOMDocument.
		$html = new DOMDocument;
		// Use some charset meta to help the domdocument parse the content.
		$html_meta = '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
		// @ because sometimes HTML is badly formed.
		@$html->loadHTML( $html_meta . $content );

		// Handle image elements.
		$elements = $html->getElementsByTagName( 'img' );
		$wp_upload_dir = wp_upload_dir();
		$base_url = $wp_upload_dir[ 'baseurl' ];

		$image_urls = [];

		// images with data-img-src attribute.
		foreach( $elements as $element )
		{
			// <img class="vce-single-image vcv-lozad" data-src="|!|vcvUploadUrl|!|/2022/02/bn1-768x1024.jpg" width="768" height="1024"
			//	srcset="|!|vcvUploadUrl|!|/2022/02/bn1-320x427.jpg 320w,|!|vcvUploadUrl|!|/2022/02/bn1-480x640.jpg 480w,|!|vcvUploadUrl|!|/2022/02/bn1-800x1067.jpg 800w,|!|vcvUploadUrl|!|/2022/02/bn1-768x1024.jpg 768w,|!|vcvUploadUrl|!|/2022/02/bn1-1536x2048.jpg 2x"
			//	src="" data-img-src="|!|vcvUploadUrl|!|/2022/02/bn1.jpg" alt="" title="bn1" />
			$class = $element->getAttribute( 'class' );
			if ( strpos( $class, 'vce-single-image' ) !== false )
			{
				foreach( [
					'src',
					'data-src',
				] as $key )
				{
					$url = $element->getAttribute( $key );
					$image_file = str_replace( $vcvuploadurl, '', $url );
					$url = str_replace( $vcvuploadurl, $base_url, $url );

					if ( ! $url )
						continue;
					$image_urls []= $url;
				}
			}
		}

		foreach( $image_urls as $url )
		{
			$this->debug( 'Looking for %s', $url );

			// Try to find the post for this url.
			$image_id = static::get_image_by_url( $url );

			if ( ! $image_id )
				continue;

			if ( $bcd->try_add_attachment( $image_id ) )
				$this->debug( 'Added WP image %s from: <em>%s</em>', $image_id, htmlspecialchars( $url ) );
			$find_data = (object)[];
			$find_data->content_id = $action->id;
			$bcd->visual_composer
				->collection( 'img' )
				->collection( $action->id )
				->set( $image_id, $image_file );
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2022-02-03 18:38:40
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
	}
}
