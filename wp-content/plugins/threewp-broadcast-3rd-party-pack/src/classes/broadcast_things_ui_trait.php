<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Handles the broadcasting of things using anonymous functions.
	@since		2017-10-15 20:15:57
**/
trait broadcast_things_ui_trait
{
	/**
		@brief		Generic broadcasting function.
		@details	Add the trait and then call the method below to display the UI which then also handles the broadcasting of the option array.
					Originally part of the Toolset add-on.
		@param		@options	Array of:
						label_plural					posts
						label_singular					post
		@since		2017-10-06 19:34:42
	**/
	public function broadcast_things_ui( $options )
	{
		$options = (object) array_merge( [
			/**
				* @brief		The callback used to add actions in the actions fieldset.
				* @since		2025-05-01 21:11:54
			**/
			'actions_add_callback' => null,
			/**
				* @brief		The callback used to parse the post, for handling the new actions in the actions fieldset.
				* @since		2025-05-01 21:11:54
			**/
			'actions_post_callback' => null,
			'broadcasting' => false,
			'source_blog_id' => get_current_blog_id(),
		], (array) $options );
		$r = '';

		$options->form = $this->form();

		$items_select = $options->form->select( 'items' )
			// Select the generic post to broadcast
			->description_( __( 'Select the %s to broadcast to the selected blogs.', 'threewp_broadcast' ), $options->label_plural )
			// ITEMTYPE to broadcast
			->label_( __( '%s to broadcast', 'threewp_broadcast' ), ucfirst( $options->label_plural ) )
			->multiple()
			->size( 10 )
			->required();

		// Display a select with all of the items on this blog.
		$callback = $options->get_items_function;
		$items = $callback( $options );
		foreach( $items as $item_id => $item_label )
		{
			if ( isset( $options->show_item_label_callback ) )
			{
				$callback = $options->show_item_label_callback;
				$item_label = $callback( $items, $item_id, $item_label );
			}
			else
			{
				// If the label is the same as the id, don't bother showing the id.
				if ( $item_id == $item_label )
					$string = '%s';
				else
					$string = '%s (%s)';
				$item_label = sprintf( $string, $item_label, $item_id );
			}
			$items_select->option( $item_label, $item_id );
		}

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to copy the selected items above.', 'threewp_broadcast' ),
			'form' => $options->form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'required' => true,
			'name' => 'blogs',
		] );

		$options->actions_fieldset = $options->form->fieldset( 'fs_actions' );
		// Fieldset label
		$options->actions_fieldset->legend->label( 'Actions' );

		$nonexisting_action = $options->actions_fieldset->select( 'nonexisting_action' )
			->description_( __( 'What to do if the %s does not exist on the target blog.', 'threewp_broadcast' ), $options->label_singular )
			->label_( __( 'If the %s does not exist', 'threewp_broadcast' ), $options->label_singular )
			->options( [
				// Create the item
				sprintf( __( 'Create the %s', 'threewp_broadcast' ), $options->label_singular ) => 'create',
				__( 'Skip this blog', 'threewp_broadcast' ) => 'skip',
			] )
			->value( 'create' );

		$existing_action = $options->actions_fieldset->select( 'existing_action' )
			// if the ITEM
			->description_( __( 'What to do if the %s already exists on the target blog.', 'threewp_broadcast' ), $options->label_singular )
			// If the ITEM exists
			->label_( __( 'If the %s exists', 'threewp_broadcast' ), $options->label_singular )
			->options( [
				__( 'Skip this blog', 'threewp_broadcast' ) => 'skip',
				// Overwrite the existing ITEM
				sprintf( __( 'Overwrite the existing %s', 'threewp_broadcast' ), $options->label_singular ) => 'overwrite',
			] )
			->value( 'overwrite' );

		if ( $options->actions_add_callback )
		{
			$callback = $options->actions_add_callback;
			$callback( $options );
		}

		$submit = $options->form->primary_button( 'copy_items' )
			// Copy ITEM button
			->value_( __( 'Copy %s', 'threewp_broadcast' ), $options->label_plural );

		if ( $options->form->is_posting() )
		{
			$options->form->post()->use_post_values();

			if ( $options->actions_post_callback !== null )
			{
				$callback = $options->actions_post_callback;
				$callback( $options );
			}

			$options->broadcasting = true;

			foreach ( $blogs_select->get_post_value() as $blog_id )
			{
				// Don't copy the item to ourself.
				if ( $blog_id == get_current_blog_id() )
					continue;
				switch_to_blog( $blog_id );

				$callback = $options->get_items_function;
				$blog_items = $callback( $options );
				$new_blog_items = [];
				$save = false;
				$options->existing_action = $existing_action->get_post_value();
				$options->nonexisting_action = $nonexisting_action->get_post_value();

				foreach( $items_select->get_post_value() as $item_slug )
				{
					$broadcast = false;
					if ( ! isset( $blog_items[ $item_slug ] ) )
					{
						$this->debug( 'Item %s not found on blog %s.', $item_slug, $blog_id );
						if ( $options->nonexisting_action == 'create' )
						{
							$this->debug( 'Creating item %s.', $item_slug );
							$broadcast = true;
						}
					}
					else
					{
						$this->debug( 'Item %s found on blog %s.', $item_slug, $blog_id );
						if ( $options->existing_action == 'overwrite' )
						{
							$this->debug( 'Overwriting item %s.', $item_slug );
							$broadcast = true;
						}
					}

					if ( $broadcast )
					{
						$save = true;
						$new_blog_items[ $item_slug ] = $items[ $item_slug ];
					}
				}

				if ( $save )
				{
					$callback = $options->set_items_function;
					$callback( $new_blog_items, $options );
				}

				restore_current_blog();
			}
			$r .= $this->info_message_box()->_( __( 'The selected items have been copied to the selected blogs.', 'threewp_broadcast' ) );
		}

		$r .= $options->form->open_tag();
		$r .= $options->form->display_form_table();
		$r .= $options->form->close_tag();

		return $r;
	}
}
