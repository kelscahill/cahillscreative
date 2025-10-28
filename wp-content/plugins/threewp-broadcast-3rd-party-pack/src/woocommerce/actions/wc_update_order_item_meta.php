<?php

namespace threewp_broadcast\premium_pack\woocommerce\actions;

/**
	@brief		Update the meta of an order item.
	@since		2021-05-05 22:31:41
**/
class wc_update_order_item_meta
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2021-05-05 22:31:26
	**/
	public $broadcasting_data;

	/**
		@brief		IN: The ID of the new order item.
		@since		2021-05-05 22:32:14
	**/
	public $new_item_id;

	/**
		@brief		IN: The parent order item.
		@since		2021-05-05 22:32:45
	**/
	public $parent_item;
}
