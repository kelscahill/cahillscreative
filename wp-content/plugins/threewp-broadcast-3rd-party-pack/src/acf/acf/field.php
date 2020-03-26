<?php

namespace threewp_broadcast\premium_pack\acf\acf;

/**
	@brief		A "wrapper" for the ACF field.
	@details	Clones the original field, to allow extra data to be saved without modifying the original field data.
	@since		20131030
**/
class field
{
	/**
		@brief		Constructor that clones the ACF field object.
		@since		2015-01-24 23:07:44
	**/
	public function __construct( $field )
	{
		foreach( (array)clone( $field ) as $key => $value )
			$this->$key = $value;
	}
	/**
		@brief		IN: The ACF field as an object.
		@since		2015-01-24 22:54:16
	**/
	public $field;

	/**
		@brief		Set the ACF field.
		@since		2015-01-24 22:55:03
	**/
	public function set_field( $field )
	{
		$this->field = $field;
	}
}
