<?php
namespace threewp_broadcast\premium_pack\learndash;

/**
	@brief			Adds support for the <a href="https://www.learndash.com/">LearnDash LMS</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-02-26 15:44:07
**/
class LearnDash
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\database_trait;
	use \threewp_broadcast\premium_pack\classes\files_trait;

	/**
		@brief		When broadcasting courses, keep the existing course access list.
		@since		2019-02-09 19:35:23
	**/
	public static $keep_sfwd_courses_course_access_list = true;

	/**
		@brief		The LDAdvQuiz instance
		@since		2024-06-05 21:15:40
	**/
	public $LDAdvQuiz;

	/**
		@brief		The LDAdvQuiz_toplist instance.
		@since		2024-06-05 21:15:50
	**/
	public $LDAdvQuiz_toplist;

	/**
		@brief		The Quiz handling class.
		@since		2017-06-29 11:22:44
	**/
	public $quiz;

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_after_update_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_filter( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_menu' );
		$this->add_action( 'threewp_broadcast_parse_content' );
		$this->add_action( 'threewp_broadcast_preparse_content' );
		$this->quiz = new Quiz();
		$this->LDAdvQuiz = new LDAdvQuiz();
		$this->LDAdvQuiz_toplist = new LDAdvQuiz_toplist();
	}

	/**
		@brief		Activate the plugin.
		@since		2017-10-01 18:46:51
	**/
	public function activate()
	{
		// Check that Broadcast's save post is set _after_ LearnDash, which is at 2000.
		$bc = ThreeWP_Broadcast();
		$key = 'save_post_priority';
		$ld_prio = 2000;
		$prio = $bc->get_site_option( $key, 10 );
		if ( $prio > $ld_prio )
			return;
		$prio = $ld_prio * 2;
		$bc->update_site_option( $key, $prio );
	}

	/**
		@brief		admin_questions
		@since		2017-10-01 18:48:19
	**/
	public function admin_questions()
	{
		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		// These are the columns in the questions table we process.
		$columns = [
			'title' => 'Question title',
			'question' => 'Question text',
			'correct_msg' => 'Correct answer message',
			'incorrect_msg' => 'Incorrect answer message',
			'answer_data' => 'The answers',
		];

		$column_options = [];
		foreach( $columns as $column_id => $column_label )
				$column_options [ $column_id ] = $column_label;

		$fs = $form->fieldset( 'fs_text_to_replace' )
			// Fieldset label
			->label( __( 'Text to replace', 'threewp_broadcast' ) );

		$text_to_replace = $fs->text( 'text_to_replace' )
			// Input description
			->description( __( 'This is the text you want replaced in the questions table', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Text to replace', 'threewp_broadcast' ) )
			->placeholder( __( 'Old text', 'threewp_broadcast' ) )
			->required()
			->size( 64 );

		$columns_to_process = $fs->select( 'columns_to_replace' )
			->description( __( 'Select the database table columns in which you wish to search for the text to replace.', 'threewp_broadcast' ) )
			->label( __( 'Columns to process', 'threewp_broadcast' ) )
			->multiple()
			->options( array_flip( $column_options ) )
			->autosize()
			->required();

		$replacement_text = $fs->text( 'replacement_text' )
			// Input description
			->description( __( 'This is the text that will replace the above text wherever it is found', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Replacement text', 'threewp_broadcast' ) )
			->placeholder( __( 'New text', 'threewp_broadcast' ) )
			->size( 64 );

		$fs = $form->fieldset( 'fs_selection' )
			// Fieldset label
			->label( __( 'Question selection', 'threewp_broadcast' ) );

		$fs->markup( 'm_selection' )
			->p( __( 'Do you want only some questions replaced? Fill in the fields below.', 'threewp_broadcast' ) );

		$selection_text = $fs->text( 'selection_text' )
			// Input description
			->description( __( 'This text, if specified, must exist somewhere in the question data for the question to be processed.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Selection text', 'threewp_broadcast' ) )
			->size( 64 );

		$columns_to_search = $fs->select( 'columns_to_search' )
			->description( __( 'Which database columns should contain the above text.', 'threewp_broadcast' ) )
			->label( __( 'Columns to search', 'threewp_broadcast' ) )
			->multiple()
			->options( array_flip( $column_options ) )
			->autosize();

		$fs = $form->fieldset( 'fs_misc' )
			// Fieldset label
			->label( __( 'Other options', 'threewp_broadcast' ) );

		$broadcast_afterwards = $fs->checkbox( 'broadcast_afterwards' )
			// Input description
			->description( __( 'Broadcast the modified questions to existing child blogs.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Broadcast afterwards', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_start' )
			// Fieldset label
			->label( __( 'Search!', 'threewp_broadcast' ) );

		$find_text = $fs->primary_button( 'find' )
			// Button
			->value( __( 'Only do the text search without replacing', 'threewp_broadcast' ) );

		$replace_text = $fs->secondary_button( 'replace' )
			// Button
			->value( __( 'Start search and replace', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			global $wpdb;
			$messages =[];
			$columns_to_process = $columns_to_process->get_post_value();
			$columns_to_search = $columns_to_search->get_post_value();
			$replacement_text = $replacement_text->get_post_value();
			$search_term = "'%" . addslashes( $text ) . "%'";
			$table = $this->get_table( 'wp_pro_quiz_question' );
			$text_for_selection = $selection_text->get_post_value();
			$text_to_replace = $text_to_replace->get_post_value();
			$where = [];

			if ( count( $columns_to_search ) > 0 )
			{
				if ( $text_for_selection != '' )
				{
					$or = [];
					// We only want to replace questions containing this selection text.
					foreach( $columns_to_search as $column_to_search )
						$or []= sprintf( "`%s` LIKE '%%%s%%'", $column_to_search, $text_for_selection );
					$or = '(' . implode( ' OR ', $or ) . ')';
					$where []= $or;
				}
			}

			// Use all columns containing the text to replace.
			$or = [];
			foreach( $columns_to_process as $column_id )
				$or []= sprintf( "`%s` LIKE '%%%s%%'", $column_id, $text_to_replace );
			$or = '(' . implode( ' OR ', $or ) . ')';
			$where []= $or;

			$where = implode( ' AND ', $where );

			$query = sprintf( "SELECT * FROM `%s` WHERE %s",
				$table,
				$where
			);
			$this->debug( $query );

			$results = $wpdb->get_results( $query );

			if ( $find_text->pressed() )
			{
				if ( count( $results ) < 1 )
					$r .= $this->info_message_box()->_( __( 'Could not find any questions containing that text.', 'threewp_broadcast' ) );
				else
				{
					$count = count( $results );
					$messages []= sprintf( __( 'Found %d questions that contain your text. Below is the first search hit:', 'threewp_broadcast' ), $count );

					// Assemble the search result.
					$result = reset( $results );
					foreach( $columns as $column => $ignore )
					{
						if ( strpos( $result->$column, $text_to_replace ) === false )
							continue;
						$messages []= sprintf( 'Found the text in the %s field: <code>%s</code>', $column, htmlspecialchars( $result->$column ) );
					}
					$messages = implode( "\n", $messages );
					$r .= $this->info_message_box()->_( $messages );
				}
			}

			if ( $replace_text->pressed() )
			{
				if ( count( $results ) < 1 )
					$r .= $this->info_message_box()->_( __( 'Could not find any questions containing that text.', 'threewp_broadcast' ) );
				else
				{
					foreach( $results as $result )
					{
						$id = $result->id;
						$update_data = [];
						foreach( $columns_to_process as $column )
						{
							if ( strpos( $result->$column, $text_to_replace ) === false )
								continue;
							if ( $column == 'answer_data' )
							{
								// We have to unserialize in order to update.
								$answer_data = maybe_unserialize( $result->$column );
								foreach( $answer_data as $index => $object )
								{
									if ( is_a( $object, 'WpProQuiz_Model_AnswerTypes' ) )
									{
										$answer = $object->getAnswer();
										$answer = str_replace( $text_to_replace, $replacement_text, $answer );
										$object->setAnswer( $answer );
									}
								}
								$update_data[ $column ] = serialize( $answer_data );
							}
							else
							{
								$update_data[ $column ] = str_replace( $text_to_replace, $replacement_text, $result->$column );
							}
						}
						$message = sprintf( 'Replacing text in %s for question <em>%s</em>.', implode( ", ", array_keys( $update_data ) ), $result->title );
						$this->debug( $message );
						$messages []= $message;
						$wpdb->update( $table, $update_data, [ 'id' => $id ] );

						// Broadcast the question?
						if ( $broadcast_afterwards->is_checked() )
						{
							// The quiz ID links to the post in the postmeta table.
							$query = sprintf( "SELECT `post_id` FROM `%s` WHERE `meta_key` = 'quiz_pro_id' AND `meta_value` = '%d' AND `post_id` > 0",
								$wpdb->postmeta,
								$result->quiz_id
							);
							$post_ids = $wpdb->get_col( $query );

							foreach( $post_ids as $post_id )
							{
								$message = sprintf( 'Broadcasting quiz %d found on post %d.', $result->quiz_id, $post_id );
								$this->debug( $message );
								$messages []= $message;

								ThreeWP_Broadcast()->api()
									->update_children( $post_id, [] );
							}
						}
					}
					$messages = implode( "\n", $messages );
					$r .= $this->info_message_box()->_( $messages );
				}
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Admin tabs.
		@since		2017-10-01 18:47:00
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'questions' )
			->callback_this( 'admin_questions' )
			// Tab heading for modifying Learndash questions.
			->heading( __( 'Question Search & Replace', 'threewp_broadcast' ) )
			// Tab name for modifying Learndash questions.
			->name( __( 'Question S&R', 'threewp_broadcast' ) );

		$tabs->tab( 'course_broadcast' )
			->callback_this( 'course_broadcast' )
			// Tab heading for modifying Learndash questions.
			->heading( __( 'LearnDash course broadcast', 'threewp_broadcast' ) )
			// Tab name for modifying Learndash questions.
			->name( __( 'Course broadcast', 'threewp_broadcast' ) );

		$tabs->tab( 'course_tool' )
			->callback_this( 'course_tool' )
			// Tab heading
			->heading( __( 'LearnDash course tool', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Course tool', 'threewp_broadcast' ) );

		$tabs->tab( 'settings' )
			->callback_this( 'settings' )
			// Tab heading
			->heading( __( 'LearnDash Settings', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Settings', 'threewp_broadcast' ) );

		echo $tabs->render();
	}

	/**
		@brief		threewp_broadcast_broadcasting_after_update_post
		@since		2017-09-15 15:32:32
	**/
	public function threewp_broadcast_broadcasting_after_update_post( $action )
	{
		$bcd = $action->broadcasting_data;

		// We must be Learndashing
		if ( ! isset( $bcd->learndash ) )
			return;

		$this->maybe_prerestore_course( $bcd );
	}

	/**
		@brief		Replace the ingredients and terms with their equivalents.
		@since		2015-04-05 08:10:43
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->maybe_restore_course( $bcd );
		$this->maybe_restore_group( $bcd );
		$this->maybe_restore_lesson( $bcd );
		$this->maybe_restore_notification( $bcd );
		if ( $this->is_26() )
			$this->maybe_restore_quiz( $bcd );
		else
			$this->maybe_restore_quiz_25( $bcd );
		$this->maybe_restore_topic( $bcd );
		$this->maybe_restore_woocommerce( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_modify_post
		@since		2021-04-04 20:14:53
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;
		$this->prepare_bcd( $bcd );

		$this->maybe_save_course_groups( $bcd );
	}

	/**
		@brief		Save the nutritional information and ingredient metadata.
		@since		2015-04-09 19:29:30
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		$this->prepare_bcd( $bcd );
		$this->maybe_broadcast_whole_course( $bcd );
		if ( $this->is_26() )
			$this->maybe_save_quiz( $bcd );
		else
			$this->maybe_save_quiz_25( $bcd );
		$this->maybe_save_lesson( $bcd );
		$this->maybe_save_topic( $bcd );
	}

	/**
		@brief		Add our types.
		@since		2016-07-27 20:15:57
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types(
			'ld-notification',
			'sfwd-courses',
			'sfwd-lessons',
			'sfwd-question',
			'sfwd-quiz',
			'sfwd-essays',
			'sfwd-assignment',
			'groups',
			'sfwd-topic',
			'sfwd-certificates',
			'sfwd-transactions'
		);
	}

	/**
		@brief		Add ourselves into the menu.
		@since		2016-01-26 14:00:24
	**/
	public function threewp_broadcast_menu( $action )
	{
		$access = is_super_admin();
		$access = apply_filters( 'broadcast_learndash_menu_access', $access );
		if ( ! $access )
			return;

		$action->menu_page
			->submenu( 'broadcast_learndash' )
			->callback_this( 'admin_tabs' )
			->menu_title( 'LearnDash' )
			->page_title( 'LearnDash' );
	}

	/**
	 	@brief		Support non-trivial hosting setups
	  @return 	string $abspath
	**/
	public function get_root_path(){
		// We don't want to use ABSPATH as that might not have the correct base path for uploads

		$upload_dir_parts  = wp_upload_dir();
		$upload_path       = $upload_dir_parts['basedir'];
		$upload_path_parts = explode( 'wp-content', $upload_path );

		$rootpath = rtrim( $upload_path_parts[0], '/' );

		return $rootpath;
	}

	/**
		@brief		Parse content for Uncanny Content Blocks.
		@since		2020-12-10 18:35:02
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( ! isset( $bcd->learndash ) )
			return;

		foreach( $bcd->learndash->collection( 'preparse_tincanny_content' ) as $action_id => $collections )
		{
			foreach( $collections->collection( 'blocks' ) as $block )
			{
				$content_id             = $block[ 'attrs' ][ 'contentId' ];
				$original_snc_file_info = $collections->collection( 'snc_file_info' )->get( $content_id );
				$content_title          = $original_snc_file_info->file_name;
				$snc_file_info          = $this->get_snc_file_info_by( 'file_name', $content_title );

				if ( ! $snc_file_info )
					$snc_file_info = $this->insert_snc_file_info( $bcd, $original_snc_file_info );


				// Delete and recopy all of the files.
				$abspath = $this->get_root_path();

				$source = $abspath . $original_snc_file_info->url;
				$source = dirname( $source );
				$target = $abspath . $snc_file_info->url;
				$target = dirname( $target );

				$this->debug( 'Deleting %s', $target );
				if ( is_dir( $target ) )
					static::delete_recursive( $target );

				$this->debug( 'Copying %s to %s', $source, $target );

				static::copy_recursive( $source, $target );

				// Replace the block with the new info.
				$block[ 'attrs' ][ 'contentId' ] = $snc_file_info->ID . '';
				$block[ 'attrs' ][ 'contentUrl' ] = $snc_file_info->url;
				$action->content = ThreeWP_Broadcast()->gutenberg()->replace_text_with_block( $block[ 'original' ], $block, $action->content, [
					'json_options' => JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
				] );
			}
		}
	}

	/**
		@brief		Preparse content for Uncanny Content Blocks.
		@since		2020-12-10 18:35:02
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		$this->prepare_bcd( $bcd );

			$tc = $bcd->learndash
				->collection( 'preparse_tincanny_content' )
				->collection( $action->id );

		// Find any blocks in the content.
		$blocks = ThreeWP_Broadcast()->gutenberg()->parse_blocks( $content, [
			'dump_blocks_once' => $action->id,
			'stripslashes' => false,
		] );
		foreach( $blocks as $block )
		{
			if ( $block[ 'blockName' ] !== 'tincanny/content' )
				continue;
			$content_id = $block[ 'attrs' ][ 'contentId' ];
			$this->debug( 'tincanny/content block found! %s / %s / %s',
				$content_id,
				$block[ 'attrs' ][ 'contentTitle' ],
				$block[ 'attrs' ][ 'contentUrl' ]
			);

			$tc->collection( 'blocks' )
				->set( $content_id, $block );

			$snc_file_info = $this->get_snc_file_info_by( 'ID', $content_id );
			$tc->collection( 'snc_file_info' )
				->set( $content_id, $snc_file_info );
 		}

 		$bcd->wp_upload_dir = wp_upload_dir();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save any course groups.
		@since		2021-04-04 20:15:25
	**/
	public function maybe_save_course_groups( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-courses' )
			return;

		if ( $this->get_site_option( 'course_groups' ) != 'keep' )
			return;

		// Find all the child fields that start with learndash_group_enrolled_
		$key = 'learndash_group_enrolled_';
		$cf = $bcd->custom_fields()->child_fields();
		$course_groups = [];
		foreach( $cf as $field_name => $field_data )
		{
			if ( strpos( $field_name, $key ) !== 0 )
				continue;
			$course_groups[ $field_name ] = reset( $field_data );
		}
		$this->debug( 'Saved course groups: %s', $course_groups );
		$bcd->learndash->set( 'course_groups', $course_groups );
	}

	/**
		@brief		Save the lesson data.
		@since		2019-05-13 19:58:34
	**/
	public function maybe_save_lesson( $bcd )
	{
		$this->find_ld_course( $bcd );
	}

	/**
		@brief		Maybe save the quiz data.
		@since		2017-02-26 16:40:30
	**/
	public function maybe_save_quiz( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-quiz' )
			return;

		// Save the quiz.
		$quiz = $this->get_quiz( $bcd->post->ID );
		$this->debug( 'Saved quiz: %s', $quiz );
		$bcd->learndash->set( 'quiz', $quiz );

		// Save the questions.
		$questions = [];
		// This contains the array with post_id => question_id
		$ld_quiz_questions = $bcd->custom_fields()->get_single( 'ld_quiz_questions' );
		$ld_quiz_questions = maybe_unserialize( $ld_quiz_questions );
		if ( ! is_array( $ld_quiz_questions ) )
			$ld_quiz_questions = [];
		foreach( $ld_quiz_questions as $post_id => $question_id )
			$questions[ $post_id ] = $this->get_question( $question_id );
		$this->debug( 'Found %d questions: %s', count( $questions ), implode( ', ', array_keys( $ld_quiz_questions ) ) );
		$bcd->learndash->set( 'questions', $questions );

		// Save all of the categories from the parent blog for syncing later.
		foreach( $this->get_categories() as $category_id => $category )
			$bcd->learndash->collection( 'categories' )->set( $category_id, $category );

		$this->find_ld_course( $bcd );
	}

	/**
		@brief		Maybe save the quiz data.
		@since		2017-02-26 16:40:30
	**/
	public function maybe_save_quiz_25( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-quiz' )
			return;

		// Save the quiz.
		$quiz = $this->get_quiz( $bcd->post->ID );
		$bcd->learndash->set( 'quiz', $quiz );

		// Save the questions.
		$questions = $this->get_questions( $quiz->id );
		$this->debug( 'Found %d questions.', count( $questions ) );
		$bcd->learndash->set( 'questions', $questions );

		// Save all of the categories from the parent blog for syncing later.
		foreach( $this->get_categories() as $category_id => $category )
			$bcd->learndash->collection( 'categories' )->set( $category_id, $category );
	}

	/**
		@brief		Topic have to be handled also.
		@details	Is similar to lessons.
		@since		2019-12-09 21:50:23
	**/
	public function maybe_save_topic( $bcd )
	{
		$this->find_ld_course( $bcd );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		maybe_prerestore_course
		@since		2017-09-15 15:32:56
	**/
	public function maybe_prerestore_course( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-courses' )
			return;

		$bcd->learndash->forget( 'existing_course_meta' );

		// Do not overwrite the user enrollments, so we have to save the current value.
		$meta = $bcd->custom_fields()->child_fields()->get( '_sfwd-courses' );

		if ( ! is_array( $meta ) )
			return;

		$meta = reset( $meta );
		$meta = maybe_unserialize( $meta );
		$bcd->learndash->set( 'existing_course_meta', $meta );
		$this->debug( 'Existing courses meta found! %s', $meta );
	}

	/**
		@brief		Maybe restore the course data.
		@since		2017-02-26 15:47:51
	**/
	public function maybe_restore_course( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-courses' )
			return;

		$ld_course_steps = $bcd->custom_fields()->get_single( 'ld_course_steps' );
		$ld_course_steps = maybe_unserialize( $ld_course_steps );
		if ( is_array( $ld_course_steps ) )
		{
            if ( isset( $ld_course_steps[ 'steps' ] ) )
                $ld_course_steps[ 'steps' ] = $this->restore_course_steps( $bcd, $ld_course_steps[ 'steps' ] );

            if ( isset( $ld_course_steps[ 'h' ] ) )
                $ld_course_steps = $this->restore_course_steps_hrt( $bcd, $ld_course_steps );

            if ( isset( $ld_course_steps[ 'course_id' ] ) )
                $ld_course_steps[ 'course_id' ] = $bcd->new_post( 'ID' );

			$bcd->custom_fields()->child_fields()->update_meta( 'ld_course_steps', $ld_course_steps );
		}

		$this->update_sfwd_custom_field( [
			'broadcasting_data' => $bcd,
			'meta_key' => '_sfwd-courses',
			'meta_values' => [ 'sfwd-courses_course_prerequisite', 'sfwd-courses_certificate' ],
		] );

		if ( $this->get_site_option( 'course_groups' ) == 'replace' )
		{
			// Default behaviour is to carry over the groups from the parent course.
			$this->update_association( $bcd, 'learndash_group_enrolled_' );
		}

		if ( $this->get_site_option( 'course_groups' ) == 'keep' )
		{
			// This will restore the groups on the child.
			$course_groups = $bcd->learndash->get( 'course_groups' );
			if ( is_array( $course_groups ) )
			{
				$this->debug( 'Restoring course groups...' );
				$cf = $bcd->custom_fields()->child_fields();
				foreach( $course_groups as $key => $value )
					$cf->update_meta( $key, $value );
			}
		}
	}

	/**
		@brief		Maybe restore the group data.
		@since		2017-02-26 17:03:12
	**/
	public function maybe_restore_group( $bcd )
	{
		$this->update_association( $bcd, 'learndash_group_users_' );
	}

	/**
		@brief		Maybe restore the lesson data.
		@since		2017-02-26 16:22:22
	**/
	public function maybe_restore_lesson( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-lessons' )
			return;

		$this->update_sfwd_custom_field( [
			'broadcasting_data' => $bcd,
			'meta_key' => '_sfwd-lessons',
			'meta_values' => [ 'sfwd-lessons_course' ],
		] );

		$this->update_ld_course( $bcd );
	}

	/**
		@brief		maybe_restore_notification
		@since		2022-09-20 22:18:48
	**/
	public function maybe_restore_notification( $bcd )
	{
		foreach( [
			'_ld_notifications_course_id',
			'_ld_notifications_lesson_id',
			'_ld_notifications_topic_id',
			'_ld_notifications_quiz_id',
		] as $key )
		{
			$old_post_id = $bcd->custom_fields()->child_fields()->get( $key );
			if ( ! $old_post_id )
				continue;
			$old_post_id = reset( $old_post_id );
			if ( $old_post_id < 1 )
				continue;
			$this->debug( 'Found %s: %s', $key, $old_post_id );
			$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
			$bcd->custom_fields()->child_fields()->update_meta( $key, $new_post_id );
		}
	}

	/**
		@brief		Maybe restore the quiz data.
		@since		2017-02-26 16:40:30
	**/
	public function maybe_restore_quiz( $bcd )
	{
		$quiz_id_custom_fields =
		[
			'quiz_pro_id',
			'quiz_pro_id_ID',
			'quiz_pro_primary_ID',
		];

		if ( $bcd->post->post_type != 'sfwd-quiz' )
			return;

		$quiz = $bcd->learndash->get( 'quiz' );
		if ( ! $quiz )
			return $this->debug( 'No quiz found.' );

		$child_quiz_post_id = $bcd->new_post( 'ID' );
		$this->debug( 'Child quiz post ID is %s', $child_quiz_post_id );

		// Note that sfwd-quiz_quiz_pro has to be saved also, but we can only do it later.
		// Man is this quiz data all over the place.
		$this->update_sfwd_custom_field( [
			'broadcasting_data' => $bcd,
			'meta_key' => '_sfwd-quiz',
			'meta_values' => [ 'sfwd-quiz_course', 'sfwd-quiz_lesson', 'sfwd-quiz_certificate' ],
		] );

		$this->update_equivalent_post_id( $bcd, 'course_id' );
		$this->update_equivalent_post_id( $bcd, 'lesson_id' );

		// Remove old references to the old ID.
		foreach( $quiz_id_custom_fields as $key )
		{
			$new_key = str_replace( 'ID', $quiz->id, $key );
			$bcd->custom_fields()->child_fields()->delete_meta( $new_key );
			$new_key = str_replace( 'ID', $bcd->new_post( 'ID' ), $key );
			$bcd->custom_fields()->child_fields()->delete_meta( $new_key );
		}

		global $wpdb;
		$table = $this->get_table( 'wp_pro_quiz_master' );

		// Find the quiz with the same name.
		$quiz_name = str_replace( "'", '\\\'', $quiz->name );
		$query = sprintf( "SELECT * FROM `%s` WHERE `name` = '%s' ORDER BY `id` DESC", $table, $quiz_name );
		$child_quiz = $wpdb->get_row( $query );

		$data = (array) $quiz;
		unset( $data[ 'id' ] );
		$this->debug( 'Quiz data is: %s', $data );

		if ( ! $child_quiz )
		{
			$this->debug( 'Creating a new quiz on the child.' );
			$wpdb->insert( $table, $data );
			$child_quiz_id = $wpdb->insert_id;
		}
		else
		{
			$child_quiz_id = $child_quiz->id;
			$this->debug( 'Using existing child quiz %d and updating.', $child_quiz_id );
			// Update the quiz table.
			$wpdb->update( $table, $data, [ 'id' => $child_quiz_id ] );
		}

		$this->debug( 'Child quiz ID is %d', $child_quiz_id );
		// quiz_pro_id = XX
		$bcd->custom_fields()->child_fields()->update_meta( 'quiz_pro_id', $child_quiz_id );
		// quiz_pro_primary_XX = XX
		$bcd->custom_fields()->child_fields()->update_meta( 'quiz_pro_primary_' . $child_quiz_id, $child_quiz_id );

		// Add new references to the quiz pro ID.
		foreach( $quiz_id_custom_fields as $key )
		{
			$new_key = str_replace( 'ID', $child_quiz_id, $key );
			$this->debug( 'Replacing %s with %s', $key, $new_key );
			$bcd->custom_fields()->child_fields()->update_meta( $new_key, $child_quiz_id );
		}

		// The sfwd-quiz_quiz_pro key needs to be updated separately.
		$quiz_data = $bcd->custom_fields()->child_fields()->get( '_sfwd-quiz' );
		$quiz_data = reset( $quiz_data );
		$quiz_data = maybe_unserialize( $quiz_data );
		if ( is_array( $quiz_data ) )
		{
			if ( isset( $quiz_data[ 'sfwd-quiz_quiz_pro' ] ) )
				$quiz_data[ 'sfwd-quiz_quiz_pro' ] = intval( $child_quiz_id );
			$this->debug( 'Saving new %s data: %s', '_sfwd-quiz', $quiz_data );
			$bcd->custom_fields()->child_fields()->update_meta( '_sfwd-quiz', $quiz_data );
		}

		// Restore the questions.
		// This is a delicate procedure, since we have to overwrite instead of delete+insert, due to the question IDs used in the stats.

		// Also, the quiz ID, probably not being written by the same people as LearnDash, has nothing to do with the post ID.

		$ld_quiz_questions = $bcd->custom_fields()->get_single( 'ld_quiz_questions' );
		$ld_quiz_questions = maybe_unserialize( $ld_quiz_questions );
		if ( ! is_array( $ld_quiz_questions ) )
			$ld_quiz_questions = [];
		$ld_quiz_questions_qid = array_flip( $ld_quiz_questions );

		$questions = $bcd->learndash->get( 'questions', [] );
		$child_questions = $this->get_questions( $child_quiz_id );

		$categories = $this->get_categories();
		$questions_to_add = $questions;
		$table = $this->get_table( 'wp_pro_quiz_question' );

		$equivalent_questions = [];

		// Update existing and delete non-needed.
		foreach( $child_questions as $child_question_index => $child_question )
		{
			$found = false;
			foreach( $questions as $question_index => $question )
			{
				if ( $child_question->title == '' )
				{
					$this->debug( 'WARNING: Child question title is empty: %s', $child_question );
					continue;
				}
				if ( $child_question->title == $question->title )
				{
					$this->debug( 'Handling question %s', $child_question );
					$found = true;
					unset( $questions_to_add[ $question_index ] );
					// Update the question data.
					$data = (array) $question;
					$data[ 'id' ] = $child_question->id;
					$data[ 'quiz_id' ] = $child_quiz_id;
					if ( $data[ 'category_id' ] > 0 )
					{
						$category = $bcd->learndash->collection( 'categories' )->get( $data[ 'category_id' ] );
						$category_name = $category->category_name;
						$new_category_id = $this->find_equivalent_category( $categories, $category_name );
						$data[ 'category_id' ] = $new_category_id;
						$this->debug( 'Equivalent category of %s (%d) is %d', $category_name, $category->category_id, $new_category_id );
					}
					$this->debug( 'Updating child quiz question: %s', $data );
					$wpdb->update( $table, $data, [ 'id' => $child_question->id ] );
					$child_question_id = $child_question->id;

					// Update the question post type.
					$parent_question_id = $question->id;
					$post_id = $ld_quiz_questions_qid[ $parent_question_id ];
					$this->debug( 'Updating question %s / %s on blog %s', $post_id, $parent_question_id, $bcd->current_child_blog_id );
					switch_to_blog( $bcd->parent_blog_id );
					$question_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $post_id, [ $bcd->current_child_blog_id ] );
					restore_current_blog();
					$new_post_id = $question_bcd->new_post( 'ID' );
					$this->debug( 'Updating question_pro_id for post %s to %s', $new_post_id, $child_question_id );
					update_post_meta( $new_post_id, 'question_pro_id', $child_question->id );
					$this->debug( 'Updating quiz_id for post %s to %s', $new_post_id, $child_quiz_post_id );
					update_post_meta( $new_post_id, 'quiz_id', $child_quiz_id );
					// Delete all quiz keys.
					$ld_quiz = false;
					foreach( get_post_meta( $new_post_id ) as $key => $value )
						if ( strpos( $key, 'ld_quiz_' ) === 0 )
						{
							$this->debug( 'Deleting key %s', $key );
							$ld_quiz = true;
							delete_post_meta( $new_post_id, $key );
						}

					// Only add the ld_quiz custom field if it has been used.
					if ( $ld_quiz )
					{
						$this->debug( 'update_post_meta ' . 'ld_quiz_' . $child_quiz_post_id );
						update_post_meta( $new_post_id, 'ld_quiz_' . $child_quiz_post_id, $child_quiz_post_id );
					}

					update_post_meta( $new_post_id, 'quiz_id', $child_quiz_post_id );
					update_post_meta( $new_post_id, 'ld_quiz_id', $child_quiz_post_id );

					$question_data = [
						'sfwd-question_quiz' => $child_quiz_id,
					];
					$this->debug( 'Updating question data _sfwd-question with %s', $question_data );
					update_post_meta( $new_post_id, '_sfwd-question', $question_data );

					$equivalent_questions[ $post_id ] = [ $new_post_id, $child_question_id ];
					break;
				}
			}

			if ( ! $found )
			{
				// This child question has no equivalent parent. Delete it.
				$query = sprintf( "DELETE FROM `%s` WHERE `id` = '%d'", $table, $child_question->id );
				$this->debug( 'Deleting orphan quiz child question: %s', $query );
				$wpdb->query( $query );
			}
		}

		// Add new questions.
		foreach( $questions_to_add as $question_to_add )
		{
			$this->debug( 'Going to add question %s', $question_to_add );
			if ( ! isset( $question_to_add ) )
			{
				$this->debug( 'WARNING: Question is invalid: %s', $question_to_add );
				continue;
			}
			$parent_question_id = $question_to_add->id;
			$question_to_add = (array) $question_to_add;
			unset( $question_to_add[ 'id' ] );
			$question_to_add[ 'quiz_id' ] = $child_quiz_id;
			if ( $question_to_add[ 'category_id' ] > 0 )
			{
				$category = $bcd->learndash->collection( 'categories' )->get( $question_to_add[ 'category_id' ] );
				$category_name = $category->category_name;
				$new_category_id = $this->find_equivalent_category( $categories, $category_name );
				$question_to_add[ 'category_id' ] = $new_category_id;
				$this->debug( 'Equivalent category of %s (%d) is %d', $category_name, $category->category_id, $new_category_id );
			}
			$this->debug( 'Adding new child quiz question: %s', $question_to_add );
			$wpdb->insert( $table, $question_to_add );
			$child_question_id = $wpdb->insert_id;

			// Create the question post type.
			$post_id = $ld_quiz_questions_qid[ $parent_question_id ];
			$this->debug( 'Broadcasting question %s / %s to blog %s', $post_id, $parent_question_id, $bcd->current_child_blog_id );
			switch_to_blog( $bcd->parent_blog_id );
			$question_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $post_id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();

			// Now set the correct pro question ID for the question post.
			$new_question_post_id = $question_bcd->new_post( 'ID' );
			$this->debug( 'Updating question_pro_id for post %s to %s', $new_question_post_id, $child_question_id );
			update_post_meta( $new_question_post_id, 'question_pro_id', $child_question_id );
			update_post_meta( $new_question_post_id, 'quiz_id', $child_quiz_id );

			// Delete all quiz keys.
			foreach( get_post_meta( $new_question_post_id ) as $key => $value )
				if ( strpos( $key, 'ld_quiz_' ) === 0 )
				{
					$this->debug( 'Deleting key %s', $key );
					delete_post_meta( $new_question_post_id, $key );
				}
			update_post_meta( $new_question_post_id, 'ld_quiz_' . $child_quiz_post_id, $child_quiz_post_id );
			update_post_meta( $new_question_post_id, '_sfwd-question', [
				'sfwd-question_quiz' => $child_quiz_id,
			] );

			$equivalent_questions[ $post_id ] = [ $new_question_post_id, $child_question_id ];
		}

		$child_ld_quiz_questions = [];
		foreach( $ld_quiz_questions as $parent_post_id => $ignore )
		{
			$equivalent = $equivalent_questions[ $parent_post_id ];
			$child_ld_quiz_questions[ $equivalent[ 0 ] ] = intval( $equivalent[ 1 ] );
		}
		// Save the new data.
		$bcd->custom_fields()->child_fields()->update_meta( 'ld_quiz_questions', $child_ld_quiz_questions );

		$this->update_ld_course( $bcd );
	}

	/**
		@brief		Maybe restore the quiz data.
		@since		2017-02-26 16:40:30
	**/
	public function maybe_restore_quiz_25( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-quiz' )
			return;

		// Note that sfwd-quiz_quiz_pro has to be saved also, but we can only do it later.
		// Man is this quiz data all over the place.
		$this->update_sfwd_custom_field( [
			'broadcasting_data' => $bcd,
			'meta_key' => '_sfwd-quiz',
			'meta_values' => [ 'sfwd-quiz_course', 'sfwd-quiz_lesson', 'sfwd-quiz_certificate' ],
		] );

		if ( $bcd->custom_fields()->has( 'course_id' ) )
			$this->update_equivalent_post_id( $bcd, 'course_id' );
		if ( $bcd->custom_fields()->has( 'lesson_id' ) )
			$this->update_equivalent_post_id( $bcd, 'lesson_id' );

		$quiz = $bcd->learndash->get( 'quiz' );
		if ( ! $quiz )
			return $this->debug( 'No quiz found.' );

		global $wpdb;
		$table = $this->get_table( 'wp_pro_quiz_master' );

		// Find the quiz with the same name.
		$quiz_name = str_replace( "'", '\\\'', $quiz->name );
		$query = sprintf( "SELECT * FROM `%s` WHERE `name` = '%s' ORDER BY `id` DESC", $table, $quiz_name );
		$child_quiz = $wpdb->get_row( $query );

		$data = (array) $quiz;
		unset( $data[ 'id' ] );
		$this->debug( 'Quiz data is: %s', $data );

		if ( ! $child_quiz )
		{
			$this->debug( 'Creating a new quiz on the child.' );
			$wpdb->insert( $table, $data );
			$child_quiz_id = $wpdb->insert_id;
		}
		else
		{
			$child_quiz_id = $child_quiz->id;
			$this->debug( 'Using existing child quiz %d and updating.', $child_quiz_id );
			// Update the quiz table.
			$wpdb->update( $table, $data, [ 'id' => $child_quiz_id ] );
		}

		$this->debug( 'Child quiz ID is %d', $child_quiz_id );
		$bcd->custom_fields()->child_fields()->update_meta( 'quiz_pro_id', $child_quiz_id );

		// The sfwd-quiz_quiz_pro key needs to be updated separately.
		$quiz_data = $bcd->custom_fields()->child_fields()->get( '_sfwd-quiz' );
		$quiz_data = reset( $quiz_data );
		$quiz_data = maybe_unserialize( $quiz_data );
		if ( is_array( $quiz_data ) )
		{
			if ( isset( $quiz_data[ 'sfwd-quiz_quiz_pro' ] ) )
				$quiz_data[ 'sfwd-quiz_quiz_pro' ] = $child_quiz_id;
			$this->debug( 'Saving new %s data: %s', '_sfwd-quiz', $quiz_data );
			$bcd->custom_fields()->child_fields()->update_meta( '_sfwd-quiz', $quiz_data );
		}

		// Restore the questions.
		// This is a delicate procedure, since we have to overwrite instead of delete+insert, due to the question IDs used in the stats.

		// Also, the quiz ID, probably not being written by the same people as LearnDash, has nothing to do with the post ID.

		$questions = $bcd->learndash->get( 'questions', [] );
		$child_questions = $this->get_questions( $child_quiz_id );

		$categories = $this->get_categories();
		$questions_to_add = $questions;
		$table = $this->get_table( 'wp_pro_quiz_question' );

		// Update existing and delete non-needed.
		foreach( $child_questions as $child_question_index => $child_question )
		{
			$found = false;
			foreach( $questions as $question_index => $question )
			{
				if ( $child_question->title == $question->title )
				{
					$found = true;
					unset( $questions_to_add[ $question_index ] );
					// Update the question data.
					$data = (array) $question;
					$data[ 'id' ] = $child_question->id;
					$data[ 'quiz_id' ] = $child_quiz_id;
					if ( $data[ 'category_id' ] > 0 )
					{
						$category = $bcd->learndash->collection( 'categories' )->get( $data[ 'category_id' ] );
						$category_name = $category->category_name;
						$new_category_id = $this->find_equivalent_category( $categories, $category_name );
						$data[ 'category_id' ] = $new_category_id;
						$this->debug( 'Equivalent category of %s (%d) is %d', $category_name, $category->category_id, $new_category_id );
					}
					$this->debug( 'Updating child quiz question: %s', $data );
					$wpdb->update( $table, $data, [ 'id' => $child_question->id ] );
					break;
				}
			}

			if ( ! $found )
			{
				// This child question has no equivalent parent. Delete it.
				$query = sprintf( "DELETE FROM `%s` WHERE `id` = '%d'", $table, $child_question->id );
				$this->debug( 'Debug orphan quiz child question: %s', $query );
				$wpdb->query( $query );
			}
		}

		// Add new questions.
		foreach( $questions_to_add as $question_to_add )
		{
			$question_to_add = (array) $question_to_add;
			unset( $question_to_add[ 'id' ] );
			$question_to_add[ 'quiz_id' ] = $child_quiz_id;
			if ( $question_to_add[ 'category_id' ] > 0 )
			{
				$category = $bcd->learndash->collection( 'categories' )->get( $question_to_add[ 'category_id' ] );
				$category_name = $category->category_name;
				$new_category_id = $this->find_equivalent_category( $categories, $category_name );
				$question_to_add[ 'category_id' ] = $new_category_id;
				$this->debug( 'Equivalent category of %s (%d) is %d', $category_name, $category->category_id, $new_category_id );
			}
			$this->debug( 'Adding new child quiz question: %s', $question_to_add );
			$wpdb->insert( $table, $question_to_add );
		}
	}

	/**
		@brief		Maybe restore this lesson topic.
		@since		2017-02-26 16:35:29
	**/
	public function maybe_restore_topic( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-topic' )
			return;

		$this->update_sfwd_custom_field( [
			'broadcasting_data' => $bcd,
			'meta_key' => '_sfwd-topic',
			'meta_values' => [ 'sfwd-topic_course', 'sfwd-topic_lesson' ],
		] );

		$this->update_equivalent_post_id( $bcd, 'course_id' );
		$this->update_equivalent_post_id( $bcd, 'lesson_id' );

		$this->update_ld_course( $bcd );
	}

	/**
	 * Courses can be associated to woocommerce products.
	 *
	 * @since		2025-01-16 09:20:17
	 **/
	public function maybe_restore_woocommerce( $bcd )
	{
		$key = '_related_course';
		$value = $bcd->custom_fields()->get_single( $key );

		$values = maybe_unserialize( $value );
		if ( ! is_array( $values ) )
			return;

		$this->debug( 'Handling %s: %s', $key, $value );

		$new_values = [];
		foreach( $values as $old_course_id )
		{
			$new_course_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_course_id, get_current_blog_id() );
			$new_values []= $new_course_id;
		}

		$bcd->custom_fields()->child_fields()->update_meta( $key, $new_values );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Broadcast the course + lessons + topic + everything to these blogs.
		@details	Broadcasts course, lessons, topics, quizzes, certificate.
		@since		2018-11-02 15:04:42
	**/
	public function broadcast_whole_course( $course_id, $blogs )
	{
		$post_ids = $this->get_course_post_ids( $course_id );

		$this->debug( 'Broadcasting whole course %s: %s', $course_id, $post_ids );

		foreach( $post_ids as $index => $post_id )
		{
			$this->debug( 'Broadcasting course part %d on blog %s.', $post_id, get_current_blog_id() );
			// This is a workaround for the queue. As of 2019-02-13 there is a "bug" that makes the queue handle all instances of a post at the time.
			// This makes it impossible to correctly broadcast a course: course, then lessons + topics etc, then the course again.
			// Only the "first" course is high priority.
			if ( $index < 1 )
			{
				ThreeWP_Broadcast()->api()->broadcast_children( $post_id, $blogs );
				$this->debug( 'Finished broadcasting course %d on blog %d.', $post_id, get_current_blog_id() );
				continue;
			}
			ThreeWP_Broadcast()->
				api()->
				low_priority()->
				broadcast_children( $post_id, $blogs );
			$this->debug( 'Finished broadcasting course part %d on blog %d.', $post_id, get_current_blog_id() );
		}
	}

	/**
		@brief		UI to broadcast a whole course.
		@since		2018-11-02 13:44:18
	**/
	public function course_broadcast()
	{
		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$form->markup( 'm_course_broadcast' )
			->p( __( 'Use the form below to broadcast a whole course, including lessons and topics to other blogs. For best results, use the Queue add-on to avoid PHP timeouts.', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_courses' )
			// Fieldset label
			->label( __( 'Course selection', 'threewp_broadcast' ) );

		$courses = get_posts( [
			'post_type' => 'sfwd-courses',
			'posts_per_page' => 500,
			'orderby' => 'post_title',
			'order' => 'ASC',
		] );

		$options = [];
		foreach( $courses as $course )
			$options[ $course->ID ] = sprintf( '%s (%s)', $course->post_title, $course->ID );

		$courses_to_broadcast = $fs->select( 'courses_to_broadcast' )
			->description( __( 'Select the courses you wish to broadcast.', 'threewp_broadcast' ) )
			->label( __( 'Courses to broadcast', 'threewp_broadcast' ) )
			->opts( $options )
			->multiple()
			->autosize();

		$fs = $form->fieldset( 'fs_blogs' )
			// Fieldset label
			->label( __( 'Blogs', 'threewp_broadcast' ) );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to broadcast the course.', 'threewp_broadcast' ),
			'form' => $fs,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$fs = $form->fieldset( 'fs_go' );
		// Fieldset label
		$fs->legend()->label( __( 'Go!', 'threewp_broadcast' ) );

		$copy_button = $fs->primary_button( 'copy' )
			->value( __( 'Copy the selected courses', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$blogs = $blogs_select->get_post_value();
			$courses = $courses_to_broadcast->get_post_value();
			$messages = [];

			foreach( $courses as $course_id )
			{
				$messages []= sprintf( __( 'Broadcasting course %s to %s', 'threewp_broadcast' ), $course_id, implode( ', ', $blogs ) );
				$this->broadcast_whole_course( $course_id, $blogs );
			}

			$messages = implode( "\n", $messages );
			$r .= $this->info_message_box()->_( $messages );

			$r = apply_filters( 'broadcast_learndash_after_whole_course', $r );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Do things to a whole course.
		@since		2019-01-28 13:58:01
	**/
	public function course_tool()
	{
		$form = $this->form2();
		$form->css_class( 'plainview_form_auto_tabs' );
		$r = '';

		$form->markup( 'm_course_tool' )
			->p( __( 'Use the form below to manipulate a whole course, including lessons, topics and quizzes.', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_courses' )
			// Fieldset label
			->label( __( 'Course selection', 'threewp_broadcast' ) );

		$courses = get_posts( [
			'post_type' => 'sfwd-courses',
			'posts_per_page' => 500,
			'orderby' => 'post_title',
			'order' => 'ASC',
		] );

		$options = [];
		foreach( $courses as $course )
			$options[ $course->ID ] = sprintf( '%s (%s)', $course->post_title, $course->ID );

		$courses_to_manipulate = $fs->select( 'courses_to_manipulate' )
			->description( __( 'Select the courses you wish to manipulate.', 'threewp_broadcast' ) )
			->label( __( 'Courses to manipulate', 'threewp_broadcast' ) )
			->multiple()
			->opts( $options )
			->required()
			->autosize();

		$fs = $form->fieldset( 'fs_options' );
		// Fieldset label
		$fs->legend()->label( __( 'Options', 'threewp_broadcast' ) );

		$manipulation = $fs->select( 'manipulation' )
			->description( __( 'What to do with the selected courses.', 'threewp_broadcast' ) )
			->label( __( 'Manipulation', 'threewp_broadcast' ) )
			->opt( '', __( 'Nothing', 'threewp_broadcast' ) )
			->opt( 'delete', __( 'Delete', 'threewp_broadcast' ) )
			->opt( 'find_unlinked_children', __( 'Find unlinked children', 'threewp_broadcast' ) )
			->opt( 'restore', __( 'Restore', 'threewp_broadcast' ) )
			->opt( 'trash', __( 'Trash', 'threewp_broadcast' ) )
			->opt( 'unlink', __( 'Unlink', 'threewp_broadcast' ) );

		$fs = $form->fieldset( 'fs_go' );
		// Fieldset label
		$fs->legend()->label( __( 'Go!', 'threewp_broadcast' ) );

		$copy_button = $fs->primary_button( 'manipulate' )
			->value( __( 'Manipulate the selected courses', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$courses = $courses_to_manipulate->get_post_value();
			$manipulation = $manipulation->get_post_value();
			$messages = [];

			foreach( $courses as $course_id )
			{
				$post_ids = $this->get_course_post_ids( $course_id );
				$post_ids = $this->get_question_ids_also( $post_ids );
				$posts = [];
				foreach( $post_ids as $post_id )
				{
					$post = get_post( $post_id );
					$posts []= sprintf( '%s %s, %s', $post->ID, $post->post_title, $post->post_type );
				}
				$messages []= sprintf( __( 'Selected %s for course %s. Post IDs affected:<br/>%s', 'threewp_broadcast' ),
					$manipulation,
					$course_id,
					implode( '<br/>', $posts )
				);
				$this->debug( 'Course tool: %s for %s', $manipulation, implode( ', ', $post_ids ) );
						$api = ThreeWP_Broadcast()->api();
				switch( $manipulation )
				{
					case 'delete':
						foreach( $post_ids as $post_id )
							wp_delete_post( $post_id, true );
					break;
					case 'find_unlinked_children':
						foreach( $post_ids as $post_id )
							$api->find_unlinked_children( $post_id );
					break;
					case 'restore':
						foreach( $post_ids as $post_id )
							wp_untrash_post( $post_id );
					break;
					case 'trash':
						foreach( $post_ids as $post_id )
							wp_trash_post( $post_id );
					break;
					case 'unlink':
						foreach( $post_ids as $post_id )
							$api->unlink( $post_id );
					break;
				}
			}

			$messages = implode( "\n", $messages );
			$r .= $this->info_message_box()->_( $messages );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Find the equivalent category in this category collection.
		@details	Will insert the category if not found.
		@param		$category	The category collection as returned by get_categories().
		@param		$category_name_to_find The name of the category to find.
		@since		2017-10-19 13:57:24
	**/
	public function find_equivalent_category( $categories, $category_name_to_find )
	{
		foreach( $categories as $category )
			if( $category->category_name == $category_name_to_find )
				return $category->category_id;

		// Since we're here, the category wasn't found. Insert it.
		$this->debug( 'Creating category %s', $category_name_to_find );

		global $wpdb;
		$wpdb->insert( $this->get_table( 'wp_pro_quiz_category' ), [
			'category_name' => $category_name_to_find,
		] );
		$new_category_id = $wpdb->insert_id;

		// We need to save this so that we don't keep creating the same category.
		$categories->set( $new_category_id, (object)[
			'category_id' => $new_category_id,
			'category_name' => $category_name_to_find,
		] );

		return $new_category_id;
	}

	/**
		@brief		Find the ld_course custom field.
		@since		2019-12-10 09:14:59
	**/
	public function find_ld_course( $bcd )
	{

		if ( $bcd->post->post_type == 'sfwd-courses' )
		{
			$course_id = $bcd->post->ID;
			$course_ids = [ $course_id ];
		}
		else
		{
			// First, in the course custom field.
			$course_id = $bcd->custom_fields()->get_single( 'course_id' );
			$course_id = intval( $course_id );

			$course_ids = [];
			// Second, sometimes course info is stored in ld_course_xxx custom fields.
			foreach( $bcd->custom_fields() as $key => $data )
			{
				if ( strpos( $key, 'ld_course_' ) === 0 )
				{
					$value = reset( $data );
					$value = intval( $value );
					if ( $value > 0 )
						$course_ids[] = reset( $data );
				}
			}
		}

		$this->debug( 'Saved course_id as %s, and ids as %s', $course_id, $course_ids );
		$bcd->learndash->set( 'course_id', $course_id );
		$bcd->learndash->set( 'course_ids', $course_ids );
	}

	/**
		@brief		Find the quiz with this name.
		@since		2018-12-12 19:24:43
	**/
	public function find_quiz( $name )
	{
		global $wpdb;
		$table = $this->get_table( 'wp_pro_quiz_master' );

		$query = sprintf( "SELECT * FROM `%s` WHERE `name` = '%s' ORDER BY `id` DESC", $table, $name );
		$child_quiz = $wpdb->get_row( $query );

		if ( ! $child_quiz )
			return false;

		return $child_quiz;
	}

	/**
		@brief		Save all of the categories on this blog.
		@since		2017-10-19 13:44:45
	**/
	public function get_categories()
	{
		global $wpdb;
		$r = ThreeWP_Broadcast()->collection();
		$query = sprintf( "SELECT * FROM `%s`", $this->get_table( 'wp_pro_quiz_category' ) );
		$results = $wpdb->get_results( $query );
		foreach( $results as $result )
			$r->set( $result->category_id, $result );
		return $r;
	}

	/**
		@brief		Return an array of post IDs of all of the lessons, topics, quizzes etc beloning to this course ID.
		@since		2019-01-28 13:55:59
	**/
	public function get_course_post_ids( $course_id )
	{
		// Begin with the course.
		$posts = [ $course_id ];

		global $wpdb;
		$query = sprintf( "SELECT `post_id` FROM `%s` WHERE ( `meta_key` = '%s' AND `meta_value` = '%s' ) OR ( `meta_key` = 'course_id' AND `meta_value` = '%s' ) ORDER BY `post_id` ASC",
			$wpdb->postmeta,
			'ld_course_' . $course_id,
			$course_id,
			$course_id
		);
		$this->debug( $query );
		$results = $wpdb->get_results( $query );

		foreach( $results as $result )
			$posts[ $result->post_id ]= $result->post_id;

		$data = get_post_meta( $course_id, '_sfwd-courses', true );
		$data = maybe_unserialize( $data );
		// Find the certificate used in this post.
		if ( is_array( $data ) )
		{
			if ( isset( $data[ 'sfwd-courses_certificate' ] ) )
				if ( $data[ 'sfwd-courses_certificate' ] > 0 )
					$posts []= intval( $data[ 'sfwd-courses_certificate' ] );
		}

		// Remove all 0 posts
		foreach( $posts as $index => $post_id )
			if ( $post_id < 1 )
				unset( $posts[ $index ] );

		// And now that we've done everything, we have to broadcast the post again.
		$posts []= $course_id;

		return $posts;
	}

	/**
		@brief		Return the equivalent post from a value in a custom field.
		@since		2017-06-29 21:45:00
	**/
	public function get_equivalent_post_from_custom_field( $bcd, $meta_key )
	{
		$old_post_id = $bcd->custom_fields()->get_single( $meta_key );
		$new_post_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
	}

	/**
		@brief		Return the questions of this quiz
		@since		2017-06-29 12:37:07
	**/
	public function get_questions( $quiz_id )
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `quiz_id` = '%d'", $this->get_table( 'wp_pro_quiz_question' ), $quiz_id );
		$questions = $wpdb->get_results( $query );
		return $questions;
	}

	/**
		@brief		Return a single question.
		@since		2018-12-13 21:32:41
	**/
	public function get_question( $question_id )
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%d'", $this->get_table( 'wp_pro_quiz_question' ), $question_id );
		$question = $wpdb->get_row( $query );
		return $question;
	}

	/**
		@brief		From this array of post IDs, add the post IDs of any questions.
		@details	Will only do that for post IDs that are quizzes.
		@since		2019-03-11 20:38:34
	**/
	public function get_question_ids_also( $post_ids )
	{
		$r = $post_ids;
		foreach( $post_ids as $post_id )
		{
			$post = get_post( $post_id );
			if ( $post->post_type != 'sfwd-quiz' )
				continue;
			$ld_quiz_questions = get_post_meta( $post_id, 'ld_quiz_questions', true );
			$ld_quiz_questions = maybe_unserialize( $ld_quiz_questions );
			if ( ! is_array( $ld_quiz_questions ) )
				continue;
			foreach( $ld_quiz_questions as $post_id => $question_id )
				$r [] = $post_id;
		}
		return $r;
	}

	/**
		@brief		Return the quiz from this post ID.
		@since		2017-06-29 13:23:10
	**/
	public function get_quiz( $post_id )
	{
		$quiz_id = get_post_meta( $post_id, 'quiz_pro_id', true );
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` = '%d'", $this->get_table( 'wp_pro_quiz_master' ), $quiz_id );
		$this->debug( $query );
		$quiz = $wpdb->get_row( $query );
		return $quiz;
	}

	/**
		@brief		Find the uncanny content info on this blog.
		@since		2020-12-10 18:35:52
	**/
	public function get_snc_file_info_by( $key = 'ID', $value = '' )
	{
		global $wpdb;
		$query = sprintf( "SELECT * FROM `%s` WHERE `%s` = '%s'", $this->get_table( 'snc_file_info' ), $key, $value );
		$this->debug( $query );
		$row = $wpdb->get_row( $query );
		return $row;
	}

	/**
		@brief		Return the name of the table on this blog with the correct prefix.
		@since		2017-06-29 21:20:20
	**/
	public function get_table( $name )
	{
		global $wpdb;

		// If this is a pro quiz table, see if the new tables exist.
		if ( strpos( $name, 'wp_pro_quiz' ) !== false )
		{
			// First, look for the old tables.
			$table = sprintf( '%s%s', $wpdb->prefix, $name );
			if ( $this->database_table_exists( $table ) )
				return $table;

			// If they don't exist, hope that LD have only renamed the tables once, to ld_pro_quiz.
			$name = str_replace( 'wp_pro_quiz', 'learndash_pro_quiz', $name );
			$table = sprintf( '%s%s', $wpdb->prefix, $name );
			return $table;
		}

		return sprintf( '%s%s', $wpdb->prefix, $name );
	}

	/**
		@brief		Broadcast the h course steps recursively.
		@since		2018-01-07 14:36:52
	**/
	public function handle_ld_h_course_steps( $bcd, $array )
	{
		$new_array = [];
		foreach( $array as $old_post_id => $subarray )
		{
			if ( strlen( intval( $old_post_id ) ) == strlen( $old_post_id ) )
			{
				$this->debug( 'Handling handle_ld_h_course_steps for %s', $old_post_id );
				$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
			}
			else
				$new_post_id = $old_post_id;
			$new_array[ $new_post_id ] = $this->handle_ld_h_course_steps( $bcd, $subarray );
		}
		return $new_array;
	}

	/**
		@brief		Insert a new row in the snc_file_info table based on this existing $snc_file_info.
		@since		2020-12-10 18:36:10
	**/
	public function insert_snc_file_info( $bcd, $snc_file_info )
	{
		global $wpdb;
		$table = $this->get_table( 'snc_file_info' );
		$data = (array)$snc_file_info;
		unset( $data[ 'ID' ] );
		$wpdb->insert( $table, $data );
		$data[ 'ID' ] = $wpdb->insert_id;

		// Now that we have the ID, we need to replace the URL.
		$data[ 'url' ] = str_replace( '/uncanny-snc/' . $snc_file_info->ID . '/', '/uncanny-snc/' . $data[ 'ID' ] . '/', $data[ 'url' ] );

		$abspath = $this->get_root_path();

		// Replace the upload dir.
		$old_wp_upload_dir = $bcd->wp_upload_dir[ 'basedir' ];
		$old_wp_upload_dir = str_replace( $abspath, '', $old_wp_upload_dir );
		$wp_upload_dir = wp_upload_dir();
		$wp_upload_dir = $wp_upload_dir[ 'basedir' ];
		$wp_upload_dir = str_replace( $abspath, '', $wp_upload_dir );
		$data[ 'url' ] = str_replace( $old_wp_upload_dir, $wp_upload_dir, $data[ 'url' ] );

		$wpdb->update( $table, [ 'url' => $data[ 'url' ] ], [ 'ID' => $data[ 'ID' ] ] );

		$this->debug( 'Inserted snc_file_info %s: %s', $data[ 'ID' ], $data );
		return (object) $data;
	}

	/**
		@brief		Is LD version 2.6 or higher?
		@since		2019-02-11 13:08:22
	**/
	public function is_26()
	{
		if ( ! defined( 'LEARNDASH_VERSION' ) )
			return false;
		return version_compare( LEARNDASH_VERSION, '2.6.0', '>=' );
	}

	/**
		@brief		If this is a whole course, broadcast everything related to it.
		@since		2018-11-02 10:54:47
	**/
	public function maybe_broadcast_whole_course( $bcd )
	{
		if ( $bcd->post->post_type != 'sfwd-courses' )
			return;

		// Are we in a course loop already?
		if ( $bcd->custom_fields()->get_single( 'broadcasting_whole_course' ) )
			return;

	}

	/**
		@brief		Prepare the broadcasting_data object.
		@since		2016-07-27 21:28:24
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->learndash ) )
			$bcd->learndash = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Restore course steps that are in a steps array.
		@since		2021-04-04 20:12:01
	**/
	public function restore_course_steps( $bcd, $steps )
	{
        if ( is_array( $steps ) )
        {
            // We have to parse the key and the value.
            $new_steps = [];
            foreach( $steps as $step_index => $step_data )
            {
                // Parse the key
                if (
                    ( strlen( intval( $step_index ) ) == strlen( $step_index ) )
                    &&
                    ( intval( $step_index ) > 0 )       // catch "h"
                )
                {
                	$this->debug( 'restore_course_steps %s', $step_index );
                    $step_index = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $step_index, get_current_blog_id() );
                }

                // Replace the value.
                $new_steps[ $step_index ] = $this->restore_course_steps( $bcd, $step_data );
            }
            $steps = $new_steps;
        }
        else
        {
            // If this is an integer, it's a post ID.
			if ( strlen( intval( $steps ) ) == strlen( $steps ) )
			{
				$this->debug( 'restore_course_steps integer %s', $steps );
				$steps = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $steps, get_current_blog_id() );
			}
        }
        return $steps;
	}

	/**
		@brief		Restore course steps that are in an hrt array.
		@since		2021-04-04 20:12:13
	**/
	public function restore_course_steps_hrt( $bcd, $ld_course_steps )
	{
        // I have no idea what these characters mean.

        // h
        $h = $this->handle_ld_h_course_steps( $bcd, $ld_course_steps[ 'h' ] );
        $ld_course_steps[ 'h' ] = $h;

        // l
        $l_data = $ld_course_steps[ 'l' ];
        foreach( $l_data as $index => $item )
        {
            // Split out the item into the type and ID.
            $parts = explode( ':', $item );
            // Broadcast the post.
            $old_post_id = $parts[ 1 ];
            $this->debug( 'restore_course_steps_hrt l %s', $old_post_id );
            $new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
            $l_data[ $index ] = sprintf( '%s:%s', $parts[ 0 ], $new_post_id );
        }
        $ld_course_steps[ 'l' ] = $l_data;

        // r
        $r = [];
        foreach( $ld_course_steps[ 'r' ] as $key => $children )
        {
            // Assemble the new key first.
            $parts = explode( ':', $key );
            $old_post_id = $parts[ 1 ];
            $this->debug( 'restore_course_steps_hrt r key %s', $old_post_id );
            $new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
            $new_key = sprintf( '%s:%s', $parts[ 0 ], $new_post_id );

            $new_children = [];
            // And now we can handle each child.
            foreach( $children as $child )
            {
                $parts = explode( ':', $child );
                $old_post_id = $parts[ 1 ];
                $this->debug( 'restore_course_steps_hrt r child %s', $old_post_id );
                $new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
                $new_child = sprintf( '%s:%s', $parts[ 0 ], $new_post_id );
                $new_children []= $new_child;
            }

            $r[ $new_key ] = $new_children;
        }
        $ld_course_steps[ 'r' ] = $r;

        // t
        foreach( $ld_course_steps[ 't' ] as $type => $posts )
        {
            $new_post_ids = [];
            foreach( $posts as $old_post_id )
            {
            	$this->debug( 'restore_course_steps_hrt t %s', $old_post_id );
                $new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
                $new_post_ids []= $new_post_id;
            }
            $ld_course_steps[ 't' ][ $type ] = $new_post_ids;
        }

        return $ld_course_steps;
	}

	/**
		@brief		Settings interface
		@since		2021-04-07 19:38:13
	**/
	public function settings()
	{
		$form = $this->form2();
		$r = '';

		$input_course_groups = $form->select( 'course_groups' )
			// Input title
			->description( __( 'How to handle the groups of a broadcasted course', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'Course groups', 'threewp_broadcast' ) )
			->opt( 'replace', "Replace the groups for the child course" )
			->opt( 'keep', "Keep the groups the child course is assigned to" )
			->value( $this->get_site_option( 'course_groups' ) );

		$save = $form->primary_button( 'save' )
			// Button
			->value( __( 'Save settings', 'threewp_broadcast' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$value = $input_course_groups->get_post_value();
			$this->update_site_option( 'course_groups', $value );

			$r .= $this->info_message_box()->_( 'Options saved!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		The site options.
		@since		2021-04-07 18:23:08
	**/
	public function site_options()
	{
		return array_merge( [
			/**
				@brief		How to handle the course groups: replace or keep
				@since		2021-04-07 18:58:45
			**/
			'course_groups' => 'replace',
		], parent::site_options() );
	}

	/**
		@brief		Update the association to other parts of LearnDash.
		@since		2017-02-26 17:04:36
	**/
	public function update_association( $bcd, $assoc_key )
	{
		$cf = $bcd->custom_fields()->child_fields();
		foreach( $cf as $key => $value )
		{
			// Look for the key.
			if ( strpos( $key, $assoc_key ) === false )
				continue;

			// If this field is protected, leave it be.
			if ( $bcd->custom_fields()->protectlist_has( $key ) )
				continue;

			// Extract the assoc ID.
			$old_assoc_id = str_replace( $assoc_key, '', $key );

			if ( $old_assoc_id == $bcd->post->ID )
			{
				$this->debug( 'Updating association %s', $assoc_key );
				$new_assoc_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_assoc_id, get_current_blog_id() );
			}
			else
			{
				$this->debug( 'New ID for association %s is ourself.', $assoc_key );
				$new_assoc_id = $bcd->new_post( 'ID' );
			}

			if ( $new_assoc_id > 0 )
			{
				$new_assoc_key = $assoc_key . $new_assoc_id;
				$this->debug( 'Assigning new association meta key: %s', $new_assoc_key );
				$cf->update_meta( $new_assoc_key, $value );
			}

			// Delete the old key that isn't being used.
			$cf->delete_meta( $key );
		}
	}

	/**
		@brief		Update the post ID in this custom field.
		@since		2017-02-26 16:37:30
	**/
	public function update_equivalent_post_id( $bcd, $meta_key )
	{
		$old_post_id = $bcd->custom_fields()->get_single( $meta_key );
		$new_post_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
		$this->debug( 'Updating custom field %s with %d from blog %d, post %d', $meta_key, $new_post_id, $bcd->parent_blog_id, $old_post_id );
		$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_post_id );
		return $new_post_id;
	}

	/**
		@brief		Set the ld_course_xx custom field.
		@since		2019-12-09 22:32:57
	**/
	public function update_ld_course( $bcd )
	{
		$course_id = $bcd->learndash->get( 'course_id' );
		if ( $course_id > 0 )
		{
			$new_course_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $course_id, get_current_blog_id() );
			$bcd->custom_fields()->child_fields()->update_meta( 'course_id', $new_course_id );
		}

		// Delete all course keys.
		foreach( $bcd->custom_fields()->child_fields() as $key => $ignore )
			if ( strpos( $key, 'ld_course_' ) === 0 )
			{
				$this->debug( 'Deleting key %s', $key );
				$bcd->custom_fields()->child_fields()->delete_meta( $key );
			}

		$old_course_ids = $bcd->learndash->get( 'course_ids' );
		if ( $old_course_ids )
		{
			foreach( $old_course_ids as $old_course_id )
			{
				$new_course_id = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $old_course_id, get_current_blog_id() );
				if ( $new_course_id > 0 )
				{
					$key = 'ld_course_' . $new_course_id;
					$this->debug( 'Updating %s', $key );
					$bcd->custom_fields()
						->child_fields()
						->update_meta( $key, $new_course_id );
				}
			}
		}
	}

	/**
		@brief		Common function to update the serialized sfwd data in the child custom field.
		@since		2017-02-26 16:24:08
	**/
	public function update_sfwd_custom_field( $options )
	{
		$options = (object) $options;

		// Make the meta values array easy to modify.
		$options->meta_values = array_combine( $options->meta_values, $options->meta_values );

		$bcd = $options->broadcasting_data;
		$data = $bcd->custom_fields()->child_fields()->get( $options->meta_key );
		$data = reset( $data );
		$data = maybe_unserialize( $data );

		$action = $this->new_action( 'update_sfwd_custom_field' );
		$action->broadcasting_data = $bcd;
		$action->custom_field = $data;
		$action->options = $options;
		$action->execute();

		$data = $action->custom_field;

		foreach( $options->meta_values as $key )
		{
			if ( ! isset( $data[ $key ] ) )
				continue;
			$this->debug( 'Handling meta value %s', $key );
			if  ( is_array( $data[ $key ] ) )
			{
				$new_value = [];
				foreach( $data[ $key ] as $value )
					$new_value []= $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $value, get_current_blog_id() );
			}
			else
			{
				$new_value = $bcd->equivalent_posts()->get( $bcd->parent_blog_id, $data[ $key ], get_current_blog_id() );
			}
			$this->debug( 'Updating meta value %s to %s', $key, $new_value );
			$data[ $key ] = $new_value;
		}

		// Do we have to merge old meta? This is mostly for user enrollments.
		if ( $options->meta_key == '_sfwd-courses' )
		{
			$old_meta = $bcd->learndash->get( 'existing_course_meta' );
			if ( $old_meta )
			{
				$action = $this->new_action( 'merge_existing_course_meta' );
				$action->broadcasting_data = $bcd;
				$action->keys = [];

				if ( static::$keep_sfwd_courses_course_access_list )
					$action->keys []= 'sfwd-courses_course_access_list';

				$action->execute();

				foreach( $action->keys as $key )
				{
					if ( isset( $old_meta[ $key ] ) )
					{
						$this->debug( 'Merging old %s: %s', $key, $old_meta[ $key ] );
						$data[ $key ] = $old_meta[ $key ];
					}
				}
			}
		}

		$this->debug( 'Saving new data for %s: %s', $options->meta_key, $data );
		$bcd->custom_fields()->child_fields()->update_meta( $options->meta_key, $data );
	}
}
