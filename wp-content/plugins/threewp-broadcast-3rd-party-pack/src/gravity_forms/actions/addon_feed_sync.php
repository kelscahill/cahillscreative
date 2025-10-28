<?php

namespace threewp_broadcast\premium_pack\gravity_forms\actions;

/**
	@brief		Sync the addon_feed table.
	@since		2021-01-11 19:38:45
**/
class addon_feed_sync
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2021-01-11 19:38:38
	**/
	public $broadcasting_data;

	/**
		@brief		IN: Was a new form created?
		@since		2022-01-09 19:59:40
	**/
	public $new_form;

	/**
		@brief		OUT: Array of old_id => new_id of the rows in the tables.
		@since		2021-01-11 19:39:21
	**/
	public $feed_ids = [];

	/**
		@brief		IN: Array of SQL rows for the source feed table.
		@since		2021-01-11 19:43:05
	**/
	public $source_feeds;

	/**
		@brief		IN: Array of SQL rows for the source forms.
		@since		2021-05-11 22:39:09
	**/
	public $source_forms;

	/**
		@brief		IN: The ID of the source form.
		@since		2021-01-11 19:42:04
	**/
	public $source_form_id;

	/**
		@brief		IN/OUT: Array of existing SQL rows for the target feed table.
		@details	If you are handling an SQL row on the target table, and don't want it modified later, remove it from here.
					Otherwise the add-on will delete all existing rows and re-add them.
		@since		2021-01-11 19:43:26
	**/
	public $target_feeds;

	/**
		@brief		IN: Array of SQL rows for the target forms.
		@since		2021-05-11 22:39:09
	**/
	public $target_forms;

	/**
		@brief		IN: The ID of the target form.
		@since		2021-01-11 19:42:52
	**/
	public $target_form_id;
}
