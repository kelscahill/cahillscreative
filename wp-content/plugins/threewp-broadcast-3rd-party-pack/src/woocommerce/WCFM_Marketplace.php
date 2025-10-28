<?php

namespace threewp_broadcast\premium_pack\woocommerce;

class WCFM_Marketplace
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Is WCFM installed?
		@since		2021-09-16 11:48:50
	**/
	public $wcfm_installed = null;

	public function _construct()
	{
		$this->add_action( 'broadcast_woocommerce_after_restore_order' );
		$this->add_action( 'broadcast_woocommerce_after_save_order' );
		$this->add_action( 'broadcast_woocommerce_delete_order_items' );
	}

	public function broadcast_woocommerce_after_restore_order( $action )
	{
		if ( ! $this->wcfm() )
			return;

		global $wpdb;

		$bcd = $action->broadcasting_data;
		$orders_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders' );
		$orders_meta_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders_meta' );

		$this->debug( 'Restoring marketplace orders' );
		$orders = $bcd->woocommerce->wcfm_marketplace->get( 'orders' );
		foreach( $orders as $order )
		{
            unset( $order->ID );
            $order->item_id = $action->new_order_items->get( $order->item_id );
            $order->order_id = $bcd->new_post( 'ID' );
            $order->product_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $order->product_id, get_current_blog_id() );

            $wpdb->insert( $orders_table, (array) $order );
            $new_order_id = $wpdb->insert_id;
            $this->debug( 'Inserted new order %s for %s', $new_order_id, $order );
		}

		$orders_meta = $bcd->woocommerce->wcfm_marketplace->get( 'orders_meta' );
		foreach( $orders_meta as $meta )
		{
            unset( $meta->ID );
            $meta->order_commission_id = $new_order_id;

            $wpdb->insert( $orders_meta_table, (array) $meta );
            $new_meta_id = $wpdb->insert_id;
            $this->debug( 'Inserted new order meta %s for %s', $new_meta_id, $meta );
		}

		$vendor_ledger_table = static::get_prefixed_table_name( 'wcfm_marketplace_vendor_ledger' );
		$vendor_ledger = $bcd->woocommerce->wcfm_marketplace->get( 'vendor_ledger' );
		foreach( $vendor_ledger as $ledger )
		{
            unset( $ledger->ID );
            $ledger->reference_id = $new_order_id;
            $wpdb->insert( $vendor_ledger_table, (array) $ledger );
            $new_ledger_id = $wpdb->insert_id;
            $this->debug( 'Inserted new vendor ledger %s for %s', $new_ledger_id, $ledger );
		}
	}

	public function broadcast_woocommerce_after_save_order( $action )
	{
		if ( ! $this->wcfm() )
			return;

        global $wpdb;

		$bcd = $action->broadcasting_data;
        $bcd->woocommerce->wcfm_marketplace = ThreeWP_Broadcast()->collection();

		$orders_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `order_id` = '%s'", $orders_table, $bcd->post->ID );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		$bcd->woocommerce->wcfm_marketplace->set( 'orders', $results );
		$this->debug( 'Saving orders %s', $results );

		$orders_meta_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders_meta' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `order_commission_id` IN ( SELECT `ID` FROM `%s` WHERE `order_id` = '%s' )",
			$orders_meta_table,
			$orders_table,
			$bcd->post->ID
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		$bcd->woocommerce->wcfm_marketplace->set( 'orders_meta', $results );
		$this->debug( 'Saving orders meta %s', $results );

		$vendor_ledger_table = static::get_prefixed_table_name( 'wcfm_marketplace_vendor_ledger' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `reference` = 'order' AND `reference_id` IN ( SELECT `ID` FROM `%s` WHERE `order_id` = '%s' )",
			$vendor_ledger_table,
			$orders_table,
			$bcd->post->ID
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		$bcd->woocommerce->wcfm_marketplace->set( 'vendor_ledger', $results );
		$this->debug( 'Saving vendor ledger %s', $results );
	}

	/**
		@brief		broadcast_woocommerce_delete_order_items
		@since		2021-09-16 11:48:08
	**/
	public function broadcast_woocommerce_delete_order_items( $action )
	{
		if ( ! $this->wcfm() )
			return;

		global $wpdb;
		$bcd = $action->broadcasting_data;

		$orders_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders' );
		$orders_meta_table = static::get_prefixed_table_name( 'wcfm_marketplace_orders_meta' );

		$query = sprintf( "DELETE FROM `%s` WHERE `order_commission_id` IN ( SELECT `ID` FROM `%s` WHERE `order_id` = '%s' )",
			$orders_meta_table,
			$orders_table,
			$bcd->new_post( 'ID' )
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		$query = sprintf( "DELETE FROM `%s` WHERE `order_id` = '%s'", $orders_table, $bcd->new_post( 'ID' ) );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
	}

	/**
		@brief		Is WCFM installed?
		@details	Checks for the existence of the marketplace orders table. Caches the value.
		@since		2021-09-16 11:48:36
	**/
	public function wcfm()
	{
		if ( $this->wcfm_installed === true )
			return true;
		if ( $this->wcfm_installed === false )
			return false;

		$table = static::get_prefixed_table_name( 'wcfm_marketplace_orders' );
		$this->wcfm_installed = $this->database_table_exists( $table );

		return $this->wcfm();
	}
}
