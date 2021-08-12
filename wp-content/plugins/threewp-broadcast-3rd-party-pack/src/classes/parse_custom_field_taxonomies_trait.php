<?php

namespace threewp_broadcast\premium_pack\classes;

/**
	@brief		Convenience method to parse the taxonomies found in a custom field of the child post.
	@since		2018-05-23 20:34:02
**/
trait parse_custom_field_taxonomies_trait
{
	/**
		@brief		Parse the taxonomies found in this custom field.
		@since		2018-05-23 20:33:39
	**/
	public function parse_custom_field_taxonomies( $bcd, $custom_field )
	{
		$value = $bcd->custom_fields()->child_fields()->get( $custom_field );
		// Does the child even has this custom field.
		if ( ! $value )
			return;
		$value = reset( $value );
		$value = maybe_unserialize( $value );
		$this->debug( 'Taxonomies field %s found: %s', $custom_field, $value );

		// Treat single values as arrays, just for simplicity.
		$is_array = is_array( $value );
		if ( ! $is_array )
			$value = [ $value ];

		// Our lookup table of synced taxonomy BCDs.
		$synced_taxonomies = [];

		$values = $value;
		$new_values = [];
		foreach( $values as $old_value )
		{
			switch_to_blog( $bcd->parent_blog_id );
			$term = get_term( $old_value );
			$taxonomy = $term->taxonomy;

			// Have we already synced this taxonomy?
			if ( ! isset( $synced_taxonomies[ $taxonomy ] ) )
				$synced_taxonomies[ $taxonomy ] = $this->sync_taxonomy_to_blogs( $taxonomy, [ $bcd->current_child_blog_id ] );

			restore_current_blog();

			$synced_bcd = $synced_taxonomies[ $taxonomy ];
			$new_value = $synced_bcd->terms()->get( $old_value );
			$new_values []= $new_value;
		}

		if ( ! $is_array )
			$new_values = reset( $new_values );

		$this->debug( 'Updating taxonomies field %s with: %s', $custom_field, $new_values );

		$bcd->custom_fields()->child_fields()->update_meta( $custom_field, $new_values );
	}
}
