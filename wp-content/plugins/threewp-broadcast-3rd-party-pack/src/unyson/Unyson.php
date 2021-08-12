<?php

namespace threewp_broadcast\premium_pack\unyson;

use threewp_broadcast\actions;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/unyson/">Unyson page builder plugin</a>.
	@plugin_group	3rd party compatability
	@since			2017-10-16 20:52:21
**/
class Unyson
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The key for the json custom field.
		@since		2017-10-16 21:38:26
	**/
	public static $json_key = 'fw:opt:ext:pb:page-builder:json';

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		Parse the builder blocks.
		@since		2017-06-30 00:19:34
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->unyson ) )
			return;

		$data = $bcd->unyson->get( 'data' );

		$this->parse_data( $data );

		$this->debug( 'New Unyson data: %s', $data );

		$bcd->custom_fields()
			->child_fields()
			->update_meta_json( static::$json_key, json_encode( $data ) );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-06-30 00:09:50
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		// Does this page have beaver info?
		$bcd = $action->broadcasting_data;

		$data = $bcd->custom_fields()->get_single( static::$json_key );
		if ( ! $data )
			return;

		$bcd->unyson = ThreeWP_Broadcast()->collection();

		$this->__bcd = $bcd;
		$data = json_decode( $data );

		$bcd->unyson->set( 'data', $data );

		$this->debug( 'The Unyson page builder data is: %s', $data );

		$this->preparse_data( $data );
	}

	/**
		@brief		Parse the data and replace things.
		@since		2017-10-16 21:31:42
	**/
	public function parse_data( $data )
	{
		if ( is_array( $data ) )
			foreach( $data as $index => $ignore )
				$this->parse_data( $data[ $index ] );
		if ( is_object( $data ) )
		{
			// Attachment ID
			if ( isset( $data->attachment_id ) )
			{
				$new_attachment_id = $this->__bcd->copied_attachments()->get( $data->attachment_id );
				$new_attachment = $this->__bcd->copied_attachments()->get_attachment( $data->attachment_id );
				$this->debug( 'Replacing attachment %d with %d.', $data->attachment_id, $new_attachment_id );
				$data->attachment_id = $new_attachment_id;
				// Fix the GUID, removing the http part.
				$guid = preg_replace( '/.*\/\//', '//', $new_attachment->guid );
				$data->url = $guid;
			}
			// Background image
			if ( isset( $data->background_image ) )
			{
				if ( isset( $data->background_image->type ) )
					if ( $data->background_image->type == 'custom' )
						if ( $data->background_image->custom > 0 )
						{
							$old_image_id = $data->background_image->custom;
							$new_attachment = $this->__bcd->copied_attachments()->get_attachment( $old_image_id );
							$this->debug( 'New background image: %d', $new_attachment->ID );
							$data->background_image->custom = $new_attachment->ID . '';
							$data->background_image->data->icon = $new_attachment->guid;
							$key = "background-image";
							$data->background_image->data->css->$key = 'url("' . $new_attachment->guid . '")';
						}
			}
			foreach( (array) $data as $key => $ignore )
				$this->parse_data( $data->$key );
		}
	}

	/**
		@brief		Recurse through the array / object.
		@since		2017-10-16 21:22:41
	**/
	public function preparse_data( $data )
	{
		if ( is_array( $data ) )
			foreach( $data as $underthing )
				$this->preparse_data( $underthing );
		if ( is_object( $data ) )
		{
			// Attachment ID
			if ( isset( $data->attachment_id ) )
			{
				if ( $this->__bcd->try_add_attachment( $data->attachment_id ) )
					$this->debug( 'Attachment found: %d', $data->attachment_id );
			}
			// Background image
			if ( isset( $data->background_image ) )
			{
				if ( isset( $data->background_image->type ) )
					if ( $data->background_image->type == 'custom' )
						if ( $data->background_image->custom > 0 )
						{
							$image_id = $data->background_image->custom;
							$this->debug( 'Background image: %d', $image_id );
							$this->__bcd->try_add_attachment( $image_id );
						}
			}
			foreach( (array) $data as $key => $underthing )
				$this->preparse_data( $data->$key );
		}
	}
}
