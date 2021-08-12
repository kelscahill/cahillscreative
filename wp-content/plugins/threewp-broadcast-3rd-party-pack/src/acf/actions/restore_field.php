<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Restores a field (converts IDs to equivalents on this child blog).
	@since		2015-01-21 22:36:13
**/
class restore_field
	extends action
{
	use post_id_trait;

	/**
		@brief		IN: The broadcasting data.
		@since		2015-01-24 21:53:43
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The field object to be parsed.
		@since		2015-01-24 21:52:58
	**/
	public $field;

	/**
		@brief		Update the ACF value.
		@since		2015-10-26 20:26:40
	**/
	public function acf_update_value( $value )
	{
		if ( function_exists( 'acf_update_value' ) )
			// acf pro
			return acf_update_value( $value, $this->post_id, (array) $this->field );
		else
		{
			// acf v4
			$r = update_field( $this->field->key, $value, $this->post_id );


			// Since acf4 can't update fields in repeaters, we force updating here.
			if ( Broadcast_ACF()->is_a_real_post( $this->post_id ) )
			{
				update_post_meta( $this->post_id, $this->field->name, $value );
			}

			return $r;
		}
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
		$this->field = $field;
	}
}
