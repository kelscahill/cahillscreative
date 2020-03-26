<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Parse a field, trying to find out what kind of field it is and how it should be handled.
	@since		2015-01-21 22:36:13
**/
class parse_field
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
		@brief		Return the raw, unprocessed value of this field.
		@since		2015-10-26 21:35:04
	**/
	public function get_raw_value()
	{
		if ( Broadcast_ACF()->is_a_real_post( $this->post_id ) )
		{
			if ( is_array( $this->broadcasting_data->custom_fields->original[ $this->field->name ] ) )
				return reset( $this->broadcasting_data->custom_fields->original[ $this->field->name ] );
			else
				return $this->broadcasting_data->custom_fields->original[ $this->field->name ];
		}

		// Return the value from the options table.
		$key = sprintf( '%s_%s', $this->post_id, $this->field->name );
		return get_option( $key, true );
	}

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
}
