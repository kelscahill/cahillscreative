<?php

namespace threewp_broadcast\premium_pack\wpcustom_category_image;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/wpcustom-category-image/">WPCustom Category Image</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-09-28 14:51:01
**/
class WPCustom_Category_Image
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_collect_post_type_taxonomies
		@since		2017-09-28 14:52:18
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->wpcustom_category_image ) )
			$bcd->wpcustom_category_image = ThreeWP_Broadcast()->collection();

		foreach( $bcd->parent_post_taxonomies as $parent_post_taxonomy => $terms )
		{
			$this->debug( 'Collecting options for %s', $parent_post_taxonomy );
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;		// Conv.

				$image_id = get_option( 'categoryimage_' . $term_id );

				if ( $image_id < 1 )
					continue;

				if ( ! $bcd->try_add_attachment( $image_id ) )
					continue;

				$this->debug( 'Found image %d for term %s (%d)',
					$image_id,
					$term->slug,
					$term_id
				);

				$bcd->wpcustom_category_image->collection( 'categoryimage' )->set( $term_id, $image_id );
			}
		}
	}

	/**
		@brief		threewp_broadcast_wp_update_term
		@since		2017-09-28 14:52:29
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->wpcustom_category_image ) )
			return;

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		$old_term_id = $action->old_term->term_id;
		$new_term_id = $action->new_term->term_id;

		$old_image_id = $bcd->wpcustom_category_image->collection( 'categoryimage' )->get( $old_term_id );

		// Was there an old image?
		if ( ! $old_image_id )
			return;

		$new_image_id = $bcd->copied_attachments()->get( $old_image_id );

		if ( ! $new_image_id )
			return;

		$this->debug( 'Replacing image %d for term %s (%d) with new image %d.',
			$old_image_id,
			$action->new_term->slug,
			$new_term_id,
			$new_image_id
		);

		update_option( 'categoryimage_' . $new_term_id, $new_image_id );
	}
}
