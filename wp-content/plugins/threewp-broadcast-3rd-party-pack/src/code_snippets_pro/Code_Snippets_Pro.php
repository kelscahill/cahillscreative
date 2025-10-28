<?php

namespace threewp_broadcast\premium_pack\code_snippets_pro;

/**
	@brief			Adds support for the <a href="https://wordpress.org/plugins/code-snippets/">Code Snippets Pro plugin</a>.
	@plugin_group	3rd party compatability
	@since			2025-09-08 19:27:26
**/
class Code_Snippets_Pro
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_things_ui_trait;
	use \threewp_broadcast\premium_pack\classes\database_trait;

	public function _construct()
	{
		$this->add_action( 'admin_menu', 1000 );		// 1000 so that the menu item gets added after the plugin.
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- CALLBACKS
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Broadcast the snippets
		@since		2025-09-08 20:06:24
	**/
	public function broadcast_snippets()
	{
		echo $this->broadcast_things_ui( [
			'actions_add_callback' => function( $options )
			{
				$options->activate_deactivate_snippet = $options->actions_fieldset->select( 'activate_deactivate_snippet' )
					->description( 'When broadcasting the snippets, what should be done with their activation status?' )
					->label( 'Activate snippets' )
					->opt( '', 'Leave as is' )
					->opt( 'activate', 'Activate the broadcasted snippets' )
					->opt( 'deactivate', 'Deactivate the broadcasted snippets' );
			},
			'get_items_function' => function( $options )
			{
				global $wpdb;
				$table = sprintf( '%ssnippets', $wpdb->prefix );

				$this->database_table_must_exist( $table );

				$query = sprintf( "SELECT * FROM `%s` ORDER BY `name`", $table );
				$this->debug( $query );
				$rows = $wpdb->get_results( $query );

				$r = ThreeWP_Broadcast()->collection();
				foreach( $rows as $row )
					$r->set( $row->name, $row );

				return $r;
			},
			'label_plural' => 'code snippets',
			'label_singular' => 'code snippet',
			'set_items_function' => function( $array, $options )
			{
				$new_row = $array;
				$new_row = reset( $new_row );
				$new_row = (array) $new_row;
				unset( $new_row[ 'id' ] );

				switch( $options->activate_deactivate_snippet->get_filtered_post_value() )
				{
					case '':
						$this->debug( "Leaving snippet enabled status as is." );
					break;
					case 'activate':
						$this->debug( "Activating snippet." );
						$new_row[ 'active' ] = 1;
					break;
					case 'deactivate':
						$this->debug( "Deactivating snippet." );
						$new_row[ 'active' ] = 0;
					break;
				}

				global $wpdb;
				$table = sprintf( '%ssnippets', $wpdb->prefix );

				// Find the existing row, if any.
				$query = sprintf( "SELECT `id` FROM `%s` WHERE `name` = '%s'",
					$table,
					$new_row[ 'name' ],
				);
				$this->debug( $query );

				$existing_row = $wpdb->get_row( $query );
				if( $existing_row )
				{
					$this->debug( 'Updating existing row %s', $existing_row->id );
					$wpdb->update( $table, $new_row, [ 'id' => $existing_row->id ] );
					$new_row[ 'id' ] = $existing_row->id;
				}
				else
				{
					$this->debug( 'Inserting new row: %s', $new_row );
					$wpdb->insert( $table, $new_row );
				}
			},
			'show_item_label_callback' => function( $items, $item_id, $item )
			{
				return sprintf( '%s (%s)', $item->name, $item->id );
			},
		] );
	}

	/**
		@brief		Add ourselves to the Jetengine menu
		@since		2023-08-23 07:42:41
	**/
	public function admin_menu()
	{
		if ( ! is_super_admin() )
			return;

		add_submenu_page(
			'snippets',
			'Broadcast',
			'Broadcast',
			'manage_options',
			'bc_code_snippets_pro',
			[ $this, 'ui_tabs' ],
			1000,
		);
	}

	/**
		@brief		UI tabs
		@since		2025-09-08 20:05:16
	**/
	public function ui_tabs( $action )
	{
		$tabs = $this->tabs();

		$tabs->tab( 'broadcast_snippets' )
			->callback_this( 'broadcast_snippets' )
			->heading( 'Broadcast Code Snippets' )
			->name( 'Snippets' );

		echo $tabs->render();
	}

}