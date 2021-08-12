<?php

namespace threewp_broadcast\premium_pack\create;

/**
	@brief		Handle the
	@since		2020-07-12 21:36:02
**/
class Create_Shortcode
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	use \threewp_broadcast\premium_pack\classes\database_trait;

	/**
		@brief		Constructor.
		@since		2017-01-11 23:16:42
	**/
	public function _construct()
	{
		parent::_construct();
		$this->add_filter( 'threewp_broadcast_parse_content' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Inherited
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Copy the item (shortcode ID) from the parent blog to this blog.
		@details	The default is to broadcast the post ID, but is overridable in case the item is not a post.
		@since		2017-01-11 23:20:49
	**/
	public function copy_item( $bcd, $item )
	{
		global $wpdb;

		switch_to_blog( $bcd->parent_blog_id );

		$source_prefix = $wpdb->prefix;

		$table = Create::table_name( 'mv_creations' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%s'", $table, $item->id );
		$creation = $wpdb->get_row( $query );
		$old_id = $creation->object_id;

		restore_current_blog();

		// Broadcast the post / object in question.
		//$new_object_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
		switch_to_blog( $bcd->parent_blog_id );
		$this->new_object_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $old_id, [ $bcd->current_child_blog_id ] );
		restore_current_blog();
		$new_object_id = $this->new_object_bcd->new_post->ID;

		// And now fetch the new ID from the broadcasted post.
		$table = Create::table_name( 'mv_creations' );
		$query = sprintf( "SELECT * FROM `%s` WHERE `object_id` = '%s'", $table, $new_object_id );
		$creation = $wpdb->get_row( $query );
		$new_creation_id = $creation->id;

		return $new_creation_id;
	}

	/**
		@brief		Return the shortcode attribute that stores the item ID.
		@since		2017-01-11 23:04:21
	**/
	public function get_shortcode_id_attribute()
	{
		return 'key';
	}

	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'mv_create';
	}

	/**
		@brief		Replace the thumbnail.
		@details	This requires that we save the BCD of when the creation's post is broadcasted, since the current BCD won't know about the thumbnail.
		@see		$this->new_object_bcd.
		@since		2020-07-13 15:11:07
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		parent::threewp_broadcast_parse_content( $action );
		$bcd = $action->broadcasting_data;		// Convenience.

		$preparse_key = $this->get_preparse_key();

		if ( ! isset( $bcd->$preparse_key ) )
			return;

		// Are there any shortcodes available for this action ID?
		$shortcodes = $bcd->$preparse_key->get( $action->id );

		if ( ! $shortcodes )
			return;

		$action->content = ThreeWP_Broadcast()->update_attachment_ids( $this->new_object_bcd, $action->content );
	}
}
