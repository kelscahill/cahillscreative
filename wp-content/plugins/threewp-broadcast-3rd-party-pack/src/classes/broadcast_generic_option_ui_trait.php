<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Handles the broadcasting of option arrays that look like posts.
	@details	Used by
					toolset since 2017-10
	@since		2017-10-13 15:57:46
**/
trait broadcast_generic_option_ui_trait
{
	/**
		@brief		Generic broadcasting function.
		@details	Add the trait and then call the method below to display the UI which then also handles the broadcasting of the option array.
					Originally part of the Toolset add-on.
		@param		@options	Array of:
						label_plural					posts
						label_singular					post
						option_name						The name of the option in which to find the array.
		@since		2017-10-06 19:34:42
	**/
	public function broadcast_generic_option_ui( $options )
	{
		$form = $this->form();
		$options = (object) $options;
		$r = '';

		$items_select = $form->select( 'items' )
			// Select the generic post to broadcast
			->description_( __( 'Select the %s to broadcast to the selected blogs.', 'threewp_broadcast' ), $options->label_plural )
			// ITEMTYPE to broadcast
			->label_( __( '%s to broadcast', 'threewp_broadcast' ), ucfirst( $options->label_plural ) )
			->multiple()
			->size( 10 )
			->required();

		// Display a select with all of the items on this blog.
		$items = get_option( $options->option_name );
		if ( ! $items )
			$items = [];
		foreach( $items as $item )
		{
			$item = (object) $item;
			$items_select->option( sprintf( '%s (%s)', $item->labels[ 'name' ], $item->slug ), $item->slug );
		}

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

		$fs = $form->fieldset( 'fs_actions' );
		// Fieldset label
		$fs->legend->label( 'Actions' );

		$nonexisting_action = $fs->select( 'nonexisting_action' )
			->description_( __( 'What to do if the %s does not exist on the target blog.', 'threewp_broadcast' ), $options->label_singular )
			->label_( __( 'If the %s does not exist', 'threewp_broadcast' ), $options->label_singular )
			->options( [
				// Create the item
				sprintf( __( 'Create the %s', 'threewp_broadcast' ), $options->label_singular ) => 'create',
				__( 'Skip this blog', 'threewp_broadcast' ) => 'skip',
			] )
			->value( 'create' );

		$existing_action = $fs->select( 'existing_action' )
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

		$submit = $form->primary_button( 'copy_items' )
			// Copy ITEM button
			->value_( __( 'Copy %s', 'threewp_broadcast' ), $options->label_plural );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			foreach ( $blogs_select->get_post_value() as $blog_id )
			{
				// Don't copy the item to ourself.
				if ( $blog_id == get_current_blog_id() )
					continue;
				switch_to_blog( $blog_id );

				$blog_items = get_option( $options->option_name );
				$save = false;

				foreach( $items_select->get_post_value() as $item_slug )
				{
					$broadcast = false;
					if ( ! isset( $blog_items[ $item_slug ] ) )
					{
						$this->debug( 'Item %s not found on blog %s.', $item_slug, $blog_id );
						if ( $nonexisting_action->get_post_value() == 'create' )
						{
							$this->debug( 'Creating item %s.', $item_slug );
							$broadcast = true;
						}
					}
					else
					{
						$this->debug( 'Item %s found on blog %s.', $item_slug, $blog_id );
						if ( $existing_action->get_post_value() == 'overwrite' )
						{
							$this->debug( 'Overwriting item %s.', $item_slug );
							$broadcast = true;
						}
					}

					if ( $broadcast )
					{
						$save = true;
						$blog_items[ $item_slug ] = $items[ $item_slug ];
					}
				}

				if ( $save )
					update_option( $options->option_name, $blog_items );

				restore_current_blog();
			}
			$r .= $this->info_message_box()->_( __( 'The selected items have been copied to the selected blogs.', 'threewp_broadcast' ) );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		return $r;
	}
}
