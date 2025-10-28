<?php

namespace threewp_broadcast\premium_pack\redirection;

/**
	@brief				Adds support for the <a href="https://wordpress.org/plugins/redirection/">Redirection plugin</a>.
	@plugin_group		3rd party compatability
	@since				2022-01-20 15:21:59
**/
class Redirection
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Constructor.
		@since		2022-01-20 15:26:32
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add options.
		@since		2017-03-31 21:21:50
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$action->menu_page
			->submenu( 'threewp_broadcast_redirection' )
			->callback_this( 'broadcast_redirections_ui' )
			->menu_title( 'Redirections' )
			->page_title( 'Broadcast Redirections' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------


	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Broadcast a redirection group from this blog to another.
		@since		2022-01-20 15:43:29
	**/
	public function broadcast_redirection_group( $options )
	{
		$options = (object) $options;
		$this->debug( 'Broadcasting redirection group %s to blog %s',
			$options->group_id,
			$options->blog_id
		);

		$group_id = $options->group_id;
		$source_blog_id = get_current_blog_id();

		// Get the group info.
		$groups = $this->get_redirection_groups();
		$group = $groups[ $group_id ];
		$items = $this->get_redirection_group_items( $group_id );


		switch_to_blog( $options->blog_id );

		$table = $this->get_prefixed_table_name( 'redirection_groups' );
		if ( ! $this->database_table_exists( $table ) )
			return $this->debug( 'No %s table on this blog.', $table );

		// Find the equivalent group.
		$child_groups = $this->get_redirection_groups();
		$found_child_group_id = false;
		foreach( $child_groups as $child_group )
		{
			if ( $child_group->name != $group->name )
				continue;

			// We have found a child group with the same name.
			$found_child_group_id = $child_group->id;
			break;
		}

		$this->debug( 'Child group ID: %s', $found_child_group_id );

		// Maybe we need to create the group?
		if ( ! $found_child_group_id )
			$found_child_group_id = $this->create_redirection_group( $group );

		$this->debug( 'Child group ID: %s', $found_child_group_id );

		// Empty the child group.
		$this->delete_group_items( $found_child_group_id );

		// Copy over the items.
		$this->copy_group_items( [
			'source_blog_id' => $source_blog_id,
			'source_group_id' => $group_id,
			'target_blog_id' => $options->blog_id,
			'target_group_id' => $found_child_group_id,
		] );

		restore_current_blog();
	}

	/**
		@brief		Show the UI for broadcasting redirections.
		@since		2022-01-20 15:25:01
	**/
	public function broadcast_redirections_ui()
	{
		$form = $this->form2();
		$r = '';

		$items_select = $form->select( 'redirection_groups' )
			->description( 'Select the redirection groups to broadcast to the selected blogs.' )
			->label( 'Redirection groups to broadcast' )
			->multiple()
			->size( 10 )
			->required();

		// Display a select with all of the types on this blog.

		$items = $this->get_redirection_groups();
		$items = $this->array_rekey( $items, 'name' );
		foreach( $items as $item )
			$items_select->option( sprintf( '%s (%s)', $item->name, $item->id ), $item->id );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to copy the selected items above.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'required' => true,
			'name' => 'blogs',
		] );

		$submit = $form->primary_button( 'copy' )
			->value( 'Copy' );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			$blog_ids = $blogs_select->get_post_value();
			foreach( $items_select->get_post_value() as $item_id )
				foreach( $blog_ids as $blog_id )
					$this->broadcast_redirection_group( [
						'blog_id' => $blog_id,
						'group_id' => $item_id,
					] );
			$r .= $this->info_message_box()->_( 'The selected items have been copied to the selected blogs.' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Copy the redirection items of a group.
		@param		$options an array or object.
						source_blog_id
						source_group_id
						target_blog_id
						target_group_id
		@since		2022-01-25 17:52:18
	**/
	public function copy_group_items( $options )
	{
		$options = (object) $options;
		global $wpdb;

		$source_table = $this->get_prefixed_table_name( 'redirection_items', $options->source_blog_id );
		$columns = $this->get_database_table_columns_string( $source_table, [ 'except' => [ 'id', 'group_id' ] ] );
		$target_table = $this->get_prefixed_table_name( 'redirection_items', $options->target_blog_id );

		$query = sprintf( "INSERT INTO `%s` ( `group_id`, %s ) SELECT '%s', %s FROM `%s` WHERE `group_id` = '%s'",
			$target_table,
			$columns,
			$options->target_group_id,
			$columns,
			$source_table,
			$options->source_group_id
		);
		$this->debug( $query );
		$wpdb->query( $query );

	}

	/**
		@brief		Create a redirection group.
		@since		2022-01-20 16:01:28
	**/
	public function create_redirection_group( $data )
	{
		$new_data = clone( $data );
		unset( $new_data->id );

		global $wpdb;
		$table = $this->get_prefixed_table_name( 'redirection_groups' );
		$wpdb->insert( $table, (array) $new_data );
		return $wpdb->insert_id;
	}

	/**
		@brief		Delete the items in a redirection group.
		@since		2022-01-25 17:49:09
	**/
	public function delete_group_items( $group_id )
	{
		global $wpdb;
		$table = $this->get_prefixed_table_name( 'redirection_items' );

		$this->debug( 'Deleting items from %s, group %s.', $table, $group_id );

		$wpdb->delete( $table, [
			'group_id' => $group_id,
		] );
	}

	/**
		@brief		Return all of the items in a redirection group.
		@since		2022-01-20 15:48:16
	**/
	public function get_redirection_group_items( $group_id )
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s%s` WHERE `group_id` = '%s'",
			$wpdb->prefix,
			'redirection_items',
			$group_id,
		);
		$results = $wpdb->get_results( $query );
		return $results;
	}
	/**
		@brief		Return all of the redirection groups on this blog.
		@since		2022-01-20 15:30:34
	**/
	public function get_redirection_groups()
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s%s` ORDER BY `name`",
			$wpdb->prefix,
			'redirection_groups'
		);
		$results = $wpdb->get_results( $query );
		$results = $this->array_rekey( $results, 'id' );
		return $results;
	}
}
