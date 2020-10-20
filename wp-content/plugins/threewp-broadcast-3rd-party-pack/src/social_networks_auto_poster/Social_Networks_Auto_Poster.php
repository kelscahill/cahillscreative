<?php

namespace threewp_broadcast\premium_pack\social_networks_auto_poster;

/**
	@brief				OBSOLETE: Adds support for <a href="https://wordpress.org/plugins/social-networks-auto-poster-facebook-twitter-g/">NextScripts' Social Networks Auto Poster</a> plugin.
	@plugin_group		3rd party compatability
	@since				2015-01-19 21:29:34
**/
class Social_Networks_Auto_Poster
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_after_switch_to_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		Tell SNAP to reload itself.
		@details

		Don't be fooled. Trying to deobfuscate, reformat and follow the code took me several hours.

		The people behind SNAP _really_ don't want anyone else to follow their code. At all. Ever.

		It's surprizing that their plugin is as popular as it is, considering how coder-hating it is.

		Tested with version 3.4.7

		@since		2015-01-19 21:30:43
	**/
	public function threewp_broadcast_broadcasting_after_switch_to_blog( $action )
	{
		global $plgn_NS_SNAutoPoster;
		if ( ! isset( $plgn_NS_SNAutoPoster ) )
			return $this->debug( 'SNAP not detected.' );

		$bcd = $action->broadcasting_data;
		if ( $bcd->post->post_status == 'publish' )
		{
			/**
				Normally, in SNAP direct mode, which is the only mode that Broadcast supports, the post is social posted before the images are available.

				To prevent half-snapped posts, the status is set to draft, and after all the images have been attached, before_store_current_blog will publish the post.
				This will in turn tell SNAP to snap the post.
			**/
			$bcd->snap = ThreeWP_Broadcast()->collection();
			$bcd->snap->set( 'publish_after_publish', true );
			$bcd->new_post->post_status = 'draft';
			$this->debug( 'Post is published. Switching to draft to accomodate immedate SNAP publishing.' );
		}

		$this->debug( 'SNAP reinit().' );
		$plgn_NS_SNAutoPoster->init();
	}

	/**
		@brief		Maybe publish the post.
		@since		2016-09-07 12:30:54
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->snap ) )
			return;

		if ( $bcd->snap->has( 'publish_after_publish' ) )
		{
			// Images should now be ready. Publish the post properly now.
			$new_post_data = (object)[
				'ID' => $bcd->new_post( 'ID' ),
				'post_status' => 'publish',
			];
			$this->debug( 'Publishing drafted SNAP post.' );
			wp_update_post( $new_post_data );
			$this->debug( 'Published.' );
		}
	}

	/**
		@brief		Broadcasting has started.
		@since		2016-09-07 12:54:18
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		foreach( [
			'snap_isAutoPosted',		// To allow autoposting at all
			'snapFB',					// To prevent "duplicates", Facebook
			'snapTW',					// To prevent "duplicates", Twitter
		] as $key )
		{
			$bcd->custom_fields->blacklist []= $key;
			$bcd->custom_fields()->forget( $key );
			$this->debug( 'Adding autoposted meta key to black and protect lists: %s', $key );
		}
	}
}
