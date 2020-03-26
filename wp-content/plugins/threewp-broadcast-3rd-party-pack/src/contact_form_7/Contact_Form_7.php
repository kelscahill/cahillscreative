<?php

namespace threewp_broadcast\premium_pack\contact_form_7;

use Exception;

/**
	@brief			Adds support for <a href="https://wordpress.org/plugins/contact-form-7/">Takayuki Miyoshi's Contact Form 7</a> plugin.
	@plugin_group	3rd party compatability
	@since			2016-07-26 18:46:37
**/
class Contact_Form_7
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Default values for form search.
		@since		2017-03-07 16:51:51
	**/
	public static $get_posts_defaults = [
		'post_type' => 'wpcf7_contact_form',
	];

	/**
		@brief		Check for an ID or a title attribute.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		// Did we find either the id or title? Convenience variable.
		$found = false;

		// We need an ID or a title. At least one.
		foreach( [ 'id', 'title' ] as $key )
			if ( isset( $item->attributes[ $key ] ) )
			{
				$found = true;
				$item->$key = $item->attributes[ $key ];
			}

		if ( ! $found )
			throw new Exception( sprintf( 'No ID or title found in %s', $item->shortcode ) );

		// Did we find just the title? Extract the ID.
		if ( ! isset( $item->id ) )
		{
			$a = static::$get_posts_defaults;
			$a[ 'title' ] = $item->title;
			$posts = get_posts( $a );
			$this->debug( 'Item is missing ID. We found the following candidates: %s', $posts );

			// There should be exactly one form like this.
			if ( count( $posts ) > 0 )
			{
				$post = reset( $posts );
				$item->id = $post->ID;
				$this->debug( 'Extracted ID %s for form <em>%s</em>.', $item->id, $item->title );
			}
		}

		// No title?
		if ( ! isset( $item->title ) )
		{
			$post = get_post( $item->id );
			$item->title = $post->post_title;
			$this->debug( 'Extracted form title %s from form %s.', $item->title, $item->id );
		}
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'contact-form-7';
	}

	/**
		@brief		If a title is available, try to create a nice link between the forms.
		@since		2017-03-07 16:30:37
	**/
	public function prepare_to_copy( $bcd, $item )
	{
		if ( isset( $item->broadcast_data ) )
			// Already linked? Great.
			if ( $item->broadcast_data->get_linked_post_on_this_blog() )
				return;

		// Try to find a post with this title.
		$a = static::$get_posts_defaults;
		$a[ 'title' ] = $item->title;
		$posts = get_posts( $a );
		if ( count( $posts ) < 1 )
			return $this->debug( 'Unable to find any forms with this title. Going to broadcast the form as a new one.' );

		// There is an existing post. Link the parent and child together, so that the child can be updated.

		$post = reset( $posts );

		// Link the parent to this post.
		$item->broadcast_data->add_linked_child( get_current_blog_id(), $post->ID );
		ThreeWP_Broadcast()->set_post_broadcast_data( $bcd->parent_blog_id, $item->id, $item->broadcast_data );
		$this->debug( 'Parent form has been linked to this child: %s', $item->broadcast_data );

		// And now link the child to the parent.
		$child_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $post->ID );
		$child_bcd->set_linked_parent( $bcd->parent_blog_id, $item->id );
		ThreeWP_Broadcast()->set_post_broadcast_data( get_current_blog_id(), $post->ID, $child_bcd );
		$this->debug( 'Child form has been linked to the parent: %s', $child_bcd );
	}

	/**
		@brief		Add the post type, for manual broadcast.
		@since		2016-07-26 19:07:17
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'wpcf7-new' );
	}
}
