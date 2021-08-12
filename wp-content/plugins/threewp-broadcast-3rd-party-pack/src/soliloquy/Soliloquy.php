<?php

namespace threewp_broadcast\premium_pack\soliloquy;

/**
	@brief			Adds support for the <a href="https://soliloquywp.com/">Soliloquy plugin</a>.
	@plugin_group	3rd party compatability
	@since			2020-07-30 09:57:42
**/
class Soliloquy
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2020-07-30 09:59:15
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		new Soliloquy_Shortcode();
	}

	/**
		@brief		Parse the data.
		@since		2020-07-30 09:59:24
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'soliloquy' )
			return;

		$key = '_sol_in_slider';
		$sol_in_slider = $bcd->custom_fields()->get_single( $key );
		$sol_in_slider = maybe_unserialize( $sol_in_slider );
		if ( $sol_in_slider )
		{
			$new_ids = [];
			foreach ( $sol_in_slider as $old_id )
				$new_ids []= $bcd->copied_attachments()->get( $old_id );

			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $new_ids );
		}

		$key = '_sol_slider_data';
		$data = $bcd->custom_fields()->get_single( $key );
		$data = maybe_unserialize( $data );
		if ( $data )
		{
			$new_slider = [];
			foreach( $data[ 'slider' ] as $old_id => $old_data )
			{
				$new_id = $bcd->copied_attachments()->get( $old_id );
				$new_data = $old_data;
				$new_data[ 'id' ] = $new_id;
				$new_data[ 'src' ] = wp_get_attachment_url( $new_id );
				$new_slider[ $new_id ] = $new_data;
			}

			$new_data = $data;
			$new_data[ 'slider' ] = $new_slider;
			$new_data[ 'id' ] = $bcd->new_post( 'ID' );

			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $new_data );
		}
	}

	/**
		@brief		Preparse the data.
		@since		2020-07-30 09:59:24
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		// Does this page have beaver info?
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'soliloquy' )
			return;

		// Parse the image IDs
		$key = '_sol_in_slider';
		$data = $bcd->custom_fields()->get_single( $key );
		$data = maybe_unserialize( $data );
		if ( $data )
		{
			foreach ( $data as $old_id )
				$bcd->try_add_attachment( $old_id );
		}

	}
}
