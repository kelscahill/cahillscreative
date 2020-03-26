<?php

namespace threewp_broadcast\premium_pack\gravity_forms\actions;

/**
	@brief		Modify the form meta of this form.
	@since		2017-11-20 20:09:30
**/
class modify_form_meta
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2017-11-20 20:10:03
	**/
	public $broadcasting_data;

	/**
		@brief		The ID of this form.
		@since		2017-11-20 20:10:30
	**/
	public $form_id;

	/**
		@brief		IN: The form meta database row as an object.
		@since		2017-11-22 19:46:51
	**/
	public $meta;
}
