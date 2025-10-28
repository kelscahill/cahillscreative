<?php

namespace threewp_broadcast\premium_pack\woocommerce\actions;

/**
	@brief		During order sync, delete the existing items in the child order.
	@since		2021-09-16 11:43:37
**/
class delete_order_items
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2021-05-05 22:31:26
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The ID of the order.
		@since		2021-09-16 11:43:57
	**/
	public $order_id;

	/**
		@brief		IN: The items of the order.
		@since		2021-09-16 11:44:05
	**/
	public $order_items;
}
