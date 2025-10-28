<?php

namespace threewp_broadcast\premium_pack\dear_flipbook;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/3d-flipbook-dflip-lite/">Dear Flipbook</a> plugin.
	@plugin_group		3rd party compatability
	@since				2025-05-14 22:36:33
**/
class Dear_Flipbook
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		* @since		2025-05-14 22:37:24
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );

		new Shortcode();
	}

	/**
	 * threewp_broadcast_broadcasting_before_restore_current_blog
	 *
	 * @since		2025-05-14 22:55:16
	 **/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( ! isset( $bcd->dear_flipbook ) )
			return;

		$meta_key = '_dflip_data';
		$data = $bcd->custom_fields()->get_single( $meta_key );
		$data = maybe_unserialize( $data );

		foreach( $bcd->dear_flipbook->collection( 'urls' ) as $key => $old_id )
		{
			$new_id = $bcd->copied_attachments()->get( $old_id );
			$new_url = wp_get_attachment_url( $new_id );
			$this->debug( 'Replacing %s with %s',
				$key,
				$new_url,
			);
			$data[ $key ] = $new_url;
		}

		$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $data );
	}

	/**
	 * threewp_broadcast_broadcasting_started
	 *
	 * @since		2025-05-14 22:49:02
	 **/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience

		if ( $bcd->post->post_type != 'dflip' )
			return;

		$data = $bcd->custom_fields()->get_single( '_dflip_data' );
		$data = maybe_unserialize( $data );

		$bcd->dear_flipbook = ThreeWP_Broadcast()->collection();

		foreach( [ 'pdf_source', 'pdf_thumb' ] as $key )
		{
			$url = $data[ $key ];
			$attachment_id = attachment_url_to_postid( $url );

			$result = $bcd->try_add_attachment( $attachment_id );
			if ( $result )
			{
				$this->debug( 'Added %s as ID %s', $key, $attachment_id );
				$bcd->dear_flipbook->collection( 'urls' )
					->set( $key, $attachment_id );
			}
		}
	}

	/**
		* @brief		Add our post types.
		* @since		2025-05-14 22:48:21
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'dflip' );
	}

}
