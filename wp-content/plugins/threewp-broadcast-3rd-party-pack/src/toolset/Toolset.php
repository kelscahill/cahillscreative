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
	use \threewp_broadcast\premium_pack\classes\database_trait;

	const CONTENT_TEMPLATE_CUSTOM_FIELD = '_views_template';

	/**
		@brief		The view loop ID custom field name.
		@since		2017-10-05 16:21:49
	**/
	const VIEW_LOOP_ID_CUSTOM_FIELD = '_view_loop_id';

	public static $association_types = [ 'child_id', 'parent_id', 'intermediary_id' ];

	public function _construct()
	{
		$this->add_action( 'broadcast_php_code_load_wizards' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_post_action' );
		$this->add_filter( 'toolset_filter_register_menu_pages', 100 );
		$this->add_action( 'types_save_post_hook' );
		$this->wp_types_group = new wp_types_group();
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

		$this->maybe_restore_content_template( $action );
		$this->maybe_restore_relationships( $action );
		$this->maybe_restore_view( $action );
	}

	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;
		if ( ! isset( $bcd->toolset ) )
			$bcd->toolset = ThreeWP_Broadcast()->collection();

		if ( $this->has_types() )
		{
			$this->maybe_save_relationships( $action );
			$this->debug( 'Unhooking Types...' );
			remove_action( 'add_attachment', 'wpcf_admin_save_attachment_hook', 10 );
			remove_action( 'add_attachment', 'wpcf_admin_add_attachment_hook', 10 );
			remove_action( 'edit_attachment', 'wpcf_admin_save_attachment_hook', 10 );
			remove_action( 'save_post', 'wpcf_admin_save_post_hook', 10, 2 );
			remove_action( 'save_post', 'wpcf_fields_checkbox_save_check', 100, 1 );
			remove_action( 'save_post', 'wpcf_pr_admin_save_post_hook', 20, 2 ); // Trigger after main hook
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
		@brief		Save the relationships.
		@since		2020-10-21 22:12:48
	**/
	public function maybe_save_relationships( $action )
	{
		$bcd = $action->broadcasting_data;
		$ts = $bcd->toolset;	// Conv.

		// Find the group ID for this post.
		$connected_element = $this->get_connected_element_via_element_id( $bcd->post->ID );
		if ( ! $connected_element )
			return;
		$this->debug( 'Connected element for this element: %s', $connected_element );
		$ts->set( 'group', $connected_element );

		$group_id = $connected_element->group_id;
		$ts->set( 'group_id', $group_id );
		$this->debug( 'Group ID is %s', $group_id );

		// Find all associations with this group ID.
		$associations = $this->get_relationship_associations( $group_id );
		$this->debug( 'Relationship associations: %s', $associations );

		// And now that we know of all associations, we need to save the connection ID of each assocation.
		$groups = ThreeWP_Broadcast()->collection();
		foreach( $associations as $association_id => $association )
		{
			if ( $this->is_broadcasting_relationship( $association->relationship_id ) )
			{
				$this->debug( 'We are already broadcasting relationship %s', $association->relationship_id );
				unset( $associations->$association_id );
				continue;
			}
			foreach( static::$association_types as $type )
			{
				$id = $association->$type;
				if ( $id < 1 )
					continue;
				if ( $groups->has( $id ) )
					continue;
				$group = $this->get_relationship_group_via_group_id( $id );
				$this->debug( 'For %s %s, getting %s', $type, $id, $group );
				$groups->set( $id, $group );
			}
		}

		$relationship_ids = $this->array_rekey( $associations, 'relationship_id' );
		// And now save all relationships that are used.
		$relationships = $this->get_relationships_by_ids( $relationship_ids );
		$relationships = $this->array_rekey( $relationships, 'slug' );

		$this->debug( 'Parent post associations: %s', $associations );
		$this->debug( 'Parent groups: %s', $groups );
		$this->debug( 'Parent relationships: %s', $relationships );

		$ts->set( 'associations', $associations );
		$ts->set( 'groups', $groups );
		$ts->set( 'relationships', $relationships );
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
		@brief		Restore the relationship data.
		@since		2020-10-21 22:11:23
	**/
	public function maybe_restore_relationships( $action )
	{
		$sync = apply_filters( 'broadcast_toolset_restore_relationships', true );
		if ( ! $sync )
			return;

		$bcd = $action->broadcasting_data;
		$ts = $bcd->toolset;	// Conv.

		// No parent associations? Can't do anything.
		$associations = $ts->get( 'associations' );
		if ( ! $associations )
			return;

		// Delete all existing associations.
		$group = $this->get_connected_element_via_element_id( $bcd->new_post( 'ID' ) );
		// Might not have any yet.
		$this->debug( 'Should we delete existing associations? %s', $group );
		if ( $group )
		{
			// Remove the group from all assocations of the same type.
			// We are going to put them back later.
			$parent_group_id = $ts->get( 'group_id' );
			$group_id = $group->group_id;
			foreach( static::$association_types as $type )
			{
				foreach( $associations as $association )
				{
					if ( $association->$type != $parent_group_id )
						continue;
					$this->debug( 'Deleting assocation type %s for group %s', $type, $group->group_id );
					$this->delete_association_type( $type, $group->group_id );
				}
			}
		}

		$parent_relationships = $ts->get( 'relationships' );
		$parent_relationship_ids = $this->array_rekey( $parent_relationships, 'id' );
		$child_relationships = $this->get_relationships_by_slugs( array_keys( $parent_relationships ) );
		$child_relationships = $this->array_rekey( $child_relationships, 'slug' );
		$this->debug( 'Child relationships: %s', $child_relationships );

		// Restore the main group.
		$parent_group = $ts->get( 'group' );
		$parent_group->element_id = $bcd->new_post( 'ID' );

		$group = $this->create_group( $parent_group );
		$this->debug( 'Main group is %s', $group );

		// To prevent recursion.
		foreach( $associations as $association )
			$this->broadcasting_relationship( $association->relationship_id );

		// Restore the groups.
		$parent_groups = $ts->get( 'groups' );
		$child_blog_groups = ThreeWP_Broadcast()->collection();
		foreach( $parent_groups as $parent_group )
		{
			if ( ! isset( $parent_group->group_id ) )
			{
				$this->debug( 'Parent group is not set properly: %s', $parent_group );
				continue;
			}
			$parent_group_id = $parent_group->group_id;
			$parent_element_id = $parent_group->element_id;
			$this->debug( 'Getting equivalent element ID for %s', $parent_element_id );

			if ( $parent_element_id == $bcd->post->ID )
				$child_element_id = $bcd->new_post( 'ID' );
			else
				$child_element_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $parent_element_id );

			$this->debug( 'Got equivalent element ID for %s: %s', $parent_element_id, $child_element_id );

			$child_group = clone( $parent_group );
			$child_group->element_id = $child_element_id;

			// And now create the group on this blog.
			$child_group = $this->create_group( $child_group );
			$child_blog_groups->set( $parent_group_id, $child_group );
		}

		$this->debug( 'New child groups: %s', $child_blog_groups );

		foreach( $associations as $association )
			$this->not_broadcasting_relationship( $association->relationship_id );

		// And now insert the new associations.
		foreach( $associations as $association )
		{
			$new_association = clone( $association );
			unset( $new_association->id );

			// Fix the relationship.
			$old_relationship_id = $association->relationship_id;
			$relationship_slug = $parent_relationship_ids[ $old_relationship_id ]->slug;

			if ( ! isset( $child_relationships[ $relationship_slug ] ) )
			{
				$this->debug( 'Warning! No relationship called %s.', $relationship_slug );
				continue;
			}
			$new_relationship_id = $child_relationships[ $relationship_slug ]->id;
			$new_association->relationship_id = $new_relationship_id;

			$found = false;
			foreach( static::$association_types as $type )
			{
				$old_group_id = $association->$type;
				if ( $old_group_id < 1 )
					continue;
				$child_group = $child_blog_groups->get( $old_group_id );
				if ( ! $child_group )
					continue;
				$found = true;
				$new_group_id = $child_group->group_id;
				$new_association->$type = $new_group_id;
			}
			if ( $found )
			{
				$this->create_association( $new_association );
				$this->debug( 'New association: %s', $new_association );
			}
		}
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
			$items_select->option( sprintf( '%s (%s)', $item->post_title, $item->ID ), $item->ID );

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

		$submit = $form->primary_button( 'copy' )
			->value( 'Copy' );

		if ( $form->is_posting() )
		{
			$form->post()->use_post_values();

			$blog_ids = $blogs_select->get_post_value();
			foreach( $items_select->get_post_value() as $item_id )
				ThreeWP_Broadcast()->api()->broadcast_children( $item_id, $blog_ids );
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
		@brief		Note that we are broadcasting this relationship.
		@details	To prevent recursion.
		@since		2021-04-22 18:39:24
	**/
	public function broadcasting_relationship( $relationship_id )
	{
		if ( ! isset( $this->__broadcasting_relationships ) )
			$this->__broadcasting_relationships = ThreeWP_Broadcast()->collection();
		$this->__broadcasting_relationships->set( $relationship_id, $relationship_id );
		$this->debug( 'We are currently broadcasting relationships: %s', implode( $this->__broadcasting_relationships->to_array() ) );
		return $this;
	}

	/**
		@brief		Create an association.
		@since		2020-11-04 12:44:51
	**/
	public function create_association( $association )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_associations';
		$wpdb->insert( $table, (array) $association );
		$association->id = $wpdb->insert_id;
	}

	/**
		@brief		Get or create a group for this post.
		@since		2020-11-03 21:40:49
	**/
	public function create_group( $group )
	{
		$child_group = $this->get_connected_element_via_element_id( $group->element_id );
		if ( $child_group )
			return $child_group;

		global $wpdb;
		$data = (array) $group;
		unset( $data[ 'id' ] );
		unset( $data[ 'group_id' ] );

		if ( function_exists( 'wpml_get_content_trid' ) )
		{
			$post = get_post( $group->element_id );
			$type = 'post_' . $post->post_type;
			$data[ 'wpml_trid' ] = wpml_get_content_trid( $type, $group->element_id );
			$this->debug( 'WPML detected. Fetching trid for %s %s: %s', $type, $group->element_id, $data[ 'wpml_trid' ] );
		}

		$table = $wpdb->prefix . 'toolset_connected_elements';

		// Find the current max.
		$query = sprintf( "SELECT MAX( `group_id` ) FROM `%s`", $table );
		$this->debug( $query );
		$max = $wpdb->get_var( $query );
		$max++;

		$data[ 'group_id' ] = $max;
		$this->debug( 'Creating group %s', $data );
		$result = $wpdb->insert( $table, $data );

		return $this->get_connected_element_via_element_id( $group->element_id );
	}

	/**
		@brief		Delete associations where this group_id is the type.
		@since		2021-04-20 21:21:09
	**/
	public function delete_association_type( $type, $group_id )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_associations';
		$query = sprintf( "DELETE FROM `%s` WHERE `%s` = '%s'", $table, $type, $group_id );
		$this->debug( $query );
		$this->query( $query );
	}

	/**
		@brief		Return the relationships on this blog.
		@since		2020-10-22 22:15:15
	**/
	public function get_relationships_by_ids( $ids )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_relationships';
		$query = sprintf( "SELECT * FROM `%s` WHERE `id` IN ( '%s' )", $table, implode( "','", array_keys( $ids ) ) );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		return $results;
	}

	/**
		@brief		Return the relationships based on these slugs.
		@since		2020-10-22 22:15:15
	**/
	public function get_relationships_by_slugs( $slugs )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_relationships';
		$query = sprintf( "SELECT * FROM `%s` WHERE `slug` IN ( '%s' )", $table, implode( "','", $slugs ) );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		return $results;
	}

	/**
		@brief		Return the associations for this group ID.
		@since		2020-11-03 20:06:03
	**/
	public function get_relationship_associations( $group_id )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_associations';
		$query = sprintf( "SELECT * FROM `%s` WHERE `child_id` = '%s' OR `parent_id` = '%s' OR `intermediary_id` = '%s' ", $table, $group_id, $group_id, $group_id );
		$this->debug( $query );
		$results = $wpdb->get_results( $query );
		return $results;
	}

	/**
		@brief		Return the group row based on the element ID.
		@since		2020-11-03 20:28:27
	**/
	public function get_connected_element_via_element_id( $element_id )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_connected_elements';
		$query = sprintf( "SELECT * FROM `%s` WHERE `element_id` = '%s'", $table, $element_id );
		$this->debug( $query );
		$result = $wpdb->get_row( $query );
		return $result;
	}

	/**
		@brief		Return the group row based on the group ID.
		@since		2020-11-03 20:28:27
	**/
	public function get_relationship_group_via_group_id( $group_id )
	{
		global $wpdb;
		$table = $wpdb->prefix . 'toolset_connected_elements';
		$query = sprintf( "SELECT * FROM `%s` WHERE `group_id` = '%s'", $table, $group_id );
		$this->debug( $query );
		$result = $wpdb->get_row( $query );
		return $result;
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

	/**
		@brief		Are we already broadcasting this relationship?
		@since		2021-04-22 18:39:24
	**/
	public function is_broadcasting_relationship( $relationship_id )
	{
		if ( ! isset( $this->__broadcasting_relationships ) )
			$this->__broadcasting_relationships = ThreeWP_Broadcast()->collection();
		return $this->__broadcasting_relationships->has( $relationship_id );
	}

	/**
		@brief		We are no longer broadcasting this relationship.
		@since		2021-04-22 18:39:24
	**/
	public function not_broadcasting_relationship( $relationship_id )
	{
		if ( ! isset( $this->__broadcasting_relationships ) )
			$this->__broadcasting_relationships = ThreeWP_Broadcast()->collection();
		$this->__broadcasting_relationships->forget( $relationship_id );
		return $this;
	}
}
