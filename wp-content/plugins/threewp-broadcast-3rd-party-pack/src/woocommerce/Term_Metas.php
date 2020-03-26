<?php

namespace threewp_broadcast\premium_pack\woocommerce;

use \threewp_broadcast\attachment_data;
use \threewp_broadcast\broadcast_data;

/**
	@brief		Container for term image / meta info stored in woocommerce term meta.
	@since		2015-07-14 19:21:00
**/
class Term_Metas
	extends \threewp_broadcast\collection
{
	/**
		@brief		Add a term + image
		@since		2015-07-14 19:20:45
	**/
	public function add_image( $term_id, $meta_key, $image_id )
	{
		$o = (object)[];
		$o->term_id = $term_id;
		$o->key = $meta_key;
		$o->image_id = $image_id;
		$this->append( $o );
	}

	/**
		@brief		Add a neutral meta value.
		@since		2015-07-15 13:03:11
	**/
	public function add_value( $term_id, $meta_key, $meta_value )
	{
		$o = (object)[];
		$o->term_id = $term_id;
		$o->key = $meta_key;
		$o->value = $meta_value;
		$this->append( $o );
	}
}
