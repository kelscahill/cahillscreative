<?php

namespace threewp_broadcast\premium_pack\generatepress;

/**
	@brief				Adds support for the <a href="https://generatepress.com/">GeneratePress</a> theme / plugin.
	@plugin_group		3rd party compatability
	@since				2021-02-27 15:39:45
**/
class GeneratePress
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		$custom_fields
		@since		2021-02-27 16:08:57
	**/
	public static $custom_fields = [
		'_generate_element_display_conditions',
		'_generate_element_exclude_conditions',
	];

	public function _construct()
	{
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
	}

	/**
		@brief		Restore an element.
		@since		2021-02-27 15:52:24
	**/
	public function restore_gp_elements( $bcd )
	{
		foreach( static::$custom_fields as $custom_field_key )
		{
			$custom_field_value = $bcd->custom_fields()->get_single( $custom_field_key );
			if ( ! $custom_field_value )
				continue;

			$custom_field_value = maybe_unserialize( $custom_field_value );

			foreach( $custom_field_value as $condition_index => $condition )
			{
				$rule = $condition[ 'rule' ];
				$object = $condition[ 'object' ];

				if ( strpos( $rule, 'post:' ) === 0 )
					if ( strpos( $rule, ':taxonomy:' ) === false )
						if ( intval( $object ) > 0 )
						{
							$this->debug( '%s', $condition );
							$new_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $object, get_current_blog_id() );
							$this->debug( 'Found new %s %s', $rule, $new_id );
							$custom_field_value[ $condition_index ][ 'object' ] = $new_id;
						}

				if ( strpos( $rule, ':taxonomy:' ) !== false )
					if ( intval( $object ) > 0 )
					{
						$new_term_id = $bcd->terms()->get( $object );
						$this->debug( 'Found new %s: %s', $rule, $new_term_id );
						$custom_field_value[ $condition_index ][ 'object' ] = $new_term_id;
					}

			}
			$bcd->custom_fields()->child_fields()->update_meta( $custom_field_key, $custom_field_value );
		}
	}

	/**
		@brief		save_gp_elements
		@since		2021-02-27 15:52:24
	**/
	public function save_gp_elements( $bcd )
	{
		foreach( static::$custom_fields as $custom_field_key )
		{
			$custom_field_value = $bcd->custom_fields()->get_single( $custom_field_key );
			if ( ! $custom_field_value )
				continue;

			$custom_field_value = maybe_unserialize( $custom_field_value );

			$this->debug( '%s is %s', $custom_field_key, $custom_field_value );

			foreach( $custom_field_value as $condition_index => $condition )
			{
				$rule = $condition[ 'rule' ];
				$object = $condition[ 'object' ];

				if ( strpos( $rule, ':taxonomy:' ) !== false )
				{
					$old_term_id = intval( $object );
					if ( $old_term_id > 0 )
					{
						$parts = explode( ':', $rule );
						$taxonomy = $parts[ 2 ];
						$this->debug( 'Also syncing %s', $taxonomy );
						$bcd->taxonomies()
							->also_sync( null, $taxonomy )
							->use_term( $object );
					}
				}

			}
		}
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2021-02-27 15:52:24
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'gp_elements' )
			$this->restore_gp_elements( $bcd );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2021-02-27 15:52:24
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( $bcd->post->post_type == 'gp_elements' )
			$this->save_gp_elements( $bcd );
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2021-02-27 16:03:57
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_types( 'gp_elements' );
	}
}
