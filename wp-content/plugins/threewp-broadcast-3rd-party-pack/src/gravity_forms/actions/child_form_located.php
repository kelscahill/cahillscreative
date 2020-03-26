<?php

namespace threewp_broadcast\premium_pack\gravity_forms\actions;

/**
	@brief		The equivalent form on the child blog has been found or created.
	@since		2018-07-30 19:56:53
**/
class child_form_located
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2018-07-30 19:56:53
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The ID of this form.
		@since		2018-07-30 19:56:53
	**/
	public $form_id;

	/**
		@brief		IN: Was a new form created on this child?
		@since		2018-07-30 19:57:45
	**/
	public $new_form = false;
}
