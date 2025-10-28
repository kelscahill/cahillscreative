<?php

namespace threewp_broadcast\premium_pack\mailster;

/**
	@brief			Adds support for the <a href="https://codecanyon.net/item/mailster-email-newsletter-plugin-for-wordpress/3078294">Mailster</a> plugin.
	@plugin_group	3rd party compatability
	@since			2017-06-15 10:16:38
**/
class Mailster
	extends \threewp_broadcast\premium_pack\base
{
	/**
		@brief		Newsletter post type.
		@since		2017-07-06 22:05:43
	**/
	public static $newsletter_post_type = 'newsletter';

	/**
		@brief		Constructor.
		@since		2017-06-15 10:17:15
	**/
	public function _construct()
	{
		$this->add_action( 'broadcast_php_code_load_wizards' );
		$this->add_filter( 'threewp_broadcast_allowed_post_statuses' );
		$this->add_action( 'threewp_broadcast_broadcasting_started' );
		$this->add_filter( 'threewp_broadcast_broadcasting_before_restore_current_blog' );
		$this->add_action( 'threewp_broadcast_get_post_types' );
		$this->add_action( 'threewp_broadcast_prepare_meta_box' );
		new Forms();
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Callbacks
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Add a wizard for copying templates.
		@since		2020-11-27 17:33:18
	**/
	public function broadcast_php_code_load_wizards( $action )
	{
		$wizard = $action->new_wizard();
		$wizard->set( 'group', '3rdparty' );
		$wizard->set( 'id', 'mailster_copy_templates' );
		$wizard->set( 'label', __( "Mailster: Copy templates", 'threewp_broadcast' ) );
		$wizard->load_code_from_disk( __DIR__ . '/php_code/' );
		$action->add_wizard( $wizard );
	}

	/**
		@brief		threewp_broadcast_allowed_post_statuses
		@since		2017-06-15 10:17:47
	**/
	public function threewp_broadcast_allowed_post_statuses( $allowed_statuses )
	{
		$allowed_statuses[ 'paused' ] = 'paused';
		$allowed_statuses[ 'autoresponder' ] = 'autoresponder';
		return $allowed_statuses;
	}

	/**
		@brief		threewp_broadcast_broadcasting_before_restore_current_blog
		@since		2017-07-06 22:08:33
	**/
	public function threewp_broadcast_broadcasting_before_restore_current_blog( $action )
	{
		global $wpdb;
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( ! isset( $bcd->mailster ) )
			return;

		$equivalents = ThreeWP_Broadcast()->collection();

		$lists = $bcd->custom_fields()->get_single( '_mailster_lists' );
		$lists = maybe_unserialize( $lists );
		if ( is_array( $lists ) )
		{
			$new_lists = [];
			foreach( $lists as $old_list_id )
			{
				$new_list_id = $this->get_equivalent_list_id( $bcd, $old_list_id );;
				$new_lists []= $new_list_id;
				$equivalents->collection( 'lists' )->set( $old_list_id, $new_list_id );
			}
			// Update the custom field.
			$this->debug( 'Updating lists with %s', $new_lists );
			$bcd->custom_fields()->child_fields()
				->update_meta( '_mailster_lists', $new_lists );
		}

		$key = '_mailster_list_conditions';
		$conditions = $bcd->custom_fields()->get_single( $key );
		if ( $conditions )
		{
			$condition_groups = maybe_unserialize( $conditions );

			foreach( $condition_groups as $cg_index => $group )
			{
				foreach( $group as $g_index => $condition )
				{
					switch( $condition[ 'field' ] )
					{
						case '_lists__in':
							$values = $condition[ 'value' ];
							$new_values = [];
							foreach( $values as $old_list_id )
							{
								$new_list_id = $this->get_equivalent_list_id( $bcd, $old_list_id );;
								$new_values []= $new_list_id;
								$equivalents->collection( 'lists' )->set( $old_list_id, $new_list_id );
							}
							$condition_groups[ $cg_index ][ $g_index ][ 'value' ] = $new_values;
						break;
						case 'form':
							$old_form_id = $condition[ 'value' ];
							$new_form_id = $this->get_equivalent_form_id( $bcd, $old_form_id );
							$condition_groups[ $cg_index ][ $g_index ][ 'value' ] = $new_form_id;
							$equivalents->collection( 'forms' )->set( $old_form_id, $new_form_id );
						break;
					}
				}
			}

			$bcd->custom_fields()->child_fields()
				->update_meta( $key, $condition_groups );
		}

		$this->update_equivalents( $bcd, $equivalents );
	}

	/**
		@brief		threewp_broadcast_broadcasting_started
		@since		2017-07-06 22:05:02
	**/
	public function threewp_broadcast_broadcasting_started( $action )
	{
		$bcd = $action->broadcasting_data;		// Convenience.

		if ( $bcd->post->post_type != static::$newsletter_post_type )
			return;

		$this->prepare_bcd( $bcd );

		global $wpdb;

		// Save the lists.
		$query = sprintf( "SELECT * FROM `%smailster_lists`",
			$wpdb->prefix
		);
		$this->debug( $query );
		$lists = $wpdb->get_results( $query );
		$this->debug( 'Saving %d lists.', count( $lists ) );
		$bcd->mailster->set( 'lists', $lists );

		// Save the forms.
		$query = sprintf( "SELECT * FROM `%smailster_forms`",
			$wpdb->prefix
		);
		$this->debug( $query );
		$forms = $wpdb->get_results( $query );
		$this->debug( 'Saving %d forms.', count( $forms ) );
		$bcd->mailster->set( 'forms', $forms );

		$bcd->mailster->set( 'prefix', $wpdb->prefix );	// So we don't have to do another switch later.
	}

	/**
		@brief		threewp_broadcast_get_post_types
		@since		2017-06-15 10:31:02
	**/
	public function threewp_broadcast_get_post_types( $action )
	{
		$action->add_type( static::$newsletter_post_type );
	}

	/**
		@brief		Show the Broadcast meta box.
		@details	Due to Mailster's broken CSS, we have to force show the Broadcast meta box.
		@since		2020-10-22 16:20:05
	**/
	public function threewp_broadcast_prepare_meta_box( $action )
	{
		$meta_box_data = $action->meta_box_data;

		if ( $meta_box_data->post->post_type != static::$newsletter_post_type )
			return;

		$linked_parent = $meta_box_data->broadcast_data->get_linked_parent();
		// This is for parents only.
		if ( $linked_parent )
			return;

		$meta_box_data->html->insert_before( 'blogs', 'mailster_show_broadcast', '
			<script>
				setTimeout( function()
				{
					jQuery( "#threewp_broadcast.postbox" ).show();
				}, 500 );
			</script>' );
	}

	// --------------------------------------------------------------------------------------------
	// ----------------------------------------- Misc functions
	// --------------------------------------------------------------------------------------------

	/**
		@brief		Return the ID of the equivalent form on this blog.
		@since		2020-11-03 17:08:46
	**/
	public function get_equivalent_form_id( $bcd, $old_form_id )
	{
		global $wpdb;
		$forms = $bcd->mailster->get( 'forms' );
		if ( is_array( $forms ) )
		{
			$table = sprintf( '%smailster_forms', $wpdb->prefix );

			// Go through each form.
			// Find out if it exists. If not, create it.
			foreach( $forms as $form )
			{
				if ( $form->ID != $old_form_id )
					continue;

				// The name is the identifier.
				$query = sprintf( "SELECT `ID` FROM `%s` WHERE `name` = '%s'",
					$table,
					$form->name
				);
				$form_id = $wpdb->get_var( $query );

				if ( ! $form_id )
				{
					// Insert a new form.
					unset( $form->ID );
					$wpdb->insert( $table, (array) $form );
					$form_id = $wpdb->insert_id;
				}

				return $form_id;
			}
		}

		// How did this happen? The form should either have been found or created.
		return 0;
	}

	/**
		@brief		Return the ID of the equivalent list on this blog.
		@since		2020-11-03 17:01:26
	**/
	public function get_equivalent_list_id( $bcd, $old_list_id )
	{
		global $wpdb;
		$lists = $bcd->mailster->get( 'lists' );
		$table = sprintf( '%smailster_lists', $wpdb->prefix );

		// Go through each list.
		// Find out if it exists. If not, create it.
		foreach( $lists as $list )
		{
			if ( $list->ID != $old_list_id )
				continue;

			// The slug is the identifier.
			$query = sprintf( "SELECT `ID` FROM `%s` WHERE `slug` = '%s'",
				$table,
				$list->slug
			);
			$list_id = $wpdb->get_var( $query );

			if ( ! $list_id )
			{
				// Insert a new list.
				unset( $list->ID );
				$wpdb->insert( $table, (array) $list );
				$list_id = $wpdb->insert_id;
			}

			return $list_id;
		}

		// How did this happen? The list should either have been found or created.
		return 0;
	}

	/**
		@brief		Common method for preparing the bcd.
		@since		2017-07-06 22:07:49
	**/
	public function prepare_bcd( $bcd )
	{
		if ( ! isset( $bcd->mailster ) )
			$bcd->mailster = ThreeWP_Broadcast()->collection();
	}

	/**
		@brief		Update the equivalent lists / forms.
		@since		2020-11-11 18:30:49
	**/
	public function update_equivalents( $bcd, $equivalents )
	{
		global $wpdb;

		$old_prefix = $bcd->mailster->get( 'prefix' );
		$new_prefix = $wpdb->prefix;

		foreach( $equivalents as $type => $eqs )
		{
			switch( $type )
			{
				case 'forms':
					$old_table = sprintf( '%smailster_forms', $old_prefix );
					$new_table = sprintf( '%smailster_forms', $new_prefix );
				break;
				case 'lists':
					$old_table = sprintf( '%smailster_lists', $old_prefix );
					$new_table = sprintf( '%smailster_lists', $new_prefix );
				break;
			}

			foreach( $eqs as $old_id => $new_id )
			{
				// Fetch the old row.
				$query = sprintf( "SELECT * FROM `%s` WHERE `ID` = '%s'", $old_table, $old_id );
				$this->debug( $query );
				$row = $wpdb->get_row( $query );

				// We don't want the ID.
				unset( $row->ID );

				// Now update the current row.
				$this->debug( 'Updating new %s %s', $type, $new_id );
				$wpdb->update( $new_table, (array)$row, [ 'ID' => $new_id ] );
			}
		}
	}
}
