<?php

namespace threewp_broadcast\premium_pack\wp_all_import_pro;

/**
	@brief				Adds support for post updates from Soffly's <a href="http://www.wpallimport.com/">WP All Import Pro</a>.
	@plugin_group		3rd party compatability
	@since				2016-02-03 16:45:40
**/
class WP_All_Import_Pro
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'pmxi_saved_post' );
	}

	/**
		@brief		A post was imported, so update any children it might have.
		@since		2016-02-03 16:51:37
	**/
	public function pmxi_saved_post( $post_id )
	{
		// Should this post be automatically broadcasted after import?
		$broadcast = apply_filters( 'broadcast_wp_all_import_pro_maybe_import', true, $post_id );

		if ( $broadcast )
		{
			wp_defer_term_counting( false );
			$this->debug( 'Imported post %s. Updating children.', $post_id );
			ThreeWP_Broadcast()->api()
				->low_priority()
				->update_children( $post_id, [] );
			$this->debug( 'Done updating children %s.', $post_id );
		}
		else
		{
			$this->debug( 'Not broadcasting imported post %s.', $post_id );
		}
	}
}
