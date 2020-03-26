<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Add a field into the broadcasting_data->acf object.
	@since		2015-01-21 22:36:13
**/
class add_field
	extends action
{
	use post_id_trait;
	use storage_trait;

	/**
		@brief		IN/OUT: The broadcasting data.
		@since		2015-01-24 21:53:43
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The field object to be parsed.
		@since		2015-01-24 21:52:58
	**/
	public $field;

	/**
		@brief		Set the broadcasting_data.
		@since		2015-01-24 21:54:01
	**/
	public function set_broadcasting_data( $broadcasting_data )
	{
		$this->broadcasting_data = $broadcasting_data;
	}

	/**
		@brief		Set the acf field object to be parsed.
		@since		2015-01-24 21:53:15
	**/
	public function set_field( $field )
	{
		$this->field = (object)$field;
	}

	/**
		@brief		Return the part of the broadcasting_data that stores the ACF data.
		@since		2015-10-26 19:56:19
	**/
	public function storage()
	{
		$storage = $this->storage;
		return $this->broadcasting_data->$storage;
	}
}
