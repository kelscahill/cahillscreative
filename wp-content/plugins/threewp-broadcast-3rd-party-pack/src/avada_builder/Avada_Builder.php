<?php

namespace threewp_broadcast\premium_pack\avada_builder;

/**
	@brief				Adds support for the <a href="https://avada.com/">Avada Builder</a> page builder.
	@plugin_group		3rd party compatability
	@since				2026-01-23 16:33:05
**/
class Avada_Builder
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2026-01-23 16:33:05
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2026-01-23 16:33:01
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$this->debug( 'Finding all FULL and THUMBNAIL image shortcodes.' );
		$bcd = $action->broadcasting_data;
		$matches = [];
		preg_match_all('/image_id="(\d+)\|[full|thumbnail]*"/', $bcd->post->post_content, $matches);

		if ( count( $matches[ 1 ] ) < 1 )
			return;

		foreach( $matches[ 1 ] as $index => $image_id )
			if ( $bcd->try_add_attachment( $image_id ) )
				$this->debug( 'Added WP image %s from: <em>%s</em>', $image_id, $matches[ 0 ][ $index ] );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2026-01-23 16:33:28
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$this->debug( 'Replacing all FULL and THUMBNAIL image shortcodes.' );
		$bcd = $action->broadcasting_data;
		$matches = [];
		preg_match_all('/image_id="(\d+)\|[full|thumbnail]*"/', $bcd->post->post_content, $matches);

		if ( count( $matches[ 1 ] ) < 1 )
			return;

		foreach( $matches[ 1 ] as $index => $image_id )
		{
			$new_image_id = $bcd->copied_attachments()->get( $image_id );
			$old_string = $matches[ 0 ][ $index ];
			$new_string = str_replace( $image_id, $new_image_id, $old_string );
			$this->debug( 'Replacing %s with %s', $old_string, $new_string );
			$bcd->modified_post->post_content = str_replace( $old_string, $new_string, $bcd->modified_post->post_content );
		}
	}
}
