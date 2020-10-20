<?php

namespace threewp_broadcast\premium_pack\formidable;

/**
	@brief		Container for form and fields.
	@since		2019-05-15 17:16:52
**/
class Form_Data
	extends \plainview\sdk_broadcast\collections\Collection
{
	use \threewp_broadcast\premium_pack\classes\broadcast_shortcode_trait;
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Broadcast a form from the parent blog to here.
		@since		2019-11-25 23:08:23
	**/
	public function broadcast_form( $parent_blog_id, $parent_form_id )
	{
		$form = $this->get_form_data( $parent_blog_id, $parent_form_id );

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			return false;

		$form = $form->form;

		global $wpdb;

		// For logging.
		$fs = Formidable_Shortcode::instance();

		switch_to_blog( $parent_blog_id );
		$source_prefix = $wpdb->prefix;
		restore_current_blog();

		$target_prefix = $wpdb->prefix;

		$table = Formidable::table_name( 'frm_forms', $target_prefix );
		$this->database_table_must_exist( $table );

		// Find a form with the same form_key.
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_key` = '%s'", $table, $form->form_key );
		$fs->debug( $query );
		$result = $wpdb->get_row( $query );

		if ( ! $result )
		{
			$columns = $this->get_database_table_columns_string( $table, [ 'except' => [ 'id' ] ] );
			$query = sprintf( "INSERT INTO `%s` ( %s ) ( SELECT %s FROM `%s` WHERE `id` ='%d' )",
				Formidable::table_name( 'frm_forms', $target_prefix ),
				$columns,
				$columns,
				Formidable::table_name( 'frm_forms', $source_prefix ),
				$parent_form_id
			);
			$fs->debug( $query );
			$wpdb->get_results( $query );
			$new_form_id = $wpdb->insert_id;
			$fs->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$new_form_id = $result->id;
			$fs->debug( 'Updating existing form %s', $new_form_id );
			$new_form_data = clone( $form );
			unset( $new_form_data->id );
			// Don't forget the (array) conversion.
			$wpdb->update( Formidable::table_name( 'frm_forms', $target_prefix ), (array) $new_form_data, [ 'id' => $new_form_id ] );
		}

		// Uncache this form so that we can do a new lookup and find it. Previously it was set to "found", but as false.
		$col = $this->collection( get_current_blog_id() );
		$col->forget( $form->form_key );

		// Do we need to update the parent_form_id?
		if ( $form->parent_form_id > 0 )
		{
			$new_parent_form_id = $this->get_equivalent_form_id( $parent_blog_id, $form->parent_form_id );
			$fs->debug( 'Updating parent_form_id to %s', $new_parent_form_id );
			$wpdb->update( Formidable::table_name( 'frm_forms', $target_prefix ), (array) [
				'parent_form_id' => $new_parent_form_id,
			], [ 'id' => $new_form_id ] );
		}

		// Save the old frm_fields.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT `id`, `field_key` FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$old_form_fields = $wpdb->get_results( $query );
/**
		// Delete the current frm_fields.
		$this->database_table_must_exist( $table );
		$query = sprintf( "DELETE FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$fs->debug( $query );
		// Delete form fields based on form_key, since they're unique and could cause conflicts.
		$wpdb->query( $query );
		$query = sprintf( "DELETE FROM `%s` WHERE `form_key` IN ( SELECT `form_key` FROM `%s` WHERE `form_id` ='%s' )",
			Formidable::table_name( 'frm_fields' , $target_prefix ),
			Formidable::table_name( 'frm_fields' , $source_prefix ),
			$parent_form_id
		);
		$fs->debug( $query );

		// And reinsert the fresh data.
		$columns = $this->get_database_table_columns_string( Formidable::table_name( 'frm_fields' ), [ 'except' => [ 'id', 'form_id' ] ] );
		$query = sprintf( "INSERT INTO `%s` ( `form_id`, %s ) ( SELECT %d, %s FROM `%s` WHERE `form_id` ='%s' )",
			Formidable::table_name( 'frm_fields' , $target_prefix ),
			$columns,
			$new_form_id,
			$columns,
			Formidable::table_name( 'frm_fields' , $source_prefix ),
			$parent_form_id
		);
		$fs->debug( $query );
		$wpdb->get_results( $query );
**/
		$this->sync_database_rows( [
			'debug_class' => $fs,
			'except' => [ 'id', 'form_id' ],
			'source' => Formidable::table_name( 'frm_fields' , $source_prefix ),
			'source_value' => $parent_form_id,
			'target' => Formidable::table_name( 'frm_fields' , $target_prefix ),
			'target_value' => $new_form_id,
			'unique_column' => 'field_key',
			'value_column' => 'form_id',
		] );
		// Uncache this form so that we can do a new lookup and find it. Previously it was set to "found", but as false.
		$col = $this->collection( get_current_blog_id() );
		$col->forget( $form->form_key );

		// Get the new frm_fields.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT `id`, `field_key` FROM `%s` WHERE `form_id` = '%s'",
			$table,
			$new_form_id
		);
		$new_form_fields = $wpdb->get_results( $query );

		// And now rename the field IDs in the frm_item_metas.
		/**
			2019 12 11 This is not even used.

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
				$fs->debug( $query );
				$wpdb->query( $query );
			}
		}
		**/

		// Fetch the fields from the old blog, because we are going to be parsing the field IDs later.
		$table = Formidable::table_name( 'frm_fields', $target_prefix );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'", $table, $new_form_id );
		$fs->debug( $query );
		$new_fields = $wpdb->get_results( $query );

		// Parse the conditionals.
		foreach( $new_fields as $new_field )
		{
			$field_options = $new_field->field_options;
			$field_options = maybe_unserialize( $field_options );

			$fs->debug( 'Field options for %s %s: %s', $new_field->id, $new_field->field_key, $field_options );

			if ( ! is_array( $field_options ) )
				continue;
			$modified = false;

			$key = 'form_select';
			if ( isset( $field_options[ $key ] ) )
			{
				$form_select_id = intval( $field_options[ $key ] );
				if ( $form_select_id > 0 )
				{
					if ( isset( $field_options[ 'repeat' ] ) )
					{
						$fs->debug( 'Broadcasting repeater %s', $form_select_id );
						$form_select_id = $this->broadcast_form( $parent_blog_id, $form_select_id );
					}

					// Lookup a field.
					if ( $new_field->type == 'lookup' )
					{
						$fs->debug( 'Looking for dynamic field %s', $form_select_id );
						// We are referencing a field.
						$form_select_field = $this->get_sql_field( $parent_blog_id, $form_select_id );
						$equivalent_form_data = $this->get_equivalent_form_data(
							$parent_blog_id,
							$form_select_field->form_id,
							$form_select_field->id
						);
						$form_select_id = $equivalent_form_data->field->id;
					}

					// Embed a form.
					if ( $new_field->type == 'form' )
					{
						// We are referencing a field.
						$equivalent_form_data = $this->get_equivalent_form_data(
							$parent_blog_id,
							$form_select_id
						);
						$form_select_id = $equivalent_form_data->form->id;
					}
					$fs->debug( 'Setting new %s from %s to %s', $key, $field_options[ $key ], $form_select_id );
					$field_options[ $key ] = $form_select_id;
					$modified = true;
				}
			}

			$key = 'hide_field';
			if ( isset( $field_options[ $key ] ) && is_array( $field_options[ $key ] ) )
			{
				foreach( $field_options[ $key ] as $index => $field_id )
				{
					$equivalent_form_data = $this->get_equivalent_form_data(
						$parent_blog_id,
						$parent_form_id,
						$field_id
					);
					$field_options[ 'hide_field' ][ $index ] = $equivalent_form_data->field->id;
					$fs->debug( 'Setting new %s from %s to %s', $key, $field_id, $field_options[ 'hide_field' ][ $index ] );
				}
				$modified = true;
			}

			$key = 'in_section';
			if ( isset( $field_options[ $key ] ) )
			{
				$field_id = intval( $field_options[ $key ] );
				if ( $field_id > 0 )
				{
					$in_section_field = $this->get_sql_field( $parent_blog_id, $field_id );
					$equivalent_form_data = $this->get_equivalent_form_data(
						$parent_blog_id,
						$in_section_field->form_id,
						$in_section_field->id
					);
					$field_options[ $key ] = $equivalent_form_data->field->id;
					$fs->debug( 'Setting new %s from %s to %s', $key, $field_id, $field_options[ $key ] );
					$modified = true;
				}
			}

			// Handle lookup forms and fields.
			$key = 'get_values_form';
			if ( isset( $field_options[ $key ] ) )
			{
				if ( intval( $field_options[ $key ] ) > 0 )
				{
					$values_form_id = $field_options[ $key ];
					$values_form_id = $this->maybe_correct_form_id( $parent_blog_id, $field_options[ 'get_values_field' ] );
					$fs->debug( 'Fixed form IDs: %s -> %s', $field_options[ 'get_values_form' ], $values_form_id );
					$fs->debug( 'Getting equivalent of %s %s', $values_form_id, $field_options[ 'get_values_field' ] );
					// Convert the ID to a string.
					$equivalent_form_data = $this->get_equivalent_form_data(
						$parent_blog_id,
						$values_form_id,
						$field_options[ 'get_values_field' ]
					);
					$get_values_form_id = $equivalent_form_data->form->id;


					// Get the parent form, if any.
					if ( $equivalent_form_data->form->parent_form_id > 0 )
						$get_values_form_id = $equivalent_form_data->form->parent_form_id;

					$fs->debug( 'Setting new %s from %s / %s to %s / %s',
						$key,
						$field_options[ 'get_values_form' ],
						$field_options[ 'get_values_field' ],
						$get_values_form_id,
						$equivalent_form_data->field->id
					);
					$field_options[ $key ] = $get_values_form_id;
					$field_options[ 'get_values_field' ] = $equivalent_form_data->field->id;
					$modified = true;
				}
			}

			$key = 'watch_lookup';
			if ( isset( $field_options[ $key ] ) && is_array( $field_options[ $key ] ) )
			{
				if ( count( $field_options[ $key ] ) > 0 )
				{
					$new_watch_lookup = [];
					// For some weird and wonderful reason the watch lookups are stored... using field IDs.
					foreach( $field_options[ $key ] as $field_id )
					{
						if ( $field_id < 1 )
						{
							$new_watch_lookup []= 0;
							continue;
						}
						$watch_lookup_field = $this->get_sql_field( $parent_blog_id, $field_id );
						$equivalent_form_data = $this->get_equivalent_form_data(
							$parent_blog_id,
							$watch_lookup_field->form_id,
							$watch_lookup_field->id
						);
						$new_watch_lookup []= $equivalent_form_data->field->id;
					}
					$field_options[ $key ] = $new_watch_lookup;
					$fs->debug( 'Setting new %s from %s to %s', $key, $field_id, $field_options[ $key ] );
				}
			}

			if ( ! $modified )
				continue;

			$fs->debug( 'Updating field_options for field %s: %s', $new_field->id, $field_options );
			$query = $wpdb->update( $table, [
				'field_options' => serialize( $field_options ),
			], [ 'id' => $new_field->id ] );
		}

		// Formidable caches its fields.
		wp_cache_delete( $new_form_id, 'frm_field' );
		\FrmField::delete_form_transient( $new_form_id );

		// A raw SQL query is far, far more reliable than a stupid get_posts with its 5 post limit and post_status sensitivity.
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` = 'frm_form_actions' AND `menu_order` = '%d'", $wpdb->posts, $new_form_id );
		$fs->debug( $query );
		$old_form_actions = $wpdb->get_col( $query );
		$old_form_actions = array_combine( $old_form_actions, $old_form_actions );
		$fs->debug( 'Old form actions: %s', $old_form_actions );

		switch_to_blog( $parent_blog_id );
		$query = sprintf( "SELECT `ID` FROM `%s` WHERE `post_type` = 'frm_form_actions' AND `menu_order` = '%d'", $wpdb->posts, $parent_form_id );
		$fs->debug( $query );
		$form_actions = $wpdb->get_col( $query );
		restore_current_blog();

		$fields = $this->collection( $parent_blog_id )
			->get( $parent_form_id )
			->fields;
		foreach( $form_actions as $post_id )
		{
			$fs->debug( 'Broadcasting form action %s', $post_id );
			$target_blog = get_current_blog_id();
			switch_to_blog( $parent_blog_id );
			$form_action_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $post_id, [ $target_blog ] );
			restore_current_blog();
			$new_form_action_id = $form_action_bcd->new_post( 'ID' );

			unset( $old_form_actions[ $new_form_action_id ] );

			// Get the current post_content.
			$action_post = get_post( $new_form_action_id );
			$post_content = $action_post->post_content;
			$post_content = json_decode( $post_content );

			$fs->debug( 'Original action %s: %s', $new_form_action_id, $post_content );

			$o = (object)[];
			$o->fields = $fields;
			$o->form_id = $parent_form_id;
			$o->form_data = $this;
			$o->parent_blog_id = $parent_blog_id;
			$o->object = $post_content;
			$this->replace_field_ids( $o );
			$post_content = $o->object;
			$post_content = json_encode( $post_content );

			// Update the menu_order (form) of this new form action.
			$query = sprintf( "UPDATE `%s` SET `menu_order` = '%d', `post_content` = '%s' WHERE `ID` = '%d'",
				$wpdb->posts,
				$new_form_id,
				esc_sql( $post_content ),
				$new_form_action_id
			);
			$fs->debug( $query );
			$wpdb->get_results( $query );
		}

		// Delete orphaned actions.
		foreach( $old_form_actions as $post_id )
		{
			$fs->debug( "Deleting orphaned form action %s", $post_id );
			wp_delete_post( $post_id, true );
		}

		return $new_form_id;
	}

	/**
		@brief		Return an array of equivalent field IDs.
		@details	Sorted with the largest new IDs first.
		@since		2020-08-18 12:21:20
	**/
	public function get_equivalent_field_ids( $parent_blog_id, $form_id )
	{
		$field_ids = [];
		foreach( $this->get( 'fields' ) as $field_data )
		{
			$old_field_id = $field_data->id;
			$new_field_id = $this->get_equivalent_form_data( $parent_blog_id, $form_id, $old_field_id )->field->id;
			$field_ids[ $old_field_id ] = $new_field_id;
		}
		// We need to replace the largest values first, otherwise we could be replacing old 5 -> new 7, and then old 7 with new 10.
		arsort( $field_ids );
		return $field_ids;
	}

	/**
		@brief		Return the form and field equivalent to these ids.
		@since		2019-05-16 14:13:52
	**/
	public function get_equivalent_form_data( $parent_blog_id, $form_id, $field_id = 0 )
	{
		$r = (object)[];
		$r->form = (object)[];
		$r->form->id = 0;
		$r->field = (object)[];
		$r->field->id = 0;

		$parent_form = $this->get_form_data( $parent_blog_id, $form_id );
		if ( $parent_form )
		{
			$child_form = $this->get_form_data_by_key( $parent_form->form->form_key );

			if ( ! $child_form )
			{
				$this->broadcast_form( $parent_blog_id, $form_id );
				return $this->get_equivalent_form_data( $parent_blog_id, $form_id, $field_id );
			}

			if ( $child_form )
			{
				$r->form = $child_form->form;
				if ( $field_id > 0 )
				{
					$fs = Formidable_Shortcode::instance();
					$parent_field_key = $this->get_field_key( $parent_form, $field_id );

					// We have now found the equivalent form on the child with the same key.
					// Find the equivalent field.
					foreach( $child_form->fields as $field )
					{
						if ( $field->field_key == $parent_field_key )
						{
							$r->field = $field;
						}
					}
				}
			}
		}

		return $r;
	}

	/**
		@brief		Return the ID of the equivalent from on this blog.
		@since		2019-05-16 19:43:05
	**/
	public function get_equivalent_form_id( $parent_blog_id, $form_id )
	{
		$equivalent_form_data = $this->get_equivalent_form_data(
			$parent_blog_id,
			$form_id
			// No field necessary.
		);

		if ( $equivalent_form_data->form->id < 1 )
		{
			Formidable::instance()->debug( 'Going to broadcast form %s', $form_id );
			$current_blog_id = get_current_blog_id();
			// Broadcast a formidable shortcode in order to get the form broadcasted.
			switch_to_blog( $parent_blog_id );
			$this->broadcast_shortcode( sprintf( '[formidable id="%s"]', $form_id ), [ $current_blog_id ] );
			restore_current_blog();

			// Try this again.
			$equivalent_form_data = $this->get_equivalent_form_data(
				$parent_blog_id,
				$form_id
				// No field necessary.
			);
		}

		return $equivalent_form_data->form->id;
	}

	/**
		@brief		Return the data for this blog ID and form ID.
		@since		2019-05-15 17:18:48
	**/
	public function get_form_data( $blog_id, $form_id )
	{
		$col = $this->collection( $blog_id );
		$r = $col->get( $form_id );
		if ( ! $r )
		{
			switch_to_blog( $blog_id );
			$r = $this->get_form_data_by_id( $form_id );
			$col->set( $form_id, $r );
			restore_current_blog();
		}

		return $r;
	}

	/**
		@brief		Convenience method to return the key of a field in a form.
		@since		2019-05-15 19:46:10
	**/
	public function get_field_key( $form_data, $field_id )
	{
		foreach( $form_data->fields as $field )
			if ( $field->id == $field_id )
				return $field->field_key;
		return false;
	}

	/**
		@brief		Return the fields for this blog and form ID.
		@since		2019-05-15 17:41:43
	**/
	public function get_fields( $blog_id, $form_id )
	{
		return $this->get_form_data( $blog_id, $form_id )->fields;
	}

	/**
		@brief		Return the form for this blog and form ID.
		@since		2019-05-15 17:41:43
	**/
	public function get_form( $blog_id, $form_id )
	{
		return $this->get_form_data( $blog_id, $form_id )->form;
	}

	/**
		@brief		Return a form and fields using this key and value (id, form_key, etc).
		@since		2019-05-15 17:46:30
	**/
	public function get_form_data_by( $key, $value )
	{
		// Have we already fetched this data?
		$blog_id = get_current_blog_id();
		$col = $this->collection( $blog_id );
		if ( $col->has( $value ) )
			return $col->get( $value );

		global $wpdb;

		$table = Formidable::table_name( 'frm_forms', $wpdb->prefix );
		Formidable::instance()->database_table_must_exist( $table );
		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%s` WHERE `%s` = '%s'", $table, $key, $value );
		Formidable::instance()->debug( $query );
		$form = $wpdb->get_row( $query );
		// The form must exist.
		if ( ! $form )
		{
			$col->set( $value, false );
			return false;
		}

		$r = (object) [];
		$r->form = $form;

		// Fetch the fields from the old blog, because we are going to be parsing the field IDs later.
		$table = Formidable::table_name( 'frm_fields', $wpdb->prefix );
		$query = sprintf( "SELECT * FROM `%s` WHERE `form_id` = '%s'", $table, $form->id );
		Formidable::instance()->debug( $query );
		$fields = $wpdb->get_results( $query );

		$r->fields = $fields;

		$col->set( $value, $r );
		$col->set( $form->id, $r );

		return $r;
	}

	/**
		@brief		Retrieve the form and fields using this form ID.
		@since		2019-05-15 17:45:31
	**/
	public function get_form_data_by_id( $form_id )
	{
		return $this->get_form_data_by( 'id', $form_id );
	}

	/**
		@brief		Retrieve the form and fields using this form key.
		@since		2019-05-15 17:45:31
	**/
	public function get_form_data_by_key( $form_key )
	{
		return $this->get_form_data_by( 'form_key', $form_key );
	}

	/**
		@brief		Retrieve the SQL row using a field ID.
		@details	Used because some form options store _only_ the field ID.
		@since		2019-05-15 22:22:23
	**/
	public function get_sql_field( $blog_id, $field_id )
	{
		global $wpdb;

		switch_to_blog( $blog_id );
		$table = Formidable::table_name( 'frm_fields', $wpdb->prefix );
		restore_current_blog();

		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $field_id );
		Formidable::instance()->debug( $query );
		$field = $wpdb->get_row( $query );

		return $field;
	}

	/**
		@brief		Find the form that this field ID belongs to.
		@since		2019-12-04 15:50:58
	**/
	public function maybe_correct_form_id( $blog_id, $field_id )
	{
		global $wpdb;

		switch_to_blog( $blog_id );
		$table = Formidable::table_name( 'frm_fields', $wpdb->prefix );
		restore_current_blog();

		$query = sprintf( "SELECT `form_id` FROM `%s` WHERE `id` = '%s'", $table, $field_id );
		Formidable::instance()->debug( $query );
		return $wpdb->get_var( $query );
	}

	/**
		@brief		Relpace the field IDs in this object.
		@since		2019-11-22 07:38:02
	**/
	public function replace_field_ids( $o )
	{
		$is_array = is_array( $o->object );
		foreach( (array) $o->object as $index => $data )
		{
			if ( is_array( $data ) || is_object( $data ) )
			{
				$new_o = clone( $o );
				$new_o->object = $data;
				$this->replace_field_ids( $new_o );
				$o->object->$index = $new_o->object;
			}
			else
			{
				foreach( $o->fields as $field )
				{
					$field_id = $field->id;
					$equivalent_form_data = $o->form_data->get_equivalent_form_data(
						$o->parent_blog_id,
						$o->form_id,
						$field_id
					);
					$new_field_id = $equivalent_form_data->field->id;
					if ( $data == $field_id )
						$data = $new_field_id;
					else
					{
						$data = str_replace( '[' . $field_id . ']', '[' . $new_field_id . ']', $data );

						// For interval fields.
						$data = preg_replace( '/^' . $field_id . '-/', $new_field_id . '-', $data );
						$data = preg_replace( '/-' . $field_id . '$/', '-' . $new_field_id, $data );
					}
				}
				if ( $is_array )
					$o->object[ $index ] = $data;
				else
					$o->object->$index = $data;
			}
		}
	}
}
