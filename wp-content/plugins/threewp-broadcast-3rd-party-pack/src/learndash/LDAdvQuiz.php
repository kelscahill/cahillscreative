<?php

namespace threewp_broadcast\premium_pack\learndash;

/**
	@brief		Quiz shortcodes use IDs that are not normal post IDs.
	@since		2020-02-07 21:02:54
**/
class LDAdvQuiz
	extends \threewp_broadcast\premium_pack\classes\Shortcode_Preparser
{
	/**
		@brief		Return the name of the shortcode we are looking for.
		@since		2017-01-11 23:03:36
	**/
	public function get_shortcode_name()
	{
		return 'LDAdvQuiz';
	}

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

		switch_to_blog( $bcd->parent_blog_id );
		$item_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $item->id, [ $bcd->current_child_blog_id ] );
		restore_current_blog();

		$new_id = $item_bcd->new_post( 'ID' );
		$quiz_pro_id = get_post_meta( $new_id, 'quiz_pro_id', true );

		return $quiz_pro_id;
	}

	/**
		@brief		Finalize the item before it is saved.
		@details	Currently just checks that the ID attribute exists.
		@since		2017-03-07 16:06:38
	**/
	public function finalize_item( $item )
	{
		$item->id = $item->attributes[ 0 ];
		$item->id = learndash_get_quiz_id_by_pro_quiz_id( $item->id );
		$item->post_id = $post_id;
	}
}
