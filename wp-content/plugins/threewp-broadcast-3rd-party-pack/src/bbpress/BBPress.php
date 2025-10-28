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
		$this->add_action( 'bbp_add_user_subscription', 10, 3 );
		$this->add_action( 'bbp_remove_user_subscription', 10, 3 );
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
		@brief		A subscription was added!
		@since		2022-11-15 07:10:31
	**/
	public function bbp_add_user_subscription( $user, $object_id, $object_type )
	{
		$this->maybe_sync_subscriptions( 'add', $user, $object_id, $object_type );
	}

	/**
		@brief		A subscription was removed!
		@since		2022-11-15 07:10:31
	**/
	public function bbp_remove_user_subscription( $user, $object_id, $object_type )
	{
		$this->maybe_sync_subscriptions( 'remove', $user, $object_id, $object_type );
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

		$reply_to_meta = $bcd->custom_fields()->get_single( '_bbp_reply_to' );
		$this->debug( 'The reply_to ID for this reply is: %s', $reply_to_meta );
		$reply_to_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $reply_to_meta );
		$bcd->bbpress->set( 'reply_to_bcd', $reply_to_bcd );
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

		$linked_child = $bcd->bbpress->get( 'reply_to_bcd' )->get_linked_child_on_this_blog();
		$this->debug( 'Setting reply_to of this reply to %s', $linked_child );
		$cf->update_meta( '_bbp_reply_to', intval( $linked_child ) );
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

	// -------------------------------------------------------------------------------------------------------------------
	// --- MISC
	// -------------------------------------------------------------------------------------------------------------------

	/**
		@brief		Maybe sync the subscriptions of this object.
		@since		2022-11-15 07:12:29
	**/
	public function maybe_sync_subscriptions( $subscription_direction, $user, $object_id, $object_type )
	{
		$do_it = true;

		// Only sync for posts.
		if ( $object_type != 'post' )
			$do_it = false;

		$do_it = apply_filters( 'broadcast_bbpress_maybe_sync_subscriptions', $do_it, $subscription_direction, $user, $object_id, $object_type );
		$this->debug( 'broadcast_bbpress_maybe_sync_subscriptions: %s -- %s -- %s -- %s -- %s', $do_it, $subscription_direction, $user, $object_id, $object_type );
		if ( ! $do_it )
			return;

		$this->sync_subscriptions( $object_id );
	}

	/**
		@brief		Actually sync the subscriptions of this post.
		@since		2022-11-15 07:14:21
	**/
	public function sync_subscriptions( $post_id )
	{
		$subscriptions = get_post_meta( $post_id, '_bbp_subscription' );

		$this->debug( 'Syncing subscriptions of %s: %s', $post_id, $subscriptions );

		$action = new \threewp_broadcast\actions\each_linked_post();
		$action->post_id = $post_id;
		$action->add_callback( function( $o ) use ( $subscriptions )
		{
			ThreeWP_Broadcast()->debug( 'Setting subscriptions of %s', $o->post_id );

			// Cleanup first.
			$child_subscriptions = get_post_meta( $o->post_id, '_bbp_subscription' );
			ThreeWP_Broadcast()->debug( 'Deleting %s subscriptions.', $child_subscriptions );
			foreach( $child_subscriptions as $child_subscription )
				delete_post_meta( $o->post_id, '_bbp_subscription', $child_subscription );

			foreach( $subscriptions as $user_id )
				add_post_meta( $o->post_id, '_bbp_subscription', $user_id );
		} );
		$action->execute();
	}
}
