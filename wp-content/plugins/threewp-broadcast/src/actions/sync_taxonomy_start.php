<?php

namespace threewp_broadcast\actions;

/**
	@brief		This taxonomy is about to get synced.
	@since		2021-01-19 16:00:39
**/
class sync_taxonomy_start
	extends action
{
	/**
		@brief		IN: The broadcasting data object.
		@since		2021-01-19 16:00:39
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The name of the taxonomy we just synced.
		@since		2021-01-19 16:00:39
	**/
	public $taxonomy;
}
