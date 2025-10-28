<?php

namespace threewp_broadcast\premium_pack\divi_builder;

/**
	@brief				Adds support for <a href="https://www.elegantthemes.com/plugins/divi-builder/">Divi Builder</a> and themes using it.
	@plugin_group		3rd party compatability
	@since				2016-11-10 21:33:22
**/
class Divi_Builder
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		The regexps for encoded URLs.
		@since		2023-01-02 12:34:38
	**/
	public static $encoded_url_regexps = [
		'/url="@ET-DC@([a-zA-Z0-9]*)[=][=]@"/',
	];
	public function _construct()
	{
		$this->add_action( 'admin_menu', 100 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_parse_content', 100 );
		$this->add_action( 'threewp_broadcast_preparse_content' );
		$this->add_action( 'threewp_broadcast_sync_taxonomy_start', 'disable_et_pb_force_regenerate_templates' );
		new Global_Module_Row();
		new Global_Module_Section();
		new et_pb_blog();
		new et_pb_image();
	}

	/**
		@brief		Add ourselves to the Divi menu.
		@since		2020-08-10 18:54:03
	**/
	public function admin_menu()
	{
		add_submenu_page(
			'et_divi_options',
			'Broadcast',
			'Broadcast',
			'manage_options',
			'bc_divi',
			[ $this, 'ui_tabs' ]
		);
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2016-11-10 21:33:22
	**/
	public function disable_et_pb_force_regenerate_templates()
	{
		$this->debug( 'Disabling et_pb_force_regenerate_templates.' );
		remove_action( 'created_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'edited_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'delete_term', 'et_pb_force_regenerate_templates' );
	}

	/**
		@brief		Restore a template.
		@since		2020-08-10 19:30:03
	**/
	public function restore_et_template( $bcd )
	{
		foreach( [
			'_et_body_layout_id',
			'_et_footer_layout_id',
			'_et_header_layout_id',
		] as $meta_key )
		{
			$old_id = $bcd->custom_fields()->get_single( $meta_key );
			if ( $old_id < 1 )
				continue;
			switch_to_blog( $bcd->parent_blog_id );
			$new_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $old_id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();
			// $new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
			$new_id = $new_bcd->new_post( 'ID' );
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $meta_key, $new_id );
		}

		$key = '_et_use_on';
		$et_use_on = $bcd->custom_fields()->get( $key );
		if ( $et_use_on )
		{
			foreach( $et_use_on as $use_index => $use_on )
			{
				$parts = explode( ':', $use_on );
				switch( $parts[ 0 ] )
				{
					case 'archive':
						switch( $parts[ 1 ] )
						{
							case 'taxonomy':
								$old_term_id = intval( $parts[ 2 ] );
								if ( $old_term_id > 0 )
								{
									$new_term_id = $bcd->terms()->get( $old_term_id );
									$this->debug( 'Found new archive taxonomy: %s', $new_term_id );
									$parts[ 2 ] = $new_term_id;
								}
							break;
						}
					break;
					case 'singular':
						switch( $parts[ 1 ] )
						{
							case 'post_type':
								switch( $parts[ 3 ] )
								{
									case 'children':
										// singular : post_type : page : children : id : 220
										$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $parts[ 5 ], get_current_blog_id() );
										$this->debug( 'Found new singular post_type children id %s', $new_id );
										$parts[ 5 ] = $new_id;
									break;
									case 'id':
										// singular:post_type:page:id:236
										$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $parts[ 4 ], get_current_blog_id() );
										$this->debug( 'Found new singular post_type id %s', $new_id );
										$parts[ 4 ] = $new_id;
									break;
								}
							break;
							case 'taxonomy':
								switch( $parts[ 4 ] )
								{
									case 'id':
										// singular:taxonomy:category:term:id:10
										$old_term_id = intval( $parts[ 5 ] );
										$new_term_id = $bcd->terms()->get( $old_term_id );
										$this->debug( 'Found new singular taxonomy: %s', $new_term_id );
										$parts[ 5 ] = $new_term_id;
									break;
								}
							break;
					break;
						}
					break;
				}
				$parts = implode( ':', $parts );
				$et_use_on[ $use_index ] = $parts;
			}
			$bcd->custom_fields()->child_fields()->update_metas( $key, $et_use_on );
		}

		// Update the template index.
		$builder_post_id = et_theme_builder_get_theme_builder_post_id( true, false );
		$key = '_et_template';
		$template_ids = get_post_meta( $builder_post_id, $key, false );
		$template_ids []= $bcd->new_post( 'ID' );
		$template_ids = array_map( 'intval', $template_ids );
		$template_ids = array_flip( $template_ids );
		$template_ids = array_flip( $template_ids );
		$this->debug( 'Updating %s with %s', $key, $template_ids );
		delete_post_meta( $builder_post_id, $key );
		foreach( $template_ids as $template_id )
			add_post_meta( $builder_post_id, $key, $template_id );
	}

	/**
		@brief		save_et_template
		@since		2021-01-27 22:00:48
	**/
	public function save_et_template( $bcd )
	{
		$key = '_et_use_on';
		$et_use_on = $bcd->custom_fields()->get( $key );
		if ( $et_use_on )
		{
			foreach( $et_use_on as $use_index => $use_on )
			{
				$parts = explode( ':', $use_on );
				switch( $parts[ 0 ] )
				{
					case 'archive':
						switch( $parts[ 1 ] )
						{
							case 'taxonomy':
								$bcd->taxonomies()
									->also_sync( null, $parts[ 2 ] )
									->use_term( $parts[ 2 ] );
							break;
						}
					break;
					case 'singular':
						switch( $parts[ 1 ] )
						{
							case 'taxonomy':
								// singular:taxonomy:category:term:id:10
								$bcd->taxonomies()->also_sync( null, $parts[ 5 ] );
								if ( $parts[ 4 ] == 'id' )
									$bcd->taxonomies()->use_term( $parts[ 5 ] );
							break;
						}
					break;
				}
				$parts = implode( ':', $parts );
				$et_use_on[ $use_index ] = $parts;
			}
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2020-08-10 19:29:25
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'et_template' )
			$this->restore_et_template( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-01-27 22:00:05
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'et_template' )
			$this->save_et_template( $bcd );

		$this->disable_et_pb_force_regenerate_templates();
	}

	/**
		@brief		Modify the encoded button links.
		@since		2022-03-12 07:10:21
	**/
	public function threewp_broadcast_parse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		foreach( static::$encoded_url_regexps as $regexp )
		{
			$this->debug( 'Looking for regexp %s', $regexp );
			$matches = [];
			preg_match_all( $regexp, $content, $matches, PREG_PATTERN_ORDER );
			foreach( $matches[ 1 ] as $base64_encoded )
			{
				$old_json_encoded = base64_decode( $base64_encoded );
				$old_settings = json_decode( $old_json_encoded );
				if ( ! is_object( $old_settings ) )
					continue;
				if ( ! isset( $old_settings->settings ) )
					continue;
				if ( ! isset( $old_settings->settings->post_id ) )
					continue;
				$old_post_id = $old_settings->settings->post_id;

				// First try handling this "post_id" as an attachment, since Divi treats them both the same way.
				$new_post_id  = $bcd->copied_attachments()->get( $old_post_id );

				// If this was not an attachment, try get the post.
				if ( ! $new_post_id )
					$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
				$old_settings->settings->post_id = $new_post_id;
				$new_json_encoded = json_encode( $old_settings );
				$new_base64_encoded = base64_encode( $new_json_encoded );
				$this->debug( "Replacing %s (%s) with %s (%s)", $base64_encoded, $old_json_encoded, $new_base64_encoded, $new_json_encoded);
				$content = str_replace( $base64_encoded, $new_base64_encoded, $content );
			}
		}
		$action->content = $content;
	}

	/**
		@brief		Parse the encoded button links.
		@since		2022-03-12 07:09:54
	**/
	public function threewp_broadcast_preparse_content( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.
		$content = $action->content;			// Also very convenient.

		foreach( static::$encoded_url_regexps as $regexp )
		{
			$this->debug( 'Looking for regexp %s', $regexp );
			$matches = [];
			preg_match_all( $regexp, $content, $matches, PREG_PATTERN_ORDER );

			if ( count( $matches[ 1 ] ) < 1 )
				continue;

			$this->debug( 'Found %s', $matches[ 1 ] );
			foreach( $matches[ 1 ] as $base64_encoded )
			{
				$old_json_encoded = base64_decode( $base64_encoded );
				$old_settings = json_decode( $old_json_encoded );
				if ( ! is_object( $old_settings ) )
					continue;
				if ( ! isset( $old_settings->settings ) )
					continue;
				if ( ! isset( $old_settings->settings->post_id ) )
					continue;
				$old_post_id = $old_settings->settings->post_id;
				// The links sometimes point to posts, sometimes to attachments.
				$bcd->try_add_attachment( $old_post_id );
			}
		}
	}

	/**
		@brief		Tabs for the UI.
		@since		2020-08-10 19:00:32
	**/
	public function ui_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'ui_templates' )
			->callback_this( 'ui_templates' )
			->heading( 'Broadcast Templates' )
			->name( 'Templates' );

		echo $tabs->render();
	}

	/**
		@brief		ui_templates
		@since		2020-08-10 19:01:42
	**/
	public function ui_templates()
	{
		$form = $this->form();
		$r = '';

		$templates = et_theme_builder_get_theme_builder_templates( true );
		$template_opts = [];
		foreach( $templates as $template )
		{
			$template_id = $template[ 'id' ];
			$title = $template[ 'title' ];
			if ( ! $title )
			{
				$post = get_post( $template_id );
				$title = $post->post_title;
			}

			$template_opts[ $template_id ] = $title;
		}

		$templates_input = $form->select( 'templates_input' )
			->description( __( 'Select which templates you wish to broadcast.', 'threewp_broadcast' ) )
			->label( __( 'Templates', 'threewp_broadcast' ) )
			->multiple()
			->opts( $template_opts )
			->required();

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to broadcast the template.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'name' => 'blogs',
			'required' => false,
		] );

		$go = $form->primary_button( 'go' )
			// Button
			->value( __( 'Broadcast selected templates', 'threewp_broadcast' ) );


		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();
			$messages = [];

			$template_ids = $templates_input->get_post_value();
			$blog_ids = $blogs_select->get_post_value();

			foreach( $template_ids as $template_id )
			{
				$this->debug( 'Broadcasting template %s to %s', $template_id, $blog_ids );
				ThreeWP_Broadcast()->api()->broadcast_children( $template_id, $blog_ids );
			}

			$r .= $this->info_message_box()->_( 'The selected templates have been broadcasted!' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}
}
