<?php

namespace threewp_broadcast\premium_pack\toolset;

/**
	@brief		Handle the broadcasting of types groups.
	@since		2021-02-16 20:32:55
**/
class wp_types_group
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		post_type
		@since		2021-02-16 20:34:12
	**/
	public static $post_type = 'wp-types-group';

	/**
		@brief		Constructor
		@since		2021-02-16 20:34:08
	**/
	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2021-02-16 20:33:24
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->wp_types_group ) )
			return;

		$item_fields = $bcd->wp_types_group->get( 'post_wpcf_fields' );
		$item_fields = explode( ',', $item_fields );
		$item_fields = array_filter( $item_fields );
		$new_item_fields = [];

		$new_target_fields = [];

		$source_fields = $bcd->wp_types_group->get( 'source_fields' );

		foreach( $item_fields as $item_field )
		{
			$subgroup = $bcd->wp_types_group->collection( 'subgroups' )->get( $item_field );
			if ( $subgroup )
			{
				switch_to_blog( $bcd->parent_blog_id );
				$subgroup_bcd = ThreeWP_Broadcast()->api()->broadcast_children( $subgroup->ID, [ $bcd->current_child_blog_id ] );
				restore_current_blog();
				$new_post_id = $subgroup_bcd->new_post( 'ID' );
				$this->debug( 'Broadcasted subgroup %s to %s', $item_field, $new_post_id );

				$parts = explode( '_', $item_field );
				$parts[ count( $parts ) - 1 ] = $new_post_id;
				$item_field = implode( '_', $parts );
			}
			else
				$new_target_fields[ $item_field ] = $source_fields[ $item_field ];

			$new_item_fields []= $item_field;
		}

		$new_item_fields = implode( ',', $new_item_fields );
		$new_item_fields = ',' . $new_item_fields . ',';
		$bcd->custom_fields()->child_fields()->update_meta( '_wp_types_group_fields', $new_item_fields );

		$target_fields = get_option( 'wpcf-fields' );
		if ( ! is_array( $target_fields ) )
			$target_fields = [];
		$this->debug( 'Current target fields: %s', $target_fields );
		$target_fields = array_merge( $target_fields, $new_target_fields );

		$this->debug( 'New target fields after merge: %s', $target_fields );
		update_option( 'wpcf-fields', $target_fields );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-02-16 20:33:34
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type != static::$post_type )
			return;

		$bcd->wp_types_group = ThreeWP_Broadcast()->collection();

		$source_fields = get_option( 'wpcf-fields' );
		$bcd->wp_types_group->set( 'source_fields', $source_fields );
		$this->debug( 'Saving source fields: %s', $source_fields );

		$post_wpcf_fields = $bcd->custom_fields()->get_single( '_wp_types_group_fields' );
		$bcd->wp_types_group->set( 'post_wpcf_fields', $post_wpcf_fields );
		$this->debug( 'Saving post wpcf-fields: %s', $post_wpcf_fields );

		// Some of the post fields might be referring to other groups.
		$post_wpcf_fields = explode( ',', $post_wpcf_fields );
		$post_wpcf_fields = array_filter( $post_wpcf_fields );
		foreach( $post_wpcf_fields as $field )
		{
			$parts = explode( '_', $field );
			$last_part = $parts[ count( $parts ) - 1 ];
			$last_part = intval( $last_part );
			if ( ! $last_part )
				continue;

			// Check that the ID this last part is referring to is a field group.
			$post = get_post( $last_part );
			if ( $post->post_type != static::$post_type )
				continue;

			$this->debug( 'Field %s is another field group.', $last_part );
			$bcd->wp_types_group->collection( 'subgroups' )->set( $field, $post );
		}
	}
}
