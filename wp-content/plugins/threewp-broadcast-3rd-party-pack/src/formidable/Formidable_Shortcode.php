<?php

namespace threewp_broadcast\premium_pack\formidable;

/**
	@brief			Handles the shortcode.
	@since			2018-08-20 10:36:00
**/
class Formidable_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );
		$form_data = Formidable::instance()->get_form_data( $item->id );
		$source_prefix = $wpdb->prefix;
		restore_current_blog();

		$form = $form_data->get( 'form' );

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			return;

		$target_prefix = $wpdb->prefix;

		$table = Formidable::table_name( 'frm_forms', $target_prefix );
		$this->database_table_must_exist( $table );

		// Find a form with the same form_key.
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_key` = '%s'", $table, $form->form_key );
		$this->debug( $query );
		$result = $wpdb->get_row( $query );

		if ( ! $result )
		{
			$columns = $this->get_database_table_columns_string( $table, [ 'except' => [ 'id' ] ] );
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` ='%d' )",
				Formidable::table_name( 'frm_forms', $target_prefix ),
				$columns,
				$columns,
				Formidable::table_name( 'frm_forms', $source_prefix ),
				$form->id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->id;
			$this->debug( 'Updating existing form %s', $new_form_id );
			$new_form_data = clone( $form );
			unset( $new_form_data->id );
			// Don't forget the (array) conversion.
			$wpdb->update( Formidable::table_name( 'frm_forms', $target_prefix ), (array) $new_form_data, [ 'id' => $new_form_id ] );
		}

		// Save the old frm_fields.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT `id`, `field_key` FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$old_form_fields = $wpdb->get_results( $query );

		// Delete the current frm_fields.
		$this->database_table_must_exist( $table );
		$query = sprintf( "DELETE FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// And reinsert the fresh data.
		$columns = $this->get_database_table_columns_string( Formidable::table_name( 'frm_fields' ), [ 'except' => [ 'id', 'form_id' ] ] );
		$query = sprintf( "INSERT INTO `%s` ( `form_id`, %s ) ( SELECT %d, %s FROM `%s` WHERE `form_id` ='%s' )",
			Formidable::table_name( 'frm_fields' , $target_prefix ),
			$columns,
			$new_form_id,
			$columns,
			Formidable::table_name( 'frm_fields' , $source_prefix ),
			$form->id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		// Get the new frm_fields.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT `id`, `field_key` FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$new_form_fields = $wpdb->get_results( $query );

		// And now rename the field IDs in the frm_item_metas.
		$table = Formidable::table_name( 'frm_item_metas', $target_prefix );
		foreach( $new_form_fields as $new_form_field )
		{
			foreach( $old_form_fields as $old_form_field )
			{
				if ( $old_form_field->field_key != $new_form_field->field_key )
					continue;
				// Change the ID.
				$query = sprintf( "UPDATE `%s` SET `field_id` = '%s' WHERE `field_id` = '%s'",
					$table,
					$new_form_field->id,
					$old_form_field->id
				);
				$this->debug( $query );
				$wpdb->query( $query );
			}
		}

		// Fetch the fields from the old blog, because we are going to be parsing the field IDs later.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'", $table, $new_form_id );
		$this->debug( $query );
		$new_fields = $wpdb->get_results( $query );

		// Parse the conditionals.
		foreach( $new_fields as $new_field )
		{
			$field_options = $new_field->field_options;
			$field_options = maybe_unserialize( $field_options );
			if ( ! is_array( $field_options ) )
				continue;
			if ( ! is_array( $field_options[ 'hide_field' ] ) )
				continue;
			$modified = false;
			foreach( $field_options[ 'hide_field' ] as $index => $field_id )
			{
				// Find the old field ID.
				foreach( $form_data->get( 'fields' ) as $old_field )
				{
					if ( $old_field->id == $field_id )
					{
						// We now know the key.
						$field_key = $old_field->field_key;
						// And now find the new ID.
						foreach( $new_fields as $temp_new_field )
							if ( $temp_new_field->field_key == $field_key )
							{
								$field_options[ 'hide_field' ][ $index ] = $temp_new_field->id;
								$this->debug( 'Updating field ID %s to %s.', $old_field->id, $temp_new_field->id );
								$modified = true;
							}
					}
				}
			}

			if ( ! $modified )
				continue;

			$this->debug( 'Updating field_options for field %s: %s', $new_field->id, $field_options );
			$query = $wpdb->update( $table, [
				'field_options' => serialize( $field_options ),
			], [ 'id' => $new_field->id ] );
		}

		// Formidable caches its fields.
		wp_cache_delete( $new_form_id, 'frm_field' );
		\FrmField::delete_form_transient( $new_form_id );

		// A raw SQL query is far, far more reliable than a stupid get_posts with its 5 post limit and post_status sensitivty.
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` = 'frm_form_actions' AND `menu_order` = '%d'", $wpdb->posts, $new_form_id );
		$this->debug( $query );
		$old_form_actions = $wpdb->get_col( $query );
		$old_form_actions = array_combine( $old_form_actions, $old_form_actions );
		$this->debug( 'Old form actions: %s', $old_form_actions );

		switch_to_blog( $bcd->parent_blog_id );
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` = 'frm_form_actions' AND `menu_order` = '%d'", $wpdb->posts, $form->id );
		$this->debug( $query );
		$form_actions = $wpdb->get_col( $query );
		restore_current_blog();

		foreach( $form_actions as $post_id )
		{
			$this->debug( 'Broadcasting form action %s', $post_id );
			$target_blog = get_current_blog_id();
			switch_to_blog( $bcd->parent_blog_id );
			$form_action_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $post_id, [ $target_blog ] );
			restore_current_blog();
			$new_form_action_id = $form_action_bcd->new_post( 'ID' );

			unset( $old_form_actions[ $new_form_action_id ] );

			// Update the menu_order (form) of this new form action.
			$query = sprintf( "UPDATE `%s` SET `menu_order` = '%d' WHERE `ID` = '%d'",
				$wpdb->posts,
				$new_form_id,
				$new_form_action_id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
		}

		// Delete orphaned actions.
		foreach( $old_form_actions as $post_id )
		{
			$this->debug( "Deleting orphaned form action %s", $post_id );
			wp_delete_post( $post_id, true );
		}

		return $new_form_id;
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'formidable';
	}
}
