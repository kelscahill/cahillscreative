<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Handles the broadcasting of posts that don't use the standard post overview or editor.
	@details	Used by
					pods since 2017-10
					toolset since 2017-10
	@since		2017-10-06 19:34:42
**/
trait broadcast_generic_post_ui_trait
{
	/**
		@brief		Generic broadcasting function.
		@details	Add the trait and then call the method below to display the UI which then also handles the broadcasting of the post.
					Originally part of the Toolset add-on.
		@param		@options	Array of:
						[post_status]					Optional post status the post type uses. Default is publish.
						post_type						post
						label_plural					posts
						label_singular					post
		@since		2017-10-06 19:34:42
	**/
	public function broadcast_generic_post_ui( $options )
	{
		$options = (object) array_merge( [
			'post_status' => 'publish',
		], $options );

		$form = $this->form();
		$r = '';

		$items_select = $form->select( 'items' )
			// Select the generic post to broadcast
			->description_( __( 'Select the %s to broadcast to the selected blogs.', 'threewp_broadcast' ), $options->label_plural )
			// POSTTYPE to broadcast
			->label_( __( '%s to broadcast', 'threewp_broadcast' ), ucfirst( $options->label_plural ) )
			->multiple()
			->size( 10 )
			->required();

		// Display a select with all of the items on this blog.
		$items = get_posts( [
			'posts_per_page' => -1,
			'post_status' => $options->post_status,
			'post_type' => $options->post_type,
		] );
		$items = $this->array_rekey( $items, 'post_name' );
		foreach( $items as $item )
			$items_select->option( sprintf( '%s (%s)', $item->post_title, $item->ID ), $item->post_name );

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

		$has_php_code = class_exists( 'threewp_broadcast\\premium_pack\\php_code\\PHP_Code' );
		if ( $has_php_code )
			$has_php_code = \threewp_broadcast\premium_pack\php_code\PHP_Code::instance();
		if ( $has_php_code )
		{
			$run_find_unlinked = $fs->checkbox( 'run_find_unlinked' )
				->checked( true )
				->description_( __( 'Use the PHP Code add-on to run a "find unlinked children" bulk post action on the child blog(s) before broadcasting, hopefully linking any existing %s beforehand, preventing dupes.', 'threewp_broadcast' ), $options->label_plural )
				->label( __( 'Run Find Unlinked bulk post action', 'threewp_broadcast' ) );
		}
		else
		{
			$fs->markup( 'm_run_find_unlinked' )
				->p_( 'Would you like to automatically try and find unlinked %s on the child blog(s) before broadcasting in order to link them up and prevent duplicates? Enable the <a href="https://broadcast.plainviewplugins.com/addon/php-code/">PHP Code add-on</a> to gain this functionality.', $options->label_plural );
		}

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

				$blog_items = get_posts( [
					'posts_per_page' => -1,
					'post_status' => $options->post_status,
					'post_type' => $options->post_type,
				] );
				$blog_items = $this->array_rekey( $blog_items, 'post_name' );

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
						restore_current_blog();

						if ( isset( $run_find_unlinked ) )
							if ( $run_find_unlinked->is_checked() )
							{
								// Run the find unlinked children bulk action for this post.
								$action = new \threewp_broadcast\premium_pack\php_code\actions\load_wizards();
								$action->execute();
								// Retrieve the wizard we use.
								$wizard = $action->wizards->get( 'run_bulk_action' );
								// Replace the post types in the code.
								$code = $wizard->code()->get( 'setup' );
								$code = str_replace( "[ 'post' ]", "[ '" . $options->post_type . "' ]", $code );
								$code = $wizard->code()->set( 'setup', $code );

								$action = new \threewp_broadcast\premium_pack\php_code\actions\run_code();
								$action->blogs = [ $blog_id ];
								$action->load_code_from_wizard( $wizard );
								$action->execute();
							}

						$original_post_id = $items[ $item_slug ]->ID;
						$this->debug( 'Broadcasting item %s.', $original_post_id );
						ThreeWP_Broadcast()->api()->broadcast_children( $original_post_id, [ $blog_id ] );
						switch_to_blog( $blog_id );
					}
				}

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
