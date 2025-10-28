<?php

namespace threewp_broadcast\premium_pack\jetengine;

/**
	* @brief		Add support for broadcasting of Jet Smart Filters.
	* @since		2025-05-12 18:50:16
**/
class Jet_Smart_Filters
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_things_ui_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
	 * threewp_broadcast_broadcasting_before_restore_current_blog
	 *
	 * @since		2025-05-12 19:22:13
	 **/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'jet-smart-filters' )
			return;

		$key = '_data_source';
		$cf = $bcd->custom_fields()->get_single( $key );
		if ( $cf )
		{
			switch( $cf )
			{
				case 'posts':
					$key = '_data_exclude_include';
					$ids = $bcd->custom_fields()->get_single( $key );
					$ids = maybe_unserialize( $ids );
					$new_ids = [];
					foreach( $ids as $old_id )
						$new_ids []= $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
					$this->debug( 'Replacing old terms %s with %s', $ids, $new_ids );
					$bcd->custom_fields()->child_fields()->update_meta( $key, $new_ids );
				break;
				case 'taxonomies':
					$key = '_data_exclude_include';
					$ids = $bcd->custom_fields()->get_single( $key );
					$ids = maybe_unserialize( $ids );
					$new_ids = [];
					foreach( $ids as $old_id )
						$new_ids []= $bcd->terms()->get( $old_id );
					$this->debug( 'Replacing old terms %s with %s', $ids, $new_ids );
					$bcd->custom_fields()->child_fields()->update_meta( $key, $new_ids );
				break;
			}
		}
	}

	/**
	 * threewp_broadcast_broadcasting_started
	 *
	 * @since		2025-05-12 19:00:20
	 **/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'jet-smart-filters' )
			return;

		$key = '_data_source';
		$cf = $bcd->custom_fields()->get_single( $key );
		if ( $cf )
		{
			switch( $cf )
			{
				case 'taxonomies':
					$taxonomy = $bcd->custom_fields()->get_single( '_source_taxonomy' );

					$bcd->taxonomies()->also_sync( null, $taxonomy );

					$ids = $bcd->custom_fields()->get_single( '_data_exclude_include' );
					$this->debug( 'Need to sync %s: %s',
						$taxonomy,
						$ids,
					);
					$ids = maybe_unserialize( $ids );
					$bcd->taxonomies()->use_terms( $ids );
				break;
			}
		}
	}
}
