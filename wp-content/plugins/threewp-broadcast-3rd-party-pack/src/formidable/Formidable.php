<?php

namespace threewp_broadcast\premium_pack\formidable;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/formidable/">Formidable</a> plugin.
	@plugin_group	3rd party compatability
	@since			2018-08-20 10:36:00
**/
class Formidable
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Constructor.
		@since		2019-05-09 20:42:01
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );

		new Formidable_Shortcode();
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2019-05-13 15:00:41
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->maybe_restore_frm_display( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2019-05-13 15:00:52
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->maybe_save_frm_display( $bcd );
	}

	/**
		@brief		Add the views.
		@since		2019-05-09 20:42:39
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ 'frm_display' ] = 'frm_display';
	}

	// ----
	// SAVE
	// ----

	/**
		@brief		Save form views.
		@since		2019-05-13 14:59:32
	**/
	public function maybe_save_frm_display( $bcd )
	{
		if ( $bcd->post->post_type != 'frm_display' )
			return;
		$bcd->formidable_frm_display = ThreeWP_Broadcast()->collection();
		$fd = $bcd->formidable_frm_display;

		$form_id = $bcd->custom_fields()->get_single( 'frm_form_id' );
		$form_data = static::get_form_data( $form_id );
		if ( ! $form_data )
			return;
		$fd->set( 'form_data', $form_data );
	}

	// -------
	// RESTORE
	// -------

	/**
		@brief		Restore form views.
		@since		2019-05-13 15:01:12
	**/
	public function maybe_restore_frm_display( $bcd )
	{
		if ( ! isset( $bcd->formidable_frm_display ) )
			return;
		$fd = $bcd->formidable_frm_display;
		$form_data = $fd->get( 'form_data' );

		// Find the equivalent form.
		$form_key = $form_data->get( 'form' )->form_key;

		global $wpdb;
		$table = Formidable::table_name( 'frm_forms', $wpdb->prefix );
		$this->database_table_must_exist( $table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_key` = '%s'", $table, $form_key );
		$this->debug( $query );
		$form = $wpdb->get_row( $query );

		if ( ! $form )
		{
			$this->debug( 'Form does not exist on this child. Ignore it.' );
			return;
		}

		$new_form_id = $form->id;
		$new_form_data = $this->get_form_data( $new_form_id );

		// Update the form ID.
		$cf = $bcd->custom_fields()->child_fields();
		$this->debug( 'New frm_form_id is %s', $new_form_id );
		$cf->update_meta( 'frm_form_id', $new_form_id );

		// Update the fields.
		$form_options = maybe_unserialize( $bcd->custom_fields()->get_single( 'frm_options' ) );
		if ( ! is_array( $form_options ) )
			return;
		$where = [];
		foreach( $form_options[ 'where' ] as $index => $field_id )
		{
			$where[ $index ] = $field_id;	// Assume this as the default.

			// Find the field with this old field ID.
			foreach( $form_data->get( 'fields' ) as $field )
			{
				if ( $field->id != $field_id )
					continue;
				// We've found the old field.
				foreach( $new_form_data->get( 'fields' ) as $new_field )
				{
					if ( $new_field->field_key != $field->field_key )
						continue;
					// We've found the new field.
					$where[ $index ] = $new_field->id;
				}
			}
		}
		$form_options[ 'where' ] = $where;
		$this->debug( 'Updating frm_options', $form_options );
		$cf->update_meta( 'frm_options', $form_options );
	}

	// -------
	// MISC
	// -------

	/**
		@brief		Return the form and fields for this form.
		@since		2019-05-13 15:07:21
	**/
	public function get_form_data( $form_id )
	{
		global $wpdb;
		$form_data = ThreeWP_Broadcast()->collection();

		$table = Formidable::table_name( 'frm_forms', $wpdb->prefix );
		$this->database_table_must_exist( $table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $form_id );
		$this->debug( $query );
		$form = $wpdb->get_row( $query );
		// The form must exist.
		if ( ! $form )
			return false;
		$form_data->set( 'form', $form );

		// Fetch the fields from the old blog, because we are going to be parsing the field IDs later.
		$table = Formidable::table_name( 'frm_fields', $wpdb->prefix );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'", $table, $form_id );
		$this->debug( $query );
		$old_fields = $wpdb->get_results( $query );
		$form_data->set( 'fields', $old_fields );

		return $form_data;
	}

	/**
		@brief		Return the table name.
		@since		2018-08-20 10:42:15
	**/
	public static function table_name( $table, $prefix = null )
	{
		if ( ! $prefix )
		{
			global $wpdb;
			$prefix = $wpdb->prefix;
		}
		return sprintf( '%s%s',
			$prefix,
			$table
		);
	}
}
