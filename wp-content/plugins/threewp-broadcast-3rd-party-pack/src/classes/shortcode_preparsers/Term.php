<?php

namespace threewp_broadcast\premium_pack\classes\shortcode_preparsers;

use Exception;

/**
	* @brief		Handles term items in shortcodes.
	* @since		2024-11-02 06:53:23
**/
class Term
	extends Base
{
	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		$this->prepare_to_copy( $bcd, $item );

		if ( ! isset( $item->id ) )
			throw new Exception( $this->debug( 'Unable to copy the item since it has no ID.' ) );

		$new_ids = [];
		$terms = $bcd->terms();
		foreach( $this->get_ids( $item ) as $old_id )
		{
			$new_id = $terms->get( $old_id );
			$new_ids []= $new_id;
		}

		$new_ids = array_filter( $new_ids );
		$new_id = implode( $this->get_id_separator(), $new_ids );

		return $new_id;
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
		foreach( $this->get_ids( $item ) as $id )
		{
			$term = get_term( $id );
			if ( ! $term )
				continue;
			$taxonomy = $term->taxonomy;
			$this->debug( 'Term %s belongs to taxonomy %s', $id, $taxonomy );
			$bcd->taxonomies()->also_sync( false, $taxonomy );	// False to force resyncing of the taxonomy.
			$bcd->taxonomies()->use_term( $id );
		}
	}
}
