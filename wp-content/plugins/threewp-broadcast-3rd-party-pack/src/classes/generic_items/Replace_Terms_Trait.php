<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

/**
	@brief		Replace post IDs.
	@since		2019-06-20 22:06:13
**/
trait Replace_Terms_Trait
{
	/**
		@brief		Take note of the taxonomies to sync.
		@since		2016-12-21 21:29:20
	**/
	public function parse_find( $bcd, $find )
	{
		$ids = ThreeWP_Broadcast()->collection();
		foreach( $find->value as $attribute => $id )
		{
			$id = intval( $id );
			$ids->set( $id, true );
		}

		foreach( $find->values as $attribute => $find_ids )
			foreach( $find_ids as $id )
			{
				$id = intval( $id );
				$ids->set( $id, true );
			}

		$taxonomies = ThreeWP_Broadcast()->collection();
		foreach( array_keys( $ids->to_array() ) as $id )
		{
			$term = get_term( $id );
			if ( ! $term )
				continue;
			$taxonomy = $term->taxonomy;
			$this->debug( 'Term %s belongs to taxonomy %s', $id, $taxonomy );
			$find->collection( 'taxonomies' )->set( $id, $taxonomy );
			$bcd->taxonomies()->also_sync( false, $taxonomy );	// False to force resyncing of the taxonomy.
			$bcd->taxonomies()->use_term( $id );
			$taxonomies->set( $taxonomy, true );
		}

		foreach( array_keys( $taxonomies->to_array() ) as $taxonomy )
		{
			if ( isset( $bcd->parent_blog_taxonomies[ $taxonomy ] ) )
				continue;
			$terms = ThreeWP_Broadcast()->get_current_blog_taxonomy_terms( $taxonomy );
			$this->debug( 'Retrieved %s terms for taxonomy %s.', count( $terms ), $taxonomy );
			$bcd->parent_blog_taxonomies[ $taxonomy ] = [
				'taxonomy' => get_taxonomy( $taxonomy ),
				'terms' => $terms,
			];
		}
	}

	/**
		@brief		Replace the old ID with a new one.
		@since		2016-07-14 14:21:21
	**/
	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		$new_id = $broadcasting_data->terms()->get( $old_id );
		if ( $new_id < 1 )
			$new_id = 0;

		$this->debug( 'Replacing term %s with %s', $old_id, $new_id );

		return $new_id;
	}
}
