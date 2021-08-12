<?php

namespace threewp_broadcast\premium_pack\category_order_and_taxonomy_terms_order;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/taxonomy-terms-order/">Category Order and Taxonomy Terms Order plugin</a>.
	@plugin_group	3rd party compatability
	@since			2020-07-02 18:41:17
**/
class Category_Order_and_Taxonomy_Terms_Order
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_setup' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_setup
		@since		2020-07-02 18:46:59
	**/
	public function threewp_broadcast_broadcasting_setup( $action )
	{
		remove_filter('terms_clauses', 'TO_apply_order_filter', 10, 3);
	}
}
