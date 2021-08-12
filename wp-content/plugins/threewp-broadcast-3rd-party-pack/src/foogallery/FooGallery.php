<?php

namespace threewp_broadcast\premium_pack\foogallery;

/**
	@brief				Adds support for <a href="https://wordpress.org/plugins/foogallery/">FooPlugins&#8217; FooGallery</a> plugin.
	@plugin_group		3rd party compatability
	@since				2015-10-02 12:32:58
**/
class FooGallery
extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_filter( 'foogallery_metabox_sanity', 100 );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- CALLBACKS
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		foogallery_metabox_sanity
		@since		2017-06-10 01:19:08
	**/
	public function foogallery_metabox_sanity( $array )
	{
		$array[ 'foogallery' ][ 'whitelist' ] []= 'threewp_broadcast';
		return $array;
	}

	/**
		@brief		Restore the plugin's data.
		@since		2015-10-02 12:48:38
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->foogallery ) )
			return;

		$this->restore_gallery( $bcd );
	}

	/**
		@brief		Modify the post, if necessary.
		@since		2015-10-02 15:22:06
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->foogallery ) )
			return;

		$this->restore_shortcodes( $bcd );
	}

	/**
		@brief		Save the plugin's Data.
		@since		2015-10-02 12:48:22
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$bcd->foogallery = (object)[];

		$this->save_gallery( $bcd );
		$this->save_shortcodes( $bcd );
	}

	/**
		@brief		Add foogallery types.
		@since		2015-10-02 12:47:49
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ 'foogallery' ] = 'foogallery';
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- SAVE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		If this is a FooGallery, then save the attachments and restore them later.
		@since		2015-10-02 12:57:27
	**/
	public function save_gallery( $bcd )
	{
		if ( $bcd->post->post_type != 'foogallery' )
			return;

		$bcd->foogallery->attachments = ThreeWP_Broadcast()->collection();

		// Examine the attachments data.
		$attachments = $bcd->custom_fields()->get_single( 'foogallery_attachments' );
		$attachments = maybe_unserialize( $attachments );
		foreach( $attachments as $attachment_id )
		{
			if ( ! $bcd->try_add_attachment( $attachment_id ) )
				continue;
			$this->debug( 'Saving attachment %s', $attachment_id );
			$bcd->foogallery->attachments->append( $attachment_id );
		}
	}

	/**
		@brief		Save any shortcodes in the post.
		@since		2015-10-02 13:00:26
	**/
	public function save_shortcodes( $bcd )
	{
		$bcd->foogallery->shortcodes = ThreeWP_Broadcast()->collection();

		$matches = ThreeWP_Broadcast()->find_shortcodes( $bcd->post->post_content, [ 'foogallery' ] );

		if ( count( $matches[ 1 ] ) < 1 )
			return $this->debug( 'No shortcodes found.' );

		foreach( $matches[ 3 ] as $index => $atts )
		{
			$shortcode = $matches[ 0 ][ $index ];
			$atts = shortcode_parse_atts( $atts );
			if ( ! isset( $atts[ 'id' ] ) )
			{
				$this->debug( 'Shortcode %s does not contain an ID attribute.', $shortcode );
				continue;
			}

			$gallery_id = intval( $atts[ 'id' ] );
			$post = get_post( $gallery_id );
			if ( ! $post )
			{
				$this->debug( 'Shortcode %s has_attribute an invalid gallery id. Skipping.', $shortcode );
				continue;
			}

			$this->debug( 'Saving shortcode %s for later.', $shortcode );
			$bcd->foogallery->shortcodes->set( $shortcode, $gallery_id );
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- RESTORE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Restore the gallery data.
		@since		2015-10-02 13:03:24
	**/
	public function restore_gallery( $bcd )
	{
		if ( ! isset( $bcd->foogallery->attachments ) )
			return;

		$new_attachments = [];
		foreach( $bcd->foogallery->attachments as $old_id )
		{
			$new_id = $bcd->copied_attachments()->get( $old_id );
			if ( $new_id < 1 )
				continue;
			$this->debug( 'Replacing old attachment %s with %s.', $old_new, $new_id );
			$new_attachments []= $new_id;
		}

		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'foogallery_attachments', $new_attachments );
	}

	/**
		@brief		Restore shortcodes.
		@since		2015-10-02 13:07:48
	**/
	public function restore_shortcodes( $bcd )
	{
		if ( ! isset( $bcd->foogallery->shortcodes ) )
			return;

		foreach( $bcd->foogallery->shortcodes as $old_shortcode => $gallery_id )
		{
			$this->debug( 'Handling FooGallery %s found in shortcode %s', $gallery_id, $old_shortcode );
			// Get the gallery broadcasted.
			switch_to_blog( $bcd->parent_blog_id );
			$gallery_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $gallery_id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();

			$new_shortcode = str_replace( $gallery_id, $gallery_bcd->new_post( 'ID' ), $old_shortcode );

			$this->debug( 'Replacing shortcode %s with %s', $old_shortcode, $new_shortcode );
			$bcd->modified_post->post_content = str_replace( $old_shortcode, $new_shortcode, $bcd->modified_post->post_content );
		}
	}
}
