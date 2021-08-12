<?php

namespace threewp_broadcast\premium_pack\eventon;

use \threewp_broadcast\broadcasting_data;

/**
	@brief				Adds support for Ashan Jay's <a href="http://www.myeventon.com/">EventON</a> plugin with locations, organizers and tickets.
	@plugin_group		3rd party compatability
	@since				2016-01-18 10:47:58
**/
class EventON
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The post type for events.
		@since		2016-01-18 11:13:20
	**/
	public static $post_type = 'ajde_events';

	/**
		@brief		The post type for RSVP attendees.
		@since		2017-05-27 21:23:06
	**/
	public static $rsvp_post_type = 'evo-rsvp';

	public function _construct()
	{
		$this->add_filter( 'bc_woocommerce_sync_order', 10, 2 );
		$this->add_action( 'eventon_save_meta', 100, 2 );		// Tickets uses 10, se we must be last.
		$this->add_action( 'eventonau_save_form_submissions' );
		$this->add_action( 'evors_new_rsvp_saved' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_finished' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Help the WooCommerce add-on decide whether this order should be synced.
		@since		2016-05-06 13:53:23
	**/
	public function bc_woocommerce_sync_order( $sync_orders, $order_id )
	{
		// If we are not set to sync tickets, then return whatever the current value is.
		if ( ! $this->get_site_option( 'sync_ticket_orders' ) )
			return $sync_orders;

		$sync_orders = $this->is_ticket_order( $order_id );
		$this->debug( 'Is this a ticket order? %d', $sync_orders );
		return $sync_orders;
	}

	/**
		@brief		Hook into this to broadcast the ticket data.
		@details	Because of the wonderful hook bug, https://core.trac.wordpress.org/ticket/17817, we have to hook into someplace AFTER the tickets has saved and updated its WC product.
		@since		2016-01-18 10:43:35
	**/
	public function eventon_save_meta( $array, $post_id )
	{
		// Is this a ticket?
		$tix = get_post_meta( $post_id, 'evotx_tix', true );
		if ( $tix !== 'yes' )
			return;

		$this->debug( 'Finished saving meta for post %s. Now broadcasting it.', $post_id );

		// This is an event with a ticket.
		// Broadcast the ticket. We can use save_post directly because all of the nice _POST data is still there.
		ThreeWP_Broadcast()->save_post( $post_id );
	}

	/**
		@brief		Allow form submissions to be caught by, say, UBS, and broadcasted.
		@since		2017-05-28 13:31:26
	**/
	public function eventonau_save_form_submissions( $event_id )
	{
		ThreeWP_Broadcast()->save_post( $event_id );
	}

	/**
		@brief		Sync this rsvp?
		@since		2017-05-27 19:53:36
	**/
	public function evors_new_rsvp_saved( $post_id )
	{
		// If broadcasting, don't do anything.
		if ( ThreeWP_Broadcast()->is_broadcasting() )
			return;

		// We must be set to sync rsvps.
		if ( ! $this->get_site_option( 'sync_rsvp' ) )
			return;

		$post = get_post( $post_id );
		if ( $post->post_type != static::$rsvp_post_type )
			return;

		// Get the event ID of this rsvp.
		$event_id = get_post_meta( $post_id, 'e_id' );

		// And look up the event broadcast data.
		$event_broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $event_id );

		if ( $event_broadcast_data->blog_id != get_current_blog_id() )
		{
			// Parent event is not on this blog.
			// Copy the ticket to the parent blog.
			$this->debug( 'RSVP: Need to move rsvp to parent event.' );
			$rsvp_broadcast_data = ThreeWP_Broadcast()->api()
				->broadcast_children( $post_id, [ $event_broadcast_data->blog_id ] );

			ThreeWP_Broadcast()->switch_broadcast_data( get_current_blog_id(), $post_id );
		}
		else
		{
			$child_blogs = $event_broadcast_data->get_linked_children();
			$child_blogs = array_keys( $child_blogs );
		}

		$this->debug( "RSVP: Broadcasting to event's children to %s", $child_blogs );
		ThreeWP_Broadcast()->api()->broadcast_children( $post_id, $child_blogs );
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
			->name( __( 'Settings', 'threewp_broadcast' ) );

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

		$fs = $form->fieldset( 'fs_tickets' )
			// Fieldset label.
			->label( __( 'Tickets', 'threewp_broadcast' ) );

		$input_sync_ticket_orders = $fs->checkbox( 'sync_ticket_orders' )
			->checked( $this->get_site_option( 'sync_ticket_orders' ) )
			->description( __( 'Expermimental as of 2016-07-14: If a ticket is ordered for a broadcasted event, copy the order and purchased ticket to all linked events. Requires that WooCommerce order syncing is enabled.', 'threewp_broadcast' ) )
			->label( __( 'Sync ticket orders', 'threewp_broadcast' ) );

		$input_sync_rsvp = $fs->checkbox( 'sync_rsvp' )
			->checked( $this->get_site_option( 'sync_rsvp' ) )
			->description( __( "Sync RSVP attendees. If the event is broadcasted, the attendees will be broadcasted with the same parent blog as the event.", 'threewp_broadcast' ) )
			->label( __( 'Sync RSVP', 'threewp_broadcast' ) );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $input_sync_ticket_orders->is_checked();
			$this->update_site_option( 'sync_ticket_orders', $value );

			$value = $input_sync_rsvp->is_checked();
			$this->update_site_option( 'sync_rsvp', $value );

			$this->info_message_box()->_( 'Settings saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->eventon ) )
			return;

		if ( $this->post_is_event( $bcd->post ) )
		{
			$this->restore_event_location( $bcd );
			$this->restore_event_organizer( $bcd );
			$this->restore_ticket_product( $bcd );
		}

		if ( $bcd->post->post_type == static::$rsvp_post_type )
			$this->restore_rsvp_data( $bcd );

		if ( $bcd->post->post_type == 'evo-tix' )
			$this->restore_ticket_data( $bcd );

		if ( $bcd->post->post_type == 'shop_order' )
			$this->restore_order_data( $bcd );

		if ( $bcd->post->post_type == 'product' )
			$this->restore_product_event( $bcd );
	}

	/**
		@brief		Handle tickets. We can't broadcast tickets until the order has been completely broadcasted.
		@since		2016-05-06 17:30:12
	**/
	public function threewp_broadcast_broadcasting_finished( $action )
	{
		$bcd = $action->broadcasting_data;

		// If an order, save the ticket.
		if ( $bcd->post->post_type == 'shop_order' )
			$this->maybe_broadcast_order_ticket( $bcd );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->requirement_fulfilled() )
			return $this->debug( 'EventON not detected.' );

		$bcd = $action->broadcasting_data;

		$bcd->eventon = ThreeWP_Broadcast()->collection();

		$this->save_evo_tax_meta( $bcd );
		$this->save_event_speakers( $bcd );

		if ( $this->post_is_event( $bcd->post ) )
		{
			$this->save_event_location( $bcd );
			$this->save_event_organizer( $bcd );
			$this->save_ticket_product( $bcd );
		}

		if ( $bcd->post->post_type == static::$rsvp_post_type )
			$this->save_rsvp_data( $bcd );

		// If a ticket, save the order and product info.
		if ( $bcd->post->post_type == 'evo-tix' )
			$this->save_ticket_data( $bcd );

		// If a product, save the correct event ID.
		if ( $bcd->post->post_type == 'product' )
			$this->save_product_event( $bcd );

		if ( $bcd->post->post_type == 'shop_order' )
			$this->save_order_data( $bcd );
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( static::$post_type );
		$action->add_type( static::$rsvp_post_type );
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
			->submenu( 'threewp_broadcast_eventon' )
			->callback_this( 'menu_tabs' )
			->menu_title( 'EventON' )
			->page_title( 'EventON' );
	}

	/**
		@brief		Restore the "term meta" of an event speaker term.
		@since		2020-10-01 11:46:17
	**/
	public function maybe_restore_event_speaker( $action )
	{
		if ( $action->taxonomy != 'event_speaker' )
			return;

		$bcd = $action->broadcasting_data;
		$old_evo_tax_meta = $bcd->eventon->get( 'evo_tax_meta' );

		if ( ! isset( $old_evo_tax_meta[ 'event_speaker' ] ) )
			return;
		if ( ! isset( $old_evo_tax_meta[ 'event_speaker' ][ $action->old_term->term_id ] ) )
			return;

		$speaker = $old_evo_tax_meta[ 'event_speaker' ][ $action->old_term->term_id ];

		// No image? Do nothing.
		if ( ! isset( $speaker[ 'evo_spk_img' ] ) )
			return;

		$speaker[ 'evo_spk_img' ] = $bcd->copied_attachments()->get( $speaker[ 'evo_spk_img' ] );

		// Retrieve, modify and save the evo_tax_meta.
		$new_evo_tax_meta = get_option( 'evo_tax_meta' );

		if ( ! isset( $new_evo_tax_meta[ 'event_speaker' ] ) )
			$new_evo_tax_meta[ 'event_speaker' ] = [];
		$new_evo_tax_meta[ 'event_speaker' ][ $action->new_term->term_id ] = $speaker;

		$this->debug( 'Updating speaker with: %s', $speaker );

		update_option( 'evo_tax_meta', $new_evo_tax_meta );
	}

	/**
		@brief		Maybe handle term meta.
		@since		2020-10-01 11:41:06
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->eventon ) )
			return;

		$this->maybe_restore_event_speaker( $action );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		save_location
		@since		2016-02-23 10:55:43
	**/
	public function save_event_location( $bcd )
	{
		$this->remember_taxonomy_meta( $bcd, 'event_location' );
	}

	/**
		@brief		save_event_organizer
		@since		2016-02-23 13:54:37
	**/
	public function save_event_organizer( $bcd )
	{
		$this->remember_taxonomy_meta( $bcd, 'event_organizer' );
	}

	/**
		@brief		Remember any speaker images.
		@since		2020-10-01 11:52:09
	**/
	public function save_event_speakers( $bcd )
	{
		$etm = $bcd->eventon->get( 'evo_tax_meta' );
		if ( ! isset( $etm[ 'event_speaker' ] ) )
			return;
		foreach( $etm[ 'event_speaker' ] as $speaker )
			if ( isset( $speaker[ 'evo_spk_img' ] ) )
				$bcd->try_add_attachment(  $speaker[ 'evo_spk_img' ] );
	}

	/**
		@brief		Save the tax meta.
		@since		2020-10-01 11:39:42
	**/
	public function save_evo_tax_meta( $bcd )
	{
		$bcd->eventon->set( 'evo_tax_meta', get_option( 'evo_tax_meta' ) );
	}

	/**
		@brief		Save ticket data for this order.
		@since		2016-07-12 21:19:34
	**/
	public function save_order_data( $bcd )
	{
		$ticket_holders = $bcd->custom_fields()->get_single( '_tixholders' );
		$ticket_holders = maybe_unserialize( $ticket_holders );
		if ( ! is_array( $ticket_holders ) )
			return;
		foreach( $ticket_holders as $event_id => $holders )
		{
			$broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( $event_id );
			$bcd->eventon->collection( 'ticket_holders' )->set( $event_id, $broadcast_data );
		}
		$this->debug( 'Ticket holders for this order: %s', $bcd->eventon->collection( 'ticket_holders' ) );
	}

	/**
		@brief		save_product_event
		@since		2016-05-06 14:15:39
	**/
	public function save_product_event( $bcd )
	{
		$event_id = $bcd->custom_fields()->get_single( '_eventid' );
		if ( $event_id < 1 )
			return;
		$bcd->eventon->product_event = (object)[];
		$bcd->eventon->product_event->event_id = $event_id;
		$bcd->eventon->product_event->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $event_id );
		$this->debug( 'Saved product event data: %s', $bcd->eventon->product_event );
	}

	/**
		@brief		Save the RSVP data.
		@since		2017-05-27 21:28:13
	**/
	public function save_rsvp_data( $bcd )
	{
		// Find the event ID.
		$event_id = $bcd->custom_fields()
			->get_single( 'e_id' );

		if ( $event_id < 1 )
			return $this->debug( 'This RSVP has no event ID.' );

		$bcd->eventon->rsvp = ThreeWP_Broadcast()->collection();
		$bcd->eventon->rsvp->set( 'event_id', $event_id );

		$event_broadcast_data = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $event_id );;
		$bcd->eventon->rsvp->set( 'event_broadcast_data', $event_broadcast_data );

		$this->debug( 'RSVP: Saved event ID %d with broadcast data %s', $event_id, $event_broadcast_data );
	}

	/**
		@brief		Save the order data for this ticket.
		@since		2016-05-06 17:36:55
	**/
	public function save_ticket_data( $bcd )
	{
		// Retrieve the order and product data.
		$bcd->eventon->ticket_data = (object)[];

		$bcd->eventon->ticket_data->event_id = $bcd->custom_fields()->get_single( '_eventid' );
		$bcd->eventon->ticket_data->event_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $bcd->eventon->ticket_data->event_id );

		$bcd->eventon->ticket_data->order_id = $bcd->custom_fields()->get_single( '_orderid' );
		$bcd->eventon->ticket_data->order_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $bcd->eventon->ticket_data->order_id );

		$bcd->eventon->ticket_data->product_id = $bcd->custom_fields()->get_single( 'wcid' );
		$bcd->eventon->ticket_data->product_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $bcd->eventon->ticket_data->product_id );

		$this->debug( 'Saved ticket data: %s', $bcd->eventon->ticket_data );
	}

	/**
		@brief		Broadcast the ticket product.
		@since		2016-01-18 11:00:22
	**/
	public function save_ticket_product( $bcd )
	{
		$product_id = $bcd->custom_fields()->get_single( 'tx_woocommerce_product_id' );
		if ( $product_id < 1 )
			return $this->debug( 'Not an EventON ticket.' );

		$this->debug( 'Broadcasting ticket product %s', $product_id );

		// Broadcast this product to all of the blogs we are supposed to broadcast to.
		$blogs = [];
		foreach( $bcd->blogs as $blog )
			$blogs []= $blog->get_id();

		$r = ThreeWP_Broadcast()->api()->broadcast_children( $product_id, $blogs );
		$bcd->eventon->ticket_broadcasting_data = $r->broadcast_data;

		$this->debug( 'Finished broadcasting ticket product %s', $product_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Translate the location.
		@since		2016-02-23 10:39:27
	**/
	public function restore_event_location( $bcd )
	{
		$this->restore_taxonomy_meta( $bcd, 'event_location' );
	}

	/**
		@brief		Translate the organizer.
		@since		2016-02-23 10:42:05
	**/
	public function restore_event_organizer( $bcd )
	{
		$this->restore_taxonomy_meta( $bcd, 'event_organizer' );
	}

	/**
		@brief		Restore the data for this ticket order.
		@since		2016-07-12 21:18:39
	**/
	public function restore_order_data( $bcd )
	{
		$old_ticket_holders = $bcd->eventon->collection( 'ticket_holders' );
		if ( count( $old_ticket_holders ) < 1 )
			return;

		$ticket_holders = $bcd->custom_fields()->get_single( '_tixholders' );
		$ticket_holders = maybe_unserialize( $ticket_holders );
		$new_ticket_holders = [];

		foreach( $old_ticket_holders as $old_event_id => $event_bcd )
		{
			$new_event_id = $event_bcd->get_linked_post_on_this_blog();
			$new_ticket_holders[ $new_event_id ] = $ticket_holders[ $old_event_id ];
		}
		$this->debug( 'Setting new ticket holders: %s', $new_ticket_holders );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_tixholders', $new_ticket_holders );
	}

	/**
		@brief		Restore the event for this product.
		@since		2016-05-06 14:17:56
	**/
	public function restore_product_event( $bcd )
	{
		if ( ! isset( $bcd->eventon->product_event ) )
			return;
		$old_event_id = $bcd->eventon->product_event->event_id;
		$new_event_id = $bcd->eventon->product_event->broadcast_data->get_linked_post_on_this_blog();
		$this->debug( 'Replacing old event ID %s for this product with %s.', $old_event_id, $new_event_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_eventid', $new_event_id );
	}

	/**
		@brief		Restore the RSVP data.
		@since		2017-05-27 21:30:00
	**/
	public function restore_rsvp_data( $bcd )
	{
		if ( ! isset( $bcd->eventon->rsvp ) )
			return;

		$rsvp = $bcd->eventon->rsvp;
		$event_broadcast_data = $bcd->eventon->rsvp->get( 'event_broadcast_data' );

		if ( ! $event_broadcast_data )
			return $this->debug( 'RSVP: No broadcast data available for this rsvp.' );

		$new_event_id = $event_broadcast_data->get_linked_post_on_this_blog();
		$this->debug( 'RSVP: New rsvp event ID is %d', $new_event_id );

		if ( $new_event_id < 1 )
			return;

		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'e_id', $new_event_id );

		if ( isset( $GLOBALS['eventon_rs'] ) )
		{
			$rsvp = $GLOBALS['eventon_rs'];
			$rsvp->functions->sync_rsvp_count( $new_event_id ) ;
		}
	}

	/**
		@brief		Set the correct event and product.
		@since		2016-05-06 17:54:01
	**/
	public function restore_ticket_data( $bcd )
	{
		if ( ! isset( $bcd->eventon->ticket_data ) )
			return;

		$new_event_id = $bcd->eventon->ticket_data->event_bcd->get_linked_post_on_this_blog();
		if ( $new_event_id < 1 )
			return $this->debug( 'No broadcasted event on this blog.' );

		$new_order_id = $bcd->eventon->ticket_data->order_bcd->get_linked_post_on_this_blog();
		if ( $new_order_id < 1 )
			return $this->debug( 'No broadcasted ticket order on this blog.' );

		$new_product_id = $bcd->eventon->ticket_data->product_bcd->get_linked_post_on_this_blog();
		if ( $new_product_id < 1 )
			return $this->debug( 'No broadcasted ticket product on this blog.' );

		$this->debug( 'Setting event ID to %s.', $new_event_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_eventid', $new_event_id );

		$this->debug( 'Setting order ID to %s.', $new_order_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( '_orderid', $new_order_id );

		$this->debug( 'Setting wcid to %s.', $new_product_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'wcid', $new_product_id );
	}

	/**
		@brief		Restore the ticket product, if any.
		@since		2016-01-18 11:09:58
	**/
	public function restore_ticket_product( $bcd )
	{
		if ( ! isset( $bcd->eventon->ticket_broadcasting_data ) )
			return;

		$bd = $bcd->eventon->ticket_broadcasting_data;	// Conv.

		if ( ! $bd->get_linked_post_on_this_blog() )
			return $this->debug( 'No linked ticket product on this blog.' );

		$product_id = $bd->get_linked_post_on_this_blog();
		$this->debug( 'Replacing ticket product ID with %s', $product_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'tx_woocommerce_product_id', $product_id );
	}


	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Is this WooCommerce order a ticket?
		@since		2016-01-26 13:53:36
	**/
	public function is_ticket_order( $order_id )
	{
		$order_type = get_post_meta( $order_id, '_order_type', true );
		return ( $order_type == 'evotix' );
	}

	/**
		@brief		Broadcast the evo ticket associated to this order, if any.
		@since		2016-05-06 17:31:55
	**/
	public function maybe_broadcast_order_ticket( $bcd )
	{
		if ( ! $this->is_ticket_order( $bcd->post->ID ) )
			return $this->debug( 'This is not a ticket order.' );;

		// Find the ticket belonging to this order.
		$ticket = get_posts( [
			'posts_per_page'=>-1,
			'post_type'=>'evo-tix',
			'meta_query' => array(
				[ 'key' => '_orderid','value' => $bcd->post->ID, 'compare' => '=' ],
			)
		] );

		if ( count( $ticket ) < 1 )
			return $this->debug( 'Warning! No ticket found for this order. This should not happen.' );

		$ticket = reset( $ticket );
		$ticket_id = $ticket->ID;

		$blogs = [];
		foreach( $bcd->blogs as $blog_id => $ignore )
			$blogs []= $blog_id;

		$this->debug( 'This is a ticket order. Broadcasting ticket %s to %s', $ticket_id, implode( ', ', $blogs ) );

		ThreeWP_Broadcast()->api()->broadcast_children( $ticket_id, $blogs );
	}

	/**
		@brief		Is this post an event?
		@since		2014-10-24 21:14:58
	**/
	public function post_is_event( $post )
	{
		return $post->post_type == static::$post_type;
	}

	/**
		@brief		Saves the meta of a location / organizer taxonomy.
		@since		2016-02-23 13:42:10
	**/
	public function remember_taxonomy_meta( $bcd, $taxonomy )
	{
		if ( ! isset( $bcd->parent_post_taxonomies[ $taxonomy ] ) )
			return $this->debug( 'Parent post has no taxonomy %s', $taxonomy );

		if ( ! isset( $bcd->eventon->taxonomy_meta ) )
			$bcd->eventon->taxonomy_meta = (object)[];

		// v2.5.1 uses a special option.
		$evo_tax_meta = get_option( 'evo_tax_meta' );

		$parent_taxonomy = $bcd->parent_post_taxonomies[ $taxonomy ];
		foreach( $parent_taxonomy as $taxonomy_id => $taxonomy_data )
		{
			if ( ! isset( $bcd->eventon->taxonomy_meta->$taxonomy ) )
				$bcd->eventon->taxonomy_meta->$taxonomy = (object)[];

			if ( is_array( $evo_tax_meta ) )
			{
				if ( isset( $evo_tax_meta[ $taxonomy ] ) )
				{
					if ( isset( $evo_tax_meta[ $taxonomy ][ $taxonomy_id ] ) )
					{
						$value = $evo_tax_meta[ $taxonomy ][ $taxonomy_id ];
						$this->debug( 'Found the taxonomy data in evo_tax_meta.' );
					}
				}
			}
			else
			{
				$option_key = 'taxonomy_' . $taxonomy_id;
				$value = get_option( $option_key, true );
				$this->debug( 'Found the taxonomy data in %s', $option_key );
			}

			$this->debug( 'Saving taxonomy meta %s for %s %s', $value, $taxonomy, $taxonomy_id );

			if ( is_array( $value ) )
				if ( isset( $value[ 'evo_loc_img' ] ) )
				{
					$image_id = $value[ 'evo_loc_img' ];
					if ( $bcd->try_add_attachment( $image_id ) )
						$this->debug( 'Saving location image %s', $image_id );
				}

			$bcd->eventon->taxonomy_meta->$taxonomy->$taxonomy_id = $value;
		}
	}

	/**
		@brief		Is the plugin installed?
		@since		2014-02-22 10:23:22
	**/
	public function requirement_fulfilled()
	{
		return class_exists( 'EventON' );
	}

	public function site_options()
	{
		return array_merge( [
			'sync_ticket_orders' => false,
			'sync_rsvp' => false,
		], parent::site_options() );
	}

	/**
		@brief		Restore the meta of a location / organizer taxonomy.
		@since		2016-02-23 10:42:33
	**/
	public function restore_taxonomy_meta( $bcd, $taxonomy )
	{
		if ( ! isset( $bcd->eventon->taxonomy_meta->$taxonomy ) )
			return $this->debug( 'No meta to restore for %s', $taxonomy );

		$evo_tax_meta = get_option( 'evo_tax_meta' );
		if ( isset( $evo_tax_meta ) )
			$this->debug( 'Found evo_tax_meta!' );

		foreach( (array) $bcd->eventon->taxonomy_meta->$taxonomy as $taxonomy_id => $value )
		{
			$new_id = $bcd->terms()->get( $taxonomy_id );

			if ( is_array( $value ) )
				if ( isset( $value[ 'evo_loc_img' ] ) )
				{
					$image_id = $value[ 'evo_loc_img' ];
					$new_image_id = $bcd->copied_attachments()->get( $image_id );
					$this->debug( 'Restore location image %s to %s', $image_id, $new_image_id );
					$value[ 'evo_loc_img' ] = $new_image_id;
				}

			$this->debug( 'Restoring taxonomy meta for %s %s: %s', $taxonomy, $new_id, $value );

			if ( is_array( $evo_tax_meta ) )
			{
				if ( ! isset( $evo_tax_meta[ $taxonomy ] ) )
					$evo_tax_meta[ $taxonomy ] = [];
				$evo_tax_meta[ $taxonomy ][ $new_id ] = $value;
			}
			else
			{
				$option_key = 'taxonomy_' . $new_id;
				update_option( $option_key, $value );
			}

		}

		if ( is_array( $evo_tax_meta ) )
		{
			$this->debug( 'Updating evo_tax_meta with %s', $evo_tax_meta );
			update_option( 'evo_tax_meta', $evo_tax_meta );
		}
	}

}
