<?php

namespace threewp_broadcast\premium_pack\learndash\actions;

/**
	@brief		Allow plugins to modify which meta values are updated.
	@since		2024-02-09 15:23:36
**/
class update_sfwd_custom_field
	extends action
{
	/**
		@brief		IN: The broadcasting_data object.
		@since		2024-05-31 23:03:53
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The unserialized custom field, specified in ->options->meta_key.
		@since		2024-02-09 15:24:22
	**/
	public $custom_field;

	/**
		@brief		IN/OUT: The options object.
		@see		update_sfwd_custom_field function.
		@since		2024-02-09 15:25:17
	**/
	public $options;
}
