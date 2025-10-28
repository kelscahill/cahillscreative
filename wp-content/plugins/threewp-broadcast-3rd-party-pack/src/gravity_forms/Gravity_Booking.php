<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

class Gravity_Booking
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	public function _construct()
	{
		$this->add_action( 'admin_menu', 100 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- CALLBACKS
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add ourselves to the Divi menu.
		@since		2020-08-10 18:54:03
	**/
	public function admin_menu()
	{
		add_submenu_page(
			'gfb',
			'Broadcast',
			'Broadcast',
			'manage_options',
			'bc_gravity_booking',
			[ $this, 'ui_tabs' ]
		);
	}

	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->maybe_restore_service( $bcd );
		$this->maybe_restore_staff( $bcd );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- RESTORE
	// --------------------------------------------------------------------------------------------

	public function maybe_restore_service( $bcd )
	{
		if ( $bcd->post->post_type != 'gfb-service' )
			return;

		// Category
		$category = $bcd->custom_fields()->get_single( 'category' );
		$new_category = $bcd->terms()->get( $category );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( 'category', $new_category );

		// Location
		$key = 'location';
		$locations = $bcd->custom_fields()->get_single( $key );
		$locations = maybe_unserialize( $locations );
		if ( is_array( $locations ) )
		{
			$new_locations = [];
			foreach( $locations as $location_id )
			{
				$new_location_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $location_id, get_current_blog_id() );
				$new_locations []= $new_location_id;
			}
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $new_locations );
		}
	}

	public function maybe_restore_staff( $bcd )
	{
		if ( $bcd->post->post_type != 'gfb-staff' )
			return;

		// services
		$key = 'services';
		$services = $bcd->custom_fields()->get_single( $key );
		$services = maybe_unserialize( $services );
		if ( is_array( $services ) )
		{
			$new_services = [];
			foreach( $services as $service_id => $service_data )
			{
				$new_service_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $service_id, get_current_blog_id() );
				$new_services [ $new_service_id ] = $service_data;
			}
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $new_services );
		}

		// staff data
		$key = 'staff_data';
		$staff_data = $bcd->custom_fields()->get_single( $key );
		$staff_data = maybe_unserialize( $staff_data );
		if ( is_array( $staff_data ) )
		{
			$new_services = [];
			foreach( $staff_data[ 'services' ] as $service_id => $service_data )
			{
				$new_service_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $service_id, get_current_blog_id() );
				$new_services [ $new_service_id ] = $service_data;
			}
			$staff_data[ 'services' ] = $new_services;
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $key, $staff_data );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- UI
	// --------------------------------------------------------------------------------------------

	public function ui_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'ui_locations' )
			->callback_this( 'ui_locations' )
			->heading( 'Broadcast Locations' )
			->name( 'Locations' );

		$tabs->tab( 'ui_service' )
			->callback_this( 'ui_service' )
			->heading( 'Broadcast Service' )
			->name( 'Service' );

		$tabs->tab( 'ui_staff' )
			->callback_this( 'ui_staff' )
			->heading( 'Broadcast Staff' )
			->name( 'Staff' );

		echo $tabs->render();
	}

	public function ui_locations()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'gfb-location',
			'label_plural' => 'locations',
			'label_singular' => 'location',
		] );
	}

	public function ui_service()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'gfb-service',
			'label_plural' => 'services',
			'label_singular' => 'service',
		] );
	}

	public function ui_staff()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'gfb-staff',
			'label_plural' => 'staff',
			'label_singular' => 'staff',
		] );
	}
}
