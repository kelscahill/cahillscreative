<?php

namespace threewp_broadcast\premium_pack\toolset;

/**
	@brief				Adds support for OnTheGoSystems' Toolset plugins: CRED, Types and Views.
	@plugin_group		3rd party compatability
	@since				2016-07-08 14:21:53
**/
class Toolset
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_option_ui_trait;
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	const CONTENT_TEMPLATE_CUSTOM_FIELD = '_views_template';

	/**
		@brief		The view loop ID custom field name.
		@since		2017-10-05 16:21:49
	**/
	const VIEW_LOOP_ID_CUSTOM_FIELD = '_view_loop_id';

	public function _construct()
	{
		$this->add_action( 'broadcast_php_code_load_wizards' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_finished' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_post_action' );
		$this->add_filter( 'toolset_filter_register_menu_pages', 100 );
		$this->add_action( 'types_save_post_hook' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		broadcast_php_code_load_wizards
		@since		2017-09-08 20:45:47
	**/
	public function broadcast_php_code_load_wizards( $action )
	{
		// The name of the PHP code wizard group.
		$action->add_group( '3rdparty', __( '3rd party plugins', 'threewp_broadcast' ) );

		$run_bulk_action_wizard = $action->wizards->get( 'run_bulk_action' );

		// Clone this wizard to create our own.
		$clone = serialize( $run_bulk_action_wizard );
		$clone = unserialize( $clone );

		$clone->set( 'group', '3rdparty' );
		$clone->set( 'id', 'toolset_find_unlinked' );
		$clone->set( 'label', __( 'Toolset - find all unlinked Toolset forms', 'threewp_broadcast' ) );

		$code = $clone->code()->get( 'setup' );
		$code = str_replace( "[ 'post' ]", "[ 'cred-form', 'cred-user-form', 'view-template', 'view' ]", $code );
		$code = $clone->code()->set( 'setup', $code );

		$action->add_wizard( $clone );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2016-03-10 13:43:45
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->toolset ) )
			return;

		if ( $this->has_types() )
			$this->maybe_restore_type( $action );
		$this->maybe_restore_content_template( $action );
		$this->maybe_restore_view( $action );
	}

	/**
		@brief		Restore the children.
		@since		2016-03-09 08:07:38
	**/
	public function threewp_broadcast_broadcasting_finished( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->toolset_types ) )
			return $this->debug( 'Nothing to do.' );

		if ( ! $bcd->toolset_types->has( 'wpcf_post_relationship' ) )
			return;

		// Collect an array of all of the blogs where we are to broadcast the children.
		$blogs = [];
		foreach( $bcd->blogs as $blog )
			$blogs []= $blog->id;

		// Broadcast each child to the blogs.
		foreach( $bcd->toolset_types->get( 'wpcf_post_relationship' ) as $post_id => $ignore )
			ThreeWP_Broadcast()->api()->broadcast_children( $post_id, $blogs );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->toolset ) )
			$bcd->toolset = ThreeWP_Broadcast()->collection();

		if ( $this->has_types() )
		{
			$this->debug( 'Unhooking Types...' );
			remove_action( 'add_attachment', 'wpcf_admin_save_attachment_hook', 10 );
			remove_action( 'add_attachment', 'wpcf_admin_add_attachment_hook', 10 );
			remove_action( 'edit_attachment', 'wpcf_admin_save_attachment_hook', 10 );
			remove_action( 'save_post', 'wpcf_admin_save_post_hook', 10, 2 );
			remove_action( 'save_post', 'wpcf_fields_checkbox_save_check', 100, 1 );
			remove_action( 'save_post', 'wpcf_pr_admin_save_post_hook', 20, 2 ); // Trigger afer main hook
			$this->maybe_save_type( $action );
		}
		if ( $this->has_views() )
		{
			$this->maybe_save_content_template( $action );
			$this->maybe_save_view( $action );
		}
	}

	/**
		@brief		We have to unhook Toolset otherwise it causes fatal errors.
		@since		2019-09-20 19:32:11
	**/
	public function threewp_broadcast_post_action( $action )
	{
		$this->debug( 'Removing all actions.' );
		remove_all_actions( 'before_delete_post' );
		remove_all_actions( 'delete_attachment' );
	}

	/**
		@brief		Add us to the menu.
		@since		2016-07-06 13:21:23
	**/
	public function toolset_filter_register_menu_pages( $pages )
	{
		$pages[] = [
		'slug'			=> 'broadcast',
		// Menu item for menu
		'menu_title'	=> __( 'Broadcast', 'threewp_broadcast' ),
		// Page title for menu
		'page_title'	=> __( 'Broadcast', 'threewp_broadcast' ),
		'callback'		=> [ $this, 'broadcast_toolset_tabs' ],
		];

		return $pages;
	}

	/**
		@brief		broadcast_toolset_tabs
		@since		2017-10-13 15:20:02
	**/
	public function broadcast_toolset_tabs()
	{
		$tabs = $this->tabs();

		if ( $this->has_views() )
		{
			$tabs->tab( 'content_templates' )
				->callback_this( 'broadcast_content_templates' )
				// Page heading
				->heading( __( 'Broadcast Content Templates', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Content Templates', 'threewp_broadcast' ) );

			$tabs->tab( 'views' )
				->callback_this( 'broadcast_views' )
				// Page heading
				->heading( __( 'Broadcast Views', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Views', 'threewp_broadcast' ) );
		}

		if ( $this->has_types() )
		{
			$tabs->tab( 'field_groups' )
				->callback_this( 'broadcast_field_groups' )
				// Page heading
				->heading( __( 'Broadcast Field Groups', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Field Groups', 'threewp_broadcast' ) );

			$tabs->tab( 'post_types' )
				->callback_this( 'broadcast_post_types' )
				// Page heading
				->heading( __( 'Broadcast Post Types', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Post Types', 'threewp_broadcast' ) );

			$tabs->tab( 'taxonomies' )
				->callback_this( 'broadcast_taxonomies' )
				// Page heading
				->heading( __( 'Broadcast Taxonomies', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Taxonomies', 'threewp_broadcast' ) );
		}

		if ( $this->has_cred() )
		{
			$tabs->tab( 'post_forms' )
				->callback_this( 'broadcast_post_forms' )
				// Page heading
				->heading( __( 'Broadcast Post Forms', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'Post Forms', 'threewp_broadcast' ) );

			$tabs->tab( 'user_forms' )
				->callback_this( 'broadcast_user_forms' )
				// Page heading
				->heading( __( 'Broadcast User Forms', 'threewp_broadcast' ) )
				// Tab name
				->name( __( 'User Forms', 'threewp_broadcast' ) );
		}

		echo $tabs->render();
	}

	/**
		@brief		This action tells Broadcast that Types is done saving the children.
		@since		2016-03-08 23:14:28
	**/
	public function types_save_post_hook( $post_id )
	{
		ThreeWP_Broadcast()->save_post( $post_id );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save the content template data.
		@since		2017-10-05 12:51:22
	**/
	public function maybe_save_content_template( $action )
	{
		$bcd = $action->broadcasting_data;

		$view_loop_id = $bcd->custom_fields()->get_single( self::VIEW_LOOP_ID_CUSTOM_FIELD );
		if ( $view_loop_id > 0 )
		{
			$view_loop_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $view_loop_id );
			$this->debug( 'View loop %d found: %s', $view_loop_id, $view_loop_bcd );
			$bcd->toolset->collection( 'view_loop' )
				->set( 'id', $view_loop_id )
				->set( 'bcd', $view_loop_bcd );
		}
	}

	/**
		@brief		Maybe save the Types data.
		@since		2016-03-08 23:11:29
	**/
	public function maybe_save_type( $action )
	{
		if ( ! $this->has_types() )
			return $this->debug( 'Types not installed.' );

		$bcd = $action->broadcasting_data;

		$bcd->toolset_types = ThreeWP_Broadcast()->collection();

		// Look through the custom fields to see whether this post has a parent somewhere.
		foreach( $bcd->custom_fields->original as $key => $value )
		{
			if ( strpos( $key, '_wpcf_belongs_' ) !== 0 )
				continue;
			$this->debug( 'Saving link to parent post %s', $parent_post_id );
			$parent_post_id = reset( $value );
			$bcd->toolset_types->set( 'belongs_key', $key );
			$bcd->toolset_types->set( 'belongs_value', $parent_post_id );
			$bcd->toolset_types->set( 'belongs_bcd', ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $parent_post_id ) );
		}

		if ( ! isset( $bcd->_POST[ 'wpcf_post_relationship' ] ) )
			return $this->debug( 'No wpcf post relationship found.' );

		// We have to look at the _POST, since the relationship info isn't saved in the custom fields of the parent post, but the postmeta of the child post(s).
		foreach( $bcd->_POST[ 'wpcf_post_relationship' ] as $parent_post_id => $child_posts )
			if ( $parent_post_id == $bcd->post->ID )
				$this->save_parent_post( $bcd );
	}

	/**
		@brief		Save the view's content template.
		@since		20131007
	**/
	public function maybe_save_view( $action )
	{
		$bcd = $action->broadcasting_data;

		// Is there a _views_template custom field?
		if ( ! $bcd->custom_fields()->has( self::CONTENT_TEMPLATE_CUSTOM_FIELD ) )
			return $this->debug( 'No template custom field found for this view.' );

		$content_template_id = $bcd->custom_fields()->get_single( self::CONTENT_TEMPLATE_CUSTOM_FIELD );

		if ( $content_template_id < 1 )
			return;

		$this->debug( 'The template ID is %s', $content_template_id );

		// Save the template (post) data
		$post= get_post( $content_template_id );

		$bcd->toolset->collection( 'content_template' )->set( 'post', $post );
	}

	/**
		@brief		Broadcast the parent post.
		@since		2016-03-10 09:48:51
	**/
	public function save_parent_post( $bcd )
	{
		$relationships = $bcd->_POST[ 'wpcf_post_relationship' ];
		$this->debug( 'The relationship array is: %s', $relationships );

		$post_id = $bcd->post->ID;
		if ( ! isset( $relationships[ $post_id ] ) )
			return $this->debug( 'This post has no relationships.' );

		// We want the relationships for just this parent post.
		$relationships = $relationships[ $post_id ];

		$bcd->toolset_types->set( 'wpcf_post_relationship', $relationships );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Restore the content template data.
		@since		2017-10-05 12:52:08
	**/
	public function maybe_restore_content_template( $action )
	{
		$bcd = $action->broadcasting_data;

		$vl = $bcd->toolset->collection( 'view_loop' );
		$view_loop_id = $vl->get( 'id' );
		if ( $view_loop_id > 0 )
		{
			$view_loop_bcd = $vl->get( 'bcd' );
			$child_view_loop_id = $view_loop_bcd->get_linked_post_on_this_blog();
			if ( $child_view_loop_id > 0 )
			{
				$this->debug( 'New view_loop_id is %d', $child_view_loop_id );
				$bcd->custom_fields()
					->child_fields()
					->update_meta( self::VIEW_LOOP_ID_CUSTOM_FIELD, $child_view_loop_id );
			}
			else
				$this->debug( 'No view loop found on this blog.' );
		}
	}

	/**
		@brief		Restore the type.
		@since		2016-07-08 14:47:33
	**/
	public function maybe_restore_type( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->toolset_types ) )
			return $this->debug( 'Nothing to do.' );

		if ( ! $bcd->toolset_types->has( 'belongs_key' ) )
			return;

		$meta_key = $bcd->toolset_types->get( 'belongs_key' );
		$parent_bcd = $bcd->toolset_types->get( 'belongs_bcd' );
		$new_parent_post_id = $parent_bcd->get_linked_child_on_this_blog();
		$this->debug( 'Replacing link to old parent %s with new parent %s.', $bcd->toolset_types->get( 'belongs_value' ), $new_parent_post_id );
		$bcd->custom_fields()->child_fields()->update_meta( $meta_key, $new_parent_post_id );
	}

	/**
		@brief		Restore the view.
		@since		20131007
	**/
	public function maybe_restore_view( $action )
	{
		$bcd = $action->broadcasting_data;

		$post = $bcd->toolset->collection( 'content_template' )->get( 'post' );

		if ( ! $post )
			return;

		$this->debug( 'Looking for a post named %s, with the %s post type.', $post->post_name, $post->post_type );

		// Find the post on this blog with the content template name.
		$args = array(
			'name' => $post->post_name,
			'numberposts' => 1,
			'post_type'=> $post->post_type,
		);
		$template = get_posts( $args );

		$this->debug( '%s posts found.', count( $template ) );
		// Was the template with the exact same name found on this blog?
		if ( count( $template ) != 1 )
		{
			// There is no equivalent template. Remove the custom field.
			$bcd->custom_fields()
				->child_fields()
				->delete_meta( self::CONTENT_TEMPLATE_CUSTOM_FIELD );
			return $this->debug( 'No equivalent content template found.' );
		}

		// We want the first (and only) result.
		$template = reset( $template );
		$this->debug( 'Equivalent content template ID is %s', $template->ID );

		// Update the ID of the view on this blog.
		$bcd->custom_fields()
			->child_fields()
			->update_meta( self::CONTENT_TEMPLATE_CUSTOM_FIELD, $template->ID );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Broadcast post forms to other blogs.
		@since		2016-07-08 20:42:30
	**/
	public function broadcast_content_templates()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'view-template',
			'label_plural' => 'content templates',
			'label_singular' => 'content template',
		] );
	}

	/**
		@brief		Broadcast field groups manually.
		@details	Cannot be generic since it uses options AND posts.
		@since		2016-07-07 14:26:28
	**/
	public function broadcast_field_groups()
	{
		$form = $this->form2();
		$post_type = 'wp-types-group';
		$r = '';

		$items_select = $form->select( 'field_groups' )
			->description( 'Select the field groups to broadcast to the selected blogs.' )
			->label( 'Field groups to broadcast' )
			->multiple()
			->size( 10 )
			->required();

		// Display a select with all of the types on this blog.
		$items = get_posts( [
			'posts_per_page' => -1,
			'post_type' => $post_type,
		] );
		$items = $this->array_rekey( $items, 'post_name' );
		foreach( $items as $item )
			$items_select->option( sprintf( '%s (%s)', $item->post_title, $item->ID ), $item->post_name );

		$blogs_select = $this->add_blog_list_input( [
			// Blog selection input description
			'description' => __( 'Select one or more blogs to which to copy the selected items above.', 'threewp_broadcast' ),
			'form' => $form,
			// Blog selection input label
			'label' => __( 'Blogs', 'threewp_broadcast' ),
			'multiple' => true,
			'required' => true,
			'name' => 'blogs',
		] );

		$fs = $form->fieldset( 'fs_actions' );
		// Fieldset label
		$fs->legend->label( __( 'Actions', 'threewp_broadcast' ) );

		$nonexisting_action = $fs->select( 'nonexisting_action' )
			// Input title
			->description( __( 'What to do if the field group does not exist on the target blog.', 'threewp_broadcast' ) )
			// Input label
			->label( __( 'If the field group does not exist', 'threewp_broadcast' ) )
			->options( [
				// What to do if the Toolset field group does not exist.
				__( 'Create the field group', 'threewp_broadcast' ) => 'create',
				// What to do if the Toolset field group does not exist.
				__( 'Skip this blog', 'threewp_broadcast' ) => 'skip',
			] )
			->value( 'create' );

		$existing_action = $fs->select( 'existing_action' )
			->description( 'What to do if the field group already exists on the target blog.' )
			->label( 'If the field group exists' )
			->options( [
				// What to do if the Toolset field group already exists
				__( 'Skip this blog', 'threewp_broadcast' ) => 'skip',
				// What to do if the Toolset field group already exists
				__( 'Overwrite the existing field group', 'threewp_broadcast' ) => 'overwrite',
			] )
			->value( 'overwrite' );

		$submit = $form->primary_button( 'copy' )
			->value( 'Copy' );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			$source_fields = get_option( 'wpcf-fields' );

			// We need to find out which group fields are used.
			$post_fields = ThreeWP_Broadcast()->collection();
			foreach( $items_select->get_post_value() as $item_slug )
			{
				$item = $items[ $item_slug ];
				$item_id = $item->ID;
				$_wp_types_group_fields = get_post_meta( $item_id, '_wp_types_group_fields', true );
				$_wp_types_group_fields = maybe_unserialize( $_wp_types_group_fields );
				$this->debug( 'Item %s has field groups: %s', $item_slug, $_wp_types_group_fields );
				$post_fields->set( $item_slug, $_wp_types_group_fields );
			}

			foreach ( $blogs_select->get_post_value() as $blog_id )
			{
				// Don't copy the type to ourself.
				if ( $blog_id == get_current_blog_id() )
					continue;
				switch_to_blog( $blog_id );

				$blog_items = get_posts( [
					'posts_per_page' => -1,
					'post_type' => $post_type,
				] );
				$blog_items = $this->array_rekey( $blog_items, 'post_name' );

				foreach( $items_select->get_post_value() as $item_slug )
				{
					$broadcast = false;
					if ( ! isset( $blog_items[ $item_slug ] ) )
					{
						$this->debug( 'Item %s not found on blog %s.', $item_slug, $blog_id );
						if ( $nonexisting_action->get_post_value() == 'create' )
						{
							$this->debug( 'Creating item %s.', $item_slug );
							$broadcast = true;
						}
					}
					else
					{
						$this->debug( 'Item %s found on blog %s.', $item_slug, $blog_id );
						if ( $existing_action->get_post_value() == 'overwrite' )
						{
							$this->debug( 'Overwriting item %s.', $item_slug );
							$broadcast = true;
						}
					}

					if ( $broadcast )
					{
						$original_post_id = $items[ $item_slug ]->ID;
						restore_current_blog();
						$this->debug( 'Broadcasting item %s.', $original_post_id );
						ThreeWP_Broadcast()->api()->broadcast_children( $original_post_id, [ $blog_id ] );
						switch_to_blog( $blog_id );

						// Update the fields, if necessary.
						$target_fields = get_option( 'wpcf-fields' );
						if ( ! is_array( $target_fields ) )
							$target_fields = [];
						$this->debug( 'Current target fields: %s', $target_fields );

						$item_fields = $post_fields->get( $item_slug );
						$item_fields = explode( ',', $item_fields );
						$item_fields = array_filter( $item_fields );

						foreach( $item_fields as $item_field )
							$target_fields[ $item_field ] = $source_fields[ $item_field ];

						$this->debug( 'New target fields after merge: %s', $target_fields );
						update_option( 'wpcf-fields', $target_fields );
					}
				}

				restore_current_blog();
			}
			$r .= $this->info_message_box()->_( 'The selected items have been copied to the selected blogs.' );
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Broadcast post forms to other blogs.
		@since		2016-07-08 20:42:30
	**/
	public function broadcast_post_forms()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cred-form',
			'post_status' => 'private',
			'label_plural' => 'post forms',
			'label_singular' => 'post form',
		] );
	}

	/**
		@brief		Broadcast taxonomies.
		@since		2017-08-16
	**/
	public function broadcast_taxonomies()
	{
		echo $this->broadcast_generic_option_ui( [
			'option_name' => 'wpcf-custom-taxonomies',
			'label_plural' => 'taxonomies',
			'label_singular' => 'taxonomy',
		] );
	}

	/**
		@brief		Broadcast Post Types to other blogs manually.
		@since		2016-06-13 13:33:09
	**/
	public function broadcast_post_types()
	{
		echo $this->broadcast_generic_option_ui( [
			'option_name' => 'wpcf-custom-types',
			'label_plural' => 'post types',
			'label_singular' => 'post type',
		] );
		return;
	}

	/**
		@brief		Broadcast user forms to other blogs.
		@since		2016-07-08 20:42:30
	**/
	public function broadcast_user_forms()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'cred-user-form',
			'post_status' => 'private',
			'label_plural' => 'user forms',
			'label_singular' => 'user form',
		] );
	}

	/**
		@brief		Broadcast views to other blogs manually.
		@since		2016-06-13 13:33:09
	**/
	public function broadcast_views()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => 'view',
			'label_plural' => 'views',
			'label_singular' => 'view',
		] );
	}

	/**
		@brief		Is Cred installed?
		@since		2016-07-08 20:17:15
	**/
	public function has_cred()
	{
		return defined( 'CRED_FE_VERSION' );
	}

	/**
		@brief		Is the types plugin installed?
		@since		2016-07-08 14:27:43
	**/
	public function has_types()
	{
		return defined( 'WPCF_VERSION' );
	}

	/**
		@brief		Is the views plugin installed?
		@since		2016-07-08 14:31:34
	**/
	public function has_views()
	{
		return defined( 'WPV_VERSION' );
	}
}
