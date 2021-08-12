<?php

namespace threewp_broadcast\premium_pack\pods;

/**
	@brief			Adds support for the <a href="https://www.pods.io/">Pods - Custom Content Types and Fields</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-09-07 12:33:48
**/
class Pods
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\broadcast_generic_post_ui_trait;

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Admin
	// --------------------------------------------------------------------------------------------

	public function _construct()
	{
		$this->add_filter( 'pods_admin_menu' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
	}

	/**
		@brief		Admin tabs.
		@since		2017-10-06 19:26:23
	**/
	public function admin_tabs()
	{
		$tabs = $this->tabs();

		$tabs->tab( 'pods' )
			->callback_this( 'broadcast_pods' )
			// Page heading
			->heading( __( 'Broadcast Pods', 'threewp_broadcast' ) )
			// Tab name
			->name( __( 'Pods', 'threewp_broadcast' ) );
		echo $tabs->render();
	}

	/**
		@brief		UI for broadcasting the pods.
		@since		2017-10-06 19:24:26
	**/
	public function broadcast_pods()
	{
		echo $this->broadcast_generic_post_ui( [
			'post_type' => '_pods_pod',
			'label_plural' => 'Pods',
			'label_singular' => 'Pod',
		] );
	}

	/**
		@brief		pods_admin_menu
		@since		2017-10-06 19:24:12
	**/
	public function pods_admin_menu( $menu )
	{
		// Add ourselves to the menu.
		$menu[ 'broadcast_pods' ] =
		[
			// Label for the menu
			'label' => __( 'Broadcast', 'threewp_broadcast' ),
			'function' => [ $this, 'admin_tabs' ],
		];
		return $menu;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-10-06 19:55:53
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->pods ) )
			return;

		$this->restore_fields( $bcd );
		$this->restore_pod( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-09-07 12:32:24
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		// Check that pods is active.
		if ( ! class_exists( 'PodsMeta' ) )
			return;

		if ( ! isset( $bcd->pods ) )
			$bcd->pods = ThreeWP_Broadcast()->collection();

		$this->disable_pods();
		$this->save_fields( $bcd );
		$this->save_pod( $bcd );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Save
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Save any special pods fields related to this post.
		@since		2017-10-06 22:45:46
	**/
	public function save_fields( $bcd )
	{
		$podsmeta = \PodsMeta::$instance;
		$groups = $podsmeta->groups_get( 'post_type', $bcd->post->post_type );
		$taxonomies_to_sync = [];
		foreach( $groups as $group )
		{
			if ( ! isset( $group[ 'fields' ] ) )
				continue;
			$this->debug( 'PODs group is: %s', $group );
			foreach( $group[ 'fields' ] as $field )
			{
				switch( $field[ 'type' ] )
				{
					case 'pick':
						if ( $field[ 'pick_object' ] == 'post_type' )
						{
							$field_name = $field[ 'name' ];
							$pick_values = $bcd->custom_fields()->get( $field_name );
							$this->debug( 'Field %s (%s) found. Values: %s',$field[ 'type' ], $field_name , $pick_values );

							if ( count( $pick_values ) < 1 )
								break;

							$field_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'id' ] );
							$pod_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'pod_id' ] );
							$pick_bcds = [];
							foreach( $pick_values as $index => $pick_value )
								$pick_bcds[ $index ] = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $pick_value );

							// This is a field we have to process.
							$bcd->pods
								->collection( 'picks' )
								->collection( $field_name )
								->set( 'field', $field )
								->set( 'field_bcd', $field_bcd )
								->set( 'pick_bcds', $pick_bcds )
								->set( 'pod_bcd', $pod_bcd )
								->set( 'values', $pick_values );
						}
						if ( $field[ 'pick_object' ] == 'taxonomy' )
						{
							$field_name = $field[ 'name' ];
							$this->debug( 'Field %s (%s) found.',$field[ 'type' ], $field_name );
							$pick_values = $bcd->custom_fields()->get( $field_name );

							if ( count( $pick_values ) < 1 )
								break;

							foreach( $pick_values as $term_id )
							{
								$term = get_term( $term_id );
								$taxonomy = $term->taxonomy;
								$taxonomies_to_sync[ $taxonomy ] = $taxonomy;
							}

							$field_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'id' ] );
							$pod_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'pod_id' ] );

							// This is a field we have to process.
							$bcd->pods
								->collection( 'picks' )
								->collection( $field_name )
								->set( 'field', $field )
								->set( 'field_bcd', $field_bcd )
								->set( 'pod_bcd', $pod_bcd )
								->set( 'values', $pick_values );
						}
						break;
					case 'file':
						$field_name = $field[ 'name' ];
						$this->debug( 'Field %s (%s) found.',$field[ 'type' ], $field_name );
						$file_ids = $bcd->custom_fields()->get( $field_name );
						$this->debug( 'File IDs: %s', $file_ids );

						if ( count( $file_ids ) < 1 )
							break;

						foreach( $file_ids as $file_id )
						{
							if ( $file_id < 1 )
								continue;
							$result = $bcd->try_add_attachment( $file_id );
							$this->debug( 'Trying to add attachment: %s, %s', $file_id, $result );
						}

						$field_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'id' ] );
						$pod_bcd = ThreeWP_Broadcast()->get_parent_post_broadcast_data( get_current_blog_id(), $field[ 'pod_id' ] );

						// This is a field we have to process.
						$bcd->pods
							->collection( 'file' )
							->collection( $field_name )
							->set( 'field', $field )
							->set( 'field_bcd', $field_bcd )
							->set( 'pod_bcd', $pod_bcd )
							->set( 'file_ids', $file_ids );
						break;
				}
			}
		}
		foreach( $taxonomies_to_sync as $taxonomy_to_sync )
			$bcd->taxonomies()->also_sync_taxonomy( $taxonomy_to_sync );
	}

	/**
		@brief		Save the pod data.
		@since		2017-10-06 20:23:10
	**/
	public function save_pod( $bcd )
	{
		// Find all fields for this pod.
		$fields = get_posts( [
			'posts_per_page' => -1,
			'post_parent' => $bcd->post->ID,
			'post_type' => '_pods_field',
		] );
		foreach( $fields as $field )
			$bcd->pods->collection( 'fields' )->set( $field->ID, $field );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Restore
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Restore any pod fields for this post.
		@since		2017-10-06 23:29:40
	**/
	public function restore_fields( $bcd )
	{
		foreach( $bcd->pods->collection( 'picks' ) as $field_name => $pick )
		{
			$field = $pick->get( 'field' );
			switch( $field[ 'pick_object' ] )
			{
				case 'post_type':
					$picks_on_this_blog = [];
					foreach( $pick->get( 'values' ) as $old_post_id )
					{
						$new_value = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_post_id, get_current_blog_id() );
						$picks_on_this_blog []= $new_value;
					}
					$this->debug( 'Updating field %s with new value: %s', $field_name, $picks_on_this_blog );
				break;
				case 'taxonomy':
					$picks_on_this_blog = [];
					foreach( $pick->get( 'values' ) as $old_term_id )
					{
						$new_term_id = $bcd->terms()->get( $old_term_id );
						$picks_on_this_blog []= $new_term_id;
					}
				break;
			}
			// Replace the normal field.
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $field_name, reset( $picks_on_this_blog ) );
			// And now the underscored field.
			$bcd->custom_fields()
				->child_fields()
				->update_meta( '_pods_' . $field_name, $picks_on_this_blog );

			// In order to save the relationship, we need to get the equivalents on this child.
			$field = $pick->get( 'field' );
			$field_id = $field[ 'id' ];
			$child_field_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $field_id, get_current_blog_id() );
			$this->debug( 'Child field of %s is %s.', $field_id, $child_field_id );

			$pod_bcd = $pick->get( 'pod_bcd' );
			$child_pod_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $pod_bcd->post_id, get_current_blog_id() );
			$this->debug( 'Child pod of %s is %s.', $pod_bcd->post_id, $child_pod_id );

			$child_pod = pods_api()->load_pod( [ 'id' => $child_pod_id ] );
			$child_field = pods_api()->load_field( [ 'id' => $child_field_id ] );
			$this->debug( 'Saving relationship of pod %d, field %d: %s', $child_pod[ 'id' ], $child_field[ 'id' ], $picks_on_this_blog );
			pods_api()->save_relationships( $bcd->new_post( 'ID' ), $picks_on_this_blog, $child_pod, $child_field );
		}

		foreach( $bcd->pods->collection( 'file' ) as $field_name => $data )
		{
			$file_ids = $data->get( 'file_ids' );
			$new_file_ids = [];
			foreach( $file_ids as $file_id )
			{
				$new_file_id = $bcd->copied_attachments()->get( $file_id );
				if ( $new_file_id < 1 )
					continue;
				$new_file_ids []= $new_file_id;
			}
			$new_file_id = reset( $new_file_ids );
			$this->debug( 'Saving new file IDs for %s as %s', $field_name, $new_file_ids );
			$bcd->custom_fields()
				->child_fields()
				->update_meta( $field_name, $new_file_id );
			$bcd->custom_fields()
				->child_fields()
				->update_meta( '_pods_' . $field_name, $new_file_ids );

			// In order to save the relationship, we need to get the equivalents on this child.
			$field = $data->get( 'field' );
			$field_id = $field[ 'id' ];
			$child_field_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $field_id, get_current_blog_id() );
			$this->debug( 'Child field of %s is %s.', $field_id, $child_field_id );

			$pod_bcd = $data->get( 'pod_bcd' );
			$child_pod_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $pod_bcd->post_id, get_current_blog_id() );
			$this->debug( 'Child pod of %s is %s.', $pod_bcd->post_id, $child_pod_id );

			$child_pod = pods_api()->load_pod( [ 'id' => $child_pod_id ] );
			$child_field = pods_api()->load_field( [ 'id' => $child_field_id ] );
			$this->debug( 'Saving relationship of pod %d, field %d: %s', $child_pod[ 'id' ], $child_field[ 'id' ], $new_file_ids );
			pods_api()->save_relationships( $bcd->new_post( 'ID' ), $new_file_ids, $child_pod, $child_field );
		}
	}

	/**
		@brief		Restore the pod data.
		@since		2017-10-06 20:32:21
	**/
	public function restore_pod( $bcd )
	{
		if ( $bcd->new_post->post_type != '_pods_pod' )
			return;

		// Restore all of the fields.
		foreach( $bcd->pods->collection( 'fields' ) as $field_id => $field )
		{
			$this->debug( 'Broadcasting field %d', $field_id );
			switch_to_blog( $bcd->parent_blog_id() );
			$field_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $field_id, [ $bcd->current_child_blog_id ] );
			restore_current_blog();

			// Modify the child field, setting the correct pod id.
			$data = [
				'ID' => $field_bcd->new_post( 'ID' ),
				'post_parent' => $bcd->new_post( 'ID' ),
			];
			$this->debug( 'Updating child field: %s', $data );
			wp_update_post( $data );
		}

		$this->debug( 'Flushing pods cache.' );
		pods_api()->cache_flush_pods();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Disable Pods when broadcasting to prevent any "pod not found" errors.
		@since		2017-10-06 20:26:06
	**/
	public function disable_pods()
	{
		$podsmeta = \PodsMeta::$instance;

		// Remove the save post action.
		$this->debug( 'Disabling Pods.' );
		remove_action( 'save_post', [ $podsmeta, 'save_post' ], 10, 3 );

		// And the meta.
		remove_filter( 'add_post_metadata', [ $podsmeta, 'add_post_meta' ], 10, 5 );
		remove_filter( 'update_post_metadata', [ $podsmeta, 'update_post_meta' ], 10, 5 );
		remove_filter( 'delete_post_metadata', [ $podsmeta, 'delete_post_meta' ], 10, 5 );
	}

}
