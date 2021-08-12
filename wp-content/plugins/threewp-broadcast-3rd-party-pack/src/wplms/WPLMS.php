<?php
namespace threewp_broadcast\premium_pack\wplms;

/**
	@brief				Adds support for Vibethemes' <a href="https://themeforest.net/item/wplms-learning-management-system/6780226">WPLMS theme</a>.
	@plugin_group		3rd party compatability
**/
class WPLMS
extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		$this->maybe_restore_course( $bcd );
		$this->maybe_restore_quiz( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$this->prepare_bcd( $bcd );

		$this->maybe_save_course( $bcd );
	}

	/**
		@brief		Append our post types.
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( 'certificate' );
		$action->add_type( 'course' );
		$action->add_type( 'question' );
		$action->add_type( 'quiz' );
		$action->add_type( 'unit' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save course data.
	**/
	public function maybe_save_course( $bcd )
	{
		if ( $bcd->post->post_type != 'course' )
			return;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Handle restoring of course. Of course.
	**/
	public function maybe_restore_course( $bcd )
	{
		if ( $bcd->post->post_type != 'course' )
			return;

		$this->broadcast_multiple_meta_key_values( $bcd, 'vibe_course_curriculum' );
		$this->broadcast_single_meta_key_value( $bcd, 'vibe_certificate_template' );
		$this->broadcast_single_meta_key_value( $bcd, 'vibe_product' );
	}

	/**
		@brief		Restore quiz data.
	**/
	public function maybe_restore_quiz( $bcd )
	{
		if ( $bcd->post->post_type != 'quiz' )
			return;

		$meta_values = $bcd->custom_fields()->get_single( 'vibe_quiz_questions' );
		$meta_values = maybe_unserialize( $meta_values );
		if ( is_array( $meta_values ) )
			if ( isset( $meta_values[ 'ques' ] ) )
			{
				$questions = $meta_values[ 'ques' ];
				$this->debug( 'Quiz questions: %s', $questions );
				$new_questions = [];
				foreach( $questions as $question_id )
				{
					$this->debug( 'Broadcasting quiz question %d', $question_id );
					switch_to_blog( $bcd->parent_blog_id );
					$post = get_post( $question_id );

					$item_bcd = false;

					if ( ! $post )
						$this->debug( 'Invalid question ID %d', $question_id );
					else
						$item_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $question_id, [ $bcd->current_child_blog_id ] );
					restore_current_blog();

					if ( $item_bcd )
					{
						$new_item_id = $item_bcd->new_post( 'ID' );
						$this->debug( 'New quiz question %d', $new_item_id );
						$new_questions []= $new_item_id;
					}
				}
			}
			$meta_values[ 'ques' ] = $new_questions;
			$bcd->custom_fields()->child_fields()->update_meta( 'vibe_quiz_questions', $meta_values );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief
	**/
	public function broadcast_multiple_meta_key_values( $bcd, $meta_key )
	{
		$meta_values = $bcd->custom_fields()->get_single( $meta_key );
		$meta_values = maybe_unserialize( $meta_values );
		if ( is_array( $meta_values ) )
		{
			$this->debug( 'Handling multiple meta key %s values %s', $meta_key, $meta_values );
			$new_meta_values = [];
			foreach( $meta_values as $item )
			{
				// Is this an ID or a string? Since everything is a string, convert to an int and check the difference.
				$meta_value = intval( $item );
				if ( (string) $meta_value === (string ) $item )
				{
					$this->debug( 'Broadcasting multiple meta key value %d', $meta_value );
					switch_to_blog( $bcd->parent_blog_id );
					// We have to check for post validity since LMS allows non-existent questions in the array.
					$post = get_post( $meta_value );

					$item_bcd = false;

					if ( ! $post )
						$this->debug( 'Invalid meta key value post %d', $meta_value );
					else
						$item_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $meta_value, [ $bcd->current_child_blog_id ] );
					restore_current_blog();

					if ( $item_bcd )
					{
						$new_item_id = $item_bcd->new_post( 'ID' );
						$this->debug( 'New multiple meta key value %d', $new_item_id );
						$new_meta_values []= $new_item_id;
					}
				}
				else
				{
					// It's a string. Add it as it.
					$new_meta_values []= $item;
				}
			}
			$this->debug( 'New %s: %s', $meta_key, $new_meta_values );
			$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_meta_values );
		}
	}

	/**
		@brief
	**/
	public function broadcast_single_meta_key_value( $bcd, $meta_key )
	{
		$meta_value = $bcd->custom_fields()->get_single( $meta_key );
		if ( $meta_value > 0 )
		{
			$this->debug( 'Broadcasting %s %d', $meta_key, $meta_value );
			switch_to_blog( $bcd->parent_blog_id );
			$meta_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $meta_value, [ $bcd->current_child_blog_id ] );
			restore_current_blog();
			$new_product_id = $meta_bcd->new_post( 'ID' );
			$this->debug( 'Setting %s to %d', $meta_key, $new_product_id );
			$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_product_id );
		}
	}

	/**
		@brief		Common method for preparing the bcd.
		@since		2017-07-06 22:07:49
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->wplms ) )
			$bcd->wplms= ThreeWP_Broadcast()->collection();
	}

}
