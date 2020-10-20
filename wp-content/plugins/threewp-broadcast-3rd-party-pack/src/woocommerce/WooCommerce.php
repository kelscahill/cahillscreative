<?php

namespace threewp_broadcast\premium_pack\woocommerce;

use Exception;
use threewp_broadcast\attachment_data;
use threewp_broadcast\broadcast_data;

/**
	@brief				Adds support for <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> extended features.
	@plugin_group		3rd party compatability
	@since				20131117
**/
class WooCommerce
extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\copy_options_trait
	{
		do_copy_options as copy_option_trait_do_copy_options;
	}
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Are we busy syncing stock?
		@since		2016-01-26 16:51:55
	**/
	public static $syncing_stock = false;

	/**
		@brief		The keys that WC stores for each term.
		@since		2016-10-05 09:56:01
	**/
	public static $term_meta_keys = [ 'display_type' ];

	public function _construct()
	{
		$this->add_action( 'broadcast_bulk_cloner_option_modifications_get_wizards' );
		$this->add_filter( 'broadcast_wp_all_import_pro_maybe_import', 10, 2 );

		$this->add_filter( 'threewp_broadcast_allowed_post_statuses' );

		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_prepare_broadcasting_data' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );

		$this->add_filter( 'wc_memberships_allowed_meta_box_ids' );
		$this->add_action( 'woocommerce_admin_process_product_object' );
		$this->add_action( 'woocommerce_api_edit_order', 'update_order' );
		$this->add_action( 'woocommerce_order_edit_status', 'update_order' );
		$this->add_action( 'woocommerce_order_status_changed', 'update_order' );

		$this->add_action( 'woocommerce_product_set_stock' );
		$this->add_action( 'woocommerce_variation_set_stock', 'woocommerce_product_set_stock' );

		new Add_To_Cart_Shortcode();
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- CALLBACKS
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Fill it up with all the wizards we have.
		@since		2019-12-18 18:35:11
	**/
	public function broadcast_bulk_cloner_option_modifications_get_wizards( $action )
	{
		$w = $action->wizard();
		$w->set_name( 'WooCommerce > E-mails > New Order > Recipient(s)' );
		$w->set_option_name( 'woocommerce_new_order_settings' );
		$w->set_option_key( 'recipient' );
	}

	/**
		@brief		Should this post be imported?
		@since		2017-03-01 15:59:38
	**/
	public function broadcast_wp_all_import_pro_maybe_import( $broadcast, $post_id )
	{
		$post = get_post( $post_id );

		if ( $post->post_type == 'product_variation' )
			$broadcast = false;

		return $broadcast;
	}

	/**
		@brief		Menu tabs.
		@since		2016-01-26 14:02:05
	**/
	public function menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'settings' )
			->callback_this( 'settings' )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) )
			->sort_order( 25 );	// Always first.

		$tabs->tab( 'copy_options' )
			->callback_this( 'show_copy_settings' )
			// Copy WooCommerce options from one blog to another.
			->name( __( 'Copy settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		Plugin settings.
		@since		2016-01-26 14:02:56
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = '';

		$fs = $form->fieldset( 'fs_misc' )
			// Fieldset label.
			->label( __( 'Misc', 'threewp_broadcast' ) );

		$input_partial_broadcast_size = $fs->number( 'partial_broadcast_size' )
			// Input title
			->description( __( 'When using the queue add-on, how many variations to broadcast at a time during each queue process opportunity. Use the debug log to see how many variations can be broadcasted before encountering a PHP timeout. This depends on the amount of images and extra WooCommerce features involved. Try 5 to start off with. 0 to disable.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Variations per queue', 'threewp_broadcast' ) )
			->min( 0 )
			->max( 1000000 )
			->value( $this->get_site_option( 'partial_broadcast_size' ) );

		$fs = $form->fieldset( 'fs_orders' )
			// Fieldset label.
			->label( __( 'Orders', 'threewp_broadcast' ) );

		$input_sync_orders = $fs->checkbox( 'sync_orders' )
			->checked( $this->get_site_option( 'sync_orders' ) )
			// Input title
			->description( __( 'Automatically broadcast orders between shared products. The Back To Parent Broadcast add-on must be enabled for orders to be synced back to the parent, else orders will only be synced to child blogs.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Broadcast and sync orders', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_stock' )
			// Fieldset label for WooCommerce stock.
			->label( __( 'Stock', 'threewp_broadcast' ) );

		$input_sync_stock = $fs->checkbox( 'sync_stock' )
			->checked( $this->get_site_option( 'sync_stock' ) )
			// Input title
			->description( __( 'Synchronize the stock count between linked products.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Sync stock', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $input_sync_orders->is_checked();
			$this->update_site_option( 'sync_orders', $value );

			$value = $input_sync_stock->is_checked();
			$this->update_site_option( 'sync_stock', $value );

			$value = $input_partial_broadcast_size->get_post_value();
			$this->update_site_option( 'partial_broadcast_size', $value );

			$r .= $this->info_message_box()->_( 'Options saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		AutomateWoo disabled workflows need to be allowed to be broadcasted.
		@since		2016-10-08 00:49:29
	**/
	public function threewp_broadcast_allowed_post_statuses( $statuses )
	{
		$statuses []= 'aw-disabled';

		// Allow all WC post statuses at once.
		if ( function_exists( 'wc_get_order_statuses' ) )
			$statuses = array_merge( $statuses, array_keys( wc_get_order_statuses() ) );

		return $statuses;
	}

	/**
		@brief		Handle restoring of WC orders and products.
		@param		$action		Broadcast action.
		@since		20131117
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->has_woocommerce() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->woocommerce ) )
			return;

		$this->maybe_restore_automatewoo_workflow( $bcd );
		$this->maybe_restore_membership_plan( $bcd );
		$this->maybe_restore_order( $bcd );
		$this->maybe_restore_product( $bcd );
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20131117
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_woocommerce() )
			return $this->debug( 'WooCommerce not detected.' );

		$bcd = $action->broadcasting_data;

		$this->maybe_save_automatewoo_workflow( $bcd );
		$this->maybe_save_membership_plan( $bcd );
		$this->maybe_save_order( $bcd );
		$this->maybe_save_product( $bcd );
	}

	/**
		@brief		Syncing taxonomies? Remember the variation swatch images.
		@since		2016-08-27
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		// For older versions of WC.
		if ( ! function_exists( 'get_term_meta' ) )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->woocommerce_termmeta) )
			$bcd->woocommerce_termmeta = ThreeWP_Broadcast()->collection();

		foreach( $bcd->parent_blog_taxonomies as $parent_post_taxonomy => $taxonomy_data )
		{
			$terms = $taxonomy_data[ 'terms' ];

			$this->debug( 'Collecting termmeta for %s', $parent_post_taxonomy );
			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;

				// Save the color and type.
				foreach( [ 'color', 'type' ] as $type )
				{
					$key = $parent_post_taxonomy . '_swatches_id_' . $type;
					$value = get_term_meta( $term_id, $key, true );
					$bcd->woocommerce_termmeta->collection( $term_id )->set( $type, $value );
				}

				// Save the image.
				$key = $parent_post_taxonomy . '_swatches_id_photo';
				$image_id = get_term_meta( $term_id, $key, true );

				if ( $image_id > 0 )
				{
				  $this->debug( 'Found photo %s for term %s (%s)',
					  $image_id,
					  $term->slug,
					  $term_id
				  );

				  $bcd->try_add_attachment( $image_id );
				  $bcd->woocommerce_termmeta->collection( $term_id )->set( 'photo', $image_id );
				}

				// Thumbnail ID
				$key = 'thumbnail_id';
				$image_id = get_term_meta( $term_id, $key, true );
				if ( $image_id > 0 )
				{
				  $bcd->try_add_attachment( $image_id );
				  $bcd->woocommerce_termmeta->collection( $term_id )->set( 'thumbnail_id', $image_id );
				}

				// And other terms.
				foreach ( static::$term_meta_keys as $key )
				{
					$value = get_term_meta( $term_id, $key, true );
					$bcd->woocommerce_termmeta->collection( $term_id )->set( $key, $value );
				}
			}
		}

		// Product Vendors?
		if ( isset( $bcd->parent_post_taxonomies[ 'wcpv_product_vendors' ] ) )
		{
			$bcd->woocommerce_termmeta->collection( $term_id )->set( $key, $value );
			foreach( $bcd->parent_post_taxonomies[ 'wcpv_product_vendors' ] as $term_id => $term_data )
			{
				$vendor_data = get_term_meta( $term_id, 'vendor_data', true );
				$vendor_data = maybe_unserialize( $vendor_data );
				if ( ! $vendor_data )
					continue;

				$bcd->woocommerce_termmeta->collection( $term_id )->set( 'vendor_data', $vendor_data );

				// The logo needs to be translated.
				$logo_id = intval( $vendor_data[ 'logo' ] );
				if ( $logo_id > 0 )
				{
					$this->debug( 'Product vendor: Adding logo %s', $logo_id );
					$bcd->try_add_attachment( $logo_id );
				}
			}
		}
	}

	/**
		@brief		Add WooCommerce types.
		@since		2015-08-02 19:05:06
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'atum_supplier' );
		$action->add_type( 'aw_workflow' );
		$action->add_type( 'product' );
		$action->add_type( 'product_variation' );
		$action->add_type( 'shop_order' );
		$action->add_type( 'shop_order_refund' );
		$action->add_type( 'wc_membership_plan' );
		$action->add_type( 'ywtm_tab' );
	}

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_woocommerce' )
			->callback_this( 'menu_tabs' )
			->menu_title( 'WooCommerce' )
			->page_title( 'WooCommerce Broadcast' );
	}

	/**
		@brief		threewp_broadcast_prepare_broadcasting_data
		@since		2017-04-05 10:02:41
	**/
	public function threewp_broadcast_prepare_broadcasting_data( $action )
	{
		if ( ! isset( $this->__woocommerce_admin_process_product_object ) )
			return;

		// Do not broadcast to any blogs right now.
		$bcd = $action->broadcasting_data;
		$bcd->blogs->flush();

		unset( $this->__woocommerce_admin_process_product_object );
	}

	/**
		@brief		Restore swatch images.
		@since		2016-08-27
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->woocommerce_termmeta ) )
			return;

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		$old_term_id = $action->old_term->term_id;
		$new_term_id = $action->new_term->term_id;

		$c = $bcd->woocommerce_termmeta->collection( $old_term_id );
		if ( count( $c ) < 1 )
			return;

		// Restore type and color.
		foreach( [ 'color', 'type' ] as $key )
		{
			$new_key = sprintf( '%s_swatches_id_%s', $action->old_term->taxonomy, $key );
			$new_value = $c->get( $key );
			if ( $new_value != '' )
			{
				$this->debug( 'Updating %s with %s', $new_key, $new_value );
				update_term_meta( $new_term_id, $new_key, $new_value );
			}
		}

		// Restore photo.
		$old_image_id = $bcd->woocommerce_termmeta->collection( $old_term_id )
			->get( 'photo', 0 );

		if ( $old_image_id > 0 )
		{
			$new_key = sprintf( '%s_swatches_id_photo', $action->old_term->taxonomy );
			$new_value = $bcd->copied_attachments()->get( $old_image_id );

			if ( $new_value < 1 )
				return $this->debug( 'Old attachment %s was not copied. Unable to restore photo.', $old_image_id );

			$this->debug( 'Restoring %s with new attachment ID %s from %s.',
				$new_key,
				$new_value,
				$old_image_id
			);
			update_term_meta( $new_term_id, $new_key, $new_value );
		}

		// Restore thumbnail ID.
		$old_image_id = $bcd->woocommerce_termmeta->collection( $old_term_id )
			->get( 'thumbnail_id', 0 );
		if ( $old_image_id > 0 )
		{
			$new_value = $bcd->copied_attachments()->get( $old_image_id );
			if ( $new_value > 0 )
			{
				$this->debug( 'Setting new thumbnail_id %s for term %s.', $new_value, $new_term_id );
				update_term_meta( $new_term_id, 'thumbnail_id', $new_value );
			}
		}

		foreach ( static::$term_meta_keys as $key )
		{
			$value = $bcd->woocommerce_termmeta->collection( $old_term_id )->get( $key, 'no value here' );
			if ( $value != 'no value here' )
			{
				$this->debug( 'Setting term meta %s for term %s to %s', $key, $new_term_id, $value );
				update_term_meta( $new_term_id, $key, $value );
			}
		}

		// Restore product vendor?
		$vendor_data = $bcd->woocommerce_termmeta->collection( $old_term_id )->get( 'vendor_data' );
		if ( $vendor_data )
		{
			// Restore the logo ID.
			$vendor_data[ 'logo' ] = $bcd->copied_attachments()->get( $vendor_data[ 'logo' ] );

			$this->debug( 'Product vendor: Saving new data %s', $vendor_data );
			update_term_meta( $new_term_id, 'vendor_data', $vendor_data );
		}
	}

	/**
		@brief		Maybe broadcast out this order to all linked blogs.
		@since		2016-02-14 23:13:41
	**/
	public function update_order( $order_id )
	{
		$this->debug( 'Update order? %s is asking.', current_action() );

		if ( isset( $this->__syncing_order ) )
			return;

		wp_cache_flush();

		// The _POST is necessary for later WC use, after sync.
		$this->__syncing_post = $_POST;

		// Do we sync orders?
		$sync_orders = $this->get_site_option( 'sync_orders' );
		// Allow other plugins to override the order sync setting. No fancy schmancy action needed. Not for something this simple.
		$sync_order = apply_filters( 'bc_woocommerce_sync_order', $sync_orders, $order_id );
		if ( ! $sync_order )
			return $this->debug( 'The filter said not to sync the order.' );

		$this->__syncing_order = true;

		$this->debug( 'Syncing order %s.', $order_id );
		$bc = ThreeWP_Broadcast();

		$order = wc_get_order( $order_id );

		$order_bcd = $bc->get_post_broadcast_data( get_current_blog_id(), $order_id );

		// Is this order already linked in any direction?
		if ( $order_bcd->has_linked_children() || $order_bcd->get_linked_parent() !== false )
		{
			if ( $order_bcd->has_linked_children() )
			{
				$this->debug( 'This order is a parent. Update its children.' );
				$children = $order_bcd->get_linked_children();
				$bc->api()->update_children( $order_id );
			}
			else
			{
				if ( ! $this->back_to_parent() )
					return $this->debug( 'The Back To Parent add-on must be enabled in order to sync orders back to the parent.' );
				else
				{
					$this->debug( 'This order is a child. Update the parent.' );
					$this->back_to_parent()->back_to_parent( $order_id );

					// Refetch the bcd since B2P will have messed it up.
					$bc->broadcast_data_cache()->flush();
					$order_bcd = $bc->get_post_broadcast_data( get_current_blog_id(), $order_id );

					$this->debug( "Update the parent orders' children." );
					$parent = $order_bcd->get_linked_parent();

					switch_to_blog( $parent[ 'blog_id' ] );
					$bc->api()->update_children( $parent[ 'post_id' ] );
					restore_current_blog();
				}
			}
		}
		else
		{
			// We should sync this order.
			// 1. Go through all of the order items.
			// 2. Find the linked blog of all products. They must all match.
			// If product has a parent:
			// 3. Broadcast the order to the parent blog.
			// 4. Delete the broadcast data so that the order can be broadcasted as a parent.
			// End if.
			// 5. Create new broadcast data, with the order originating from the parent blog.
			// 6. Broadcast the order to all child blogs of the product(s). We assume all products exist on all blogs.

			// Time to find out whether this post should be linked to a parent blog.
			$parent_blog = 0;
			$child_products = [];
			$this->debug( 'This order is not linked. Should we link it? Begin search for parent of product(s).' );
			$items = $this->get_order_items( $order );
			$this->debug( 'The order has %s items.', count( $items ) );
			foreach( $items as $item_id => $item )
			{
				$product_id = $item->meta->_product_id;
				$this->debug( 'Item %s has product %s.', $item_id, $product_id );

				// Load the broadcast data for this product.
				$bcd = $bc->get_post_broadcast_data( get_current_blog_id(), $product_id );
				$this->debug( 'Loaded broadcast data: %s', $bcd );

				$this_product_parent = 0;
				$this_product_children = [];
				$parent = $bcd->get_linked_parent();
				if ( $parent !== false )
				{
					// This is a child product. We now know where the parent is.
					$this_product_parent = $parent[ 'blog_id' ];

					// Retrieve the parent product BCD to find out which children there are.
					$parent_product_bcd = $bc->get_post_broadcast_data( $parent[ 'blog_id' ], $parent[ 'post_id' ] );
					$this_product_children = array_keys( $parent_product_bcd->get_linked_children() );
				}

				if ( $bcd->has_linked_children() )
				{
					// This is a parent product. The parent blog is here.
					$this_product_parent = get_current_blog_id();
					$this_product_children = array_keys( $bcd->get_linked_children() );
				}

				if ( $this_product_parent == 0 )
					return $this->debug( 'This product is not broadcasted, so there is nowhere to broadcast the order.' );

				if ( count( $this_product_children ) < 1 )
					return $this->debug( 'The product has no children anywhere. Nothing to do.' );

				$this->debug( 'This product has the parent blog %s and is broadcasted to blogs %s', $this_product_parent, $this_product_children );

				if ( $parent_blog == 0 )
				{
					$parent_blog = $this_product_parent;
					$child_products = $this_product_children;
				}

				if ( $parent_blog != $this_product_parent )
					return $this->debug( "This product's parent blog does not match the one found previously: %s", $parent_blog );
				if ( count( array_diff( $child_products, $this_product_children ) ) > 0 )
					return $this->debug( "This product's children are not the same as those found previously: %s", $child_products );
			}

			if ( $parent_blog == 0 )
				return $this->debug( 'No broadcasted products were found in the items.' );

			$this->debug( 'All products come from the parent blog %s and are broadcasted to %s', $parent_blog, $child_products );

			if ( $parent_blog != get_current_blog_id() )
			{
				$this->debug( 'Broadcasting order to product parent blog %s', $parent_blog );
				$new_bcd = $bc->api()->broadcast_children( $order_id, $parent_blog );

				$order_parent_bcd = $new_bcd->broadcast_data;
				$this->debug( 'Returned broadcast data for parent order is %s', $order_parent_bcd );

				// Note that the parent is actually linked as a child. We'll switch this later.
				$children = $order_parent_bcd->get_linked_children();
				$order_parent = [ 'blog_id' => key( $children ), 'post_id' => reset( $children ) ];
				$this->debug( 'New parent order data is %s', $order_parent );

				$this->debug( 'Delete broadcasting data links.' );
				$bc->delete_post_broadcast_data( $order_parent[ 'blog_id' ], $order_parent[ 'post_id' ] );
				$bc->delete_post_broadcast_data( get_current_blog_id(), $order_id );

				$this->debug( 'Link parent order to this child order.' );
				$new_parent_bcd = $bc->get_post_broadcast_data( $order_parent[ 'blog_id' ], $order_parent[ 'post_id' ] );
				$new_parent_bcd->add_linked_child( get_current_blog_id(), $order_id );
				$bc->set_post_broadcast_data( $order_parent[ 'blog_id' ], $order_parent[ 'post_id' ], $new_parent_bcd );

				$this->debug( 'Link this child order to the parent.' );
				$new_child_bcd = $bc->get_post_broadcast_data( get_current_blog_id(), $order_id );
				$new_child_bcd->set_linked_parent( $order_parent[ 'blog_id' ], $order_parent[ 'post_id' ] );
				$bc->set_post_broadcast_data( get_current_blog_id(), $order_id, $new_child_bcd );

				$this->debug( 'Broadcast the order to all product child blogs.' );
				// And now broadcast the order to all product children blogs.
				switch_to_blog( $parent_blog );
				$bc->api()->broadcast_children( $order_parent[ 'post_id' ], $child_products );
				restore_current_blog();
			}
			else
			{
				// Broadcast this order to all child blogs.
				$bc->api()->broadcast_children( $order_id, $child_products );
			}
		}

		$_POST = $this->__syncing_post;

		$this->__syncing_order = false;
	}

	/**
		@brief		Workaround for WC v3 product saving.
		@details	We have to allow the product to save itself before broadcasting.
		@since		2017-04-05 10:04:08
	**/
	public function woocommerce_admin_process_product_object()
	{
		// Check the WC version, since they seem to enjoy changing their product saving methods all the time.

		if ( ! function_exists( 'WC' ) )
			return;

		// Not needed for 3.0.4+
		if ( version_compare( WC()->version, '3.0.3' ) > 0 )
			return $this->debug( 'v3 save_post workaround not needed.' );

		// Not needed for less than 3.0.0
		if ( version_compare( WC()->version, '3.0.0' ) < 0 )
			return $this->debug( 'v3 save_post workaround not needed.' );

		$this->debug( 'Enabling v3 save_post workaround.' );

		$this->__woocommerce_admin_process_product_object = true;
	}

	/**
		@brief		Sync the stock number between linked products.
		@since		2016-01-26 16:53:11
	**/
	public function woocommerce_product_set_stock( $product )
	{
		if ( static::$syncing_stock )
			return $this->debug( 'Already syncing.' );

		$this->debug( 'Setting stock for %s', $product->get_id() );

		// Do we sync stock?
		$sync_stock = $this->get_site_option( 'sync_stock' );
		// Allow other plugins to override the stock sync setting. No fancy schmancy action needed. Not for something this simple.
		$sync_stock = apply_filters( 'bc_woocommerce_sync_stock', $sync_stock, $product );
		if ( ! $sync_stock )
			return $this->debug( 'The filter said not to sync the stock.' );

		// Not syncing? Now we are!
		static::$syncing_stock = true;

		// Get the product or variation ID.
		if ( isset( $product->variation_id ) )
			$post_id = $product->variation_id;
		else
			$post_id = $product->get_id();

		$new_stock = $product->get_stock_quantity();

		$action = new \threewp_broadcast\actions\each_linked_post();
		$action->post_id = $post_id;
		$action->add_callback( function( $o ) use ( $new_stock )
		{
			// Load the product on this blog.
			$product = wc_get_product( $o->post_id );
			// And tell the product to set its new stock.
			$this->debug( 'Setting stock for product %s on blog %s to %s.', $o->post_id, get_current_blog_id(), $new_stock );
			$product->set_stock( $new_stock );
		} );
		$action->execute();

		// We're done syncing.
		static::$syncing_stock = false;
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- SAVE (even though S comes after R, it is more logical for save to come first.
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Handle AutomateWoo workflows
		@since		2016-10-05 17:13:39
	**/
	public function maybe_save_automatewoo_workflow( $bcd )
	{
		if ( $bcd->post->post_type != 'aw_workflow' )
			return;

		$aw = ThreeWP_Broadcast()->collection();
		$bcd->woocommerce->automatewoo = $aw;

		$trigger_options = $bcd->custom_fields()->get_single( 'trigger_options' );
		$trigger_options = maybe_unserialize( $trigger_options );
		$trigger_options = (object) $trigger_options;

		$this->debug( 'AutomateWoo: Trigger options: %s', $trigger_options );

		if ( isset( $trigger_options->category ) )
		{
			$value = $trigger_options->category;
			$this->debug( 'Saving trigger category %s', $value );
			$aw->set( 'product_cat', $value );
		}

		if ( isset( $trigger_options->tag ) )
		{
			$value = $trigger_options->tag;
			$this->debug( 'Saving trigger tag %s', $value );
			$aw->set( 'product_tag', $value );
		}

		if ( isset( $trigger_options->term ) )
		{
			$value = $trigger_options->term;
			$this->debug( 'Saving trigger term %s', $value );
			$aw->set( 'term', $value );
		}

		if ( isset( $trigger_options->product ) )
		{
			$product_id = $trigger_options->product;
			$value = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $product_id );
			$this->debug( 'Saving trigger product %s', $product_id );
			$aw->set( 'product', $value );
		}
	}

	/**
		@brief		Save the membership plan data.
		@since		2015-12-23 18:32:01
	**/
	public function maybe_save_membership_plan( $bcd )
	{
		if ( $bcd->post->post_type != 'wc_membership_plan' )
			return;

		$rules = get_option( 'wc_memberships_rules' );

		if ( ! isset( $bcd->woocommerce ) )
			$bcd->woocommerce = (object)[];

		$mp = (object)[];
		$bcd->woocommerce->membership_plan = $mp;
		$mp->rules = [];

		// Save the bcd for all of the objects.
		$mp->broadcast_data = ThreeWP_Broadcast()->collection();
		foreach( $rules as $rule )
			if ( $rule[ 'membership_plan_id' ] == $bcd->post->ID )
			{
				$mp->rules []= $rule;
				foreach( $rule[ 'object_ids' ] as $object_id )
				{
					$object_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $object_id );
					$mp->broadcast_data->set( $object_id, $object_bcd );
				}
			}
	}

	/**
		@brief		Handle the broadcasting of an order.
		@since		2016-02-14 19:10:26
	**/
	public function maybe_save_order( $bcd )
	{
		if ( $bcd->post->post_type != 'shop_order' )
			return $this->debug( 'Post is not an order.' );

		if ( ! isset( $bcd->woocommerce ) )
			$bcd->woocommerce = (object)[];

		// Save info about the order.
		$bcd->woocommerce->order = new \WC_Order( $bcd->post->ID );
		$this->debug( 'Saving order info: %s', $bcd->woocommerce->order );

		$bcd->woocommerce->order_items = $this->get_order_items( $bcd->woocommerce->order );
		$this->debug( 'Saving order items info: %s', $bcd->woocommerce->order_items );

		// Save the broadcasting data for each item's product, so that we will know if the product exists on the child blog.
		$bcd->woocommerce->order_item_bcd = $this->collect_order_item_product_bcds( $bcd->woocommerce->order_items );
		$this->debug( 'Saving broadcast data for all order item products: %s', $bcd->woocommerce->order_item_bcd );

		// Any refunds?
		$refunds = get_posts( [
			'post_parent' => $bcd->post->ID,
			'post_type' => 'shop_order_refund',
			'post_status' => 'wc-completed',
		] );
		$this->debug( '%d refunds found: %s', count( $refunds ), $refunds );
		$bcd->woocommerce->order_refunds = $refunds;
	}

	/**
		@brief		Handle the broadcasting of a product.
		@since		2016-02-14 19:09:44
	**/
	public function maybe_save_product( $bcd )
	{
		// WC product post type?
		if ( ! in_array( $bcd->post->post_type, [ 'product', 'product_variation' ] ) )
			return $this->debug( 'Post is not a product.' );

		if ( ! isset( $bcd->woocommerce ) )
			$bcd->woocommerce = (object)[];

		$bcd->woocommerce->product = wc_get_product( $bcd->post->ID );
		$this->debug( 'The product is: %s', $bcd->woocommerce->product );
		$bcd->woocommerce->term_metas = new Term_Metas();

		$this->save_attribute_taxonomies( $bcd );
		$this->save_atum_inventory( $bcd );
		$this->save_category_images( $bcd );
		$this->save_german_market( $bcd );
		$this->save_image_gallery( $bcd );
		$this->save_product_references( $bcd, '_children' );
		$this->save_product_references( $bcd, '_crosssell_ids' );
		$this->save_product_references( $bcd, '_upsell_ids' );
		$this->save_swatch_images( $bcd );
		$this->save_swatch_taxonomy_images( $bcd );
		$this->save_variations( $bcd );
		$this->save_yith_tabs( $bcd );
	}

	/**
		@brief		Saves any attribute taxonomies.
		@since		2015-07-10 13:48:25
	**/
	public function save_attribute_taxonomies( $bcd )
	{
		// Save the attribute taxonomies.
		$bcd->woocommerce->attribute_taxonomies = wc_get_attribute_taxonomies();
	}

	/**
		@brief		Save the info in the atum inventory table.
		@since		2020-01-23 09:26:37
	**/
	public function save_atum_inventory( $bcd )
	{
		$table = static::get_prefixed_table_name( 'atum_product_data' );
		if ( ! $this->database_table_exists( $table ) )
			return;
		$bcd->woocommerce->atum_inventory = ThreeWP_Broadcast()->collection();

		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `product_id` = '%s'", $table, $bcd->post->ID );
		$row = $wpdb->get_row( $query );
		if ( ! $row )
			return;
		$this->debug( 'Saving atum_product_data %s', $row );
		$bcd->woocommerce->atum_inventory->set( 'atum_product_data', $row );
	}

	/**
		@brief		Save category image data.
		@since		2015-07-10 13:48:52
	**/
	public function save_category_images( $bcd )
	{
		// If there are product categories involved...
		if ( ! isset( $bcd->parent_post_taxonomies[ 'product_cat' ] ) )
		{
			$this->debug( 'No product catgories available.' );
			return;
		}

		$images = $bcd->woocommerce->term_metas;
		foreach( $bcd->parent_post_taxonomies[ 'product_cat' ] as $category )
		{
			// Note the images down.
			$term_id = $category->term_id;
			$image_id = absint( get_term_meta( $term_id, 'thumbnail_id', true ) );
			$this->debug( 'Product category image is %s', $image_id );
			if ( $image_id < 1 )
				continue;

			$this->debug( 'Adding product category image %.', $image_id, $category->name );
			$images->add_image( $term_id, 'thumbnail_id', $image_id );
			$bcd->try_add_attachment( $image_id );
		}
	}

	/**
		@brief		Save info for the German Market plugin.
		@details	The delivery times is special. It is a taxonomy, but the taxonomy terms are never assigned to the product.

		Instead, the delivery time is saved as the term_id in the postmeta. So we have to sync ALL of the terms, just to get the equivalent term ID.

		What a mess. What was the author thinking?

		@since		2015-08-28 16:35:45
	**/
	public function save_german_market( $bcd )
	{
		$this->debug( 'Save German market.' );
		$delivery_taxonomy = 'product_delivery_times';
		$taxonomy_terms = get_terms( [ $delivery_taxonomy ], [
			'hide_empty' => false,
		] );

		if ( is_wp_error( $taxonomy_terms ) )
			return;

		if ( count( $taxonomy_terms ) < 1 )
			return;

		$this->debug( 'Found delivery times: %s', $taxonomy_terms );

		// Delivery times found! Add them _all_.
		$bcd->parent_blog_taxonomies[ $delivery_taxonomy ] = [
			'taxonomy' => null,
			'terms' => $this->array_rekey( $taxonomy_terms, 'term_id' )
		];

		// And tell Broadcast to sync all of the taxonomies.
		$bcd->add_new_taxonomies = true;
	}

	/**
		@brief		Save the image gallery.
		@since		2017-04-04 07:39:29
	**/
	public function save_image_gallery( $bcd )
	{
		// Inform Broadcast of the images.
		$ids = $bcd->custom_fields()->get_single( '_product_image_gallery' );
		$ids = explode( ',', $ids );
		if ( count( $ids ) < 1 )
			return;
		$this->debug( 'Adding image gallery: %s', $ids );
		foreach( $ids as $id )
			$bcd->try_add_attachment( $id );
	}

	/**
		@brief		Save references to other products.
		@since		2015-08-05 14:29:24
	**/
	public function save_product_references( $bcd, $type )
	{
		$ids = $bcd->custom_fields()->get_single( $type );
		if ( ! $ids )
			return $this->debug( 'No %s products.', $type );

		$ids = maybe_unserialize( $ids );

		if ( ! is_array( $ids ) )
			return $this->debug( 'Not an array.' );

		$products = ThreeWP_Broadcast()->collection();

		foreach( $ids as $id )
			$products->set( $id, ThreeWP_Broadcast()->get_post_broadcast_data( $bcd->parent_blog_id, $id ) );

		$bcd->woocommerce->$type = $products;
	}

	/**
		@brief		Save the swatch images, if any.
		@since		2015-07-14 17:16:51
	**/
	public function save_swatch_images( $bcd )
	{
		if ( ! $bcd->custom_fields()->has( '_swatch_type_options' ) )
			return $this->debug( 'No swatches found.' );

		$options = maybe_unserialize( $bcd->custom_fields()->get_single( '_swatch_type_options' ) );
		$bcd->woocommerce->swatch_type_options = $options;
		$this->debug( 'Found swatch: %s', $options );

		foreach( $options as $option_id => $data )
		{
			foreach( $data[ 'attributes' ] as $attribute_key => $attribute_data )
			{
				if ( ! isset( $attribute_data[ 'image' ] ) )
					continue;
				$image_id = absint( $attribute_data[ 'image' ] );
				$this->debug( 'Found image %s.', $image_id );
				$bcd->try_add_attachment( $image_id );
			}
		}
	}

	/**
		@brief		Save the images associated with swatch taxonomies.
		@since		2015-07-14 18:47:15
	**/
	public function save_swatch_taxonomy_images( $bcd )
	{
		$images = $bcd->woocommerce->term_metas;

		// This is an inefficient way of looking for swatch terms, but there is no other way.
		foreach( $bcd->parent_post_taxonomies as $taxonomy => $terms )
			foreach( $terms as $term )
			{
				$term_id = $term->term_id;

				// Search for the keys.
				$metas = get_metadata( 'woocommerce_term', $term_id );

				if ( ! is_array( $metas ) )
					continue;

				foreach( $metas as $meta_key => $meta_value )
				{
					if ( strpos( $meta_key, 'swatches_id_photo' ) === false )
						continue;
					$image_id = reset( $meta_value );
					$this->debug( 'Found a swatch taxonomy image: %s', $image_id );
					$images->add_image( $term_id, $meta_key, $image_id );
					$bcd->try_add_attachment( $image_id );

					// Save the rest of the terms.
					foreach( $metas as $this_key => $this_value )
						if ( $this_key != $meta_key )
						{
							$this_value = reset( $this_value );
							$this->debug( 'Also saving term meta: %s: %s', $this_key, $this_value );
							$images->add_value( $term_id, $this_key, $this_value );
						}
				}
			}
	}

	/**
		@brief		Save the variations, if any.
		@since		2015-07-10 13:50:42
	**/
	public function save_variations( $bcd )
	{
		if ( ! is_object( $bcd->woocommerce->product ) )
			return $this->debug( 'No product.' );

		// This is a product. Is it a variation?
		if ( ! $bcd->woocommerce->product->is_type( 'variable' ) )
			return $this->debug( 'Post is not a variation.' );

		// Instead of get_available_variations(), force ALL children to be retrieved.
		$children = $bcd->woocommerce->product->get_children();
		$variations = [];
		foreach( $children as $child_id )
		{
			$variation = wc_get_product( $child_id );
			if ( ! $variation || $variation->post_type != 'product_variation' )
				continue;
			$variations []= get_post( $child_id );
		}

		if ( count( $variations ) < 1 )
		{
			$this->debug( 'Product does not have any variations.' );
			return;
		}
		else
		{
			$bcd->woocommerce->variations = $variations;
			$this->debug( '%s variations found: %s',
				count( $bcd->woocommerce->variations ),
				$bcd->woocommerce->variations
			);
		}
	}

	/**
		@brief		Save Yith Tab Manager data, if found.
		@since		2018-01-22 15:16:44
	**/
	public function save_yith_tabs( $bcd )
	{
		if ( ! class_exists( 'YITH_WCTM_Post_Type' ) )
			return;
		$tabs = \YITH_WCTM_Post_Type::get_instance();
		$tabs = $tabs->get_tabs();

		if ( count( $tabs ) < 1 )
			return;

		$yt = $bcd->woocommerce->yith_tabs = ThreeWP_Broadcast()->collection();
		foreach( $tabs as $tab )
		{
			$tab_id = $tab[ 'id' ];
			$tab_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $tab_id );
			$yt->collection( 'broadcast_data' )
			->set( $tab_id, $tab_bcd );
		}

		$this->debug( 'Yith tabs found: %s', $yt );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- RESTORE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Restore AutomateWoo data.
		@since		2016-10-05 17:27:19
	**/
	public function maybe_restore_automatewoo_workflow( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->automatewoo ) )
			return;

		$aw = $bcd->woocommerce->automatewoo;

		$to_sync = [];

		if ( $aw->has( 'product_cat' ) )
			$to_sync []= 'product_cat';
		if ( $aw->has( 'product_tag' ) )
			$to_sync []= 'product_tag';
		if ( $aw->has( 'term' ) )
		{
			$string = $aw->get( 'term' );
			$all_terms = explode( ',', $string );
			foreach( $all_terms as $a_term )
			{
				$term_parts = explode( '|', $a_term );
				$to_sync []= $term_parts[ 1 ];
			}
		}

		$to_sync = array_flip( $to_sync );
		$to_sync = array_flip( $to_sync );

		$this->debug( 'AW: Taxonomies to sync: %s', $to_sync );

		$synced_taxonomies = ThreeWP_Broadcast()->collection();

		foreach( $to_sync as $taxonomy_to_sync )
		{
			$this->debug( 'AW: Syncing %s', $taxonomy_to_sync );

			// Fake a product.
			$post = (object)[
				'ID' => 0,
				'post_type' => 'product',
				'post_status' => 'publish',
			];

			// Sync the category in order to get their new IDs.
			switch_to_blog( $bcd->parent_blog_id );

			$taxonomy_bcd = new \threewp_broadcast\broadcasting_data( [
				'parent_post_id' => -1,
				'post' => $post,
			] );
			$taxonomy_bcd->add_new_taxonomies = true;
			unset( $taxonomy_bcd->post->ID );		// This is so that collect_post_type_taxonomies returns ALL the terms, not just those from the non-existent post.

			ThreeWP_Broadcast()->collect_post_type_taxonomies( $taxonomy_bcd );

			restore_current_blog();

			ThreeWP_Broadcast()->sync_terms( $taxonomy_bcd, $taxonomy_to_sync );

			$synced_taxonomies->set( $taxonomy_to_sync, $taxonomy_bcd );
		}

		$trigger_options = $bcd->custom_fields()
			->child_fields()
			->get( 'trigger_options' );
		$trigger_options = reset( $trigger_options );
		$trigger_options = maybe_unserialize( $trigger_options );

		// We have to handle these separately because they don't use the same array key as the taxonomy name.
		foreach( [
			'product_cat' => 'category',
			'product_tag' => 'tag',
		] as $taxonomy_to_sync => $name )
		{
			if ( $aw->has( $taxonomy_to_sync ) )
			{
				// Now we can extract the new term ID.
				$old_term_id = $aw->get( $taxonomy_to_sync );
				$new_term_id = $synced_taxonomies->get( $taxonomy_to_sync )->terms()->get( $old_term_id );
				$this->debug( 'AW: Updating %s from %s to %s',
					$taxonomy_to_sync,
					$old_term_id,
					$new_term_id
				);
				$trigger_options[ $name ] = $new_term_id;
			}
		}

		if ( $aw->has( 'product' ) )
		{
			$product_bcd = $aw->get( 'product' );
			$child_product_id = $product_bcd->get_linked_post_on_this_blog();
			if ( $child_product_id > 0 )
			{
				$this->debug( 'AW: The new product trigger will be %s', $child_product_id );
				$trigger_options[ 'product' ] = $child_product_id;
			}
		}

		if ( $aw->has( 'term' ) )
		{
			$new_all_terms = [];
			foreach( $all_terms as $a_term )
			{
				$term_parts = explode( '|', $a_term );
				$to_sync []= $term_parts[ 1 ];

				$old_term_id = $term_parts[ 0 ];
				$new_term_id = $synced_taxonomies->get( $term_parts[ 1 ] )->terms()->get( $old_term_id );
				$new_term_parts = $new_term_id . '|' . $term_parts[ 1 ];
				$new_all_terms []= $new_term_parts;
			}
			$trigger_options[ 'term' ] = implode( ',', $new_all_terms );
		}

		$this->debug( 'AW: New trigger options: %s', $trigger_options );
		$bcd->custom_fields()->child_fields()->update_meta( 'trigger_options', $trigger_options );
	}

	/**
		@brief		Restore the membership plan data.
		@since		2015-12-23 18:44:04
	**/
	public function maybe_restore_membership_plan( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->membership_plan ) )
			return $this->debug( 'No membership plan.' );

		$mp = $bcd->woocommerce->membership_plan;

		$rules = get_option( 'wc_memberships_rules', [] );
		$this->debug( 'Membership rules on this blog: %s', $rules );

		foreach( $rules as $index => $rule )
		{
			if ( $rule[ 'membership_plan_id' ] == $bcd->new_post( 'ID' ) )
				unset( $rules[ $index ] );
		}

		// Re-add all the rules from the parent.
		foreach( $mp->rules as $rule )
		{
			// Change the ID
			$rule[ 'membership_plan_id' ] = $bcd->new_post( 'ID' );

			// And fix the object IDs.
			$new_object_ids = [];
			foreach( $rule[ 'object_ids' ] as $object_id )
			{
				// Is the object ID broadcasted?
				$object_bcd = $mp->broadcast_data->Get( $object_id, false );
				if ( ! $object_bcd )
					continue;
				// It is. Does it have a child here?
				$child_id = $object_bcd->get_linked_child_on_this_blog();
				if ( ! $child_id )
					continue;
				$new_object_ids []= $child_id;
			}
			$rule[ 'object_ids' ] = $new_object_ids;
			$rules []= $rule;
		}

		$this->debug( 'Replacing wc_memberships_rules with: %s', $rules );
		update_option( 'wc_memberships_rules', $rules );
	}

	/**
		@brief		Handle the restoring of an order.
		@since		2016-02-14 19:53:00
	**/
	public function maybe_restore_order( $bcd )
	{
		$this->debug( 'Maybe restoring order.' );
		if ( ! isset( $bcd->woocommerce->order ) )
			return;

		// An order can only be broadcasted if _all_ of the products are available on this blog.
		foreach( $bcd->woocommerce->order_item_bcd as $item_id => $item_bcd )
		{
			if ( ! $item_bcd->get_linked_post_on_this_blog() )
				return $this->debug( 'Product %s does not exist on this blog. The order cannot be broadcasted.', $bcd->woocommerce->order_item[ $item_id ][ 'product_id' ] );
		}

		$order_id = $bcd->new_post( 'ID' );
		$order = new \WC_Order( $order_id );
		$order_items = $this->get_order_items( $order );

		// Delete all order items.
		foreach( $order_items as $order_item_id => $order_item )
		{
			$this->debug( 'Deleting order item %d', $order_item_id );
			wc_delete_order_item( $order_item_id );
		}

		// Add the new items from the parent order that don't exist on the child order.
		$this->debug( 'Adding items %s', $bcd->woocommerce->order_items );
		foreach( $bcd->woocommerce->order_items as $parent_item )
		{
			$new_item_id = wc_add_order_item( $order_id, [
				'order_item_name' => $parent_item->name,
			] );
			$this->debug( 'Added item %d to order.', $new_item_id );

			$this->debug( 'Handling database meta: %s', $parent_item->meta );

			// Update all of the meta.
			foreach( $parent_item->meta as $key => $value )
			{
				if ( in_array( $key, [ '_product_id', '_variation_id' ] ) )
					if ( isset( $bcd->woocommerce->order_item_bcd->$value ) )
						$value = $bcd->woocommerce->order_item_bcd->$value->get_linked_post_on_this_blog();
				wc_update_order_item_meta( $new_item_id, $key, $value );
				$this->debug( 'Updated item meta %s for item %s to %s', $key, $new_item_id, $value );
			}
		}

		// Delete any existing refunds.
		$refunds = get_posts( [
			'post_parent' => $order_id,
			'post_type' => 'shop_order_refund',
			'post_status' => 'wc-completed',
		] );
		foreach( $refunds as $refund )
		{
			$this->debug( 'Deleting refund %d', $refund->ID );
			wp_delete_post( $refund->ID );
		}

		// And add the new ones.
		foreach( $bcd->woocommerce->order_refunds as $refund )
		{
			// Broadcast all refunds.
			$this->debug( 'Broadcasting refund %d', $refund->ID );
			switch_to_blog( $bcd->parent_blog_id );
			$refund_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $refund->ID, [ $bcd->current_child_blog_id ] );
			restore_current_blog();
		}
	}

	/**
		@brief		Handle the restoring of a product.
		@since		2016-02-14 19:11:57
	**/
	public function maybe_restore_product( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->product ) )
			return;

		$this->restore_attribute_taxonomies( $bcd );
		$this->restore_atum_inventory( $bcd );
		$this->restore_composite_product( $bcd );
		$this->restore_german_market( $bcd );
		$this->restore_image_gallery( $bcd );
		$this->restore_product_references( $bcd, '_children' );
		$this->restore_product_references( $bcd, '_crosssell_ids' );
		$this->restore_product_references( $bcd, '_upsell_ids' );
		$this->restore_swatch_images( $bcd );
		$this->restore_term_metas( $bcd );
		$this->recount_terms();
		$this->restore_variations( $bcd );
		$this->restore_yith_badges( $bcd );
		$this->restore_yith_tabs( $bcd );
	}

	/**
		@brief		Handle the attribute taxonomies for this product.
		@since		2014-09-04 20:06:03
	**/
	public function restore_attribute_taxonomies( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->attribute_taxonomies ) )
			return;

		global $wpdb;

		$this->debug( 'Handling attribute taxonomies.' );

		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach( $bcd->woocommerce->attribute_taxonomies as $at )
		{
			$found = false;
			// Look for an AT with the same name.
			foreach( $attribute_taxonomies as $existing_at )
			{
				if ( $existing_at->attribute_name != $at->attribute_name )
					continue;
				// We've found it!
				$found = true;
				$updated_attribute = clone( $at );
				unset( $updated_attribute->attribute_id );
				$this->debug( 'Updating attribute taxonomy %s (%s)', $updated_attribute->attribute_name, $existing_at->attribute_id );
				$updated_attribute = (array)$updated_attribute;
				$wpdb->update( $wpdb->prefix . 'woocommerce_attribute_taxonomies',
					$updated_attribute,
					[ 'attribute_id' => $existing_at->attribute_id ]
				);
			}

			if ( ! $found )
			{
				$new_attribute = clone( $at );
				unset( $new_attribute->attribute_id );
				$this->debug( 'Creating attribute taxonomy %s', $new_attribute->attribute_name );
				$new_attribute = (array)$new_attribute;
				$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $new_attribute );
				do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $new_attribute );
			}
		}

		// After updating the taxonomies we have to clear the transient else they won't be visible.
		$transient_name = 'wc_attribute_taxonomies';
		delete_transient( $transient_name );
	}

	/**
		@brief		Restore the atum inventory data.
		@since		2020-01-23 09:45:36
	**/
	public function restore_atum_inventory( $bcd )
	{
		$table = static::get_prefixed_table_name( 'atum_product_data' );
		if ( ! $this->database_table_exists( $table ) )
			return $this->debug( 'No %s table on this blog.', $table );

		if ( ! isset( $bcd->woocommerce->atum_inventory ) )
			return;

		$data = $bcd->woocommerce->atum_inventory->get( 'atum_product_data' );
		if ( $data )
		{
			$data->product_id = $bcd->new_post( 'ID' );
			if ( $data->supplier_id > 0 )
				$data->supplier_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $data->supplier_id, get_current_blog_id() );

			// Maybe copy the supplier.
			global $wpdb;

			// Delete any existing row.
			$wpdb->delete( $table, [ 'product_id' => $data->product_id ] );

			// Insert this new row.
			$this->debug( 'Inserting atum_product_data %s', $data );
			$wpdb->insert( $table, (array)$data );
		}
	}

	/**
		@brief		Restore a composite product.
		@since		2019-06-10 17:29:43
	**/
	public function restore_composite_product( $bcd )
	{
		// These two fields contain the ID of a product.
		foreach( [
			'default_id_categories',
			'default_id_products',
		] as $key )
		{
			$old_product_id = $bcd->custom_fields()->get_single( $key );
			if ( ! $old_product_id )
				continue;
			$new_product_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_product_id, get_current_blog_id() );
			$this->debug( 'Composite: Updating %s to %s', $key, $new_product_id );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $new_product_id );
		}

		// The assigned IDs is stored in the bto_data custom field.
		$data_key = '_bto_data';
		$bto_data = $bcd->custom_fields()->get_single( $data_key );
		$bto_data = maybe_unserialize( $bto_data );

		if ( ! is_array( $bto_data ) )
			return;

		$key = 'assigned_ids';
		if ( isset( $bto_data[ $key ] ) )
		{
			$new_assigned_ids = [];
			foreach( $bto_data[ $key ] as $assigned_id )
			{
				$new_assigned_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $assigned_id, get_current_blog_id() );
				$new_assigned_ids []= $new_assigned_id;
			}
			$bto_data[ $key ] = $new_assigned_ids;
			$this->debug( 'Restoring composite %s to %s', $data_key, $bto_data );
			$bcd->custom_fields()->child_fields()->update_meta( $data_key, $bto_data );
		}
	}

	/**
		@brief		Handle the WooCommerce German Market plugin.
		@details
		@since		2015-08-28 16:15:38
	**/
	public function restore_german_market( $bcd )
	{
		// The _lieferzeit post meta contains a term ID.
		$key = '_lieferzeit';
		$term_id = $bcd->custom_fields()->get_single( $key );
		if ( $term_id > 0 )
		{
			$new_term_id = $bcd->terms()->get( $term_id );
			$this->debug( 'German Market: Updating %s from %s to %s', $key, $term_id, $new_term_id );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $new_term_id );
		}
	}

	/**
		@brief		Restore the image gallery.
		@since		2017-04-04 07:41:12
	**/
	public function restore_image_gallery( $bcd )
	{
		$cf = $bcd->custom_fields()
			->child_fields();
		$old_ids = $cf->get( '_product_image_gallery' );
		if ( ! $old_ids )
			return;
		$old_ids = reset( $old_ids );
		$old_ids = explode( ',', $old_ids );
		$new_ids = [];
		foreach( $old_ids as $old_id )
			$new_ids []= $bcd->copied_attachments()->get( $old_id );

		$new_ids = implode( ',', $new_ids );
		$this->debug( 'Replacing image gallery with %s', $new_ids );
		$cf->update_meta( '_product_image_gallery', $new_ids );
	}

	/**
		@brief		Restore the product references of a type.
		@since		2015-08-05 14:34:11
	**/
	public function restore_product_references( $bcd, $type )
	{
		if ( ! isset( $bcd->woocommerce->$type ) )
			return $this->debug( 'No product references of type %s', $type );

		$new_ids = [];
		foreach( $bcd->woocommerce->$type as $old_id => $bcd_data )
		{
			// Does this product exist on this blog?
			if ( ! $bcd_data->has_linked_child_on_this_blog() )
				continue;
			$new_id = $bcd_data->get_linked_child_on_this_blog();
			$new_ids []= $new_id;
		}

		$this->debug( 'Updating product references of type %s to %s', $type, $new_ids );

		$bcd->custom_fields()->child_fields()->update_meta( $type, $new_ids );
	}

	/**
		@brief		Restore the swatch images, if any.
		@since		2015-07-14 17:23:14
	**/
	public function restore_swatch_images( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->swatch_type_options ) )
			return;

		$this->debug( 'Restoring swatch images.' );

		$new_options = $bcd->woocommerce->swatch_type_options;
		foreach( $new_options as $option_id => $data )
		{
			foreach( $data[ 'attributes' ] as $attribute_key => $attribute_data )
			{
				if ( ! isset( $attribute_data[ 'image' ] ) )
					continue;
				$image_id = absint( $attribute_data[ 'image' ] );
				if ( ! $bcd->copied_attachments()->has( $image_id ) )
					continue;
				$new_image_id = $bcd->copied_attachments()->get( $image_id );
				$this->debug( 'Replacing image %s with %s.', $image_id, $new_image_id );
				$new_options[ $option_id ][ 'attributes' ][ $attribute_key ][ 'image' ] = $new_image_id;
			}
		}
		$this->debug( 'Updating swatch options: %s', $new_options );
		update_post_meta( $bcd->new_post( 'ID' ), '_swatch_type_options', $new_options );
	}

	/**
		@brief		Restore the term images.
		@since		2015-07-10 13:46:45
	**/
	public function restore_term_metas( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->term_metas ) )
			return;

		$term_metas = $bcd->woocommerce->term_metas;
		$this->debug( 'Restoring %s term metas.', count( $term_metas ) );

		foreach( $term_metas as $term_meta )
		{
			// Find the equivalent term on this blog.
			$new_term_id = $bcd->terms()->get( $term_meta->term_id );

			if ( $new_term_id < 1 )
			{
				$this->debug( 'Unable to find equivalent term for %s.', $term_meta->term_id );
				continue;
			}

			// Restoring images requires that their image ID is translated.
			if ( isset( $term_meta->image_id ) )
			{
				// Find the equivalent image on this blog.
				$new_meta_value = $bcd->copied_attachments()->get( $term_meta->image_id );
				if ( $new_meta_value < 1 )
					$this->debug( 'Unable to find new attachment ID for %s.', intval( $term_meta->image_id ) );
				else
					$this->debug( 'Updating meta %s for term %s / %s with thumbnail ID %s / %s.', $term_meta->key, $term_meta->term_id, $new_term_id, $term_meta->image_id, $new_meta_value );
			}

			// Restoring neutral meta values doesn't require anything special.
			if ( isset( $term_meta->value ) )
				$new_meta_value = $term_meta->value;

			$this->debug( 'Updating meta %s for term %s / %s with value %s.', $term_meta->key, $term_meta->term_id, $new_term_id, $new_meta_value );

			update_term_meta( $new_term_id, $term_meta->key, $new_meta_value );
		}
	}

	/**
		@brief		Handle the variations.
		@since		2014-09-04 20:04:52
	**/
	public function restore_variations( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->variations ) )
			return;

		// Delete all old variations that no longer have parent variations.
		$product = wc_get_product( $bcd->new_post( 'ID' ) );
		if ( $product && $product->is_type( 'variable' ) )
		{
			$children = $product->get_children();

			$children_to_delete = array_flip( $children );
			// Check which of the children still have parents.
			foreach( $bcd->woocommerce->variations as $variation )
			{
				$variation_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( $bcd->parent_blog_id, $variation->ID );
				$linked_child = $variation_bcd->get_linked_child_on_this_blog();
				unset( $children_to_delete[ $linked_child ] );
			}

			foreach( array_flip( $children_to_delete ) as $child_id )
			{
				$this->debug( 'Deleting unused product child %s.', $child_id );
				wp_delete_post( $child_id, true );
			}
		}

		// An array of old_variation_post_id => new_post_object.
		if ( ! isset( $bcd->woocommerce->variation_equivalents ) )
			$bcd->woocommerce->variation_equivalents = [];
		else
			$this->debug( 'Existing variations from partial broadcast (%d): %s',
				count( $bcd->woocommerce->variation_equivalents ),
				$bcd->woocommerce->variation_equivalents
			);

		$variation_counter = 0;
		$target_blog = get_current_blog_id();

		// Add the current variations
		foreach( $bcd->woocommerce->variations as $variation )
		{
			$skip = false;

			// Have we already done this variation?
			if ( isset( $bcd->woocommerce->variation_equivalents[ $variation->ID ] ) )
				if ( isset( $bcd->woocommerce->variation_equivalents[ $variation->ID ][ $target_blog ] ) )
					$skip = true;

			if ( $skip )
			{
				$this->debug( "Partial broadcast: Skipping variation %s which we've already done.", $variation->ID );
				continue;
			}

			$variation_counter++;

			$this->debug( 'Broadcasting variation %d %s', $variation_counter, $variation );

			switch_to_blog( $bcd->parent_blog_id );
			$variation_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $variation->ID, [ $target_blog ] );
			restore_current_blog();

			$new_post = $variation_bcd->new_post;
			$bcd->woocommerce->variation_equivalents[ $variation->ID ][ $target_blog ] = $new_post;

			$fixed_stuff = [
			  'ID' => $new_post->ID,
			  'post_title' => str_replace( '#' . $variation_bcd->post->ID, '#' . $new_post->ID, $new_post->post_title ),
			  // Have the new variation's parent point to the new post on this blog.
			  'post_parent' => $bcd->new_post( 'ID' ),
			  'post_name' => $variation->post_name,
			];
			$fixed_stuff[ 'post_name' ] = str_replace( 'product-' . $bcd->post->ID, 'product-' . $bcd->new_post( 'ID' ), $fixed_stuff[ 'post_name' ] );
			$fixed_stuff[ 'post_name' ] = str_replace( 'variation-' . $variation->ID, 'variation-' . $new_post->ID, $fixed_stuff[ 'post_name' ] );
			$this->debug( 'Replacing variation data: %s', $fixed_stuff );
			wp_update_post( $fixed_stuff );
			wc_delete_product_transients( $new_post->ID );

			// If using the queue, only do a few variations at a time.
			if ( isset( $bcd->using_queue ) )
				// Size must be greater than zero
				if ( $this->get_site_option( 'partial_broadcast_size' ) > 0 )
					// And we must have reached the size limit
					if ( $variation_counter >= $this->get_site_option( 'partial_broadcast_size' ) )
					{
						$this->debug( 'Setting partial broadcast and continuing later. %s variations already copied.', count( $bcd->woocommerce->variation_equivalents ) );
						$bcd->partial_broadcast()->set( 'woocommerce_variations', true );
						return;
					}
		}

		// Did we handle any partials?
		if ( $variation_counter < 1 )
		{
			$this->debug( 'No need for partial broadcast of variations.' );
			$bcd->partial_broadcast()->forget( 'woocommerce_variations' );
		}

		$this->debug( 'Equivalents (%d) %s',
			count( $bcd->woocommerce->variation_equivalents ),
			$bcd->woocommerce->variation_equivalents
		);

		foreach( [
			'_min_price_variation_id',
			'_max_price_variation_id',
			'_min_regular_price_variation_id',
			'_max_regular_price_variation_id',
			'_min_sale_price_variation_id',
			'_max_sale_price_variation_id',
		] as $key )
		{
			$value = $bcd->custom_fields()->get_single( $key );
			if ( $value == '' )
			{
				$this->debug( 'Warning: %s is empty.', $key );
				continue;
			}
			$new_post_id = $bcd->woocommerce->variation_equivalents[ $value ][ $target_blog ] ;
			$new_post_id = $new_post_id->ID;
			$this->debug( 'Updating %s post ID from %s to %s', $key, $value, $new_post_id );
			update_post_meta( $bcd->new_post( 'ID' ), $key, $new_post_id );
		}

		$transient_name = 'wc_product_children_ids_' . $bcd->new_post( 'ID' );
		delete_transient( $transient_name );

		wc_delete_product_transients( $bcd->new_post( 'ID' ) );
	}

	/**
		@brief		Restore any Yith Product Badges.
		@since		2019-03-06 14:59:36
	**/
	public function restore_yith_badges( $bcd )
	{
		$key = '_yith_wcbm_product_meta';
		$value = $bcd->custom_fields()->get_single( $key );
		$value = maybe_unserialize( $value );
		if ( ! is_array( $value ) )
			return;
		$this->debug( 'Found %s %s', $key, $value );
		if ( ! isset( $value[ 'id_badge' ] ) )
			return;
		$badge_ids = $value[ 'id_badge' ];
		$multiple = is_array( $badge_ids );
		if ( ! $multiple )
			$badge_ids = [ $badge_ids ];

		$new_badge_ids = [];
		foreach( $badge_ids as $badge_id )
		{
			$new_badge_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $badge_id, get_current_blog_id() );
			$new_badge_ids []= $new_badge_id;
		}

		if ( ! $multiple )
			$new_badge_ids = reset( $new_badge_ids );
			$value[ 'id_badge' ] = $new_badge_ids;

		$this->debug( 'Saving %s %s', $key, $value );
		$bcd->custom_fields()->child_fields()->update_meta( $key, $value );
	}

	/**
		@brief		restore_yith_tabs
		@since		2018-01-22 15:44:50
	**/
	public function restore_yith_tabs( $bcd )
	{
		if ( ! isset( $bcd->woocommerce->yith_tabs ) )
			return;
		$yt = $bcd->woocommerce->yith_tabs;
		$custom_fields = $bcd->custom_fields();
		$ccf = $custom_fields->child_fields();
		foreach ( $yt->collection( 'broadcast_data' ) as $tab_id => $tab_bcd )
		{
			// Is there a tab of this kind set for this product?
			// We can only figure this out if we happen to find the xxx_default_editor custom field.
			$key = sprintf( '%d_default_editor', $tab_id );
			$value = $custom_fields->get_single( $key );
			if ( $value === false )
				continue;
			// Find the equivalent tab ID on this blog, if any.
			$new_tab_id = $tab_bcd->get_linked_post_on_this_blog();
			if ( ! $new_tab_id )
			{
				$this->debug( 'There is no equivalent for tab %s on this blog.', $key );
				continue;
			}
			// Set the new key.
			$new_key = str_replace( $tab_id, $new_tab_id, $key );
			$this->debug( 'Replacing %s with %s', $key, $new_key );
			$ccf->update_meta( $new_key, $value );
			$ccf->delete_meta( $key );
		}
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC is always last.
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Return the back to parent instance, if any.
		@since		2016-04-28 14:00:08
	**/
	public function back_to_parent()
	{
		if ( ! class_exists( '\\threewp_broadcast\\premium_pack\\back_to_parent\\Back_To_Parent' ) )
			return false;

		$r = \threewp_broadcast\premium_pack\back_to_parent\Back_To_Parent::instance();
		return $r;
	}

	/**
		@brief		Return the broadcast datas of the order items.
		@since		2016-02-14 21:46:54
	**/
	public function collect_order_item_product_bcds( $order_items )
	{
		$r = (object)[];
		foreach( $order_items as $item_id => $item )
		{
			foreach( $item->meta as $meta_key => $meta_value )
			{
				switch( $meta_key )
				{
					case '_product_id':
					case '_variation_id':
						$id = $meta_value;
						if ( $id < 1 )
							break;
						$r->$id = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $id );
						break;
				}
			}
		}
		return $r;
	}

		/**
		@brief		Handle the copying of the options.
		@since		2017-03-17 13:10:40
	**/
	public function do_copy_options( $options )
	{
		$options[ 'linked_posts' ] = [
			// These page IDs need translating during copy.
			'woocommerce_shop_page_id',
			'woocommerce_cart_page_id',
			'woocommerce_checkout_page_id',
			'woocommerce_myaccount_page_id',
			'woocommerce_terms_page_id',
		];
		return $this->copy_option_trait_do_copy_options( $options );
	}

	/**
		@brief		Return an array of the options to copy.
		@since		2017-05-01 22:48:56
	**/
	public function get_options_to_copy()
	{
		return [
			'wgm_*',
			'woocommerce_*',
			'wp_wc_*',
		];
	}

	/**
		@brief		Return the items of an order using SQL, bypassing all caches.
		@since		2018-04-24 15:59:29
	**/
	public function get_order_items( $order )
	{
		global $wpdb;
		$order_id = $order->get_id();

		$r = (object) [];

		$query = sprintf( "SELECT * FROM `%swoocommerce_order_items` WHERE `order_id` = '%d' AND `order_item_type` = 'line_item'",
			$wpdb->prefix,
			$order_id
		);
		$results = $wpdb->get_results( $query );
		foreach( $results as $item )
		{
			$order_item_id = $item->order_item_id;

			$data = (object) [];

			$data->meta = (object) [];
			$data->name = $item->order_item_name;

			$query = sprintf( "SELECT * FROM `%swoocommerce_order_itemmeta` WHERE `order_item_id` = '%d'",
				$wpdb->prefix,
				$order_item_id
			);
			$this->debug( $query );
			$metas =  $wpdb->get_results( $query );
			foreach( $metas as $meta )
			{
				$meta_key = $meta->meta_key;
				$data->meta->$meta_key = $meta->meta_value;
			}

			$r->$order_item_id = $data;
		}
		return $r;
	}

	/**
		@brief		Check for the existence of WooCommerce.
		@return		bool		True if WooCommerce is alive and kicking. Else false.
		@since		20131117
	**/
	public function has_woocommerce()
	{
		return function_exists( 'wc_get_product' );
	}

	/**
		@brief		Force WooCommerce to recount the terms.
		@since		2015-07-13 18:09:25
	**/
	public function recount_terms()
	{
		// Taken directly from woocommerce/includes/admin/class-wc-admin-status.php
		$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
		_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );
		$product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );
		_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );
	}

	/**
		@brief		show_copy_options
		@since		2017-05-01 22:47:16
	**/
	public function show_copy_settings()
	{
		echo $this->generic_copy_options_page( [
			'plugin_name' => 'WooCommerce',
		] );
	}

	public function site_options()
	{
		return array_merge( [
			'partial_broadcast_size' => 0,
			'sync_orders' => false,
			'sync_stock' => false,
		], parent::site_options() );
	}

	/**
		@brief		Allow the Broadcast meta box.
		@since		2015-12-23 16:28:56
	**/
	public function wc_memberships_allowed_meta_box_ids( $ids )
	{
		$bc_id = 'threewp_broadcast';
		if ( ! in_array( $bc_id, $ids ) )
			$ids []= $bc_id;
		return $ids;
	}

}
