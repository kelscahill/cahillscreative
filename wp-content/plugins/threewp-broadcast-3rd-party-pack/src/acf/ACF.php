<?php

namespace threewp_broadcast\premium_pack\acf
{

use \Exception;
use \plainview\sdk_broadcast\collections\collection;
use \threewp_broadcast\attachment_data;
use \threewp_broadcast\broadcasting_data;

/**
	@brief				Adds support for Elliot Condon's <a href="http://wordpress.org/plugins/advanced-custom-fields/">Advanced Custom Field</a> plugin.
	@plugin_group		3rd party compatability
	@details			Supports 4.2.2 and 5.2.6.
**/
class ACF
	extends \threewp_broadcast\premium_pack\base
{
	use \threewp_broadcast\premium_pack\classes\sync_taxonomy_trait;

	public function _construct()
	{
		$this->add_action( 'broadcast_acf_add_field', 100 );			// Let other plugins handle this first.
		$this->add_action( 'broadcast_acf_parse_field', 100 );			// Let other plugins handle this first.
		$this->add_action( 'broadcast_acf_restore_field', 100 );		// Let other plugins handle this first.
		$this->add_action( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_broadcasting_modify_post' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_action( 'threewp_broadcast_collect_post_type_taxonomies' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_wp_update_term' );

		$bc = ThreeWP_Broadcast();
		{
			add_filter( 'manage_edit-acf_columns', [ $bc, 'manage_posts_columns' ], 20 );
			add_filter( 'manage_edit-acf-field-group_columns', [ $bc, 'manage_posts_columns' ], 20 );
		}
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add a field.
		@details	Responds only to those fields which this plugin can handle.
		@since		2014-03-27 20:42:09
	**/
	public function broadcast_acf_add_field( $action )
	{
		if ( $action->is_finished() )
			return;

		$field = $action->field;

		if ( $field->value == '' )
		{
			$this->debug( 'Field %s %s has no value. Ignoring.', $field->name, $field->key );
			return;
		}

		$this->debug( 'Attempting to add a %s field with the value %s', $field->type, htmlspecialchars( json_encode( $field->value ) ) );
		try
		{
			switch( $field->type )
			{
				case 'conditional_gallery':
				case 'file':
				case 'image':
				case 'image_crop':
					$this->add_image_file_field( $action );
				break;
				case 'gallery':
					$this->add_gallery_field( $action );
				break;
				case 'link':
					$this->add_link_field( $action );
				break;
				case 'page_link':
				case 'post_object':
				case 'relationship':
					$this->add_post_obj_field( $action );
				break;
				case 'taxonomy':
					$this->add_taxonomy_field( $action );
				break;
				case 'text':
				case 'textarea':
				case 'url':
				case 'wysiwyg':
					$this->add_text_field( $action );
				break;
				default:
					// Only save non-special values for non-posts, since the non-special values for posts are stored in custom fields that are copied over anyways.
					if ( static::is_a_real_post( $action->get_post_id() ) )
						break;
					$action->get_storage()->append( $field );
				break;
			}
		}
		catch( Exception $e )
		{
			$this->debug( 'Exception adding field: ' . $e->getMessage() );
		}
	}

	/**
		@brief		Parse an ACF field, deciding what to do with it.
		@details	Responds only to those fields which this plugin can handle.
		@since		2014-01-22 11:52:15
	**/
	public function broadcast_acf_parse_field( $action )
	{
		if ( $action->is_finished() )
			return;

		$bcd = $action->broadcasting_data;		// Convenience

		// Skip this field if it is in the blacklist
		if ( $this->field_is_only_blacklisted( $action->field, $action->broadcasting_data ) )
		{
			$this->debug( 'The field %s (%s) exists in the custom field blacklist. Skipping.', $action->field->label, $action->field->key );
			return;
		}

		// ACF4 fields should be raw.
		if ( ! $this->has_acf_pro() )
		{
			//$unformatted_field = get_field_object( $action->field->key, $action->get_post_id(), [ 'format_value' => false ] );
			$action->field->value = $action->get_raw_value();
			$action->field->value = maybe_unserialize( $action->field->value );
			$this->debug( 'Raw value for %s is %s', $action->field->name, $action->field->value );
		}

		// All fields are to be parsed first.
		$add_field_action = new actions\add_field();
		$add_field_action->set_broadcasting_data( $bcd );
		$add_field_action->set_field( $action->field );
		$add_field_action->set_post_id( $action->get_post_id() );
		$add_field_action->set_storage( $action->get_storage() );
		$add_field_action->execute();

		// Some fields require recursion.

		$this->debug( 'Field %s %s is of type: %s',
			$action->field->name,
			$action->field->key,
			$action->field->type );
		switch( $action->field->type )
		{
			case 'clone':
				foreach( $action->field->sub_fields as $sub_index => $subfield )
				{
					$name = $subfield[ 'name' ];

					// Make a temp field and recurse.
					$tempfield = (object) $subfield;
					$tempfield->name = $name;
					$tempfield->value = $action->field->value[ $subfield[ 'key' ] ];

					$parse_field_action = new actions\parse_field();
					$parse_field_action->set_broadcasting_data( $bcd );
					$parse_field_action->set_field( $tempfield );
					$parse_field_action->set_post_id( $action->get_post_id() );
					$parse_field_action->set_storage( $action->get_storage() );
					$parse_field_action->execute();
				}
			break;
			case 'flexible_content':
				if( ! is_array( $action->field->layouts ) || empty( $action->field->value ) )
					break;
				$layout_map = [];

				foreach( $action->field->layouts as $layout )
					$layout_map[ $layout['name'] ] = $layout;

				foreach( $action->field->value as $id => $v )
				{
					if ( $this->has_acf_pro() )
						$layout = $layout_map[ $v['acf_fc_layout'] ];
					else
						$layout = $layout_map[ $v ];

					if ( ! isset( $layout[ 'sub_fields' ] ) )
						continue;

					foreach( $layout[ 'sub_fields' ] as $sub_index => $subfield )
					{
						$name = $subfield[ 'name' ];

						// Make a temp field and recurse.
						$tempfield = (object) $subfield;
						$tempfield->name = sprintf( '%s_%s_%s', $action->field->name, $id, $name );

						if ( $this->has_acf_pro() )
							$tempfield->value = $v[ $tempfield->key ];

						$tempfield->flexible_content = true;
						$tempfield->field_index = $sub_index;

						$parse_field_action = new actions\parse_field();
						$parse_field_action->set_broadcasting_data( $bcd );
						$parse_field_action->set_field( $tempfield );
						$parse_field_action->set_post_id( $action->get_post_id() );
						$parse_field_action->set_storage( $action->get_storage() );
						$parse_field_action->execute();
					}
				}
			break;
			case 'group':
				foreach( $action->field->sub_fields as $sub_index => $subfield )
				{
					$name = $subfield[ 'name' ];

					// Make a temp field and recurse.
					$tempfield = (object) $subfield;
					$tempfield->name = sprintf( '%s_%s', $action->field->name, $subfield[ 'name' ] );

					$tempfield->value = $action->field->value[ $subfield[ 'key' ] ];

					$tempfield->flexible_content = true;
					$tempfield->field_index = $sub_index;

					$parse_field_action = new actions\parse_field();
					$parse_field_action->set_broadcasting_data( $bcd );
					$parse_field_action->set_field( $tempfield );
					$parse_field_action->set_post_id( $action->get_post_id() );
					$parse_field_action->set_storage( $action->get_storage() );
					$parse_field_action->execute();
				}
			break;
			case 'component_field':
			case 'repeater':
				// As usual, ACF4 handles repeaters differently.
				if ( ! $this->has_acf_pro() )
				{
					$unformatted_field = get_field_object( $action->field->key, $action->get_post_id() );
					$action->field->value = $unformatted_field[ 'value' ];
				}

				$this->debug( 'Repeater field %s found with %s subfields.',
					$action->field->name,
					$action->field->key,
					count( $action->field->sub_fields )
				);

				if ( $this->has_acf_pro() )
				{
					if ( ! is_array( $action->field->value ) )
					{
						$this->debug( 'Repeater field %s %s has no values.',
							$action->field->name,
							$action->field->key
						);
						break;
					}

					foreach( $action->field->value as $value_index => $value )
					{
						foreach( $action->field->sub_fields as $subfield_index => $subfield )
						{
							$this->debug( 'Handling field %s in ACF5 repeater %s', $subfield[ 'name' ], $action->field->name );
							$subfield = (object) $subfield;
							$name = $subfield->name;

							// Make a temp field and recurse.
							$tempfield = (object) $subfield;
							$tempfield->name = sprintf( '%s_%s_%s', $action->field->name, $value_index, $name );
							$tempfield->field_index = $value_index;

							// Extract the value first in order to display it.
							if ( $this->has_acf_pro() )
							{
								$subfield_value = $value[ $subfield->key ];
							}
							else
							{
								$subfield_value = $value[ $subfield->_name ];
							}
							$this->debug( 'Assigning value: %s', $subfield_value );
							$tempfield->value = $subfield_value;

							$parse_field_action = new actions\parse_field();
							$parse_field_action->set_broadcasting_data( $bcd );
							$parse_field_action->set_field( $tempfield );
							$parse_field_action->set_post_id( $action->get_post_id() );
							$parse_field_action->set_storage( $action->get_storage() );
							$parse_field_action->execute();
						}
					}
				}
				else
				{
					// ACF v4
					$cf = $bcd->custom_fields();
					$max = $cf->get_single( $action->field->name );
					for( $block_counter = 0; $block_counter < $max; $block_counter++ )
					{
						foreach( $action->field->sub_fields as $subfield )
						{
							$this->debug( 'Handling field %s in ACF4 repeater %s', $subfield[ 'name' ], $action->field->name );
							$subfield = (object) $subfield;
							$name = $subfield->name;

							// Make a temp field and recurse.
							$tempfield = (object) $subfield;
							$tempfield->name = sprintf( '%s_%s_%s', $action->field->name, $block_counter, $name );
							$tempfield->field_index = $block_counter;

							$parse_field_action = new actions\parse_field();
							$parse_field_action->set_broadcasting_data( $bcd );
							$parse_field_action->set_field( $tempfield );
							$parse_field_action->set_post_id( $action->get_post_id() );
							$parse_field_action->set_storage( $action->get_storage() );
							$parse_field_action->execute();
						}
					}
				}
			break;
		} // switch
	}

	/**
		@brief		Restore a field using the item in the action.
		@since		2015-01-24 22:57:29
	**/
	public function broadcast_acf_restore_field( $action )
	{
		$field = $action->field;				// Convenience.
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( $action->is_finished() )
			return;

		if ( $this->field_is_only_blacklisted( $field, $bcd ) )
		{
			$this->debug( 'The field %s (%s) exists in the custom field blacklist. Skipping.', $field->label, $field->key );
			return;
		}

		$this->debug( 'Restoring %s field %s %s', $field->type, $field->name, $field->key );
		switch( $field->type )
		{
			case 'conditional_gallery':
			case 'file':
			case 'image':
			case 'image_crop':
				$new_meta = [];

				foreach( $field->original_ids as $index => $original_id )
				{
					$new_id = $bcd->copied_attachments()->get( $original_id );
					if ( $new_id )
					{
						$this->debug( 'Replacing old ID %s from %s with new attachment ID %s', $original_id, $field->name, $new_id );
						$new_meta [ $index ]= $new_id;
					}
					else
					{
						$this->debug( 'No new image found. Using old ID %s from %s.', $original_id, $field->name );
						$new_meta [ $index ]= $original_id;
					}
				}
				if ( ! $field->multiple )
					$new_meta = reset( $new_meta );
				else
				{
					if ( isset( $field->json_encoded ) )
						$new_meta = json_encode( $new_meta );
				}
				$this->debug( 'The new %s is: %s', $field->type, $new_meta );
				$action->acf_update_value( $new_meta );

				// Handle conditional gallery locations.
				if ( isset( $field->image_location ) )
				{
					if ( is_array( $field->image_location ) )
					{
						$new_image_location = [];
						foreach( $field->image_location as $image_id => $location )
						{
							if ( $location == 'local' )
							{
								$new_id = $bcd->copied_attachments()->get( $image_id );
								$terms = $field->media_category[ $image_id ];
								$this->debug( 'Setting media_category terms for image %s: %s', $new_id, $terms );
								wp_set_object_terms( $new_id, $terms, 'media_category' );
							}
							else
								$new_id = $image_id;
							$new_image_location[ $new_id ] = $location;
						}
					}

					if ( $field->image_location == 'global' )
						$new_image_location = $field->image_location;

					if ( $field->image_location == 'local' )
					{
						$this->debug( 'Restoring single location.' );
						$new_id = $bcd->copied_attachments()->get( $new_meta );
						$terms = $field->media_category[ reset( $field->original_ids ) ];
						$this->debug( 'Setting media_category terms for image %s: %s', $new_id, $terms );
						wp_set_object_terms( $new_id, $terms, 'media_category' );
						$new_image_location = $field->image_location;
					}

					$this->debug( 'Setting new image location: %s', $new_image_location );
					update_post_meta( $bcd->new_post( 'ID' ), 'image_location-' . $field->ID, $new_image_location );
				}
			break;
			case 'gallery':
				$new_meta = [];
				foreach( $field->original_ids as $original_id )
				{
					$new_id = $bcd->copied_attachments()->get( $original_id );
					if ( $new_id )
						$new_meta[] = $new_id;
				}
				// Replace the IDs of the posts with the new IDs
				$this->debug( 'The new gallery is: %s', $new_meta );
				$action->acf_update_value( $new_meta );
			break;
			case 'link':
				$old_value = $field->value[ 'url' ];

				$parse_action = new \threewp_broadcast\actions\parse_content();
				$parse_action->broadcasting_data = $bcd;
				$parse_action->content = $old_value;
				$parse_action->id = $field->name;
				$parse_action->execute();
				$new_url = $parse_action->content;

				$value = $field->value;
				$value[ 'url' ] = $new_url;

				$this->debug( 'Setting new value <em>%s</em> for link field %s.', $new_url, $field->name );

				$action->acf_update_value( $value );
			break;
			case 'page_link':
			case 'post_object':
			case 'relationship':
				$new_meta = [];
				$this->debug( 'Restoring %s %s', $field->type, $field->name );
				foreach( $field->original_posts as $old_id => $old_post )
				{
					$new_post_id = false;

					// Are we linking to ourselves?
					if ( isset( $bcd->post->ID ) )
						if ( $old_id == $bcd->post->ID )
							$new_post_id = $bcd->new_post( 'ID' );

					// Check if the post is already known
					if ( ! $new_post_id )
						$new_post_id = $bcd->equivalent_posts()->get_or_broadcast( $bcd->parent_blog_id, $old_id, get_current_blog_id() );
					$this->debug( 'Using equivalent of %s/%s: %s/%s', $bcd->parent_blog_id, $old_id, get_current_blog_id(), $new_post_id );
					$new_meta[] = $new_post_id;
				}
				if ( ! $field->multiple )
					$new_meta = reset( $new_meta );
				$this->debug( 'The new %s is: %s', $field->type, $new_meta );
				// Replace the ID of the post with the new ID
				$action->acf_update_value( $new_meta );
			break;
			case 'taxonomy':
				$new_values = [];
				$taxonomy = $field->taxonomy;

				// Handle single and multiple values as multiple
				$values = $field->value;
				if ( ! is_array( $values ) )
					$values = [ $values ];

				foreach( $values as $term_id )
				{
					if ( is_object( $term_id ) )
						$term_id = $term_id->term_id;

					ThreeWP_Broadcast()->maybe_sync_taxonomy( $bcd, $taxonomy );

					$new_values[] = $bcd->terms()->get( $term_id );
				}

				// Convert single values back to a single value.
				if ( ! is_array( $field->value ) )
					$new_values = reset( $new_values );

				$this->debug( 'New taxonomy %s value: %s', $taxonomy, $new_values );
				$action->acf_update_value( $new_values );
			break;
			case 'text':
			case 'textarea':
			case 'url':
			case 'wysiwyg':
				$value = maybe_unserialize( $field->value );

				$parse_action = new \threewp_broadcast\actions\parse_content();
				$parse_action->broadcasting_data = $bcd;
				$parse_action->content = $value;
				$parse_action->id = $field->name;
				$parse_action->execute();
				$value = $parse_action->content;

				$this->debug( 'Setting new value <em>%s</em> for text field %s.', htmlspecialchars( $value ), $field->name );
				$action->acf_update_value( $value );
				break;
			break;
			default:
				$this->debug( 'Setting new value <em>%s</em> for non-special field %s.', $field->value, $field->name );
				$action->acf_update_value( $field->value );
				break;
		}
	}

	/**
		@brief		Handle updating of the advanced custom fields image fields.
		@param		$action		Broadcast action.
		@since		20131030
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		if ( ! $this->has_acf() )
			return;

		$bcd = $action->broadcasting_data;

		if ( isset( $bcd->acf_field_group ) )
			$this->restore_field_group( $bcd );

		if ( isset( $bcd->acf ) )
			$this->restore_normal_post( $bcd );
	}

	/**
		@brief		Modify the field group, if necessary.
		@since		2016-06-23 23:00:14
	**/
	public function threewp_broadcast_broadcasting_modify_post( $action )
	{
		$bcd = $action->broadcasting_data;

		if ( isset( $bcd->acf_field_group ) )
			if ( $bcd->acf_field_group->collection( 'location_values' )->count() > 0 )
			{
				// Convert the location values to their equivalents on this blog.
				$content = $bcd->modified_post->post_content;
				$content = maybe_unserialize( $content );

				$this->debug( 'The location data for the child post is: %s', $content[ 'location' ] );

				$post_types = get_post_types();

				foreach( $content[ 'location' ] as $location_group_index => $location_group )
					foreach( $location_group as $location_index => $location )
					{
						$value = $location[ 'value' ];
						$location_bcd = $bcd->acf_field_group->collection( 'location_values' )->get( $value );

						// No b_d?
						if ( ! $location_bcd )
							continue;

						$new_value = $location_bcd->get_linked_post_on_this_blog();
						if ( ! $new_value )
						{
							$this->debug( 'No equivalent location value for %s %s %s.',
								$location_group_index,
								$location_index,
								$value
							);
							continue;
						}
						$this->debug( 'New location value for %s %s %s set to %s.',
							$location_group_index,
							$location_index,
							$value,
							$new_value
						);
						$content[ 'location' ][ $location_group_index ][ $location_index ][ 'value' ] = $new_value;
					}

				$bcd->modified_post->post_content = serialize( $content );
			}
	}

	/**
		@brief		Save info about the broadcast.
		@param		Broadcast_Data		The BCD object.
		@since		20131030
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		if ( ! $this->has_acf() )
			return;

		if ( $this->has_acf_pro() )
			$this->debug( 'ACF pro detected.' );
		else
			$this->debug( 'ACF detected.' );

		$bcd = $action->broadcasting_data;

		// Is this a field group?
		if ( $bcd->post->post_type == 'acf-field-group' )
			$this->start_field_group( $bcd );
		else
			$this->start_normal_post( $bcd );
	}

	/**
		@brief		Looks like we're going to sync taxonomies. Take note of all associated images.
		@since		2016-07-19 20:55:17
	**/
	public function threewp_broadcast_collect_post_type_taxonomies( $action )
	{
		if ( ! $this->has_acf() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->acf_taxonomy_data ) )
			$bcd->acf_taxonomy_data = ThreeWP_Broadcast()->collection();

		if ( isset( $bcd->acf ) )
			$old_acf = $bcd->acf;

		foreach( $bcd->parent_post_taxonomies as $parent_post_taxonomy => $terms )
		{
			// Get all of the fields for all terms
			foreach( $terms as $term )
			{
				$this->prepare_broadcasting_data( $bcd );

				// Parse each one.
				$key = sprintf( '%s_%s', $parent_post_taxonomy, $term->term_id );
				$this->save_post_fields( $bcd, $key );

				// Save the acf field data in a separate object for this specific term.
				if ( isset( $bcd->acf ) )
				{
					// Purge the blacklisted terms.
					foreach( $bcd->acf as $index => $field )
					{
						if ( $bcd->taxonomies()->blacklist_has( $parent_post_taxonomy, $term->slug, $field->name ) )
						{
							$this->debug( 'Removing ACF taxonomy field for %s / %s / %s', $parent_post_taxonomy, $term->slug, $field->name );
							unset( $bcd->acf[ $index ] );
						}
					}
					if ( count( $bcd->acf ) > 0 )
						$this->debug( 'Saving %s fields: %s', count( $bcd->acf ), $bcd->acf );
					$bcd->acf_taxonomy_data->set( $key, $bcd->acf );
					unset( $bcd->acf );
				}
			}
		}

		if ( isset( $old_acf ) )
			$bcd->acf = $old_acf;
	}

	public function threewp_broadcast_get_post_types( $action )
	{
		$action->post_types[ 'acf' ] = 'acf';
		$action->post_types[ 'acf-field-group' ] = 'acf-field-group';
	}

	/**
		@brief		Updating the term.
		@since		2016-07-22 16:36:28
	**/
	public function threewp_broadcast_wp_update_term( $action )
	{
		if ( ! $this->has_acf() )
			return;

		$bcd = $action->broadcasting_data;

		if ( ! isset( $bcd->acf_taxonomy_data ) )
			return;

		$this->debug( 'Restoring ACF taxonomies.' );

		ThreeWP_Broadcast()->copy_attachments_to_child( $bcd );

		// Build the original key for this term.
		$key = sprintf( '%s_%s', $action->taxonomy, $action->old_term->term_id );
		$fields = $bcd->acf_taxonomy_data->get( $key, false );
		if ( ! $fields )
			return $this->debug( 'No ACF taxonomies found for %s', $key );

		// Assemble the new key.
		$key = sprintf( '%s_%s', $action->taxonomy, $action->new_term->term_id );

		$this->debug( 'Restoring ACF taxonomies for %s', $key );

		// Might not exist, therefore @.
		@ $old_acf = $bcd->acf;
		$bcd->acf = $fields;
		$this->restore_post_fields( $bcd, $key );
		$bcd->acf = $old_acf;
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Adds a gallery.
		@since		2014-06-06 14:30:44
	**/
	public function add_gallery_field( $action )
	{
		$field = $action->field;

		$acf_field = new acf\field( $field );
		$acf_field->original_ids = [];

		$this->debug( 'The gallery field value is %s', $field->value );

		foreach( $field->value as $value )
		{
			// If the image is not already being broadcasted, then add it
			$this->debug( 'Adding attachment data for the gallery %s.', $value );
			$action->broadcasting_data->try_add_attachment( $value );
			$acf_field->original_ids[] = $value;
		}

		if ( count( $acf_field->original_ids ) < 1 )
			return;

		$action->get_storage()->append( $acf_field );
	}

	/**
		@brief		Adds this image field to the ACF object.
		@since		2014-01-22 11:57:22
	**/
	public function add_image_file_field( $action )
	{
		$field = $action->field;
		$images = [];

		$acf_field = new acf\field( $field );

		// Try handle json encoded also.
		if ( strpos( $field->value, '{' ) !== false )
		{
			$json_decoded = json_decode( $field->value );
			if ( $json_decoded !== null )
			{
				$field->value = (array) $json_decoded;
				$acf_field->json_encoded = true;
			}
		}

		// ACF4 and 5 handle things differently. Of course.
		if ( $this->has_acf_pro() )
		{
			$acf_field->multiple = is_array( $field->value );
			// Convert the single value to an array anyways, to ease handling.
			if ( ! $acf_field->multiple )
				$field->value = [ $field->value ];
		}
		else
		{
			$field->ID = $field->id;

			// If the value consists of arrays, then it's a gallery.
			if ( is_array( $field->value ) )
			{
				$acf_field->multiple = true;
			}
			else
			{
				$acf_field->multiple = false;
				$field->value = [ $field->value ];
			}
		}

		$this->debug( 'Images in %s are: %s', $field->name, $field->value );

		foreach( $field->value as $index => $image_id )
		{
			if ( ! $image_id )
				continue;
			$images[ $index ] = $image_id;
		}

		$acf_field->original_ids = $images;

		// If using Conditional Gallery, only save the local images.
		if ( isset( $action->broadcasting_data->post->ID ) )
		{
			$image_location = get_post_meta( $action->broadcasting_data->post->ID, 'image_location-' . $field->ID, true );
			$image_location = maybe_unserialize( $image_location );
			if ( is_array( $image_location ) )
			{
				$acf_field->image_location = $image_location;
				if ( ! isset( $acf_field->media_category  ) )
					$acf_field->media_category = [];
				foreach( $image_location as $image_id => $location )
					if ( $location == 'global' )
					{
						$this->debug( 'Forgetting global image %s', $image_id );
						unset( $images[ $image_id ] );
					}
					else
					{
						// Local images have media_category terms we must take with us.
						$terms = wp_get_object_terms( $image_id, 'media_category', [ 'fields' => 'names' ] );
						$this->debug( 'Saving media_category terms for image %s: %s', $image_id, $terms );
						$acf_field->media_category[ $image_id ] = $terms;
					}
			}

			if ( $image_location == 'local' )
			{
				$this->debug( 'Single local location.' );
				$acf_field->image_location = $image_location;
				$image_id = $field->value;
				// Local images have media_category terms we must take with us.
				$terms = wp_get_object_terms( $image_id, 'media_category', [ 'fields' => 'names' ] );
				$this->debug( 'Saving media_category terms for image %s: %s', $image_id, $terms );
				$acf_field->media_category[ $image_id ] = $terms;
			}

			if ( $image_location == 'global' )
			{
				$this->debug( 'Single global location.' );
				$acf_field->image_location = $image_location;
				$image_id = $field->value;
				unset( $images[ $image_id ] );
			}
		}

		foreach( $images as $image_id )
		{
			// If the image is not already being broadcasted, then add it
			$this->debug( 'Adding attachment data for the image %s.', $image_id );
			$action->broadcasting_data->try_add_attachment( $image_id );
		}

		$action->get_storage()->append( $acf_field );
	}

	/**
		@brief		Link fields from the
		@since		2016-04-22 15:41:04
	**/
	public function add_link_field( $action )
	{
		$field = $action->field;
		$acf_field = new acf\field( $field );
		$action->get_storage()->append( $acf_field );

		$preparse_action = new \threewp_broadcast\actions\preparse_content();
		$preparse_action->broadcasting_data = $action->broadcasting_data;
		$preparse_action->content = $field->value[ 'url' ];
		$preparse_action->id = $field->name;
		$preparse_action->execute();
	}

	/**
		@brief		Adds this post field to the ACF object.
		@since		2014-03-25 18:40:00
	**/
	public function add_post_obj_field( $action )
	{
		$field = $action->field;
		$posts = [];

		$multiple = is_array( $field->value );

		// Convert the single value to an array anyways, to ease handling.
		if ( ! $multiple )
			$field->value = [ $field->value ];

		foreach( $field->value as $post_id )
		{
			$post_id = intval( $post_id );
			if ( ! $post_id )
				continue;
			if ( $post_id < 2 )
			{
				$this->debug( 'Warning! Post object field reports post #1, which is never valid. Ignoring.' );
				continue;
			}
			$the_post = get_post( $post_id );
			if ( ! $the_post )
			{
				$this->debug( 'Warning! Post %s does not exist. Ignoring.', $post_id );
				continue;
			}
			$posts[ $post_id ] = $the_post;
		}

		if ( count( $posts ) < 1 )
			return $this->debug( 'No post IDs found.' );

		$this->debug( 'Post IDs are: %s', implode( ', ', array_keys( $posts ) ) );
		$acf_field = new acf\field( $field );
		$acf_field->multiple = $multiple;
		$acf_field->original_posts = $posts;
		$action->get_storage()->append( $acf_field );
	}

	/**
		@brief		Add this taxonomy field.
		@since		2014-10-27 13:01:20
	**/
	public function add_taxonomy_field( $action )
	{
		$field = $action->field;

		if ( absint( $field->value ) < 1 )
		{
			$this->debug( 'Taxonomy %s has no IDs set. Ignoring.', $field->taxonomy );
			return;
		}

		$this->debug( 'Taxonomy %s IDs are %s.', $field->taxonomy, $field->value );

		$taxonomy = get_taxonomy( $field->taxonomy );
		$post_type = reset( $taxonomy->object_type );

		$acf_field = new acf\field( $field );
		$action->broadcasting_data->taxonomies()->also_sync( $post_type, $field->taxonomy );
		$action->get_storage()->append( $acf_field );
	}

	/**
		@brief		Add a text field.
		@since		2016-03-29 09:20:02
	**/
	public function add_text_field( $action )
	{
		$field = $action->field;
		$acf_field = new acf\field( $field );
		$action->get_storage()->append( $acf_field );

		// Tell all plugins about this content, since it could contain shortcodes or whatever.
		$preparse_action = new \threewp_broadcast\actions\preparse_content();
		$preparse_action->broadcasting_data = $action->broadcasting_data;
		$preparse_action->content = $field->value;
		$preparse_action->id = $field->name;
		$preparse_action->execute();
	}

	/**
		@brief		Extracts an ID out of this object / array / whatever.
		@since		2014-03-27 21:37:06
	**/
	public function extract_id( $id )
	{
		// Convert objects to arrays.
		if ( is_object( $id ) )
			$id = (array) $id;
		// And extract the ID from the array.
		if ( is_array( $id ) )
		{
			foreach( [ 'ID', 'id' ] as $key )
				if ( isset( $id[ $key ] ) )
				{
					$id = $id[ $key ];
					break;
				}
			if ( is_array( $id ) )
				return false;
		}

		// Last chance: if this is not an integer...
		$id = intval( $id );
		if ( $id < 1 )
			return false;
		// But it is!
		return $id;
	}

	/**
		@brief		Checks whether a field is only blacklist.
		@since		2015-05-28 20:55:57
	**/
	public function field_is_only_blacklisted( $field, $bcd )
	{
		// If it's not blacklisted we're fine.
		if ( ! $this->field_is_listed( $field, $bcd, 'blacklist' ) )
			return false;
		// Field is now in the blacklist. But is it white or protected?
		if ( $this->field_is_listed( $field, $bcd, 'whitelist' ) )
			return false;
		if ( $this->field_is_listed( $field, $bcd, 'protectlist' ) )
			return false;
		return true;
	}

	/**
		@brief		Is this field name or key named in a custom field list?
		@since		2015-05-28 20:56:40
	**/
	public function field_is_listed( $field, $bcd, $type = 'blacklist' )
	{
		if ( ! isset( $bcd->custom_fields->$type ) )
			return false;

		$field = (object) $field;

		foreach( [
			$field->key,
			$field->name,
		] as $field_name )
		{
			foreach( $bcd->custom_fields->$type as $entry )
			{
				// No wildcard = straight match
				if ( strpos( $entry, '*' ) === false )
				{
					if ( $entry == $field_name )
						return true;
				}
				else
				{
					$preg = str_replace( '*', '.*', $entry );
					$preg = sprintf( '/%s/', $preg );
					preg_match( $preg, $field_name, $matches );
					if ( ( count( $matches ) == 1 ) && $matches[ 0 ] == $field_name )
						return true;
				}
			}
		}
		return false;
	}

	/**
		@brief		Finds all of the fields of this field.
		@since		2015-04-11 13:46:02
	**/
	public function find_fields( $field_group, $post_id )
	{
		$r = get_posts( [
			'post_parent' => $post_id,
			'posts_per_page' => -1,
			'post_type' => 'acf-field',
		] );

		foreach( $r as $index => $post )
		{
			$field_group->collection( 'fields' )->set( $post->ID, $post );
			$this->find_fields( $field_group, $post->ID );
		}

		return $r;
	}

	/**
		@brief		Return the ACF field objects for this target.
		@since		2017-02-22 12:49:38
	**/
	public function get_field_objects( $target_id )
	{
		if ( $this->has_acf_pro() )
		{
			// Sometimes the cache prevents the values of fields from being returned.
			if ( function_exists( 'acf_disable_cache' ) )
				acf_disable_cache();
			// v5
			$fields = get_field_objects( $target_id, false );
		}
		else
			// v4
			$fields = get_field_objects( $target_id, [ 'format_value' => false ] );
		return $fields;
	}

	/**
		@brief		Check for the existence of ACF.
		@return		bool		True if ACF is alive and kicking. Else false.
		@since		20131030
	**/
	public function has_acf()
	{
		if ( function_exists( 'acf' ) )
			return true;

		return false;
	}

	/**
		@brief		Is ACF pro installed?
		@since		2015-11-04 10:06:02
	**/
	public function has_acf_pro()
	{
		return class_exists( 'acf_pro' );
	}

	/**
		@brief		Is this post ID a real integer?
		@since		2017-02-22 12:31:05
	**/
	public static function is_a_real_post( $post_id )
	{
		// Comparing an int with a string will convert the string to an int and therefore ... equal true.
		// So we compare the $post_id string with a string.
		return (string) intval( $post_id ) === (string) $post_id;
	}

	/**
		@brief		Add the ACF data storage collection to the bcd.
		@since		2016-07-22 15:03:59
	**/
	public function prepare_broadcasting_data( $broadcasting_data )
	{
		if ( isset( $broadcasting_data->acf ) )
			return;
		$broadcasting_data->acf = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Copy over the ACF field group fields.
		@since		2015-04-11 12:34:01
	**/
	public function restore_field_group( $bcd )
	{
		// Find the groups current fields.
		$old_fields = ThreeWP_Broadcast()->collection();
		$this->find_fields( $old_fields, $bcd->new_post( 'ID' ) );
		// And delete them all. This is not 100% efficient (only modified fields should be deleted) but since ACF field groups are broadcasted so rarely, it will do for the meantime.
		foreach( $old_fields->collection( 'fields' ) as $old_field )
			wp_delete_post( $old_field->ID, true );

		// Copy all of the fields to this new field group.
		$this->restore_field( $bcd, $bcd->acf_field_group, $bcd->post->ID, $bcd->new_post( 'ID' ) );
	}

	/**
		@brief		Restore an ACF v5 field.
		@since		2015-04-12 11:03:34
	**/
	public function restore_field( $bcd, $field_group, $old_field_id, $new_field_id )
	{
		// Find all fields with this old field as the parent
		foreach( $field_group->collection( 'fields' ) as $field )
		{
			if ( $field->post_parent !== $old_field_id )
				continue;
			$field_bcd = new broadcasting_data( $bcd );
			$field_bcd->custom_fields = true;
			$field_bcd->taxonomies = true;
			$field_bcd->link = false;
			$field_bcd->parent_blog_id = $bcd->parent_blog_id;
			$field_bcd->parent_post_id = $field->ID;
			$field_bcd->post = $field;
			$field_bcd->broadcast_data = ThreeWP_Broadcast()->get_post_broadcast_data( $bcd->parent_blog_id, $field->ID );

			// Broadcast only to this blog.
			$blog = new \threewp_broadcast\broadcast_data\blog;
			$blog->id = $bcd->current_child_blog_id;
			$field_bcd->broadcast_to( $blog );

			ThreeWP_Broadcast()->broadcast_post( $field_bcd );

			// The post parent is not broadcasted and needs to be set manually.
			wp_update_post( [
				'ID' => $field_bcd->new_post( 'ID' ),
				'post_parent' => $new_field_id,
			] );
			$this->debug( 'Restored field %s to %s', $field->ID, $field_bcd->new_post( 'ID' ) );
			$this->restore_field( $bcd, $field_group, $field->ID, $field_bcd->new_post( 'ID' ) );
		}
	}

	/**
		@brief		Restore a normal post's ACF info.
		@since		2015-04-11 12:33:35
	**/
	public function restore_normal_post( $bcd )
	{
		$this->debug( 'Restoring post fields...' );
		$this->restore_post_fields( $bcd );
	}

	/**
		@brief		Restore the ACF fields of a post.
		@since		2015-10-26 20:21:22
	**/
	public function restore_post_fields( $bcd, $target_id = null )
	{
		if ( $target_id === null )
			$target_id = $bcd->new_post( 'ID' );

		$this->debug( 'Restoring %s post fields for %s', count( $bcd->acf), $target_id );

		foreach( $bcd->acf as $acf_field )
		{
			if ( is_object( $bcd->custom_fields ) )
			{
				// If this field is protected and has content, skip it.
				if ( $this->field_is_listed( $acf_field, $bcd, 'protectlist' ) )
				{
					$exists = false;

					if ( static::is_a_real_post( $target_id ) )
						$exists = $bcd->custom_fields()->child_fields()->has( $acf_field->name );
					else
					{
						$exists = get_option( $target_id );
						$exists = count( $exists ) > 0;
					}

					if ( $exists )
					{
						$this->debug( 'The field %s exists in the protect list. Skipping.', $acf_field->name );
						continue;
					}
					else
						$this->debug( 'The field %s exists in the protect list but has no previous value. Restoring.', $acf_field->name );
				}
				else
					$this->debug( 'The field %s does not exist in the protect list.', $acf_field->name );
			}

			$this->debug( 'Restoring field %s which is a %s', $acf_field->name, $acf_field->type );
			$restore_field_action = new actions\restore_field();
			$restore_field_action->set_broadcasting_data( $bcd );
			$restore_field_action->set_field( $acf_field );
			$restore_field_action->set_post_id( $target_id );
			$restore_field_action->execute();
		}
	}

	/**
		@brief		Save the normal ACF data for the post.
		@since		2015-10-25 14:20:05
	**/
	public function save_post_fields( $bcd, $target_id = null )
	{
		if ( $target_id === null )
			$target_id = $bcd->post->ID;

		$fields = $this->get_field_objects( $target_id );

		if ( ! is_array( $fields ) )
			return;

		// This is the collection of ACF fields that might be saved into the BCD later.
		$bcd->acf = new acf\collection;

		$this->debug( 'The ACF fields are <pre>%s</pre>', var_export( $fields, true ) );

		$field_counter = 0;
		foreach( $fields as $field_index => $field )
		{
			if ( $field[ 'key' ] == '' )
				continue;

			$field[ 'field_index' ] = $field_counter;

			// Begin by trimming off all blacklisted custom fields that have their field keys specified.
			if ( $this->field_is_only_blacklisted( $field, $bcd ) )
			{
				$name = $field[ 'name' ];
				$this->debug( 'Deleting blacklisted custom field: %s (%s)', $name, $field[ 'key' ] );
				$cf = $bcd->custom_fields();
				$cf->forget( $name );
				$cf->forget( '_' . $name );
				continue;
			}

			$this->debug( 'Parsing field: %s %s %s',
				$field[ 'label' ],
				$field[ 'name' ],
				$field[ 'key' ]
			);
			$parse_action = new actions\parse_field();
			$parse_action->set_broadcasting_data( $bcd );
			$parse_action->set_field( $field );
			$parse_action->set_post_id( $target_id );
			$parse_action->set_storage( $bcd->acf );
			$parse_action->execute();

			$field_counter++;
		}

		if ( count( $bcd->acf ) < 1 )
			unset( $bcd->acf );
	}

	/**
		@brief		Save any field groups associated to the taxonomies used.
		@details	For V5.
		@since		2015-10-25 13:35:21
	**/
	public function save_taxonomy_fields( $bcd )
	{
		$bcd->acf_taxonomies = ThreeWP_Broadcast()->collection();

		foreach( $bcd->parent_post_taxonomies as $taxonomy => $terms )
		{
			$field_groups = acf_get_field_groups( [ 'taxonomy' => $taxonomy ] );

			if ( count( $field_groups ) < 1 )
				continue;

			foreach( $terms as $term )
			{
				$post_id = "{$taxonomy}_{$term->term_id}";

				$storage = $bcd->acf_taxonomies->collection( 'post_ids' )->collection( $post_id );
				$storage->set( 'taxonomy', $taxonomy );
				$storage->set( 'term', $term );

				$storage = $bcd->acf_taxonomies->collection( 'fields' )->collection( $post_id );

				foreach( $field_groups as $field_group )
				{
					$fields = acf_get_fields( $field_group );
					foreach( $fields as $field )
					{
						$parse_action = new actions\parse_field();
						$parse_action->set_broadcasting_data( $bcd );
						$parse_action->set_field( $field );
						$parse_action->set_post_id( $post_id );
						$parse_action->set_storage( $storage );
						$parse_action->execute();
					}
				}
			}
		}

		if ( count( $bcd->acf_taxonomies ) < 1 )
			unset( $bcd->acf_taxonomies );
	}

	/**
		@brief		Handle the broadcast of an ACF v5 field group.
		@since		2015-04-11 12:23:06
	**/
	public function start_field_group( $bcd )
	{
		$bcd->acf_field_group = ThreeWP_Broadcast()->collection();

		// Find all related fields recursively.
		$this->find_fields( $bcd->acf_field_group, $bcd->post->ID );

		// Save the locations.
		$content = $bcd->post->post_content;
		$content = maybe_unserialize( $content );

		// We need a lookup of post types.
		$post_types = get_post_types();

		$this->debug( 'The location data is: %s', $content[ 'location' ] );

		foreach( $content[ 'location' ] as $location_group_index => $location_group )
		{
			foreach( $location_group as $location_index => $location )
			{
				// Is this param a post type?
				if ( ! in_array( $location[ 'param' ], $post_types ) )
					continue;
				$location_bcd = ThreeWP_Broadcast()->get_post_broadcast_data( get_current_blog_id(), $location[ 'value' ] );

				// Is this value broadcasted?
				$bcd->acf_field_group->collection( 'location_values' )
					->set( $location[ 'value' ], $location_bcd );

				$this->debug( 'This location is a %s with a value of %s, which has the broadcast data %s', $location[ 'param' ], $location[ 'value' ], $location_bcd );
			}
		}
	}

	/**
		@brief		Handle the broadcast of a normal post.
		@since		2015-04-11 12:22:07
	**/
	public function start_normal_post( $bcd )
	{
		$this->save_post_fields( $bcd );
		if ( $this->has_acf_pro() )
			$this->save_taxonomy_fields( $bcd );
	}
}

}	// namespace

namespace
{
	/**
		@brief		Return the instance of the Broadcast ACF add-on.
		@since		2017-02-22 12:29:01
	**/
	function Broadcast_ACF()
	{
		return \threewp_broadcast\premium_pack\acf\ACF::instance();
	}
}
