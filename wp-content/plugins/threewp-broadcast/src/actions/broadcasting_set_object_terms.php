<?php

namespace threewp_broadcast\actions;

/**
	@brief		Set the terms of the child post.
	@details	The child post that gets the terms is new_post( 'ID' ).
	@since		2024-04-07 20:21:20
**/
class broadcasting_set_object_terms
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2024-04-07 20:21:20
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The taxonomy the terms belong to.
		@since		2024-04-07 20:22:06
	**/
	public string $taxonomy;

	/**
		@brief		IN: The term IDs to add.
		@since		2024-04-07 20:21:57
	**/
	public array $term_ids;

	/**
		@brief		[IN]: Use SQL to set the terms? Otherwise use the wp_set_object_terms function.
		@details	This is used by add-ons that are dealing with badly-coded plugins such as WPML that confuse the terms.
		@since		2024-04-07 20:22:45
	**/
	public $use_sql = false;
}
