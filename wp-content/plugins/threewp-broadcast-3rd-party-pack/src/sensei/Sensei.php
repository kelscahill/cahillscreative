<?php

namespace threewp_broadcast\premium_pack\sensei;

/**
	@brief			Adds support for the <a href="https://woocommerce.com/products/sensei/">Sensei</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-10-18 11:34:18
**/
class Sensei
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		add_filter( 'manage_edit-course_columns', [ ThreeWP_Broadcast(), 'manage_posts_columns' ] );
		add_filter( 'manage_edit-lesson_columns', [ ThreeWP_Broadcast(), 'manage_posts_columns' ] );
		add_filter( 'manage_edit-question_columns', [ ThreeWP_Broadcast(), 'manage_posts_columns' ] );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-10-18 11:35:18
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->maybe_restore_course( $bcd );
		$this->maybe_restore_lesson( $bcd );
		$this->maybe_restore_question( $bcd );
		$this->maybe_restore_quiz( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-10-18 11:35:34
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$bcd->sensei = ThreeWP_Broadcast()->collection();

		$this->maybe_save_question( $bcd );
		$this->maybe_save_quiz( $bcd );
	}

	/**
		@brief		Add our types.
		@since		2016-07-27 20:15:57
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'course', 'lesson', 'question', 'quiz' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		maybe_save_question
		@since		2017-10-18 11:36:08
	**/
	public function maybe_save_question( $bcd )
	{
		if ( ! in_array( $bcd->post->post_type, [ 'multiple_question', 'question' ] ) )
			return;

		$key = 'category';
		$old_value = $bcd->custom_fields()->get_single( $key );
		if ( $old_value > 0 )
			// We will be needing the equivalent category later.
			$bcd->taxonomies()->also_sync_taxonomy( [
				'post_type' => 'question',
				'taxonomy' => 'question-category',
			] );

		$key = '_question_media';
		$id = $bcd->custom_fields()->get_single( $key );
		if ( $id > 0 )
		{
			if ( ! $bcd->try_add_attachment( $id ) )
				return;
			$this->debug( 'Found question media %d.', $id );
			$bcd->sensei->collection( 'questions' )
				->collection( 'media' )
				->collection( $bcd->post->ID )
				->set( $key, $id );
		}
	}

	/**
		@brief		Maybe save the quiz.
		@since		2017-10-19 14:57:51
	**/
	public function maybe_save_quiz( $bcd )
	{
		if ( $bcd->post->post_type != 'quiz' )
			return;

		// Get the questions.
		global $wpdb;
		$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `meta_key` = '_quiz_id' AND `meta_value` = '%d'",
			$wpdb->postmeta,
			$bcd->post->ID
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		$this->debug( 'Saving quiz questions: %s', $results );

		foreach( $results as $result )
			$bcd->sensei->collection( 'questions' )
				->collection( 'question' )
				->append( $result->post_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Restore the course data.
		@since		2017-10-18 11:36:08
	**/
	public function maybe_restore_course( $bcd )
	{
		if ( $bcd->post->post_type != 'course' )
			return;

		$this->equivalent_post_id( $bcd, '_course_prerequisite' );

		$key = '_lesson_order';
		$old_lesson_order = $bcd->custom_fields()->get_single( $key );
		$old_lesson_order = explode( ',', $old_lesson_order );
		$new_lesson_order = [];
		foreach( $old_lesson_order as $old_lesson_id )
		{
			if ( $old_lesson_id < 1 )
			{
				$new_lesson_order []= 0;
				continue;
			}

			$this->debug( 'Getting new lesson ID for %d', $old_lesson_id );
			$new_lesson_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_lesson_id, get_current_blog_id() );
			$new_lesson_order []= $new_lesson_id;
		}
		$new_lesson_order = implode( ',', $new_lesson_order );
		$this->debug( 'Setting new lesson order: %s', $new_lesson_order );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $new_lesson_order );
	}

	/**
		@brief		maybe_restore_lesson
		@since		2017-10-18 12:41:40
	**/
	public function maybe_restore_lesson( $bcd )
	{
		if ( $bcd->post->post_type != 'lesson' )
			return;

		$key = '_lesson_course';
		$new_course_id = $this->equivalent_post_id( $bcd, $key );

		// Modify the order.
		$old_course_id = $bcd->custom_fields()->get_single( $key );
		$key = '_order_';
		$old_order = $bcd->custom_fields()->get_single( $key . $old_course_id );
		$new_key = $key . $new_course_id;
		$this->debug( 'Setting new order %s to %s', $new_key, $old_order );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $new_key, $old_order );

		$this->equivalent_post_id( $bcd, '_lesson_prerequisite' );

		// Force a rebroadcast of the whole quiz in order to get the questions rebroadcast.
		$key = '_lesson_quiz';
		$old_quiz_id = $bcd->custom_fields()->get_single( $key );
		switch_to_blog( $bcd->parent_blog_id );
		$this->debug( 'Rebroadcasting quiz %d', $old_quiz_id );
		ThreeWP_Broadcast()->api()->broadcast_children( $old_quiz_id, [ $bcd->current_child_blog_id ] );
		restore_current_blog();
		// And now set the lesson quiz cf.
		$this->equivalent_post_id( $bcd, $key );
	}

	/**
		@brief		maybe_restore_question
		@since		2017-10-18 13:04:42
	**/
	public function maybe_restore_question( $bcd )
	{
		if ( ! in_array( $bcd->post->post_type, [ 'multiple_question', 'question' ] ) )
			return;

		// Translate the quiz ID.
		$key = '_quiz_id';
		$old_quiz_id = $bcd->custom_fields()->get_single( $key );
		$new_quiz_id = $this->equivalent_post_id( $bcd, $key );

		// Update the quiz ID meta.
		$this->debug( 'Setting new quiz ID to %d.', $new_quiz_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $new_quiz_id );

		// Use that quiz ID to replace the order.
		$key = '_quiz_question_order';
		$old_order = $bcd->custom_fields()->get_single( $key . $old_quiz_id );
		$new_order = str_replace( $old_quiz_id, $new_quiz_id, $old_order );
		$new_key = $key . $new_quiz_id;
		$this->debug( 'Setting new question order %s to %s', $new_key, $new_order );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $new_key, $new_order );

		$key = 'category';
		$old_value = $bcd->custom_fields()->get_single( $key );
		if ( $old_value > 0 )
		{
			$new_value = $bcd->terms()->get( $old_value );
			$this->debug( 'Setting new question category: %s', $new_value );
			$bcd->custom_fields()->child_fields()
				->update_meta( $key, $new_value );
		}

		// Translate the media ID, if any.
		$key = '_question_media';
		$old_id = $bcd->sensei->collection( 'questions' )
				->collection( 'media' )
				->collection( $bcd->post->ID )
				->get( $key );
		if ( ! $old_id )
			return;
		$new_id = $bcd->copied_attachments()->get( $old_id );
		$this->debug( 'Replacing question media %d with %d.', $old_id, $new_id );
		$bcd->custom_fields()
			->child_fields()
			->update_meta( $key, $new_id );
	}

	/**
		@brief		Maybe restore the quiz data.
		@since		2017-10-18 12:58:00
	**/
	public function maybe_restore_quiz( $bcd )
	{
		if ( $bcd->post->post_type != 'quiz' )
			return;

		$this->equivalent_post_id( $bcd, '_quiz_lesson' );

		// Broadcast the questions.
		foreach( $bcd->sensei->collection( 'questions' )
				->collection( 'question' ) as $old_question_id )
		{
			$this->debug( 'Broadcasting or updating question %d.', $old_question_id );
			$new_question_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_question_id, get_current_blog_id() );
			$this->debug( 'New question ID is %d.', $new_question_id );
		}

		$key = '_question_order';
		$new_question_order = [];
		$old_order = $bcd->custom_fields()->get_single( $key );
		$old_order = maybe_unserialize( $old_order );
		if ( ! is_array( $old_order ) )
			$old_order = [];
		$this->debug( 'Old question order: %s', $old_order );
		foreach( $old_order as $question_id )
		{
			$this->debug( 'Getting new question ID for %d', $question_id );
			$new_question_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $question_id, get_current_blog_id() );
			$new_question_order []= $new_question_id;
		}
		$this->debug( 'Setting new question order: %s', $new_question_order );
		$bcd->custom_fields()->child_fields()
			->update_meta( $key, $new_question_order );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Update the child meta key to the equivalent post ID on this blog.
		@since		2017-10-18 12:42:11
	**/
	public function equivalent_post_id( $bcd, $key )
	{
		$cf = $bcd->custom_fields();
		$chf = $cf->child_fields();

		$old_post_id = $cf->get_single( $key );

		// No value? Don't do anything.
		if ( $old_post_id < 1 )
			return;

		$this->debug( 'Getting equivalent of %s: %d', $key, $old_post_id );
		$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
		$this->debug( 'Setting new %s from %d to %d', $key, $old_post_id, $new_post_id );
		$chf->update_meta( $key, $new_post_id );

		return $new_post_id;
	}
}
