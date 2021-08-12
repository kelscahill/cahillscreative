<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief		Handle the Gravity Views plugin.
	@since		2020-07-06 22:01:50
**/
class Gravity_Views
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->gravity_views_shortcode = new Gravity_Views_Shortcode();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2020-07-07 16:51:07
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->gravity_views ) )
			return;

		$gv = $bcd->gravity_views;

		// Find the equivalent form on this blog.
		global $wpdb;
		$table = static::gf_addon()->rg_gf_table( 'form' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `title` = '%s'", $table, $gv->get( 'form_title' ) );
		$this->debug( $query );
		$new_form = $wpdb->get_row( $query );

		if ( ! $new_form )
			return $this->debug( 'Form %s not found on this blog.', $gv->get( 'form_title' ) );

		$new_form_id = $new_form->id;
		$this->debug( 'Equivalent of form %s is %s', $gv->get( 'form_id' ), $new_form_id );

		$old_fields = $gv->get( 'form_fields' );
		// Lookup by ID.
		$old_fields = array_flip( $old_fields );
		$new_fields = static::get_form_fields(  $new_form_id );

		$this->debug( 'Old fields: %s', $old_fields );
		$this->debug( 'New fields: %s', $new_fields );

		$x = $bcd->custom_fields()->get_single( '_gravityview_directory_fields' );
		$x = maybe_unserialize( $x );
		foreach( $x as $type => $fields )
		{
			foreach( $fields as $field_index => $field )
			{
				if ( isset( $field[ 'form_id' ] ) )
					$field[ 'form_id' ] = $new_form->id;
				if ( isset( $field[ 'id' ] ) )
					if ( intval( $field[ 'id' ] ) > 0 )
					{
						$field_label = $field[ 'label' ];
						$new_field_id = $new_fields[ $field_label ];
						$field[ 'id' ] = $new_field_id;
					}
				$x[ $type ][ $field_index ] = $field;
			}
		}

		// Save the new fields.
		$bcd->custom_fields()->child_fields()->update_meta( '_gravityview_directory_fields', $x );
		$bcd->custom_fields()->child_fields()->update_meta( '_gravityview_form_id', $new_form_id );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2020-07-07 16:48:03
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != 'gravityview' )
			return;

		global $wpdb;

		$form_id = $bcd->custom_fields()->get_single( '_gravityview_form_id' );

		// Store the form and fields.
		$table = static::gf_addon()->rg_gf_table( 'form' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $form_id );
		$this->debug( $query );
		$form = $wpdb->get_row( $query );
		if ( ! $form )
			return $this->debug( 'No Gravity Form found with ID %s', $form_id );


		$bcd->gravity_views = ThreeWP_Broadcast()->collection();
		$gv = $bcd->gravity_views;
		$gv->set( 'form_id', $form_id );
		$gv->set( 'form_title', $form->title );
		$gv->set( 'form_fields', $this->get_form_fields( $form_id ) );
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2020-07-07 18:38:10
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'gravityview' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Return the form fields of this form.
		@since		2020-07-07 18:45:00
	**/
	public function get_form_fields( $form_id )
	{
		global $wpdb;
		$table = static::gf_addon()->rg_gf_table( 'form_meta' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$form_id
		);
		$meta = $wpdb->get_row( $query );
		$display_meta = json_decode( $meta->display_meta );

		$r = [];
		foreach( $display_meta->fields as $field )
			$r[ $field->label ] = $field->id;
		return $r;
	}

	/**
		@brief		Return the instance of the GF add-on.
		@since		2020-07-07 18:46:04
	**/
	public static function gf_addon()
	{
		return Gravity_Forms::instance();
	}

}
