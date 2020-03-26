<?php

namespace threewp_broadcast\premium_pack\download_monitor;

use threewp_broadcast\attachment_data;

/**
	@brief			Adds support for downloads and shortcodes from <a href="https://wordpress.org/plugins/download-monitor/">Never5's Download Monitor</a> plugin.
	@plugin_group	3rd party compatability
	@since			2016-03-30 09:59:06
**/
class Download_Monitor
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_copy_attachment', 'threewp_broadcast_copy_attachment_pre' );
		$this->add_action( 'threewp_broadcast_copy_attachment', 'threewp_broadcast_copy_attachment_post', 100 );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-10-18 16:16:10
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$dlm = $bcd->download_monitor;

		if ( $bcd->post->post_type == 'dlm_download_version' )
		{
			// Update the files array, replacing the URL of each attachment with the local copy, if possible.

			// First some sanity checking.
			$cf = $bcd->custom_fields()->child_fields();
			if ( ! $cf->has( '_files' ) )
				return $this->debug( 'No _files custom field.' );

			$files = $cf->get( '_files' );
			$files = reset( $files );
			$files = json_decode( $files );
			if ( ! is_array( $files ) )
				return $this->debug( 'Warning: _files custom field is invalid!' );

			// Now we can begin working.
			global $wpdb;
			$modified = false;

			foreach( $files as $index => $filename )
			{
				$attachment_data = $dlm->collection( 'download_version_attachments' )->get( $filename );
				if ( ! $attachment_data )
				{
					$this->debug( 'No attachment data found for file %s', $filename );
					continue;
				}

				// Try to find the equivalent attachment on this blog.
				$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_name` = '%s' AND `post_type` = 'attachment'",
					$wpdb->posts,
					$attachment_data->post->post_name
				);
				$result = $wpdb->get_row( $query );

				if ( ! $result )
				{
					$this->debug( 'No equivalent attachment found for %s', $filename );
					continue;
				}

				$new_attachment_data = attachment_data::from_attachment_id( $result->ID );

				$new_filename = $new_attachment_data->post->guid;
				$this->debug( 'Replacing %s with %s', $filename, $new_filename );
				$modified = true;
				$files[ $index ] = $new_filename;
			}

			if ( ! $modified )
				return $this->debug( '_files not modified.' );

			$new_value = json_encode( $files );
			$this->debug( 'Updating _files with %s', $new_value );
			$cf->update_meta( '_files', $new_value );
		}
	}

	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		if ( ! isset( $bcd->download_monitor ) )
			return;

		$dlm = $bcd->download_monitor;

		if ( $dlm->has( 'download_versions' ) )
		{
			$existing_versions = get_posts( [
				'post_type' => 'dlm_download_version',
				'post_parent' => $bcd->new_post( 'ID' ),
				'posts_per_page' => -1,
			] );
			$existing_versions = $this->array_rekey( $existing_versions, 'ID' );
			$this->debug( 'The download on this blog already has the following %s existing versions: %s',
				count( $existing_versions ),
				implode( ', ', array_keys( $existing_versions ) )
			);

			// Allow Download Manager to modify the upload_dir so that the copied files get put in the right place.
			$_POST[ 'type' ] = 'dlm_download';

			$versions = $dlm->get( 'download_versions' );
			$new_versions = [];
			foreach( $versions as $version )
			{
				$this->debug( 'Broadcasting download version %s.', $version->ID );

				switch_to_blog( $bcd->parent_blog_id );
				$download_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $version->ID, [ $bcd->current_child_blog_id ] );
				restore_current_blog();
				$new_id = $download_bcd->new_post( 'ID' );
				$new_versions []= $new_id;

				// We have now seen this version.
				unset( $existing_versions[ $new_id ] );

				// Fix the post parent.
				$data = [
					'ID' => $new_id,
					'post_parent' => $bcd->new_post( 'ID' ),
					'post_name' => sprintf( 'download-%s-file-version', $bcd->new_post( 'ID' ) ),
					'post_title' => sprintf( 'Download #%s File Version', $bcd->new_post( 'ID' ) ),
				];
				$this->debug( 'Updating version post: %s', $data );
				wp_update_post( $data );
			}

			unset( $_POST[ 'type' ] );

			// Delete all existing versions we have not seen. We regard these as orphans.
			foreach( $existing_versions as $post_id => $ignore )
			{
				$this->debug( 'Deleting existing, unknown download version %s', $post_id );
				wp_delete_post( $post_id );
			}

			// Update the transient.
			$key = sprintf( 'dlm_file_version_ids_%s', $bcd->new_post( 'ID' ) );
			$this->debug( 'Updating file version transient %s with %s', $key, $new_versions );
			set_transient( $key, $new_versions, YEAR_IN_SECONDS );
		}
	}

	/**
		@brief		Clean up the _POST after copying the attachment.
		@since		2016-10-12 22:03:19
	**/
	public function threewp_broadcast_copy_attachment_post( $action )
	{
		if ( ! isset( $this->__post_set ) )
			return;
		$this->debug( 'Unsetting POST type.' );
		if ( ! $this->__post_set )
			unset( $_POST[ 'type' ] );
		else
			$_POST[ 'type' ] = $this->__post_set;
		unset( $this->__post_set );
	}

	/**
		@brief		If copying a dlm_download, put it in the right directory.
		@since		2016-10-12 22:03:19
	**/
	public function threewp_broadcast_copy_attachment_pre( $action )
	{
		if ( isset( $_POST[ 'type' ] ) AND $_POST[ 'type' ] == 'dlm_download' )
			return;

		$attachment_data = $action->attachment_data;
		if ( strpos( $attachment_data->filename_path, 'dlm_uploads' ) === false )
			return;

		$this->debug( 'Setting POST type to dlm_download.' );
		if ( isset( $_POST[ 'type' ] ) )
			$this->__post_set = $_POST[ 'type' ];
		else
			$this->__post_set = false;
		$_POST[ 'type' ] = 'dlm_download';
	}

	/**
		@brief		Restore any shortcodes.
		@since		2016-06-08 16:04:21
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( ! isset( $bcd->download_monitor_preparse ) )
			return;

		$shortcodes = $bcd->download_monitor_preparse->get( $action->id );

		if ( ! $shortcodes )
			return;

		foreach( $shortcodes as $shortcode => $data )
		{
			$this->debug( 'Broadcasting download %s from shortcode %s in content %s', $data->id, $data->shortcode, $action->id );
			switch_to_blog( $bcd->parent_blog_id );
			// Broadcast the download.
			$download_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $data->id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();
			$new_id = $download_bcd->new_post( 'ID' );

			// And replace the shortcode(s).
			$new_shortcode = str_replace( $data->id, $new_id, $data->shortcode );
			$this->debug( 'New download ID is %s. New shortcode is: %s', $new_id, $new_shortcode );
			$action->content = str_replace( $data->shortcode, $new_shortcode, $action->content );
		}
	}

	/**
		@brief		Handle shortcodes in content.
		@since		2016-06-08 15:58:21
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;

		if ( ! isset( $bcd->download_monitor_preparse ) )
			$bcd->download_monitor_preparse = ThreeWP_Broadcast()->collection();

		$dlm = ThreeWP_Broadcast()->collection();

		$matches = ThreeWP_Broadcast()->find_shortcodes( $content, [ 'download' ] );
		$this->debug( '%s shortcodes found in content %s', count( $matches[ 0 ] ), $action->id );
		if ( count( $matches[ 0 ] ) < 1 )
			return;

		foreach( $matches[ 0 ] as $index => $shortcode )
		{
			$attributes = shortcode_parse_atts( $matches[ 3 ][ $index ] );

			if ( ! isset( $attributes[ 'id' ] ) )
			{
				$this->debug( 'Shortcode %s has no ID. Ignoring.', $shortcode );
				continue;
			}

			$download = (object)[];
			$download->shortcode = $shortcode;
			$download->id = $attributes[ 'id' ];
			$this->debug( 'Saving shortcode %s with ID %s', $shortcode, $download->id );
			$dlm->collection( 'shortcodes' )->set( $shortcode, $download );
		}

		$bcd->download_monitor_preparse->set( $action->id, $dlm->collection( 'shortcodes' ) );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return;

		$bcd = $action->broadcasting_data;		// Convenience.

		$dlm = ThreeWP_Broadcast()->collection();
		$bcd->download_monitor = $dlm;

		if ( $bcd->post->post_type == 'dlm_download' )
		{
			// Find all child posts.
			global $wpdb;
			$query = sprintf( "SELECT * FROM `%s` WHERE `post_parent` = %s AND `post_type` = 'dlm_download_version'",
				$wpdb->posts,
				$bcd->post->ID
			);
			$versions = $wpdb->get_results( $query );
			$this->debug( 'Saving download versions: %s', $versions );
			$dlm->set( 'download_versions', $versions );
		}

		if ( $bcd->post->post_type == 'dlm_download_version' )
		{
			// We need to save the attachment data of the files in order to replace the URLs on each child blog.

			$files = $bcd->custom_fields()->get_single( '_files' );
			$files = json_decode( $files );

			if ( ! is_array( $files ) )
				return;

			global $wpdb;

			foreach( $files as $filename )
			{
				$attachment_id = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $filename ) );
				if ( ! is_object( $attachment_id ) )
				{
					$this->debug( 'Could not find the attachment for %s', $filename );
					continue;
				}

				$attachment_id = $attachment_id->ID;

				$this->debug( 'Found attachment ID %s for %s', $attachment_id, $filename );

				$attachment_data = attachment_data::from_attachment_id( $attachment_id );
				$dlm->collection( 'download_version_attachments' )->set( $filename, $attachment_data );
			}
		}
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2016-03-30 10:06:43
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'dlm_download' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		requirement_fulfilled
		@since		2016-06-08 15:55:30
	**/
	public function requirement_fulfilled()
	{
		return defined( 'DLM_VERSION' );
	}

}
