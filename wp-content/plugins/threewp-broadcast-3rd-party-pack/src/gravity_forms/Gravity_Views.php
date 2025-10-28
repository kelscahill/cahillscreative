<?php

namespace threewp_broadcast\premium_pack\gravity_forms;

/**
	@brief		Handle the Gravity Views plugin.
	@since		2020-07-06 22:01:50
**/
class Gravity_Views
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\parse_and_preparse_content_trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		new Gravity_Views_Shortcode();
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
		$parent_blog_id = $bcd->parent_blog_id;

		$forms_data = $gv->get( 'forms_data' );

		// $forms_data->collect_forms();		// This is done automatically by Forms_Data

		$new_form = $forms_data->get_equivalent_form();

		if ( ! $new_form )
			return $this->debug( 'Form %s not found on this blog.', $gv->get( 'form_title' ) );

		$new_form_id = $new_form->id;
		$this->debug( 'Equivalent of form %s is %s', $gv->get( 'form_id' ), $new_form_id );
		$bcd->custom_fields()->child_fields()->update_meta( '_gravityview_form_id', $new_form_id );

		$gravityview_directory_fields = $bcd->custom_fields()->get_single( '_gravityview_directory_fields' );
		$gravityview_directory_fields = maybe_unserialize( $gravityview_directory_fields );
		foreach( $gravityview_directory_fields as $type => $fields )
		{
			foreach( $fields as $field_index => $field )
			{
				$this->debug( 'Handling field %s', $field_index );
				if ( isset( $field[ 'content' ] ) )
				{
					$new_content = $this->parse_content( [
						'broadcasting_data' => $bcd,
						'content' => $field[ 'content' ],
						'id' => $field_index . 'content',
					] );
					$this->debug( 'Replacing content %s with %s',
						htmlspecialchars( $field[ 'content' ] ),
						htmlspecialchars( $new_content ),
					);
					$field[ 'content' ] = $new_content;
				}
				if ( isset( $field[ 'form_id' ] ) )
				{
					$this->debug( 'Replacing form ID %s with %s',
						$field[ 'form_id' ],
						$new_form->id,
					);
					$field[ 'form_id' ] = $new_form->id;
				}
				if ( isset( $field[ 'view_id' ] ) )
				{
					$new_view_id = $bcd
						->equivalent_posts()
						->get_or_broadcast( $bcd->parent_blog_id, $field[ 'view_id' ], get_current_blog_id() );
					$this->debug( 'Replacing view_id %s with %s', $field[ 'view_id' ], $new_view_id );
					$field[ 'view_id' ] = $new_view_id;
				}
				$gravityview_directory_fields[ $type ][ $field_index ] = $field;
			}
		}
		$bcd->custom_fields()->child_fields()->update_meta( '_gravityview_directory_fields', $gravityview_directory_fields );

		$gravityview_directory_widgets = $bcd->custom_fields()->get_single( '_gravityview_directory_widgets' );
		$gravityview_directory_widgets = maybe_unserialize( $gravityview_directory_widgets );
		foreach( $gravityview_directory_widgets as $placement => $widgets )
			foreach( $widgets as $widget_id => $widget_fields )
				foreach( $widget_fields as $field_key => $field_value )
				{
					if ( in_array( $field_key, [ 'form_id', 'widget_form_id' ] ) )
					{
						$new_widget_form_id = $forms_data->get_equivalent_form_id( $parent_blog_id, $field_value );
						$this->debug( 'Replacing %s %s with %s', $field_key, $field_value, $new_widget_form_id );
						$gravityview_directory_widgets[ $placement ][ $widget_id ][ $field_key ] = $new_widget_form_id;
					}
				}
		$bcd->custom_fields()->child_fields()->update_meta( '_gravityview_directory_widgets', $gravityview_directory_widgets );
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

		$forms_data = new Forms_Data();
		$forms_data->collect_forms();
		$forms_data->remember_form( $form_id );
		$gv->set( 'forms_data', $forms_data );

		$gravityview_directory_fields = $bcd->custom_fields()->get_single( '_gravityview_directory_fields' );
		$gravityview_directory_fields = maybe_unserialize( $gravityview_directory_fields );
		foreach( $gravityview_directory_fields as $type => $fields )
		{
			foreach( $fields as $field_index => $field )
			{
				$this->debug( 'Prehandling field %s', $field_index );
				if ( isset( $field[ 'content' ] ) )
				{
					$new_content = $this->preparse_content( [
						'broadcasting_data' => $bcd,
						'content' => $field[ 'content' ],
						'id' => $field_index . 'content',
					] );
					$this->debug( 'Preparsing content %s',
						htmlspecialchars( $field[ 'content' ] ),
					);
				}
			}
		}
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
		$this->debug( 'The display meta for the form fields: %s', $display_meta );

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
