<?php

namespace threewp_broadcast\premium_pack\classes\generic_items;

use \Exception;

/**
	@brief		Generic handler for items in shortcodes.
	@since		2016-07-14 12:29:31
**/
abstract class Generic_Items
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\generic_items\Replace_Attachments_Trait;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Edit a container.
		@since		2014-03-12 15:59:17
	**/
	public function edit_item( $id )
	{
		$items = $this->items();
		if ( ! $items->has( $id ) )
			wp_die( 'No item with this ID exists!' );

		$generic_data = $this->get_generic_data();

		$form = $this->form2();
		$item = $items->get( $id );
		$r = '';

		$r .= $this->get_editor_html();

		$form->text( 'name' )
			->description( sprintf( 'The name of the %s.', $generic_data->singular ) )
			->size( 20, 128 )
			->label( $generic_data->Singular . ' ' . 'name' )
			->trim()
			->required()
			->size( 30 )
			->value( $item->get_slug() );

		$form->textarea( 'value' )
			->description( 'One attribute per line that contains a single attachment ID. Use a * for numbers 0 to 100.' )
			->label( 'Single ID attributes' )
			->rows( 5, 20 )
			->trim()
			->value( $item->get_value_text() );

		$text = 'One attribute per line that contains multiple attachment IDs. Use a * for numbers 0 to 100.';
		if ( isset( $generic_data->delimiters ) )
			if ( $generic_data->delimiters )
				$text .= ' Delimiters are written separated by spaces after the attribute.';
		$form->textarea( 'values' )
			->description( $text )
			->label( 'Multiple ID attributes' )
			->rows( 5, 20 )
			->trim()
			->value( $item->get_values_text() );

		if ( isset( $generic_data->delimiters ) )
			if ( $generic_data->delimiters )
				$form->markup( 'values_info' )
					->markup( 'Delimiters can be mixed within the same attribute, meaning that if you have specified commas and semicolons as delimiters, <em>ids="123,234;345"</em> will work.' );

		$form->create = $form->primary_button( 'save' )
			->value( __( 'Save', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			try
			{
				$item->set_slug( $form->input( 'name' )->get_filtered_post_value() );
				$item->parse_value( $form->input( 'value' )->get_filtered_post_value() );
				$item->parse_values( $form->input( 'values' )->get_filtered_post_value() );
				$this->save_items();
				$r .= $this->info_message_box()->_( __( 'The %s has been updated!', 'threewp_broadcast' ), $generic_data->singular );
			}
			catch( Exception $e )
			{
				$this->error_message_box()->_( 'You have errors in your settings: %s', $e->getMessage() );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Add to the admin menu.
		@since		2016-07-14 12:30:21
	**/
	public function threewp_broadcast_menu( $action )
	{
		if ( ! is_super_admin() )
			return;

		$slug = $this->get_class_slug();

		$action->menu_page
			->submenu( $slug )
			->callback_this( 'admin_menu_tabs' )
			->menu_title( $this->get_plugin_name() )
			->page_title( $this->get_plugin_name() );
	}

	/**
		@brief		Show all items.
		@since		2016-07-14 12:40:58
	**/
	public function items_overview()
	{
		$form = $this->form2();
		$generic_data = $this->get_generic_data();
		$r = ThreeWP_Broadcast()->html_css();

		$sc = $this->new_item();
		$form->select( 'type' )
			->description_( 'Choose to create an empty template or use a known %s.', $generic_data->singular )
			->label( 'Wizard' )
			->options( $sc->get_wizard_options() );

		$form->create = $form->primary_button( 'create' )
			->value( __( 'Create a new %s', 'threewp_broadcast' ), $generic_data->singular );

		$table = $this->table();
		$row = $table->head()->row();
		$table->bulk_actions()
			->form( $form )
			->add( __( 'Delete', 'threewp_broadcast' ), 'delete' )
			->cb( $row );
		$row->th()->text( $generic_data->Singular );
		$row->th()->text( 'Example' );

		$items = $this->items();

		if ( $form->is_posting() )
		{
			$form->post();
			if ( $table->bulk_actions()->pressed() )
			{
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						$ids = $table->bulk_actions()->get_rows();
						foreach( $ids as $id )
							$items->forget( $id );
						$this->save_items();
						$r .= $this->info_message_box()->_( __( 'The selected %s have been deleted!', 'threewp_broadcast' ), $generic_data->plural );
					break;
				}
			}
			if ( $form->create->pressed() )
			{
				$items = $this->items();
				$item = $this->new_item();
				$item->apply_wizard( $form->input( 'type' )->get_filtered_post_value() );
				$items->append( $item );
				$this->save_items();
				$r .= $this->info_message_box()->_(
					// Generic_Item NAME has been created
					__( '%s %s has been created!', 'threewp_broadcast' ),
					$generic_data->Singular,
					$item->get_slug() );
			}
		}

		foreach( $items as $index => $item )
		{
			$row = $table->body()->row();
			$table->bulk_actions()->cb( $row, $index );
			$url = sprintf( '<a href="%s">%s</a>', add_query_arg( [
				'tab' => 'edit',
				'id' => $index,
			] ), $item->get_slug() );
			$row->td()->text( $url );
			$row->td()->text( $item->get_info() );
		}

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $this->p( 'The spaces in the example column are for legibility.' );
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Menu tabs.
		@since		2016-07-14 12:39:44
	**/
	public function admin_menu_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'items' )
			->callback_this( 'items_overview' )
			->name( $this->get_plugin_name() );

		if ( $tabs->get_is( 'edit' ) )
		{
			$generic_data = $this->get_generic_data();
			$tabs->tab( 'edit' )
				->callback_this( 'edit_item' )
				->parameters( intval( $_GET[ 'id' ] ) )
				// Edit a BLOCK
				->name_( __( 'Edit a %s', 'threewp_broadcast' ), $generic_data->singular );
		}

		echo $tabs->render();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Content parsing
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Parse a find array containing a value or values.
		@since		2016-07-14 13:40:28
	**/
	public function parse_find( $data, $find )
	{
	}

	public function replace_id( $broadcasting_data, $find, $old_id )
	{
		return $broadcasting_data->copied_attachments()->get( $old_id );
	}

	/**
		@brief		Parse the content, replacing item.
		@since		2016-07-14 13:57:20
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		$slug = $this->get_class_slug() . '_preparse';

		if ( ! isset( $bcd->$slug ) )
			return;

		$generic_data = $this->get_generic_data();

		$finds = $bcd->$slug->get( $action->id, [] );

		foreach( $finds as $find )
		{
			$shared_find_collection = $this->shared_finds()->get_find_collection( $find );
			$shared_find = $shared_find_collection->get( 'find' );
			$unmodified_find = $shared_find;
			$item = $shared_find->original;
			//$item = $find->original;

			$replace_id_action = $this->new_replace_id_action();
			$replace_id_action->called_class = get_called_class();
			$replace_id_action->broadcasting_data = $bcd;
			$replace_id_action->find = $find;
			$replace_id_action->item = $item;
			$replace_id_action->execute();

			if ( $replace_id_action->is_finished() )
			{
				$item = $replace_id_action->item;
				$this->debug( 'Replacing %s <em>%s</em> with <em>%s</em>', $generic_data->singular, htmlspecialchars( $find->original ), htmlspecialchars( $item ) );
				$action->content = str_replace( $find->original, $item, $action->content );
				continue;
			}

			// Find single IDs
			foreach( $find->value as $attribute => $old_id )
			{
				$new_id = $this->replace_id( $bcd, $find, $old_id );
				if ( $new_id )
				{
					if ( intval( $new_id ) > 0 )
					{
						$old_attribute = sprintf( '/(%s=[\"|\'])%s([\"|\'])/', $attribute, $old_id );
						$new_attribute = sprintf( '${1}%s${2}', $new_id );
						$item = preg_replace( $old_attribute, $new_attribute, $item );
					}
					else
					{
						$item = str_replace( $old_id, $new_id, $item );
					}
				}
			}

			// Find multiple IDs
			foreach( $find->values as $attribute => $data )
			{
				$ids = $data[ 'ids' ];
				$delimiters = $data[ 'delimiters' ];

				$old_ids = $ids;
				$new_ids = $old_ids;
				foreach( $ids as $index => $old_id )
				{
					$new_id = $this->replace_id( $bcd, $find, $old_id );
					if ( $new_id )
						$new_ids[ $index ] = $new_id;
				}
				$old_regexp = sprintf( '/(%s=[\"|\'])%s([\"|\'])/', $attribute, implode( '(.*)', $old_ids ) );
				$new_regexp = reset( $new_ids );
				array_shift( $new_ids );
				foreach( $new_ids as $index => $new_id )
					$new_regexp .= sprintf( '${%s}%s', $index+2, $new_id );
				$new_regexp = sprintf( '${1}%s${%s}', $new_regexp, count( $new_ids ) + 2 );

				$this->debug( 'Replacing old %s <em>%s</em> with new <em>%s</em>.',
					$generic_data->singular,
					htmlspecialchars( $find->original ),
					htmlspecialchars( $item )
				);
				$item = preg_replace( $old_regexp, $new_regexp, $item );
			}

			// Update the shared find.
			$shared_find->original = $item;
			$shared_find_collection->decrease_counter();

			if ( ! $shared_find_collection->can_be_replaced() )
			{
				$this->debug( 'Cannot update this shared find yet.' );
				continue;
			}

			$this->debug( 'Replacing %s <em>%s</em><br/>with<br/> <em>%s</em>',
				$generic_data->singular,
				htmlspecialchars( $find->original ),
				htmlspecialchars( $item )
			);
			$action->content = str_replace( $find->original, $item, $action->content );

			// Replace the content.
			$original_content = $find->content;
			$modified_content = ThreeWP_Broadcast()->update_attachment_ids( $bcd, $original_content );
			$hash_before = md5( $action->content );
			$action->content = str_replace( ']' . $original_content . '[', ']' . $modified_content . '[', $action->content );
			$hash_after = md5( $action->content );

			if ( $hash_before != $hash_after )
				$this->debug( 'Content was modified also.' );
		}
	}

	/**
		@brief		Preparse some content.
		@since		2016-07-14 13:27:35
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		$slug = $this->get_class_slug() . '_preparse';

		// In case another preparse hasn't asked for this already.
		if ( ! isset( $bcd->$slug ) )
			$bcd->$slug = ThreeWP_Broadcast()->collection();

		$items = $this->items();

		$finds = [];

		foreach( $items as $item )
		{
			$matches = ThreeWP_Broadcast()->find_shortcodes( $content, [ $item->get_slug() ] );

			if ( count( $matches[ 0 ] ) < 1 )
				continue;

			// We've found something!
			// [2] contains only the item command / key. No options.
			foreach( $matches[ 2 ] as $index => $key )
			{
				// Does the key match this item?
				if ( $key !== $item->get_slug() )
					continue;
				$find = ThreeWP_Broadcast()->collection();
				$find->value = ThreeWP_Broadcast()->collection();
				$find->values = ThreeWP_Broadcast()->collection();

				// Complete match is in 0.
				$find->original = $matches[ 0 ][ $index ];

				// Trim off everything after the first ]
				$find->original = preg_replace( '/\].*/s', ']', $find->original );

				$find->content = $matches[ 5 ][ $index ];

				$this->debug( 'Found item %s as %s', $key, htmlspecialchars( $find->original ) );

				// Extract the ID
				foreach( $this->expand_values( $item->value ) as $attribute => $ignore )
				{
					// Does this item use this attribute?
					if ( strpos( $find->original, $attribute . '=' ) === false )
					{
						$this->debug( 'The item does not contain the attribute %s.', $attribute );
						continue;
					}

					// Remove anything before the attribute
					// That space is to ensure that we get only "image_id" and not "background_image_id".
					$string = preg_replace( '/.* ' . $attribute .'=[\"|\']/', '', $find->original );

					// And everything after the quotes.
					$string = preg_replace( '/[\"|\'].*/s', '', $string );

					// Workaround for items that don't follow the Wordpress standards: remove single apostrophies from the ends.
					$string = trim( $string, "'" );

					$this->debug( 'Attribute is: %s', $string );

					$id = $string;

					$this->debug( 'Found item %s in attribute %s.', $id, $attribute );

					$find->value->set( $attribute, $id );
				}

				// Extract the images IDs
				foreach( $item->values as $attribute => $delimiters )
				{
					// Does this item use this attribute?
					if ( strpos( $find->original, $attribute . '=' ) === false )
					{
						$this->debug( 'The item does not contain the attribute %s.', $attribute );
						continue;
					}

					// Remove anything before the attribute
					$string = preg_replace( '/.*' . $attribute .'=[\"|\']/', '', $find->original );
					// And everything after the quotes.
					$string = preg_replace( '/[\"|\'].*/', '', $string );

					// Workaround for items that don't follow the Wordpress standards: remove single apostrophies from the ends.
					$string = trim( $string, "'" );

					$this->debug( 'Attribute is: %s', $string );

					$ids = $string;

					// Convert all delimiters to commas.
					foreach( $delimiters as $delimiter )
						$ids = str_replace( $delimiter, ',', $ids );

					$this->debug( 'While looking in attribute %s, we found this: <em>%s</em>', $attribute, htmlspecialchars( $ids ) );
					// And now explode the ids.
					$ids = explode( ',', $ids );

					// Save the IDs in the find.
					$find->values->set( $attribute, [
						'ids' => $ids,
						'delimiters' => $delimiters,
					] );

					$this->debug( 'Found items %s in attribute %s', implode( ', ', $ids ), $attribute );
				}

				$parse_find_action = $this->new_parse_find_action();
				$parse_find_action->called_class = get_called_class();
				$parse_find_action->broadcasting_data = $bcd;
				$parse_find_action->find = $find;
				$parse_find_action->execute();

				if ( ! $parse_find_action->is_finished() )
					$this->parse_find( $bcd, $find );

				try
				{
					$find_collection = $this->shared_finds()->add_find( $find );
					$this->debug( 'Adding this find to the array x %s: %s', $find_collection->get_counter(), $find );
				}
				catch( Exception $e )
				{
					$this->debug( $e->getMessage() );
				}

				$finds []= $find;
			}
		}

		if ( count( $finds ) < 1 )
			return;

		$this->debug( 'Found %s item occurrences in the content.', count( $finds ) );

		$bcd->$slug->set( $action->id, $finds );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Expand any wildcards in the item.
		@since		2022-11-29 21:40:40
	**/
	public function expand_values( $array )
	{
		$r = ThreeWP_Broadcast()->collection();
		foreach( $array as $key => $value )
		{
			if ( strpos( $key, '*' ) === false )
				$r->set( $key, $value );
			else
			{
				for( $counter = 0; $counter <= 100; $counter++ )
				{
					$new_key = str_replace( '*', $counter, $key );
					$r->set( $new_key, $value );
				}
			}
		}
		return $r;
	}

	/**
		@brief		Return any html that you want to display above the shortcode editor.
		@since		2016-07-14 13:10:17
	**/
	public function get_editor_html()
	{
	}

	/**
		@brief		Return the class slug.
		@since		2016-07-14 13:28:59
	**/
	public function get_class_slug()
	{
		$slug = get_called_class();
		$slug = preg_replace( '/.*\\\\/', 'bc_', $slug );
		$slug = sanitize_title( $slug );
		return $slug;
	}

	/**
		@brief		Get the data for the type of generic handler.
		@since		2019-06-19 22:02:02
	**/
	public function get_generic_data()
	{
		return (object) [
			'delimiters' => true,
			'singular' => 'shortcode',
			'plural' => 'shortcodes',
			'Singular' => 'Shortcode',
			'Plural' => 'Shortcodes',
			'option_name' => 'shortcodes',
		];
	}

	/**
		@brief		Create a new Item object.
		@since		2016-07-14 12:54:45
	**/
	public abstract function new_item();

	/**
		@brief		Create a collection of items.
		@since		2016-07-14 12:44:29
	**/
	public function new_items()
	{
		return new Items();
	}

	/**
		@brief		Create a parse find action.
		@since		2021-02-05 16:49:19
	**/
	public function new_parse_find_action()
	{
		return new actions\parse_find();
	}

	/**
		@brief		Create a replace_id action.
		@since		2021-02-05 16:52:10
	**/
	public function new_replace_id_action()
	{
		return new actions\replace_id();
	}

	/**
		@brief		Save the collection.
		@since		2016-07-14 13:02:18
	**/
	public function save_items()
	{
		$this->update_site_option( $this->get_generic_data()->option_name, $this->items() );
	}

	/**
		@brief		Return the Shared_Finds object.
		@since		2020-01-31 08:15:43
	**/
	public function shared_finds()
	{
		$bc = ThreeWP_Broadcast();
		$key = static::shared_finds_key();
		if ( isset( $bc->$key ) )
			return $bc->$key;
		$bc->$key = $this->create_shared_finds();
		$bc->$key->parent = $this;
		return $bc->$key;
	}

	/**
		@brief		Create the shared finds instance.
		@since		2023-08-02 00:11:55
	**/
	public abstract function create_shared_finds();

	/**
		@brief		Return the key where the shared finds are stored.
		@since		2023-08-02 00:06:22
	**/
	public abstract static function shared_finds_key();

	/**
		@brief		Load all of the items in their collection.
		@since		2016-07-14 12:42:50
	**/
	public function items()
	{
		if ( isset( $this->__collection ) )
			return $this->__collection;
		$this->__collection = $this->get_site_option( $this->get_generic_data()->option_name, null );
		if ( ! $this->__collection )
			$this->__collection = $this->new_items();
		return $this->__collection;
	}

}
