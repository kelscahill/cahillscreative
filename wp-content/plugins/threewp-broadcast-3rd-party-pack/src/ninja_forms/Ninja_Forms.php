<?php

namespace threewp_broadcast\premium_pack\ninja_forms;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/ninja-forms/">Ninja Forms</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-01-11 22:51:31
**/
class Ninja_Forms
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\copy_options_trait;
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Constructor.
		@since		2017-04-27 22:46:42
	**/
	public function _construct()
	{
		parent::_construct();
		$this->add_action( 'threewp_broadcast_menu' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_ninja_forms' )
			->callback_this( 'show_copy_settings' )
			->menu_title( 'Ninja Forms' )
			->page_title( 'Ninja Forms Broadcast' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		// Retrieve the form.
		$query = sprintf( "SELECT * FROM `%snf3_forms` WHERE `id` = '%s'", $source_prefix, $item->id );
		$this->debug( $query );
		$form = $wpdb->get_row( $query );

		restore_current_blog();

		// No form? Invalid shortcode. Too bad.
		if ( ! $form )
			throw new \Exception( 'No form found.' );

		$target_prefix = $wpdb->prefix;

		// Find a form with the same name.
		$query = sprintf( "SELECT * FROM `%snf3_forms` WHERE `title` = '%s'", $target_prefix, $form->title );
		$results = $wpdb->get_results( $query );

		if ( count( $results ) < 1 )
		{
			$columns = $this->get_database_table_columns( $source_prefix . 'nf3_forms', [ 'except' => [ 'id' ] ] );
			$columns = "`" . implode( "`,`", $columns ) . "`";
			$query = sprintf( "INSERT INTO `%snf3_forms` ( %s ) ( SELECT %s FROM `%snf3_forms` WHERE `title` ='%s' )",
				$target_prefix,
				$columns,
				$columns,
				$source_prefix,
				$form->title
			);
			$wpdb->query( $query );
			$new_form_id = $wpdb->insert_id;
			$this->debug( 'Using new form %s', $new_form_id );
		}
		else
		{
			$result = reset( $results );
			$new_form_id = $result->id;
			$this->debug( 'Using existing form %s', $new_form_id );
		}

		// Update the form data.
		$new_form_data = (array)$form;
		unset( $new_form_data[ 'id' ] );
		$formats = [];
		foreach( $new_form_data as $index => $value )
		{
			if ( intval( $value ) == $value )
				$formats []= '%d';
			else
				$formats []= '%s';
		}
		$this->debug( 'Updating %s, %s, %s', $target_prefix . 'nf3_forms', $new_form_data, [ 'id' => $new_form_id ] );
		$wpdb->update( $target_prefix . 'nf3_forms', $new_form_data, [ 'id' => $new_form_id ], $formats );

		// Form meta. Delete all existing values.
		$query = sprintf( "DELETE FROM `%snf3_form_meta` WHERE `parent_id` = '%s'",
			$target_prefix,
			$new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// And reinsert the fresh data.
		$columns = $this->get_database_table_columns( $source_prefix . 'nf3_form_meta', [ 'except' => [ 'id', 'parent_id' ] ] );
		$columns = "`" . implode( "`,`", $columns ) . "`";
		$query = sprintf( "INSERT INTO `%snf3_form_meta` ( `parent_id`, %s ) ( SELECT %s, %s FROM `%snf3_form_meta` WHERE `parent_id` ='%s' )",
			$target_prefix,
			$columns,
			$new_form_id,
			$columns,
			$source_prefix,
			$form->id
		);
		$this->debug( $query );
		$wpdb->get_results( $query );

		// Field meta must be deleted before the fields, because of their IDs.
		$query = sprintf( "DELETE FROM `%snf3_field_meta` WHERE `parent_id` IN ( SELECT `id` FROM `%snf3_fields` WHERE `parent_id` = %s )",
			$target_prefix,
			$target_prefix,
			$new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Now we can delete the old fields.
		$query = sprintf( "DELETE FROM `%snf3_fields` WHERE `parent_id` = '%s'",
			$target_prefix,
			$new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		$o = (object)[];
		$o->form_id = $form->id;
		$columns =
		$o->item_columns = $this->get_database_table_columns( $source_prefix . 'nf3_fields', [ 'except' => [ 'id', 'parent_id' ] ] );
		$o->item_table = 'fields';
		$o->item_meta_table = 'field_meta';
		$o->item_meta_columns = $this->get_database_table_columns( $source_prefix . 'nf3_field_meta', [ 'except' => [ 'id', 'parent_id' ] ] );
		$o->new_form_id = $new_form_id;
		$o->source_prefix = $source_prefix;
		$o->target_prefix = $target_prefix;
		$this->replace_item_with_meta( $o );

		unset( $o->option_key );	// So that it correctly sets the option_key again.

		// Reuse the $o from previously.
		$o->item_columns = $this->get_database_table_columns( $source_prefix . 'nf3_actions', [ 'except' => [ 'id', 'parent_id' ] ] );
		$o->item_table = 'actions';
		$o->item_meta_table = 'action_meta';
		$o->item_meta_columns = $this->get_database_table_columns( $source_prefix . 'nf3_action_meta', [ 'except' => [ 'id', 'parent_id' ] ] );
		$o->new_form_id = $new_form_id;
		$this->replace_item_with_meta( $o );

		\WPN_Helper::delete_nf_cache( $new_form_id );

		return $new_form_id;
	}

	/**
		@brief		Return an array of the options to copy.
		@since		2017-05-01 22:48:56
	**/
	public function get_options_to_copy()
	{
		return [
			'ninja_forms_settings',
		];
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'ninja_form';
	}

	/**
		@brief		Generic function for handling items with meta.
		@since		2017-01-26 10:36:02
	**/
	public function replace_item_with_meta( $options )
	{
		global $wpdb;
		if ( ! isset( $options->option_key ) )
			$options->option_key = $options->item_table;

		if ( ! isset( $options->item_meta_columns ) )
			$options->item_meta_columns = [ 'key', 'value', 'meta_key', 'meta_value' ];

		// Item meta must be deleted before the actions, because of their IDs.
		$query = sprintf( "DELETE FROM `%snf3_%s` WHERE `parent_id` IN ( SELECT `id` FROM `%snf3_%s` WHERE `parent_id` = %s )",
			$options->target_prefix,
			$options->item_meta_table,
			$options->target_prefix,
			$options->item_table,
			$options->new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Items. Delete all existing values.
		$query = sprintf( "DELETE FROM `%snf3_%s` WHERE `parent_id` = '%s'",
			$options->target_prefix,
			$options->item_table,
			$options->new_form_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

		// Each item will have to be copied individually so that we can keep track of the IDs.
		$query = sprintf( "SELECT `id` FROM `%snf3_%s` WHERE `parent_id` ='%s'",
			$options->source_prefix,
			$options->item_table,
			$options->form_id
		);
		$item_ids = $wpdb->get_col( $query );

		$item_columns = '`' . implode( '`,`', $options->item_columns ) . '`';
		$item_meta_columns = '`' . implode( '`,`', $options->item_meta_columns ) . '`';

		foreach( $item_ids as $item_id )
		{
			// Insert the field first.
			$query = sprintf( "INSERT INTO `%snf3_%s` ( `parent_id`, %s ) ( SELECT %s, %s FROM `%snf3_%s` WHERE `id` = %s )",
				$options->target_prefix,
				$options->item_table,
				$item_columns,
				$options->new_form_id,
				$item_columns,
				$options->source_prefix,
				$options->item_table,
				$item_id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
			$new_item_id = $wpdb->insert_id;

			// And now the item meta.
			$query = sprintf( "INSERT INTO `%snf3_%s` ( `parent_id`, %s ) ( SELECT %s, %s FROM `%snf3_%s` WHERE `parent_id` = %s )",
				$options->target_prefix,
				$options->item_meta_table,
				$item_meta_columns,
				$new_item_id,
				$item_meta_columns,
				$options->source_prefix,
				$options->item_meta_table,
				$item_id
			);
			$this->debug( $query );
			$wpdb->get_results( $query );
		}
	}

	/**
		@brief		show_copy_options
		@since		2017-05-01 22:47:16
	**/
	public function show_copy_settings()
	{
		echo $this->generic_copy_options_page( [
			'plugin_name' => 'Ninja Forms',
		] );
	}
}
