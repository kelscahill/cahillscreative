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
		$this->add_filter( 'broadcast_update_term_keys_to_check' );
		$this->add_action( 'threewp_broadcast_broadcasting_setup' );
		$this->add_action( 'threewp_broadcast_wp_update_term', 100 );
	}

	/**
		@brief		broadcast_update_term_keys_to_check
		@since		2021-05-03 09:25:52
	**/
	public function broadcast_update_term_keys_to_check( $keys )
	{
		$keys []= 'term_order';
		return $keys;
	}

	/**
		@brief		threewp_broadcast_broadcasting_setup
		@since		2020-07-02 18:46:59
	**/
	public function threewp_broadcast_broadcasting_setup( $action )
	{
		remove_filter('terms_clauses', 'TO_apply_order_filter', 10, 3);
	}

	/**
		@brief		The term_order has to be updated via wpdb.
		@since		2021-05-03 09:30:10
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		if ( ! isset( $action->new_term->term_order ) )
			return;
		global $wpdb;
		$this->debug( 'Updating term %s with term order %s', $action->new_term->term_id, $action->new_term->term_order );
		$wpdb->update( $wpdb->terms, [ 'term_order' => $action->new_term->term_order ], [ 'term_id' => $action->new_term->term_id ] );
	}
}
