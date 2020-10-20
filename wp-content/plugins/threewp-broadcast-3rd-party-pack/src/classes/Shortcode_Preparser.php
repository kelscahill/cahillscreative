<?php

namespace threewp_broadcast\premium_pack\classes;

use \Exception;

/**
	@brief		Common class for all add-ons that preparse shortcodes.
	@details	We refer to the complete shortcodes as "items" so as not to confuse ourselves with the shortcode key / name.
	@since		2017-01-11 23:15:55
**/
class Shortcode_Preparser
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Constructor.
		@since		2017-01-11 23:16:42
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_filter( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
	}

	/**
		@brief		Allow subclasses to add their own post types.
		@since		2017-01-16 15:59:05
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
	}

	/**
		@brief		Restore any shortcodes.
		@since		2017-01-11 22:51:31
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		$preparse_key = $this->get_preparse_key();

		if ( ! isset( $bcd->$preparse_key ) )
			return;

		// Are there any shortcodes available for this action ID?
		$shortcodes = $bcd->$preparse_key->get( $action->id );

		if ( ! $shortcodes )
			return;

		foreach( $shortcodes as $item )
		{
			$this->debug( 'Handling shortcode <em>%s</em> in content <em>%s</em>', $item->shortcode, $action->id );

			// Broadcast the item.
			try
			{
				$new_item_id = $this->copy_item( $bcd, $item );
				if ( ! isset( $item->new_shortcode ) )
				{
					// use the xxx as a placeholder so that the number doesn't confuse the backlinks.
					$new_shortcode = preg_replace( '/=([\'"]?)' . $item->id . '([\'"]?)/', '=\1xxxxx\2', $item->shortcode );
					$new_shortcode = str_replace( 'xxxxx', $new_item_id, $new_shortcode );
				}
				else
				{
					// Use the new shortcode provided by the subclass.
					$new_shortcode = $item->new_shortcode;
					unset( $item->new_shortcode );
				}
				$this->debug( 'New item ID is %s. New shortcode is: %s', $new_item_id, $new_shortcode );
				$action->content = str_replace( $item->shortcode, $new_shortcode, $action->content );
			}
			catch ( Exception $e )
			{
				$this->debug( 'Error: %s', $e->getMessage() );
			}
		}
	}

	/**
		@brief		Handle shortcodes in content.
		@since		2017-01-11 22:51:31
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;

		$preparse_key = $this->get_preparse_key();

		if ( ! isset( $bcd->$preparse_key ) )
			$bcd->$preparse_key = ThreeWP_Broadcast()->collection();

		$items = ThreeWP_Broadcast()->collection();

		$shortcode_name = $this->get_shortcode_name();
		$matches = ThreeWP_Broadcast()->find_shortcodes( $content, [ $shortcode_name ] );
		if ( count( $matches[ 0 ] ) < 1 )
			return;
		$this->debug( '%s shortcodes found in content <em>%s</em>', count( $matches[ 0 ] ), $action->id );

		$id_attribute = $this->get_shortcode_id_attribute();

		foreach( $matches[ 0 ] as $index => $shortcode )
		{
			try
			{
				$item = (object)[];
				$item->attributes = shortcode_parse_atts( $matches[ 3 ][ $index ] );;
				$item->shortcode = $shortcode;
				$this->finalize_item( $item );
				// If available, save the broadcast_data of this item.
				if ( isset( $item->id ) )
					$item->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $item->id );

				$this->remember_item( $bcd, $item );
				$this->debug( 'Saving shortcode <em>%s</em> as <em>%s</em>', $shortcode, $item );
				$items->collection( 'shortcodes' )->set( $shortcode, $item );
			}
			catch ( Exception $e )
			{
				$this->debug( 'Unable to save shortcode %s: %s', $shortcode, $e->getMessage() );
			}
		}

		$bcd->$preparse_key->set( $action->id, $items->collection( 'shortcodes' ) );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		$this->prepare_to_copy( $bcd, $item );
		if ( ! isset( $item->id ) )
			throw new Exception( $this->debug( 'Unable to copy the item since it has no ID.' ) );

		// Allow plugins to override the forced broadcasting of new items, or to use get_or_broadcast and then not broadcast anything.
		$broadcast_children = apply_filters( 'shortcode_preparser_broadcast_children', true, $bcd, $item );
		if ( $broadcast_children )
		{
			switch_to_blog( $bcd->parent_blog_id );
			$item_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $item->id, [ $bcd->current_child_blog_id ] );
			$new_id = $item_bcd->new_post( 'ID' );
			restore_current_blog();
		}
		else
		{
			$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $item->id, get_current_blog_id() );
		}
		return $new_id;
	}

	/**
		@brief		Finalize the item before it is saved.
		@details	Currently just checks that the ID attribute exists.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		$id_attribute = $this->get_shortcode_id_attribute();

		// No ID attribute? No problem.
		if ( ! $id_attribute )
			return;

		if ( ! isset( $item->attributes[ $id_attribute ] ) )
			throw new Exception( sprintf( 'Shortcode %s has no ID. Ignoring.', $item->shortcode ) );

		$item->id = $item->attributes[ $id_attribute ];
	}

	/**
		@brief		Returns the key in the BCD in which we store our data.
		@since		2017-01-11 23:17:37
	**/
	public function get_preparse_key()
	{
		$name = $this->get_shortcode_name();
		// Contact Form 7 has a shortcode that uses dashes. But PHP variables can't be called that.
		$name = str_replace( '-', '_', $name );
		return $name . '_preparse';
	}

	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'id';
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'override_me';
	}

	/**
		@brief		Allow subclasses to handle any special preparation of the item.
		@since		2017-03-07 16:38:14
	**/
	public function prepare_to_copy( $bcd, $item )
	{
	}

	/**
		@brief		Allow subclases to handle the newly found item from the shortcode.
		@details	If you don't want to save this item, perhaps because the post isn't found, then throw an exception.
		@since		2017-01-12 12:25:55
	**/
	public function remember_item( $bcd, $item )
	{
	}
}
