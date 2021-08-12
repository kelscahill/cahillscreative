<?php

namespace threewp_broadcast\premium_pack\divi_builder;

/**
	@brief				Adds support for <a href="https://www.elegantthemes.com/plugins/divi-builder/">Divi Builder</a> and themes using it.
	@plugin_group		3rd party compatability
	@since				2016-11-10 21:33:22
**/
/**
	@brief
**/
class Divi_Builder
	extends \threewp_broadcast\premium_pack\base
{
	public function _construct()
	{
		$this->add_action( 'admin_menu', 100 );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		new Global_Module();
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
		@since		2016-11-10 21:33:22
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$this->debug( 'Disabling et_pb_force_regenerate_templates.' );
		remove_action( 'created_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'edited_term', 'et_pb_force_regenerate_templates' );
		remove_action( 'delete_term', 'et_pb_force_regenerate_templates' );
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
