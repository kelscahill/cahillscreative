<?php

namespace threewp_broadcast\premium_pack\woocommerce\actions;

/**
	@brief		During order sync, delete the existing items in the child order.
	@since		2021-09-16 11:43:37
**/
class after_restore_order
	extends action
{
	/**
		@brief		IN: The broadcasting data.
		@since		2021-05-05 22:31:26
	**/
	public $broadcasting_data;
	
	// IN: A convenience method pointing to the $bcd->woocommerce->order_items->new_order_items of this blog.
	public $new_order_items;
}
