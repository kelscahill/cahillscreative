<?php

namespace threewp_broadcast\premium_pack\acf\actions;

/**
	@brief		Add storage handling methods, that specify where in the broadcasting_data to store the ACF field data.
	@since		2015-10-26 20:46:11
**/
trait storage_trait
{
	/**
		@brief		[IN] The key within the broadcasting_data that is storing the ACF data.
		@details	Used to switch the accepting collections from normal ACF to ACF taxonomies and ACF options and whatever, depending on who is asking.
		@since		2015-10-26 19:58:27
	**/
	public $storage = 'acf';

	/**
		@brief		Return the storage value.
		@since		2015-10-26 20:46:51
	**/
	public function get_storage()
	{
		return $this->storage;
	}

	/**
		@brief		Set the storage value.
		@since		2015-10-26 20:47:06
	**/
	public function set_storage( $storage )
	{
		$this->storage = $storage;
	}
}
