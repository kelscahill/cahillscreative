<?php

namespace threewp_broadcast\premium_pack\imagify;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/imagify/">Imagify</a> plugin / service.
	@plugin_group		3rd party compatability
	@since				2021-04-25 17:08:59
**/
class Imagify
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2021-04-25 17:09:57
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_apply_existing_attachment_action' );
		$this->add_action( 'threewp_broadcast_copy_attachment', 100 );		// Let the others copy it first.
	}

	/**
		@brief		threewp_broadcast_apply_existing_attachment_action
		@since		2021-04-25 17:16:33
	**/
	public function threewp_broadcast_apply_existing_attachment_action( $action )
	{
		$this->copy_attachment_webp( $action->source_attachment );
	}

	/**
		@brief		threewp_broadcast_copy_attachment
		@since		2021-04-25 17:18:10
	**/
	public function threewp_broadcast_copy_attachment( $action )
	{
		$this->copy_attachment_webp( $action->attachment_data );
	}

	/**
		@brief		Copy all of the webps we find for this attachment.
		@since		2021-04-25 17:17:20
	**/
	public function copy_attachment_webp( $attachment_data )
	{
		$upload_dir = wp_upload_dir();
		$source = $attachment_data->filename_path;
		$target = $upload_dir[ 'path' ] . '/' . $attachment_data->filename_base;

		$source_webp = $source . '.webp';
		if ( file_exists( $source_webp ) )
		{
			$target_webp = $target . '.webp';
			$this->debug( 'Copying %s to %s', $source_webp, $target_webp );
			copy( $source_webp, $target_webp );
		}
		else
			$this->debug( '%s does not exist.', $source_webp );

		if ( $attachment_data->file_metadata )
		{
			$this->debug( 'Handling metadata.' );
			$metadata = $attachment_data->file_metadata;
			$source_dir = dirname( $source );
			$target_dir = dirname( $target );
			if ( isset( $metadata[ 'sizes' ] ) )
			{
				foreach( $metadata[ 'sizes' ] as $data )
				{
					if ( ! isset( $data[ 'file' ] ) )
						continue;
					$filename = $data[ 'file' ];
					$source_file = $source_dir . '/' . $filename . '.webp';
					$target_file = $target_dir . '/' . $filename . '.webp';
					if  ( ! file_exists( $source_file ) )
						continue;
					$this->debug( 'Copying %s to %s', $source_file, $target_file );
					copy( $source_file, $target_file );
				}
			}

		}
	}
}
