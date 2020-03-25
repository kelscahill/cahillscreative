<?php

namespace threewp_broadcast\broadcasting_data;

/**
	@brief		Remember which taxonomies we have synced.
	@since		2020-01-08 21:57:06
**/
class Synced_Taxonomies
	extends \threewp_broadcast\collection
{
	/**
		@brief		This taxonomy has been synced.
		@since		2020-01-08 21:57:44
	**/
	public function add( $taxonomy )
	{
		$blog_id = get_current_blog_id();
		$this->collection( 'blog_id' )
			->collection( $blog_id )
			->set( $taxonomy, $taxonomy );
		return $this;
	}

	/**
		@brief		Check whether this taxonomy has been synced on this blog.
		@since		2020-01-09 05:38:54
	**/
	public function has_synced( $taxonomy )
	{
		$blog_id = get_current_blog_id();
		return $this->collection( 'blog_id' )->collection( $blog_id )->has( $taxonomy );
	}
}
