<?php

namespace threewp_broadcast\premium_pack\classes;

use \Exception;

trait copy_options_trait
{
	/**
		@brief		Handle the copying of the options.
		@since		2017-03-17 13:10:40
	**/
	public function do_copy_options( $options )
	{
		global $wpdb;

		$options = array_merge( [
			'blogs' => [],					// Array of blog_ids to which to copy to.
			'dry_run' => false,				// Will not copy any options unless set to true.
			'linked_posts' => [],			// Array of strings that contain IDs of linked posts that need to be translated.
			'options_to_copy' => [],		// An array of strings that qualify an option name to be copied. Use an asterisk to enable preg mode.
		], (array) $options );

		$options = (object) $options;

		$this->debug( 'The copy options are: %s', $options );

		// Get all of the options using an SQL query since WP doesn't have a function to return them all.
		$query = sprintf( "SELECT `option_name`, `option_value` FROM `%s`", $wpdb->options );
		$all_options = $wpdb->get_results( $query );

		$options_to_copy = ThreeWP_Broadcast()->collection();
		$linked_posts_bcd = ThreeWP_Broadcast()->collection();

		// Decide which options to copy.
		foreach( $all_options as $source_option )
		{
			$copy = false;

			$this->debug( 'Judging %s', $source_option->option_name );

			foreach( $options->options_to_copy as $search )
			{
				// Is this a regexp?
				if ( strpos( $search, '*' ) !== false )
				{
					$preg = str_replace( '*', '.*', $search );
					$preg = sprintf( '/%s/', $preg );
					preg_match( $preg, $source_option->option_name, $matches );
					if ( ( count( $matches ) == 1 ) && $matches[ 0 ] == $source_option->option_name )
					{
						$copy = true;
						break;
					}
				}
				else
				{
					// Do a normal strpos.
					if ( strpos( $source_option->option_name, $search ) !== false )
					{
						$copy = true;
						break;
					}
				}
			}

			if ( $copy )
			{
				$this->debug( 'Copying!' );
				$options_to_copy->append( $source_option );
			}

			// Save the broadcast_data for this post?
			if ( $copy )
			{
				foreach( $options->linked_posts as $search )
				{
					if ( strpos( $source_option->option_name, $search ) !== false )
					{
						$bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $source_option->option_value );
						$linked_posts_bcd->set( $source_option->option_name, $bcd );
					}
				}
			}

		}

		foreach( $options->blogs as $blog_id )
		{
			switch_to_blog( $blog_id );
			$this->debug( 'Switched to blog %s', $blog_id );

			foreach( $options_to_copy as $source_option )
			{
				$value = $source_option->option_value;
				$value = maybe_unserialize( $value );

				// Translate the page id?
				if ( $linked_posts_bcd->has( $source_option->option_name ) )
				{
					$bcd = $linked_posts_bcd->get( $source_option->option_name );
					$value = $bcd->get_linked_post_on_this_blog();
				}

				if ( $options->dry_run )
					$this->debug( 'New value for %s would be %s', $source_option->option_name, $value );
				else
				{
					$this->debug( 'Setting %s to %s', $source_option->option_name, $value );
					update_option( $source_option->option_name, $value );
				}
			}

			restore_current_blog();
		}
	}

	/**
		@brief		Return an array of the options to copy.
		@since		2017-05-01 22:48:56
	**/
	public function get_options_to_copy()
	{
		return [];
	}

	/**
		@brief		Generic copy options menu page.
		@since		2017-05-01 22:46:32
	**/
	public function generic_copy_options_page( $options )
	{
		$form = $this->form();
		$options = (object) $options;
		$r = '';

		$r .= $this->p_(
			// All of the PLUGINNAME settings
			__( 'This tool will copy all of the %s settings from this blog to one or more others.', 'threewp_broadcast' ),
			$options->plugin_name
		);

		$r .= $this->p( __( 'The settings on the child blog will be overwritten.', 'threewp_broadcast' ) );

		$blogs = $this->add_blog_list_input( [
			'description' => __( 'Select one or more blogs to which to copy the settings.', 'threewp_broadcast' ),
			'form' => $form,
			'label' => __( 'Destination blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => true,
		] );

		$dry_run = $form->checkbox( 'dry_run' )
			->checked()
			->description( __( 'Do not copy any settings, just do a dry run. Best used with debug mode.', 'threewp_broadcast' ) )
			->label( __( 'Dry run', 'threewp_broadcast' ) );

		$submit = $form->primary_button( 'submit' )
			// Button to start copying the settings between blogs
			->value( __( 'Copy settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();
			try
			{
				$this->do_copy_options( [
					'dry_run' => $dry_run->is_checked(),
					'options_to_copy' => $this->get_options_to_copy(),
					'blogs' => $blogs->get_post_value(),
				] );

				$r .= $this->info_message_box()->_( __( 'The settings have been copied to the selected blog(s).', 'threewp_broadcast' ) );
			}
			catch ( Exception $e )
			{
				$r .= $this->error_message_box()->_( sprintf(
					__( 'There was a problem copying the settings: %s', 'threewp_broadcast' ),
					$e->getMessage()
				) );
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		return $this->wrap( $r,
			sprintf(
				// Copy PLUGINNAME settings
				__( 'Copy %s settings', 'threewp_broadcast' ),
				$options->plugin_name
			)
		);
	}
}
