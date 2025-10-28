<?php

namespace threewp_broadcast\premium_pack\formidable;

use Exception;

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
		$this->add_action( 'threewp_broadcast_menu' );

		new Formidable_Shortcode();
		new Formidable_Display_Shortcode();
	}

	/**
		@brief		Add us to the menu.
		@since		2020-08-17 19:21:32
	**/
	public function admin_menu()
	{
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

	/**
		@brief		Add us to the broadcast menu.
		@since		2020-08-17 19:23:29
	**/
	public function threewp_broadcast_menu( $action )
	{
		$action->broadcast->add_submenu_page(
			'threewp_broadcast',
			'Formidable',
			'Formidable',
			'edit_posts',
			'bc_formidable',
			[ &$this, 'menu_tabs' ]
		);
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
		if ( $bcd->post->post_type != 'frm_display' )
			return;

		$fd = $bcd->formidable_frm_display;
		$form_data = $fd->get( 'form_data' );

		$form_id = $bcd->custom_fields()->get_single( 'frm_form_id' );
		$new_form_id = $form_data->get_equivalent_form_data( $bcd->parent_blog_id, $form_id )->form->id;

		if ( ! $new_form_id )
			return;

		$cf = $bcd->custom_fields()->child_fields();
		$cf->update_meta( 'frm_form_id', $new_form_id );

		// VARIABLES
		$strings = [
			'post_content' => $bcd->post->post_content,
			'frm_dyncontent' => $bcd->custom_fields()->get_single( 'frm_dyncontent' ),

		];
		$field_ids = $form_data->get_equivalent_field_ids( $bcd->parent_blog_id, $form_id );
		foreach( $strings as $string_index => $string )
		{
			// This is a two step process: first replace OLD with xNEW, then when everything is done, xNEW with just NEW.
			// This is to prevent 3 being replaced with 10 and then 10 with 13 directly afterwards.
			foreach( $field_ids as $old_id => $new_id )
				$string = str_replace( '[' . $old_id . ']', '[x_broadcastx' . $new_id . ']', $string );
			$string = str_replace( '[x_broadcastx', '[', $string );
			$strings[ $string_index ] = $string;
		}
		$this->debug( 'Updating post content: %s', $strings[ 'post_content' ] );
		wp_update_post( [ 'ID' => $bcd->new_post( 'ID' ), 'post_content' => $strings[ 'post_content' ] ] );
		if ( $strings[ 'frm_dyncontent' ] != '' )
			$cf->update_meta( 'frm_dyncontent', $strings[ 'frm_dyncontent' ] );

		// FRM OPTIONS
		$frm_options = $bcd->custom_fields()->get_single( 'frm_options' );
		$frm_options = maybe_unserialize( $bcd->custom_fields()->get_single( 'frm_options' ) );
		if ( is_array( $frm_options ) )
		{
			$this->debug( 'Original frm_options %s', $frm_options );

			foreach( [
				'order_by',
				'where',
			] as $type )
			{
				$new_type_values = [];
				if ( ! isset( $frm_options[ $type ] ) )
					continue;
				foreach( $frm_options[ $type ] as $index => $field_id )
				{
					$parent_field = $form_data->get_sql_field( $bcd->parent_blog_id, $field_id );
					$new_where_field = $form_data->get_equivalent_form_data( $bcd->parent_blog_id, $parent_field->form_id, $field_id );
					$new_type_values[ $index ] = $new_where_field->field->id;
				}
				$frm_options[ $type ] = $new_type_values;
			}
			foreach( [
				'date_field_id',
				'edate_field_id',
				'repeat_event_field_id',
				'repeat_edate_field_id',
			] as $type )
			{
				if ( ! isset( $frm_options[ $type ] ) )
					continue;
				$field_id = $frm_options[ $type ];
				$new_field = $form_data->get_equivalent_form_data( $bcd->parent_blog_id, $form_id, $field_id );
				$new_type_value = $new_field->field->id;
				$frm_options[ $type ] = $new_type_value;
			}

			$this->debug( 'Updating frm_options %s', $frm_options );
			$cf->update_meta( 'frm_options', $frm_options );
		}
	}

	// -------
	// MISC
	// -------

	/**
		@brief		Broadcast the entries of a form to one or more blogs.
		@since		2020-08-17 19:36:36
	**/
	public function broadcast_entries( $options )
	{
		$options = array_merge( [
			'blogs' => [],
			'form_id' => false,
			'mode' => 'append',		// "append" to only add new entries, "overwrite" to overwrite all entries.
		], (array) $options );
		$options = (object) $options;

		if ( ! $options->form_id )
			throw new Exception( 'No form ID specified!' );

		global $wpdb;

		$form_data = static::get_form_data( $options->form_id );
		$parent_blog_id = get_current_blog_id();

		$parent_entries_table = $this->table_name( 'frm_items' );
		$entries_columns = $this->get_database_table_columns_string( $parent_entries_table, [ 'except' => [ 'id', 'form_id' ] ] );
		$parent_entry_meta_table = $this->table_name( 'frm_item_metas' );
		$entry_meta_columns = $this->get_database_table_columns_string( $parent_entry_meta_table, [ 'except' => [ 'id', 'item_id' ] ] );

		foreach( $options->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );
			$new_form_id = $form_data->get_equivalent_form_data( $parent_blog_id, $options->form_id )->form->id;
			if ( ! $new_form_id )
			{
				$this->debug( 'No equivalent of form %s on blog %s.', $options->form_id, $blog_id );
				restore_current_blog();
				continue;
			}
			$this->debug( 'Copying form entries to form #%s on blog %s', $new_form_id, $blog_id );

			$child_entries_table = $this->table_name( 'frm_items' );
			$child_entry_meta_table = $this->table_name( 'frm_item_metas' );

			if ( $options->mode == 'overwrite' )
			{
				$query = sprintf( "DELETE FROM `%s` WHERE `form_id` = '%s'",
					$child_entries_table,
					$new_form_id
				);
				$this->debug( $query );
				$entries = $wpdb->get_results( $query );
			}

			// Insert each entry individually.
			$query = sprintf( "SELECT `id`, %s FROM `%s` WHERE `form_id` = '%s' AND `item_key` NOT IN
						( SELECT `item_key` FROM `%s` WHERE `form_id` = '%s' )",
				$entries_columns,
				$parent_entries_table,
				$options->form_id,
				$child_entries_table,
				$new_form_id
			);
			$this->debug( $query );
			$entries = $wpdb->get_results( $query );

			// Replace the field IDs.
			$field_ids = $form_data->get_equivalent_field_ids( $parent_blog_id, $options->form_id );

			$new_entry_ids = [];
			foreach( $entries as $entry )
			{
				$old_entry_id = $entry->id;
				unset( $entry->id );
				$entry->form_id = $new_form_id;

				if ( $entry->post_id > 0 )
				{
					$old_post_id = $entry->post_id;
					switch_to_blog( $parent_blog_id );
					$post_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $old_post_id, [ $blog_id ] );
					restore_current_blog();
					$new_post_id = $post_bcd->new_post( 'ID' );
					$this->debug( 'New post ID for %s is %s', $old_post_id, $new_post_id );
					$entry->post_id = $new_post_id;
				}

				$wpdb->insert( $child_entries_table, (array)$entry );
				$new_entry_id = $wpdb->insert_id;
				$this->debug( 'Inserted entry %s as %s', $old_entry_id, $new_entry_id );
				$new_entry_ids []= $new_entry_id;

				$query = sprintf( "SELECT %s FROM `%s` WHERE `item_id` = '%s'",
					$entry_meta_columns,
					$parent_entry_meta_table,
					$old_entry_id
				);
				$this->debug( $query );
				$old_metas = $wpdb->get_results( $query );

				foreach( $old_metas as $old_meta )
				{
					$new_meta = $old_meta;
					$new_field_id = $field_ids[ $old_meta->field_id ];
					$new_meta->item_id = $new_entry_id;
					$new_meta->field_id = $new_field_id;
					$this->debug( 'Inserting entry meta %s', $new_meta );
					$wpdb->insert( $child_entry_meta_table, (array) $new_meta );
				}
			}
			restore_current_blog();
		}
	}

	/**
		@brief		Show the UI to broadcast form entries.
		@since		2020-08-17 19:25:52
	**/
	public function broadcast_entries_ui()
	{
		$form = $this->form();
		$r = '';

		$forms = \FrmForm::getAll();
		$form_opts = [];
		foreach( $forms as $formidable_form )
			$form_opts[ $formidable_form->id ] = $formidable_form->name;
		asort( $form_opts );

		$forms_input = $form->select( 'forms_input' )
			->description( __( 'Select the form whose entries you wish to broadcast.', 'threewp_broadcast' ) )
			->label( __( 'Forms', 'threewp_broadcast' ) )
			->multiple()
			->opts( $form_opts )
			->required();

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to broadcast the entries.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$mode_input = $form->select( 'mode' )
			->description( __( 'How to broadcast the entries.', 'threewp_broadcast' ) )
			->label( __( 'Mode', 'threewp_broadcast' ) )
			->opt( 'append', 'Add new entries only' )
			->opt( 'overwrite', 'Replace all entries' )
			->value( 'append' );

		$go = $form->primary_button( 'go' )
			// Button
			->value( __( 'Broadcast selected form entries', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();
			$messages = [];

			$form_ids = $forms_input->get_post_value();
			$blog_ids = $blogs_select->get_post_value();

			switch( $mode_input->get_post_value() )
			{
				case 'overwrite':
					$mode = 'overwrite';
					break;
				default:
					$mode = 'append';
					break;
			}

			foreach( $form_ids as $form_id )
			{
				$this->broadcast_entries( [
					'form_id' => $form_id,
					'blogs' => $blog_ids,
					'mode' => $mode,
				] );
			}

			$r .= $this->info_message_box()->_( 'The selected entries have been broadcasted!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Return the form and fields for this form.
		@since		2019-05-13 15:07:21
	**/
	public function get_form_data( $form_id )
	{
		global $wpdb;
		$form_data = new Form_Data();

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
		@brief		Display the tabs for the menu.
		@since		2015-03-15 10:45:55
	**/
	public function menu_tabs()
	{
		$tabs = $this->tabs();
		$tabs->tab( 'entries' )
			->callback_this( 'broadcast_entries_ui' )
			->name( 'Formidable Entries' );

		echo $tabs->render();
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
