<?php

namespace threewp_broadcast\premium_pack\bbpress;

/**
	@brief			Adds support for <a href="https://wordpress.org/plugins/bbpress/">BBPress</a> forums, topics and replies.
	@plugin_group	3rd party compatability
	@since			2015-07-08 16:12:07
**/
class BBPress
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		Is BBPress installed?
		@since		2015-10-10 20:42:07
	**/
	public function has_bbpress()
	{
		return ( class_exists( '\\bbPress' ) );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2015-10-11 08:13:04
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->bbpress ) )
			return;

		$this->restore_reply( $bcd );
		$this->restore_topic( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2015-10-10 20:43:08
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_bbpress() )
			return;

		$bcd = $action->broadcasting_data;
		$bcd->bbpress = ThreeWP_Broadcast()->collection();

		if ( in_array( $bcd->post->post_type, [ 'forum', 'reply', 'topic' ] ) )
			$this->save_parent( $bcd );

		$this->save_reply( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2015-10-10 20:50:10
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->bbpress ) )
			return;

		$this->restore_parent( $bcd );
	}

	/**
		@brief		Add our supported post types.
		@since		2015-10-10 20:46:59
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'forum', 'reply', 'topic' );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- SAVE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Handle parents.
		@since		2015-10-10 20:48:23
	**/
	public function save_parent( $bcd )
	{
		if ( $bcd->post->post_parent < 1 )
			return $this->debug( 'Post does not have a parent and is therefore not interesting.' );

		// Retrieve the bcd of the parent, if any.
		$bcd->bbpress->post_parent = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $bcd->post->post_parent );
		$this->debug( 'Saving parent post broadcast data of post %s', $bcd->post->post_parent );
	}

	/**
		@brief		Save the reply data.
		@since		2015-10-11 09:38:36
	**/
	public function save_reply( $bcd )
	{
		if ( $bcd->post->post_type != 'reply' )
			return;

		$forum_id_meta = $bcd->custom_fields()->get_single( '_bbp_forum_id' );
		$this->debug( 'The forum ID for this reply is: %s', $forum_id_meta );
		$forum_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $forum_id_meta );
		$bcd->bbpress->set( 'forum_bcd', $forum_bcd );
	}

	// -------------------------------------------------------------------------------------------------------------------
	// --- RESTORE
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Restore the parent.
		@since		2015-10-10 20:49:20
	**/
	public function restore_parent( $bcd )
	{
		if ( ! isset( $bcd->bbpress->post_parent ) )
			return;

		$linked_child = $bcd->bbpress->post_parent->get_linked_child_on_this_blog();

		if ( ! $linked_child )
			return $this->debug( 'Parent has no linked child on this blog. Will not set the parent.' );

		$bcd->modified_post->post_parent = $linked_child;
		$this->debug( 'Setting post parent to %s', $linked_child );
	}

	/**
		@brief		Restore the reply data.
		@since		2015-10-11 09:43:07
	**/
	public function restore_reply( $bcd )
	{
		if ( ! $bcd->bbpress->has( 'forum_bcd' ) )
			return;

		$cf = $bcd->custom_fields()
			->child_fields();

		// The topic ID is the parent.
		$linked_child = $bcd->bbpress->post_parent->get_linked_child_on_this_blog();
		$this->debug( 'Setting topic of this reply to %s', $linked_child );
		$cf->update_meta( '_bbp_topic_id', intval( $linked_child ) );

		$linked_child = $bcd->bbpress->get( 'forum_bcd' )->get_linked_child_on_this_blog();
		$this->debug( 'Setting forum of this reply to %s', $linked_child );
		$cf->update_meta( '_bbp_forum_id', intval( $linked_child ) );
	}

	/**
		@brief		Restore the topic IDs.
		@since		2015-10-11 08:15:34
	**/
	public function restore_topic( $bcd )
	{
		if ( $bcd->post->post_type != 'topic' )
			return $this->debug( 'Not a topic.' );

		// Time to update some custom fields.
		$cf = $bcd->custom_fields()
			->child_fields();

		$cf->update_meta( '_bbp_topic_id', $bcd->new_post( 'ID' ) );
		$cf->update_meta( '_bbp_last_active_id', $bcd->new_post( 'ID' ) );

		// Update the parent forum, if any.
		if ( isset( $bcd->bbpress->post_parent ) )
			$linked_child = $bcd->bbpress->post_parent->get_linked_child_on_this_blog();
		$cf->update_meta( '_bbp_forum_id', intval( $linked_child ) );
	}
}
